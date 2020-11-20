<?php

namespace app\stock\controller;

use app\common\controller\Backendbase;
use app\stock\model\allot\AllotModel;
use think\Db;
use think\Query;
/**
 * 调拨单
 */
class Allot extends Backendbase
{

    //调拨单--列表查询
    public function getList(){

        $res = input('post.');

        $page =input('page',1);
        $limit =input('limit',10);

            $allot = new AllotModel();

            //调出仓库
            if(isset($res['out_shop']) && !empty($res['out_shop'])){
                $where[] = ['out_shop','=',intval($res['out_shop'])];
            }
            // 调入仓库
            if(isset($res['in_shop']) && !empty($res['in_shop'])){
                $where[] = ['in_shop','=',intval($res['in_shop'])];
            }
            // 状态
            if(isset($res['status']) && !empty($res['status'])){
                $where[] = ['status','=',intval($res['status'])];
            }

            // 开始时间  strtotime(($data['end_time'].'23:59:59')
            if(isset($res['time']) && !empty($res['time'])){
                $where[] = ['create_time','>=',strtotime($res['time'])];
            }

            if(isset($res['end_time']) && !empty($res['end_time'])){
                $where[] = ['create_time','<=',strtotime($res['end_time'])];
            }
            //搜索框
//            if(isset($res['search']) && !empty($res['search'])){
//                $where[] = ['sn',"like","%{$res['search']}%"];
//            }
            $where[] = ['del','=',"1"];

            $list = $allot->getList($where,$page,$limit);

        $listCount = $allot->getListCount($where);
        $newList =[];

        foreach ($list as $k=>$v){

            $newV['id'] =$v['id'];
            $newV['sn'] =$v['sn'];
            $newV['out_admin_user'] =$v['out_admin_user'];
            $newV['out_time'] =$v['out_time'];
            $newV['in_admin_user'] =$v['in_admin_user'];
            $newV['in_time'] =$v['in_time'];
            $newV['status'] =$v['status'];
            $newV['remark'] =$v['remark'];
            $newV['create_time'] =$v['create_time'];
            $newV['creator'] =$v['creator'];
            $newV['out_shop_name'] =$v['out_shop_name'];
            $newV['in_shop_name'] =$v['in_shop_name'];

            $item=$v['item'];

            $item_name =[];
            $cg_amount =[];
//                $md_amount =[];
            $remarks =[];
            $num =[];
            $barcode =[];
            $attr_name =[];
            foreach ($item as $h=>$v2){
                $item_name[$h]=$v2['item_name'];
//                    $md_amount[$h]=$v2['md_amount'];
                $remarks[$h]=$v2['remark'];
                $num[$h]=$v2['num'];
                $barcode[$h]=$v2['barcode'];
                $attr_name[$h]=$v2['attr_name'];
            }
            $newV['item_name']=$item_name;
            $newV['attr_name']=$attr_name;
//                $newV['md_amount']=$md_amount;
            $newV['remarks']=$remarks;
            $newV['num']=$num;
            $newV['item_code']=$barcode;

            $newList[$k]=$newV;
        }
        return json(["code" => 200, "count" => $listCount, "data" => $newList]);


//            $count = 0;
//            if(isset($res['search']) && !empty($res['search'])){
//
//                $name = $res['search'];
//                $count = Db::name("allot")->where($where)
//                    ->where('id', 'IN', function($query) use (&$name) {
//                        $query->table('ddxm_allot_item')->whereOr('barcode','like','%'.$name.'%')
//                            ->whereOr('item','like','%'.$name.'%')->field('allot_id');
//                    })
//                    ->count();
//            }else{
//                $count = Db::name("allot")->where($where)->count();
//            }
//            return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }

    //删除调拨单
    public function del(){
        $id = input('id');
        $allot = new AllotModel();
        return$allot->del($id);
    }

    //新增调拨单
    public function allot_add(){

        $res = input('post.');
        $in_shop_id  = $res['in_shop_id'];  //调入仓库ID
        $out_shop_id  = $res['out_shop_id'];// 调出仓库ID

        if($in_shop_id =='' || $out_shop_id ==''){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        if($in_shop_id == $out_shop_id){
            return json(["code" => -1, "msg" => '调出仓库 不能够和调入仓库一致', "data" => '']);
        }

        $jsonlist = $res['jsonlist'];
        if(empty($jsonlist)){
            return json(["code" => -1, "msg" => '请先选择商品！', "data" => '']);
        }

        $jsonData = json_decode($jsonlist,true);
        if(is_array($jsonData)){
            return json(["code" => -1, "msg" => 'jsonlist 格式不正确，请核对！', "data" => '']);
        }

        $purchase = new AllotModel();
        $count = $purchase->getListCount();

        $allot = [
            "sn" => "DB".date("Ymd").sprintf("%05d",$count+1),
            "out_shop" => $out_shop_id,
            "out_admin_id"=>0,
            "out_admin_user"=>"",
            "in_shop"=>$in_shop_id,
            "in_admin_id"=>0,
            "in_admin_user"=>"",
            "status"=>0,
            "remark" =>$res['remarks'],
            "create_time"=>time(),
            "type"=>1,
            "creator"=> $this->getUserInfo()['nickname'],
            "creator_id"=>$this->getUserInfo()['userid']
            /*"amount"=>*/
        ];

        $allot_db = db::name("allot");
        $allot_db->startTrans();
        try{

            $newId = $allot_db->insertGetId($allot);

//        [{"id": 11,"num": 10,"title": "我是商品名字","attr_ids": "11 _12","attr_name": "我是规格组名字","barcode": "我是条形码"}]
            $purchase_item_list = [];
            foreach ($jsonData as  $k=>$v){

                $data =[
                    "allot_id"=>$newId,
                    "item_id"=>$v['id'],
                    "item"=>$v['title'],
                    "num"=>$v['num'],
                    "remark"=>$v['remake'],
                    "barcode"=>$v['bar_code'],
                    'attr_ids'=>$v['key'],//规格组ID
                    'attr_name'=>$v['key_name'],//规格组 名字
                ];

                $purchase_item_list[$k]=$data;
            }

            $result = db::name("allot_item")->insertAll($purchase_item_list);
            if(!$result){
                $allot_db->rollback();
                return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
            }
            $allot_db->commit();
            return json(['code'=>200,"msg"=>"",'data'=>"添加成功！"]);
        }catch (\Exception $exception){
            $allot_db->rollback();
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }
    }

    //编辑调拨单
    public function allot_up(){

        $res = input('post.');
        $in_shop_id  = $res['in_shop_id'];  //调入仓库ID
        $out_shop_id  = $res['out_shop_id'];// 调出仓库ID
        $id  = $res['id'];// 调出仓库ID

        if($in_shop_id ==''|| $out_shop_id ==''|| $id ==''){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        if($in_shop_id == $out_shop_id){
            return json(["code" => -1, "msg" => '调出仓库 不能够和调入仓库一致', "data" => '']);
        }

        $jsonlist = $res['jsonlist'];
        if(empty($jsonlist)){
            return json(["code" => -1, "msg" => '请先选择商品！', "data" => '']);
        }

        $jsonData = json_decode($jsonlist,true);
        if(is_array($jsonData)){
            return json(["code" => -1, "msg" => 'jsonlist 格式不正确，请核对！', "data" => '']);
        }
        $allot = [
            "out_shop" => $out_shop_id,
            "out_admin_id"=>0,
            "out_admin_user"=>"",
            "in_shop"=>$in_shop_id,
            "in_admin_id"=>0,
            "in_admin_user"=>"",
            "remark" =>$res['remarks'],
            "creator"=> $this->getUserInfo()['nickname'],
            "creator_id"=>$this->getUserInfo()['userid']
            /*"amount"=>*/
        ];

        $allot_db = db::name("allot");
        $allot_db->startTrans();
        try{
            $update = $allot_db->where('id',$id)->update($allot);
            if($update == false){
                $allot_db->rollback();
                return json(['code'=>"-1","msg"=>"调拨单修改失败",'data'=>""]);
            }
//        [{"id": 11,"num": 10,"title": "我是商品名字","attr_ids": "11 _12","attr_name": "我是规格组名字","barcode": "我是条形码"}]
            $purchase_item_list = [];
            foreach ($jsonData as  $k=>$v){

                $data =[
                    "allot_id"=>$id,
                    "item_id"=>$v['id'],
                    "item"=>$v['title'],
                    "num"=>$v['num'],
                    "remark"=>$v['remake'],
                    "barcode"=>$v['bar_code'],
                    'attr_ids'=>$v['key'],//规格组ID
                    'attr_name'=>$v['key_name'],//规格组 名字
                ];

                $purchase_item_list[$k]=$data;
            }

            db::name("allot_item")->where('allot_id',$id)->delete();

            $result = db::name("allot_item")->insertAll($purchase_item_list);
            if(!$result){
                $allot_db->rollback();
                return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
            }
            $allot_db->commit();
            return json(['code'=>200,"msg"=>"",'data'=>"添加成功！"]);
        }catch (\Exception $exception){
            $allot_db->rollback();
            return json(['code'=>"500","msg"=>"系统错误，请稍候",'data'=>""]);
        }
    }


    //根据ID      查找详情
    public function findId(){
        $purchase = new AllotModel();
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
        $newV['out_admin_user'] =$v['out_admin_user'];
        $newV['out_time'] =$v['out_time'];
        $newV['in_admin_user'] =$v['in_admin_user'];
        $newV['in_time'] =$v['in_time'];
        $newV['status'] =$v['status'];
        $newV['remark'] =$v['remark'];
        $newV['create_time'] =$v['create_time'];
        $newV['creator'] =$v['creator'];
        $newV['out_shop_name'] =$v['out_shop_name'];
        $newV['in_shop_name'] =$v['in_shop_name'];
        $newV['in_shop'] =$v['in_shop'];
        $newV['out_shop'] =$v['out_shop'];

        $item=$v['item'];

        $item_name =[];
        $cg_amount =[];
//                $md_amount =[];
        $remarks =[];
        $num =[];
        $barcode =[];
        $attr_name =[];
        foreach ($item as $h=>$v2){
            $item_name[$h]=$v2['item_name'];
//                    $md_amount[$h]=$v2['md_amount'];
            $remarks[$h]=$v2['remark'];
            $num[$h]=$v2['num'];
            $barcode[$h]=$v2['barcode'];
            $attr_name[$h]=$v2['attr_name'];
        }
        $newV['item_name']=$item_name;
        $newV['attr_name']=$attr_name;
//                $newV['md_amount']=$md_amount;
        $newV['remarks']=$remarks;
        $newV['num']=$num;
        $newV['item_code']=$barcode;

        return json(["code" => 200, "msg" => '查询成功！', "data" =>$newV]);
    }

    //发货
    public function out_shop(){

        $id = input('post.id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $allot = new AllotModel();

        $admin_nickname =  $this->getUserInfo()['nickname'];
        $admin_id = $this->getUserInfo()['userid'];

        $data = $allot->out_shop($id,$admin_id,$admin_nickname);
        return $data;
    }

    // 门店确认收货
    public function confirm_shop(){

        $id = input('post.id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $allot = new AllotModel();
        $admin_nickname =  $this->getUserInfo()['nickname'];
        $admin_id = $this->getUserInfo()['userid'];

        return $allot->confirm_shop($id,$admin_id,$admin_nickname);
    }

    // 取消发货
    public function shop_cancel(){

        $id = input('post.id');
        if(empty($id)){
            return json(["code" => -1, "msg" => '参数错误', "data" => '']);
        }
        $allot = new AllotModel();
        $data = $allot->shop_cancel($id);
        return $data;
    }

    //打印
    public function prints(){
        $res = $this->request->get();
        $data = db::name("allot")
        ->where("id",intval($res['id']))
        ->field("sn,out_shop,in_shop,create_time,id,remark")
        ->withAttr("out_shop",function($value,$data){
            return db::name("shop")->where("id",$data['out_shop'])->value("name");
        })
        ->withAttr("in_shop",function($value,$data){
            return db::name("shop")->where("id",$data['in_shop'])->value("name");
        })
        ->withAttr("create_time",function($value,$data){
            return date("Y-m-d H:i:s",$data['create_time']);
        })
        ->withAttr("worker",function($value,$data){
            return db::name("admin")->where("userid",session("admin_user_auth")['uid'])->value("nickname");
        })
        ->withAttr("item",function($value,$data){
            return $this->print_item($data['id']);
        })
        ->find();
        $this->assign("data",$data);
        return $this->fetch();
    }
    public function print_item(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $data = db::name("allot_item")
            ->alias("a")
            ->where("allot_id",$id)
            ->field("a.item,a.num,a.item_id,i.bar_code")
            ->join("item i","a.item_id = i.id")
            ->select();
        if($data){
            return json(['result'=>true,"msg"=>"成功","data"=>$data]);
        }else{
            return json(['result'=>false,'msg'=>"失败",'data'=>""]);
        }
    }
    //调拨单定时器
    public function allot_timer(){
        $time = strtotime("-1 day");
        $data = db::name("allot")->where("status",1)->where("out_time","<",$time)->select();
        $allotmodel = new AllotModel();
        foreach($data as $key=>$val){
            $allot =[
                "in_admin_id"=>1,
                "in_admin_user"=>"自动入库",
                "in_time"=>time(),
                "status"=>2,
            ];
            db::name("allot")->where("id",$val['id'])->update($allot);
            $res =   $allotmodel->allot_timer($val['id']);
        }
    }
}
