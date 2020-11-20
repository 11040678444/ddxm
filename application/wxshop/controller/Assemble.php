<?php
namespace app\wxshop\controller;

use app\wxshop\controller\Base;
use app\wxshop\model\comment\StPack;
use think\Controller;
use think\Db;
use think\Exception;
use think\Query;
use think\Request;
use think\facade\Cache;

use app\wxshop\model\item\ItemModel;
use app\wxshop\model\seckill\FlashSaleModel;
use app\wxshop\model\seckill\FlashSaleAttrModel;
use app\wxshop\model\assemble\AssembleListModel;
use app\wxshop\model\item\BrandModel;
/**
第二期活动列表
 */
class Assemble extends Base
{
    /***
     * 拼团：第二期
     */
    public function assemble_List(){
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',10);
        $where = [];
        $where[] = ['a.end_time','>=',time()];
        $where[] = ['a.status','eq',1];
        $where[] = ['b.status','eq',1];
        $where[] = ['a.type','eq',3];   //拼团
        $list = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id,b.flash_sale_id')
            ->page($page,$limit)
            ->field('a.id,a.title,a.assemble_num,a.start_time,a.end_time,b.item_id,b.item_name,b.old_price,b.price,b.already_num,b.residue_num,b.stock,b.virtually_num')
            ->order('a.start_time asc')
            ->select()
            ->append(['status','now_time','is_over','pic']);
        $count = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id')->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /***
     * 拼团详情：第二期
     */
    public function assemble_info(){
        $data = $this ->request ->param();
        if( empty($data['assemble_id']) || empty($data['item_id']) ){
            return json(['code'=>100,'msg'=>'请传入秒杀id或者商品id']);
        }
        //先查询拼团
        $info = ( new FlashSaleModel() ) ->where('id',$data['assemble_id'])
            ->field('id,title,start_time,end_time,type,people_num,assemble_num')
            ->find()->append(['now_time','status','is_over'])->toArray();  //秒杀详情
        //查询商品详情
        $itemInfo = ( new ItemModel() )->alias('a') ->where('a.id',$data['item_id'])
            ->field('a.id,a.status,a.title,a.subtitle,a.mold_id,a.video,a.initial_sales,a.reality_sales,a.lvid,a.content,a.pics,a.own_ratio as ratio,ratio_type')
            ->find()->append(['mold','mold_know','promise'])->toArray();
        $where = [];
        $where[] = ['flash_sale_id','eq',$data['assemble_id']];
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['status','eq',1];
        $item_specs = ( new FlashSaleAttrModel() )->alias('b') ->where($where)
            ->field('b.specs_ids,b.item_name,b.specs_names,b.old_price,b.price,b.commander_price,b.item_id,b.residue_num,b.virtually_num,b.residue_num as over_num,b.already_num')
            ->select()->append(['pic'])->toArray();
        if( $info['status'] == 2 ){
            foreach ( $item_specs as $k=>$v ){
                $item_specs[$k]['already_num'] = 0;//如果活动未开始则将已抢数量赋值为0
            }
        }
        //拼装规格组名称
        $specs_ids = $item_specs[0]['specs_ids'];
        if( !empty($specs_ids) ){
            $specs_ids = explode('_',$specs_ids);
            $map = [];
            $map[] = ['id','in',implode(',',$specs_ids)];
            $attributes = Db::name('item_specs')->where($map)->column('pid');
            $map = [];
            $map[] = ['id','in',implode(',',$attributes)];
            $attributes = Db::name('item_specs')->where($map)->column('title');
            $attributes = implode(',',$attributes);
        }else{
            $attributes = '';
        }
        foreach ( $item_specs as $k=>$v ){
            if( isset($itemInfo['price'])  ){
                if( $v['price'] < $itemInfo['price'] ){
                    $itemInfo['price'] = $v['price'];
                }
            }else{
                $itemInfo['price'] = $v['price'];
            }
            if( isset($itemInfo['old_price']) ){
                if ( $v['old_price'] < $itemInfo['old_price'] ) {
                    $itemInfo['old_price'] = $v['old_price'];
                }
            }else{
                $itemInfo['old_price'] = $v['old_price'];
            }
            if( $v['residue_num'] != '-1' ){      //每种规格剩余可以卖出的数量
                if( self::getToken() ){
                    //number
                    $orderWhere = [];
                    //0: 普通订单 1：拼团订单 2：抢购订单，3限时抢购',
                    $orderWhere[] = ['o_type','eq',$info['type']==1?3:$info['type']==2?2:1];
                    $orderWhere[] = ['member_id','eq',self::getToken()];
                    $orderWhere[] = ['o_pay_status','eq',1];
                    $orderWhere[] = ['o_deduct_id','eq',$data['assemble_id']];
                    $orderIds = Db::name('n_order') ->where($orderWhere)->column('id');
                    $orderGoods = Db::name('n_order_goods')->where('order_id',implode(',',$orderIds))
                        ->field('goods_id as item_id,og_goods_key as attr_ids,og_num asnum')
                        ->select(); //单人已经购买的数量
                    $people_num = 0;        //每人购买的这个这个商品对应的规格的数量
                    $xian_num = 0;          //每人购买的这个商品的数量
                    foreach ( $orderGoods as $k1=>$v1 ){
                        if( ($v['item_id'] == $v1['item_id'])  ){
                            if( ($v['specs_ids'] == $v1['attr_ids']) ){
                                $people_num += $v1['num'];  //用来判断
                            }
                            $xian_num += $v1['num'];
                        }
                    }
                    if( $info['people_num'] != '-1' ){
                        if( $info['people_num'] >$xian_num ){
                            $item_specs[$k]['residue_num'] = $info['people_num']-$xian_num;
                        }else{
                            $item_specs[$k]['residue_num'] = 0;
                        }
                    }
                }
            }
        }
        //查询拼团有几组人正在拼团
        $groupWhere = [];
        $groupWhere[] = ['assemble_id','eq',$data['assemble_id']];
        $groupWhere[] = ['status','eq',1];
        $groupWhere[] = ['r_num','>=',0];
        $assemble_group =  (new AssembleListModel())
            ->where($groupWhere)
            ->limit(10)
            ->order('r_num asc')
            ->field('id,r_num,end_time')
            ->select()
            ->append(['info'])->toArray();
        $LoginId = self::getToken();  //判断当前是否为登陆
        $order_info = [];       //假如当前登陆用户已经参与了拼团，则放此信息
        $login_assemble_info = [];  //当前登录人信息的拼团信息
//        dump($assemble_group);die;
        if( count($assemble_group)>0 ){
            foreach ($assemble_group as $k=>$v){
                foreach ( $v['info'] as $k1=>$v1 ){
                    if( $v1['commander'] == 1 ){    //将团长排在上面
                        $assemble_group[$k]['commander_nickname'] = $v1['nickname'];
                        $assemble_group[$k]['commander_pic'] = $v1['pic'];
                        $assemble_group[$k]['m_id'] = $v1['member_id'];
                    }
                    if( $LoginId ){
                        if( $v1['member_id']==$LoginId ){
                            $order_info['order_id'] = $v1['order_id'];
                            $order_info['order_status'] = $v1['status'];
                            $order_info['order_amount'] = $v1['amount'];
                            $login_assemble_info = [
                                'member_id' =>$v1['member_id'],
                                'id' =>$v['id'],
                                'r_num' =>$v['r_num'],
                                'end_time' =>$v['end_time'],
                                'type' =>$v1['commander'],
                            ];
                        }
                    }
                }
            }
            foreach ( $assemble_group as $k=>$v ){
                unset($assemble_group[$k]['info']);
            }
            $assemble_group = (new FlashSaleModel()) ->getCopywriting($assemble_group);
        }
        $info['attributes'] = $attributes;  //规格名称
        $info['item'] = $itemInfo;      //商品信息
        $info['item_specs'] = $item_specs;  //商品的规格组信息
        $info['assemble_group'] = $assemble_group;  //拼团信息
        $info['order_info'] = $order_info;  //当前登录用户的订单信息

        //提取部分拼团数据到redis中，方便下单使用
        $assemble_info = [
            'assemble_group'=>$login_assemble_info,//已参加活动人员
            'goods'=>$item_specs,//商品规格组
            'people_num'=>$info['people_num'],//每人限购购买数量
            'status'=>$info['status'], //活动状态
            'mold_id'=>$info['item']['mold_id'],//是否为跨境购
            'end_time'=>$info['end_time'],
            'assemble_num'=>$info['assemble_num']//总参团人数
        ];

        //键值规则：活动ID_用户ID
        $key = $info['id'].'_'.$LoginId;
        redisObj()->setex($key,$info['end_time']-time(),serialize($assemble_info));

        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /***
     * 秒杀第三期：时间段
     */
    public function seckill_time(){
        $where = [];
        $where[] = ['end_time','>',time()];
        $where[] = ['start_time','>=',strtotime(date('Y-m-d').' 00:00:00')];
        $where[] = ['start_time','<=',strtotime(date('Y-m-d').' 23:59:59')];
        $where[] = ['type','=',2];
        $where[] = ['status','=',1];
        $list = (new FlashSaleModel())
            ->where($where)->group('start_time')
            ->field('start_time,title,start_time as start_title')
            ->select()->append(['status','now_time']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }
}