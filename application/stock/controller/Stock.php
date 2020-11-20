<?php

namespace app\stock\controller;

use app\stock\Base;
use app\common\controller\Backendbase;
use app\stock\model\stock\StockItemModel;
use app\stock\model\stock\StockLogItemModel;
use app\stock\model\stock\StockLogModel;
use app\stock\model\stock\StockModel;

/**
 * controller 盘点单控制器
 */
class Stock extends Backendbase
{
    /***
     * 盘点单列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_list(){
        $StockLog = new StockLogModel();
        $data = $this ->request->param();
        $res = $StockLog ->getList($data);
        return json($res);
    }

    /***
     * 盘点单详情
     */
    public function info(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'单据ID错误']);
        }
        $page = !empty($data['page'])?$data['page']:1;
        $limit = !empty($data['limit'])?$data['limit']:10;
        $StockItem = new StockLogItemModel();
        $list = $StockItem ->getItemList($data['id'],$data)->page($page,$limit)->select();
        $count = $StockItem ->getItemList($data['id'])->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /***
     * 新增盘点单
     */
    public function add(){
        $data = $this ->request ->param();
        $data['user_id'] = self::getUserInfo()['userid'];
        $data['is_admin'] = 1;
        $res = (new StockLogModel()) ->add($data);
        return $res;
    }

    /***
     * 编辑盘点单
     */
    public function edit(){
        $data = $this ->request ->param();
        $res = (new StockLogModel()) ->edit($data);
        return $res;
    }

    /***
     * 确认盘点单
     */
    public function confirm(){
        $data = $this ->request ->param();
        $data['user_id'] = self::getUserInfo()['userid'];
        $data['is_admin'] = 1;
        $res = (new StockLogModel()) ->confirm($data);
        return $res;
    }

    /***
     * 盘点单删除
     */
    public function delete(){
        $data = $this ->request ->param();
        $res = (new StockLogModel()) ->delete_list($data);
        return $res;
    }

    /***
     * 盘盈盘亏单列表
     */
    public function check_list(){
        $data = $this ->request ->param();
        $list = (new StockModel()) ->check_list($data);
        return json($list);
    }

    /***
     * 盘盈盘亏单详情
     */
    public function check_info(){
        $data = $this ->request ->param();
        $list = (new StockItemModel()) ->getItemList($data['id'])->select()->toArray();
        if( $list == false ){
            return json(['code'=>100,'msg'=>'获取失败']);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 盘盈盘亏单确认库存
     */
    public function check_confirm(){
        $data = $this ->request ->param();
        $res = (new StockModel()) ->check_confirm($data);
        return $res;
    }

}
