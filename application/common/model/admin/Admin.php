<?php
namespace app\common\model\admin;

use think\Model;
use think\Db;
/**
进销存用户模型
 */
class Admin extends Model
{
    /***
     * 账号密码快速登陆
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function login($data){
        $identifier = $data['username'];
        $password = $data['password'];
        if (empty($identifier)) {
            return false;
        }
        $map = array();
        //判断是uid还是用户名
        if (is_int($identifier)) {
            $map['userid'] = $identifier;
        } else {
            $map['username'] = $identifier;
        }
//        $userInfo = $this->alias('a')->field('a.*,s.level')->join('shop s','a.shop_id = s.id')->where($map)->find();
        $userInfo = $this->where($map)->find();
        //dump($userInfo);die;
        if (empty($userInfo)) {
            return false;
        }
        //密码验证
        if (!empty($password) && self::encrypt_password($password, $userInfo['encrypt']) != $userInfo['password']) {
            return false;
        }

        //生成token
        $userInfo['token'] = self::admin_token($userInfo['userid']);

        //测试临时换成下用户信息
        cache($userInfo['token'],$userInfo);
        return_succ($userInfo,'ok');
        //return ['token'=>$userInfo];
    }

    /***
     * 获取用户信息
     */
    public function getUserInfo($userId){
        $identifier = $userId;
        if (empty($identifier)) {
            return false;
        }
        $map = array();
        //判断是uid还是用户名
        if (is_int($identifier)) {
            $map['userid'] = $identifier;
        } else {
            $map['username'] = $identifier;
        }
        $userInfo = $this->where($map)->find();
        if (empty($userInfo)) {
            return false;
        }

        return $userInfo;
    }

    /**
     * 对用户的密码进行加密
     * @param $password
     * @param $encrypt //传入加密串，在修改密码时做认证
     * @return array/password
     */
    function encrypt_password($password, $encrypt = '')
    {
        $pwd = array();
        $pwd['encrypt'] = $encrypt ? $encrypt : genRandomString();
        $pwd['password'] = md5(trim($password) . $pwd['encrypt']);
        return $encrypt ? $pwd['password'] : $pwd;
    }

    /***
     * 生成或刷新token
     * @param $userId
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function admin_token($userId){
        $where['user_id'] = $userId;
        $findUserToken = Db::name('admin_token')->where($where)->find();
        $currentTime    = time();
        $expireTime     = $currentTime + 24 * 3600 * 180;
        $token          = md5(uniqid()) . md5(uniqid());
        if (empty($findUserToken)) {
            $userData = [
                'token'       => $token,
                'user_id'     => $userId,
                'expire_time' => $expireTime,
                'create_time' => $currentTime
            ];
            Db::name('admin_token') ->insert($userData);
        } else {
            $userData = [
                'token'       => $token,
                'expire_time' => $expireTime,
                'create_time' => $currentTime
            ];
            Db::name('admin_token')->where('user_id',$userId) ->update($userData);
        }
        return $token;
    }
}