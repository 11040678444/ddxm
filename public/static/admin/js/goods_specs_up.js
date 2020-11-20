/**
 * 商品 规格 动态生成表单
 *  username: zgc
 * 2019-09-04
 */

var specsAllData = new Array();//保存 所有的 选择过来的数组
var arrayTile = new Array();//标题组数   ["颜色分类", "尺寸", "类型", "品牌"]
var arrayInfor = new Array();// 每组数据对应的参数
var arrayColumn = new Array();//指定列，用来合并哪些列
var fgh = "#@$";// 商品与ID的分隔符

// 添加规格---选择规格框
function addsecs() {

    // 获取已经选择的 数组 顶级 ID---传递给查询选择规格列表，防止重复选择
    var ids = "";
    for(var ind = 0;ind<specsAllData.length;ind++){
        ids=ids+specsAllData[ind].tid+","
    }
    if(ids !=""){
        ids = ids.substring(0,ids.length-1);
    }

    layer.open({
        type: 2,
        title: "选择规格",
        shadeClose: true,
        shade: 0.4,
        area: ['70%', '70%'],
        content: 'selectspecs?ids='+ids,
        btn: ['确定','取消'],
        yes: function(index){
            installspecs(index);
        },
        cancel: function(){
        }
    });
}

// 添加规格
function installspecs(index) {

    var res = window["layui-layer-iframe" + index].callbackdata();
    if(res.length == 0 || res.value.length == 0){
        layer.msg("请选择规格",{icon:5});
        return;
    }

    var len = specsAllData.length;

    for (var h = 0; h < len; h++) {
        var tid = specsAllData[h].tid;
        if (tid == res.tid) {
            layer.msg("请勿重新选择规格", {icon: 5});
            return;
        }
    }

    layer.close(index);
    var con = res.value.length;
    res.cont = con;
    specsAllData[len] = res;

    // var aaa = specsAllData.sort(ascend);
    // console.log(aaa);

    arrayColumn = new Array();
    arrayInfor = new Array();
    for (var k = 0; k < specsAllData.length; k++) {
        arrayColumn.push(k);
        var obg = new Array();

        for (var ins = 0; ins < specsAllData[k].value.length; ins++) {
            obg[ins] = specsAllData[k].value[ins].name+fgh+specsAllData[k].value[ins].id;
        }
        arrayInfor[k]=obg;
    }
    if (len == 0) {
        $("#tb_specs_all").empty();
        $("#tb_specs_all_02").empty();
    }

    addok01();
    arrayTile[arrayTile.length]=res.tname;
    step.Creat_Table();
}

// 删除规格
function delspecs(tid) {

    if(specsAllData.length == 0){
        layer.msg("请选择规格",{icon:5});
        return;
    }

    for(var p = 0;p<specsAllData.length;p++){
        var tid2 = specsAllData[p].tid;
        if(tid2==tid){
            specsAllData.splice(p,1);
        }
    }

    var len = specsAllData.length;

    arrayColumn = new Array();
    arrayInfor = new Array();
    arrayTile = new Array();


    for (var k = 0; k < len; k++) {
        arrayColumn.push(k);
        var obg = new Array();

        for (var ins = 0; ins < specsAllData[k].value.length; ins++) {
            for (var ins = 0; ins < specsAllData[k].value.length; ins++) {
                obg[ins] = specsAllData[k].value[ins].name+fgh+specsAllData[k].value[ins].id;
            }
        }
        arrayInfor[k]=obg;
        arrayTile[k]=specsAllData[k].tname;
    }

    $("#tb_specs_all").empty();
    $("#createTable").empty();
    addok01();
    if(len != 0){
        step.Creat_Table();
    }

}

//选择规格----设置规格列表
function addok01() {

    var length = specsAllData.length;
    document.getElementById('tb_specs_all').innerHTML="";
    if(length == 0){//全部删除 显示默认数据
        $("#tb_specs_all").append("<tr><td colspan=\"2\" style=\"text-align: center;color: red\">请先添加规格</td></tr>");
        return;
    }
    for (var k = 0; k < length; k++) {

        $("#tb_specs_all").append("<tr><td>"+specsAllData[k].tname+"</td><td><button type='button' " +
            "class='layui-btn layui-btn-danger layui-btn-sm' onclick='delspecs("+specsAllData[k].tid+")'>"+
            "<i class='layui-icon'>&#xe640;</i></button></td></tr>");
    }
}

