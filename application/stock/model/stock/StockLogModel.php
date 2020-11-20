<?php

// +----------------------------------------------------------------------
// | 盘点单
// +----------------------------------------------------------------------
namespace app\stock\model\stock;

use think\Model;
use think\Db;

class StockLogModel extends Model
{
	protected $table = 'ddxm_stock_log';

    /***
     * 获取盘点单列表
     * @param $data
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($data){
        $page = !empty($data['page'])?$data['page']:1;
        $limit = !empty($data['limit'])?$data['limit']:10;
        $where = [];
        if( !empty($data['name']) ){
            $name = $data['name'];
            $where[] = ['order_sn','like',"%$name%"];
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['shop_id','=',$data['shop_id']];
        }
        if( !empty($data['status']) ){
            $where[] = ['status','=',$data['status']];
        }
        if( !empty($data['start_time'])  ){
            $where[] = ['create_time','>=',$data['start_time']];
        }
        if( !empty($data['end_time'])  ){
            $where[] = ['create_time','<=',$data['end_time']];
        }
        $list = $this
            ->where($where)
            ->page($page,$limit)
            ->field('id,order_sn,shop_id,shop_id as shop_name,user_id,create_time,status,status as status_name,remarks,end_time,is_admin')
            ->order('id desc')
            ->select()->toArray();

        foreach ($list as $key => $value) {
            if( $value['is_admin'] ){
                $list[$key]['user_id'] = Db::name('admin')->where('userid',$value['user_id'])->value('username');
            }else{
                $list[$key]['user_id'] = Db::name('shop_worker')->where('id',$value['user_id'])->value('name');
            }
            if( $value['end_time'] != 0 ){
                $list[$key]['time'] = $value['end_time'];
            }else{
                $list[$key]['time'] = $value['create_time'];
            }
        }
        $total =  $this->where($where)->count();
        return ['code'=>200,'msg'=>'获取成功','count'=>$total,'data'=>$list];
    }

    //添加盘点单
    public function add($data){
        if( empty($data['shop_id']) ){
            return json(['code'=>100,'msg'=>'请选择盘点仓','data'=>'缺少盘点shop_id']);
        }
        if( empty($data['item']) ){
            return json(['code'=>100,'msg'=>'未选择商品']);
        }
        $Unfinished = json_decode(self::isOk($data['shop_id']),true);
        if( $Unfinished['code'] != 200 ){
            return json($Unfinished);   //存在库存未确认的盘点单
        }
        $order_sn = 'PD'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$data['shop_id'];
        $log = array(
            'order_sn'	=>$order_sn,
            'shop_id'	=>$data['shop_id'],
            'user_id'	=>$data['user_id'],
            'create_time'=>time(),
            'status'	=>1,
            'remarks'	=>$data['remarks'],
            'is_admin'	=>$data['is_admin']
        );  //盘点单数据
        $item = $data['item'];
        // 启动事务
        Db::startTrans();
        try {
            $stockLogId = $this ->insertGetId($log);
            foreach ($item as $key => $value) {
                $item[$key]['log_id'] = $stockLogId;
            }
            Db::name('stock_log_item') ->insertAll($item);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>500,'msg'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'添加成功']);
    }

    //编辑盘点单
    public function edit($data){
        if( empty($data['shop_id']) ){
            return json(['code'=>100,'msg'=>'请选择盘点仓','data'=>'缺少盘点shop_id']);
        }
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择盘点单','data'=>'缺少盘点单ID']);
        }
        if( empty($data['item']) ){
            return json(['code'=>100,'msg'=>'未选择商品']);
        }
        $info = $this ->where('id',$data['id'])->find();
        if( $info['status'] != 1 ){
            return json(['code'=>'-20','msg'=>'已确认过的订单不能被编辑']);
        }
        $items = $data['item'];	//盘点单商品明细表数据
        $item = [];
        foreach ($items as $key => $value) {
            $arr = array(
                'log_id' =>$data['id'],
                'item_id'=>$value['item_id'],
                'item_title'=>$value['title'],
                'stock_now'=>$value['stock_now'],
                'stock_reality'=>$value['stock_reality'],
                'attr_ids'=>$value['attr_ids'],
                'attr_name'=>$value['attr_name'],
            );
            array_push($item,$arr);
        }
        $log = array(
            'shop_id'	=>$data['shop_id'],
            'remarks'	=>$data['remarks']?$data['remarks']:'',
        );  //盘点单数据
        // 启动事务
        Db::startTrans();
        try {
            $this ->where('id',$data['id'])->update($log);
            (new StockLogItemModel())->where('log_id',$data['id']) ->delete();
            (new StockLogItemModel()) ->insertAll($item);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>500,'msg'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'编辑成功']);
    }

    //确认盘点单
    public function confirm($data){
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择盘点单','data'=>'缺少盘点单ID']);
        }
        $info = $this ->where('id',$data['id'])->find();
        if( $info['status'] != 1 ){
            return json(['code'=>100,'msg'=>'此单已确认']);
        }
        $item = (new StockLogItemModel()) ->where('log_id',$data['id'])->select()->toArray();
        if( count($item) <= 0 ){
            return json(['code'=>100,'msg'=>'盘点单商品出错,请删除此盘点单重新操作']);
        }
        $win = [];	//盘盈数据
        $wen = [];	//盘亏数据
        foreach ($item as $key => $value) {
            $array = [];
            $array = array(
                'item_id'	=>$value['item_id'],
                'item'		=>$value['item_title'],
                'stock'		=>$value['stock_reality'],
                'num'		=>$value['stock_now'],
                'attr_ids'		=>$value['attr_ids'],
                'attr_name'		=>$value['attr_name'],
            );
            if( $value['stock_now'] > $value['stock_reality'] ){
                //生成盘盈单
                array_push($win, $array);
            }else if( $value['stock_now'] < $value['stock_reality'] ){
                //生成盘亏单
                array_push($wen, $array);
            }
        }
        $stock_log_data = [
            'status'    =>2,
            'remarks'   =>$data['remarks']?$data['remarks']:''
        ];  //盘点单数据
        if( (count($win)==0) && (count($wen)==0) ){
            $stock_log_data['status'] = 3;
            $stock_log_data['end_time'] = time();
        }
        // 启动事务
        Db::startTrans();
        try {
            $this ->where('id',$data['id']) ->update($stock_log_data);
            if( count($win) >0 ){
                //生成盘盈单
                $stockData = array(
                    'log_id'	=>$data['id'],
                    'order_sn'	=>'PY'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$info['shop_id'],
                    'shop_id'	=>$info['shop_id'],
                    'creator_id'=>$data['user_id'],
                    'is_admin'=>$data['is_admin'],
                    'type'	=>1,
                    'time'	=>time(),
                    'status'	=>1,
                    'remarks'	=>$stock_log_data['remarks']
                );
                $stockId = Db::name('stock')->insertGetId($stockData);
                foreach ($win as $key => $value) {
                    $win[$key]['stock_id'] = $stockId;
                }
                Db::name('stock_item')->insertAll($win);
            }
            if( count($wen) >0 ){
                //生成盘亏单
                $stockData1 = array(
                    'log_id'	=>$data['id'],
                    'order_sn'	=>'PK'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$info['shop_id'],
                    'shop_id'	=>$info['shop_id'],
                    'creator_id'=>$data['user_id'],
                    'is_admin'=>$data['is_admin'],
                    'type'	=>2,
                    'time'	=>time(),
                    'status'	=>1,
                    'remarks'	=>$stock_log_data['remarks']
                );
                $stockId1 = Db::name('stock')->insertGetId($stockData1);
                foreach ($wen as $key => $value) {
                    $wen[$key]['stock_id'] = $stockId1;
                }
                Db::name('stock_item')->insertAll($wen);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>500,'msg'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'确认成功']);
    }

    //删除盘点单
    public function delete_list($data){
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择盘点单','data'=>'缺少盘点单ID']);
        }
        // 启动事务
        Db::startTrans();
        try {
            $this ->where('id',$data['id'])->delete();
            (new StockLogItemModel())->where('log_id',$data['id']) ->delete();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>500,'msg'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'删除成功']);
    }


	//仓库
	public function getShopNameAttr($val){
		if( $val == 0 ){
			return '门店错误';
		}
		return Db::name('shop')->where('id',$val)->value('name');
	}
	//盘点时间
	public function getCreateTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
	//盘点时间
	public function getEndTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
	//盘点单状态
    public function getStatusNameAttr($val){
        $status = [
            1   =>'盘点待确认',
            2   =>'库存待确认',
            3   =>'已完成',
        ];
        return $status[$val];
    }
    //判断是否存在未完成的盘盈盘亏单
    public function isOk($shop_id){
        $tWhere = [];
        $tWhere[] = ['shop_id','=',$shop_id];
        $tWhere[] = ['status','neq',3];
        $sto = $this->where($tWhere)->select()->toArray();
        if( count($sto) >0 ){
            $res = ['code'=>100,'msg'=>'存在未确认库存单','data'=>$sto];
        }else{
            $res = ['code'=>200,'msg'=>'已全部确认','data'=>''];
        }
        return json_encode($res);
    }
}