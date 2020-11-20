<?php
/*
    股东数据统计表
*/
namespace app\wxshop\model\statistics;
use think\Model;
use think\Cache;
use think\Db;

// type:1余额充值,2购卡,3小号收款,4余额消耗,5消费消耗,6商品外包分润,
//              7推拿外包分润,8商品成本,9营业费用,10外包商品成本

/***
 * Class StatisticsLogModel
 * @package app\wxshop\model\statistics
 */
class StatisticsLogModel extends Model
{
	protected $table = 'ddxm_statistics_log';

	public function getCreateTimeAttr($val){
		return date('m-d H:i:s',$val);
	}

	public function getTypeAttr($val){
		$type = array(
				'1'		=>'余额充值',
				'2'		=>'购卡',
				'3'		=>'消费收款',
				'4'		=>'余额消耗',
				'5'		=>'消费消耗',
				'6'		=>'商品外包分润',
				'7'		=>'推拿外包分润',
				'8'		=>'商品成本',
				'9'		=>'营业费用',
				'10'	=>'外包商品成本'
			);

		return $type[$val];
	}

	public function getPayWayAttr($val){
		//当外包分润时没有支付方式，为0
		$payway = array(
				'0'=>'',
				'1'=>'微信支付',
				'2'=>'支付宝',
				'3'=>'余额',
				'4'=>'银行卡',
				'5'=>'现金',
				'6'=>'美团',
				'7'=>'赠送',
				'8'=>'门店自用',
				'9'=>'兑换',
				'10'=>'包月服务',
				'11'=>'定制疗程',
				'12'=>'超级汇购',
				'13'=>'限时余额',
				'14'=>'云客赞',
                '15'  =>'框框宝',
                '16'  =>'公司转门店',
				'99'=>'管理员充值'
			);
		return $payway[$val];
	}


    /***
     * @param $where
     * @return float
     */
	public function getAllPricedata($where){
		$price = $this ->where($where)->sum('price');
		return $price;
	}

    /***
     * @param $where
     * @param $page
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
	public function getAllList($where,$page){
		return $this ->where($where)
			->page($page)
			->order('create_time desc')
			->field('order_id as id,type,create_time,price,order_sn,data_type,pay_way,title')
			->select();
	}
	
}