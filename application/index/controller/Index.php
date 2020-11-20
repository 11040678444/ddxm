<?php
namespace app\index\controller;

use app\index\model\ItemModel;
use app\index\model\MemberModel;
use app\index\model\Member\MemberModel as Tp5MemberModel;
use app\index\model\WorkerModel;
use app\index\model\ServiceModel;

//第二期引入的model
use app\index\model\item\ItemCategoryModel;
use app\index\model\Shop\ShopWorkerModel;
use app\index\model\item\ServiceModel as Tp5ServiceModel;
use app\index\model\item\ItemModel as Tp5ItemModel;
use app\index\model\item\ShopItemModel;
use app\index\model\item\PurchasePriceModel;
use app\common\model\UtilsModel;


use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;

class Index extends Base
{
	//商品首页
    public function index()
    {
        echo $this->getUserInfo()['id'];
        echo "hello word!";

    }
        /**
          商品列表  第二期
        */
        public function itemList(){
            $data = $this ->request ->param();
            if( empty($data['page']) ){
                $data['page'] = '1,12';
            }
            $shop_id = $this->getUserInfo()['shop_id'];
            $Item = new Tp5ItemModel();
            $itemList = $Item ->getItems($data,$shop_id);
            if( count($itemList)==0 ){
                return json(['code'=>'1','msg'=>'该门店还未添加商品,快去添加商品吧','data'=>'']);
            }

            $itemIds = [];
            foreach ($itemList as $key => $value) {
                $itemList[$key]['stock'] = 0; //赋值默认库存
                $itemList[$key]['pic'] = config('QINIU_URL').$value['pic'];
                $itemIds[] = $value['id'];
            }
            $itemIds = implode(',',$itemIds);

            $ShopItem = new ShopItemModel();
            $shopStock = $ShopItem ->getStock($itemIds,$shop_id);
            foreach ($itemList as $key => $value) {
              $itemList[$key]['is_service_goods'] = '0';
                foreach ($shopStock as $k => $val) {
                    if( $value['id'] == $val['item_id'] ){
                        $itemList[$key]['stock'] = $val['stock'];
                    }
                }
            }

            foreach ($itemList as $key => $value) {
                $title = $value['title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                   $itemList[$key]['is_outsourcing_goods'] = "1";   //1外包商品，0不是
                }else{
                   $itemList[$key]['is_outsourcing_goods'] = "0";
                }
            }
            return json(['code'=>'200','msg'=>'获取成功','data'=>$itemList]);
        }

    /***
     *商品列表：第三期
     */
    public function itemList3()
    {
        $data = $this ->request ->param();
        $shop_id = $this->getUserInfo()['shop_id'];
        $post_data = [];
        $post_data['warehouse_id'] = $shop_id;
        $post_data['g_type'] = 2;
        if ( !empty($data['title']) )
        {
            $post_data['title'] = $data['title'];
        }
        if ( !empty($data['bar_code']) )
        {
            $post_data['bar_code'] = $data['bar_code'];
        }

        if ( !empty($data['page']) )
        {
            $page = explode(',',$data['page']);
            $post_data['page'] = $page[0];
            $post_data['paginate'] = $page[1];
        }else{
            $post_data['page'] = 1;
            $post_data['paginate'] = 12;
        }

        $url = config('erp_url').'api/warehouse_goods/getList';
        $result = sendPost($url,$post_data);
        $result = json_decode($result,true);
        if ( $result['code'] != 200 )
        {
            return json(['code'=>'100','msg'=>'获取失败','data'=>[]]);
        }
        if ( $result['data']['total'] == 0 )
        {
            return json(['code'=>'200','msg'=>'获取成功','data'=>[]]);
        }
        $resGoods = $result['data']['data'];

        $returnGoods = [];
        foreach ( $resGoods as $k=>$v )
        {
            $title = $v['g_title'];
            $title_s = mb_substr($title,0,2,'utf-8');
            if( $title_s === '外包' ){
                $is_outsourcing_goods = "1";   //1外包商品，0不是
            }else{
                $is_outsourcing_goods = "0";
            }
            $arr = [];
            $arr = [
                'id'    =>$v['goods_id'],
                'title'    =>$v['g_title'],
                'price'    =>$v['price'],
                'bar_code'    =>$v['bar_code'],
                'pic'    =>count($v['gr_url']) ? config('QINIU_URL').$v['gr_url'][0] : '',
                'stock'    =>$v['w_actual_stock'],
                'minimum_selling_price'    =>0,
                'key'    =>!empty($v['key']) ? $v['key'] : '',
                'key_name'    =>!empty($v['key_name']) ? $v['key_name'] : '',
                'is_outsourcing_goods'  =>$is_outsourcing_goods,
                'is_service_goods'  =>"0"
            ];
            array_push($returnGoods,$arr);
        }
        return json(['code'=>'200','msg'=>'获取成功','data'=>$returnGoods]);
    }

