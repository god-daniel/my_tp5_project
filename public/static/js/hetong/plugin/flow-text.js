//type = 1;=>纯文本名称
//type = 2;=>结算价(价格名：，单价：)
//type = 3;=>结算总价(价格名：，单价：，数量：)
//type = 4;=>编号
//type = 5;=>建议销售价(价格名：，单价：)
//type = 6;=>旅行社审核信息
//type = 7;=>加盟的旅行社
//type = 8;=>旅行社相关时间
$.fn.flowText = function(options)
{
	var defaults = {
		'top':0,
		'left':0,
		'html':'',
		'type':1
	};
	var settings = $.extend(defaults,options);
	//创建html:
	var _html = '';
	if(settings.type==1||settings.type==4)
	{
		 _html = "<div class='flowText disnone'>"+settings.html+"<img src='/Public/Images/grand-m.png'></div>";
	}else if(settings.type==2||settings.type==3||settings.type==5||settings.type==6||settings.type==7||settings.type==8){
		 _html = "<div class='flowText disnone'><table cellspacing='0' cellpadding='0' style='font-size:14px;color:#555'></table><img src='/Public/Images/grand-m.png'></div>";
	}else{
	}
	$('body').append(_html);
	$('.flowText').css({'top':settings.top,'left':settings.left});
}

	function flowtexts(){
		$('.textoverflow-init').each(function(i){
			if($(this).attr('init-type')==4){
				$(this).flowText({'html':$(this).next().html(),'type':$(this).attr('init-type')});
			}else{
				$(this).flowText({'html':$(this).html(),'type':$(this).attr('init-type')});
			}
		})
	}