//上传 规格 图片
function openuploadimg(item,ids) {

    layer.open({
        type: 2,
        title: item+"--图片上传",
        shadeClose: true,
        shade: 0.4,
        area: ['480px', '480px'],
        content: 'uploadSpecs?ids=2',
        btn: ['确定','取消'],
        yes: function(index){
            var res = window["layui-layer-iframe" + index].callbackdata();
            if(res.imgurl == ""){
                layer.msg("请先上传图片",{icon:5});
                return;
            }
            layer.close(index);
            $("#img"+ids).attr('src',"http://picture.ddxm661.com/"+res.imgurl);
            $("#img"+ids).attr('name',res.imgurl);
            $("#img"+ids).show();
            $("#"+ids).hide();


            if(res.check == 1){
                //批量更换图片
                allUpImg(ids,res.imgurl);
            }
        },
        cancel: function(){
        }
    });
}

//上传 规格--详情 图片
function openuploadimg2(item,ids) {

   var imgurls =  $("#img2"+ids).attr('name');

   if(imgurls == undefined){
       imgurls ='';
   }
    layer.open({
        type: 2,
        title: item+"--图片上传",
        shadeClose: true,
        shade: 0.4,
        area: ['480px', '480px'],
        content: 'uploadSpecsDetails?imgurls='+imgurls,
        btn: ['确定','取消'],
        yes: function(index){
            var res = window["layui-layer-iframe" + index].callbackdata();
            if(res.imgurl == ""){
                layer.msg("请先上传图片",{icon:5});
                return;
            }
            layer.close(index);

            var imgarr = res.imgurl;
            var imgurl = "";
            for(var k =0;k<imgarr.length;k++){

                imgurl = imgurl+imgarr[k]+",";
            }

            if(imgurl !=""){
                imgurl = imgurl.substring(0,imgurl.length-1);
            }

            $("#img2"+ids).attr('name',imgurl);
            $("#input2"+ids).val(imgurl);
            $("#img2"+ids).show();
            $("#i"+ids).hide();

            if(res.check == 1){
                //批量更换图片
                allUpImg(ids,res.imgurl);
            }

        },
        cancel: function(){
        }
    });
}


