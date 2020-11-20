<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 下午 14:36
 */

namespace app\mall_admin_pack\model;
use think\Model;
use think\Db;

class StPack extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * @param int $limit 分页
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPackList($limit)
    {
        $where['is_delete']= 0;
        $p_status = input('p_status');
        isset($p_status)? $where['p_status'] = input('p_status') : '';//搜索条件

        $list = $this->field('id,p_name,p_status,if(p_status = 0,"正常","失效") status_name,p_condition1,
                     p_condition2,sta_time,end_time')
                ->where($where)
                ->order('id desc')
                ->paginate($limit)
                ->toArray();

        //根据主键ID查询
        $item = db('st_pack_rule')
                ->field('spr.p_id,i.title')
                ->alias('spr')
                ->join('item i','spr.item_id = i.id')
                ->whereIn('spr.p_id',array_column($list['data'],'id'))
                ->select();

        //数据处理
        foreach ($item as $key=>$value)
        {
            foreach ($list['data'] as $k=>$v)
            {
                if($value['p_id'] == $v['id'])
                {
                    $list['data'][$k]['item'][] = $value;
                }
            }
        }
        return $list;
    }

    /**
     * @param $data 数据集
     * @return bool|\think\Collection
     * @throws \Exception
     * @throws \think\exception\PDOException
     */
    public function add($data)
    {
        empty($data) ? return_error('数据不能为空') : '';

        $this->startTrans();

        if(empty($data['id']))
        {
            //过滤保存
            $res = $this->allowField(true)->save($data);
            //获取新增后的主键ID
            $id = $this->id;
        }else{
            //过滤编辑
            $res = $this->allowField(true)->update($data);
            //获取新增后的主键ID
            $id = $data['id'];
        }


        if($res)
        {
            $packrule = new StPackRule();
            $packrule->startTrans();

            //检测商品在另外活动中使用，如果存在直接返回
            $packrule = new StPackRule();
            $packrule->isItemUse($id,$data['item_id']);

            //如果是编辑先删除
            $packrule->destroy(['p_id'=>$id]);

            //处理数据
            $datas = [];
            foreach ($data['item_id'] as $key => $val)
            {
                $datas[] = ['item_id'=>$val,'p_id'=>$id];
            }

            //保存规则
            $res = $packrule->allowField(true)->saveAll($datas);

            if($res)
            {
                $this->commit();$packrule->commit();
            }else{
                $packrule->rollback();
            }
        }else{
            $this->rollback();
        }

        return $res;
    }

    /**
     * @param $data 更新条件数据集
     * @return static
     */
    public function changeField($data)
    {
       empty($data) ? return_error('数据不能为空') : '';

       $res = $this->allowField(true)->update($data);

       return $res;
    }

    /** 查看活动详细
     * @param $id 活动ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPackInfo($id)
    {
        $data = $this->alias('p')
                ->field('p.id,p.p_name,p.p_status,if(p.p_status = 0,"正常","失效") status_name,
                p.p_condition1,p.p_condition2,p.sta_time,p.end_time,spr.item_id,i.title,sgp.price,
                sgp.imgurl,p.p_overlay')
                ->join([['st_pack_rule spr','p.id = spr.p_id'],['item i','spr.item_id = i.id'],
                        ['specs_goods_price sgp','sgp.gid = spr.item_id']
                       ])
                ->where(['p.id'=>$id,'is_delete'=>0,'sgp.status'=>1])
                ->group('p.id')
                ->select();

        //处理数据返回
        $info = [];
        foreach ($data as $key=>$val)
        {
            if(empty($info))
            {
                $info['id'] = $val['id'];
                $info['p_name'] = $val['p_name'];
                $info['p_status'] = $val['p_status'];
                $info['p_condition1'] = $val['p_condition1'];
                $info['p_condition2'] = $val['p_condition2'];
                $info['sta_time'] = $val['sta_time'];
                $info['end_time'] = $val['end_time'];
                $info['overlay'] = $val['p_overlay'];
            }

            $info['item'][]=[
                'id'=>$val['item_id'],
                'name'=>$val['title'],
                'old_price'=>$val['price'],
                'pic_url'=>config('QINIU_URL').$val['imgurl']
                ];

        }

       return $info;
    }
}