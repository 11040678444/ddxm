layui.use(["table","layer",'laydate',"form"], function(){
    var table = layui.table,
        layer = layui.layer,
        laydate = layui.laydate,
        form = layui.form,
        $ = layui.$;
    var other_number = $("#other_number").val();
    $("#close").click(function(){
        var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
        parent.layer.close(index); //再执行关闭
    })
    $(document).on("click",'.service',function(){
        var obj = $(this).parent().children(".service_name");
        var obji = $(this).parent().children(".service_id");
        var array = obj.val();
        $.ajax({
            url:'/admin/ticket/service_list',
            method:"post",
            success:function(data){
                if(data.code==200){
                    var service = data.data;
                    var hRadio ="";
                    var service_array = new Array();
                    $("#service_object .service_name").each(function(index,item){
                         service_array.push(item.value);
                    });
                    var objval = obj.val();
                    for(var i = 0; i<service.length;i++){
                        if($.inArray(service[i]['sname'],service_array) === -1){
                            hRadio +=`<input type="radio" name="service" value="`+service[i].s_id+`" 
                            title="`+service[i].sname+`">`;
                        }else {
                            if(service[i]['sname'] ==objval){
                            hRadio +=`<input type="radio" name="service"
                             value="`+service[i].s_id+`" title="`+service[i].sname+`">`;
                            }
                        }
                    }
                    layer.open({
                        type: 1, //此处以iframe举例
                        title: '选择服务',
                        area: ['390px', '400px'],
                        shade: 0.5,
                        content: `
                        <form class="layui-form">
                            <div class="layui-form-item" >
                                <label class="layui-form-label">选择服务</label>
                                <div class="layui-input-block" id="radio_service">
                                    ${hRadio}
                                </div>
                            </div>
                        </form>
                        `,
                        btn: ['确认选择', '全部关闭'],//只是为了演示
                        yes: function(){
                            var object  = $("#radio_service  input[name='service']:checked");
                            if(object.length!=0){
                                var sname= object.attr("title");
                                var sid  = object.val();
                                obj.val(sname);
                                obji.val(sid);
                            }
                            layer.closeAll();
                        },
                        btn2: function(){
                            layer.closeAll();
                        },
                        success: function(layero){
                            layer.setTop(layero); //重点2
                            form.render("radio");
                        },
                    });
                }else{
                    layer.msg("暂无数据");
                }
            }
        })
        return false;
    })
    $(document).on("click",'.add_custom',function(){
        var other = $(this).attr("data");
        var html =`<div class="layui-input-block custom dd-left-60"><div class="custom_add custome_num">
        <span>自定义项: </span>
        <input type="text" name="other_time`+other+`" autocomplete="off"
         placeholder="请选择开始时间和结束时间" class="dd-input dd-input-width other_time"value="" >
        <input type="text" name="other_num`+other+`" autocomplete="off"
           placeholder="请输入限制次数" class="dd-input number dd-left other_num" value=""style="width:110px"></div>
        <div class="custom_add custome_attach">
        <button type="" class="layui-btn layui-btn-normal add_custom" data="`+other+`">
        <i class="layui-icon">&#xe654;</i>
        </button>
        </div><div style="clear:both;"></div></div>`;
        $(this).parent().parent(".custom").after(html);
        $(this).children("i").html("&#x1006;");
        $(this).removeClass('add_custom');
        $(this).addClass('del_custom');
        return  false;
    })
    $(document).on("click",'.del_custom',function(){
        $(this).parent().parent(".custom").detach();
        return false;
    })

    $(document).on("click",'.add_service',function(){
        var button = `<button type="" class="layui-btn layui-btn-normal del_service 
        dd-left" title="删除当前服务"><i class="layui-icon">&#x1006;</i></button>`;
        var level_name = $("#level_name").prop("outerHTML");
        var type =$("input:radio[name='type']:checked").val();
        if(type==1){
            var sub = `<input type="text" name="service_num" 
            lay-verify="number"   autocomplete="off" placeholder="请输入服务次数" 
            class="dd-input sub dd-left number service_number servive_sub" value="" style="width:110px;">
            <span class="sub servive_sub">次</span></div>`;
        }else{
             var sub ='';
        }
        var html = `<div class="layui-input-block ddxm-title-bg dd-space"><div class="dd-center">
        <button type="" class="layui-btn layui-btn-normal service" data="0">服务项目</button>
         <input type="text" name="service_name" readonly  autocomplete="off" placeholder="左边按钮选择服务项目" 
         class="dd-input service_name " lay-verify="required" value=""><input type="hidden" name="service_id"   class="service_id" >
         `+sub+`<div>`+level_name+`</div><div><p class="ddxm-form-label">其他限制:</p>
         <div class="layui-input-block dd-left-60"><span>每日限制: </span>
         <input type="text" name="day" autocomplete="off" placeholder="请输入每日限制次数" class="dd-input 
         number dd-input-width" value=""></div><div class="layui-input-block dd-space dd-left-60">
         <span>每月限制: </span><input type="text" name="month" autocomplete="off" placeholder="请输入每月限制次数"
          class="dd-input number dd-input-width" value=""></div>
          <div class="layui-input-block dd-space dd-left-60"><span>每年限制: </span>
          <input type="text" name="year" autocomplete="off" placeholder="请输入每年限制次数" class="dd-input
           number dd-input-width" value=""></div><div class="layui-input-block custom dd-left-60">
           <div class="custom_add custome_num"><span>自定义项: </span>
           <input type="text" name="other_time`+other_number+`" autocomplete="off" readonly 
           placeholder="请选择开始时间和结束时间" class="dd-input dd-input-width other_time" value="" > 
           <input type="text" name="other_num`+other_number+`" autocomplete="off" placeholder="请输入限制次数"
            class="dd-input number dd-left" value="" style="width:110px"></div>
            <div class="custom_add custome_attach"><button type="" class="layui-btn layui-btn-normal
             add_custom"><i class="layui-icon">&#xe654;</i></button></div><div style="clear:both;"></div>
             </div></div><div style="clear:both;padding-bottom:10px;"></div></div>`;
        $("#service_object").children(".ddxm-title-bg:last").children(".dd-center").append(button);
        $("#service_object").append(html);
        other_number++;
        return false;
    })
    $(document).on("click",'.del_service',function(){
        $(this).parent().parent().detach();
        return false;
    })
    $(document).on("click",".other_time",function(){
        var time =$(this);
        layer.open({
            type: 1, //此处以iframe举例
            title: '选择时间',
            area: ['340px', '170px'],
            shade: 0.5,
            content: `
                <form class="layui-form">
                    <div class="layui-form-item" >
                    <label class="layui-form-label">时间选择</label>
                         <input type="text"  autocomplete="off" placeholder="请选择开始时间和结束时间"
                          class="dd-input dd-input-width " value="" id="other_time" >
                    </div>
                </form>
                        `,
            btn: ['关闭'],
            success: function(layero){
                laydate.render({
                    elem: '#other_time',
                    type: 'date',// 时间类型  年月日
                    min:0,// 最小时间 0 为当前时间
                    format: 'yyyy-MM-dd',
                    range: "~",
                    done: function(value, date, endDate){
                         time.val(value);
                         layer.closeAll();
                    }
                });
            },
            yes: function(){
                layer.closeAll();
            },
        })

    })
    form.on('radio(type)', function(data){
        var length = $("#service_object .servive_sub").length;
        var html =`<input type="text" name="service_num" lay-verify="number" 
          autocomplete="off" placeholder="请输入服务次数" class="dd-input sub dd-left number
           service_number servive_sub" value="" style="width:110px;"><span class="sub servive_sub">次</span>`;
        if(data.value !=1){
            $("#service_object .servive_sub").remove();
        }else{
            if(length==0){
                $("#service_object .service_id").after(html);
            }
        }
        var type_data = $("#type_data input").attr("name");
        if(data.value ==1){
            if(type_data !== "use_day"){
                $("#type_number").html("使用天数");
                $("#type_data").empty();
                $("#type_data").append('<input type="text" name="use_day" lay-verify="number" autocomplete="off" placeholder="请输入使用天数" class="layui-input number" value="">');
            }
        }else if(data.value ==2){
            if(type_data !=="month"){
                $("#type_number").html("使用月数");
                $("#type_data").empty();
                $("#type_data").append('<input type="text" name="over_month" lay-verify="number" autocomplete="off" placeholder="请输入使用月数" class="layui-input number" value="">');                
            }
        }else if(data.value ==4){
            if(type_data !=="year"){
                $("#type_number").html("使用年数");
                $("#type_data").empty();
                $("#type_data").append('<input type="text" name="over_year" lay-verify="number" autocomplete="off" placeholder="请输入使用年数" class="layui-input number" value="">');
            }
        }
    })
    //初始化开始时间
    laydate.render({
        elem: '#time',
        type: 'date',// 时间类型  年月日
        format: 'yyyy-MM-dd',
        min:0,// 最小时间 0 为当前时间
        range: '~',
    });
    /*// 初始化结束时间
    laydate.render({
      elem: '#end_time',
      type: 'date',// 时间类型  年月日
      min:0,// 最小时间 0 为当前时间
      format: 'yyyy年MM月dd日',
    });*/
    form.verify({
        cardname: function(value, item){ //value：表单的值、item：表单的DOM对象
            // if(!new RegExp("^[a-zA-Z0-9_\u4e00-\u9fa5\\s·]+$").test(value)){
            //   return '用户名不能有特殊字符';
            // }
            if(/(^\_)|(\__)|(\_+$)/.test(value)){
              return '用户名首尾不能出现下划线\'_\'';
            }
            if(/^\d+\d+\d$/.test(value)){
              return '用户名不能全为数字';
            }
        },
        numer:function (value,item){

        }
    });
    $(document).on('keyup','.number',function(data){
        var number;
        if(this.value.length==1){
            number=this.value.replace(/[^0-9]/g,'');
        }else if(this.value.length==0){
            number=0;
        }else{
            number=this.value.replace(/\D/g,'');
        }
        this.value = number==''?0:parseInt(number);
    })
    form.on('checkbox(c_all)', function(data){
        $(".f_all").prop("checked",false);
        if(data.elem.checked){
            $(".shop_id").prop("checked", true);
            form.render('checkbox');
        }else{
            $(".shop_id").prop("checked", false);
            form.render('checkbox');
        }

    });
    form.on('checkbox(f_all)', function (data) {
        var item = $(".shop_id");
        item.each(function () {
            if ($(this).prop("checked")) {
                 $(this).prop("checked", false);
            } else {
                $(this).prop("checked", true);
            }
        })
        form.render('checkbox');
    });

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
    $(document).on('afterpaste','.number',function(data){
        var number;
        if(this.value.length==1){
            number=this.value.replace(/[^0-9]/g,'');
        }else if(this.value.length==0){
            number=0;
        }else{
            number=this.value.replace(/\D/g,'');
        }
        this.value = number==''?0:parseInt(number);
    })
    $(document).on('blur','.level',function(data){

        var id = $(this).attr("data");
        var price =0;
        $(".level"+id).each(function(index,item){
            if(item.value=="" || item.value == null || item.value == undefined){

            }else{
                price += parseFloat(item.value);
            }
        });
        $("#card"+id+" span").html(price);
        $("#card"+id+"  input[name='price']").val(price);
    })
    //点击添加按钮事件处理
    form.on('submit(add)', function(data){
        var shop_id = new Array();
        var time  = data.field.time.split('~');
        data.field.start_time = time[0];
        data.field.end_time = time[1];
        var shop_name = new Array();
         $("input:checkbox[name='shop_name']:checked").each(function(i){
            shop_name[i] = $(this).val();
            shop_id[i] = $(this).attr("data");
        });
        data.field.shop_id = shop_id;
        data.field.shop_name = shop_name;
        var service_name = new Array();
        $("input[name='service_name']").each(function(s){
            service_name[s] = $(this).val();
        });
        data.field.service_name = service_name;
        var service_id = new Array();
        $("input[name='service_id']").each(function(d){
            service_id[d] = $(this).val();
        });
        data.field.service_id = service_id;
        var service_num = new Array();
        $("input[name='service_num']").each(function(n){
            service_num[n] = $(this).val();
        });
        data.field.service_num = service_num;
        var mprice = new Array();
        $("input[name='mprice']").each(function(m){
            mprice[m] = $(this).val();
        });
        data.field.mprice = mprice;
        var level_id = new Array();
        $("input[name='level_id']").each(function(le){
            level_id[le] = $(this).val();
        });
        data.field.level_id = level_id;
        var level_name = new Array();
        $("input[name='level_name']").each(function(ln){
            level_name[ln] = $(this).val();
        });
        data.field.level_name = level_name;
        var price = new Array();
        $("input[name='price']").each(function(p){
            price[p] = $(this).val();
        });
        data.field.price = price;

        var other_time =new Array();
        var other_num = new Array();
        var service_level_price = new Array();
        var price_length =$("#level_name").attr("data");
        for(var o = 0; o < price_length ;o++){
            service_level_price[o] = new Array();
            $("input[name='service_level_price"+o+"']").each(function(pr){
                service_level_price[o][pr]=$(this).val();
            })
        }
        for(var ot =0; ot<other_number;ot++){
            other_time[ot] = new Array();
            $("input[name='other_time"+ot+"']").each(function(ont){
                other_time[ot][ont]=$(this).val();
            })
            other_num[ot] = new Array();
            $("input[name='other_num"+ot+"']").each(function(onn){
                other_num[ot][onn]=$(this).val();
            })
        }
        var day = new Array();
        var month = new Array();
        var year = new Array();
        $("input[name='day']").each(function(da){
            day[da]=$(this).val();
        })
        $("input[name='month']").each(function(mo){
            month[mo]=$(this).val();
        })
        $("input[name='year']").each(function(ye){
            year[ye]=$(this).val();
        })
        data.field.day = day;
        data.field.month = month;
        data.field.year = year;
        data.field.other_time = other_time;
        data.field.other_num = other_num;
        data.field.service_level_price = service_level_price;
        var data = data.field;
        $("#add").hide();
        $.ajax({
            url:'/admin/ticket/add',
            data:{data:data},
            type:"POST",
            dataType:"json",
            success: function(data){
                if(data.result){                    
                    layer.msg(data.msg, {icon: 6 , time: 1200 ,shade:0.6},function(){
                         window.location.href = 'index';
                        return false;
                    });
                }else{
                    layer.msg(data.msg, {icon: 5 , time: 2000 ,shade:0.6});
                    $("#add").show();
                }
            },
            error:function(e){               
                layer.msg(data.msg, {icon: 5 , time: 2000 ,shade:0.6});
                $("#add").show();
            }
        })
        return false; //阻止表单跳转。如果需要表单跳转，去掉这段即可。
    });
});
