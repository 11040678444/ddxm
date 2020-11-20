<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/7 0007
 * Time: 下午 16:50
 */
namespace app\admin\model\statistics;
use think\Model;

class StatisticsLog extends Model
{
    /**
     * 获取列表
     * @param $where 查询条件
     * @return array
     */
    public function getCostReverseList($where)
    {
        $list = StatisticsLog::alias('sl')
            ->field('sl.id,shop_id,order_sn,price,title,FROM_UNIXTIME(create_time) create_time,s.name,og_id subtitle')
            ->join('shop s','s.id = sl.shop_id')
            ->where($where)
            ->order('create_time desc')
            ->paginate(10)->toArray();

        return $list;
    }

    protected function getSubtitleAttr($val)
    {
        if(!empty($val))
        {
            $subtitle = db('order_goods')->where(['id'=>$val])->value('subtitle');

            return $subtitle;
        }else{
            return "";
        }
    }
}