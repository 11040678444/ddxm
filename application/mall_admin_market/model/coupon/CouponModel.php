<?php

// +----------------------------------------------------------------------
// | 优惠券模块
// +----------------------------------------------------------------------
namespace app\mall_admin_market\model\coupon;

use app\mall_admin_market\validate\coupon\Coupon;
use think\Model;
use think\Db;

/**
 * 优惠券--
 * Class CouponModel
 * @package app\mall_admin_market\model\coupon
 */
class CouponModel extends Model
{
    protected $table = 'ddxm_coupon';
    //优惠券列表
    public function getList($data){
        $page = !empty($data['page'])?$data['page']:1;
        $limit = !empty($data['limit'])?$data['limit']:10;
        $where = [];
        if( !empty($data['c_name']) ){
            $where[] = ['a.c_name','like','%'.$data['c_name'].'%'];
        }
        if( isset($data['c_type']) && $data['c_type'] != '' ){
            $where[] = ['a.c_type','eq',$data['c_type']];
        }
        if( isset($data['c_is_show']) && $data['c_is_show'] !== '' ){
            $where[] = ['c_is_show','eq',$data['c_is_show']];
        }
        $where[] = ['is_delete','eq',0];
        $field = 'a.id,count(b.id) as receive_num,IF(a.c_provide_num <> "-1",count(b.id)+a.c_provide_num,"不限制") all_c_provide_num,c_name,c_type as c_type_name,c_amo_dis as c_amo_dis_name,c_use_scene,c_use_scene as c_use_scene_name,c_use_price as c_use_price_name,c_use_time as c_use_time_name,c_receive_num as c_receive_num_name,c_provide_num as c_provide_num_name,c_content,c_is_show as c_is_show_name,c_start_time as c_start_time_name,c_use_cill as c_use_cill_name';
        $list = $this->alias('a')
            ->field($field)
            ->join('coupon_receive b','a.id=b.c_id','LEFT')
            ->where($where)
            ->order('a.id desc')
            ->group('a.id')
            ->page($page,$limit)
            ->select();
        $count = $this->alias('a')->join('coupon_receive b','a.id=b.c_id','LEFT') ->where($where)
            ->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    //优惠券详情
    public function info($data){
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择优惠券']);
        }
        $info = $this ->where('id',$data['id'])->field('*,c_use_time as c_use_times') ->find()->append(['cus_scene_id']);
        return json(['code'=>200,'data'=>$info]);
    }

    //添加、编辑
    public function addOrEdit($data){
        $Validate = (new Coupon());
        if (!$Validate->check($data)) {
            return json(['code'=>100,'msg'=>$Validate->getError()]);
        }
        $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';     //金额规则
        if( !preg_match($rule,$data['c_amo_dis']) ){
            return json(['code'=>100,'msg'=>'金额或折扣格式有误']);
        }
        if(!is_numeric($data['c_use_time'])||strpos($data['c_use_time'],".")!==false){
            if( !isset($data['c_use_time']) && $data['c_use_time']['start_time'] == '' ){
                return json(['code'=>100,'msg'=>'请选择优惠券用券开始时间']);
            }
            if( !isset($data['c_use_time']) && $data['c_use_time']['end_time'] == '' ){
                return json(['code'=>100,'msg'=>'请选择优惠券用券结束时间']);
            }
            $c_use_time = json_encode($data['c_use_time']);
        }else{
            $c_use_time = $data['c_use_time'];  //整数
        }
        $coupon = [];   //优惠券主表
        $coupon = [
            'c_name'    =>$data['c_name'],
            'c_type'    =>$data['c_type'],
            'c_amo_dis'    =>$data['c_amo_dis'],
            'c_use_scene'    =>$data['c_use_scene'],
            'c_use_price'    =>$data['c_use_price'],
            'c_use_cill'    =>$data['c_use_cill'],
            'c_use_time'    =>$c_use_time,//
            'c_receive_num'    =>$data['c_receive_num'],
            'c_provide_num'    =>$data['c_provide_num'],
            'c_content'    =>$data['c_content'],
            'c_is_show'    =>$data['c_is_show'],
            'c_start_time'    =>$data['c_start_time'],
            'create_time'    =>time(),
        ];
        if( $data['c_use_scene'] == 0 ){
            $cus_scene_id = 0;
        }else{
            if( count($data['cus_scene_id']) == 0 ){
                return json(['code'=>100,'msg'=>'请选择指定的商品或品牌']);
            }
            $cus_scene_id = ','.implode(',',$data['cus_scene_id']).',';
        }

        //开启事务
        Db::startTrans();
        try{
            if( empty($data['id']) ){
                $couponId = $this ->insertGetId($coupon);
            }else{
                $this ->where('id',$data['id']) ->update($coupon);
                $couponId = $data['id'];
            }
            $coupon_use_scene = [];     //优惠券使用场景
            $coupon_use_scene = [
                'c_id'  =>$couponId,
                'cus_use_scene' =>$data['c_use_scene'],
                'cus_scene_id'  =>$cus_scene_id
            ];
            if( empty($data['id']) ){
                $coupon_use_scene['create_time'] = time();
                Db::name('coupon_use_scene') ->insert($coupon_use_scene);
            }else{
                $coupon_use_scene['update_time'] = time();
                Db::name('coupon_use_scene')->where('c_id',$couponId) ->update($coupon_use_scene);
            }
            //提交事务
            Db::commit();
        }catch (\Exception $e){
            //回滚
            Db::rollback();
            return json(['code'=>500,'msg'=>'创建失败','data'=>$e->getMessage()]);
        }
        return json(['code'=>200,'msg'=>'创建成功']);
    }

