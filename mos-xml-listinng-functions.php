<?php
function mos_xml_listing_admin_enqueue_scripts () {
	wp_enqueue_script( 'mos-xml-listinng', plugins_url( 'js/mos-xml-listinng.js', __FILE__ ), array('jquery') );	
	wp_localize_script('mos-xml-listinng',  'xml_ajax_url', admin_url( 'admin-ajax.php' ));
}
add_action( 'admin_enqueue_scripts', 'mos_xml_listing_admin_enqueue_scripts' );
add_action( 'wp_enqueue_scripts', 'mos_xml_listing_admin_enqueue_scripts' );

/*Ajax*/
/* AJAX Action*/
add_action( 'wp_ajax_check_new_file', 'check_new_file_ajax_callback' );
add_action( 'wp_ajax_nopriv_check_new_file', 'check_new_file_ajax_callback' );
/**
 * Ajax Callback
 */
function check_new_file_ajax_callback () {
	global $dir;
	if (is_admin()) {
		$files = dirToArray($dir);
		foreach ($files as $file) {		
			if (check_file_ext(array('xml', 'XML'), $file) AND check_new_file ($file)) {
				store_data ($file);
				update_file_name ($file);
			}
		}
	}	
	//header("Content-type: text/x-json");
    exit; // required. to end AJAX request.
}
/*Ajax*/

function dirToArray($dir) {    
	$result = array(); 
	$cdir = scandir($dir); 
	foreach ($cdir as $key => $value) { 
		if (!in_array($value,array(".",".."))) { 
	        /*if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) { 
	            $result[$value] = dirToArray($dir . DIRECTORY_SEPARATOR . $value); 
	        } else { 
	            $result[] = $value; 
	        }*/ 
        	$result[] = $value; 
	    } 
	}    
	return $result; 
}
function check_file_ext ($allowed, $filename) {
	if (!is_array($allowed)) $allowed =  array($allowed);
	$ext = pathinfo($filename, PATHINFO_EXTENSION);
	if(!in_array($ext,$allowed) ) {
	    return false;
	}
	return true;
}
function check_new_file ($filename) {
	//check already stored or not return false if stored
	$data  = file_get_contents(plugin_dir_path( __FILE__ ) . 'file-tree.json');
	$json_arr  = json_decode($data, true);
	foreach ($json_arr as $key => $value) {
		if ($value['file_name'] == $filename) return false;
	}
	return true;
}

