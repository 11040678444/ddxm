<?php /*a:2:{s:69:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\item\item_add.html";i:1601275830;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
        <form class="layui-form form-horizontal">    <!-- action="<?php echo url('admin/Member/level_doPost'); ?>" method="post" -->
            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>分类</label>
                <div class="layui-input-inline">
                    <select name="type_id" id="type_id" lay-filter="type_id">
                        <option>请选择</option>
                        <?php if(is_array($category) || $category instanceof \think\Collection || $category instanceof \think\Paginator): $i = 0; $__LIST__ = $category;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$vo): $mod = ($i % 2 );++$i;?>
                            <option value="<?php echo htmlentities($vo['id']); ?>" <?php if($vo['id'] == $list['type_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($vo['cname']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?> 
                    </select>
                </div>
                <div class="layui-input-inline">
                    <select name="type" lay-filter="type" id="type">
                        <option value="0">请先选择一级分类</option>
                        <?php if(is_array($type) || $type instanceof \think\Collection || $type instanceof \think\Paginator): $i = 0; $__LIST__ = $type;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$tt): $mod = ($i % 2 );++$i;?>
                            <option <?php if($tt['id'] == $list['type']): ?> selected="selected" <?php endif; ?> value="<?php echo htmlentities($tt['id']); ?>"><?php echo htmlentities($tt['cname']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                    </select>
                </div>
            </div>

            <div class="layui-form-item">
                <label class="layui-form-label"><span style="color:red;">*</span>商品名称 </label>
                <div class="layui-input-inline w300">
                    <input type="text" name="title"  autocomplete="title"  placeholder="商品名称" value="<?php echo htmlentities($list['title']); ?>" class="layui-input" >
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选择单位</label>
                <div class="layui-input-inline">
                    <select name="unit_id" lay-filter="unit_id">
                        <?php if(is_array($unit) || $unit instanceof \think\Collection || $unit instanceof \think\Paginator): $i = 0; $__LIST__ = $unit;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$unit): $mod = ($i % 2 );++$i;?>
                            <option value="<?php echo htmlentities($unit['id']); ?>" <?php if($unit['id'] == $list['unit_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($unit['title']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?> 
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">选择规格</label>
                <div class="layui-input-inline">
                    <select name="specs_id" lay-filter="specs_id">
                        <?php if(is_array($specs) || $specs instanceof \think\Collection || $specs instanceof \think\Paginator): $i = 0; $__LIST__ = $specs;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$specs): $mod = ($i % 2 );++$i;?>
                            <option value="<?php echo htmlentities($specs['id']); ?>" <?php if($specs['id'] == $list['specs_id']): ?> selected="selected" <?php endif; ?> ><?php echo htmlentities($specs['title']); ?></option>
                        <?php endforeach; endif; else: echo "" ;endif; ?> 
                    </select>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">商品条码 </label>
                <div class="layui-input-inline w300">
                    <input type="text" name="bar_code"  autocomplete="bar_code"  placeholder="商品条码" value="<?php echo htmlentities($list['bar_code']); ?>" class="layui-input">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">库存预警值 </label>
                <div class="layui-input-inline w300">
                    <input type="text" name="stock_alert"  autocomplete="stock_alert"  placeholder="库存预警值" value="<?php echo htmlentities($list['stock_alert']); ?>"  onkeyup="this.value=this.value.replace(/\D/g,'')" onblur="this.value=this.value.replace(/\D/g,'')" class="layui-input" >
                </div>
            </div>
            <div class="layui-form-item">
                    <label class="layui-form-label">序号</label>
                    <div class="layui-input-inline">
                        <input type="text" name="sort" lay-verify="sort" onkeyup="this.value=this.value.replace(/\D/g,'')" onblur="this.value=this.value.replace(/\D/g,'')" autocomplete="sort" placeholder="请输入序号" class="layui-input " value="<?php echo htmlentities($list['sort']); ?>">
                    </div>
                </div>
            <div class="layui-form-item" style="width: 400px;">
                <label class="layui-form-label">设置价格 <span class="must">*</span></label>
                <table class="layui-table">
                      <colgroup>
                        <col>
                        <col>
                        <col>
                      </colgroup>
                      <thead>
                        <tr width="300px;">
                          <th width="80px;">是否使用</th>
                          <th width="120px;">门店名称</th>
                          <th width="80px;">销售价格</th>
                          <th width="80px;">销售最低价格</th>
                        </tr> 
                      </thead>
                      <tbody>
                        
                        <?php if($list['in_allshop'] == 0): ?>
                        <tr>
                          <td><input type="checkbox" id="all_shop" lay-filter="all_shop" name="all_shop" lay-skin="primary" title="使用" value="1"></td>
                          <td>所有门店</td>
                          <td><input type="text" name="all_price"  autocomplete="all_price"  placeholder="" value="<?php echo htmlentities($list['all_price']); ?>"   class="layui-input price" ></td>
                          <td><input type="text" name="all_price1"  autocomplete="all_price1"  placeholder="" value="<?php echo htmlentities($list['all_price1']); ?>"   class="layui-input price" ></td>
                        </tr>
                        <?php endif; if($list['in_allshop'] == 1): ?>
                        <tr>
                          <td><input type="checkbox" checked="checked" id="all_shop" lay-filter="all_shop" name="all_shop" lay-skin="primary" title="使用" value="1"></td>
                          <td>所有门店</td>
                          <td><input type="text" name="all_price"  autocomplete="all_price"  placeholder="" value="<?php echo htmlentities($itemPrice['0']['selling_price']); ?>"   class="layui-input price" ></td>
                          <td><input type="text" name="all_price1"  autocomplete="all_price1"  placeholder="" value="<?php echo htmlentities($itemPrice['0']['minimum_selling_price']); ?>"  class="layui-input price" ></td>
                        </tr>
                        <?php endif; if(is_array($shop) || $shop instanceof \think\Collection || $shop instanceof \think\Paginator): $i = 0; $__LIST__ = $shop;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$shop): $mod = ($i % 2 );++$i;?>
                            <tr class="item_price" <?php if($list['in_allshop'] == 1): ?> style="display:none" <?php endif; ?>>
                              <td><input type="checkbox" name="is_use[<?php echo htmlentities($shop['id']); ?>][status]" lay-skin="primary" title="使用" value="1" <?php if(is_array($itemPrice) || $itemPrice instanceof \think\Collection || $itemPrice instanceof \think\Paginator): $i = 0; $__LIST__ = $itemPrice;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$price): $mod = ($i % 2 );++$i;if($price['shop_id'] == $shop['id']): if($price['item_id'] == $list['id']): ?> checked="checked" <?php endif; ?> <?php endif; ?> <?php endforeach; endif; else: echo "" ;endif; ?> ></td>
                              <td><?php echo htmlentities($shop['name']); ?></td>
                              <td><input type="text" name="is_use[<?php echo htmlentities($shop['id']); ?>][shop_price]"  autocomplete="shop_price"  placeholder="" <?php if(is_array($itemPrice) || $itemPrice instanceof \think\Collection || $itemPrice instanceof \think\Paginator): $i = 0; $__LIST__ = $itemPrice;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$price): $mod = ($i % 2 );++$i;if($price['shop_id'] == $shop['id']): if($price['item_id'] == $list['id']): ?> value="<?php echo htmlentities($price['selling_price']); ?>" <?php endif; ?> <?php endif; ?> <?php endforeach; endif; else: echo "" ;endif; ?>    class="layui-input price" ></td>
                              <td><input type="text" name="is_use[<?php echo htmlentities($shop['id']); ?>][shop_price1]"  autocomplete="shop_price1"  placeholder="" <?php if(is_array($itemPrice) || $itemPrice instanceof \think\Collection || $itemPrice instanceof \think\Paginator): $i = 0; $__LIST__ = $itemPrice;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$price): $mod = ($i % 2 );++$i;if($price['shop_id'] == $shop['id']): if($price['item_id'] == $list['id']): ?> value="<?php echo htmlentities($price['minimum_selling_price']); ?>" <?php endif; ?> <?php endif; ?> <?php endforeach; endif; else: echo "" ;endif; ?>   class="layui-input price" ></td>
                            </tr>
                        <?php endforeach; endif; else: echo "" ;endif; ?>
                      </tbody>
                </table>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">商品图</label>
                <div class="layui-input-block">
                    <button type="button" class="layui-btn" id="test1">
                        <i class="layui-icon">&#xe67c;</i>上传图片
                    </button>
                    <div class="upload-img-box">
                        <?php if(count($list['pics'] >0)): if(is_array($list['pics']) || $list['pics'] instanceof \think\Collection || $list['pics'] instanceof \think\Paginator): $i = 0; $__LIST__ = $list['pics'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pics): $mod = ($i % 2 );++$i;if(!empty($pics)): ?>
                                <div class="upload-icon-img" >
                                    <div class="upload-pre-item">
                                        <i class="layui-icon  del_img"></i>
                                        <img src="http://picture.ddxm661.com/<?php echo htmlentities($pics); ?>" class="img">
                                        <input type="hidden" name="images[]" class="img_val" value="<?php echo htmlentities($pics); ?>" />
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php endforeach; endif; else: echo "" ;endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

<!--            <div class="layui-form-item">-->
<!--                <label class="layui-form-label"><span style="color:red;">*</span>商品库</label>-->
<!--                <div class="layui-input-block">-->
<!--                    <input type="checkbox" <?php if($list['item_type'] == 2): ?> checked <?php endif; if($list['item_type'] == 3): ?> checked <?php endif; ?>  name="item_type[]" value="2" title="门店商品">-->
<!--                    <input type="checkbox" <?php if($list['item_type'] == 1): ?> checked <?php endif; if($list['item_type'] == 3): ?> checked <?php endif; ?> name="item_type[]" value="1" title="线上商品">-->
<!--                </div>-->
<!--            </div>-->
            <div class="layui-form-item">
                <label class="layui-form-label">分区</label>
                <div class="layui-input-block">
                    <?php if(is_array($cate) || $cate instanceof \think\Collection || $cate instanceof \think\Paginator): $i = 0; $__LIST__ = $cate;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$cate): $mod = ($i % 2 );++$i;?>
                    <input type="radio" <?php if($cate['id'] == $list['cate_id']): ?> checked <?php endif; ?>  name="cate_id" value="<?php echo htmlentities($cate['id']); ?>" title="<?php echo htmlentities($cate['title']); ?>">
                    <?php endforeach; endif; else: echo "" ;endif; ?>
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">描述</label>
                <div class="layui-input-block w300">
                    <textarea name="content" required lay-verify="content" placeholder="相关描述" class="layui-textarea"><?php echo htmlentities($list['content']); ?></textarea>
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
<script>
    layui.use(["form"], function(){
        var layer = layui.layer;
        form = layui.form;

        //筛选分类
        form.on('select(type_id)', function(data){   
            var val=data.value;
            $.ajax({
                type : "post",
                url : "/admin/Item/category_select",
                data: {pid:val},
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        var category = res.data;
                        console.log(category);
                        var option = '';
                        $('#type').empty();
                        $('#type').append("<option value=''>请选择</option>");
                        for( var k in category){
                            $("#type").append("<option value=" + category[k].id + ">" + category[k].cname + "</option>");
                        }
                        form.render("select");
                    }
                }
            });
        });

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

        //选中全部
        form.on('checkbox(all_shop)', function(data){
            // console.log(data.elem.checked); //是否被选中，true或者false
            // console.log(data.value); //复选框value值，也可以通过data.elem.value得到
            if( data.elem.checked == true ){
                $(".item_price").hide();
            }else{
                $(".item_price").show();
            }
        }); 

        //上传图片
        layui.use('upload', function(){
            var upload = layui.upload;
            //执行实例
            var uploadInst = upload.render({
                elem: '#test1', //绑定元素
                url: '/admin/Item/test', //上传接口
                multiple: true, 
                before: function(obj) {
                    layer.msg('图片上传中...', {
                        icon: 16,
                        shade: 0.01,
                        time: 0
                    })
                },
                done: function(res){
                    //上传完毕回调
                    if( res.code == 0 ){
                        layer.close(layer.msg('上传成功！'));
                        $('.upload-img-box').append('<div class="upload-icon-img" ><div class="upload-pre-item"><i   class="layui-icon  del_img"></i><img src="http://picture.ddxm661.com/' + res.data.key + '" class="img"><input type="hidden" name="images[]" class="img_val" value="' + res.data.key + '" /></div></div>');

                    }
                },
                error: function(){
                    //请求异常回调
                }
            });
        });



        form.on('submit(L_submit)', function(data){
            var formData = data.field;
            var itemID = $('#itemID').val();
            $.ajax({
                type : "post",
                url : "/admin/Item/item_doPost",
                data: formData,
                dataType: 'json',
                success:function(res){
                    if( res.code == 1 ){
                        layer.msg(res.msg); 
                        var index = parent.layer.getFrameIndex(window.name);
                        setTimeout(function(){
                            parent.layer.close(index);
                            parent.location.reload();
                        }, 1000); 
                    }else{
                        layer.msg(res.msg);
                    }
                }
            });
            return false;
        });
    });

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
