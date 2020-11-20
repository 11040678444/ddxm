<?php


namespace app\admin\controller;


use PHPExcel_Style_Alignment;
use think\Controller;

class Test extends Controller
{
    function index(){

        $order = db('order')->select();
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
        $sheet_title = array('时间','门店','会员账号','会员昵称','支付方式','商品或服务','数量','单价','合计金额','备注');

// 设置第一行和第一行的行高
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(20);
        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(25);
        $letter = array('A','B','C','D','E','F','G','H','I','J');
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
                        $cellvalue = "我是订单编号123123";
                        break;
                    case 1 :
                        //门店
                        $cellvalue = '2019-12-12 12:12:12';
//                        $cellvalue = date('Y-m-d H:i:s',$temp['addtime']);
                        break;
                    case 2 :
                        //会员账号
                        $cellvalue = "￥1212.00";
                        break;
                    case 3 :
                        //会员昵称
                        $cellvalue = '已购买';
                        break;
                    case 4 :
                        //支付方式
                        $cellvalue = '已支付';
                        break;
                    case 5 :
                        //商品或服务
                        $cellvalue = '2019-12-12 12:12:12';
                        break;
                    case 6 :
                        //数量
                        $cellvalue = '111111111111';
                        break;
                    case 7 :
                        //单价
                        $cellvalue = '自提';
                        break;
                    case 8 :
                        //合计金额
                        $cellvalue ='收货人姓名张三';
                        break;
                    case 9 :
                        //备注
                        $cellvalue = '1111';
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j].$row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j].$row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                //设置自动换行
                if ((in_array($j,[15,16,17,18,19])) && "" != $cellvalue) {
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
    function exportOrderExcel($title,$cellName,$data)
    {
        require_once APP_PATH . '/../vendor/PHPExcel/PHPExcel.php';

    }

    /***
     * 测试redis
     */
}