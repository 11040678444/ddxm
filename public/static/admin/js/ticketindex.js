layui.use(["table","layer","form"], function() {
    var table = layui.table,
        layer = layui.layer,
        form  = layui.form,
        $ = layui.$;
    table.render({
        elem: '#ticket',
        toolbar: '#toolbarDemo',
        skin: 'row', //行边框风格
        even: true ,//开启隔行背景
        id:"tableTest",
        url: '{:url("admin/ticket/ticket_list")}',
        height: 'full-148',
        page: true,
        cols: [[
            { field: 'shop',align: 'center',  title: '所属门店', toolbar: '#shopTool'},
            { field: 'card_name',align: 'center',width: 120,  title: '服务卡名称'},
            { field: 'type',align: 'center',  title: '类型',},
            { field: 'service',align: 'center', title: '所属服务' , toolbar: '#serviceTool'},
            { field: 'critulation',align: 'center',title: '发行量' },
            { field: 'restrict_num',align: 'center',  title: '单人限制'},
            { field: 'start_time',align: 'center',width: 120, title: '开始时间'},
            { field: 'end_time',align: 'center',width: 120, title: '结束时间'},
            { field: 'create_time',align: 'center', width: 160, title: '创建时间'},
            { field: 'update_time',align: 'center', width: 160, title: '更新时间'},
            { field: 'status',align: 'center',width: 80, title: '状态'},
            { align: 'center', width: 185, title: '操作', toolbar: '#barTool' }
        ]],
    });
    table.on('tool(ticket)', function(obj) {
        var data = obj.data;
        if (obj.event === 'shop') {
            var id = data.id;
            $.ajax({
                url: '{:url("admin/ticket/ticket_shop")}',
                type: "POST" ,
                data: {id:id},
                beforeSend: function (XMLHttpRequest) {
                    loadingFlag= layer.msg('正在读取数据，请稍候……',
                        {icon: 16,
                        shade: 0.3,
                        shadeClose:false,
                        time:30000
                    });
                },
                success:function(data){
                    layer.close(loadingFlag);
                    var shop ="";
                    for(var i = 0;i<data.data.length;i++){
                        shop += `<input type="checkbox" name="shop_name" lay-skin="primary" title="`+data.data[i].shop_name+`"  checked disabled="disabled" >`;
                    }
                    console.log(shop);
                    /*return false;*/
                    layer.open({
                        type:1,
                        title:"已选门店",
                        area:['300px','400px'],
                        content:`<form class="layui-form">
                            <div class="layui-form-item" >
                                <div style="margin-left:20px;">
                                    ${shop}
                                </div>
                            </div>
                        </form>`,
                        success: function(layero){                            
                            form.render("checkbox");
                        },
                    })
                }
            })
        }
        if (obj.event === 'service') {
            var id = data.id;                  
            layer.open({
                type:1,
                title:"服务列表",
                area:['800px','600px'],
                content:` <table class="layui-hide" id="ticket_service" lay-filter="ticket_service"></table>`,
                success: function(layero){                            
                    table.render({
                        elem: '#ticket_service',
                        /*toolbar: '#toolbarDemo',*/
                        skin: 'row', //行边框风格
                        even: true ,//开启隔行背景
                        id:"tableTest",
                        url: '{:url("admin/ticket/ticket_service")}?id='+id,
                        height: '530',
                        page: true,
                        cols: [[
                            { field: 'service_name',align: 'center',  title: '服务名'},
                            { field: 'num',align: 'center',  title: '服务次数'},
                            { align: 'center',  title: '金额', toolbar: '#priceTool' },
                            { field: 'day',align: 'center',  title: '每日限制',},
                            { field: 'month',align: 'center', title: '每月限制' },
                            { field: 'year',align: 'center',title: '每年限制' },
                            { align: 'center',  title: '其他限制', toolbar: '#otherTool' },
                        ]],
                    });
                    table.on('tool(ticket_service)', function(obj) {
                        if (obj.event === 'price') {
                            var data = obj;
                            var id = obj.data.id;
                            var price ="";
                            $.ajax({
                                url: '{:url("admin/ticket/ticket_service_money")}',
                                type: "POST" ,
                                data: {id:id},
                                success:function(pdata){
                                    price = pdata.data;
                                    var phtml ="";
                                    for(var p =0;p<price.length;p++){
                                        phtml +=`
                                        <div class="ddxm-form-item ddxm-content-long" >
                                            <label class="layui-form-label">`+price[p].level_name+`：</label>
                                            <div class="layui-form-mid layui-word-aux">
                                                `+price[p].price+`
                                            </div>
                                        </div>`;
                                    }
                                    layer.open({
                                        type:1,
                                        title:"金额详情",
                                        area:["300px","300px"],
                                        content:phtml,
                                        success:function(lay){

                                        }
                                    })
                                }
                            })
                            
                        }  
                        if (obj.event === 'other') {
                            var data = obj;
                            var id = obj.data.id;
                            var other ="";
                            $.ajax({
                                url: '{:url("admin/ticket/ticket_service_other")}',
                                type: "POST" ,
                                data: {id:id},
                                success:function(odata){
                                    other = odata.data;
                                    var ohtml ="";
                                    for(var o =0;o<other.length;o++){
                                        ohtml +=`
                                        <div class="ddxm-form-item ddxm-content-long" >
                                            <label class="layui-form-label">起始时间：</label>
                                            <div class="layui-form-mid layui-word-aux ddxm-col">
                                                `+other[o].start_time+`~`+other[o].end_time+`
                                            </div>
                                            <label class="layui-form-label">限制次数：</label>
                                            <div class="layui-form-mid layui-word-aux">
                                                `+other[o].num+`
                                            </div>
                                        </div>`;
                                    }
                                    layer.open({
                                        type:1,
                                        title:"金额详情",
                                        area:["500px","300px"],
                                        content:ohtml,
                                        success:function(lay){
                                        }
                                    })
                                }
                            })
                        }
                    })
                }
            })
        }

    });
});