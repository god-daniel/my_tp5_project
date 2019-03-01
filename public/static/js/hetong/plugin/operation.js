$(document).on('click','.operation-init',function(){
	$(this).parents('tr').next().toggleClass('disnone').siblings('.operationTr').addClass('disnone');
	$('.flowText').addClass('disnone');
})

//模拟select
$(document).on('click','.sim-select',function(){
	$(this).find('.sim-option-box').toggleClass('disnone');
})
$(document).on('click','.sim-option-box p',function(){
	if($(this).parents('.sim-select').attr('init-type')==1)
	{
		var parms = $(this).parent().find('input');
		if(parms.val()!=$(this).attr('data'))
		{
			parms.val($(this).attr('data'));
			$(this).parents('form').submit();
		}
		$(this).parents('.sim-select').find('span').eq(1).html($(this).html());
	}else{
		$(this).parents('form').find('input[name="keytype"]').val($(this).attr('key-type'));
		$(this).parents('.sim-select').find('span').eq(0).html($(this).html());
	}
})
