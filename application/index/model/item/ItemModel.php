<?php

// +----------------------------------------------------------------------
// | 普通商品
// +----------------------------------------------------------------------
namespace app\index\model\item;

use app\index\model\item\ShopItemModel;
use think\Model;
use think\Db;

class ItemModel extends Model
{
	protected $table = 'ddxm_item';

	/***
	门店 前台收银：
	普通门店商品列表
  	$data['type']:商品的分类id，如果存在type，则必须传参type_category
  	$data['type_category']:区分分类id的类型，1为1级分类id，2为2级分类id
  	$data['title']:商品名称
  	*/
	public function getItems($data,$shop_id){
		$where = [];
		if( !empty($data['type']) ){
			//分类
			if( $data['type_category'] == 1 ){
				$where[] = ['a.type_id','=',$data['type']];
			}else if( $data['type_category'] == 2 ){
				$where[] = ['a.type','=',$data['type']];
			}
		}

		if( !empty($data['title']) ){
			//商品名称
			$where[] = ['a.title|a.bar_code','like','%'.$data['title'].'%'];
		}
        if( !empty($data['bar_code']) ){
            //商品名称
            $where[] = ['a.bar_code','like','%'.$data['bar_code'].'%'];
        }
		$where[] = ['a.item_type','in','2,3'];
		$where[] = ['a.status','=',1];
		$where[] = ['b.status','=',1];
		$where[] = ['b.shop_id','=',$shop_id];

		$list = $this
			->alias('a')
			->where($where)
			->join('ddxm_item_price b','a.id=b.item_id')
			->field('a.id,a.title,a.pic,a.bar_code,b.selling_price price,b.minimum_selling_price')
			->page($data['page'])
			->order('a.id desc')
			->select();
		return $list;
	}

	/***
	门店 进销存（获取门店的所有商品库存不为0）
	普通门店商品列表
  	$data['type']:商品的分类id，如果存在type，则必须传参type_category
  	$data['type_category']:区分分类id的类型，1为1级分类id，2为2级分类id
  	$data['title']:商品名称
  	*/
	public function getItemList($data,$shop_id){
		$where = [];
		if( !empty($data['type']) ){
			$where[] = ['a.type','=',$data['type']];
		}
		if( !empty($data['type_id']) ){
			$where[] = ['a.type_id','=',$data['type_id']];
		}

		if( !empty($data['title']) ){
			//商品名称
			$where[] = ['a.title|a.bar_code','like','%'.$data['title'].'%'];
		}

		if( !empty($data['bar_code']) ){
			$where[] = ['a.bar_code','=',$data['bar_code']];
		}
		$where[] = ['a.status','neq',3];
		$where[] = ['c.shop_id','eq',$shop_id];
		$where[] = ['c.stock','gt',0];
		$list = $this
			->alias('a')
			->where($where)
			->join('shop_item c','a.id=c.item_id','LEFT')
			->field('a.id,a.title,a.bar_code,a.type_id,a.type,c.stock')
			->page($data['page'])
			->order('a.id desc')
			->select()->append(['types','type_ids']);
		$count = $this
            ->alias('a')
            ->where($where)
            ->join('shop_item c','a.id=c.item_id','LEFT')
            ->field('a.id,a.title,a.bar_code,a.type_id,a.type,c.stock')
            ->count();
		return ['data' =>$list ,'count'=>$count];
	}

	/***
	门店 进销存（获取门店的所有商品，库存为0）
	普通门店商品列表
  	$data['type']:商品的分类id，如果存在type，则必须传参type_category
  	$data['type_category']:区分分类id的类型，1为1级分类id，2为2级分类id
  	$data['title']:商品名称
  	*/
	public function getItemList1($data,$shop_id){
		$where = [];
		if( !empty($data['type']) ){
			$where[] = ['a.type','=',$data['type']];
		}
		if( !empty($data['type_id']) ){
			$where[] = ['a.type_id','=',$data['type_id']];
		}

		if( !empty($data['title']) ){
			//商品名称
			$where[] = ['a.title|a.bar_code','like','%'.$data['title'].'%'];
		}

		if( !empty($data['bar_code']) ){
			$where[] = ['a.bar_code','=',$data['bar_code']];
		}
		$where[] = ['a.status','neq',3];
		$where[] = ['c.shop_id','=',$shop_id];
		$where[] = ['c.stock','=',0];
		$list = $this
			->alias('a')
			->where($where)
			->join('shop_item c','a.id=c.item_id','LEFT')
			->field('a.id,a.title,a.type_id,a.bar_code,a.type,c.stock')
			->page($data['page'])
			->order('a.id desc')
			->select()->append(['types','type_ids']);
		$count = $this
            ->alias('a')
            ->where($where)
            ->join('shop_item c','a.id=c.item_id','LEFT')
            ->field('a.id,a.title,a.type_id,a.bar_code,a.type,c.stock')
            ->count();
        return ['data' =>$list ,'count'=>$count];
	}

