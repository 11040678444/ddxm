<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/15 0015
 * Time: 上午 11:10
 *
 * 专题-》打包一口价
 */

namespace app\mall_admin_pack\controller;
use app\common\controller\Backendbase;
use app\mall_admin_pack\model\StPack as pack;

class Stpack extends  Backendbase
{
    /**
     * 列表查询
     * @throws \Exception
     */
    public function getPackList()
    {
        try{
            if(request()->isPost())
            {
                $st_spack = new pack();
                $list = $st_spack->getPackList(empty(input('limit'))?10:input('limit'));

                return_succ($list,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 活动新增
     * @throws \Exception
     */
    public function add()
    {
        try{
            if(request()->isPost())
            {
               $data = request()->post();
               //数据验证
               dataValidate($data,[
                   'p_name|活动名称'=>'require',
                   'p_condition1|实付金额'=>'require',
                   'p_condition2|任选x件'=>'require',
                   'item_id|商品'=>'require',
               ]);

               //判断是存在重复商品
                if(count($data['item_id']) != count(array_unique($data['item_id'])))
                {
                    return_error('存在商品重复');
                }

                $st_spack = new pack();
                $res = $st_spack->add($data);

                !empty($res) ? return_succ([],'新增成功') : return_error('新增失败');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 活动编辑
     * @throws \Exception
     */
    public function edit()
    {
        try{
            if(request()->isPost())
            {
                $data = request()->post();
                //数据验证
                dataValidate($data,[
                    'p_name|活动名称'=>'require',
                    'p_condition1|实付金额'=>'require',
                    'p_condition2|任选x件'=>'require',
                    'item_id|商品'=>'require',
                    'id|修改对象ID'=>'require',
                ]);

                $st_spack = new pack();
                $res = $st_spack->add($data);

                !empty($res) ? return_succ([],'编辑成功') : return_error('编辑失败');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**活动上下架
     * @throws \Exception
     */
    public function upAndDown()
    {
        try{
            if(request()->isPost())
            {
                //数据验证
                dataValidate(request()->param(),[
                    'id|修改对象ID'=>'require',
                    'p_status|状态'=>'require',
                ]);

                $st_spack = new pack();
                $res = $st_spack->changeField(request()->param());

                !empty($res) ? return_succ([],'操作成功') : return_error('操作失败');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**活动删除
     * @throws \Exception
     */
    public function del()
    {
        try{
            if(request()->isPost())
            {
                empty(request()->param()['is_delete']) ? return_error('非法操作') : '';

                //数据验证
                dataValidate(request()->param(),[
                    'id|修改对象ID'=>'require',
                    'is_delete|状态'=>'require',
                ]);

                $st_spack = new pack();
                $res = $st_spack->changeField(request()->param());

                !empty($res) ? return_succ([],'删除成功') : return_error('删除失败');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }

    /**
     * 查询单个活动详细
     */
    public function getPackInfo()
    {
        try{
            if(request()->isPost())
            {
                //数据验证
                dataValidate(request()->param(),[
                    'id|查询对象ID'=>'require',
                ]);

                $info = (new pack())->getPackInfo(input('id'));

                return_succ($info,'ok');
            }
        }catch (\Exception $e){
            return_error($e->getMessage());
        }
    }
}