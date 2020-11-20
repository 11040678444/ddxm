<?php
namespace app\shift\controller;

use app\shift\model\member\MemberModel;	//会员表
use app\shift\model\member\MemberCardModel;	//供应商表
use app\shift\model\level\LevelModel;	//等级表
use app\shift\model\shop\ShopModel;	//门店表
use app\shift\model\shop\WorkerModel;	//服务人员表
use app\shift\model\shop\UserModel;	//店长表
use app\shift\model\item\ItemCategoryModel;	//商品分类表
use app\shift\model\item\ItemModel;	//商品表
use app\shift\model\supplier\SupplierModel;	//供应商表
use app\shift\model\service\ServiceModel;	//服务表
use app\shift\model\service\ServiceCardModel;	//服务表
use app\shift\model\service\CardModel;	//会员卡表
use app\shift\model\service\YuyueModel;	//会员卡使用表
use app\shift\model\order\OrderModel;	//供应商表MemberCardModel
use app\shift\model\service\OldcardModel;

//小程序，是用的是renkunhong数据库
use app\shift\model\item\ItemsamllModel;
use app\shift\model\item\SpecsModel;

use think\Controller;
use think\Db;
use think\Exception;
use think\Query;
use think\Request;
use app\admin\model\allot\AllotModel;
/**
	将其他数据库数据转到ddxmmtp5数据库
*/
class Shift
{	
	/**/
	public function memberList(){
		 set_time_limit(0);
		//truncate table ddxm_member_money
		$Member = new MemberModel();
		$count = $Member->count();
		$count = (int)($count/200)+1;
		for ($i=1; $i<=$count ; $i++) { 
			$result = $this ->member($i);
			if( $result == 200 ){
				echo "添加失败";
				break;
			}
		}
		dump('添加成功');
	}

	/**
		会员表数据
	*/
	public function member($i){
		$Member = new MemberModel();
		$page = $i.',200';
		$oldMember = $Member->page($page)->select();
		if( empty($oldMember) ){
			return 200;
		}
		// dump($oldMember);die;				//4136
		//构造数据
		$newMember = [];  //member数据
		$newMemberData = [];	//member_data数据
		$newMemberDetails = [];  //member_details数据
		$newMemberMoney = [];	//ddxm_member_money数据
		foreach ($oldMember as $key => $value) {
			//member数据
			$newMember1['id'] = $value['id'];
			$newMember1['pid'] = $value['pid'];
			$newMember1['no'] = $value['no'];
			$newMember1['mobile'] = $value['mobile'];
			$newMember1['shop_code'] = $value['shop_code'];
			$newMember1['realname'] = $value['realname'];
			$newMember1['level_id'] = $value['level_id'];
			$newMember1['openid'] = $value['openid'];
			$newMember1['pic'] = $value['pic'];
			$newMember1['nickname'] = $value['nickname'];
			$newMember1['regtime'] = $value['regtime'];
			$newMember1['is_staff'] = $value['is_staff'];
			$newMember1['status'] = $value['status'];
//			$newMember1['source'] = 2;
			array_push($newMember, $newMember1);

			//member_data数据
			$newMemberData1['member_id'] = $value['id'];
			$newMemberData1['mobile'] = $value['mobile'];
			$newMemberData1['profit'] = $value['profit'];
			$newMemberData1['paypwd'] = $value['paypwd'];
			$newMemberData1['logintime'] = $value['logintime'];
			$newMemberData1['platform'] = $value['platform'];
			$newMemberData1['declare'] = $value['declare'];
			array_push($newMemberData, $newMemberData1);

			//member_details数据
			$newMemberDetails1['member_id'] = $value['id'];
			$newMemberDetails1['mobile'] = $value['mobile'];
			$newMemberDetails1['amount'] = $value['amount'];
			$newMemberDetails1['remarks'] = '会员之前的累计充值';
			$newMemberDetails1['reason'] = '会员之前的累计充值';
			$newMemberDetails1['addtime'] = time();
			$newMemberDetails1['type'] = 1; //计算成累计充值
			$newMemberDetails1['order_id'] = 0;
			array_push($newMemberDetails, $newMemberDetails1);

			//ddxm_member_money数据
			$newMemberMoney1['member_id'] = $value['id'];
			$newMemberMoney1['mobile'] = $value['mobile'];
			$newMemberMoney1['money'] = $value['money'];
			$newMemberMoney1['server_item_money'] = 0;	//
			$newMemberMoney1['server_money'] = 0;	//
			$newMemberMoney1['item_money'] = 0;	//
			$newMemberMoney1['score_item'] = $value['score_item'];
			$newMemberMoney1['score_server'] = $value['score_server'];
			array_push($newMemberMoney, $newMemberMoney1);
		}

		// 启动事务
		Db::startTrans();
		try {

		    Db::name('member')->insertAll($newMember);
		    Db::name('member_money')->insertAll($newMemberMoney);
		    Db::name('member_data')->insertAll($newMemberData);
		    Db::name('member_details')->insertAll($newMemberDetails);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    echo $e->getMessage();die;
		}
		return 1 ;
	}

	/**
		等级
	*/
	public function member_level(){
		$Level = new LevelModel();
		$oldLevel = $Level ->select();
		$newarray = [];
		foreach ($oldLevel as $key => $value) {
			$arr = array(
					'id'	=>$value['id'],
					'level_name'	=>$value['level_name'],
					'add_time'	=>$value['add_time'],
					'status'	=>$value['status'],
					'sort'	=>$value['sort'],
					'update_time'	=>time(),
					'user_id'	=>1,
				);
			array_push($newarray, $arr);
		}
		$result = Db::name('member_level')->insertAll($newarray);
		dump($result);
	}

	//门店等级价格
	public function level_price(){
		$Shop = new ShopModel();
		$shop = $Shop ->field('id,level_standard')->select();
		$price = [];	//
		foreach ($shop as $key => $value) {
			if( !empty($value['level_standard']) ){
				foreach ($value['level_standard'] as $k => $v) {
					$arr = array(
						'shop_id'	=>$value['id'],
						'level_id'	=>$k,
						'price'		=>$v
					);
					array_push($price, $arr);
				}
			}
		}
		$t = Db::name('level_price')->insertAll($price);
		dump($t);die;
	}

