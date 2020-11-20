<?php
namespace app\wxshop\controller;

use app\admin\common\model\UploadModel;
use app\common\controller\Message;
use app\common\model\WechatModel;
use app\common\model\WxPayModel;
use app\wxshop\model\shareholder\ShareholderModel;
use org\QRcode;
use think\Controller;
use think\Db;
use think\Exception;
use think\facade\Cache;
use think\Request;

use app\wxshop\validate\user\User as UserValidate;
use app\wxshop\model\member\MemberAddressModel;
use app\common\model\CheckingIdCardModel;
use app\wxshop\model\retail\RetailCashOut;

use app\wxshop\model\n_order\OrderList;
use app\wxshop\model\n_order\OrderReturn;
/**
商城,获取用户信息
 */
class User extends Token
{
    /***
     * 获取地址列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function address_list(){
        $memberId = parent::getUserId();
        $data = $this ->request ->param();
        $MemberAds = new MemberAddressModel();
        $where = [];
        if( !empty($data['default']) ){
            $where[] = ['default','eq',$data['default']];
        }
        $where[] = ['member_id','eq',$memberId];
        $where[] = ['status','eq',1];
        $list = $MemberAds
            ->where($where)
            ->order('default desc , id desc')
            ->field('id,name,phone,area_ids,area_names,address,default,attestation')
            ->select()->append(['addres']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 获取地址详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function address_info(){
        $data = $this ->request->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请传入id']);
        }
        $MemberAds = new MemberAddressModel();
        $list = $MemberAds->where('id',$data['id'])->find()->append(['id_info']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 添加编辑地址
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function add_address(){
        $data = $this->request->post();
        $memberId = parent::getUserId();
        $validate = new UserValidate();
        if (!$validate->check($data)) {
            return json(['code'=>100,'msg'=>$validate->getError()]);
        }
        //判断是否实名
        $attestation = Db::name('member_attestation') ->where('member_id',self::getUserId())->field('name')->find();
        if( $attestation ){
            if( $attestation['name'] == $data['name'] ){
                $data['attestation'] = 1;   //已实名
            }
        }
        if( empty($data['id']) ){
            $data['create_time'] = time();
            $data['member_id'] = $memberId;
            if( $data['default'] == 1 ){
                Db::name('member_address')->where('member_id',$memberId)->update(['default'=>0]);
            }else{
                //查询是否存在默认地址，如果没有，则将此地址设置为默认地址
                $map = [];
                $map[] = ['member_id','eq',$memberId];
                $map[] = ['default','eq',1];
                $map[] = ['status','neq',0];
                $addDefault =  Db::name('member_address')->where($map)->find();
                if( !$addDefault ){
                    $data['default'] = 1;
                }
            }
            $res = Db::name('member_address')->insert($data);
        }else{
            if( $data['default'] == 1 ){
                Db::name('member_address')->where('member_id',$memberId)->update(['default'=>0]);
            }else{
                //查询是否存在默认地址，如果没有，则将此地址设置为默认地址
                $map = [];
                $map[] = ['member_id','eq',$memberId];
                $map[] = ['default','eq',1];
                $map[] = ['status','neq',0];
                $addDefault =  Db::name('member_address')->where($map)->find();
                if( !$addDefault ){
                    $data['default'] = 1;
                }
            }
            $res = Db::name('member_address')->update($data);
        }
        if( $res ){
            return json(['code'=>200,'msg'=>'操作成功']);
        }else{
            return json(['code'=>100,'msg'=>'操作失败']);
        }
    }

    /***
     * 上传身份证
     */
    public function CheckingIdCard(){
        set_time_limit(0);
        $data = $this ->request ->post();
        if( empty($data['front']) || empty($data['back']) ){
            return json(['code'=>100,'msg'=>'缺少参数']);
        }
        $memberInfo = Db::name('member') ->where('id',self::getUserId()) ->find();
        if( $memberInfo['attestation'] == 1 ){
            return json(['code'=>100,'msg'=>'该用户已认证通过']);
        }
        $CheckingIdCardModel = new CheckingIdCardModel();
        $frontInfo = $CheckingIdCardModel ->checkingIdCard('http://picture.ddxm661.com/'.$data['front'],'front');
        $backInfo = $CheckingIdCardModel ->checkingIdCard('http://picture.ddxm661.com/'.$data['back'],'back');
        if( $frontInfo['code'] !== 200 ){
            return json(['code'=>100,'msg'=>$frontInfo['msg']]);
        }
        if( $backInfo['code'] !== 200 ){
            return json(['code'=>100,'msg'=>$backInfo['msg']]);
        }
        if( time()<strtotime($backInfo['expiryDate']) ){
            return json(['code'=>100,'msg'=>'身份证已过期']);
        }
        $frontInfo = $frontInfo['result'];
        $backInfo = $backInfo['result'];
        $attestation = array(
            'member_id' =>parent::getUserId(),
            'front'     =>$data['front'],
            'back'     =>$data['back'],
            'name'     =>$frontInfo['name'],
            'address'     =>$frontInfo['address'],
            'birthday'     =>$frontInfo['birthday'],
            'code'     =>$frontInfo['code'],
            'sex'     =>$frontInfo['sex'],
            'nation'     =>$frontInfo['nation'],
            'issue'     =>$backInfo['issue'],
            'issueDate'     =>$backInfo['issueDate'],
            'expiryDate'     =>$backInfo['expiryDate'],
            'status'     =>1
        );
        if( empty($data['id']) ){
            $res = Db::name('member_attestation') ->insertGetId($attestation);
            $tt = Db::name('member') ->where('id',self::getUserId())->setField('attestation',1);
            $addList = Db::name('member_address') ->where('member_id',self::getUserId())->select();
            $addIds = [];
            foreach ( $addList as $k=>$v ){
                if ($v['name'] == $attestation['name']){
                    array_push($addIds,$v['id']);
                }
            }
            if( count($addIds) > 0 ){
                $where = [];
                $where[] = ['id','in',implode(',',$addIds)];
                Db::name('member_address')->where($where)->setField('attestation',1);
            }
        }else{
            $res = Db::name('member_attestation')->where('id',$data['id']) ->update($attestation);
            $res = $data['id'];
        }
        if( !$res ){
            return json(['code'=>100,'msg'=>'身份验证失败！请重试']);
        }
        return json(['code'=>200,'msg'=>'身份验证成功','data'=>array('attestation_id'=>$res)]);
    }

