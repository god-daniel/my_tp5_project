(function () {
    $.MsgBox = {
        Alert: function (msg, title) {
            title = title ? title : "温馨提示";
            GenerateHtml("alert", title, msg);
            btnOk(); //alert只是弹出消息，因此没必要用到回调函数callback
            btnNo();
        },
        Confirm: function (msg, callback, title) {
            title = title ? title : "温馨提示";
            GenerateHtml("confirm", title, msg);
            btnOk(callback);
            btnNo();
        }
    }

    //生成Html
    var GenerateHtml = function (type, title, msg) {
        var _html = "";

        _html += '<div id="mb_box"></div><div id="mb_con"><span id="mb_tit">' + title + '</span>';
        _html += '<a id="mb_ico" title="关闭">×</a><div id="mb_msg">' + msg + '</div><div id="mb_btnbox">';

        if (type == "alert") {
            _html += '<input id="mb_btn_ok" class="cp btnsure" type="button" value="确定" />';
        }
        if (type == "confirm") {
            _html += '<input id="mb_btn_ok" class="cp btnsure radius3px" type="button" value="确定" />';
            _html += '<input id="mb_btn_no" class="cp btncancel ml20 radius3px" type="button" value="取消" />';
        }
        _html += '</div></div>';

        //必须先将_html添加到body，再设置Css样式
        $("body").append(_html); GenerateCss();
    }

    //生成Css
    var GenerateCss = function () {

        $("#mb_box").css({ width: '100%', height: '100%', zIndex: '99999', position: 'fixed', top: '0', left: '0'
        });

        $("#mb_con").css({ zIndex: '999999', width: '400px', position: 'fixed',
            backgroundColor: '#fff',boxShadow: '1px 1px 28px #ccc,1px 1px 23px #948787',border:'1px solid #b32402'
        });

        $("#mb_tit").css({ display: 'block', fontSize: '14px', color: '#fff', padding: '10px 15px',
            backgroundColor: '#b32402', fontWeight: 'bold'
        });

        $("#mb_msg").css({ padding: '20px', lineHeight: '20px',
            borderBottom: '1px dashed #d5d5d5', fontSize: '13px',color:'#666'
        });

        $("#mb_ico").css({ display: 'block', position: 'absolute', right: '10px', top: '11px',
            width: '18px', height: '18px', textAlign: 'center',
            lineHeight: '16px', cursor: 'pointer', fontFamily: 'Tahoma',color:'#fff'
        });

        $("#mb_btnbox").css({ margin: '10px 0 10px 0', textAlign: 'center' });


        //右上角关闭按钮hover样式
        $("#mb_ico").hover(function () {
            $(this).css({ color: '#fff' });
        }, function () {
            $(this).css({ color: '#fff' });
        });

        var _widht = document.documentElement.clientWidth; //屏幕宽
        var _height = document.documentElement.clientHeight; //屏幕高

        var boxWidth = $("#mb_con").width();
        var boxHeight = $("#mb_con").height();

        //让提示框居中
        $("#mb_con").css({ top: (_height - boxHeight) / 2 + "px", left: (_widht - boxWidth) / 2 + "px" });
    }


    //确定按钮事件
    var btnOk = function (callback) {
        $("#mb_btn_ok").click(function () {
            
            $("#mb_box,#mb_con").remove();
            if (typeof (callback) == 'function') {
                callback();
                
            }
            if(flg=='login'){
                $('.loginsubmitt').attr('onclick','loginpost()');
                $('body').attr('onkeydown','keyd()');
            }
             
        });
    }

    //取消按钮事件
    var btnNo = function () {
        $("#mb_btn_no,#mb_ico").click(function () {
            $("#mb_box,#mb_con").remove();
        });
    }
})();
//关闭弹层
function closeList(str){
    $('.'+str).css('display','none');
    if(str=="guanzuWrap")
    {
        $('.storeGZ').html('已关注')
    }
    if(str=="souchangWrap")
    {
        pthis.html('<i class="lineIcon"></i>已收藏');
    }
}
$(function(){
    $('.maskBox').css({'height':$(window).height(),'position':'fixed','top':'0px','left':'0px','right':'0px'});
})