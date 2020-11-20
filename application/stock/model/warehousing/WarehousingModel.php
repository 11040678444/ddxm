<?php


namespace app\stock\model\warehousing;

use think\Db;
use think\Model;

/**
 * 直接入库-
 * Class SupplierModel
 * @package app\stock\model\supplier
 */
class WarehousingModel extends Model
{
    protected $table = 'ddxm_warehousing';

    //获取直接入库单列表
    public function getList($data){
        $limit = $data['limit']?$data['limit']:10;
        $page = $data['page']?$data['page']:1;
        $where = [];
        if( !empty($data['name']) ){
            $where[] = ['sn','like','%'.$data['name'].'%'];
        }
        if( !empty($data['status']) ){
            $where[] = ['status','=',$data['status']];
        }
        if( isset($data['transfer']) && $data['transfer'] != '' ){
            $where[] = ['transfer','=',$data['transfer']];
        }
        if( !empty($data['supplier_id']) ){
            $where[] = ['supplier_id','=',$data['supplier_id']];
        }
        if( !empty($data['shop_id']) ){
            $where[] = ['shop_id','=',$data['shop_id']];
        }
        if( !empty($data['start_time']) ){
            $where[] = ['create_time','>=',$data['start_time']];
        }
        if( !empty($data['end_time']) ){
            $where[] = ['create_time','<=',$data['end_time']];
        }
        $list = $this->where($where)->page($page,$limit)
            ->order('create_time desc')
            ->field('id,shop_id as shop_name,sn,supplier_id as supplier_name,user_id as user_name,quser_id as quser_name,status,status as status_name,store_time,amount,create_time,remark,transfer,transfer_amount')
            ->select()
            ->append(['item_list'])->toArray();
        $total =  $this->where($where)->count();
        $result = array("code" => 200, "count" => $total, "data" => $list);

        return $result;
    }

