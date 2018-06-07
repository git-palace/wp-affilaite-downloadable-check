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
	<div class="row">
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

	<div class="row flex-column">
		<h3 class="col-4">Download History</h3>
		<table class="col-3" border="1">
			<thead>
				<tr>
					<th class="text-center">No</th>
					<th class="text-center">User Email</th>
					<th class="text-center">Download link</th>
				</tr>
				<tbody>		
					<?php
						$d_list = get_transient("d_aff_csv_list");
						if($d_list && is_array($d_list) && !empty($d_list)) {
							$idx = 0;
							foreach ($d_list as $user_id => $file_arr) {
								foreach ($file_arr as $filename) {
									$idx++;

									$target_file = implode("/", array(
										wp_upload_dir()['baseurl'],
										"aff-csv-files/downloaded",
										get_current_user_id(),
										$filename
									));

									echo '<tr>';
									echo '<td class="text-center">'.$idx.'</td>';
									$user = get_user_by('id', $user_id);
									echo '<td class="text-center">'.$user->user_email.'</td>';
									echo '<td class="text-center"><a href="'.$target_file.'">Click here to download</a></td>';
									echo '</tr>';
								}
							}

						} else {
							echo '<tr><td class="text-center" colspan=3>No History</td></tr>';
						}
					?>
				</tbody>
			</thead>
		</table>
	</div>
</div>
<?php
}

if ($_FILES['aff_csv_file']) {
	$target_dir = wp_upload_dir()['basedir']."/aff-csv-files/uploaded/";

	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}

	$filename = uniqid().".csv";

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

// aff_csv download in frontend
add_shortcode("aff_csv_download", function() {
	wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', array('jquery'), '4.1.1', true);
	wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');

	wp_enqueue_script('user.js', plugins_url('assets/js/user.js', __FILE__), array('bootstrap-js'), '1.0.0', true);
?>
<form id="u-aff-csv-form" enctype="multipart/form-data">
	<div class="custom-file mb-3">
		<?php if(is_user_logged_in()): ?>
			<input type="hidden" name="user_id" value="<?php echo get_current_user_id(); ?>">
		<?php endif; ?>
		<input name="u_aff_csv_file" type="file" class="custom-file-input" id="u-aff-csv-uploader" required accept=".csv">
		<label class="custom-file-label" for="u-aff-csv-uploader">Choose file</label>
	</div>

	<button class="btn btn-primary" type="submit">Upload</button>
</form>
<?php
});

add_action('rest_api_init', function () {
	register_rest_route(
		'aff-csv',
		'download-matched-csv',
		array(
			'methods' => 'post',
			'callback' => 'download_m_aff_csv',
		)
	);
});

function download_m_aff_csv() {
	if(empty($_FILES["u_aff_csv_file"]))
		return array();
	
	$file = $_FILES["u_aff_csv_file"]["tmp_name"];

	$result =  getMatchedResult($_FILES["u_aff_csv_file"]["tmp_name"]);

	if(!empty($_POST['user_id']))
		upload_downloaded_csv($result, $_POST['user_id']);

	return $result;
}

function getMatchedResult($file) {
	$u_csv = array_map("str_getcsv", file($file));

	$result = array(array_shift($u_csv));

	$a_file_path = wp_upload_dir()['basedir']."/aff-csv-files/uploaded/".get_transient("latest_aff_csv_file");

	if (!file_exists($a_file_path))
		return array();

	$a_csv = file($a_file_path);

	$a_header = array_shift($a_csv);

	$email_list = array();

	foreach ($a_csv as $idx => $query) {
		$query = str_replace('"', ".", $query);
		$query = explode(",", $query);
		array_push($email_list, $query[2]);
	}

	foreach ($u_csv as $key => $query) {
		if (in_array($query[0], $email_list)) {
			array_push($result, $query);
		}
	}

	return $result;
}

function upload_downloaded_csv($fields, $user_id) {
	$target_dir = implode("/", array(
		wp_upload_dir()['basedir'],
		"aff-csv-files/downloaded",
		$user_id,
		""
	));
	
	if (!file_exists($target_dir)) {
		mkdir($target_dir, 0777, true);
	}

	$filename = time().".csv";
	$target_file = $target_dir.$filename;

	$fp = fopen($target_file, "w");

	foreach ($fields as $field)
		fputcsv($fp, $field);

	fclose($fp);

	$d_list = get_transient("d_aff_csv_list");

	if ($d_list && is_array($d_list[$user_id])) {
		array_push($d_list[$user_id], $filename);
	} else {
		$d_list[$user_id] = array($filename);
	}

	set_transient("d_aff_csv_list", $d_list);
}