        /**
             根据条形码搜索商品  第二期
             $data['bar_code']条形码参数
          */
          public function search_code_item(){
              $Item = new Tp5ItemModel();
              $data = $this ->request->param();
              if ( empty($data['bar_code']) ) {
                  $result = array('code'=>'-3','msg'=>'缺少参数bar_code','data'=>'');
                  return json($result);
              }
              $validate = Validate::make([
                'bar_code'  => 'number'
              ]);

              if (!$validate->check($data)) {
                  $result = array('code'=>'-100','msg'=>'参数必须为数字类型','data'=>'');
                  return json($result);
              }
              $shop_id = $this->getUserInfo()['shop_id'];
              $itemList = $Item ->getItems($data,$shop_id)['0'];
              if( empty($itemList) ){
                return json(['code'=>'100','msg'=>'暂无该条形码的商品']);
              }
              $itemList['pic'] = config('QINIU_URL').$itemList['pic'];

              $ShopItem = new ShopItemModel();
              $shopStock = $ShopItem ->getStock($itemList['id'],$shop_id)['0'];
              if( count($shopStock) != 0 ){
                  $itemList['stock'] = $shopStock['stock'];
              }else{
                  $itemList['stock'] = 0;
              }

              $itemList['is_service_goods'] = "0";
              $title = $itemList['title'];
              $title_s = mb_substr($title,0,2,'utf-8');
              if( $title_s === '外包' ){
                 $itemList['is_outsourcing_goods'] = "1";   //1外包商品，0不是
              }else{
                 $itemList['is_outsourcing_goods'] = "0";
              }
              return json(['code'=>'200','msg'=>'获取成功','data'=>$itemList]);
          }


      //   /**
      //   根据商品名称搜索商品
      //     */
      // public function search_item_title(){
      //     $data = $this ->request->param();
      //     unset($data['/index/index/search_item_title_html']);
      //     $Item = new ItemModel();
      //     if ( empty($data['title']) ){
      //       $result = array('code'=>'0','msg'=>'请输入商品名','data'=>'');
      //       return $result;
      //     }
      //     $list = $Item ->search_item($data,$this->shop_id)->paginate(5, false, ['query'=>$data]);
      //     $page = $list->render();
      //     $result = array('code'=>'1','msg'=>'请求成功','data'=>$list->toArray()['data'],'page'=>$page);
      //     return $result;
      // }

      /**
        服务商品列表  第一期
      */
      // public function serviceItemList(){
      //     $data = $this ->request ->param();
      //     if ( empty($data['page']) ) {
      //         $data['page'] = '';
      //     }
      //     if ( empty($data['member_id']) ) {
      //         $data['vip_rank'] = 1;
      //     }else{
      //         $data['vip_rank'] = Db::name('member')->where('id',$data['member_id'])->value('level_id');
      //     }
      //     $Service = new ServiceModel();
      //     $item_list = $Service->service_list($data)->select();    //商品列表
      //     foreach ($item_list as $key => $value) {
      //           $vip_price = json_decode($value['standard_price'],true);
      //           $item_list[$key]['standard_price'] = $vip_price[$data['vip_rank']];
      //       }
      //     $goodsList = [];
      //     if ( !empty($item_list) ) {
      //         foreach ($item_list as $key => $value) {
      //             $goodsList[$key]['id'] = $value['s_id'];
      //             $goodsList[$key]['title'] = $value['sname'];
      //             $goodsList[$key]['price'] = $value['standard_price'];
      //             $goodsList[$key]['bar_code'] = $value['bar_code'];
      //             $goodsList[$key]['pics'] = $value['cover'];
      //             $goodsList[$key]['is_service_goods'] = '1';
      //         }
      //     }
      //      // dump($goodsList);die;
      //     return json(['code'=>'200','msg'=>'查询成功','data'=>$goodsList]);
      // }

