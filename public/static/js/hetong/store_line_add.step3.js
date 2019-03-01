$(function(){
    //线路图片上传操作
    $('#image_upload').change(function(){
        var url = this.getAttribute('uri');
        var id = this.getAttribute('id');
        var line_id = document.getElementById('lid').value;
        $.ajaxFileUpload({
            url : url,
            secureuri : false,
            fileElementId : id,
            dataType : 'json',
            data : {name : id,lid : line_id},
            success : function (data, status) {
                console.log(data,status);
                if (typeof(data.error) != 'undefined') {
                    alert(data.error);
                } else {
                    console.log(data.url);
                    var layout ='<span class="item"><span class="dels" onclick="ajax_get_confirm(\'您确认要删除该图片吗？\',\''+data.url+'\')">删除</span><img class="vt" src="'+data.thumb_name+'" width="298" height="198"></span>';
                    $('div>.pic').append(layout);
                }
                $.getScript( '/Public/Js/store_line_add.step3.js');
            },
            error : function (data, status, e) {
                alert(e);
                $.getScript( '/Public/Js/store_line_add.step3.js');
            }
        });
        return false;
    });
    //行程文档上传操作
    $('#file_upload').change(function(){
        var url = this.getAttribute('uri');
        var id = this.getAttribute('id');
        var upload_name = $('#upload_name');
        var loading = '<img src="/Public/Js/dialog/images/loading.gif">';
        upload_name.html(loading);
        $.ajaxFileUpload({
            url : url,
            secureuri : false,
            fileElementId : id,
            dataType : 'json',
            data : {name : id},
            success : function (data, status) {
                if (typeof(data.error) != 'undefined') {
                    alert(data.error);
                } else {
                    upload_name.html(data.file_name+'<img src="/Public/Js/dialog/images/right.gif">');
                    $('input[name="file_name"]').val(data.file_name);
                    $('input[name="file_path"]').val(data.file_path);
                    $('#submits').css({'background':'#37ba65','cursor':'pointer'});
                }
                $.getScript( '/Public/Js/store_line_add.step3.js');
            },
            error : function (data, status, e) {
                alert(e);
                $.getScript( '/Public/Js/store_line_add.step3.js');
            }
        });
        return false;
    });
    //删除图片操作
    $('*[nctype="del"]').click(function(){
        var image_id = this.getAttribute('data-id');
        var url = "<?php echo U('image_del');?>";
        console.log(image_id,url);
    });

    // 商品图片ajax上传
    $('.ncsc-upload-btn').find('input[type="file"]').unbind().bind('change', function(){
        var id = this.getAttribute('id');
        var url = this.getAttribute('url');
        ajaxFileUpload(id,url);
    });
    //凸显鼠标触及区域、其余区域半透明显示
    //$(".container > div").jfade({
    //    start_opacity:"1",
    //    high_opacity:"1",
    //    low_opacity:".5",
    //    timing:"200"
    //});
    //浮动导航  waypoints.js
    //$("#uploadHelp").waypoint(function(event, direction) {
    //    $(this).parent().toggleClass('sticky', direction === "down");
    //    event.stopPropagation();
    //});
    // 关闭相册
    $('a[nctype="close_album"]').click(function(){
        $(this).hide();
        $(this).prev().show();
        $(this).parent().next().html('');
    });
    // 绑定点击事件
    $('div[nctype^="file"]').each(function(){
        if ($(this).prev().find('input[type="hidden"]').val() != '') {
            selectDefaultImage($(this));
        }
    });
});

// 图片上传ajax
function ajaxFileUpload(id, url) {
    $('img[nctype="' + id + '"]').attr('src', "/Public/images/loading.gif");

    $.ajaxFileUpload({
        url : url,
        secureuri : false,
        fileElementId : id,
        dataType : 'json',
        data : {name : id},
        success : function (data, status) {
                    if (typeof(data.error) != 'undefined') {
                        alert(data.error);
                        $('img[nctype="' + id + '"]').attr('src',DEFAULT_GOODS_IMAGE);
                    } else {
                        $('input[nctype="' + id + '"]').val(data.name);
                        $('img[nctype="' + id + '"]').attr('src', data.thumb_name);
                        selectDefaultImage($('div[nctype="' + id + '"]'));      // 选择默认主图
                    }
                    $.getScript(SHOP_RESOURCE_SITE_URL+ '/js/store_goods_add.step3.js');
                },
        error : function (data, status, e) {
                    alert(e);
                    $.getScript(SHOP_RESOURCE_SITE_URL+ '/js/store_goods_add.step3.js');
                }
    });
    return false;

}

// 选择默认主图&&删除
function selectDefaultImage($this) {
    // 默认主题
    $this.click(function(){
        $(this).parents('ul:first').find('.show-default').removeClass('selected').find('input').val('0');
        $(this).addClass('selected').find('input').val('1');
    });
    // 删除
    $this.parents('li:first').find('a[nctype="del"]').click(function(){
        $this.unbind('click').removeClass('selected').find('input').val('0');
        $this.prev().find('input').val('').end().find('img').attr('src', DEFAULT_GOODS_IMAGE);
    });
}

// 从图片空间插入主图
function insert_img(name, src, color_id) {
    var $_thumb = $('ul[nctype="ul'+ color_id +'"]').find('.upload-thumb');
    $_thumb.each(function(){
        if ($(this).find('input').val() == '') {
            $(this).find('img').attr('src', src);
            $(this).find('input').val(name);
            selectDefaultImage($(this).next());      // 选择默认主图
            return false;
        }
    });
}