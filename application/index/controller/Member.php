<?php
/**
	会员列表

*/
namespace app\index\controller;

use app\index\model\Member\MemberModel;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;

class Member extends Base
{

/*
	会员信息
	mobile 会员手机号码
 */
    public function member(){
    	$shop_id = $this->getUserInfo()['shop_id'];
    	if(empty($shop_id)){
    		return json(['code'=>'500','msg'=>'服务器内部出错','data'=>'']);
    	}
    	$res = $this ->request ->param();
    	if(empty($res['id'])){
            return json(['code'=>'401','msg'=>'用户身份验证失败，请稍后再试','data'=>'']);
        }
        $member = new MemberModel();
        $data = $member->getMember($res['id']);
        if($data){
            return json(['code'=>200,"msg"=>"查询成功","data"=>$data]);
        }else{
            return json(['code'=>400,"msg"=>"暂无数据","data"=>""]);
        }
    }

    //会员列表
    public function memberList(){
        $data = $this ->request ->param();
        if( empty($data['page']) ){
            $page = '';
        }else{
            $page = $data['page'];
        }
        $shop_id = $this->getUserInfo()['shop_id'];
        $shop_code = Db::name('shop')->where('id',$shop_id)->value('code');
        $where[] = ['a.shop_code','=',$shop_code];
        $where[] = ['a.status','=',1];
        if( !empty($data['mobile']) ){
            $where[] = ['a.mobile','like','%'.$data['mobile'].'%'];
        }
        $member = new MemberModel();
        $info = $member
                ->alias('a')
                ->where($where)
                ->join('member_level b','a.level_id=b.id')
                ->join('member_money c','a.id=c.member_id')
                ->field('a.id,a.mobile,a.nickname,a.regtime as addtime,b.level_name,c.money')
                ->page($page)
                ->order('a.regtime desc')
                ->select();
        foreach ($info as $key => $value) {
            $memberIds[] = $value['id'];
            $info[$key]['amount'] = 0;
        }
        if( count($info)<=0 ){
            return json(['code'=>200,'msg'=>'获取成功','count'=>0,'data'=>[]]);
        }
        $count = $member
                ->alias('a')
                ->where($where)
                ->join('member_level b','a.level_id=b.id')
                ->join('member_money c','a.id=c.member_id')
                ->count();
        $memberIds = implode(',', $memberIds);
        $detailsWhere[] = ['member_id','in',$memberIds];
        $detailsWhere[] = ['type','=',1];
        $details = Db::name('member_details')->where($detailsWhere)->field('member_id,amount')->select();
        foreach ($info as $key => $value) {
            foreach ($details as $k => $v) {
                if( $value['id'] == $v['member_id'] ){
                    if( !isset($info[$key]['amount']) ){
                        $info[$key]['amount'] = $v['amount'];
                    }else{
                        $info[$key]['amount'] += $v['amount'];
                    }
                }
            }
        }
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$info]);
    }
}