    //获取适用场景
    public function getCusSceneIdAttr($val,$data){
        if( $data['c_use_scene'] == 0 ){
            return '';  //全部商品
        }
        $info = Db::name('coupon_use_scene') ->where('c_id',$data['id']) ->find();
        if( $info['cus_use_scene'] == 0 ){
            return '';  //全部商品
        }
        $ids = substr($info['cus_scene_id'],1);
        $ids = substr($ids,0,-1);
        $map = [];
        $map[] = ['id','in',$ids];
        if( $info['cus_use_scene'] == 1 || $info['cus_use_scene'] == 2 ){
            $list = Db::name('item')->where($map)->field('id,title')->select();
        }else{
            $list = Db::name('brand')->where($map)->field('id,title')->select();
        }
        return $list;
    }

    //获取已领取的
    public function couponReceiveInfo($data){
        $val = $data['id'];
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $list = Db::name('coupon_receive')->alias('a')
            ->join('member b','a.member_id=b.id','LEFT')
            ->where('a.c_id',$val)
            ->field('a.id,a.member_id,a.is_use,a.use_time,a.create_time,a.invalid_time,b.wechat_nickname,b.mobile')
            ->page($page)
            ->select();
        if( count($list) == 0 ){
            return ['count'=>0,'data'=>$list];
        }
        $count = Db::name('coupon_receive')->alias('a')
            ->join('member b','a.member_id=b.id','LEFT')
            ->where('a.c_id',$val)
            ->count();
        foreach ( $list as $k=>$v ){
            $list[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            $list[$k]['invalid_time'] = date('Y-m-d H:i:s',$v['invalid_time']);
            if( !empty($v['use_time']) ){
                $list[$k]['use_time'] = date('Y-m-d H:i:s',$v['use_time']);
            }else{
                $list[$k]['use_time'] = '';
            }
        }
        return ['count'=>$count,'data'=>$list];
    }

    /***
     * 禁用、启用用户已领取的优惠券
     * @param $data
     * id :coupon_receive->id
     * type:0或者不传表示禁用,1表示启用
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateIsUse($data){
        if( empty($data['id']) ){
            return_error('请选择卡片');
        }
        $info = Db::name('coupon_receive') ->where('id',$data['id'])->find();
        if( !$info ){
            return_error('ID出错');
        }
        $update = [];
        if( empty($data['type']) ){
            $update = [
                'is_use'   =>3,
                'update_time'   =>time(),
                'remarks'   =>$data['nickname'].'禁用了优惠券',
            ];
        }else{
            if( $info['invalid_time'] <= time() ){
                return_error('当前优惠券已过期,不允许重新激活');
            }
            $update = [
                'is_use'   =>1,
                'update_time'   =>time(),
                'remarks'   =>$data['nickname'].'启用了优惠券',
            ];
        }
        $res = Db::name('coupon_receive') ->where('id',$data['id'])->update($update);
        return $res;
    }

    //c_type_name
    public function getCTypeNameAttr($val){
        $arr = [
            1   =>'满减券',
            2   =>'折扣券'
        ];
        return $arr[$val];
    }
    //c_use_scene_name
    public function getCUseSceneNameAttr($val){
        $arr = [
            0   =>'全部商品可用',
            1   =>'指定商品可用',
            2   =>'指定商品不可用',
            3   =>'指定品牌可用',
            4   =>'指定品牌不可用'
        ];
        return $arr[$val];
    }
    //c_use_price_name
    public function getCUsePriceNameAttr($val){
        $arr = [
            1   =>'原价适用',
            2   =>'会员价上使用'
        ];
        return $arr[$val];
    }
    //c_use_time_name
    public function getCUseTimeNameAttr($val){
        if(!is_numeric($val)||strpos($val,".")!==false){
            $time = json_decode($val,true);
            return date('Y-m-d H:i:s',$time['start_time']).'至'.date('Y-m-d H:i:s',$time['end_time']);
        }else{
            return '领卷起'.$val.'天内有效';
        }
    }
    public function getCUseTimeAttr($val){
        if(!is_numeric($val)||strpos($val,".")!==false){
            $time = json_decode($val,true);
            return $time;
        }else{
            return '领卷起'.$val.'天内有效';
        }
    }
    //c_receive_num_name
    public function getCReceiveNumNameAttr($val){
        if( $val == 0 ){
            return '不限制';
        }
        return $val;
    }
    //c_provide_num_name
    public function getCProvideNumNameAttr($val){
        if( $val == -1 ){
            return '不限制';
        }
        return $val;
    }
    //c_is_show_name
    public function getCIsShowNameAttr($val){
        if( $val == 0 ){
            return '不显示';
        }
        return '显示';
    }
    //c_start_time_name
    public function getCStartTimeNameAttr($val){
        if( $val == 0 ){
            return '立即生效';
        }
        return date('Y-m-d H:i:s',$val);
    }
    //c_use_cill_name
    public function getCUseCillNameAttr($val){
        if( $val == 0 ){
            return '无门槛';
        }
        return $val;
    }
}