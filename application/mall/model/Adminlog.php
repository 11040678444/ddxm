<?php

// +----------------------------------------------------------------------
// | 后台用户管理
// +----------------------------------------------------------------------
namespace app\admin\model;

use app\admin\service\User;
use think\Model;

class Adminlog extends Model
{
    protected $autoWriteTimestamp = true;
    /**
     * 记录日志
     * @param type $message 说明
     */
    public function record($message, $status = 0,$remarks="")
    {
        $data = array(
            'uid' => (int) User::instance()->isLogin(),
            'status' => $status,
            'info' => "提示语:{$message}",
            'get' => request()->url(),
            'ip' => request()->ip(1),
            'remarks'=>$remarks,
        );
        return $this->save($data) !== false ? true : false;
    }
    public function record_insert($message, $status = 0,$remarks="")
    {
        $data = array(
            'uid' => (int) User::instance()->isLogin(),
            'status' => $status,
            'info' => "提示语:{$message}",
            'get' => request()->url(),
            'ip' => request()->ip(1),
            "create_time"=>time(),
            'remarks'=>$remarks,
        );
        return $this->insert($data) !== false ? true : false;
    }
    
    /**
     * 删除一个月前的日志
     * @return boolean
     */
    public function deleteAMonthago()
    {
        $status = $this->where('create_time', '<= time', time() - (86400 * 30))->delete();
        return $status !== false ? true : false;
    }

}
