$(document).ready(function() {
  $("ul#article_menu li").each(function() {
		$(this).find("ul").css("display", "none");
		$(this).find("a.year").first().click(function() {
			$("ul#article_menu ul").slideUp(200);
			$(this).next().slideDown(200);
			return false;
		});
	});
	$("ul#article_menu ul").first().css("display", "");
});

function setActive() {
	$("ul#article_menu ul").first().css("display", "none");
	$("a.active").next().css("display", "");
}
