<?php
namespace app\wxshop\controller;

use think\Db;
use app\wxshop\model\item\ItemModel;
use app\wxshop\model\seckill\FlashSaleModel;
use app\wxshop\model\seckill\FlashSaleAttrModel;
/**
分销礼包控制器
 */
class RetailGoods extends Base
{
    /***
     * 分销礼包列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function retail_List(){
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',4);
        $where = [];
        $where[] = ['a.status','eq',1];
        $where[] = ['b.status','eq',1];
        $where[] = ['a.type','eq',4];   //礼包
        $list = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id,b.flash_sale_id')
            ->page($page,$limit)
            ->field('a.id,a.title,b.item_id,b.item_name,b.old_price,b.price,b.already_num,b.residue_num,b.stock,b.virtually_num')
            ->order('a.id desc')
            ->select()
            ->append(['is_over','pic']);
        $count = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id')->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /***
     * 分销礼包商品详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function retail_info(){
        $data = $this ->request ->param();
        if( empty($data['retail_id']) || empty($data['item_id']) ){
            return json(['code'=>100,'msg'=>'请传入秒杀id或者商品id']);
        }
        $data['assemble_id'] = $data['retail_id'];
        //先查询礼包
        $info = ( new FlashSaleModel() ) ->where('id',$data['assemble_id'])
            ->field('id,title,start_time,end_time,type,people_num,assemble_num')
            ->find()->append(['is_over'])->toArray();  //秒杀详情
        //查询商品详情
        $itemInfo = ( new ItemModel() )->alias('a') ->where('a.id',$data['item_id'])
            ->field('a.id,a.status,a.title,a.subtitle,a.mold_id,a.video,a.initial_sales,a.reality_sales,a.lvid,a.content,a.pics,a.own_ratio as ratio')
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
        }
        $info['attributes'] = $attributes;  //规格名称
        $info['item'] = $itemInfo;      //商品信息
        $info['item_specs'] = $item_specs;  //商品的规格组信息
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }
}