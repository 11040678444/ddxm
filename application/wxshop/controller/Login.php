<?php
namespace app\wxshop\controller;

use app\wxshop\wxpay\JsApiPay;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;
use app\wxshop\controller\Base;
use app\common\model\MessageModel;
use app\common\model\WechatModel;
use app\extend\WxGetUserInfo\GetInfo;
use app\common\model\WxPayModel;
/**
商城登陆
 */
class Login extends Base
{
    /***
     * 获取验证码
     * @return \think\response\Json
     * @throws \AlibabaCloud\Client\Exception\ClientException
     * @throws \AlibabaCloud\Client\Exception\ServerException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCode(){
        $data = $this ->request ->post();
        if( empty($data['mobile']) ){
            return json(['code'=>-3,'msg'=>'请输入手机号']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule,$data['mobile']);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        $Message = new MessageModel();
        $res = $Message ->sendMessage($data['mobile']);
        return json($res);
    }

    /***
     * 手机号快速登陆与注册
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function mobileLogin(){
        $data = $this ->request ->param();
        if( empty($data['mobile']) || empty($data['code']) ){
            return json(['code'=>100,'msg'=>'请输入手机号或验证码']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule,$data['mobile']);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        $where[] = ['mobile','eq',$data['mobile']];
        //验证码
        //测试专用
        if( $data['code'] != '1234' ){
            $verification = Db::name('verification_code')->where($where)->order('send_time desc')->find();
            if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                return json(array('code'=>'-6','msg'=>'验证码错误或已过期','data'=>''));
            }
        }
        //查询会员是否存在
        $member = Db::name('member')->where($where)->find();
        if( !$member ){
            //无此用户,添加会员
            $member1['level_id'] = 1;  //默认为普通会员
            $member1['regtime'] = time();
            $member1['mobile'] = $data['mobile'];
            $member1['nickname'] = $data['mobile'];
            $member1['source'] = 1;
            //余额数据
            $moneyData = array(
                'mobile'  =>$data['mobile'],
                'money'   =>'0.00',
            );
            // 启动事务
            Db::startTrans();
            try {
                $result = Db::name('member') ->insertGetId($member1);
                $member['id'] = $result;
                $moneyData['member_id'] = $result;
                Db::name('member_money')->insert($moneyData);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                json(['code'=>100,'msg'=>$e->getMessage()]);
            }
        }
        if( $member['status'] != 1 ){
            return json(array('code'=>6,'msg'=>'用户已被禁用,请联系管理员','data'=>''));
        }
        //登陆
        $user_token = $this ->member_token($member['id']);
        $resultUser = array('code'=>200,'msg'=>'登陆成功','data'=>['token'=>$user_token]);
        return json($resultUser);
    }

    /***
     * 微信登陆
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function weChat(){
        $data = $this ->request->post();
        if( empty($data['js_code']) ){
            return json(['code'=>100,'msg'=>'缺少参数js_code']);
        }
        $Wechat = new WechatModel();
        $openid = $Wechat ->getOpendId($data['js_code']);
        if( !isset($openid['openid']) && !isset($openid['session_key']) ){
            return json(['code'=>100,'msg'=>'用户授权失败']);
        }
        //判断是否存在用户
        $where[] = ['smallOpenid','eq',$openid['openid']];
        $member = Db::name('member')->where($where)->find();
        if( !$member ){
            //获取微信用户信息
            $GetInfo = new GetInfo($openid['session_key'],$data['iv'],$data['encryptedData']);
            $userInfo = $GetInfo->render();
            $userInfo = json_decode($userInfo,true);
            if( !is_array($userInfo) ){
                return json(['code'=>100,'msg'=>$userInfo]);
            }
            return json(['code'=>400,'msg'=>'请绑定手机号','data'=>$userInfo]);
        }
        $user_token = $this ->member_token($member['id']);
        $resultUser = array('code'=>200,'msg'=>'登陆成功','data'=>['token'=>$user_token]);
        return json($resultUser);
    }

    /***
     * 微信绑定手机号
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function bd_mobile(){
        $data = $this ->request->post();
        if( empty($data['mobile']) || empty($data['code']) ){
            return json(['code'=>100,'msg'=>'请输入手机号或验证码']);
        }
        if( empty($data['openid']) ){
            return json(['code'=>100,'msg'=>'请传入参数openid']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule,$data['mobile']);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        $where[] = ['mobile','eq',$data['mobile']];
        //验证码
        //测试专用
        if( $data['code'] != '1234' ){
            $verification = Db::name('verification_code')->where($where)->order('send_time desc')->find();
            if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                return json(array('code'=>'-6','msg'=>'验证码错误或已过期','data'=>''));
            }
        }
        $member = Db::name('member')->where($where)->find();
        if( !$member || ($member['smallOpenid'] == '') ){
            //无此用户,添加会员
            $member1['level_id'] = 1;  //默认为普通会员
            $member1['regtime'] = time();
            $member1['mobile'] = $data['mobile'];
            $member1['nickname'] = $data['nickName'];
            $member1['smallOpenid'] = $data['openid'];
            $member1['pic'] = $data['avatarUrl'];
            $member1['source'] = 1;
            //余额数据
            $moneyData = array(
                'mobile'  =>$data['mobile'],
                'money'   =>'0.00',
            );
            // 启动事务
            Db::startTrans();
            try {
                if( !$member ){
                    $result = Db::name('member') ->insertGetId($member1);
                    $member['id'] = $result;
                    $moneyData['member_id'] = $result;
                    Db::name('member_money')->insert($moneyData);
                }else{
                    Db::name('member') ->where('id',$member['id'])->update($member1);
                }
                //判断是否为被邀请的
                if( !empty($data['user_id']) ){
                    $newmemberInfo = Db::name('member')->where('id',$data['user_id'])->find();
                    if( $newmemberInfo && ($newmemberInfo['status'] == 1) ){
                        //添加到ddxm_retail_user、先查询分享人的上一级
                        $retail = Db::name('retail_user') ->where('member_id',$data['user_id'])->find();
                        if( $retail ){
                            $two_member_id = $retail['one_member_id'];
                        }else{
                            $two_member_id = 0;
                        }
                        $arr = [];
                        $arr = [
                            'member_id' =>$member['id'],
                            'one_member_id' =>$data['user_id'],
                            'two_member_id' =>$two_member_id,
                            'create_time' =>time(),
                            'update_time' =>time(),
                        ];
                        Db::name('retail_user') ->insert($arr);
                    }
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>100,'msg'=>$e->getMessage()]);
            }
        }else{
            if( $member['status'] != 1 ){
                return json(array('code'=>6,'msg'=>'用户已被禁用,请联系管理员','data'=>''));
            }
            $openid = Db::name('member')->where('smallOpenid',$data['openid'])->find();
            if( empty($member['openid']) && !$openid ){
                Db::name('member')->where($where)->update(['smallOpenid'=>$data['openid'],'pic'=>$data['avatarUrl']]);
            }else{
                return json(['code'=>100,'msg'=>'该手机号已绑定微信,请直接登陆']);
            }
        }
        $user_token = $this ->member_token($member['id']);
        $resultUser = array('code'=>200,'msg'=>'登陆成功','data'=>['token'=>$user_token]);
        return json($resultUser);
    }

    /***
     * 分销员分销图片第一步：
     * 根据code获取openid,判断是否存在此用户
     */
    public function judgeOpenid(){
        $code = $this ->request ->param('code');    //授权码
        $tools = new JsApiPay();
        $openid = $tools->GetOpenidFromMp2($code);
        if( !isset($openid['openid']) ){
            return json(['code'=>100,'msg'=>'服务器繁忙','data'=>'获取openid失败']);
        }
        $openId = $openid['openid'];

        //判断是否存在此用户
        $user = Db::name('member') ->where('openid',$openId)->find();
        $res = [];  //返回的数据
        if( $user ){
            //如果找到，则判断是否为分销商
            if( $user['retail'] == 1 ){
                //是分销员，模拟登陆返回token
//                $user_token = $this ->member_token($user['id']);
                $res['is_retail'] = 1;  //是分销员
                $res['token'] = '';
            }else{
                $res['is_retail'] = 0;  //不是分销员
                $res['token'] = '';
            }
            $res['openid'] = $user['openid'];
            $res['is_reg'] = 1; //已经注册
        }else{
            //未找到
            $res['is_retail'] = 0;  //不是分销员
            $res['is_reg'] = 0; //未注册
            $res['openid'] = $openId;
            $res['token'] = '';
        }
        return json(['code'=>$res['is_retail']==1?333:200,'msg'=>'获取成功','data'=>$res]);
    }

