<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\allot\AllotModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
/**
 * 出库管理模块
 */
class Allot extends Adminbase
{
    protected function initialize()
    {
        parent::initialize();
      /*  $this->AdminUser = new AdminUser;*/
    }
    //调拨单
    public function index(){
        set_time_limit(0);
        $res = $this->request->get();
        if(!isset($res['page']) || empty($res['page'])){
            $shopWhere[] = ['status','=',1];
            $shop = Db::name('shop')->where($shopWhere)->field('id,name')->select();
            $this ->assign('shop',$shop);
            return $this->fetch();
        }else{
            $allot = new AllotModel();
            $res = $this->request->param();
            $res['limit'] = $this->request->param('limit/d', 10);
            $res['page'] = $this->request->param('page/d', 1);


            //调出仓库
            if(isset($res['out_shop']) && !empty($res['out_shop'])){
                $where[] = ['out_shop','=',intval($res['out_shop'])];
            }
            // 调入仓库
            if(isset($res['in_shop']) && !empty($res['in_shop'])){
                $where[] = ['in_shop','=',intval($res['in_shop'])];
            }
            // 状态
            if(isset($res['status']) && $res['status'] != ''){
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
            $data = $allot->allot_index($res,$where);

            $count = 0;
            if(isset($res['search']) && !empty($res['search'])){
                $name = $res['search'];
                $count = Db::name("allot")->where($where)
                    ->where('id', 'IN', function($query) use (&$name) {
                        $query->table('ddxm_allot_item')->whereOr('barcode','like','%'.$name.'%')->whereOr('item','like','%'.$name.'%')->field('allot_id');
                    })
                    ->count();
            }else{
                $count = Db::name("allot")->where($where)->count();
            }
            return json(["code"=>0,"count"=>$count,"data"=>$data]);
        }
    }
    //新增调拨单
    public function allot_add(){
        if($this->request->isPost()){
            $res = $this->request->post();
            $allot = new AllotModel();
            $data = $allot->allot_add($res);

            return $data;
        }else{
            $data['shop'] = Db::name("shop")->where('status','1')->field("id,name")->select();
            $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->field("id,cname")->select();
            $this->assign("data",$data);
            return $this->fetch();
        }
    }


    //编辑调拨单
    public function allot_up(){
        if($this->request->isPost()){
            $res = $this->request->post();

            $allot = new AllotModel();
            $data = $allot->allot_up($res);
            return $data;
        }else{

            $res = $this->request->param();

            $id =$res['id'];
            $allot = \db('allot')->where('id',$id)->find();
            $data['out_shop_id']=$allot['out_shop'];
            $data['in_shop_id']=$allot['in_shop'];
            $data['out_shop']=\db('shop')->where('id',$allot['out_shop'])->value('name');//调出仓库
//            $data['in_shop']=\db('shop')->where('id',$allot['in_shop'])->value('name');

            $data['remark']=$allot['remark'];

//            $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->field("id,cname")->select();
            $allot_item = Db::name("allot_item")->where("allot_id",$id)->select();

            $allot_item2 = null;
            $index = 0;
            $count = 0;
            $item_id = '';
            foreach ($allot_item as $value){
                $value['barcode'] = \db('item')->where('id',$value['item_id'])->value('bar_code');
                $value['sto'] = \db('shop_item')
                    ->where('shop_id',$allot['out_shop'])
                    ->where('item_id',$value['item_id'])
                    ->value('stock');
                $item_id = $item_id.$value['item_id'].',';
                $count = $count+$value['num'];
                $allot_item2[$index] = $value;
                $index++;
            }

            $data['item']=$allot_item2;
            $data['count']=$count;// 调拨总数量
            $data['countAll']=count($allot_item);//商品总数量
            $data['shop'] = Db::name("shop")->where('status','1')->field("id,name")->select();
            $this->assign("data",$data);
            $this->assign("item_id",$item_id);
            $this->assign("allot_id",$id);
            return $this->fetch();
        }
    }

    //调拨单商品信息
    public function allot_item(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $shop_id = $res['shop'];
        $where = [];
        $where[] = ['a.status','=',1];

        if(isset($res['data']['name']) && !empty($res['data']['name'])){
           $where[] = ['a.title',"like","%{$res['data']['name']}%"]; 
        }
        if(isset($res['id']) && !empty($res['id'])){//商品 不包含的ID
            $id = explode(",",$res['id']);
            $where[] =['a.id',"not in",$id];
        }
        if(isset($res['data']['code']) && !empty($res['data']['code'])){
           $where[] = ['a.bar_code',"like","%{$res['data']['code']}%"]; 
        }
        if(isset($res['data']['parent']) && !empty($res['data']['parent'])){
            if(isset($res['data']['child']) && !empty($res['data']['child'])){
               $where[] = ['a.type',"=",intval($res['data']['child'])]; 
            }else{
                $where[] = ['i.pid',"=",intval($res['data']['parent'])]; 
            }
        }
        $where[] =["s.shop_id","=",intval($shop_id)];
        $where[] =["s.stock",">",0];
        $where[] =['item_type',"in",[2,3]];
        $data = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status,s.stock")
        ->withAttr("p_type",function($value,$data){
            return db::name("item_category")->where("id",$data['pid'])->value("cname");
        })
        ->join("shop_item s","a.id=s.item_id")
        ->join("item_category i","a.type = i.id")
        ->page($page,$limit)
        ->select();
        $count = db::name("item")
        ->alias("a")
        ->where($where)
        ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
        ->join("shop_item s","a.id=s.item_id")
        ->join("item_category i","a.type = i.id")->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }
    //调拨单出库列表
    public function  out_shop_list(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $allot = new AllotModel();
        $data = $allot->out_shop_list($id);
//        dump($data);die;
        if($data){
            return json(['result'=>true,"msg"=>"请求成功","data"=>$data]);
        }else{
            return json(['result'=>false,"msg"=>"服务器错误,请稍后重试!","data"=>""]);
        }
    }
    //调拨单调拨时商品列表
    public function out_shop_item(){
        $res= $this->request->get();
        $id = $res['id'];
        $data = db::name("allot_item")
        ->alias("a")
        ->where("a.allot_id",$id)
        ->field("a.item,a.item_id,a.num,i.bar_code")
        ->join("item i","a.item_id = i.id")
        ->select();
        $count = db::name("allot_item")
        ->where("allot_id",$id)
        ->count();
        return json(['code'=>0,'count'=>$count,"data"=>$data]);
    }
    //出库
    public function out_shop(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->out_shop(intval($res['id']));
        return $data;
    }
    //确认收货列表
    public function allot_confirm_list(){
        if($this->request->isPost()){
            $res = $this->request->post();
            $id = intval($res['id']);
            $data = Db::name("allot")->where("id",$id)
            ->withAttr("create_time",function($value,$data){
                return date("Y-m-d H:i:s",$data['create_time']);
            })
            ->withAttr("out_time",function($value,$data){
                return date("Y-m-d H:i:s",$data['out_time']);
            })
            ->withAttr("out_shop",function($value,$data){
                return db::name("shop")->where("id",$data['out_shop'])->value("name");
            })
            ->withAttr("in_shop",function($value,$data){
                return db::name("shop")->where("id",$data['in_shop'])->value("name");
            })
            ->withAttr("in_time",function($value,$data){
                return date("Y-m-d H:i:s",$data['in_time']);
            })
            ->withAttr("in_admin_user",function($value,$data){
                if($data['in_admin_id'] >0){
                    return $data['in_admin_user'];
                }else if($data['in_admin_id'] ==0){
                    return "到期时间";
                }else{
                    return "<b style='color:red'>系统自动收货<b>";
                }
            })
            ->find();
            if($data){
                return json(['result'=>true,"msg"=>"获取成功","data"=>$data]);
            }else{
                return json(['result'=>false,"msg"=>"系统繁忙，数据错误","data"=>""]);
            }
        }else{
            $res = $this->request->get();
            $allot = new AllotModel();
            $data = $allot->allot_confirm_list(intval($res['id']));
            return $data;
        }
    }

    // 门店确认收货
    public function confirm_shop(){
        $res = $this->request->post();
        $id = intval($res['id']);
        $allot = new AllotModel();
        $data = $allot->confirm_shop($id);
        return $data;
    }
    // 取消发货
    public function shop_cancel(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->shop_cancel($res);
        return $data;
    }
    //删除调拨单
    public function del(){
        $res = $this->request->post();
        $allot = new AllotModel();
        $data = $allot->del($res);
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
