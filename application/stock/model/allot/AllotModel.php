<?php
// +----------------------------------------------------------------------
// | 服务卡模型
// +----------------------------------------------------------------------
namespace app\stock\model\allot;
use app\stock\model\purchase\PurchaseItemModel;
use think\Model;
use think\Db;
class AllotModel extends Model {
    protected $table = 'ddxm_allot';

    // 查询 采购单 列表
    public function getList($map,$Nowpage,$limits){

        return $this
            ->alias('a')
            ->field('a.sn,a.id,s.name as out_shop_name,a.out_admin_user,a.out_time,s2.name as in_shop_name,a.in_admin_user,a.in_time,a.status,
            a.remark,a.create_time,a.creator')
            ->page($Nowpage,$limits)
            ->join('shop s','s.id = a.out_shop')
            ->join('shop s2','s2.id = a.in_shop')
            ->where($map)
            ->order('a.id desc')
            ->select()
            ->append(['item']);
    }

    // 采购单列表---》对应 采购单 商品明细
    public function getItemAttr($val,$data){

        $id = $data['id'];
        $allotItemModel = new AllotItemModel();
        return $allotItemModel->getAllotIdList($id);
    }

    //查询 调拨单 列表  总长度
    public function getListCount($map){
        return $this
            ->alias('a')
            ->where($map)->count();
    }

    //删除
    public function del($id){
        $allot['del'] = 2;
        $res =$this->where("id",$id)->update($allot);
        if($res){
            return json(['code'=>200,"msg"=>"删除成功",'data'=>""]);
        }
    }

    // 查询 采购单 列表
    public function findId($id){

        return $this
            ->alias('a')
            ->field('a.sn,a.id,a.out_shop,s.name as out_shop_name,a.out_admin_user,a.out_time,s2.name as in_shop_name,a.in_shop,a.in_admin_user,a.in_time,a.status,
            a.remark,a.create_time,a.creator')
            ->join('shop s','s.id = a.out_shop')
            ->join('shop s2','s2.id = a.in_shop')
            ->where('a.id',$id)
            ->find()
            ->append(['item']);
    }

    // 发货
    public function out_shop($id,$admin_id,$admin_nickname){
        $allot = $this->where("id",$id)->find();
        if(!$allot){
            return json(['code'=>-1,"msg"=>"调拨单不存在","data"=>""]);
        }
        if($allot['status'] !=0){
            return json(['code'=>-1,"msg"=>"系统繁忙，请稍后再试","data"=>""]);
        }
        $allot_item = db::name("allot_item")->where("allot_id",$id)->select();
        if(!$allot_item){
            return json(['code'=>-1,"msg"=>"系统错误","data"=>""]);
        }
        $db = db::name("shop_item");
        $db->startTrans();

        try{
            foreach($allot_item as $k =>$v){
                $number = $v['num'];
                $shop_item = db::name("shop_item")
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->where("shop_id",$allot['out_shop'])
                    ->find();
                if($number >$shop_item['stock']){
                    $db->rollback();
                    return json(['code'=>-1,"msg"=>$v["item"]."商品库存不足,调拨失败",'data'=>""]);
                }
                $update_shop_item['stock'] = $shop_item['stock'] - $number;
                $update_shop_item['stock_ice'] = $shop_item['stock_ice'] + $number;
                db::name("shop_item")
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->where("shop_id",$allot['out_shop'])->update($update_shop_item);

                $purchase_price = db::name("purchase_price")
                    ->where("item_id",$v['item_id'])
                    ->where("shop_id",$allot['out_shop'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->where("stock",">",0)
                    ->order("time","asc")->select();
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
                    db::name("deposit")->insert($deposit);
                    DB::name("purchase_price")->where("id",$val['id'])->update($purchase);
                    if($number ==0){
                        break;
                    }
                }
            }
            $update_allot['status'] = 1;
            $update_allot['out_admin_user'] =$admin_nickname;
            $update_allot['out_admin_id'] =$admin_id;
            $update_allot['out_time'] = time();
            $update_allot['in_time'] = strtotime('+1 day');
            DB::name("allot")->where("id",$id)->update($update_allot);
            $db->commit();
            return json(['code'=>200,"msg"=>"发货成功","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            return json(['code'=>-1,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }

    // 确认收货
    public function confirm_shop($id,$admin_id,$admin_nickname){
        $allot = db::name("allot")->where("id",$id)->find();
        $db = db::name("allot");
        $db->startTrans();
        $allot_item = Db::name("allot_item")->where("allot_id",$id)->select();

        try{
            foreach($allot_item as $k =>$v){
                $stock_ice = db::name("shop_item")
                    ->where("shop_id",$allot['out_shop'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->value("stock_ice");
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
                    db::name("shop_item")->where("shop_id",$allot['out_shop'])
                        ->where("item_id",$v['item_id'])
                        ->where("attr_ids",$v['attr_ids'])
                        ->setDec("stock_ice",$v1['num']);
                    //判断调入门店是否存在这个
                    $in_shop_item = db::name("shop_item")
                        ->where("shop_id",$allot['in_shop'])
                        ->where("item_id",$v['item_id'])
                        ->where("attr_ids",$v['attr_ids'])
                        ->find();
                    //存在进行修改
                    if($in_shop_item){
                        db::name("shop_item")
                            ->where("shop_id",$allot['in_shop'])
                            ->where("item_id",$v['item_id'])
                            ->where("attr_ids",$v['attr_ids'])
                            ->setInc("stock",$v1['num']);
                        // 不存在进行添加
                    }else{
                        $in_shop_item['shop_id'] = $allot['in_shop'];
                        $in_shop_item['item_id'] = $v['item_id'];
                        $in_shop_item['attr_ids'] = $v['attr_ids'];
                        $in_shop_item['stock']   = $v1['num'];
                        db::name("shop_item")->insert($in_shop_item);
                    }
                    $purchase['shop_id'] = $allot['in_shop'];
                    $purchase['type'] =2;
                    $purchase['pd_id'] = $allot['id'];
                    $purchase['item_id'] = $v['item_id'];
                    $purchase['attr_ids'] = $v['attr_ids'];
                    $purchase['md_price'] = $shop_item["md_price"];
                    $purchase['store_cose'] = $shop_item['store_cose'];
                    $purchase['stock'] = $v1['num'];
                    $purchase['time'] = time();
                    DB::name("purchase_price")->insert($purchase);
                }
            }
            $update_allot['in_admin_id'] = $admin_id;
            $update_allot['in_admin_user'] = $admin_nickname;
            $update_allot['in_time'] = time();
            $update_allot["status"] = 2;
            db::name("allot")->where("id",$id)->update($update_allot);
            $db->commit();
            return json(['code'=>200,"msg"=>"成功收货","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            return json(['code'=>-1,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
        }
    }

    //取消发货
    public function shop_cancel($id){
        $allot = Db::name("allot")->where("id",$id)->find();
        if($allot['status'] !=1){
            return json(['code'=>-1,"msg"=>"数据错误",'data'=>""]);
        }
        $allot_item = db::name("allot_item")->where("allot_id",$id)->select();
        $db = db::name("allot");

        $db->startTrans();
        try{
            foreach($allot_item as $k => $v){
                $stock_ice = db::name("shop_item")
                    ->where("shop_id",$allot['out_shop'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->value("stock_ice");
                if($stock_ice <$v['num']){
                    $db->rollback();
                    return json(['code'=>-1,"msg"=>"冻结库存错误,请稍后再试","data"=>""]);
                }
                db::name("shop_item")
                    ->where("shop_id",$allot['out_shop'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->setDec("stock_ice",$v['num']);
                db::name("shop_item")->where("shop_id",$allot['out_shop'])
                    ->where("item_id",$v['item_id'])
                    ->where("attr_ids",$v['attr_ids'])
                    ->setInc("stock",$v['num']);
//                $log->record("调拨单取消发货",0,"商品:".$v['item']."/数量:".$v['num']);
            }
            $deposit = db::name("deposit")
                ->where("allot_id",$id)
                ->where("status",1)->select();
            foreach($deposit as $key => $val){
                $purchase_price = DB::name("purchase_price")->where("id",$val['p_id'])->find();
                $update_p['stock'] = $purchase_price['stock'] + $val['num'];
                $update_p['type'] = 2;
                $update_p['time'] = time();
                db::name("deposit")->where("id",$val['id'])->update(['status'=>2]);
                db::name("purchase_price")->where("id",$val['p_id'])->update($update_p);
            }
            $update_allot['status'] = 0;
            Db::name("allot")->where("id",$id)->update($update_allot);
//            $time = date("Y-m-d H:i:s",time());
//            $log->record_insert("调拨单取消发货",0, $name.":".$time."取消了 id:".intval($res['id'])."商品".$allot["out_amdin_user"]."的发货.原因：".$res['value']);
            $db->commit();
            return json(['code'=>200,"msg"=>"已取消","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            return json(['code'=>-1,"msg"=>"系统错误,请稍后重试!","data"=>$error]);
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
            $update_allot['in_admin_id'] = session("admin_user_auth")['uid'];
            $update_allot['in_admin_user'] = Db::name("admin")->where("userid",intval(session("admin_user_auth")['uid']))->value("nickname");
            $update_allot['in_time'] = time();
            $update_allot["status"] = 2;
            db::name("allot")->where("id",$id)->update($update_allot);
            $db->commit();
            return json(['result'=>true,"msg"=>"成功收货","data"=>""]);
        } catch (Exception $e) {
            $db->rollback();
            $error = $e->getMessage();
            dump($error);die;
        }
    }
}
