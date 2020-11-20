<?php


namespace app\index\controller;


use PHPExcel_Cell;
use PHPExcel_IOFactory;
use think\Controller;
use Think\Db;

class Test extends Controller
{


    //检查余额 是否有问题的 添加到 商城上面   最新
    public function index3()
    {

        set_time_limit(0);
//        1  查询所有会员
        $member_list = \db('member')
            ->where('is_verify','0')
//            ->where('mobile','15683269190')
//            ->where('id','32')
            ->select();


        if($member_list == false){
            $floag = false;
        }

        $youwneti_data = [];
        $last_id = 0;
        foreach ($member_list as $member){

//        $member = \db('member')->where('mobile','15923803667')->find();

            $member_id = $member['id'];
            $member_mobile = $member['mobile'];

            $last_id = $member_id;

            //order 购买订单
            //门店退单  order_refund
            //商城 退单  refund_apply

//           2 查询这个用户一共 余额用了多少钱
//            付款总金额
            $order_sum =\db('order')
                ->where('member_id',$member_id)
                ->where('pay_status','1')
                ->where('pay_way','in','3,13')
                ->sum('amount');

//            $order_sum =\db('order')
//                ->alias('o')
//                ->field('o.id,o.amount,o.paytime')
//                ->where('o.member_id',$member_id)
//                ->where('o.pay_status','1')
//                ->where('o.pay_way','in','3,13')
//                ->select();
//
//            $dd =[];
//
//            foreach ($order_sum as $value){
//
//                $value['paytime']=date('Y-m-d H:i:s',$value['paytime']);
//                $name='';
//
//                $ddd = \db('order_goods')->where('order_id',$value['id'])->select();
//                foreach ($ddd as $value2){
//
//                    $name =  $name.$value2['subtitle'].',';
//                }
//                $value['name']=$name;
//                array_push($dd,$value);
//            }
//
//            return json($dd);

            //  3 门店 退款余额
//            付款总金额
            $order_refund_sum =\db('order_refund')
                ->alias('or')
                ->where('o.member_id',$member_id)
                ->where('o.is_online','0')
                ->where('or.r_status','1')
                ->where('o.pay_status','1')
                ->where('o.pay_way','in','3,13')
                ->join("order o","o.id=or.order_id")
                ->sum('or.r_amount');


//            $ddxm_order_refund_apply_sum =\db('order_refund_apply')
//                ->field('og.subtitle,or.add_time,or.money')
//                ->alias('or')
//                ->where('o.member_id',$member_id)
//                ->where('or.status','2')
//                ->where('o.is_online','1')
//                ->where('o.pay_status','1')
//                ->where('o.pay_way','3')
//                ->join("ddxm_order o","o.id=or.order_id")
//                ->join('ddxm_order_goods og','og.id=or.goods_id')
//                ->select();
//            return $ddxm_order_refund_apply_sum;
//            og.subtitle
            $dd =[];
//
//            foreach ($ddxm_order_refund_apply_sum as $value){
//
//                $value['add_time']=date('Y-m-d H:i:s',$value['add_time']);
//                array_push($dd,$value);
//            }
////
//            return json($dd);


            //  4 商城 退款余额
//            付款总金额
            $ddxm_order_refund_apply_sum =\db('order_refund_apply')
                ->alias('or')
                ->where('o.member_id',$member_id)
                ->where('or.status','2')
                ->where('o.is_online','1')
                ->where('o.pay_status','1')
                ->where('o.pay_way','3')
                ->join("order o","o.id=or.order_id")
                ->sum('or.money');

//            5 老系统余额
            $lxt_money =Db::name('member22')
                ->where('mobile',$member_mobile)->value('money');

//            6 门店充值余额
//            $ddxm_member_recharge_log_sum1 = \db('member_recharge_log')
//                ->where('member_id',$member_id)
//                ->where('type','1')
//                ->sum('price');
//
//            $ddxm_member_recharge_log_sum2 = \db('member_recharge_log')
//                ->where('member_id',$member_id)
//                ->where('type','2')
//                ->where('price','<','0')
//                ->sum('price');
//
//
//
//            $ddxm_member_recharge_log_sum = $ddxm_member_recharge_log_sum1+$ddxm_member_recharge_log_sum2;

            //总的充值限时余额 + 总的充值余额
            $ddxm_member_recharge_log_sum = Db::name('order')
                ->where('member_id',$member_id)
                ->where('pay_status',1)
                ->where('type',3)
//                ->select();
                ->sum('amount');

//            return $ddxm_member_recharge_log_sum;

//            return json($ddxm_member_recharge_log_sum);
//                7 后台充值限时余额
//            $ddxm_member_recharge_log_xsye_sum = \db('member_recharge_log')
////                ->where('member_id',$member_id)
////                ->where('type','2')
////                ->where('price','>','0')
////                ->sum('price');
///
///             用户充值的限时余额
            $ddxm_member_recharge_log_xsye_sum = \db('member_money_expire')
                ->where('member_id',$member_id)
                ->sum('price');


//                8 当前余额情况
            $ddxm_member_money_sum = \db('member_money')
                ->where('member_id',$member_id)->find();

//                9 已过期限时余额
            $ddxm_member_money_expire_sum = \db('member_money_expire')
                ->where('member_id',$member_id)
                ->where('status','=','2')
                ->select();



            $ddxm_member_recharge_log_sum = $ddxm_member_recharge_log_sum - $ddxm_member_recharge_log_xsye_sum;

            $price_sum = 0;
            foreach ($ddxm_member_money_expire_sum as $value){
                $price = $value['price']-$value['use_price'];
                $price_sum = $price_sum+$price;
            }

//            10 未激活的线上余额
            $ddxm_member_money_expire =\db('member_money_expire')
                ->where('member_id',$member_id)
                ->where('status','0')
                ->sum('price');

//                计算是否超标的数据

            $all = $lxt_money+$ddxm_member_recharge_log_sum+$ddxm_member_recharge_log_xsye_sum
                -$order_sum+$order_refund_sum+$ddxm_order_refund_apply_sum-$price_sum;

            $index_money = $ddxm_member_money_sum['money'];

            //               11 未使用的限时余额（包括未激活）
            $notUsePriceList = \db('member_money_expire')
                ->where('member_id',$member_id)
                ->where('status','<>','2')
                ->select();
            $notUsePrice = 0;       //未使用的限时余额（包括未激活）
            foreach ( $notUsePriceList as $k=>$v )
            {
                $notUsePrice += $v['price']- $v['use_price'];
            }


            $zq_money = $lxt_money+$ddxm_member_recharge_log_sum+$ddxm_member_recharge_log_xsye_sum-
                $order_sum+$order_refund_sum+$ddxm_order_refund_apply_sum;


            $rea = '会员ID：'. $member_id
                .' 会员手机号： '.$member_mobile
                .'<br>'
                .' 老系统剩余余额： '.$lxt_money
                .'<br>'
                .' 当前---余额 包含限时余额 '.$index_money
                .' 当前---剩余限时余额 '.$notUsePrice
                .' 当前---余额当前剩余普通余额 '.($index_money-$notUsePrice)
                .' 当前---线上充值余额 '.$ddxm_member_money_sum['online_money']
                .' 当前---会员分销余额 '.$ddxm_member_money_sum['retail_money']
                .'<br>'
                .' 门店充值金额： '.$ddxm_member_recharge_log_sum
                .' 后台充值限时金额： '.$ddxm_member_recharge_log_xsye_sum
                .' 限时余额过期金额： '.$price_sum
                .' 未激活的限时余额： '.$ddxm_member_money_expire
                .'<br>'
                .'  总消费金额:'.$order_sum
                .'<br>'
                .'  门店总退款金额： '.$order_refund_sum
                .'  商城总退款金额： '.$ddxm_order_refund_apply_sum
                .'<br>'
                .'最后的用户ID： '.$last_id
            .'<br>'
            .' 真正的---正确余额： '.$zq_money;

//            return $rea;

//            return $index_money.'___'.$notUsePrice.'____'.$all.'____'.$index_money;
//            $index_money-$notUsePrice)<0
            if((bccomp($index_money,$notUsePrice) ==-1) || (bccomp($all,$index_money) !=0)){ //异常

                //余额有问题的

                $rea = '会员ID：'. $member_id
                    .' 会员手机号： '.$member_mobile
                    .'<br>'
                    .' 老系统剩余余额： '.$lxt_money
                    .'<br>'
                    .' 当前---余额 包含限时余额 '.$index_money
                    .' 当前---剩余限时余额 '.$notUsePrice
                    .' 当前---余额当前剩余普通余额 '.($index_money-$notUsePrice)
                    .' 当前---线上充值余额 '.$ddxm_member_money_sum['online_money']
                    .' 当前---会员分销余额 '.$ddxm_member_money_sum['retail_money']
                    .'<br>'
                    .' 门店充值金额： '.$ddxm_member_recharge_log_sum
                    .' 后台充值限时金额： '.$ddxm_member_recharge_log_xsye_sum
                    .' 限时余额过期金额： '.$price_sum
                    .' 未激活的限时余额： '.$ddxm_member_money_expire
                    .'<br>'
                    .'  总消费金额:'.$order_sum
                    .'<br>'
                    .'  门店总退款金额： '.$order_refund_sum
                    .'  商城总退款金额： '.$ddxm_order_refund_apply_sum
                    .'<br>'
                    .'最后的用户ID： '.$last_id
                    .'<br>'
                    .' 真正的---正确余额： '.$zq_money
                ;

//                return $rea;

                $rea3=[
                    'uid'=>$member_id,
                    'mobile'=>$member_mobile,
                    'lxt_money'=>$lxt_money,
                    'index_money'=>$index_money,
                    'weijihuo'=>$ddxm_member_money_expire,
                    'ddxm_member_money_sum'=>$ddxm_member_money_sum,
                    'ddxm_member_recharge_log_sum'=>$ddxm_member_recharge_log_sum,
                    'ddxm_member_recharge_log_xsye_sum'=>$ddxm_member_recharge_log_xsye_sum,
                    'price_sum'=>$price_sum,
                    'order_sum'=>$order_sum,
                    'order_refund_sum'=>$order_refund_sum,
                    'ddxm_order_refund_apply_sum'=>$ddxm_order_refund_apply_sum,
                    'all'=>$zq_money,
                    'all2'=>$all-$index_money,
                    'indexshengyumoney'=>($index_money-$notUsePrice),
                ];

//                return $rea;

                $rea2=[
                    'content'=>$rea
                ];

                \db('test2')->insert($rea3);
//                \db('test')->insert($rea2);
//                array_push($youwneti_data,$rea2);
            }
//                9 限时余额过期  ddxm_money_expire_log
//    ddxm_member_money_expire 总限时余额

            \db('member')
                ->where('id',$member_id)->update(['is_verify'=>1]);

        }

        return \db('member')->where('is_verify','0')->count();
//        return  json($youwneti_data);
//        return $floag;
    }

