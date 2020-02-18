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

add_action('woocommerce_order_status_changed', 'seccow_send_email', 10, 4 );
function seccow_send_email( $order_id, $old_status, $new_status, $order ){
    if ( $new_status == 'cancelled' || $new_status == 'failed' ){
        $wc_emails = WC()->mailer()->get_emails(); // Obtener todas las instancias de WC_emails
        $email_cliente = $order->get_billing_email(); // Email del cliente
    }

    if ( $new_status == 'cancelled' ) {
        // Cambiar el destinatario de la instancia
        $wc_emails['WC_Email_Cancelled_Order']->recipient = $email_cliente;
        // Enviar email desde la instancia
        $wc_emails['WC_Email_Cancelled_Order']->trigger( $order_id );
    } 
    elseif ( $new_status == 'failed' ) {
        // Cambiar el destinatario de la instancia
        $wc_emails['WC_Email_Failed_Order']->recipient = $email_cliente;
        // Enviar email desde la instancia
        $wc_emails['WC_Email_Failed_Order']->trigger( $order_id );
    } 
}

add_filter ( 'woocommerce_account_menu_items', 'ccc_rename_orders' );
add_filter ( 'get_job_listings_cache_results', function() { return false; });
add_action( 'woocommerce_before_customer_login_form', 'ccc_woocommerce_before_customer_login_form' );

function ccc_woocommerce_before_customer_login_form() {
    wp_safe_redirect( site_url() . '/wp-login.php');
}

add_filter( 'woocommerce_address_to_edit', 'ccc_woocommerce_address_to_edit', 999, 1 );


function ccc_woocommerce_address_to_edit($address) {
  $address['billing_state']['priority'] = 75;
  $address['billing_state']['class'] = array('address-field' , 'form-row-first');
  $address['billing_country']['class'] = array('address-field' , 'update_totals_on_change', 'form-row-last');
  return $address;
}




add_action( 'template_redirect', 'acf_template_redirect', 1 );
 
function acf_template_redirect() {
  /* do nothing if we are not on the appropriate page */
  if( !is_wc_endpoint_url( 'order-received' ) || empty( $_GET['key'] ) ) {
    return;
  }
  
  $post_id = url_to_postid(wp_get_referer());
  $url = get_field('custom_thank_you_page_url', $post_id);
  
  if( $url ) { /* WC 3.0+ */
    wp_redirect( $url );
    exit;
  }
}

function ccc_rename_orders( $menu_links ){
 
	// $menu_links['TAB ID HERE'] = 'NEW TAB NAME HERE';
	$menu_links['orders'] = 'Investments';
 
	return $menu_links;
}

add_action( 'woocommerce_new_order', 'action_woocommerce_new_order', 10, 1 );

function action_woocommerce_new_order( $order_id ) {
	$order = new WC_Order($order_id);
	$user = $order->get_user();
	error_log("Woo Hoo");
	if( !$user ){
		//guest order
		$userdata = get_user_by( 'email', $order->get_billing_email() );
		if(isset( $userdata->ID )){
			//registered
			update_post_meta($order_id, '_customer_user', $userdata->ID );

			$data = array(
				'billing_city'          => $order->get_billing_city(),
				'billing_postcode'      => $order->get_billing_postcode(),
				'billing_email'         => $order->get_billing_email(),
				'billing_phone'         => $order->get_billing_phone(),
				'billing_address_1'     => $order->get_billing_address_1(),
                'billing_address_2'    => $order->get_billing_address_2()
			);
			
			foreach ($data as $meta_key => $meta_value ) {
				update_user_meta( $userdata->ID, $meta_key, $meta_value );
			}
		} else {

			/* random password with 12 chars */
			 $order_email = $order->get_billing_email();
			 $random_password = wp_generate_password();
			 $user_id = wp_create_user( $order_email, $random_password, $order_email );

			//WC guest customer identification
			 //update_user_meta( $user_id, 'guest', 'yes' );
			 update_post_meta( $order_id, '_customer_user', $user_id );

			//user's billing data
			 update_user_meta( $user_id, 'billing_address_1', $order->billing_address_1 );
			 update_user_meta( $user_id, 'billing_address_2', $order->billing_address_2 );
			 update_user_meta( $user_id, 'billing_city', $order->billing_city );
			 update_user_meta( $user_id, 'billing_company', $order->billing_company );
			 update_user_meta( $user_id, 'billing_country', $order->billing_country );
			 update_user_meta( $user_id, 'billing_email', $order->billing_email );
			 update_user_meta( $user_id, 'billing_first_name', $order->billing_first_name );
			 update_user_meta( $user_id, 'billing_last_name', $order->billing_last_name );
			 update_user_meta( $user_id, 'billing_phone', $order->billing_phone );
			 update_user_meta( $user_id, 'billing_postcode', $order->billing_postcode );
			 update_user_meta( $user_id, 'billing_state', $order->billing_state );

			// user's shipping data
			 update_user_meta( $user_id, 'shipping_address_1', $order->shipping_address_1 );
			 update_user_meta( $user_id, 'shipping_address_2', $order->shipping_address_2 );
			 update_user_meta( $user_id, 'shipping_city', $order->shipping_city );
			 update_user_meta( $user_id, 'shipping_company', $order->shipping_company );
			 update_user_meta( $user_id, 'shipping_country', $order->shipping_country );
			 update_user_meta( $user_id, 'shipping_first_name', $order->shipping_first_name );
			 update_user_meta( $user_id, 'shipping_last_name', $order->shipping_last_name );
			 update_user_meta( $user_id, 'shipping_method', $order->shipping_method );
			 update_user_meta( $user_id, 'shipping_postcode', $order->shipping_postcode );
			 update_user_meta( $user_id, 'shipping_state', $order->shipping_state );

			/**
			 * link past orders to this newly created customer
			 */
			 wc_update_new_customer_past_orders( $user_id );
		}
	}
}

