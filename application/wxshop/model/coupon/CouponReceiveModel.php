<?php

// +----------------------------------------------------------------------
// | 用户已领取的优惠券模块
// +----------------------------------------------------------------------
namespace app\wxshop\model\coupon;
use app\wxshop\model\coupon\CouponModel;
use think\Model;
use think\Db;
class CouponReceiveModel extends Model
{
    protected $table = 'ddxm_coupon_receive';

    /**
     * 已下单时间为准3.6号14：00分，使用优惠券ID3的优惠券购买汪汪队系列确认收货后立返买2送1优惠券，优惠券ID;4
     * 汪汪队商品 [4472,4473,4474,4475,4476,4605,4606,4607,4608,4609,4610,4611,4612]
     * member_id 用户ID
     * @param array $data
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function giveCoupon($data=array()){
        if ( empty($data) )
        {
            return false;
        }
        if ( empty($data['member_id']) )
        {
            return false;
        }
        $data['id'] = 4;        //优惠券ID为4
        $info = (new CouponModel()) ->alias('a') ->where('id',$data['id'])->field('a.id,a.c_name,a.c_type,a.c_amo_dis,a.c_use_price,
                a.c_use_cill,a.c_use_time,a.c_receive_num,a.c_provide_num,
                a.c_content,a.c_start_time,a.c_is_show,a.is_delete')->find();
        if( $info['is_delete'] == 1 )
        {
            return false; //当前优惠券不可领取
        }
        if( $info['c_receive_num'] != 0 ){
            $map = [];
            $map[] = ['member_id','eq',$data['member_id']];
            $map[] = ['c_id','eq',$data['id']];
            $member_receive_num = Db::name('coupon_receive') ->where($map) ->count();
            if( $info['c_receive_num'] <= $member_receive_num ){
                return_error('您已领取过此优惠券了');
            }
        }
        if( is_array($info['c_use_time']) ){
            $invalid_time = $info['c_use_time']['end_time'];
        }else{
            $invalid_time = time() + ($info['c_use_time']*24*60*60);
        }
        $coupon_receive_data = [];
        $coupon_receive_data = [
            'c_id'  =>$info['id'],
            'member_id'  =>$data['member_id'],
            'is_use'  =>1,
            'use_time'  =>0,
            'create_time'  =>time(),
            'invalid_time'  =>$invalid_time,
            'get_type'     =>2
        ];
        //开启事务
        Db::startTrans();
        try{
            $this ->insert($coupon_receive_data);
            if( $info['c_provide_num'] != -1 ){
                (new CouponModel()) ->where('id',$data['id'])->setDec('c_provide_num',1);
            }
            //提交事务
            Db::commit();
        }catch (\Exception $e){
            //事务回滚
            Db::rollback();
            return false;
        }
        return true;
    }

    /***
     * 赠送ID为7的优惠券
     * 2020-04-23 00:00:00至2020-05-07 00:00:00
     * 原价300送一张，600送两张，依次类推
     * @param $amount   总金额
     * @param $memberId 会员ID
     * @return boolean
     * @throws \Exception
     */
    public function giveCoupon2($amount,$memberId)
    {
        //这边不需要判断,直接在调用的时候判断好
        $time = time();
        if ( ($time>=1587571200) || ($time<=1588780800) )
        {
            if ( bccomp($amount,300) == -1 )
            {
                return false;
            }else{
                $addData = [];      //需要添加的数据
                $bei = floor(bcdiv($amount,300,2)); //整数倍数
                $coupon_receive_data = [
                    'c_id'  =>7,
                    'member_id'  =>$memberId,
                    'is_use'  =>1,
                    'use_time'  =>0,
                    'create_time'  =>time(),
                    'invalid_time'  =>1588780800,
                    'get_type'     =>2
                ];
                for ($i=0;$i<$bei;$i++)
                {
                    array_push($addData,$coupon_receive_data);
                }
                $res = $this ->allowField(true)->isUpdate(false)->saveAll($addData);
                return $res;
            }
        }else{
            return true;
        }
    }
}