// 批量更换图片
function allUpImg(ids,imgurl) {

    var ind = ids.lastIndexOf("_");
    if(ind == -1){
        return;
    }
    var id2 = ids.substring(0,ind);

    var length = specsAllData.length;

    $("#innerTable").find("tr").each(function() {
        var tdArr = $(this).children();

        var imgurl_id = tdArr.eq(length).find('img').attr('id');//规格图片

        imgurl_id = imgurl_id.substring(3,imgurl_id.size);
        var ind = imgurl_id.lastIndexOf("_");
        var id3 = imgurl_id.substring(0,ind);

        console.log(id2+"___"+id3);

        //判断ID 是否 一致，如果相同  就更换所有的图片
        if(id2 == id3){

            $("#img"+imgurl_id).attr('src',"http://picture.ddxm661.com/"+imgurl);
            $("#img"+imgurl_id).attr('name',imgurl);
            $("#img"+imgurl_id).show();
            $("#"+imgurl_id).hide();

        }

    });

}
var step = {
    //SKU信息组合
    Creat_Table: function () {

        step.hebingFunction();

        // alert(specsAllData.length);
        // if (specsAllData.length>=2) {
        if (true) {
            //开始创建Table表
            var RowsCount = 0;
            $("#createTable").html("");
            var table = $("<table id=\"process\" border=\"1\" class=\"layui-table\" cellpadding=\"1\" cellspacing=\"0\" style=\"width:100%;padding:5px;\"></table>");
            table.appendTo($("#createTable"));
            var thead = $("<thead></thead>");
            thead.appendTo(table);
            var trHead = $("<tr></tr>");
            trHead.appendTo(thead);

            //创建表头  循环 有多少个 顶部数组就创建多少个
            $.each(arrayTile, function (index, item) {
                var td = $("<th>" + item + "</th>");
                td.appendTo(trHead);
            });

            var itemColumHead = $("<th  style=\"width:70px;\">图片</th>" +
                // "<th style=\"width:70px;\">详情图</th> " +
                "<th style=\"width:70px;\">条形码</th> " +
                "<th style=\"width:70px;\">库存</th> " +
                "<th style=\"width:70px;\">成本价</th>" +
                "<th style=\"width:70px;\">原价</th>" +
                "<th style=\"width:70px;\">会员价</th> " +
                "<th style=\"width:70px;\">容量ml</th> " +
                "<th style=\"width:70px;\">重量kg</th> " +
                "<th style=\"width:70px;\">初始销量</th> " +
                "");
            itemColumHead.appendTo(trHead);
            var tbody = $("<tbody id='innerTable'></tbody>");
            tbody.appendTo(table);

            //、、、、、、、、、上面  代表 是 创建 顶部  标题 数据

            //生成组合  得到 生成的  数组 数据
            var zuheDate = step.doExchange(arrayInfor);


            if (zuheDate.length > 0) {

                //创建行
                $.each(zuheDate, function (index, item) {

                    //  1
                    // ["红色#@$2", "白色#@$3", "黑色#@$4"]
                    //2
                    //  红色#@$2,大号#@$6

                    // console.log(item);
                    var names="";
                    var ids="";
                    if(specsAllData.length == 1){
                        //取消分隔符  showitem :显示的名字    id： 二级 ID
                        names =item.substring(0,item.indexOf(fgh));
                        ids =item.substring(item.indexOf(fgh)+fgh.length,item.length);

                        var tr = $("<tr></tr>");
                        tr.appendTo(tbody);
                            var td = $("<td>" + names + "</td>");
                            td.appendTo(tr);

                    }else{
                        var td_array = item.split("#$%^&*,");
                        var tr = $("<tr></tr>");
                        tr.appendTo(tbody);
                        $.each(td_array, function (i, values) {
                            //取消分隔符
                            var showvalues =values.substring(0,values.indexOf(fgh));
                            var td = $("<td>" + showvalues + "</td>");
                            td.appendTo(tr);
                            // 红色#@$2,大号#@$6

                            names = names+values.substring(0,values.indexOf(fgh))+"_";
                            ids =ids+values.substring(values.indexOf(fgh)+fgh.length,values.length)+"_";
                        });
                        names = names.substring(0,names.length-1);
                        ids = ids.substring(0,ids.length-1);
                    }

                    var td0 = $("<td ><button type='button' name='"+names+"' class='layui-btn layui-btn-primary layui-btn-sm imgupload ddxm-show' id='"+ids+"' " +
                        "onclick=openuploadimg('"+names+"','"+ids+"')>"+
                        "<i class='layui-icon'>&#xe654;</i>"+
                        "</button>" +
                        "<img style='display: none' id=img"+ids+" title='点击更换图片'  width='100px' height='100px' onclick=openuploadimg('"+names+"','"+ids+"') >"+
                        "</td>");
                    td0.appendTo(tr);

                    // var td100 = $("<td ><button type='button' name='"+names+"' class='layui-btn layui-btn-primary layui-btn-sm imgupload ddxm-show' id='i"+ids+"' " +
                    //     "onclick=openuploadimg2('"+names+"','"+ids+"')>"+
                    //     "<i class='layui-icon'>&#xe654;</i>"+
                    //     "</button>" +
                    //     "<div style='display: none;color:red' id=img2"+ids+" title='点击更换图片'  onclick=openuploadimg2('"+names+"','"+ids+"') >已上传,点击更换" +
                    //     "</div>"+
                    //     "<input type='hidden' id=input2"+ids+">" +
                    //     "</td>");
                    // td100.appendTo(tr);
                    var td101 = $("<td ><input name=\"Txt_PriceSon\" type=\"text\" class=\"layui-input\"  autocomplete='off' value=''>" +
                        "</td>");
                    td101.appendTo(tr);
                    var td1 = $("<td ><input name=\"Txt_PriceSon\" type=\"text\" maxlength=\"8\" required lay-verify=\"required\" class=\"layui-input\"  autocomplete='off' value='-1'>" +
                        "<div style='color: red;font-size: 10px'>-1：表示库存无限制</div></td>");
                    td1.appendTo(tr);
                    var td2 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|validateMoney\" class=\"layui-input\" autocomplete='off'  value='0.00'></td>");
                    td2.appendTo(tr);
                    var td3 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|validateMoney\" class=\"layui-input\" autocomplete='off'  value='0.00'></td>");
                    td3.appendTo(tr);
                    var td4 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|validateMoney\" class=\"layui-input\" autocomplete='off'  value='0.00'></td>");
                    td4.appendTo(tr);
                    var td5 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|validateMoney\" class=\"layui-input\" autocomplete='off'  value='0.00'></td>");
                    td5.appendTo(tr);
                    var td6 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|validateMoney\" class=\"layui-input\" autocomplete='off'  value='0.00'></td>");
                    td6.appendTo(tr);
                    var td7 = $("<td ><input name=\"Txt_CountSon\" type=\"text\" maxlength=\"9\" required lay-verify=\"required|cost\" class=\"layui-input\" autocomplete='off'  value='0'></td>");
                    td7.appendTo(tr);
                });
            }

            //结束创建Table表
            // arrayColumn.pop();//删除数组中最后一项

            //合并单元格
            $(table).mergeCell({
                // 目前只有cols这么一个配置项, 用数组表示列的索引,从0开始
                cols: arrayColumn
            });
        }else{
            //未全选中,清除表格
            document.getElementById('createTable').innerHTML="";
        }
    },//合并行
    //何必 表格
    hebingFunction: function () {
        $.fn.mergeCell = function (options) {
            return this.each(function () {
                var cols = options.cols;
                for (var i = cols.length - 1; cols[i] != undefined; i--) {
                    mergeCell($(this), cols[i]);
                }
                dispose($(this));
            });
        };
        function mergeCell($table, colIndex) {

            $table.data('col-content', ''); // 存放单元格内容
            $table.data('col-rowspan', 1); // 存放计算的rowspan值 默认为1
            $table.data('col-td', $()); // 存放发现的第一个与前一行比较结果不同td(jQuery封装过的), 默认一个"空"的jquery对象
            $table.data('trNum', $('tbody tr', $table).length); // 要处理表格的总行数, 用于最后一行做特殊处理时进行判断之用
            // 进行"扫面"处理 关键是定位col-td, 和其对应的rowspan
            $('tbody tr', $table).each(function (index) {
                // td:eq中的colIndex即列索引
                var $td = $('td:eq(' + colIndex + ')', this);
                // 取出单元格的当前内容
                var currentContent = $td.html();
                // 第一次时走此分支
                if ($table.data('col-content') == '') {
                    $table.data('col-content', currentContent);
                    $table.data('col-td', $td);
                } else {
                    // 上一行与当前行内容相同
                    if ($table.data('col-content') == currentContent) {
                        // 上一行与当前行内容相同则col-rowspan累加, 保存新值
                        var rowspan = $table.data('col-rowspan') + 1;
                        $table.data('col-rowspan', rowspan);
                        // 值得注意的是 如果用了$td.remove()就会对其他列的处理造成影响
                        $td.hide();
                        // 最后一行的情况比较特殊一点
                        // 比如最后2行 td中的内容是一样的, 那么到最后一行就应该把此时的col-td里保存的td设置rowspan
                        if (++index == $table.data('trNum'))
                            $table.data('col-td').attr('rowspan', $table.data('col-rowspan'));
                    } else { // 上一行与当前行内容不同
                        // col-rowspan默认为1, 如果统计出的col-rowspan没有变化, 不处理
                        if ($table.data('col-rowspan') != 1) {
                            $table.data('col-td').attr('rowspan', $table.data('col-rowspan'));
                        }
                        // 保存第一次出现不同内容的td, 和其内容, 重置col-rowspan
                        $table.data('col-td', $td);
                        $table.data('col-content', $td.html());
                        $table.data('col-rowspan', 1);
                    }
                }
            });
        }
        // 同样是个private函数 清理内存之用
        function dispose($table) {
            $table.removeData();
        }
    },
    //组合数组
    doExchange: function (doubleArrays) {

        var len = doubleArrays.length;
        if (len >= 2) {// 只有长度 大于 2 才能够生成相应的 组合
            var arr1 = doubleArrays[0];
            var arr2 = doubleArrays[1];
            var len1 = doubleArrays[0].length;
            var len2 = doubleArrays[1].length;
            var newlen = len1 * len2;
            var temp = new Array(newlen);
            var index = 0;
            for (var i = 0; i < len1; i++) {
                for (var j = 0; j < len2; j++) {
                    temp[index] = arr1[i] + "#$%^&*," + arr2[j];
                    index++;
                }
            }
            var newArray = new Array(len - 1);
            newArray[0] = temp;
            if (len > 2) {
                var _count = 1;
                for (var i = 2; i < len; i++) {
                    newArray[_count] = doubleArrays[i];
                    _count++;
                }
            }
            // 生成 组合 数据

            var value = step.doExchange(newArray);
            return value;
        }
        else {
            return doubleArrays[0];
        }
    }
};

