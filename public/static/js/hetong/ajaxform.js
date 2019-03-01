/**
 * Created by Administrator on 2016/2/15.
 */
function ajax_post(url, data, dialog_id) {
    if(dialog_id){
        dialog_id.hide();
    }
    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        dataType: 'json',
        success: function (json) {
            if (json.info) {
                $.MsgBox.Alert(json.info);
                reload(1500);
            } else {
                assign(json);
            }
        }
    })
}

function ajax_post_dialog(url,data,dialog_id){
    dialog_id.hide();
    ajax_post(url,data);
}


function ajax_get(url) {
    $.ajax({
        type: 'GET',
        url: url,
        dataType: 'json',
        success: function (json) {
            if (json.info) {
                $.MsgBox.Alert(json.info);
                reload(1500);
            } else {
                assign(json);
            }
        }
    })
}

//删除操作
function ajax_get_confirm(alter, url) {
    $.MsgBox.Confirm(alter, function(){
        ajax_get(url);
    });
}

function reload(time) {
    setTimeout(function () {
        window.location.reload();
    }, time);
}