    //添加入库单
    public function add($data){
        if( empty($data['shop_id']) ){
            return json(['code'=>100,'msg'=>'请选择入库仓','data'=>'缺少入库仓ID']);
        }
        if( empty($data['supplier_id']) ){
            return json(['code'=>100,'msg'=>'请选择供应商']);
        }
        if( empty($data['item']) ){
            return json(['code'=>100,'msg'=>'未选择商品']);
        }
        $item = $data['item'];
        $warehousing_item_data = [];    //商品副表数据
        $purPrice_data = [];            //成本变化表
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';     //金额规则
        $reg = '/^\+?[1-9][0-9]*$/';      //正整数规则
        foreach ( $item as $k=>$v ){
            if( empty($v['item_id']) ){
                return json(['code'=>100,'msg'=>'商品ID为空']);
            }
            if( empty($v['item_name']) ){
                return json(['code'=>100,'msg'=>'商品名称为空']);
            }
            if( !preg_match($reg,$v['num']) ){
                return json(['code'=>100,'msg'=>'商品入库数量格式有误为空']);
            }
            if( !preg_match($rule,$v['price']) ){
                return json(['code'=>100,'msg'=>'商品入库单价格式有误为空']);
            }
            if( empty($v['bar_code']) ){
//                return json(['code'=>100,'msg'=>'商品条形码为空']);
            }
            $arr = [];
            $arr = [
                'price'     =>$v['price'],
                'num'     =>$v['num'],
                'all_price'     =>$v['num']*$v['price'],
                'item_id'     =>$v['item_id'],
                'item_name'     =>$v['item_name'],
                'bar_code'     =>$v['bar_code'],
                'attr_ids'     =>$v['attr_ids'],
                'attr_name'     =>$v['attr_name'],
            ];
            array_push($warehousing_item_data,$arr);

            $new_arr = [];
            $new_arr = [
                'shop_id'   =>$data['shop_id'],
                'type'   =>1,
                'item_id'   =>$v['item_id'],
                'md_price'   =>$v['price'],
                'store_cose'   =>$v['price'],
                'stock'   =>$v['num'],
                'time'   =>time(),
                'sort'   =>0,
                'attr_ids'   =>$v['attr_ids']
            ];
            array_push($purPrice_data,$new_arr);
        }
        $amount = 0;  //单据总金额
        foreach ( $warehousing_item_data as $k=>$v ){
            $amount += $v['all_price'];     //总金额
        }
        //直接入库单数据
        $warehousing =array(
            'shop_id'	=>$data['shop_id'],
            'sn'	=>'RK'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$data['shop_id'],
            'supplier_id'	=>$data['supplier_id'],
            'user_id'	=>$data['admin_id'],
            'status'	=>1,
            'store_time'	=>0,
            'amount'	=>$amount,
            'create_time'	=>time(),
            'remark'	=>$data['remarks'],
            'quser_id'	=>0
        );
        //开启事务
        Db::startTrans();
        try{
            //入库单
            $warehousingId = $this ->insertGetId($warehousing);
            foreach ( $warehousing_item_data as $k=>$v ){
                $warehousing_item_data[$k]['warehousing_id'] = $warehousingId;
            }
            //入库单商品
            Db::name('warehousing_item') ->insertAll($warehousing_item_data);
            foreach ( $warehousing_item_data as $k=>$v ){
                $where = [];
                $where['shop_id'] = $data['shop_id'];
                $where['item_id'] = $v['item_id'];
                if( !empty($v['attr_ids']) ){
                    $where['attr_ids'] = $v['attr_ids'];
                }
                $info = Db::name('shop_item')->where($where)->find();
                if( $info ){
                    Db::name('shop_item')->where($where)->setInc('stock',$v['num']);
                }else{
                    $where['stock'] = $v['num'];
                    $where['attr_name'] = $v['attr_name'];
                    Db::name('shop_item')->insert($where);
                }
            }
            foreach ( $purPrice_data as $k=>$v ){
                $purPrice_data[$k]['pd_id'] = $warehousingId;
            }
            Db::name('purchase_price') ->insertAll($purPrice_data);
            //提交事务
            Db::commit();
        }catch (\Exception $e){
            //事务回滚
            Db::rollback();
            return json(['code'=>500,'msg'=>'服务器内部出错,请联系管理人员','data'=>$e ->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'添加成功']);
    }


    //拼接商品信息
    public function getItemListAttr($val,$data){
        $item = Db::name('warehousing_item')
            ->field('id,item_name,attr_ids,attr_name,price,all_price,num,bar_code')
            ->where('warehousing_id',$data['id'])
            ->select();
        $item_name = [];
        $item_attr_ids = [];
        $item_attr_name = [];
        $item_price = [];
        $item_num = [];
        $item_all_price = [];
        $item_bar_code = [];
        foreach ($item as $key => $value) {
            array_push($item_name,$value['item_name']);
            array_push($item_attr_ids,$value['attr_ids']);
            array_push($item_attr_name,$value['attr_name']);
            array_push($item_price,$value['price']);
            array_push($item_all_price,$value['all_price']);
            array_push($item_num,$value['num']);
            array_push($item_bar_code,$value['bar_code']);
        }
        $result = [
            'item_name'    =>$item_name,
            'item_attr_ids'    =>$item_attr_ids,
            'item_attr_name'    =>$item_attr_name,
            'item_price'    =>$item_price,
            'item_num'    =>$item_num,
            'item_all_price'    =>$item_all_price,
            'item_bar_code'    =>$item_bar_code
        ];
        return $result;
    }
    //门店
    public function getShopNameAttr($val){
        if( $val == 0 ){
            return '门店错误';
        }
        return Db::name('shop')->where('id',$val)->value('name');
    }
    //供应商
    public function getSupplierNameAttr($val){
        if( $val == 0 ){
            return '供应商错误';
        }
        return Db::name('supplier')->where('id',$val)->value('supplier_name');
    }
    //入库人
    public function getUserNameAttr($val){
        if( $val == 0 ){
            return '入库人错误';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }
    //入库人
    public function getQuserNameAttr($val){
        if( $val == 0 ){
            return '';
        }
        return Db::name('admin')->where('userid',$val)->value('username');
    }
    //入库时间
    public function getCreateTimeAttr($val){
        if( $val == 0 ){
            return '';
        }
        return date('Y-m-d H:i:s',$val);
    }
    //取消入库时间
    public function getStoreTimeAttr($val){
        if( $val == 0 ){
            return '';
        }
        return date('Y-m-d H:i:s',$val);
    }
    //状态名称
    public function getStatusNameAttr($val){
        if( $val == 1 ){
            return '已入库';
        }
        return '已取消';
    }
}