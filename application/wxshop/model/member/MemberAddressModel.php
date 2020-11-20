<?php
/*
    订单模型
*/
namespace app\wxshop\model\member;
use think\Model;
use think\Cache;
use think\Db;

class MemberAddressModel extends Model
{
    protected $table = 'ddxm_member_address';

    public function getAddresAttr($val,$data){
        $areaNames = $data['area_names'];
        $areaNames = explode(',',$areaNames);
        return $areaNames['0'].' '.$areaNames['1'].$areaNames['2'].$data['address'];
    }

    //获取身份认证信息
    public function getIdInfoAttr($val,$data){
        $attestationId = $data['attestation_id'];
        $info = Db::name('member_attestation')->where('id',$attestationId)->find();
        if( !$info ){
            return [];
        }
        return ["http://picture.ddxm661.com/".$info['front'],"http://picture.ddxm661.com/".$info['back']];
    }
}