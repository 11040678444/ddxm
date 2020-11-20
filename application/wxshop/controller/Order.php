<?php
namespace app\wxshop\controller;

use app\common\model\TrackingMore;
use app\wxshop\model\assemble\AssembleListModel;
use app\wxshop\model\comment\StPack;
use app\wxshop\model\st_recharge\StRechargeFlow;
use Predis\Client;
use think\Db;
use think\Exception;
use think\Query;
use app\wxshop\model\item\SpecsModel;
use app\wxshop\model\order\OrderModel;
use app\wxshop\model\seckill\FlashSaleModel;
use app\wxshop\model\coupon\CouponModel;
use app\wxshop\model\coupon\CouponReceiveModel;
use think\validate;

/**
订单
 */
class Order extends Token
{
    /***
     * 获取运费
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPostage(){
        $data = $this ->request ->param();
        if( empty($data['address_id'])  ){
            return json(['code'=>100,'msg'=>'']);
        }
        if( empty($data['item'])  ){
            return json(['code'=>100,'msg'=>'未选择商品']);
        }
        $city_ids = Db::name('member_address')->where('id',$data['address_id'])->value('area_ids');
        if( !$city_ids ){
            return json(['code'=>100,'msg'=>'']);
        }

        $city_ids = explode(',',$city_ids);
        $cityId = $city_ids['0'];       //会员的地址id
        foreach ( $data['item'] as $k=>$v ){
            if( !empty($data['activity_id']) ){
                $data['item'][$k]['activity_id'] = $data['activity_id'];
            }
            if( !empty($data['order_distinguish']) ){
                $data['item'][$k]['order_distinguish'] = $data['order_distinguish'];
            }
            if( !empty($data['commander']) ){
                $data['item'][$k]['commander'] = $data['commander'];
            }
        }
        $postage = controller('Base')->getPostage($cityId,$data['item']);
        if( $postage['code'] == 200 ){
            //新人专享没有使用此运费规则,因为是免邮,在下单时运费也是在控制器写的固定值为0
            $postagePrice = $data['order_distinguish'] == 6? 0 :$postage['allNewPostagePrice'];
            return json(['code'=>200,'msg'=>'获取成功','data'=>$postagePrice]);
        }else{
            return json($postage);
        }
    }

    /***
     * 获取可使用的优惠券
     */
    public function getCanUseCoupon(){
        $data = $this ->request ->param();
        if( empty($data['item']) ){
            return_error('请选择商品');
        }
        $itemList = controller('Base')->getPostage(1,$data['item']);
        if( $itemList['code'] != 200 ){
            return_error('获取失败');
        }
        $itemList = $itemList['data'];
        $goodsList = [];
        foreach ( $itemList as $k=>$v ){
            $arr = [];
            $arr = [
                'id'    =>$v['id'],
                'num'    =>$v['num'],   //数量
                'brand_id'    =>$v['brand_id'],   //品牌id
                'all_price'    =>$v['price']*$v['num'],   //总会员价
                'all_old_price'    =>$v['old_price']*$v['num'],   //总原价
            ];
            array_push($goodsList,$arr);
        }
        $postData = [
            'item'  =>$goodsList,
            'member_id'  =>self::getUserId(),
            'kill'  =>1,
            'is_use'  =>1
        ];
        $canUse = (new CouponModel()) ->getCanUseCoupon($postData);
        return_succ($canUse,'获取成功');
    }

    /**
     * 获取充值送（活动）限时抵扣余额
     * member_id 会员ID
     * @param $call 0外部调用、1内部调用
     */
    public function getStRecharge($call=0)
    {
        try
        {
            if(request()->isPost())
            {
                $member_id = empty($call) ? self::getUserId() : $call;
                empty($member_id) ? return_succ('参数错误！') : '';

                $where[]= ['sr.member_id','eq',$member_id];
                $where[]= ['expires_time','egt',time()];
                $where[]= ['remain_price','<>','0'];

                $db = db('st_recharge')->alias('sr')->where($where);//->value('sum((enter_price-remain_price))');

                //判断内外部调用
                if($call)
                {
                    $balance = $db->join('st_recharge_flow srf','sr.id = srf.rec_id and srf.type = 3','left')
                             ->group('sr.id')
                             ->order('sr.expires_time asc')
                             ->column('sr.id,sr.remain_price,ifnull(sum(discount_price),0) freeze_amount');

                    return $balance;
                }else{
                    $balance = $db->value('sum((remain_price))');
                    return_succ(['amount'=>$balance,'ratio'=>config('Recharge')['rate']],'ok');
                }

            }
        }catch (\Exception $e){
            returnJson(500,[],$e->getMessage());
        }
    }

