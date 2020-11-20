<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\items\ItemModel;
use app\mall\model\seckill\SeckillModel;
use app\mall\model\seckill\FlashSaleModel;
use think\Db;
/**
 * 秒杀设置
 */
class Seckill extends Adminbase
{
    /***
     * 秒杀列表
     */
    public function list1(){
        if ($this->request->isAjax()) {
            $ItemCategory = new FlashSaleModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 1);
            $where = [];
            $item_name = $this ->request ->param('name');
            $type = $this->request->param('type',2);
            if( $item_name ){
                $where[] = ['item_name','like',"%$item_name%"];
            }

            //已结束时间查询（精确到绝对时间）
            if(!empty(input('end_time')) and empty(input('start_time')))
            {
                $where[] = ['a.end_time','=', strtotime(input('end_time'))];
            }
            //以开始时间查询（精确到绝对时间）
            if(!empty(input('start_time')) and empty(input('end_time')))
            {
                $where[] = ['a.start_time','=', strtotime(input('start_time'))];
            }
            //开始时间与结束时间同时存在以区间查询
            if(!empty(input('end_time')) && !empty(input('start_time')))
            {
                $start_time = strtotime(input('start_time'));
                $end_time = strtotime(input('end_time'));
                $where[] = ['a.start_time','>=',$start_time];
                $where[] = ['a.end_time','<=',$end_time];
            }
//        dump($where);die;

            $where[] = ['a.status','neq',0];
            $where[] = ['a.type','eq',$type];
            $where[] = ['b.status','eq',1];
            $list = $ItemCategory
                ->alias('a')
                ->join('flash_sale_attr b','a.id=b.flash_sale_id')
                ->group('b.item_id,b.flash_sale_id')
                ->where($where)
                ->field('a.id,a.status,a.people_num,a.start_time,a.end_time,b.item_id,b.item_name,b.old_price,b.price,b.stock,b.already_num,residue_num')
                ->page($page,$limit)->order('a.id desc')->select()
                ->append(['item_specs','old_price_list','price_list','over_list','stock_list','pic']);
            $total = $ItemCategory->alias('a')
                ->join('flash_sale_attr b','a.id=b.flash_sale_id')
                ->where($where)
                ->group('b.item_id,b.flash_sale_id')
                ->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //限时购 列表
    public function list2(){
        return $this->fetch();
    }

    //拼团 列表
    public function list3(){
        return $this->fetch();
    }

    //分销礼包 列表
    public function list4(){
        return $this->fetch();
    }

    /***
     * 添加秒杀商品
     */
    public function item_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('seckill')->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        $type = $this->request->param('type',2);
        $this ->assign('type',$type);
        return $this ->fetch();
    }