    /***
     *删除
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function address_del(){
        $data = $this->request->post();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请传入id']);
        }
        $addressInfo = Db::name('member_address')->where('id',$data['id'])->find();
        $res = Db::name('member_address')->where('id',$data['id'])->setField('status','0');
        if( $res ){
            return json(['code'=>200,'msg'=>'操作成功']);
        }else{
            return json(['code'=>100,'msg'=>'操作失败']);
        }
    }

    /***
     * 获取用户信息
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserInfo()
    {
        $userId = parent::getUserId();
        $userInfo = Db::name('member')
            ->field('id,mobile,pic,wechat_nickname as name1,nickname as name2,shop_code,attestation,retail,openid')
            ->where(['id'=>$userId])->find();
        $userInfo['nickname'] = !empty($userInfo['name1'])?$userInfo['name1']:$userInfo['name2'];
        //门店
        if( !empty($userInfo['shop_code']) ){
            $userInfo['shop_name'] = Db::name('shop') ->where('code',$userInfo['shop_code']) ->value('name');
        }else{
            $userInfo['shop_name'] = '';
        }
        //余额
        $menmbermoney = Db::name('member_money')->where(['member_id'=>$userId])->find();
        //总资产
        if($menmbermoney == false){
            $userInfo['money'] = $menmbermoney['money'];
            $userInfo['usable_money'] = '0.00';
        }else{
            //查询会员余额是否存在未激活限时余额
            $userInfo['money'] = $menmbermoney['money']+$menmbermoney['online_money'];  //加上线上余额
            $expireMoneyWhere = [];
            $expireMoneyWhere[] = ['member_id','eq',$userId];
            $expireMoneyWhere[] = ['status','neq',2];
            $expireList = Db::name('member_money_expire')->where($expireMoneyWhere)->order('id asc')->field('id,price,use_price,status,expire_time')->select();
            if( count($expireList) <= 0 ){
                $userInfo['usable_money'] = $menmbermoney['money'];
                $userInfo['expireList'] = [];
            }else{
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
                $userInfo['usable_money'] = $menmbermoney['money'] - $notExpireMoney;  //可用余额
                //拼装会员限时余额列表
                $newExpireList = [];        //限时余额列表
                foreach ( $expireList as $k=>$v ){
                    $arr = [];
                    $arr = [
                        'status'    =>$v['status'],
                        'price'    =>$v['price'] - $v['use_price'],
                        'expire_time'    =>$v['expire_time']==0?'':date('Y-m-d H:i:s',$v['expire_time'])
                    ];
                    if( $arr['price'] != 0 ){
                        array_push($newExpireList,$arr);
                    }
                }
                $userInfo['expireList'] = $newExpireList;
            }
        }
        $userInfo['usable_money'] += $menmbermoney['online_money'];  //加上线上余额
        //累积收益
        $userInfo['accumulate_money'] = 0.00;
        //预估收益
        $userInfo['stimate_money'] = 0.00;
        $OrderList = new OrderList();
        //待付款
//        $userInfo['stay_pay'] = controller('Orderinfo')->countOrder(1);
        $senData1 = ['member_id'=>$userInfo['id'],'o_pay_status'=>0];
        $userInfo['stay_pay'] = $OrderList ->getList($senData1)['count'];
        //待发货
//        $userInfo['stay_sendout'] = controller('Orderinfo')->countOrder(2);
        $senData2 = ['member_id'=>$userInfo['id'],'orderAllStatus'=>1];
        $userInfo['stay_sendout'] = $OrderList ->getList($senData2)['count'];
        //待收货
//        $userInfo['stay_takeover'] = controller('Orderinfo')->countOrder(3);
        $senData3 = ['member_id'=>$userInfo['id'],'orderAllStatus'=>2];
        $userInfo['stay_takeover'] = $OrderList ->getList($senData3)['count'];
        //待评论
        $userInfo['stay_discuss'] = controller('Orderinfo')->countOrder(4);
        //售后/退货
//        $userInfo['stay_sale'] = controller('Orderinfo')->countOrder(5);
        $userInfo['stay_sale'] = ( new OrderReturn() )->getList(['o.member_id'=>$userInfo['id'],'or.ot_status'=>0]);
        //分销员数量
        $map = [];
        $map[] = ['a.one_member_id','eq',self::getUserId()];
        $map[] = ['b.status','eq',1];
        $userInfo['fans_num'] = Db::name('retail_user')
            ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->where($map)
            ->field('b.id,b.nickname,b.mobile as phone,b.pic,b.regtime as createtime')
            ->count();
        $userInfo['fens_num'] = Db::name('retail_fans')
            ->where('member_id',self::getUserId())
            ->where('status',1)
            ->count();  //粉丝数量
        //可提现金额
        $userInfo['out_money'] = $menmbermoney['retail_money']; //可提现金额
        //可用余额---用于提交订单使用
//        $userInfo['usable_money'] = 0.00;
        //判断用户是否为股东
        $Shareholder = new ShareholderModel();
        $where = [];
        $where['mobile'] = $userInfo['mobile'];
        $where['status'] = 1;
        $info = $Shareholder ->where($where)->field('id,mobile,shop_ids')->find();
        if( $info ){
            $userInfo['isShareholder'] = 1;
        }else{
            $userInfo['isShareholder'] = 0;
        }
        //获取总共优惠券
        $userInfo['coupon_count'] = Db::name('coupon_receive') ->where('member_id',$userId)->where('is_use',1)->count();

        //充值抵押金额（充值送活动）
        $whe[] = ['member_id','eq',$userId];
        $whe[] = ['expires_time','egt',time()];
        $whe[] = ['remain_price','gt',0];
        $userInfo['st_recharge'] = Db::name('st_recharge')->where($whe)->sum('remain_price');
        $userInfo['is_st_recharge'] = Db::name('st_recharge')->where(['member_id'=>$userId])->count();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$userInfo]);
    }

    /***
     * 获取认证信息
     */
    public function getAttestation(){
        $attestation = Db::name('member_attestation') ->field('front,back') ->where('member_id',self::getUserId())->find();
        if( !$attestation ){
            return json(['code'=>100,'msg'=>'还未认证']);
        }
        $attestation['front'] = config('QINIU_URL').$attestation['front'];
        $attestation['back'] = config('QINIU_URL').$attestation['back'];
        return json(['code'=>200,'msg'=>'获取成功','data'=>$attestation]);
    }

