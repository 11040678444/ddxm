<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\mall\model\items\ItemModel;
use app\mall\model\MailactivityModel;
use app\mall\model\seckill\SeckillModel;
use app\mall\model\seckill\FlashSaleModel;
use think\Db;
/**
 * 商城 ---   活动
 */
class Mailactivity extends Adminbase
{
    /***
     * 秒杀列表
     */
    public function index(){

        if ($this->request->isAjax()) {

            $mailacticity = new MailactivityModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 1);
            $result = array("code" => 0, "count" => $mailacticity->getListCount(), "data" => $mailacticity->getList($limit,$page));
            return json($result);
        }
        return $this->fetch();
    }

    //添加活动
    public function add(){
        $data = $this ->request ->param();

        if ($this->request->isAjax()) {
            $mailacticity = new MailactivityModel();
            $flag =  $mailacticity->add($data);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        //查询服务卡 列表
        $list = \db('ticket_card')
            ->field('id,card_name')
            ->where('status','1')
            ->where('del','0')
            ->order('id','desc')->select();

        //查询门店 列表
        $shoplist = \db('shop')
            ->field('id,name')
            ->where('status','1')
            ->where('delete_id','0')
            ->order('id','desc')->select();
        $this ->assign('list',$list);
        $this ->assign('shop_list',$shoplist);
        return $this ->fetch();
    }


    //编辑活动
    public function update(){
        $data = $this ->request ->param();
        $mailacticity = new MailactivityModel();
        if ($this->request->isAjax()) {

            $flag =  $mailacticity->editAd($data);

            return json(['code' => $flag['code'], 'data' => $flag['data'], 'msg' => $flag['msg']]);
        }

        //查询服务卡 列表
        $list = \db('ticket_card')
            ->field('id,card_name')
            ->where('status','1')
            ->where('del','0')
            ->order('id','desc')->select();

        //查询门店 列表
        $shoplist = \db('shop')
            ->field('id,name')
            ->where('status','1')
            ->where('delete_id','0')
            ->order('id','desc')->select();

        $id = input('param.id');
        $this->assign('ad',$mailacticity->getOneAd($id));
        $this ->assign('list',$list);
        $this ->assign('shop_list',$shoplist);
        return $this ->fetch();
    }

    /***
     * 下架
     */
    public function del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id为空');
        }
            //修改商品表
            $itemData = [
                'status' =>2,
            ];
            Db::name('online_activity')->where('id',$data['id'])->update($itemData);
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
        //修改商品表
        $itemData = [
            'status' =>1,
        ];
        Db::name('online_activity')->where('id',$data['id'])->update($itemData);
        $this ->success('操作成功');
    }


}