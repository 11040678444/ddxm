<?php

// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------

namespace app\admin\model\purchase;
use app\admin\common\Logs;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class PurchaseModel extends Model {
    protected $table = 'ddxm_purchase';
    public function purchase_index($purchase){
    	foreach($purchase as $k=>$v){
    		$data[$k]['message'] = "
    		<p>订单号:{$v['sn']}</p>
    		<p>供货商:{$v['supplier_name']}</p>
    		<p>采购人:{$v['purchase_admin_name']}</p>
    		";
    		$item = $this->getItemName($v['id']);
    		$data[$k]['item'] = $item['item_name'];
    		$data[$k]['item_code'] = $item['item_code'];
    		$data[$k]['price'] = $item['price'];
    		$data[$k]['num'] = $item['num'];
    		$data[$k]['amount'] = $item['amount'];
    		$data[$k]['r_num'] = $item['r_num'];
    		$data[$k]['status'] =$v['status'];
    		$data[$k]['id'] = $v['id'];
    	}
    	return $data;
    }	
    public function getItemName($res){
    	$data = db::name("purchase_item")->where("purchase_id",$res)->select();
    	/*$array*/
    	foreach($data as $k=>$v){
    		$array['item_name'] .= "<p>{$v['item_name']}</p>";
    		$array['price'] .= "<p>￥{$v['cg_amount']}</p>";
    		$array['num'] .= "<p>{$v['num']}</p>";
    		$amount = $v['cg_amount'] * $v['num'];
    		$array['amount'] .="<p>￥{$amount}</p>";
    		$r_num  = $v['num'] - $v['s_num'];
    		$array['r_num'] .="<p>{$r_num}</p>";
            $array['item_code'] .= "<p>{$v['item_code']}</p>";
    	}
    	return $array;
    }
    public function purchase_storage($res){
    	$id = intval($res['id']);
    	$data = $this->table($this->table)->where("id",$id)->field("sn,id,create_time,supplier_name,purchase_admin_name,remark")->find();
    	$data['shop'] = $this->getshop();
    	return $data;
    }
    public function getCreateTimeAttr($res){
    	if($res){
    		return date("Y-m-d H:i:s",$res);
    	}
    	return "暂无数据";
    }
    public function getshop(){
        $where = [];
        $where[] = ['id','neq',1];
    	$data = DB::name("shop")->where($where)->field('id,name')->select();
    	return $data;
    }
    public function purchase_item(){
    	$data =  db::name("item")
        ->alias("a")->join("item_category it","a.type = it.id");
        return $data;
    }
    public function purchaseAdd($res){
    	$count = $this->table($this->table)->count();
    	$count ++;
    	$purchase=[
    		"sn"=>"CG".date("Ymd").sprintf("%05d",$count),// CG 采购 年月日 总数填充0 5位
    		"supplier_id"=>$res["data"]['supplier'],
    		"supplier_name"=>db::name("supplier")->where("id",intval($res["data"]['supplier']))->value("supplier_name"),
    		"purchase_admin_id" => session("admin_user_auth")['uid'],
    		"purchase_admin_name" => Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
    		"status"=>1,
    		"create_time"=>time(),
    		"remark"=>$res['data']['remarks'],
    		"amount"=>$res['data']['amount'],
    		"real_amount"=>$res['data']['amount'],
    	];

    	/*exit;*/
    	$db = db::name("purchase");
    	$db->startTrans();
    	$id = $db->insertGetId($purchase);
    	try{
    		for($i = 0;$i<intval($res['data']['count']);$i++){
    			$item = [
    				"purchase_id"=>$id,
    				"num"=>$res['data']['number'][$i],
    				"item_id"=>$res['data']['item_id'][$i],
    				"item_name"=>$res['data']['item_name'][$i],
    				"item_code"=>$res['data']['bar_code'][$i],
    				"remarks"=>$res['data']['remake'][$i],
    				"s_num"=>$res['data']['number'][$i],
    				"cg_amount"=>$res['data']['price'][$i],
    				"md_amount"=>$res['data']['price'][$i],
    				"level" =>$res['data']['p_type'][$i],
    				"level_id" =>$res['data']['level_id'][$i],
    				"levels" =>$res['data']['cname'][$i],
    				"levels_id" =>$res['data']['levels_id'][$i],
    			];
    			DB::name("purchase_item")->insert($item);
    		}
    		$db->commit();
           	return json(['result'=>true,"msg"=>"添加成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            return json(['result'=>false,"msg"=>"系统繁忙,请稍后重试!","data"=>""]);
        }

    }
    //商品入库
    public function storage_item($res){
    	$data = $res['data'];
    	$shop_id = intval($res['shop_id']);
        $purchase_id = intval($res['purchase_id']);
    	$nickname = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
    	$db = db::name("purchase_item");
    	$result = false;
    	$db->startTrans();
    	try{
            $insert = 0;
	    	foreach($data as $key =>$value){
	    		$id = intval($value['id']);
	    		$item = Db::name("purchase_item")->where("id",$id)->find();
	    		$num = intval($value['l_num']);
	    		if($num>$item['s_num']){
	    			return json(['result'=>false,"msg"=>"非法操作","data"=>""]);
	    		}
	    		if($num==0){
	    			continue;
	    		}
	    		$store =[
	    			"pd_id"=>$id,
	    			"num" =>$num,
                    "purchase_id" =>$value['purchase_id'],
	    			"shop_id"=>$shop_id,
	    			"cg_amount" =>$value['cg_amount'],
	    			"md_amount" =>$value['md_amount'],
	    			"item_id"   =>$value['item_id'],
	    			"item_name" =>$value['item_name'],
	    			"item_code" =>$value["item_code"],
	    			"operator_id"=>session("admin_user_auth")['uid'],
	    			"operator"=>$nickname,
	    			"time"=>time(),
	    		];
	    		db::name("purchase_store")->insert($store);
	    		$purchase_price=[
	    			"shop_id"=>$shop_id,
	    			"type"=>1,
	    			"pd_id"=>$id,
	    			"item_id"=>$value['item_id'],
	    			"md_price"=>$value['md_amount'],
	    			"store_cose"=>$value['cg_amount'],
	    			"stock"=>$num,
	    			"time"=>time(),
	    		];
	    		db::name('purchase_price')->insert($purchase_price);
	    		Db::name("purchase_item")->where("id",$id)->setDec("s_num",$num);
	    		
                $shop_item = db::name("shop_item")->where("shop_id",$shop_id)->where("item_id",$value['item_id'])->find();
                if($shop_item){
                    db::name("shop_item")->where("shop_id",$shop_id)->where("item_id",$value['item_id'])->setInc("stock",$num);
                }else{
                    $shop_item =[
                        "shop_id"=>$shop_id,
                        "item_id"=>$value['item_id'],
                        "stock"=>$num,
                    ];
                    db::name("shop_item")->insert($shop_item);
                }
                
	    		$result = true;
	    		$insert++;
	    	}

	    	if($insert==0){
                $db->rollback();
                return json(['result'=>false,"msg"=>"没有商品入库!","data"=>""]);
            }else{
                $db->commit();
            
            $number = Db::name("purchase_item")->where("purchase_id",$purchase_id)->where("s_num",0)->count();
            $count = Db::name("purchase_item")->where("purchase_id",$purchase_id)->count();
            }
	    	if($result){
                if($number ==$count){
                    db::name("purchase")->where("id",$purchase_id)->update(['status'=>3]);
                }else{
                    db::name("purchase")->where("id",$purchase_id)->update(['status'=>2]);
                }
	    		return json(['result'=>true,"msg"=>"入库成功","data"=>""]);
	    	}else{
	    		return json(['result'=>false,"msg"=>"无数据更新","data"=>""]);
	    	}
    	} catch (Exception $e) {
            $db->rollback();
            return json(['result'=>false,"msg"=>"系统繁忙,请稍后重试!","data"=>""]);
        }
    }
    // 反入库
    public function refund_storage($id){
        $purchase = DB::name("purchase")->where("id",intval($id))->find();
        if($purchase['status'] !=3){
            return  json(['result'=>false,"msg"=>"系统繁忙","data"=>""]);
        }
        $pd_id = db::name("purchase_item")->where("purchase_id",intval($id))->select();
        $purchase_store = db::name("purchase_store")->where("purchase_id",intval($id))->where("status",1)->select();
        $db = db::name("shop_item");
        $log = new Adminlog();
       
        $db->startTrans();
        try{
            foreach($purchase_store as $k =>$v){
                $shop_item = db::name("shop_item")->where("shop_id",$v['shop_id'])->where("item_id",$v['item_id'])->find();
                if($shop_item < $v['num']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>"{".$v['item_name']."}商品库存不足","data"=>""]);
                }
                $number = $v['num'];
                $purchase_price = db::name("purchase_price")->where("shop_id",$v['shop_id'])->where("item_id",$v['item_id'])->order("time","desc")->select();
                // 商品价格库存  修改
                foreach($purchase_price as $key => $val){
                    if($number ==0){
                        continue;
                    }
                    if($val['stock'] >= $number){
                        db::name("purchase_price")->where("id",$val['id'])->setDec("stock",$number);
                        $number =0;
                    }else{
                        db::name("purchase_price")->where("id",$val['id'])->setDec("stock",$val['stock']);
                        $number =$number - $val['stock'];
                    }
                }
                // 商品库存修改
                db::name("shop_item")->where("shop_id",$v['shop_id'])->where("item_id",$v['item_id'])->setDec("stock",$v['num']);
                $update_store['reverse_purchase_id'] = session("admin_user_auth")['uid'];
                $update_store['reverse_purchase_user'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
                $update_store['reverse_purchase_time'] = time();
                $update_store['status'] = 2;
                db::name("purchase_store")->where("id",$v["id"])->update($update_store);
                $log->record_insert("采购单'".$purchase["sn"]."'反入库成功",0,"商品:".$v['item_name']." 数量:".$v['num']);
                db::name("purchase_item")->where("id",$v['pd_id'])->setInc("s_num",$v['num']);
            }
            DB::name("purchase")->where("id",intval($id))->update(['status'=>1]);
            $db->commit();
            return json(['result'=>true,"msg"=>"反入库成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            return json(['result'=>false,"msg"=>"系统繁忙,请稍后重试!","data"=>""]);
        }
    }
}
