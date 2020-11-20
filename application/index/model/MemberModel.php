<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\index\model;

use think\Model;
use think\Cache;
use think\Db;

class MemberModel extends BaseModel
{
	public $connect = [
    // 数据库类型
    'type'     => 'mysql',
    // 服务器地址
    'hostname'    => '120.79.5.57',
    // 数据库名
    'database' => 'ddxx',
    // 用户名
    'username' => 'root',
    // 密码
    'password' => 'ddxm2019',
    // 端口
    'hostport' => '3306',
    // 数据库编码默认采用utf8
    'charset'  => 'utf8mb4',
    // 数据库表前缀
    'prefix'   => 'tf_',
    "authcode" => 'OV4w80Ndr23wt4yW1j',
    //#COOKIE_PREFIX#
    ];

    protected $table = 'tf_member';

    /**

    */
    public function getRegtimeAttr($val){
        return date('Y-m-d H:i',$val);
    }


    /*
        查找会员
    */
    public function search_vip($where){
    	$result = $this
    			->alias('a')
    			->where($where)
    			->join('tf_level b','a.level_id=b.id')
    			->field('a.id,a.no,a.mobile,a.shop_code,a.realname,a.level_id,a.openid,a.nickname,a.money,a.amount,a.regtime,b.level_name')
    			->order('a.id desc');

    	return $result;
    }

    /**
        添加、编辑会员
        $data['id']存在表示编辑反正为添加
    */
    public function saveVip($data){
        if ( empty($data['id']) ) {
            //添加会员
            $info = $this ->insert($data);
            if( $info ){
                $result = array('code'=>'1','msg'=>'添加成功','data'=>'');
            }else{
                $result = array('code'=>'0','msg'=>'添加失败','data'=>'');
            }
        }else{
            //修改会员
            unset($data['id']);
            $info = $this ->where('id',$data['id'])->save($data);
            if( $info ){
                $result = array('code'=>'1','msg'=>'修改成功','data'=>'');
            }else{
                $result = array('code'=>'0','msg'=>'修改失败','data'=>'');
            }
        }
        return $result;
    }



    /**
    查询门店编号
    */
    public function getShopcode($shop_id){
        $db = Db::connect($this->connect);
        return $db->name('shop')->where('id',$shop_id)->value('code');
    }

}