    /***
     * 添加商品提交
     */
    public function doPost(){
        $data = $this ->request ->param();
        $data = $data['data'];
        if( $data['type'] != 4 ){
            if( $data['start_time'] >= $data['end_time'] ){
                return json(['code'=>0,'msg'=>'结束时间不能小于开始时间']);
            }
        }
        if( count($data['specs_ids_itemid']) <= 0 ){
            return json(['code'=>0,'msg'=>'未选择商品']);
        }
        $type = $data['type'];

        if($type == 1){// 页面提示的 1  是秒杀，但是数据库 是 2 才是秒杀
            $type = 2;
        }else if($type == 2){
            $type = 1;
        }

        if( $data['type'] == 3 ){
            if( empty($data['assemble_num']) || $data['assemble_num'] <2 ){
                return json(['code'=>0,'msg'=>'拼团人数至少为1人']);
            }
        }
        if( $type == 4 ){
            $people_num = 1;
        }else{
            $people_num = $data['people_num']?$data['people_num']:'-1';
        }
        $saleData = [];     //主表
        $attrData = [];     //副表
        $saleData = [
            'title'     =>empty($data['tag'])?'':$data['tag'],
            'people_num'     =>$people_num,
            'type'     =>$type,     //1限时购，2,秒杀,3拼团
            'start_time'    =>strtotime($data['start_time'])?strtotime($data['start_time']):0,
            'end_time'    =>strtotime($data['end_time'])?strtotime($data['end_time']):0,
            'postage_way'   =>$data['postage_way'],
            'status'   =>$data['hide'],
            'auto'   =>!empty($data['auto'])?$data['auto']:0,
            'assemble_num'   =>empty($data['assemble_num'])?0:$data['assemble_num']
        ];
        foreach ( $data['specs_ids_itemid'] as $k=>$v ){
            if( ($data[$k]['store'] != '不限制') && ($data[$k]['sto'] != '-1') ){
                if( $data[$k]['sto'] > $data[$k]['store'] ){
                    return json(['code'=>0,'msg'=>$data[$k]['item_name'].'库存不足']);
                }
            }
            $arr = [];
            $arr = [
                'item_id'     =>$data['specs_ids_itemid'][$k],
                'specs_ids'     =>$data['specs_ids'][$k],
                'item_name'     =>$data['item_name'][$k],
                'specs_names'     =>$data['specs_names'][$k]=='无'?'':$data['specs_names'][$k],
                'old_price'     =>$data['old_price'][$k],
                'price'     =>$data['price'][$k],
                'stock'     =>$data['sto'][$k],
                'virtually_num'     =>$data['virtually_num'][$k],
                'already_num'     =>0,
                'residue_num'     =>$data['sto'][$k],
                'commander_price'     =>empty($data['commander_price'][$k])?0:$data['commander_price'][$k],
            ];
            array_push($attrData,$arr);
        }
        $addAttrData = [];  //将数据按照商品分类
        foreach ( $attrData as $k=>$v ){
            if( isset($addAttrData[$v['item_id']]) ){
                array_push($addAttrData[$v['item_id']],$v);
            }else{
                $addAttrData[$v['item_id']][] = $v;
            }
        }
        //未做完
        // 启动事务
        Db::startTrans();
        try {

            foreach ( $addAttrData as $k=>$v ){
                //1秒杀主表
                $saleId = Db::name('flash_sale') ->insertGetId($saleData);
                //2添加到秒杀副表
                $newAttr = [];      //要添加到副表的数据
                $newPrice = [];     //商品价格
                foreach ( $v as $k1=>$v1 ){
                    $v1['flash_sale_id'] = $saleId;
                    array_push($newAttr,$v1);
                    array_push($newPrice , $v1['price']);
                }
                Db::name('flash_sale_attr') ->insertAll($newAttr);
                $updateItem = [];
                //活动类型：1 普通 2 抢购 3 拼团 4限时购,         //$type 1限时购，2,秒杀,3拼团
                $updateItem = [
                    'activity_type'     =>$type==1?4:($type==2?2:3),
                    'activity_price'     =>min($newPrice),
                    'activity_id'     =>$saleId,
                    'activity_start_time'     =>strtotime($data['start_time']),
                    'activity_end_time'     =>strtotime($data['end_time'])
                ];
                Db::name('item') ->where('id',$k)->update($updateItem);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>'服务器繁忙','data'=>$e->getMessage()]);
        }
        return json(['code'=>1,'msg'=>'添加成功']);
    }

    /***
     * 搜索商品
     */
    public function purchase_item(){
        $data = $this ->request ->param();
        $ItemCategory = new ItemModel();
        $userId = session('admin_user_auth')['uid'];
        $roleid = Db::name('admin')->where('userid',$userId)->value('roleid');
        $limit = $this->request->param('limit/d', 10);
        $page = $this->request->param('page/d', 10);
        $where = [];
        if( $roleid == 5 ){
            $where[] = ['a.user_id','eq',$userId];   //5为新角色（供应商）
        }
        if( isset($data['data']['name']) ){
            $where[] = ['a.title','like','%'.$data['data']['name'].'%'];
        }
        $where[] = ['a.status','eq',1];
        $where[] = ['item_type','eq',1];
        $list = $ItemCategory
            ->alias('a')
            ->where($where)
            ->page($page,$limit)
            ->order('a.sort asc')
            ->field('a.id,a.title,a.pic as pic_src,a.pic')
            ->select()
            ->append(['key']);
        foreach ( $list as $k=>$v ){
            $list[$k]['keyNames'] = $v['key']['keyNames'];
            $list[$k]['prices'] = $v['key']['prices'];
            $list[$k]['stores2'] = $v['key']['stores'];
//            $list[$k]['pic'] = config('QINIU_URL').$v['pic'];
        }
        $total = $ItemCategory->alias('a')->where($where)->count();
        $result = array("code" => 0, "count" => $total, "data" => $list);
        return json($result);
    }

    /***
     * 商品编辑提交
     */
    public function update()
    {
        $data = $this->request->param();
        $id = $data['id'];
        if (empty($id)) {
            return json(['code' => 100, 'msg' => '参数出错，请重试']);
        }

        $flash_sale_attr = \db('flash_sale_attr')->alias('fsa')
            ->field('fsa.*')//,sgp.store residue_num
//            ->join('specs_goods_price sgp','sgp.gid = fsa.item_id and sgp.key = fsa.specs_ids and sgp.status = 1')
            ->where('flash_sale_id',$id)
            ->where('fsa.status',1)
            ->group('item_id')
            ->select();
        if($flash_sale_attr == false){
            return json(['code' => 100, 'msg' => '数据为空！，请重试']);
        }

        $arr = array();
        foreach ($flash_sale_attr  as $value){

            $id2 =$value['item_id'];
            $title =$value['item_name'];

            $arrall['id']=$id2;
            $arrall['title']=$title;
            $arrall['arr_box']=[];

            $store = \db('specs_goods_price')->where('gid',$id2)->where('status',1)->value('store');

            if($store == -1){
                $store ='不限制';
            }

            //规格值 ID
            $specs_ids   = $value['specs_ids'];
            //单品规格
            if(empty($specs_ids)){
                $arr2=[
                    'specs_names'=>'无',
                    'specs_id'=>'',
                    'price'=>$value['old_price'],
                    'store'=>$store,
                    'store2'=>$value['residue_num'],////2020-07-18修改总库存随着商品的而变化
//                    'store2'=>$value['stock'],
                    'pricenow'=>$value['price'],
                    'flash_sale_attr_id'=>$value['id'],
                    'commander_price'=>$value['commander_price'],
                    'virtually_num'=>$value['virtually_num'],
                    'already_num'   =>$value['already_num']
                ];
                array_push($arrall['arr_box'],$arr2);
                array_push($arr,$arrall);
            }else{
                //查询多规格商品
                $flash_sale_attr2 = \db('flash_sale_attr')->alias('fsa')
                    ->field('fsa.*,sgp.store') //2020-07-18修改总库存随着商品的而变化
                    ->join('specs_goods_price sgp','sgp.gid = fsa.item_id and sgp.key = fsa.specs_ids and sgp.status = 1')//2020-07-18修改总库存随着商品的而变化
                    ->where('item_id',$id2)
                    ->where('fsa.status',1)
                    ->where('flash_sale_id',$id)
                    ->select();

                foreach ($flash_sale_attr2  as $value2){

                    $arr3=[
                        'specs_names'=>$value2['specs_names'],
                        'specs_id'=>$value2['specs_ids'],
                        'price'=>$value['old_price'],
//                        'store'=>$store,
                        'store'=>$value2['store'],//2020-07-18修改总库存随着商品的而变化
                        'store2'=>$value2['residue_num'],//2020-07-18修改总库存随着商品的而变化
//                        'store2'=>$value2['stock'],
                        'pricenow'=>$value2['price'],
                        'flash_sale_attr_id'=>$value2['id'],
                         'commander_price'=>$value['commander_price'],
                         'virtually_num'=>$value2['virtually_num'],
                         'already_num'=>$value2['already_num']
                    ];
                    array_push($arrall['arr_box'],$arr3);
                }
                array_push($arr,$arrall);
            }
        }

        //显示抢购表当前数据
        $ddxm_flash_sale = \db('flash_sale')->where('id',$id)->find();

        $this ->assign('id',$id);
        $this ->assign('title',$ddxm_flash_sale['title']);
        $this ->assign('assemble_num',$ddxm_flash_sale['assemble_num']);
        $this ->assign('people_num',$ddxm_flash_sale['people_num']);
        $this ->assign('auto',$ddxm_flash_sale['auto']);

        $this ->assign('postage_way',$ddxm_flash_sale['postage_way']);
        $this ->assign('status',$ddxm_flash_sale['status']);
        $this ->assign('type',$ddxm_flash_sale['type']);
        $this ->assign('auto',$ddxm_flash_sale['auto']);

        $this ->assign('start_time',date("Y-m-d H:i:s",$ddxm_flash_sale['start_time']));
        $this ->assign('end_time',date("Y-m-d H:i:s",$ddxm_flash_sale['end_time']));
        $this ->assign('arr',json_encode($arr));

        $type = $this->request->param('type',1);
        $this ->assign('type',$type);

        return $this ->fetch();
    }

    /***
     * 编辑
     */
    public function save_doPost(){
        $data = $this ->request ->param();
        $data = $data['data'];
        if( $data['type'] != 4 ){
            if( $data['start_time'] >= $data['end_time'] ){
                return json(['code'=>0,'msg'=>'结束时间不能小于开始时间']);
            }
        }
        if( count($data['specs_ids_itemid']) <= 0 ){
            return json(['code'=>0,'msg'=>'未选择商品']);
        }

        $type = Db::name('flash_sale') ->where('id',$data['id']) ->value('type');
        if( !$type ){
            return json(['code'=>0,'msg'=>'id错误']);
        }
        if( $data['type'] == 3 ){
            if( empty($data['assemble_num']) || $data['assemble_num'] <2 ){
                return json(['code'=>0,'msg'=>'拼团人数至少为1人']);
            }
        }
        if( $data['type'] == 4 ){
            $people_num = 1;
        }else{
            $people_num = $data['people_num']?$data['people_num']:'-1';
        }
        $saleData = [];     //主表
        $attrData = [];     //副表
        $saleData = [
            'title'     =>empty($data['tag'])?'秒杀':$data['tag'],
            'people_num'     =>$people_num,
            'type'     =>$type,
            'start_time'    =>strtotime($data['start_time']),
            'end_time'    =>strtotime($data['end_time']),
            'postage_way'   =>$data['postage_way'],
            'status'   =>$data['hide'],
            'assemble_num'   =>empty($data['assemble_num'])?0:3,
            'auto'   =>!empty($data['auto'])?$data['auto']:0,
        ];
        foreach ( $data['specs_ids_itemid'] as $k=>$v ){
            if( ($data[$k]['store'] != '不限制') && ($data[$k]['sto'] != '-1') ){

                if( $data['sto'][$k] > $data['store'][$k] ){
                    return json(['code'=>0,'msg'=>$data[$k]['item_name'].'库存不足']);
                }
            }
            $arr = [];
            $arr = [
//                'id'        =>$data['flash_sale_attr_id'][$k],
                'item_id'     =>$data['specs_ids_itemid'][$k],
                'specs_ids'     =>$data['specs_ids'][$k],
                'item_name'     =>$data['item_name'][$k],
                'specs_names'     =>$data['specs_names'][$k]=='无'?'':$data['specs_names'][$k],
                'commander_price'     =>empty($data['commander_price'][$k])?0:$data['commander_price'][$k],
                'old_price'     =>$data['old_price'][$k],
                'price'     =>$data['price'][$k],
                'stock'     =>$data['store'][$k], //2020-7-20修改总库存随着商品的而变化（当前字段弃用）
                'already_num'     =>$data['already_num'][$k],
                'residue_num'     =>$data['sto'][$k] != 0 ? $data['sto'][$k] - $data['already_num'][$k] : 0,
                'virtually_num'     =>$data['virtually_num'][$k]
            ];
            array_push($attrData,$arr);
        }
        $addAttrData = [];  //将数据按照商品分类
        foreach ( $attrData as $k=>$v ){
            if( isset($addAttrData[$v['item_id']]) ){
                array_push($addAttrData[$v['item_id']],$v);
            }else{
                $addAttrData[$v['item_id']][] = $v;
            }
        }

        // 启动事务
        Db::startTrans();
        try {
            //先将商品表的数据清除
            $itemData = [];
            $itemData = [
                'activity_type' =>1,
                'activity_price' =>0,
                'activity_id' =>0,
                'activity_start_time' =>0,
                'activity_end_time' =>0,
            ];
            $itemWhere = [];
            $itemWhere[] = ['activity_type','in','2,4,3'];
            $itemWhere[] = ['activity_id','eq',$data['id']];
            Db::name('item') ->where($itemWhere)->update($itemData);
            //将附表全部设置成删除状态
            Db::name('flash_sale_attr')->where('flash_sale_id',$data['id'])->setField('status',2);
            //将主表设置为删除状态
//            Db::name('flash_sale')->where('id',$data['id'])->setField('status',0);
            Db::name('flash_sale')->where('id',$data['id'])->update($saleData);
            $saleId = $data['id'];
            //数据新增
            foreach ( $addAttrData as $k=>$v ){
                //1秒杀主表
//                $saleId = Db::name('flash_sale') ->insertGetId($saleData);
                //2添加到秒杀副表
                $newAttr = [];      //要添加到副表的数据
                $newPrice = [];     //商品价格
                foreach ( $v as $k1=>$v1 ){
                    $v1['flash_sale_id'] = $saleId;
                    array_push($newAttr,$v1);
                    array_push($newPrice , $v1['price']);
                }
                Db::name('flash_sale_attr') ->insertAll($newAttr);
                $updateItem = [];
                //活动类型：1 普通 2 抢购 3 拼团 4限时购,         //$type 1限时购，2,秒杀,3拼团
                $updateItem = [
                    'activity_type'     =>$type==1?4:($type==2?2:3),
                    'activity_price'     =>min($newPrice),
                    'activity_id'     =>$saleId,
                    'activity_start_time'     =>strtotime($data['start_time']),
                    'activity_end_time'     =>strtotime($data['end_time'])
                ];
                Db::name('item') ->where('id',$k)->update($updateItem);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>'服务器繁忙','data'=>$e->getMessage()]);
        }

        //编辑秒杀活动列表时,动态修改商品缓存
        if($data['type']==1)
        {
            $k = $data['item_id'][0];
            //可能出现多规格情况，统一遍历处理
            foreach ($data['specs_ids'] as $key=>$val)
            {
                $k = $data['item_id'][0].'_'.$val;
                if(redisObj()->exists($k))
                {
                    if(count($data['sto'])==1)
                    {
                        //单规格
                        redisObj()->set($k,$data['sto'][0]-$data['already_num'][0]);//修改限购总数量
                    }else{
                        //多规格
                        for ($i=0;$i<=count($data['sto']);$i++)
                        {
                            redisObj()->set($k,$data['sto'][$i]-$data['already_num'][$i]);
                        }
                    }
                    //修改个人限购数量
                    redisObj()->set($k.'_residue_num',$data['people_num']);
                }
            }
        }
        return json(['code'=>1,'msg'=>'编辑成功']);
    }

    /***
     * 下架
     */
    public function del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id为空');
        }
        // 启动事务
        Db::startTrans();
        try {
            $where = [];
            $where[] = ['flash_sale_id','in',rtrim($data['id'],',')];
            //$where[] = ['item_id','eq',$data['item_id']];
            $res = Db::name('flash_sale_attr')->where($where)->setField('status',2);
            $map[] = ['flash_sale_id','in',rtrim($data['id'],',')];
            $map[] = ['status','eq',1];
            $count = Db::name('flash_sale_attr')->where($map)->count();
            if( $count==0 ){
                Db::name('flash_sale') ->where('id','in',rtrim($data['id'],','))->setField('status',2);
            }
            //修改商品表
            $itemData = [
                'activity_type' =>1,
                'activity_price' =>0,
                'activity_id' =>0,
                'activity_start_time' =>0,
                'activity_end_time' =>0,
            ];
            $res = Db::name('item')->where('id','in',rtrim($data['item_id'],','))->update($itemData);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            $this ->error('操作失败');
        }
        $this ->success('操作成功');
    }

    /***
     * 上架
     */
    public function start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id为空');
        }
        $where = [];
        $where[] = ['flash_sale_id','eq',$data['id']];
        $where[] = ['item_id','eq',$data['item_id']];
        $res = Db::name('flash_sale_attr')->where($where)->setField('status',1);
        if( $res ){
            $this ->success('操作成功');
        }else{
            $this ->error('操作失败');
        }
    }
}