        /**
        服务商品列表 第二期
      */
      public function serviceItemList(){
          $data = $this ->request ->param();
          $where = [];
          if( empty($data['page']) ){
              $page = '1,10';
          }else{
              $page = $data['page'];
          }
          if ( empty($data['member_id']) ) {
              $level_id = 1;
          }else{
              $level_id = Db::name('member')->where('id',$data['member_id'])->value('level_id');
          }
          if( !empty($data['service_type']) ){
              $where[] = ['a.type','=',$data['service_type']];
          }

          if( !empty($data['ids']) ){
              $ids = implode(',',$data['ids']);
              $where[] = ['a.id','in',$ids];
          }
          $where[] = ['a.status','=',1];
          $where[] = ['b.status','=',1];
          $where[] = ['b.shop_id','=',$this->getUserInfo()['shop_id']];
          $where[] = ['b.level_id','=',$level_id];
          $Service = new Tp5ServiceModel();
          $list = $Service
                ->alias('a')
                ->join('service_price b','a.id=b.service_id')
                ->where($where)
                ->order('id asc')
                ->page($page)
                ->field('a.id,a.sname as title,a.type,a.cover,b.price')
                ->select()
                ->append(['category']);
          
          foreach ($list as $key => $value) {
              $list[$key]['old_price'] = Db::name('service_price')->where(['level_id'=>1,'shop_id'=>$this->getUserInfo()['shop_id'],'service_id'=>$value['id']])->value('price');
          }
          foreach ($list as $key => $value) {
              $list[$key]['is_service_goods'] = '1';
          }

          foreach ($list as $key => $value) {
                $title = $value['title'];
                $title_s = mb_substr($title,0,2,'utf-8');
                if( $title_s === '外包' ){
                   $list[$key]['is_outsourcing_goods'] = "1";   //1外包商品，0不是
                }else{
                   $list[$key]['is_outsourcing_goods'] = "0";
                }
            }
          return json(['code'=>'200','msg'=>'查询成功','data'=>$list]);
      }

      

    /**
      服务人员:第一期
    */
      // public function waiter(){
      //     $Worker= new WorkerModel();

      //     $where['sid'] = $this->getUserInfo()['shop_id'];
      //     $where['status'] = 1;
      //     $worker_list = $Worker->getWorker($where)->field('id,name,type')->select()->toArray();
      //     $newarray = array(
      //           'id'=>0,
      //           'name'=>$this->getUserInfo()['user_nickname'],
      //           'type'=>'店长'
      //       );
      //     array_unshift($worker_list, $newarray);
      //     $result = array('code'=>'200','msg'=>'查询成功','data'=>$worker_list);
      //     return json($result);
      // }

      /**
        服务人员  ：第二期
      */
        public function waiter(){
          $Worker= new ShopWorkerModel();
          $where['sid'] = $this->getUserInfo()['shop_id'];
          $where['status'] = 1;
          $worker_list = $Worker->where($where)->order('post_id asc')->field('id,name,post_id as type')->select();  //取别名主要是为了配合第一版本接口的参数
          
          $result = array('code'=>'200','msg'=>'查询成功','data'=>$worker_list);
          return json($result);
      }


    /**
	     查询分类，当type=0时为一级分类：第一期
       $data['type'] 分类的id
    */
      	// public function getTwotype(){
       //  		$data = $this ->request ->param();
       //  		$Item = new ItemModel();
       //      if( empty($data['type']) ){
       //        $data['type'] = 0;
       //      }
       //      if( empty($data['page']) ){
       //          $page = '1,10';
       //      }
       //  		$type_child = $Item ->type_child($data);
       //      $categoryList = [];
       //      if ( !empty($type_child) ) {
       //          foreach ($type_child as $key => $value) {
       //              $categoryList[$key]['id'] = $value['id'];
       //              $categoryList[$key]['pid'] = $value['pid'];
       //              $categoryList[$key]['cname'] = $value['cname'];
       //          }
       //      }
       //      // dump($categoryList);die;
       //  		$result = array('code'=>'1','msg'=>'请求成功','data'=>$categoryList);
       //  		return json($result);
      	// }

