<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/11/6 0006
 * Time: 上午 11:43
 * 营业成本充值
 *  其用于门店常有买商品送商品情况，应财务部需求赠送商品不能在“股东数据->营业成本中”
 *  因此这里需要添加一条为负的营业成本来冲正，商品库存量无需返回，因为门店赠送的商品
 *  对应供应商会返回给门店，因此无需对商品库存进行操作。
 */

namespace app\admin\controller;

use app\admin\model\statistics\StatisticsLog;
use app\common\controller\Adminbase;

class CostReverse extends Adminbase
{
    /**
     * 列表查询
     * @return mixed
     */
    public function getList()
    {
        try
        {
            if(request()->isAjax())
            {
                $param = request()->param();

                $where[] = ['data_type','=',1];
                $where[] = ['price','<',0];
                $where[] = ['type','=',8];
                //拼接查询条件
                if(!empty($param['name']))
                {
                    $where[] = ['title|order_sn','like',"%{$param['name']}%"];
                }

                if(!empty($param['start_time']) && !empty($param['end_time']))
                {
                    $where[] = ['create_time','between',"{$param['start_time']},{$param['end_time']}"];
                }

                if(!empty($param['shop_id']))
                {
                    $where[] = ['shop_id','=',$param['shop_id']];
                }

                $list = (new StatisticsLog())->getCostReverseList($where);

                $result = array("code" => 0, "count" => $list['total'], "data" => $list['data']);
                return json($result);
            }

            $shop = db('shop')->where('status',1)->field('id,name')->select();
            $this ->assign('shop',$shop);
            return $this->fetch('index');
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    /**
     * 新增
     * @return mixed
     */
    public function add()
    {
        try
        {
            if(request()->isAjax())
            {
                //数据集
                $data = json_decode(request()->post('data'),true);

                //获取原有支付方式
                $pay_way = db('statistics_log')->where(['order_id'=>$data[0]['order_id'],'data_type'=>1])->value('pay_way');
                empty($pay_way) ? return_error('获取支付方式错误') : '';

                //数据验证
                foreach ($data as $key=>$val)
                {
                    dataValidate($val,[
                        'order_sn'=>'require|alphaNum',
                        'order_id'=>'require|number',
                        'price'=>'require|lt:0',
                        'title'=>'require',
                        'shop_id'=>'require|number|gt:0'
                    ]);

                   //整理数据
                   $data[$key]['type'] = 8;
                   $data[$key]['pay_way'] = $pay_way;
                   $data[$key]['create_time'] = time();
                }

                $res = db('statistics_log')->insertAll($data);

                !empty($res) ? return_succ([],'添加成功') : return_error('添加失败');
            }
            return $this->fetch();
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit()
    {
        try
        {
            $model = (new StatisticsLog());

            if(request()->isAjax())
            {
                $data = json_decode(request()->post('data'),true)[0];

                //数据验证
                dataValidate($data,[
                    'id'=>'require|number|gt:0',
                    'title|描述'=>'require'
                ]);

                $res = $model->allowField(true)->save(['title'=>$data['title']],['id'=>$data['id']]);

                !empty($res) ? returnJson(200,$data,'修改成功') : returnJson(300,$data,'修改失败');
            }

            $info = $model->getCostReverseList(['sl.id'=>input('id/d')])['data'];

            $this->assign('data',$info);
            return $this->fetch();
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    /**
     * 查询订单商品详细数据
     */
    public function find()
    {
        try
        {
            if(request()->isAjax())
            {
                $order_code = input('order_code/s');

                empty($order_code) ? return_error('参数错误') : '';

                //获取到订单主键ID
                $order_info = db('order')->field('id,shop_id')->where(['sn'=>$order_code])->find();

                empty($order_info) ? return_error('该订单不存在') : '';

                //获取购买商品详细
                $info = db('order_goods')->field('id,order_id,subtitle,oprice')->where(['order_id'=>$order_info['id']])->select();

                returnJson(200,['data'=>$info,'shop_id'=>$order_info['shop_id']],'ok');
            }
        }catch (\Exception $e){
            returnJson(500,$e->getCode(),$e->getMessage());
        }
    }

    /**
     * 删除
     */
    public function del()
    {
        try
        {
            if(request()->isAjax())
            {
                $id = input('id/d');

                empty($id) ? return_error('参数错误') : '';

                $res = (new StatisticsLog())->where(['id'=>$id])->delete();

                !empty($res) ? return_succ([],'删除成功') : return_error('删除失败');
            }
        }catch (\Exception $e){
           returnJson(500,$e->getCode(),$e->getMessage());
        }
    }
}