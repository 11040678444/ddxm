<?php


namespace app\wxshop\controller;

use app\admin\model\deposit\PurchasePriceLog;
use app\common\model\WxPayModel;
use app\wxshop\model\coupon\CouponModel;
use app\wxshop\wxpay\JsApiPay;
use app\wxshop\wxpay\WxPayApi;
use app\wxshop\wxpay\WxPayUnifiedOrder;
use app\wxshop\wxpay\WxPayConfig;
use EasyWeChat\Factory;
use think\Db;
use think\Exception;

class Test extends Base
{

    // 支付
    public function pay(){


//        var_dump( dirname(__FILE__));
        try{
            $tools = new JsApiPay();

            //②、统一下单
            $input = new WxPayUnifiedOrder();
            $input->SetBody("测试商品");//设置商品或支付单简要描述
            $input->SetAttach('测试商品');//设置附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            $input->SetOut_trade_no(time());//设置商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
            $input->SetTotal_fee(round(0.01*100));//支付金额
            $input->SetTime_start(date("YmdHis"));
            $input->SetTime_expire(date("YmdHis", time() + 600));
            $input->SetNotify_url("http://www.dezhuchewu.com/weixin/Notify/index");
            $input->SetTrade_type("JSAPI");
            $input->SetOpenid('oVQ0O5Nf9qWxPBxJH2Nwr7YTytQg');
            $config = new WxPayConfig();
            $order = WxPayApi::unifiedOrder($config, $input);
            $jsApiParameters = $tools->GetJsApiParameters($order);

            return $jsApiParameters;
        } catch(Exception $e) {
            foreach($order as $key=>$value){
                echo "<font color='#00ff55;'>$key</font> :  ".htmlspecialchars($value, ENT_QUOTES)." <br/>";
            }
        }
    }

    public function test2(){

        $order_sn = input('order_sn');
        $refund_no = input('refund_no');

//        $wxPayRefund->SetOut_trade_no($data['order_sn']);// 商品 订单号-- 必须和下单时 订单号 一致
//        $wxPayRefund->SetOut_refund_no($data['order_sn']);// 退单 订单号
//        $wxPayRefund->SetTotal_fee($data['total_fee']);//订单总金额，单位为分，只能为整数，详见支付金额
//        $wxPayRefund->SetRefund_fee($data['total_fee']);//	退款总金额，订单总金额，单位为分，只能为整数，详见支付金额

        $wxpayModel = new WxPayModel();
        $data =[
            'order_sn'=>$order_sn,//商品订单号
            'refund_no'=>$refund_no,//退款金额
            'total_fee'=>'1',//商品支付的时候价格
            'refund_fee'=>'1',//商品退款金额  单位 分
        ];
       $da =   $wxpayModel->refund($data);

       dump($da);
//失败
//        array(9) {
//            ["appid"] => string(18) "wx867b49bff338c6c4"
//            ["err_code"] => string(13) "ORDERNOTEXIST"
//            ["err_code_des"] => string(15) "订单不存在"
//            ["mch_id"] => string(10) "1486226662"
//            ["nonce_str"] => string(16) "GevM4HOsJ5RdBWoM"
//            ["result_code"] => string(4) "FAIL"
//            ["return_code"] => string(7) "SUCCESS"
//            ["return_msg"] => string(2) "OK"
//            ["sign"] => string(64) "39DE129D32B981A425C80D8B69C5D847B4A688466BF5884E198E7DAB2DE6E3C6"


//成功
//        array(18) {
//            ["appid"] => string(18) "wx867b49bff338c6c4"
//            ["cash_fee"] => string(1) "1"
//            ["cash_refund_fee"] => string(1) "1"
//            ["coupon_refund_count"] => string(1) "0"
//            ["coupon_refund_fee"] => string(1) "0"
//            ["mch_id"] => string(10) "1486226662"
//            ["nonce_str"] => string(16) "X0rK1hsFoLOKKMgh"
//            ["out_refund_no"] => string(19) "td91901290120129190"
//            ["out_trade_no"] => string(18) "WM2019103052504849"
//            ["refund_channel"] => array(0) {
//            }
//  ["refund_fee"] => string(1) "1"
//            ["refund_id"] => string(29) "50300002122019103013018646705"
//            ["result_code"] => string(7) "SUCCESS"
//            ["return_code"] => string(7) "SUCCESS"
//            ["return_msg"] => string(2) "OK"
//            ["sign"] => string(64) "40E4365B0593BFC109CE7A8B6E3E1F50ADB74B04AAB38C35D12AF9A18E932697"
//            ["total_fee"] => string(1) "1"
//            ["transaction_id"] => string(28) "4200000410201910301960388360"
//}
    }