        /**
          查询商品分类  第二期
        */
          public function getTwotype(){
            $data = $this ->request ->param();
            $ItemCategory = new ItemCategoryModel();
            if( empty($data['page']) ){
                $page = '';
            }else{
              $page = $data['page'];
            }
            $where = [];
            if( empty($data['type']) ){
              $where[] = ['pid','=',0];
            }else{
              $where[] = ['pid','=',$data['type']];
            }

            $where[] =['status','=',1];
            $where[] = ['type','=',1];
            // dump($where);die;
            $categoryList = $ItemCategory ->where($where)->page($page)->order('sort desc,id desc')->field('id,pid,cname')->select();
            $result = array('code'=>'200','msg'=>'请求成功','data'=>$categoryList);
            return json($result);
        }

        /**
          获取服务分类
        */
          public function getServiceCategory(){
              $data = $this ->request->param();
              if( empty($data['page']) ){
                  $data['page'] = '';
              }
              $list = Db::name('item_category')->where(['status'=>1,'type'=>2,'pid'=>0])->page($data['page'])->field('cname,id')->select();
              $result = array('code'=>'200','msg'=>'请求成功','data'=>$list);
              return json($result);
          }


          // /**
      	   //   根据条形码搜索商品
          //    $data['bar_code']条形码参数
          // */
          // public function search_code_item(){
          // 	$Item = new ItemModel();
          // 	$data = $this ->request->param();
          //   if ( empty($data['bar_code']) ) {
          //       $result = array('code'=>'-3','msg'=>'缺少参数bar_code','data'=>'');
          //       return json($result);
          //   }
          // 	$validate = Validate::make([
      		  //   'bar_code'  => 'number'
        		// ]);

        		// if (!$validate->check($data)) {
        		//     $result = array('code'=>'-100','msg'=>'参数必须为数字类型','data'=>'');
          //       return json($result);
        		// }

        		// $list = $Item->search_code_item($data['bar_code']);
          //   $goodsList = [];
          //   if ( !empty($list) ) {
          //       $goodsList['id'] = $list['id'];
          //       $goodsList['title'] = $list['title'];
          //       $goodsList['price'] = $list['price'];
          //       $goodsList['bar_code'] = $list['bar_code'];
          //   }
          //   // dump($goodsList);die;
          //   $result = array('code'=>'200','msg'=>'请求成功','data'=>$goodsList);
        		// return json($result);
          // }



          /**
      		  根据手机号码搜索会员
            $data['mobile']会员手机号
          */
      	public function search_vip(){
      		$data = $this ->request->param();
      		if( empty($data['mobile']) ){
            $result = array('code'=>'-3','msg'=>'请输入会员手机号码','data'=>'');
      			return json($result);
      		}

          $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
          $ruleResult = preg_match($rule, $data['mobile']);
          if (!$ruleResult) {
              $result['code'] = '-100';
              $result['msg'] = '手机号格式错误';
              $result['data'] = '';
              return json($result);
          }
      		$where = [];
      		$where[] = ['mobile','=',$data['mobile']];	//此处是根据手机号码精准查找
      		$Member = new MemberModel();

      		$vip = $Member ->search_vip($where)->find();
          if ( !empty($vip) ) {
              $vipList['id'] = $vip['id'];
              $vipList['mobile'] = $vip['mobile'];
              $vipList['shop_code'] = $vip['shop_code'];
              $vipList['level_id'] = $vip['level_id'];
              $vipList['nickname'] = $vip['nickname'];
              $vipList['level_name'] = $vip['level_name'];

              $vipList['money'] = $vip['money'];  //余额
              $vipList['amount'] = $vip['amount'];  //累积充值
              $vipList['regtime'] = date('Y-m-d H:i:s',$vip['regtime']);
              $vipList['service_card'] = Db::name("ticket_user_pay")->where("member_id",intval($vip['id']))->count();
          }
      		$result = array('code'=>'200','msg'=>'请求成功','data'=>$vipList);
      		return json($result);
      	}

