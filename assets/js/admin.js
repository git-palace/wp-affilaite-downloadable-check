(function($) {
	$("#aff-csv-uploader").change(function(e) {
		$("label[for=" + $(this).attr("id") + "]").text(e.target.files[0].name);
	});
})(jQuery);