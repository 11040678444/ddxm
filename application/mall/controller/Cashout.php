<?php

namespace app\mall\controller;

use app\common\controller\Adminbase;
use app\common\model\UtilsModel;
use app\mall\model\cashout\RetailCashOutModel;
use app\common\model\WxPayModel;
use think\Db;
/**
 * 提现管理
 */
class Cashout extends Adminbase
{
    /***
     * 提现列表
     */
    public function retail_list(){
        if ($this->request->isAjax()) {
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 1);
            $data = $this ->request ->param();
            $where = [];
            if( isset($data['status']) && $data['status'] != '' ){
                $where[] = ['a.status','eq',$data['status']];
            }
            if( !empty($data['member']) ){
                $where[] = ['b.nickname|b.wechat_nickname|b.mobile','like','%'.$data['member'].'%'];
            }
            if( !empty($data['time']) ){
                $time = explode('-',$data['time']);
                $timeWhere = strtotime($time[0].'-'.$time['1'].'-'.$time[2]).','.strtotime($time[3].'-'.$time['4'].'-'.$time[5]);
                $where[] = ['a.create_time','between',$timeWhere];
            }
            $list = (new RetailCashOutModel())
                ->alias('a')
                ->join('member b','a.member_id=b.id')
                ->where($where)
                ->field('a.id,a.member_id,a.member_id as member,a.admin_id,a.price,a.status,a.create_time,a.update_time,a.remarks,a.title')
                ->page($page,$limit)->order('status asc')->order('id desc')->select();
            $total = (new RetailCashOutModel())->alias('a')
                ->join('member b','a.member_id=b.id')
                ->where($where)
                ->count();
            $result = array("code" => 0, "count" => $total, "data" => $list);
            return json($result);
        }
        return $this->fetch();
    }

    /***
     * 提现审核
     */
    public function handle(){
        $data = $this ->request ->param()['data'];
        if( ($data['status'] == 2) && ($data['remarks'] == '') ){
            return json(['code'=>100,'msg'=>'请输入拒绝理由']);
        }
        $info = (new RetailCashOutModel()) ->where('id',$data['id']) ->find();
        if( $info['status'] != 0 ){
            return json(['code'=>100,'msg'=>'此请求已经处理过啦']);
        }
        $member = Db::name('member')->where('id',$info['member_id']) ->field('id,openid')->find();
        if( !$member ){
            return json(['code'=>100,'msg'=>'会员出错']);
        }
        if( empty($member['openid']) ){
            return json(['code'=>100,'msg'=>'会员信息不完整']);
        }
        // 启动事务
        Db::startTrans();
        try {
            if( $data['status'] == 2 ){
                //拒绝
                $res = Db::name('member_money') ->where('member_id',$info['member_id']) ->setInc('retail_money',$info['price']);
            }else{
                //微信提现
                //new \Exception('微信提现还未做');   //微信提现还未做
                $post_data = [
                    'sn'    =>$info['sn'],
                    'openid'    =>$member['openid'],
                    'amount'    =>$info['price']
                ];
                $res = (new WxPayModel()) ->transfers($post_data);
                if( $res['result_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'  ){
                    //成功
                    $res = 1;
                }else{
                    $res = 0;
                    return_error($res['err_code_des']);
                }
                if ( $res )
                {
                    $data['admin_id'] = session('admin_user_auth')['uid'];
                    $data['update_time'] = time();
                    $data['title'] = $data['status'] == 1?'同意提现':'拒绝提现';
                    $res = (new RetailCashOutModel()) ->where('id',$data['id'])->update($data);
                }
            }
            // 提交事务
            $res?Db::commit():return_error('系统繁忙');
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            return json(['code'=>100,'msg'=>'服务器繁忙','data'=>$e->getMessage()]);
        }
        return json(['code'=>1,'msg'=>'审核成功']);
    }

    public function CashOutAdd()
    {
       if(request()->isAjax())
       {
           $data = request()->param();

           //数据验证
           dataValidate($data,[
               'mobile|手机号'=>'require|number|min:11',
               'price|提现金额'=>'require|float'
           ]);

           //根据电话号码查询用户是否在
           $user_id = db('member')->where(['mobile'=>$data['mobile']])->value('id');
           empty($user_id) ? return_error('手机号不存在') : '';

           $cash_out_data = [
               'member_id'=>$user_id,
               'admin_id'=>session('admin_user_auth.uid'),
               'price'=>$data['price'],
               'create_time'=>time(),
               'remarks'=>empty($data['remarks']) ? '后台提现申请' : $data['remarks'],
               'sn'=>'TX'.date('Ymd').rand(11111111,99999999).$user_id
           ];

           $res = db('retail_cash_out')->insert($cash_out_data);

           !empty($res) ? return_succ([],'添加成功') : return_error('添加失败');
       }else{
           return $this->fetch('cash_out_add');
       }
    }
}