add_action('init', 'ccc_create_staff_role');

function ccc_create_staff_role() {
    global $wp_roles;
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();

    $adm = $wp_roles->get_role('editor');

    //Adding a 'new_role' with all admin caps
    $wp_roles->add_role('staff', 'Staff', $adm->capabilities);
}

function ccc_account_menu_items( $items ) {
 	    $items = array(
	        'dashboard'         => __( 'Dashboard', 'woocommerce' ),
	        'orders'            => __( 'Applications', 'woocommerce' ),
	        'edit-account'		=> __( 'Account Details', 'woocommerce'),
			'contact_details'		=> __( 'Contact Details', 'woocommerce'),
	        'bank_details'	 	=> __( 'Bank Details', 'woocommerce' ),
	        'customer-logout'   => __( 'Logout', 'woocommerce' ),
		);

    return $items;
 
}

add_filter( 'woocommerce_my_account_get_addresses', 'ccc_woocommerce_my_account_get_addresses', 10, 1 );

function ccc_woocommerce_my_account_get_addresses($array) {
  return array(
      'billing' => __( 'Billing address', 'woocommerce' ),
    );
}

add_action( 'woocommerce_account_contact_details_endpoint', 'contact_endpoint_content' );
function contact_endpoint_content() {
    echo WC_Shortcode_My_Account::edit_address();
}

add_action('woocommerce_admin_order_data_after_shipping_address', 'ccc_add_bank_details');


function ccc_add_bank_details($order){
  if ( $order->get_user_id() ) {
    $user_id     = absint( $order->get_user_id() );
    $user        = get_user_by( 'id', $user_id );
    if ( $user ) {
        echo '<h5 class="wcfm-bank-details"> Bank Details </h5>' ;
        echo "Bank Name : " . get_field('bank_name', 'user_' . $user_id) . '<br>';
        echo "Account Number : " . get_field('account_number', 'user_' . $user_id) . '<br>';
        echo "Sort Code : " . get_field('sort_code', 'user_' . $user_id) . '<br>';
    }
  }

}


add_action( 'woocommerce_order_status_on-hold', 'ccc_woocommerce_new_order', 10, 1 );
add_action( 'woocommerce_order_status_processing', 'ccc_woocommerce_new_order', 10, 1 );

function ccc_woocommerce_new_order( $order_id ) {
	$order = wc_get_order( $order_id );
	$items = $order->get_items();
	$user = get_user_by('email', $order->get_billing_email());

	if ($user) {
		foreach ( $items as $item ) {
		    $product_name = $item->get_name();
		    $product_id = $item->get_product_id();
		    $vendor_code = get_field( 'vendor_code', $product_id );
			update_user_meta($user->ID, $vendor_code, true);
		}
	}
}