    /***
     * openid,user_id,mobile,code
     * 填写了资料之后,提交
     * 返回关注状态
     */
    public function retailMessage(){
        $data = $this ->request ->param();
        if( empty($data['openid']) ){
            return json(['code'=>100,'msg'=>'系统繁忙','data'=>'缺少openid']);
        }
        //获取是否关注状态
        $userinfo = \controller('Customer')->getUserinfo($data['openid']);
        // 0：未关注  1： 已经关注
        $subscribe = $userinfo['subscribe'];
        //判断是否有上级分销商
        if( !empty($data['user_id']) ){
            $user = Db::name('member')->where('id',$data['user_id'])->field('status,openid,retail,shop_code') ->find();
            if( (!$user) || ($user['status']!= 1) ){
                return json(['code'=>100,'msg'=>'推荐人信息错误','data'=>['subscribe'=>$subscribe]]);
            }
            if( $user['retail'] != 1 ){
                return json(['code'=>100,'msg'=>'推荐人不是分销商','data'=>['subscribe'=>$subscribe]]);
            }
            $one_member_id = $data['user_id'];  //一级分销员
            $shop_code = $user['shop_code'];
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
        }else{
            $one_member_id = 0;     //1级推荐人id
            $two_member_id = 0;     //二级推荐人id
            $shop_code = 'A00000';
        }
        //逻辑处理
        $member = Db::name('member')->where('openid',$data['openid'])->find();  //查询用户
        if( $member ){
            if( $member['retail'] == 1 ){
                return json(['code'=>100,'msg'=>'此用户已经成为分销员啦！','data'=>['subscribe'=>$subscribe]]);
            }
            //表示已经注册直接成为分销员
            $retail_user_data = [];
            $retail_user_data = [
                'member_id' =>$member['id'],
                'one_member_id' =>$one_member_id,
                'two_member_id' =>$two_member_id,
                'create_time' =>time(),
                'update_time' =>time(),
                'mobile' =>$member['mobile'],
                'name' =>!empty($member['wechat_nickname'])?$member['wechat_nickname']:(!empty($member['nickname'])?$member['nickname']:$member['mobile']),
            ];
            // 启动事务
            Db::startTrans();
            try {
                Db::name('retail_user') ->insert($retail_user_data);
                Db::name('member')->where('id',$member['id'])->setField('retail',1);
                //将这个会员的粉丝过期
                Db::name('retail_fans') ->where('fans_id',$member['id'])->update(['status'=>2,'expire_time'=>time()]);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>100,'msg'=>'服务器繁忙,请稍后重试','data'=>$e->getMessage()]);
            }
        }else{
            //表示要注册
            if( empty($data['mobile']) || empty($data['code']) ){
                return json(['code'=>100,'msg'=>'请输入手机号或验证码！','data'=>['subscribe'=>$subscribe]]);
            }
            $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
            if (!preg_match($rule,$data['mobile'])) {
                return json(['code'=>100,'msg'=>'手机号格式错误']);
            }
            //验证码 ：1130测试专用
            if( $data['code'] != '1130' ){
                $verification = Db::name('verification_code')->where('mobile',$data['mobile'])->order('send_time desc')->find();
                if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                    return json(array('code'=>-6,'msg'=>'验证码错误或已过期','data'=>''));
                }
            }
            // 启动事务
            Db::startTrans();
            try {
                //openid已经不存在了，再判断手机号是否存在
                $mobile = Db::name('member')->where('mobile',$data['mobile']) ->find();
                if( $mobile ){
                    if( empty($mobile['openid']) ){
                        //没有openid
                        //注册为分销员
                        $retail_user_data = [
                            'member_id' =>$mobile['id'],
                            'one_member_id' =>$one_member_id,
                            'two_member_id' =>$two_member_id,
                            'create_time' =>time(),
                            'update_time' =>time(),
                            'mobile' =>$data['mobile'],
                            'name' =>$data['nickname'],
                        ];
                        Db::name('member')->where('id',$mobile['id'])->update(['retail'=>1,'openid'=>$data['openid']]);
                        Db::name('retail_user') ->insert($retail_user_data);
                        Db::name('retail_fans') ->where('fans_id',$mobile['id'])->update(['status'=>2,'expire_time'=>time()]);
                    }else{
                        //有其他openid
                        return json(array('code'=>-6,'msg'=>'此手机号已经注册过啦','data'=>''));
                    }
                }else{
                    //注册为会员
                    $member_data = [];
                    $member_data = [
                        'mobile'    =>$data['mobile'],
                        'shop_code'    =>$shop_code,
                        'level_id'    =>1,
                        'openid'    =>$data['openid'],
                        'pic'    =>'http://picture.ddxm661.com/70c53e3c43c6722f4c7b90f0904f14c.png',
                        'nickname'    =>$data['mobile'],
                        'wechat_nickname'    =>$data['mobile'],
                        'regtime'    =>time(),
                        'source'    =>1,
                        'retail'    =>1
                    ];
                    $memberId = Db::name('member') ->insertGetId($member_data);
                    //添加初始金额
                    $member_money_data = [];
                    $member_money_data = [
                        'member_id' =>$memberId,
                        'mobile' =>$data['mobile'],
                        'money' =>0,
                    ];
                    Db::name('member_money') ->insert($member_money_data);
                    //注册为分销员
                    $retail_user_data = [
                        'member_id' =>$memberId,
                        'one_member_id' =>$one_member_id,
                        'two_member_id' =>$two_member_id,
                        'create_time' =>time(),
                        'update_time' =>time(),
                        'mobile' =>$data['mobile'],
                        'name' =>$data['mobile'],
                    ];
                    Db::name('retail_user') ->insert($retail_user_data);
                    if( !empty($data['user_id']) ){
                        //是分享过来的、添加成为过期粉丝
                        $fans_data = [];
                        $fans_data = [
                            'member_id' =>$data['user_id'],
                            'fans_id' =>$memberId,
                            'status' =>2,
                            'create_time' =>time(),
                            'expire_time' =>time(),
                        ];
                        Db::name('retail_fans') ->insert($fans_data);
                    }
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>100,'msg'=>'服务器繁忙,请稍后重试','data'=>$e->getMessage()]);
            }
        }
        //如果有上级、给上级发客户消息
        if( !empty($data['user_id']) ){
            //发送客户消息
            $name = '';
            if( $member ){
                $name = !empty($member['wechat_nickname'])?$member['wechat_nickname']:$member['nickname'];
            }else{
                $name = substr($data['mobile'],-4);
            }
            $content = '亲爱的客官,恭喜你啦,'.$name.'成为你的新分销员,共同开启美好生活';
            $post_data = '{"touser":"'.$user['openid'].'","msgtype":"text","text":{"content":"'.$content.'"}}';
            (new WxPayModel())->sed_custom_message($post_data);     //给上级发消息
        }
        return json(['code'=>200,'msg'=>'成为分销员成功','data'=>['subscribe'=>$subscribe]]);
    }

}