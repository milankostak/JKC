//https://github.com/vojtech-dobes/nette.ajax.js
$(function() {
	$.nette.init();
	$('a.ajax').click(function() {
		$("#ajax_loader").show();
	});
});