    public function index2()
    {

//        1  查询所有会员
        $member = \db('member')->where('mobile','13752822260')->find();
//        $member = \db('member')->where('mobile','15923803667')->find();

        $member_id = $member['id'];
        $member_mobile = $member['mobile'];

        //order 购买订单
        //门店退单  order_refund
        //商城 退单  refund_apply

//           2 查询这个用户一共 余额用了多少钱
//            付款总金额
        $order_sum =\db('order')
            ->where('member_id',$member_id)
            ->where('pay_status','1')
            ->where('pay_way','in','3,13')
            ->sum('amount');

        //  3 门店 退款余额
//            付款总金额
        $order_refund_sum =\db('order_refund')
            ->alias('or')
            ->where('o.member_id',$member_id)
            ->where('o.is_online','0')
            ->where('or.r_status','1')
            ->where('o.pay_status','1')
            ->where('o.pay_way','in','3,13')
            ->join("order o","o.id=or.order_id")
            ->sum('or.r_amount');

        //  4 商城 退款余额
//            付款总金额
        $ddxm_order_refund_apply_sum =\db('order_refund_apply')
            ->alias('or')
            ->where('o.member_id',$member_id)
            ->where('or.status','2')
            ->where('o.is_online','1')
            ->where('o.pay_status','1')
            ->where('o.pay_way','3')
            ->join("order o","o.id=or.order_id")
            ->sum('or.money');

//            5 老系统余额
        $lxt_money =Db::name('member22')
            ->where('mobile',$member_mobile)->value('money');

//            6 门店充值余额
        $ddxm_member_recharge_log_sum1 = \db('member_recharge_log')
            ->where('member_id',$member_id)
            ->where('type','1')
            ->sum('price');

        $ddxm_member_recharge_log_sum2 = \db('member_recharge_log')
            ->where('member_id',$member_id)
            ->where('type','2')
            ->where('price','<','0')
            ->sum('price');
        $ddxm_member_recharge_log_sum = $ddxm_member_recharge_log_sum1+$ddxm_member_recharge_log_sum2;


//                7 后台充值限时余额
        $ddxm_member_recharge_log_xsye_sum = \db('member_recharge_log')
            ->where('member_id',$member_id)
            ->where('type','2')
            ->where('price','>','0')
            ->sum('price');


//                8 当前余额情况
        $ddxm_member_money_sum = \db('member_money')
            ->where('member_id',$member_id)->find();

//                9 当前剩余的限时余额
        $ddxm_member_money_expire_sum = \db('member_money_expire')
            ->where('member_id',$member_id)
            ->where('status','<>','2')
            ->select();

        $price_sum = 0;
        foreach ($ddxm_member_money_expire_sum as $value){
            $price = $value['price']-$value['use_price'];
            $price_sum = $price_sum+$price;
        }


//                计算是否超标的数据

        $all = $lxt_money+$ddxm_member_recharge_log_sum+$ddxm_member_recharge_log_xsye_sum
            -$order_sum+$order_refund_sum+$ddxm_order_refund_apply_sum-$price_sum;

        $index_money = $ddxm_member_money_sum['money'];
        if($all!=$index_money){

            //余额有问题的

        }
//                9 限时余额过期  ddxm_money_expire_log
//    ddxm_member_money_expire 总限时余额

        return  '会员ID：'. $member_id
            .' 会员手机号： '.$member_mobile
            .'<br>'
            .' 老系统剩余余额： '.$lxt_money
            .'<br>'
            .' 当前---余额 包含限时余额 '.$index_money
            .' 当前---线上充值余额 '.$ddxm_member_money_sum['online_money']
            .' 当前---会员分销余额 '.$ddxm_member_money_sum['retail_money']
            .'<br>'
            .' 门店充值金额： '.$ddxm_member_recharge_log_sum
            .' 后台充值限时金额： '.$ddxm_member_recharge_log_xsye_sum
            .' 限时余额剩余金额： '.$price_sum
            .'<br>'
            .'  总消费金额:'.$order_sum
            .'<br>'
            .'  门店总退款金额： '.$order_refund_sum
            .'  商城总退款金额： '.$ddxm_order_refund_apply_sum
            .'<br>'
            ;
    }