add_action( 'lygo_process_order', function($order_id) {
	$order = wc_get_order( $order_id );
	$order->update_status( 'processing' );
});

add_action( 'woocommerce_order_status_on-hold', 'ccc_update_status_low_value_order');

function ccc_update_status_low_value_order($order_id) {
  if ( ! $order_id ) {
    return;
  }

  $order = wc_get_order( $order_id );
  $total = $order->get_total();

  if (10000 > $total) {
	wp_schedule_single_event( time() + 300, 'lygo_process_order', array($order_id) );
  }
}
 
add_filter( 'woocommerce_account_menu_items', 'ccc_account_menu_items', 10, 1 );

add_filter ( 'woocommerce_account_menu_items', 'ccc_remove_my_account_links', 99999, 1 );

function ccc_remove_my_account_links( $menu_links ){
 
 
 
	// unset( $menu_links['dashboard'] ); // Remove Dashboard
	//unset( $menu_links['payment-methods'] ); // Remove Payment Methods
	//unset( $menu_links['orders'] ); // Remove Orders
	unset( $menu_links['downloads'] ); // Disable Downloads
	unset( $menu_links['inquiry'] ); // Remove Account details tab
	//unset( $menu_links['customer-logout'] ); // Remove Logout link
 
	return $menu_links;
 
}

add_filter( 'login_headerurl', 'ccc_custom_loginlogo_url');

function ccc_custom_loginlogo_url($url) {

     return site_url();

}

add_filter( 'default_checkout_billing_country', 'ccc_change_default_checkout_country' );
 
function ccc_change_default_checkout_country() {
  return 'GB'; 
}

add_action( 'wp_enqueue_scripts', 'oceanwp_child_enqueue_parent_style' );

function my_login_css() { ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('/wp-content/uploads/2019/10/LYGO-LOGO.png');
		        width:254px;
            background-size: 175px;
			      margin: auto;
        }

        .login form {
		      box-shadow: 0 1px 3px rgba(0,0,0,.00) !important;
        }

        body.login {
		      background: #00afd3;
		    }

		div#login {
		    background: white;
		    margin: auto;
		    margin-top: 30px;
		    padding-bottom: 0px;
		    padding-top: 30px;
		}

		.login form {
		    padding-top: 0px;
		    margin-top: 0px;
		}

		p.forgetmenot {
		    display: none;
		}

		p.submit #wp-submit {
		    width: 100%;
		    display: block;
		    clear: both;
		    background: #fbbb22;
		    border: none;
		    box-shadow: none;
		    text-shadow: none;
		    text-transform: uppercase;
		}

		.login #backtoblog, .login #nav {
		    text-align: center;
		    background: #00afd3;
		    color: white;
		}

		.login #backtoblog a, .login #nav a {
		    text-align: center;
		    background: #00afd3;
		    color: white !important;
		}

		.login #nav {
		    margin-top: 26px;
		    background:white;
		}
		
		.login #nav a{
			background:white;
		}
		.login #nav > a:nth-child(1) {
			display: none;
		}

		.login #nav > a:nth-child(2) {
			color: black !important;
		}

		.login #backtoblog a {
		    margin-top: 0px;

		}

		p#backtoblog {
		    padding-top: 20px !important;
		}
    </style>
<?php }
add_action( 'login_enqueue_scripts', 'my_login_css' );

apply_filters(
  'woocommerce_my_account_my_orders_columns',
  function() { return array(
    'order-number'  => esc_html__( 'Investments', 'woocommerce' ),
    'order-date'    => esc_html__( 'Date', 'woocommerce' ),
    'order-status'  => esc_html__( 'Status', 'woocommerce' ),
    'order-total'   => esc_html__( 'Total', 'woocommerce' ),
    'order-actions' => '&nbsp;',
  ); });

add_action( 'woocommerce_before_account_orders', function() {
  echo '<h4 class="wc-ma-heading">' . esc_html__( 'Applications', 'woocommerce' ) . '</h4>';
});

add_action( 'woocommerce_before_edit_account_form', function() {
  echo '<h4 class="wc-ma-heading">' . esc_html__( 'Account Details', 'woocommerce' ) . '</h4>';
});

add_action( 'woocommerce_account_before_dashboard', function() {
  echo '<h4 class="wc-ma-heading">' . esc_html__( 'Dashboard', 'woocommerce' ) . '</h4>';
});

