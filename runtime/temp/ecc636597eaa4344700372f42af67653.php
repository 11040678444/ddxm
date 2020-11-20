<?php /*a:2:{s:69:"D:\PHPTutorial\WWW\ddxm_svn\application\mall\view\items\item_add.html";i:1601275823;s:67:"D:\PHPTutorial\WWW\ddxm_svn\application\mall\view\index_layout.html";i:1601275824;}*/ ?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>后台管理系统</title>
    <meta name="author" content="YZNCMS">
    <link rel="stylesheet" href="/public/static/libs/layui/css/layui.css">
    <link rel="stylesheet" href="/public/static/admin/css/admin.css">
    <link rel="stylesheet" href="/public/static/admin/css/ddxm.css">
    <link rel="stylesheet" href="/public/static/admin/font/iconfont.css">
    <script src="/public/static/libs/layui/layui.js"></script>
    <script src="/public/static/libs/jquery/jquery.min.js"></script>
   
<script type="text/javascript">
//全局变量
var GV = {
    'image_upload_url': '<?php echo !empty($image_upload_url) ? htmlentities($image_upload_url) :  url("attachment/attachments/upload", ["dir" => "images", "module" => request()->module()]); ?>',
    'file_upload_url': '<?php echo !empty($file_upload_url) ? htmlentities($file_upload_url) :  url("attachment/attachments/upload", ["dir" => "files", "module" => request()->module()]); ?>',
    'WebUploader_swf': '/public/static/webuploader/Uploader.swf',
    'upload_check_url': '<?php echo !empty($upload_check_url) ? htmlentities($upload_check_url) :  url("attachment/Attachments/check"); ?>',
    'ueditor_upload_url': '<?php echo !empty($ueditor_upload_url) ? htmlentities($ueditor_upload_url) :  url("attachment/Ueditor/run"); ?>',
};
</script>
</head>
<body class="childrenBody">
    
<style type="text/css">
    .box{
        margin-top: 10%;
        margin-bottom: 10px;
        color: #FF5722;
        font-size: 18px;
        margin-left: 45%;
    }
    .box1{
        width: 900px;
        height: 500px;
        margin-left: auto;
        border:solid  1px;
        margin-right:auto;
    }
    .box1 .controls{

    }
    .upload-icon-img{
        width:120px;
    }
    .upload-pre-item{
        position: relative;
    }
    .upload-pre-item .img{
        margin-top: 5px;
        width: 116px;
        height: 76px;
    }
    .upload-pre-item i {
        position: absolute;
        cursor: pointer;
        top: 5px;
        background: #2F4056;
        padding: 2px;
        line-height: 15px;
        text-align: center;
        color: #fff;
        margin-left: 1px;
        /* float: left; */
        filter: alpha(opacity=80);
        -moz-opacity: .8;
        -khtml-opacity: .8;
        opacity: .8;
        transition: 1s;
    }
    .upload-pre-item i:hover{transform:rotate(360deg);}
    .upload-pre-item,.upload-icon-img{
        width:120px;
        float: left;
        margin-left: 8px;
    }
