// 正则表达式
var notnone = /^\S+$/;


//删除角色
function delrole(url){
	//$.MsgBox.Alert("请确定第一步信息");Confirm
	//$.MsgBox.Confirm
	 $.MsgBox.Confirm("您确定要删除吗？", function () {
            //删除逻辑
            ajaxGet(url);
        },"删除角色")
}
//新增角色
function addrole(url){
	 var vals='';
	 $('.menubox:checked').each(function(i){
	 	vals+=($(this).val()+',');
	 })
	 $('input[name="menu_group"]').val(vals.substring(0,vals.length-1))
	 var name =  $(".addroleform").find("input[name='name']").val()
	 if(!notnone.test(name))
	 {
	 	$(".addroleform").find('.errorinfo').html('请填写名称！');
	 	return false
	 }
	 var data = $('.addroleform').serialize();	
	 $.ajax({
        type:'POST',
        url:url,
        data:data,
        dataType:'json',
        success:function(msg){
            $.MsgBox.Alert(msg.info);
            if(msg.status==1)
            {
            	closeMask('modifyjuesemaskwrap');
            	setTimeout(function(){window.location.reload()},1500);
            }
        }
    })
}
//修改角色
function edit(id){
	var data = ajaxDataPost("id="+id,'editrole');
	$('.modifyjuesemaskwrap input[name="id"]').val(id);
	var info = (data.info).role_info;
	var menulist = (data.info).menu_list;
	//当有功能选项时
	if(menulist){
		var htmls = '';
		var leab = '';
		for (var i = 0; i < menulist.length; i++) {
			htmls+='<tr><td class="textAR maskinfofont"><div class="mb20" data-id='+menulist[i].id+'>'+menulist[i].name+'：</div></td>';
			leab='';
			if(menulist[i].child_list)
			{
				for (var j = 0; j < menulist[i].child_list.length; j++) {
					leab+='<label><input class="vm fuck"   type="checkbox" value="'+(menulist[i].child_list)[j].id+'" /><span class="maskinfofont">'+(menulist[i].child_list)[j].name+'</span></label>';
				};
			}
			htmls+='<td colspan="2"><div class="mb20">'+leab+'</div></td></tr>'
		};
		$('.menutbody').html(htmls);
	}
	$('.fuck').each(function(i){
		for (var i = 0; i < (info.operation).length; i++) {
			if((info.operation)[i]==$(this).val()){
				$(this).attr('checked','checked');
			}
		};
	});

	if(info.status==1)
	{
		$('.modifyjuesemaskwrap input[name="status"]').eq(0).attr('checked','checked');
	}else{
		$('.modifyjuesemaskwrap input[name="status"]').eq(1).attr('checked','checked');	
	}
	$('.modifyjuesemaskwrap input[name="name"]').val(info.name);
	openMask('modifyjuesemaskwrap');
}


function modifyrole(url){
	
	 var name = $(".modifyform").find("input[name='name']").val()
	  var vals='';
	 $('.modifyjuesemaskwrap .fuck:checked').each(function(i){
	 	vals+=($(this).val()+',');
	 })
	 $('.modifyjuesemaskwrap input[name="menu_group"]').val(vals.substring(0,vals.length-1));
	 var data = $(".modifyform").serialize();
	 if(!notnone.test(name))
	 {
	 	$(".modifyform").find('.errorinfo').html('请填写名称！');
	 	return false
	 }	
	 $.ajax({
        type:'POST',
        url:url,
        data:data,
        dataType:'json',
        success:function(msg){
        	$.MsgBox.Alert(msg.info);
        	if(msg.status==1)
        	{
        		closeMask('modifyjuesemaskwrap');
        		setTimeout(function(){window.location.reload()},1500);
        	}else{

        	}
        }
    })
}