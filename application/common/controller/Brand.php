<?php
// +----------------------------------------------------------------------
// | 品牌控制器
// +----------------------------------------------------------------------
namespace app\common\controller;
use think\Db;
use think\Controller;
use app\common\model\brand\BrandModel;

class Brand extends Controller
{
    public function get_list(){
        $data = $this ->request ->param();
        $list = (new BrandModel()) ->getList($data);
        return $list;
    }
}