    /**
    选择会员 - 2.1版
     */
    public function findMember(){
        $data = $this ->request->param();
        if( empty($data['mobile']) ){
            $result = array('code'=>'-3','msg'=>'请输入会员手机号码/或昵称','data'=>'');
            return json($result);
        }

//            $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
//            $ruleResult = preg_match($rule, $data['mobile']);
//            if (!$ruleResult) {
//                $result['code'] = '-100';
//                $result['msg'] = '手机号格式错误';
//                $result['data'] = '';
//                return json($result);
//            }
        $shop_id = $this->getUserInfo()['shop_id'];

        $Tp5Member = new Tp5MemberModel();
        $where = [];
        $where[] = ['a.status','eq',1];
        $where[] = ['a.shop_code','eq',$Tp5Member->getShopcode($shop_id)];
        $where[] = ['a.mobile|a.nickname','like','%'.$data['mobile'].'%'];
        $vip = $Tp5Member
            ->alias('a')
            ->join('member_money b','a.id=b.member_id','left')
            ->where($where)
            ->field('a.id,a.mobile,a.status,a.shop_code,a.level_id,a.pic,a.nickname,a.regtime,b.money,b.score_item,b.score_server,b.server_money,b.item_money,b.server_item_money')
            ->select();



        if( $vip == false ){
            $result = array('code'=>'-6','msg'=>'会员不存在','data'=>'');
            return json($result);
        }

        $datalist = [];
        $index = 0;
        foreach ($vip as $value){
            $amount = Db::name('member_details')->where(['member_id'=>intval($value['id']),'type'=>1])->sum('amount');
            $value['amount'] = $amount;
//            if( $vip['status'] == 0 ){
//                return json(['code'=>'-5','msg'=>'该会员已被禁用']);
//            }
            $vipList = [];
            if ( !empty($value) ) {
                $vipList['id'] = $value['id'];
                $vipList['mobile'] = $value['mobile'];
                $vipList['shop_code'] = $value['shop_code'];
                $vipList['level_id'] = $value['level_id'];
                $vipList['nickname'] = $value['nickname'];
                $vipList['level_name'] = $value['level_id'];
                $vipList['service_card'] = Db::name("ticket_user_pay")->where("member_id",intval($value['id']))->count();
                if(empty($value['money'])){
                    $vipList['money'] = 0;  //余额
                }else{
                    $vipList['money'] = bcsub($value['money'],self::getLimitedPrice1(['member_id'=>$value['id']]),2);  //余额
                }
                if(empty($value['amount'])){
                    $vipList['amount'] = 0;  //累积充值
                }else{
                    $vipList['amount'] = $value['amount'];  //累积充值
                }
                $vipList['regtime'] = date('Y-m-d H:i:s',$value['regtime']);
            }
            $datalist[$index] = $vipList;
            $index++;
        }
        $result = array('code'=>'200','msg'=>'查询成功','data'=>$datalist);
        return json($result);
    }


    /***
         * 查询会员的限时余额
         */
        public function getLimitedPrice(){
            $data = $this ->request ->param();
            if( empty($data['member_id']) ){
                return json(['code'=>101,'msg'=>'请选择会员']);
            }
            $map = [];
            $map[] = ['member_id','eq',$data['member_id']];
            $map[] = ['status','in','0,1'];
//            $map[] = ['expire_time','>=',time()];
            $list = Db::name('member_money_expire') ->where($map)->field('id,price,use_price,expire_time,status,expire_day')->select();
            $Utils = new UtilsModel();
            $info = []; //数据
            foreach ($list as $k=>$v){
                $list[$k]['limited'] = $v['price']-$v['use_price'];
                if( $v['use_price'] <$v['price'] ){
                    $arr = [];
                    $arr = [
                        'id'    =>$v['id'],
                        'price'    =>bcsub($v['price'],$v['use_price'],2),
                        'expire_time'    =>$v['expire_time'],
                        'status'    =>$v['status'],
                        'expire_day'    =>$v['expire_day'],
                    ];
                    array_push($info,$arr);
                }
            }
            foreach ($info as $k=>$v){
                if( $v['status'] == 1 ){
                    $info[$k]['company'] = date('Y-m-d H:i:s',$v['expire_time']);
                }else{
                    $info[$k]['company'] = '未激活';
                }
            }
            $allPrice = 0;
            foreach ($info as $k=>$v){
                $allPrice += $v['price'];
            }
            return json(['code'=>200,'msg'=>'获取成功','data'=>array('allprice'=>$allPrice,'data'=>$info)]);
        }

