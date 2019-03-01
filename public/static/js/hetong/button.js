/**
 * ajax POST请求
 * @param string data
 * @param string url
 */
function ajaxPost(data,url){
    $.ajax({
        type:'POST',
        url:url,
        data:data,
        dataType:'json',
        success:function(msg){
            $.MsgBox.Alert(msg.info);
            if(msg.url != null && typeof(msg.url) != "undefined"){
                setTimeout(function(){window.location.href = msg.url;},1500);
            }
        }
    })
}

/**
 * 得到返回的数组数据
 * @param string data
 * @param string url
 * @return array/string
 */
function ajaxDataPost(dataval,url){
    var return_data = '';
    $.ajax({
        type:'POST',
        url:url,
        data:dataval,
        dataType:'json',
        async:false,
        success:function(msg){
            if(msg.status == 0){
                $.MsgBox.Alert(msg.info);
                return false;
            }else{
                return_data = msg;
            }  
            if(msg.url != null && typeof(msg.url) != "undefined"){
                setTimeout(function(){window.location.href = msg.url;},1500);
            }        
        }
    })
    return return_data;
}

/**
 * 得到返回的数组数据给列表
 * @param string data
 * @param string url
 * @return array/string
 */
function ajaxDataTablePost(data,url){
    var return_data = '';
    $.ajax({
        type:'POST',
        url:url,
        data:data,
        dataType:'json',
        async:false,
        beforeSend:function(){
            //$(".secondtable tbody").html("<tr><td colspan='15'><img src='/Public/images/dateOlading.gif'></td></tr>");
        },
        success:function(msg){
            if(msg.url){
               setTimeout(function(){window.location.href = msg.url;},1500);
            }
           return_data = msg;            
        }
    })
    return return_data;
}

/**
 * ajax GET请求
 * @param string url
 */
function ajaxGet(url){
    $.ajax({
        type:"GET",
        url:url,
        dataType:"json",
        success:function(msg){
            $.MsgBox.Alert(msg.info);
            if(msg.url){
                setTimeout(function(){window.location.href = msg.url;},1500);
            }
        }
    })
}

/**
 * ajax GET得到返回的数组数据
 * @param string url
 * @return string/array
 */
function ajaxDataGet(url){
    var return_data = '';
    $.ajax({
        type:"GET",
        url:url,
        dataType:"json",
        async:false,
        success:function(msg){
            if(msg.status == 0){
                $.MsgBox.Alert(msg.info);
                return false;
            }else{
                return_data = msg.info;
            }
            if(msg.url){
                setTimeout(function(){window.location.href = msg.url;},1500);
            }
        }
    })
    return return_data;
}

/**
 * 查看详情
 * @param string url
 */
function read_info(url){
    window.location.href = url;
}

//全选
//复选框事件  
//全选、取消全选的事件  
function selectAll(obj){
    if($('select[name="ut_id"] option:selected').val()==0)
    {
        $.MsgBox.Alert("请先选择旅行社");
        $("#all").attr("checked", false); 
    }else{
         if ($("#all").attr("checked")) {  
        $(".sibcheckbox").attr("checked", true);  
        } else {  
           $(".sibcheckbox").attr("checked", false); 
           $("#all").parents('li').nextAll().find('input').each(function(i){
                userChooseArr.remove($(this).val()+'%'+$(this).next().text());
           })
        }  
        showUserChoose()
    }
}

//子复选框的事件  
function setSelectAll(obj){ 
    if (!$(".sibcheckbox").checked) {  
        $("#all").attr("checked", false);
        if($(obj).attr('checked')!='checked')
        {
           
            userChooseArr.remove($(obj).val()+'%'+$(obj).next().text());
            console.log(userChooseArr);
        }
    }  
    var chsub = $(".sibcheckbox").length;   
    var checkedsub = $(".sibcheckbox:checked").length; 
    if (checkedsub == chsub) {  
        $("#all").attr("checked", true);  
    }  
    showUserChoose();
}

$(document).on('mouseover','.storeBacktbody .borderL',function(){
         $('.storeBacktbody .borderL').each(function(i){
             $(this).removeClass('mnuehover').css('border-left-color','#ccc');
         })
         var opi = $(this).find('.otherOperating');
         if($(this).find('p').length==0)
         {
            opi.css({'display':'none'})
         }
        $(this).addClass('mnuehover');
        
        opi.css({'top':$(this).innerHeight()});
    })