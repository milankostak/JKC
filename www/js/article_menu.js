$(document).ready(function() {
	$("ul.article_menu a.year").click(function() {
		if ($(this).next().hasClass("active2")) {
			$(this).next().removeClass("active2").slideUp(200);
		} else {
			$("ul.article_menu ul.active2").removeClass("active2").slideUp(200);
			$(this).next().addClass("active2").slideDown(200);
		}
		return false;
	});
	$("ul.article_menu ul:visible").addClass("active2");

	//if no active item, show the first one
	$submenu = $("ul.article_menu a.active + ul");
	if ($submenu.length == 0) {
		$("ul.article_menu ul").first().show().addClass("active2");
	}
});