        /***
         * 查询会员的限时余额
         */
        public function getLimitedPrice1($data){
        $map = [];
        $map[] = ['member_id','eq',$data['member_id']];
        $map[] = ['status','in','0,1'];
//            $map[] = ['expire_time','>=',time()];
        $list = Db::name('member_money_expire') ->where($map)->field('id,price,use_price,expire_time,status,expire_day')->select();
        $Utils = new UtilsModel();
        $info = []; //数据
        foreach ($list as $k=>$v){
            $list[$k]['limited'] = bcsub($v['price']-$v['use_price'],2);
            if( $v['use_price'] <$v['price'] ){
                $arr = [];
                $arr = [
                    'id'    =>$v['id'],
                    'price'    =>bcsub($v['price'],$v['use_price'],2),
                    'expire_time'    =>$v['expire_time'],
                    'status'    =>$v['status'],
                    'expire_day'    =>$v['expire_day'],
                ];
                array_push($info,$arr);
            }
        }
        foreach ($info as $k=>$v){
            if( $v['status'] == 1 ){
                $info[$k]['company'] = date('Y-m-d H:i:s',$v['expire_time']);
            }else{
                $info[$k]['company'] = '未激活';
            }
        }
        $allPrice = 0;
        foreach ($info as $k=>$v){
            $allPrice += $v['price'];
        }
        return $allPrice;
    }

        /***
         *激活限时余额
         */
        public function activationExpireMoney(){
            $data = $this ->request ->post();
            if( empty($data['id']) ){
                return json(['code'=>100,'msg'=>'请选择数据']);
            }
            $expireMoney = Db::name('member_money_expire') ->where('id',$data['id'])->find();
            if( !$expireMoney ){
                return json(['code'=>100,'msg'=>'ID错误']);
            }
            if( $expireMoney['status'] != 0 ){
                return json(['code'=>100,'msg'=>'已激活，请勿重复激活']);
            }
            $res = Db::name('member_money_expire') ->where('id',$data['id'])
                ->update(['activate_time'=>time(),'expire_time'=>time()+($expireMoney['expire_day']*24*60*60),'status'=>1]);
            if( $res ){
                return json(['code'=>200,'msg'=>'激活成功']);
            }else{
                return json(['code'=>100,'msg'=>'激活失败']);
            }
        }

        /***
         *查询会员是否为公司门店
         */
        public function getMemberShop(){
            $data = $this ->request ->param();
            if ( empty($data['mobile']) ) {
                return json(['code'=>'-3','msg'=>'请输入会员电话','data'=>'']);
            }
            $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
            $ruleResult = preg_match($rule, $data['mobile']);
            if (!$ruleResult) {
                $result['code'] = '-100';
                $result['msg'] = '手机号格式错误';
                $result['data'] = '';
                return json($result);
            }
            $list = Db::name('member') ->alias('a')
                ->join('shop s','a.shop_code=s.code','left')
                ->where('a.mobile',$data['mobile'])->field('a.*,s.id as shop_id') ->find();
            if( $list )
            {
                if ( $list['shop_id'] == self::getUserInfo()['sid'] )
                {
                    return json(['code'=>'100','msg'=>'此会员已经是此门店会员啦，请勿重复添加！！']);
                }else if( $list['shop_code'] !="0" && $list['shop_code'] != 'A00000' )
                {
                    return json(['code'=>'100','msg'=>'此会员已经是其他门店会员啦']);
                }else{
                    $res = [];
                    $res = [
                        'type'  =>1,    //是总部会员
                        'data'  =>['nickname'=>!empty($list['nickname'])?$list['nickname']:$list['wechat_nickname']],    //是总部会员
                    ];
                    return json(['code'=>'200','msg'=>'此会员是总部会员','data'=>$res]);
                }
            }else{
                return json(['code'=>'200','msg'=>'此会员是新会员','data'=>['type'=>2,'data'=>'']]); //新会员
            }
        }

