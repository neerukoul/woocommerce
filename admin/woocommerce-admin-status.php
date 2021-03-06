<?php
/**
 * Debug/Status page
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce/Admin/System Status
 * @version     1.6.4
 */

/**
 * Output the content of the debugging page.
 *
 * @access public
 * @return void
 */
function woocommerce_status() {
	global $woocommerce, $wpdb;

	$tools = apply_filters( 'wc_debug_tools', array(
		'clear_transients' => array(
			'name'		=> __( 'WC Transients','woocommerce'),
			'button'	=> __('Clear transients','woocommerce'),
			'desc'		=> __( 'This tool will clear the product/shop transients cache.', 'woocommerce' ),
		),
		'clear_expired_transients' => array(
			'name'		=> __( 'Expired Transients','woocommerce'),
			'button'	=> __('Clear expired transients','woocommerce'),
			'desc'		=> __( 'This tool will clear ALL expired transients from Wordpress.', 'woocommerce' ),
		),
		'recount_terms' => array(
			'name'		=> __('Term counts','woocommerce'),
			'button'	=> __('Recount terms','woocommerce'),
			'desc'		=> __( 'This tool will recount product terms - useful when changing your settings in a way which hides products from the catalog.', 'woocommerce' ),
		),
		'reset_roles' => array(
			'name'		=> __('Capabilities','woocommerce'),
			'button'	=> __('Reset capabilities','woocommerce'),
			'desc'		=> __( 'This tool will reset the admin, customer and shop_manager roles to default. Use this if your users cannot access all of the WooCommerce admin pages.', 'woocommerce' ),
		),
	) );

    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-status" id="icon-woocommerce"><br /></div>
		<h2><?php _e( 'System Status', 'woocommerce' ); ?> <a href="#" class="add-new-h2 debug-report"><?php _e('Generate report', 'woocommerce' ); ?></a></h2>

		<?php
			if ( ! empty( $_GET['action'] ) && ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'debug_action' ) ) {

				switch ( $_GET['action'] ) {
					case "clear_transients" :
						$woocommerce->clear_product_transients();

						echo '<div class="updated"><p>' . __( 'Product Transients Cleared', 'woocommerce' ) . '</p></div>';
					break;
					case "clear_expired_transients" :

						// http://w-shadow.com/blog/2012/04/17/delete-stale-transients/
						$rows = $wpdb->query( "
							DELETE
								a, b
							FROM
								{$wpdb->options} a, {$wpdb->options} b
							WHERE
								a.option_name LIKE '_transient_%' AND
								a.option_name NOT LIKE '_transient_timeout_%' AND
								b.option_name = CONCAT(
									'_transient_timeout_',
									SUBSTRING(
										a.option_name,
										CHAR_LENGTH('_transient_') + 1
									)
								)
								AND b.option_value < UNIX_TIMESTAMP()
						" );

						$rows2 = $wpdb->query( "
							DELETE
								a, b
							FROM
								{$wpdb->options} a, {$wpdb->options} b
							WHERE
								a.option_name LIKE '_site_transient_%' AND
								a.option_name NOT LIKE '_site_transient_timeout_%' AND
								b.option_name = CONCAT(
									'_site_transient_timeout_',
									SUBSTRING(
										a.option_name,
										CHAR_LENGTH('_site_transient_') + 1
									)
								)
								AND b.option_value < UNIX_TIMESTAMP()
						" );

						echo '<div class="updated"><p>' . sprintf( __( '%d Transients Rows Cleared', 'woocommerce' ), $rows + $rows2 ) . '</p></div>';

					break;
					case "reset_roles" :
						// Remove then re-add caps and roles
						woocommerce_remove_roles();
						woocommerce_init_roles();

						echo '<div class="updated"><p>' . __( 'Roles successfully reset', 'woocommerce' ) . '</p></div>';
					break;
					case "recount_terms" :

						$product_cats = get_terms( 'product_cat', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

						_woocommerce_term_recount( $product_cats, get_taxonomy( 'product_cat' ), false, false );

						$product_tags = get_terms( 'product_tag', array( 'hide_empty' => false, 'fields' => 'id=>parent' ) );

						_woocommerce_term_recount( $product_cats, get_taxonomy( 'product_tag' ), false, false );

						echo '<div class="updated"><p>' . __( 'Terms successfully recounted', 'woocommerce' ) . '</p></div>';
					break;
					default:
						$action = esc_attr( $_GET['action'] );
						if( isset( $tools[ $action ]['callback'] ) ) {
							$callback = $tools[ $action ]['callback'];
							$return = call_user_func( $callback );
							if( $return === false ) {
								if( is_array( $callback ) ) {
									echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s::%s', 'woocommerce' ), get_class( $callback[0] ), $callback[1] ) . '</p></div>';

								} else {
									echo '<div class="error"><p>' . sprintf( __( 'There was an error calling %s', 'woocommerce' ), $callback ) . '</p></div>';
								}
							}
						}
				}
			}
		?>
		<br/>
		<textarea id="debug-report" readonly="readonly"></textarea>
		<table class="wc_status_table widefat" cellspacing="0">

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Versions', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('WooCommerce version','woocommerce')?></td>
                    <td><?php echo esc_html( $woocommerce->version ); ?></td>
                </tr>
                <tr>
                    <td><?php _e('WooCommerce DB version','woocommerce')?></td>
                    <td><?php echo esc_html( get_option( 'woocommerce_db_version' ) ); ?></td>
                </tr>
                <tr>
                    <td><?php _e('WordPress version','woocommerce')?></td>
                    <td><?php if ( is_multisite() ) echo 'WPMU'; else echo 'WP'; ?> <?php echo bloginfo('version'); ?></td>
                </tr>
             	<tr>
             		<td><?php _e('Installed plugins','woocommerce')?></td>
             		<td><?php
             			$active_plugins = (array) get_option( 'active_plugins', array() );

             			if ( is_multisite() )
							$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );

						$wc_plugins = array();

						foreach ( $active_plugins as $plugin ) {

							$plugin_data = @get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

    						if ( ! empty( $plugin_data['Name'] ) ) {

    							$wc_plugins[] = $plugin_data['Name'] . ' ' . __( 'by', 'woocommerce' ) . ' ' . $plugin_data['Author'] . ' ' . __( 'version', 'woocommerce' ) . ' ' . $plugin_data['Version'];

    						}
						}

						if ( sizeof( $wc_plugins ) == 0 ) echo '-'; else echo '<ul><li>' . implode( ', </li><li>', $wc_plugins ) . '</li></ul>';

             		?></td>
             	</tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Settings', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('Home URL','woocommerce')?></td>
                    <td><?php echo home_url(); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Site URL','woocommerce')?></td>
                    <td><?php echo site_url(); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Force SSL','woocommerce')?></td>
					<td><?php echo ( get_option( 'woocommerce_force_ssl_checkout' ) === 'yes' ) ? '<mark class="yes">'.__( 'Yes', 'woocommerce' ).'</mark>' : '<mark class="no">'.__( 'No', 'woocommerce' ).'</mark>'; ?></td>
                </tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Shop Pages', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php
					$check_pages = array(
						__( 'Shop base page', 'woocommerce' ) => array(
								'option' => 'woocommerce_shop_page_id',
								'shortcode' => ''
							),
						__( 'Cart Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_cart_page_id',
								'shortcode' => '[woocommerce_cart]'
							),
						__( 'Checkout Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_checkout_page_id',
								'shortcode' => '[woocommerce_checkout]'
							),
						__( 'Pay Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_pay_page_id',
								'shortcode' => '[woocommerce_pay]'
							),
						__( 'Thanks Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_thanks_page_id',
								'shortcode' => '[woocommerce_thankyou]'
							),
						__( 'My Account Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_myaccount_page_id',
								'shortcode' => '[woocommerce_my_account]'
							),
						__( 'Edit Address Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_edit_address_page_id',
								'shortcode' => '[woocommerce_edit_address]'
							),
						__( 'View Order Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_view_order_page_id',
								'shortcode' => '[woocommerce_view_order]'
							),
						__( 'Change Password Page', 'woocommerce' ) => array(
								'option' => 'woocommerce_change_password_page_id',
								'shortcode' => '[woocommerce_change_password]'
							)
					);

					$alt = 1;

					foreach ( $check_pages as $page_name => $values ) {

						if ( $alt == 1 ) echo '<tr>'; else echo '<tr>';

						echo '<td>' . esc_html( $page_name ) . '</td><td>';

						$error = false;

						$page_id = get_option($values['option']);

						// Page ID check
						if ( ! $page_id ) {
							echo '<mark class="error">' . __( 'Page not set', 'woocommerce' ) . '</mark>';
							$error = true;
						} else {

							// Shortcode check
							if ( $values['shortcode'] ) {
								$page = get_post( $page_id );

								if ( ! strstr( $page->post_content, $values['shortcode'] ) ) {

									echo '<mark class="error">' . sprintf(__( 'Page does not contain the shortcode: %s', 'woocommerce' ), $values['shortcode'] ) . '</mark>';
									$error = true;

								}
							}

						}

						if ( ! $error ) echo '<mark class="yes">#' . absint( $page_id ) . ' - ' . get_permalink( $page_id ) . '</mark>';

						echo '</td></tr>';

						$alt = $alt * -1;
					}
				?>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Core Taxonomies', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('Order Statuses','woocommerce')?></td>
                    <td><?php
                    	$order_statuses = get_terms( 'shop_order_status', array( 'fields' => 'names', 'hide_empty' => 0 ) );
                    	echo implode( ', ', array_map( 'esc_html', $order_statuses ) );
                    ?></td>
                </tr>
			</tbody>

			<thead>
				<tr>
					<th colspan="2"><?php _e( 'Server Environment', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody>
                <tr>
                    <td><?php _e('PHP Version','woocommerce')?></td>
                    <td><?php
                    	if ( function_exists( 'phpversion' ) )
                    		echo esc_html( phpversion() );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('Server Software','woocommerce')?></td>
                    <td><?php
                    	echo esc_html( $_SERVER['SERVER_SOFTWARE'] );
                    ?></td>
                </tr>
				<tr>
                    <td><?php _e('WP Max Upload Size','woocommerce'); ?></td>
                    <td><?php
                    	echo wp_convert_bytes_to_hr( wp_max_upload_size() );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('Server upload_max_filesize','woocommerce')?></td>
                    <td><?php
                    	if(function_exists('phpversion'))
                    		echo wp_convert_bytes_to_hr( woocommerce_let_to_num( ini_get('upload_max_filesize') ) );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('Server post_max_size','woocommerce')?></td>
                    <td><?php
                    	if(function_exists('phpversion'))
                    		echo wp_convert_bytes_to_hr( woocommerce_let_to_num( ini_get('post_max_size') ) );
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('WP Memory Limit','woocommerce')?></td>
                    <td><?php
                    	$memory = woocommerce_let_to_num( WP_MEMORY_LIMIT );

                    	if ( $memory < 67108864 ) {
                    		echo '<mark class="error">' . sprintf( __( '%s - We recommend setting memory to at least 64MB. See: <a href="%s">Increasing memory allocated to PHP</a>', 'woocommerce' ), wp_convert_bytes_to_hr( $memory ), 'http://codex.wordpress.org/Editing_wp-config.php#Increasing_memory_allocated_to_PHP' ) . '</mark>';
                    	} else {
                    		echo '<mark class="yes">' . wp_convert_bytes_to_hr( $memory ) . '</mark>';
                    	}
                    ?></td>
                </tr>
                <tr>
                    <td><?php _e('WP Debug Mode','woocommerce')?></td>
                    <td><?php if ( defined('WP_DEBUG') && WP_DEBUG ) echo '<mark class="yes">' . __( 'Yes', 'woocommerce' ) . '</mark>'; else echo '<mark class="no">' . __( 'No', 'woocommerce' ) . '</mark>'; ?></td>
                </tr>
                <tr>
                    <td><?php _e('WC Logging','woocommerce')?></td>
                    <td><?php
                    	if ( @fopen( $woocommerce->plugin_path() . '/logs/paypal.txt', 'a' ) )
                    		echo '<mark class="yes">' . __( 'Log directory is writable.', 'woocommerce' ) . '</mark>';
                    	else
                    		echo '<mark class="error">' . __( 'Log directory (<code>woocommerce/logs/</code>) is not writable. Logging will not be possible.', 'woocommerce' ) . '</mark>';
                    ?></td>
                </tr>
            </tbody>

            <thead>
				<tr>
					<th colspan="2"><?php _e( 'Remote Posting/IPN', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<?php
				$posting = array();

				// fsockopen/cURL
				$posting['fsockopen_curl']['name'] = __( 'fsockopen/cURL','woocommerce');
				if ( function_exists( 'fsockopen' ) || function_exists( 'curl_init' ) ) {
					if ( function_exists( 'fsockopen' ) && function_exists( 'curl_init' )) {
						$posting['fsockopen_curl']['note'] = __('Your server has fsockopen and cURL enabled.', 'woocommerce' );
					} elseif ( function_exists( 'fsockopen' )) {
						$posting['fsockopen_curl']['note'] = __( 'Your server has fsockopen enabled, cURL is disabled.', 'woocommerce' );
					} else {
						$posting['fsockopen_curl']['note'] = __( 'Your server has cURL enabled, fsockopen is disabled.', 'woocommerce' );
					}
					$posting['fsockopen_curl']['success'] = true;
				} else {
            		$posting['fsockopen_curl']['note'] = __( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', 'woocommerce' ). '</mark>';
            		$posting['fsockopen_curl']['success'] = false;
            	}

            	// SOAP
            	$posting['soap_client']['name'] = __( 'SOAP Client','woocommerce' );
				if ( class_exists( 'SoapClient' ) ) {
					$posting['soap_client']['note'] = __('Your server has the SOAP Client class enabled.', 'woocommerce' );
					$posting['soap_client']['success'] = true;
				} else {
            		$posting['soap_client']['note'] = sprintf( __( 'Your server does not have the <a href="%s">SOAP Client</a> class enabled - some gateway plugins which use SOAP may not work as expected.', 'woocommerce' ), 'http://php.net/manual/en/class.soapclient.php' ) . '</mark>';
            		$posting['soap_client']['success'] = false;
            	}

            	// WP Remote Post Check
				$posting['wp_remote_post']['name'] = __( 'WP Remote Post Check','woocommerce');
				$request['cmd'] = '_notify-validate';
				$params = array(
					'sslverify' 	=> false,
		        	'timeout' 		=> 60,
		        	'user-agent'	=> 'WooCommerce/' . $woocommerce->version,
		        	'body'			=> $request
				);
				$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

				if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
            		$posting['wp_remote_post']['note'] = __('wp_remote_post() was successful - PayPal IPN is working.', 'woocommerce' );
            		$posting['wp_remote_post']['success'] = true;
            	} elseif ( is_wp_error( $response ) ) {
            		$posting['wp_remote_post']['note'] = __( 'wp_remote_post() failed. PayPal IPN won\'t work with your server. Contact your hosting provider. Error:', 'woocommerce' ) . ' ' . $response->get_error_message();
            		$posting['wp_remote_post']['success'] = false;
            	} else {
	            	$posting['wp_remote_post']['note'] = __( 'wp_remote_post() failed. PayPal IPN may not work with your server.', 'woocommerce' );
            		$posting['wp_remote_post']['success'] = false;
            	}

            	$posting = apply_filters( 'wc_debug_posting', $posting );
            ?>

			<tbody>
			<?php foreach($posting as $post) { $mark = ( isset( $post['success'] ) && $post['success'] == true ) ? 'yes' : 'error'; ?>
				<tr>
                    <td><?php echo esc_html( $post['name'] ); ?></td>
                    <td>
                    	<mark class="<?php echo $mark; ?>">
	                    	<?php echo wp_kses_data( $post['note'] ); ?>
                    	</mark>
                    </td>
                </tr>
			<?php } ?>
            </tbody>

            <thead class="tools">
				<tr>
					<th colspan="2"><?php _e( 'Tools', 'woocommerce' ); ?></th>
				</tr>
			</thead>

			<tbody class="tools">
			<?php foreach($tools as $action => $tool) { ?>
				<tr>
                    <td><?php echo esc_html( $tool['name'] ); ?></td>
                    <td>
                    	<p>
	                    	<a href="<?php echo wp_nonce_url( admin_url('admin.php?page=woocommerce_status&action=' . $action ), 'debug_action' ); ?>" class="button"><?php echo esc_html( $tool['button'] ); ?></a>
	                    	<span class="description"><?php echo wp_kses_post( $tool['desc'] ); ?></span>
                    	</p>
                    </td>
                </tr>
			<?php } ?>
			</tbody>
		</table>

	</div>
	<script type="text/javascript">

		jQuery('a.debug-report').click(function(){

			if ( ! jQuery('#debug-report').val() ) {

				// Generate report - user can paste into forum
				var report = '`';

				jQuery('thead:not(".tools"), tbody:not(".tools")', '.wc_status_table').each(function(){

					$this = jQuery( this );

					if ( $this.is('thead') ) {

						report = report + "\n=============================================================================================\n";
						report = report + " " + jQuery.trim( $this.text() ) + "\n";
						report = report + "=============================================================================================\n";

					} else {

						jQuery('tr', $this).each(function(){

							$this = jQuery( this );

							report = report + $this.find('td:eq(0)').text() + ": \t";
							report = report + $this.find('td:eq(1)').text() + "\n";

						});

					}

				});

				report = report + '`';

				jQuery('#debug-report').val( report );
			}

			jQuery('#debug-report').slideToggle('500', function() {
				jQuery(this).select();
			});

      		return false;

		});

	</script>
	<?php
}