    /***
     * 盘点单第二期：获取全部商品
     * @param $val
     * @param $data
     * @return mixed|string
     */
    public function twoItems($data,$shop_id){
        $where = [];
        if( !empty($data['title']) ){
            $where[] = ['title|bar_code','like','%'.$data['title'].'%'];
        }
        if( !empty($data['type']) ){
            $where[] = ['type','=',$data['type']];
        }
        if( !empty($data['type_id']) ){
            $where[] = ['type_id','=',$data['type_id']];
        }
        $where[] = ['status','neq',3];
        $where[] = ['item_type','in','2,3'];
        $list = $this ->where($where) ->page($data['page'])->field('id,title,type,type_id,bar_code')->select()->append(['types','type_ids']);
        //获取商品对应门店的库存
        if( count($list) <= 0 ){
            return  ['count' =>0, 'data' =>[]];
        }
        $count = $this ->where($where)->count();
        foreach ( $list as $k=>$v ){
            $where = [];
            $where[] = ['shop_id','eq',$shop_id];
            $where[] = ['item_id','eq',$v['id']];
            $stock = Db::name('shop_item') ->where($where) ->value('stock');
            if( $stock ){
                $list[$k]['stock'] = $stock;
            }else{
                $list[$k]['stock'] = 0;
            }
        }

        return ['count' =>$count, 'data' =>$list];

    }


	//获取商品的分类名称
	public function getTypeIdsAttr($val,$data){
		if($data['type_id'] == 0){
			return '暂无一级分类';
		}
		return Db::name('item_category')->where('id',$data['type_id'])->value('cname');
	}

	//获取商品的分类名称
	public function getTypesAttr($val,$data){
		if($data['type'] == 0){
			return '暂无一级分类';
		}
		return Db::name('item_category')->where('id',$data['type'])->value('cname');
	}

	//商品确定门店，只需要按照商品id查询库存
	public function getGoods($itemIds,$shop_id){
		$where= [];
		$where[] = ['a.id','in',$itemIds];
		$where[] = ['a.item_type','in','2,3'];
		$where[] = ['a.status','=',1];
		$where[] = ['b.status','=',1];
		$where[] = ['b.shop_id','=',$shop_id];
		$list = $this
			->alias('a')
			->where($where)
			->join('ddxm_item_price b','a.id=b.item_id')
			->field('a.id,a.title,a.pic,a.bar_code,a.type_id,a.type,b.selling_price price,b.minimum_selling_price')
//			->page($data['page'])
			->order('a.id desc')
			->select();
		if( count($list)==0 ){
			return 0;
		}
		
		foreach ($list as $key => $value) {
			$list[$key]['stock'] = 0;
		}

		//直接显示总库存，用于比较库存是否不足
		$stockWhere[] = ['shop_id','eq',$shop_id];
		$stockWhere[] = ['item_id','in',$itemIds];
		$stock = Db::name('shop_item')
				->where($stockWhere)
				->field('id,stock,item_id')
				->select();

		foreach ($list as $key => $value) {
			foreach ($stock as $k => $v) {
				if( $value['id'] == $v['item_id'] ){
					$list[$key]['stock'] = $v['stock'];
					$list[$key]['shop_item_id'] = $v['id'];
				}
			}
		}
		return $list;
	}

	//获取仓库商品列表
	public function getgood($data)
	{
		$where = [];
		if( !empty($data['shop_id']) ){
			$where[] = ['b.shop_id','eq',$data['shop_id']];
		}
		if( !empty($data['type_id']) ){
			$where[] = ['a.type_id','eq',$data['type_id']];
		}
		if( !empty($data['type']) ){
			$where[] = ['a.type','eq',$data['type']];
		}
		if( !empty($data['title']) ){
			$where[] = ['a.title|a.bar_code','like','%'.$data['title'].'%'];
		}
        if( !empty($data['stock']) ){
            if( $data['stock'] == 1 ){
                $where[] = ['b.stock','eq',0];
            }else{
                $where[] = ['b.stock','neq',0];
            }
        }
		return $this ->alias('a')
				->where($where)
				->join('shop_item b','a.id=b.item_id','LEFT')
				->field('a.id,a.title,a.type_id,a.type,a.bar_code,b.shop_id,b.stock');
	}

}