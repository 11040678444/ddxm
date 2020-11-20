<?php

namespace app\stock\controller;

use app\common\controller\Backendbase;
use app\stock\model\purchase\PurchaseModel;
use app\stock\model\supplier\SupplierModel;
use think\Db;
use think\Query;
/**
 * 采购管理模块
 */
class Purchase extends Backendbase
{

    //  采购单 列表
    public function getList()
    {
        $purchase = new PurchaseModel();
        $res = input('post.');
            $where = [];
            if (isset($res['supplier_id']) && !empty($res['supplier_id'])) {
                $where[] = ['p.supplier_id', "=", $res['supplier_id']];
            }
            if (isset($res['status']) && !empty($res['status'])) {
                $where[] = ['p.status', "=", $res['status']];
            }
            if (isset($res['start_time']) && !empty($res['start_time'])) {
//                $start_time = strtotime($res['start_time']);
                $where[] = ['p.create_time', ">", $res['start_time']];
            }
            if (isset($res['end_time']) && !empty($res['end_time'])) {
//                $end_time = strtotime($res['end_time']) + 86399;
                $where[] = ['p.create_time', "<", $res['end_time']];
            }
            if (isset($res['search']) && !empty($res['search'])) {
                $where[] = ['p.sn', "like", "%{$res['search']}%"];
            }

            $page =input('page',1);
            $limit =input('limit',10);

            $where[]=['p.status','neq',6];

            $list = $purchase->getList($where,$page,$limit);
            $listCount = $purchase->getListCount($where);
            $newList =[];

            foreach ($list as $k=>$v){

                $newV['id'] =$v['id'];
                $newV['sn'] =$v['sn'];
                $newV['supplier_name'] =$v['supplier_name'];
                $newV['status'] =$v['status'];
                $newV['purchase_admin_name'] =$v['purchase_admin_name'];

                $item=$v['item'];

                $item_name =[];
                $cg_amount =[];
//                $md_amount =[];
                $remarks =[];
                $y_num =[];
                $num =[];
                $item_code =[];
                $amount =[];
                $attr_name =[];
                foreach ($item as $h=>$v2){
                    $item_name[$h]=$v2['item_name'];
                    $cg_amount[$h]=$v2['cg_amount'];
//                    $md_amount[$h]=$v2['md_amount'];
                    $remarks[$h]=$v2['remarks'];
                    $num[$h]=$v2['num'];
                    $y_num[$h]=$v2['num']-$v2['s_num'];
                    $item_code[$h]=$v2['item_code'];
                    $attr_name[$h]=$v2['attr_name'];
                    $amount[$h] = $v2['cg_amount'] * $v2['num'];
                }
                $newV['item_name']=$item_name;
                $newV['attr_name']=$attr_name;
                $newV['cg_amount']=$cg_amount;
//                $newV['md_amount']=$md_amount;
                $newV['remarks']=$remarks;
                $newV['y_num']=$y_num;
                $newV['num']=$num;
                $newV['item_code']=$cg_amount;
                $newV['amount']=$amount;

                $newList[$k]=$newV;
            }
            return json(["code" => 200, "count" => $listCount, "data" => $newList]);

    }

