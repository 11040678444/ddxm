<?php
namespace app\wxshop\controller;

use app\admin\common\model\UploadModel;
use app\common\model\CheckingIdCardModel;
use app\wxshop\model\order\PostageModel;
use app\common\model\UtilsModel;
use app\wxshop\wxpay\JsApiPay;
use think\Controller;
use think\Db;
use think\Query;
use think\Request;
/**
商城
 */
//
//                       _oo0oo_
//                      o8888888o
//                      88" . "88
//                      (| -_- |)
//                      0\  =  /0
//                    ___/`---'\___
//                  .' \\|     |// '.
//                 / \\|||  :  |||// \
//                / _||||| -:- |||||- \
//               |   | \\\  -  /// |   |
//               | \_|  ''\---/''  |_/ |
//               \  .-\__  '-'  ___/-. /
//             ___'. .'  /--.--\  `. .'___
//          ."" '<  `.___\_<|>_/___.' >' "".
//         | | :  `- \`.;`\ _ /`;.`/ - ` : | |
//         \  \ `_.   \_ __\ /__ _/   .-` /  /
//     =====`-.____`.___ \_____/___.-`___.-'=====
//                       `=---='
//
//
//     ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
//
//     佛祖保佑         永无BUG           永不修改
//
//
//
class Base extends Controller
{
    protected function initialize()
    {
        header('Content-Type:application/json; charset=utf-8');
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Max-Age:86400'); // 允许访问的有效期
        header('Access-Control-Allow-Headers:*');
        header('Access-Control-Allow-Methods:OPTIONS, GET, POST, DELETE');
    }

    /***
     * 生成或刷新token
     * @param $userId
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function member_token($userId){
        $where['user_id'] = $userId;
        $findUserToken = Db::name('member_token')->where($where)->find();
        $currentTime    = time();
        $expireTime     = $currentTime + 24 * 3600 * 180;
        $token          = md5(uniqid()) . md5(uniqid());
        if (empty($findUserToken)) {
            $userData = [
                'token'       => $token,
                'user_id'     => $userId,
                'expire_time' => $expireTime,
                'create_time' => $currentTime
            ];
            Db::name('member_token') ->insert($userData);
        } else {
            $userData = [
                'token'       => $token,
                'expire_time' => $expireTime,
                'create_time' => $currentTime
            ];
            Db::name('member_token')->where('user_id',$userId) ->update($userData);
        }
        //生成登陆日志
        $userInfo = Db::name('member')->where('id',$userId)->find();
        self::setLoginLog(['id'=>$userId,'user_login'=>$userInfo['mobile'],'user_name'=>$userInfo['nickname']]);
        return $token;
    }

    /***
     *  生成登陆日志
     */
    public function setLoginLog($memberInfo){
        if( empty($memberInfo['id']) || empty($memberInfo['user_login']) ){
            return false;
        }
        $data = array(
            'user_id'   =>$memberInfo['id'],
            'user_login'   =>$memberInfo['user_login'],
            'ip'   => (new UtilsModel()) ->getUserIpAddr() ,
            'time'   =>time(),
            'user_name'   =>!empty($memberInfo['user_name'])?$memberInfo['user_name']:$memberInfo['user_login'],
            'type'   =>1,
            'info'  =>!empty($memberInfo['user_name'])?$memberInfo['user_name']:$memberInfo['user_login'].'在'.date('Y-m-d H:i:s').' 登陆了商城'
        );
        $res = Db::name('login_log')->insert($data);
        if( !$res ){
            return false;
        }
        return true;
    }

    /***
     * 判断是否存在token，如果是，则返回会员id
     * @return bool
     */
    public function getToken(){
        $token = $this ->request->header('XX-Token');
        if( $token ){
            $Token = controller('Token');
            $memberId = $Token ->getUserId();
            return $memberId;
        }
        return false;
    }

    /***
     * 搜索存入历史记录
     * @param $data
     * @return bool|int|string
     */
    public function postHot($data){
        if( count($data)<=0 ){
            return false;
        }
        $res = Db::name('hot')->insert($data);
        return $res;
    }

    /***
     * 存入浏览历史
     * @param $data
     * @return bool|int|string
     */
    public function postHistory($data){
        if( count($data)<=0 ){
            return false;
        }
        $res = Db::name('browse_history')->insert($data);
        return $res;
    }

