<?php

// +----------------------------------------------------------------------
// | 优惠券模块
// +----------------------------------------------------------------------
namespace app\wxshop\model\coupon;
use think\Model;
use think\Db;
use app\wxshop\model\order\OrderModel;
class CouponModel extends Model
{
    protected $table = 'ddxm_coupon';

    //根据商品id查询此商品可用的优惠券
    public function getCouponList( $data )
    {
        if( !empty($data['item_id']) ){
            $item_id = $data['item_id'];
            $brand_id = Db::name('item') ->where('id',$item_id)->value('brand_id');
        }
        $sql = 'select a.id,a.c_name,a.c_type,a.c_amo_dis,a.c_use_price,
                a.c_use_cill,a.c_use_time,a.c_receive_num,a.c_provide_num,
                a.c_content,a.c_start_time,a.c_is_show 
                from ddxm_coupon as a join ddxm_coupon_use_scene as b on a.id = b.c_id
                where  (a.is_delete=0 and a.c_is_show=1) 
                ';
        $sql2 = 'select count(a.id) as count
                from ddxm_coupon as a join ddxm_coupon_use_scene as b on a.id = b.c_id
                where (a.is_delete=0 and a.c_is_show=1) 
                ';
        if( isset($item_id) ){
            $sql .= "and (( b.cus_use_scene = 0 )";
            $sql2 .= "and (( b.cus_use_scene = 0 )";
        }
        if( isset($item_id) ){
            $itemLike = '%,'.$item_id.',%';
            $sql .= " or (b.cus_use_scene = 1 and b.cus_scene_id like '$itemLike') or 
               (b.cus_use_scene = 2 and b.cus_scene_id not like '$itemLike') ";
            $sql2 .= " or (b.cus_use_scene = 1 and b.cus_scene_id like '$itemLike') or 
               (b.cus_use_scene = 2 and b.cus_scene_id not like '$itemLike')";
        }
        if( isset($brand_id) && $brand_id !== '' ){
            $brandLike = '%,'.$brand_id.',%';
            $sql .= " or (b.cus_use_scene = 3 and b.cus_scene_id like '$brandLike') or 
               (b.cus_use_scene = 4 and b.cus_scene_id not like '$brandLike') ";
            $sql2 .= " or (b.cus_use_scene = 3 and b.cus_scene_id like '$brandLike') or 
               (b.cus_use_scene = 4 and b.cus_scene_id not like '$brandLike') ";
        }
        if( isset($item_id) ){
            $sql .= ')';
            $sql2 .= ')';
        }
        $sql .= ' order by a.id desc';
        if( !empty($data['page']) && !empty($data['limit']) ){
            $sql .= ' LIMIT '.(($data['page']-1)*$data['limit']).','.$data['limit'];
        }
        $list = Db::query($sql);
        if( count($list) >0 ){
            foreach ( $list as $k=>$v ){
                if(!is_numeric($v['c_use_time'])||strpos($v['c_use_time'],".")!==false){
                    $list[$k]['c_use_time'] = json_decode($v['c_use_time'],true);
                }else{
                    $list[$k]['c_use_time'] = $v['c_use_time'];
                }
            }
        }
        $count = Db::query($sql2)[0]['count'];
        $res = ['count'=>$count,'data'=>$list];
        return $res;
    }

