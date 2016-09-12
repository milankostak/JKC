var updateFormValues;

$(document).ready(function() {
	var formValues = [];

	window.onbeforeunload = function(e) {
		// if submiting form, then do nothing
		if (e.srcElement.activeElement.type == "submit") {
			// http://stackoverflow.com/a/29627642
			return undefined;
		}

		for (var el in formValues) {
			var val = "";
			switch (formValues[el][1]) {
				case "checkbox": val = $("#"+el).is(":checked");
						break;
				case "select": val = $("#"+el).find(":selected").text();
						break;
				case "textarea":  if (typeof tinyMCE !== "undefined") tinyMCE.triggerSave(); 
				default: val = $("#"+el).val();
						break;
			}
			if (val != formValues[el][0]) {
				return false;
			}
		}
	};

	updateFormValues = function() {

		$("form input[type=checkbox]").each(function() {
			var val = $(this).is(":checked");
			var id = $(this).attr("id");
			formValues[id] = [val, "checkbox"];
		});
		$("form input[type=text]").each(function() {
			var val = $(this).val();
			var id = $(this).attr("id");
			formValues[id] = [val, "text"];
		});
		$("form select").each(function() {
			var val = $(this).find(":selected").text();
			var id = $(this).attr("id");
			formValues[id] = [val, "select"];
		});
		$("form textarea").each(function() {
			if (typeof tinyMCE !== "undefined") tinyMCE.triggerSave();
			var val = $(this).val();
			var id = $(this).attr("id");
			formValues[id] = [val, "textarea"];
		});

	};
	updateFormValues();
});