    /***
     * 获取运费
     * @param $areaId ::省级地址id
     * @param $data ::二维数组，包含商品id与数量
     * @return bool|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPostage($areaId,$data){
        $memberId = self::getToken();
        if( count($data)<=0 ){
            return false;
        }
        $ids = [];      //运费id
        foreach ($data as $k=>$v){
            $ids[] = $v['id'];
        }
        $ids = implode(',',$ids);
        $where[] = ['id','in',$ids];
        $lv = Db::name('item')->where($where)->field('id,lvid,title,real_ID,type_id,type,sender_id,mold_id,brand_id,status')->select();      //运费模板
        foreach ($data as $key=>$val){
            foreach ($lv as $k=>$v){
                if( $val['id'] == $v['id'] ){
                    $data[$key]['lvid'] = $v['lvid'];
                    $data[$key]['title'] = $v['title'];
//                    $data[$key]['real_ID'] = $v['real_ID'];
                    $data[$key]['mold_id'] = $v['mold_id'];
//                    $data[$key]['type_id'] = $v['type_id'];
//                    $data[$key]['type'] = $v['type'];
                    $data[$key]['sender_id'] = $v['sender_id'];
                    $data[$key]['brand_id'] = $v['brand_id'];
                    $data[$key]['status'] = $v['status'];
                }
            }
        }
        //查询商品的重量等
        foreach ($data as $k=>$v){
            $where = [];
            $where[] = ['gid','eq',$v['id']];
            $where[] = ['key','eq',$v['specs_ids']?$v['specs_ids']:''];
            $where[] = ['status','eq',1];
            $specs = Db::name('specs_goods_price')->where($where)->find();
            $data[$k]['volume'] = $specs['volume'];
            $data[$k]['weight'] = $specs['weight'];
            $data[$k]['price'] = $specs['price'];
            $data[$k]['attr_name'] = $specs['key_name'];
            $data[$k]['old_price'] = $specs['recommendprice'];
            $data[$k]['store'] = $specs['store'];
            $data[$k]['pic'] = $specs['imgurl'];
            $data[$k]['cost'] = $specs['cost'];
        }

        //判断商品id是否错误
        foreach ($data as $k=>$v){
            if( !isset($v['lvid']) ){
                return  ['code'=>100,'msg'=>$v['id'].'商品id错误'];
            }
        }
        //查询运费计算方式
        $lvId = [];
        foreach ($data as $k=>$v){
            if( !in_array($v['lvid'],$lvId) ){
                $lvId[] = $v['lvid'];
            }
        }
        $lvId = implode(',',$lvId);
        $map[] = ['id','in',$lvId];
        $PostageModel = new PostageModel();
        $postage = $PostageModel ->where($map)->select()->append(['child']);        //运费
        foreach ($postage as $k=>$v){
            $area_id = '';
            foreach ($v['child'] as $v1){
                $area_id .= ','.$v1['area_ids'];
            }
            $postage[$k]['all_areaIds'] = $area_id;
        }
        //先比较所选地址是否存在地址模板中
//        foreach ($data as $key=>$val){
//            foreach ($postage as $k=>$v){
//                if( $val['lvid'] == $v['id'] ){
//                    $all_areaIds = explode(',',$v['all_areaIds']);
//                    if( !in_array($areaId,$all_areaIds) ){
//                        return ['code'=>100,'msg'=>$val['title'].'暂不支持该地区发货'];exit;
//                    }
//                }
//            }
//        }
//        //计算运费
//        foreach ($data as $key=>$val){
//            foreach ($postage as $k=>$v){
//                if( $val['lvid'] == $v['id'] ){
//                    if( $v['type'] == 1 ){
//                        //重量
//                        $itemWeight = $val['weight'];       //商品重量
//                        $postagePrice = self::getPostAgePrice($areaId,$itemWeight,$v['child']);
//                        $data[$key]['postagePrice'] = $postagePrice;
//                    }else if( $v['type'] == 2 ){
//                        //体积
//                        $itemVolume = $val['volume'];       //商品体积
//                        $postagePrice = self::getPostAgePrice($areaId,$itemVolume,$v['child']);
//                        $data[$key]['postagePrice'] = $postagePrice;
//                    }else if( $v['type'] == 3 ){
//                        //件数
//                        $itemNum = $val['num'];       //商品数量
//                        $postagePrice = self::getPostAgePrice($areaId,$itemNum,$v['child']);
//                        $data[$key]['postagePrice'] = $postagePrice;
//                    }else if( $v['type'] == 0 ){
//                        //默认的免邮模板
//                        $postagePrice = 0;
//                        $data[$key]['postagePrice'] = $postagePrice;
//                    }
//                }
//            }
//        }
        //判断如果是拼团或者抢购，则将金额改为拼团或者抢购金额  //1拼团，2抢购；拼团时：commander 1表示团长，反之为团员
        $isAssemble = 0;    //1拼团订单或者秒杀订单，0普通订单
        $assemblePostagePrice = 0;  //如果为拼团或者秒杀订单的运费结算方式
        foreach ( $data as $k=>$v ){
            if( !empty($v['order_distinguish']) && ($v['order_distinguish'] == 1) ){
                //1：拼团
                if( empty($v['activity_id']) ){
                    return ['code'=>100,'msg'=>'缺少拼团id'];
                }
                $map = [];
                $map[] = ['a.id','eq',$v['activity_id']];
                $map[] = ['b.item_id','eq',$v['id']];
                $map[] = ['b.specs_ids','eq',$v['specs_ids']];
                $map[] = ['b.status','eq',1];
                $assemble = Db::name('flash_sale')
                    ->alias('a')
                    ->join('flash_sale_attr b','a.id=b.flash_sale_id')
                    ->where($map)
                    ->field('a.postage_way,b.price,b.old_price,b.commander_price')
                    ->find();
                if( !$assemble ){
                    return ['code'=>100,'msg'=>'拼团活动错误'];
                }
                if( $v['commander'] == 1 ){
                    $data[$k]['price'] = $assemble['commander_price'];      //团长价
                }else{
                    $data[$k]['price'] = $assemble['price'];        //成员价
                }
                $isAssemble = 1;
                $assemblePostagePrice = $assemble['postage_way'];
            }else if( !empty($v['order_distinguish']) && ($v['order_distinguish'] == 2) ){
                //2:秒杀
                if( empty($v['activity_id']) ){
                    return ['code'=>100,'msg'=>'缺少秒杀id'];
                }
                $map = [];
                $map[] = ['a.id','eq',$v['activity_id']];
                $map[] = ['b.item_id','eq',$v['id']];
                $map[] = ['b.specs_ids','eq',$v['specs_ids']];
                $map[] = ['b.status','eq',1];
                $seckill = Db::name('flash_sale')
                    ->alias('a')
                    ->join('flash_sale_attr b','a.id=b.flash_sale_id')
                    ->where($map)
                    ->field('a.postage_way,b.price,b.old_price')
                    ->find();
                if( !$seckill ){
                    return ['code'=>100,'msg'=>'秒杀活动错误'];
                }
                $data[$k]['old_price'] = $seckill['old_price'];        //原价
                $data[$k]['price'] = $seckill['price'];        //秒杀价
                $isAssemble = 1;
                $assemblePostagePrice = $seckill['postage_way'];
            }else if( !empty($v['order_distinguish']) && ($v['order_distinguish'] == 4) ){
                //2:秒杀
                if( empty($v['activity_id']) ){
                    return ['code'=>100,'msg'=>'缺少礼包id'];
                }
                $map = [];
                $map[] = ['a.id','eq',$v['activity_id']];
                $map[] = ['b.item_id','eq',$v['id']];
                $map[] = ['b.specs_ids','eq',$v['specs_ids']];
                $map[] = ['b.status','eq',1];
                $seckill = Db::name('flash_sale')
                    ->alias('a')
                    ->join('flash_sale_attr b','a.id=b.flash_sale_id')
                    ->where($map)
                    ->field('a.postage_way,b.price,b.old_price')
                    ->find();
                if( !$seckill ){
                    return ['code'=>100,'msg'=>'礼包ID错误'];
                }
                $data[$k]['old_price'] = $seckill['old_price'];        //原价
                $data[$k]['price'] = $seckill['price'];        //礼包价
                $isAssemble = 1;
                $assemblePostagePrice = $seckill['postage_way'];
            }
        }
        $allPostagePrice = 0;
        foreach ( $data as $k=>$v ){
            $allPostagePrice += ($v['num']*$v['price']);
        }
        if( $assemblePostagePrice == 1 && $isAssemble == 1 ){   //免邮模式
            $allNewPostagePrice = 0;
        }else{
            if( $allPostagePrice >= 99 ){
                $allNewPostagePrice = 0; //第二期的计算运费规则：满99包邮，反之运费十元,此处的运费只能是普通商品的运费
            }else{
                $allNewPostagePrice = 10;
            }
        }
//        $data 为最后计算出每个商品的运费,data为第一期每一种商品的运费
        return ['code'=>200,'data'=>$data,'allNewPostagePrice'=>$allNewPostagePrice];
    }

    /***
     * $areaId 地址id
     * $itemData 商品的具体参数：重量，体积，数量
     * $postage  商品对应的运费规格
     * 计算运费
     */
    public function getPostAgePrice($areaId,$itemData,$postage){
        foreach ($postage as $k=>$v){
            $postage[$k]['area_ids'] = explode(',',$v['area_ids']);
        }
        foreach ($postage as $k=>$v){
            if( in_array($areaId,$v['area_ids']) ){
                if( $itemData <= $v['first'] ){
                    $postagePrice = $v['first_price'];
                    return $postagePrice;
                }else{
                    $surplus = $itemData - $v['first'];     //剩余的重量/件数/体积
                    if( $surplus <= $v['two'] ){
                        $postagePrice = $v['first_price'] + $v['two_price'];
                        return $postagePrice;
                    }else{
                        $integer = intval($surplus/$v['two']);      //剩余部分的整数 肯定大于等于1
                        $remainder = $surplus%$v['two'];        //剩余部分的余数
                        if( $remainder >0 ){
                            $postagePrice = $v['first_price'] + ($integer*$v['two_price']) + $v['two_price'];
                        }else{
                            $postagePrice = $v['first_price'] + ($integer*$v['two_price']);
                            return $postagePrice;
                        }
                    }
                }
            }
        }
    }

