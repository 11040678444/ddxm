<?php
namespace app\index\controller;

use think\Db;
use think\Controller;

class Common extends Controller
{

    /***
     * 模拟post请求
     * @param $url
     * @param $post_data
     * @return bool|string
     */
    function request_post($url, $post_data) {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        return $data;
    }

    /***
     * @param $length
     * @param string $type
     * @return string|null
     */
    function getRandChar($length,$type = 'numeric')
    {
        $str = null;
        switch ($type){
            case 'numeric':
                $strPol = "0123456789";
                break;
            default:
                $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        }
        $max = strlen($strPol) - 1;

        for ($i = 0;
             $i < $length;
             $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    /***
     * 服务卡延期
     */
    public function ticketDelay()
    {
        $data = $this->request->post();
        if ( empty($data['id']) || empty($data['expire_time']) )
        {
            return_error('请传入ID或者过期时间');
        }
        $ticketInfo = Db::name('ticket_user_pay') ->where('id',intval($data['id']))->find();
        if ( !$ticketInfo )
        {
            return_error('服务卡不存在');
        }
        if( ($ticketInfo['status'] == 0) || ($ticketInfo['status'] == 4) )
        {
            return_error('未激活或已过期的服务卡禁止延期');
        }
        Db::startTrans();
        try{
            $res = Db::name('ticket_user_pay')->where('id',intval($data['id']))->setField('end_time',strtotime($data['expire_time'].' 23:59:59'));
            if ( $res )
            {
                $arr = [
                    'member_id' =>$ticketInfo['member_id'],
                    'tup_id'    =>$ticketInfo['id'],
                    'old_over_time' =>$ticketInfo['end_time'],
                    'new_over_time' =>strtotime($data['expire_time'].' 23:59:59'),
                    'create_time'   =>time()
                ];
                $res = Db::name('ticket_delay') ->insert($arr);
            }
            Db::commit();
        }catch (\Exception $e){
            Db::rollback();
            return_error($e->getMessage());
        }
        return_succ('延期成功');
    }
}
