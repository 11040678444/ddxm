<?php

// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------
error_reporting(E_ALL ^ E_NOTICE);
return [
    // 应用名称
    'app_name' => '',
    // 应用地址
    'app_host' => '',
    // 应用调试模式
    'app_debug' => true,
    // 应用Trace
    'app_trace' => false,
    // 应用模式状态
    'app_status' => '',
    // 是否支持多模块
    'app_multi_module' => true,
    // 入口自动绑定模块
    'auto_bind_module' => false,
    // 注册的根命名空间
    'root_namespace' => ['addons' => ROOT_PATH . 'addons/'],
    // 默认输出类型
    'default_return_type' => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return' => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler' => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler' => 'callback',
    // 默认时区
    'default_timezone' => 'PRC',
    // 是否开启多语言
    'lang_switch_on' => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter' => '',
    // 默认语言
    'default_lang' => 'zh-cn',
    // 应用类库后缀
    'class_suffix' => false,
    // 控制器类后缀
    'controller_suffix' => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module' => 'admin',
    // 禁止访问模块
    // 'deny_module_list' => ['common'],
    'deny_module_list' => [],
    // 默认控制器名
    'default_controller' => 'Index',//Customer
    // 默认操作名
    'default_action' => 'index',
    // 默认验证器
    'default_validate' => '',
    // 默认的空模块名
    'empty_module' => 'error',
    // 默认的空控制器名
    'empty_controller' => 'Error',
    // 操作方法前缀
    'use_action_prefix' => false,
    // 操作方法后缀
    'action_suffix' => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo' => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch' => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr' => '/',
    // HTTPS代理标识
    'https_agent_name' => '',
    // URL伪静态后缀
    'url_html_suffix' => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param' => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type' => 0,
    // 是否开启路由延迟解析
    'url_lazy_route' => false,
    // 是否强制使用路由
    'url_route_must' => false,
    // 路由是否完全匹配
    'route_complete_match' => false,
    // 使用注解路由
    'route_annotation' => false,
    // 域名根，如thinkphp.cn
    'url_domain_root' => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert' => true,
    // 默认的访问控制器层
    'url_controller_layer' => 'controller',
    // 表单请求类型伪装变量
    'var_method' => '_method',
    // 表单ajax伪装变量
    'var_ajax' => '_ajax',
    // 表单pjax伪装变量
    'var_pjax' => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache' => false,
    // 请求缓存有效期
    'request_cache_expire' => null,
    // 全局请求缓存排除规则
    'request_cache_except' => [],

    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl' => APP_PATH . 'common' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',
    'dispatch_error_tmpl' => APP_PATH . 'common' . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'dispatch_jump.tpl',

    // 异常页面的模板文件
    'exception_tmpl' => Env::get('think_path') . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle' => '',

    //公开路径
    'public_url' => ROOT_URL . (defined('IF_PUBLIC') ? '' : 'public/'),
    // 文件上传文件路径
    'upload_path' => ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'uploads',
    // 资源文件路径
    'static_path' => ROOT_PATH . 'public' . DIRECTORY_SEPARATOR . 'static',
    'ddxx'=>[
    // // 数据库类型
     'type'     => 'mysql',
    // // 服务器地址
     'hostname' => '120.79.5.57',//120.79.5.57
    // // 数据库名
     'database' => 'ddxx',
    // // 用户名
     'username' => 'root', //
    // // 密码
     'password' => 'ddxm2019',//ddxm2019
    // // 端口
     'hostport' => '3306',
    // // 数据库编码默认采用utf8
     'charset'  => 'utf8mb4',
    // // 数据库表前缀
     'prefix'   => 'tf_',
     "authcode" => 'OV4w80Ndr23wt4yW1j',
    // //#COOKIE_PREFIX#
     ],
     'ddxm_erp'=>[
        // // 数据库类型
         'type'     => 'mysql',
        // // 服务器地址
//         'hostname' => '120.79.5.57',
         'hostname' => '127.0.0.1',
        // // 数据库名
         'database' => 'ddxm_erp',
        // // 用户名
//         'username' => 'ddxm_erp', //
         'username' => 'root', //
        // // 密码
//         'password' => 'WZS5Mi4SHt8WxrPd',
         'password' => 'root',
        // // 端口
//         'hostport' => '3339',
         'hostport' => '3306',
        // // 数据库编码默认采用utf8
         'charset'  => 'utf8mb4',
        // // 数据库表前缀
         'prefix'   => 'ddxm_',
         "authcode" => 'OV4w80Ndr23wt4yW1j',
     ],

     //普通商品的七牛云图片地址
     'QINIU_URL'    =>"http://picture.ddxm661.com/",

     //七牛云配置
    'qiniu'=>[
        'accesskey' => 'ChbjC0NsNlFawXdmV9GXZtaoU5rfq5ZS9d919Z1n',
        'secretkey' => 'Fnd1ud7q77V7qlLlW0uqFna24RD2B-AI_2Jrd0IH',
        'bucket' => 'ddxm-Item',
        'domain'=>'picture.ddxm661.com'
    ],

    //微信小程序配置
    'appid'=>'wx867b49bff338c6c4',
    'AppSecret'=>'bacf8e48094026198543ecb4275daec7',

    //捣蛋熊猫公众号微信配置
//    'WeChat'   =>[
//        'appid' =>'wxb5ee49b69efc2429',
//        'AppSecret'=>'f775096b8d02d3a34761ccef9796c8f4',
//        'MerchantId'    =>'1486226662',
//        'key'      =>'daodanxiongmaokkgg4945sdfjklghgh'
//    ],

    //捣蛋熊猫经销商后台微信配置
    'WeChat'   =>[
        'appid' =>'wx6e3b81d3c901cbf2',
        'AppSecret'=>'faff929ed271fe64f63e95f2128065b5',
        'MerchantId'    =>'1486226662',
        'key'      =>'daodanxiongmaokkgg4945sdfjklghgh'
    ],

    //身份证验证配置
    'OCR'   =>[
        'appkey'    =>'93nnkacawg0b0nldtkwulwfqzbk73eup',
        'appcode'   =>'4ed1ea8856cf4d15aff1c1a6f3e55afd'
    ],

    //支付回调地址（线上与线下区分）
    'notify_url'=>empty(APP_DEBUG) ? 'https://ddxm661.com/wxshop/Wxnotify/' : 'https://www.ddxm661.com/wxshop/Wxnotify/',

    //2月-6余额预存送活动
    'Recharge' => [
        '200'=>2000,
        '400'=>4000,
        '800'=>8000,
        '1200'=>12000,
        'rate'  =>0.1       //购买商品时优惠的折扣
    ],

    //erp系统地址
    'erp_url'   =>'http://testadmin2.ddxm661.com/',
];
