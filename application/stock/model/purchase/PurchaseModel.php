<?php

namespace app\stock\model\purchase;
use think\Model;
use think\Db;

/**
 * 采购单
 * Class PurchaseModel
 * @package app\admin\model\purchase
 */
class PurchaseModel extends Model {
    protected $table = 'ddxm_purchase';

    // 查询 采购单 列表
    public function getList($map,$Nowpage,$limits){



        return $this
            ->alias('p')
            ->field('p.id,p.sn,supplier_name,p.supplier_name,purchase_admin_name,p.status')
            ->page($Nowpage,$limits)
            ->where($map)
            ->order('p.id desc')
            ->select()
            ->append(['item']);
    }

    // 采购单列表---》对应 采购单 商品明细
    public function getItemAttr($val,$data){

        $id = $data['id'];
        $purchaseItemModel = new PurchaseItemModel();
        return $purchaseItemModel->getPurchaseIdList($id);
    }

    //查询采购单 列表  总长度
    public function getListCount($map){
        return $this
            ->alias('p')
            ->where($map)->count();
    }
    // 查询 采购单 列表
    public function findId($id){

        return $this
            ->alias('p')
            ->field('p.id,p.sn,supplier_name,p.supplier_name,purchase_admin_name,p.status,remark,p.create_time')
            ->where('status','<>',6)
            ->where('id',$id)
            ->order('p.id desc')
            ->find()
            ->append(['item']);
    }

    //根据ID  查询 采购单
    public function findIdUp($id){

        return $this
            ->alias('p')
            ->field('p.id,p.sn,supplier_name,p.supplier_name,purchase_admin_name,p.status')
            ->where('status','<>',6)
            ->where('id',$id)
            ->order('p.id desc')
            ->find();
    }

    //商品入库
    public function storage_item($res){
    	$data = $res['data'];
    	$shop_id = intval($res['shop_id']);
        $purchase_id = intval($res['purchase_id']);
    	$nickname = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))
            ->value("nickname");
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
                    db::name("shop_item")->where("shop_id",$shop_id)
                        ->where("item_id",$value['item_id'])->setInc("stock",$num);
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
    public function refund_storage($id,$admin_id,$admin_user){
        $purchase = DB::name("purchase")->where("id",intval($id))->find();
        if($purchase['status'] !=3){
            return json(["code" => -1, "msg" => '入库失败！', "data" => '']);
        }
        $purchase_store = db::name("purchase_store")->where("purchase_id",intval($id))->where("status",1)->select();
        $db = db::name("shop_item");

        $db->startTrans();
        try{
            foreach($purchase_store as $k =>$v){
                $shop_item = db::name("shop_item")->where("shop_id",$v['shop_id'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->find();
                if($shop_item < $v['num']){
                    $db->rollback();
                    return json(["code" => -1, "msg"=>"{".$v['item_name']."}商品库存不足", "data" => '']);
                }
                $number = $v['num'];
                $purchase_price = db::name("purchase_price")
                    ->where("shop_id",$v['shop_id'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->order("time","desc")->select();
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
                db::name("shop_item")->where("shop_id",$v['shop_id'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->setDec("stock",$v['num']);
                $update_store['reverse_purchase_id'] = $admin_id;
                $update_store['reverse_purchase_user'] = $admin_user;
                $update_store['reverse_purchase_time'] = time();
                $update_store['status'] = 2;
                db::name("purchase_store")->where("id",$v["id"])->update($update_store);
                db::name("purchase_item")->where("id",$v['pd_id'])->setInc("s_num",$v['num']);
            }
            DB::name("purchase")->where("id",intval($id))->update(['status'=>1]);
            $db->commit();
            return json(["code" => 200, "msg" => '反入库成功', "data" => '']);
        } catch (Exception $e) {
            $db->rollback();
            return json(["code" => -1, "msg" => '操作失败，请联系管理员！', "data" => '']);
        }
    }
}
