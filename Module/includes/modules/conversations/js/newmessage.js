// JavaScript Document

$(function() {
	
	$("#recipients").tokenInput(CMS_URL.langRoot + 'users/autocomplete', {preventDuplicates: true, minChars: 2, theme: 'facebook', propertyToSearch: 'nickname'});

});