$(function(){
	flowtexts();
})
$(document).on('click','.textoverflow-init',function(){
	var url = $(this).attr('data-url');
	var item = $(this).index('.textoverflow-init');
	$('.flowText').eq(item).css({'top':$(this).offset().top+35,'left':$(this).offset().left-(108-($(this).innerWidth()/2)),'width':'200px'}).toggleClass('disnone').siblings('.flowText').addClass('disnone');
	if($(this).attr('init-type')==1)
	{
		$('.flowText').eq(item).html($(this).html()+"<img src='/Public/Images/grand-m.png'>")
	}
	if($(this).attr('init-type')==4)
	{
		$('.flowText').eq(item).html($(this).next().html()+"<img src='/Public/Images/grand-m.png'>")
		.css({'width':'400px','left':$(this).offset().left-(208-($(this).innerWidth()/2)),'top':$(this).offset().top+30});
	}
	if($(this).attr('init-type')==2||$(this).attr('init-type')==3||$(this).attr('init-type')==5||$(this).attr('init-type')==6||$(this).attr('init-type')==7||$(this).attr('init-type')==8)
	{
		$('.flowText').css({'width':'300px','left':$(this).offset().left-(158-($(this).innerWidth()/2)),'top':$(this).offset().top+34});
		if($(this).attr('isfuck'))
		{
			$('.flowText').css('width','350px').css('left',$(this).offset().left-(187-($(this).innerWidth()/2)));
		}
		//$.ajax({})
		if($(this).attr('init-type')==2)
		{
			//结算价
			var tr_html="";
			$.ajax({
				url:$(this).attr('data-url'),
				dataType:'json',
				type:'get',
				async:false,
				success:function(msg){
					var data = msg;
					tr_html = '<tr><th>价格名</th><th>单价</th></tr>';
					for(i in data)
					{
						tr_html+='<tr><td>'+data[i]['price_msg']+'</td><td>'+data[i]['lt_price']+'</td></tr>';
					}
				}
			})
		}else if($(this).attr('init-type')==3){
			//结算总价
			var tr_html="";
			$.ajax({
				url:$(this).attr('data-url'),
				data:{'oid':$(this).attr('data-id')},
				dataType:'json',
				type:'post',
				async:false,
				success:function(msg){
					var data = msg;
					tr_html = '<tr><th>价格名</th><th>单价</th><th>人数</th></tr>';
					for(i in data)
					{
						tr_html+='<tr><td>'+data[i]['key1']+'</td><td>'+data[i]['val1']+'</td><td>'+data[i]['pel_num']+'</td></tr>';
					}
				}
			})
		}else if($(this).attr('init-type')==5){
			//建议销售价
			var data = [{'key1':'成人价','val1':'￥1890'},{'key1':'儿童价','val1':'￥790'}]
			var tr_html = '<tr><th>价格名</th><th>单价</th></tr>';
			for(i in data)
			{
				tr_html+='<tr><td>'+data[i]['key1']+'</td><td>'+data[i]['val1']+'</td></tr>';
			}
		}else if($(this).attr('init-type')==6){
			//审核情况
			var isfuck = $(this).attr('isfuck');
			var nextspan = $(this).next().text();
			var tr_html="";
			$.ajax({
				url:$(this).attr('data-url'),
				dataType:'json',
				type:'get',
				async:false,
				success:function(msg){
					var data = msg;
					var isfuckTD = "";
					if(isfuck)
					{
						tr_html = '<tr><th style="border-bottom:1px solid #e6e6e6"></th><th style="border-bottom:1px solid #e6e6e6">旅行社</th><th style="border-bottom:1px solid #e6e6e6">审核状态</th></tr>';
						isfuckTD = '<td rowspan='+data.length+' style="border-right:1px solid #e6e6e6;padding:0 3px;border-bottom:none">'+nextspan+'</td>'
					}else{
						tr_html = '<tr><th style="border-bottom:1px solid #e6e6e6">旅行社</th><th style="border-bottom:1px solid #e6e6e6">审核状态</th></tr>';
					}
					for(i in data)
					{
						var strs = null;
						if(data[i]['verify_state']==0)
						{
							strs = '未通过';
						}
						if(data[i]['verify_state']==1)
						{
							strs = '已通过';
						}
						if(data[i]['verify_state']==5)
						{
							strs = '待审核';
						}
                        if(data[i]['verify_state']==10){
                            strs = '已禁售';
                        }
                        if(i==0){
                        	tr_html+='<tr>'+isfuckTD+'<td>'+data[i]['travel_name']+'</td><td>'+strs+'</td></tr>';
                        }else{
                        	tr_html+='<tr><td>'+data[i]['travel_name']+'</td><td>'+strs+'</td></tr>';
                        }
						
					}
				}
			})
		}else if($(this).attr('init-type')==7){
			//加盟旅行社
			var tr_html="";
			$.ajax({
				url:$(this).attr('data-url'),
				dataType:'json',
				data: {user_id:$(this).attr('data-id')},//
				type:'post',
				async:false,
				success:function(msg){
					var data = msg;
					tr_html = '<tr><th>旅行社</th></tr>';
					if(msg)
					{
						for(i in data)
						{
							tr_html+='<tr><td>'+data[i]['name']+'</td></tr>';
						}
					}else{
						tr_html+='<tr><td>暂无加盟旅行社</td></tr>';
					}
					
				}
			})
		}else{
			//旅行社相关时间
			var tr_html="";
			$.ajax({
				url:$(this).attr('data-url'),
				dataType:'json',
				data: {oid:$(this).attr('data-id')},//
				type:'post',
				async:false,
				success:function(msg){
					var data = msg;
					tr_html = '<tr><th>门店支付时间</th><th>金额</th></tr>';
					if(msg)
					{
						for(i in data)
						{
							tr_html+='<tr><td>'+data[i]['key1']+'</td><td>'+data[i]['val1']+'</td></tr>';
						}
					}else{
						tr_html+='<tr><td colspan="2">暂无数据</td></tr>';
					}
				}
			})
		}
		$('.flowText').eq(item).find('table').html(tr_html);
	}
})
$(document).on('hover','.header-s .haschild',function(){
	var p_len = $(this).find('p').length;
	if(p_len>3)
	{
		$(this).find('.childlink').css('width','150px');
	}
	if(p_len>6)
	{
		$(this).find('.childlink').css('width','225px');
	}
})
//获取相关编号