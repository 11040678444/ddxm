<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/3/12 0012
 * Time: 上午 11:01
 *
 * 支付与流水
 */

namespace app\common\controller;
use app\common\model\MemberMoneyExpire;
use app\index\model\Member\MemberMoneyModel;
use think\Controller;
use think\Db;
use think\Validate;

class FinancialFlow extends Controller
{
    public function balancePay($data)
    {
        try
        {
            if(request()->isPost())
            {
                $data = [
                    'member_id'=>'8600',
                    'money'=>10100,
                    'change_money'=>1250,
                    'flow_type'=>1,
                    'order_sn'=>'WM20200304495010118600',
                    'order_id'=>'14177'
                    ];

                //数据验证
                dataValidate($data,[
                    'member_id|用户ID'=>'require',
                    'money|用户余额'=>'require',
                    'change_money|变化余额'=>'require',
                    'flow_type|类型'=>'require'//1线上商品消费、2门店消费、3现金充值、4赠送充值
                ]);

                //判断余额是否满足本次消费
                if(bccomp($data['money'],$data['change_money'])<0)
                {
                    return ['status'=>300,'余额不足'];
                }

                //根据不同的类型操作余额
                switch ($data['flow_type'])
                {
                    case 1:
                    case 2:
                       $after_change_money =  bcsub($data['money'],$data['change_money']);
                    break;

                    case 3:
                    case 4:
                    case 5:
                        $after_change_money = bcadd($data['money'],$data['change_money']);
                    break;
                }

                //查看是否存在限时余额 ,'status'=>1
                $money_expire = db('member_money_expire')->field('id,price,(price-use_price) balance')->where(['member_id'=>$data['member_id']])->select();

                Db::startTrans();
                //如果存在限时余额，并且处在可用限时余额，则执行扣除
                $tag = $data['change_money'];
                if($money_expire)
                {
                     foreach ($money_expire as $key=>$value)
                     {
                         //如果消费金额小于等于零，则表示已经满足扣除金额，则直接退出循环！
                         if(!$tag)
                         {
                             break;
                         }

                        if(bcsub($value['balance'],$tag)<=0)
                        {
                            //如果当前可用限时金额-消费金额<=0，则表示当前限时余额记录已扣完。
                            $datas[] = ['id'=>$value['id'],'user_price'=>$value['price']];
                        }else{
                             //如果还未扣除完，则继续扣除另外的限时余额，直到扣完为止！
                            $datas[] = ['id'=>$value['id'],'user_price'=>bcsub($value['balance'],$tag)];
                        }
                        //每次更新剩余未扣限时金额
                         $tag=bcsub($tag,$value['balance']);
                     }
                    // dump($datas);die;

                    //扣除限时余额
                    $res = (new MemberMoneyExpire())->saveAll($datas);
                    dump($res);die;
                   //添加限时余额扣除日志
                   if($res)
                   {

                   }
                }
                //dump($tag);die;
            }
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    /** 添加流水
     * @param array $data 数据集
     * @return array
     */
    public function addFlow($data=[])
    {
        try
        {
            //数据验证
            foreach ($data as $key=>$val)
            {
                $validate=new Validate([
                    'member_id|用户ID'=>'require',
                    'flow_code|流水编号'=>'require',//流水编号（格式：DDXM2020031288888888）
                    'order_code|订单ID'=>'require',
                    'flow_type|类型'=>'require',//类型：1线上商品消费、2门店消费、3线上充值、4赠送充值、5门店充值
                    'change_money|变化金额'=>'require',
                    'pre_change_money|变化前金额'=>'require',
                    'after_change_money|变化后金额'=>'require',
                    'pay_type|支付类型'=>'require',
                    'money_type|余额类型'=>'require'
                ]);
                if (!$validate->check($val)){
                    return ['status'=>300,'msg'=>$validate->getError()];
                }

                $data[$key]['create_time'] = $data[$key]['update_time'] = time();
            }


            //简单处理下并发
            $file = fopen('addFlow.txt','w+');

            if(flock($file,LOCK_EX|LOCK_NB))
            {
                //执行保存
                $res = Db::name('financial_flow')->insertAll($data);

                if($res)
                {
                    //打开文件锁
                    flock($file,LOCK_UN);
                    return ['status'=>200,'msg'=>'ok'];
                }

            }else{
                return ['status'=>300,'msg'=>'系统繁忙'];
            }
        }catch (\Exception $e){
            fclose($file);
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }
}