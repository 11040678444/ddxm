<?php
namespace app\wxshop\controller;

use app\admin\common\model\UploadModel;
use app\wxshop\model\assemble\AssembleListModel;
use app\wxshop\model\comment\StPack;
use app\wxshop\model\order\OrderinfoModel;
use app\wxshop\wxpay\JsApiPay;
use think\Controller;
use think\Db;
use think\Exception;
use think\Query;
use think\Request;
use app\wxshop\controller\Base;
use think\facade\Cache;

use app\wxshop\model\item\CategoryModel;
use app\wxshop\model\item\ItemModel;
use app\wxshop\model\item\SpecsGoodsPriceModel;
use app\wxshop\model\assemble\AssembleModel;
use app\wxshop\model\seckill\SeckillModel;
use app\wxshop\model\seckill\FlashSaleModel;
use app\wxshop\model\seckill\FlashSaleAttrModel;
use app\wxshop\model\item\BrandModel;
use app\common\model\WxPayModel;
/**
商城首页
 */
class Index extends Base
{

    // 获取 公众号  userinfo
    public function getUserinfo($openid){
        $access_token = Cache::get('access_token');
        if( !$access_token ){
            $access_token = (new WxPayModel()) ->getAccessToken();
        }
        // $access_token = (new WxPayModel()) ->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $post_data = array(
        );
        $postdata = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postdata,
                'timeout' => 20 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        try{
            $arr = json_decode($result);
            $arr2 = json_decode(json_encode($arr), true);
            return $arr2;
        }catch (Exception $e){
            return false;
        }

    }

    //微信公众号 获取 token
    public function getusertoken1(){
        $code = input('code','');
        $tuserid = input('tuserid','0');
        if($code == ''){
            return json(['code' => -1, 'msg' => 'code 错误' , 'data' => '']);
        }
        try {
            if ($code != '') {
                $tools = new JsApiPay();
                $openid = $tools->GetOpenidFromMp2($code);
                $openId = $openid['openid'];
                $userinfo = $this->getUserinfo($openId);

                // 0：未关注  1： 已经关注
                $subscribe = $userinfo['subscribe'];
                //openid
                if($subscribe == 1){//已经关注公众号
                    // 判断该用户是否 注册用户
                    $member = \db('member')->where('openid',$openId)->find();
                    if($member == false) {// 已经关注公众号  未注册
                        //无此用户,添加会员
                        $member1['level_id'] = 1;  //默认为普通会员
                        $member1['openid'] = $openId;
//                        $member1['regtime'] = time();
                        $member1['nickname'] = $userinfo['nickname'];
                        $member1['pic'] = $userinfo['headimgurl'];
                        $member1['source'] = 1;
                        $token = '0';
                        $udata=[
                            'subscribe'=>$subscribe,
                            'token'=>$token,
                            'isbindMobile'=>0
                        ];
                        return json(['code' => 200, 'msg' => '获取成功', 'data' => $udata , 'member'=>$member1]);
                    }else{// 已经关注公众号，已注册用户
                        $token = '0';
                        $member_token = \db('member_token')->where('user_id',$member['id'])->find();
                        if($member_token == false) {//表示未 token
                            $token = $this->member_token($member_token['id']);
                        }else{
                            $token = $member_token['token'];
                        }
                        $bindMobile = 0;// 0 ： 未绑定手机号  1：已绑定手机号
                        $mobile = $member['mobile'];
                        if(!empty($mobile)){//未绑定手机号
                            $bindMobile = 1;
                        }
                        $udata=[
                            'subscribe'=>$subscribe,
                            'token'=>$token,
                            'isbindMobile'=>$bindMobile
                        ];
                        return json(['code' => 200, 'msg' => '获取成功', 'data' => $udata]);
                    }
                }else{// 未关注公众号
                    $token = '0';
                    $member = \db('member')->where('openid',$openId)->find();
                    if($member == true) {
                        $member_token = \db('member_token')->where('user_id',$member['id'])->find();
                        if($member_token == false) {//表示未 token
                            $token = $this->member_token($member_token['id']);
                        }else{
                            $token = $member_token['token'];
                        }
                        $bindMobile = 0;// 0 ： 未绑定手机号  1：已绑定手机号
                        $mobile = $member['mobile'];
                        if(!empty($mobile)){//未绑定手机号
                            $bindMobile = 1;
                        }
                        $udata=[
                            'subscribe'=>0,//未关注公众号
                            'token'=>$token,
                            'isbindMobile'=>$bindMobile
                        ];
                        return json(['code' => 200, 'msg' => '获取成功', 'data' => $udata]);
                    }
                    $udata=[
                        'subscribe'=>0,//未关注公众号
                        'token'=>0,// 0：未注册用户，非0 表示已经注册
                        'isbindMobile'=>0 //是否绑定手机号  0： 未绑定 1：已绑定手机号
                    ];
                    return json(['code' => 200, 'msg' => '获取成功', 'data' => $udata]);
                }
            }
        }catch (Exception $e){
            return json(['code' => -1, 'msg' => '获取失败' , 'data' => '']);
        }
    }

    /**
    微信公众号，获取token
     */
    public function getusertoken(){
        $code = $this ->request ->param('code');    //授权码
        $tools = new JsApiPay();
        $openid = $tools->GetOpenidFromMp2($code);
        if( !isset($openid['openid']) ){
            return json(['code'=>-2,'msg'=>'登录已失效']);
        }
        $openId = $openid['openid'];
        $userinfo = $this->getUserinfo($openId);
        // 0：未关注  1： 已经关注
        $subscribe = $userinfo['subscribe'];
        $member = Db::name('member') ->where('openid',$openId) ->find();    //会员信息
        if( !$member ){
            //未注册
            $toData = [];   //返回数据
            $toData = [
                'subscribe' =>$subscribe,
                'isbindMobile'  =>0,
                'token'     =>0,
            ];
            if( $subscribe == 1 ){
                //已关注，则生成member数据
                $member1 = [];
                $member1['level_id'] = 1;  //默认为普通会员
                $member1['openid'] = $openId;
                $member1['wechat_nickname'] = $userinfo['nickname'];
                $member1['pic'] = $userinfo['headimgurl'];
                $member1['source'] = 1;
                $toData['member'] = json_encode($member1);
            }
        }else{
            //已注册过
            if( $subscribe != 1 ){
                //未注册，不允许登陆
                $toData = [
                    'subscribe' =>$subscribe,
                    'isbindMobile'  =>1,
                    'token'     =>'',
                    'member'    =>""
                ];
                return json(['code'=>201,'msg'=>'请先关注公众号','data'=>$toData]);
            }

            $toData = [];   //返回数据
            $toData = [
                'subscribe' =>$subscribe,
                'isbindMobile'  =>1,
                'token'     =>$this->member_token($member['id']),
                'member'    =>""
            ];
            if( $subscribe == 1 ){
                $updateData = [];
                $updateData = [
                    'wechat_nickname'   =>$userinfo['nickname'],
                    'pic'   =>$userinfo['headimgurl']
                ];
                Db::name('member') ->where('openid',$openId) ->update($updateData);    //更改会员信息
            }
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$toData]);
    }

    /***
     * 已关注公众号绑定手机号
     * 绑定手机号
     */
    public function bd_mobile(){
        $data = $this ->request->post();
        if( empty($data['mobile']) || empty($data['code']) ){
            return json(['code'=>100,'msg'=>'请输入手机号或验证码']);
        }
        $data['member'] = json_decode($data['member'],true);
        if( empty($data['member']['openid']) ){
            return json(['code'=>100,'msg'=>'请传入参数openid']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule,$data['mobile']);
        if (!$ruleResult) {
            $result['code'] = '-100';
            $result['msg'] = '手机号格式错误';
            return json($result);
        }
        $where[] = ['mobile','eq',$data['mobile']];
        //验证码
        //测试专用
        if( $data['code'] != '1130' ){
            $verification = Db::name('verification_code')->where($where)->order('send_time desc')->find();
            if( !$verification || ($verification['code'] != $data['code']) || (time()>$verification['expire_time']) ){
                return json(array('code'=>'-6','msg'=>'验证码错误或已过期','data'=>''));
            }
        }
        $member = Db::name('member')->where($where)->find();
        if( !$member || ($member['openid'] == '') ){
            //无此用户,添加会员
            $member1 = $data['member'];
            $member1['regtime'] = time();
            $member1['mobile'] = $data['mobile'];
            //余额数据
            $moneyData = array(
                'mobile'  =>$data['mobile'],
                'money'   =>'0.00',
            );
            // 启动事务
            Db::startTrans();
            try {
                if( !$member ){
                    $member1['shop_code'] = 'A00000';   //进来为默认公司总部的人
                    $member1['nickname'] = $member1['wechat_nickname'];	//新增用户门店名称默认为微信昵称
                    $result = Db::name('member') ->insertGetId($member1);
                    $member['id'] = $result;
                    $moneyData['member_id'] = $result;
                    Db::name('member_money')->insert($moneyData);
                    //判断是否为被邀请的:1有user_id则以user_id为主
                    if( !empty($data['user_id']) ){
                        $newmemberInfo = Db::name('member')->where('id',$data['user_id'])->find();
                        if( $newmemberInfo && ($newmemberInfo['status'] == 1) ){
                            //如果有user_id,门店则以user_id的门店为主
                            if( !empty( $newmemberInfo['shop_code'] ) ){
                                Db::name('member') ->where('id',$member['id']) ->setField('shop_code',$newmemberInfo['shop_code']);
                            }
                            //如果有user_id,判断user_id是否为分销员,是分销员则添加粉丝
                            if( $newmemberInfo['retail'] == 1 ){
                                $fans_data = [];
                                $fans_data = [
                                    'member_id' =>$data['user_id'],
                                    'fans_id' =>$result,
                                    'status' =>1,
                                    'create_time' =>time(),
                                ];
                                Db::name('retail_fans') ->insert($fans_data);
                            }
                        }
                    }
                    //如果没有user_id则判断shop_id
                    if( empty($data['user_id']) && !empty( $data['shop_id'] ) ){
                        Db::name('member') ->where('id',$member['id']) ->setField('shop_code',$data['shop_id']);
                    }
                }else{
                    Db::name('member') ->where('id',$member['id'])->update($member1);
                }
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return json(['code'=>100,'msg'=>$e->getMessage()]);
            }
        }else{
            if( $member['status'] != 1 ){
                return json(array('code'=>6,'msg'=>'用户已被禁用,请联系管理员','data'=>''));
            }
            $openid = Db::name('member')->where('openid',$data['member']['openid'])->find();
            if( empty($member['openid']) && !$openid ){
                Db::name('member')->where($where)->update(['openid'=>$data['openid'],'pic'=>$data['member']['pic'],'wechat_nickname'=>$data['member']['wechat_nickname']]);
            }else{
                return json(['code'=>100,'msg'=>'该手机号已绑定微信,请直接登陆']);
            }
        }
        $user_token = $this ->member_token($member['id']);
        //如果有上级、给上级发客户消息
        if( !empty($data['user_id']) ){
            $user = Db::name('member')->where('id',$data['user_id'])->field('status,openid,retail,shop_code') ->find();
            if( $user ){
                if( $user['retail'] == 1 && $user['status']== 1 ){
                    $newmember = Db::name('member') ->where('id',$member['id']) ->find();
                    //发送客户消息
                    $name = !empty($newmember['wechat_nickname'])?$newmember['wechat_nickname']:$newmember['nickname'];
                    $content = '亲爱的客官,恭喜你啦,'.$name.'成为你的新粉丝,共同开启美好生活';
                    $post_data = '{"touser":"'.$user['openid'].'","msgtype":"text","text":{"content":"'.$content.'"}}';
                    (new WxPayModel())->sed_custom_message($post_data);     //给上级发消息
                }
            }
        }
        $resultUser = array('code'=>200,'msg'=>'登陆成功','data'=>['token'=>$user_token,'subscribe'=>1,'isbindMobile'=>1]);
        return json($resultUser);
    }

    /***
     * 商品详情
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function item_info(){
        $data = $this ->request ->param();
        $Item = new ItemModel();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'服务器出错']);
        }
        $where[] = ['id','eq',$data['id']];
        $filed = 'id,status,title,subtitle,mold_id,video,type,min_price,max_price,initial_sales,reality_sales,lvid,content,pics,specs_list,item_service_ids,own_ratio as ratio,ratio_type';
        $item = $Item ->where($where)
            ->field($filed)
            ->find()->append(['sales','mold','price']);
        if( $item['mold_id'] == 1 ){
            $item['percentage'] = '您购买成功后，实际支付的价格已含9.1%的跨境代扣税';
            $item['tips'] = '7-15个工作日送达。按照国家新政对跨境商品征收跨境综合税。跨境购订单需要顾客保持信息一致，要求顾客支付人姓名、收货人姓名、实名认证的姓名一致。';
        }
        $item['promise'] = '捣蛋熊承诺：正品保证  安心售后  假一赔十';    //承若

        $memberId = parent::getToken();
        if( $memberId ){
            $hot = array(
                'member_id' =>$memberId,
                'category_id'  =>$item['type'],
                'item_id' =>$data['id'],
                'create_time'  =>time()
            );
            parent::postHistory($hot);
        }
        //检测当前商品是否存在活动
        $stpack = new StPack();
        $pack_data = $stpack->getPack($data['id']);
        //把数据拼接到item中
        $item['pack'] = $pack_data;
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);
    }

    /***
     * 选择商品规格的金额库存等信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info_specs(){
        $data = $this ->request ->param();
        if( empty($data['id']) || !isset($data['specs_ids']) ){
            return json(['code'=>100,'msg'=>'服务器出错']);
        }
        $where = [];
        $where[] = ['gid','eq',$data['id']];
        $where[] = ['key','eq',$data['specs_ids']];
        $where[] = ['status','eq',1];
        $SpecsMode = new SpecsGoodsPriceModel();
        $item = $SpecsMode ->where($where)->field('price,store,imgurl as pic,pic_info') ->find();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);
    }

    /***
     * 根据商品分类搜索商品
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getItem_category(){
        $data = $this ->request ->param();
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        if( !empty($data['type']) ){
            $CategoryModel = new CategoryModel();
            $where[] = ['id','eq',$data['type']];
            $where[] = ['online','eq',1];
            $where[] = ['type','eq',1];
            $where[] = ['status','eq',1];
            $cate = $CategoryModel ->where($where) ->field('id,pid,cname')->find();
            if( $cate['pid'] == 0 ){
                $map[] = ['type_id','eq',$cate['id']];
            }else{
                $map[] = ['type','eq',$cate['id']];
            }
        }
        $map[] = ['status','neq',3];
        $map[] = ['item_type','eq',1];
        $Item = new ItemModel();
        $item = $Item ->where($map)
            ->order('id desc')
            ->field('id,title,min_price,initial_sales,reality_sales,pic,status')
            ->page($page)->select()->append(['sales']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);
    }

    /***
     * 搜索商品
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_item(){
        $data = $this ->request ->param();
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        if( !empty($data['price']) && ($data['price'] == 1) ){
            //价格升序
            $order = 'min_price asc';
        }else if( !empty($data['price']) && ($data['price'] == 2) ){
            $order = 'min_price desc';
        }else{
//            $order = [
//                'sort_sales'=>'desc',
//                'id'=>'desc'
//            ];
            $order = rand();
        }
        $where = [];    //搜索条件
        if( !empty($data['type']) && empty($data['is_cate']) ){
            //分类搜索,不是从首页进入
            $itemIds = (new CategoryModel()) ->getAllCategoryList($data['type']);
        }
        if( isset($itemIds) ){
            $where[] = ['id','in',implode(',',$itemIds)];
        }
        if( ($data['type']==284) && ($data['is_cate']==1)  ){
            //是从首页进入,并且为284表示为跨境购，则查询分区
            $where[] = ['mold_id','eq',1];
        }
        if( !empty($data['title']) ){
            $where[] = ['title','like','%'.$data['title'].'%'];
        }
        if( !empty($data['brand']) ){
            $where[] = ['brand_id','eq',$data['brand']];
        }
        $where[] = ['status','eq',1];
        $where[] = ['item_type','eq',1];
        $where[] = ['show','eq',1];
        $Item = new ItemModel();
        $field = 'id,title,min_price,max_price,initial_sales,reality_sales,pic,status,type,activity_type,activity_price,activity_id,activity_start_time,activity_end_time,(initial_sales+reality_sales) sort_sales';
        $item = $Item
            ->where($where)
            ->field($field)
            ->order($order)
            ->page($page)->select()
            ->append(['sales'])
            ->toArray();

        if( count($item)>0 ){
            if( isset($data['sales']) && $data['sales'] ==1 ){
                array_multisort(array_column($item,'sales'),SORT_ASC,$item);
            }else if( isset($data['sales']) && $data['sales'] ==2 ){
                array_multisort(array_column($item,'sales'),SORT_DESC,$item);
            }
        }
        //判断是否登录，存入历史记录
        $memberId = parent::getToken();
        if( $memberId && !empty($data['title']) ){
            $hot = array(
                'title' =>$data['title'],
                'type'  =>2,
                'member_id' =>$memberId,
                'time'  =>time()
            );
            parent::postHot($hot);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);     //都没查询到，返回空
    }

    /***
     *获取属性
     * @param $category_id
     * @param $pid
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAttrList( $category_id,$pid ){
        $list = Db::name('item_attribute') ->where(['pid'=>$pid,'category_id'=>$category_id,'status'=>1])->field('id,title')->select();
        foreach ($list as $k=>$v){
            $data = self::getAttrList($category_id,$v['id']);
            if( count($data)>0 ){
                $list[$k]['child'] = $data;
            }
        }
        return $list;
    }

    /***
     * 搜索页面的热搜与历史搜索
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_history(){
        $list = []; //数据
        $hot = Db::name('hot')->where(['type'=>1,'status'=>1])
            ->order('sort asc')
            ->field('id,title')
            ->select();
        $list['hot'] = $hot;
        $memberId = parent::getToken();
        if( $memberId ){
            $map = [];
            $map[] = ['status','eq',1];
            $map[] = ['type','eq',2];
            $map[] = ['member_id','eq',$memberId];
            $map[] = ['title','neq',''];
            $history = Db::name('hot')
                ->where($map)
                ->order('id desc')
                ->page('1,15')
                ->field('id,title')
                ->group('title')
                ->select();
            $list['history'] = $history;
        }else{
            $list['history'] = [];
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 猜你喜欢
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function like_item(){
        $where = [];
        $memberId = $this ->request->header('XX-Token');
        $data = $this ->request ->param();
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '';
        }
        if( $memberId ){
            $category = Db::name('browse_history')->where('member_id',$memberId)->order('create_time desc')->find();
            if( $category ){
                $categoryId = $category['category_id'];
                $where[] = ['type','eq',$categoryId];
            }
        }
        $where[] = ['status','neq',3];
        $where[] = ['item_type','eq',1];
        $Item = new ItemModel();
        $item = $Item ->where($where)
            ->order('id desc')
            ->field('id,title,min_price,initial_sales,reality_sales,pic,status')
            ->page($page)->select()->append(['sales']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);
    }

    /***
     * 分类
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function category(){
        $Cate = new CategoryModel();
        $data = $this ->request ->param();
        $where[] = ['status','eq',1];
        $where[] = ['online','eq',1];
        if( !empty($data['pid']) ){
            $where[] = ['pid','eq',$data['pid']];
        }else{
            $where[] = ['pid','eq',0];
        }
        $list = $Cate ->where($where)->field('id,cname,thumb')->order('sort asc')->select();
        $info = [];
        if( $data['pid'] == 0 ){
            foreach ($list as $k=>$v){
                $map = [];
                $map[] = ['pid','eq',$v['id']];
                $map[] = ['status','eq',1];
                $er = $Cate ->where($map)->select();
                if( count($er) >0 ){
                    array_push($info,$v);
                }
            }
        }else{
            $info = $list;
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /***
     * 获取分类 ：第二期
     */
    public function getCategory(){
        $Cate = new CategoryModel();
        $where[] = ['online','eq',1];
        $where[] = ['status','eq',1];
        $where[] = ['pid','eq',0];
        $where[] = ['type','eq',1];
        $list = CategoryModel::with(['child','child.child'])->where($where)->field('id,cname,thumb,cate_id,pid,thumb')->order('sort asc')->select()->toArray();
        //判断二级分类下是否存在三级分类，如果不存在则将不存在的三级分类的二级分类归到一个新的空的二级分类下的三级分类
        foreach ( $list as $k=>$v ){
            foreach ( $v['child'] as $k1 => $v1 ){
                if( count( $v1['child'] ) <= 0 ){
                    unset($v1['child']);
                    if( isset( $list[$k]['child']['newArr'] ) ){
                        array_push( $list[$k]['child']['newArr'] , $v1 );
                    }else{
                        $list[$k]['child']['newArr'][] = $v1;
                    }
                    unset($list[$k]['child'][$k1]);
                }
            }
        }
        foreach ( $list as $k => $v ) {
            if( isset($v['child']['newArr']) ){
                $arr = [];
                $arr = [
                    'id'    =>0,
                    'cname'    =>$v['cname'],
                    'thumb'    =>'',
                    'cate_id'    =>0,
                    'pid'    =>0,
                    'child'     =>$v['child']['newArr']
                ];
                array_unshift( $list[$k]['child'] ,$arr);
                unset($list[$k]['child']['newArr']);
            }
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 获取品牌
     */
    public function getBrand(){
        $data = $this ->request ->param();
        $where = [];
        if( empty($data['type']) ){
            $where[] = ['type','eq',1];
        }else{
            $where[] = ['type','eq',$data['type']];
        }
        $where[] = ['status','eq',1];
        $list = (new BrandModel())->field('id,title,tag,is_hot,thumb,pinyin')->where($where)->order('tag asc')->select()->toArray();
        $data = [];
        $data['hot'] = [];
        foreach ( $list as $k=>$v ){
            if( $v['is_hot'] == 1 ){
                if( isset($data['hot']) ){
                    $data['hot'][] = $v;
                }else{
                    $data['hot'][] = $v;
                }
            }
            if( isset($data[$v['tag']]) ){
                array_push($data[$v['tag']],$v);
            }else{
                $data[$v['tag']][] = $v;
            }
        }
        $info = [];
        //转为数组格式
        foreach ( $data as $k=>$v ){
            $arr = [];
            $arr = [
                'name'  =>$k,
                'data'  =>$v
            ];
            array_push($info,$arr);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /***
     * banner图
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function banner(){
        $list = Db::name('banner')->where('status',1)->field('id,type,value,thumb,url')->order('sort asc')->select();
        if( count($list)>0 ){
            foreach ($list as $k=>$v){
                $list[$k]['thumb'] = "http://picture.ddxm661.com/".$v['thumb'];
            }
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 获取城市
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function city(){
        $data = $this ->request ->param();
        $where = [];
        if( !empty($data['id']) ){
            $where[] = ['pid','eq',$data['id']];
        }else{
            $where[] = ['pid','eq',0];
            $where[] = ['grade','eq',1];
        }
        $area = Db::name('area')->where($where)->order('sort asc')->field('id,area_name,areacode')->select();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$area]);
    }

    /***
     * 获取协议
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAgreement(){
        $data = $this ->request->param();
        if( empty($data['type']) ){
            return json(['code'=>100,'msg'=>'请传入type']);
        }
        $list = Db::name('setting')->where('type',$data['type'])->find();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /**
     * 拼团列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function assemble_list(){
        $data = $this ->request->param();
        $Assemble = new AssembleModel();
        $field = "a.id,b.pic,c.title,a.old_price,c.price,c.people_num";
        if( isset($data['hot']) && $data['hot'] == 1 ){
            $list = $Assemble->alias('a')
                ->where(['a.status'=>1,'a.hot'=>1])
                ->join('item b','a.item_id=b.id','left')
                ->join('assemble_attr c','a.id=c.assemble_id and a.update=c.update','left')
                ->field($field)->order('a.id desc')->limit('3')->select()
                ->append(['assemble_num','all_people']);
        }else{
            if( !empty($data['limit']) && !empty($data['page']) ){
                $page = $data['page'].','.$data['limit'];
            }else{
                $page = '';
            }
            $list = $Assemble->alias('a')
                ->where(['a.status'=>1])
                ->join('item b','a.item_id=b.id','left')
                ->join('assemble_attr c','a.id=c.assemble_id and a.update=c.update','left')
                ->field($field)->order('a.hot desc,a.id desc')->page($page)->select()->append(['assemble_num','all_people']);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 首页图标
     */
    public function icon(){
        $list = Db::name('icon') ->where('status',1)->limit('5')->field('id,title,thumb,value,type,url')->select();
        foreach ($list as $k=>$v){
            $list[$k]['thumb'] = "http://picture.ddxm661.com/".$v['thumb'];
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /**
     * 拼团详情
     */
    public function assemble_info(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'id参数错误']);
        }
        $AssembleModel = new AssembleModel();
        $where = [];
        $where[] = ['a.id','eq',$data['id']];
        $field = 'a.id,a.update,c.pics,c.ratio,c.subtitle,c.video,c.video_time,c.item_service_ids,c.mold_id,c.content,b.title,b.item_id,b.item_name,a.old_price,b.price,b.commander_price,b.people_num,b.buy_num,b.all_stock,b.remaining_stock,a.retail,a.begin_time,a.end_time,a.hot,a.postage_id';
        $list = $AssembleModel->alias('a')
            ->where($where)
            ->join('assemble_attr b','a.id=b.assemble_id and a.update=b.update','left')
            ->join('item c','a.item_id=c.id','left')
            ->field($field)
            ->find()->append(['mold','all_people','begin']); //拼团详情
        $list['now_time'] = time();
        $list['promise'] = '捣蛋熊承诺：正品保证  安心售后  假一赔十';    //承若
        if( $list['mold_id'] == 1 ){
            $list['percentage'] = '您购买成功后，实际支付的价格已含9.1%的跨境代扣税';
            $list['tips'] = '7-15个工作日送达。按照国家新政对跨境商品征收跨境综合税。跨境购订单需要顾客保持信息一致，要求顾客支付人姓名、收货人姓名、实名认证的姓名一致。';
        }
        //获取正在拼团的有几组
        $assemble_list = Db::name('assemble_list')
            ->where(['status'=>1,'assemble_id'=>$data['id']])
            ->limit('10')
            ->order('create_time desc')
            ->field('id,end_time,num,r_num,sn')
            ->select();
        if( count($assemble_list) <=0 ){
            $list['count'] = 0;     //有几人正在拼团
            $list['assemble_list'] = [];    //拼团组数
            return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
        }
        $listId = [];
        foreach ($assemble_list as $k=>$v){
            $listId[] = $v['id'];
        }
        $listId = implode(',',$listId);
        $map = [];
        $map[] = ['assemble_list_id','in',$listId];
        $infoList = Db::name('assemble_info')->where($map)->field('id,assemble_list_id,order_id,status,commander,member_id')->select();
        foreach ($assemble_list as $k=>$v){
            foreach ($infoList as $k1=>$v1){
                if( $v['id'] == $v1['assemble_list_id'] ){
                    $assemble_list[$k]['child'][] = $v1;
                }
            }
        }
        $LoginId = self::getToken();
        foreach ($assemble_list as $k=>$v){
            foreach ($v['child'] as $k1=>$v1 ){
                if( $v1['commander'] == 1  ){
                    //先给团长id，后面查询头像与昵称
                    $assemble_list[$k]['member_id'] = $v1['member_id'];
                }
                if( $LoginId && ($LoginId==$v1['member_id']) && $v1['status'] != 2 ){
                    $assemble_list[$k]['order_id'] = $v1['order_id'];
                }
            }
        }
        foreach ($assemble_list as $k=>$v){
            if( !isset($v['order_id']) ){
                $assemble_list[$k]['order_id'] = '';
            }
            unset($assemble_list[$k]['child']);
        }
        $count = 0; //还差总人数
        $member_id = [];
        foreach ($assemble_list as $k=>$v){
            $LackNum = $v['num']-$v['r_num'];
            $count += $LackNum;
            if( !in_array($v['member_id'],$member_id) ){
                array_push($member_id,$v['member_id']);
            }
        }
        $list['count'] = $count;        //总共有多少人拼团
        $memberId = implode(',',$member_id);
        $mep = [];
        $mep[] = ['id','in',$memberId];
        $memberList = Db::name('member')->where($mep)->field('id,nickname,pic')->select();
        $time = time();
        foreach ($assemble_list as $key=>$val){
            $assemble_list[$key]['now_time'] = $time;
            foreach ($memberList as $k=>$v){
                if( $val['member_id'] == $v['id'] ){
                    $assemble_list[$key]['pic'] = $v['pic'];
                    $assemble_list[$key]['nickname'] = $v['nickname'];
                }
            }
        }

        foreach ( $assemble_list as $k=>$v ){
            if( $v['end_time'] > $list['end_time'] ){
                $assemble_list[$k]['end_time'] = $list['end_time'];
            }
        }
        $list['assemble_list'] = $assemble_list;
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 首页限时秒杀--时间段
     * 第一版本
     */
    public function seckill_list1(){
        $beginToday = mktime(0,0,0,date('m'),date('d'),date('Y'));      //今日开始时间
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;  //今日结束时间
        $where = [];
        // $where[] = ['start_time','>=',$beginToday];
        $where[] = ['end_time','<=',$endToday];
        $where[] = ['status','eq',1];
        $where[] = ['hide','eq',0];
        $Seckill = new SeckillModel();
//        $list = $Seckill->where($where)->field('any_value(id) as id,any_value(end_time) as end_time,start_time')->group('start_time')->order('start_time asc')->select()->append(['start','begin']);
        $list = $Seckill->where($where)->field('id,start_time,end_time')->group('start_time')->order('start_time asc')->select()->append(['start','begin']);         //本地
        $now_time = time();
        foreach ($list as $k=>$v){
            $list[$k]['now_time'] = $now_time;
        }
        return json(['code'=>200,'msg'=>'获取时间','data'=>$list]);
    }

    /***
     * 首页秒杀--获取商品
     * 第一版本
     */
    public function seckill_list_goods1(){
        $data = $this ->request ->param();
        if( empty($data['start_time']) ){
            return json(['code'=>100,'msg'=>'请选择时间段']);
        }
        if( !empty($data['limit']) && !empty($data['page']) ){
            $page = $data['page'].','.$data['limit'];
        }else{
            $page = '1,10';
        }
        $Seckill = new SeckillModel();
//        $end_time = $Seckill ->where(['start_time'=>$data['start_time']])->find();
//        if( !$end_time ){
//            return json(['code'=>100,'msg'=>'时间段错误']);
//        }
        $where = [];
        $where[] = ['a.start_time','=',$data['start_time']];
//        $where[] = ['a.end_time','=',$end_time['end_time']];
        $where[] = ['a.status','eq',1];
        $where[] = ['c.status','eq',1];
        $where[] = ['a.hide','eq',0];
        $item = $Seckill->alias('a') ->where($where)
            ->join('item b','a.item_id=b.id',"left")
            ->join('specs_goods_price c','a.item_id=c.gid','left')
            ->order('a.price asc')
            ->field('a.id,a.item_name,a.price,a.old_price,a.item_id,a.num,a.already_num,b.pic,c.store')
            ->page($page)->select()->append(['num_type','now_time']);
        return json(['code'=>200,'msg'=>'获取成功','data'=>$item]);
    }

    /***
     * 普通秒杀：第二期
     */
    public function seckillList(){
        $page = $this ->request ->param('page',1);
        $limit = $this ->request ->param('limit',10);
        $data = $this ->request ->param();
        $where = [];
        if( !empty($data['start_time']) ){
            $where[] = ['a.start_time','=',$data['start_time']];
        }
        $where[] = ['a.end_time','>=',time()];
        $where[] = ['a.status','eq',1];
        $where[] = ['b.status','eq',1];
        $where[] = ['a.type','eq',2];   //秒杀
        $list = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id,b.flash_sale_id')
            ->page($page,$limit)
            ->field('a.id,a.title,a.start_time,a.end_time,b.item_id,b.item_name,b.old_price,b.price,b.already_num,b.residue_num,b.stock,b.virtually_num')
            ->order('a.start_time asc')
            ->select()
            ->append(['status','now_time','is_over','pic'])->toArray();
        array_multisort(array_column($list,'status'),SORT_ASC,$list);
        $count = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id')->count();
        return json(['code'=>200,'msg'=>'获取成功','count'=>$count,'data'=>$list]);
    }

    /***
     * 秒杀详情：第二期
     */
    public function seckill_info(){
        try{
            $data = $this ->request ->param();

	 //redisObj()->del('4249__8602');
    // dump(redisObj()->get('4249__8602'));
//     dump(redisObj()->keys('*'));die;
            if( empty($data['seckill_id']) || empty($data['item_id']) ){
                return json(['code'=>100,'msg'=>'请传入秒杀id或者商品id']);
            }
            //先查询秒杀
            $info = ( new FlashSaleModel() ) ->where('id',$data['seckill_id'])
                ->field('id,title,start_time,end_time,type,people_num')
                ->find()->append(['now_time','status','is_over'])->toArray();  //秒杀详情
            //查询商品详情
            $itemInfo = ( new ItemModel() )->alias('a') ->where('a.id',$data['item_id'])
                ->field('a.id,a.status,a.title,a.subtitle,a.mold_id,a.video,a.initial_sales,a.reality_sales,a.lvid,a.content,a.pics,a.own_ratio as ratio,ratio_type')
                ->find()->append(['mold','mold_know','promise'])->toArray();
            //秒杀商品规格详情
            $where = [];
            $where[] = ['flash_sale_id','eq',$data['seckill_id']];
            $where[] = ['item_id','eq',$data['item_id']];
            $where[] = ['status','eq',1];
            $item_specs = ( new FlashSaleAttrModel() )->alias('b') ->where($where)
                ->field('b.specs_ids,b.item_name,b.specs_names,b.old_price,b.price,b.item_id,residue_num,residue_num as over_num,b.already_num,b.virtually_num')
                ->select()->append(['pic'])->toArray();
            if( $info['status'] == 2 ){
                foreach ( $item_specs as $k=>$v ){
                    $item_specs[$k]['already_num'] = 0;//如果活动未开始则将已抢数量赋值为0
                }
            }
            //拼装规格组名称
            $specs_ids = $item_specs[0]['specs_ids'];
            if( !empty($specs_ids) ){
                $specs_ids = explode('_',$specs_ids);
                $map = [];
                $map[] = ['id','in',implode(',',$specs_ids)];
                $attributes = Db::name('item_specs')->where($map)->column('pid');
                $map = [];
                $map[] = ['id','in',implode(',',$attributes)];
                $attributes = Db::name('item_specs')->where($map)->column('title');
                $attributes = implode(',',$attributes);
            }else{
                $attributes = '';
            }
            foreach ( $item_specs as $k=>$v ){
                if( isset($itemInfo['price'])  ){
                    if( $v['price'] < $itemInfo['price'] ){
                        $itemInfo['price'] = $v['price'];
                    }
                }else{
                    $itemInfo['price'] = $v['price'];
                }
                if( isset($itemInfo['old_price']) ){
                    if ( $v['old_price'] < $itemInfo['old_price'] ) {
                        $itemInfo['old_price'] = $v['old_price'];
                    }
                }else{
                    $itemInfo['old_price'] = $v['old_price'];
                }
                if( $v['residue_num'] != '-1' ){      //每种规格剩余可以卖出的数量
                    if( self::getToken() ){
                        //number
                        $orderWhere = [];
                        //0: 普通订单 1：拼团订单 2：抢购订单，3限时抢购',
                        $orderWhere[] = ['order_distinguish','eq',$info['type']==1?3:$info['type']==2?2:1];
                        $orderWhere[] = ['member_id','eq',self::getToken()];
                        $orderWhere[] = ['pay_status','eq',1];
                        $orderWhere[] = ['event_id','eq',$data['seckill_id']];
                        $orderIds = Db::name('order') ->where($orderWhere)->column('id');
                        $orderGoods = Db::name('order_goods')->where('order_id',implode(',',$orderIds))
                            ->field('item_id,attr_ids,num')
                            ->select(); //单人已经购买的数量
                        $people_num = 0;        //每人购买的这个这个商品对应的规格的数量
                        $xian_num = 0;          //每人购买的这个商品的数量
                        foreach ( $orderGoods as $k1=>$v1 ){
                            if( ($v['item_id'] == $v1['item_id'])  ){
                                if( ($v['specs_ids'] == $v1['attr_ids']) ){
                                    $people_num += $v1['num'];  //用来判断
                                }
                                $xian_num += $v1['num'];
                            }
                        }
                        if( $info['people_num'] != '-1' ){
                            if( $info['people_num'] >$xian_num ){
                                $item_specs[$k]['residue_num'] = $info['people_num']-$xian_num;
                            }else{
                                $item_specs[$k]['residue_num'] = 0;
                            }
                        }
                    }
                }
            }
            $info['attributes'] = $attributes;
            $info['item'] = $itemInfo;
            $info['item_specs'] = $item_specs;

            //启用REDIS缓存，预存库存
            $redis = redisObj();

            $redis->set($info['id'],serialize([
                'people_num'=>$info['people_num'],
                'goods'=>$item_specs,
                'end_time'=>$info['end_time'] 
            ]));
            $over_time = $info['end_time']-time();
            $redis->expire($info['id'],$over_time);

            //处理商品到缓存（可能存在多规格）
            foreach ($item_specs as $key=>$val)
            {
                $k = $val['item_id'].'_'.$val['specs_ids'];
                if(!$redis->exists($k))
                {
                    for ($i=0;$i<$val['over_num'];$i++)
                    {
                        $redis->lPush($k,1);
                    }
                }

                if(!$redis->exists($k.'_'.'residue_num'))
                {
                    $redis->set($k.'_'.'residue_num',$val['residue_num']);
                    $redis->expire($k.'_'.'residue_num',$over_time);
                }
                //设置过期时间
                $redis->expire($k,$over_time);
            }

            return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
        }catch (\Exception $e){
            \exception($e->getMessage(),500);
        }
    }

    /***
     * 秒杀详情
     */
    public function seckillInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'msg'=>'请选择秒杀的商品']);
        }
        $Seckill = new SeckillModel();
        $info = $Seckill->alias('a') ->where('a.id',$data['id'])
            ->join('item b','a.item_id=b.id','left')
            ->field('a.id,a.item_id,b.ratio,b.subtitle,a.old_price,a.price,a.start_time,a.end_time,item_name as title,b.item_service_ids,a.num,b.pics,b.content,b.mold_id')
            ->find()->append(['begin','end_or_start','now_time','mold']);
        $info['promise'] = '捣蛋熊承诺：正品保证  安心售后  假一赔十';    //承若
        if( $info['mold_id'] == 1 ){
            $info['percentage'] = '您购买成功后，实际支付的价格已含9.1%的跨境代扣税';
            $info['tips'] = '7-15个工作日送达。按照国家新政对跨境商品征收跨境综合税。跨境购订单需要顾客保持信息一致，要求顾客支付人姓名、收货人姓名、实名认证的姓名一致。';
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /***
     * 获取分区的购买需知
     */
    public function getNeeds(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>100,'分区id为空']);
        }
        $list = Db::name('item_type')->where(['id'=>$data['id']])->field('id,title,content')->find();
        if( !$list ){
            return json(['code'=>100,'分区id错误']);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 用户意见反馈
     */
    public function feedback(){
        $data = $this ->request->param();
        if( empty($data['content']) || empty($data['mobile']) ){
            return json(['code'=>100,'msg'=>'请输入内容或联系方式']);
        }
        $rule = '^1(3|4|5|7|8|9)[0-9]\d{8}$^';
        $ruleResult = preg_match($rule, $data['mobile']);
        if (!$ruleResult) {
            $result['code'] = 206;
            $result['msg'] = '手机号格式错误';
            $result['data'] = '';
            return json($result);
        }
        $memberId = self::getToken();
        if( !$memberId ){
            $memberId = 0;
        }
        $arr = [];
        $arr = [
            'member_id' =>$memberId,
            'content' =>$data['content'],
            'mobile' =>$data['mobile'],
            'create_time' =>time(),
            'handle_status' =>0,
            'status' =>1,
            'delete_time' =>0,
            'handle_time' =>0
        ];
        $res = Db::name('feedback')->insert($arr);
        if( $res ){
            return json(['code'=>200,'msg'=>'反馈成功']);
        }else{
            return json(['code'=>100,'msg'=>'反馈失败']);
        }
    }

    /***
     * 拼团订单详情
     */
    public function assembleorderInfo(){
        $data = $this ->request ->param();
        if( empty($data['id']) ){
            return json(['code'=>404,'msg'=>'请选择订单']);
        }
        $Order = new OrderinfoModel();
        $order = $Order ->where('id',$data['id'])->find();
        if( !$order || ($order['order_distinguish'] !=1) ){
            return json(['code'=>404,'msg'=>'订单信息错误或此订单不是拼团订单']);
        }
        $assembleListId = Db::name('assemble_info')->where(['order_id'=>$data['id'],'o_sn'=>$order['sn']])->value('assemble_list_id');
        if( !$assembleListId ){
            return json(['code'=>404,'msg'=>'订单信息错误请联系客服']);
        }
        $AssembleList = new AssembleListModel();
        $list = $AssembleList->where('id',$assembleListId)
            ->field('id,assemble_id,create_time,end_time,num,r_num,status,reason,assemble_price,old_price,price,over_time')
            ->find()->append(['info']);

        //查看拼团整体活动的结束时间
        $assemble_end_time = Db::name('flash_sale') ->where('id',$list['assemble_id']) ->find();
//        if( $list['end_time'] > $assemble_end_time['end_time'] ){
//            $list['end_time'] = $assemble_end_time['end_time'];
//        }
        $info = [];

        //做数据回显
        $info['tuanyuan_price'] = $list['price'];       //普通团员价
        $info['assemble_price'] = $list['assemble_price'];       //团长价
        $info['item_id'] = $list['info']['0']['item_id'];
        $info['mold_id'] = $list['info']['0']['mold_id'];
        $mold = Db::name('item_type')->where('id',$info['mold_id'])->value('title');
        $info['mold'] = $mold?$mold:'熊猫自营';
        $info['assemble_id'] = $order['event_id'];
        $info['assemble_list_id'] = $assembleListId;
        $info['buy_num'] = $assemble_end_time['people_num'];     //限购数量

//        //查询限购
//        $map = [];
//        $map[] = ['flash_sale_id','eq',$order['event_id']];
//        $map[] = ['item_id','eq',$list['info']['0']['item_id']];
//        $map[] = ['specs_ids','eq',empty($list['info']['0']['attr_ids'])?'':$list['info']['0']['attr_ids']];
//        dump($list);
//        $attr = Db::name('flash_sale_attr') ->where($map) ->find();
//        dump($attr);die;
//        $info['remaining_stock'] = $assembleListId; //库存，为-1表示不限制
        //做数据回显

        $info['end_time'] = $list['end_time'];
        $info['reason'] = $list['reason'];
        $info['status'] = $list['status'];
        $info['old_price'] = $list['old_price'];
        $info['num'] = $list['num'];
        $info['r_num'] = $list['r_num'];
        $info['item_name'] = $list['info']['0']['item_name'];

        $info['item_pic'] = "http://picture.ddxm661.com/".$list['info']['0']['item_pic'];
        $info['time'] = time();
        $info['info'] = $list['info'];
//        dump($info);
//        dump($list);
//        die;
        return json(['code'=>200,'msg'=>'获取成功','data'=>$info]);
    }

    /***
     * 首页公告
     */
    public function notice(){
        $arr = [
            'id'    =>1,
            'title' =>'微商城正式上线啦！'
        ];
        $list = [];
        $list[0] = $arr;
        $list[1] = $arr;
        $list[2] = $arr;
        $list[3] = $arr;
        $list[4] = $arr;
        $list[5] = $arr;
        $list[6] = $arr;
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 首页分类及分类下的商品
     */
    public function category_item(){
        $list = [
            0   =>['category_id'=>219],
            1   =>['category_id'=>204],
            2   =>['category_id'=>277],
            3   =>['category_id'=>264],
            4   =>['category_id'=>240],
            5   =>['category_id'=>231],
        ];
        $categoryId = array_column($list,'category_id');
        $categoryWhere[] = ['id','in',implode(',',$categoryId)];
        $categoryList = (new CategoryModel()) ->where($categoryWhere)->field('id,cname,thumb')->select()->toArray();
        foreach ( $list as $k=>$v ){
            foreach ( $categoryList as $k1=>$v1 ){
                if( $v['category_id'] == $v1['id'] ){
                    $list[$k]['cname'] = $v1['cname'];
                    $list[$k]['thumb'] = $v1['thumb'];
                }
            }
        }
        foreach ( $list as $k=>$v )
        {
            $ids = (new CategoryModel()) ->getAllCategoryList($v['category_id']);
            $where = [];
            $where[] = ['id','in',implode(',',$ids)];
            $where[] = ['status','eq',1];
            $list[$k]['items'] = (new ItemModel()) ->where($where)
                ->order(rand())
                ->limit(10)
                ->field('id,title,pic,min_price,max_price')
                ->select()
                ->toArray();
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     *首页 人气爆款
     */
    public function explosion(){
        $where = [];
        $where[] = ['type','in','3,4,5'];
        $list = Db::name('setting') ->where($where)->order('type asc')->field('type,content') ->select(); //推荐三款商品[id1,id2,id3]
        $map = [];
        $map[] = ['item_type','eq',1];
        $map[] = ['status','eq',1];
        $items = (new ItemModel())
            ->where($map)
            ->order('reality_sales desc')
            ->limit(6)
            ->field('id,title,pic,min_price,max_price')
            ->select();    //   随意推荐的三个销量最高的商品
        if( count($list) > 0 ){
            $itemIds = array_column($list,'content');
            $emp = [];
            $emp[] = ['id','in',implode(',',$itemIds)];
            $itemList = (new ItemModel())
                ->where($emp)->field('id,title,pic,min_price,max_price')
                ->select();
            foreach ( $list as $k=>$v ){
                foreach ( $itemList as $k1=>$v1 ){
                    if( $v['content'] == $v1['id'] ){
                        $list[$k]['item'] = $v1;
                    }
                }
            }
        }
        $arr = [];
        $arr = [
            'type'  =>1,
            'item'  =>$items
        ];
        array_push($list,$arr);

        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 首页：超级拼团，限时秒杀，童装童鞋，跨境购
     */
    public function combination(){
        $where = [];
        $where[] = ['a.end_time','>=',time()];
        $where[] = ['a.status','eq',1];
        $where[] = ['a.type','eq','3'];   //拼团
        $assembleList = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id,b.flash_sale_id')
            ->field('a.id,b.item_id,b.item_name as title,b.old_price as max_price,b.price as min_price')
            ->order('a.end_time asc')
            ->limit(2)
            ->select()
            ->append(['pic']);
        //超级秒杀
        $where = [];
        $where[] = ['a.end_time','>=',time()];
        $where[] = ['a.status','eq',1];
        $where[] = ['a.type','eq','2'];   //拼团
        $seckillList = ( new FlashSaleModel() ) ->alias('a')
            ->join('flash_sale_attr b','a.id=b.flash_sale_id')
            ->where($where)
            ->group('b.item_id,b.flash_sale_id')
            ->field('a.id,b.item_id,b.item_name as title,b.old_price as max_price,b.price as min_price')
            ->order('a.end_time asc')
            ->limit(2)
            ->select()
            ->append(['pic']);
        //童装童鞋
        $itemIds = (new CategoryModel())->getAllCategoryList(219);  //童装童鞋
        $where = [];
        $where[] = ['id','in',implode(',',$itemIds)];
        $where[] = ['status','eq',1];
        $where[] = ['show','eq',1];
        $tongList = (new ItemModel()) ->where($where) ->field('id as item_id,title,pic,min_price,max_price')
            ->limit(2)
            ->select();
        //跨境购
        $itemIds = (new CategoryModel())->getAllCategoryList(284);  //跨境购
        $where = [];
        $where[] = ['id','in',implode(',',$itemIds)];
        $where[] = ['status','eq',1];
        $where[] = ['show','eq',1];
        $kuaList = (new ItemModel()) ->where($where) ->field('id as item_id,title,pic,min_price,max_price')
            ->limit(2)
            ->select();
        $list = [];
        $list['assemble_list'] = $assembleList->toArray();
        $list['seckill_list'] = $seckillList->toArray();
        $tongCate = (new CategoryModel()) ->where('id',219)->field('id,cname,thumb')->find();
        $kuaCate = (new CategoryModel()) ->where('id',284)->field('id,cname,thumb')->find();
        $tongArr = [
            'id'        =>219,
            'name'      =>$tongCate['cname'],
            'thumb'      =>$tongCate['thumb'],
            'data'  =>$tongList->toArray()
        ];
        $kuaArr = [
            'id'        =>284,
            'name'      =>$kuaCate['cname'],
            'thumb'      =>$kuaCate['thumb'],
            'data'  =>$kuaList->toArray()
        ];
        $list['tong_list'] = $tongArr;
        $list['kua_list'] = $kuaArr;
        return json(['code'=>200,'msg'=>'查询成功','data'=>$list]);
    }

    /***
     * 拼团规则详情
     */
    public function assemble_rule_info(){
        $list = Db::name('setting') ->where('type',7) ->find();
        return json(['code'=>200,'msg'=>'获取成功','data'=>$list]);
    }

    /***
     * 获取微信公众号配置config
     */
    public function getWeChatConfig(){
        $data = $this ->request ->param();
        if( empty($data['url']) ){
            return json(['code'=>101,'msg'=>'缺少参数url']);
        }
        $access_token = Cache::get('access_token');
        if( !Cache::has('access_token') ){
            $access_token = ( new WxPayModel() ) ->getAccessToken();
        }
        $jsapi_ticket = ( new WxPayModel() ) ->getJsApiTicket($access_token , urldecode($data['url']));
        if( !$jsapi_ticket ){
            return json(['code'=>100,'msg'=>'获取失败']);
        }
        return json(['code'=>200,'msg'=>'获取成功','data'=>$jsapi_ticket]);
    }

    /***
     * 获取临时素材
     */
    public function getMediaId(){
        $data = $this ->request ->param();
        if( empty($data['media_id']) ){
            return json(['code'=>100,'msg'=>'媒体文件ID缺失']);
        }
        $imageAll  = (new WxPayModel()) ->getDownloadMedia($data['media_id']);

        $savename = md5(microtime(true));
        $savename = $savename.mt_rand(1,9999).".jpg";
        //图片保存到服务器
        $imgurl = $this->saveWeixinFile($savename,$imageAll);	//本地图片地址
        $imgurl = ROOT_PATH.'public/'.$imgurl;	//图片的绝对地址
        $res = (new UploadModel()) ->upload1($imgurl);

        if( $res['code'] == 0 ){
            unlink($imgurl);    //删除本地图片
            $newData['key'] = $res['data']['key'];
            $newData['url'] = config('QINIU_URL').$res['data']['key'];
            return json(['code'=>200,'msg'=>'获取成功','data'=>$newData]);
        }else{
            return json(['code'=>100,'msg'=>'失败']);
        }
    }

    /**
     * 保存图片到本地
     * @param  [type] $filename    [description]
     * @param  [type] $filecontent [description]
     * @return [type]              [description]
     */
    private function saveWeixinFile($filename,$filecontent)
    {
        $path = "uploads/" . date('Ymd');//路径
        $a = is_dir($path);
        if (!$a) {
            mkdir($path, 0777, true);
        }
        $imageSrc = $path . "/" . $filename; //图片名字
        $imgpath = ROOT_PATH . "public/" . $imageSrc;
        $imgpath = str_replace("\\", "/", $imgpath);
        $local_file = fopen($imgpath, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $filecontent)) {
                $imageSrc = config('http_url') . "/" . $path . "/" . $filename;
                fclose($local_file);
                return $imageSrc;
            }
        }
    }

    /** 获取 活动 专题 列表  **/
    public function getActivityList(){

        $type = input('type',0);

        $index = 0;
        $list=[];

        switch ($type){

            case 0://口腔
                    $da=[
                        'url'=>'http://picture.ddxm661.com/img7c53568b7476126e62c378da590b95aaff8798b8',
                        'item_id'=>4379,
                    ];
                    $list[0] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img09fa53c9337e57b2990a12a6654c0a0fc9d76195',
                    'item_id'=>4566,
                ];
                $list[1] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imge3bad18600751f7a20ebed262424a8d24eda1eff',
                    'item_id'=>4593,
                ];
                $list[2] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img77f865249de8917a32426e6a364b763b32480bf9',
                    'item_id'=>4635,
                ];
                $list[3] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img6d7f89866056b2c716c183a0705df12915cf7286',
                    'item_id'=>4104,
                ];
                $list[4] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img9b89036f0facd926afd05f2f96b31b6cc609128e',
                    'item_id'=>4106,
                ];
                $list[5] = $da;

                $da=[
                    'url'=>'http://picture.ddxm661.com/img6d99f434be359fc12d0c6ed61b25e5ad1cd03cce',
                    'item_id'=>4128,
                ];
                $list[6] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imga275208e0e94f33c6bde6f843dff176b86a6822b',
                    'item_id'=>4129,
                ];
                $list[7] = $da;

                break;
            case 1://洗护品

                    $da=[
                        'url'=>'http://picture.ddxm661.com/img46a6834b15b62e4e4b36f59aaf66ec6b1b487723',
                        'item_id'=>4394,
                    ];
                    $list[0] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgb9d7490684d5613e880b44f4b3c669dbec932354',
                    'item_id'=>4626,
                ];
                $list[1] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img9d68360e74a2ceea904bb673a3a8a9fae4e6b3ff',
                    'item_id'=>4365,
                ];
                $list[2] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img7337cfef4969ac6cbd65f3196fcf65f96549064a',
                    'item_id'=>4629,
                ];
                $list[3] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img0809bd9017dcc864c022b58caf484812077adcee',
                    'item_id'=>4600,
                ];
                $list[4] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img4c63dfee1813e76c1da8620f3e03ac371f47219a',
                    'item_id'=>4630,
                ];
                $list[5] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img8f286e95d1633b45a3502c74f9a2be6c54fcb940',
                    'item_id'=>4598,
                ];
                $list[6] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imge22b9a8a974de33417985c3ac1e4153b36074fc1',
                    'item_id'=>4384,
                ];
                $list[7] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img00ca5da03174b752b88c77f770ed031847ed3eff',
                    'item_id'=>4375,
                ];
                $list[8] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imgd00e25bf5f3862f2f8ad5d46a9bd889d13c00edb',
                'item_id'=>4385,
            ];
                $list[9] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imgfb1a07042f72fde1d3844d591496e9f6c49ce37c',
                'item_id'=>4424,
            ];
                $list[10] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgdb14ec34fcf92a1702ae81b8fdae3056b6d5c917',
                    'item_id'=>4632,
                ];
                $list[11] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img80638d00e153c402e72e0a7f367cd9f387fc0e11',
                    'item_id'=>4633,
                ];
                $list[12] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgf7f6308113f3176d54083fd45058040e12d846fb',
                    'item_id'=>4386,
                ];
                $list[13] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img919e4ff5ff83cbf7e697280df5991ed512924281',
                    'item_id'=>4372,
                ];
                $list[14] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img2d6e81d05d48e20e1edde22bee5551865607f30c',
                'item_id'=>4602,
            ];
                $list[15] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imge286f2df8d9c5aaf4736cbdf2953ca44363d3b69',
                'item_id'=>4565,
            ];
                $list[16] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img69ebefa99a7b0ad204da6f56c7fcb058b384cd80',
                'item_id'=>4457,
            ];
                $list[17] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img7eafdbf23d15a862d5c63ea9c73cbd6cbe051047',
                'item_id'=>4592,
            ];
                $list[18] = $da;
                $da=[
                'url'=>'http://picture.ddxm661.com/img1ebfb0ac620fe80d919ca2f8a6e257362cdc4927',
                'item_id'=>4124,
            ];
                $list[19] = $da;
               $da=[
                'url'=>'http://picture.ddxm661.com/img4e3fbe9ba1e418e1997866e467fb8ee9e820fda5',
                'item_id'=>4441,
            ];
                $list[20] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img3b17a143da0f902df7f5f412bca5f180c21998fc',
                'item_id'=>4639,
            ];
                $list[21] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img7e97743154c401ea4907386523e6153ea487a574',
                'item_id'=>4639,
            ];
                $list[22] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imgba74a4d442a7fe654107a8ef3a6af58a4aa4ae8f',
                'item_id'=>4639,
            ];
                $list[23] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img223c823258a554debed2565fb61775745b6210dd',
                'item_id'=>4641,
            ];
                $list[24] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imgf18aab2d724cf42ac150364fc95d0ad3cab449b8',
                'item_id'=>4641,
            ];
                $list[25] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img4368c5c2318a26fd454d6a64618b15297bb43a2c',
                'item_id'=>4117,
            ];
                $list[26] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imge8f17b32ef3464f59fef2d58d4f725f6155b47ef',
                'item_id'=>4454,
            ];
                $list[27] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/imgd0addf1411e27f168776bd3d1c225891eaf65b58',
                'item_id'=>4103,
            ];
                $list[28] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img8f5a090886d9d9f6029d533269311a5b39a2fe5e',
                'item_id'=>4105,
            ];
                $list[29] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img4708367a5390d00ba9c4240dfaf077f116092d70',
                'item_id'=>4114,
            ];
                $list[30] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img0737dd86e916c2f6932d09881d7e26e4d5a47924',
                'item_id'=>4107,
            ];
                $list[31] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img8ce2fe11d1c3e6e72facbf2a601e75828669053a',
                'item_id'=>4110,
            ];
                $list[32] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img7e26be2a1b72234d9986d6ee4d999cdffa21890a',
                'item_id'=>4631,
            ];
                $list[33] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img924cefc2c2faa6372137f2254c710f31dd272bda',
                'item_id'=>4631,
            ];
                $list[34] = $da;$da=[
                'url'=>'http://picture.ddxm661.com/img5e80a271491710613992b830003534030a516652',
                'item_id'=>4631,
            ];
                $list[35] = $da;

                break;
            case 2://卫生巾

                $da=[
                    'url'=>'http://picture.ddxm661.com/img94ab659354ec22ed1134eb8f742ab65a6c5cf9b3',
                    'item_id'=>4638,
                ];
                $list[0] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgc07adc69e25708b92b7f83b763d08d45a7c3e6df',
                    'item_id'=>4637,
                ];
                $list[1] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgc71196f0361a027ad08990b41a1104281e88c7a5',
                    'item_id'=>4637,
                ];
                $list[2] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img410803e0bf521cb06cb91b8f19c6ccfe0d34aa9b',
                    'item_id'=>4642,
                ];
                $list[3] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgd9ad41fda8b207d29b6b2b1a86522516bbb436fd',
                    'item_id'=>4642,
                ];
                $list[4] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img0261313657d6e8b6299ea21972eee0b75c453a3d',
                    'item_id'=>4373,
                ];
                $list[5] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img4f392f18088596c508ec550db4be9e3624d42ab4',
                    'item_id'=>4594,
                ];
                $list[6] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgdb5f75f808959edd6b01a5ddcf3f20aced4cb59c',
                    'item_id'=>4568,
                ];
                $list[7] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img71b1d5ff175c31534ed8632c4d1757c6d3b3d1ad',
                    'item_id'=>4627,
                ];
                $list[8] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img996119354695045f60a69a4085cc1a3cfb11c8e5',
                    'item_id'=>4628,
                ];
                $list[9] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img8bbf8d35dfd952e3e7fdd302852666ee91aac10b',
                    'item_id'=>4628,
                ];
                $list[10] = $da;

                $da=[
                    'url'=>'http://picture.ddxm661.com/imgd07ba8bf3edbfa324e8c4e0313b707b81318d495',
                    'item_id'=>4640,
                ];
                $list[11] = $da;
                break;
            case 3://唇膏

                $da=[
                    'url'=>'http://picture.ddxm661.com/imgdd81c8a71bebbdb27adb0e922371d5eae87d40c9',
                    'item_id'=>4095,
                ];
                $list[0] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/img6fbb77c5d8c09c2bcbc806efd3a5d25587891372',
                    'item_id'=>4623,
                ];
                $list[1] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgd5f73de8e0467ca9ab164ee0e744b137eeb190d7',
                    'item_id'=>4625,
                ];
                $list[2] = $da;
                $da=[
                    'url'=>'http://picture.ddxm661.com/imgd5285106cf378d45ee16425f3b0fefafe69aca53',
                    'item_id'=>4624,
                ];
                $list[3] = $da;

                break;
        }
        return json(['code' => 200, 'msg' => '' , 'data' => $list]);
    }

}