    //测试微信框架

    /***
     * 测试微信支付
     */
    public function wxPay(){
        $tt = (new WxPayModel()) ->getAccessToken();
        dump($tt);
    }

    /***
     * 测试推送模板消息
     */
    public function ttt(){
        $res = ( new WxPayModel() ) ->send_message('oZULn02wnBIf73Iq26nFJIIf9wQE','-nLe8_D8KBTUEccRk2ehM4iKzSIgbQquGkxIcXZElJM');
        dump($res);die;
    }

    /***
     * 发送客服消息
     */
    public function sed_custom_message(){
        //发送客户消息
        $post_data = [];
        $post_data = [
            'touser'    =>'o3jiDwD43DBq2blbpHyRL-_QrJ8A',
            'msgtype'    =>'text',
            'text'    =>[
                'content'   =>'亲爱的《用户信息》'.':您有新的下级分销员啦'
            ],
        ];
        (new WxPayModel())->sed_custom_message($post_data);
    }
    public function tt(){
        $time = '1575796976';
        $tt = strtotime("+1month",$time);
        dump($time);dump($tt);
    }

    /***
     * 2019/11/29测试统计
     */
    public function tongji(){
        $start_time = strtotime(date('Y-m-d').' 00:00:00');
        $end_time = time();
        $timeWhere = $start_time.','.$end_time;

        //统计总人数
        $where = [];
        $where[] = ['status','eq',1];
        $all_people = Db::name('member') ->where($where)->count();      //总人数
        $where[] = ['regtime','between',$timeWhere];
        $today_all_people = Db::name('member') ->where($where)->count();  //今日新增会员人数

        //今日总销售额
        $where = [];
        $where[] = ['pay_status','eq',1];
        $where[] = ['refund_status','eq',0];
        $where[] = ['add_time','between',$timeWhere];
        $order = Db::name('order')->where($where)->field('amount')->select();
        $today_all_price = 0; //今日销售总金额
        foreach ( $order as $k=>$v ){
            $today_all_price += $v['amount'];
        }
        $today_all_count = count($order);     //今日订单数

        $where = [];
        $all_retail_user_list = Db::name('retail_user') ->where($where)->select();
        $all_retail_user = count($all_retail_user_list);    //总分销人数

        //统计前十名
        $all_retail_user_list_new = $all_retail_user_list;
        foreach ( $all_retail_user_list as $k=>$v ){
            $all_retail_user_list[$k]['all_num'] = 0;
            foreach ( $all_retail_user_list_new as $k1=>$v1 ){
                if( ($v['member_id'] == $v1['one_member_id']) ){
                    $all_retail_user_list[$k]['all_num'] += 1;
                }
            }
        }
        $all_nums = array_column($all_retail_user_list,'all_num');
        array_multisort($all_nums,SORT_DESC,$all_retail_user_list);
        $res = [];  //最终的数据
        for ($i=0;$i<count($all_retail_user_list);$i++){
            if( $i<=9 ){
                array_push($res,$all_retail_user_list[$i]);
            }
        }
        $result = [];
        $result = [
            'all_people'    =>$all_people,  //合计总人数
            'today_all_people'    =>$today_all_people,  //今日新增人数
            'today_all_price'    =>$today_all_price,    //今日销售总金额
            'today_all_count'    =>$today_all_count,    //今日销售订单数
            'all_retail_user'    =>$all_retail_user,    //总分销人员
            'list'    =>$res,   //排名前十
        ];
        return json(['code'=>200,'msg'=>'获取成功','data'=>$result]);
    }