	/**
		门店
	*/
	public function shop(){
		$Shop = new ShopModel();
		$where[] = ['id','not in','18,32,33,43,44,45'];
		$shop = $Shop->where($where) ->select();
		$list = [];
		foreach ($shop as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'code'	=>$value['code'],
				'name'	=>$value['name'],
				'addtime'	=>$value['addtime'],
				'status'	=>$value['status'],
				'update_time'	=>time(),
			);
			array_push($list, $arr);
		}
		$list = Db::name('shop')->insertAll($list);
		dump($list);
	}

	//门店店长
	public function worker(){
		$shopIds = Db::name('shop')->column('id');
		$shopIds = implode(',', $shopIds);
		$User = new UserModel();
		$info = $User ->getshopowner($shopIds,5);


		foreach ($info as $key => $value) {
			if( count($value['shop_id']) >1 ){
				unset($info[$key]);
			}

			if( $value['shop_id']['0'] == 18 || $value['shop_id']['0'] == 44 ){
				unset($info[$key]);	
			}
		}
		//先加入店长
		$list = [];
		$authCode = 'OV4w80Ndr23wt4yW1j';

		foreach ($info as $key => $value) {
			$arr = array(
				'user_id'	=>1,
				'workid'	=>'',
				'name'		=>$value['user_login'],
				'head'		=>'',
				'detail'		=>'',
				'type'		=>0,
				'pid'		=>0,
				'sid'		=>$value['shop_id']['0'],
				'status'		=>$value['user_status'],
				'addtime'		=>time(),
				'iswork'		=>1,
				'mobile'		=>$value['user_email'],
				'lv'		=>'',
				'remark'		=>'',
				'pay'		=>0,
				'post_id'		=>1,
				'update_time'		=>time(),
				'delete_time'		=>0,
				'delete_id'		=>0,
				'sex'		=>$value['sex'],
				'entry_time'		=>'',
				'password'		=>"###" . md5(md5($authCode . '123456')),
			);
			array_push($list, $arr);
		}
		$res = Db::name('shop_worker')->insertAll($list);
		dump($res);
	}

	//商品分类
	public function itemcategory(){
		$ItemCategory = new ItemCategoryModel();
		$list = $ItemCategory ->select();
		$info = [];
		foreach ($list as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'pid'	=>$value['pid'],
				'cname'	=>$value['cname'],
				'thumb'	=>$value['thumb'],
				'status'=>$value['status'],
				'sort'	=>$value['sort'],
				'addtime'	=>$value['addtime'],
				'delete_time'	=>0,
				'update_time'	=>time(),
				'update_id'	=>1,
				'delete_id'	=>0,
				'user_id'	=>1,
				'type'	=>1,
			);
			array_push($info, $arr);
		}
		$result = Db::name('item_category')->insertAll($info);
		dump($result);
	}

	//供应商
	public function supplier(){
		$Supplier = new SupplierModel();
		$list = $Supplier ->select();
		$info = [];
		foreach ($list as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'supplier_name'	=>$value['name'],
				'contacts'	=>$value['linkman'],
				'mobile'	=>$value['tel'],
				'remarks'	=>$value['remark'],
				'del'	=>$value['status'],
				'update_time'	=>$value['time'],
				'creater'	=>'admin',
				'creater_id'	=>1,
				'del_time'	=>0,
				'del_staff'	=>0,
				'update_id'	=>1,
				
			);
			array_push($info, $arr);
		}
		$result = Db::name('supplier')->insertAll($info);
		dump($result);
	}

	/*
	//添加服务项目
	public function addservice(){
		for ($i=1; $i <= 20; $i++) { 
			$t = $this ->service($i);
		}
		dump(1);
	}

	public function service($i){
		$info = [];
		//服务表
		$arr = array(
			'sname'		=>'游泳'.$i,
			'type'		=>rand(1,3),
			'status'	=>1,
			'create_time'=>time(),
			'update_time'=>time(),
			'user_id'	=>1,
			'update_id'=>1,
			'sort'		=>rand(1,9)
		);
		//服务价格表
		for($j=1;$j<=7;$j++){
			$arr1 = array(
				'shop_id'	=>6,
				'level_id'	=>$j,
				'status'=>1,
				'price'=>100,
			);
			array_push($info, $arr1);
		}
		// 启动事务
		Db::startTrans();
		try {
		    $id = Db::name('service')->insertGetId($arr);
		    foreach ($info as $key => $value) {
		    	$info[$key]['service_id'] = $id;
		    }
		    Db::name('service_price')->insertAll($info);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		}
	}
	*/

	//商品
	public function item(){
		// SELECT title,count(*) as count FROM tf_item GROUP BY title HAVING count>1   查询title相同的商品并统计数量
		set_time_limit(0);
		$Item = new ItemModel();
//		$Shop = new ShopModel();
//		$shop = $Shop ->select();
		$shop = Db::name('shop')->select();
		$count = $Item->count();
		$count = (int)($count/100)+1;
		for ($i=1; $i<=$count ; $i++) { 
			$result = $this ->item1($i,$shop);
			if( $result == 200 ){
				echo "添加失败";
				break;
			}
		}
		dump('添加成功');
	}
	public function item1($i,$shop){
		$Item = new ItemModel();
		$page = $i.',100';
		$where = [];
		$where[] = ['item_type','in','2,3'];
		$where[] = ['bar_code','neq',''];
		$where[] = ['title','neq','亚工-30W杀菌管'];
		$where[] = ['title','neq','小萌希奥纸尿裤'];
		$where[] = ['title','neq','康挺健身架'];
		$where[] = ['title','neq','洗衣机'];
		$where[] = ['title','neq','游泳衣'];
		$where[] = ['title','neq','美杰75度酒精'];
		$where[] = ['title','neq','美杰碘伏'];
		$where[] = ['title','neq','鳕鱼鳕鱼肝油软胶囊'];
		$oldItem = $Item->page($page)->where($where)->select()->append(['tt','pic']);



		if( empty($oldItem) ){
			return 200;
		}
		$item = [];	//商品
		$price = [];	//商品价格
		foreach ($oldItem as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'title'	=>$value['title'],
				'item_type'	=>$value['item_type'],
				'type_id'	=>$value['tt'],
				'type'	=>$value['type'],
				'unit_id'	=>1,
				'cate_id'	=>$value['type_id'],
				'specs_id'	=>1,
				'video_href'	=>$value['video_href'],
				'lvid'	=>$value['lvid'],
				'reality_sales'	=>$value['sales'],
				'status'	=>$value['status'],
				'bar_code'	=>$value['bar_code'],
				'pic'	=>$value['pic'],
				'pics'	=>$value['pics'],
				'content'	=>$value['content'],
				'time'	=>$value['time'],
				'stock_alert'	=>$value['stock_alert'],
				'update_time'	=>time(),
				'delete_time'	=>0,
				'delete_id'	=>0,
				'in_allshop'	=>1,
				'sort'	=>0,
			);
			array_push($item, $arr);
			foreach ($shop as $k => $v) {
				$ar = array(
					'user_id'	=>1,
					'status'	=>1,
					'shop_id'	=>$v['id'],
					'item_id'	=>$value['id'],
					'selling_price'=>$value['price'],
					'minimum_selling_price'=>0
				);
				array_push($price,$ar);
			}
		}
		Db::startTrans();
		try {
		    Db::name('item')->insertAll($item);
		    Db::name('item_price')->insertAll($price);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    dump($e->getMessage());die;
		}
		return 1;
	}

    //服务商品,商品价钱从门店表导入
    public function service(){
        set_time_limit(0);
        $Service = new ServiceModel();
        $where = [];
        $list = $Service->where($where)->select();
        $Shop = new ShopModel();
        $shopIds = Db::name('shop')->column('id');
        $where = [];
        $where[] = ['id','in',implode(',',$shopIds)];

        $shop = $Shop ->where($where)->select();
        $info = [];	//服务商品
        foreach ($list as $key => $value) {
            $arr = array(
                'id'	=>$value['s_id'],
                'sname'	=>$value['sname'],
                's_id'	=>$value['id'],     // 用于 到服务卡数据时的辅助字段；
                'status'	=>$value['status'],
                'create_time'	=>$value['addtime'],
                'bar_code'	=>$value['bar_code'],
                'is_online'	=>$value['is_online'],
                'cover'		=>$value['cover'],
                'icon'		=>$value['icon'],
                'remark'		=>$value['remark'],
                'update_time'		=>time(),
                'delete_time'		=>0,
                'user_id'		=>1,
                'update_id'		=>1,
                'sort'		=>1,
                'delete_id'		=>0,
                'type'  =>192
            );
            array_push($info, $arr);
        }
        $price = [];	//服务商品价格
        foreach ($shop as $k=>$v){
            $shop[$k]['service_level_price'] = json_decode($v['service_level_price'],true);
        }
        foreach ($list as $key => $value) {
            foreach ($shop as $k=>$v){
                if( count($v['service_level_price']) >0 ){
                    foreach ($v['service_level_price'] as $k1=>$v1){
                        if( $k1 == $value['s_id'] ){
                            foreach ($v1 as $k2=>$v2){
                                $arr = [];
                                $arr = [
                                    'shop_id'   =>$v['id'],
                                    'service_id'   =>$value['s_id'],
                                    'level_id'   =>$k2,
                                    'status'   =>1,
                                    'price'   =>$v2,
                                ];
                                array_push($price,$arr);
                            }
                        }

                    }
                }
            }
        }
        Db::startTrans();
        try {
            Db::name('service')->insertAll($info);
            Db::name('service_price')->insertAll($price);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            echo $e->getMessage();die;
        }
        dump('添加成功');
    }

	//充值订单
	public function recharge(){
		// set_time_limit(0);
		$Order = new OrderModel();
		$count = $Order->count();
		$count = (int)($count/50)+1;
		for ($i=1; $i<=$count ; $i++) { 
			$result = $this ->recharge1($i);
			if( $result == 200 ){
				echo "添加失败";
				break;
			}
		}
		dump('添加成功');
	}

	public function recharge1($i)
	{
		$MemberCard = new MemberCardModel();
		$page = $i.',50';
		$Order = new OrderModel();
		$shopIds = Db::name('shop')->column('id');
		$shopIds = implode(',', $shopIds);
		$where[] = ['type','=',3];
		$where[] = ['member_id','=',5055];
		// $where[] = ['shop_id','in',$shopIds];
		$list = $Order ->getList($where,$page)->select();
		foreach ($list as $key => $value) {
			$exsit = $MemberCard ->getcard($value['id']);
			if( $exsit ){
				//判断是充值还是购卡，购卡则pass掉
				unset($list[$key]);
			}
		}
		dump($list);die;
		//制单人:当前门店的店长，服务人员:线上商品没有，门店默认为店长
		foreach ($list as $key => $value) {
			$worker = Db::name('shop_worker')->where(['sid'=>$value['shop_id'],'post_id'=>1])->field('id,name')->find();
			$list[$key]['user_id'] = $worker['id'];
			if( $value['is_online'] == 0 ){
				$list[$key]['waiter_id'] = $worker['id'];
				$list[$key]['waiter'] = $worker['name'];
			}else{
				$list[$key]['waiter_id'] = '';
				$list[$key]['waiter'] = '';
			}
		}
		$info = [];	//充值订单数据
		dump($list);die;
		foreach ($list as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'shop_id'	=>$value['shop_id'],
				'member_id'	=>$value['member_id'],
				'card_id'	=>$value['card_id'],
				'yy_id'	=>$value['yy_id'],
				'sn'	=>$value['sn'],
				'type'	=>$value['type'],
				'pay_sn'	=>$value['pay_sn'],
				'realname'	=>$value['realname'],
				'detail_address'	=>$value['detail_address'],
				'mobile'	=>$value['mobile'],
				'voucher_ids'	=>'',
				'ticket_id'	=>'',
				'number'	=>$value['number'],
				'discount'	=>$value['discount'],
				'postage'	=>$value['postage'],
				'amount'	=>$value['amount'],
				'pay_status'	=>$value['pay_status'],
				'send_way'	=>$value['send_way'],
				'pay_way'	=>$value['pay_way'],
				'paytime'	=>$value['paytime'],
				'sendtime'	=>$value['sendtime'],
				'fixtime'	=>$value['fixtime'],
				'overtime'	=>$value['overtime'],
				'returntime'	=>$value['returntime'],
				'dealwithtime'	=>$value['dealwithtime'],
				'canceltime'	=>$value['canceltime'],
				'order_status'	=>$value['order_status'],
				'evaluate'	=>$value['evaluate'],
				'isdel'	=>$value['isDel'],
				'is_online'	=>$value['is_online'],
				'waiter'	=>$value['waiter'],
				'is_examine'	=>$value['check_status'],
				'order_type'	=>$value['order_type'],
				'old_amount'	=>$value['old_amount'],
				'integral_amount'	=>$value['integral_amount'],

				'order_triage'	=>0,
				'service_money_id'	=>0,
				'is_outsourcing_goods'	=>0,
				'waiter_id'	=>$value['waiter_id'],
				'user_id'	=>$value['user_id'],
				'is_admin'	=>0,
				'remarks'	=>'',
				'refund_num'	=>0,
			);
			array_push($info,$arr);
		}
		dump($info);die;
	}
 
	//股东列表
	public function service_card(){
		$shopIds = Db::name('shop')->column('id');
		$User = new UserModel();
		$info = $User ->getgudong(8);
		foreach ($info as $key => $value) {
			foreach ($value['shop_id'] as $k => $v) {
				if( !in_array($v, $shopIds) ){
					unset($info[$key]['shop_id'][$k]);
				}
			}
		}
		foreach ($info as $key => $value) {
			$info[$key]['shop_id1'] = ','.implode(',',$value['shop_id']).',';
			unset($info[$key]['shop_id']);
		}
		$list = [];
		foreach ($info as $key => $value) {
			$arr = array(
				'id'	=>$value['id'],
				'mobile'=>$value['user_email'],
				'shop_ids'=>$value['shop_id1'],
				'user_id'=>1,
				'create_time'=>time(),
				'delete_time'=>0,
				'delete_id'=>0,
				'status'=>$value['user_status']
			);
			array_push($list,$arr);
		}
		$res = Db::name('shareholder')->insertAll($list);
		dump($res);
	}

	/*
	public function tt(){
		$arr = array(
			'0'	=>array(
				'title'	=>'分区添加',
				'app'	=>'admin',
				'controller'=>'item',
				'action'	=>'cate_add'
			),
			'1'	=>array(
				'title'	=>'单位添加',
				'app'	=>'admin',
				'controller'=>'item',
				'action'	=>'unit_add'
			),
			'2'	=>array(
				'title'	=>'规格添加',
				'app'	=>'admin',
				'controller'=>'item',
				'action'	=>'specs_add'
			),
			'3'	=>array(
				'title'	=>'服务卡添加',
				'app'	=>'admin',
				'controller'=>'ticket',
				'action'	=>'add'
			),
			'3'	=>array(
				'title'	=>'帮助添加',
				'app'	=>'admin',
				'controller'=>'help',
				'action'	=>'add'
			),
			'4'	=>array(
				'title'	=>'支出类型添加',
				'app'	=>'admin',
				'controller'=>'expenditure',
				'action'	=>'add'
			),
			'5'	=>array(
				'title'	=>'支出添加',
				'app'	=>'admin',
				'controller'=>'expenditure',
				'action'	=>'add1'
			),
			'6'	=>array(
				'title'	=>'门店添加',
				'app'	=>'admin',
				'controller'=>'shop',
				'action'	=>'add'
			),
			'7'	=>array(
				'title'	=>'岗位添加',
				'app'	=>'admin',
				'controller'=>'shop',
				'action'	=>'add_post'
			),
			'8'	=>array(
				'title'	=>'员工添加',
				'app'	=>'admin',
				'controller'=>'shop',
				'action'	=>'add_worker'
			),
			'9'	=>array(
				'title'	=>'股东添加',
				'app'	=>'admin',
				'controller'=>'shop',
				'action'	=>'sharegolder_add'
			),
			'10'	=>array(
				'title'	=>'服务添加',
				'app'	=>'admin',
				'controller'=>'service',
				'action'	=>'add'
			),
			'11'	=>array(
				'title'	=>'服务分类添加',
				'app'	=>'admin',
				'controller'=>'service',
				'action'	=>'catetory_add'
			),
			'12'	=>array(
				'title'	=>'分润添加',
				'app'	=>'admin',
				'controller'=>'epiboly',
				'action'	=>'add'
			)
		);
		Db::name('menu')->insertAll($arr);
	}
	*/

	public function cha_card(){
        $ServiceCard = new OldcardModel();
        set_time_limit(0);
        $where = [];
        $info = $ServiceCard ->old_card();
        $list = Db::name('ticket_card') ->column('id');
        $res = [];
        foreach ($info as $k=>$v){
            if( !in_array($v['id'],$list) ){
                array_push($res,$v);
            }
        }

    }

    /***
     * 查询是购买的服务卡否存在
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCard(){
        set_time_limit(0);
        $ser_card = Db::name('ticket_card')  ->column('id');        //本地所有的服务卡id集合
        $serviceIds = Db::name('service') ->column('id');       //本地所以的服务项目id集合
        $CardModel = new CardModel();
        $payCard = $CardModel ->group('card_id') ->order('card_id asc')->select()->toArray();   //老系统会员购买的服务卡集合
        $list = [];     //不存在的服务卡
        foreach ( $payCard as $k=>$v ){
            if( !in_array($v['card_id'],$ser_card) ){
                array_push($list,$v);
            }
        }
//        dump($list);die;
        foreach ($list as $k=>$v){
            $list[$k]['service_id'] = explode(',', $v['service_id']);;
            $list[$k]['server_list'] = json_decode($v['server_list'],true);
            $list[$k]['shop_id'] = explode(',', $v['shop_id']);
            $list[$k]['service_id'] = explode(',', $v['service_id']);
            $num = count($list[$k]['service_id']);
            $list[$k]['pingjun_money'] = $v['money']/$num;
        }
        $noServiceIds = [];     //不存在的服务项目id集合
        foreach ($list as $k=>$v){
            foreach ( $v['service_id'] as $k1=>$v1 ){
                if( !in_array($v1,$serviceIds) ){
                    array_push($noServiceIds,$v1);
                }
            }
        }
//        dump($noServiceIds);
//        dump($payCard);
//        dump($list);die;


        $card = [];	//服务卡列表	ddxm_ticket_card
        $card_price = [];	//价格	ddxm_ticket_money
        $card_shop = [];	//服务卡门店 ddxm_ticket_shop
        $card_service = [];	//ddxm_ticket_service
        $card_service_money = [];	//服务金额 ddxm_ticket_service_money


        foreach ($list as $key=>$value){
            $arr['id'] = $value['card_id'];
            $arr['card_name'] = $value['card_name'];
//            $arr['cover'] = $value['cover'];
            $arr['critulation'] = 0;	//发行量
            $arr['exchange_num'] = 0;	//剩余兑换量
            $arr['restrict_num'] = 0;	//单人限制
            $arr['start_time'] = 0;
            $arr['end_time'] = 0;
            $arr['integral_price'] = 0;
            $arr['create_time'] = $value['addtime'];
            $arr['status'] = 1;
            $arr['del'] = 1;
            $arr['creator_id'] = 1;
            $arr['update_time'] = time();
            $arr['modifier'] = 1;
            if( $value['type'] == 2 ){
                $arr['type'] = 1;	//次卡
                $use_day = 365;
                $month = 0;
                $year = 0;
            }else if( $value['type'] == 1 && $value['expire_month']<12 ){
                $arr['type'] = 2;	//月卡
                $use_day = 0;
                $month = $value['expire_month'];
                $year = 0;
            }else if( $value['type'] == 1 && $value['expire_month']>=12 ){
                $arr['type'] = 4;	//年卡
                $use_day = 0;
                $month = 0;
                $year = ceil($value['expire_month']/12);
            }
            $arr['term_of_validity'] = $value['expire_month'];
            $arr['all_shop'] = 1;
            $arr['give'] =	0;
            $arr['day'] = 365;
            $arr['use_day'] = $use_day;
            $arr['month'] = $month;
            $arr['year'] = $year;
            $arr['display'] = 0;
            //服务卡列表
            array_push($card,$arr);

            //服务卡金额
            for( $i=1;$i<7;$i++ ){
                $priceArr = array(
                    'card_id'	=>$value['card_id'],
                    'level_id'	=>$i,
                    'price'		=>$value['money'],
                    'mprice'	=>0,
                    'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
                );
                array_push($card_price, $priceArr);	//服务卡总金额
            }

            //服务卡 服务项目$card_service
            foreach ($value['service_id'] as $k => $v) {
                if( $value['type'] == 2 ){
                    $serviceArr['num'] = $value['server_list'][$k]['num'];
                }else{
                    $serviceArr['num'] = 0;
                }
                $serviceArr['card_id'] = $value['card_id'];
                $serviceArr['service_id'] = $v;
                $serviceArr['service_name'] = Db::name('service')->where('id',$v)->value('sname');
                $serviceArr['day'] = 0;
                $serviceArr['month'] = 0;
                $serviceArr['year'] = 0;
                array_push($card_service,$serviceArr);	//服务项目

                //$card_service_money 单服务金额
                for( $i=1;$i<7;$i++ ){
                    if( count($value['service_id']) == 1 ){
                        //只有一个服务
                        $money = $value['money'];
                    }else{
                        //多个服务
                        if( count($value['server_list']) > 0 ){
                            $money = $value['server_list'][$k]['money'];
                        }else{
                            $money = $value['pingjun_money'];
                        }
                    }
                    $moneyArr = array(
                        'ts_id'	=>'',		//$card_service,ddxm_ticket_service的id
                        'level_id'	=>$i,
                        'price'		=>$money,
                        'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
                    );
                    array_push($card_service_money, $moneyArr);	//$card_service_money 单服务金额
                }
            }


        }

        // 启动事务
        Db::startTrans();
        try {
            Db::name('ticket_card')	->insertAll($card);
            Db::name('ticket_money')->insertAll($card_price);
            Db::name('ticket_shop')	->insertAll($card_shop);
            foreach ($list as $key => $value) {
                //服务卡 服务项目$card_service
                //服务卡服务项目金额
                foreach ($value['service_id'] as $k => $v) {
                    if( $value['type'] == 2 ){
                        $serviceArr['num'] = $value['server_list'][$k]['num'];
                    }else{
                        $serviceArr['num'] = 0;
                    }
                    $serviceArr['card_id'] = $value['id'];
                    $serviceArr['service_id'] = $v;
                    $serviceArr['service_name'] = Db::name('service')->where('id',$v)->value('sname');
                    $serviceArr['day'] = 0;
                    $serviceArr['month'] = 0;
                    $serviceArr['year'] = 0;
                    $serviceArrId = Db::name('ticket_service')	->insertGetId($serviceArr);	//服务项目
                    $card_service_money = [];
                    for( $i=1;$i<7;$i++ ){
                        if( count($value['service_id']) == 1 ){
                            //只有一个服务
                            $money = $value['money'];
                        }else{
                            //多个服务
                            if( count($value['server_list']) > 0 ){
                                $money = $value['server_list'][$k]['money'];
                            }else{
                                $money = $value['pingjun_money'];
                            }
                        }
                        $moneyArr = array(
                            'ts_id'	=>$serviceArrId,		//$card_service,ddxm_ticket_service的id
                            'level_id'	=>$i,
                            'price'		=>$money,
                            'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
                        );
                        array_push($card_service_money, $moneyArr);	//$card_service_money 单服务金额
                    }
                    Db::name('ticket_service_money')->insertAll($card_service_money);
                }
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e ->getMessage());
        }
        dump('添加成功');

    }

    /***
     * 查询激活时间与国企时间一样的购买的服务卡
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGuoqi(){
        $where[] = ['start_time','neq',0];
        $list = Db::name('ticket_user_pay') ->where($where) ->select();
        $info = [];
        foreach ( $list as $k=>$v ){
            if( $v['start_time'] == $v['end_time'] ){
                array_push($info,$v['id']);
            }
        }
        $ids = implode(',',$info);
        $where = [];
        $where[] = ['id','in',$ids];
        Db::name('ticket_user_pay') ->where($where) ->setField('end_time',0);
        dump($ids);
    }

	//服务卡列表
	public function serviceCard(){

		$ServiceCard = new ServiceCardModel();
		set_time_limit(0);
		$where = [];
//		$where[] = ['service_id','neq',0];
		$info = $ServiceCard ->where($where)->order('id desc')->select();
        dump($info);die;

	    /*
        $ServiceCard = new OldcardModel();
        set_time_limit(0);
        $where = [];
        $info = $ServiceCard ->old_card();
        $list = Db::name('ticket_card') ->column('id');
        $res = [];
        foreach ($info as $k=>$v){
            if( !in_array($v['id'],$list) ){
                array_push($res,$v);
            }
        }
        $info = $res;
        */

		foreach ($info as $key => $value) {
			$info[$key]['server_list'] = json_decode($value['server_list'],true);
			$info[$key]['shop_id'] = explode(',', $value['shop_id']);
			$info[$key]['service_id'] = explode(',', $value['service_id']);
			$num = count($info[$key]['service_id']);
			$info[$key]['pingjun_money'] = $value['money']/$num;
		}

		$card = [];	//服务卡列表	ddxm_ticket_card
		$card_price = [];	//价格	ddxm_ticket_money
		$card_shop = [];	//服务卡门店 ddxm_ticket_shop
		$card_service = [];	//ddxm_ticket_service
		$card_service_money = [];	//服务金额 ddxm_ticket_service_money
		foreach ($info as $key => $value) {
			$arr['id'] = $value['id'];
			$arr['card_id'] = '';
			$arr['card_name'] = $value['name'];
			$arr['cover'] = $value['cover'];
			$arr['critulation'] = 0;	//发行量
			$arr['exchange_num'] = 0;	//剩余兑换量
			$arr['restrict_num'] = $value['restrict_num'];	//单人限制
			$arr['start_time'] = 0;
			$arr['end_time'] = 0;
			$arr['integral_price'] = 0;
			$arr['create_time'] = $value['addtime'];
			$arr['status'] = $value['status'];
			$arr['del'] = $value['del'];
			$arr['creator_id'] = 1;
			$arr['update_time'] = '';
			$arr['modifier'] = '';
			if( $value['type'] == 2 ){
				$arr['type'] = 1;	//次卡
                $use_day = 365;
                $month = 0;
                $year = 0;
			}else if( $value['type'] == 1 && $value['expire_month']<12 ){
				$arr['type'] = 2;	//月卡
                $use_day = 0;
                $month = $value['expire_month'];
                $year = 0;
			}else if( $value['type'] == 1 && $value['expire_month']>=12 ){
				$arr['type'] = 4;	//年卡
                $use_day = 0;
                $month = 0;
                $year = ceil($value['expire_month']/12);
			}
			$arr['term_of_validity'] = $value['expire_month'];
			$arr['all_shop'] = 0;
			$arr['give'] =	0;
			$arr['day'] = 0;
			$arr['use_day'] = $use_day;
			$arr['month'] = $month;
			$arr['year'] = $year;
			$arr['display'] = 0;
			//服务卡列表
			array_push($card,$arr);	
			//服务卡金额
			for( $i=1;$i<7;$i++ ){
				$priceArr = array(
					'card_id'	=>$value['id'],
					'level_id'	=>$i,
					'price'		=>$value['money'],
					'mprice'	=>0,
					'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
				);
				array_push($card_price, $priceArr);	//服务卡总金额
			}
			//服务卡门店
			foreach ($value['shop_id'] as $v) {
				$shopArr = array(
					'card_id'	=>$value['id'],
					'shop_id'	=>$v,
					'shop_name'	=>Db::name('shop')->where('id',$v)->value('name')
				);
				array_push($card_shop, $shopArr);	//服务卡门店
			}
			//服务卡 服务项目$card_service
			foreach ($value['service_id'] as $k => $v) {
				if( $value['type'] == 2 ){
					$serviceArr['num'] = $value['server_list'][$k]['num'];
				}else{
					$serviceArr['num'] = 0;
				}
				$serviceArr['card_id'] = $value['id'];
				$serviceArr['service_id'] = $v;
				$serviceArr['service_name'] = Db::name('service')->where('id',$v)->value('sname');
				$serviceArr['day'] = 0;
				$serviceArr['month'] = 0;
				$serviceArr['year'] = 0;
				array_push($card_service,$serviceArr);	//服务项目

				//$card_service_money 单服务金额
				for( $i=1;$i<7;$i++ ){
					if( count($value['service_id']) == 1 ){
						//只有一个服务
						$money = $value['money'];
					}else{
						//多个服务
						if( count($value['server_list']) > 0 ){
							$money = $value['server_list'][$k]['money'];
						}else{
							$money = $value['pingjun_money'];
						}
						
					}
					$moneyArr = array(
						'ts_id'	=>'',		//$card_service,ddxm_ticket_service的id
						'level_id'	=>$i,
						'price'		=>$money,
						'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
					);
					array_push($card_service_money, $moneyArr);	//$card_service_money 单服务金额
				}
			}
		}
		// 启动事务
		Db::startTrans();
		try {
		    Db::name('ticket_card')	->insertAll($card);
		    Db::name('ticket_money')->insertAll($card_price);
		    Db::name('ticket_shop')	->insertAll($card_shop);
		    foreach ($info as $key => $value) {
		    	//服务卡 服务项目$card_service
		    	//服务卡服务项目金额
				foreach ($value['service_id'] as $k => $v) {
					if( $value['type'] == 2 ){
						$serviceArr['num'] = $value['server_list'][$k]['num'];
					}else{
						$serviceArr['num'] = 0;
					}
					$serviceArr['card_id'] = $value['id'];
					$serviceArr['service_id'] = $v;
					$serviceArr['service_name'] = Db::name('service')->where('id',$v)->value('sname');
					$serviceArr['day'] = 0;
					$serviceArr['month'] = 0;
					$serviceArr['year'] = 0;
					$serviceArrId = Db::name('ticket_service')	->insertGetId($serviceArr);	//服务项目
					$card_service_money = [];
					for( $i=1;$i<7;$i++ ){
						if( count($value['service_id']) == 1 ){
							//只有一个服务
							$money = $value['money'];
						}else{
							//多个服务
							if( count($value['server_list']) > 0 ){
								$money = $value['server_list'][$k]['money'];
							}else{
								$money = $value['pingjun_money'];
							}
						}
						$moneyArr = array(
							'ts_id'	=>$serviceArrId,		//$card_service,ddxm_ticket_service的id
							'level_id'	=>$i,
							'price'		=>$money,
							'level_name'=>Db::name('member_level')->where('id',$i)->value('level_name')
						);
						array_push($card_service_money, $moneyArr);	//$card_service_money 单服务金额
					}
					Db::name('ticket_service_money')->insertAll($card_service_money);
				}
		    }
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		    dump($e ->getMessage());
		}
		dump('添加成功');
	}

	//会员所拥有的会员卡
    public function member_card(){
	    set_time_limit(0);
		$CardModel = new CardModel();
		$YuyueModel = new YuyueModel();
		$where = [];
//		$where[] = ['id','>',1698];
		$CardModel->where($where)->chunk(50, function ($list) use ($CardModel, $YuyueModel) {
            //ddxm_ticket_user_pay 会员购买的服务卡，
            // 是否激活： 0 禁用，1未激活，2正常，3 已过期；    0 未激活 1待使用 2已使用，3已过期 4已退卡
		    $payArr = [];
		    foreach ($list as $key => $value) {
		    $error_id = $value['id'];
			//计算状态
			if( $value['status'] == 1 ){
				$status = 0;	//未激活
			}else if( $value['status'] == 3 ){
				$status = 3;
			}else if ( $value['status'] == 2 ){
			    $status = 1;
            }else if ( $value['status'] == 0 ){
                $status = 4;
            }
            if( $value['type'] == 2 ){
                $card_type = 1;	//此卡
                $day = 365; //设置使用天数
                $month = 0;
                $year = 0;
            }else if( $value['type'] == 1 && $value['expire_month']<12 ){
                $card_type = 2;	//月卡
                $day = 0;
                $month = $value['expire_month'];    //使用月数
                $year = 0;
            }else if( $value['type'] == 1 && $value['expire_month']>=12 ){
                $card_type = 4;	//年卡
                $day = 0;
                $month = 0;
                $year = $value['expire_month']/12;  //使用年数
            }
			//计算过期时间
			if( $value['active_time'] == 0 ){
			    //未激活
				$end_time = 0;
			}else{
				$active_time = $value['active_time'];
				$month = $value['expire_month'];
				$end_time = strtotime(date("Y-m-d H:i:s",strtotime("+$month month",$active_time)));
			}
			$arr = array(
				'id'	=>$value['id'],
				'order_id'	=>$value['order_id'],
				'shop_id'	=>$value['shop_id'],
				'member_id'	=>$value['member_id'],
				'mobile'	=>Db::name('member')->where('id',$value['member_id'])->value('mobile'),
				'ticket_id'	=>$value['card_id'],
				'status'	=>$status,		//有问题
				'price'	=>$value['old_money'],
				'real_price'	=>$value['money'],
				'start_time'	=>$value['active_time'],		//激活时间
				'end_time'	=>$end_time,
				'over_time'	=>0,
				'day'	=>$day,
				'month'	=>$month,
				'year'	=>$year,
				'refund'	=>0,
				'refund_time'	=>0,
				'type'	=>$card_type,
				'waiter'	=>Db::name('shop_worker')->where(['sid'=>$value['shop_id'],'post_id'=>1])->value('name'),
				'waiter_id'	=>Db::name('shop_worker')->where(['sid'=>$value['shop_id'],'post_id'=>1])->value('id'),
				'create_time'	=>$value['addtime'],
				'level_id'	=>Db::name('member')->where('id',$value['member_id'])->value('level_id'),
			);
//			dump($arr);die;
            $re = Db::name('ticket_user_pay')->insert($arr);      //循环一次添加一条购买记录
            /*生成激活记录，与消费明细记录*********************************************************************/
            $serviceId = $value['service_id'];
            $serviceId = explode(',',$serviceId);   //数组
            $serverList = json_decode($value['server_list'],true);
            if( $status !=0 ){
                //已激活，生成激活记录
                foreach ($serviceId as $k=>$v){
                    if( $value['type'] == 1 ){
                        //非次卡,不限制次数
                        $useArr = array(
                            'service_id'    =>$v,   //服务id
                            'ticket_id'     =>$value['id'], //ddxm_ticket_user_pay 的id
                            'num'           =>0,        //限制次数
                            'r_num'         =>$YuyueModel ->getCishu($value['id'],$v), //使用次数
                            's_num'         =>0,        //剩余次数
                            'start_year'    =>0,
                            'end_year'    =>0,
                            'start_month'    =>0,
                            'end_month'    =>0,
                            'start_day'    =>0,
                            'end_day'    =>0,
                            'year_num'    =>0,
                            'month_num'    =>0,
                            'day_num'    =>0,
                            'r_year'    =>0,
                            'r_month'    =>0,
                            'r_day'    =>0,
                            'money'    =>$value['money']/count($serviceId),
                        );
                        $useId = Db::name('ticket_use') ->insertGetId($useArr); //添加激活记录
                        //添加消费明细ddxm_ticket_consumption
                        $consumptionArr = $YuyueModel ->getxiao($value['id'],$v); //使用明细
                        $mingxiArr = [];
                        foreach ($consumptionArr as $k1=>$v1){
                            $arr11 = array(
                                'id'    =>$v1['id'],
                                'member_id'    =>$v1['member_id'],
                                'shop_id'    =>$v1['sid'],
                                'ticket_id'    =>$value['id'],
                                'service_id'    =>$v,
                                'service_name'    =>Db::name('service')->where('id',$v)->value('sname'),
                                'waiter'    =>Db::name('shop_worker')->where(['post_id'=>1,'sid'=>$v1['sid']])->value('name'),
                                'waiter_id'    =>Db::name('shop_worker')->where(['post_id'=>1,'sid'=>$v1['sid']])->value('id'),
                                'time'    =>$v1['addtime'],
                                'num'    =>$v1['num'],
                                'ts_id'    =>$useId,
                                'price'    =>$v1['price'],
                            );
                            array_push($mingxiArr,$arr11);
                        }
                        Db::name('ticket_consumption')->insertAll($mingxiArr);
                    }else{
                        //次卡,有限制次数，$serverList
                        $useArr1 = array(
                            'service_id'    =>$serverList[$k]['id'],   //服务id
                            'ticket_id'     =>$value['id'], //ddxm_ticket_user_pay 的id
                            'num'           =>$serverList[$k]['num'],        //限制次数
                            'r_num'         =>$YuyueModel ->getCishu($value['id'],$v), //使用次数
                            's_num'         =>$serverList[$k]['num'] -$YuyueModel ->getCishu($value['id'],$v),        //剩余次数
                            'start_year'    =>0,
                            'end_year'    =>0,
                            'start_month'    =>0,
                            'end_month'    =>0,
                            'start_day'    =>0,
                            'end_day'    =>0,
                            'year_num'    =>0,
                            'month_num'    =>0,
                            'day_num'    =>0,
                            'r_year'    =>0,
                            'r_month'    =>0,
                            'r_day'    =>0,
                            'money'    =>$serverList[$k]['money'],
                        );
                        $useId1 = Db::name('ticket_use') ->insertGetId($useArr1); //添加激活记录
                        //添加消费明细ddxm_ticket_consumption
                        $consumptionArr1 = $YuyueModel ->getxiao($value['id'],$v); //使用明细
                        $mingxiArr1 = [];
                        foreach ($consumptionArr1 as $k1=>$v1){
                            $arr111 = array(
                                'id'    =>$v1['id'],
                                'member_id'    =>$v1['member_id'],
                                'shop_id'    =>$v1['sid'],
                                'ticket_id'    =>$value['id'],
                                'service_id'    =>$v,
                                'service_name'    =>Db::name('service')->where('id',$v)->value('sname'),
                                'waiter'    =>Db::name('shop_worker')->where(['post_id'=>1,'sid'=>$v1['sid']])->value('name'),
                                'waiter_id'    =>Db::name('shop_worker')->where(['post_id'=>1,'sid'=>$v1['sid']])->value('id'),
                                'time'    =>$v1['addtime'],
                                'num'    =>$v1['num'],
                                'ts_id'    =>$useId1,
                                'price'    =>$v1['price'],
                            );
                            array_push($mingxiArr1,$arr111);
                        }
                        Db::name('ticket_consumption')->insertAll($mingxiArr1);
                    }
                }
            }
		}
        });	//购买的服务卡列表
        dump(11);
	}

