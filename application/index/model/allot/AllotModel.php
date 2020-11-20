<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\index\model\allot;
use app\index\model\Adminlog;
use think\Model;
use think\Controller;
use think\Db;
use think\Query;
use think\Validate;
use think\Request;
use think\db\Where;
class AllotModel extends Model {
    protected $table = 'ddxm_allot';
    public function allot_index($res,$shop_id){
        //调出仓库
        $whereor = [];
        $where = [];
        if(!empty($res['out_shop']) && !empty($res['in_shop'])){
            if((intval($res['in_shop']) == $shop_id) ||(intval($res['out_shop']) == $shop_id)){
                $where[] = ['out_shop','=',intval($res['out_shop'])];
                $where[] = ['in_shop','=',intval($res['in_shop'])];
            }else{
                $array['data'] = "";
                $array['count'] = 0;
                return $array;
            }
        }else if(!empty($res['out_shop'])){

            if(intval($res['out_shop']) == $shop_id){

                $where[] = ['out_shop','=',intval($res['out_shop'])];
            }else{
                $where[] = ['out_shop','=',intval($res['out_shop'])];
                $where[] = ['in_shop','=',$shop_id];
            }
        }else if(!empty($res['in_shop'])){
            if(intval($res['in_shop']) == $shop_id){
                $where[] = ['in_shop','=',intval($res['in_shop'])];
            }else{
                $where[] = ['in_shop','=',intval($res['in_shop'])];
                
                $where[] = ['out_shop','=',$shop_id];
            }
        }
        if(isset($res['time']) && !empty($res['time'])){
            if(isset($res['start_time']) && !empty($res['start_time'])){
                $where[] = [$res['time'],">",strtotime($res['start_time'])];
            }
            if(isset($res['end_time']) && !empty($res['end_time'])){
                $where[] = [$res['time'],"<",strtotime($res['end_time'])];
            }
        }
        // 状态
        if(isset($res['status']) && !empty($res['status'])){
            $where[] = ['status','=',intval($res['status'])];
        }
        //搜索框
        if(isset($res['search']) && !empty($res['search'])){
            $where[] = ['sn',"like","%{$res['search']}%"]; 
        }   
        if(!$where){
            $whereor[] = [ "in_shop|out_shop","=" , $shop_id];
        } 
        $where[] =["del","=",1];   
    	$allot = DB::name("allot")->where($where)->where($whereor)->order("create_time","desc")->page($res['page'],$res['limit'])->select();
        $count = Db::name("allot")->where($where)->where($whereor)->count();
    	foreach($allot as $k=>$v){
    		$out_shop = Db::name("shop")->where("id",$v['out_shop'])->value("name");
    		$in_shop = Db::name("shop")->where("id",$v['in_shop'])->value("name");
    		$create_time = date('Y-m-d H:i:s',$v['create_time']);
    		$out_time  = date("Y-m-d H:i:s",$v["out_time"]);
    		$in_time  = date("Y-m-d H:i:s",$v["in_time"]);
    		$data[$k]['message']="
    		<p>订单号：{$v['sn']}</p>
    		<p>调拨人员：{$v['creator']}</p>
    		<p>调出仓库：{$out_shop}</p>
    		<p>调拨时间：{$create_time}</p>	
    		";
    		$data[$k]['out_message']= $v['out_admin_id']?"
    		<p>发货人员：{$v['out_admin_user']}</p>
    		<p>发货时间：{$out_time}</p>
    		":"未发货";
    		$data[$k]['in_message']= $v['in_admin_id']?"
    		<p>所入仓库：{$in_shop}仓库</p>
    		<p>入库人员：{$v['in_admin_user']}</p>
    		<p>入库时间：{$in_time}</p>
    		":"未入库";
    		$item = $this->item_message($v['id']);
            $data[$k]['item'] = $item['item'];
            $data[$k]['number'] = $item['number'];
            $data[$k]['status'] = $v['status'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['out_shop'] = $v['out_shop'];
            $data[$k]['in_shop'] = $v['in_shop'];
            $data[$k]['del'] = $v['del'];
    	}
        $array['data'] = $data;
        $array['count'] = $count;
    	return $array;
    }
    public function item_message($id){
    	$item = DB::name("allot_item")->where("allot_id",$id)->select();
        $data['item'] = "";
        $data['number'] = "";
        foreach($item as $k=>$v){
            $data['item'] .= "<p>{$v['item']}</p>";
            $data['number'] .= "<p>{$v['num']}</p>";
        }
        return $data;
    }
    public function allot_add($r){
    	$res = $r['data'];
        if(empty($res)){
           return json(['code'=>"-3","msg"=>"未传入商品信息","data"=>""]);
        }
        /*if(empty($res['allot_in'])){
            return json(['code'=>"-3","msg"=>"调出仓库未知","data"=>""]);
        }*/
        if(empty($res['allot_in'])){
            return json(['code'=>"-3","msg"=>"调入仓库未知","data"=>""]);
        }
        if(empty($res['count'])){
            return json(['code'=>"-3","msg"=>"商品数量","data"=>""]);
        }
    	$allot_in  = intval($res['allot_in']);
    	$allot_out = intval($r['shop_id']);

    	$count = intval($res['count']);
    	$db = db::name("allot");
    	$db_count = Db::name("allot")->count();
    	/*$amount = 0;
    	*/
       /* exit;*/
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
	    		"creator"=> Db::name("shop_worker")->where("id",intval($res['worker_id']))->value("name"),
                "number"=>$count,
	    		"creator_id"=>intval($res['worker_id']),
	    		/*"amount"=>*/
	    	];
	    	$id = $db->insertGetId($allot);
	    	for($i=0;$i<$count;$i++){
	    		$item =[
	    			"allot_id"=>$id,
	    			"item_id"=>$res['item_id'][$i],
	    			"item"=>$res['item_name'][$i],
	    			"num"=>$res['purchase_number'][$i],
	    			"remark"=>$res['remark'][$i],
	    		];
	    		db::name("allot_item")->insert($item);
	    	}
            $db->commit();
	    	return json(['code'=>"200","msg"=>"新增成功","data"=>""]);
    	} catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['code'=>"500","msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    public function out_shop_list($id){
        $data = $this->table($this->table)->where("id",$id)->field("sn,out_shop as shop_name,creator,create_time as time,remark,id")->find();
        return $data;
    }
    // 获取门店名称
    public function getShopNameAttr($val){
        return db::name("shop")->where("id",$val)->value("name");
    }
    //时间转换
    public function getTimeAttr($val){
        if($val){
            return date("Y-m-d H:i:s",$val);
        }
        return "暂无数据";
    }
    public function out_shop($id,$worker_id){
        $allot = DB::name("allot")->where("id",$id)->find();
        if(!$allot){
            return json(['code'=>"-3","msg"=>"调拨单不存在","data"=>""]);
        }
        if($allot['status'] !=0){
            return json(['code'=>"-3","msg"=>"系统繁忙，请稍后再试","data"=>""]);
        }
        $allot_item = db::name("allot_item")->where("allot_id",$id)->select();
        if(!$allot_item){
            return json(['code'=>"500","msg"=>"系统错误","data"=>""]);
        }
        $db = db::name("shop_item");
        $db->startTrans();
        /*$d_number = 0;*/
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
                db::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$allot['out_shop'])->update($update_shop_item);

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
                   