// 获取规格数据  如果 返回false  直接抛出，否者返回 json
/**
 * 获取规格数据
 * return false： 直接跳出，该方法 会自动提示；否者返回 json 格式数据，用于添加数据库
 *
 */
    function getSpescData(){

        var data = new Array();
        var length = specsAllData.length;
        var index = 0;
        $("#innerTable").find("tr").each(function(){
            var tdArr = $(this).children();
            var id = tdArr.eq(length).find('button').attr("id");//id
            var key_name = tdArr.eq(length).find('button').attr("name");//name
            var imgurl = tdArr.eq(length).find('img').attr('name');//规格图片
            // var imgurl2 = tdArr.eq(length+1).find('input').val();//规格详情图片
            var bar_code = tdArr.eq(length+1).find('input').val();//条形码
            var kucun = tdArr.eq(length+2).find('input').val();//库存
            var chenben = tdArr.eq(length+3).find('input').val();//成本jia
            var jianyilingshoujia = tdArr.eq(length+4).find('input').val();//建议零售价
            var lingshoujia = tdArr.eq(length+5).find('input').val();//零售价
            var tiji = tdArr.eq(length+6).find('input').val();//体积
            var zhongliang = tdArr.eq(length+7).find('input').val();//重量
            var chushixiaoliang = tdArr.eq(length+8).find('input').val();//初始销量

            //封装为 json 数据

            var obj = new Object();
            obj.key=id;
            obj.key_name=key_name;
            obj.imgurl = imgurl;
            // obj.imgurl2 = imgurl2;
            obj.store = kucun;
            obj.cost = chenben;
            obj.recommendprice = jianyilingshoujia;
            obj.price = lingshoujia;
            obj.volume = tiji;
            obj.weight = zhongliang;
            obj.initial_sales = chushixiaoliang;
            obj.bar_code = bar_code;

            data[index] = obj;
            index++;
        });
        var obj1 = new Object();
        obj1.data = JSON.stringify(data);
        obj1.data1 = JSON.stringify(specsAllData);
        return obj1;
    }

