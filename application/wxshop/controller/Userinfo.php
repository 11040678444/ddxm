<?php
namespace app\wxshop\controller;

use app\wxshop\model\retail\RetailCashOut;
use app\wxshop\model\st_recharge\StRechargeFlow;
use think\Db;
use think\Request;
use think\Query;
use app\wxshop\model\member\MemberExpireLogModel;
use app\wxshop\model\order\OrderRetailModel;
/**
商城,用户信息
 */
class Userinfo extends Token
{
    /***
     * 我的钱包:限时金额列表
     */
    public function expireList(){
        $data = $this ->request ->param();
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $list = Db::name('member_money_expire')
            ->where('member_id',self::getUserId())
            ->page($page)
            ->select();
        $count = Db::name('member_money_expire')
            ->where('member_id',self::getUserId())
            ->count();
        $res = [];
        foreach ( $list as $k=>$v ){
            $arr = [];
            $arr = [
                'id'    =>$v['id'],
                'status'    =>$v['status'],
                'price'    =>$v['price']-$v['use_price'],
                'expire_time'    =>$v['expire_time']==0?'':date('Y-m-d H:i:s',$v['expire_time']),
            ];
            array_push($res,$arr);
        }
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$res]);
    }

    /***
     * 会员限时余额激活
     */
    public function activationExpire(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择限时余额激活']);
        }
        if( empty($data['code']) || empty($data['mobile']) ){
            return json(['code'=>100,'msg'=>'请输入手机号或验证码']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        if (!preg_match($rule,$data['mobile'])) {
            return json(['code'=>100,'msg'=>'请输入正确的手机号']);
        }
        $where[] = ['mobile','eq',$data['mobile']];
        //验证码
        if( $data['code'] != '1130' ){
            $verification = Db::name('verification_code')->where($where)->order('send_time desc')->find();
            if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                return json(array('code'=>100,'msg'=>'验证码错误或已过期','data'=>''));
            }
        }
        $expireMoney = Db::name('member_money_expire') ->where('id',$data['id'])->where('member_id',self::getUserId())->find();
        if( !$expireMoney ){
            return json(['code'=>100,'msg'=>'服务发生错误','data'=>'id错误,未找到此用户的此id下的限时余额']);
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
     * 会员限时余额的使用详情
     */
    public function expireLog(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择限时余额']);
        }
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $expireMoney = Db::name('member_money_expire') ->where('id',$data['id'])->where('member_id',self::getUserId())->find();
        if( !$expireMoney ){
            return json(['code'=>100,'msg'=>'服务发生错误','data'=>'id错误,未找到此用户的此id下的限时余额']);
        }
        $arr =[];
        $arr = [
            'money' =>$expireMoney['price'],
            'title' =>'余额充值',
            'order_id' =>$expireMoney['order_id'],
            'create_time' =>date('m-d H:i:s',$expireMoney['create_time'])
        ];
        //查询余额记录
        $list = (new MemberExpireLogModel())
            ->where('money_expire_id',$data['id'])
            ->field('price as money,reason as title,order_id,create_time')
            ->page($page)->select()->toArray();
        foreach ( $list as $k=>$v ){
            if( $v['money'] < 0 ){
                $list[$k]['title'] .= '(返款)';
            }
        }
        $count = (new MemberExpireLogModel())
            ->where('money_expire_id',$data['id'])
            ->count()+1;
        array_unshift($list,$arr);

        $expireInfo = [
            'money' =>$expireMoney['price'] - $expireMoney['use_price'],
            'status' =>$expireMoney['status'],
            'expire_time' =>$expireMoney['expire_time'] != 0?date('Y-m-d H:i:s',$expireMoney['expire_time']):''
        ];
        $res = [];
        $res = [
            'expireInfo'    =>$expireInfo,
            'list'          =>$list
        ];
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$res]);
    }

    /**
     * 申请提现
     */
    public function applyOutMoney(){
        $data = $this ->request ->param();
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';
        $res = preg_match($rule, $data['money']);
        if($res == 0){
            return json(['code'=>100,'msg'=>'提现金额格式有误请核对！']);
        }
        if($data['money'] <10){
            return json(['code'=>100,'msg'=>'申请提现金额不低于10元']);
        }
        //判断可提现金额是否大于可提现金额
        $retail_money = Db::name('member_money') ->where('member_id',self::getUserId())->value('retail_money');
        if( $data['money'] > $retail_money ){
            return json(['code'=>100,'msg'=>'允许提现金额为:'.$retail_money.'元']);
        }
        $cash_out_data = [];
        $cash_out_data = [
            'sn'    =>$sn = 'TX'.date('Ymd').substr(implode(NULL,array_map('ord',str_split(substr(uniqid(),7,13),1))),0,8).self::getUserId(),
            'member_id' =>self::getUserId(),
            'price' =>$data['money'],
            'status' =>0,
            'create_time' =>time(),
            'title' =>'申请提现',
        ];
        // 启动事务
        Db::startTrans();
        try {
            Db::name('retail_cash_out') ->insert($cash_out_data);
            Db::name('member_money') ->where('member_id',self::getUserId())->setDec('retail_money',$data['money']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器繁忙','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'申请成功！']);
    }

    /**
     * 查询 可提现金额 收益--或 提现列表
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProfitList(){
        // 0:提现记录 1：收益记录
        $data = $this ->request ->param();
        $limit = $this ->request ->param('limit',10);
        $page = $this ->request ->param('page',1);
        $memberId = self::getUserId();
        $retail_money = Db::name('member_money') ->where('member_id',$memberId)->value('retail_money'); //可提现金额
        if( empty($data['state']) ){
            //提现记录
            $list = (new RetailCashOut())
                ->where(['member_id'=>$memberId])
                ->field('id,price as money,create_time as time,status as state')
                ->page($page,$limit)->select()
                ->append(['state']);
            foreach ( $list as $k=>$v ){
                $list[$k]['title'] = '用户提现';
            }
            $count = (new RetailCashOut())
                ->where(['member_id'=>$memberId])
                ->count();
        }else{
            //收益记录，查询
            $list = ( new OrderRetailModel() )
                ->where(['member_id'=>$memberId])
                ->where(['status'=>1])
                ->field('id,price as money,cut_of_time as time')
                ->page($page,$limit)->select();
            foreach ( $list as $k=>$v ){
                $list[$k]['title'] = '订单收益';
                $list[$k]['state'] = '';
            }
            $count = ( new OrderRetailModel() )
                ->where(['member_id'=>$memberId])
                ->where(['status'=>1])->count();
        }
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'retail_money'=>$retail_money,'data'=>$list]);
    }

    /***
     * 可用金额
     */
    public function canMoney(){
        $data = $this ->request ->param();
        $limit = $this ->request ->param('limit',10);
        $page = $this ->request ->param('page',1);
        //查询可用金额
        $memberId = self::getUserId();
        $money = Db::name('member_money')->where('member_id',$memberId)->field('money,online_money')->find();//money包含限时余额
        //查询已过期和未激活的
        $expireMoneyWhere = [];
        $expireMoneyWhere[] = ['member_id','eq',$memberId];
        $expireMoneyWhere[] = ['status','neq',2];
        $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->order('id asc')->field('id,price,use_price,status,expire_time')->select();
        if( count($expireList) > 0 ){
            $notExpireList = [];        //未激活的金额
            foreach ( $expireList as $k=>$v ){
                if ( ($v['status'] == 0) ){
                    array_push( $notExpireList , $v );
                }
            }
            $notExpireMoney = 0;       //未激活的限时余额
            if( count($notExpireList) > 0 ){
                foreach ( $notExpireList as $k=>$v ){
                    $notExpireMoney += $v['price'];
                }
            }
        }
        if( isset($notExpireMoney) ){
            $useMoney = $money['money']-$notExpireMoney+$money['online_money'];         //可用余额
        }else{
            $useMoney = $money['money']+$money['online_money'];         //可用余额
        }
        $useMoney = $useMoney<0?0:$useMoney;
        if( empty($data['type']) || $data['type'] == 1 ){
            //余额使用明细
            $list = Db::name('member_details')
                ->where('member_id',$memberId)
                ->field('id,amount as price,reason as title,addtime as time')
                ->page($page,$limit)
                ->order('id desc')
                ->select();
            $count = Db::name('member_details')
                ->where('member_id',$memberId)
                ->count();
        }else{
            //充值明细
            $list = Db::name('member_recharge_log')->where('member_id',$memberId)
                ->field('id,price,title,type,create_time as time')
                ->order('id desc')
                ->page($page,$limit)->select();
            $count = Db::name('member_recharge_log')->where('member_id',$memberId)
                ->count();
        }
        foreach ( $list as $k=>$v ){
            $list[$k]['time'] = date('m月d日 H:i',$v['time']);
        }
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /**
     * 获取充值送（活动）使用记录
     */
    public function getRechargeFlow()
    {
        try
        {
            if(request()->isPost())
            {
                $member_id = self::getUserId();

                empty($member_id) ? return_error('参数对象为空') : '';

                $list = (new StRechargeFlow())->getRechargeFlow($member_id);

                //使用过的金额
                $used_amount = array_sum(array_column($list['data'],'discount_price'));

                //获取当前抵扣最早失效的金额
                $where[] = ['member_id','eq',$member_id];
                $where[] = ['expires_time','egt',time()];
                $where[] = ['remain_price','gt',0];

                $st_recharge = db('st_recharge')
                            ->field('id,remain_price,from_unixtime(expires_time,"%Y-%m-%d %H:%i") expires_time')
                            ->where($where)
                            ->order('expires_time asc')
                            ->select();

                //总剩余金额
                $total_amount = array_sum(array_column($st_recharge,'remain_price'));

                $expires_tips = !empty($st_recharge) ? '你有一笔金额:'.$st_recharge[0]['remain_price'].'元  将在'.$st_recharge[0]['expires_time'].'过期' : '暂无信息';

                return_succ(['list'=>$list,'total_amount'=>$total_amount,'used_amount'=>$used_amount,'expires_tips'=>$expires_tips],'ok');
            }
        }catch (\Exception $e){
            returnJson(500,[],$e->getMessage());
        }
    }

    /***
     * 微信OPENID解绑手机号
     */
    public function relieveMobile(){
        $memberId = self::getUserId();
        Db::startTrans();
        try
        {
            $setRes = Db::name('member')->where('id',$memberId)->setField('openid','');
            $res = Db::name('member_token') ->where('user_id',$memberId)->delete();
            if( !$setRes || !$res )
            {
              throw new \Exception('服务器错误');
            }
            Db::commit();
        }
        catch (\Exception $e)
        {
            Db::rollback();
            return_error('服务器错误');
        }
        return_succ([],'解绑成功,请重新登录');
    }
}