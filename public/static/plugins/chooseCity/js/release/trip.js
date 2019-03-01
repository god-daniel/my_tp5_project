/**
 * 线路发布目的地操作js
 * Created by Administrator on 2016/8/23.
 */

/**
 * js获取id元素
 * @param element
 * @returns {HTMLElement}
 */
function elementById(element){
    return element?document.getElementById(element):'';
}
/**
 * js获取class元素
 * @param element
 * @returns {NodeList}
 */
function elementsByClassName(element){
    return document.getElementsByClassName?document.getElementsByClassName(element):$('.'+element);
}

function search_trip(){
    $.ajax({
        type:'get',
        url:trip.getAttribute('url'),
        data:{trip:trip.value},
        dataType:'json',
        success:function(citys_val){
            if(citys_val){
                for (var i = 0; i < citys_val.length; i++) {
                    var  first_letter = citys_val[i]['first_char'].charCodeAt(0);
                    if(citys_val[i]['is_hot'] == 1){
                        mudi_hot += '<li class="click"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>';
                    }

                    /* A-G */
                    if(first_letter >= '97' && first_letter <= '103'){
                        mudi_pingy1 +='<li class="click"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>'
                    }
                    /* H-L */
                    else if(first_letter >= '104' && first_letter <= '108'){
                        mudi_pingy2 +='<li class="click"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>'
                    }
                    /* M-T */
                    else if(first_letter >= '109' && first_letter <= '116'){
                        mudi_pingy3 +='<li class="click"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>'
                    }
                    /* U-Z */
                    else{
                        mudi_pingy4 +='<li class="click"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>'
                    }
                }
                mudi_cityareaz +=(mudi_hot+'</ul>'+mudi_pingy1+'</ul>'+mudi_pingy2+'</ul>'+mudi_pingy3+'</ul>'+mudi_pingy4+'</ul>'+'</div>');
                if($("#city_list").text()==""){
                    $("#city_list").append(mudi_cityareaz);
                }
            }
        }
    })
}
function search_trip_input(){
    if(!trip.value){$('#search_wrap').addClass('disnone');return false}
    $('#search_wrap').removeClass('disnone');
    $.ajax({
        type:'get',
        url:trip.getAttribute('url'),
        data:{trip:trip.value},
        dataType:'json',
        success:function(citys_val){
            var str='';
            if(citys_val){
                for (var i = 0; i < citys_val.length; i++) {
                    str += '<li class="click" style="float:left;width: 69px;padding: 5px 0 5px 12px;cursor:pointer"><span title="'+citys_val[i].name+'" onclick="append_trip_val(&quot;'+citys_val[i]['id']+'&quot;,&quot;'+citys_val[i]['name']+'&quot;,this)">'+citys_val[i].name+'</span></li>';
                }
            }else{
                str +='<li style="text-align:center;font-size:14px">未找到目的地</li>'
            }
            $("#search_wrap_box").html(str);
        }
    })
}
/**
 * 追加目的地操作
 * @param id
 * @param name
 * @param trip
 */
var firsts = true;
$(document).on('click','#trip_name_wrap',function(event){
    $('#trip_dialog').show().css('left',$('#trip_name_wrap').offset().left).css('top',$('#trip_name_wrap').offset().top+$('#trip_name_wrap').innerHeight());
    if(firsts)
    {
        search_trip();
        $('#trip').val('');
        firsts=false;
    }
    
    $('#city').hide();
    event.stopPropagation();
})
var triparr=[];
var triparrid=[];
var v3 = $('#trip_id').val();
var v4 = $('#trip_name').val();
if(v3){
    triparrid=v3.split(',')
    console.log(triparrid);
}
if(v4){
    triparr=v4.split(',')
    console.log(triparr);
}
function append_trip_val(id,name,trip){
    var fls = sifc(triparr,name);
    if(!fls){return false};
    triparr.push(name);
    triparrid.push(id);
    var old_html = $('#trip_name_wrap').html();
    $('#trip_name_wrap').html(old_html+'<lable class="tipsss">'+name+'<span data-f="2" data-name="'+name+'" data-id="'+id+'" class="tipsssclose">×</span></lable>').removeClass('nopad').addClass('haspad');
    $('#trip_name').attr('value',triparr);
    $('#trip_id').attr('value',triparrid);
    $('#trip_dialog').hide();
    $('#trip_name_wrap').removeClass('hello')
}


