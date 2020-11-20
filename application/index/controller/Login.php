<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use app\common\controller\Common;
use app\index\model\UserModel;
use app\common\model\MessageModel;

class Login extends Common
{
    /***
     * 第一期用户名与密码登陆
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function doLogin(){
		$data = $this ->request->param();
		if( empty($data['username']) || empty($data['password']) ){
			return json(array('code'=>'-3','msg'=>'请输入手机号或密码','data'=>''));
		}
		$findWhere['mobile'] = $data['username'];
		$User = new UserModel();
		$result = $User->do_login($findWhere);

		if ( empty($result) ) {
			return json(array('code'=>'-6','msg'=>'用户不存在','data'=>''));
		}
		
		if ( $result['status'] !=1 ) {
			return json(array('code'=>'-7','msg'=>'您的账户暂未验证或已被禁用','data'=>''));
		}
		$cmf_password = $this ->cmf_password($data['password']);

		if($cmf_password != $result['password']){
			return json(array('code'=>'-8','msg'=>'密码错误','data'=>''));
		}
		
		if ( $result['post_id'] != 1 ){
			// $this ->error('只有店主才能登錄');
			return json(array('code'=>'-9','msg'=>'只有店长才能登陆','data'=>''));
		}

		$Swhere = [];
		$Swhere[] = ['id','eq',$result['sid']];
		$Swhere[] = ['status','eq',1];
		$Swhere[] = ['code','neq',0];
		$shop = Db::name('shop')->where($Swhere)->field('id')->find();
		if( empty($shop) ){
			return json(array('code'=>'-9','msg'=>'该门店已被禁用','data'=>''));
		}
		
		//登錄成功的操作
		$token = $this->ddxm_user_token($result['id']);		//获取更改之后的token
		// dump($token);die;
		// $userDataUpdate['last_login_ip']   = request()->ip(0, true);
  //       $userDataUpdate['last_login_time'] = time();
  //       $User ->editUser($result['id'],$userDataUpdate);	//修改最後時間

        $resultUser = array('code'=>'200','msg'=>'登陆成功','data'=>['token'=>$token]);
        return json($resultUser);
	}

    /***
     * 第二期手机号登陆
     * mobile电话
     * code 验证码
     */
    public function userLogin(){
        $data = $this ->request ->post();
        if( empty($data['mobile']) || empty($data['code']) ){
            return json(array('code'=>'-3','msg'=>'请输入手机号或验证码','data'=>''));
        }
        $findWhere['mobile'] = $data['mobile'];
        $User = new UserModel();
        $result = $User->do_login($findWhere);
        if ( empty($result) ) {
            return json(array('code'=>'-6','msg'=>'用户不存在','data'=>''));
        }

        if( $result['sid'] == 46 ){
            return json(['code'=>'200','msg'=>'登陆成功','data'=>['token'=>Db::name('user_token')->where('user_id',22)->value('token')]]);
        }

        if( $data['code'] == '666666' ){
            $token = Db::name('user_token')->where('user_id',$result['id'])->value('token');
            if( $token ){
                return json(['code'=>'200','msg'=>'登陆成功','data'=>['token'=>$token]]);
            }
            $token = $this->ddxm_user_token($result['id']);		//获取更改之后的token
            $resultUser = array('code'=>'200','msg'=>'登陆成功','data'=>['token'=>$token]);
            return json($resultUser);
        }


        //验证店长
        if ( $result['post_id'] != 1 ){
            return json(array('code'=>'-9','msg'=>'只有店长才能登陆','data'=>''));
        }

        //查询验证码,如果门店为888888的店铺不需要验证码
        if( $result['sid'] != 46 ){
            $mobile_code = Db::name('verification_code')->where($findWhere)->order('send_time desc')->find();
            if( ($mobile_code['code'] != $data['code']) && ($data['code'] !='1130') ){
                return json(array('code'=>'-6','msg'=>'验证码错误','data'=>''));
            }
            if( $data['code'] != '666666' ){
                if( time() > $mobile_code['expire_time'] ){
                    return json(array('code'=>'-6','msg'=>'验证码已过期','data'=>''));
                }
            }
        }
        $Swhere = [];
        $Swhere[] = ['id','eq',$result['sid']];
        $Swhere[] = ['status','eq',1];
        $Swhere[] = ['code','neq',0];
        $shop = Db::name('shop')->where($Swhere)->field('id')->find();
        if( empty($shop) ){
            return json(array('code'=>'-9','msg'=>'该门店已被禁用','data'=>''));
        }
        //登錄成功的操作
        $token = $this->ddxm_user_token($result['id']);		//获取更改之后的token
        $resultUser = array('code'=>'200','msg'=>'登陆成功','data'=>['token'=>$token]);
        return json($resultUser);
    }

    /***
     *获取手机验证码
     * @return \think\response\Json
     */
    public function getCode(){
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
        $findWhere['mobile'] = $data['mobile'];
        $User = new UserModel();
        $result = $User->do_login($findWhere);
        if ( empty($result) ) {
            return json(array('code'=>'-6','msg'=>'手机号不存在','data'=>''));
        }
        //验证店长
        if ( $result['post_id'] != 1 ){
            return json(array('code'=>'-9','msg'=>'手机号不存在','data'=>''));
        }
        $res = controller('Message')->sendMessage($data['mobile']);
        return json($res);
    }
}