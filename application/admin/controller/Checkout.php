<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\checkout\CheckoutitemModel;
use app\admin\model\checkout\CustomerModel;
use think\Db;
use app\admin\model\allot\AllotModel;

/**
门店公司出库管理
 */
class Checkout extends Adminbase
{
    public function index(){
        if ($this->request->isAjax()) {
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $data = $this ->request->param();
            $where = [];
            if( !empty($data['name']) ){
                $where[] = ['nickname|mobile|sn','like','%'.$data['name'].'%'];
            }
            if( !empty($data['reconciliation']) && ($data['reconciliation']==1) ){
                $where[] = ['reconciliation','eq',1];
            }else if( !empty($data['reconciliation']) && ($data['reconciliation']==2) ){
                $where[] = ['reconciliation','eq',0];
            }
            if( !empty($data['shop_id']) ){
                $where[] = ['shop_id','eq',$data['shop_id']];
            }
            if( !empty($data['out_of_stock']) ){
                $where[] = ['out_of_stock','eq',$data['out_of_stock']];
            }
            if( !empty($data['time']) ){
                $where[] = ['create_time','>=',strtotime($data['time'])];
            }
            if( !empty($data['end_time']) ){
                $where[] = ['create_time','>=',strtotime($data['end_time'])];
            }
            $list = (new CheckoutitemModel())
                ->where($where)
                ->page($page,$limit)
                ->order('id desc')
                ->select()
                ->append(['message','item_list','price_list','num_list','code_list','amount_list','cost_list']);

            $total =  (new CheckoutitemModel())->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        $shop = Db::name('shop')->where('status',1)->field('id,name')->select();
        $this ->assign('shop',$shop);
        return $this->fetch();
    }

    //新增
    public function add(){
        $data['shop'] = db::name("shop")->where('status','1')->field("id,name")->select();
        $data['item'] = Db::name("item_category")->where("pid",0)->where("status",1)->where('type',1)->field("id,cname")->select();
        $data['customer'] = Db::name('customer')->where('del',0)->field('id,supplier_name')->select();
        $this->assign("data",$data);
        return $this->fetch();
    }

    /***
     * 选择商品
     */
    public function shop_item(){
        $res = $this->request->get();
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d',1);
        $shop_id = $res['shop_id'];
        $where = [];
        $where[] = ['a.status','=',1];

        if(isset($res['data']['name']) && !empty($res['data']['name'])){
            $where[] = ['a.title',"like","%{$res['data']['name']}%"];
        }
        if(isset($res['id']) && !empty($res['id'])){
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
        $data = (new CheckoutitemModel()) ->getAllCost($shop_id,$data);
        $count = db::name("item")
            ->alias("a")
            ->where($where)
            ->field("a.id,i.cname,i.pid,a.title,a.type,a.bar_code,a.status")
            ->join("shop_item s","a.id=s.item_id")
            ->join("item_category i","a.type = i.id")->count();
        return json(["code"=>0,"count"=>$count,"data"=>$data]);
    }

    /***
     * 对账
     */
    public function no(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('check_out')->where('id',$data['id'])->setField('reconciliation',1);
        if( $result ){
            $this ->success('对账成功');
        }else{
            $this ->error('对账失败');
        }
    }

    /***
     * 对账
     */
    public function ok(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('check_out')->where('id',$data['id'])->setField('reconciliation',0);
        if( $result ){
            $this ->success('对账成功');
        }else{
            $this ->error('对账失败');
        }
    }

    /***
     * 出库
     */
    public function no_stock(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('check_out')->where('id',$data['id'])->setField('out_of_stock',1);
        if( $result ){
            $this ->success('出库成功');
        }else{
            $this ->error('出库失败');
        }
    }

    /***
     * 出库
     */
    public function ok_stock(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = Db::name('check_out')->where('id',$data['id'])->setField('out_of_stock',0);
        if( $result ){
            $this ->success('出库成功');
        }else{
            $this ->error('出库失败');
        }
    }

    //批量对账
    public function edit_status(){
        $data = $this ->request ->param()['ids'];
        if( count($data) <=0 ){
            return json(['code' =>0,'msg'=>'对账失败']);
        }
        $where = [];
        $where[] = ['id','in',implode(',',$data)];
        $res = Db::name('check_out') ->where($where) ->setField('reconciliation',1);
        if( $res ){
            return json(['code' =>1,'msg'=>'对账成功']);
        }else{
            return json(['code' =>0,'msg'=>'对账失败']);
        }
    }
    //批量出库
    public function edit_stock(){
        $data = $this ->request ->param()['ids'];
        if( count($data) <=0 ){
            return json(['code' =>0,'msg'=>'对账失败']);
        }
        $where = [];
        $where[] = ['id','in',implode(',',$data)];
        $res = Db::name('check_out') ->where($where) ->setField('out_of_stock',1);
        if( $res ){
            return json(['code' =>1,'msg'=>'出库成功']);
        }else{
            return json(['code' =>0,'msg'=>'出库失败']);
        }
    }

    /***
     * 更改备注
     */
    public function edit_remarks(){
        $data = $this ->request ->param();
        $res = Db::name('check_out') ->where('id',$data['id']) ->setField('remarks',$data['value']);
        if( $res ){
            return json(['code' =>1,'msg'=>'编辑成功']);
        }else{
            return json(['code' =>0,'msg'=>'编辑失败']);
        }
    }

    /***
     * 添加
     */
    public function doPost(){
        $data = $this ->request ->param()['data'];

        $res = (new  AllotModel)->isStockException($data['shop_id'],$data['item_id']);

        if( count($data['item_id']) <= 0 ){
            return json(['code'=>0,'msg'=>'请选择商品']);
        }
        if( empty($data['shop_id']) ){
            return json(['code'=>0,'msg'=>'请出库仓库']);
        }
        $sn = 'CK'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8);
        $checkOutData = [];  //出库单数据
        $customer = Db::name('customer')->where('id',$data['customer_id'])->find();
        if( !$customer ){
            return json(['code'=>0,'msg'=>'客户信息丢失,请更换客户或更新客户资料']);
        }
        $checkOutData = [
            'sn'    =>$sn,
            'shop_id'   =>$data['shop_id'],
            'nickname'  =>$customer['supplier_name'],    //客户名
            'mobile'    =>$customer['mobile'],      //客户电话
            'remarks'   =>!empty($data['remarks'])?$data['remarks']:'',
            'user_id'   =>session('admin_user_auth')['uid'],
            'user_name'   =>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            'create_time'   =>time(),
            'amount'    =>$data['amount'],
            'all_cost'  =>$data['amount_cost'],  //总成本
            'customer_id'   =>$data['customer_id']
        ];
        $checkOutItemData = []; //出库商品列表
        foreach ( $data['item_id'] as $k=>$v ){
            if( $data['number'][$k] > $data['stock'][$k] ){
                return json(['code'=>0,'msg'=>$data['item_name'][$k].'库存不足']);
            }
            $arr = [];
            $arr = [
                'item_id'   =>$data['item_id'][$k],
                'num'   =>$data['number'][$k],
                'price'   =>$data['price'][$k],
                'title'   =>$data['item_name'][$k],
                'type'   =>$data['level_id'][$k],
                'type_name'   =>$data['p_type'][$k],
                'type_id'   =>$data['levels_id'][$k],
                'type_id_name'   =>$data['cname'][$k],
                'bar_code'   =>$data['bar_code'][$k],
                'allcost'   =>$data['money_costs'][$k],     //总成本
                'cost'   =>$data['single_cost'][$k],        //平均成本
            ];
            array_push($checkOutItemData,$arr);
        }
        // 启动事务
        Db::startTrans();
        try {
            $Id = Db::name('check_out') ->insertGetId($checkOutData);
            foreach ( $checkOutItemData as $k=>$v ){
                $v['check_out_id'] = $Id;
                $check_out_item_id = Db::name('check_out_item') ->insertGetId($v);
                Db::name('shop_item') ->where('shop_id',$data['shop_id'])->where('item_id',$v['item_id'])->setDec('stock',$v['num']);
                //添加成本使用表
                $purchase_price = db::name("purchase_price")->where("item_id",$v['item_id'])->where("shop_id",$data['shop_id'])->where("stock",">",0)->order("time","asc")->select();
                foreach($purchase_price as $key => $val){
                    $purchase['type'] =2;
                    $number = $v['num'];
                    if($val['stock'] >$number){
                        $purchase['stock'] = $val['stock']-$number;
                        $deposit['num'] = $number;
                        $number = 0;
                    }else{
                        $purchase['stock'] = 0;
                        $number  = $number -$val['stock'];
                        $deposit['num'] = $val['stock'];
                    }
                    $deposit['check_out_id'] = $Id;
                    $deposit['check_out_item_id'] = $check_out_item_id;
                    $deposit['purchase_price_id'] = $val['id'];
                    /*$d_number++;*/
                    Db::name("check_out_item_purchase")->insert($deposit);
                    Db::name("purchase_price")->where("id",$val['id'])->update($purchase);
                    if($number ==0){
                        break;
                    }
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>'服务器出错,请联系管理员','data'=>$e->getMessage()]);
        }
        return json(['code'=>1,'添加成功']);
    }

    /***
     * 客户列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function supplier_list(){
        if ($this->request->isAjax()) {
            $Supplier = new CustomerModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this ->request->param('name');
            $where = [];
            $where[] = ['del','=',0];
            if( !empty($name) ){
                $where[] = ['mobile|supplier_name','like',"%$name%"];
            }
            $list = $Supplier->where($where)->page($page,$limit)->select();
            $total =  $Supplier->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * 客户添加
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function supplier_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $Supplier = new CustomerModel();
            $list = $Supplier ->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    /***
     * 客户添加操作
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function supplier_doPost(){
        $data = $this ->request ->post();
        if( empty($data['supplier_name']) ){
            return json(['code'=>0,'msg'=>'请输入供应商名称']);
        }
        if( empty($data['contacts']) ){
            return json(['code'=>0,'msg'=>'请输入联系人']);
        }
        if( empty($data['mobile']) ){
            return json(['code'=>0,'msg'=>'请输入联系人电话']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $data['mobile']);
        if(!$ruleResult){
            return json(['code'=>0,'msg'=>'手机号格式不正确']);
        }
        $Supplier = new CustomerModel();
        $info = $Supplier ->where('mobile',$data['mobile'])->find();

        if( empty($data['id']) ){
            if( $info ){
                return json(['code'=>0,'msg'=>'该手机号已存在']);
            }
            $data['creater'] = session('admin_user_auth')['username'];
            $data['creater_id'] = session('admin_user_auth')['uid'];
            $data['del'] = 0;
            $data['update_id']	= session('admin_user_auth')['uid'];
            $data['update_time']  = time();
            $Supplier ->insert($data);
        }else{
            if( $info && $info['id'] != $data['id'] ){
                return json(['code'=>0,'msg'=>'该手机号已存在']);
            }
            $data['update_id']	= session('admin_user_auth')['uid'];
            $data['update_time']  = time();
            $Supplier ->where('id',$data['id'])->update($data);
        }
        return json(['code'=>1,'msg'=>'操作成功']);
    }

    /**
     * 客户删除
     */
    public function supplier_del(){
        $data = $this ->request->param();
        if( empty($data['id']) ){
            $this ->error('参数为空');
        }
        $Supplier = new CustomerModel();
        $result = $Supplier->where('id',$data['id'])->update(['del'=>1,'del_time'=>time(),'del_staff'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('操作成功',url("Checkout/supplier_list"));
        }else{
            $this ->error('操作失败');
        }
    }

}