add_action( 'init', 'my_account_new_endpoints' );

function my_account_new_endpoints() {
    add_rewrite_endpoint( 'bank_details', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'contact_details', EP_ROOT | EP_PAGES );
}

add_action( 'template_redirect', 'save_bank_details' );

function save_bank_details() {
    if (!is_user_logged_in()) return;
    
    $current_user = wp_get_current_user();
    if (isset($_POST['bank_name'])) {
        update_user_meta($current_user->ID, 'bank_name', filter_var($_POST['bank_name'], FILTER_SANITIZE_STRING));
    }

    if (isset($_POST['sort_code'])) {
        update_user_meta($current_user->ID, 'sort_code', filter_var($_POST['sort_code'], FILTER_SANITIZE_STRING));
    }

    if (isset($_POST['account_number'])) {
        update_user_meta($current_user->ID, 'account_number', filter_var($_POST['account_number'], FILTER_SANITIZE_STRING));
    }
}

 add_action( 'woocommerce_account_bank_details_endpoint', 'bank_endpoint_content' );
 function bank_endpoint_content() {
     ?>
        <h4 class="wc-ma-heading"><?php _e( 'Bank Details', 'woocommerce' ); ?></h4>
        <div>
         <p> <b> Not all investments require bank details however if yours does, or if you have been contacted and asked to provide them, then please add them here. 
For security reasons the platform does not display bank details. It does not matter if you have provided them previously or are unsure, simply add the details of the bank account you wish to receive payment to and click the submit button. </b></p>

        <p><b> On submission the details will be received and our records updated.</b></p>
        </div>
         <form method="post" action="/my-account/bank_details" class="bank-details form-row form-row-wide">
          <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_email"><?php _e( 'Name of Bank', 'woocommerce' ); ?></label>
            <input type="text" class="woocommerce-Input input-text" name="bank_name" id="bank_name">
          </p>
          <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_email"><?php _e( 'Sort Code', 'woocommerce' ); ?></label>
            <input type="text" class="woocommerce-Input input-text" name="sort_code" id="sort_code">
          </p>
          <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="account_email"><?php _e( 'Account Number', 'woocommerce' ); ?></label>
            <input type="text" class="woocommerce-Input input-text" name="account_number" id="account_number">
          </p>
          <p>
          <button type="submit" class="woocommerce-Button button" name="save_bank_details" value="Save changes">Save changes</button>
          <input type="hidden" name="action" value="save_account_details">
        </p>
        </form>
       <div class="clear"></div>
       <?php
 }

function wps_change_role_name() {
    global $wp_roles;
    if ( ! isset( $wp_roles ) )
        $wp_roles = new WP_Roles();
    $wp_roles->roles['dc_pending_vendor']['name'] = 'Pending Issuer';
    $wp_roles->role_names['dc_pending_vendor'] = 'Pending Issuer';
    $wp_roles->roles['dc_rejected_vendor']['name'] = 'Rejected Issuer';
    $wp_roles->role_names['dc_rejected_vendor'] = 'Rejected Issuer';
    $wp_roles->roles['dc_vendor']['name'] = 'Issuer';
    $wp_roles->role_names['dc_vendor'] = 'Issuer';
    $wp_roles->roles['customer']['name'] = 'Investor';
    $wp_roles->role_names['customer'] = 'Investor';
    $wp_roles->roles['subscriber']['name'] = 'investor';
    $wp_roles->role_names['subscriber'] = 'investor';
}
add_action('init', 'wps_change_role_name');

function add_woocommerce_support() {
  add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'add_woocommerce_support' );

remove_action( 'woocommerce_before_main_content','woocommerce_breadcrumb', 20, 0);

function dequeue_woocommerce_cart_fragments() {
  wp_dequeue_script('wc-cart-fragments');
}
// add_action( 'wp_enqueue_scripts', 'dequeue_woocommerce_cart_fragments', 11);

add_action( 'woocommerce_thankyou', 'redirect_product_attribute_based', 1 ); 
function redirect_product_attribute_based( $order_id ) {
    $order = wc_get_order( $order_id );
    foreach( $order->get_items() as $item_obj ) {
      $item_data = $item_obj->get_data();
      $custom_thankyou = get_post_meta( $item_data['product_id'],'custom_thankyou', true);
      if ($custom_thankyou) {
         wp_redirect( $custom_thankyou );
         break;
      }
    }
}

