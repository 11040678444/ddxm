<?php


namespace app\stock\controller;


use app\common\controller\Backendbase;
use app\stock\model\supplier\SupplierModel;

/**
 * 供应商
 * Class Supplier
 * @package app\stock\controller
 */
class Supplier extends Backendbase
{

    //获取所有的供应商
    public function getAllList(){

        $supplier = new SupplierModel();
        $list = $supplier->getAllList();
        return json(["code" => 200, "data" => $list]);
    }

}