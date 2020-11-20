<?php
/*
    股东数据统计表
*/
namespace app\wxshop\model\item;
use think\Model;
use think\Db;

/***
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class ItemModel extends Model
{
    protected $table = 'ddxm_item';

    /***
     * 商品图片
     * @param $val
     * @return string
     */
    public function getPicAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    /***
     * 商品视频
     * @param $val
     * @return string
     */
    public function getVideoAttr($val){
        if( $val == '' ){
            return '';
        }
        return "http://picture.ddxm661.com/".$val;
    }

    /***
     * 总销量
     * @param $val
     * @param $data
     * @return mixed
     */
    public function getSalesAttr($val,$data){
        return $data['initial_sales']+$data['reality_sales'];
    }

    /***
     * @param $val
     * @return array
     */
    public function getPicsAttr($val){
        if( $val == '' ){
            return [];
        }
        $img = [];
        $img = explode(',',$val);
        foreach ($img as $k=>$v){
            $img[$k] = "http://picture.ddxm661.com/".$v;
        }
        return $img;
    }

    /***
 * 分区
 * @param $val
 * @param $data
 * @return mixed|string
 */
    public function getMoldAttr($val,$data){
        $val = $data['mold_id'];
        $title = Db::name('item_type')->where('id',$val)->value('title');
        if( $title ){
            return $title;
        }
        return '熊猫自营';
    }

    /***
     * 返回规格specs_list
     * @param $val
     * @return mixed
     */
    public function getSpecsListAttr($val){
        $res = json_decode($val,true);
        return $res;
    }

    /***
     * j金额
     * @param $val
     * @param $data
     * @return string
     */
    public function getPriceAttr($val,$data){
        $specs_list = $data['specs_list'];
        $specs_list = json_decode($specs_list,true);
        if( count($specs_list)<=0 ){
            return $data['min_price'];
        }
        return $data['min_price'].'-'.$data['max_price'];
    }

    /***
     * 服务item_service_ids
     */
    public function getItemServiceIdsAttr($val){
        if( $val == '' ){
            return [];
        }
        $map[] = ['id','in',$val];
        $list = Db::name('item_ensure')->where($map)->order('sort asc')->field('title,content')->select();
        return $list;
    }

    /***
     * 获取商品是否存在拼团活动
     */
    public function getAssembleIdAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['id']];
        $where[] = ['status','eq',1];
//        $where[] = ['begin_time','>=',time()];
        $where[] = ['end_time','>=',time()];
        $assembleId = Db::name('assemble') ->where($where) ->value('id');
        if( !$assembleId ){
            return '';
        }
        return $assembleId;
    }

    /***
     * 获取商品是否为秒杀商品
     */
    public function getSeckillIdAttr($val,$data){
        $where = [];
        $where[] = ['item_id','eq',$data['id']];
        $where[] = ['end_time','>=',time()];
        $where[] = ['status','eq',1];
        $seckillId = Db::name('seckill') ->where($where) ->where('already_num','exp','< num')->value('id');
        if( !$seckillId ){
            return '';
        }
        return $seckillId;
    }

    /**
     * 判断是否为活动价
     */
    public function getMinPriceAttr($val,$data){
        $activity_price = $data['activity_price'];
        if( ($data['activity_type'] == 4 ) && !empty($data['activity_id']) ){       //目前只处理限时购
            if( (time() >= $data['activity_start_time']) && (time() <= $data['activity_end_time']) ){
                return $activity_price;
            }else{
                return $val;
            }
        }else{
            return $val;
        }
    }

    /**
     * 判断是否为活动价
     */
    public function getActivityIdAttr($val,$data){
        if( ($data['activity_type'] != 1) && !empty($data['activity_id']) ){       //目前只处理限时购和拼团功能
            if( time() <= $data['activity_end_time'] ){
                return $val;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }

    /***
     * 获取分区需知
     */
    public function getMoldKnowAttr( $val,$data ){
        $list = Db::name('item_type')->where(['id'=>$data['mold_id']])->field('id,title,content')->find();
        if( !$list ){
            return '';
        }
        return $list['content'];
    }

    /***
     * 捣蛋熊承诺
     */
    public function getPromiseAttr($val,$data){
        return '捣蛋熊承诺：正品保证  安心售后  假一赔十';    //承若
    }

    /***
     * 获取分销佣金
     */
    public function getRatioAttr($val,$data)
    {
        if ($data['ratio_type'] != 3)
        {
            return '0.00';
        }else{
            return $val;
        }
    }
}