//remove cart message
add_filter( 'wc_add_to_cart_message_html', '__return_null' );


// total sales shortcode
function total_sales_func( $atts ) {
  $pid = (isset($atts['pid']) )? $atts['pid'] : (int)$atts['id'];
  return get_post_meta($pid, 'total_sales', true );
}
add_shortcode( 'total_sales', 'total_sales_func' );
add_action( 'rest_api_init', function () {
  register_rest_route( 'pc/v1', '/total_sales/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'total_sales_func'
  ) );
} );


// total unique sales shortcode
// gets buyers according to postmeta.meta_key = _customer_user
// then sort onli uniques
function total_buyers_func( $atts ) {
  
  $pid = (isset($atts['pid']) )? $atts['pid'] : (int)$atts['id'];
  global $wpdb;

  $results = $wpdb->get_results("
    SELECT order_items.order_id, pm.meta_value as user_id
    FROM {$wpdb->prefix}woocommerce_order_items as order_items
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
    LEFT JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = posts.ID
    WHERE posts.post_type = 'shop_order'
    AND pm.meta_key = '_customer_user'
    AND posts.post_status = 'wc-completed'
    AND order_items.order_item_type = 'line_item'
    AND order_item_meta.meta_key = '_product_id'
    AND order_item_meta.meta_value = $pid
    ");

  $count = [];
  foreach ($results as $res) {
    if ( !in_array($res->user_id, $count) ) {
      array_push($count, $res->user_id);
    }
  }

  return count($count);
  
}
add_shortcode( 'total_buyers', 'total_buyers_func' );
add_action( 'rest_api_init', function () {
  register_rest_route( 'pc/v1', '/total_buyers/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'total_buyers_func'
  ) );
} );


// then sort onli uniques
function sales_completed_func( $atts ) {
  $pid = (isset($atts['pid']) )? $atts['pid'] : (int)$atts['id'];
  global $wpdb;

  $results = $wpdb->get_results("
    SELECT order_items.order_id, pm.meta_value as user_id
    FROM {$wpdb->prefix}woocommerce_order_items as order_items
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
    LEFT JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = posts.ID
    WHERE posts.post_type = 'shop_order'
    AND pm.meta_key = '_customer_user'
    AND posts.post_status = 'wc-completed'
    AND order_items.order_item_type = 'line_item'
    AND order_item_meta.meta_key = '_product_id'
    AND order_item_meta.meta_value = $pid
    ");


  return count($results);
}
add_shortcode( 'sales_completed', 'sales_completed_func' );
add_action( 'rest_api_init', function () {
  register_rest_route( 'pc/v1', '/sales_completed/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'sales_completed_func'
  ) );
} );

// total sales value
function sales_value_func( $atts ) {
  $pid = (isset($atts['pid']) )? $atts['pid'] : (int)$atts['id'];
  global $wpdb;
  

  $results = $wpdb->get_results("
    SELECT order_items.order_id, pm.meta_value as user_id
    FROM {$wpdb->prefix}woocommerce_order_items as order_items
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
    LEFT JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = posts.ID
    WHERE posts.post_type = 'shop_order'
    AND pm.meta_key = '_customer_user'
    AND posts.post_status = 'wc-completed'
    AND order_items.order_item_type = 'line_item'
    AND order_item_meta.meta_key = '_product_id'
    AND order_item_meta.meta_value = $pid
    ");

  $count = [];
  foreach ($results as $res) {
    if ( !in_array($res->user_id, $count) ) {
      array_push($count, $res->user_id);
    }
  }

  $product = wc_get_product( $pid );

  $tv = 0;
  if ( $product ) {
    $pprice = $product->get_price();  
    $tv = $pprice * count($results);
  }
  

  return $tv;
}
add_shortcode( 'sales_value', 'sales_value_func' );
add_action( 'rest_api_init', function () {
  register_rest_route( 'pc/v1', '/sales_value/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'sales_value_func'
  ) );
} );


