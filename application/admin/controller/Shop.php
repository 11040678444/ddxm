<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use think\Db;
use app\admin\model\shop\ShopModel;
use app\admin\model\shop\ShopPostModel;
use app\admin\model\shop\ShopWorkerModel;
use app\admin\model\shop\ShareholderModel;
/**
	门店管理controller
*/
class Shop extends Adminbase
{
	//门店列表
	public function shop_list(){
		if ($this->request->isAjax()) {
			$Shop = new ShopModel(); 
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this ->request->param('name');
            $where = [];
            // $where[] = ['status','=',1];
            if( !empty($name) ){
            	$where[] = ['name','like',"%$name%"];
            }
            $where[] = ['code','neq',0];
            $list = $Shop->where($where)->page($page,$limit)->order('addtime desc')->select();
            $total =  $Shop->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	public function add(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$Shop = new ShopModel();
			$list = $Shop ->where('id',$data['id'])->field('id,name')->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//门店添加编辑逻辑
	public function doPost(){
		$Shop = new ShopModel();
		$data = $this ->request ->post();
		if( empty($data['name']) ){
			$this ->error('请输入门店名称');
		}
		if( mb_strlen($data['name']) >10 ){
			$this ->error('门店名称不能超过10个字');
		}
		if( empty($data['id']) ){
			$maxid = (new ShopModel())->maxId();
            $shop_code='A'.str_pad($maxid +1,5,'0',STR_PAD_LEFT );
            $data['code'] = $shop_code;
			$data['addtime'] = time();
			$data['update_time'] = time();
			// 启动事务
			Db::startTrans();
			try {
			    $result = $Shop ->insertGetId($data);
			    Db::name('level_price')->insert(['shop_id'=>$result,'level_id'=>1,'price'=>0]);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			}
		}else{
			$data['update_time'] = time();
			$result = $Shop ->where('id',$data['id'])->update($data);
		}
		if( $result ){
			$this ->success('操作成功',url("Shop/shop_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//门店删除
	public function del(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$Shop = new ShopModel();
		$result = $Shop->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("Shop/shop_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//
	public function start(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$Shop = new ShopModel();
		$result = $Shop->where('id',$data['id'])->update(['status'=>1,'delete_time'=>0,'delete_id'=>0]);
		if( $result ){
			$this ->success('操作成功',url("Shop/shop_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//岗位列表
	public function post_list(){
		if ($this->request->isAjax()) {
			$ShopPost = new ShopPostModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $title = $this ->request->param('name');
            $where = [];
            $where[] = ['status','=',1];
            $where[] = ['delete_time','<=',0];

            $where = [];
            $where[] = ['status','=',1];
            if( !empty($title) ){
            	$where[] = ['title','like',"%$title%"];
            }
            $list = $ShopPost->where($where)->page($page,$limit)->field('id,title,create_time,update_time')->order('create_time desc')->select();
            $total =  $ShopPost->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	public function add_post(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$list = Db::name('shop_post') ->where('id',$data['id'])->field('id,title')->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//添加岗位逻辑
	public function post_doPost(){
		$data = $this ->request ->post();
		if( empty($data['title']) ){
			// $this ->error('请输入门店名称');
			return json(['code'=>0,'msg'=>'请输入门店名称']);
		}

		if( empty($data['id']) ){
			$info = Db::name('shop_post') ->where('title',$data['title'])->find();
			if( $info ){
				// $this ->error('岗位名称不能重复');
				return json(['code'=>0,'msg'=>'岗位名称不能重复']);
			}
			$data['create_time'] = time();
			$data['update_time'] = time();
			$data['user_id'] = session('admin_user_auth')['uid'];
			$result = Db::name('shop_post') ->insert($data);
		}else{
			$data['update_time'] = time();
			$info = Db::name('shop_post') ->where('title',$data['title'])->find();
			if($info){
				if( $info['id'] != $data['id'] ){
					// $this ->error('岗位名称不能重复');
					return json(['code'=>0,'msg'=>'岗位名称不能重复']);
				}
			}
			$result = Db::name('shop_post') ->where('id',$data['id'])->update($data);
		}
		if( $result ){
			return json(['code'=>1,'msg'=>'操作成功']);
		}else{
			return json(['code'=>0,'msg'=>'操作失败']);
		}
	}

	//岗位删除
	public function del_psot(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}
		$workerWhere['status'] = 1;
		$workerWhere['post_id'] = $data['id'];
		$worker = Db::name('shop_worker')->where($workerWhere)->select();
		if( count($worker)>0 ){
			$this ->error('已有服务人员为该职位,请勿删除！');
		}

		$result = Db::name('shop_post')
				->where('id',$data['id'])
				->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("Shop/shop_post"));
		}else{
			$this ->error('操作失败');
		}
	}

	//员工列表
	public function user_list(){
		if ($this->request->isAjax()) {
			$ShopWorker = new ShopWorkerModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this->request->param('name');
            $sid = $this->request->param('shop_id');
            $post_id = $this->request->param('post_id');
            $where = [];
            $where[] = ['status','=',1];
            $where[] = ['delete_time','<=',0];
            if( !empty($name) ){
            	$where[] = ['name','like',"%$name%"];
            }
            if( !empty($sid) && $sid != 0 ){
            	$where[] = ['sid','=',$sid];
            }
            if( !empty($post_id) && $post_id !=0 ){
            	$where[] = ['post_id','=',$post_id];
            }
            $list = $ShopWorker->where($where)->page($page,$limit)->field('id,name,sid,post_id,addtime,update_time,mobile')->order('id desc')->select();
            $total =  $ShopWorker->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$shopWhere[] = ['code','neq',0];
		$shopWhere[] = ['status','=',1];
		$shop = Db::name('shop')->where($shopWhere)->field('id,name')->select();
        $this ->assign('shop',$shop);

        $post = Db::name('shop_post')->where('status',1)->field('id,title')->select();
        $this ->assign('post',$post);
		return $this->fetch();
	}

	//添加员工
	public function add_worker(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$list = Db::name('shop_worker') ->where('id',$data['id'])->field('id,name,sid,mobile,remark,post_id,sex,entry_time')->find();
			$list['entry_time'] = date('Y-m-d',$list['entry_time']);
			$this ->assign('list',$list);
			// dump($list);
		}
		$whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);

		$shop_post = Db::name('shop_post')->where('status',1)->select();
		$this ->assign('post',$shop_post);
		return $this->fetch();	
	}

	//添加员工逻辑处理
	public function worker_doPost(){
		$data = $this ->request ->post();
		$validate = new \app\admin\validate\shop\ShopWorker;
		if (!$validate->check($data)) {
            return json(['code'=>0,'msg'=>$validate->getError()]);
        }

        if( !empty($data['entry_time']) ){
        	$data['entry_time'] = strtotime($data['entry_time']." 00:00:00");
        }

        $postIDs = Db::name('shop_worker')->where(['sid'=>$data['sid'],'post_id'=>1])->find();
        // dump($postIDs);die;
		if( empty($data['id']) ){
			$info = Db::name('shop_worker')->where('mobile',$data['mobile'])->find();
	        if( $info ){
	        	return json(['code'=>0,'msg'=>'手机号已绑定员工']);
	        }
	        if ( $data['post_id']==1 && $postIDs ) {
        		return json(['code'=>0,'msg'=>'该门店已有店长']);
        	}

        	$info = Db::name('shop_worker')->where('name',$data['name'])->find();
	        if( $info ){
	        	return json(['code'=>0,'msg'=>'账号已存在']);
	        }

			$workId = date("Ymd").rand(111,999);
			$data['workid'] = $this ->workId($workId);
			$data['addtime'] = time();
			$data['update_time'] = time();
			$data['user_id'] = session('admin_user_auth')['uid'];
			$authCode = 'OV4w80Ndr23wt4yW1j';
	    	$data['password'] = "###" . md5(md5($authCode . '123456'));
			// $userMember = array(
			// 		'mobile'	=>$data['mobile'],
			// 		'shop_code'	=>Db::name('shop')->where('id',$data['sid'])->value('code'),
			// 		'realname'	=>$data['name'],
			// 		'nickname'	=>$data['name'],
			// 		'regtime'	=>time(),
			// 		'is_staff'	=>1,
			// 		'status'	=>1
			// 	);
			// $member = Db::name('member')->where('mobile',$data['mobile'])->find();
			// dump($member);die;
			// 启动事务
			Db::startTrans();
			try {
			    Db::name('shop_worker') ->insert($data);
			    // if( !$member ){
			    // 	Db::name('member') ->insert($userMember);
			    // }
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    $this ->error($e->getMessage());
			}
		}else{
			if( $postIDs && ($data['post_id']==1) && ($postIDs['id'] != $data['id'])){
				return json(['code'=>0,'msg'=>'该门店已有店长']);
			}

			$info11 = Db::name('shop_worker')->where('name',$data['name'])->find();
			if( $info11 && ($info11['id'] != $data['id']) ){
				return json(['code'=>0,'msg'=>'账号已存在']);
			}
			$data['update_time'] = time();
			// $userMember = array(
			// 		'mobile'	=>$data['mobile'],
			// 		'shop_code'	=>Db::name('shop')->where('id',$data['sid'])->value('code'),
			// 		'realname'	=>$data['name'],
			// 		'nickname'	=>$data['name'],
			// 	);
			// dump($userMember);die;
			// 启动事务
			Db::startTrans();
			try {
			    Db::name('shop_worker') ->where('id',$data['id'])->update($data);
			    // $me = Db::name('member')->where('mobile',$data['mobile'])->find();
			    // dump($me);die;
			    // if( !$me ){
			    // 	Db::name('member')->where('id',$me['id']) ->update($userMember);
			    // }

			    // $me_data = Db::name('member_data')->where('mobile',$data['mobile'])->find();
			    // if( !$me_data ){
			    // 	Db::name('member_data')->where('id',$me_data['id']) ->update(['mobile'=>$data['mobile']]);
			    // }

			    // $me_de = Db::name('member_details')->where('mobile',$data['mobile'])->find();
			    // if( !$me_de){
			    // 	Db::name('member_details')->where('id',$me_de['id'])->update(['mobile'=>$data['mobile']]);
			    // }

			    // $me_mo = Db::name('member_money')->where('mobile',$data['mobile'])->find();
			    // if( $me_mo ){
			    // 	Db::name('member_money')->where('id',$me_mo['id']) ->update(['mobile'=>$data['mobile']]);
			    // }
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    return json(['code'=>0,'msg'=>'操作失败']);
			}
		}
		return json(['code'=>1,'msg'=>'操作成功']);
	}

	//判断工号是否重复
	public function workId($workId){
		$isset = Db::name('shop_worker')->where(['workid'=>$workId])->find();
        if ($isset) {
            $workId = date("Ymd").rand(111,999);
            $workId = $this ->workId($workId);
        }else{
        	return $workId;
        }
	}

	//服务人员删除
	public function del_worker(){
		$data = $this ->request->param();
		if( empty($data['id']) ){
			$this ->error('参数为空');
		}

		$result = Db::name('shop_worker')
				->where('id',$data['id'])
				->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
		if( $result ){
			$this ->success('操作成功',url("Shop/user_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//股东列表
	public function shareholder_list(){
		if ($this->request->isAjax()) {
			$Shareholder = new ShareholderModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $name = $this->request->param('name');	//手机号
            $sid = $this->request->param('shop_id');	//门店ids
            $where = [];
            if( !empty($sid) && $sid !=0 ){
            	$where[] = ['shop_ids','like',"%$sid%"];
            }
            if( !empty($name) ){
            	$where[] = ['mobile','like',"%$name%"];
            }

            $list = $Shareholder->where($where)->page($page,$limit)->field('id,mobile,shop_ids,create_time,status')->order('id desc')->select();
            $total =  $Shareholder->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$shopWhere[] = ['code','neq',0];
		$shopWhere[] = ['status','=',1];
		$shop = Db::name('shop')->where($shopWhere)->field('id,name')->select();
        $this ->assign('shop',$shop);
		return $this->fetch();
	}

	//添加股东
	public function shareholder_add(){
		$data = $this ->request ->param();
		if( !empty($data['id']) ){
			$list = Db::name('shareholder')->where('id',$data['id'])->field('id,mobile,shop_ids')->find();
			$shop_ids = rtrim($list['shop_ids'],','); 	//去除最后一个 逗号
			$shopIds = ltrim($shop_ids,','); 	//去除第一个 逗号
			$list['shop_ids'] = explode(',',$shopIds);
			$this ->assign('list',$list);
		}
		$where[] = ['code','neq',0];
		$where[] = ['status','eq',1];
		$shop = Db::name('shop')->where($where)->field('id,name')->select();
		$this ->assign('shop',$shop);
		return $this->fetch();
	}

	//股东数据操作
	public function shareholder_doPost(){
		$data = $this ->request ->post();
		$Shareholder = new ShareholderModel();
		if( empty($data['mobile']) ){
			return json(['code'=>-1,'msg'=>'请输入手机号']);
		}
		if( empty($data['all_shop']) && count($data['shop_ids'])==0 ){
			return json(['code'=>-1,'msg'=>'请选择门店']);
		}
		$rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $data['mobile']);
        if( !$ruleResult ){
        	return json(['code'=>-1,'msg'=>'请输入正确的手机号']);	
        }
        if( $data['all_shop'] == 1 ){
        	$shop_ids = Db::name('shop')->where('status',1)->column('id');
        	$data['shop_ids'] = ','.implode(',', $shop_ids).',';
        	unset($data['all_shop']);
        }else{
        	$data['shop_ids'] = ','.implode(',', $data['shop_ids']).',';
        }

        $info = $Shareholder->where('mobile',$data['mobile'])->find();
        if( empty($data['id']) ){
        	if( $info ){
        		return json(['code'=>-1,'msg'=>'该手机号已被注册']);	
        	}
        	$data['create_time'] = time();
			$data['user_id'] = session('admin_user_auth')['uid'];
			$result = $Shareholder ->insert($data);
        }else{
        	if( $info && $info['id'] != $data['id'] ){
        		return json(['code'=>-1,'msg'=>'该手机号已被注册']);	
        	}
        	$result = $Shareholder ->where('id',$data['id'])->update($data);
        }
        if( $result ){
            return json(['code'=>1,'msg'=>'操作成功']);
        }else{
            return json(['code'=>0,'msg'=>'操作失败']);
        }
	}

	//股东禁用
	public function shareholder_del(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $Shareholder = new ShareholderModel();
        $result = $Shareholder ->where('id',$data['id'])->update(['status'=>0,'delete_time'=>time(),'delete_id'=>session('admin_user_auth')['uid']]);
        if( $result ){
            $this ->success('禁用成功');
        }else{
            $this ->error('禁用失败');
        }
    }

    //股东启用
	public function shareholder_start(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('参数错误');
        }
        $Shareholder = new ShareholderModel();
        $result = $Shareholder ->where('id',$data['id'])->update(['status'=>1,'delete_time'=>0,'delete_id'=>0]);
        if( $result ){
            $this ->success('启用成功');
        }else{
            $this ->error('启用失败');
        }
    }
}