    /***
     * 获取运费第二期
     */
    public function getPostage1($item){
        if( count($item) <= 0 ){
            return  ['code'=>100,'msg'=>'请选择商品'];
        }
    }

    /***
     * 上传图片
     */
    public function upload(){
        $file = $this->request->file('file');
        $Upload = new UploadModel();
        $res = $Upload ->upload($file);
        if( $res['code'] == 0 ){
            $data = array(
                'code'  =>200,
                'msg'   =>'上传成功',
                'data'  =>array(
                    'key'   =>$res['data']['key'],
                    'url'   =>"http://picture.ddxm661.com/".$res['data']['key']
                )
            );
        }else{
            $data = array(
                'code'=>100,
                'msg'   =>$res['msg']
            );
        }
        return json($data);
    }

    /***
     * 删除图片
     */
    public function delImg(){
        $file = request()->post('file');
        $Upload = new UploadModel();
        $res = $Upload ->delImg($file);
        if( $res['code'] == 1 ){
            $res['code'] = 200;
        }
        return json($res);
    }

    /***
     * 验证身份信息
     * @return \think\response\Json
     */
    public function CheckingIdCard(){
        $data = $this ->request ->post();
        if( empty($data['img'])){
            return json(['code'=>100,'msg'=>'缺少图片参数']);
        }
        $CheckingIdCardModel = new CheckingIdCardModel();
        $info = $CheckingIdCardModel ->checkingIdCard('http://picture.ddxm661.com/'.$data['img'],$data['idCardSide']);
        return json($info);
    }