</style>
<div class="layui-card">
    <div class="layui-card-header">编辑商品</div>
    <div class="layui-card-body">
        <form class="layui-form form-horizontal">
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>分类</label>
                <div style="display: flex;flex-direction: column;" class="custom2" >
                    <div  style="margin-bottom: 20px" >
                        <div class="layui-input-inline">
                            <select name="type_id[0]" id="type_id" lay-filter="type_id">
                                <option>请选择</option>
                                <?php if(is_array($category) || $category instanceof \think\Collection || $category instanceof \think\Paginator): $i = 0; $__LIST__ = $category;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                                <option value="<?php echo htmlentities($vo['id']); ?>" <?php if($vo['id'] == $list['type_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($vo['cname']); ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </div>
                        <div class="layui-input-inline">
                            <select name="type[0]" lay-filter="type" id="type">
                                <option value="0">请先选择一级分类</option>
                                <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$tt): $mod = ($i % 2 );++$i;?>
                                <option <?php if($tt['id'] == $list['type']): ?> selected="selected" <?php endif; ?> value="<?php echo htmlentities($tt['id']); ?>"><?php echo htmlentities($tt['cname']); ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </div>

                        <div class="layui-input-inline">
                            <select name="type_three[0]" lay-filter="type_three" id="type_three">
                                <option value="0">请先选择一级分类</option>
                                <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$tt): $mod = ($i % 2 );++$i;?>
                                <option <?php if($tt['id'] == $list['type']): ?> selected="selected" <?php endif; ?> value="<?php echo htmlentities($tt['id']); ?>"><?php echo htmlentities($tt['cname']); ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </div>

                        <div class="layui-input-inline">
                            <button type="" class="layui-btn layui-btn-normal add_custom2" data="0"><i class="layui-icon">&#xe654;</i></button>
                        </div>
                    </div>
                </div>

            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>商品标题 </label>
                <div class="layui-input-inline w300">
                    <input type="text" name="title"  autocomplete="title" required lay-verify="required" placeholder="商品名称" value="<?php echo htmlentities($list['title']); ?>" class="layui-input" >
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">商品副标题 </label>
                <div class="layui-input-inline w300">
                    <input type="text" name="titles"  autocomplete="titles"  placeholder="商品副标题" value="<?php echo htmlentities($list['titles']); ?>" class="layui-input" >
                </div>
            </div>

                <!--分类-->
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>商品库</label>
                <div class="layui-input-inline">
                    <select name="g_type" id="g_type" lay-filter="g_type">
                        <option value="1" selected="selected">线上商城</option>
                        <option value="2">门店商品</option>
                    </select>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>分区</label>
                <div class="layui-input-inline">
                    <select name="mold_id" id="mold_id" lay-filter="mold_id">
                        <option>请选择</option>
                        <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$type): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($type['id']); ?>" <?php if($type['id'] == $list['type_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($type['title']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>
