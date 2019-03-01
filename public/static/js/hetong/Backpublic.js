// 关闭浮沉
function closeMask(str){
	$('.'+str).css('display','none');
	$('.errorinfo').html('');
}
//打开浮层

function openMask(str,obj){
	$('.'+str).css('display','block').find('.maskBox').css({'height':window.screen.height,'position':'fixed','top':'0px','left':'0px','right':'0px'});
}