    //新增采购单
    public function add(){
        $res = $this->request->post();

        $supplier_id = $res['supplier_id'];
        if(empty($supplier_id)){
            return json(["code" => -1, "msg" => '请先选择供应商', "data" => '']);
        }
        $SupplierModel  = new SupplierModel();
        $supplier = $SupplierModel->findId($supplier_id);
        if($supplier == false){
            return json(["code" => -1, "msg" => '供应商不存在！', "data" => '']);
        }

        $jsonlist = $res['jsonlist'];
        if(empty($jsonlist)){
            return json(["code" => -1, "msg" => '请先选择商品！', "data" => '']);
        }

        $jsonData = json_decode($jsonlist,true);
        if(is_array($jsonData)){
            return json(["code" => -1, "msg" => 'jsonlist 格式不正确，请核对！', "data" => '']);
        }

        $purchase = new PurchaseModel();
        $count = $purchase->getListCount();

        $purchase=[
            "sn"=>"CG".date("Ymd").sprintf("%05d",$count),// CG 采购 年月日 总数填充0 5位
            "supplier_id"=>$supplier_id,
            "supplier_name"=>$supplier['supplier_name'],
            "purchase_admin_id" => $this->getUserInfo()['userid'],
            "purchase_admin_name" => $this->getUserInfo()['nickname'],
            "status"=>1,
            "create_time"=>time(),
            "remark"=>$res['remark'],//备注
        ];

        $purchase_db = db::name("purchase");
        $purchase_db->startTrans();
        try{

            $newId = $purchase_db->insertGetId($purchase);

//        [{"cg_amount": "100","num": 10,"item_id": 290,"attr_ids": "11 _12"}]
            $purchase_item_list = [];
            foreach ($jsonData as  $k=>$v){

                $cg_amount=$v['cg_amount'];
                $num=$v['num'];
                $data=[
                    'purchase_id'=>$newId,
                    'cg_amount'=>$cg_amount,//采购金额
                    'md_amount'=>$cg_amount,
                    'remarks'=>$v['remarks'],//备注
                    's_num'=>$num,//采购数量
                    'num'=>$num,
                    'item_id'=>$v['id'],//商品ID
                    'item_name'=>$v['title'],//商品名字
                    'item_code'=>$v['bar_code'],//商品条形码
                    'attr_ids'=>$v['key'],//规格组ID
                    'attr_name'=>$v['key_name'],//规格组 名字
                ];
                $purchase_item_list[$k]=$data;
            }

            $result = db::name("purchase_item")->insertAll($purchase_item_list);
            if(!$result){
                $purchase_db->rollback();
                return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
            }
            $purchase_db->commit();
            return json(['code'=>200,"msg"=>"",'data'=>"添加成功！"]);
        }catch (\Exception $exception){
            $purchase_db->rollback();
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }

    }

