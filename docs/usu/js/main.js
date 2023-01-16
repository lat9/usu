$(function() {
	$("#top-links a").button();
	$("#tabs").tabs();
	
	// Iterate over the tab content, add the target index to any link pointing
	// to a tab, and when clicked have the tab index open.
	var index = 0;
	$("#tabs > ul > li > a").each(function() {
		var href = $(this).attr("href");
		if(href != undefined) {
			$("#tabs .ui-tabs-panel a[href='" + href + "']")
				.data("index", index)
				.on("click", function(e){
					e.preventDefault();
					$("#tabs").tabs("option", "active", $(this).data("index"));
					return false;
				});
		}
		index++;
	});

	// Syntax Highlighter
	SyntaxHighlighter.defaults['toolbar'] = false;
	SyntaxHighlighter.all();
});