        /**
          添加会员
        */
        public function addVip(){
            $result = [];
            $data['mobile'] = $this ->request->param('mobile');
            $data['nickname'] = $this ->request->param('nickname');
            if ( empty($data['mobile']) || empty($data['nickname']) ) {
                return json(['code'=>'-3','msg'=>'请输入会员名字或电话','data'=>'']);
            }
            if( mb_strlen($data['nickname'])>10 ){
                return json(['code'=>'-3','msg'=>'会员昵称过长','data'=>'']);
            }
            $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
            $ruleResult = preg_match($rule, $data['mobile']);
            if (!$ruleResult) {
                $result['code'] = '-100';
                $result['msg'] = '手机号格式错误';
                $result['data'] = '';
                return json($result);
            }
            $findMember = Db::name('member')->alias('a')
                ->join('shop s','a.shop_code=s.code','left')
                ->where(['a.mobile'=>$data['mobile']])->field('a.*,s.id as shop_id')->find();
            if ( $findMember ) {
                //判断会员是否为公司总部会员
                if( $findMember['shop_code'] != "0" && $findMember['shop_code'] != 'A00000' ){
                    return json(['code'=>'-5','msg'=>'该用户已是其他门店的会员','data'=>'']);
                }else if( self::getUserInfo()['sid'] == $findMember['shop_id'] )
                {
                    return json(['code'=>'-5','msg'=>'该用户已是此门店的会员，请勿重复添加！！','data'=>'']);
                }else{
                    //将此会员转入到
                    $shop_id = self::getUserInfo()['sid'];
                    $shop_code = Db::name('shop')->where('id',$shop_id)->value('code');   //当前门店code
                    Db::startTrans();
                    try {
                        //更改门店
                        Db::name('member') ->where('id',$findMember['id'])->update(['shop_code'=>$shop_code,'nickname'=>$data['nickname']]);
                        //判断是否有余额
                        $moneytt = Db::name('member_money') ->where('member_id',$findMember['id']) ->field('money,online_money')->find();
                        $money = $moneytt['money'] + $moneytt['online_money'];
                        if( $money > 0 ){
                            //股东数据:公司总部反冲一笔,门店正充值一笔
                            $order_sn = 'CZ'.time().'1';
                            // 生成订单表信息
                            $order = array(
                                'user_id'	=>$this->getUserInfo()['id'],	//制单人
                                'is_admin'	=>0,
                                'shop_id'	=>1,
                                'member_id'	=>$findMember['id'],
                                'sn'		=>$order_sn,
                                'type'		=>3,
                                'amount'	=>'-'.$money,
                                'number'	=>1,
                                'pay_status'=>1,
                                'pay_way'	=>16,//公司转门店
                                'paytime'	=>time(),
                                'overtime'	=>time(),
                                'dealwithtime'=>time(),
                                'order_status'=>2,		//已完成
                                'add_time'	=>time(),
                                'is_online'	=>0,
                                'order_type'=>1,
                                'old_amount'=>'-'.$money,
                                'waiter'	=>self::getUserInfo()['name'],		//操作人员名字
                                'waiter_id'	=>self::getUserInfo()['id'],		//操作人员id
                                'remarks'	=>'会员转门店余额转换反充值'		//留言
                            );
                            $orderId1 = Db::name('order') ->insertGetId($order); //总部反充值
                            $rechargeLog = array(
                                'member_id'		=>$findMember['id'],
                                'shop_id'		=>1,
                                'price'			=>'-'.$money,
                                'pay_way'		=>16,        //公司转门店
                                'is_only_service'=>0,		//是否只限制服务使用：1只能服务使用,0都可使用(暂时无用)
                                'remarks'		=>'会员转门店余额转换反充值',
                                'create_time'	=>time(),
                                'order_id'      =>$orderId1
                            );
                            Db::name('member_recharge_log') ->insert($rechargeLog);//总部反充值
                            $statisticsLog = array(
                                'shop_id'		=>1,
                                'order_sn'		=>$order_sn,
                                'type'			=>1,
                                'data_type'	=>2,
                                'pay_way'		=>16,        //公司转门店
                                'price'			=>'-'.$money,
                                'create_time'	=>time(),
                                'order_id'      =>$orderId1
                            );
                            Db::name('statistics_log')->insert($statisticsLog);

                            //门店充值
                            $order_sn = 'CZ'.time().$shop_id;
                            // 生成订单表信息
                            $order = array(
                                'user_id'	=>$this->getUserInfo()['id'],	//制单人
                                'is_admin'	=>0,
                                'shop_id'	=>$shop_id,
                                'member_id'	=>$findMember['id'],
                                'sn'		=>$order_sn,
                                'type'		=>3,
                                'amount'	=>$money,
                                'number'	=>1,
                                'pay_status'=>1,
                                'pay_way'	=>16,
                                'paytime'	=>time(),
                                'overtime'	=>time(),
                                'dealwithtime'=>time(),
                                'order_status'=>2,		//已完成
                                'add_time'	=>time(),
                                'is_online'	=>0,
                                'order_type'=>1,
                                'old_amount'=>$money,
                                'waiter'	=>self::getUserInfo()['name'],		//操作人员名字
                                'waiter_id'	=>self::getUserInfo()['id'],		//操作人员id
                                'remarks'	=>'会员转门店余额充值'		//留言
                            );
                            $orderId1 = Db::name('order') ->insertGetId($order); //总部反充值
                            $rechargeLog = array(
                                'member_id'		=>$findMember['id'],
                                'shop_id'		=>$shop_id,
                                'price'			=>$money,
                                'pay_way'		=>16,
                                'is_only_service'=>0,		//是否只限制服务使用：1只能服务使用,0都可使用(暂时无用)
                                'remarks'		=>'会员转门店余额充值',
                                'create_time'	=>time(),
                                'order_id'      =>$orderId1
                            );
                            Db::name('member_recharge_log') ->insert($rechargeLog);//总部反充值
                            $statisticsLog = array(
                                'shop_id'		=>$shop_id,
                                'order_sn'		=>$order_sn,
                                'type'			=>1,
                                'data_type'	=>1,
                                'pay_way'		=>16,
                                'price'			=>$money,
                                'create_time'	=>time(),
                                'order_id'      =>$orderId1
                            );
                            Db::name('statistics_log')->insert($statisticsLog);
                        }
                        // 提交事务
                        Db::commit();
                    } catch (\Exception $e) {
                        // 回滚事务
                        Db::rollback();
                        return $e->getMessage();
                    }
                    return json(['code'=>'200','msg'=>'会员更改门店成功','data'=>'']);
                }
                return json(['code'=>'-5','msg'=>'会员账户已存在','data'=>'']);
            }

            $Member = new Tp5MemberModel();
            $shop_id = $this->getUserInfo()['shop_id'];
            
            $data['shop_code'] = $Member ->getShopcode($shop_id);
            $data['level_id'] = 1;  //默认为普通会员
            $data['regtime'] = time();

            //余额数据
            $moneyData = array(
                'mobile'  =>$data['mobile'],
                'money'   =>'0.00',
              );
            // 启动事务
            Db::startTrans();
            try {
                $result = $Member ->insertGetId($data);
                $moneyData['member_id'] = $result;
                Db::name('member_money')->insert($moneyData);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return $e->getMessage();
            }
            return json(['code'=>'200','msg'=>'添加成功','data'=>'']);
        }

