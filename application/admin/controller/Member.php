<?php

namespace app\admin\controller;

use app\common\controller\Adminbase;
use app\admin\model\member\MemberModel;
use app\admin\model\member\MemberLevelModel;
use app\index\model\Member\MoneyExpireLogModel;
use app\wxshop\model\member\MemberExpireLogModel;
use think\Db;

/**
	会员管理
*/
class Member extends Adminbase
{
	public function member_list(){
		if ($this->request->isAjax()) {
			//
			$Member = new MemberModel();
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $mobile = $this ->request ->param('name');
            $shopCode = $this ->request ->param('shop_id');
            $where = [];
            if( !empty($mobile) ){
            	$where[] = ['mobile','like',"%$mobile%"];
            }
            if( !empty($shopCode) ){
            	$where[] = ['shop_code','=',"$shopCode"];
            }
            // $where[] = ['status','=','1'];
            $list = $Member
            		->where($where)
            		->page($page,$limit)
            		->order('id desc')
            		->select();
            $memberIds = [];
        	foreach ($list as $key => $value) {
        		$list[$key]['amount'] = 0;
        		$memberIds[] = $value['id'];
        		$list[$key]['money'] = Db::name('member_money')->where('member_id',$value['id'])->value('money');
        	}
        	$memberIds = implode(',', $memberIds);
        	$detailsWhere[] = ['member_id','in',$memberIds];
        	$detailsWhere[] = ['type','=',1];
        	$details = Db::name('member_details')->where($detailsWhere)->field('member_id,amount')->select();
        	foreach ($list as $key => $value) {
        		foreach ($details as $k => $v) {
        			if( $value['id'] == $v['member_id'] ){
        				if( !isset($list[$key]['amount']) ){
        					$list[$key]['amount'] = $v['amount'];
        				}else{
        					$list[$key]['amount'] += $v['amount'];
        				}
        			}
        		}
        	}
        	$expireWhere = [];
        	$expireWhere[] = ['member_id','in',$memberIds];
        	$expireWhere[] = ['status','eq',1];
        	$expireWhere[] = ['expire_time','>=',time()];
        	$expireList = Db::name('member_money_expire') ->where($expireWhere) ->field('id,member_id,price,use_price')->select();
        	$memberExpireMoney = [];
        	if( count($expireList) >0 ){
                foreach ( $expireList as $k=>$v ) {
                    if( isset($memberExpireMoney[$v['member_id']]) ){
                        $memberExpireMoney[$v['member_id']] += $v['price']-$v['use_price'];
                    }else{
                        $memberExpireMoney[$v['member_id']] = $v['price']-$v['use_price'];
                    }
                }
                foreach ( $list as $k=>$v ){
                    foreach ( $memberExpireMoney as $k1=>$v1 ){
                        if( $v['id'] == $k1 ){
                            $list[$k]['money'] = $v['money'].'(限时余额:'.$v1.')';
                        }
                    }
                }
            }
            $total =  $Member->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		$whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('code,name')->select();
        $this ->assign('shop',$shop);
		return $this->fetch();
	}

	//更改手机号
	public function member_add(){
		$data = $this ->request->param();
		if( !empty($data['id']) ){
			$Member = new MemberModel();
			$list = $Member ->where('id',$data['id'])->field('id,mobile,nickname')->find();
			$this ->assign('list',$list);
		}
		return $this->fetch();
	}

	//
	public function member_doPost(){
		$data = $this ->request ->post();
		if( !empty($data['mobile']) ){
            $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
            $ruleResult = preg_match($rule, $data['mobile']);
            if( !$ruleResult ){
                return json(['code'=>-1,'msg'=>'请输入正确的手机号']);
            }
            $Member = new MemberModel();
            $list = $Member ->where('mobile',$data['mobile'])->field('id,mobile')->find();
            if( $list && $list['id'] != $data['id'] ){
                $result = array("code" => 2,  "msg" => '该手机号已被他人注册');
                return json($result);
            }
		}
		$upda = [];
		if( !empty($data['mobile']) ){
            $upda['mobile'] = $data['mobile'];
        }
        $upda['nickname'] = $data['nickname'];
		// 启动事务
		Db::startTrans();
		try {
		    Db::name('member')->where('id',$data['id'])->update($upda);
		    Db::name('member_data')->where('member_id',$data['id'])->setField('mobile',$data['mobile']);
		    Db::name('member_details')->where('member_id',$data['id'])->setField('mobile',$data['mobile']);
		    Db::name('member_money')->where('member_id',$data['id'])->setField('mobile',$data['mobile']);
		    // 提交事务
		    Db::commit();
		} catch (\Exception $e) {
		    // 回滚事务
		    Db::rollback();
		}
		$result = array("code" => 1,  "msg" => '修改成功');
        return json($result);
	}

	//会员禁用
	public function del_member(){
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			$this ->error('参数错误,请刷新页面重试!');
		}
		$result = Db::name('member')
				->where('id',$data['id'])
				->setField('status',0);
		if( $result ){
			$this ->success('操作成功',url("Member/member_list"));
		}else{
			$this ->error('操作失败');
		}
	}
	//会员启用
	public function star_member(){
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			$this ->error('参数错误,请刷新页面重试!');
		}
		$result = Db::name('member')
				->where('id',$data['id'])
				->setField('status',1);
		if( $result ){
			$this ->success('操作成功',url("Member/member_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	//等级列表
	public function level_list(){
		if ($this->request->isAjax()) {
			$limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $where = [];
            // $where['status'] = 1;
            $MemberLevel = new MemberLevelModel();
            $list = $MemberLevel->where($where)->page($page,$limit)->order('id asc')->select();
            $total =  $MemberLevel->where($where)->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
		}
		return $this->fetch();
	}

	//等级添加
	public function level_add(){
		$data = $this ->request ->param();
		$where = [];
		$where[] = ['status','=',1];
		$where[] = ['code','neq',0];
		$shop = Db::name('shop')->where($where)->field('id,name')->select();
		if( !empty($data['id']) ){
			$list = Db::name('member_level')->where('id',$data['id'])->field('id,level_name,sort')->find();
			$this ->assign('list',$list);

			foreach ($shop as $key => $value) {
				$shop[$key]['price'] = Db::name('level_price')->where(['shop_id'=>$value['id'],'level_id'=>$data['id']])->value('price');
			}
		}
		// dump($shop);die;
		$this ->assign('shop',$shop);
		return $this->fetch();	
	}

	//等级编辑逻辑处理
	public function level_doPost(){
		$data = $this ->request ->post();

		if( empty($data['level_name']) ){
			$this ->error('请填写必填项');
		}
		
		$shop_ = $data['shop_id'];
		$levelPrice = [];	//等级表数据
		foreach ($shop_ as $key => $value) {
	    	foreach ($value as $k => $v) {
	    		// if( $v == '' ){
	    		// 	$array['price'] = 0;
	    		// }else{
	    		// 	$array['price'] = $v;
	    		// }
	    		if( $v !== '' ){
	    			$array['price'] = $v;
	    			$array['shop_id'] = $k;
		    		if( !empty($data['id']) ){
		    			$array['level_id'] = $data['id'];
		    		}
		    		array_push($levelPrice, $array);
	    		}else{
	    			return json(['code'=>0,'msg'=>'每个门店都必须设置价格']);
	    		}
	    	}
	    }
	    unset($data['shop_id']);
		$MemberLevel = new MemberLevelModel();
		if( empty($data['id']) ){
			$data['add_time'] = time();
			$data['update_time'] = time();
			$data['user_id'] = session('admin_user_auth')['uid'];
			// 启动事务
			Db::startTrans();
			try {
			    $levelId = $MemberLevel ->insertGetId($data);
			    foreach ($levelPrice as $key => $value) {
			    	$levelPrice[$key]['level_id'] = $levelId;
			    }
			    Db::name('level_price')->insertAll($levelPrice);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    $this ->error('操作失败');
			}
		}else{
			$data['update_time'] = time();
			$data['update_id'] = session('admin_user_auth')['uid'];
			// 启动事务
			Db::startTrans();
			try {
			    $MemberLevel ->where('id',$data['id'])->update($data);
				
				Db::name('level_price')->where('level_id',$data['id'])->delete();
				Db::name('level_price')->insertAll($levelPrice);
			    // 提交事务
			    Db::commit();
			} catch (\Exception $e) {
			    // 回滚事务
			    Db::rollback();
			    $this ->error('操作失败'.$e->getMessage());
			}
		}
		$this ->success('操作成功',url('admin/Member/level_list'));
	}

	//等级删除
	public function level_del(){
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			$this ->error('参数错误,请刷新页面重试!');
		}
		$result = Db::name('member_level')
				->where('id',$data['id'])
				->update(['status'=>0,'delete_id'=>session('admin_user_auth')['uid'],'delete_time'=>time()]);
		if( $result ){
			$this ->success('操作成功',url("Member/level_list"));
		}else{
			$this ->error('操作失败');
		}
	}
	//等级启用
	public function level_start(){
		$data = $this ->request ->post();
		if( empty($data['id']) ){
			$this ->error('参数错误,请刷新页面重试!');
		}
		$result = Db::name('member_level')
				->where('id',$data['id'])
				->update(['status'=>1,'delete_id'=>0,'delete_time'=>0]);
		if( $result ){
			$this ->success('操作成功',url("Member/level_list"));
		}else{
			$this ->error('操作失败');
		}
	}

	/***
     * 会员限时余额
     */
	public function expireList(){
        if ($this->request->isAjax()) {
            $data = $this ->request ->param();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d',0);
            $where = [];
            if( !empty($data['member']) ){
                $where[] = ['b.nickname|b.wechat_nickname','like','%'.$data['member'].'%'];
            }
            if( !empty($data['mobile']) ){
                $where[] = ['b.mobile','like','%'.$data['member'].'%'];
            }
            if( !empty($data['sn']) ){
                $where[] = ['a.sn','like',$data['sn']];
            }
            if( !empty($data['start_time']) ){
                $where[] = ['a.create_time','>=',strtotime($data['start_time'].' 00:00:00')];
            }
            if( !empty($data['end_time']) ){
                $where[] = ['a.create_time','>=',strtotime($data['end_time'].' 23:59:59')];
            }
            if( !empty($data['shop_id']) ){
                $where[] = ['a.shop_id','eq',$data['shop_id']];
            }
            $list = (new MoneyExpireLogModel())
                ->alias('a')
                ->join('member b','a.member_id=b.id')
                ->where($where)
                ->field('a.check_verify,a.id,a.money_expire_id,a.shop_id as shop,b.mobile,b.nickname,b.wechat_nickname,a.craete_time,a.sn,a.price,a.pay_way,a.remarks')
                ->page($page,$limit)
                ->order('a.id desc')
                ->select();
            $count = (new MoneyExpireLogModel())
                ->alias('a')
                ->join('member b','a.member_id=b.id')
                ->where($where)
                ->count();
            return json(['code'=>0,'count'=>$count,'data'=>$list]);
        }
        $whereShop[] = ['code','neq',0];
        $whereShop[] = ['status','eq',1];
        $shop = Db::name('shop')->where($whereShop)->field('id,name')->select();
        $this ->assign('shop',$shop);
        return $this->fetch();
    }

    //对账
    public function duizhang(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = (new MoneyExpireLogModel())->where('id',$data['id'])->setField('check_verify',1);
        if( $result ){
            $this ->success('对账成功');
        }else{
            $this ->error('对账失败');
        }
    }
    //对账
    public function quxiao(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            $this ->error('id参数错误');
        }
        $result = (new MoneyExpireLogModel())->where('id',$data['id'])->setField('check_verify',0);
        if( $result ){
            $this ->success('操作成功');
        }else{
            $this ->error('操作失败');
        }
    }

    /***
     * 限时余额使用详情
     */
        public function history(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择限时余额']);
        }
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        $expireMoney = Db::name('member_money_expire') ->where('id',$data['id'])->find();
        if( !$expireMoney ){
            return json(['code'=>100,'msg'=>'服务发生错误','data'=>'id错误,未找到此用户的此id下的限时余额']);
        }
        $arr =[];
        $arr = [
            'money' =>$expireMoney['price'],
            'title' =>'余额充值',
            'order_id' =>$expireMoney['order_id'],
            'create_time' =>date('m-d H:i:s',$expireMoney['create_time'])
        ];
        //查询余额记录
        $list = (new MemberExpireLogModel())
            ->where('money_expire_id',$data['id'])
            ->field('price as money,reason as title,order_id,create_time')
            ->page($page)->select()->toArray();
        foreach ( $list as $k=>$v ){
            if( $v['money'] < 0 ){
                $list[$k]['title'] .= '(返款)';
            }
        }
        $count = (new MemberExpireLogModel())
                ->where('money_expire_id',$data['id'])
                ->count()+1;
        array_unshift($list,$arr);

        return json(['code'=>0,'count'=>$count,'data'=>$list]);
    }

    /***
     * 详情
     */
    public function info(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择限时余额']);
        }
        $info =  (new MoneyExpireLogModel())->alias('a') ->where('id',$data['id'])->field('a.*,a.shop_id as shop')->find();
        $this ->assign('info',$info);
        return $this ->fetch();
    }
}