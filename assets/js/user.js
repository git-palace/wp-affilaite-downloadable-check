(function($) {
	$("#u-aff-csv-uploader").change(function(e) {
		$("label[for=" + $(this).attr("id") + "]").text(e.target.files[0].name);

		if (e.target.files.length)
			$("#u-aff-csv-form button").text("Download");
		else
			$("#u-aff-csv-form button").text("Upload");
	});

	$("#u-aff-csv-form").submit(function(e) {
		e.preventDefault();

		var formData = new FormData(this);

		console.log(formData);

		$.ajax({
			url: '/wp-json/aff-csv/download-matched-csv',
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			processData: false,
			success: function (data) {
				var csv = ConvertToCSV(JSON.stringify(data));
				var blob = new Blob([csv], { type: 'text/csv' }); 
				var csvUrl = window.URL.createObjectURL(blob);
				var filename = 'download.csv';

				var link = document.createElement("a");
				link.setAttribute("download", filename);
				link.setAttribute("target", "_blank");
				link.setAttribute("href", csvUrl);
				link.dispatchEvent(
					new MouseEvent(`click`, { bubbles: true, cancelable: true, view: window })
				);
			}
		});
	});

	function ConvertToCSV(objArray) {
		var array = typeof objArray != 'object' ? JSON.parse(objArray) : objArray;
		var str = '';

		for (var i = 0; i < array.length; i++) {
			var line = '';
			for (var index in array[i]) {
				if (line != '') line += ','

				line += array[i][index];
			}

			str += line + '\r\n';
		}

		return str;
	}
})(jQuery);