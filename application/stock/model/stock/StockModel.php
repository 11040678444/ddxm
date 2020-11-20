<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\stock\model\stock;

use think\Model;
use think\Db;

class StockModel extends Model
{
	protected $table = 'ddxm_stock';
	//盘盈盘亏单列表
    public function check_list($data){
        $page = !empty($data['page'])?$data['page']:1;
        $limit = !empty($data['limit'])?$data['limit']:10;
        $where = [];
        if( !empty($data['type']) ){
            $where[] = ['a.type','=',$data['type']];
        }else{
            $where[] = ['a.type','=',1];
        }
        if( !empty($data['status']) ){
            $where[] = ['a.status','eq',$data['status']];
        }
        if( !empty($data['start_time']) ){
            $where[] = ['a.time','>=',$data['start_time']];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['a.end_time','<=',$data['end_time']];
        }
        if( !empty($data['name']) ){
            $where[] = ['a.order_sn','like','%'.$data['name'].'%'];
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['a.shop_id','eq',$data['shop_id']];
        }
        $list = $this
            ->alias('a')
            ->where($where)
            ->field('a.id,a.order_sn,a.creator_id,a.time create_time,a.status,a.status as status_name,a.shop_id,a.shop_id as shop_name,a.end_time,a.creator_id,a.is_admin')
            ->page($page,$limit)
            ->order('create_time desc')
            ->select();
        foreach ($list as $key => $val) {
            if( $val['end_time'] != 0 ){
                $list[$key]['time'] = $val['end_time'];
            }else{
                $list[$key]['time'] = $val['create_time'];
            }

            if( $val['is_admin'] == 1 ){
                $val['creator_id'] = Db::name('admin')->where('userid',$val['creator_id'])->value('username');
            }else{
                $val['creator_id'] = Db::name('shop_worker')->where('id',$val['creator_id'])->value('name');
            }
            unset($list[$key]['create_time']);
            unset($list[$key]['end_time']);
            unset($list[$key]['is_admin']);
        }
        $count = $this
            ->alias('a')
            ->where($where)
            ->join('admin b','a.creator_id=b.userid','LEFT')
            ->count();
        return ['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list];
    }

    //盘盈盘亏单确认库存
    //确认库存的操作
    public function check_confirm($data){
        if( empty($data['id']) ){
            return json(['code'=>0,'msg'=>'id参数错误','data'=>'']);
        }
        $StockItem = new StockItemModel();
        $info = $StockItem ->getItemList($data['id'])->select();	//商品列表
        $stocks = $this->where('id',$data['id'])->field('type,shop_id,log_id')->find(); //盘盈盘亏单详情
        $newArray = [];	//库存调配数据
        foreach ($info as $key => $value) {
            if( $value['stock']>$value['num'] ){
                $t = 2;	//1表示添加库存，反之表示减少库存
                $num = $value['stock']-$value['num'];
            }else{
                $t = 1;
                $num = $value['num']-$value['stock'];
            }
            $array = array(
                'type'	=>$t,	//1表示添加库存，反正表示减少库存
                'num'	=>$num,	//成本表需要增加或修改的数量
                'stock'	=>$value['num'],	//商品最终的数量
                'item_id'	=>$value['item_id'],
                'shop_id'	=>$stocks['shop_id'],
                'attr_ids'	=>$value['attr_ids'],
                'attr_name'	=>$value['attr_name'],
            );
            array_push($newArray, $array);  //库存调配数据
        }
        $stockData = [];    //盘盈盘亏修改的数据
        $stockData = [
            'end_time'  =>time(),
            'status'  =>2,
            'remarks'  =>$data['remarks']?$data['remarks']:'',
        ];
        // 启动事务
        Db::startTrans();
        try {
            //修改备注，修改状态
            $this ->where('id',$data['id'])->update($stockData);
            //修改库存表
            foreach ($newArray as $key => $value) {
                $siWhere = [];
                $siWhere['shop_id'] = $value['shop_id'];
                $siWhere['item_id'] = $value['item_id'];
                $siWhere['attr_ids'] = $value['attr_ids'];
                $siList = Db::name('shop_item')->where($siWhere)->find();
                if( $siList ){
                    Db::name('shop_item')->where($siWhere)->setField('stock',$value['stock']);
                    //判断是否存在多的库存数据数据，如果有多的则删除
                    $emp = [];
                    $emp[] = ['shop_id','eq',$value['shop_id']];
                    $emp[] = ['item_id','eq',$value['item_id']];
                    $emp[] = ['id','neq',$siList['id']];
                    Db::name('shop_item') ->where($emp) ->delete();
                }else{
                    $arr = [];
                    $arr = [
                        'shop_id'   =>$value['shop_id'],
                        'item_id'   =>$value['item_id'],
                        'stock'   =>$value['stock'],
                        'stock_ice'   =>0,
                        'attr_ids'   =>$value['attr_ids'],
                        'attr_name'   =>$value['attr_name'],
                    ];
                    Db::name('shop_item')->insert($arr);
                }
            }
            //修改成本表
            foreach ($newArray as $key => $value) {
                $ppWhere = [];
                $ppWhere[] = ['shop_id','eq',$value['shop_id']];
                $ppWhere[] = ['item_id','eq',$value['item_id']];
                $ppWhere[] = ['attr_ids','eq',$value['attr_ids']];
                if( $value['type'] == 1 ){
                    //表示需要添加库存，则添加最后一条
                    $ppList = Db::name('purchase_price')->where($ppWhere)->order('time desc')->find();
                    $arr = [];
                    $arr = [
                        'shop_id'   =>$value['shop_id'],
                        'type'   =>3,
                        'pd_id'   =>$data['id'],
                        'item_id'   =>$value['item_id'],
//                        'md_price'   =>-1,
//                        'store_cose'   =>-1,
                        'stock'   =>$value['num'],
                        'time'   =>time(),
                        'sort'   =>0,
                        'attr_ids'   =>$value['attr_ids'],
                    ];
                    if( $ppList ){
                        $arr['md_price'] = $ppList['store_cose'];
                        $arr['store_cose'] = $ppList['store_cose'];
                    }else{
                        $arr['md_price'] = -1;      //盘盈入库未找到之前的成本，设置为-1方便以后查找
                        $arr['store_cose'] = -1;
                    }
                    Db::name('purchase_price') ->insert($arr);  //盘盈入库
                }else{
                    //表示需要减少库存，则减少库存不为0的第一条
                    $ppWhere[] = ['stock','>=',0];
                    $tt = $this ->getCostPrice($ppWhere,$value['num']);
                    if( count($tt) >0 ){
                        foreach ($tt as $k => $v) {
                            Db::name('purchase_price') ->where('id',$v['id'])->setDec('stock',$v['num']);
                        }
                    }
                }
            }

            //查看此盘点单是否全部确认库存完成
            $stockLogCount = $this->where('log_id',$stocks['log_id'])->where('status',1)->count();//待确认最多两条记录
            if( $stockLogCount == 0 ){
                //表示都已确认入库
                Db::name('stock_log')->where('id',$stocks['log_id'])->update(['status'=>3,'end_time'=>time()]);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>500,'msg'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'确认库存成功']);
    }

    public function getCostPrice($where,$num){
        $list = Db::name('purchase_price')->where($where)->field('id,stock')->order('time asc')->select();
        $new = [];	//需要修改的数据
        foreach ($list as $key => $value) {
            if( $num >$value['stock'] ){
                $array = array(
                    'id'	=>$value['id'],
                    'num'	=>$value['stock']
                );
                array_push($new, $array);
                $num  = $num - $value['stock'];
            }else{
                $array = array(
                    'id'	=>$value['id'],
                    'num'	=>$num
                );
                array_push($new, $array);
                break;
            }
        }
        return $new;
    }
	//仓库
	public function getShopNameAttr($val){
		if( $val == 0 ){
			return '门店错误';
		}
		return Db::name('shop')->where('id',$val)->value('name');
	}
	//状态
    public function getStatusNameAttr($val){
        if( $val == 1 ){
            return '待确认';
        }
        return '已确认';
    }
	//盘点时间
	public function getTimeAttr($val){
		if( $val == 0 ){
			return '时间错误';
		}
		return date('Y-m-d H:i:s',$val);
	}
}