    //删除采购单
    public function del(){
        $res = $this->request->post();

        $purchase_id = $res['id'];
        if(empty($purchase_id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $SupplierModel  = new SupplierModel();
        return $SupplierModel->delAd($purchase_id);
    }

    //根据ID      查找采购单详情
    public function findId(){
        $purchase = new PurchaseModel();
        $id = input('id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }

        $v= $purchase->findId($id);
        if($v == false){
            return json(["code" => -1, "msg" => '采购单不存在！', "data" => '']);
        }
        return json(["code" => 200, "msg" => '查询成功！', "data" =>$v]);
    }
    public function findId2(){
        $purchase = new PurchaseModel();
        $id = input('id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }

        $v= $purchase->findId($id);
        if($v == false){
            return json(["code" => -1, "msg" => '采购单不存在！', "data" => '']);
        }
        $newV['id'] =$v['id'];
        $newV['sn'] =$v['sn'];

        $newV['supplier_name'] =$v['supplier_name'];
        $newV['status'] =$v['status'];
        $newV['purchase_admin_name'] =$v['purchase_admin_name'];

            $item=$v['item'];

            $item_name =[];
            $cg_amount =[];
//                $md_amount =[];
            $remarks =[];
            $y_num =[];
            $num =[];
            $item_code =[];
            $amount =[];
            $attr_name =[];
            $item_id =[];
            $purchase_item_id =[];
            foreach ($item as $h=>$v2){
                $item_id[$h]=$v2['item_id'];
                $item_name[$h]=$v2['item_name'];
                $purchase_item_id[$h]=$v2['purchase_item_id'];
                $cg_amount[$h]=$v2['cg_amount'];
//                    $md_amount[$h]=$v2['md_amount'];
                $remarks[$h]=$v2['remarks'];
                $num[$h]=$v2['num'];
                $y_num[$h]=$v2['num']-$v2['s_num'];
                $item_code[$h]=$v2['item_code'];
                $attr_name[$h]=$v2['attr_name'];
                $amount[$h] = $v2['cg_amount'] * $v2['num'];
            }
            $newV['item_id']=$item_id;
            $newV['item_name']=$item_name;
            $newV['purchase_item_id']=$purchase_item_id;
            $newV['attr_name']=$attr_name;
            $newV['cg_amount']=$cg_amount;
//                $newV['md_amount']=$md_amount;
            $newV['remarks']=$remarks;
            $newV['y_num']=$y_num;
            $newV['num']=$num;
            $newV['item_code']=$cg_amount;
            $newV['amount']=$amount;


        return json(["code" => 200, "msg" => '查询成功！', "data" =>$newV]);
    }

    //编辑采购单
    public function update(){

        $id = input('id');
        $jsonlist = input('jsonlist');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }

        $supplier_id = input('supplier_id');
        if(empty($supplier_id)){
            return json(["code" => -1, "msg" => '请先选择供应商', "data" => '']);
        }
        $SupplierModel  = new SupplierModel();
        $supplier = $SupplierModel->findId($supplier_id);
        if($supplier == false){
            return json(["code" => -1, "msg" => '供应商不存在！', "data" => '']);
        }

        if(empty($jsonlist)){
            return json(["code" => -1, "msg" => '请先选择商品！', "data" => '']);
        }

        $jsonData = json_decode($jsonlist,true);
        if(is_array($jsonData)){
            return json(["code" => -1, "msg" => 'jsonlist 格式不正确，请核对！', "data" => '']);
        }

        $purchase=[
            "supplier_id"=>$supplier_id,
            "supplier_name"=>$supplier['supplier_name'],
            "purchase_admin_id" =>  $this->getUserInfo()['userid'],
            "purchase_admin_name" => $this->getUserInfo()['nickname'],
            "create_time"=>time(),
            "remark"=>input('remark'),//备注
        ];

        $purchase_db = db::name("purchase");
        $purchase_db->startTrans();
        try{

            $newId = $purchase_db->where('id',$id)->update($purchase);
            if($newId == false){
                $purchase_db->rollback();
                return json(['code'=>"-1","msg"=>"采购单添加失败",'data'=>""]);
            }

//        [{"cg_amount": "100","num": 10,"item_id": 290,"attr_ids": "11 _12"}]
            $purchase_item_list = [];
            foreach ($jsonData as  $k=>$v){

                $cg_amount=$v['cg_amount'];
                $num=$v['num'];
                $data=[
                    'purchase_id'=>$id,
                    'cg_amount'=>$cg_amount,//采购金额
                    'md_amount'=>$cg_amount,
                    'remarks'=>$v['remarks'],//备注
                    's_num'=>$num,//采购数量
                    'num'=>$num,
                    'item_id'=>$v['id'],//商品ID
                    'item_name'=>$v['title'],//商品名字
                    'item_code'=>$v['bar_code'],//商品条形码
                    'attr_ids'=>$v['key'],//规格组ID
                    'attr_name'=>$v['key_name'],//规格组 名字
                ];
                $purchase_item_list[$k]=$data;
            }

            db::name("purchase_item")->where('purchase_id',$id)->delete();
            $result = db::name("purchase_item")->insertAll($purchase_item_list);
            if(!$result){

                $purchase_db->rollback();
                return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
            }
            $purchase_db->commit();
            return json(['code'=>200,"msg"=>"",'data'=>"添加成功！"]);
        }catch (\Exception $exception){
            $purchase_db->rollback();
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }

    }


    // 关闭采购单
    public function purchase_close(){
        $res = $this->request->post();
        $purchase['status'] = 4;
        $result = Db::name("purchase")->where("id",intval($res['id']))->update($purchase);
        if($result){
            return json(["code" => 200, "msg" => '关闭成功！', "data" => '']);
        }else{
            return json(["code" => -1, "msg" => '关闭失败！', "data" => '']);
        }
    }

    // 商品入库
    public function storage_item(){

        $id = input('id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }

        $status = Db::name("purchase")->where("id",$id)->value("status");
        if($status!=1 || $status!=2){
            return json(["code" => -1, "msg" => '操作失败！', "data" => '']);
        }
        $jsonlist = input('jsonlist');
        if(empty($jsonlist)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $jsonData = json_decode($jsonlist,true);
        if(is_array($jsonData)){
            return json(["code" => -1, "msg" => 'jsonlist 格式不正确，请核对！', "data" => '']);
        }
        $purchase = new PurchaseModel();
        $purchase = $purchase->findIdUp($id);
        if($purchase == false){
            return json(["code" => -1, "msg" => '采购单不存在，请核对！', "data" => '']);
        }


        $purchase_item_db = db::name("purchase_item");
        $purchase_item_db->startTrans();
        try {
            $purchase_store_list = [];
            $purchase_price_list = [];
            foreach ($jsonData as $k => $v) {

                $purchase_item_id = $v['purchase_item_id'];
                $c_num = $v['c_num'];
                if (empty($purchase_item_id) || empty($c_num)) {
                    return json(["code" => -1, "msg" => '参数错误', "data" => '']);
                }

                //判断当前库存是否大于 入库 库存
                $s_num = $purchase_item_db->where("id", $purchase_item_id)->value("s_num");
                if ($s_num == false) {
                    return json(["code" => -1, "msg" => '当前采购单不存在，请核对！', "data" => '']);
                }
                if ($c_num > $s_num) {
                    return json(["code" => -1, "msg" => '当前采购单大于剩余数量，请核对！', "data" => '']);
                }
                if(empty($v['item_id'])){
                    return json(["code" => -1, "msg" => 'item_id 参数错误！', "data" => '']);
                }

                $num = $s_num - $c_num;// 入库数量

                $purchase_item_db->where("id", $purchase_item_id)->setDec("s_num", $num);
                $purchase_store = [
                    "pd_id" => $purchase_item_id,
                    "num" => $num,
                    "purchase_id" => $id,
                    "shop_id" => $purchase['shop_id'],
                    "cg_amount" => $purchase['amount'],
                    "md_amount" => $purchase['amount'],
                    "item_id" => $v['item_id'],
                    "item_name" => $v['item_name'],
                    "item_code" => $v["item_code"],
                    "attr_ids" => $v["attr_ids"],
                    "attr_name" => $v["attr_name"],
                "operator_id"=> $this->getUserInfo()['userid'],
                "operator"=> $this->getUserInfo()['nickname'],
                    "time" => time(),
                ];
                $purchase_store_list[$k] = $purchase_store;

                $purchase_price = [
                    "shop_id" => $purchase['shop_id'],
                    "type" => 1,
                    "pd_id" => $purchase_item_id,
                    "item_id" => $v['item_id'],
                    "md_price" => $purchase['amount'],
                    "store_cose" => $purchase['amount'],
                    "attr_ids" => $v["attr_ids"],
                    "stock" => $num,
                    "time" => time(),
                ];

                $purchase_price_list[$k] = $purchase_price;

                $shop_item = db::name("shop_item")->where("shop_id",$purchase['shop_id'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->find();
                if($shop_item){
                    db::name("shop_item")->where("shop_id",$purchase['shop_id'])
                        ->where("item_id",$v['item_id'])
                        ->where("attr_ids",$v['attr_ids'])
                        ->setInc("stock",$num);
                }else{
                    $shop_item =[
                        "shop_id"=>$purchase['shop_id'],
                        "item_id"=>$v['item_id'],
                        "attr_ids"=>$v['attr_ids'],
                        "attr_name" => $v["attr_name"],
                        "stock"=>$num,
                    ];
                    db::name("shop_item")->insert($shop_item);
                }
            }

            db::name("purchase_store")->insertAll($purchase_store_list);
            db::name("purchase_price")->insertAll($purchase_price_list);

            $number = Db::name("purchase_item")->where("purchase_id",$id)->where("s_num",0)->count();
            $count = Db::name("purchase_item")->where("purchase_id",$id)->count();


                if($number ==$count){
                    db::name("purchase")->where("id",$id)->update(['status'=>3]);
                }else{
                    db::name("purchase")->where("id",$id)->update(['status'=>2]);
                }

            $purchase_item_db->commit();
            return json(['code'=>200,"msg"=>"入库成功！",'data'=>""]);

        }catch (\Exception $exception){
            $purchase_item_db->rollback();
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }

    }

    //入库记录
    public function store_item(){
        $id  = input('id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $purchase = new PurchaseModel();
        $v= $purchase->findId($id);
        if($v == false){
            return json(["code" => -1, "msg" => '采购单不存在！', "data" => '']);
        }

        $data = Db::name("purchase_store")
            ->alias("a")
            ->where("a.purchase_id",$id)
            ->field("a.id,a.pd_id,a.num,a.shop_id,a.item_name,a.item_id,a.operator,a.time,s.name as shop_name,p.num as c_num,a.attr_name")
            ->withAttr("time",function($value,$data){
                return date("Y-m-d H:i:s",$data['time']);
            })
            ->join("shop s","a.shop_id = s.id")
            ->join("purchase_item p","a.pd_id = p.id")
            ->select();
        $v['item_log']=$data;
        return json(["code"=>200,"msg"=>'查询成功！',"data"=>$v]);
    }

    // 反入库
    public function refund_storage(){
        $id = input('id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }

        $admin_id =  $this->getUserInfo()['userid'];
        $admin_nickname =  $this->getUserInfo()['nickname'];

        $purchase = new PurchaseModel();
        $data = $purchase->refund_storage($id,$admin_id,$admin_nickname);
        return $data;
    }
}