function gravity_forms_move_progress_bar( $form_string, $form ) {
    // Check if Pagination is enabled on this form
    if ( ! is_array( $form['pagination'] ) ) {
        return $form_string;
    } 
    if ( empty( $form['pagination']['type'] ) ) {
        return $form_string;
    }
    // Check if the first page CSS class is progress-bar-bottom
    if ( ! isset( $form['firstPageCssClass'] ) ) {
        return $form_string;
    }
    if ( $form['firstPageCssClass'] != 'progress-bar-bottom' ) {
        return $form_string;
    }
    // If here, the progress bar needs to be at the end of the form
    // Load form string into DOMDocument
    $dom = new DOMDocument;
    @$dom->loadHTML( $form_string );
    // Load Xpath
    $xpath = new DOMXPath( $dom );
    // Find Progress Bar
    $progress_bar = $xpath->query( '//div[@class="gf_progressbar_wrapper"]' )->item(0);
    // Find Form
    $form = $xpath->query( '//form' )->item(0);
    // Move Progress Bar to end of the Form
    $form->appendChild( $progress_bar );
    // Get HTML string
    $form_string = $dom->saveHTML();
    // Return modified HTML string
    return $form_string;
}
add_filter( 'gform_get_form_filter', 'gravity_forms_move_progress_bar', 10, 2 );


// total sales value comb
function combined_sales_func( $atts ) {
  
  $p1 = (isset($atts['p1']) )? $atts['p1'] : 0;
  $p2 = (isset($atts['p2']) )? $atts['p2'] : 0;
  $p3 = (isset($atts['p3']) )? $atts['p3'] : 0;
  $p4 = (isset($atts['p4']) )? $atts['p4'] : 0;

  global $wpdb;
  $res = $wpdb->get_results("
    SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as line_total FROM {$wpdb->prefix}woocommerce_order_items as order_items
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
    WHERE posts.post_type = 'shop_order'
    AND posts.post_status IN ( 'wc-completed' )
    AND order_items.order_item_type = 'line_item'
    AND order_item_meta.meta_key = '_line_total'
    AND order_item_meta_2.meta_key = '_product_id'
    GROUP BY order_item_meta_2.meta_value
  ");

  $sales = array();
  foreach ($res as $row) {
    $sales[$row->product_id] = $row->line_total;
  }

  $tv1 = 0;
  if ($p1 && isset($sales[$p1]) ) {
    $tv1 = $sales[$p1];
  }

  $tv2 = 0;
  if ($p2 && isset($sales[$p2]) ) {
    $tv2 = $sales[$p2];
  }


  $tv3 = 0;
  if ($p3 && isset($sales[$p3]) ) {
    $tv3 = $sales[$p3];
  }


  $tv4 = 0;
  if ($p4 && isset($sales[$p4]) ) {
    $t4 = $sales[$p4];
  }

  return $tv1 + $tv2 + $tv3 + $tv4;
}

// total sales value comb
function sales_value_comb_func( $atts ) {
  $p1 = (isset($atts['p1']) )? $atts['p1'] : 0;
  $p2 = (isset($atts['p2']) )? $atts['p2'] : 0;
  $p3 = (isset($atts['p3']) )? $atts['p3'] : 0;
  $p4 = (isset($atts['p4']) )? $atts['p4'] : 0;

  $res1 = ordersCompletedQuery($p1);
  $res2 = ordersCompletedQuery($p2);
  $res3 = ordersCompletedQuery($p3);
  $res4 = ordersCompletedQuery($p4);

  $results = array_merge($res1,$res2,$res3,$res4);

  $count = [];
  foreach ($results as $res) {
    if ( !in_array($res->user_id, $count) ) {
      array_push($count, $res->user_id);
    }
  }

  $product1 = wc_get_product( $p1 );
  $product2 = wc_get_product( $p2 );
  $product3 = wc_get_product( $p3 );
  $product4 = wc_get_product( $p4 );

  $tv1 = 0;
  if ( $product1 ) {
    $pprice = $product1->get_price();
    $tv1 = $pprice * count($results);
  }

  $tv2 = 0;
  if ( $product2 ) {
    $pprice = $product2->get_price();
    $tv2 = $pprice * count($results);
  }

  $tv3 = 0;
  if ( $product3 ) {
    $pprice = $product3->get_price();
    $tv3 = $pprice * count($results);
  }

  $tv4 = 0;
  if ( $product4 ) {
    $pprice = $product4->get_price();
    $tv4 = $pprice * count($results);
  }

  return $tv1 + $tv2 + $tv3 + $tv4;
}

add_shortcode( 'sales_value_comb', 'combined_sales_func' );
add_action( 'rest_api_init', function () {
  register_rest_route( 'pc/v1', '/sales_value_comb/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'combined_sales_func'
  ) );
} );