    /***
     * 根据选择的商品和优惠券获取最终金额
     */
    public function getCouponPrice( $postData = array(),$kill = 0 ){
        $data = !empty($postData)?$postData : $this ->request ->param();
        $itemData1 = controller('Base')->getPostage(1,$data['item']);      //商品列表，并携带每个商品的运费和售价，重量，体积等信息
        if( $itemData1['code'] !=200 ){
            return json($itemData1);
        }
        $itemData = $itemData1['data'];
        //使用了优惠券,判断优惠券可使用
        if( !empty($data['receive_id']) ){
            $coupon = (new CouponReceiveModel())
                ->alias('a')
                ->join('coupon_use_scene b','a.c_id=b.c_id')
                ->join('coupon c','a.c_id=c.id')
                ->where('a.id',$data['receive_id'])
                ->where('a.member_id',self::getUserId())
                ->field('a.c_id,a.is_use,a.invalid_time,b.cus_use_scene,b.cus_scene_id,c.c_use_price,c.c_use_cill,c.c_type,c.c_amo_dis')
                ->find();
            if( !$coupon ){
                return_error('优惠券不存在');
            }
            if( $coupon['is_use'] != 1 ){
                return_error('优惠券已失效');
            }
            if( $coupon['invalid_time'] < time() ){
                return_error('优惠券已过期');
            }
            $couponItems = [];      //使用了优惠券的商品id组
            $Items = [];            //未使用优惠券的商品id组
            foreach ( $itemData as $k=>$v ){
                if( !empty($coupon['cus_scene_id']) ){
                    $cus_scene_id = explode(',',$coupon['cus_scene_id']);
                    $cus_scene_id = array_filter($cus_scene_id);
                    if( $coupon['cus_use_scene'] == 1 ){
                        //指定商品
                        if( in_array($v['id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else if( $coupon['cus_use_scene'] == 2 ){
                        //指定商品不可用
                        if( !in_array($v['id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else if( $coupon['cus_use_scene'] == 3 ){
                        //指定品牌
                        if( in_array($v['brand_id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else{
                        //指定品牌不可用
                        if( !in_array($v['brand_id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }
                }else{
                    //都可用
                    array_push( $couponItems,$v['id'] );
                }
            }
            foreach ( $itemData as $k=>$v ){
                if( !in_array($v['id'],$couponItems) ){
                    array_push( $Items,$v['id'] );
                }
            }

            $couponGoodsList = [];  //使用了优惠券的商品
            $goodsList = [];  //未使用优惠券的商品
            foreach ( $itemData as $k=>$v ){
                if( in_array($v['id'],$couponItems) ){
                    array_push($couponGoodsList,$v);
                }
                if( in_array($v['id'],$Items) ){
                    array_push($goodsList,$v);
                }
            }
        }
        $discount = 0;
        if( !empty($data['receive_id']) ){
            //使用了优惠券
            $number1 = 0;        //优惠券商品总数量
            $item_amount1 = 0;        //优惠券商品总金额
            if( count($couponGoodsList) >0 ){
                foreach ( $couponGoodsList as $k=>$v ){
                    $number1 += $v['num'];
                    if( $coupon['c_use_price'] == 2 ){	//1原价适用、2会员价上使用
                        //会员价
                        $item_amount1 = $item_amount1 + ($v['num']*$v['price']);
                    }else{
                        //原价
                        $item_amount1 = $item_amount1 + ($v['num']*$v['old_price']);
                    }
                }
            }
            if( bccomp($item_amount1,$coupon['c_use_cill']) == -1 ){
                return_error('优惠券未达到要求');
            }
            if( $coupon['c_type'] == 1 ){   //1满减卷、2折扣券'
                $item_amount1 -= $coupon['c_amo_dis'];
                $discount = $coupon['c_amo_dis'];
            }else{
                $item_amount1 = $item_amount1*$coupon['c_amo_dis']/100;
                $discount = $item_amount1*(100-$coupon['c_amo_dis'])/100;
            }
            if( $item_amount1 < 0 ){
                $item_amount1 = 0;
            }
            $number2 = 0;        //未优惠券商品总数量
            $item_amount2 = 0;        //未优惠券商品总金额
            if( count($goodsList) > 0 ){
                foreach ( $goodsList as $k=>$v ){
                    $number2 += $v['num'];
                    $item_amount2 = $item_amount2 + ($v['num']*$v['price']);
//                    if( $coupon['c_use_price'] == 1 ){
//                        //会员价
//                        $item_amount2 = $item_amount2 + ($v['num']*$v['price']);
//                    }else{
//                        //原价
//                        $item_amount2 = $item_amount2 + ($v['num']*$v['old_price']);
//                    }
                }
            }
            $number = $number1 + $number2;
            $item_amount = $item_amount1 + $item_amount2;
//            if( $item_amount >= 99 ){
//                $postage = 0;
//            }else{
//                $postage = 10;
//            }
        }else{
            //未使用优惠券
            $number = 0;        //总数量
            $item_amount = 0;        //商品总金额
            foreach ($itemData as $k=>$v){
                $number += $v['num'];
                $item_amount = $item_amount + ($v['num']*$v['price']);
            }
//            if( $item_amount >= 99 ){
//                $postage = 0;
//            }else{
//                $postage = 10;
//            }
        }
        $postage = $itemData1['allNewPostagePrice'];       //总运费
        $amount = $item_amount + $postage;        //总金额
        if( empty($kill) ){
            return_succ(['money'=>$amount,'discount'=>$discount,'postage'=>$postage],'获取成功');
        }else{
            return ['amount'=>$amount,'postage'=>$postage]; //包含运费的总价，运费
        }
    }

    /***
     * 提交订单1：普通商品下单
     */
    public function order_doPost(){
        $data = $this ->request ->post();
        if( empty($data['address_id']) ){
            return json(['code'=>30,'msg'=>'请选择地址']);
        }
        if( empty($data['item']) ){
            return json(['code'=>30,'msg'=>'暂无商品']);
        }
        //判断会员本身是否绑定了手机号
        $memInfo = Db::name('member') ->where('id',self::getUserId())  ->field('mobile,attestation')->find();
        if( empty($memInfo['mobile']) ){
            return json(['code'=>100,'msg'=>'请先绑定手机号再下单哦']);
        }
        //地址
        $address = Db::name('member_address')->where(['id'=>$data['address_id'],'member_id'=>self::getUserId()])->find();
        if( !$address ){
            return json(['code'=>100,'msg'=>'地址错误']);
        }
        //根据地址计算运费
        $city_ids = $address['area_ids'];
        if( !$city_ids ){
            return json(['code'=>100,'msg'=>'地址获取失败']);
        }
        $city_ids = explode(',',$city_ids);
        $cityId = $city_ids['0'];       //会员的地址id
        $itemData1 = controller('Base')->getPostage($cityId,$data['item']);     //商品列表，并携带每个商品的运费和售价，重量，体积等信息
        if( $itemData1['code'] !=200 ){
            return json($itemData1);
        }
        $itemData = $itemData1['data'];
        //打包活动检测条件拼接
        $item_ids = '';

        //先判断普通购买商品时 库存情况
        foreach ($itemData as $k=>$v){

            //下架商品不能下单
            if($v['status'] !=1)
            {
                return json(['code'=>100,'msg'=>$v['title'].' 该商品已下架']);
            }

            if( $v['store'] != -1 ){
                if( $v['num'] >$v['store']  ){
                    return json(['code'=>100,'msg'=>$v['title'].'库存不足']);
                }
            }
            //拼接打包活动商品ID，方便后面调用验证查询
            if($v['order_distinguish'] == 5)
            {
                $item_ids.=$v['id'].',';
            }
        }

        //验证是否满足打包活动条件
        if(!empty($item_ids))
        {
            $stpack = (new  StPack())->isPack(rtrim($item_ids,','));
        }

        //判断 跨境商品和其他分区商品不能同时提交订单
        $moldIds = array_column($itemData,'mold_id');
        if( in_array(1,$moldIds) ){     //存在跨境商品
            foreach ( $moldIds as $k=>$v ){
                if( $v != 1 ){       //必须全部为跨境商品
                    return json(['code'=>100,'msg'=>'跨境商品需要单独提交订单哦,给您造成的不便请谅解']);
                }
            }
        }
        //判断是否需要身份验证
        $cross_border = 2;  //1跨境购订单，2自营订单
        foreach ($itemData as $k=>$v){
            if( $v['mold_id'] == 1 ){   //分区id为1的商品为跨境商品，需要验证身份证信息
                if( $memInfo['attestation'] != 1 ){
                    return json(['code'=>108,'msg'=>'请先实名认证']);     //前端需跳转到认证界面
                }
                if( $address['attestation'] != 1 ){
                    return json(['code'=>107,'msg'=>'收件人姓名与实名认证信息不符,请修改或更换收件人信息']);     //前端需跳转到编辑地址
                }
                $cross_border = 1;
            }
        }
        //使用了优惠券,判断优惠券可使用
        if( !empty($data['receive_id']) ){
            $coupon = (new CouponReceiveModel())
                ->alias('a')
                ->join('coupon_use_scene b','a.c_id=b.c_id')
                ->join('coupon c','a.c_id=c.id')
                ->where('a.id',$data['receive_id'])
                ->where('a.member_id',self::getUserId())
                ->field('a.c_id,a.is_use,a.invalid_time,b.cus_use_scene,b.cus_scene_id,c.c_use_price,c.c_use_cill,c.c_type,c.c_amo_dis')
                ->find();
            if( !$coupon ){
                return_error('优惠券不存在');
            }
            if( $coupon['is_use'] != 1 ){
                return_error('优惠券已失效');
            }
            if( $coupon['invalid_time'] < time() ){
                return_error('优惠券已过期');
            }
            $couponItems = [];      //使用了优惠券的商品id组
            $Items = [];            //未使用优惠券的商品id组
            foreach ( $itemData as $k=>$v ){
                if( !empty($coupon['cus_scene_id']) ){
                    $cus_scene_id = explode(',',$coupon['cus_scene_id']);
                    $cus_scene_id = array_filter($cus_scene_id);
                    if( $coupon['cus_use_scene'] == 1 ){
                        //指定商品
                        if( in_array($v['id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else if( $coupon['cus_use_scene'] == 2 ){
                        //指定商品不可用
                        if( !in_array($v['id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else if( $coupon['cus_use_scene'] == 3 ){
                        //指定品牌
                        if( in_array($v['brand_id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }else{
                        //指定品牌不可用
                        if( !in_array($v['brand_id'],$cus_scene_id) ){
                            array_push( $couponItems,$v['id'] );
                        }
                    }
                }else{
                    //都可用
                    array_push( $couponItems,$v['id'] );
                }
            }
            foreach ( $itemData as $k=>$v ){
                if( !in_array($v['id'],$couponItems) ){
                    array_push( $Items,$v['id'] );
                }
            }
            $couponGoodsList = [];  //使用了优惠券的商品
            $goodsList = [];  //未使用优惠券的商品
            foreach ( $itemData as $k=>$v ){
                if( in_array($v['id'],$couponItems) ){
                    array_push($couponGoodsList,$v);
                }
                if( in_array($v['id'],$Items) ){
                    array_push($goodsList,$v);
                }
            }
        }

        //拼装规格格式,方便订单明细
        $SpecsModel = new SpecsModel();
        foreach ($itemData as $k=>$v){
            $where = [];
            if( !empty($v['specs_ids']) ){
                $specsIds = str_replace('_',',',$v['specs_ids']);
                $where[] = ['id','in',$specsIds];
                $Specs = $SpecsModel ->where($where)->select()->append(['superior']);
                $t = '';
                foreach ($Specs as $k1=>$v1){
                    $t = $t.' '.$v1['superior'].':'.$v1['title'];
                }
                $itemData[$k]['attr_name'] = $t;
            }
        }
        $discount = 0;  //折扣
        $old_item_amount = 0;   //商品原价
        if( !empty($data['receive_id']) ){
            //使用了优惠券
            $number1 = 0;        //优惠券商品总数量
            $item_amount1 = 0;        //优惠券商品总金额
            if( count($couponGoodsList) >0 ){
                foreach ( $couponGoodsList as $k=>$v ){
                    $number1 += $v['num'];
                    if( $coupon['c_use_price'] == 2 ){	//1原价适用、2会员价上使用
                        //会员价
                        $item_amount1 = $item_amount1 + ($v['num']*$v['price']);
                    }else{
                        //原价
                        $item_amount1 = $item_amount1 + ($v['num']*$v['old_price']);
                    }
                }
            }
            $old_item_amount += $item_amount1; //商品原价
            if( bccomp($item_amount1,$coupon['c_use_cill']) == -1 ){
                return_error('优惠券未达到要求');
            }
            $item_coupon_amount = $item_amount1;    //优惠券商品的总金额
            if( $coupon['c_type'] == 1 ){   //1满减卷、2折扣券'
                $item_amount1 -= $coupon['c_amo_dis'];
                $discount = $coupon['c_amo_dis'];
            }else{
                $item_amount1 = $item_amount1*$coupon['c_amo_dis']/100;
                $discount = ($old_item_amount*(100-$coupon['c_amo_dis']))/100;
            }
            if( $item_amount1 < 0 ){
                $item_amount1 = 0;
                $discount = $item_coupon_amount;
            }
            $number2 = 0;        //未优惠券商品总数量
            $item_amount2 = 0;        //未优惠券商品总金额
            if( count($goodsList) > 0 ){
                foreach ( $goodsList as $k=>$v ){
                    $number2 += $v['num'];
                    $item_amount2 = $item_amount2 + ($v['num']*$v['price']);
//                    if( $coupon['c_use_price'] == 1 ){
//                        //会员价
//                        $item_amount2 = $item_amount2 + ($v['num']*$v['price']);
//                    }else{
//                        //原价
//                        $item_amount2 = $item_amount2 + ($v['num']*$v['old_price']);
//                    }
                }
            }
            $number = $number1 + $number2;
            $item_amount = $item_amount1 + $item_amount2;
            $old_item_amount += $item_amount2;  //商品原价
//            if( $item_amount >= 99 ){
//                $postage = 0;
//            }else{
//                $postage = 10;
//            }
        }else{
            //未使用优惠券
            $number = 0;        //总数量
            $item_amount = 0;        //商品总金额
            foreach ($itemData as $k=>$v){
                $number += $v['num'];
                $item_amount = $item_amount + ($v['num']*$v['price']);
            }
//            if( $item_amount >= 99 ){
//                $postage = 0;
//            }else{
//                $postage = 10;
//            }
            $old_item_amount += $item_amount;
        }
        //$data['order_distinguish']等于5表示打包活动免运费
        $postage = $data['order_distinguish'] !=5 ? $itemData1['allNewPostagePrice'] :0;       //总运费
        $amount = $item_amount + $postage;        //总金额
        $old_item_amount += $postage;   //商品原价

        //判断是否有门店
        $shopCode = Db::name('member') ->where('id',self::getUserId()) ->field('id,shop_code')->find();
        if( empty($shopCode['shop_code']) || $shopCode['shop_code']=='A00000' ){
            $shopId = 1;        //总店
        }else{
            $shopId = Db::name('shop') ->where('code',$shopCode['shop_code']) ->value('id');
        }
        //计算商品实际支付金额
        $all_coupon_old_price = 0;  //使用了优惠券商品的总原价
        foreach ( $itemData as $k=>$v ){
            if( !empty($couponItems) ){
                if( in_array($v['id'],$couponItems) ){
                    if( $coupon['c_use_price'] == 2 ){  //1原价适用、2会员价上使用
                        $old_price = $v['price'];   //单个商品原价
                    }else{
                        $old_price = $v['old_price'];
                    }
                    $itemData[$k]['old_price'] = $old_price;
                    $itemData[$k]['all_old_price'] = $old_price*$v['num'];
                    $all_coupon_old_price += $old_price*$v['num'];
                }
            }
        }
        foreach ( $itemData as $k=>$v ){
            if( isset($v['all_old_price']) ){
                $real_price = ($v['all_old_price'] - (($v['all_old_price']/$all_coupon_old_price)*$discount)) /$v['num'];
                $itemData[$k]['real_price'] = $real_price;
            }
        }
        //下单
        $order = [];        //订单数据
        //W->Wechat,M->mall
        $sn = 'WM'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).self::getUserId();
        $order = array(
            'shop_id'       =>$shopId,
            'member_id'     =>self::getUserId(),
            'sn'            =>$sn,
            'type'          =>1,
            'realname'      =>$address['name'],
            'detail_address'      =>str_replace(',','',$address['area_names']).$address['address'],
            'mobile'      =>$address['phone'],
            'number'      =>$number,       //商品总数量
            'discount'      =>$discount,     //折扣金额
            'postage'      =>$postage,         //总邮费
            //总金额,打包活动使用三元表达式（数量/活动基数*活动价格）
            'amount'      =>$data['order_distinguish'] !=5 ?$amount:($number/$stpack[0]['p_condition2'])*$stpack[0]['p_condition1'],
            'event_id'=> $data['order_distinguish'] ==5 ? $stpack[0]['id']:0,
            'pay_status'      =>0,
            'order_status'      =>0,
            'add_time'      =>time(),
            'is_online'      =>1,
            'order_type'      =>1,
            'old_amount'      =>$old_item_amount,       //原价
            'order_triage'      =>0,
            'user_id'      =>self::getUserId(),
            'is_admin'      =>2,
            'deliver_status'      =>count($itemData),   //商品种类个数
            'order_distinguish'      =>empty('order_distinguish') ? 0 :$data['order_distinguish'],//0: 普通订单 1：拼团订单 2：抢购订单，3限时抢购,4大礼包、5打包
            'address_id'    =>$data['address_id'],  //地址id
            'share_id'  =>!empty($data['share_id'])?$data['share_id']:0,
            'coupon_id'  =>!empty($data['receive_id'])?$coupon['c_id']:0,
            'c_receive_id'  =>!empty($data['receive_id'])?$data['receive_id']:0,
            'cross_border'  =>$cross_border
        );

        $orderGoodsData = [];       //订单详情商品表
        foreach ($itemData as $k=>$v){
            $arr = array(
                'type_id'   =>$v['type_id'],
                'category_id'   =>$v['type'],
                'subtitle'   =>$v['title'],
                'attr_price'   =>'',
                'pic'   =>$v['pic'],
                'item_id'   =>$v['id'],
                'num'   =>$v['num'],
                'price'   =>$v['old_price'],
                'oprice'   =>0,        //成本价
                'all_oprice'   =>0,
                'modify_price'   =>0,       //修改的金额
                'real_price'   =>isset($v['real_price'])?$v['real_price']:$v['price'],
                'status'   =>1,
                'is_outsourcing_goods'   =>0,
                'attr_name'   =>$v['attr_name']?$v['attr_name']:'',
                'supplier'   =>$v['sender_id'],
                'deliver_status'    =>0,
                'attr_ids'    =>$v['specs_ids'],
                'oprice'    =>$v['cost'],
                'all_oprice'    =>($v['cost']*$v['num'])
            );

            //是否使用限时金额抵扣（充值送活动）
            if(input('is_deduct/d'))
            {
                //修改单件商品实际支付价格（方便后台退款）
                $arr['real_price'] = bcsub($arr['real_price'],$arr['real_price']*config('Recharge')['rate'],2);
            }
            array_push($orderGoodsData,$arr);
        }

        //是否使用限时金额抵扣（充值送活动）
        if(input('is_deduct/d'))
        {
            //原总价（不加运费）
            $cost_total_price = bcsub($order['amount'],$postage,2);

            //抵扣后的价格【原价-（原价*抵扣率）+运费】
            $deduct_amount = $cost_total_price*config('Recharge')['rate'];//折扣金额
            $d_price = bcsub($cost_total_price,$deduct_amount,2);

            //最终订单价格
            $deduct_price = bcadd($d_price,$postage,2);

            //折扣不能大于商品价格
            if(bccomp($d_price,$cost_total_price) == 1)
            {
                return_error('折扣金额必须小于商品价格');
            }

            //修改折扣后的价格
            $order['amount'] = $deduct_price;

            //获取现有可用抵扣金额ID
            $ids = $this->getStRecharge($order['member_id']);
            empty($ids) ? return_error('抵扣金额无法使用') : '';

        }

        //是否参与五一活动1587571200
        if ( (time()>=1587571200) || (time()<=1588780800) )
        {
            if ( isset($data['wuyi_ok']) && $data['wuyi_ok'] == 1 )
            {
                $its = self::getAuth($data,1);
                if ( bccomp($its['amount'],300) == -1 )
                {
                    return_error('不满足活动条件,请按会员价支付');
                }
                $order['amount'] = $its['amount'];
                $order['wuyi_ok'] = 1;
                $order['wuyi_item_amount'] = $its['old_amount'];
                foreach ( $orderGoodsData as $k=>$v )
                {
                    foreach ( $its['items'] as $k1=>$v1 )
                    {
                        if ( $v['item_id'] == $v1['id'] )
                        {
                            if ($v1['wuyi_ok'] == 1)
                            {
                                $orderGoodsData[$k]['real_price'] = $v1['old_price'];
                                $orderGoodsData[$k]['price'] = $v1['price'];
                            }
                        }
                    }
                }
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            $orderId = Db::name('order')->insertGetId($order);

            if($orderId)
            {
                foreach ($orderGoodsData as $k=>$v){
                    $orderGoodsData[$k]['order_id'] = $orderId;
                }
                $res = Db::name('order_goods') ->insertAll($orderGoodsData);
            }else{
                Db::rollback();
                return_error('下单失败');
            }

            //如果使用抵扣金额（充值送活动），则添加使用记录
            if($res && input('is_deduct/d'))
            {
                //拼接使用记录数据
                foreach ($ids as $kk=>$vv)
                {
                    if($deduct_amount>0)
                    {
                        $this_discount = bcsub($vv['remain_price'],$deduct_amount,2);
                        //拼接使用记录数据
                        $recharge_flow[] = [
                            'rec_id'=>$vv['id'],
                            'order_id'=>$orderId,
                            'member_id'=>$order['member_id'],
                            'discount_price'=>$this_discount <= 0 ? $vv['remain_price'] : $deduct_amount,
                            'type'=>3,
                            'create_time'=>time()
                        ];
                        $deduct_amount-=$this_discount <= 0 ? $vv['remain_price'] : $deduct_amount;
                    }else{
                        break;
                    }
                }

                $res = (new StRechargeFlow())->setRechargeFlow($recharge_flow);
            }

            //判断是否从购物车的商品
            if($res)
            {
                $cartId = [];   //购物车id
                foreach ($itemData as $k=>$v){
                    if( isset($v['card_id']) && !empty($v['card_id']) ){
                        array_push($cartId,$v['card_id']);
                    }
                }
                if( count($cartId) >0 ){
                    $where = [];
                    $where[] = ['id','in',implode(',',$cartId)];
                    $res = Db::name('shopping_cart')->where($where)->update(['delete_time'=>time(),'status'=>0]);
                }
            }

            // 提交事务
            if($res)
            {
                Db::commit();
            }else{
                Db::rollback();
                return_error('下单失败');
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器内部错误','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'提交订单成功','data'=>array('amount'=>$order['amount'],'order_id'=>$orderId)]);
    }

    /***
     * 拼团下单第二期
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assemble_doPost1(){
        $data = $this ->request ->post();
        if( empty($data['address_id']) ){
            return json(['code'=>30,'msg'=>'请选择地址']);
        }
        if( empty($data['activity_id']) ){
            return json(['code'=>30,'msg'=>'请选择拼团的商品']);
        }
        $Assemble = new FlashSaleModel();
        $info = $Assemble->where('id',$data['activity_id'])
            ->field('id,people_num,assemble_num,type,start_time,end_time')
            ->find()->append(['status']);
        if( $info['status'] == 2 ){
            return json(['code'=>203,'msg'=>'活动还未开始']);
        }
        //判断当前登录用户是否存在此拼团
        $map = [];
        $map[] = ['assemble_id','eq',$data['activity_id']];
        $map[] = ['status','eq',1];
        $notOverAssembleList = (new AssembleListModel()) ->where($map)
            ->field('id,assemble_id')->append(['info'])->select()->toArray();
        foreach ( $notOverAssembleList as $k=>$v ){
            foreach ( $v['info'] as $k1=>$v1 ){
                if( $v1['member_id'] == self::getUserId() ){
                    $newArr = ['code'=>$v1['status']==0?208:109,'msg'=>$v1['status']==0?'您当前存在未支付的拼团订单,请先支付':'您已拼过此团了,请勿重复拼团','data'=>['order_id'=>$v1['order_id'],'amount'=>$v1['amount']]];
                    return json($newArr);
                }
            }
        }
        //判断拼团是否成员已满
        if( !empty($data['assemble_list_id']) ){
            $assemble_list_info_data = Db::name('assemble_list')
                ->where('id',$data['assemble_list_id'])
                ->find();
            if( $assemble_list_info_data['r_num'] == 0 ){
                return json(['code'=>188,'msg'=>'此团人数已满']);
            }
        }
        //判断是否存在未支付的拼团订单
        $map = [];
        $map[] = ['flash_sale_id','eq',$data['activity_id']];   //此活动
        $map[] = ['pay_way','eq',2];    //未支付
        $map[] = ['member_id','eq',self::getUserId()];  //本人
        if( empty($data['assemble_list_id']) ){
            $map[] = ['assemble_list_id','eq',0];
        }else{
            $map[] = ['assemble_list_id','eq',$data['assemble_list_id']];
        }
        $count = Db::name('order_assemble_log')->where($map)->find();
        if( $count ){
            $orderInfo = Db::name('order')->where('id',$count['order_id'])->field('id as order_id,amount')->find();
            return json(['code'=>208,'msg'=>'您当前存在未支付的拼团订单,请先支付','data'=>$orderInfo]);
        }
        //限购,这儿只判断限购，下面会判断库存是否不足
        $attWhere = [];
        $attWhere[] = ['flash_sale_id','eq',$data['activity_id']];
        $attWhere[] = ['item_id','eq',$data['item'][0]['id']];
        $attWhere[] = ['specs_ids','eq',$data['item'][0]['specs_ids']];
        $falash_sale_info = Db::name('flash_sale_attr')->where($attWhere)
            ->field('residue_num,commander_price')->find();

        //当前提交订单商品数量
        $num = $data['item'][0]['num'];
        $newIds = 'PT'.$data['item'][0]['id'].'_'.$data['item'][0]['specs_ids'];

        $residue_num =  $falash_sale_info['residue_num'];

        if($residue_num !='-1'){
            require_once APP_PATH . '/../vendor/predis/predis/autoload.php';

            $client     = new Client();
            $client->auth('ddxm661_admin');
            //判断对应的 库存是否存在
            $order_datas    = $client->get($newIds);

            if ($order_datas ==''){
                //如果不存在 添加缓存
                $res = $client->set($newIds,$residue_num-$num);
                $exp = $client->EXPIRE($newIds,3600);
            }else{//如果存在判断是否超出库存

                $order_datas = $order_datas-$num;
                $res = $client->set($newIds,$order_datas);
                $exp = $client->EXPIRE($newIds,3600);

                if($order_datas<0){
                    return json(['code'=>100,'msg'=>'该商品已经拼完啦']);
                }
            }
        }


        if( $falash_sale_info['residue_num'] != '-1' ){ //该商品此规格限制一共卖出的数量

            if( $falash_sale_info['residue_num'] == 0 ){
                return json(['code'=>100,'msg'=>'该商品已经拼完啦']);
            }else{
                if( $data['item'][0]['num'] > $falash_sale_info['residue_num'] ){
                    return json(['code'=>100,'msg'=>'该商品限购'.$falash_sale_info['residue_num'].'件']);
                }else{
                    //该商品此规格还未卖完,查看每人限制
                    if( $info['people_num'] != '-1' ){
                        //查询该用户此团已买数量
                        $infoWhere = [];
                        $infoWhere[] = ['assemble_id','eq',$data['activity_id']];
                        $infoWhere[] = ['status','in','1,2'];
                        $assembleListIds = Db::name('assemble_list')->where($infoWhere)->column('id');
                        $memberBuyWhere = [];
                        $memberBuyWhere[] = ['assemble_list_id','in',implode(',',$assembleListIds)];
                        $memberBuyWhere[] = ['status','neq','2'];
                        $memberBuyWhere[] = ['member_id','eq',self::getUserId()];
                        $buNum = Db::name('assemble_info')->where($memberBuyWhere)->sum('num');
                        if( ($buNum+$data['item'][0]['num']) > $info['people_num'] ){
                            return json(['code'=>100,'msg'=>'每人限购'.$info['people_num'].'件']);
                        }
                    }
                }
            }
        }
        foreach ( $data['item'] as $k=>$v ){
            if( !empty($data['activity_id']) ){
                $data['item'][$k]['activity_id'] = $data['activity_id'];
            }
            if( !empty($data['order_distinguish']) ){
                $data['item'][$k]['order_distinguish'] = $data['order_distinguish'];
            }
            if( !empty($data['commander']) ){
                $data['item'][$k]['commander'] = $data['commander'];
            }
        }
        //判断会员本身是否绑定了手机号
        $memInfo = Db::name('member') ->where('id',self::getUserId()) ->field('mobile,attestation')->find();
        if( empty($memInfo['mobile']) ){
            return json(['code'=>100,'msg'=>'请先绑定手机号再下单哦']);
        }
        //地址
        $address = Db::name('member_address')->where(['id'=>$data['address_id'],'member_id'=>self::getUserId()])->find();
        if( !$address ){
            return json(['code'=>100,'msg'=>'地址错误']);
        }
        //根据地址计算运费
        $city_ids = $address['area_ids'];
        if( !$city_ids ){
            return json(['code'=>100,'msg'=>'地址获取失败']);
        }
        $city_ids = explode(',',$city_ids);
        $cityId = $city_ids['0'];       //会员的地址id
        $itemData1 = controller('Base')->getPostage($cityId,$data['item']);      //商品列表，并携带每个商品的运费和售价，重量，体积等信息
        if( $itemData1['code'] !=200 ){
            return json($itemData1);
        }
        $itemData = $itemData1['data'];
        //判断库存
        foreach ( $itemData as $k=>$v ){

            //下架商品不能下单
            if($v['status'] !=1)
            {
                return json(['code'=>100,'msg'=>$v['title'].' 该商品已下架']);
            }

            if( $v['store'] != '-1' ){
                if( $v['store'] < $v['num'] ){
                    return json(['code'=>100,'msg'=>'商品库存不足']);
                }
            }
        }
        //判断是否需要身份验证
        $cross_border = 2;
        foreach ($itemData as $k=>$v){
            if( $v['mold_id'] == 1 ){
                if( $memInfo['attestation'] != 1 ){
                    return json(['code'=>108,'msg'=>'请先实名认证']);     //前端需跳转到认证界面
                }
                if( $address['attestation'] != 1 ){
                    return json(['code'=>107,'msg'=>'收件人姓名与实名认证信息不符,请修改或更换收件人信息']);     //前端需跳转到编辑地址
                }
                $cross_border = 1;
            }
        }
        //计算
        $number = 0;        //总数量
        $item_amount = 0;        //商品总金额
        $item_old_amount = 0;        //商品原价总金额
        $postage = $itemData1['allNewPostagePrice'];       //总运费
        foreach ($itemData as $k=>$v){
            $number += $v['num'];
            $item_amount = $item_amount + ($v['num']*$v['price']);
            $item_old_amount = $v['num']*$v['old_price'];
            $postage += $v['postagePrice'];
        }

        $amount = $item_amount+$postage;        //总金额
        $discount = $item_old_amount - $item_amount;        //折扣金额
        $old_amount = $item_old_amount + $postage;      //总原价
        //判断是否有门店
        $shopCode = Db::name('member') ->where('id',self::getUserId()) ->field('id,shop_code')->find();
        if( empty($shopCode['shop_code']) || $shopCode['shop_code']=='A00000' ){
            $shopId = 1;        //总店
        }else{
            $shopId = Db::name('shop') ->where('code',$shopCode['shop_code']) ->value('id');
        }
        //下单
        $sn = 'WM'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).self::getUserId();
        $order = [];        //订单数据
        $order = array(
            'shop_id'       =>$shopId,
            'member_id'     =>self::getUserId(),
            'sn'            =>$sn,
            'type'          =>1,
            'realname'      =>$address['name'],
            'detail_address'      =>str_replace(',','',$address['area_names']).$address['address'],
            'mobile'      =>$address['phone'],
            'number'      =>$number,       //商品总数量
            'discount'      =>$discount,     //折扣金额
            'postage'      =>$postage,         //总邮费
            'amount'      =>$amount,        //总金额
            'pay_status'      =>0,
            'order_status'      =>0,
            'add_time'      =>time(),
            'is_online'      =>1,
            'order_type'      =>1,
            'old_amount'      =>$old_amount,       //原价
            'order_triage'      =>0,
            'user_id'      =>self::getUserId(),
            'is_admin'      =>2,
            'deliver_status'      =>count($itemData),   //商品种类个数
            'order_distinguish'      =>1,        //0: 普通订单 1：拼团订单 2：抢购订单',3限时秒杀
            'address_id'    =>$data['address_id'],  //地址id
//            'attestation_id'    =>$attestation?$attestation['id']:0,    //身份验证id
            'event_id'  =>$data['activity_id'],
            'share_id'  =>!empty($data['share_id'])?$data['share_id']:0,
            'commander_type'  =>empty($data['assemble_list_id'])?1:2,    //拼团订单购买者的类型：1团长2团员
            'cross_border' =>$cross_border
        );
        $orderGoodsData = [];       //订单详情商品表
        foreach ($itemData as $k=>$v){
            $arr = array(
                'type_id'   =>$v['type_id'],
                'category_id'   =>$v['type'],
                'subtitle'   =>$v['title'],
                'attr_price'   =>'',
                'pic'   =>$v['pic'],
                'item_id'   =>$v['id'],
                'num'   =>$v['num'],
                'price'   =>$v['old_price'],
                'oprice'   =>0,        //成本价
                'all_oprice'   =>0,
                'modify_price'   =>$v['old_price']-$v['price'],       //修改的金额
                'real_price'   =>$v['price'],
                'status'   =>1,
                'is_outsourcing_goods'   =>0,
                'attr_name'   =>$v['attr_name']?$v['attr_name']:'',
                'supplier'   =>$v['sender_id'],
                'deliver_status'    =>0,
                'attr_ids'    =>$v['specs_ids'],
                'oprice'    =>$v['cost'],
                'all_oprice'    =>($v['cost']*$v['num'])
            );
            array_push($orderGoodsData,$arr);
        }
        // 启动事务
        Db::startTrans();
        try {
            //订单表和详情表
            $orderId = Db::name('order')->insertGetId($order);
            foreach ($orderGoodsData as $k=>$v){
                $orderGoodsData[$k]['order_id'] = $orderId;
            }
            Db::name('order_goods') ->insertAll($orderGoodsData);
            $order_assemble_data = [];  //拼团草稿表
            $order_assemble_data = [
                'flash_sale_id' =>$data['activity_id'],
                'commander_price'   =>$falash_sale_info['commander_price'],
                'price' =>$itemData[0]['price'],
                'old_price'   =>$itemData[0]['old_price'],
                'order_id'   =>$orderId,
                'item_id'   =>$itemData[0]['id'],
                'attr_ids'   =>$itemData[0]['specs_ids'],
                'assemble_list_id'   =>empty($data['assemble_list_id'])?0:$data['assemble_list_id'],
                'num'   =>$info['assemble_num'],
                'buy_num'   =>$itemData[0]['num'],
                'item_name'   =>$itemData[0]['title'],
                'real_price'   =>$itemData[0]['price'],
                'attr_name'   =>$itemData[0]['attr_name'],
                'member_id'   =>self::getUserId(),
                'pay_way'   =>2,
            ];
            Db::name('order_assemble_log') ->insert($order_assemble_data);
            //提交到assemble_list、assemble_info
//            if( empty($data['assemble_list_id']) ){
//                //表示开团
//                $assemble_list_data = [];   //拼团组的数据
//                $assemble_list_data = [
//                    'assemble_id'   =>$data['activity_id'],
//                    'create_time'   =>time(),
//                    'end_time'   =>time()+(24*60*60),
//                    'num'   =>$info['assemble_num'],
//                    'r_num'   =>$info['assemble_num'] - 1,
//                    'status'   =>1,
//                    'assemble_price'   =>$falash_sale_info['commander_price'],
//                    'old_price'   =>$itemData[0]['old_price'],
//                    'price'   =>$itemData[0]['price']
//                ];
//                // dump($assemble_list_data);die;
//                $assemble_list_id = Db::name('assemble_list') ->insertGetId($assemble_list_data);
//                $assemble_list_info = [];   //拼团详情的数据
//                $assemble_list_info = [
//                    'assemble_list_id'      =>$assemble_list_id,
//                    'order_id'      =>$orderId,
//                    'o_sn'      =>$sn,
//                    'item_id'      =>$itemData[0]['id'],
//                    'item_name'      =>$itemData[0]['title'],
//                    'real_price'      =>$itemData[0]['price'],
//                    'commander'      =>$data['commander']==1?1:2,
//                    'num'      =>$itemData[0]['num'],
//                    'create_time'      =>time(),
//                    'status'      =>0,
//                    'member_id'      =>self::getUserId(),
//                    'attr_ids'      =>$itemData[0]['specs_ids'],
//                    'attr_name'      =>$itemData[0]['attr_name'],
//                ];
//                Db::name('assemble_info')->insert($assemble_list_info);
//            }else{
//                //表示参团
//                $assemble_list_info = [];   //拼团详情的数据
//                $assemble_list_info = [
//                    'assemble_list_id'      =>$data['assemble_list_id'],
//                    'order_id'      =>$orderId,
//                    'o_sn'      =>$sn,
//                    'item_id'      =>$itemData[0]['id'],
//                    'item_name'      =>$itemData[0]['title'],
//                    'real_price'      =>$itemData[0]['price'],
//                    'commander'      =>$data['commander']==1?1:2,
//                    'num'      =>$itemData[0]['num'],
//                    'create_time'      =>time(),
//                    'status'      =>0,
//                    'member_id'      =>self::getUserId(),
//                    'attr_ids'      =>$itemData[0]['specs_ids'],
//                    'attr_name'      =>$itemData[0]['attr_name'],
//                ];
//                Db::name('assemble_info')->insert($assemble_list_info);
//                $assemble_list_info_data = Db::name('assemble_list')
//                    ->where('id',$data['assemble_list_id'])
//                    ->find();
//                if( $assemble_list_info_data['r_num'] == 0 ){
//                    return json(['code'=>188,'msg'=>'此团人数已满']);
//                }
//                $assemble_list_data = [];       //要修改的数据
//                Db::name('assemble_list')->where('id',$data['assemble_list_id']) ->setDec('r_num',1);
//            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>101,'msg'=>'下单失败','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'提交订单成功','data'=>['amount'=>$amount,'order_id'=>$orderId]]);
    }

    /**
     *判断秒杀商品是否超卖
     */
    public function isSeckill()
    {
        try{
            if(request()->isPost())
            {
                //获取详细参数
                $parm = input('post.item')[0];

                //数据验证
                $validate=new Validate([
                    'id|商品ID'=>'require',
                    'num|购买数量'=>'require',
                    'residue_num|限购数量'=>'require',
                ]);

                if (!$validate->check($parm)){
                    returnJson(301,[],$validate->getError());
                }

                //拼接KEY
                $newIds = $parm['id'].'_'.$parm['specs_ids'];

                require_once APP_PATH . '/../vendor/predis/predis/autoload.php';
                //查询REDIS缓存数据
                $client  = new Client();
                $res = $client->EXISTS($newIds);

                //第一次操作把库存优先存入Redis
                switch ($parm['residue_num'])
                {
                    case 0://限购数量为空直接返回
                        return_error('商品已秒杀结束',0);
                    break;

                    case $parm['residue_num']>0://大于0可进行秒杀

                        empty($res) ? $client->set($newIds,$parm['residue_num']) : $num = $client->get($newIds);

                        if(isset($num) && $num<=0)
                        {
                            return_error('商品已秒杀结束',$num);
                        }else{
                            return_succ($num,'ok');
                        }
                    break;

                    case -1://不限制
                        return_succ([],'ok');
                    break;
                }
            }
        }catch (\Exception $e){
            abort(300,$e->getMessage());
        }
    }

    /***
     * 秒杀下单：第二期
     */
    public function seckill_doPost1(){
        $data = $this ->request ->post();

        //当前提交订单商品数量
        $num = $data['item'][0]['num'];
        $newIds = $data['item'][0]['id'].'_'.$data['item'][0]['specs_ids'];
        $uid = self::getUserId();

        //开启分布式锁
        $lock = redisObj()->setnx($newIds.'_'.$uid.'_lock',time()+60);
        //设置分布式锁过期时间
        redisObj()->expire($newIds.'_'.$uid.'_lock',60);

        if(!$lock)
        {
            return_error('系统繁忙，请稍后！');
        }
	// redisObj()->del('2008_');
    // dump(redisObj()->get('2008_'));
    // dump(redisObj()->keys('*'));die;
		//判断是否非法购买
		if(intval($num) > intval(redisObj()->llen($newIds)))
		{
			return_error('购买数量不能大于剩余数量');
		}
		
        //根据Redis限购数量判断是否已被抢光
        if(redisObj()->llen($newIds)==0)
        {
            return_error('该商品被抢空了');
        }

        //缓存用户购买数量到Redis中
        if(!redisObj()->exists($newIds.'_'.$uid))
        {
        	if($num>intval(redisObj()->get($newIds.'_'.'residue_num')))
        	{
        		return_error('每人限购'.redisObj()->get($newIds.'_'.'residue_num'));
        	}

        }else{
        	//第二次进入
            $is_pass = (intval(redisObj()->llen($newIds.'_'.$uid))+intval($num))-redisObj()->get($newIds.'_'.'residue_num');

            if($is_pass>0)
            {
                return_error('每人限购'.redisObj()->get($newIds.'_'.'residue_num'));
            }
        }
        
        sleep(0.4);
        //判断每人限购数量
//        if(($num+redisObj()->llen($newIds.'_'.$uid)) >  redisObj()->get($newIds.'_'.'residue_num') || !redisObj()->get($newIds.'_'.'residue_num'))
//        {
//            return_error('每人限购'.redisObj()->get($newIds.'_residue_num'));
//        }


        if( empty($data['address_id']) ){
        	
            return json(['code'=>30,'msg'=>'请选择地址']);
        }
        if( empty($data['activity_id']) ){
        	
            return json(['code'=>30,'msg'=>'请秒杀的商品']);
        }
        // 查询  活动状态
        $Seckill = new FlashSaleModel();
        $info = $Seckill->where('id',$data['activity_id'])
            ->field('id,people_num,type,start_time,end_time')
            ->find()->append(['status']);
        if( $info['type'] == 3 ){
        	
            return json(['code'=>203,'msg'=>'活动id错误']);
        }
        if( $info['type'] != 4 ){
            if( $info['status'] == 2 ){
            	
                return json(['code'=>203,'msg'=>'活动还未开始']);
            }
            //判断此用户是否有此秒杀未支付的订单
            $mWhere = [];
            $mWhere[] = ['member_id','eq',self::getUserId()];
            $mWhere[] = ['pay_status','eq',0];
            $mWhere[] = ['event_id','eq',$data['activity_id']];
            $orderInfo = Db::name('order')->where($mWhere)->field('id as order_id,amount')->find();
            if ( $orderInfo ) {
            	
                return json(['code'=>208,'msg'=>'您当前存在未支付的秒杀订单,请先支付','data'=>$orderInfo]);
            }
            //限购,这儿只判断限购，下面会判断库存是否不足
            $attWhere = [];
            $attWhere[] = ['flash_sale_id','eq',$data['activity_id']];
            $attWhere[] = ['item_id','eq',$data['item'][0]['id']];
            $attWhere[] = ['specs_ids','eq',$data['item'][0]['specs_ids']];
            $falash_sale_info = Db::name('flash_sale_attr')->where($attWhere)
                ->field('residue_num')->find();
//            $residue_num = $falash_sale_info['residue_num'];
            //当前 可卖库存  -1表示无限


//            if($residue_num !='-1'){
//                require_once APP_PATH . '/../vendor/predis/predis/autoload.php';
//
//                $client     = new Client();
//
//                //判断对应的 库存是否存在
//                $order_datas    = $client->get($newIds);
//
//                if ($order_datas ==''){
//                    //如果不存在 添加缓存
//                    $res = $client->set($newIds,$residue_num-$num);
//                    $exp = $client->EXPIRE($newIds,3600);
//                }else{//如果存在判断是否超出库存
//
//                    $order_datas = $order_datas-$num;
//                    $res = $client->set($newIds,$order_datas);
//                    $exp = $client->EXPIRE($newIds,3600);
//
//                    if($order_datas<0){
//                        return json(['code'=>100,'msg'=>'该商品已经拼完啦']);
//                    }
//                }
//            }

            if( $falash_sale_info['residue_num'] != '-1' ){ //该商品此规格限制一共卖出的数量
            if( $falash_sale_info['residue_num'] == 0 ){
            	
                return json(['code'=>100,'msg'=>'该商品已经拼完啦']);
            }else{
                //该商品此规格还未卖完,查看每人限制
                if( $data['item'][0]['num'] > $falash_sale_info['residue_num'] ){
                	
                    return json(['code'=>100,'msg'=>'该商品限购'.$falash_sale_info['residue_num'].'件']);
                }else{
                    if( $info['people_num'] != '-1' ){
                        if( $data['item'][0]['num'] > $info['people_num'] ){
                        	
                            return json(['code'=>100,'msg'=>'该商品每人限购'.$info['people_num'].'件']);
                        }
                        //查询该用户此团已买数量
                        $infoWhere = [];
                        $infoWhere[] = ['event_id','eq',$data['activity_id']];
                        $infoWhere[] = ['pay_status','neq','-1'];
                        $infoWhere[] = ['member_id','eq',$uid];
                        $buNum = Db::name('order')->where($infoWhere)->sum('number');
                        if( ($buNum+$data['item'][0]['num']) > $info['people_num'] ){
                        	
                            return json(['code'=>100,'msg'=>'您当前已购买了'.$buNum.'件每人限购'.$info['people_num'].'件']);
                        }
                    }
                }
            }
        }
        }
        foreach ( $data['item'] as $k=>$v ){
            if( !empty($data['activity_id']) ){
                $data['item'][$k]['activity_id'] = $data['activity_id'];
            }
            if( !empty($data['order_distinguish']) ){
                $data['item'][$k]['order_distinguish'] = $data['order_distinguish'];
            }
            if( !empty($data['commander']) ){
                $data['item'][$k]['commander'] = $data['commander'];
            }
        }
        //判断会员本身是否绑定了手机号
        $memInfo = Db::name('member') ->where('id',$uid)  ->field('id,mobile,attestation')->find();
        if( empty($memInfo['mobile']) ){
        	
            return json(['code'=>100,'msg'=>'请先绑定手机号再下单哦']);
        }
        //地址
        $address = Db::name('member_address')->where(['id'=>$data['address_id'],'member_id'=>$uid])->find();
        if( !$address ){
        	
            return json(['code'=>100,'msg'=>'地址错误']);
        }
        //根据地址计算运费
        $city_ids = $address['area_ids'];
        if( !$city_ids ){
        	
            return json(['code'=>100,'msg'=>'地址获取失败']);
        }
        $city_ids = explode(',',$city_ids);
        $cityId = $city_ids['0'];       //会员的地址id
        $itemData1 = controller('Base')->getPostage($cityId,$data['item']);      //商品列表，并携带每个商品的运费和售价，重量，体积等信息
        if( $itemData1['code'] !=200 ){
            return json($itemData1);
        }
        $itemData = $itemData1['data'];
        //判断库存
        foreach ( $itemData as $k=>$v ){

            //下架商品不能
            if($v['status'] !=1)
            {
                return json(['code'=>100,'msg'=>$v['title'].' 该商品已下架']);
            }

            if( $v['store'] != '-1' ){
                if( $v['store'] < $v['num'] ){
                	
                    return json(['code'=>100,'msg'=>'商品库存不足']);
                }
            }
        }
        //判断是否需要身份验证
        $cross_border = 2;
        foreach ($itemData as $k=>$v){
            if( $v['mold_id'] == 1 ){
                if( $memInfo['attestation'] != 1 ){
                	
                    return json(['code'=>108,'msg'=>'请先实名认证']);     //前端需跳转到认证界面
                }
                if( $address['attestation'] != 1 ){
                	
                    return json(['code'=>107,'msg'=>'收件人姓名与实名认证信息不符,请修改或更换收件人信息']);     //前端需跳转到编辑地址
                }
                $cross_border = 1;
            }
        }

        //计算
        $number = 0;        //总数量
        $item_amount = 0;        //商品总金额
        $item_old_amount = 0;        //商品原价总金额
        $postage = $itemData1['allNewPostagePrice'];       //总运费
        foreach ($itemData as $k=>$v){
            $number += $v['num'];
            $item_amount = $item_amount + ($v['num']*$v['price']);
            $item_old_amount = $v['num']*$v['old_price'];
            $postage += $v['postagePrice'];
        }

        $amount = $item_amount+$postage;        //总金额
        $discount = $item_old_amount - $item_amount;        //折扣金额
        $old_amount = $item_old_amount + $postage;      //总原价
        //判断是否有门店
        $shopCode = Db::name('member') ->where('id',$uid) ->field('id,shop_code')->find();
        if( empty($shopCode['shop_code']) || $shopCode['shop_code']=='A00000' ){
            $shopId = 1;        //总店
        }else{
            $shopId = Db::name('shop') ->where('code',$shopCode['shop_code']) ->value('id');
        }
        //下单
        $sn = 'WM'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).$uid;
        $order = [];        //订单数据
        $order = array(
            'shop_id'       =>$shopId,
            'member_id'     =>$uid,
            'sn'            =>$sn,
            'type'          =>1,
            'realname'      =>$address['name'],
            'detail_address'      =>str_replace(',','',$address['area_names']).$address['address'],
            'mobile'      =>$address['phone'],
            'number'      =>$number,       //商品总数量
            'discount'      =>$discount,     //折扣金额
            'postage'      =>$postage,         //总邮费
            'amount'      =>$amount,        //总金额
            'pay_status'      =>0,
            'order_status'      =>0,
            'add_time'      =>time(),
            'is_online'      =>1,
            'order_type'      =>1,
            'old_amount'      =>$old_amount,       //原价
            'order_triage'      =>0,
            'user_id'      =>$uid,
            'is_admin'      =>2,
            'deliver_status'      =>count($itemData),   //商品种类个数
            'order_distinguish'      =>$info['type']==2?2:($info['type']==1?3:4),        //0: 普通订单 1：拼团订单 2：抢购订单',3限时秒杀,4大礼包,5打包
            'address_id'    =>$data['address_id'],  //地址id
//            'attestation_id'    =>$attestation?$attestation['id']:0,    //身份验证id
            'event_id'  =>$data['activity_id'],
            'share_id'  =>!empty($data['share_id'])?$data['share_id']:0,
            'cross_border'  =>$cross_border
        );
        $orderGoodsData = [];       //订单详情商品表
        foreach ($itemData as $k=>$v){
            $arr = array(
                'type_id'   =>$v['type_id'],
                'category_id'   =>$v['type'],
                'subtitle'   =>$v['title'],
                'attr_price'   =>'',
                'pic'   =>$v['pic'],
                'item_id'   =>$v['id'],
                'num'   =>$v['num'],
                'price'   =>$v['old_price'],
                'oprice'   =>0,        //成本价
                'all_oprice'   =>0,
                'modify_price'   =>$v['old_price']-$v['price'],       //修改的金额
                'real_price'   =>$v['price'],
                'status'   =>1,
                'is_outsourcing_goods'   =>0,
                'attr_name'   =>$v['attr_name']?$v['attr_name']:'',
                'supplier'   =>$v['sender_id'],
                'deliver_status'    =>0,
                'attr_ids'    =>$v['specs_ids'],
                'oprice'    =>$v['cost'],
                'all_oprice'    =>($v['cost']*$v['num'])
            );
            array_push($orderGoodsData,$arr);
        }

        // 启动事务
        Db::startTrans();
        try {
            //订单表和详情表
            $orderId = Db::name('order')->insertGetId($order);
            foreach ($orderGoodsData as $k=>$v){
                $orderGoodsData[$k]['order_id'] = $orderId;
            }
            Db::name('order_goods') ->insertAll($orderGoodsData);
            // 提交事务
            Db::commit();

            //修改数量（场景：每人限购2件，第一次只购买一件，再次购累加数量）
//            if(!redisObj()->exists($newIds.'_'.$uid.'_'.'tag'))
//            {
//            	redisObj()->lPush($newIds.'_'.$uid,1);
//            	//删除分布式锁
//            	redisObj()->del($newIds.'_'.$uid.'_lock');
//            }
            for ($i=0;$i<$num;$i++)
            {
                redisObj()->lPush($newIds.'_'.$uid,1);
            }

            //设置会员购买记录过期时间
            if(redisObj()->exists($newIds.'_'.$uid) && intval(redisObj()->ttl($newIds.'_'.$uid))<0)
            {
                $ttl = unserialize(redisObj()->get($data['activity_id']))['end_time']-time();
                redisObj()->expire($newIds.'_'.$uid,$ttl);
            }

            //删除分布式锁
            redisObj()->del($newIds.'_'.$uid.'_lock');

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>101,'msg'=>'下单失败']);
        }
        return json(['code'=>200,'msg'=>'提交订单成功','data'=>['amount'=>$amount,'order_id'=>$orderId]]);
    }

    // 商品 详情
    public function order_details(){
        $res = $this->request->post();
        if($res['order_id'] == ''){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }
        $model = new OrderModel();
        $data  = $model->order_details()->where("id",intval($res['order_id']))->find();
        if($data == false){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }
        $status= $model->getOrderStatus($data);
        $data['status'] = $status['status'];
        $data['status_attr'] = $status['status_attr'];
        $items = $model->order_goods($data['id'])->toArray();
        foreach ( $items as $k=>$v ){
            if( $data['paytime'] == '待支付' ){
                $items[$k]['deliver'] = '';
            }
        }
        $data['goods'] = $items;
        if( $data['paytime'] == '待支付' ){
            $end_time = Db::name('overtime_set')->field('set_waitpay_time,set_group_waitpay_time,set_rob_waitpay_time')->find();
            if( $data['order_distinguish'] == 0 ){
                //普通
                $outTime = $end_time['set_waitpay_time']*60*60;
            }else if( $data['order_distinguish'] == 1 ){
                //拼团
                $outTime = $end_time['set_group_waitpay_time']*60*60;
            }else if( $data['order_distinguish'] == 2 ){
                //抢购
                $outTime = $end_time['set_rob_waitpay_time']*60;
            }else{
                //限时抢购
                $outTime = $end_time['set_rob_waitpay_time']*60;
            }
            $data['end_time'] = strtotime($data['add_time'])+$outTime;
            $data['now_time'] = time();
        }

        //抵扣金额
        $data['discount_price'] = Db::name('st_recharge_flow')->where(['order_id'=>$res['order_id']])->value('ifnull(sum(discount_price),0.00)');
        return json(['code'=>200,'msg'=>"获取成功",'data'=>$data]);
    }

    // 更改快递编号
    public function test(){

        \db('item_company')->where('code','SF')->update(['code'=>'sf-express']);
        \db('order_express')->where('code','SF')->update(['code'=>'sf-express']);

        \db('item_company')->where('code','YD')->update(['code'=>'yunda']);
        \db('order_express')->where('code','YD')->update(['code'=>'yunda']);

        \db('item_company')->where('code','STO')->update(['code'=>'sto']);
        \db('order_express')->where('code','STO')->update(['code'=>'sto']);

        \db('item_company')->where('code','YTO')->update(['code'=>'yto']);
        \db('order_express')->where('code','YTO')->update(['code'=>'yto']);

        \db('item_company')->where('code','HTKY')->update(['code'=>'bestex']);
        \db('order_express')->where('code','HTKY')->update(['code'=>'bestex']);

        \db('item_company')->where('code','ZTO')->update(['code'=>'zto']);
        \db('order_express')->where('code','ZTO')->update(['code'=>'zto']);

        \db('item_company')->where('code','YZPY')->update(['code'=>'china-post']);
        \db('order_express')->where('code','YZPY')->update(['code'=>'china-post']);

        \db('item_company')->where('code','EMS')->update(['code'=>'china-ems']);
        \db('order_express')->where('code','EMS')->update(['code'=>'china-ems']);

        \db('item_company')->where('code','HHTT')->update(['code'=>'ttkd']);
        \db('order_express')->where('code','HHTT')->update(['code'=>'ttkd']);
    }
    //查看物流  state  0-无 轨 迹 1-已揽收 2-在途中 3-签收 4-问题件
    //Success 成功与否  true   false
    //查询 错误 信息  Reason
    public function getexpress(){

        $orderGoodsId = input('orderGoodsId');
        $order_id = input('order_id');
        if($orderGoodsId == '' || $order_id == ''){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }
        $order_express=\db('order_express')
            ->where('order_id',$order_id)
            ->where('order_goods_id',$orderGoodsId)
            ->find();
        if($order_express == false){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }

        $code = $order_express['code'];
        $sn = $order_express['sn'];

        try {
            $track = new Trackingmore();
            $extraInfo['destination_code'] = 'US';
            $extraInfo['lang'] = 'cn';
            $track = $track->getRealtimeTrackingResults($code, $sn, $extraInfo);

            $data = $track['data']['items'];
            $status = $data[0]['status'];
            if ($status == 'notfound') {//没有找到该物流单号

                $da = array();
                $va['AcceptName'] = '物流信息';
                $AcceptTime = '';
                $va['AcceptStation'] = '暂无轨迹';
                $va['AcceptTime'] = $AcceptTime;
                $da[0] = $va;
                $dat['list'] = $da;

                return json(['code' => 200, 'msg' => "获取成功", "data" => $dat,"brief_code"=>$code,"sn"=>$sn]);
            }

            if(!isset($data[0]['origin_info'])){
                $da = array();
                $va['AcceptName'] = '物流信息';
                $AcceptTime = '';
                $va['AcceptStation'] = '暂无轨迹';
                $va['AcceptTime'] = $AcceptTime;
                $da[0] = $va;
                $dat['list'] = $da;
                return json(['code' => 200, 'msg' => "获取成功", "data" => $dat,"brief_code"=>$code,"sn"=>$sn]);
            }

            if(!isset($data[0]['origin_info']['trackinfo'])){
                $da = array();
                $va['AcceptName'] = '物流信息';
                $AcceptTime = '';
                $va['AcceptStation'] = '暂无轨迹';
                $va['AcceptTime'] = $AcceptTime;
                $da[0] = $va;
                $dat['list'] = $da;
                return json(['code' => 200, 'msg' => "获取成功", "data" => $dat,"brief_code"=>$code,"sn"=>$sn]);
            }
            $trackinfo = $data[0]['origin_info']['trackinfo'];

            $da=array();
            $ischuku = false;//是否有 已出库  如果有了 表示运输中
            foreach ($trackinfo as $value){

                $AcceptStation =  $value['StatusDescription'];
                $AcceptName = '运输中';

                if(stristr($AcceptStation,'已收件')){
                    $AcceptName = '已收件';
                }else if(stristr($AcceptStation,'已打包')){
                    $AcceptName = '已打包';
                }else if(stristr($AcceptStation,'已发出')){
                    if(!$ischuku){
                        $AcceptName = '已出库';
                        $ischuku = true;
                    }
                }else if(stristr($AcceptStation,'派件中')){
                    $AcceptName = '派送中';
                }else if(stristr($AcceptStation,'快件已暂存')||stristr($AcceptStation,'取件')){
                    $AcceptName = '待取件';
                }else if(stristr($AcceptStation,'已签收')){
                    $AcceptName = '已签收';
                }

                $va['AcceptName'] = $AcceptName;
                $va['AcceptStation'] = $value['StatusDescription'];
                $va['AcceptTime'] = $value['Date'];
                $da[count($da)]=$va;
            }

            $dat['title']=$order_express['title'];
            $dat['sn']=$order_express['sn'];
            $dat['list']=$da;
            return json(['code'=>200,'msg'=>"获取成功","data"=>$dat,"brief_code"=>$code]);

        }catch (Exception $e){

            $da = array();
            $va['AcceptName'] = '物流信息';
            $AcceptTime = '';
            $va['AcceptStation'] = '暂无轨迹';
            $va['AcceptTime'] = $AcceptTime;
            $da[0] = $va;
            $dat['list'] = $da;

            return json(['code' => 200, 'msg' => "获取成功", "data" => $dat,"brief_code"=>$code,"sn"=>$sn]);
        }

    }

    //快递鸟查询快递
    public function getexpress2(){

        $orderGoodsId = input('orderGoodsId');
        $order_id = input('order_id');
        if($orderGoodsId == '' || $order_id == ''){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }
        $order_express=\db('order_express')
            ->where('order_id',$order_id)
            ->where('order_goods_id',$orderGoodsId)
            ->find();
        if($order_express == false){
            return json(['code'=>100,'msg'=>"该订单不存在"]);
        }

        $code = $order_express['code'];
        $sn = $order_express['sn'];
        $requestData['OrderCode']='';
        $requestData['ShipperCode']=$code;
        $requestData['LogisticCode']=$sn;

        $requestData = json_encode($requestData);

        $datas = array(
            'EBusinessID' => 1448049,
            'RequestType' => '1002',
            'RequestData' => urlencode($requestData) ,
            'DataType' => '2',
        );
        $datas['DataSign'] = encrypt($requestData, "ebeb5bc1-0dd6-4efc-806d-3f5812877735");
        $result=sendPost('http://api.kdniao.com/api/dist', $datas);
        $json = json_decode($result,true);
        if(!empty($json['Reason'])){//查询失败

            $dat['title']=$order_express['title'];
            $dat['sn']=$order_express['sn'];

            $da = array();
            $va['AcceptName'] = '物流信息';
            $AcceptTime ='';
            $va['AcceptStation'] = '暂无轨迹';
            $va['AcceptTime'] = $AcceptTime;
            $da[0]=$va;

            $dat['list']=$da;

            return json(['code'=>200,'msg'=>"获取成功","data"=>$dat]);
        }

        $Traces = $json['Traces'];
        $da=array();
        $ischuku = false;//是否有 已出库  如果有了 表示运输中
        foreach ($Traces as $value){

            $AcceptStation =  $value['AcceptStation'];
            $AcceptName = '运输中';

            if(stristr($AcceptStation,'已收件')){
                $AcceptName = '已收件';
            }else if(stristr($AcceptStation,'已打包')){
                $AcceptName = '已打包';
            }else if(stristr($AcceptStation,'已发出')){
                if(!$ischuku){
                    $AcceptName = '已出库';
                    $ischuku = true;
                }
            }else if(stristr($AcceptStation,'派件中')){
                $AcceptName = '派送中';
            }else if(stristr($AcceptStation,'快件已暂存')||stristr($AcceptStation,'取件')){
                $AcceptName = '待取件';
            }else if(stristr($AcceptStation,'已签收')){
                $AcceptName = '已签收';
            }

            $va['AcceptName'] = $AcceptName;
            $AcceptTime = date("m-d H:i",strtotime($value['AcceptTime']));
            $va['AcceptStation'] = $value['AcceptStation'];
            $va['AcceptTime'] = $AcceptTime;
            $da[count($da)]=$va;
        }
        $dat['title']=$order_express['title'];
        $dat['sn']=$order_express['sn'];
        $dat['list']=$da;
        return json(['code'=>200,'msg'=>"获取成功","data"=>$dat]);

    }
    //订单取消
    public function cancel_order(){
        $res = $this->request->post();
        if(!isset($res['order_id']) || empty($res['order_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }
        $order_id = intval($res['order_id']);
        $order = db::name("order")->alias('o')->field('o.*,og.attr_ids,og.item_id')->join('order_goods og','o.id = og.order_id')->where("o.id",$order_id)->find();

        if($order['pay_status']  == 1 ){
            return json(['code'=>"-3","msg"=>"数据错误","data"=>""]);
        }
        $attr = [
            "order_id"=>$order_id,
            "status"  => -8,
            "title"   => "订单取消",
            "add_time"=>time(),
        ];
        $update_order = [
            "pay_status" =>-1,
            "order_status"=>-8,
            "canceltime"=>time(),
            "cancel_way"=>1,
        ];

        // 启动事务
        Db::startTrans();
        try {
            Db::name('order') ->where('id',$order_id) ->update($update_order);
            $result = db::name("order_attr")->insert($attr);
            //判断是否为拼团订单,如果是拼团订单,判断此拼团是否成功
            if( $order['order_distinguish'] == 1 ){
                //拼团订单
                $assemble_info_order = Db::name('assemble_info') ->where('order_id',$order_id)
                    ->field('id,assemble_list_id,commander,status') ->find();
                if( $assemble_info_order['status'] == 0 ){
                    //修改拼团详情订单支付状态
                    Db::name('assemble_info') ->where('order_id',$order_id)->setField('status',2);
                    //修改拼团组表
                    if( $assemble_info_order['commander'] == 1 ){
                        //团长取消则这个组都取消
                        $assemble_list_update = [];
                        $assemble_list_update = [
                            'status'    =>3,
                            'reason'    =>'团长订单超时商城取消订单',
                            'over_time' =>time()
                        ];
                        Db::name('assemble_list')->where('id',$assemble_info_order['assemble_list_id'])->update($assemble_list_update);
                    }else{
                        //拼团团员取消订单
                        Db::name('assemble_list')->where('id',$assemble_info_order['assemble_list_id'])->setInc('r_num');
                    }
                }
                Db::name('order_assemble_log') ->where('order_id',$order_id) ->setField('pay_way',3);
            }
            // 提交事务
            Db::commit();
            
            //回滚Reids个人购买记录缓存
            $k = $order['item_id'].'_'.$order['attr_ids'].'_'.$order['member_id'];
            $u_num = redisObj()->llen($k);
            if(redisObj()->exists($k))
            {
            	$u_num > 0 ? redisObj()->rpop($k): redisObj()->del($k);
            }
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
        }
        return json(['code'=>200,"msg"=>"取消成功","data"=>""]);
//        if($result){
//            if(db::name("order")->where("id",$order_id)->update($update_order)){
//                return json(['code'=>200,"msg"=>"取消成功","data"=>""]);
//            }else{
//                return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
//            }
//        }else{
//            return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
//        }
    }
    //订单删除--前台
    public function del_order(){
        $res = $this->request->post();
        if(!isset($res['order_id']) || empty($res['order_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }
        $order_id = intval($res['order_id']);
        $order = db::name("order")->where("id",$order_id)->find();
        $user_id  = self::getUserId();
        if($user_id !== $order['member_id']){
            return json(['code'=>"-3","msg"=>"订单与申请人不一致",'data'=>""]);
        }
//        if($order['order_status']  !== 2 ){
//            return json(['code'=>"-3","msg"=>"数据错误","data"=>""]);
//        }
        $attr = [
            "order_id"=>$order_id,
            "status"  => -1,
            "title"   => "订单删除",
            "add_time"=>time(),
        ];
        $result = db::name("order_attr")->insert($attr);
        $update_order = [
            "isdel" =>1,
        ];
        if($result){
            if(db::name("order")->where("id",$order_id)->update($update_order)){
                if( $order['order_distinguish'] == 1 ){
                    Db::name('order_assemble_log') ->where('order_id',$order_id) ->setField('pay_way',3);
                }
                return json(['code'=>200,"msg"=>"删除成功","data"=>""]);
            }else{
                return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
            }
        }else{
            return json(['code'=>"-3","msg"=>"系统繁忙","data"=>""]);
        }
    }

    /***
     * 获取商品是否满足参与活动
     * 赠送ID为7的优惠券
     * 2020-04-23 00:00:00至2020-05-07 00:00:00
     * 原价300送一张，600送两张，依次类推
     * @param $kill
     * @return array
     */
    public function getAuth($postData=array(),$kill=0)
    {
        $data = count($postData) ?$postData:  $this ->request ->param();
        $amount = 0;        //参加活动的原价
        $allAmount = 0;     //未参加活动商品的会员价
        $list = controller('Base')->getPostage(1,$data['item']);
        $goodsIds = '2168,2176,2179,2181,2182,2183,2184,2185,2187,2189,2190,2192,2193,2194,2195,2197,2198,2199,2203,2205,2208,2209,2210,2211,4663,4664,5695,5696,5697,5698,5699,5700,4472,4473,4474,4475,4476,4605,4606,4607,4608,4609,4610,4611,4612,2143,2144,2145,2147,2150,2152,2274,2275,2276,2277,2278,2279,2280,2281,2282,2283,2284,2285,2286,2640,2646,2650,2651,2652,2653,2654,2655,2658,2659,2660,2661,2662,2663,2664,2666,2667,2668,2670,2674,2676,2703,2987,2988,4231,4429,4431,5104,5638,5673,5674,5675,5677,5678,5679,5680,5681,5685,5686,5687,5688,5689,5692,5694,4410,4411,4412,4430,5666,5672,2346,2347,2348,2349,2350,2351,2353,2354,2355,2356,2357,2358,2359,3916,3831,3832,3834,3840,3841,3842,3844,3845,3846,3847,3848,3849,3851,3852,3853,3854,3855,3856,3857,3859,3860,3861,3862,3864,3865,3866,3867,3868,3869,3871,3872,3874,3875,3876,3885,4526,4527,4528,4529,4530,4531,4532,4533,4534,4535,4536,4537,4993,4994,4995,4996,4997,4998,4999,5000,5037,5041,5044,5045,5046,5047,5050,5051,5052,5053,5054,5055,5056,5057,5058,5059,5060,5061,5062,5063,5064,5065,5066,5067,5068,5069,5074,5075,5076,5077,5078,5079,5080,5081,5082,5083,5084,5085,5086,5087,5088,5089,5090,5333,5660,5661,5662,5663,5664,5701,5702,5703,5704,5705,5706,5707,5708,5709,5710,5711,5712,5713,5714,5715,5716,5717,5718,5719,5720,5721,5722,5723,5724,5725,5726,5727,5728,5729,5730,5732,5733,5734,5735,5736,5737,5738,5739,5740,5741,5742,5743,5744';
        $goodsIds = explode(',',$goodsIds);
        foreach ( $list['data'] as $k=>$v )
        {
            if (in_array($v['id'],$goodsIds))
            {
                $list['data'][$k]['price'] = $v['old_price'];
                $list['data'][$k]['wuyi_ok'] = 1;
                $amount = bcadd($amount,bcmul($v['old_price'],$v['num'],2),2);
            }else{
                $list['data'][$k]['wuyi_ok'] = 0;
                $allAmount = bcadd($allAmount,bcmul($v['price'],$v['num'],2),2);
            }
        }
        //返回  如果参与活动的原价大于300,则返回所有商品（包括未参与活动的商品）的应付金额,反之则返回参与活动的商品的原价金额让前端判断
        if ( !$kill )
        {
            return_succ(['amount'=>bccomp($amount,300) != -1 ? bcadd($amount,$allAmount,2):$amount ],'success');
        }else {
            return ['amount'=>bccomp($amount,300) != -1 ? bcadd($amount,$allAmount,2):$amount ,'old_amount'=>$amount,'items'=>$list['data']];
        }
    }

    /***
     * 确认收货
     * @return \think\response\Json
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function confirm_order(){
        $res = $this->request->post();
        if(!isset($res['order_id']) || empty($res['order_id'])){
            return json(['code'=>"-3","msg"=>"参数错误","data"=>""]);
        }
        $order_id = intval($res['order_id']);
        $order = db::name("order")->where("id",$order_id)->find();
        //查询是否存在汪汪队商品
        $giveType = 0;  //是否赠送优惠券：0不赠送，1赠送
        if ( $order['coupon_id'] == 3 )
        {
            $goods = Db::name('order_goods') ->where('order_id',$order_id)->column('item_id');  //商品ID
            $ids = [4472,4473,4474,4475,4476,4605,4606,4607,4608,4609,4610,4611,4612];
            foreach ( $goods as $k=>$v ){
                if ( in_array($v,$ids) )
                {
                    $giveType = 1;
                    break;
                }
            }
        }

        if($order['order_status']  !== 1 ){
            return json(['code'=>"-3","msg"=>"数据错误","data"=>""]);
        }
        $attr = [
            "order_id"=>$order_id,
            "status"  => 2,
            "title"   => "确认收货",
            "add_time"=>time(),
        ];
        Db::startTrans();
        try {
            $res = db::name("order_attr")->insert($attr);
            if( !$res )
            {
                throw new \Exception('系统错误');
            }
            $update_order = [
//            "pay_status" =>-1,
                "order_status"=>2,
            ];
            $res = db::name("order")->where("id",$order_id)->update($update_order);
            if( !$res )
            {
                throw new \Exception('系统错误');
            }
            //已下单时间为准3.6号14：00分，汪汪队系列确认收货后立返买2送1优惠券，优惠券ID;4
            //赠送优惠券活动于2020/4/9日下线
//            if ( $giveType == 1 )
//            {
//                $res = (new CouponReceiveModel()) ->giveCoupon(['member_id'=>$order['member_id']]);
//                if( !$res )
//                {
//                    throw new \Exception('系统错误');
//                }
//            }

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            return_error('系统繁忙');
        }
        return_succ([],'收货成功');
    }
}