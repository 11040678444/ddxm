<?php

// +----------------------------------------------------------------------
// | 导出excel
// +----------------------------------------------------------------------
namespace app\admin\controller;
use app\admin\model\order\OrderTp5Model;
use app\mall\model\order\OrderModel;
use app\common\controller\Adminbase;
use app\admin\model\excel\ExcelModel;
use think\Db;
use think\db\Where;

class Excel extends Adminbase
{

    public function index(){
        set_time_limit(0);
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel2007.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
        $phpexcel = new \PHPExcel();
        $model = new ExcelModel();
        $data  = $model->MemberExport();
        $phpexcel->getActiveSheet()->setCellValue('A1','ID')
            ->setCellValue('B1','昵称')
            ->setCellValue('C1','手机号')
            ->setCellValue('D1','所属店铺')
            ->setCellValue('E1','会员等级')
            ->setCellValue('F1','OPENID')
            ->setCellValue('G1','累积充值')
            ->setCellValue('H1','会员余额');
        /*$options = ["A1","A2","A3"];*/
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        foreach ($data as $k=>$v){
            $phpexcel->getActiveSheet()->setCellValue('A'.($k+2),$v['id'])
                ->setCellValue('B'.($k+2),$v["nickname"])
                ->setCellValueExplicit('C'.($k+2),$v['mobile'])
                ->setCellValueExplicit('D'.($k+2),$v['shop_name'])
                ->setCellValueExplicit('E'.($k+2),$v['level_name'])
                ->setCellValue('F'.($k+2),isset($v['openid'])?$v['openid']:'无')
                ->setCellValue('G'.($k+2),$v['recharge'])
                ->setCellValue('H'.($k+2),$v['money']);
        }
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date("Y-m-d H:i:s",time()).' 会员列表.xls"');
        header('Cache-Control: max-age=0');
        $a = new \PHPExcel_Writer_Excel5($phpexcel);
        $a->save('php://output');
    }

    public function ticket_consume_details(){
        $res = $this->request->get();
        set_time_limit(0);
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel5.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/Writer/Excel2007.php';
        require_once APP_PATH . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel/IOFactory.php';
        $phpexcel = new \PHPExcel();
        $model = new ExcelModel();
        $data  = $model->ticket_consume_details($res);
        $phpexcel->getActiveSheet()->setCellValue('A1','序号')
            ->setCellValue('B1','门店')
            ->setCellValue('C1','会员昵称')
            ->setCellValue('D1','会员账号')
            ->setCellValue('E1','服务名称')
            ->setCellValue('F1','服务人员')
            ->setCellValue('G1','服务次数')
            ->setCellValue('H1','金额')
            ->setCellValue('I1','时间');
        /*$options = ["A1","A2","A3"];*/
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('H')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('I')->setWidth(10);
        foreach ($data as $k=>$v){
            $phpexcel->getActiveSheet()->setCellValue('A'.($k+2),$v['id'])
                ->setCellValue('B'.($k+2),$v["shop_name"])
                ->setCellValueExplicit('C'.($k+2),$v['nickname'])
                ->setCellValueExplicit('D'.($k+2),$v['mobile'])
                ->setCellValueExplicit('E'.($k+2),$v['service_name'])
                ->setCellValue('F'.($k+2),$v['waiter'])
                ->setCellValue('G'.($k+2),$v['num'])
                ->setCellValue('H'.($k+2),$v['price'])
                ->setCellValue('I'.($k+2),$v['time'])
            ;
        }
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date("Y-m-d H:i:s",time()).' 会员列表.xls"');
        header('Cache-Control: max-age=0');
        $a = new \PHPExcel_Writer_Excel5($phpexcel);
        $a->save('php://output');
    }



    //导出 门店订单数据
    public function exportShopOrderExcel(){
        set_time_limit(0);
        $res = $this->request->get();
        $where = [];
        $Order = new OrderTp5Model();
        if(isset($res['search']) && !empty($res['search'])){
            $where[] = ['a.sn|m.mobile',"like","%{$res['search']}%"];
        }
        //门店搜索
        if(isset($res['shop']) && !empty($res['shop'])){
            $where[] = ['a.shop_id',"=",$res['shop']];
        }
        //支付方式搜索
        if(isset($res['pay_way']) && !empty($res['pay_way'])){
            $where[] = ['a.pay_way',"=",$res['pay_way']];
        }
        //订单状态搜索
        if(isset($res['status']) && !empty($res['status'])){
            $where[] = ['a.order_status',"=",$res['status']];
        }
        //订单对账状态搜索
        if(isset($res['is_examine']) && $res['is_examine'] != '' ){
            $where[] = ['a.is_examine',"=",$res['is_examine']];
        }
        //开始时间
        if(isset($res['start_time']) && !empty($res['start_time'])){
            $start_time = strtotime($res['start_time']);
            $where[] = ['a.add_time',">=",$start_time];
        }
        // 结束时间
        if(isset($res['end_time']) && !empty($res['end_time'])){
            $end_time = strtotime($res['end_time']) + 86399;
            $where[] = ['a.add_time',"<=",$end_time];
        }
        $where[] = ["type","in",'1,2,7'];
        $where[] = ["is_online","eq",0];

        $_list = $Order
            ->alias("a")
            ->where($where)
            ->field("s.name,a.sn,s.name,a.add_time,m.mobile,m.nickname,a.pay_way,a.id,a.amount,a.remarks,a.waiter_id")
            ->join("ddxm_member m","a.member_id=m.id",'LEFT')
            ->join("ddxm_shop s","a.shop_id=s.id")
            ->order("overtime desc")
            ->select();

        foreach ( $_list as $k=>$v ){
            $_list[$k]['item_list1'] = $Order ->get_item_list(['id'=>$v['id']]);
            $_list[$k]['price_list1'] = $Order ->get_price_list(['id'=>$v['id']]);
            $_list[$k]['num_list1'] = $Order ->get_num_list(['id'=>$v['id']]);
            $_list[$k]['worker_name'] = $Order ->get_worker_name(['id'=>$v['id'],'waiter_id'=>$v['waiter_id']]);
            $_list[$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            if( empty($v['mobile']) ){
                $_list[$k]['mobile'] = '散客';
                $_list[$k]['nickname'] = '散客';
            }
            if( empty($v['remarks']) ){
                $_list[$k]['remarks'] = '无';
            }
        }
        $result = [];
        foreach ( $_list as $k=>$v ){
            $arr = [];
            if( count($v['item_list1']) >= 2 ){
                foreach ($v['item_list1'] as $k1=>$v1){
                    $arr = array(
                        'name'  =>$v['name'],
                        'sn'  =>$v['sn'],
                        'add_time'  =>$v['add_time'],
                        'mobile'  =>$v['mobile'],
                        'nickname'  =>$v['nickname'],
                        'pay_way'  =>$v['pay_way'],
                        'id'  =>$v['id'],
                        'amount'  => $v['price_list1'][$k1]*$v['num_list1'][$k1],
//                        'amount'  => round($v['price_list1'][$k1]*$v['num_list1'][$k1]),
                        'remarks'  =>$v['remarks'],
                        'item_list1'  =>$v['item_list1'][$k1],
                        'price_list1'  =>$v['price_list1'][$k1],
                        'num_list1'  =>$v['num_list1'][$k1],
                        'worker_name'  =>$v['worker_name'][$k1]

                    );
                    array_push($result ,$arr);
                }

            }else{
                $arr = array(
                    'name'  =>$v['name'],
                    'sn'  =>$v['sn'],
                    'add_time'  =>$v['add_time'],
                    'mobile'  =>$v['mobile'],
                    'nickname'  =>$v['nickname'],
                    'pay_way'  =>$v['pay_way'],
                    'id'  =>$v['id'],
                    'amount'  =>$v['amount'],
                    'remarks'  =>$v['remarks'],
                    'item_list1'  =>$v['item_list1']['0'],
                    'price_list1'  =>$v['price_list1']['0'],
                    'num_list1'  =>$v['num_list1']['0'],
                    'worker_name'  =>$v['worker_name'][0]
                );
                array_push($result ,$arr);
            }
        }
        $order = [];
        $order = $result;
        $count = count($order);
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
//引入Excel类
        $objPHPExcel = new \PHPExcel();
// 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("order")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
//设置excel工作表名及文件名
        $excel_filename = '门店订单_'.date('Ymd_His');
// 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
//第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1',$excel_filename);
//合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:j1');

//        $objPHPExcel->getActiveSheet()->mergeCells('A1:AC1');
//设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//设置excel的表头
        $sheet_title = array('订单编号','时间','门店','会员账号','会员昵称','支付方式','商品或服务','数量','单价','合计金额','备注','服务员');

// 设置第一行和第一行的行高
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L');
//            'K','L','M','N','O','P',
//            'Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
//设置单元格
        $objPHPExcel->getActiveSheet()->getStyle('A2:j2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//首先是赋值表头
        for ($k=0;$k<count($sheet_title);$k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].'2',$sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);

        }
//开始赋值

//        $sheet_title = array('时间','门店','会员账号','会员昵称','支付方式','商品或服务','数量','单价','合计金额','备注');
        for ($i=0;$i<$count;$i++) {
            //先确定行
            $row = $i+3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $order[$i];
            for ($j = 0;$j<count($sheet_title);$j++) {
                //开始为每个单元格赋值
                switch ($j) {
                    case 0 :
                        //时间
                        $cellvalue = $temp['sn'];
                        break;
                    case 1 :
                        //时间
                        $cellvalue = $temp['add_time'];
                        break;
                    case 2:
                        //门店
                        $cellvalue = $temp['name'];
//                        $cellvalue = date('Y-m-d H:i:s',$temp['addtime']);
                        break;
                    case 3 :
                        //会员账号
                        $cellvalue = $temp['mobile'];
                        break;
                    case 4 :
                        //会员昵称
                        $cellvalue = $temp['nickname'];
                        break;
                    case 5 :
                        //支付方式
                        $cellvalue = $temp['pay_way'];
                        break;
                    case 6 :
                        //商品或服务
                        $cellvalue = $temp['item_list1'];
                        break;
                    case 7 :
                        //数量
                        $cellvalue = $temp['num_list1'];
                        break;
                    case 8 :
                        //单价
                        $cellvalue = $temp['price_list1'];
                        break;
                    case 9 :
                        //合计金额
                        $cellvalue =$temp['amount'];
                        break;
                    case 10 :
                        //备注
                        $cellvalue = $temp['remarks'];
                        break;
                    case 11 :
                        //服务员
                        $cellvalue = $temp['worker_name'];
                        break;
                }

//                    $objPHPExcel->getActiveSheet()->getStyle('A4')->getAlignment()->setWrapText(true);
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j].$row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                if ((in_array($j,[5,6,7])) && "" != $cellvalue) {
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
                }
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(21);
        }
        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle('门店订单');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /***
     * 导出,微商城订单数据
     */
    public function onlineOrderExcel(){
        set_time_limit(0);
        $res = $this->request->get();
        $where = [];
        if( !empty($res['sn']) ){
            $where[] = ['o.sn','like','%'.$res['sn'].'%'];
        }
        if( !empty($res['shop_id']) ){
            $where[] = ['o.shop_id','eq',$res['shop_id']];
        }
        if( !empty($res['mobile']) ){
            $where[] = ['o.mobile|m.mobile','like','%'.$res['mobile'].'%'];
        }
        if( !empty($res['subtitle']) ){
            $where[] = ['a.subtitle','like','%'.$res['subtitle'].'%'];
        }
        if( isset($res['pay_status']) && $res['pay_status'] != '' ){
            $where[] = ['o.pay_status','eq',$res['pay_status']];
        }
        if( isset($res['pay_way']) && $res['pay_way'] != '' ){
            $where[] = ['o.pay_way','eq',$res['pay_way']];
        }
        if( !empty($res['add_time']) ){
            $add_time = explode('-',$res['add_time']);
            $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $where[] = ['o.add_time','between',$addWhere];
        }
        if( !empty($res['paytime']) ){
            $add_time = explode('-',$res['paytime']);
            $addWhere = strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00').','.strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $where[] = ['o.paytime','between',$addWhere];
        }
//         if( isset($res['order_status']) && $res['order_status'] != '' ){
//             if( $res['order_status'] == 1 ){
//                 $where[] = ['o.pay_status','eq',0];
//             }
//             if( $res['order_status'] == 8 ){
//                 $where[] = ['o.pay_status','eq',-1];
//             }
//             if( $res['order_status'] == 2 ){
//                 $where[] = ['o.pay_status','eq',1];
//                 $where[] = ['o.refund_status','eq',0];    //没有退单
//                 $where[] = ['o.order_status','eq',0];    //待发货
//             }
//             if( $res['order_status'] == 3 ){
//                 $where[] = ['o.pay_status','eq',1];
//                 $where[] = ['o.refund_status','eq',0];    //没有退单
//                 $where[] = ['o.order_status','eq',1];
//             }
//             if( $res['order_status'] == 4 ){
//                 $where[] = ['o.pay_status','eq',1];
//                 $where[] = ['o.refund_status','eq',0];    //没有退单
//                 $where[] = ['o.order_status','not in','0,1']; //已完成
//             }
//             if( $res['order_status'] == 5 ){
// //                    $where[] = ['o.pay_status','eq',1];
//                 $where[] = ['o.refund_status','eq',1];    //申请退单中
//             }
//         }
        $where[] = ["o.is_online","=",1];
        $data = Db::name('order')->alias('o')
            ->join('shop s','o.shop_id=s.id')
            ->where($where)
            ->field("o.sn,o.add_time,o.paytime,o.pay_way,o.amount,s.name")
            ->withAttr('pay_way',function ($val,$data){
                if( $data['pay_way'] == 3 ){
                    return '余额';
                }else if( $data['pay_way']==1 ){
                    return '微信';
                }else{
                    return '未支付';
                }
            })
            ->withAttr('add_time',function ($val,$data){
                return date('Y-m-d H:i:s',$data['add_time']);
            })
            ->withAttr('paytime',function ($val,$data){
                if( empty($data['paytime']) ){
                    return '未支付';
                }else if( strlen($data['paytime']) >=14  ){
                    return date('Y-m-d H:i:s',strtotime($data['paytime']));
                }else{
                    return date('Y-m-d H:i:s',$data['paytime']);
                }
            })
            ->select();
        $order = [];
        $order = $data;
        $count = count($order);
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
//引入Excel类
        $objPHPExcel = new \PHPExcel();
// 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("order")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
//设置excel工作表名及文件名
        $excel_filename = '线上商城订单_'.date('Ymd_His');
// 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
//第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1',$excel_filename);
//合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:E1');

//        $objPHPExcel->getActiveSheet()->mergeCells('A1:AC1');
//设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//设置excel的表头
        $sheet_title = array('订单编号','下单时间','支付时间','支付方式','合计金额','所属门店');

// 设置第一行和第一行的行高
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        $letter = array('A','B','C','D','E','F');
//            ,'G','H','I','J','K','K','L','M','N','O','P',
//            'Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
//设置单元格
        $objPHPExcel->getActiveSheet()->getStyle('A2:j2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//首先是赋值表头
        for ($k=0;$k<count($sheet_title);$k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].'2',$sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);

        }
//开始赋值

//        $sheet_title = array('时间','门店','会员账号','会员昵称','支付方式','商品或服务','数量','单价','合计金额','备注');
//        dump($count);die;
        for ($i=0;$i<$count;$i++) {
            //先确定行
            $row = $i+3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $order[$i];
            for ($j = 0;$j<count($sheet_title);$j++) {
                //开始为每个单元格赋值
                switch ($j) {
                    case 0 :
                        //时间
                        $cellvalue = $temp['sn'];
                        break;
                    case 1 :
                        //时间
                        $cellvalue = $temp['add_time'];
                        break;
                    case 2:
                        //门店
                        $cellvalue = $temp['paytime'];
//                        $cellvalue = date('Y-m-d H:i:s',$temp['addtime']);
                        break;
                    case 3 :
                        //会员账号
                        $cellvalue = $temp['pay_way'];
                        break;
                    case 4 :
                        //会员昵称
                        $cellvalue = $temp['amount'];
                        break;
                    case 5 :
                        //支付方式
                        $cellvalue = $temp['name'];
                        break;
                }

//                    $objPHPExcel->getActiveSheet()->getStyle('A4')->getAlignment()->setWrapText(true);
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j].$row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                if ((in_array($j,[5,6,7])) && "" != $cellvalue) {
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
                }
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(21);
        }
        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle('线上商城订单');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /***
     * 已退单的导出
     */
    public function onlineRefundOrderExcel(){
        $res = $this ->request ->param();
        $model = new OrderModel();
        //排序规则， 注释掉
        $data = $model->refund_apply($res)
            ->order('id desc')
            ->select();
        $res = [];
        foreach ( $data as $k=>$v ){
            $arr = [];
            $arr = [
                'sn'    =>$v['sn'],
                'add_time'    =>date('Y-m-d H:i:s',$v['add_time']),
                'paytime'    =>$v['paytime'],
                'refund_time'    =>$v['refund_time']==0?'未处理':date('Y-m-d H:i:s',$v['refund_time']),
                'pay_way'    =>$v['pay_way']==1?'微信':'余额',
                'amount'    =>$v['money'],
                'my_status'    =>$v['refund_status']==1?'申请中':($v['refund_status']==2?'已同意':'已拒绝'),
                'shop_name'    =>$v['shop_name']
            ];
            array_push($res,$arr);
        }
        $order = [];
        $order = $res;
        $count = count($order);
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
//引入Excel类
        $objPHPExcel = new \PHPExcel();
// 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("order")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
//设置excel工作表名及文件名
        $excel_filename = '线上商城退货单_'.date('Ymd_His');
// 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
//第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1',$excel_filename);
//合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');

//        $objPHPExcel->getActiveSheet()->mergeCells('A1:AC1');
//设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//设置excel的表头
        $sheet_title = array('订单编号','下单时间','支付时间','退货时间','支付方式','合计金额','退款状态','所属门店');

// 设置第一行和第一行的行高
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        $letter = array('A','B','C','D','E','F','G','H');
//            ,'G','H','I','J','K','K','L','M','N','O','P',
//            'Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
//设置单元格
        $objPHPExcel->getActiveSheet()->getStyle('A2:j2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//首先是赋值表头
        for ($k=0;$k<count($sheet_title);$k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].'2',$sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        }
//开始赋值
//        $sheet_title = array('时间','门店','会员账号','会员昵称','支付方式','商品或服务','数量','单价','合计金额','备注');
//        dump($count);die;
        for ($i=0;$i<$count;$i++) {
            //先确定行
            $row = $i+3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $order[$i];
            for ($j = 0;$j<count($sheet_title);$j++) {
                //开始为每个单元格赋值
                switch ($j) {
                    case 0 :
                        //时间
                        $cellvalue = $temp['sn'];
                        break;
                    case 1 :
                        //时间
                        $cellvalue = $temp['add_time'];
                        break;
                    case 2:
                        //门店
                        $cellvalue = $temp['paytime'];
//                        $cellvalue = date('Y-m-d H:i:s',$temp['addtime']);
                        break;
                    case 3 :
                        //会员账号
                        $cellvalue = $temp['refund_time'];
                        break;
                    case 4 :
                        //会员昵称
                        $cellvalue = $temp['pay_way'];
                        break;
                    case 5 :
                        //支付方式
                        $cellvalue = $temp['amount'];
                        break;
                    case 6 :
                        //支付方式
                        $cellvalue = $temp['my_status'];
                        break;
                    case 7 :
                        //支付方式
                        $cellvalue = $temp['shop_name'];
                        break;
                }

//                    $objPHPExcel->getActiveSheet()->getStyle('A4')->getAlignment()->setWrapText(true);
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j].$row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                if ((in_array($j,[5,6,7])) && "" != $cellvalue) {
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
                }
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(21);
        }
        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle('线上商城退货订单');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /***
     *未付货订单导出
     */
    public function nofahuoOrderExcel(){
        $data = $this ->request ->param();
        $sql = 'SELECT
                    sh.name,
                    a.member_id,
                    a.sn,
                    a.paytime,
                    a.realname,
                    a.detail_address,
                    a.mobile,
                    a.amount,
                    a.pay_status,
                    a.pay_way,
                    a.order_distinguish,
                    a.assemble_status,
                    a.remarks,
                    b.subtitle,
                    s.bar_code,
                    b.num,
                    b.real_price,
                    s.cost,
                    s_sup.title as sup_titile,
                    b.attr_name,
                    b.attr_ids,
                    b.oprice,
                    a.cross_border,
                    b.refund_status,
                    a.wuyi_ok,
                    a.wuyi_item_amount
                FROM
                    ddxm_order_goods AS b
                JOIN ddxm_order a ON b.order_id = a.id
                LEFT JOIN ddxm_specs_goods_price s ON b.item_id=s.gid AND b.attr_ids=s.`key` AND s.`status`=1
                JOIN ddxm_item i ON b.item_id=i.id
                JOIN ddxm_shop sh ON a.shop_id=sh.id
                LEFT JOIN ddxm_shop_supplier s_sup ON i.sender_id=s_sup.id
                WHERE
                    b.`status` = 1
                AND b.deliver_status = 0
                AND a.is_online = 1
                AND a.pay_status = 1
                AND a.refund_status != 2
                AND a.order_status = 0';//JOIN ddxm_specs_goods_price s ON b.item_id=s.gid AND b.attr_ids=s.`key`
        if( !empty($data['sn']) ){
            $sn = '%'.$data['sn'].'%';
            $sql .= " AND a.sn like '$sn'" ;
        }
        if( !empty($data['mobile']) ){
            $mobile = '%'.$data['mobile'].'%';
            $sql .= " AND a.mobile like '$mobile'" ;
        }
        if( !empty($data['shop_id']) ){
            $shop_id = $data['shop_id'];
            $sql .= " AND a.shop_id = $shop_id" ;
        }
        if ( !empty($data['deliver_type']) && $data['deliver_type'] == 1 )
        {
            $sql .=" AND b.refund_status = 0 ";
        }
        else if ( !empty($data['deliver_type']) && $data['deliver_type'] == 2 )
        {
            $sql .=" AND b.refund_status = 1 ";
        }else{
            $sql .=" AND b.refund_status = 0 ";
        }
        if( !empty($data['paytime']) ){
            $add_time = explode('-',$data['paytime']);
            $start_time =  strtotime($add_time[0].'-'.$add_time[1].'-'.$add_time[2].' 00:00:00');
            $end_time = strtotime($add_time[3].'-'.$add_time[4].'-'.$add_time[5].' 23:59:59');
            $sql .= " AND a.paytime BETWEEN $start_time AND $end_time";
        }
        //添加个分组查询，目的是兼容用户购买规格商品后，后台又修改了规格的场景
        $sql.=' GROUP BY b.id ORDER BY a.sn desc';
        $res = Db::query($sql);
        $order = [];
        foreach ( $res as $k=>$v ){
            $arr = [];
            $arr = [
                'sn'    =>  $v['sn'],
                'paytime'    => date('Y-m-d H:i:s',$v['paytime']),
                'shop_name'    => $v['name'],
                'mobile'    => $v['mobile'],
                'amount'    => $v['amount'],
                'subtitle'    => $v['subtitle'],
                'real_price'    => $v['real_price'],
                'num'    => $v['num'],
                'cost'    => $v['oprice'],
                'sup_titile'    => $v['sup_titile'],
                'attr_name'    => $v['attr_name'],
                'bar_code'    => $v['bar_code'],
                'realname'  =>$v['realname'],
                'detail_address' =>$v['detail_address'],
                'remarks' =>$v['remarks'],
                'cross_border'=>$v['cross_border']==1?'跨境购':'商城自营',
                'refund_status'	=>$v['refund_status']==1?'申请中':'正常',
                'wuyi_huodong'  =>$v['wuyi_ok']==1?$v['wuyi_item_amount']:'未参与五一活动'
            ];
            array_push($order,$arr );
        }//halt($order);
        // dump($order);die;
        $count = count($order);
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';
//引入Excel类
        $objPHPExcel = new \PHPExcel();
// 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("order")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
//设置excel工作表名及文件名
        $excel_filename = '线上商城未发货单_'.date('Ymd_His');
// 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
//第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1',$excel_filename);
//合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');

//        $objPHPExcel->getActiveSheet()->mergeCells('A1:AC1');
//设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
//设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
//设置excel的表头
        $sheet_title = array('订单编号','支付时间','所属门店','收货人','电话','收货地址','金额','商品名称','规格','条形码','商品实际支付金额','商品数量','商品成本','供应商','备注','来源','退款状态','五一活动');

// 设置第一行和第一行的行高
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','P');
//            ,'G','H','I','J','K','K','L','M','N','O','P',
//            'Q','R','S','T', 'U','V','W','X','Y','Z','AA','AB','AC');
//设置单元格
        $objPHPExcel->getActiveSheet()->getStyle('A2:j2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
//首先是赋值表头
        for ($k=0;$k<count($sheet_title);$k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].'2',$sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k].'2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(18);
            $objPHPExcel->getActiveSheet()->getColumnDimension('P')->setWidth(30);
        }
//开始赋值
//        array('订单编号','支付时间','所属门店','电话','金额','商品名称','商品实际支付金额','商品数量','商品成本','供应商','规格','收货人','收货地址','备注','备注');
//        dump($count);die;
        for ($i=0;$i<$count;$i++) {
            //先确定行
            $row = $i+3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $order[$i];
            for ($j = 0;$j<count($sheet_title);$j++) {
                //开始为每个单元格赋值
                switch ($j) {
                    case 0 :
                        //时间
                        $cellvalue = $temp['sn'];
                        break;
                    case 1 :
                        //时间
                        $cellvalue = $temp['paytime'];
                        break;
                    case 2:
                        //门店
                        $cellvalue = $temp['shop_name'];
//                        $cellvalue = date('Y-m-d H:i:s',$temp['addtime']);
                        break;
                    case 3 :
                        //会员账号
                        $cellvalue = $temp['realname'];
                        break;
                    case 4 :
                        //会员昵称
                        $cellvalue = $temp['mobile'];
                        break;
                    case 5 :
                        //支付方式
                        $cellvalue = $temp['detail_address'];
                        break;
                    case 6 :
                        //支付方式
                        $cellvalue = $temp['amount'];
                        break;
                    case 7 :
                        //支付方式
                        $cellvalue = $temp['subtitle'];
                        break;
                    case 8 :
                        //支付方式
                        $cellvalue = $temp['attr_name'];
                        break;
                    case 9 :
                        //支付方式
                        $cellvalue = $temp['bar_code'];
                        break;
                    case 10 :
                        //支付方式
                        $cellvalue = $temp['real_price'];
                        break;
                    case 11 :
                        //支付方式
                        $cellvalue = $temp['num'];
                        break;
                    case 12 :
                        //收货人
                        $cellvalue = $temp['cost'];
                        break;
                    case 13 :
                        //收货地址
                        $cellvalue = $temp['sup_titile'];
                        break;
                    case 14 :
                        //备注
                        $cellvalue = $temp['remarks'];
                        break;
                    case 15 :
                        //来源
                        $cellvalue = $temp['cross_border'];
                        break;
                    case 16 :
                        //来源
                        $cellvalue = $temp['refund_status'];
                        break;
                    case 17 :
                        //活动
                        $cellvalue = $temp['wuyi_huodong'];
                        break;
                }

//                    $objPHPExcel->getActiveSheet()->getStyle('A4')->getAlignment()->setWrapText(true);
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j].$row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                if ((in_array($j,[5,6,7])) && "" != $cellvalue) {
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setWrapText(true); // 自动换行
                    $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); // 垂直方向上中间居中
                }
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(21);
        }
        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle('线上商城退货订单');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$excel_filename.'.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }
}