    public function memberMoney()
    {
        set_time_limit(0);
        $list = Db::name('member')
            ->alias('a')
            ->join('member_money b','a.id=b.member_id')
            ->join('shop c','a.shop_code=c.code','LEFT')
            ->where('b.money','<>',0)
            ->where('b.money','<>',0.00)
            ->where('a.shop_code','<>','A00046')
            ->field('a.id,a.mobile,a.shop_code,a.nickname,a.wechat_nickname,a.regtime,b.money,c.name')
            ->select();
        foreach ( $list as $k=>$v )
        {
            $list[$k]['regtime'] = date('Y-m-d H:i:s',$v['regtime']);
            $list[$k]['nickname'] = !empty($v['nickname'])?$v['nickname']:$v['wechat_nickname'];
            $list[$k]['wechat_nickname'] = !empty($v['wechat_nickname'])?$v['wechat_nickname']:$v['nickname'];
        }
        return json($list);
    }


    function GrabImage()
    {

        $url ='https://img20.360buyimg.com/vc/jfs/t1/81703/27/13843/952686/5db64900E41a9781e/e5ec72431c01ff68.jpg';
        if ($url != "") {    //如果图片地址为空
            $ext = strrchr($url, '.');    //判断图片的格式
            if ($ext != '.jpg' && $ext != '.gif' && $ext != '$png') {
                return false;
                exit;
            }
            $filename_r = time() . rand(10, 9000) . $ext;    //给图片命名
            $filename = 'getimg/' . $filename_r;
            ob_start();    //打开缓冲区
            readfile($url);
            $imginfo = ob_get_contents();    //获得缓冲区的内容
            ob_end_clean(); //清除并关闭缓冲区
            $fp = fopen($filename, 'a');
            fwrite($fp, $imginfo);
            fclose($fp);
        } else {
            return false;
        }

    }