    //用户领取优惠券
    public function collectCoupon( $data )
    {
        $file = fopen('collect_loke.txt','w+');
        if (flock($file,LOCK_EX|LOCK_NB))
        {
            if( empty($data['id']) ){
                return_error('请先选择优惠券');
            }
            if( empty($data['member_id']) ){
                return_error('请先登录');
            }
            //获取优惠券详情
            $info = $this ->alias('a') ->where('id',$data['id'])->field('a.id,a.c_name,a.c_type,a.c_amo_dis,a.c_use_price,
                a.c_use_cill,a.c_use_time,a.c_receive_num,a.c_provide_num,
                a.c_content,a.c_start_time,a.c_is_show,a.is_delete')->find();

            if( $info['is_delete'] == 1 ){
                return_error('当前优惠券不可领取');
            }
            if( ($info['c_provide_num'] != -1) && ($info['c_provide_num'] == 0) ){
                return_error('当前优惠券已领取完啦');
            }
            if( $info['c_receive_num'] != 0 ){
                $map = [];
                $map[] = ['member_id','eq',$data['member_id']];
                $map[] = ['c_id','eq',$data['id']];
                $member_receive_num = Db::name('coupon_receive') ->where($map) ->count();
                if( $info['c_receive_num'] <= $member_receive_num ){
                    return_error('您已领取过此优惠券了');
                }
            }
            //判断是否到了领取时间
            if ( $info['c_start_time'] != 0 )
            {
                if ( time() < $info['c_start_time'] )
                {
                    return_error('优惠券暂未到领取时间');
                }
            }

            //6.18有一张优惠券需要单独处理ID25（每天总量5张），每天十点开抢
            if ( $data['id'] == 25 )
            {
                if ( time() < strtotime(date('Y-m-d')."10:00:00") )
                {
                    return_error('每日十点才能开始抢券哦!');
                }
                $map = [];
                $map[] = ['c_id','eq',$data['id']];
                $start_time = strtotime(date('Y-m-d'));
                $end_time = strtotime(date('Y-m-d').'23:59:59');
                $timeWhere = $start_time.','.$end_time;
                $map[] = ['create_time','between',$timeWhere];
                $all_num_today = Db::name('coupon_receive') ->where($map) ->count();    //今日一共领取
                if ( $all_num_today >= 5 )
                {
                    return_error('今日已抢完了');
                }
            }
            if( is_array($info['c_use_time']) ){
                $invalid_time = $info['c_use_time']['end_time'];
            }else{
                $invalid_time = time() + ($info['c_use_time']*24*60*60);
                $invalid_time = strtotime(date('Y-m-d',$invalid_time).' 23:59:59');
            }
            //判断当前时间是否已经过了过期时间
            if ( time() >= $invalid_time )
            {
                //如果过期，将该卷显示状态修改为不显示
                $this->where(['id'=>$data['id']])->setField('c_is_show',0);

                return_error('当前优惠券活动已经结束啦');
            }
            $coupon_receive_data = [];
            $coupon_receive_data = [
                'c_id'  =>$info['id'],
                'member_id'  =>$data['member_id'],
                'is_use'  =>1,
                'use_time'  =>0,
                'create_time'  =>time(),
                'invalid_time'  =>$invalid_time
            ];

            //开启事务
            Db::startTrans();
            try{
                Db::name('coupon_receive') ->insert($coupon_receive_data);
                if( $info['c_provide_num'] != -1 ){
                    $this ->where('id',$data['id'])->setDec('c_provide_num',1);
                }
                //提交事务
                Db::commit();
            }catch (\Exception $e){
                //事务回滚
                Db::rollback();
                //关闭并发进程
                flock($file,LOCK_UN);
                fclose($file);
                return_error($e ->getMessage());
            }
            //释放并发进程
            flock($file,LOCK_UN);
            return true;
        }else{
            return_error('系统繁忙,请稍后再试');
        }
    }

    //用户领取的优惠券
    public function memberCoupon( $data ){
        if( empty($data['member_id']) ){
            if( empty($data['kill']) ){
                return_error('请先登录');
            }else{
                return false;
            }
        }
        if( !empty($data['page']) && !empty($data['limit']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $where = [];
        if( !empty($data['is_use']) ){
            $where[] = ['b.is_use','eq',$data['is_use']];
            if( $data['is_use'] == 3 ){
//                $where[] = ['b.invalid_time','<=',time()];
            }
            if( $data['is_use'] == 1 ){
//                $where[] = ['b.invalid_time','>',time()];
            }
        }
        if( empty($data['is_use']) ){
            $where[] = ['b.is_use','eq',1];
        }
        $where[] = ['b.member_id','eq',$data['member_id']];
        $field = 'a.id,b.id as receive_id,a.c_name,a.c_type,a.c_amo_dis,a.c_use_price,a.c_use_scene,
                a.c_use_cill,a.c_use_time,a.c_receive_num,a.c_provide_num,
                a.c_content,a.c_start_time,a.c_is_show,b.is_use,b.use_time,b.invalid_time,c.cus_scene_id,c.cus_scene_id as cus_scene_ids,b.create_time';
        $list = $this ->alias('a')
            ->join('coupon_receive b','a.id=b.c_id')
            ->join('coupon_use_scene c','a.id=c.c_id')
            ->field($field)
            ->where($where)
            ->order('b.id desc')
            ->page($page)
            ->select()->toArray();
        $count = $this ->alias('a')
            ->join('coupon_receive b','a.id=b.c_id')
            ->where($where)
            ->count();
        return ['count'=>$count,'data'=>$list];
    }

    /***
     * 限制用卷时间
     */
    public function getCUseTimeAttr($val){
        if(!is_numeric($val)||strpos($val,".")!==false){
            $time = json_decode($val,true);
            return $time;
        }else{
            return $val;
        }
    }

    //根据选择的商品获取会员可使用的优惠券
    public function getCanUseCoupon($data){
        $canUseCoupon = self::memberCoupon($data);
        if( $canUseCoupon['count'] == 0 ){
            return [];
        }
        $couponList = $canUseCoupon['data'];
        $items = $data['item'];
        $canUse = [];       //用户最终可使用的优惠券
        foreach ( $couponList as $k=>$v ){
            $couponList[$k]['item_ids'] = [];
            foreach ( $items as $k1=>$v1 ){
                if( $v['c_use_scene'] == 0 ){
                    array_push($couponList[$k]['item_ids'],$v1);
                }
                $sceneIds = explode(',',$v['cus_scene_ids']);
                $sceneIds = array_filter($sceneIds);
                if( $v['c_use_scene'] == 1 ){
                    //指定商品可用
                    if( in_array($v1['id'],$sceneIds) ){
                        array_push($couponList[$k]['item_ids'],$v1);
                    }
                }
                if( $v['c_use_scene'] == 2 ){
                    //指定商品不可以
                    if( !in_array($v1['id'],$sceneIds) ){
                        array_push($couponList[$k]['item_ids'],$v1);
                    }
                }
                if( $v['c_use_scene'] == 3 ){
                    //指定品牌可用
                    if( in_array($v1['brand_id'],$sceneIds) ){
                        array_push($couponList[$k]['item_ids'],$v1);
                    }
                }
                if( $v['c_use_scene'] == 4 ){
                    //指定品牌不可用
                    if( !in_array($v1['brand_id'],$sceneIds) ){
                        array_push($couponList[$k]['item_ids'],$v1);
                    }
                }
            }
        }
        //循环判断,将不满足条件的优惠券除去
        $result = [];   //最终可使用的优惠券
        foreach ( $couponList as $k=>$v ){
            if( $v['c_use_price'] == 2 ){   //1原价适用、2会员价上使用'
                $all_amount = 0;
                foreach ( $v['item_ids'] as $v2 ){
                    $all_amount += $v2['all_price'];
                }
                if( $all_amount >= $v['c_use_cill'] ){
                    unset($v['item_ids']);
                    array_push($result,$v);
                }
            }
            if( $v['c_use_price'] == 1 ){
                $all_old_price = 0;
                foreach ( $v['item_ids'] as $v2 ){
                    $all_old_price += $v2['all_old_price'];
                }
                if( $all_old_price >= $v['c_use_cill'] ){
                    unset($v['item_ids']);
                    array_push($result,$v);
                }
            }
        }

        $res = [];  //最终返回数据
        //判断使用时间
        foreach ( $result as $k=>$v )
        {
            $end_time =  $v['invalid_time'];
            if ( is_array($v['c_use_time']) )
            {
                $start_time =  $v['c_use_time']['start_time'];  //设置了时间段就是开始时间
            }else{
                $start_time = $v['create_time'];    //设置的为领取后几天内可使用，则开始时间就是领取时间
            }
            if ( ($start_time <= time()) && ($end_time >= time()) )
            {
                array_push($res,$v);
            }
        }
        return $res;
    }

    //优惠券详情
    public function getCouponInfo($data){
        if( empty($data['id']) ){
            return_error('请选择优惠券');
        }
        $info = $this ->where('id',$data['id'])
            ->field('id,c_name,c_type,c_amo_dis,c_use_scene,c_use_cill,c_use_price,c_use_cill,c_use_time,c_content')
            ->find()->append(['cusScene_id']);
        return $info;
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
}