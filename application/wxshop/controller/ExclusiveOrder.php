<?php

// +----------------------------------------------------------------------
// | 新人专享下单控制器
// +----------------------------------------------------------------------
namespace app\wxshop\controller;

use app\mall_admin_market\model\exclusive\NewExclusive;
use app\mall_admin_market\model\exclusive\NewExclusiveGoods;
use app\mall_admin_market\model\exclusive\StPayLog;
use app\wxshop\model\item\ItemModel;
use app\wxshop\model\item\SpecsModel;
use think\Db;

class ExclusiveOrder extends Token
{
    /***
     * 新人专享下单
     */
    public function index()
    {
        $data = $this ->request ->param();
        if( empty($data['address_id']) )
        {
            return_error('请选择地址');
        }
        if( empty($data['activity_id']) )
        {
            return_error('请传入活动ID');
        }
        if( empty($data['order_distinguish']) )
        {
            return_error('ERROR');
        }
        //判断是否已购买过新人专享商品
        $payStatus = ( new StPayLog() ) ->userPayLog(['member_id'=>$this->getUserId()]);
        if( $payStatus == 1 )
        {
            return_error('您已购买过新人专享商品啦');
        }
        //判断活动商品是否已下架
        $map = [];
        $map[] = ['a.item_id','eq',$data['item'][0]['id']];
        $map[] = ['a.is_delete','eq',0];
        $map[] = ['a.ng_id','eq',$data['activity_id']];
        $map[] = ['b.is_delete','eq',0];
        $exclusive_goods = ( new NewExclusive() ) ->alias('a')
            ->join('st_exclusive_goods b','a.ng_id=b.id')
            ->where($map)
            ->field('a.id,a.price')->find();
        if( !$exclusive_goods )
        {
            return_error('新人专享商品已下架');
        }
        //判断会员本身是否绑定了手机号
        $memInfo = Db::name('member') ->where('id',self::getUserId())  ->field('mobile,attestation,id,shop_code')->find();
        if( empty($memInfo['mobile']) ){
            return json(['code'=>100,'msg'=>'请先绑定手机号再下单哦']);
        }
        if( empty($shopCode['shop_code']) || $shopCode['shop_code']=='A00000' ){
            $shopId = 1;        //总店
        }else{
            $shopId = Db::name('shop') ->where('code',$shopCode['shop_code']) ->value('id');
        }
        //地址
        $address = Db::name('member_address')->where(['id'=>$data['address_id'],'member_id'=>self::getUserId()])->find();
        if( !$address ){
            return json(['code'=>100,'msg'=>'地址错误']);
        }
        $itemData1 = controller('Base')->getPostage(1,$data['item']);
        if( $itemData1['code'] !=200 ){
            return json($itemData1);
        }
        $itemData = $itemData1['data'];
        //检测是否需要验证身份证
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
        //拼装规格格式,方便订单明细,并且将新人价换成实际金额
        $SpecsModel = new SpecsModel();
        foreach ($itemData as $k=>$v){
            $itemData[$k]['price'] = $exclusive_goods['price']; //将新人价换成实际支付金额
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
        $number = 1;    //数量，限制只能买一件
        $postage = 0;   //运费免邮
        $amount = $exclusive_goods['price'];   //总金额

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
            'discount'      =>0,     //折扣金额
            'postage'      =>$postage,         //总邮费
            'amount'      =>$amount,//总金额
            'pay_status'      =>0,
            'order_status'      =>0,
            'add_time'      =>time(),
            'is_online'      =>1,
            'order_type'      =>1,
            'old_amount'      =>$itemData[0]['old_price'],       //原价
            'order_triage'      =>0,
            'user_id'      =>self::getUserId(),
            'is_admin'      =>2,
            'deliver_status'      =>count($itemData),   //商品种类个数
            'order_distinguish'      =>empty('order_distinguish') ? 0 :$data['order_distinguish'],//0: 普通订单 1：拼团订单 2：抢购订单，3限时抢购,4大礼包、5打包、6新人专享
            'address_id'    =>$data['address_id'],  //地址id
            'event_id'  =>!empty($data['activity_id'])?$data['activity_id']:0,
            'share_id'  =>!empty($data['share_id'])?$data['share_id']:0,
            'coupon_id'  =>0,   //禁止使用优惠券
            'c_receive_id'  =>!empty($data['receive_id'])?$data['receive_id']:0,
            'cross_border'  =>$cross_border
        );
        $orderGoodsData = [];       //订单详情商品表
        foreach ($itemData as $k=>$v){
            $arr = array(
                'type_id'   =>0,
                'category_id'   =>0,
                'subtitle'   =>$v['title'],
                'attr_price'   =>'',
                'pic'   =>$v['pic'],
                'item_id'   =>$v['id'],
                'num'   =>$v['num'],
                'price'   =>$v['old_price'],
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
            array_push($orderGoodsData,$arr);
        }
        // 启动事务
        Db::startTrans();
        try {
            $orderId = Db::name('order')->insertGetId($order);
            foreach ($orderGoodsData as $k=>$v){
                $orderGoodsData[$k]['order_id'] = $orderId;
            }
            Db::name('order_goods') ->insertAll($orderGoodsData);
            //判断是否从购物车的商品
            $cartId = [];   //购物车id
            foreach ($itemData as $k=>$v){
                if( isset($v['card_id']) && !empty($v['card_id']) ){
                    array_push($cartId,$v['card_id']);
                }
            }
            if( count($cartId) >0 ){
                $where = [];
                $where[] = ['id','in',implode(',',$cartId)];
                Db::name('shopping_cart')->where($where)->update(['delete_time'=>time(),'status'=>0]);
            }
            //支付时再使用
//            if( !empty($data['receive_id'])){
//                (new CouponReceiveModel()) ->where('id',$data['receive_id'])->update(['is_use'=>2,'use_time'=>time(),'order_id'=>$orderId]);
//            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器内部错误','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'提交订单成功','data'=>array('amount'=>$amount,'order_id'=>$orderId)]);
    }
}