// 编辑规格---设置编辑规格数据
function setSpecsData() {

    var len = specsAllData.length;

    arrayColumn = new Array();
    arrayInfor = new Array();
    for (var k = 0; k < specsAllData.length; k++) {
        arrayColumn.push(k);
        var obg = new Array();

        arrayTile[arrayTile.length]=specsAllData[k].tname;

        for (var ins = 0; ins < specsAllData[k].value.length; ins++) {
            obg[ins] = specsAllData[k].value[ins].name+fgh+specsAllData[k].value[ins].id;
        }
        arrayInfor[k]=obg;
    }
    if (len == 0) {
        $("#tb_specs_all").empty();
        $("#tb_specs_all_02").empty();
    }

    addok01();
    step.Creat_Table();
}


// 设置公共数据
function setallData(){

    var length = specsAllData.length;
    $("#innerTable").find("tr").each(function(){
        var tdArr = $(this).children();

        //条形码
        var all_tiaoxm = $("#all_tiaoxm").val();
        if(all_tiaoxm !=""){
            tdArr.eq(length+1).find('input').val(all_tiaoxm);
        }

        //库存
        var all_kucun = $("#all_kucun").val();
        if(all_kucun !=""){
            tdArr.eq(length+2).find('input').val(all_kucun);
        }

        //成本价
        var all_chengbenjia = $("#all_chengbenjia").val();
        if(all_chengbenjia !=""){
            tdArr.eq(length+3).find('input').val(all_chengbenjia);
        }

        //原价
        var all_yuanjia = $("#all_yuanjia").val();
        if(all_yuanjia !=""){
            tdArr.eq(length+4).find('input').val(all_yuanjia);
        }

        //会员价
        var all_huiyuanjia = $("#all_huiyuanjia").val();
        if(all_huiyuanjia !=""){
            tdArr.eq(length+5).find('input').val(all_huiyuanjia);
        }

    });
}

