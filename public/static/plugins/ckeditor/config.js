/*
Copyright (c) 2003-2011, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';
	config.language = 'zh-cn';
	config.uiColor = '#FFF';
	config.width = 'auto';
	config.height='300px';
	config.skin='office2003';
	config.toolbar ='MyToolbar'; //工具栏风格（基础'Basic',全能'Full',自定义）
	config.toolbarCanCollapse = false;
	config.resize_enabled = false;
	config.filebrowserUploadUrl='/Admin/Uploads/upload';
	
	//自定义工具栏
	config.toolbar_MyToolbar = 
		[{name:'document',
		items:['Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates']},
		{name:'clipboard',items:['Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo']},
		{name:'editing',items:['Find','Replace','-','SelectAll','-','SpellChecker','Scayt']},
		{name:'forms',items:['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField']},'/',
		{name:'basicstyles',items:['Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat']},
		{name:'paragraph',items:['NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','CreateDiv','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl']},
		{name:'links',items:['Link','Unlink','Anchor']},
		{name:'insert',items:['Image','Flash','Table','HorizontalRule','Smiley','SpecialChar','PageBreak','Iframe']},'/',
		{name:'styles',items:['Styles','Format','Font','FontSize']},
		{name:'colors',items:['TextColor','BGColor']},
		{name:'tools',items:['Maximize','ShowBlocks']}
		];
};