    //测试文件锁
    public function lockTest(){
        Db::name('order')->select();
        Db::name('order_goods')->select();
        $info = Db::name('test')->where([['id','>',1]])->find();
//        $file = fopen('lock.txt','w+');
//        if( flock( $file,LOCK_EX|LOCK_NB ) ){
//            if( !$info  ){
//                Db::name('test')->insert(['title'=>20]);
//                fclose($file);
//            }
//        }
//        if( Db::name('test') ->where('create_time',time())->find() ){
//            echo '操作太频繁';
//            return;
//        }else{
//            Db::name('test')->insert(['title'=>substr(time(),-1),'create_time'=>time()]);
//        }
//        if( $info['title'] >10  ){
//            Db::name('test')->where('id',1)->setDec('title',10);
//        }
        if( !$info ){
            Db::name('test')->insert(['title'=>20,'create_time'=>time()]);
        }
//            Db::name('test')->insert(['title'=>20,'create_time'=>time()]);
    }

    //查询纸尿裤销量
    public function getValue(){
        $list = Db::name('order_goods')->alias('a')
            ->where([
            ['a.subtitle','like','%纸尿裤%']
        ])
            ->join('item b','a.item_id=b.id')
            ->field('a.id,a.num,b.item_type,a.subtitle,a.item_id,brand_id')
            ->select();
        $res = [];
        foreach ( $list as $k=>$v ){
            if( !isset($res[$v['item_id']]) ){
                $res[$v['item_id']][] = $v;
            }else{
                array_push($res[$v['item_id']],$v);
            }
        }
        $tt = [];
        foreach ( $res as $k=>$v ){
            $num = 0;
            foreach ( $v as $k1=>$v1 ){
                $num += $v1['num'];
            }
            $arr = [];
            $arr = [
                'item_id'   =>$k,
                'subtitle'  =>$v[0]['subtitle'],
                'item_type' =>$v[0]['item_type'],
                'num' =>$num,
                'brand_id' =>$v[0]['brand_id'],
            ];
            array_push($tt,$arr);
        }

        foreach ( $tt as $k=>$v ){
            if( $v['item_type'] == 1 ){
                $tt[$k]['item_type'] = '线上商品';
            }else{
                $tt[$k]['item_type'] = '线下商品';
            }
            if( !empty($v['brand_id'])){
                $tt[$k]['brand'] = Db::name('brand')->where('id',$v['brand_id'])->value(['title']);
            }else{
                $tt[$k]['brand'] = '';
            }
        }
        return json($tt);
    }

    /***
     * 修改成本
     */
    public function getOr(){
        $where = [];
        $where[] = ['b.pay_status','eq',1];
        $where[] = ['b.order_distinguish','in','2,3'];
        $where[] = ['b.paytime','between','1578639600,1578650400'];
        $list = Db::name('statistics_log')      //下单的信息
            ->alias('a')
            ->join('order b','a.order_id=b.id')
            ->where($where)
            ->field('a.id,a.order_id,a.order_sn,a.type,a.data_type,a.pay_way,a.price')
            ->order('a.id asc')
//            ->group('a.order_id')
            ->select();
        $dn = [];   //订单
        $ch = [];   //成本
        foreach ( $list as $k=>$v ){
            if( $v['type'] == 4 || $v['type'] == 3 || $v['type'] == 5 ){
                if( !in_array($v['order_id'],array_column($dn,'order_id')) ){
                    array_push($dn,$v);
                }
            }
            if( $v['type'] == 8 ){
                array_push($ch,$v);
            }
        }
        $res = [];
        foreach ( $dn as $k=>$v ){
            foreach ( $ch as $k1=>$v1 ){
                if( $v['order_id'] == $v1['order_id'] ){
                    if( $v['price'] < $v1['price'] ){
                        $arr = [];
                        $arr = [
                            'order_id'  =>$v['order_id'],
                            'sell_price'  =>$v['price'],
                            'cost_price'  =>$v1['price'],
                        ];
                        array_push($res,$arr);
                    }
                }
            }
        }
//        Db::startTrans();
//        try{
//            foreach ( $res as $k=>$v ){
//                $map = [];
//                $map[] = ['order_id','eq',$v['order_id']];
//                $map[] = ['type','eq',8];
//                Db::name('statistics_log')->where($map)->setField('price',$v['sell_price']);
//                Db::name('order_goods')->where('order_id',$v['order_id'])->update(['oprice'=>$v['sell_price'],'all_oprice'=>$v['sell_price']]);
//            }
//            Db::commit();
//        }catch (\Exception $e){
//            Db::rollback();
//        }

        dump($res);
    }