//编辑规格---设置 规格生成的表格 数据
function setSpescData(specsAllData1){
    var data = new Array();
    var length = specsAllData.length;
    var index = 0;
    $("#innerTable").find("tr").each(function(){
        var tdArr = $(this).children();
        var id = tdArr.eq(length).find('button').attr(specsAllData1[index].key);//id
        var key_name = tdArr.eq(length).find('button').attr(specsAllData1[index].key_name);//name
        var ids = tdArr.eq(length).find('button').attr("id");//规格图片
        $("#img"+ids).attr('src',"http://picture.ddxm661.com/"+specsAllData1[index].imgurl1);
        $("#img"+ids).attr('name',specsAllData1[index].imgurl1);
        $("#img"+ids).show();
        $("#"+ids).hide();

        // var imgurl2 = tdArr.eq(length+1).find('input').val(specsAllData1[index].imgurl2);//规格详情图片
        // var ids = tdArr.eq(length+1).find('button').attr("id");
        // var imgurl = "";
        // var imgurl22 = specsAllData1[index].imgurl2;

        // var imurlarr = imgurl22.split(',');
        // for(var k =0;k<imurlarr.length;k++){
        //     imgurl = imgurl+imurlarr[k]+",";
        // }
        // if(imgurl !=""){
        //     imgurl = imgurl.substring(0,imgurl.length-1);
        // }
        ids = ids.substring(1,ids.length);
        // $("#img2"+ids).attr('name',imgurl);
        // $("#input2"+ids).val(imgurl);
        $("#img2"+ids).show();
        $("#i"+ids).hide();

        tdArr.eq(length+1).find('input').val(specsAllData1[index].bar_code);//条形码
        tdArr.eq(length+2).find('input').val(specsAllData1[index].kucun);//库存
        tdArr.eq(length+3).find('input').val(specsAllData1[index].chenben);//成本jia
        tdArr.eq(length+4).find('input').val(specsAllData1[index].jianyilingshoujia);//建议零售价
        tdArr.eq(length+5).find('input').val(specsAllData1[index].lingshoujia);//零售价
        tdArr.eq(length+6).find('input').val(specsAllData1[index].tiji);//体积
        tdArr.eq(length+7).find('input').val(specsAllData1[index].zhongliang);//重量
        tdArr.eq(length+8).find('input').val(specsAllData1[index].chushixiaoliang);//初始销量

        index++;
    });
}

$(function(){
    if( unify_specs == 2 ){
        $("#unified").empty();
        $("#more").show();
    }

    layui.use(["form"], function(){
        form = layui.form;
        form.on('radio(typeradio)', function (data) {
            if(data.value == 1){// 多规格
                $("#unified").empty();
                $("#more").show();
            }else{
                var html = '';
                html = '<div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">库存</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="store" required lay-verify="required|cost" placeholder="请输入库存" maxlength="8" value="0" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">件&nbsp;&nbsp;<span style="color: red">注意：0表示库存无限制</span></div>\n' +
                    '                </div>\n' +
                    '\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">成本</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="cost" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入成本" value="0.00" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">元</div>\n' +
                    '                </div>\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">原价</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="recommendprice" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入建议零售价" value="0.00" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">元</div>\n' +
                    '                </div>\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">会员价</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="price" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入销售价格" value="0.00" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">元</div>\n' +
                    '                </div>\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">容积</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="volume" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入容积"  value="0.00" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">m³</div>\n' +
                    '                </div>\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">重量</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="weight" required lay-verify="required|validateMoney" maxlength="9" placeholder="请输入重量"  value="0.00" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                    <div class="layui-form-mid layui-word-aux">kg</div>\n' +
                    '                </div>\n' +
                    '                <div class="layui-form-item">\n' +
                    '                    <label class="layui-form-label">初始销量</label>\n' +
                    '                    <div class="layui-input-inline">\n' +
                    '                        <input type="text" name="initial_sales" required lay-verify="required|cost" maxlength="9" placeholder="请输入initial_sales"  value="0" autocomplete="off" class="layui-input">\n' +
                    '                    </div>\n' +
                    '                </div>';
                $('#unified').append(html);
                $("#more").hide();
            }
        });

        //验证
        form.verify({
            cost: [
                /^(0|-?[1-9][0-9]*)$/  //正则表达式
                ,'库存格式不正确'  //提示信息
            ],
            validateMoney: function(val, item) {
                if(!/(^[1-9]([0-9]+)?(\.[0-9]{1,2})?$)|(^(0){1}$)|(^[0-9]\.[0-9]([0-9])?$)/.test(val)){
                    return '请输入正确的格式'
                }
            }
        });
    });
});
