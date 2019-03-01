var Reg = {
	people_num:/^([1-9]\d{0,}|0)$/,//人数必须是正整数。
	price:/^\d+(\.\d{1,2})?$/,//价格
	notnull:/^\S+$/,//非空
	card:/^[A-Za-z0-9]+$/,//证件号
	age:/^[1-9]\d*$/,//年龄必须是正整数。
	phone:/^(13[0-9]|14[0-9]|15[0-9]|18[0-9])\d{8}$/i,//电话
	bankcard:/^(\d{16}|\d{19})$///银行卡
}

//正整数验证（人数）
$.validator.addMethod('isPeople',function(value,element){
	return this.optional(element) || (Reg.people_num.test(value));
},'必须为正整数')
//价格验证
$.validator.addMethod('isPrice',function(value,element){
	return this.optional(element) || (Reg.price.test(value));
},'价格输入有误')
//非空
$.validator.addMethod('isNotnull',function(value,element){
	return this.optional(element) || (Reg.notnull.test(value));
},'不能为空')
//证件
$.validator.addMethod('isCard',function(value,element){
	return this.optional(element) || (Reg.card.test(value));
},'证件有误')
//年龄
$.validator.addMethod('isAge',function(value,element){
	return this.optional(element) || (Reg.age.test(value));
},'年龄有误')
//手机号
$.validator.addMethod('isPhone',function(value,element){
	return this.optional(element) || (Reg.phone.test(value));
},'手机号有误')
//银行卡号
$.validator.addMethod('isBankcard',function(value,element){
	return this.optional(element) || (Reg.bankcard.test(value));
},'银行卡号有误')