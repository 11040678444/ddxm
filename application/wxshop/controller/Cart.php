<?php
namespace app\wxshop\controller;

use think\Controller;
use think\Db;
use think\Query;
use think\Request;
use app\wxshop\controller\Token;
use app\wxshop\model\item\CartModel;
/**
购物车
 */
class Cart extends Token
{
    /***
     * cart购物车列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cart_list(){
        $data = $this ->request ->param();
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $memberId = parent::getUserId();
        if( !$memberId ){
            return json(['code'=>100,'msg'=>'清先登录']);
        }
        $CartModel = new CartModel();
        $where[] = ['a.member_id','eq',$memberId];
        $where[] = ['a.status','eq',1];
        $where[] = ['c.status','eq',1];
        $list = $CartModel->alias('a') ->where($where)
            ->join('item b','a.item_id = b.id')
            ->join('specs_goods_price c','a.specs_ids=c.key and a.item_id=c.gid')
            ->join('st_pack_rule spr','a.item_id = spr.item_id','left')//添加打包活动
            ->join('st_pack sp','spr.p_id = sp.id','left')//添加打包活动
            ->field('a.id,a.item_id,a.num,b.title,b.status,b.mold_id,c.key_name,c.price,c.imgurl as pic,
                  store,c.key,sp.p_name,sp.id pack_id,sp.p_condition1,sp.p_condition2')
            ->order('sp.id desc')
            ->page($page)
            ->select()->append(['mold']);

        //处理数据
        $data = ['pack'=>[],'data'=>[]];
        foreach ($list->toArray() as $key=>$value)
        {
            if(!empty($value['pack_id']))
            {
                if(!isset($data['pack'][$value['id']]))
                {
                    $data['pack'][$value['pack_id']]['pack']['id'] = $value['pack_id'];
                    $data['pack'][$value['pack_id']]['pack']['p_name'] = $value['p_name'];
                    $data['pack'][$value['pack_id']]['pack']['p_condition1'] = $value['p_condition1'];
                    $data['pack'][$value['pack_id']]['pack']['p_condition2'] = $value['p_condition2'];

                }
                //同一活动组装在一起
                foreach ($data['pack'] as $k=>$v)
                {
                    if($v['pack']['id'] == $value['pack_id'])
                    {
                        $data['pack'][$value['pack_id']]['data'][]= $value;
                    }
                }

            }else{
                $data['data'][]= $value;
            }
        }
        $data['pack'] = array_values($data['pack']);

        $data['code'] = 200;$data['msg'] = '获取成功';
        return json($data);
    }

    /***
     * 添加到购物车
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function add_cart(){
        $data = $this ->request ->post();
        if( empty($data['item_id']) ){
            return json(['code'=>100,'msg'=>'服务器内部错误']);
        }
        $CartModel = new CartModel();
        $where[] = ['item_id','eq',$data['item_id']];
        $where[] = ['specs_ids','eq',$data['specs_ids']?$data['specs_ids']:''];
        $where[] = ['member_id','eq',parent::getUserId()];
        $info = $CartModel ->where($where)->find();
        if( $info ){
            $newArr = [];
            if( $info['status'] == 1 ){
                $newArr['num'] = $info['num']+($data['num']?$data['num']:1);
                $newArr['create_time'] = time();
            }else{
                $newArr['num'] = $data['num']?$data['num']:1;
                $newArr['create_time'] = time();
                $newArr['status'] = 1;
            }
            $result = $CartModel ->where($where)->update($newArr);
            if( $result ){
                return json(['code'=>200,'msg'=>'加入购物车成功']);
            }else{
                return json(['code'=>100,'msg'=>'网络错误，请刷新重试']);
            }
        }
        $cart = [];     //购物车数据
        $cart['member_id'] = parent::getUserId();
        $cart['item_id'] = $data['item_id'];
        $cart['specs_ids'] = $data['specs_ids']?$data['specs_ids']:'';
        $cart['num'] = $data['num']?$data['num']:1;
        $cart['create_time'] = time();
        $CartModel = new CartModel();
        $res = $CartModel ->insert($cart);
        if( $res ){
            return json(['code'=>200,'msg'=>'加入购物车成功']);
        }else{
            return json(['code'=>100,'msg'=>'网络错误，请刷新重试']);
        }
    }

    /***
     * 修改购物车数量
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function edit_cart(){
        $data = $this ->request ->post();
        if( empty($data['id']) || empty($data['num']) ){
            return json(['code'=>100,'msg'=>'数量错误']);
        }
        if( $data['num'] <=0 ){
            return json(['code'=>100,'msg'=>'数量错误']);
        }
        $Cart = new CartModel();
        $res = $Cart ->where('id',$data['id'])->update(['num'=>$data['num'],'update_time'=>time()]);
        if( $res ){
            return json(['code'=>200,'msg'=>'操作成功']);
        }else{
            return json(['code'=>100,'msg'=>'网络错误，请刷新重试']);
        }
    }

    /***
     * 删除购物车
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del_cart(){
        $data = $this ->request ->post();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择商品']);
        }
        $Cart = new CartModel();
        $where[] = ['id','in',$data['id']];
        $res = $Cart ->where($where)->update(['status'=>0,'delete_time'=>time()]);
        if( $res ){
            return json(['code'=>200,'msg'=>'操作成功']);
        }else{
            return json(['code'=>100,'msg'=>'网络错误，请刷新重试']);
        }
    }

    /***
     * 购物车数量
     */
    public function cart_num(){
        $Cart = new CartModel();
        $where =[];
        $where[] = ['status','eq',1];
        $where[] = ['member_id','eq',self::getUserId()];
        $count = $Cart ->where($where) ->count();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$count]);
    }
}