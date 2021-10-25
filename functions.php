<?php
/**
 * Child theme functions
 *
 * When using a child theme (see http://codex.wordpress.org/Theme_Development
 * and http://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions would be used.
 *
 * Text Domain: oceanwp
 * @link http://codex.wordpress.org/Plugin_API
 *
 */

/**
 * Load the parent style.css file
 *
 * @link http://codex.wordpress.org/Child_Themes
 */
function oceanwp_child_enqueue_parent_style() {
	// Dynamically get version number of the parent stylesheet (lets browsers re-cache your stylesheet when you update your theme)
	$theme   = wp_get_theme( 'OceanWP' );
	$version = $theme->get( 'Version' );
	// Load the stylesheet
	wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'oceanwp-style' ), $version );
	
}

add_action( 'wp_enqueue_scripts', 'oceanwp_child_enqueue_parent_style' );

function load_js_assets() {
    if( is_page(array( 17183, 17308 ) ) ) {
        wp_enqueue_script('uvg-js', 'https://platform.lygocapital.com/uvg-js.js', array('jquery'), '', false);
    } 
}

add_action('wp_enqueue_scripts', 'load_js_assets');

function lygoc_set_content_type(){
    return "text/html";
} 
add_filter( 'wp_mail_content_type','lygoc_set_content_type' );

add_action( 'wp_ajax_lcd_email', 'lcd_email' );
add_action( 'wp_ajax_nopriv_lcd_email', 'lcd_email' );
function lcd_email(){ 
	$post_id = $_POST['pid'];
	$vendor_user = get_post_meta($post_id,'_assign_vendor_user',true);	
	if(!empty($vendor_user)){
		$vendor_data = get_userdata($vendor_user);
		$vEmail = $vendor_data->data->user_email;
		$subject = 'A document was downloaded from Lygo Capital!';  
		$message = '';
		$message .= '<p>Hi,</p>';	
		$message .= "<p>Your Lygo Capital Document is downloaded.</p>";
		$message .= '<p>Kind regards,<br/>'; 
		$message .= 'Lygo Capital</p>';	
		$webAdmin = get_option('admin_email');  // If you like change this email address 
		$to = 'weliveyear@gmail.com';  
		//$to = $vendoer; 
		$header = 'From: '.get_option('blogname').' <investments@lygocapital.com>'.PHP_EOL;
		$header .= 'Reply-To: '.$email.PHP_EOL;	
		if ( wp_mail($to, $subject, $message, $header) ) {
			$sendmsg = 'Email sent to Vendor.'; 
			$status = 'success';
			$error = $sendmsg;
		} else {
			$error = 'Email errors occurred.';
			$status = 'error'; 
		}
	}else{
			$error = 'Email errors occurred.';
			$status = 'error'; 
	}		 
	$resp = array('status' => $status, 'errmessage' => $error);
	header( "Content-Type: application/json" );
	echo json_encode($resp);
	die();
}

add_action( 'wp_footer', function () { ?>
<script> 
jQuery(document).on("click","figure",function() {
	var pdfURL = jQuery('figure a').attr('href');
	var pid = jQuery('#content .entry  > div').data("elementor-id");
	if(pdfURL){
		jQuery.ajax({
			url  : '<?php echo admin_url('admin-ajax.php'); ?>',
			type : 'post',
			data : {
				action : 'lcd_email',
				pdf_url : pdfURL,
				pid : pid,
			},
			success: function (response) {
				if (response.status == 'success') {
				}else{
				}   
			}
		});
	}
}); 
</script>
<?php } ); 
?>