function store_data ($filename) {	
	global $dir;	
	$xmlstring = file_get_contents($dir . '/' . $filename);
	$xml = simplexml_load_string($xmlstring, "SimpleXMLElement", LIBXML_NOCDATA);
	$json = json_encode($xml);
	$array = json_decode($json,TRUE);
	$post_title = ( $array["residential"]["headline"]) ? $array["residential"]["headline"] : 'Realty Property';
	$project = array(
		'post_title'    => $post_title,
		'post_content'  => $array["residential"]["description"],
		'post_status'   => 'publish',
		'post_type'   => 'project'
	);
	$post_id = wp_insert_post( $project );
	add_post_meta($post_id, '_mosacademy_child_project_key_for', 'buy', true);
	$address = $array["residential"]["address"]["street"] . ', ' . $array["residential"]["address"]["suburb"] . ', ' . $array["residential"]["address"]["state"] . '-' . $array["residential"]["address"]["postcode"]  . ', ' . $array["residential"]["address"]["country"];
	add_post_meta($post_id, 'search-map-location', $address, true);
	if (@$array["residential"]["objects"]["img"]["0"]["@attributes"]["url"]) {
		//xml_listing_generate_featured_image( $array["residential"]["objects"]["img"]["0"]["@attributes"]["url"], $post_id  );
		Generate_Featured_Image($array["residential"]["objects"]["img"]["0"]["@attributes"]["url"], $post_id);
	}	

	$n = 0;
	$data = array();
		if (!@$array["residential"]["listingAgent"]["name"]) {
		foreach ($array["residential"]["listingAgent"] as $agent) {						
			add_post_meta($post_id, '_mosacademy_child_agent_name_'.$n, $agent["name"], true);						
			$data[$n]['_mosacademy_child_agent_name'] = $agent["name"];
			if ($agent["telephone"]) {
				$data[$n]['_mosacademy_child_agent_phone'][] = $agent["telephone"];
			}
			if ($agent["email"]) {
				$data[$n]['_mosacademy_child_agent_email'][] = $agent["email"];
			}
			$n++;

		}

	} else {					
		add_post_meta($post_id, '_mosacademy_child_agent_name_'.$n, $array["residential"]["listingAgent"]["name"], true);	
		$data[$n]['_mosacademy_child_agent_name'] = $array["residential"]["listingAgent"]["name"];					
		if ($array["residential"]["listingAgent"]["telephone"]) {
			$data[$n]['_mosacademy_child_agent_phone'][] = $array["residential"]["listingAgent"]["telephone"];
		}
		if ($array["residential"]["listingAgent"]["email"]) {
			$data[$n]['_mosacademy_child_agent_email'][] = $array["residential"]["listingAgent"]["email"];
		}
	}
	add_post_meta( $post_id, '_mosacademy_child_agent_Group', $data );



	if ($array["residential"]["category"]["@attributes"]["name"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_property_type', $array["residential"]["category"]["@attributes"]["name"], true);
	}
	if ($array["residential"]["features"]["livingAreas"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_area', $array["residential"]["features"]["livingAreas"], true);
	}
	if ($array["residential"]["price"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_price', round($array["residential"]["price"]), true);
	}
	if ($array["residential"]["features"]["bedrooms"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_bed', $array["residential"]["features"]["bedrooms"], true);
	}
	if ($array["residential"]["features"]["bathrooms"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_toilets', $array["residential"]["features"]["bathrooms"], true);
	}
	if ($array["residential"]["features"]["garages"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_car_parking', $array["residential"]["features"]["garages"], true);
	}
	if ($array["residential"]["features"]["ensuite"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_ensuite', $array["residential"]["features"]["ensuite"], true);
	}
	if ($array["residential"]["features"]["remoteGarage"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_remoteGarage', $array["residential"]["features"]["remoteGarage"], true);
	}
	if ($array["residential"]["features"]["secureParking"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_secureParking', $array["residential"]["features"]["secureParking"], true);
	}
	if ($array["residential"]["features"]["airConditioning"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_airConditioning', $array["residential"]["features"]["airConditioning"], true);
	}
	if ($array["residential"]["features"]["alarmSystem"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_alarmSystem', $array["residential"]["features"]["alarmSystem"], true);
	}
	if ($array["residential"]["features"]["vacuumSystem"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_vacuumSystem', $array["residential"]["features"]["vacuumSystem"], true);
	}
	if ($array["residential"]["features"]["intercom"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_intercom', $array["residential"]["features"]["intercom"], true);
	}
	if ($array["residential"]["features"]["poolInGround"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_poolInGround', $array["residential"]["features"]["poolInGround"], true);
	}
	if ($array["residential"]["features"]["poolAboveGround"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_poolAboveGround', $array["residential"]["features"]["poolAboveGround"], true);
	}
	if ($array["residential"]["features"]["tennisCourt"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_tennisCourt', $array["residential"]["features"]["tennisCourt"], true);
	}
	if ($array["residential"]["features"]["balcony"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_balcony', $array["residential"]["features"]["balcony"], true);
	}
	if ($array["residential"]["features"]["deck"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_deck', $array["residential"]["features"]["deck"], true);
	}
	if ($array["residential"]["features"]["courtyard"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_courtyard', $array["residential"]["features"]["courtyard"], true);
	}
	if ($array["residential"]["features"]["outdoorEnt"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_outdoorEnt', $array["residential"]["features"]["outdoorEnt"], true);
	}
	if ($array["residential"]["features"]["shed"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_shed', $array["residential"]["features"]["shed"], true);
	}
	if ($array["residential"]["features"]["fullyFenced"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_fullyFenced', $array["residential"]["features"]["fullyFenced"], true);
	}
	if ($array["residential"]["features"]["openFirePlace"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_openFirePlace', $array["residential"]["features"]["openFirePlace"], true);
	}
	if ($array["residential"]["features"]["insideSpa"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_insideSpa', $array["residential"]["features"]["insideSpa"], true);
	}
	if ($array["residential"]["features"]["outsideSpa"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_outsideSpa', $array["residential"]["features"]["outsideSpa"], true);
	}
	if ($array["residential"]["features"]["broadband"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_broadband', $array["residential"]["features"]["broadband"], true);
	}
	if ($array["residential"]["features"]["builtInRobes"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_builtInRobes', $array["residential"]["features"]["builtInRobes"], true);
	}
	if ($array["residential"]["features"]["dishwasher"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_dishwasher', $array["residential"]["features"]["dishwasher"], true);
	}
	if ($array["residential"]["features"]["ductedCooling"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_ductedCooling', $array["residential"]["features"]["ductedCooling"], true);
	}
	if ($array["residential"]["features"]["ductedHeating"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_ductedHeating', $array["residential"]["features"]["ductedHeating"], true);
	}
	if ($array["residential"]["features"]["evaporativeCooling"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_evaporativeCooling', $array["residential"]["features"]["evaporativeCooling"], true);
	}
	if ($array["residential"]["features"]["floorboards"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_floorboards', $array["residential"]["features"]["floorboards"], true);
	}
	if ($array["residential"]["features"]["gasHeating"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_gasHeating', $array["residential"]["features"]["gasHeating"], true);
	}
	if ($array["residential"]["features"]["gym"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_gym', $array["residential"]["features"]["gym"], true);
	}
	if ($array["residential"]["features"]["hydronicHeating"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_hydronicHeating', $array["residential"]["features"]["hydronicHeating"], true);
	}
	if ($array["residential"]["features"]["payTV"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_payTV', $array["residential"]["features"]["payTV"], true);
	}
	if ($array["residential"]["features"]["reverseCycleAirCon"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_reverseCycleAirCon', $array["residential"]["features"]["reverseCycleAirCon"], true);
	}
	if ($array["residential"]["features"]["rumpusRoom"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_rumpusRoom', $array["residential"]["features"]["rumpusRoom"], true);
	}
	if ($array["residential"]["features"]["splitSystemAirCon"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_splitSystemAirCon', $array["residential"]["features"]["splitSystemAirCon"], true);
	}
	if ($array["residential"]["features"]["splitSystemHeating"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_splitSystemHeating', $array["residential"]["features"]["splitSystemHeating"], true);
	}
	if ($array["residential"]["features"]["study"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_study', $array["residential"]["features"]["study"], true);
	}
	if ($array["residential"]["features"]["workshop"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_workshop', $array["residential"]["features"]["workshop"], true);
	}
	if ($array["residential"]["features"]["otherFeatures"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_otherFeatures', $array["residential"]["features"]["otherFeatures"], true);
	}
	if ($array["residential"]["ecoFriendly"]["solarPanels"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_solarPanels', $array["residential"]["ecoFriendly"]["solarPanels"], true);
	}
	if ($array["residential"]["ecoFriendly"]["solarHotWater"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_solarHotWater', $array["residential"]["ecoFriendly"]["solarHotWater"], true);
	}
	if ($array["residential"]["ecoFriendly"]["waterTank"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_waterTank', $array["residential"]["ecoFriendly"]["waterTank"], true);
	}
	if ($array["residential"]["ecoFriendly"]["greyWaterSystem"]) {
		add_post_meta($post_id, '_mosacademy_child_project_key_greyWaterSystem', $array["residential"]["ecoFriendly"]["greyWaterSystem"], true);
	}
	if ($array["residential"]["agentID"]) {
		add_post_meta($post_id, '_mosacademy_child_project_agentID', $array["residential"]["agentID"], true);
	}
	if ($array["residential"]["uniqueID"]) {
		add_post_meta($post_id, '_mosacademy_child_project_uniqueID', $array["residential"]["uniqueID"], true);
	}
	if ($array["residential"]["objects"]["floorplan"]["0"]["@attributes"]["url"]) {
		$data = upload_file ($array["residential"]["objects"]["floorplan"]["0"]["@attributes"]["url"]);
		add_post_meta($post_id, '_mosacademy_child_project_plan', $data['url'], true);
	}



}
function update_file_name ($filename) {
	$data  = file_get_contents(plugin_dir_path( __FILE__ ) . 'file-tree.json');
	$json_arr  = json_decode($data, true);

	$json_arr[] = array("file_name" => $filename);
	file_put_contents(plugin_dir_path( __FILE__ ) . 'file-tree.json', json_encode($json_arr));
	// $new_value = array($filename);
	// update_option( 'xml_file_list', $new_value, $autoload );
}
function xml_listing_generate_featured_image( $image_url, $post_id  ) {
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);
    if(wp_mkdir_p($upload_dir['path']))     $file = $upload_dir['path'] . '/' . $filename;
    else                                    $file = $upload_dir['basedir'] . '/' . $filename;
    file_put_contents($file, $image_data);

    $wp_filetype = wp_check_filetype($filename, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    $res1= wp_update_attachment_metadata( $attach_id, $attach_data );
    //$res2= set_post_thumbnail( $post_id, $attach_id );
}
function upload_file ($url) {
	// Gives us access to the download_url() and wp_handle_sideload() functions
	require_once( ABSPATH . 'wp-admin/includes/file.php' );
	$timeout_seconds = 5;
	// Download file to temp dir
	$temp_file = download_url( $url, $timeout_seconds );

	if ( !is_wp_error( $temp_file ) ) {

		// Array based on $_FILE as seen in PHP file uploads
		$file = array(
			'name'     => basename($url), // ex: wp-header-logo.png
			//'type'     => 'image/png',
			'tmp_name' => $temp_file,
			'error'    => 0,
			'size'     => filesize($temp_file),
		);

		$overrides = array(
			'test_form' => false,
			'test_size' => true,
		);
		$results = wp_handle_sideload( $file, $overrides );

		if ( empty( $results['error'] ) ) {
			//$filename  = $results['file']; // Full path to the file
			//$local_url = $results['url'];  // URL to the file in the uploads dir
			//$type      = $results['type']; // MIME type of the file
			return $results;
		}
	}
	return false;
}
function Generate_Featured_Image( $file, $post_id, $desc='' ){
    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
    require_once(ABSPATH . "wp-admin" . '/includes/file.php');
    require_once(ABSPATH . "wp-admin" . '/includes/media.php');
    preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
    if ( ! $matches ) {
         return new WP_Error( 'image_sideload_failed', __( 'Invalid image URL' ) );
    }

    $file_array = array();
    $file_array['name'] = basename( $matches[0] );

    // Download file to temp location.
    $file_array['tmp_name'] = download_url( $file );

    // If error storing temporarily, return the error.
    if ( is_wp_error( $file_array['tmp_name'] ) ) {
        return $file_array['tmp_name'];
    }

    // Do the validation and storage stuff.
    $id = media_handle_sideload( $file_array, $post_id, $desc );

    // If error storing permanently, unlink.
    if ( is_wp_error( $id ) ) {
        @unlink( $file_array['tmp_name'] );
        return $id;
    }
    return set_post_thumbnail( $post_id, $id );

}