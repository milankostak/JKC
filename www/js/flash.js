var initFlashes;
$(document).ready(function() {
	initFlashes = function() {
		$(".flash").addClass("alert");
		$(".flash").append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
		setTimeout(function() {
			$(".flash.success").slideUp();
		}, 4000);
	};
	initFlashes();
});
