/**
 * Created by Administrator on 2016/3/5.
 */
function ajax_get(url){
    $.ajax({
        type:'GET',
        url:url,
        dataType:'json',
        success:function(json){
            if(json.info){
                $.MsgBox.Alert(json.info);
                setTimeout(function(){window.location.reload()},2000);
            }else{
                assign(json);
            }
        }
    })
}

function ajax_get_confirm(alter,url){
    $.MsgBox.Confirm(alter,function(){
        ajax_get(url);
    })
}

function ajax_post(url,data){
    $.ajax({
        type:'POST',
        url:url,
        data:data,
        dataType:'json',
        success:function(json){
            if(json.info){
                $.MsgBox.Alert(json.info);
                setTimeout(function(){window.location.reload()},2000);
            }else{
                assign(json);
            }
        }
    })
}