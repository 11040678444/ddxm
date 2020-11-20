<?php

namespace app\wxshop\model\comment;
use think\Model;
use think\Db;

/***
 * 评论模型
 */
class CommentModel extends Model
{
    protected $table = 'ddxm_comment';

    /***
     * 获取商品评论列表
     */
    public function comment_list(){
        return $this->table($this->table)
        ->field("member_id,add_time,comment,pic,level,member_id as nickname,member_id as m_pic,specs");
        
    }
    public function getPicAttr($val){
        if($val == '' ){
            return [];
        }
        $val = explode(',',$val);
        $info = [];
        foreach ($val as $k=>$v){
            array_push($info,$v);
        }
        return $info;
    }
    public function getAddTimeAttr($val){
        return date("Y-m-d");
    }
    public function getNicknameAttr($val){
        if($val==0){
            return "匿名用户";
        }
        $meber =  db::name("member")->where("id",intval($val))->field("nickname,wechat_nickname")->find();
        return !empty($meber['wechat_nickname'])?$meber['wechat_nickname']:$meber['nickname'];
    }
    public function getMPicAttr($val){
        $pic = db::name("member")->where("id",intval($val))->value("pic");
        if(!$pic){
            return "http://picture.ddxm661.com/70c53e3c43c6722f4c7b90f0904f14c.png";
        }
        return $pic;
    }
}