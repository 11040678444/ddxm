<?php
// +----------------------------------------------------------------------
// | 公共类
// +----------------------------------------------------------------------
use think\Validate;

/**
 * 二维数组去重
 * @param $arr  需要去重的数组
 * @param $key  判断什么相同的键名下相同的键值去重
 * @return mixed
 */
function assoc_unique_array($arr, $key) {
    $tmp_arr = array();
    foreach ($arr as $k => $v) {
        if (in_array($v[$key], $tmp_arr)) {//搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
            unset($arr[$k]);
        } else {
            $tmp_arr[] = $v[$key];
        }
    }
    return $arr;
}

/**
 * 解析json串
 * @param $json_str
 * @return type
 */
function analyJson($json_str)
{
    $json_str = str_replace('＼＼', '', $json_str);
    $out_arr =
        array();
    preg_match('/{.*}/', $json_str, $out_arr);
    if (!empty($out_arr))
    {
        $result = json_decode($out_arr[0], TRUE);
    } else {
        return
            FALSE;
    }
    return $result;
}

/**
 * 根据时间戳计算年龄
 * @param $birth
 * @return mixed
 */
function howOld($birth)
{
    list($birthYear, $birthMonth, $birthDay) = explode('-', date('Y-m-d', $birth));
    list($currentYear, $currentMonth, $currentDay) = explode('-', date('Y-m-d'));
    $age = $currentYear - $birthYear - 1;
    if ($currentMonth > $birthMonth || $currentMonth == $birthMonth && $currentDay >= $birthDay)
        $age++;

    return $age;
}

/**
 * 数组 转 对象
 *
 * @param array $arr 数组
 * @return object
 */
function array_to_object($arr)
{
    if (gettype($arr) != 'array') {
        return;
    }
    foreach ($arr as $k => $v) {
        if (gettype($v) == 'array' || getType($v) == 'object') {
            $arr[$k] = (object)array_to_object($v);
        }
    }

    return (object)$arr;
}

/**
 * 对象 转 数组
 *
 * @param object $obj 对象
 * @return array
 */
function object_to_array($obj)
{
    $obj = (array)$obj;
    foreach ($obj as $k => $v) {
        if (gettype($v) == 'resource') {
            return;
        }
        if (gettype($v) == 'object' || gettype($v) == 'array') {
            $obj[$k] = (array)object_to_array($v);
        }
    }

    return $obj;
}

/**
 * 生成随机字符串
 * @param string $prefix
 * @return string
 */
function get_random($prefix = '')
{
    return $prefix . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid();
}

/**
 * 多维数组去重
 * @param array
 * @return array
 */
function super_unique($array)
{
    $result = array_map("unserialize", array_unique(array_map("serialize", $array)));

    foreach ($result as $key => $value) {
        if (is_array($value)) {
            $result[$key] = super_unique($value);
        }
    }

    return $result;
}

/**
 * 从二维数组中取出自己要的KEY值
 * @param  array $arrData
 * @param string $key
 * @param $im true 返回逗号分隔
 * @return array
 */
function filter_value($arrData, $key, $im = false)
{
    $re = [];
    foreach ($arrData as $k => $v) {
        if (isset($v[$key])) $re[] = $v[$key];
    }
    if (!empty($re)) {
        $re = array_flip(array_flip($re));
        sort($re);
    }

    return $im ? implode(',', $re) : $re;
}

//返回json数据
/**
 * @param int $status
 * @param array $data
 * @param string $msg
 * @return string
 */
//function returnJson($status = 200, $data = array(), $msg = '')
//{
//    $info = array();
//    $info['code'] = $status;
//    $info['data'] = $data;
//    $info['msg'] = $msg;
//    $json_data = json_encode($info);
//    echo $json_data;
//    exit();
//}
//
//function return_error($msg = '', $data = [])
//{
//    returnJson(300, $data, $msg);
//}
//
//function return_succ($data = [], $msg = '')
//{
//    returnJson(200, $data, $msg);
//}

//判断数组为几维数组
function getmaxdim($vDim)
{
    if (!is_array($vDim)) return 0;
    else {
        $max1 = 0;
        foreach ($vDim as $item1) {
            $t1 = getmaxdim($item1);
            if ($t1 > $max1) $max1 = $t1;
        }
        return $max1 + 1;
    }
}

/**
 * 数据验证
 * @param $validate 验证字段与规则（数组）
 * @param $data 验证数据
 */
//function dataValidate($data,$validate)
//{
//    $validate=new Validate($validate);
//
//    if (!$validate->check($data)){
//        return return_error($validate->getError());
//    }
//}