    /***
     * 填写分销员资料
     */
    public function retail_message(){
        $data = $this ->request ->param();
        if( empty($data['mobile']) || empty($data['name']) ){
            return json(['code'=>100,'msg'=>'请填写手机号或姓名']);
        }
        $user = Db::name('member')->where('id',self::getUserId())->find();
        if( $user['retail'] == 1 ){
            return json(['code'=>100,'msg'=>'您已成为分销员,请勿重复提交']);
        }
        if( !empty($data['user_id']) ){
            $user = Db::name('member')->field('status,retail') ->where('id',$data['user_id'])->find();
            if( (!$user) || ($user['status']!= 1) ){
                return json(['code'=>100,'msg'=>'推荐人信息错误']);
            }
            if( $user['retail'] != 1 ){
                return json(['code'=>100,'msg'=>'推荐人不是分销商']);
            }
            $retail_user = Db::name('retail_user') ->where('member_id',$data['user_id'])->find();
            if( $retail_user ){
                if( !empty($retail_user['one_member_id']) ){
                    $two_member_id = $retail_user['one_member_id'];     //二级推荐人id
                }else{
                    $two_member_id = 0;     //二级推荐人id
                }
            }else{
                $two_member_id = 0;     //二级推荐人id
            }
        }
        $retail_user_data = [
            'member_id' =>self::getUserId(),
            'one_member_id'=>!empty($data['user_id'])?$data['user_id']:0,
            'two_member_id'=>isset($two_member_id)?$two_member_id:0,
            'create_time'=>time(),
            'update_time'=>time(),
            'mobile'=>$data['mobile'],
            'name'=>$data['name']
        ];
        // 启动事务
        Db::startTrans();
        try {
            Db::name('retail_user') ->insert($retail_user_data);
            Db::name('member')->where('id',self::getUserId())->setField('retail',1);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器繁忙,请稍后重试','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'成为分销商成功']);
    }

    /***
     * 更改手机号
     */
    public function edit_mobile(){
        $data = $this ->request ->post();
        if( empty($data['mobile']) || !isset($data['code']) ){
            return json(['code'=>100,'mgs'=>'请输入验证码']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule,$data['mobile']);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        //验证码
        //测试专用
        $where[] = ['mobile','eq',$data['mobile']];
        if( $data['code'] != '1234' ){
            $verification = Db::name('verification_code')->where($where)->order('send_time desc')->find();
            if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                return json(array('code'=>'-6','msg'=>'验证码错误或已过期','data'=>''));
            }
        }
        $member = Db::name('member')->where($where)->find();
        if( $member ){
            if( $member['id'] !== self::getUserId() ){
                return json(['code'=>100,'msg'=>'该手机号已被他人使用']);
            }else{
                return json(['code'=>100,'msg'=>'与原手机号一致']);
            }
        }
        // 启动事务
        Db::startTrans();
        try {
            Db::name('member')->where('id',self::getUserId())->setField('mobile',$data['mobile']);
            Db::name('member_data')->where('member_id',self::getUserId())->setField('mobile',$data['mobile']);
            Db::name('member_details')->where('member_id',self::getUserId())->setField('mobile',$data['mobile']);
            Db::name('member_money')->where('member_id',self::getUserId())->setField('mobile',$data['mobile']);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>300,'msg'=>'修改失败']);
        }
        return json(['code'=>200,'msg'=>'修改成功']);
    }

    /***
     * 退出登陆
     * @return mixed
     */
    public function outLogin()
    {
        $res =  parent::outLogin(); // TODO: Change the autogenerated stub
        if( $res ){
            return json(['code'=>200,'msg'=>'退出成功','data'=>'']);
        }else{
            return json(['code'=>100,'msg'=>'退出失败','data'=>'']);
        }
    }

    /***
     * 删除搜索历史
     * @return \think\response\Json
     */
    public function del_history(){
        $memberId = parent::getUserId();
        $res = Db::name('hot')->where(['type'=>2,'member_id'=>$memberId])->setField('status',2);
        if( $res ){
            return json(['code'=>200,'msg'=>'删除成功']);
        }else{
            return json(['code'=>100,'msg'=>'删除失败']);
        }
    }

    /**
     * 查询 收益--或 提现列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getProfitList(){
//        // 0:提现记录 1：收益记录
//        $data = $this ->request ->param();
//        $limit = $this ->request ->param('limit',10);
//        $page = $this ->request ->param('page',1);
//        $memberId = self::getUserId();
//        $list = (new RetailCashOut())
//            ->where(['member_id'=>$memberId])
//            ->field('id,price as money,create_time as time,status as state')
//            ->page($page,$limit)->select()
//            ->append(['state']);
//        foreach ( $list as $k=>$v ){
//            $list[$k]['title'] = '用户提现';
//        }
//        $count = (new RetailCashOut())
//            ->where(['member_id'=>$memberId])
//            ->count();
//        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
//    }

//    /**
//     * 申请提现
//     */
//    public function applyOutMoney(){
//        $money=input('money',0);
//        if($money <10){
//            return json(['code'=>100,'msg'=>'申请提现金额不低于10元']);
//        }
//        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';
//        $res = preg_match($rule, $money);
//        if($res == 0){
//            return json(['code'=>100,'msg'=>'提现金额格式有误请核对！']);
//        }
//        return json(['code'=>200,'msg'=>'申请成功！']);
//    }

    // 发送模板消息
    public function sendTemplateMessage()
    {

        $form_id = input('form_id');
        $page = input('page','');
        $oid = input('oid','');//订单id
        $state = input('state','');//0 未支付通知 1：支付成功；2：订单取消
        if(empty($form_id)){
            return json(['code'=>100,'msg'=>'参数错误！']);
        }

        if($state ==''){
            return;
        }
        if($oid ==''){
            return;
        }

        $order = \db('order')->where('id',$oid)->find();
        $datacont=[];
        $template_id = '';
        if($state == '0'){
            $template_id = 'DWjA2E7lxooEFf9Zfmt4r75Osjt4hfCKYzTH1tYUIgE';
            $datacont = [
                //  订单号、商品名称、订单价格、订单状态
                $order['sn'],'购买商品',$order['amount']
            ];
        }else if($state == '1'){
            $template_id = 'RRvzaPpQ8THxghxqrN9Pzs8PzpYdf1bLbOt4ilg9P30';
            $datacont = [
                //  单号、金额、下单时间
                $order['sn'],$order['amount'], date("Y-m-d H:i:s",$order['add_time'])
            ];
        }else if($state == '2'){
            $datacont = [
                //  取消原因、下单时间、订单金额、订单编号
                '',date("Y-m-d H:i:s",$order['add_time']),$order['amount'],$order['sn']
            ];
            $template_id = 'aetIDzUqKGJdURRiwOvnvRb_9L6iwPwu67yBOwYWJIs';
        }

        $Wechat = new WechatModel();
        $token = $Wechat ->getToken();

        $userId = parent::getUserId();
        $userInfo = Db::name('member')
            ->field('openid')
            ->where(['id'=>$userId])->find();

        $params = [
            'openid' => $userInfo['openid'],
            'access_token' =>$token['access_token'],
            'page' => $page,
            'form_id' => $form_id,
            'data'=>$datacont
        ];

        # 模板关键字
        $data = [];
        foreach ($params['data'] as $k => $v) {
            $data['keyword'.($k+1)] = ['value'=>$v];
        }
        $postData = [
            'touser'        =>  $params['openid'],
            'template_id'   =>  $template_id,
            'page'          =>  $params['page'],
            'form_id'       =>  $params['form_id'],
            'data'          =>  $data
        ];

        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$params['access_token'];

        $return=json_encode($postData);
        $Message = new Message();
        $result = strip_tags($Message->request_post($url,$return));
        if($result)
        {
            return 'true';
        }else{
            return 'faile';
        }
    }

    // 得到 粉丝 列表
    public function  getFansList(){
        $data = $this ->request ->param();
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $map = [];
        $map[] = ['a.one_member_id','eq',self::getUserId()];
        $map[] = ['b.status','eq',1];
        $list = Db::name('retail_user')
            ->alias('a')
            ->join('member b','a.member_id=b.id')
            ->where($map)
            ->page($page)
            ->field('b.id,b.nickname,b.mobile as phone,b.pic,b.regtime as createtime')
            ->select();
        if( count($list) <= 0 ){
            return json(['code'=>200,'msg'=>'查询成功！','data'=>[]]);   //粉丝为空
        }
        foreach ($list as $k=>$v){
            $list[$k]['createtime'] = date('Y-m-d',$v['createtime']);
            $list[$k]['phone'] = self::yc_phone($v['phone']);
            $list[$k]['istop'] = 0;
        }
        return json(['code'=>200,'msg'=>'查询成功！','data'=>$list]);
    }

    //自定义函数手机号隐藏中间四位
    function yc_phone($str){
        $str=$str;
        $resstr=substr_replace($str,'****',3,4);
        return $resstr;
    }

    // 分享商品
    public function shareGoods(){

        $scene = input('scene','');
        $page = input('page');
        $pic = input('pic');
        $title = input('title');
        $price = input('price');
        $gid = input('goods_id');
        if(empty($scene)){
            $scene = '123';
        }

        if(empty($pic) || empty($pic) || empty($title)|| empty($price)|| empty($gid)){
            return json(['code'=>100,'msg'=>'参数有误！']);
        }
        // 生成 小程序 二维码
        $Wechat = new WechatModel();
        $token = $Wechat ->getToken();

//        $userId = parent::getUserId();
//        $userInfo = Db::name('member')
//            ->field('openid')
//            ->where(['id'=>$userId])->find();

        $postData = [
            'scene' => $scene,
//            'page' => '',
            'width'=>430,
            'auto_color'=>false,
            'is_hyaline'=>false,
        ];

//        https://blog.csdn.net/fl521fl/article/details/80481479
        $return=json_encode($postData,true);
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$token['access_token'];

        $Message = new Message();
        $result = $Message->request_post($url,$return);

//        $data='image/png;base64,'.base64_encode($result);

        $strtand = $this->str_rand(4).time();
        $strtand_name = $strtand.".jpg";

        $erfilename = "qrcode/";

        //保存图片在本地
        $res = file_put_contents($erfilename.'er'.$strtand_name, $result);
        //  将获取到的二维码图片流保存成图片文件  
        if($res===false)   return json(['code'=>100,'msg'=>'图片生成错误！']);;
        // 二维码 图片地址
        $erimgurl = "https://www.ddxm661.com/".$erfilename.'er'.$strtand_name;
//        $erimgurl = "http://testmd.ddxm661.com/".$erfilename.'er'.$strtand_name;

        $strtand = $this->str_rand(4).time();
        $strtand_name = $strtand.".jpg";
        $erfilename = "qrcode/";

        $rcode =  new QrcodeGoods();

        $picimg_name = $erfilename.'goods'.$strtand_name;

        $re =  $rcode->createShareGoods($price,$pic,$title,$erimgurl,$picimg_name);//  创建 分享图片
        if($re == false){
            return json(['code'=>100,'msg'=>'图片生成错误！']);
        }else{
            $dd=[
                'price'=>0,
//                'pic'=>"http://testmd.ddxm661.com/".$picimg_name,
                'pic'=>"https://www.ddxm661.com/".$picimg_name,
            ];
            return json(['code'=>200,'msg'=>'生成成功！','data'=>$dd]);
        }
    }

    //生成网址的二维码 返回图片地址
    function Qrcode3($token, $url, $size = 8){
        $md5 = md5($token);
        $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
        $patch = 'qrcode/' . $dir;
        if (!file_exists($patch)){
            mkdir($patch, 0755, true);
        }
        $file = 'qrcode/' . $dir . $md5 . '.png';
        $fileName =  $file;
        if (!file_exists($fileName)) {

            $level = 'L';
            $data = $url;
            QRcode::png($data, $fileName, $level, $size, 2, true);
        }
        return $file;
    }


    //分享好友---分享好友图片
    public function shareFriend(){

        $uid = input('uid');
        if (empty($uid)) {
            return json(['code'=>-1,'msg'=>'参与错误！','data'=>'']);
        }

//            $strtand = $this->str_rand(4) . time();
        $erimgurl = "https://www.ddxm661.com/h5";
        //生成 二维码图片
        $filename = $this->Qrcode3(time(),$erimgurl,8);
        return json(['code' => 200, 'msg' => '生成成功！', 'data' => 'https://www.ddxm661.com/'.$filename]);

    }

    //分销中心
    public function getUserRetail(){
        $memberId = parent::getUserId();
        //团队
        $map = [];
        $map[] = ['one_member_id|two_member_id','eq',$memberId];
        $memberList = Db::name('retail_user') ->where($map)->field('member_id,create_time')->select();
        $today_member_array = [];       //新增今日客户
        $all_member_array = [];         //累积客户
        if( count($memberList) > 0 ){
            foreach ( $memberList as $k=>$v ) {
                if( !in_array($v['member_id'],$all_member_array) ){
                    array_push($all_member_array,$v['member_id']);
                }
                if( !in_array($v['member_id'],$today_member_array) &&
                    $v['create_time'] >= strtotime(date('Y-m-d').' 00:00:00') &&
                    $v['create_time'] <= strtotime(date('Y-m-d').' 23:59:59' ) ){
                    array_push($today_member_array,$v['member_id']);
                }
            }
        }

        //个人
        $map = [];
        $map[] = ['member_id','eq',$memberId];
        $orderList = Db::name('order_retail') ->where($map) ->field('id,price,amount,status,create_time')->select();
        $cumulative_order = count($orderList);      //累计订单
        $accumulated_income = 0;    //累积收益
        $balance_accounts = 0;      //待结算收益
        $today_estimated_revenue = 0;   //今日预估收益
        $today_order = 0;           //今日订单
        $sales_volume = 0;          //累积销售金额
        if( count( $orderList ) > 0 ){
            foreach ( $orderList as $k=>$v ) {
                if( $v['status'] == 1 ){    //已结算的累积收益
                    $accumulated_income += $v['price'];
                }
                if ( $v['status'] == 0 ) {  //待结算收益
                    $balance_accounts += $v['price'];
                }
                if( $v['status'] == 0 &&
                    $v['create_time'] >= strtotime(date('Y-m-d').' 00:00:00') &&
                    $v['create_time'] <= strtotime(date('Y-m-d').' 23:59:59' ) )
                {
                    //今日预估收益
                    $today_estimated_revenue += $v['price'];
                }
                $sales_volume += $v['amount'];
                if( $v['create_time'] >= strtotime(date('Y-m-d').' 00:00:00') &&
                    $v['create_time'] <= strtotime(date('Y-m-d').' 23:59:59' ) ){
                    $today_order += 1;      //今日订单
                }
            }
        }
        //已提现金额
        $map = [];
        $map[] = ['member_id','eq',$memberId];
        $map[] = ['status','eq',1];
        $use_amount = Db::name('retail_cash_out') ->where($map)->sum('price');

        $res = [];
        $res = [
            'accumulated_income' =>$accumulated_income,      //累积收益
            'sales_volume' =>$sales_volume,      //团队累积销售额
            'cumulative_order' =>$cumulative_order,      //累计订单
            'cumulative_member' =>count($all_member_array),      //累计客户
            'balance_accounts' =>$balance_accounts,      //待结算收益
            'can_use_amount' =>Db::name('member_money') ->where('member_id',$memberId)->value('retail_money'),      //可提现金额
            'use_amount' =>$use_amount,      //已提现金额
            'today_estimated_revenue' =>$today_estimated_revenue,      //今日预估收益
            'today_order' =>$today_order,      //今日订单
            'today_member' =>count($today_member_array),      //今日新增客户
            'fans_num'  =>Db::name('retail_fans')
                ->where('member_id',self::getUserId())
                ->where('status',1)
                ->count()	//粉丝数量
        ];
        return json(['code'=>200,'msg'=>'获取成功','data'=>$res]);
    }

    /**
     * 生成随机数
     * @param int $length
     * @param string $char
     * @return bool|string
     */
    function str_rand($length = 32, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        if(!is_int($length) || $length < 0) {
            return false;
        }

        $string = '';
        for($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }

        return $string;
    }


    function getrandstr(){
        $str='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $rands= substr($randStr,0,4);//substr(string,start,length);返回字符串的一部分
        return $rands;
    }

    /**
     * 分享好友--图片 成为 分销员
     * @param string $fileName
     * @return bool|string
     */
    function shareRetailFriend()
    {
        $token = $this ->request->header('XX-Token');
        $userTokenInfo = Db::name('member_token')->where('token',$token)->find();
        $userId = $userTokenInfo['user_id'];
        $userNickname = \db('member')->where('id',$userId)->find();
        if( !empty($userNickname['retail_img']) ){
            return $userNickname['retail_img'];
        }
        $erurl = 'https://www.ddxm661.com/h5/#/pages2/user/distribution/distributor-details?user_id='.$userId;
        //生成 二维码图片
        $filename = $this->Qrcode3(time().$this->getrandstr(),$erurl,8);
        $erwm = 'https://www.ddxm661.com/'.$filename;
        $md5 = md5(time().$this->getrandstr());
        $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
        $patch = 'qrcode/' . $dir;
        if (!file_exists($patch)){
            mkdir($patch, 0755, true);
        }
        $picimg_name = 'qrcode/' . $dir . $md5 . '.png';
        $rcode =  new QrcodeGoods();
        $re =  $rcode->shareRetailFriend($erwm,$picimg_name);
        //上传到七牛云
        $imgurl = '/www/wwwroot/ddxm/public/'.$picimg_name;	//图片的绝对地址
        $res = (new UploadModel()) ->upload1($imgurl);
        if( $res['code'] == 0 ){
            //如果上传到七牛云成功
            unlink($imgurl);    //删除本地图片
            Db::name('member')->where('id',$userId)->setField('retail_img',config('QINIU_URL').$res['data']['key']);
            return config('QINIU_URL').$res['data']['key'];
        }
        //上传失败直接返回本地地址
        $erwm222 = 'https:www.//ddxm661.com/'.$picimg_name;
        Db::name('member')->where('id',$userId)->setField('retail_img',$erwm222);
        return $erwm222;
    }

}