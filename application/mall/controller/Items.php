<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\items\CategoryModel;
use app\mall\model\items\ItemUnitModel;
use app\mall\model\items\AttributeModel;
use app\mall\model\items\TypeModel;
use app\mall\model\items\CompanyModel;
use app\mall\model\items\SpecsModel;
use app\mall\model\items\PostageModel;
use app\mall\model\items\ItemModel;
use app\mall\model\items\EnsureModel;
use app\mall\model\items\BrandModel;
use app\mall\model\item_common\Goods;
use think\Db;


/**
 * 商品模块
 */
class Items extends Adminbase
{
    /***
     * 商品列表
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function item_list(){
        $page2 = $this ->request->param('page',-1);
        if ( $this->request->isAjax() ) {
            $ItemCategory = new ItemModel();
            $userId = session('admin_user_auth')['uid'];
            $roleid = Db::name('admin')->where('userid',$userId)->value('roleid');
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $brand_id = $this ->request->param('brand_id');
            $classId = $this ->request->param('class_id');
            $status = $this->request->param('status/d',1);
            $bar_code = $this->request->param('bar_code');
            $sup_id = $this->request->param('sup_id');
            $where = [];
            if( $title ){
                $where[] = ['a.title','like','%'.$title.'%'];
            }
            if( $brand_id ){
                if ( $brand_id == 't1' )
                {
                    $where[] = ['a.brand_id','eq',''];
                }else{
                    $where[] = ['a.brand_id','eq',$brand_id];
                }
            }
            if( $sup_id ){
                if ( $sup_id == 't1' )
                {
                    $where[] = ['a.sender_id','eq',''];
                }else{
                    $where[] = ['a.sender_id','eq',$sup_id];
                }
            }
            if( $classId ){
                $itemIds = (new \app\wxshop\model\item\CategoryModel()) ->getAllCategoryList($classId);
                $where[] = ['a.id','in',implode(',',$itemIds)];
            }
            if( $bar_code ){
                $where[] = ['b.bar_code','like','%'.$bar_code.'%'];
            }
            $where[] = ['a.item_type','eq',1];
            $where[] = ['a.status','eq',$status==1?$status:0];
            $where[] = ['b.status','eq',1];
            $filed = "a.id,a.title,a.subtitle,a.cate_id,lvid,a.status,a.pic,a.brand_id,a.mold_id,a.time,a.show,a.brand_id";
            $list = $ItemCategory
                ->alias('a')
                ->join('specs_goods_price b','a.id=b.gid')
                ->where($where)
                ->page($page,$limit)
                ->order('id desc')
                ->group('a.id')
                ->field($filed)->select()
                ->append(['specs','pic_src','brand'])
                ->toArray();
            $list = $ItemCategory ->zuItem($list);
            $total = $ItemCategory->alias('a')->join('specs_goods_price b','a.id=b.gid')->where($where)->group('a.id')->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        $brand = (new BrandModel()) ->where('status',1) ->field('id,title')->select();
        $this ->assign('brand',$brand);

        $where = [];
        $where[] = ['pid','eq',0];
        $where[] = ['online','eq',1];
        $where[] = ['status','eq',1];
        $where[] = ['type','eq',1];
        $category = (new CategoryModel()) ->where($where) ->field('id,cname')->select();
        $this ->assign('category',$category);
        $sup = Db::name('shop_supplier')->where('status',1)->select();
        $this ->assign('sup',$sup);

        $this ->assign('page2',$page2);
        return $this->fetch();
    }

    /***
     * 商品显示与隐藏
     */
    public function no_show(){
        $id = $this ->request ->param('id');
        if( !$id ){
            return json(['code'=>0,'msg'=>'服务器出错']);
        }
        $res = Db::name('item') ->where('id',$id) ->setField('show',0);
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    /***
     * 商品显示与隐藏
     */
    public function show(){
        $id = $this ->request ->param('id');
        if( !$id ){
            return json(['code'=>0,'msg'=>'服务器出错']);
        }
        $res = Db::name('item') ->where('id',$id) ->setField('show',1);
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    /***
     * 列表编辑
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update(){
        $data = $this ->request ->param();
        if( !isset($data['id']) || empty($data['field']) || !isset($data['value']) ){
            return json(['code'=>0,'msg'=>'参数不齐']);
        }
        $item = (new ItemModel()) ->where('id',$data['id'])->field('unify_specs')->find();
        if( !$item ){
            return json(['code'=>0,'msg'=>'商品id错误']);
        }
        if( $data['field'] == 'title' ){
            $res = (new ItemModel()) ->where('id',$data['id']) ->setField($data['field'],$data['value']);
        }else{
            if( $item['unify_specs'] == 2 ){
                return json(['code'=>2,'msg'=>'目前仅支持单规格编辑']);
            }
            $rule = '/^(0|[1-9]\d{0,3})(\.\d{1,2})?$/';
            $res = preg_match($rule, $data['value']);
            if($res == 0){
                return json(['code'=>100,'msg'=>'金额有误！']);
            }
            if( $data['value'] <= 0 ){
                return json(['code'=>100,'msg'=>'金额有误！']);
            }
            $where = [];
            $where[] = ['gid','eq',$data['id']];
            $where[] = ['status','eq',1];
            if( $data['field'] == 'yuanjia' ){
                $data['field'] = 'recommendprice';
            }
            $res = Db::name('specs_goods_price') ->where($where) ->setField($data['field'],$data['value']);
            if( $data['field'] == 'recommendprice' ){
                Db::name('item') ->where('id',$data['id']) ->setField('max_price',$data['value']);
            }else if( $data['field'] == 'price' ){
                Db::name('item') ->where('id',$data['id']) ->setField('min_price',$data['value']);
            }
        }
        if( !$res ){
            return json(['code'=>0,'msg'=>'设置失败']);
        }
        return json(['code'=>1,'msg'=>'设置成功']);
    }

    // 选择 规格
    public function selectspecs(){
        $ids = input('ids',-1);// 接受 不能够查询的父级ID  格式 123,123
        $where = '';
        if(!empty(input('title')))
        {
            $where_title['title'] = input('title');
            $pids = db('item_specs')
                ->where('status','1')
                ->where('pid','<>','0')
                ->where('title','like','%'.input('title').'%')
                ->order('id desc')
                ->column('pid');

            !empty($pids) ? $where[] = ['id','in',$pids] : '';
        }
        if($ids == -1){
            $specs = db('item_specs')
                ->field('id,title')
                ->where('status','1')
                ->where('pid','0')
                ->order('id desc')
                ->select();
        }else{
            $specs = db('item_specs')
                ->field('id,title')
                ->where('status','1')
                ->where('pid','0')
                ->where('id','not in',$ids)
                ->where($where)
                ->order('id desc')
                ->select();
        }
        $specsListnet = [];
        $specsList = [];
        $index = 0;

        $jsJson =[];// 传递给 选择 规格的 默认选中 第一项的全部 规格
        foreach ($specs as $value){
            $id = $value['id'];
            //查询二级  数组
            $specs2 = db('item_specs')
                ->field('id,pid,title')
                ->where('status','1')
                ->where('pid',$id)
                ->order('sort desc')
                ->select();
            $json ="";
            if($specs2 == true){
                if($index == 0){
                    $specsListnet=$specs2;
                    $jsJson['tid']=$id.'';
                    $jsJson['tname']=$value['title'];
                    $sp2 = null;
                    $in = 0;
                    foreach ($specs2 as $va){
                        $next=[
                            "id"=>$va['id'].'',
                            "name"=>$va['title'],
                        ];
                        $sp2[$in] = $next;
                        $in++;
                    }
                    $jsJson['value']=$sp2;
                }
                $json = json_encode($specs2);
            }
            $value['next']=$json;
            $specsList[$index] = $value;
            $index++;
        }

        $this ->assign('specslist',$specsList);
        $this ->assign('specslistnet',$specsListnet);

        // 传递 默认第一个选项的 数组  给  js  端
        $this ->assign('specslistnet_json',json_encode($jsJson));
        return $this->fetch();
    }

    // 上传  规格 图片
    public function uploadSpecs(){
        return $this->fetch();
    }

    // 上传  规格  详情 图片
    public function uploadSpecsDetails(){

        $imgurls = input('imgurls','');

        if($imgurls!=''){
            $arr = explode(',',$imgurls);
            $this ->assign('arr',$arr);
        }else{
            $this ->assign('arr',array());
        }


        $this ->assign('imgurls',$imgurls);
        return $this->fetch();
    }

    /***
     * 添加商品
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function item_add(){
        //分类
        $ItemCategory = new CategoryModel();
        $where['pid'] = 0;
        $where['status'] = 1;
        $where['type'] = 1;
        $where['online'] = 1;
        $category = $ItemCategory->where($where)->order('sort asc')->select();
        $this ->assign('category',$category);

        //单位
        $ItemUnit = new \app\admin\model\item\ItemUnitModel();
        $unit = $ItemUnit->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('unit',$unit);

        //运费
        $lvid = DB::name('postage')->where('status',1)->field('id,title')->select();
        $this ->assign('lvid',$lvid);

        //类型
        $type = Db::name('item_type')->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('type',$type);

        //供应商
        $supplier = Db::name('shop_supplier')->where('status',1)->select();
        $this ->assign('supplier',$supplier);

        //商品服务
        $itemService = Db::name('item_ensure')->where(['status'=>1])->order('sort asc')->field('id,title,content')->select();
        $this ->assign('itemService',$itemService);

        //品牌
        $brand = Db::name('brand')->where('status',1)->order('sort asc') ->field('id,title')->select();
        $this ->assign('brand',$brand);
        return $this->fetch();
    }

    //上传商品
    public function item_doPost(){
        $data = $this ->request ->post();
        if( empty($data['images']) && empty($data['video']) ){
            return json(['code'=>0,'msg'=>'请上传商品图或视频']);
        }
        if( !empty($data['pic']) ){
            array_unshift($data['images'],$data['pic']);
        }
        $categoreIds = [];    //选择的分类iD
        if( count($data['type_id']) >0 && ($data['type_id']['0'] !== '请选择') ){
            //选择了分类
            foreach ( $data['type_id'] as $k=>$v ){
                if( empty($data['type'][$k]) ){
                    //选择了第一级分类，但是未选择二级分类
                    return json(['code'=>0,'msg'=>'只能选择存在二级或三级的分类']);
                }
                //判断二级分类下是否存在三级id
                if( empty($data['type_three'][$k]) ){
                    $where = [];
                    $where[] = ['pid','eq',$data['type'][$k]];
                    $where[] = ['status','eq',1];
                    $categoryCount = Db::name('item_category')->where($where) ->count();
                    if( $categoryCount > 0 ){
                        return json(['code'=>0,'msg'=>'请选择三级分类']);
                    }
                }
                array_push($categoreIds,empty($data['type_three'][$k])?$data['type'][$k]:$data['type_three'][$k]);
            }
        }else{
            return json(['code'=>0,'msg'=>'请选择商品分类']);
        }
        if( empty($data['sort']) ){
            $data['sort'] = 0;
        }
        if( $data['specs_type'] == 0 ){
            $unify_specs = 1;
        }else{
            $unify_specs = 2;
        }
        $data['specs'] = json_decode($data['specs'],true);
        $item = [];     //商品表数据
        $erp_item = [];     //erp商品表数据
        if( !empty($data['service_ids']) ){
            $item_service_ids = implode(',',$data['service_ids']);
        }else{
            $item_service_ids = '';
        }
        $item = array(
            'title'     =>$data['title'],
            'subtitle'     =>!empty($data['titles'])?$data['titles']:'',
            'item_type'     =>!empty($data['g_type']) ? $data['g_type'] :1,   //商品类型：线上/门店商品
            'mold_id'     =>$data['mold_id'],
            'lvid'     =>$data['lvid'],
            'reality_sales'     =>0,
            'status'     =>1,
            'time'     =>time(),
            'update_time'     =>time(),
            'sort'     =>empty($data['sort'])?0:$data['sort'],
            'user_id'     =>session('admin_user_auth')['uid'],
            'quota'     =>$data['quota'],
            'unify_specs'     =>$unify_specs,
            'pic'     =>$data['images']['0'],
            'pics'     =>empty($data['images'])?'':implode(',',$data['images']),
            'content'     =>!isset($data['content'])?'':$data['content'],
            'initial_sales'     =>0,
            'min_price'     =>0,
            'max_price'     =>0,
            'specs_list'    =>$data['specs_list'],
            'item_service_ids'=>$item_service_ids,
            "sender_id" =>isset($data['sender_id'])?$data['sender_id']:0,
            "ratio"     =>empty($data['ratio'])?0:$data['ratio'],
            "two_ratio"     =>empty($data['two_ratio'])?0:$data['two_ratio'],
            "own_ratio"     =>empty($data['own_ratio'])?0:$data['own_ratio'],
            "ratio_type" =>$data['ratio_type'],
            "video"     =>empty($data['video'])?'':$data['video'],
            "video_time"=>empty($data['video_time'])?'':$data['video_time'],
            "brand_id"  =>!empty($data['brand_id'])?$data['brand_id']:0,
            "is_activity_pic"=>empty($data['pic'])?0:1
        );
        $erp_item = [
            'g_title'   =>$data['title'],
            'g_subtitle'   =>!empty($data['titles'])?$data['titles']:'',
            'g_img'   =>$data['images']['0'],
            'g_type'   =>!empty($data['g_type']) ? $data['g_type'] :1,   //商品类型：线上/门店商品
            'g_init_num'   =>0,
            'g_reality_num'   =>0,
            'g_min_price'   =>0,
            'g_max_price'   =>0,
            'g_content'   =>!isset($data['content'])?'':$data['content'],
            'g_ratio'   =>$data['ratio_type'],
            'g_video'   =>empty($data['video'])?'':$data['video'],
            'type_id'   =>$data['mold_id'],
            'brand_id'   =>!empty($data['brand_id'])?$data['brand_id']:0,
            'supplier_id'   =>isset($data['sender_id'])?$data['sender_id']:0,
            'is_shelf'   =>1,
            'is_show'   =>1,
            'is_delete'   =>0,
        ];
        $specs_goods = [];      //商品规格表(二维数组)
        if( $data['specs_type'] == 0 ){
//            if( $data['store'] == 0 ){
//                $store = -1;
//            }else{
//                $store = $data['store'];
//            }
            $store = $data['store'];
            $arr = array(
                'gid'       =>0,
                'recommendprice'       =>$data['recommendprice'],   //原价
                'price'       =>$data['price'], //会员价
                'store'       =>$store,     //库存
                'cost'       =>$data['cost'],   //成本
                'volume'       =>$data['volume'],   //容积
                'weight'       =>$data['weight'],   //重量
                'imgurl'       =>$item['pic'],      //规格图片
//                'pic_info'     =>implode(',',$data['content']),
                'initial_sales'       =>$data['initial_sales'],
                'reality_sales'       =>0,
                'bar_code'      =>$data['bar_code']?$data['bar_code']:'',
//                'commission'    =>$data['commission']
            );
            array_push($specs_goods,$arr);
        }else{
            //多规格
            foreach ($data['specs'] as $k=>$v){
                if( !isset($v['imgurl']) ){
                    $imgurl = $item['pic'];
                }else{
                    $imgurl = $v['imgurl'];
                }
//                if( $v['store'] == 0 ){
//                    $v_store = -1;
//                }else{
//                    $v_store = $v['store'];
//                }
                $v_store = $v['store'];
                $arr = array(
                    'gid'       =>0,
                    'key'       =>$v['key'],
                    'key_name'     =>$v['key_name'],
                    'recommendprice'  =>$v['recommendprice'],
                    'price'       =>$v['price'],
                    'store'       =>$v_store,
                    'cost'       =>$v['cost'],
                    'volume'       =>$v['volume'],
                    'weight'       =>$v['weight'],
                    'imgurl'       =>$imgurl,
                    'initial_sales'       =>$v['initial_sales'],
                    'reality_sales'       =>0,
//                    'pic_info'      =>$v['imgurl2']?$v['imgurl2']:implode(',',$data['content']),
                    'bar_code'  =>$v['bar_code']?$v['bar_code']:'',
//                    'commission'    =>$v['commission']
                );
                array_push($specs_goods,$arr);
            }
        }
        //判断条形码不能重复
        $barCode = [];
        foreach ($specs_goods as $k=>$v){
            if( !empty($v['bar_code']) ){
                array_push($barCode,$v['bar_code']);
            }
        }
        if( count( $barCode ) >0 ){
            $where = [];
            $where[] = ['bar_code','in',implode(',',$barCode)];
            $unique = Db::name('specs_goods_price') ->where($where)->find();
            if( $unique ){
//                return json(['code'=>0,'msg'=>'条形码已存在']);
            }
        }
        //给商品赋值初始销量和最高最低价格
        $initial_sales = 0;//初始销量
        foreach ($specs_goods as $k=>$v){
            $initial_sales += $v['initial_sales']; //初始销量
        }
        $item['initial_sales'] = $initial_sales;    //初始销量
        $item['min_price'] = self::searchmax($specs_goods,'price',1);       //会员最低价
        $item['max_price'] = self::searchmax($specs_goods,'recommendprice',1);       //原价最低价

        $erp_item['initial_sales'] = $initial_sales;    //初始销量
        $erp_item['min_price'] = self::searchmax($specs_goods,'price',1);       //会员最低价
        $erp_item['max_price'] = self::searchmax($specs_goods,'recommendprice',1);       //原价最低价

        $itemClassErp = [];
        foreach ( $categoreIds as $k=>$v ){
            $arr = [];
            $arr = [
                'item_id'   =>0,
                'category_id'   =>$v
            ];
            array_push($itemClassErp,$arr);
        }

        $resourceErp = [];
        $i = 0;
        foreach ( $data['images'] as $k=>$v )
        {
            $arr = [
                'gr_url'    =>$v,
                'gr_type'    =>1,
                'gr_sort'    =>$i,
                'goods_id'    =>0,
                'is_first'    =>$k==0?1:0,
            ];
            array_push($resourceErp,$arr);
            $i++;
        }
        $goodsDistribution = [
            'gd_one'    =>empty($data['own_ratio'])?0:$data['own_ratio'],
            'gd_two'    =>empty($data['ratio'])?0:$data['ratio'],
            'gd_three'    =>empty($data['two_ratio'])?0:$data['two_ratio'],
            'goods_id'  =>0
        ];
        $erp_add_goods = [
            'erp_item'  =>$erp_item,
            'specs_goods'=>$specs_goods,
            'itemClassErp'=>$itemClassErp,
            'goodsDistribution'=>$goodsDistribution,
            'resourceErp'=>$resourceErp
        ];

        // 启动事务
        Db::startTrans();
        try {
            $res  = Db::name('item')->insertGetId($item);
            $itemId = $res;
            if ( $res )
            {
                $res = Db::name('item')->where('id',$itemId)->setField('gid',$itemId);
            }

            if ( $res )
            {
                foreach ($specs_goods as $k=>$v){
                    $specs_goods[$k]['gid'] = $itemId;
                }
                $res = Db::name('specs_goods_price')->insertAll($specs_goods);
            }

            if( count($categoreIds) >0 ){
                //添加分类
                $itemClass = [];
                foreach ( $categoreIds as $k=>$v ){
                    $arr = [];
                    $arr = [
                        'item_id'   =>$itemId,
                        'category_id'   =>$v
                    ];
                    array_push($itemClass,$arr);
                }
                if ( $res )
                {
                    $res = Db::name('item_class') ->insertAll($itemClass);
                }

            }

            //添加erp系统的商品信息
            $erpGoods = new Goods();
            $res = $erpGoods ->addGoods($erp_add_goods,$itemId);

            if ( $res )
            {
                // 提交事务
                Db::commit();
                $erpGoods ->commit();
            }else{
                $erpGoods ->rollback();
                return json(['code'=>0,'msg'=>'服务器出错']);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>$e ->getMessage()]);
        }
        return json(['code'=>1,'msg'=>'添加成功']);
    }

    //编辑商品
    public function item_save(){
        $data = $this ->request ->param();
        $list = Db::name('item')->where('id',$data['id'])->find();
        $list['item_service_ids'] = explode(',',$list['item_service_ids']);
        $list['pics'] = explode(',',$list['pics']);
        if( $list['is_activity_pic'] == 1 ){
            unset($list['pics'][0]);
        }
        $list['content'] = str_replace("\"","'",$list['content']);

        //单位
        $ItemUnit = new \app\admin\model\item\ItemUnitModel();
        $unit = $ItemUnit->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('unit',$unit);

        //运费
        $lvid = DB::name('postage')->where('status',1)->field('id,title')->select();
        $this ->assign('lvid',$lvid);

        //类型
        $type = Db::name('item_type')->where(['status'=>1])->order('sort asc')->select();
        $this ->assign('type',$type);

        //品牌
        $brand = Db::name('brand')->where('status',1)->order('sort asc') ->field('id,title')->select();
        $this ->assign('brand',$brand);

        //商品服务
        $itemService = Db::name('item_ensure')->where(['status'=>1])->order('sort asc')->field('id,title,content')->select();
        $this ->assign('itemService',$itemService);

        //供应商
        $supplier = Db::name('shop_supplier')->where('status',1)->select();
        $this ->assign('supplier',$supplier);

        if( count($itemService) == count($list['item_service_ids']) ){
            $list['all_service'] = 1;
        }else{
            $list['all_service'] = 0;
        }

        $this ->assign('list',$list);

        //判断是否 统一规格 还是 多规格 unify_specs1 是 2 否
        $unify_specs = $list['unify_specs'];
        if($unify_specs == 2){//多规格
            $this ->assign('specs_list',$list['specs_list']);
            $specs = Db::name('specs_goods_price') ->where(['gid'=>$data['id'],'status'=>1])->select();
            $info = [];
            foreach ($specs as $k=>$v){
                $arr = [];
                $arr = [
                    'id'    =>$v['id'],
                    'key'    =>$v['key'],
                    'key_name'    =>$v['key_name'],
                    'imgurl1'    =>$v['imgurl'],
                    'imgurl2'    =>$v['pic_info'],
                    'kucun'    =>$v['store'],
                    'chenben'    =>$v['cost'],
                    'jianyilingshoujia'    =>$v['recommendprice'],
                    'lingshoujia'    =>$v['price'],
                    'tiji'    =>$v['volume'],
                    'zhongliang'    =>$v['weight'],
                    'chushixiaoliang'    =>$v['initial_sales'],
                    'bar_code'    =>$v['bar_code']
                ];
                array_push($info,$arr);
            }
            $this ->assign('specs',json_encode($info));

            $specsData = [
                'recommendprice'    =>'0.00',
                'price'    =>'0.00',
                'store'    =>'-1',
                'cost'    =>'0.00',
                'volume'    =>'0.00',
                'weight'    =>'0.00',
                'initial_sales'    =>'0',
                'bar_code'    =>'',
                'commission'    =>'0'
            ];
            $this ->assign('specsData',$specsData);
            $this ->assign('specs_list',$list['specs_list']);
            $specs_list =$list['specs_list'];
            $this ->assign('specs_list',$specs_list);
            //二级数据库数据
        }else{
            $this ->assign('specs_list','');
            $specs = Db::name('specs_goods_price') ->where(['gid'=>$data['id'],'status'=>1])->find();
            $this ->assign('specsData',$specs);
        }

//        筛选分类 处理
        //分类
        $ItemCategory = new CategoryModel();
        $where['pid'] = 0;
        $where['status'] = 1;
        $where['type'] = 1;
        $where['online'] = 1;
        $category = $ItemCategory->where($where)->order('sort asc')->select();
        $this ->assign('category',$category);

        /**************************** 1 查询当前商品对应有哪些 **********************************/
        $item_id = $data['id'];
        $item_class = \db('item_class')->where('item_id',$item_id)->select();

        $arr = array();
        /**************************** 2 查询当前商品对应有哪些底层分类数据 **********************************/
        foreach ($item_class as $value){

            $id = $value['category_id'];
            $name = $this->getItemClass($id);
//            $name['end_id']=$id;
            array_push($arr,$name);
        }

        $this ->assign('arr',json_encode($arr));

        $this ->assign('page',$data['page']);
        return $this ->fetch();
    }


    //根据分类最后一级ID 得到 上级所有的 分类名字
    //$id   分类ID
    private function getItemClass($id){

        $ItemCategory = new CategoryModel();
        //得到最底层分类数据----当前选中的最后一级数据
        $categoryTid01 = $ItemCategory->where('id',$id)->find();
        //找到 当前登录的      顶部ID

        $categoryTid01_name = $categoryTid01['cname'];
        $category02 = $ItemCategory->where('id',$categoryTid01['pid'])->find();

        $categoryTid02 = $category02['pid'];
        $categoryTid02_name = $category02['cname'];


        //倒数 一级数据  列表
        $arr1_list01 = $ItemCategory
            ->field('cname,id')
            ->where('pid',$categoryTid01['pid'])
            ->where('status',1)
            ->where('type',1)
            ->where('online',1)
            ->select()->toArray();




        //倒数 二级数据  列表
        $arr1_list02 = $ItemCategory
            ->field('cname,id')
            ->where('pid',$categoryTid02)
            ->where('status',1)
            ->where('type',1)
            ->where('online',1)
            ->select()->toArray();


        $name = '';
        $arr2 = array();
        $category03 = $ItemCategory->where('id',$categoryTid02)->find();
        if($category03  == true){

            $categoryTid03_name = $category03['cname'];

            $arr2 = array();

            $where['pid'] = 0;
            $where['status'] = 1;
            $where['type'] = 1;
            $where['online'] = 1;

            //倒数 三级数据  列表
            $arr1_list03 = $ItemCategory
                ->field('cname,id')
                ->where('pid',0)
                ->where('status',1)
                ->where('type',1)
                ->where('online',1)
                ->select()->toArray();

            array_push($arr2,['name'=>$categoryTid03_name,'id'=>$categoryTid02,'arr2'=>$arr1_list03]);
            array_push($arr2,['name'=>$categoryTid02_name,'id'=>$category02['id'],'arr2'=>$arr1_list02]);
            array_push($arr2,['name'=>$categoryTid01_name,'id'=>$id,'arr2'=>$arr1_list01]);

        }else{
            array_push($arr2,['name'=>$categoryTid02_name,'id'=>$category02['id'],'arr2'=>$arr1_list02]);
            array_push($arr2,['name'=>$categoryTid01_name,'id'=>$id,'arr2'=>$arr1_list01]);
        }

        $arr=[
            'id'=>$id,
            'arr'=>$arr2,
        ];
        return $arr;
    }

    /***
     * 商品编辑提交
     */
    public function item_save_doPost(){
        $data = $this ->request ->param();

        //处理空格
        $data['title'] = str_replace(" ",'',$data['title']);

        $specs = json_decode($data['specs'],true);
        foreach ($specs as $k=>$v)
        {
            $specs[$k]['bar_code'] = str_replace(" ",'',$v['bar_code']);
        }
        $data['specs'] = json_encode($specs);

        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'商品出错，请重试']);
        }
        if( !empty($data['pic']) ){
            array_unshift($data['images'],$data['pic']);
        }
        $categoreIds = [];    //选择的分类iD
        if( count($data['type_id']) >0 && ($data['type_id']['0'] !== '请选择') ){
            //选择了新分类
            foreach ( $data['type_id'] as $k=>$v ){
//                if( $data['type_id'] != '请选择' ){
                if( empty($data['type'][$k]) ){
                    //选择了第一级分类，但是未选择二级分类
                    return json(['code'=>0,'msg'=>'只能选择存在二级或三级的分类']);
                }
                //判断二级分类下是否存在三级id
                if( empty($data['type_three'][$k]) ){
                    $where = [];
                    $where[] = ['pid','eq',$data['type'][$k]];
                    $where[] = ['status','eq',1];
                    $categoryCount = Db::name('item_category')->where($where) ->count();
                    if( $categoryCount > 0 ){
                        return json(['code'=>0,'msg'=>'请选择三级分类']);
                    }
                }
                array_push($categoreIds,empty($data['type_three'][$k])?$data['type'][$k]:$data['type_three'][$k]);
//                }
            }
        }
        if( count($data['new_type_three0']) >0 && ($data['new_type_three0']['0'] !== '请选择') ){
            //选择了新分类
            foreach ( $data['new_type_three0'] as $k=>$v ){
                if( $data['new_type_three0'] != '请选择' ){
                    if( empty($data['new_type_three1'][$k]) ){
                        //选择了第一级分类，但是未选择二级分类
                        return json(['code'=>0,'msg'=>'只能选择存在二级或三级的分类']);
                    }
                    //判断二级分类下是否存在三级id
                    if( empty($data['new_type_three2'][$k]) ){
                        $where = [];
                        $where[] = ['pid','eq',$data['type'][$k]];
                        $where[] = ['status','eq',1];
                        $categoryCount = Db::name('item_category')->where($where) ->count();
                        if( $categoryCount > 0 ){
                            return json(['code'=>0,'msg'=>'请选择三级分类']);
                        }
                    }
                    array_push($categoreIds,$data['new_type_three2'][$k]=='无'?$data['new_type_three1'][$k]:$data['new_type_three2'][$k]);
                }
            }
        }
        if ( count($categoreIds) <= 0 ) {
            return json(['code'=>0,'msg'=>'请选择分类']);
        }
        if( empty($data['images']) || empty($data['content']) ){
            return json(['code'=>0,'msg'=>'请上传商品图或者详情图']);
        }
        if( empty($data['sort']) ){
            $data['sort'] = 0;
        }
        if( $data['specs_type'] == 0 ){
            $unify_specs = 1;
        }else{
            $unify_specs = 2;
        }
        $data['specs'] = json_decode($data['specs'],true);
        $item = [];     //商品表数据
