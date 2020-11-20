<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 下午 15:56
 */

namespace app\mall_admin_pack\model;

use think\Model;

class StPackRule extends Model
{
    protected $autoWriteTimestamp = true;

    /**
     * 检查商品是否重复使用
     * @param $p_id
     * @param $item_ids
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function isItemUse($p_id,$item_ids)
    {
        $list = $this->alias('s')
            ->field('i.title,i.id')
            ->join('item i','s.item_id = i.id')
            ->where('p_id <>'.$p_id)
            ->whereIn('item_id',$item_ids)
            ->select();

        //数据处理，判断商品是否存在重复
        foreach ($list as $key=>$value)
        {
            $switch = array_search($value['id'],$item_ids);
            if($switch !== false)
            {
                return_error('该商品已存在：'.$value['title']);
            }
        }

        return true;
    }
}