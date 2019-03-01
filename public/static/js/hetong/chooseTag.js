//处理已选条件的
$(document).on('click','.chooseTapes',function(){
	var str = '/'+$(this).attr('data-column')+'/'+$(this).attr('data-number');
	var url = window.location.href;
	url = url.replace(str,'');
	window.location.href =url;
})