//        if( !empty($data['service_ids']) ){
//            $item_service_ids = implode(',',$data['service_ids']);
//        }else{
//            $item_service_ids = '';
//        }
        $erp_item = [];     //erp商品表数据

        $item = array(
            'title'     =>$data['title'],
            'subtitle'     =>!empty($data['titles'])?$data['titles']:'',
            'item_type'     =>!empty($data['g_type']) ? $data['g_type'] :1,   //商品类型：线上/门店商品
            'mold_id'     =>$data['mold_id'],
            'lvid'     =>$data['lvid'],
//            'reality_sales'     =>0,
            'status'     =>1,
            'update_time'     =>time(),
            'sort'     =>empty($data['sort'])?0:$data['sort'],
            'quota'     =>$data['quota'],
            'unify_specs'     =>$unify_specs,
            'pic'     =>$data['images']['0'],
            'pics'     =>empty($data['images'])?'':implode(',',$data['images']),
            'content'     =>!isset($data['content'])?'':$data['content'],
            'initial_sales'     =>0,
            'min_price'     =>0,
            'max_price'     =>0,
            'specs_list'    =>$data['specs_list'],
            "sender_id" =>isset($data['sender_id'])?$data['sender_id']:0,
            "ratio"     =>empty($data['ratio'])?0:$data['ratio'],
            "two_ratio"     =>empty($data['two_ratio'])?0:$data['two_ratio'],
            "own_ratio"     =>empty($data['own_ratio'])?0:$data['own_ratio'],
            "ratio_type" =>$data['ratio_type'],
//            "video"     =>empty($data['video'])?'':$data['video'],    //视频编辑等会儿做
//            "video_time"=>empty($data['video_time'])?'':$data['video_time'],
            "brand_id"  =>!empty($data['brand_id'])?$data['brand_id']:0,
            "is_activity_pic"=>empty($data['pic'])?0:1
        );
        $erp_item = [
            'g_title'   =>$data['title'],
            'g_subtitle'   =>!empty($data['titles'])?$data['titles']:'',
            'g_img'   =>$data['images']['0'],
            'g_type'   =>!empty($data['g_type']) ? $data['g_type'] :1,   //商品类型：线上/门店商品
            'g_init_num'   =>0,
//            'g_reality_num'   =>0,
            'g_min_price'   =>0,
            'g_max_price'   =>0,
            'g_content'   =>!isset($data['content'])?'':$data['content'],
            'g_ratio'   =>$data['ratio_type'],
            'g_video'   =>empty($data['video'])?'':$data['video'],
            'type_id'   =>$data['mold_id'],
            'brand_id'   =>!empty($data['brand_id'])?$data['brand_id']:0,
            'supplier_id'   =>isset($data['sender_id'])?$data['sender_id']:0,
            'is_shelf'   =>1,
            'is_show'   =>1,
            'is_delete'   =>0,
        ];

        $specs_goods = [];      //商品规格表(二维数组)
        if( $data['specs_type'] == 0 ){
            //统一规格
//            if( empty($data['bar_code']) ){
//                return json(['code'=>0,'msg'=>'请填写条形码']);
//            }
//            if( $data['store'] == 0 ){
//                $store = -1;
//            }else{
//                $store = $data['store'];
//            }
            $store = $data['store'];
            $arr = array(
                'gid'       =>$data['id'],
                'recommendprice'       =>$data['recommendprice'],
                'price'       =>$data['price'],
                'store'       =>$store,
                'cost'       =>$data['cost'],
                'volume'       =>$data['volume'],
                'weight'       =>$data['weight'],
                'imgurl'       =>$item['pic'],
//                'pic_info'     =>implode(',',$data['content']),
                'initial_sales'       =>$data['initial_sales'],
                'reality_sales'       =>0,
                'bar_code'      =>$data['bar_code']?$data['bar_code']:'',
//                'commission'    =>$data['commission']
            );
            array_push($specs_goods,$arr);
        }else{
            //多规格
            foreach ($data['specs'] as $k=>$v){
                if( !isset($v['imgurl']) ){
                    $imgurl = $item['pic'];
                }else{
                    $imgurl = $v['imgurl'];
                }
//                if( $v['store'] == 0 ){
//                    $v_store = -1;
//                }else{
//                    $v_store = $v['store'];
//                }
                $v_store = $v['store'];
//                if( empty($v['bar_code']) ){
//                    return json(['code'=>0,'msg'=>'请填写条形码']);
//                }
                $arr = array(
                    'gid'       =>$data['id'],
                    'key'       =>$v['key'],
                    'key_name'     =>$v['key_name'],
                    'recommendprice'  =>$v['recommendprice'],
                    'price'       =>$v['price'],
                    'store'       =>$v_store,
                    'cost'       =>$v['cost'],
                    'volume'       =>$v['volume'],
                    'weight'       =>$v['weight'],
                    'imgurl'       =>$imgurl,
                    'initial_sales'       =>$v['initial_sales'],
                    'reality_sales'       =>0,
//                    'pic_info'      =>$v['imgurl2']?$v['imgurl2']:implode(',',$data['content']),
                    'bar_code'  =>$v['bar_code']?$v['bar_code']:'',
//                    'commission'    =>$v['commission']
                );
                array_push($specs_goods,$arr);
            }
        }
        //判断条形码不能重复
        $barCode = [];
        foreach ($specs_goods as $k=>$v){
            array_push($barCode,$v['bar_code']);
        }
        //判断条形码未结束

        $initial_sales = 0;//初始销量
        foreach ($specs_goods as $k=>$v){
            $initial_sales += $v['initial_sales']; //初始销量
        }
        $item['initial_sales'] = $initial_sales;    //初始销量
        $item['min_price'] = self::searchmax($specs_goods,'price',1);       //会员最低价
        $item['max_price'] = self::searchmax($specs_goods,'recommendprice',1);       //原价最低价

        $erp_item['initial_sales'] = $initial_sales;    //初始销量
        $erp_item['min_price'] = self::searchmax($specs_goods,'price',1);       //会员最低价
        $erp_item['max_price'] = self::searchmax($specs_goods,'recommendprice',1);       //原价最低价++$itemClassErp = [];

        $itemClassErp = [];
        foreach ( $categoreIds as $k=>$v ){
            $arr = [];
            $arr = [
                'item_id'   =>$data['id'],
                'category_id'   =>$v
            ];
            array_push($itemClassErp,$arr);
        }

        $resourceErp = [];
        $i = 0;
        foreach ( $data['images'] as $k=>$v )
        {
            $arr = [
                'gr_url'    =>$v,
                'gr_type'    =>1,
                'gr_sort'    =>$i,
                'goods_id'    =>$data['id'],
                'is_first'    =>$k==0?1:0,
            ];
            array_push($resourceErp,$arr);
            $i++;
        }
        $goodsDistribution = [
            'gd_one'    =>empty($data['own_ratio'])?0:$data['own_ratio'],
            'gd_two'    =>empty($data['ratio'])?0:$data['ratio'],
            'gd_three'    =>empty($data['two_ratio'])?0:$data['two_ratio'],
            'goods_id'  =>$data['id']
        ];
        $erp_add_goods = [
            'erp_item'  =>$erp_item,
            'specs_goods'=>$specs_goods,
            'itemClassErp'=>$itemClassErp,
            'goodsDistribution'=>$goodsDistribution,
            'resourceErp'=>$resourceErp
        ];

        // 启动事务
        Db::startTrans();
        try {
            $res = Db::name('item')->where('id',$data['id'])->update($item);
            foreach ($specs_goods as $k=>$v){
                $specs_goods[$k]['gid'] = $data['id'];
            }
            if ( $res )
            {
                $res = Db::name('specs_goods_price') ->where('gid',$data['id'])->setField('status',2);
            }
            if ( $res )
            {
                $res = Db::name('specs_goods_price')->insertAll($specs_goods);
            }
            //编辑商品分类，暂时不做
            if ( $res )
            {
                $res = Db::name('item_class')->where('item_id',$data['id'])->delete();
            }
            $itemClassData = [];
            foreach ( $categoreIds as $k=>$v ){
                $arr = [];
                $arr = [
                    'item_id'   =>$data['id'],
                    'category_id'   =>$v
                ];
                array_push($itemClassData,$arr);
            }
            if ( $res )
            {
                $res = Db::name('item_class')->insertAll($itemClassData);
            }
            //做erp系统的商品编辑
            $erpGoods = new Goods();
            $res = $erpGoods ->saveGoods($erp_add_goods,$data['id']);

            if ( $res )
            {
                // 提交事务
                Db::commit();
                $erpGoods ->commit();
            }else{
                $erpGoods ->rollback();
                return json(['code'=>0,'msg'=>'服务器出错']);
            }

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>0,'msg'=>$e ->getMessage()]);
        }
        return json(['code'=>1,'msg'=>'编辑成功','page'=>$data['page']]);
    }

    /***
     * 获取最大最小值
     * @param $arr
     * @param $field
     * @return bool|mixed
     */
    public function searchmax($arr,$field,$type)
    {
        if(!is_array($arr) || !$field){ //判断是否是数组以及传过来的字段是否是空
            return false;
        }
        $temp = array();
        foreach ($arr as $key=>$val) {
            $temp[] = $val[$field]; // 用一个空数组来承接字段
        }
        if( $type == 1 ){
            //最小
            return min($temp);
        }else{
            //最大
            return max($temp);
        }
    }

    //分类列表
    public function category_list(){
        if ($this->request->isAjax()) {
            $ItemCategory = new CategoryModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $cname = $this->request->param('cname');
            $pid =  $this->request->param('pid')?$this->request->param('pid'):0;
            $upId = $this ->request ->param('up_idssss');   //上级id
            if( !empty($upId) ){
                $pid = $ItemCategory ->where('id',$upId)->value('pid');
            }
            $where = [];
            $where[] = ['pid','=',$pid];
            if( !empty($cname) ){
                $where[] = ['cname','like',"%$cname%"];
            }
            $where[] = ['status','neq','-1'];
            $where[] = ['online','eq',1];
            $list = $ItemCategory->where($where)->page($page,$limit)->order('sort desc')->select()->append(['pid_name']);
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list,"pid"=>$pid);
            return json($result);
        }
        return $this->fetch();
    }

    //分类添加
    public function category_add(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( !empty($data['id']) ){
            $list = Db::name('item_category')->where('id',$data['id'])->field('id,sort,cname,pid,thumb,ratio')->find();
            $data['pid'] = $list['pid'];
            $this ->assign('list',$list);
        }
        $this ->assign('pid',$data['pid']);
        return $this->fetch();
    }

    //分类添加的操作
    public function category_doPost(){
        $data = $this ->request ->post();
        if( empty($data['cname']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        if( $data['pid'] != 0 ){
            if( empty($data['thumb']) ){
//                return json(['code'=>-1,'msg'=>'请上传分类展示图']);
            }
            unset($data['file']);
        }
        unset($data['file']);
        $data['ratio'] = round($data['ratio'],2);
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['addtime'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $data['online'] = 1;
            $result = $ItemCategory ->insert($data);
        }else{
            $info = Db::name('item_category')->where('id',$data['id'])->find();
            if( $info['pid'] == 0 ){
                $t = Db::name('item_category')->where(['pid'=>$data['id']])->count();
                if( $t>0 && $data['pid'] != 0 ){
                    return json(['code'=>0,'msg'=>'此分类下存在二级分类，禁止更改层级']);
                }
            }
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemCategory ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //分类删除
    public function category_del(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //分类启用
    public function category_start(){
        $data = $this ->request ->param();
        $ItemCategory = new CategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //单位列表
    public function unit_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new ItemUnitModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加单位
    public function unit_add(){
        $data = $this ->request ->param();
        $ItemUnit = new ItemUnitModel();
        if( !empty($data['id']) ){
            $list = $ItemUnit ->where('id',$data['id'])->field('id,sort,title')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加单位的操作
    public function unit_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = new ItemUnitModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //单位删除
    public function unit_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new ItemUnitModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //属性
    public function attribute_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new AttributeModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $pid = $this ->request ->param('pid')?$this ->request ->param('pid'):0;
            $data = $this ->request ->param();
            $where = [];
            $where[] = ['pid','eq',$pid];
            $where[] = ['status','neq',0];
            if( $pid == 0 ){
                if( !empty($title) ){
                    $where[] = ['title','like',"%$title%"];
                }
            }
            if( !empty($data['type_id']) && empty($data['type']) ){
                $ids = Db::name('item_category') ->where(['pid'=>$data['type_id']])->column('id');
                $where[] = ['category_id','in',implode(',',$ids)];
            }else if( !empty($data['type']) ){
                $where[] = ['category_id','eq',$data['type']];
            }

            $list = $ItemUnit->where($where)->page($page,$limit)->order('create_time desc')->select()->append(['category_name']);
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0,'pid'=>$pid, "count" => $total, "data" => $list);
            return json($result);
        }
        //一级分类
        $where = [];
        $where[] = ['online','eq',1];
        $where[] = ['status','eq',1];
        $where[] = ['pid','eq',0];
        $category = Db::name('item_category') ->where($where) ->field('id,cname')->order('sort asc')->select();
        $this ->assign('category',$category);

        return $this->fetch();
    }

    //选择分类
    public function category_select(){
        $pid = $this ->request->param('pid');
        if( !$pid ){
            return json(['code'=>0,'msg'=>'父id为空']);
        }
        $cate = Db::name('item_category')->where('pid',$pid)->field('id,cname')->select();
        if( count($cate)<=0 ){
            return json(['code'=>0,'msg'=>'此分类下无二级分类，请重新选择']);
        }
        return json(['code'=>1,'msg'=>'获取成功','data'=>$cate]);
    }

    //添加属性
    public function attribute_add(){
        $data = $this ->request ->param();
        $Attr = new AttributeModel();
        if( !empty($data['id']) ){
            $list = $Attr ->where('id',$data['id'])->find() ->append(['category_name','type_id']);
            $this ->assign('list',$list);

            $type = Db::name('item_category')->where(['pid'=>$list['type_id'],'status'=>1,'online'=>1])->select();
            $this ->assign('type',$type);
            $data['pid'] = $list['pid'];
        }
        $category = Db::name('item_category')->where(['pid'=>0,'status'=>1,'online'=>1])->select();
        $this ->assign('pid',$data['pid']);

        if( $data['pid'] !=0 ){
            $cate = $Attr->where('id',$data['pid'])->field('category_id')->find() ->append(['category_name']);
            $this ->assign('category_name',$cate['category_name']);
        }
        $this ->assign('category',$category);
        return $this->fetch();
    }

    //添加属性的操作
    public function attribute_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title'])){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        if( $data['pid'] == 0 && mb_strlen($data['title'])>4 ){
            return json(['code'=>-1,'msg'=>'名称不能超过4个字']);
        }
        if( $data['pid'] == 0 && empty($data['category_id']) ){
            return json(['code'=>-1,'msg'=>'请选择分类']);
        }
        if( empty($data['category_id']) ){
            $data['category_id'] = Db::name('item_attribute')->where('id',$data['pid'])->value('category_id');
        }
        $ItemUnit = new AttributeModel();
        $count = $ItemUnit ->where(['category_id'=>$data['category_id'],'pid'=>0])->count();
        if( empty($data['id']) ){
            if( $data['pid'] == 0 && $count >=4 ){
                return json(['code'=>-1,'msg'=>'该分类下已有4个属性，请重新选择分类']);
            }
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //属性禁用
    public function attribute_del(){
        $data = $this ->request ->param();
        $ItemCategory = new AttributeModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //属性启用
    public function attribute_start(){
        $data = $this ->request ->param();
        $ItemCategory = new AttributeModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //类型列表
    public function type_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new TypeModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加类型
    public function type_add(){
        $data = $this ->request ->param();
        $ItemUnit = new TypeModel();
        if( !empty($data['id']) ){
            $list = $ItemUnit ->where('id',$data['id'])->field('id,title,content')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加类型的操作
    public function type_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) || empty($data['content']) ){
            return json(['code'=>-1,'msg'=>'名称或购买需知不能为空']);
        }
        unset($data['file']);
        $ItemUnit = new TypeModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }

        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //类型删除
    public function type_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new TypeModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //快递公司列表
    public function company_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new CompanyModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加类型
    public function company_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_company') ->where('id',$data['id'])->field('id,title,sort')->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加类型的操作
    public function company_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemUnit = new CompanyModel();
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->insert($data);
        }else{
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $result = $ItemUnit ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //类型删除
    public function company_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new CompanyModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('删除成功');
        }else{
            $this ->error('删除失败');
        }
    }

    //类型启用
    public function company_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new CompanyModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //规格
    public function specs(){
        if ($this->request->isAjax()) {
            $ItemUnit = new SpecsModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $title_val = $this ->request->param('name_val');
            $where = [];
            $where[] = ['pid','eq',0];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }

            if(!empty($title_val))
            {
                $ids = $ItemUnit->whereLike('title',"%{$title_val}%")->group('pid')->column('pid');
                $ItemUnit = $ItemUnit->whereIn('id',$ids);
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('sort asc')->select()->append(['children']);
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加规格
    public function specs_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_specs') ->where('id',$data['id'])->find();
            $info = DB::name('item_specs')->where('pid',$data['id'])->column('title');
            $res =  '';
            foreach ($info as $k=>$v){
                $res = $res."\n".$v;
            }
            $list['children'] = $res;
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //g规格添加
    public function specs_doPost(){
        $data = $this ->request ->post();
        if( empty($data['title']) || empty($data['titles']) ){
            return json(['code'=>-1,'msg'=>'规格名或规格值不能为空']);
        }
        $list = explode("\n",$data['titles']);
        $tt = [];
        foreach ($list as $k=>$v){
            $v = preg_replace("/ /","",$v);
            $v = preg_replace("/&nbsp;/","",$v);
            $v = preg_replace("/　/","",$v);
            $v = preg_replace("/\r\n/","",$v);
            $v = str_replace(chr(13),"",$v);
            $v = str_replace(chr(10),"",$v);
            $v = str_replace(chr(9),"",$v);
            if( $v !='' ){
                array_push($tt,$v);
            }
        }
        $info = [];     //二级
        foreach ( $tt as $k=>$v ){
            if( !in_array($v,$info) &&  $v !='' ){
                array_push($info,$v);
            }
        }
        $infoDatas = [];
        foreach ($info as $k=>$v){
            $arr = array('title'=>$v);
            array_push($infoDatas,$arr);
        }
        unset($data['titles']);
        if( empty($data['id']) ){
            if( $data['pid'] == 0 ){
                $count = Db::name('item_specs')->where(['pid'=>0,'title'=>$data['title']])->find();
                if( $count ){
//                    return json(['code'=>0,'msg'=>'规格名称不能重复']);
                }
            }
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            // 启动事务
            Db::startTrans();
            try {
                $pid = Db::name('item_specs')->insertGetId($data);
                foreach ($infoDatas as $k=>$v){
                    $infoDatas[$k]['pid'] = $pid;
                    $infoDatas[$k]['user_id'] = session('admin_user_auth')['uid'];
                    $infoDatas[$k]['create_time'] = time();
                    $infoDatas[$k]['update_time'] = time();
                    $infoDatas[$k]['update_id'] = session('admin_user_auth')['uid'];
                }
                Db::name('item_specs')->insertAll($infoDatas);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>2,'msg'=>'服务器出错,请及时联系管理员']);
            }
        }else{
            $ersoecs = Db::name('item_specs')->where('pid',$data['id'])->select();
            $savearr = []; //修改的数据
            $newarr = [];   //新增的或者减少的
            if( count($ersoecs) == count($infoDatas) ){
                foreach ($ersoecs as $key=>$val){
                    foreach ($infoDatas as $k=>$v){
                        if( $key == $k ){
                            $arr = $val;
                            $arr['title'] = $v['title'];
                            $arr['update_time'] = time();
                            $arr['update_id'] = session('admin_user_auth')['uid'];
                            array_push($savearr,$arr);
                        }
                    }
                }
            }else if(count($ersoecs) > count($infoDatas)){
                foreach ($infoDatas as $key=>$val){
                    foreach ($ersoecs as $k=>$v){
                        if( $key==$k ){
                            $arr = $v;
                            $arr['title'] = $infoDatas[$k]['title'];
                            $arr['update_time'] = time();
                            $arr['update_id'] = session('admin_user_auth')['uid'];
                            array_push($savearr,$arr);
                        }
                    }
                }
                foreach ($ersoecs as $k=>$v){
                    if( $k >= count($infoDatas) ){
                        array_push($newarr,$v);
                    }
                }
            }else if(count($ersoecs) < count($infoDatas)){
                foreach ($infoDatas as $key=>$val){
                    foreach ($ersoecs as $k=>$v) {
                        if( $key == $k ){
                            $arr = $v;
                            $arr['title'] = $val['title'];
                            $arr['update_time'] = time();
                            $arr['update_id'] = session('admin_user_auth')['uid'];
                            array_push($savearr,$arr);
                        }
                    }
                }
                foreach ($infoDatas as $k=>$v){
                    if( $k >= count($ersoecs) ){
                        array_push($newarr,$v);
                    }
                }

            }

            foreach ($savearr as $k=>$v){
                Db::name('item_specs') ->update($v);
            }
            if( count($ersoecs) > count($infoDatas) ){
                //删除
                $ids = [];
                foreach ($newarr as $k=>$v){
                    $ids[] = $v['id'];
                }
                $erids = implode(',',$ids);
                $map[] = ['id','in',$erids];
                Db::name('item_specs')->where($map)->setField('status',0);
            }else if( count($ersoecs) < count($infoDatas) ){
                //新增
                foreach ($newarr as $k=>$v){
                    $newarr[$k]['pid'] = $data['id'];
                    $newarr[$k]['user_id'] = session('admin_user_auth')['uid'];
                    $newarr[$k]['create_time'] = time();
                    $newarr[$k]['update_time'] = time();
                    $newarr[$k]['update_id'] = session('admin_user_auth')['uid'];
                }
                Db::name('item_specs')->insertAll($newarr);
            }

            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            Db::name('item_specs')->where('id',$data['id'])->update($data);
        }
        return json(['code'=>1,'msg'=>'操作成功']);
    }

    //规格启用
    public function specs_start(){
        $id = $this ->request->param('id');
        if( !$id ){
            $this ->error('缺少参数');
        }
        $where[] = ['id|pid','eq',$id];
        $result = Db::name('item_specs') ->where($where)->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    //规格禁用
    public function specs_del(){
        $id = $this ->request->param('id');
        if( !$id ){
            $this ->error('缺少参数');
        }
        $where[] = ['id|pid','eq',$id];
        $result = Db::name('item_specs') ->where($where)->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //运费
    public function postage_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new PostageModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('id desc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

//    //添加运费模板
//    public function postage_add1(){
//        $data = $this ->request ->param();
//        $Post = new PostageModel();
//        if( !empty($data['id']) ){
//            $list = $Post ->where('id',$data['id'])->find();
//            $list['area_ids'] = rtrim($list['area_ids'], ",");
//            $list['area_ids'] = ltrim($list['area_ids'], ",");
//            $list['area_ids'] = explode(',',$list['area_ids']);
//            if( isset($list) && (count($list['area_ids']) == count($area)) ){
//                $list['all'] = 1;
//            }else{
//                $list['all'] = 0;
//            }
//            $this ->assign('list',$list);
//        }
//        $area = Db::name('area')->where(['pid'=>0,'grade'=>1])->order('sort asc')->field('id,area_name,areacode')->select();
//        $this ->assign('area',$area);
//        return $this->fetch();
//    }

    //运费添加操作
    public function postage_doPost1(){
        $data = $this ->request->post();
        $Postage = new PostageModel();
        if( empty($data['area_ids']) ){
            return json(['code'=>2,'msg'=>'请选择城市']);
        }
        if( empty($data['title']) || empty($data['type']) ){
            return json(['code'=>2,'msg'=>'输入标题或选择运费类型']);
        }
        $data['area_ids'] = implode(',',$data['area_ids']);
        $areaWhere[] = ['id','in',$data['area_ids']];
        $area = DB::name('area')->where($areaWhere)->column('area_name');
        $data['area_names'] = implode(',',$area);
        $data['area_ids'] = ','.$data['area_ids'].',';

        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $res = $Postage ->insert($data);
        }else{
            $res = $Postage ->update($data);
        }
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //运费删除
    public function postage_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new PostageModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //运费启用
    public function postage_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new PostageModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    /***
     * 运费管理
     * @return mixed|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postage_add(){
        return  $this ->fetch();
    }

    //运费添加操作
    public function postage_doPost(){
        $data = $this ->request->post();
        $data['citys'] = json_decode($data['citys'],true);
        if( count($data['citys']) <= 0 ){
            return json(['code'=>2,'msg'=>'请选择城市']);
        }
        if( empty($data['title']) || empty($data['type']) ){
            return json(['code'=>2,'msg'=>'输入标题或选择运费类型']);
        }
        foreach ($data['citys'] as $val){
            if( !isset($val['id']) ){
                return json(['code'=>2,'msg'=>'当前有列表未选择地址']);
            }
        }
        $PostageModel = new PostageModel();
        $postageData = array(
            'title' =>$data['title'],
            'status'    =>1,
            'user_id'   =>session('admin_user_auth')['uid'],
            'create_time'   =>time(),
            'type'      =>$data['type']
        );
        $postageInfoData = [];  //详情
        foreach ($data['citys'] as $k=>$v){
            $arr = array(
                'area_ids'  =>$v['id'],
                'area_names'    =>$v['city_name'],
                'first'    =>$v['first'],
                'first_price'    =>$v['first_price'],
                'two'    =>$v['two'],
                'two_price'    =>$v['two_price'],
            );
            array_push($postageInfoData,$arr);
        }

        // 启动事务
        Db::startTrans();
        try {
            $postageId = $PostageModel ->insertGetId($postageData);
            foreach ($postageInfoData as $k=>$v){
                $postageInfoData[$k]['postage_id'] = $postageId;
            }
            Db::name('postage_info') ->insertAll($postageInfoData);
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>2,'msg'=>'内部错误']);
        }
        return json(['code'=>1,'msg'=>'操作成功']);
    }

    /***
     * 编辑运费模板
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function postage_save(){
        $data = $this ->request ->param();
        $PostageModel = new PostageModel();
        $list = $PostageModel ->where('id',$data['id'])->find();
        $this ->assign('list',$list);
//        dump($list);
        return $this ->fetch();
    }

    // 选择 城市---弹出框
    public function select_city(){

        $ids = input('ids');
        $names = input('names');
        $area = null;
        if(empty($ids)){
            $area = Db::name('area')->where(['pid'=>0,'grade'=>1])->order('sort asc')->field('id,area_name')->select();
        }else{
            $area = Db::name('area')->where(['pid'=>0,'grade'=>1])->where("id",'not in',$ids)
                ->order('sort asc')->field('id,area_name')->select();
        }
        $area2 = null;
        $index = 0;
        $arr3= null;
        if(!empty($names)){
            $arr = explode(",",$names);

            $k=0;
            foreach ($area as $value){
                $id = $value['id'];
                if(in_array($id,$arr)){
                    $value['isch']=1;

                    $value2['id']=$value['id'];
                    $value2['name']=$value['area_name'];
                    $arr3[$k] =$value2;
                    $k++;
                }else{
                    $value['isch']=0;
                }
                $area2[$index]=$value;
                $index++;
            }
        }else{
            foreach ($area as $value){

                $value['isch']=0;
                $area2[$index]=$value;
                $index++;
            }
        }

        $json = "";
        if($arr3!=null){
            $json = json_encode($arr3);
        }

//        $area = json_encode($area);
        $this ->assign('area',$area2);
        $this ->assign('json',$json);
        return  $this ->fetch();
    }

    //服务管理
    public function item_service(){
        if ($this->request->isAjax()) {
            $ItemUnit = new EnsureModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('title');
            $where = [];
            $where[] = ['status','neq',0];
            if( !empty($title) ){
                $where[] = ['title','like',"%$title%"];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('id desc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    //添加
    public function service_add(){
        $data = $this ->request->param();
        if( !empty($data['id']) ){
            $list = Db::name('item_ensure')->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    //添加
    public function service_doPost(){
        $data = $this ->request ->post();
        $Ensure = new EnsureModel();
        if( empty($data['title']) || empty($data['content']) ){
            return json(['code'=>0,'msg'=>'标题或内容不能为空']);
        }
        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['create_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $data['update_time'] = time();
            $res = $Ensure ->insert($data);
        }else{
            $data['update_id'] = session('admin_user_auth')['uid'];
            $data['update_time'] = time();
            $res = $Ensure ->where('id',$data['id'])->update($data);
        }
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    //服务禁用
    public function service_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new EnsureModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>2,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //服务启用
    public function service_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $ItemUnit = new EnsureModel();
        $result = $ItemUnit ->where('id',$data['id'])->update(['status'=>1,'update_time'=>time(),'update_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    /***
     * 品牌列表
     */
    public function brand_list(){
        if ($this->request->isAjax()) {
            $ItemUnit = new BrandModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $data = $this ->request ->param();
            $where = [];
            $where[] = ['status','neq',0];
            if( $title ){
                $where[] = ['title','like','%'.$title.'%'];
            }
            if(!empty($data['type'])){
                $where[] = ['type','eq',$data['type']];
            }
            $list = $ItemUnit->where($where)->page($page,$limit)->order('id desc')->select();
            $total = $ItemUnit->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * 添加品牌
     */
    public function brand_add(){
        $data = $this ->request ->param();
        if( !empty($data['id']) ){
            $list = Db::name('brand')->where('id',$data['id'])->find();
            $this ->assign('list',$list);
        }
        return $this->fetch();
    }

    /***
     * 添加品牌操作
     */
    public function brand_doPost(){
        $data = $this ->request ->post();
        unset($data['file']);
        if( empty($data['title']) ){
            return json(['code'=>0,'msg'=>'请输入品牌名称']);
        }
        if( empty($data['thumb']) ){
//            return json(['code'=>0,'msg'=>'请上传缩略图']);
        }
        if( empty($data['title']) ){
            return json(['code'=>0,'msg'=>'请输入品牌名称']);
        }
        $pinyin = new \Overtrue\Pinyin\Pinyin();
//        $quanpin = $pinyin ->convert($data['title']);
        $quanpin = explode('-',$pinyin ->permalink($data['title']));
        $quanpinstr = '';
        foreach ( $quanpin as $v ){
            $quanpinstr .= $v;
        }
        $data['pinyin'] = $quanpinstr;
        $data['tag'] = substr(ucwords($quanpinstr),0,1);
        $data['simplicity'] = $pinyin->abbr($data['title']);
        $data['sort'] = empty($data['sort'])?0:$data['sort'];
        $data['ratio'] = round($data['ratio'],2);
        $data['update_time'] = time();
        if( empty($data['id']) ){
            $data['create_time'] = time();
            $data['user_id'] = session('admin_user_auth')['uid'];
            $res = Db::name('brand')->insert($data);
        }else{
            $res = Db::name('brand')->where('id',$data['id'])->update($data);
        }
        if( $res ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
    }

    /***
     * 品牌禁用
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function brand_del(){
        $data = $this ->request ->param();
        $ItemCategory = new BrandModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])
            ->update(['status'=>2,'delete_time'=>time()]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    /***
     * 品牌启用
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function brand_start(){
        $data = $this ->request ->param();
        $ItemCategory = new BrandModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $result = $ItemCategory ->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }

    // 选择商品  规格--后  返回数据
    public function selectitem_specs(){
        return $this->fetch();
    }
}
