<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\admin\model\allot;
use app\admin\model\Adminlog;
use think\Exception;
use think\Model;
use think\Db;
class AllotModel extends Model {
    protected $table = 'ddxm_allot';
    public function allot_index($res,$where){

        $allot = null;
        if ( isset($res['search']) && $res['search'] != '' )
        {
            $where[] = ['b.item','like','%'.$res['search'].'%'];
        }
        $allot = $this ->alias('a')
            ->join('allot_item b','a.id=b.allot_id')
            ->where($where)
            ->field('a.*,sum(b.num) as all_num')
            ->page($res['page'],$res['limit'])
            ->group('a.id')
            ->order("a.create_time","desc")->select();
        foreach($allot as $k=>$v){
    		$out_shop = Db::name("shop")->where("id",$v['out_shop'])->value("name");
    		$in_shop = Db::name("shop")->where("id",$v['in_shop'])->value("name");
    		$create_time = date('Y-m-d H:i:s',$v['create_time']);
    		$out_time  = date("Y-m-d H:i:s",$v["out_time"]);
    		$in_time  = date("Y-m-d H:i:s",$v["in_time"]);
    		$all_num = $v['all_num'];
    		$data[$k]['message']="
    		<p>订单号：{$v['sn']}</p>
    		<p>调拨人员：{$v['creator']}</p>
    		<p>调出仓库：{$out_shop}</p>
    		<p>调拨时间：{$create_time}</p>	
    		<p>调拨总量：{$all_num}</p>	
    		";
    		$data[$k]['out_message']= $v['out_admin_id']?"
    		<p>发货人员：{$v['out_admin_user']}</p>
    		<p>发货时间：{$out_time}</p>
    		":"未发货";
    		$data[$k]['in_message']= $v['in_admin_id']?"
    		<p>所入仓库：{$in_shop}仓库</p>
    		<p>入库人员：{$v['in_admin_user']}</p>
    		<p>入库时间：{$in_time}</p>
    		":"
            <p>所入仓库：{$in_shop}仓库</p>
    		<p>入库人员：暂未入库</p>
    		<p>入库时间：暂未入库</p>";
    		$item = $this->item_message($v['id']);
            $data[$k]['item'] = $item['item'];
            $data[$k]['barcode'] = $item['barcode'];
            $data[$k]['number'] = $item['number'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['remark'] = $v['remark'];
    	}
    	return $data;
    }
    public function item_message($id){
    	$item = DB::name("allot_item")->where("allot_id",$id)->select();
        $data['item'] = "";
        $data['number'] = "";
        $data['barcode'] = "";
        foreach($item as $k=>$v){
            $data['item'] .= "<p>{$v['item']}</p>";
            $data['number'] .= "<p>{$v['num']}</p>";
            $item_id = $v['item_id'];
//            $bar_code ="<p>{$v['barcode']}</p>";
            $bar_code = Db::name('item')->where('id',$item_id)->value('bar_code');
            $data['barcode'] .= "<p>$bar_code</p>";
        }
        return $data;
    }
    // 新增 调拨单
    public function allot_add($r){
    	$res = $r['data'];
    	$allot_in  = intval($res['allot_in']);
    	$allot_out = intval($res['allot_out']);
    	$count = intval($res['count']);
    	$db = db::name("allot");
    	$db_count = Db::name("allot")->count();

    	/*$amount = 0;
    	*/
    	// 暂无
    	$db->startTrans();
    	try{
	    	$allot = [
	    		"sn" => "DB".date("Ymd").sprintf("%05d",$db_count+1),
	    		"out_shop" => $allot_out,
	    		"out_admin_id"=>0,
	    		"out_admin_user"=>"",
	    		"in_shop"=>$allot_in,
	    		"in_admin_id"=>0,
	    		"in_admin_user"=>"",
	    		"status"=>0,
	    		"remark" =>$res['remarks'],
	    		"create_time"=>time(),
	    		"type"=>1,
	    		"creator"=> Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
	    		"creator_id"=>session("admin_user_auth")['uid'],
	    		/*"amount"=>*/
	    	];
	    	$id = $db->insertGetId($allot);
	    	for($i=0;$i<$count;$i++){
	    		$item =[
	    			"allot_id"=>$id,
	    			"item_id"=>$res['item_id'][$i],
	    			"item"=>$res['item_name'][$i],
	    			"num"=>$res['purchase_number'][$i],
	    			"remark"=>$res['remake'][$i],
	    			"barcode"=>$res['bar_code'][$i],
	    		];
	    		db::name("allot_item")->insert($item);
	    	}
            $db->commit();
	    	return json(['result'=>true,"msg"=>"新增成功","data"=>""]);
    	} catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    // 修改 调拨单
    public function allot_up($r){
        $res = $r['data'];


        $allot_id  = intval($res['allot_id']);//调拨单 ID
        $allot_in  = intval($res['allot_in']);//调入仓库 DI
        $count = intval($res['count']);
        $db = db::name("allot");
        $db_count = Db::name("allot")->count();
        /*$amount = 0;
        */
        // 暂无
        $db->startTrans();
        try{
            $allot = [
                "remark" =>$res['remarks'],
                "creator"=> Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
                "creator_id"=>session("admin_user_auth")['uid'],
                "in_shop"=>$allot_in,
                /*"amount"=>*/
            ];

             $db->where('id',$allot_id)->update($allot);

             db::name('allot_item')->where('allot_id',$allot_id)->delete();
            for($i=0;$i<$count;$i++){
                $item =[
                    "allot_id"=>$allot_id,
                    "item_id"=>$res['item_id'][$i],
                    "item"=>$res['item_name'][$i],
                    "num"=>$res['purchase_number'][$i],
                    "remark"=>$res['remake'][$i],
                ];
                db::name("allot_item")->insert($item);
            }
            $db->commit();
            return json(['result'=>true,"msg"=>"编辑成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    public function out_shop_list($id){
        $data = $this->table($this->table)->where("id",$id)->field("sn,out_shop as shop_name,in_shop as shop_names,creator,create_time as time,remark,id")->find();
        return $data;
    }
    // 获取门店名称
    public function getShopNameAttr($val){
        return db::name("shop")->where("id",$val)->value("name");
    }
    // 获取门店名称
    public function getShopNamesAttr($val){
        return db::name("shop")->where("id",$val)->value("name");
    }
    //时间转换
    public function getTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "暂无数据";
    }
    public function out_shop($id){
        $allot = DB::name("allot")->where("id",$id)->find();
        if(!$allot){
            return json(['result'=>false,"msg"=>"调拨单不存在","data"=>""]);
        }
        if($allot['status'] !=0){
            return json(['result'=>false,"msg"=>"系统繁忙，请稍后再试","data"=>""]);
        }
        $allot_item = db::name("allot_item")->where("allot_id",$id)->select();
        if(!$allot_item){
            return json(['result'=>false,"msg"=>"系统错误","data"=>""]);
        }
        $db = db::name("shop_item");
        $db->startTrans();
        
        try{
            foreach($allot_item as $k =>$v){
                $number = $v['num'];
                $shop_item = db::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$allot['out_shop'])->find();
                if($number >$shop_item['stock']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>$v["item"]."商品库存不足,调拨失败",'data'=>""]);
                }
                $update_shop_item['stock'] = $shop_item['stock'] - $number;
                $update_shop_item['stock_ice'] = $shop_item['stock_ice'] + $number;
                $res = db::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$allot['out_shop'])->update($update_shop_item);
                if(empty($res))
                {
                    $db->rollback();
                    return json(['result'=>false,"msg"=>$v["item"]."操作失败-219",'data'=>""]);
                }

                $purchase_price = db::name("purchase_price")->where("item_id",$v['item_id'])->where("shop_id",$allot['out_shop'])->where("stock",">",0)->order("time","asc")->select();
                foreach($purchase_price as $key => $val){
                    $purchase['type'] =2;
                    if($val['stock'] >$number){
                        $purchase['stock'] = $val['stock']-$number;
                        $deposit['num'] = $number;
                        $number = 0;
                    }else{
                        $purchase['stock'] = 0;
                        $number  = $number -$val['stock'];
                        $deposit['num'] = $val['stock'];
                    }
                    $deposit['p_id'] = $val['id'];
                    $deposit['pd_id'] = $v['id'];
                    $deposit['allot_id'] = $v['allot_id'];
                    /*$d_number++;*/
                    $purchase['time'] = time();
                    $res = db::name("deposit")->insert($deposit);
                    if(empty($res))
                    {
                        $db->rollback();
                        return json(['result'=>false,"msg"=>$v["item"]."操作失败-243",'data'=>""]);
                    }

                    $res = DB::name("purchase_price")->where("id",$val['id'])->update($purchase);
                    if(empty($res))
                    {
                        $db->rollback();
                        return json(['result'=>false,"msg"=>$v["item"]."操作失败-250",'data'=>""]);
                    }

                    if($number ==0){
                        break;
                    }
                }
            }
            $update_allot['status'] = 1;
            $update_allot['out_admin_user'] =Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
            $update_allot['out_admin_id'] = session("admin_user_auth")['uid'];
            $update_allot['out_time'] = time();
            $update_allot['in_time'] = strtotime('+1 day');
            $res = DB::name("allot")->where("id",$id)->update($update_allot);
            if(empty($res))
            {
                $db->rollback();
                return json(['result'=>false,"msg"=>$v["item"]."操作失败-267",'data'=>""]);
            }

            $db->commit();
            return json(['result'=>true,"msg"=>"发货成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    public function allot_confirm_list($id){
        $allot = DB::name("allot")->where("id",$id)->find();
        $data = db::name("allot_item")
        ->alias("a")
        ->where("a.allot_id",$id)
        /*->where("s.shop_id",$allot['in_shop'])*/
        ->field("a.item_id,a.item,a.num,i.bar_code")
        ->withAttr("bar_code",function($value,$data){
            if($data['bar_code']){
                return $data['bar_code'];
            }else{
                return "暂无条形码";
            }
        })
        ->join("item i","a.item_id = i.id")
        ->select();
        foreach($data as $k=>$v){
            $stock  = DB::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$allot['in_shop'])->value("stock");
            $data[$k]['stock'] = $stock?$stock:0;
        }
        $count = db::name("allot_item")->where("allot_id",$id)->count();
        return json(['code'=>0,"data"=>$data,"count"=>$count]);
    }
    public function confirm_shop($id){
        $allot = db::name("allot")->where("id",$id)->find();
        $db = db::name("allot");
        $log = new Adminlog();
        $db->startTrans();
        $allot_item = Db::name("allot_item")->where("allot_id",$id)->select();

        //确认前判断是否存在盘盈/盘亏订单
        $res = $this->isStockException($allot['in_shop'],array_column($allot_item,'item_id'));

        try{
            foreach($allot_item as $k =>$v){
                $stock_ice = db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->value("stock_ice");
                if($stock_ice <$v['num']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>"冻结库存错误,请稍后再试","data"=>""]);
                }
                $where = [];
                $where[] = ['pd_id','eq',$v['id']];
                $where[] = ['allot_id','eq',$v['allot_id']];
                $deposit = db::name("deposit")->where("pd_id",$v['id'])->where("status",1)->select();
                foreach ( $deposit as $k1=>$v1 ){
                    $shop_item = db::name("purchase_price")->where("id",$v1['p_id'])->find();
                    $res = db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->setDec("stock_ice",$v1['num']);
                    if(empty($res))
                    {
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"操作失败-329","data"=>""]);
                    }

                    //判断调入门店是否存在这个
                    $in_shop_item = db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->find();
                    //存在进行修改
                    if($in_shop_item){
                        $res = db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->setInc("stock",$v1['num']);
                        // 不存在进行添加
                    }else{
                        $in_shop_item['shop_id'] = $allot['in_shop'];
                        $in_shop_item['item_id'] = $v['item_id'];
                        $in_shop_item['stock']   = $v1['num'];
                        $res = db::name("shop_item")->insert($in_shop_item);
                    }
                    if(empty($res))
                    {
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"操作失败-346","data"=>""]);
                    }