    //导入 盘点数据
    public function importexcel(){
        set_time_limit(0);
        ini_set("memory_limit","1000M");
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
        //获取表单上传文件
        $file = request()->file('file');

//        $info = $file->validate(['ext' => 'xlsx,xls'])->move(APP_PATH . '/../public/' . DS . 'uploads');
        $info = $file->validate(['ext' => 'xlsx,xls'])->move(ROOT_PATH2 . '/uploads');
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/face');
        //数据为空返回错误
        if(empty($info)){
            $output['status'] = false;
            $output['info'] = '导入数据失败~';
            $this->ajaxReturn($output);
        }
        //获取文件名
        $exclePath = $info->getSaveName();
        //上传文件的地址
//        $filename = ROOT_PATH2 . '/uploads/3684149be5c08e804110c31baa19c675.xlsx';
        $filename = ROOT_PATH2 . '/uploads/' . $exclePath;

        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
        $extension = strtolower( pathinfo($filename, PATHINFO_EXTENSION) );
        if ($extension =='xlsx') {
            $objReader = new \PHPExcel_Reader_Excel2007();
            $objExcel = $objReader ->load($filename);
        } else if ($extension =='xls') {
            $objReader = new \PHPExcel_Reader_Excel5();
            $objExcel = $objReader->load($filename);
        }
        dump($objExcel);die;
        $excel_array=$objExcel->getsheet(0)->toArray();   //转换为数组格式

        array_shift($excel_array);  //删除第一个数组(标题);
//        array_shift($excel_array);  //删除th+



        //东和春天35,保利5,港城28,金茂29,麓山30,洋河40,爱情海6,奥山34,北郡24,茶园 38,国宾 39,江与城 27,蓝光 26,两江时光 21,鲁能 36,微商 49,南郡25
//        $purchaseData = [];
//        $shopItemData = [];
//        foreach ($data as $k=>$v){
//            $arr = [];
//            $arr = [
//                'shop_id'  => 49,
//                'item_id'   =>$v['id'],
//                'stock'     =>$v['number'],
//                'stock_ice' =>0
//            ];
//            array_push($shopItemData,$arr);
//
//            $arr = [];
//            $arr = [
//                'shop_id'   =>49,
//                'item_id'   =>$v['id'],
//                'type'      =>0,
//                'pd_id'     =>0,
//                'md_price'  =>$v['price'],
//                'store_cose'=>$v['price'],
//                'stock'     =>$v['number'],
//                'time'      =>time(),
//                'sort'      =>0
//            ];
//            array_push($purchaseData,$arr);
//        }
        dump($excel_array);die;
        $data = [];
        foreach ( $excel_array as $k=>$v ){
            if( !empty($v[16]) ){
                $arr = [];
                $arr = [
                    'code'   =>$v[16],
                    'stock' =>$v[13],
                    'price' =>$v[10],
                ];
                array_push($data,$arr);
            }
        }

        // 启动事务
        Db::startTrans();
        try {
            foreach ( $data as $k=>$v ){
                $arr = [];
                $itemId = Db::name('item')->where('bar_code',$v['code'])->value('id');
//                dump($itemId);die;
                $arr = [
                    'shop_id'   =>50,
                    'stock' =>$v['stock'],
                    'item_id'   =>$itemId,
                    'stock_ice'=>0
                ];
                Db::name('shop_item') ->insert($arr);

                $arr = [];
                $arr = [
                    'shop_id'   =>50,
                    'item_id'   =>$itemId,
                    'md_price'  =>$v['price'],
                    'store_cose'  =>$v['price'],
                    'stock'  =>$v['stock'],
                    'time'  =>time(),
                ];
                Db::name('purchase_price') ->insert($arr);
            }
//            foreach ($excel_array as $k=>$v){
//                if( $k >= 0 && !empty($v[0]) ){
//                    $arr = [];
//                    $arr = [
//                        'title' =>$v[2].$v[3].$v[5].$v[1],    //标题
//                        'item_type' =>2,    //门店商品
//                        'type_id' =>43,    //一级分类
//                        'type' =>81,    //二级分类
//                        'unit_id' =>9,    //单位
//                        'cate_id' =>9,    //分区
//                        'status' =>1,    //上线
//                        'bar_code' =>$v[0],    //上线
//                        'time' =>time(),    //上线
//                        'stock_alert' =>1,    //上线
//                        'update_time' =>time(),    //上线
//                        'in_allshop' =>1,    //上线
//                        'sort' =>1,    //上线
//                        'user_id' =>1    //上线
//                    ];
//                    //添加商品
//                    $itemId = Db::name('item') ->insertGetId($arr);
//
//                    //添加金额
//                    foreach ( $shopIds as $k1=>$v1 ){
//                        $itemPr = [];
//                        $itemPr = [
//                            'user_id'   =>1,
//                            'status'   =>1,
//                            'shop_id'   =>$v1,
//                            'item_id'   =>$itemId,
//                            'selling_price'   =>$v[6],
//                            'minimum_selling_price'   =>0,
//                        ];
//                        Db::name('item_price')->insert($itemPr);
//                    }
//
//                    //添加库存
//                    $newArr = [];
//                    $newArr = [
//                        'shop_id'   =>50,
//                        'item_id'   =>$itemId,
//                        'stock'   =>1,
//                        'stock_ice'   =>0,
//                    ];
//                    Db::name('shop_item') ->insert($newArr);
//
//                    //添加成本
//                    $puArr = [];
//                    $puArr = [
//                        'shop_id'   =>50,
//                        'type'  =>1,
//                        'pd_id' =>0,
//                        'item_id'   =>$itemId,
//                        'md_price'  =>sprintf("%.2f", $v[8]),
//                        'store_cose'  =>sprintf("%.2f", $v[8]),
//                        'stock'  =>1,
//                        'time'  =>time(),
//                        'sort'  =>0,
//                    ];
////                    dump($puArr);die;
//                    Db::name('purchase_price') ->insert($puArr);
//                }
//            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();
            dump('添加失败'.$e->getMessage());die;
        }
        dump('添加成功');
        exit;
    }