                    $purchase['time'] = time();
                    db::name("deposit")->insert($deposit);
                    DB::name("purchase_price")->where("id",$val['id'])->update($purchase);
                    if($number ==0){
                        break;
                    }
                }
            }
            $update_allot['status'] = 1;
            $update_allot['out_admin_user'] =Db::name("shop_worker")->where("id",intval($worker_id))->value("name");
            $update_allot['out_admin_id'] = $worker_id;
            $update_allot['out_time'] = time();
            $update_allot['in_time'] = strtotime('+1 day');
            DB::name("allot")->where("id",$id)->update($update_allot);
            $db->commit();
            return json(['code'=>"200","msg"=>"发货成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['code'=>"500","msg"=>"系统错误,请稍后重试!","data"=>$error]);
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
        return json(['code'=>200,"data"=>$data,"count"=>$count]);
    }
    public function confirm_shop($res,$shop_id){
        $id = intval($res['id']);
        $worker_id = $res['worker_id'];
        $allot = db::name("allot")->where("id",$id)->find();
        if($shop_id !== $allot["in_shop"]){
            return json(['code'=>"-3","msg"=>"不是本店的调拨单","data"=>""]);
        }
        if($allot['status'] !==1){
            return json(['code'=>"-3","msg"=>"数据错误","data"=>""]);
        }
        $db = db::name("allot");
        $log = new Adminlog();
        $db->startTrans();
        $allot_item = Db::name("allot_item")->where("allot_id",$id)->select();
        try{
            foreach($allot_item as $k =>$v){
                $stock_ice = db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->value("stock_ice");
                if($stock_ice <$v['num']){
                    $db->rollback();
                    return json(['code'=>"-3","msg"=>"冻结库存错误,请稍后再试","data"=>""]);
                }
                $deposit = db::name("deposit")->where("pd_id",$v['id'])->where("status",1)->find();
                $shop_item = db::name("purchase_price")->where("id",$deposit['p_id'])->find();
                db::name("shop_item")->where("shop_id",$allot['out_shop'])->where("item_id",$v['item_id'])->setDec("stock_ice",$v['num']);
                //判断调入门店是否存在这个
                $in_shop_item = db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->find();
                //存在进行修改  
                if($in_shop_item){
                    db::name("shop_item")->where("shop_id",$allot['in_shop'])->where("item_id",$v['item_id'])->setInc("stock",$v['num']);
                // 不存在进行添加
                }else{
                    $in_shop_item['shop_id'] = $allot['in_shop'];
                    $in_shop_item['item_id'] = $v['item_id'];
                    $in_shop_item['stock']   = $v['num'];
                    db::name("shop_item")->insert($in_shop_item);
                }
                $purchase['shop_id'] = $allot['in_shop'];
                $purchase['type'] =2;
                $purchase['pd_id'] = $allot['id'];
                $purchase['item_id'] = $v['item_id'];
                $purchase['md_price'] = $shop_item["md_price"];
                $purchase['store_cose'] = $shop_item['store_cose'];
                $purchase['stock'] = $v['num'];
                $purchase['time'] = time();
                DB::name("purchase_price")->insert($purchase);
                $log->record_insert("调拨单入库成功",0,"商品:".$v['item']."/数量:".$v['num']);
            }
            $update_allot['in_admin_id'] = $worker_id;
            $update_allot['in_admin_user'] = Db::name("shop_worker")->where("id",intval($worker_id))->value("name");
            $update_allot['in_time'] = time();
            $update_allot["status"] = 2;
            db::name("allot")->where("id",$id)->update($update_allot);
            $db->commit();
            return json(['code'=>"200","msg"=>"成功收货","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['code'=>"500","msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    //取消发货
    public function shop_cancel($res){
        $allot = Db::name("allot")->where("id",intval($res['id']))->find();
        if($allot['status'] !=1){
            return json(['code'=>"-3","msg"=>"数据错误",'data'=>""]);
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
                $log->record_insert("调拨单取消发货",0,"商品:".$v['item']."/数量:".$v['num']);
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
            return json(['code'=>"200","msg"=>"已取消","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();  
            return json(['code'=>"-3","msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }
    public function del($res){
        $id = intval($res['id']);
        $allot['del'] = 2;
        $res =db::name("allot")->where("id",$id)->update($allot);
        if($res){
            return json(['code'=>"200","msg"=>"删除成功",'data'=>""]);
        }else{
            return json(['code'=>"500","msg"=>"系统错误",'data'=>""]);
        }
    }
}
