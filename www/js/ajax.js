//https://github.com/vojtech-dobes/nette.ajax.js
$(document).ready(function() {
	$.nette.init();
	$.nette.ext("name", {
		before: function () {
			$("#ajax_loader").show();
			$(".flash.ajax").remove();
		},
		complete: function () {
			$(".flash.ajax").slideDown();
			$("#ajax_loader").hide();
			initFlashes();
		}
	});
	$(".ajax").click(function() {
		if (typeof tinyMCE !== "undefined") tinyMCE.triggerSave();
	});
});