    /***
     * 查询单个会员的余额
     */
    public function tt(){
        $data = $this ->request->param();
        if( !empty($data['mobile']) ){
            $map[] = ['mobile','eq',$data['mobile']];
        }else{
            return_error('mobile');
        }
        $data['id'] = Db::name('member')->where('mobile',$data['mobile'])->value('id');
        if( empty($data['id']) ) {
            dump('用户未找到');die;
        }
        $map = [];
        $map[] = ['member_id','eq',$data['id']];
        $map[] = ['pay_status','eq',1];
//        $map[] = ['pay_way','in','3,13'];
        $order_id = Db::name('order')->where($map)->field('id,type,pay_way')->select();

        $czId = []; //总充值
        $xfId = []; //总消费(包含退单)
        foreach ( $order_id as $k1=>$v1 ){
            if( $v1['type'] == 3 ){
                array_push($czId,$v1['id']);
            }else{
                if( ($v1['pay_way']==3) || ($v1['pay_way'] == 13) ){
                    array_push($xfId,$v1['id']);
                }
            }
        }

        //查询总充值
        if( count($czId)>0 ){
            $all_cz_amount = Db::name('statistics_log')->where([['order_id','in',implode(',',$czId)]])->sum('price');
            $all_cz_order = Db::name('statistics_log')->where([['order_id','in',implode(',',$czId)]])->count();
        }else{
            $all_cz_amount = 0;
            $all_cz_order = 0;
        }
        //查询总消费
        if( count($xfId)>0 ){
            $all_xf_amount = Db::name('statistics_log')->where([['order_id','in',implode(',',$xfId)]])->where('type',4)->sum('price');
            $all_xf_count = Db::name('statistics_log')->where([['order_id','in',implode(',',$xfId)]])->where('type',4)->group('order_id')->count();
        }else{
            $all_xf_amount = 0;
            $all_xf_count = 0;
        }
        $old = Db::name('member_zheng') ->where('id',$data['id'])->value('money');
//        dump(Db::name('member_zheng')->getLastSql());die;
        $new = Db::name('member_money') ->where('member_id',$data['id'])->field('money,online_money')->find();
        dump('总充值：'.$all_cz_amount);
        dump('订单总充值订单：'.count($czId));
        dump('股东总充值订单：'.$all_cz_order);
        dump('总消费：'.$all_xf_amount);
        dump('订单总充值订单：'.count($xfId));
        dump('股东总充值订单：'.$all_xf_count);
        dump('老系统余额：'.$old);
        dump($new);
        die;
    }


