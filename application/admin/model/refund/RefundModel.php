<?php
// +----------------------------------------------------------------------
// | 后台用户管理
// +----------------------------------------------------------------------
namespace app\admin\model\refund;
use app\admin\service\User;
use app\admin\model\Adminlog;
use think\Model;
use think\Db;
class RefundModel extends Model
{
    protected $autoWriteTimestamp = true;
    protected $table = 'ddxm_allot';
    /**
     * 记录日志
     * @param type $message 说明
     */
    public function index($res){
        foreach($res as $k=>$v){
            $shop = db::name("shop")->where("id",$v['shop_id'])->value("name");
            $create_time =date("Y-m-d H:i:s",$v['create_time']);
            $data[$k]['message'] =  "
            <p>订单号:{$v['sn']}</p>
            <p>供货商:{$v['supplier']}</p>
            <P>退货仓库:{$shop}</p>
            <p>退货人:{$v['create_user']}</p>
            <p>退货时间:{$create_time}</p>
            ";
            $item = $this->getItem($v['id']);
            $data[$k]['item_name'] =$item['item_name'];
            $data[$k]['number'] = $item['number'];
            $data[$k]['price'] = $item['price'];
            $data[$k]['amount'] = $item['amount'];
            $data[$k]['id'] = $v['id'];
            $data[$k]['status'] = $v['status'];
            $out_time = date("Y-m-d H:i:s",$v['out_time']);
            $data[$k]['out_message'] =!empty($v['out_stock_id'])?"<p>出库人员:{$v['out_stock_user']}</p><p>出库时间:{$out_time}</p>":"未出库";
        }
        return $data;
    }
    public function getItem($res){
        $data = DB::name("reject_item")->where("reject_id",$res)->select();
        foreach($data as $k=>$v){
            $item_name = db::name("item")->where("id",$v['item_id'])->value("title");
            $item['item_name'] .="<p>{$item_name}</p>";
            $item['number'] .="<p>{$v['num']}</p>";
            $item['price'] .="<p>{$v['cost_price']}</p>";
            $amount = $v['cost_price'] * $v['num'];
            $amount = sprintf("%.2f",$amount); 
            $item['amount'] .="<p>{$amount}</p>";
        }
       /* dump($item);*/
        return $item;
    }
    public function add_refund($data){
        $res = $data['data'];
        $count =db::name("reject")->count();
        $count ++;
        $reject = [
            "shop_id"=>$res['shop'],
            "supplier_id"=>$res['supplier'],
            "supplier" =>db::name("supplier")->where("id",intval($res['supplier']))->value("supplier_name"),
            "sn" =>"IR".date("Ymd").sprintf("%05d",$count),
            "out_stock_user"=>"",
            "out_stock_id"=>0,
            "out_time"=>0,
            "create_id"=>session("admin_user_auth")['uid'],
            "create_user"=>Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname"),
            "create_time"=>time(),
            "status"=>1,
            "amount"=>$res['item_number'],
            "remark"=>$res['remarks'],
        ];
        $db = db::name("reject");
        $db->startTrans();
        $id = $db->insertGetId($reject);
        try{
            $result = "";
            for($i=0;$i<$res['count'];$i++){
                if($res['r_stock'][$i] > $res['stock'][$i]){
                    $result =$res['item_name']."  退货数量大于库存";
                    break;
                }
                if($res['r_price'][$i] > $res['price'][$i]){
                    $result =$res['item_name']."  退款金额大于成本价格";
                    break;
                }
                $item =[
                    "reject_id"=>$id,
                    "item_id"=>$res["item_id"][$i],
                    "num"=>$res['r_stock'][$i],
                    "cost_price"=>$res['r_price'][$i],
                    "remark"=>"",
                ];
                db::name("reject_item")->insert($item);
            }
            if($result){                
                $db->rollback();
                return json(['result'=>true,"msg"=>$result,"data"=>""]);
            }else{
                $db->commit();
                return json(['result'=>true,"msg"=>"添加成功","data"=>""]);
            }
        }catch (Exception $e) {
            $db->rollback();
            return json(['result'=>false,"msg"=>"系统繁忙,请稍后重试!","data"=>""]);
        }
    }
    public function out_shop($res){
        $id =$res['id'];
        $reject = db::name("reject")->where("id",$id)->find();
        $item = db::name("reject_item")->where("reject_id",$id)->select();
        $db = db::name("reject");
        Db::startTrans();
        try{
            $msg = "";
            foreach($item as $k=>$v){
                $stock = Db::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$reject['shop_id'])->value("stock");                
                $item_name = db::name("item")->where("id",$v['item_id'])->value("title");
                if($stock<$v['num']){
                    $db->rollback();
                    return json(['result'=>false,"msg"=>$item_name." 商品库存不足,退货失败",'data'=>""]);
                }
                $shop_item['stock'] = $stock - $v['num'];
                $number = $v["num"];
                $purchase_price = Db::name("purchase_price")->where("item_id",$v['item_id'])->where("shop_id",$reject['shop_id'])->where("stock",">",0)->order("time","asc")->select();
                foreach($purchase_price as $key => $val){
                    $purchase['type'] =5;
                    if($val['stock'] >$number){
                        $purchase['stock'] = $val['stock']-$number;
                        $reject_purchase['num'] = $number;
                        $number = 0;
                    }else{
                        $purchase['stock'] = 0;
                        $number  = $number -$val['stock'];
                        $reject_purchase['num'] = $val['stock'];
                    }
                    $reject_purchase['p_id'] = $val['id'];
                    $reject_purchase['r_id'] = $v['id'];
                    $reject_purchase['reject_id'] = $id;
                    $reject_purchase['time'] = time();
                    $res = db::name("reject_purchase")->insert($reject_purchase);
                    if(!$res)
                    {
                        $msg = 'reject_purchase';
                        break;
                    }
                    $purchase['time'] = time();
                    $res = DB::name("purchase_price")->where("id",$val['id'])->update($purchase);
                    if(!$res)
                    {
                        $msg = 'purchase_price';
                        break;
                    }
                    if($number ==0){
                        break;
                    }
                }
                $res = Db::name("shop_item")->where("item_id",$v['item_id'])->where("shop_id",$reject['shop_id'])->update($shop_item);
                if(!$res)
                {
                    $msg = 'shop_item';
                    break;
                }
            }
            if($msg){                
                $db->rollback();
                return json(['result'=>true,"msg"=>$msg,"data"=>""]);
            }else{
                $update_reject['status'] = 2;
                $update_reject['out_stock_user'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
                $update_reject['out_stock_id'] = session("admin_user_auth")['uid'];
                $update_reject['out_time'] =time();
                $db->where("id",$id)->update($update_reject);
                $db->commit();
                return json(['result'=>true,"msg"=>"添加成功","data"=>""]);
            }
        }catch (Exception $e){
            $db->rollback();
            return json(['result'=>false,"msg"=>"系统繁忙，请稍后重试!","data"=>""]);
        }
    }
}