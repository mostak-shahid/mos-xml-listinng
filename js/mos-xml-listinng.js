jQuery(document).ready(function($) {
	
		$.ajax({
			type: "POST",                 // use $_POST request to submit data
			url: xml_ajax_url,      // URL to "wp-admin/admin-ajax.php"
			data: {
				action     : 'check_new_file', // wp_ajax_*, wp_ajax_nopriv_*
			},
			success:function( data ) {
				//console.log(data);
			},
			error: function(){
				console.log(errorThrown); // error
			}
		}); 

});