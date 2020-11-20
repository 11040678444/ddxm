<?php

// +----------------------------------------------------------------------
// | 门店
// +----------------------------------------------------------------------
namespace app\admin\model\order;

use think\Model;
use think\Db;

class OrderTp5Model extends Model
{
	protected $table = 'ddxm_order';

	//获取时间
	public function getOvertimeAttr($val){
		return date('Y-m-d H:i:s',$val);
	}

	//状态
	public function getOrderStatusAttr($value)
    {
        $status = [
            2=>'正常',
            -3=>'有退单',
            -6=>'已退款',
            -2=>"已退货",
        ];
        return $status[$value];
    }

    public function getPayWayAttr($value)
    {
    	if (empty($value)) {
    		return '未支付';
    	}
        $status = array(
                1 => '微信',
                2 => '支付宝',
                3 => '余额',
                4 => '银行卡',
                5 => '现金支付',
                6 => '美团',
                7 => '赠送',
                8 => '门店自用',
                9 => '兑换',
                10 => '包月服务',
                11 => '定制疗程',
                12 => '超级汇买',
                13 => '限时余额',
                14 => '云客赞',
                15  =>'框框宝',
                16  =>'公司转门店',
                99 => '异常充值'
            );
        return $status[$value];
    }

	//拼装订单信息
	public function getMessageAttr($val,$data){
		if( empty($data['mobile']) ){
			$mobile = '散客用户';
		}else{
			$mobile = "(".$data['nickname'].")".$data['mobile'];
		}
		$sn = $data['sn'];
		$pay_way = [
            1 => '微信',
            2 => '支付宝',
            3 => '余额',
            4 => '银行卡',
            5 => '现金支付',
            6 => '美团',
            7 => '赠送',
            8 => '门店自用',
            9 => '兑换',
            10 => '包月服务',
            11 => '定制疗程',
            12 => '超级汇买',
            13 => '限时余额',
            15  =>'框框宝',
            99 => '异常充值'
        ];
        $shop = Db::name('shop')->where('id',$data['shop_id'])->value('name');
		$message = "<p>订单号：".$sn."</p>
				<p>会员号：".$mobile."</p>
				<p>付款金额：".$data['amount']." 元</p>
				<p>付款方式：".$pay_way[$data['pay_way']]."</p>
				<p>门店：".$shop."</p>";
		return $message;
	}

	//拼接商品信息
    public function getItemListAttr($val,$data){
        $item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('subtitle');
        $item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('service_name');
        $item = [];
        if( count($item1)>0 ){
            $item = $item1;
        }
        if( count($item2)>0 ){
            foreach ($item2 as $key => $value) {
                array_push($item, $value);
            }
        }
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p>".$value."</p>";
        }
        return $tt;
    }

    //拼接商品信息
    public function get_item_list($data){
        $item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('subtitle');
        $item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('service_name');
        $item = [];
        if( count($item1)>0 ){
            $item = $item1;
        }
        if( count($item2)>0 ){
            foreach ($item2 as $key => $value) {
                array_push($item, $value);
            }
        }
//        $tt = '';
        $tt = [];
        foreach ($item as $key => $value) {
//            $tt .= $value."\n";
            array_push($tt,$value);
        }
        return $tt;
    }


	//拼接单价信息
    public function getPriceListAttr($val,$data){
        $item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('real_price');
        $item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('real_price');
        $item = [];
        if( count($item1)>0 ){
            $item = $item1;
        }
        if( count($item2)>0 ){
            foreach ($item2 as $key => $value) {
                array_push($item, $value);
            }
        }
        $tt = '';
        foreach ($item as $key => $value) {
            $tt .= "<p> ￥".$value." 元</p>";
        }
        return $tt;
    }

    //拼接单价信息
    public function get_price_list($data){
        $item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('real_price');
        $item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('real_price');
        $item = [];
        if( count($item1)>0 ){
            $item = $item1;
        }
        if( count($item2)>0 ){
            foreach ($item2 as $key => $value) {
                array_push($item, $value);
            }
        }
//        $tt = '';
        $tt = [];
        foreach ($item as $key => $value) {
            array_push($tt,$value);
        }
        return $tt;
    }
    //拼接服务员
    public function get_worker_name($data){
        $item = Db::name('service_goods')->where('order_id',$data['id'])->column('name');
        $count = Db::name('order_goods')->where('order_id',$data['id'])->count();
        $name = Db::name('shop_worker')->where('id',$data['waiter_id'])->value('name');
        if( $count == 0 ){
            $tt = [];
            foreach ($item as $key => $value) {
                array_push($tt,$value);
            }
            return $tt;
        }else if(count($item) == 0){
            $tt = [];
            for ( $i=0;$i<$count;$i++ ){
                array_push($tt,$name);
            }
            return $tt;
        }else{
            $tt = [];
            for ( $i=0;$i<$count;$i++ ){
                array_push($tt,$name);
            }
            foreach ($item as $key => $value) {
                array_push($tt,$value);
            }
            return $tt;
        }
    }

	//拼接数量信息
	public function getNumListAttr($val,$data){
		$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('num');
		$item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('num');
		$item = [];
		if( count($item1)>0 ){
			$item = $item1;
		}
		if( count($item2)>0 ){
			foreach ($item2 as $key => $value) {
				array_push($item, $value);
			}
		}
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> X".$value."</p>";
		}
		return $tt;
	}

    //拼接数量信息
    public function get_num_list($data){
        $item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('num');
        $item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('num');
        $item = [];
        if( count($item1)>0 ){
            $item = $item1;
        }
        if( count($item2)>0 ){
            foreach ($item2 as $key => $value) {
                array_push($item, $value);
            }
        }
        $tt = '';
        $tt = [];
        foreach ($item as $key => $value) {
//            $tt .= $value."\n";
            array_push($tt,$value);
        }
        return $tt;
    }

	//拼接服务人员
	public function getWaiterListAttr($val,$data){
		$item = [];
		if( $data['type'] == 1 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->count();
			for( $i=0;$i<$item1;$i++ ){
				$item[$i] = $data['waiter'];
			}
		}
		if( $data['type'] == 2 ){
			$item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('name');
			foreach ($item2 as $key => $value) {
				array_push($item, $value);
			}
		}

		if( $data['type'] == 7 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->count();
			for( $i=0;$i<$item1;$i++ ){
				$item[$i] = $data['waiter'];
			}
			$item2 = Db::name('service_goods')->where('order_id',$data['id'])->column('name');
			foreach ($item2 as $key => $value) {
				array_push($item, $value);
			}
		}
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p>".$value."</p>";
		}
		return $tt;
	}

	//拼接成本
	public function getCostListAttr($val,$data){
		$item = [];
		if( $data['type'] == 1 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('all_oprice');
			foreach ($item1 as $key => $value) {
				array_push($item, $value);
			}
		}
		if( $data['type'] == 2 ){
			$item2 = Db::name('service_goods')->where('order_id',$data['id'])->count();
			for( $i=0;$i<$item2;$i++ ){
				$item[$i] = 0;
			}
		}

		if( $data['type'] == 7 ){
			$item1 = Db::name('order_goods')->where('order_id',$data['id'])->column('all_oprice');
			foreach ($item1 as $key => $value) {
				array_push($item, $value);
			}
			$item2 = Db::name('service_goods')->where('order_id',$data['id'])->count();
			for( $i=0;$i<$item2;$i++ ){
				array_push($item, 0);
			}
		}
		$tt = '';
		foreach ($item as $key => $value) {
			$tt .= "<p> ￥".$value." 元</p>";
		}
		return $tt;
	}
}