        /***
         * 编辑会员
         * @return \think\response\Json
         * @throws \think\db\exception\DataNotFoundException
         * @throws \think\db\exception\ModelNotFoundException
         * @throws \think\exception\DbException
         */
        public function edit(){
            $data = $this ->request ->param();
            if( empty($data['member_id']) || empty($data['nickname']) ){
                return json(['code'=>'100','msg'=>'请输入昵称','data'=>'']);
            }
            //查询本门店的shop_code
            $shop_code = Db::name('shop')->where('id',self::getUserInfo()['sid'])->value('code');
            //查询编辑的会员是否为本门店的会员
            $member = Db::name('member') ->where('id',$data['member_id']) ->find();
            if( !$member ){
                return json(['code'=>'100','msg'=>'会员出错','data'=>'']);
            }
            if( $member['shop_code'] != $shop_code ){
                return json(['code'=>'100','msg'=>'此会员不是本门店会员','data'=>'']);
            }
            if( $member['nickname'] == $data['nickname'] ){
                return json(['code'=>'100','msg'=>'会员名未更改','data'=>'']);
            }
            $res = Db::name('member') ->where('id',$data['member_id']) ->setField('nickname',$data['nickname']);
            if( !$res ){
                return json(['code'=>'100','msg'=>'编辑失败','data'=>'']);
            }else{
                return json(['code'=>'200','msg'=>'编辑成功','data'=>'']);
            }
        }

        //会员等级说明
        public function level_info(){
            $shop_id = $this->getUserInfo()['shop_id'];
            $list = Db::name('level_price')
                  ->alias('a')
                  ->where('a.shop_id',$shop_id)
                  ->join('member_level b','a.level_id=b.id')
                  ->order('b.id asc')
                  ->field('a.price,b.level_name')
                  ->select();
            return json(['code'=>'200','msg'=>'添加成功','data'=>$list]);
        }

        //获取仓库
        public function shop_list(){
            $data = $this ->request->param();
            if( empty($data['page']) ){
                $page = '';
            }else{
                $page = $data['page'];
            }
            $where = [];
            $where[] = ['status','eq',1];
            $where[] = ['id','<>',1];
            $list = Db::name('shop')->where($where)->field('id,name,code')->select();
            return json(['code'=>200,'msg'=>'查询成功','data'=>$list]);
        }
}
