<?php
namespace app\index\controller;


use app\common\model\PayModel;
use app\wxshop\wxpay\WxPayMicroPay;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;
use app\index\model\Member\MemberMoneyModel;
use app\index\model\Member\MemberModel;
use app\index\model\Member\MemberRechargeLogModel;
use app\index\model\Member\MemberDetailsModel;
use app\index\model\Order\Order as OrderModel;

/**
	会员充值
*/
class Recharge extends Base
{

	/**
		会员充值
	*/
	public function index(){
		$data = $this ->request->param();
		$shop_id = $this->getUserInfo()['shop_id'];		//门店id

		if ( empty($data['member_id']) || empty($data['price']) ) {
			return json(['code'=>'-3','msg'=>'缺少会员id或充值金额','data'=>'']);
		}

		if( !is_numeric($data['price']) || $data['price']<0 ){
			return json(['code'=>'-100','msg'=>'充值金额必须大于0元','data'=>'']);
		}

		if ( empty($data['pay_way']) ) {
			return json(['code'=>'-3','msg'=>'缺少参数支付方式','data'=>'']);
		}
		if ( empty($data['waiter_id']) ) {
			return json(['code'=>'-3','msg'=>'缺少服务人员','data'=>'']);
		} 

		if( empty($data['is_only_service']) ){
			$data['is_only_service'] = 0;
		}else{
			$data['is_only_service'] = 1;
		}
		if( empty($data['remarks']) ){
			$data['remarks'] = '';
		}
		$waiter = Db::name('shop_worker')->where('id',$data['waiter_id'])->field('id,name')->find();
		if( !$waiter ){
			return json(['code'=>'-3','msg'=>'请选择服务人员','data'=>'']);
		}
		$Member = new MemberModel();
		$MemberMoney = new MemberMoneyModel();
		$MemberRechargeLog = new MemberRechargeLogModel();
		$Order = new OrderModel();
		$MemberDetails = new MemberDetailsModel();

		//获取选择的会员的等级
		$member_level = Db::name('member') ->where('id',$data['member_id'])->value('level_id');		//自身会员等级

		$member_sort = Db::name('member_level') ->where('id',$member_level)->value('sort');		//自身会员等级的序号
		// $level_standard = $Order ->getLevelStandard($shop_id);	//会员达到相应等级所需的积分标准
		$level_standard = Db::name('level_price')
			->alias('a')
			->where(['a.shop_id'=>$shop_id])
			->join('member_level b','a.level_id=b.id')
			->order('b.sort desc')
			->field('a.level_id,a.price,b.sort')
			->select();
//		$amount = Db::name('member_recharge_log') ->where('member_id',$data['member_id'])->sum('price');	//累积充值
        $amount = Db::name('member_details')->where(['member_id'=>$data['member_id'],'type'=>1])->sum('amount');//累积充值
        $new_amount = $amount + $data['price'];

		$new_level = $member_level;		//将自身原本等级赋值给新等级

		for ($i=0; $i <=count($level_standard); $i++) { 
			if( $new_amount >= $level_standard[$i]['price'] ){
				//达到金额要求
                if( $level_standard[$i]['sort'] >$member_sort ){
                    //新等级高于原来的等级
                    $new_level = $level_standard[$i]['level_id'];
                    break;
                }
			}
		}
		$order_sn = 'CZ'.time().$shop_id;
		// 生成订单表信息
		$order = array(
			'user_id'	=>$this->getUserInfo()['id'],	//制单人
			'is_admin'	=>0,
			'shop_id'	=>$shop_id,
			'member_id'	=>$data['member_id'],
			'sn'		=>$order_sn,
			'type'		=>3,
			'amount'	=>$data['price'],
			'number'	=>1,
			'pay_status'=>1,
			'pay_way'	=>$data['pay_way'],
			'paytime'	=>time(),
			'overtime'	=>time(),
			'dealwithtime'=>time(),
			'order_status'=>2,		//已完成
			'add_time'	=>time(),
			'is_online'	=>0,
			'order_type'=>1,
			'old_amount'=>$data['price'],
			'waiter'	=>$waiter['name'],		//操作人员名字
			'waiter_id'	=>$waiter['id'],		//操作人员id
			'remarks'	=>!empty($data['remarks'])?$data['remarks']:''		//留言
		);

		//生成会员表明细数据、member_recharge_log
		$rechargeLog = array(
			'member_id'		=>$data['member_id'],
			'shop_id'		=>$shop_id,
			'price'			=>$data['price'],
			'pay_way'		=>$data['pay_way'],
			'is_only_service'=>$data['is_only_service'],		//是否只限制服务使用：1只能服务使用,0都可使用(暂时无用)
			'remarks'		=>!empty($data['remarks'])?$data['remarks']:'',
			'create_time'	=>time()
		);

		//生成股东数据统计表数据ddxm_statistics_log
		$statisticsLog = array(
				'shop_id'		=>$shop_id,
				'order_sn'		=>$order_sn,
				'type'			=>1,
				'data_type'	=>1,
				'pay_way'		=>$data['pay_way'],
				'price'			=>$data['price'],
				'create_time'	=>time()
		);

		$member_mobile = $Member->where('id',$data['member_id'])->value('mobile');
		
		// 启动事务
		Db::startTrans();
		try {
		    $orderId = $Order ->insertGetId($order);	//添加订单表订单

		    $rechargeLog['order_id'] = $orderId;
		    $MemberRechargeLog ->insert($rechargeLog);	//添加累积充值记录

		    if( $MemberMoney ->where('member_id',$data['member_id'])->find() ){
		    	$MemberMoney ->where('member_id',$data['member_id'])->setInc('money',$data['price']);	//增加余额
		    }
		    $Member ->where('id',$data['member_id'])->update(['level_id'=>$new_level]);			//新的会员等级

		    // $MemberDetails ->where('member_id',$data['member_id'])->setInc('amount',$data['price']);	//添加累积充值金额
		    // 生成累积充值记录
		    $MemberDetailsData = array(
		    		'member_id'		=>$data['member_id'],
		    		'mobile'		=>$member_mobile,
		    		'remarks'		=>!empty($data['remarks'])?$data['remarks']:'',
		    		'reason'		=>'充值'.$data['price'].'元',
		    		'addtime'		=>time(),
		    		'amount'		=>$data['price'],
		    		'type'			=>1,
		    		'order_id'		=>$orderId
		    	);
		    $MemberDetails ->insert($MemberDetailsData);

		    $statisticsLog['order_id'] = $orderId;
		    Db::name('statistics_log')	->insert($statisticsLog);

		    //微信支付宝支付
            if( $data['pay_way'] == 1 ){
                //微信支付
                $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                // 支付授权码
                $auth_code = $data['auth_code'];
                $input = new WxPayMicroPay();
                $input->SetAuth_code($auth_code);
                $input->SetBody($shopName.'--'.$order_sn);
                $input->SetTotal_fee($order['amount']);//订单金额  订单单位 分
//                $input->SetTotal_fee(1);//订单金额  订单单位 分
                $input->SetOut_trade_no($order['sn']);
                $PayModel = new PayModel();
                $resPay = $PayModel ->pay($input);
                if( $resPay == false ){
                    throw new \Exception("结账失败,微信扣款失败！");
                }
            }else if($data['pay_way'] == 2){
                //支付宝支付
                $shopName = Db::name('shop')->where('id',$shop_id)->value('name');
                $PayModel = new PayModel();
                $resPay = $PayModel ->AliCodePay($data['auth_code'],$order['sn'],$shopName.'--'.$order_sn,$order['amount']);
//                $resPay = $PayModel ->AliCodePay($data['auth_code'],$order['sn'],$shopName.'--'.$order_sn,0.01);
                if( $resPay['code'] != 200 ){
                    $resPay['msg'] = '结账失败,支付宝扣款失败！！';
                    throw new \Exception("结账失败,支付宝扣款失败！");
                }
            }
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
            Db::rollback();
		    // 回滚事务
		    return json(['code'=>'500','msg'=>$e->getMessage(),'data'=>'']);
		}

		return json(['code'=>'200','msg'=>'充值成功','data'=>'']);
	}

	//会员充值记录
	public function rechargeLog(){
		$data = $this ->request->param();
		if( empty($data['member_id']) ){
			return json(['code'=>'-1','msg'=>'缺少会员id','data'=>'']);
		}

		if( empty($data['page']) ){
			$data['page'] = '';
		}
		$MemberRechargeLog = new MemberRechargeLogModel();
		$where['a.member_id'] = $data['member_id'];
		$where['a.shop_id'] = $this->getUserInfo()['shop_id'];
		$list = $MemberRechargeLog 
				->alias('a')
				->where($where)
				->join('order b','a.order_id=b.id')
				->order('create_time desc')
                ->page($data['page'])
				->field('a.id,a.member_id,a.price,a.create_time,a.title,b.waiter')
				->select();
		$count = $MemberRechargeLog 
				->alias('a')
				->where($where)
				->join('order b','a.order_id=b.id')
				->count();
		return json(['code'=>'200','msg'=>'查询成功','data'=>$list,'count'=>$count]);
	}
}