                    $purchase['shop_id'] = $allot['in_shop'];
                    $purchase['type'] =2;
                    $purchase['pd_id'] = $allot['id'];
                    $purchase['item_id'] = $v['item_id'];
                    $purchase['md_price'] = $shop_item["md_price"];
                    $purchase['store_cose'] = $shop_item['store_cose'];
                    $purchase['stock'] = $v1['num'];
                    $purchase['time'] = time();
                    $res = DB::name("purchase_price")->insert($purchase);
                    if(empty($res))
                    {
                        $db->rollback();
                        return json(['result'=>false,"msg"=>"操作失败：purchase_price","data"=>""]);
                    }

                    $log->record_insert("调拨单入库成功",0,"商品:".$v['item']."/数量:".$v1['num']);
                }
            }
            $update_allot['in_admin_id'] = session("admin_user_auth")['uid'];
            $update_allot['in_admin_user'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
            $update_allot['in_time'] = time();
            $update_allot["status"] = 2;
            $res = db::name("allot")->where("id",$id)->update($update_allot);
            if(empty($res))
            {
                $db->rollback();
                return json(['result'=>false,"msg"=>"操作失败：allot","data"=>""]);
            }
            $db->commit();
            return json(['result'=>true,"msg"=>"成功收货","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }


    //判断库存是否存在异常（采购、调拨、直接入库必须检查当前门店是否存在当前商品对应的盘盈盘亏单）
    public function isStockException($shop_id,$goods_ids)
    {
        $where[] = ['shop_id','eq',$shop_id];
        $where[] = ['item_id','in',$goods_ids];
        $where[] = ['status','eq',1];

        $list = db::name('stock')->alias('s')
                ->join('stock_item si','s.id = si.stock_id')
                ->where($where)
                ->find();

        if(!empty($list))
        {
           return_error('存在未处理的盘盈/盘亏单');
           exit;
        }

        return true;
    }

    //取消发货
    public function shop_cancel($res){
        $allot = Db::name("allot")->where("id",intval($res['id']))->find();
        if($allot['status'] !=1){
            return json(['result'=>false,"msg"=>"数据错误",'data'=>""]);
        }
        $allot_item = db::name("allot_item")->where("allot_id",intval($res['id']))->select();
        $db = db::name("allot");
        $log = new Adminlog();
        $db->startTrans();
        try{
            foreach($allot_item as $k => $v){
                $stock_ice = db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->value("stock_ice");
                if($stock_ice <$v['num']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>"冻结库存错误,请稍后再试","data"=>""]);
                }
                db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->setDec("stock_ice",$v['num']);
                db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->setInc("stock",$v['num']);
//                $log->record("调拨单取消发货",0,"商品:".$v['item']."/数量:".$v['num']);
            }
            $deposit = db::name("deposit")->where("allot_id",intval($res['id']))->where("status",1)->select();
            foreach($deposit as $key => $val){
                $purchase_price = DB::name("purchase_price")->where("id",$val['p_id'])->find();
                $update_p['stock'] = $purchase_price['stock'] + $val['num'];
                $update_p['type'] = 2;
                $update_p['time'] = time();
                db::name("deposit")->where("id",$val['id'])->update(['status'=>2]);
                db::name("purchase_price")->where("id",$val['p_id'])->update($update_p);
            }
            $update_allot['status'] = 0;
            $name = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
            Db::name("allot")->where("id",intval($res['id']))->update($update_allot);
            $time = date("Y-m-d H:i:s",time());
            $log->record_insert("调拨单取消发货",0, $name.":".$time."取消了 id:".intval($res['id'])."商品".$allot["out_amdin_user"]."的发货.原因：".$res['value']);
            $db->commit();
            return json(['result'=>true,"msg"=>"已取消","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['result'=>false,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    public function del($res){
        $id = intval($res['id']);
        $allot['del'] = 2;
        $res =db::name("allot")->where("id",$id)->update($allot);
        if($res){
            return json(['result'=>true,"msg"=>"删除成功",'data'=>""]);
        }
    }
    public function allot_timer($id){
        $allot = db::name("allot")->where("id",$id)->find();
        $db = db::name("allot");
        $log = new Adminlog();
        $db->startTrans();
        $allot_item = Db::name("allot_item")->where("allot_id",$id)->select();

        try{
            foreach($allot_item as $k =>$v){
                $stock_ice = db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->value("stock_ice");
                if($stock_ice <$v['num']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>"冻结库存错误,请稍后再试","data"=>""]);
                }
                $where = [];
                $where[] = ['pd_id','eq',$v['id']];
                $where[] = ['allot_id','eq',$v['allot_id']];
                $deposit = db::name("deposit")->where("pd_id",$v['id'])->where("status",1)->select();
                foreach ( $deposit as $k1=>$v1 ){
                    $shop_item = db::name("purchase_price")->where("id",$v1['p_id'])->find();
                    db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->setDec("stock_ice",$v1['num']);
                    //判断调入门店是否存在这个
                    $in_shop_item = db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->find();
                    //存在进行修改
                    if($in_shop_item){
                        db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->setInc("stock",$v1['num']);
                        // 不存在进行添加
                    }else{
                        $in_shop_item['shop_id'] = $allot['in_shop'];
                        $in_shop_item['item_id'] = $v['item_id'];
                        $in_shop_item['stock']   = $v1['num'];
                        db::name("shop_item")->insert($in_shop_item);
                    }
                    $purchase['shop_id'] = $allot['in_shop'];
                    $purchase['type'] =2;
                    $purchase['pd_id'] = $allot['id'];
                    $purchase['item_id'] = $v['item_id'];
                    $purchase['md_price'] = $shop_item["md_price"];
                    $purchase['store_cose'] = $shop_item['store_cose'];
                    $purchase['stock'] = $v1['num'];
                    $purchase['time'] = time();
                    DB::name("purchase_price")->insert($purchase);
                    $log->record_insert("调拨单入库成功",0,"商品:".$v['item']."/数量:".$v1['num']);
                }
            }
//            $update_allot['in_admin_id'] = '';
//            $update_allot['in_admin_user'] = '';
//            $update_allot['in_time'] = time();
//            $update_allot["status"] = 2;
//            $res = db::name("allot")->where("id",$id)->fetchSql()->update($update_allot);
            $db->commit();
            return json(['result'=>true,"msg"=>"成功收货","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            dump($error);die;
        }
    }
}