function ordersCompletedQuery( $pid ) {
  global $wpdb;
  $res = $wpdb->get_results("
    SELECT order_items.order_id, pm.meta_value as user_id
    FROM {$wpdb->prefix}woocommerce_order_items as order_items
    LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
    LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID
    LEFT JOIN {$wpdb->prefix}postmeta AS pm ON pm.post_id = posts.ID
    WHERE posts.post_type = 'shop_order'
    AND pm.meta_key = '_customer_user'
    AND posts.post_status = 'wc-completed'
    AND order_items.order_item_type = 'line_item'
    AND order_item_meta.meta_key = '_product_id'
    AND order_item_meta.meta_value = $pid
    ");
    return $res;
}

//Code to remove shipped and refunded order types from vendor dashboard

add_filter( 'wcfmu_orders_menus', function( $orders_menus ) {
  if( isset( $orders_menus['shipped'] ) ) unset( $orders_menus['shipped'] );
  return $orders_menus;
}, 50 );


add_filter( 'wcfmu_orders_menus', function( $orders_menus ) {
  if( isset( $orders_menus['refunded'] ) ) unset( $orders_menus['refunded'] );
  return $orders_menus;
}, 50 );

//code to remove formatting from vendor status update email

add_filter( 'wcfm_is_allow_status_update_by_main_order_status', function( $is_allow ) {
add_filter( 'wcfm_email_content_wrapper', function( $content_body, $email_heading ) {
global $WCFM;
remove_filter( 'wcfm_email_content_wrapper', array( $WCFM, 'wcfm_email_content_wrapper' ), 10, 2 );
return $content_body;
}, 9, 2 );
return $is_allow;
});

add_filter( 'wcfm_is_allow_notification_email', function($type) {

if ( 'status-update' == $type || 'enquiry' == $type || 'support' == $type)
	return true;
	
}, 9999);

// Checkout customization
add_filter( 'woocommerce_order_item_permalink', '__return_false' );
add_filter( 'woocommerce_cart_item_permalink', '__return_null' );


add_action( 'init', 'ccc_woocommerce_clear_cart_url' );
add_action( 'template_redirect', 'ccc_woocommerce_clear_cart_url' );

function ccc_woocommerce_clear_cart_url() {
  if (isset($_GET['clear-cart'])) {
        global $woocommerce;
		WC()->cart->empty_cart();
		unset($_GET['clear-cart']);
  }
}

remove_filter('the_content', 'wptexturize');

add_action('woocommerce_cart_actions', 'ccc_back_button');

function ccc_back_button() {
  $return_url = str_replace('application', 'affirmation', strtok($_SERVER["REQUEST_URI"],'?')) . '/?clear-cart=1';
  echo "<div class='wc-buttons'><a href='" . site_url($return_url) ."'  value='BACK'><button style='margin-right: 10px;' class='button' type='button'>BACK</button></a></div>";
}

add_action('woocommerce_before_lost_password_form', function() {
	echo ' <h3 class="align-center"> Lost your password ? </h3> ';
});

add_filter( 'woocommerce_reset_password_message', function() {
	esc_html__( 'Enter a new password below. You can type a new one below if you do not wish to use the default', 'woocommerce' );
});

add_action('wp_footer', 'print_link_remover');

function print_link_remover() {
?>
<script>
jQuery(document).ready(function($){
	var selectors = ['.wcfm_product_for_support a', 'a.wcfm_dashboard_item_title'];

	$.each(selectors, function(i){
		$(selectors[i]).attr("href", "javascript:void(0)");
		$(selectors[i]).attr("target", "_self");
		$(selectors[i]).css("cursor", "default");
		$(selectors[i]).css("pointer-events", "none");
	});

	$('.woocommerce-support-tickets-table').before('<h3 class="blue-heading">Support Tickets <h3>') ;
	
	$('.woocommerce-orders-table__header-order-number').text('ID');
	
	$('input#after_customer_details_terms_and_conditions + strong').click(function() {
		var href =  $(location).attr('href');
		var res = href.replace("application","terms_conditions");
		var win = window.open(res);
		if (win) {
			//Browser has allowed it to be opened
			win.focus();
		}
	})	
});
</script>
<?php
}