    /***
     * $data 为二维数组
     * 7个参数
     *小程序订单下单、退单
     *只有门店用户才会产生股东数据
     * $data['pay_way']:购买东西的类型:1购买商品，2充值，3购买服务，4购卡
     */
    public function addToStatistics( $data ){
        if( count($data) == 0 ){
            return ['code'=>100,'msg'=>'请传入二维数组'];
        }
        $statisticsData = [];       //所有数据
        foreach ( $data as $k=>$v ) {
            if( empty($v['order_id']) ){
                return ['code'=>100,'msg'=>'缺少订单id'];
            }
            if( empty($v['shop_id']) ){
                return ['code'=>100,'msg'=>'缺少门店id'];
            }
            if( empty($v['order_sn']) ){
                return ['code'=>100,'msg'=>'缺少订单编号'];
            }
            if( empty($v['type']) ){
                return ['code'=>100,'msg'=>'统计类型:1:余额充值,2:购卡,3:消费收款,4:余额消耗,5消费消耗,6商品外包分润,7推拿外包分润,8商品成本,9营业费用,10外包商品成本'];
            }
            if( empty($v['data_type']) ){
                return ['code'=>100,'msg'=>'订单类型:1新订单，2退单'];
            }
            if( empty($v['pay_way']) ){
                return ['code'=>100,'msg'=>'支付方式'];
            }
            if( empty($v['price']) ){
                return ['code'=>100,'msg'=>'缺少金额'];
            }
            if( $v['data_type'] == 2 && ($v['price']>0) ){
                return ['code'=>100,'msg'=>'退单金额必须为负数'];
            }
            if( $v['data_type'] == 1 && ($v['price']<0) ){
                return ['code'=>100,'msg'=>'下单金额必须为正数'];
            }
            if( empty($v['pay_way']) || $v['pay_way'] == 1 || $v['pay_way'] == 3 ){
                $v['title'] = '小程序购买商品';
            }else if( $v['pay_way'] == 2 ){
                $v['title'] = '小程序购买充值';
            }else if( $v['pay_way'] == 3 ){
                $v['title'] = '小程序购买服务';
            }else if( $v['pay_way'] == 4 ){
                $v['title'] = '小程序购卡';
            }
            $arr = $v;
//            unset($arr['pay_way']);
            $arr['create_time'] = time();
            array_push($statisticsData , $arr);
        }
        $res = Db::name('statistics_log') ->insertAll($statisticsData);
        if( $res ){
            return ['code'=>200,'msg'=>'加入股东数据成功'];
        }else{
            return ['code'=>100,'msg'=>'加入股东数据失败'];
        }
    }
}