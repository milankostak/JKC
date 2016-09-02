//https://github.com/vojtech-dobes/nette.ajax.js
$(function() {
	$.nette.init();
	$.nette.ext("name", {
		before: function () {
			$("#ajax_loader").show();
			$(".flash.ajax").remove();
		},
		complete: function () {
			$(".flash.ajax").slideDown().click(function() {
				$(this).hide();
			});
			$("#ajax_loader").hide();
			var timeout = setTimeout(function(){
				$(".flash.ajax.success").slideUp();
			}, 3000);
		}
	});
	$(".ajax").click(function() {
		if (typeof tinyMCE !== "undefined") tinyMCE.triggerSave();
	});
});
