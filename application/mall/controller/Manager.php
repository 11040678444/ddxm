<?php

namespace app\admin\controller;

use app\admin\model\AdminUser;
use app\admin\model\AuthGroup as AuthGroup_Model;
use app\common\controller\Adminbase;
use think\Db;

/**
 * 管理员管理
 */
class Manager extends Adminbase
{
    protected function initialize()
    {
        parent::initialize();
        $this->AdminUser = new AdminUser;
    }

    /**
     * 管理员管理列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            $this->AuthGroup_Model = new AuthGroup_Model();
            $limit = $this->request->param('limit/d', 10);
            $page = $this->request->param('page/d', 10);
            $_list = Db::name("admin")
                ->page($page,$limit)
                ->order(array('userid' => 'ASC'))

                ->withAttr('last_login_time', function ($value, $data) {
                    return date('Y-m-d H:i:s', $value);
                })
                ->withAttr('last_login_ip', function ($value, $data) {
                    return long2ip($value);
                })
                ->withAttr('roleid', function ($value, $data) {
                    return $this->AuthGroup_Model->getRoleIdName($value);
                })
                ->select();
            $total = Db::name("admin")->count();
            $result = array("code" => 0, "count" => $total, "data" => $_list);
            return json($result);
        }
        return $this->fetch();
    }

    /**
     * 添加管理员
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.insert');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->createManager($data)) {
                $this->success("添加管理员成功！", url('admin/manager/index'));
            } else {
                $error = $this->AdminUser->getError();
                $this->error($error ? $error : '添加失败！');
            }

        } else {
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员编辑
     */
    public function edit()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post('');
            $result = $this->validate($data, 'AdminUser.update');
            if (true !== $result) {
                return $this->error($result);
            }
            if ($this->AdminUser->editManager($data)) {
                $this->success("修改成功！");
            } else {
                $this->error($this->User->getError() ?: '修改失败！');
            }
        } else {
            $id = $this->request->param('id/d');
            $data = $this->AdminUser->where(array("userid" => $id))->find();
            if (empty($data)) {
                $this->error('该信息不存在！');
            }
            $this->assign("data", $data);
            $this->assign("roles", model('admin/AuthGroup')->getGroups());
            return $this->fetch();
        }
    }

    /**
     * 管理员删除
     */
    public function del()
    {
        $id = $this->request->param('id/d');
        if ($this->AdminUser->deleteManager($id)) {
            $this->success("删除成功！");
        } else {
            $this->error($this->AdminUser->getError() ?: '删除失败！');
        }
    }

}
