<?php /*a:2:{s:64:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\help\add.html";i:1601275829;s:68:"D:\PHPTutorial\WWW\ddxm_svn\application\admin\view\index_layout.html";i:1601275830;}*/ ?>
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
    
<div class="layui-card">
    <div class="layui-card-header"><?php echo !empty($auth_group['id']) ? '编辑' : '新增'; ?>帮助</div>
    <div class="layui-card-body">
        <form class="layui-form form-horizontal"  > <!-- action="<?php echo url('admin/help/doPost'); ?>" method="post" -->
            <div class="layui-form-item">
                <label class="layui-form-label">标题</label>
                <div class="layui-input-block w300">
                    <input type="text" name="title" lay-verify="title" autocomplete="off" placeholder="请输入标题" class="layui-input" value="<?php echo htmlentities($list['title']); ?>">
                </div>
            </div>
            <div class="layui-form-item">
                <label class="layui-form-label">内容</label>
                <div class="layui-input-block w600">
                    <textarea name="content" autocomplete="off" id="content" lay-verify="content" placeholder="请输入内容" class="layui-textarea"><?php echo htmlentities($list['content']); ?></textarea>
                    
                </div>
            </div>
            
            <input type="hidden" name="id" id="dsada" value="<?php echo htmlentities($list['id']); ?>" />
            <div class="layui-form-item">
                <label class="layui-form-label">序号</label>
                <div class="layui-input-block w300">
                    <input type="text" name="sort" lay-verify="sort" autocomplete="off" placeholder="请输入序号" class="layui-input" value="<?php echo htmlentities($list['sort']); ?>">
                </div>
            </div>
            <div class="layui-form-item">
                <div class="layui-input-block w300">
                    <button class="layui-btn" lay-submit lay-filter="confirm">立即提交</button>
                    <button class="layui-btn layui-btn-normal" id="colse">返回</button>
                </div>
            </div>
        </form>
    </div>
</div>

    
<script type="text/javascript" src="/public/static/admin/js/common.js"></script>
<script>
    layui.use(['layedit',"form"], function(){
        var layedit = layui.layedit;
        form = layui.form;

        var up_url="<?php echo url('admin/help/test'); ?>";//上传图片url

        layedit.set({
            uploadImage: {
                url:up_url //接口url
                ,type: 'post' //默认post
            }
        });


        var index = layedit.build('content'); //建立编辑器
        form.on('submit(confirm)', function(data){
            data.field.content = layedit.getContent(index);
            var formData = data.field;
            var id = $('#dsada').val();
            $.ajax({
                type : "post",
                url : "/admin/help/doPost",
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
                        
                    }else if( res.code == '-1' ){
                        alert()
                    }
                }
            });
            return false;
        });

        //关闭
        $("#colse").click(function(){
            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
            parent.layer.close(index); //再执行关闭
            parent.location.reload();
        })
    });
</script>

</body>

</html>
