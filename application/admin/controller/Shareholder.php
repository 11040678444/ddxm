<?php
namespace app\admin\controller;

use think\Controller;
use PHPExcel;
use think\Db;

class Shareholder extends Controller
{
	//添加单个股东数据
    /**
    添加股东数据，给别人掉接口
     */
	public function add(){
		$data = $this ->request->post();
		if( count($data) <= 0 ){
			return json(['code'=>'100','msg'=>'没有参数']);
		}

		if( empty($data['shop_id']) ){
			return json(['code'=>'100','msg'=>'缺少没有门店id']);
		}
		if( empty($data['type']) ){
			return json(['code'=>'100','msg'=>'缺少没有类型']);
		}
		if( empty($data['data_type']) ){
			return json(['code'=>'100','msg'=>'缺少订单类型']);
		}
		if( empty($data['pay_way']) ){
			return json(['code'=>'100','msg'=>'缺少支付方式']);
		}
		if( empty($data['price']) ){
			return json(['code'=>'100','msg'=>'缺少金额']);
		}
		if( empty($data['order_id']) ){
			return json(['code'=>'100','msg'=>'缺少订单id']);
		}
		if( empty($data['order_sn']) ){
			return json(['code'=>'100','msg'=>'缺少订单编号']);
		}
		$data['create_time'] = time();
		$info = Db::name('statistics_log')->insertGetId($data);
		if( $info ){
			return json(['code'=>'200','msg'=>'添加成功','data'=>array('id'=>$info)]);
		}else{
			return json(['code'=>'100','msg'=>'添加失败']);
		}
	}

    public function test(){
	    $list = Db::name('shop')->field('id,name,code')->select();
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("ctos")
            ->setLastModifiedBy("ctos")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);

        //设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);

        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        //set font size bold
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        //设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //合并cell
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');

