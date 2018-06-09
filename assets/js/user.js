(function($) {
	$("#u-aff-csv-uploader").change(function(e) {
		$("label[for=" + $(this).attr("id") + "]").text(e.target.files[0].name);
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
				showDataTable(data);
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


	function showDataTable(data) {
		var headers = data.shift();
		var t_html ='<table class="table table-striped table-bordered"><thead><tr><th>No</th>';

		headers.forEach(function(header) {
			t_html += '<th>' + header + '</th>';
		});

		t_html += "</tr></thead><tbody>";

		data.forEach(function(record) {
			t_html += "<tr><td></td>";

			record.forEach(function(field) {
				t_html += '<td>' + field + '</td>';
			});

			t_html += "</tr>";
		});
			
		t_html += '</tbody><tfoot><tr><th></th>';

		headers.forEach(function(header) {
			t_html += '<th>' + header + '</th>';
		});

		t_html += '</tr></tfoot></table>';

		$("#uploaded-csv-review").html(t_html);

		var table = $("#uploaded-csv-review table").DataTable({
			dom: 'Bfrtip',
			buttons: [
				'selectAll',
				'selectNone',
				{
					text: 'Download as CSV',
					action: function () {
						var s_records = table.rows({selected: true}).data();
						if(!s_records.length) {
							alert("Please select rows to download as csv");
						} else {
							var data = [headers];

							for (var i = 0; i < s_records.length; i++)
								data.push(s_records[i]);

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
					}
				}
			],
			select: {
				style: 'multi'
			}
		});

		table.on('order.dt search.dt', function() {
			table.column(0, {search:'applied', order:'applied'}).nodes().each(function (cell, i) {
				cell.innerHTML = i+1;
			});
		}).draw();

		$(".uploaded-csv-review-container").removeClass("d-none");
	}
})(jQuery);