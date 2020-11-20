<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/17 0017
 * Time: 上午 9:49
 *
 * 打包一口价活动查询
 */

namespace app\wxshop\model\comment;
use think\Model;

class StPack extends Model
{
    protected $autoWriteTimestamp = true;

    /**查看商品是否参加活动
     * @param $item_id
     * @return array
     */
    public function getPack($item_id)
    {
        try{

            //组装查询条件
            $where[] = ['p.p_status & p.is_delete','eq',0];
            $where[] = ['p.sta_time','<=',time()];
            $where[] = ['p.end_time','>=',time()];
            $where[] = ['spr.item_id','in',$item_id];

            $list = $this->alias('p')
                ->field('p.id,p.p_name,p.p_condition1,p.p_condition2')
                ->join('st_pack_rule spr','spr.p_id = p.id')
                ->where($where)
                ->find();
            $this->getPackItemInfo($list['id']);
            return !empty($list) ? ['id'=>$list['id'],'p_name'=>$list['p_name'],'p_condition1'=>$list['p_condition1'],'p_condition2'=>$list['p_condition2']] : [];
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /** 获取活动详细
     * @param $id
     */
    public function getPackItemInfo($id)
    {
        try{
            //组装查询条件
            $where[] = ['p.p_status & p.is_delete','eq',0];
            $where[] = ['p.sta_time','<=',time()];
            $where[] = ['p.end_time','>=',time()];
            $where[] = ['spr.p_id','in',$id];
            $where[] = ['i.item_type & sgp.status','eq',1];

            $list = $this->alias('p')
                ->field('p.id,p.p_name,i.title,i.pic,sgp.price,sgp.key,sgp.key_name,p.p_condition1,
                      p.p_condition2,i.id item_id,sgp.store')
                ->join([['st_pack_rule spr','spr.p_id = p.id'],['item i','i.id = spr.item_id'],
                        ['specs_goods_price sgp','sgp.gid = i.id']
                       ])
                ->where($where)
                ->order('sgp.id desc')
                ->paginate(10);

            $list = $list->toArray();
            $list['url'] = config('QINIU_URL');
            return $list;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    //获取活动详细
    public function isPack($item_ids)
    {
        try{

            $data = $this->alias('p')
                    ->field('p.id,p.p_condition1,p.p_condition2,spr.item_id')
                    ->join('st_pack_rule spr','p.id = spr.p_id')
                    ->where(['p.is_delete'=>0,'p.p_status'=>0])
                    ->whereIn('spr.item_id',$item_ids)
                    ->group('p.id')
                    ->select()
                    ->toArray();

            return $data;
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}