    /***
     * 会员门店转店
     */
    public function memberToShop(){
        $data = $this ->request->param();
        if( empty($data['shop_id']) ){
            return_error('门店id错误');
        }
        if( empty($data['mobile']) && empty($data['id']) ){
            return_error('会员id或手机号错误');
        }
        $shopInfo = Db::name('shop')->where('id',$data['shop_id'])->find();
        if( !$shopInfo ){
            return_error('门店ID错误');
        }
        $where = [];
        if( !empty($data['id']) ){
            $where[] = ['a.id','eq',$data['id']];
        }else if(!empty($data['mobile'])){
            $where[] = ['a.mobile','eq',$data['mobile']];
        }
        $memberInfo = Db::name('member')
            ->alias('a')
            ->join('member_money b','a.id=b.member_id')
            ->where($where)
            ->field('a.*,b.money,online_money')
            ->find();
        if( !$memberInfo ){
            return_error('会员错误！');
        }
        if( $shopInfo['code'] == $memberInfo['shop_code'] ){
            return_error('会员已经是当前门店了');
        }
        //查看是否存在服务卡
        $ticket = Db::name('ticket_user_pay')
            ->where([
                ['member_id','eq',$memberInfo['id']],
                ['status','in','0,1,2']
            ])->select();
        if( count($ticket)>0 ){
            return_error('会员当前存在服务卡,请手动转店');
        }
        //查询会员是否存在限时余额
        if( $memberInfo['money'] != 0 ){
            $map = [];
            $map[] = ['member_id','eq',$memberInfo['id']];
            $map[] = ['status','neq',2];
            $list = Db::name('member_money_expire')->where($map)->select();
            if( count($list) > 0 ){
                $exp_lis = [];
                foreach ( $list as $k=>$v ){
                    if( $v['status']==0 ){
                        array_push($exp_lis,$v);
                    }else if( $v['status'] == 1 ){
                        if( (time() < $v['expire_time']) && ($v['use_price'] < $v['price']) ){
                            array_push($exp_lis,$v);
                        }
                    }
                }
                if( count( $exp_lis ) ){
                    $exp_money = 0; //限时余额
                    foreach ( $exp_lis as $k=>$v ){
                        $exp_money += $v['price']-$v['use_price'];
                    }
                    $memberInfo['exp_money'] = $exp_money;      //限时余额
                    $memberInfo['put_money'] = $memberInfo['money']-$exp_money; //普通余额
                    $memberInfo['exp_lis'] = $exp_lis;
                }
            }
        }
        //订单数据
        $shop_id = $shopInfo['id']; //新门店id
        $old_shop_id = Db::name('shop') ->where('code',$memberInfo['shop_code'])->value('id');  //会员当前门店id
//        $order_sn = 'CZ'.time().$shop_id;
        // 生成订单表信息
        $order = array(
            'user_id'	=>1,
            'is_admin'	=>1,
//            'shop_id'	=>$shop_id,
            'member_id'	=>$memberInfo['id'],
//            'sn'		=>$order_sn,
            'type'		=>3,
//            'amount'	=>$data['price'],
            'number'	=>1,
            'pay_status'=>1,
            'pay_way'	=>16,   //公司转门店
            'paytime'	=>time(),
            'overtime'	=>time(),
            'dealwithtime'=>time(),
            'order_status'=>2,		//已完成
            'add_time'	=>time(),
            'is_online'	=>0,
            'order_type'=>1,
//            'old_amount'=>$data['price'],
            'waiter'	=>'系统管理员',		//操作人员名字
            'waiter_id'	=>1,		//操作人员id
        );
        //生成股东数据统计表数据ddxm_statistics_log
        $statisticsLog = array(
//            'shop_id'		=>$shop_id,
//            'order_sn'		=>$order_sn,
            'type'			=>1,
            'data_type'	=>1,
            'pay_way'		=>16,   //公司转门店
//            'price'			=>$data['price'],
            'create_time'	=>time()
        );
        //生成会员表明细数据、member_recharge_log
        $rechargeLog = array(
            'member_id'		=>$memberInfo['id'],
//            'shop_id'		=>$shop_id,
//            'price'			=>$data['price'],
            'pay_way'		=>16,        //公司转门店
            'is_only_service'=>0,		//是否只限制服务使用：1只能服务使用,0都可使用(暂时无用)
            'remarks'		=>!empty($data['remarks'])?$data['remarks']:'',
            'create_time'	=>time()
        );

        $orderData = [];        //订单数据
        $rechargeData = [];        //充值记录
        $statisticsData = [];        //股东记录
        if( $memberInfo['put_money'] > 0 ){
            //有普通余额
            //1：一笔原门店反充值
            $order_sn = 'CZ'.date('ymd').time().$old_shop_id;
            $order['shop_id'] =  $old_shop_id;
            $order['sn'] =  $order_sn;
            $order['amount'] = '-'.$memberInfo['put_money'];
            $order['old_amount'] = '-'.$memberInfo['put_money'];
            array_push($orderData,$order);      //普通反充值

            $rechargeLog['shop_id'] = $old_shop_id;
            $rechargeLog['price'] = '-'.$memberInfo['put_money'];
            array_push($rechargeData,$rechargeLog); //普通反充值记录

            $statisticsLog['shop_id'] = $old_shop_id;
            $statisticsLog['order_sn'] = $order_sn;
            $statisticsLog['data_type'] = 2;
            $statisticsLog['price'] = '-'.$memberInfo['put_money'];
            $statisticsLog['title'] = '会员转门店';
            array_push($statisticsData,$statisticsLog); //普通退单股东记录

            //2：一笔新门店充值
            $order_sn = 'CZ'.time().$shop_id;
            $order['shop_id'] =  $shop_id;
            $order['sn'] =  $order_sn;
            $order['amount'] = $memberInfo['put_money'];
            $order['old_amount'] = $memberInfo['put_money'];
            array_push($orderData,$order);      //普通充值

            $rechargeLog['shop_id'] = $shop_id;
            $rechargeLog['price'] = $memberInfo['put_money'];
            array_push($rechargeData,$rechargeLog); //普通反充值记录

            $statisticsLog['shop_id'] = $shop_id;
            $statisticsLog['order_sn'] = $order_sn;
            $statisticsLog['data_type'] = 1;
            $statisticsLog['price'] = $memberInfo['put_money'];
            $statisticsLog['title'] = '会员转门店';
            array_push($statisticsData,$statisticsLog); //普通退单股东记录
        }

        if( $memberInfo['exp_money'] > 0 ){
            //有限时余额
            //1：一笔原门店反充值
            $order_sn = 'CZ'.time().$old_shop_id;
            $order['shop_id'] =  $old_shop_id;
            $order['sn'] =  $order_sn;
            $order['amount'] = '-'.$memberInfo['exp_money'];
            $order['old_amount'] = '-'.$memberInfo['exp_money'];
            array_push($orderData,$order);      //普通反充值

            $rechargeLog['shop_id'] = $old_shop_id;
            $rechargeLog['price'] = '-'.$memberInfo['exp_money'];
            array_push($rechargeData,$rechargeLog); //普通反充值记录

            $statisticsLog['shop_id'] = $old_shop_id;
            $statisticsLog['order_sn'] = $order_sn;
            $statisticsLog['data_type'] = 2;
            $statisticsLog['price'] = '-'.$memberInfo['exp_money'];
            $statisticsLog['title'] = '会员转门店';
            array_push($statisticsData,$statisticsLog); //普通退单股东记录

            //2：一笔新门店充值
            $order_sn = 'CZ'.time().$shop_id;
            $order['shop_id'] =  $shop_id;
            $order['sn'] =  $order_sn;
            $order['amount'] = $memberInfo['exp_money'];
            $order['old_amount'] = $memberInfo['exp_money'];
            array_push($orderData,$order);      //普通充值

            $rechargeLog['shop_id'] = $shop_id;
            $rechargeLog['price'] = $memberInfo['exp_money'];
            array_push($rechargeData,$rechargeLog); //普通反充值记录

            $statisticsLog['shop_id'] = $shop_id;
            $statisticsLog['order_sn'] = $order_sn;
            $statisticsLog['data_type'] = 1;
            $statisticsLog['price'] = $memberInfo['exp_money'];
            $statisticsLog['title'] = '会员转门店';
            array_push($statisticsData,$statisticsLog); //普通退单股东记录
        }
        Db::startTrans();
        try{
            Db::name('member')->where('id',$memberInfo['id'])->setField('shop_code',$shopInfo['code']);
            if( count($orderData) ){
                foreach ( $orderData as $k=>$v ){

                    $orderId = Db::name('order') ->insertGetId($v);
                    $rechargeData[$k]['order_id'] = $orderId;
                    $statisticsData[$k]['order_id'] = $orderId;
                    Db::name('member_recharge_log')->insert($rechargeData[$k]);
                    Db::name('statistics_log')->insert($statisticsData[$k]);
                }
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return_error($e ->getMessage());
        }
        return_succ([],'转店成功');
    }

    /***
     * 查询提现金额是否正确
     */
    public function retailMoney()
    {
        $list = Db::name('order_retail') ->where('status',1)->select();
        $cash_out_list = Db::name('retail_cash_out')->where('status',0)->select();
        $res_out_list = [];     //申请提现的金额
        foreach ( $cash_out_list as $k=>$v ){
            if( in_array($v['member_id'],array_column($res_out_list,'member_id')) ){
                foreach ( $res_out_list as $k1=>$v1 ){
                    if( $v1['member_id']==$v['member_id'] ){
                        $res_out_list[$k1]['price'] += $v['price'];
                    }
                }
            }else{
                array_push($res_out_list,$v);
            }
        }
        foreach ( $res_out_list as $k=>$v ){
            $res_out_list[$k]['ke'] = 0;
            foreach ( $list as $k1=>$v1 ){
                if( $v['member_id'] == $v1['member_id'] ){
                    $res_out_list[$k]['ke'] += $v1['price'];
                }
            }
            $res_out_list[$k]['retail_money'] = Db::name('member_money')->where('member_id',$v['member_id'])->value('retail_money');
        }
        dump($res_out_list);
//        Db::startTrans();
//        try{
//            foreach ( $res_out_list as $k=>$v ){
//                Db::name('retail_cash_out') ->where('member_id',$v['member_id']) ->setField('price',$v['ke']);
//                Db::name('member_money')->where('member_id',$v['member_id'])->setField('retail_money',0);
//            }
//            Db::commit();
//        }catch (\Exception $e){
//            Db::rollback();
//        }
    }

    /***
     * 检查可提现金额是否正确
     */
    public function retailMoneyT()
    {
        $map[] = ['retail_money','>',0];
        $list = Db::name('member_money') ->where($map)->select();
        $list1 = Db::name('order_retail') ->where('status',1)->select();
        foreach ( $list as $k=>$v ){
            $list[$k]['tt'] = 0;
            foreach ( $list1 as $k1=>$v1 ){
                if( $v['member_id'] == $v1['member_id'] ){
                    $list[$k]['tt'] += $v1['price'];
                }
            }
        }
//        Db::startTrans();
//        try{
//            foreach ( $list as $k=>$v ){
//                Db::name('member_money')->where('member_id',$v['member_id'])->setField('retail_money',$v['tt']);
//            }
//            Db::commit();
//        }catch (\Exception $e){
//            Db::rollback();
//        }
        dump($list);
    }

    /***
     * 修改订单口罩的成本
     */
    public function editCost()
    {
        $where[] = ['b.is_online','eq',1];
        $where[] = ['b.pay_status','eq',1];
        $where[] = ['a.deliver_status','eq',1];
        $where[] = ['a.subtitle','like','%一次性无纺布口罩%'];
        $list = Db::name('order_goods')
            ->alias('a')
            ->join('order b','a.order_id=b.id')
            ->field('a.order_id,a.id,a.num,a.subtitle,a.real_price,a.oprice,a.all_oprice,a.attr_name,a.attr_ids,b.sn')
            ->where($where)->select();
//        dump($list);die;
        Db::startTrans();
        try{
            foreach ( $list as $k=>$v )
            {
                if ( $v['attr_ids'] == 2231 )
                {
                    //10包装的
                    $oprice = 13;
                }else{
                    $oprice = 65;
                }
                $all_oprice = bcmul($oprice,$v['num'],2);
                $arr = [];
                $arr = [
                    'oprice'    =>$oprice,
                    'all_oprice'    =>$all_oprice,
                ];
                $res = Db::name('order_goods') ->where('id',$v['id'])->update($arr);
                if ( !$res )
                {
                    break;
                }
                if ( $res )
                {
                    $where = [];
                    $where[] = ['type','eq',8];
                    $where[] = ['order_id','eq',$v['order_id']];
                    $res = Db::name('statistics_log') ->where($where)->setField('price',$all_oprice);
                }
                if ( !$res )
                {
                    break;
                }
            }
            if ( $res )
            {
                Db::commit();
            }else{
                return_error('error');
            }
        }catch (\Exception $e)
        {
            Db::rollback();
            dump($e ->getMessage());die;
        }
        return_succ([],$res);


    }

//    public function toCoupon()
//    {
//        $data = $this ->request ->param();
//        if ( empty($data['c_id']) )
//        {
//            return_error('请选择优惠券');
//        }
//        if ( !isset($data['member_id']) || !is_array($data['member_id']) || count($data['member_id'])==0 )
//        {
//            return_error('请选择用户');
//        }
//        $num = !empty($data['num']) ? $data['num'] : 1;
//        //获取优惠券详情
//        $info = (new CouponModel()) ->alias('a') ->where('id',$data['c_id'])->field('a.id,a.c_name,a.c_type,a.c_amo_dis,a.c_use_price,
//                a.c_use_cill,a.c_use_time,a.c_receive_num,a.c_provide_num,
//                a.c_content,a.c_start_time,a.c_is_show,a.is_delete')->find();
//
//        if( is_array($info['c_use_time']) ){
//            $invalid_time = $info['c_use_time']['end_time'];
//        }else{
//            $invalid_time = time() + ($info['c_use_time']*24*60*60);
//        }
//
//        $coupon_receive_data = [];
//        foreach ( $data['member_id'] as $v )
//        {
//            for ( $i=0;$i<$num;$i++ )
//            {
//                $arr = [
//                    'c_id'  =>$info['id'],
//                    'member_id'  =>$v,
//                    'is_use'  =>1,
//                    'use_time'  =>0,
//                    'create_time'  =>time(),
//                    'invalid_time'  =>$invalid_time,
//                    'get_type'  =>2
//                ];
//                array_push($coupon_receive_data,$arr);
//            }
//        }
////        $res = Db::name('coupon_receive') ->insertAll($coupon_receive_data);
////        dump($res);
//        dump($coupon_receive_data);
//    }
}