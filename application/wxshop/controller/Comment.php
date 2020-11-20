<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Request;
/*use app\wxshop\controller\Token;*/
use app\wxshop\controller\Base;
use app\wxshop\model\item\SpecsGoodsPriceModel;
use app\wxshop\model\item\SpecsModel;
use app\wxshop\model\comment\CommentModel;
class Comment extends Base
{
    //添加评论
    public function add(){
        $res = $this->request->post();
        $goods = db::name("order_goods")->where("id",intval($res['goods_id']))->find();
        if(!$goods){
            return json(['code'=>"-3","msg"=>"goods 参数错误","data"=>""]);
        }
        if(!isset($res['level']) || empty($res['level'])){
            return json(['code'=>"-3","msg"=>"level 参数错误","data"=>""]);
        }
        if(!isset($res['comment']) || empty($res['comment'])){
            return json(['code'=>"-3","msg"=>"comment 参数错误","data"=>""]);
        }

        $goods_id = input('goods_id','');
        $order_id = input('order_id','');
        if($goods_id == ''){
            return json(['code'=>"-3","msg"=>"goods_id 参数错误","data"=>""]);
        }
        if($order_id == ''){
            return json(['code'=>"-3","msg"=>"order_id 参数错误","data"=>""]);
        }
        $commentlist = \db('comment')
            ->where('goods_id',$goods_id)
            ->where('order_id',$order_id)
            ->find();
        if($commentlist!=false){
            return json(['code'=>"-3","msg"=>"您已经评价，请勿重复添加！","data"=>""]);
        }
//        $specs = $res['specs'];
        $specs = input('specs','');

//        if(!isset($res['specs']) || empty($res['specs'])){
//            return json(['code'=>"-3","msg"=>"specs  参数错误","data"=>""]);
//        }
//        $user_id = self::getUserId();
//        if(isset($res['anonymous']) && !empty($res['anonymous'])){
//            $user_id = 0;
//        }

        $Token = controller('Token');
        $user_id = $Token ->getUserId();

        $order = db::name("order")->where("id",$order_id)->find();
        if($order['order_status'] !==2){
            return json(['code'=>"-3","msg"=>"状态错误",'data'=>""]);
        }
        $comment = [
           "order_id"=>$order_id,
           "goods_id"=>$goods['id'],
           "item_id" =>$goods['item_id'],
           "specs"=>$specs,
           "add_time"=>time(),
           "member_id"=>$user_id,
           "comment"=>$res['comment'],
           "pic" => $res['pic'],
           "level"=>$res['level'],
           "status"=>0,
        ];

        Db::startTrans();
        try {
            $result = Db::name("comment")->insert($comment);
            $updata = [
                'evaluate'=>1
            ];
            Db::name('order')->where('id',$order_id)->update($updata);
            Db::commit();
         } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
        }
        if($result){
            return json(['code'=>200,"msg"=>"添加成功","data"=>""]);
        }else{
            return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
        }
    }

    //商品详情--评论
    public function item_comment_list(){
        $res = $this->request->post();
        if(!isset($res['item_id']) || empty($res['item_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

        $where[] = ["item_id","=",intval($res['item_id'])];
        $where[] = ["status","=",1];
        $model = new CommentModel();
        $data = $model->comment_list()->where($where)->limit('2')->order('id','desc')->select();
        $count = $model->comment_list()->where($where)->count();
        return json(["code" =>200, "count" => $count, "data" => $data]);
    }

    //商品评论-更多列表
    public function comment_list(){
        $res = $this->request->post();

        $state = $this->request->param('state',1);

        if(!isset($res['item_id']) || empty($res['item_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

        if(!isset($res['item_id']) || empty($res['item_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }

//        1:全部 2 ：有图 3：好评 4 中评 5差评
        $limit = $this->request->param('limit',0);
        $page = $this->request->param('page',0);
        $where = [];
        if($state == 2){
            $where[] = ['pic',"neq",""];
        } else if($state == 3){
            $where[] = ["level","=",5];
        }else if($state == 4){
            $where[] = ["level","in",[3,4]];
        }else if($state == 5){
            $where[] = ["level","in",[1,2]];
        }

        $where[] = ["item_id","=",intval($res['item_id'])];
        $where[] = ["status","=",1];
        $model = new CommentModel();
        $data2 = $model->comment_list()
            ->where($where)
            ->page($page,$limit)
            ->order('top','desc','sort','asc','id','desc')->select();
        $count_hp = $model->where('level','5')->where('status','1')->where('item_id',intval($res['item_id']))->count();
        $count_zp = $model->whereIn('level',[3,4])->where('status','1')->where('item_id',intval($res['item_id']))->count();
        $count_cp = $model->whereIn('level',[1,2])->where('status','1')->where('item_id',intval($res['item_id']))->count();
        $map = [];
        $map[] = ['pic','neq',''];
        $map[] = ['status','eq',1];
        $map[] = ['item_id','eq',intval($res['item_id'])];
        $count_tu = $model->where($map)->count();
        $count = $model->where(['status'=>1,'item_id'=>intval($res['item_id'])])->count();
        $data=[
            'list'=>$data2,
            'count_hp'=>$count_hp,//好评数量
            'count_zp'=>$count_zp,//中评数量
            'count_cp'=>$count_cp,//中评数量
            'count_tu'=>$count_tu,//有图数量
            'count'=>$count,//全部数量
        ];

        return json(["code" =>200, "data" => $data,'msg'=>'获取成功！']);
    }

    //订单明细评论
    public function order_comment(){
        $res = $this->request->post();  
    }
    public function getexpress(){
        $order = [
            'sn'=>"",
            "express_code"=>"YT2011544316293",
            "code"=>"YTO",
        ];
       
        $logisticResult=getOrderTracesByJson($order);
        return $logisticResult;
    }
}