<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/10/30 0030
 * Time: 上午 11:02
 */

namespace libs;


class ExportTool
{
    public $_useGbk = true;
    private $fp = null;

    public function __construct(){
        //打开PHP文件句柄,php://output 表示直接输出到php缓存
        $this->fp = fopen('php://output', 'w');
    }

    public function ExportData($filename,$head,$data)
    {
        set_time_limit(0);
        $csv = new ExportTool();
        $filename = "{$filename}_" . date('YmdHis') . '.csv';
        // 输出Excel文件头名
        $csv->cvsHeader($filename);

        //封装CSV格式
        $head = $head;//[ '用户名称','用户ID', '电话号码', '邮箱','注册来源'];
        $this->outputData($head);

        $limit = 10000;     //刷新缓存限制，避免php缓存过大
        $cnt   = 0;         //用于计算缓存数量
        $size  = 2000;
        while(1){
            //$query = User::query()->orderBy('id', 'desc');
            //$items = $query->take($size)->get();
            if(empty($data) || count($data) < 1){
                break;
            }

            foreach ($data as $key => $val)
            {
                //这里循环，不要再有查库操作或者其他请求操作，这样会占用大量PHP内存
                $da = array_values($val);
                $cnt++;
                if ($limit == $cnt) {
                    //刷新一下输出buffer，防止由于数据过多造成问题
                    $csv->csvFlush($cnt);
                }
                $this->outputData($da);
		    }

            //释放内存
            unset($data);
        }
        $csv->closeFile();  //每生成一个文件关闭
        exit;
    }

    //设置头部
    public function cvsHeader($filename)
    {
        //error_reporting(0);
        if($this->_useGbk) {
            header("Content-type:text/csv;charset=gbk");//application/vnd.ms-excel
        } else {
            header("Content-type:text/csv;charset=utf-8");
        }
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0,max-age=0');
        header('Expires:0');
        header('Pragma:public');
    }

    //采用putcsv封装格式
    public function outputData($data){
        foreach ($data as $key => $value) {
            //CSV的Excel支持GBK编码，一定要转换，否则乱码
            $data[$key] = mb_convert_encoding($value, 'GBK', 'UTF-8');
        }
        fputcsv($this->fp,$data);
    }

    //刷新缓存，将PHP的输出缓存输出到浏览器上
    public function csvFlush(&$cnt){
        ob_flush();
        flush();
        $cnt = 0;
    }

    //关闭输出流
    public function closeFile(){
        fclose($this->fp);
    }
}