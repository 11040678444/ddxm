<?php

// +----------------------------------------------------------------------
// | 导出excel
// +----------------------------------------------------------------------
namespace app\admin\controller;
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
            ->setCellValue('H1','金额');
        /*$options = ["A1","A2","A3"];*/
        $phpexcel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('C')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('D')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('E')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $phpexcel->getActiveSheet()->getColumnDimension('G')->setWidth(10);
        $phpexcel->getActiveSheet()->getColumnDimension('h')->setWidth(10);
        foreach ($data as $k=>$v){
            $phpexcel->getActiveSheet()->setCellValue('A'.($k+2),$v['id'])
                ->setCellValue('B'.($k+2),$v["shop_name"])
                ->setCellValueExplicit('C'.($k+2),$v['nickname'])
                ->setCellValueExplicit('D'.($k+2),$v['mobile'])
                ->setCellValueExplicit('E'.($k+2),$v['service_name'])
                ->setCellValue('F'.($k+2),$v['waiter'])
                ->setCellValue('G'.($k+2),$v['num'])
                ->setCellValue('H'.($k+2),$v['price']);
        }
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.date("Y-m-d H:i:s",time()).' 会员列表.xls"');
        header('Cache-Control: max-age=0');
        $a = new \PHPExcel_Writer_Excel5($phpexcel);
        $a->save('php://output');
    }
}
