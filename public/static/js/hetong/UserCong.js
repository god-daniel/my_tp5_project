$(document).on('click','.history label',function(){
	var type = $(this).attr('type');
	var url = $(this).attr('data-url');
		if(!$(this).hasClass('active'))
		{
			if($(this).attr('data-num')>0){
				$.post(UserCongUrl,{id:$(this).attr('data-id'),num:$(this).attr('data-num')},function(data){
					var html = '';
					var html2 = '';
					var data = data.info;
					for(var i = 0;i<data.length;i++)
					{
						if(type=='hasprice')
						{
							html2 = '<td class="textac">'+data[i].price+'</td>';
						}
						html+='<tr>'+
							  '<td class="textac">'+data[i].name+'</td>'+
							  '<td class="textac">'+data[i].sex+'</td>'+html2+
							  '<td class="textac">'+data[i].age+'</td>'+
							  '<td class="textac">'+data[i].card_name+'</td>'+
							  '<td class="textac">'+data[i].card_number+'</td>'+
							  '<td class="textac">'+data[i].tel+'</td>'+
							  '<td class="textac"><p style="width:300px" class="textoverflow textoverflow-init textac cp">'+data[i].remark+'</p></td>'+
							  '</tr>'	
					}
					$('#history-user').html(html);
					$('#daochu').find('a').attr('href',url);
				});
				$('#daochu').removeClass('disnone');
				flowtexts();
			}else{
				$('#history-user').html('<tr><td colspan="9" class="textac">暂无数据</td></tr>');
				$('#daochu').addClass('disnone');
			
			}
			$(this).addClass('active').siblings('label').removeClass('active');
		}		


	
})