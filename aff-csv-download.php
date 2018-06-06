<?php
/*
Plugin Name:  Affilaite CSV Download
Plugin URI:   https://github.com/git-palace/wp-affilaite-downloadable-check
Description:  Download matched affiliate csv content
Author:       Legendary Assassin
*/

add_action('admin_menu', function() {
	add_menu_page(
		'Affilaite CSV Upload',
		'Affiliate CSV Upload',
		'manage_options',
		'aff-csv-upload',
		'aff_csv_upload_view'
	);
});

function aff_csv_upload_view() {
	wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', array('jquery'), '4.1.1', true);
	wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');

	wp_enqueue_script('admin.js', plugins_url('assets/js/admin.js', __FILE__), array('bootstrap-js'), '1.0.0', true);
?>
<div class="container-fluid">
	<div class="col-3 my-5">
		<form method="post" enctype="multipart/form-data">
			<div class="custom-file mb-3">
				<input name="aff_csv_file" type="file" class="custom-file-input" id="aff-csv-uploader" required accept=".csv">
				<label class="custom-file-label" for="aff-csv-uploader"><?php echo get_transient("latest_aff_csv_file") ? get_transient("latest_aff_csv_file") : "Choose file"; ?></label>
			</div>

			<button class="btn btn-primary" type="submit">Upload</button>
		</form>
	</div>
</div>
<?php
}

if ($_FILES['aff_csv_file']) {
	$target_dir = wp_upload_dir()['basedir']."/aff-csv-files/";
	$filename = uniqid().".csv";
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}

	$target_file = $target_dir.$filename;

	if (!file_exists($target_file)) {
		if (move_uploaded_file($_FILES["aff_csv_file"]["tmp_name"], $target_file)) {
			set_transient("latest_aff_csv_file", $filename);
			return;
		}
	}

	echo `
		<script>
			alert("Uploading csv file is failed.");
		</script>
	`;
}

add_shortcode("m_aff_csv_download", function() {
	wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', array('jquery'), '4.1.1', true);
	wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');

	wp_enqueue_script('user.js', plugins_url('assets/js/user.js', __FILE__), array('bootstrap-js'), '1.0.0', true);
?>
<form method="post" enctype="multipart/form-data">
	<div class="custom-file mb-3">
		<input name="u_aff_csv_file" type="file" class="custom-file-input" id="u-aff-csv-uploader" required accept=".csv">
		<label class="custom-file-label" for="u-aff-csv-uploader">Choose file</label>
	</div>

	<button class="btn btn-primary" type="submit">Upload</button>
</form>
<?php
});