    /***
     * 利用股东数据查询会员余额
     */
    public function memberMoney2()
    {
        set_time_limit(0);
        $list = Db::name('member')
            ->alias('a')
            ->join('member_money b','a.id=b.member_id','LEFT')
            ->where('a.is_fictitious',0)
            ->field('a.id,a.mobile,a.nickname,a.wechat_nickname,b.money,online_money')
            ->select();
        foreach ( $list as $k=>$v )
        {
            $map = [];
            $map[] = ['member_id','eq',$v['id']];
            $map[] = ['pay_status','eq',1];
//            $map[] = ['pay_way','in','3,13'];
            $order_id = Db::name('order')->where($map)->field('id,type,pay_way')->select();

            $czId = []; //总充值
            $xfId = []; //总消费(包含退单)
            foreach ( $order_id as $k1=>$v1 ){
                if( $v1['type'] == 3 ){
                    array_push($czId,$v1['id']);
                }else{
                    if( ($v1['pay_way']==3) || ($v1['pay_way'] == 13) ){
                        array_push($xfId,$v1['id']);
                    }
                }
            }

            //查询总充值
            if( count($czId)>0 ){
                $list[$k]['all_cz_amount'] = Db::name('statistics_log')->where([['order_id','in',implode(',',$czId)]])->sum('price');
            }else{
                $list[$k]['all_cz_amount'] = 0;
            }

            //查询总消费
            if( count($xfId)>0 ){
                $list[$k]['all_xf_amount'] = Db::name('statistics_log')->where([['order_id','in',implode(',',$xfId)]])->where('type',4)->sum('price');
            }else{
                $list[$k]['all_xf_amount'] = 0;
            }

            //查询老系统剩余余额
            $old = Db::name('member_zheng') ->where('mobile',$v['mobile'])->value('money');
            $list[$k]['old_money'] = !empty($old)?$old:0;
        }
        foreach ( $list as $k=>$v ){
            $list[$k]['duibi_money'] = $v['all_cz_amount'] - $v['all_xf_amount'] - $v['money'] - $v['online_money'] + $v['old_money'];
        }
        $count = 0;
        foreach ( $list as $k=>$v ){
            if( $v['duibi_money'] != 0 ){
                $count += 1;
            }
        }
        return json($list);
        dump($list);
    }

}