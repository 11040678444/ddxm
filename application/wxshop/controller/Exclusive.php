<?php

// +----------------------------------------------------------------------
// | 新人专享控制器
// +----------------------------------------------------------------------
namespace app\wxshop\controller;

use app\mall_admin_market\model\exclusive\NewExclusive;
use app\mall_admin_market\model\exclusive\NewExclusiveGoods;
use app\mall_admin_market\model\exclusive\StPayLog;
use app\mall_admin_market\model\special\SpecialType;
use app\wxshop\model\item\ItemModel;

class Exclusive extends Base
{
    //获取专题类型
    public function getTypeList()
    {
        $data = $this ->request ->param();
        $data['type'] = 2;
        $list = (new SpecialType()) ->getTypeList( $data );
        $list = $list['data'];
        foreach ( $list as $k=>$v )
        {
            $list[$k]['item'] = (new NewExclusiveGoods()) ->itemALLList($v['id']);
        }
        $res = [];
        foreach ( $list as $k=>$v ){
            if( !empty($v['item']) )
            {
                array_push($res,$v);
            }
        }
        return_succ($res,'获取成功');
    }

    /***
     * 新人专享商品列表
     */
    public function getItemList()
    {
        $data = $this ->request ->param();
        $list = (new NewExclusiveGoods()) ->itemList($data);
        return_succ($list,'获取成功');
    }

    /***
     * 新人专享商品详情
     */
    public function itemInfo()
    {
        $data = $this ->request ->param();
        if ( empty($data['id']) && empty($data['item_id']) )
        {
            return_error('ID出错');
        }
        //查询商品详情
        $itemInfo = ( new ItemModel() )->alias('a') ->where('a.id',$data['item_id'])
            ->field('a.id,a.status,a.title,a.subtitle,a.mold_id,a.video,a.initial_sales,a.reality_sales,a.lvid,a.content,a.pics,a.own_ratio as ratio')
            ->find()->append(['mold','mold_know','promise'])->toArray();

        //查询新人专享详情
        $newItem = ( new NewExclusive() ) ->itemInfo($data)['data'];

        //查询是否为新人
        $memberId = parent::getToken();
        if ( $memberId )
        {
            $payStatus = ( new StPayLog() ) ->userPayLog(['member_id'=>$memberId]);
        }
        else
        {
            $payStatus = 2;
        }
        $res = ['new_status'=>1,'pay_status'=>$payStatus];
        $itemInfo['activity_id'] = $data['id'];
        $result = [
            'item_type' =>$res,
            'item_info' =>$itemInfo,
            'item_attr' =>$newItem
        ];
        return_succ($result,'获取成功');
    }
}