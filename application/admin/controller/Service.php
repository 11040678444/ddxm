<?php

// +----------------------------------------------------------------------
// | 服务管理
// +----------------------------------------------------------------------
namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\shop\ServiceModel;
use app\admin\model\shop\ServicePriceModel;
use app\admin\model\item\ItemCategoryModel;
use think\Db;


class Service extends Adminbase
{
	public function show_list(){
		if ($this->request->isAjax()) {
			$Service = new ServiceModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $sname = $this ->request ->param('name');
            $where = [];
             $where[] = ['status','neq',0];
            if( !empty($sname) ){
            	$where[] = ['sname','like',"%$sname%"];
            }
            $list = $Service->where($where)->page($page,$limit)->order('sort asc')->select()->append(['types']);
            $total =  $Service->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	//添加
	public function add(){
		$data = $this ->request ->param();
		$ServicePrice = new ServicePriceModel();
		if( !empty($data['id']) ){
			$list = Db::name('service')->where('id',$data['id'])->field('id,sort,sname,status,type,cover')->find();
			$this ->assign('list',$list);
			$servicePrice = $ServicePrice->where('service_id',$data['id'])->select();
			$this ->assign('servicePrice',$servicePrice);			
		}

		$level = Db::name('member_level')->where('status',1)->field('id,level_name')->select();
		$this ->assign('level',$level);

		$where[] = ['code','neq',0];
		$where[] = ['status','eq',1];
		$shop = Db::name('shop')->where($where)->field('id,name')->select();
		$this ->assign('shop',$shop);

		$category = Db::name('item_category')->where(['status'=>1,'type'=>2,'pid'=>0])->select();
		$this ->assign('category',$category);

		return $this->fetch();
	}

	//提交处理
	public function doPost(){
		$data = $this ->request ->post();
		if( !empty($data['images']) ){
            $data['cover'] = implode(',',$data['images']);
        }

		// if( empty($data['sname']) || empty($data['sort']) ){
		// 	$result = array("code" => 0, "msg" => '请填完所有内容');
  		//  	return json($result);
		// }
		if( empty($data['sort']) ){
			$data['sort'] = 1;
		}
		if( empty($data['sname']) ){
			$result = array("code" => 0, "msg" => '请输入服务名称');
           	return json($result);
		}
		if( empty($data['type']) ){
			$result = array("code" => 0, "msg" => '请选择分类');
           	return json($result);
		}
		$Service = new ServiceModel();
		$ServicePrice = new ServicePriceModel();
		$price = $data['shop_price'];
		unset($data['shop_price']);
		
		$servicePriceData = [];		//价格数组
		$newArray = [];
		foreach ($price as $key => $value) {
			foreach ($value as $ke => $val) {
				foreach ($val as $k => $v) {
					if( $v !== '' ){
						$array = array(
							'shop_id'	=>$ke,
							'price'		=>$v,
							'level_id'	=>$k
							);
						array_push($servicePriceData, $array);
					}
					$array1 = array(
						'shop_id'	=>$ke,
						'price'		=>$v,
						'level_id'	=>$k
					);
					array_push($newArray, $array1);
				}
			}
		}

		$newArray = $this ->array_group_by($newArray,'shop_id');
		foreach ($newArray as $key => $value) {
			for ($i=0; $i < count($value); $i++) {
				if( $value[$i]['price'] != '' ){
					for ($j=0; $j < count($value); $j++) { 
						if( $value[$j]['price'] == '' ){
							return json(['code'=>0,'msg'=>'选择的门店必须每个等级都填写价格']);
						}
					}
				}else{
					for ($j=0; $j < count($value); $j++) { 
						if( $value[$j]['price'] != '' ){
							return json(['code'=>0,'msg'=>'选择的门店必须每个等级都填写价格']);
						}
					}
				}
			}
		}
		unset($data['file']);
        unset($data['images']);
//        dump($data);die;
		if ( empty($data['id']) ) {
			// 启动事务
			Db::startTrans();
			try {
				$data['create_time'] = time();
				$data['update_time'] = time();
				$data['user_id'] = session('admin_user_auth')['uid'];
				$data['update_id'] = session('admin_user_auth')['uid'];
			    $serviceId = $Service ->insertGetId($data);
			    foreach ($servicePriceData as $key => $value) {
			    	$servicePriceData[$key]['service_id'] = $serviceId;
			    }

			    $ServicePrice ->insertAll($servicePriceData);

			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    $result = array("code" => -1, "msg" => $e->getMessage());
        		return json($result);
			}
		}else{
			// 启动事务
			Db::startTrans();
			try {
				$data['update_time'] = time();
				$data['update_id'] = session('admin_user_auth')['uid'];
			    $serviceId = $Service->where('id',$data['id']) ->update($data);
			    foreach ($servicePriceData as $key => $value) {
			    	$servicePriceData[$key]['service_id'] = $data['id'];
			    	$serviceWhere['shop_id'] = $value['shop_id'];
			    	$serviceWhere['level_id'] = $value['level_id'];
			    	$serviceWhere['service_id'] = $data['id'];
			    	$ServicePrice->where($serviceWhere) ->delete();
			    }
			    $ServicePrice ->insertAll($servicePriceData);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    $result = array("code" => -1, "msg" => '服务名称过长请重新输入');
        		return json($result);
			}
		}
		$result = array("code" => 1, "msg" => '操作成功');
        return json($result);
	}
	public static function array_group_by($arr, $key)
    {
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('array_group_by', $parms);
            }
        }
        return $grouped;
    }
	//服务删除
	public function del(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$Service = new ServiceModel();
		$result = $Service->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("service/show_list"));
		}else{
			$this ->error('操作失败');
		}
	}

    /***
     * 服务上架
     * @return mixed|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function start(){
        $data = $this ->request->param();
        if( empty($data['id']) ){
            $this ->error('参数为空');
        }
        $Service = new ServiceModel();
        $result = $Service->where('id',$data['id'])->update(['status'=>1]);
        if( $result ){
            $this ->success('操作成功',url("service/show_list"));
        }else{
            $this ->error('操作失败');
        }
    }

    /***
     * 下架
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function del1(){
        $data = $this ->request->param();
        if( empty($data['id']) ){
            $this ->error('参数为空');
        }
        $Service = new ServiceModel();
        $result = $Service->where('id',$data['id'])->update(['status'=>2]);
        if( $result ){
            $this ->success('操作成功',url("service/show_list"));
        }else{
            $this ->error('操作失败');
        }
    }


	//服务分类列表
	public function category_list(){
		$data = $this ->request ->param('cname');
        if ($this->request->isAjax()) {
            $ItemCategory = new ItemCategoryModel();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $cname = $this->request->param('cname');
            $pid =  $this->request->param('pid')?$this->request->param('pid'):0;
            $where = [];
            $where[] = ['pid','=',$pid];
            if( !empty($cname) ){
                $where[] = ['cname','like',"%$cname%"];
            }
            $where[] = ['type','eq',2];
            $list = $ItemCategory->where($where)->page($page,$limit)->order('sort desc')->select();
            $total = $ItemCategory->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list,'pid'=>$pid);
            return json($result);
        }
        $this ->assign('cname',$data);
        return $this->fetch();
	}

	 //分类添加
    public function category_add(){
        $data = $this ->request ->param();
        $ItemCategory = new ItemCategoryModel();
        if( !empty($data['id']) ){
            $list = $ItemCategory ->where('id',$data['id'])->field('id,sort,cname,pid')->find();
            $this ->assign('list',$list);
        }
        $pid = 0;
        $where = [];
        $where[] = ['pid','=',$pid];
        $where[] = ['status','=',1];
        $where[] = ['type','=',2];
        $pid = $ItemCategory ->where($where)->order('sort asc')->field('id,cname')->select();
        $this ->assign('pids',$pid);
        return $this->fetch();
    }

    //分类添加的操作
    public function category_doPost(){
        $data = $this ->request ->post();
        if( empty($data['cname']) ){
            return json(['code'=>-1,'msg'=>'名称不能为空']);
        }
        $ItemCategory = new ItemCategoryModel();

        if( empty($data['id']) ){
            $data['user_id'] = session('admin_user_auth')['uid'];
            $data['addtime'] = time();
            $data['update_time'] = time();
            $data['update_id'] = session('admin_user_auth')['uid'];
            $data['type'] = 2;
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
        $ItemCategory = new ItemCategoryModel();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }

        $where['status'] = 1;
        $where['pid'] = $data['id'];
        $info = $ItemCategory ->where($where)->select();    //查询是否存在二级分类
        // if( count($info) >0 ){
        //     $this ->error('此分类下存在正在使用的二级分类,请勿删除');
        // }
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
        $ItemCategory = new ItemCategoryModel();
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
}