//
//	/***
//     * ddxm_ticket_consumption会员服务卡使用记录
//     * 会员的服务卡使用记录
//     */
//	public function consumption(){
//        $Yuyue = new YuyueModel();
//        $where = [];
//        $where[] = ['isdel','eq',0];
//        $where[] = ['status','in','0,1'];
//        $count = $Yuyue ->where($where)->count();
//        $count = (int)($count/100)+1;
//        for ($i=1; $i<=$count ; $i++) {
//            $result = $this ->consumption1($i);
//            if( $result == 200 ){
//                echo "添加失败";
//                break;
//            }
//        }
//        dump('添加成功');
//    }
//	public function consumption1($i){
//        $Yuyue = new YuyueModel();
//        $where = [];
//        $where[] = ['isdel','eq',0];
//        $where[] = ['status','in','0,1'];
//        $page = $i.',100';
//        $list = $Yuyue ->where($where)->page($page)->select();
//        foreach ($list as $key=>$value){
//            $arr = array(
//                'id'    =>$value['id'],
//                'member_id' =>$value['member_id'],
//                'shop_id'   =>$value['sid'],
//                'ticket_id'   =>$value['user_card_id'],
//                'waiter'   =>$value['id'],
//                'waiter_id'   =>$value['id'],
//                'time'   =>$value['addtime'],
//                'num'   =>$value['num'],
//                'price'   =>$value['price'],
//
//                'service_id'   =>$value['id'],
//                'service_name'   =>$value['id'],
//                'ts_id'   =>$value['id']
//            );
//        }
//        dump($list);exit;
//    }
    public function test(){
	    set_time_limit(0);
            $t = $this ->udate('Y-m-d H:i:s u');
	        Db::name('test')->insert(['create_time'=>$t]);
        }

    function udate($format = 'u', $utimestamp = null)
    {
        if (is_null($utimestamp)) {
            $utimestamp = microtime(true);
        }
        $timestamp = floor($utimestamp);
        $milliseconds = round(($utimestamp - $timestamp) * 1000000);//改这里的数值控制毫秒位数
        return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
    }

    /**
     * 导入门店商品的库存
     */
    public function item_stock(){
        set_time_limit(0);
        $shopId = Db::name('shop')->column('id');
        $itemId = Db::name('item')->column('id');
        $data = file_get_contents('http://192.168.25.126/admin/stock_check/getStockList');
        $data = json_decode($data,true);
        $data = $data['data']['list'];
//        dump($data);die;
        $num = 200;
        $limit = ceil(count($data)/$num);
        for ( $i=1;$i<=$limit;$i++ ){
            $arr = [];
            $offset=($i-1)*$num;
            $arr = array_slice($data,$offset,$num);
            $purchasePrice = [];        //门店商品成本价
            $shopitem = [];     //门店库存展示表
            foreach ($arr as $k=>$v){

            if( in_array($v['item_id'],$itemId) && in_array($v['shop_id'],$shopId) ){
                $arrPrice = [];
                $arrPrice = [
                    'shop_id'   =>$v['shop_id'],
                    'type'   =>1,
                    'pd_id'   =>'',
                    'item_id'   =>$v['item_id'],
                    'md_price'   =>$v['store_cost'],
                    'store_cose'   =>$v['store_cost'],
                    'stock'   =>$v['stock'],
                    'time'   =>time(),
                    'sort'   =>0
                ];
                array_push($purchasePrice,$arrPrice);

                $arrShop = [];
                $arrShop = [
                    'shop_id'   =>$v['shop_id'],
                    'item_id'   =>$v['item_id'],
                    'stock'   =>$v['stock'],
                    'stock_ice'   =>0
                ];
                array_push($shopitem,$arrShop);
            }
            }
            // 启动事务
            Db::startTrans();
            try {
                Db::name('purchase_price') ->insertAll($purchasePrice);
                Db::name('shop_item') ->insertAll($shopitem);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                dump($e->getMessage());die;
            }
        }
        dump("添加成功---");
    }

    /***
     * 导入小程序数据 分类，分区，单位，快递公司，运费，服务，规格，商品，秒杀，拼团
     */

    public function xiaochengxuitem(){
        $Item = new ItemsamllModel();
//        $count = Db::name('item')->where('item_type',1)->delete();
//        dump($count);die;
        $item = $Item ->select()->append(['specs'])->toArray();     //商品信息
//        $assmble = $Item ->getAssemble();       //拼团商品
//        $seckill = $Item ->getSeckill();    //秒杀商品

        $itemData = []; //商品
        $specsData = [];    //商品的规格数据

        foreach ( $item as $k=>$v ){
            $itemData = [];
            $specsData = [];
            $itemData = $v;
            unset($itemData['id']);
            $specsData = $v['specs'];
            unset($itemData['specs']);

            // 启动事务
            Db::startTrans();
            try {
                $itemId = Db::name('item')->insertGetId($itemData);
                foreach ($specsData as $k1=>$v1){
                    $specsData[$k1]['gid'] = $itemId;
                }
                Db::name('specs_goods_price')->insertAll($specsData);
                //修改拼团数据
                Db::name('assemble') ->where('item_id',$v['id'])->setField('item_id',$itemId);
                Db::name('assemble_attr') ->where('item_id',$v['id'])->setField('item_id',$itemId);
                //修改秒杀
                Db::name('seckill') ->where('item_id',$v['id'])->setField('item_id',$itemId);

                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                dump($e->getMessage());die;
            }
        }

        dump('添加成功');
    }

    public function categoryddd(){
        $Item = new ItemsamllModel();
        $list = $Item ->getCatrgory();
        $res = Db::name('item_category')->insertAll($list);
        dump($res);die;
    }

    /***
     * 查询会员余额表数据是否齐全
     */
    public function getMember(){
        $memberIds = Db::name('member') ->column('id');
        $memberMoneyIds = Db::name('member_money') ->column('member_id');

        $res = [];
        foreach ($memberIds as $k=>$v){
            if( !in_array($v,$memberMoneyIds) ){
                array_push($res,$v);
            }
        }
        dump($res);die;
    }

    /***
     * 添加基本成本
     */
    public function purchase_price(){
        set_time_limit(0);
        $where = [];
        $where[] = ['title','like','%中国灸%'];
        $items = Db::name('item') ->where($where) ->select();
        foreach ($items as $k=>$v){
            $items[$k]['cost'] = 30;        //中国灸成本30
        }
        $where = [];
        $where[] = ['title','like','%衍生%'];
        $items1 = Db::name('item') ->where($where) ->select();
        foreach ( $items1 as $k=>$v ){
            if( $v['bar_code'] == '6949657770123' || $v['bar_code'] == '6949657714790' ){
                $items1[$k]['cost'] = 37.5;
            }
            if( $v['bar_code'] == '6949657704869' ){
                $items1[$k]['cost'] = 34.5;
            }
            if( $v['bar_code'] == '4895157206896' || $v['bar_code'] == '4895157206872'  ){
                $items1[$k]['cost'] = 47.5;
            }
            if( $v['bar_code'] == '4895157206636'
                || $v['bar_code'] == '4895157206643'
                || $v['bar_code'] == '4895157206650'
                || $v['bar_code'] == '4895157206711'
                || $v['4895157206698']  ){
                $items1[$k]['cost'] = 44.5;
            }
            if( $v['bar_code'] == '4895157209217'
                || $v['bar_code'] == '4895157209231'
                || $v['bar_code'] == '4895157209255'
                || $v['bar_code'] == '4895157209279' ){
                $items1[$k]['cost'] = 19.95;
            }
            if( $v['bar_code'] == '4895157212347'
                || $v['bar_code'] == '4895157212309'
                || $v['bar_code'] == '4895157212286'
                || $v['bar_code'] == '4895157212323' ){
                $items1[$k]['cost'] = 39.5;
            }
            if( $v['bar_code'] == '4895157214570'
                || $v['bar_code'] == '4895157214686' ){
                $items1[$k]['cost'] = 57.5;
            }
            if( $v['bar_code'] == '4895157215058'){
                $items1[$k]['cost'] = 17.5;
            }
            if( $v['bar_code'] == '4895157215355'
                || $v['bar_code'] == '4895157215270'
                || $v['bar_code'] == '4895157215294'
                || $v['bar_code'] == '4895157215331'
                || $v['bar_code'] == '4895157215317'

                || $v['bar_code'] == '4895157216383'
                || $v['bar_code'] == '4895157216390'

                || $v['bar_code'] == '4895157216888'
                || $v['bar_code'] == '4895157216918'
                || $v['bar_code'] == '4895157216963'
            ){
                $items1[$k]['cost'] = 19.5;
            }
            if( $v['bar_code'] == '4895157215379'
                || $v['bar_code'] == '4895157215393'
                || $v['bar_code'] == '4895157215416'
                || $v['bar_code'] == '4895157216246' ){
                $items1[$k]['cost'] = 49.5;
            }
            if( $v['bar_code'] == '4895157216406' ){
                $items1[$k]['cost'] = 99;
            }
        }

        foreach ($items1 as $k=>$v){
            array_push($items ,$v);
        }

        $shopId = Db::name('shop')->column('id');

        $purchasePrice = [];
        $shopitem = [];
        foreach ($items as $k=>$v){
            $arrPrice = [];
            foreach ( $shopId as $k1 =>$v1 ){
                $arrPrice = [
                    'shop_id'   =>$v1,
                    'type'   =>1,
                    'pd_id'   =>'',
                    'item_id'   =>$v['id'],
                    'md_price'   =>$v['cost'],
                    'store_cose'   =>$v['cost'],
                    'stock'   =>1,
                    'time'   =>time(),
                    'sort'   =>0
                ];
                $re = Db::name('purchase_price') ->where(['item_id'=>$v['id'],'shop_id'=>$v1]) ->find();
                if( !$re ){
                    array_push($purchasePrice,$arrPrice);
                }


                $arrShop = [];
                $arrShop = [
                    'shop_id'   =>$v1,
                    'item_id'   =>$v['id'],
                    'stock'   =>1,
                    'stock_ice'   =>0
                ];
                $re = Db::name('purchase_price') ->where(['item_id'=>$v['id'],'shop_id'=>$v1]) ->find();
                if( !$re ){
                    array_push($shopitem,$arrShop);
                }
            }
        }
//        dump($shopitem);
//        exit;
        // 启动事务
        Db::startTrans();
        try {
            Db::name('purchase_price') ->insertAll($purchasePrice);
            Db::name('shop_item') ->insertAll($shopitem);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e->getMessage());die;
        }
        dump($items);
    }

    /***
     * 判断是否有重复
     */
    public function shop_item(){
        $where = [];
        $where[] = ['title','like','%中国灸%'];
        $items = Db::name('item') ->where($where) ->column('id');

        $where = [];
        $where[] = ['title','like','%衍生%'];
        $items1 = Db::name('item') ->where($where) ->column('id');
        foreach ($items1 as $k=>$v){
            array_push($items ,$v);
        }

        $itemsIds = implode(',',$items);
        dump($items1);
        dump($itemsIds);
        die;
        // 启动事务
        Db::startTrans();
        try {
            $where = [];
            $where[] = ['item_id','in',$itemsIds];
            $price = Db::name('purchase_price') ->where($where) ->setField('stock',0) ;

            Db::name('shop_item') ->where($where) ->setField('stock',0) ;
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump($e->getMessage());die;
        }
        dump($items);die;
    }
    
    /***
     * 上传文件测试
     */
    public function file1(){
        $file = request()->file('file');
        $filePath = $file->getRealPath();
        $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
        
        dump($file);
        dump($filePath);
        dump($ext);
        die;
    }

    /***
     * 会员对比测试
     */
    public function memberOrMember1(){
        set_time_limit(0);
        $new = Db::name('member')->select();
        $old = Db::name('member2')->select();
        $newArr = [];   //门店不一样的会员
        foreach ( $old as $k=>$v ){
            foreach ( $new as $k1=>$v1 ){
                if( $v1['mobile'] == $v['mobile'] ){
                    if( $v1['shop_code'] != $v['shop_code'] ){
                        $arr = [];
                        $arr = [
                            'new_id'    =>$v1['id'],
                            'old_id'    =>$v['id'],
                            'mobile'    =>$v['mobile'],
                            'new_shop_code' =>$v1['shop_code'],
                            'old_shop_code' =>$v['shop_code'],
                        ];
                        array_push($newArr,$arr);
                    }
                }
            }
        }
        foreach ( $newArr as $k=>$v ){
            if(  empty($v['old_shop_code']) ){
                $shop_code = 'A00000';
            }else{
                $shop_code = $v['old_shop_code'];
            }
            Db::name('member')->where('mobile',$v['mobile'])->setField('shop_code',$shop_code);
        }

        dump($newArr);


//        dump($new);
//        dump($count1);
    }

    public function gettt(){
        $where = [];
        set_time_limit(0);
        $where[] = ['is_online','eq',1];
        $where[] = ['pay_status','neq',0];
        $order = Db::name('order')
            ->where($where)
            ->field('id,sn,pay_status,add_time,paytime')
            ->order('id desc')
            ->select();

        foreach ( $order as $k=>$v ){
            if( !empty($v['paytime']) ){
                if( strlen($v['paytime']) >= 14 ){
                    Db::name('order')->where('id',$v['id'])->setField('paytime',strtotime($v['paytime']));
                }
            }
        }
        dump($order);
    }

    /***
     * 更改已发货的订单状态
     */
    public function ttt(){
        set_time_limit(0);
        $where = [];
        $where[] = ['id','between',"416,485"];
        $list = Db::name('order_express') ->where($where)->select();
        foreach ( $list as $k=>$v ){
            // 启动事务
            Db::startTrans();
            try {
                Db::name('order_goods')->where('id',$v['order_goods_id'])->update(['express_id'=>0,'deliver_status'=>0]);    //改详情表
                Db::name('order')->where('id',$v['order_id'])->update(["order_status"=>0,'sendtime'=>0]);    //改订单表
                //删除股东数据
                $map = [];
                $map[] = ['order_id','eq',$v['order_id']];
                $map[] = ['type','eq',8];
                Db::name('statistics_log')->where($map)->delete();
                Db::name('order_express') ->where('id',$v['id'])->delete(); //删除发货数据
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
            }
        }
        dump('修改成功');
    }

    /***
     * 调拨单
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function diaobo(){
        set_time_limit(0);
        $where[] = ['a.create_time','>=',1575561600];
//        $where[] = ['a.id','in','198,196'];
        $list = (new AllotModel())->alias('a')
            ->join('allot_item b','a.id=b.allot_id')
            ->where($where)
            ->field('a.id,sn,out_shop,in_shop,a.create_time,b.item,b.barcode,b.num,a.status')
            ->select();
        foreach ( $list as $k=>$v ){
            $list[$k]['out_shop'] = Db::name('shop')->where('id',$v['out_shop'])->value('name');
            $list[$k]['in_shop'] = Db::name('shop')->where('id',$v['in_shop'])->value('name');
            $list[$k]['status'] = $v['status']==0?'发货中':$v['status']==1?'调拨中':'已完成';
            $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
        }
        return json($list);
    }

    /***
     * 添加虚拟用户
     */
    public function xuni(){
        $tt = Db::name('ddd')->select();
        $add_data = [];
        foreach ( $tt as $k=>$v ){
            $arr = [];
            $arr = [
                'pid'   =>0,
                'no'   =>0,
                'mobile'   =>0,
                'shop_code'   =>0,
                'realname'   =>$v['name'],
                'level_id'   =>0,
                'openid'   =>0,
                'pic'   =>$v['pic'],
                'nickname'   =>$v['name'],
                'wechat_nickname'   =>$v['name'],
                'regtime'   =>0,
                'is_staff'   =>0,
                'status'   =>0,
                'source'   =>0,
                'smallOpenid'   =>0,
                'attestation'   =>0,
                'retail'   =>0,
                'retail_img'   =>0,
                'is_fictitious'   =>1
            ];
            array_push($add_data,$arr);
        }
//        dump($add_data);die;
        Db::name('member') ->insertAll($add_data);
    }

    public function kuCun(){
        set_time_limit(0);
        $map = [];
        $map[] = ['a.shop_id','in','48,50'];
        $map[] = ['a.stock','>',0];
        $list = Db::name('shop_item')->alias('a')
            ->join('item b','a.item_id=b.id')
            ->join('shop c','a.shop_id=c.id')
            ->where($map)->field('a.shop_id,a.item_id,a.stock,b.title,c.name')
            ->select();
//        dump($list);die;
        foreach ( $list as $k=>$v )
        {
            $where = [];
            $where[] = ['shop_id','eq',$v['shop_id']];
            $where[] = ['item_id','eq',$v['item_id']];
            $where[] = ['stock','>',0];
            $all_cost = Db::name('purchase_price') ->where($where)->field('stock,store_cose')->select();
            if( count($all_cost) > 0 )
            {
                $all = 0;
                foreach ( $all_cost as $k1=>$v1 )
                {
                    $all += $v1['stock']*$v1['store_cose'];
                }
                $list[$k]['all_cost'] = $all;
            }else{
                $list[$k]['all_cost'] = 0;
            }
        }
        return json($list);
        dump($list);die;
    }
}