<!--            <div class="layui-form-item">-->
<!--                <label class="layui-form-label">选择单位</label>-->
<!--                <div class="layui-input-inline">-->
<!--                    <select name="unit_id" lay-filter="unit_id">-->
<!--                        <?php if(is_array($unit) || $unit instanceof \think\Collection || $unit instanceof \think\Paginator): $i = 0; $__LIST__ = $unit;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$unit): $mod = ($i % 2 );++$i;?>-->
<!--                        <option value="<?php echo htmlentities($unit['id']); ?>" <?php if($unit['id'] == $list['unit_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($unit['title']); ?></option>-->
<!--                        <?php endforeach; endif; else: echo "" ;endif; ?>-->
<!--                    </select>-->
<!--                </div>-->
<!--            </div>-->
            <div class="layui-form-item">
                <label class="layui-form-label">运费模板</label>
                <div class="layui-input-inline">
                    <select name="lvid" lay-filter="lvid">
                        <?php if(is_array($lvid) || $lvid instanceof \think\Collection || $lvid instanceof \think\Paginator): $i = 0; $__LIST__ = $lvid;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$lvid): $mod = ($i % 2 );++$i;?>
                            <option value="<?php echo htmlentities($lvid['id']); ?>" <?php if($lvid['id'] == $list['unit_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($lvid['title']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">选择品牌</label>
                <div class="layui-input-inline">
                    <select name="brand_id" lay-filter="brand_id" lay-search="">
                        <option value="0"></option>
                        <?php if(is_array($brand) || $brand instanceof \think\Collection || $brand instanceof \think\Paginator): $i = 0; $__LIST__ = $brand;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$brand): $mod = ($i % 2 );++$i;?>
                        <option value="<?php echo htmlentities($brand['id']); ?>" <?php if($brand['id'] == $list['brand_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($brand['title']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>
            <?php if(isset($supplier)): ?>
                <div class="layui-form-item">
                    <label class="layui-form-label">选择供应商</label>
                    <div class="layui-input-inline">
                        <select name="sender_id" lay-filter="sender_id" lay-search="">
                            <option value="">请选择</option>
                            <?php if(is_array($supplier) || $supplier instanceof \think\Collection || $supplier instanceof \think\Paginator): $i = 0; $__LIST__ = $supplier;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$supplier): $mod = ($i % 2 );++$i;?>
                            <option value="<?php echo htmlentities($supplier['id']); ?>" <?php if($supplier['id'] == $list['sender_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($supplier['title']); ?></option>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

<!--            <div class="layui-form-item">-->
<!--                <label class="layui-form-label">选择服务</label>-->
<!--                <div class="layui-input-block">-->
<!--                    <input type="checkbox" value="1" lay-skin="primary" title="所有服务" lay-filter="c_all" >-->
<!--                </div>-->
<!--                <div class="layui-input-block">-->
<!--                    <?php if(is_array($itemService) || $itemService instanceof \think\Collection || $itemService instanceof \think\Paginator): $i = 0; $__LIST__ = $itemService;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$itemService): $mod = ($i % 2 );++$i;?>-->
<!--                    <input type="checkbox"  name="service_ids[]" lay-skin="primary" title="<?php echo htmlentities($itemService['title']); ?>" class="shop_id tt" value="<?php echo htmlentities($itemService['id']); ?>" data="<?php echo htmlentities($itemService['id']); ?>">-->
<!--                    <?php endforeach; endif; else: echo "" ;endif; ?>-->
<!--                </div>-->
<!--            </div>-->
            <div class="layui-form-item">
                <label class="layui-form-label">限购</label>
                <div class="layui-input-inline">
                    <input type="text" name="quota" lay-verify="sort" onkeyup="this.value=this.value.replace(/\D/g,'')" onblur="this.value=this.value.replace(/\D/g,'')" autocomplete="sort" placeholder="请输入限购件数" class="layui-input " value="0">
                </div>
                <div class="layui-form-mid layui-word-aux">不输或输入0表示为无限制</div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">序号</label>
                <div class="layui-input-inline">
                    <input type="text" name="sort" lay-verify="sort" onkeyup="this.value=this.value.replace(/\D/g,'')" onblur="this.value=this.value.replace(/\D/g,'')" autocomplete="sort" placeholder="请输入序号" class="layui-input " value="0">
                </div>
            </div>
<!--            规格-->
            <div class="layui-form-item">
                <label class="layui-form-label">商品规格</label>
                <div class="layui-input-block">
                    <input type="radio" name="specs_type" lay-filter="typeradio" value="0" title="统一规格" checked>
                    <input type="radio" name="specs_type" value="1" lay-filter="typeradio" title="多规格" >
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">分销设置</label>
                <div class="layui-input-block">
                    <input type="radio" name="ratio_type" lay-filter="ratio_type" value="1" title="不参与分销" checked>
                    <input type="radio" name="ratio_type" lay-filter="ratio_type" value="2" title="按品牌/分类">
                    <input type="radio" name="ratio_type" value="3" lay-filter="ratio_type" title="商品本身">
                </div>
            </div>
            <div id="ratioCont"></div>
            <!--            // 统一规格 布局-->
            <div id="unified" >
                <div class="layui-form-item">
                    <label class="layui-form-label">库存</label>
                    <div class="layui-input-inline">
                        <input type="text" name="store" min="0" required lay-verify="required|cost" placeholder="请输入库存" maxlength="8" value="-1" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">件&nbsp;&nbsp;<span style="color: red">注意：-1表示库存无限制</span></div>
                </div>

                <div class="layui-form-item">
                    <label class="layui-form-label">成本</label>
                    <div class="layui-input-inline">
                        <input type="text" name="cost" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入成本" value="0.00" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">元</div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">条形码</label>
                    <div class="layui-input-inline">
                        <input type="text" name="bar_code"  placeholder="请输入条形码" value="" autocomplete="off" class="layui-input">
                    </div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">商品原价</label>
                    <div class="layui-input-inline">
                        <input type="text" name="recommendprice" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入商品原价" value="0.00" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">元</div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">会员价</label>
                    <div class="layui-input-inline">
                        <input type="text" name="price" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入会员价" value="0.00" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">元</div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">容量ml</label>
                    <div class="layui-input-inline">
                        <input type="text" name="volume" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入容量"  value="0.00" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">ml</div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">重量</label>
                    <div class="layui-input-inline">
                        <input type="text" name="weight" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入重量"  value="0.00" autocomplete="off" class="layui-input">
                    </div>
                    <div class="layui-form-mid layui-word-aux">kg</div>
                </div>
                <div class="layui-form-item">
                    <label class="layui-form-label">初始销量</label>
                    <div class="layui-input-inline">
                        <input type="text" name="initial_sales" required lay-verify="required|cost" maxlength="9" placeholder="请输入initial_sales"  value="0" autocomplete="off" class="layui-input">
                    </div>
                </div>
<!--                <div class="layui-form-item">-->
<!--                    <label class="layui-form-label">分销佣金</label>-->
<!--                    <div class="layui-input-inline">-->
<!--                        <input type="text" name="commission" required lay-verify="validateMoney" maxlength="9" placeholder="请输入佣金"  value="0.00" autocomplete="off" class="layui-input">-->
<!--                    </div>-->
<!--                </div>-->
            </div>
            <!--            // 多规格 布局&ndash;&gt;-->
            <div id="more"  style="display:none">
                <table class="layui-table">
                    <colgroup>
                        <col>
                        <col>
                    </colgroup>
                    <thead>
                    <tr>
                        <th>规格名称</th>
                        <th rowspan="2">操作</th>
                    </tr>
                    </thead>
                    <tbody id="tb_specs_all">
                    <tr>
                        <td colspan="2" style="text-align: center;color: red">请先添加规格</td>
                    </tr>
                    </tbody>


                </table>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <button type="button" class="layui-btn" lay-even="true" onclick="addsecs()" >增加规格</button>

                <div class="layui-form-item" style="margin-top: 10px">

                    <label class="layui-form-label">条形码</label>
                    <div class="layui-input-inline">
                        <input type="text"  id="all_tiaoxm"
                                maxlength="20"
                               placeholder="请输入条形码"  autocomplete="off" class="layui-input">
                    </div>

                    <label class="layui-form-label">库存</label>
                    <div class="layui-input-inline">
                        <input type="text" id="all_kucun" maxlength="9"
                               placeholder="请输入库存"  autocomplete="off" class="layui-input">
                    </div>

                    <label class="layui-form-label">成本价</label>
                    <div class="layui-input-inline">
                        <input type="text" id="all_chengbenjia" maxlength="9"
                               placeholder="请输入成本价"  autocomplete="off" class="layui-input">
                    </div>

                    <label class="layui-form-label">原价</label>
                    <div class="layui-input-inline">
                        <input type="text" id="all_yuanjia" maxlength="9"
                               placeholder="请输入原价"autocomplete="off" class="layui-input">

                    </div>

                    <label class="layui-form-label">会员价</label>
                    <div class="layui-input-inline">
                        <input type="text" id="all_huiyuanjia" maxlength="9"
                               placeholder="请输入会员价" autocomplete="off" class="layui-input">
                    </div>

                    <div class="layui-input-inline">
                         <button type="button" class="layui-btn" lay-even="true" onclick="setallData()" >批量更改</button>
                    </div>
                </div>

                <!--                规格列表-->
                <div id="createTable"></div>
            </div>
            <!--规格-->

            <div class="layui-form-item"></div>

            <div class="layui-form-item">
                <label class="layui-form-label">活动主图</label>
                <div class="layui-input-block">
                    <button type="button" class="layui-btn" id="test3">
                        <i class="layui-icon">&#xe67c;</i>上传图片
                    </button>
                    <span style="color: #ff2222;font-size: 1em;"> <strong>请上传活动主图</strong> </span>
                    <div class="upload-img-box3">

                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">商品图</label>
                <div class="layui-input-block">
                    <button type="button" class="layui-btn" id="test1">
                        <i class="layui-icon">&#xe67c;</i>上传图片
                    </button>
                    <span style="color: #ff2222;font-size: 1em;"> <strong>最多上传5张且第一张为主图</strong> </span>
                    <div class="upload-img-box">

                    </div>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label">上传视频</label>
                <div class="layui-input-block w300">
                    <button type="button" class="layui-btn" id="test2">
                        <i class="layui-icon">&#xe67c;</i>上传视频
                    </button>
                    <div class="upload-img-box1">

                    </div>
                </div>
            </div>


            <div class="layui-form-item">
                <label class="layui-form-label">商品详情</label>
                <div class="layui-input-block">
                    <div class="layui-card" style="margin-top: 20px">
                        <div style="width: 1020px;">
                            <div style="float: left;">
                                <div id="myPreview" style="width: 500px;height:752px;overflow:hidden;overflow-y: scroll;border: 1px solid #cfcfcf;background-color: white">
                                </div>
                            </div>
                            <div style="width:500px;height:600px;float: right;">
                                <script id="editor" name="content" type="text/plain" type="text/plain" style="width: 500px;height: 600px;"></script>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <input type="hidden" name="id" id="itemID" value="<?php echo htmlentities($list['id']); ?>">
            <div class="layui-form-item">
                <div class="layui-input-block">
                    <button class="layui-btn" lay-submit lay-filter="L_submit">立即提交</button>
                    <button class="layui-btn layui-btn-normal" id = "close">返回</button>
                </div>
            </div>
        </form>
    </div>
</div>

    
<script type="text/javascript" src="/public/static/admin/js/common.js"></script>
<script type="text/javascript" src="/public/static/admin/js/goods_specs.js"></script>
<script type="text/javascript" src="/public/static/admin/js/ticket.js"></script>

<script type="text/javascript" charset="utf-8" src="/public/static/ueditor/ueditor.config.js"></script>
<script type="text/javascript" charset="utf-8" src="/public/static/ueditor/ueditor.all.min.js"> </script>
<script type="text/javascript" charset="utf-8" src="/public/static/ueditor/lang/zh-cn/zh-cn.js"></script>
<script>

    var ue = UE.getEditor('editor');
    ue.addListener("blur",function(){
        document.getElementById("myPreview").innerHTML = UE.getEditor('editor').getContent()
    })

    layui.use(["form","upload"], function(){
        var layer = layui.layer;
        form = layui.form;

        //筛选分类
        form.on('select(type_id)', function(data){
            var val=data.value;
            if( val == '请选择' ){
                return false;
            }
            $.ajax({
                type : "post",
                url : "/admin/Item/category_select",
                data: {pid:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        var option = '';
                        console.log(category.length)
                        if( category.length >= 1 ){
                            var ts = '请选择';
                        }else{
                            var ts = '无';
                        }
                        $('#type').empty();
                        $('#type').append("<option value=''>"+ts+"</option>");
                        for( var k in category){
                            $("#type").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                        }
                        form.render();
                    }
                }
            });
        });

        //筛选分类
        form.on('select(type)', function(data){
            var val=data.value;
            if( val == '请选择' ){
                return false;
            }
            $.ajax({
                type : "post",
                url : "/admin/Item/category_select",
                data: {pid:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        if( category.length >= 1 ){
                            var ts = '请选择';
                        }else{
                            var ts = '无';
                        }
                        var option = '';
                        $('#type_three').empty();
                        $('#type_three').append("<option value=''>"+ts+"</option>");
                        for( var k in category){
                            $("#type_three").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                        }
                        form.render();
                    }
                }
            });
        });


        //分销设置
        //监听radio框选择
        form.on('radio(ratio_type)' , function (data) {
            let val = data.value;
            let html = `<div class="layui-form-item">
                            <label class="layui-form-label"><span style="color:red;">*</span>自省金额 </label>
                            <div class="layui-input-inline w300">
                                <input type="text" name="own_ratio" lay-verify="validateMoney" autocomplete="ratio" placeholder="自省金额" value="0" class="layui-input" >
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label"><span style="color:red;">*</span>一级分销金额 </label>
                            <div class="layui-input-inline w300">
                                <input type="text" name="ratio" lay-verify="validateMoney" autocomplete="ratio" placeholder="请输入分销金额" value="0" class="layui-input" >
                            </div>
                        </div>
                        <div class="layui-form-item">
                            <label class="layui-form-label"><span style="color:red;">*</span>二级分销金额 </label>
                            <div class="layui-input-inline w300">
                                <input type="text" name="two_ratio" lay-verify="validateMoney" autocomplete="ratio" placeholder="请输入分销金额" value="0" class="layui-input" >
                            </div>
                        </div>`;
            if( val == 3 ){
                $('#ratioCont').html(html);
            }else{
                $('#ratioCont').html('');
            }
        })

        //关闭
        $("#close").click(function(){
            var itemID = $('#itemID').val();
            if( itemID == '' ){
                window.location.href="/admin/item/index";
            }else{
                var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                parent.layer.close(index); //再执行关闭
                parent.location.reload();
            }
        })

        //详情视频
        layui.use('upload', function(){
            var upload = layui.upload;
            //执行实例
            var uploadInst = upload.render({
                elem: '#test2', //绑定元素
                url: '/admin/Upload/video', //上传接口
                multiple: true,
                accept:'video',
                size:10*1024,
                before: function(obj) {
                    layer.msg('视频上传中...', {
                        icon: 16,
                        shade: 0.01,
                        time: 0
                    })
                },
                done: function(res){
                    //上传完毕回调
                    if( res.code == 0 ){
                        layer.close(layer.msg('上传成功！'));
                        $('.upload-img-box1').append('<div class="upload-icon-img" >' +
                            '<div class="upload-pre-item"><i   class="layui-icon  del_img"></i>' +
                            '<img src="' + res.data.fengmian + '" class="img">' +
                            '<input type="hidden" name="video" class="img_val" value="' + res.data.key + '" />' +
                            '<input type="hidden" name="video_time" value="' + res.data.video_time + '" />' +
                            '</div></div>');
                    }
                },
                error: function(){
                    //请求异常回调
                }
            });
        });

        //上传活动主图
        layui.use('upload', function(){
            var upload = layui.upload;
            //执行实例
            var uploadInst = upload.render({
                elem: '#test3', //绑定元素
                url: '/admin/Upload/upload', //上传接口
                multiple: true,
                number:1,
                accept:'images',
                before: function(obj) {
                    layer.msg('图片上传中...', {
                        icon: 16,
                        shade: 0.01,
                        time: 3000
                    })
                },
                done: function(res){
                    //上传完毕回调
                    if( res.code == 0 ){
                        $('.upload-img-box3').append('<div class="upload-icon-img" ><div class="upload-pre-item"><i   class="layui-icon  del_img"></i><img src="http://picture.ddxm661.com/' + res.data.key + '" class="img"><input type="hidden" name="pic" class="img_val" value="' + res.data.key + '" /></div></div>');
                    }
                    // layer.close(layer.msg('上传成功！'));
                },
                error: function(){
                    //请求异常回调
                    layer.close(layer.msg('上传失败！'));
                }
            });
        });

        //上传多图片
        layui.use('upload', function(){
            var upload = layui.upload;
            //执行实例
            var uploadInst = upload.render({
                elem: '#test1', //绑定元素
                url: '/admin/Upload/upload', //上传接口
                multiple: true,
                number:5,
                accept:'images',
                before: function(obj) {
                    layer.msg('图片上传中...', {
                        icon: 16,
                        shade: 0.01,
                        time: 3000
                    })
                },
                done: function(res){
                    //上传完毕回调
                    if( res.code == 0 ){
                        $('.upload-img-box').append('<div class="upload-icon-img" ><div class="upload-pre-item"><i   class="layui-icon  del_img"></i><img src="http://picture.ddxm661.com/' + res.data.key + '" class="img"><input type="hidden" name="images[]" class="img_val" value="' + res.data.key + '" /></div></div>');
                    }
                    // layer.close(layer.msg('上传成功！'));
                },
                error: function(){
                    //请求异常回调
                    layer.close(layer.msg('上传失败！'));
                }
            });
        });

        //提交
        form.on('submit(L_submit)', function(data){
            var specs = getSpescData();
            var formData = data.field;
            formData.specs = specs.data;
            formData.specs_list = specs.data1;
            if( formData.mold_id == '请选择' ){
                layer.msg('请选择分区');
                return false;
            }
            if( formData.specs_type == 1 && formData.specs.length == 2 ){
                layer.msg('请先设置规格参数');
                return false;
            }
            $.ajax({
                type : "post",
                url : "/mall/Items/item_doPost",
                data: formData,
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        layer.msg(res.msg,{ shift: -1, time: 1000 },function () {
                            location.href="/mall/Items/item_list";
                        });
                    }else{
                        layer.msg(res.msg);
                    }
                }
            });
            return false;
        });

        let category = "<?php echo htmlentities($category); ?>";
        var indexnum = 1;
        $(document).on("click",'.add_custom2',function(){
            var other = $(this).attr("data");
            var html =`<div  style="margin-bottom: 20px">
                        <div class="layui-input-inline">
                            <select name="type_id[${indexnum}]" id="type_id${indexnum}"  lay-filter="type_id${indexnum}">
                                <option>请选择</option>
                                <?php if(is_array($category) || $category instanceof \think\Collection || $category instanceof \think\Paginator): $i = 0; $__LIST__ = $category;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                                <option value="<?php echo htmlentities($vo['id']); ?>" <?php if($vo['id'] == $list['type_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($vo['cname']); ?></option>
                                <?php endforeach; endif; else: echo "" ;endif; ?>
                            </select>
                        </div>
                        <div class="layui-input-inline">
                            <select name="type[${indexnum}]" lay-filter="type${indexnum}" id="type${indexnum}">
                                <option value="0">请先选择一级分类</option>
                            </select>
                        </div>

                        <div class="layui-input-inline">
                            <select name="type_three[${indexnum}]" lay-filter="type_three${indexnum}" id="type_three${indexnum}">
                                <option value="0">请先选择二级分类</option>
                            </select>
                        </div>

                        <div class="layui-input-inline">
                            <button type="" class="layui-btn layui-btn-normal add_custom2" data="0"><i class="layui-icon">&#xe654;</i></button>
                        </div>
                    </div>`;
            $(this).parent().parent().parent(".custom2").append(html);
            $(this).children("i").html("&#x1006;");
            $(this).removeClass('add_custom2');
            $(this).addClass('del_custom2');
            form.render("select");
            form.on('select(type_id'+indexnum+')', function(data){
                var x = data.elem.getAttribute("id");
                x = x.substring(7,x.length);
                var val=data.value;
                if( val == '请选择' ){
                    return false;
                }
                $.ajax({
                    type : "post",
                    url : "/admin/Item/category_select",
                    data: {pid:val},
                    dataType: 'json',
                    success:function(res){
                        if( res.code == 1 ){
                            var category = res.data;
                            if( category.length >= 1 ){
                                var ts = '请选择';
                            }else{
                                var ts = '无';
                            }
                            var option = '';
                            $('#type'+x+'').empty();
                            $('#type'+x+'').append("<option value=''>"+ts+"</option>");
                            for( var k in category){
                                $('#type'+x+'').append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                            }
                            form.render();
                        }
                    }
                });
            });

            form.on('select(type'+indexnum+')', function(data){
                var val=data.value;
                var j = data.elem.getAttribute("id");
                j = j.substring(4,j.length);
                if( val == '请选择' ){
                    return false;
                }
                $.ajax({
                    type : "post",
                    url : "/admin/Item/category_select",
                    data: {pid:val},
                    dataType: 'json',
                    success:function(res){
                        if( res.code == 1 ){
                            var category = res.data;
                            if( category.length >= 1 ){
                                var ts = '请选择';
                            }else{
                                var ts = '无';
                            }
                            var option = '';
                            $('#type_three'+j+'').empty();
                            $('#type_three'+j+'').append("<option value=''>"+ts+"</option>");
                            for( var k in category){
                                $('#type_three'+j+'').append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                            }
                            form.render();
                        }
                    }
                });
            });

            indexnum++;

            return  false;
        })
        $(document).on("click",'.del_custom2',function(){
            $(this).parent().parent().detach();
            form.render("select");
            // indexnum--;
            return false;
        })
    });

    /***
     *删除图片
     */
    $(document).on("click",".del_img",function(data){
        $(this).parent('.upload-pre-item').parent('.upload-icon-img').remove(); //删除页面的
        var imgname = $(this).nextAll('.img_val').val();
        $.ajax({
            type : "post",
            url : "/admin/Item/delelteImage",
            data: {file:imgname},
            dataType: 'json',
            success:function(res){
                layer.msg(res.msg);
            }
        });

    })

    /***
     * 数字
     */
    $(".price").keyup(function () {
        var reg = $(this).val().match(/\d+\.?\d{0,2}/);
        var txt = '';
        if (reg != null) {
            txt = reg[0];
        }
        $(this).val(txt);
    }).change(function () {
        $(this).keypress();
        var v = $(this).val();
        if (/\.$/.test(v))
        {
            $(this).val(v.substr(0, v.length - 1));
        }
    });

</script>

</body>

</html>