        // set table header content
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A2', '门店ID')
            ->setCellValue('B2', '门店名称')
            ->setCellValue('C2', '代码');


        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<count($list)-1;$i++){
            $objPHPExcel->getActiveSheet(0)->setCellValue('A'.($i+3), $list[$i]['id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B'.($i+3), $list[$i]['name']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C'.($i+3), $list[$i]['code']);
            $objPHPExcel->getActiveSheet()->getRowDimension($i+3)->setRowHeight(16);
        }


        //  sheet命名
        $objPHPExcel->getActiveSheet()->setTitle('门店汇总表');


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="门店表('.date('Ymd-His').').xls"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式

        $objWriter->save('php://output');
    }

    public  function test1 (){
	    $data = $this ->request ->post();
	    $res = $this ->refund_recharge($data);
	    dump($res);
    }

    /***
     * 退货添加充值订单
     *type:1:门店充值,2后台充值,3小程序充值,4服务卡退单充值,5商品退单充值
     * $type,$member_id,$price,$pay_way,$waiter_id,$remarks,$title
     */
        public  function refund_recharge($data)
        {
            if( empty($data['type']) || empty($data['member_id']) ){
                return ['code'=>100,'msg'=>'缺少type或会员id'];
            }
            if( empty($data['pay_way']) || empty($data['waiter_id']) ){
                return ['code'=>100,'msg'=>'缺少pay_way或waiter_id'];
            }
            if( empty($data['price']) ){
                return ['code'=>100,'msg'=>'请传入金额'];
            }
            if( empty($data['remarks']) ){
                $remarks = '';
            }else{
                $remarks = $data['remarks'];
            }
            $memberInfo = Db::name('member')->where('id',$data['member_id'])->field('level_id,shop_code')->find();
            $member_sort = Db::name('member_level')->where('id',$memberInfo['level_id'])->value('sort');
            if( !$memberInfo ){
                return ['code'=>100,'msg'=>'会员id出错'];
            }
            $shop_id = Db::name('shop')->where('code',$memberInfo['shop_code'])->value('id');

            $level_standard = Db::name('level_price')
                ->alias('a')
                ->where(['a.shop_id'=>$shop_id])
                ->join('member_level b','a.level_id=b.id')
                ->order('b.sort desc')
                ->field('a.level_id,a.price,b.sort')
                ->select();
//            $amount = Db::name('member_recharge_log') ->where('member_id',$data['member_id'])->sum('price');	//累积充值
            $amount = Db::name('member_details')->where(['member_id'=>$data['member_id'],'type'=>1])->sum('amount');//累积充值
            $new_amount = $amount + $data['price'];
            for ($i=0; $i <=count($level_standard); $i++) {
                if( $new_amount >= $level_standard[$i]['price'] ){
                    //达到金额要求
                    if( $level_standard[$i]['sort'] >=$member_sort ){
                        //新等级高于原来的等级
                        $new_level = $level_standard[$i]['level_id'];
                        break;
                    }
                }
            }

            $order_sn = 'CZ'.time().$shop_id;
            $waiter = Db::name('shop_worker')->where('id',$data['waiter_id'])->find();
            // 生成订单表信息
            $order = array(
                'user_id'	=>1,	//制单人
                'is_admin'	=>0,
                'shop_id'	=>$shop_id,
                'member_id'	=>$data['member_id'],
                'sn'		=>$order_sn,
                'type'		=>3,
                'amount'	=>$data['price'],
                'number'	=>1,
                'pay_status'=>1,
                'pay_way'	=>$data['pay_way'],
                'paytime'	=>time(),
                'overtime'	=>time(),
                'dealwithtime'=>time(),
                'order_status'=>2,		//已完成
                'add_time'	=>time(),
                'is_online'	=>0,
                'is_admin'	=>1,
                'order_type'=>1,
                'old_amount'=>$data['price'],
                'waiter'	=>$waiter['name'],		//操作人员名字
                'waiter_id'	=>$waiter['id'],		//操作人员id
            );

            //生成会员表明细数据、member_recharge_log
            $rechargeLog = array(
                'member_id'		=>$data['member_id'],
                'shop_id'		=>$shop_id,
                'price'			=>$data['price'],
                'pay_way'		=>$data['pay_way'],
                'remarks'		=>$remarks,
                'create_time'	=>time(),
                'type'          =>$data['type'],
                'title'         =>$data['title']
            );

            //生成股东数据统计表数据ddxm_statistics_log
            $statisticsLog = array(
                'shop_id'		=>$shop_id,
                'order_sn'		=>$order_sn,
                'type'			=>1,
                'data_type'	=>$data['price']<0?2:1,
                'pay_way'		=>$data['pay_way'],
                'price'			=>$data['price'],
                'title'         =>$data['title'],
                'create_time'	=>time()
            );
            $member_mobile = Db::name('member')->where('id',$data['member_id'])->value('mobile');
            // 启动事务
            Db::startTrans();
            try {
                //表示需要添加充值订单
                $orderId = Db::name('order') ->insertGetId($order);	//添加订单表订单
                $rechargeLog['order_id'] = $orderId;
                Db::name('member_recharge_log') ->insert($rechargeLog);	//添加累积充值记录

                if( $data['price']>0 ){
                    if( Db::name('member_money') ->where('member_id',$data['member_id'])->find() ){
                        Db::name('member_money') ->where('member_id',$data['member_id'])->setInc('money',$data['price']);	//增加余额
                    }
                }

                Db::name('member') ->where('id',$data['member_id'])->update(['level_id'=>$new_level]);			//新的会员等级
                // $MemberDetails ->where('member_id',$data['member_id'])->setInc('amount',$data['price']);	//添加累积充值金额
                // 生成累积充值记录
                $MemberDetailsData = array(
                    'member_id'		=>$data['member_id'],
                    'mobile'		=>$member_mobile,
                    'remarks'		=>$remarks,
                    'reason'		=>'充值'.$data['price'].'元',
                    'addtime'		=>time(),
                    'amount'		=>$data['price'],
                    'type'			=>1,
                    'order_id'		=>$orderId
                );
                Db::name('member_details') ->insert($MemberDetailsData);
                $statisticsLog['order_id'] = $orderId;
                Db::name('statistics_log')	->insert($statisticsLog);
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                return ['code'=>100,'msg'=>$e->getMessage()];
            }
            return ['code'=>200,'msg'=>'添加成功'];
        }
}
?>