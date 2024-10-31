<?php
/*
Plugin Name: MicroPayments - Fans Paysite: Paid Creator Subscriptions, Digital Assets, Wallet
Plugin URI: https://ppvscript.com/micropayments/
Description: <strong>MicroPayments - Fans Paysite: Paid Creator Subscriptions, Digital Assets, Wallet</strong>: Sell subscription to author content, membership, content access or downloads with micropayments. Reduce billing fees, processing time and friction with micropayments using virtual wallet credits/tokens. Authors can accept donations, setup donation goals, crowdfunding for each content item. Credits/tokens can be purchased with real money using TeraWallet for WooCommerce or MyCred - multiple billing gateways supported. Control access to content (including custom posts) by site membership, author subscription. Sell digital assets (videos, pictures, documents, custom posts) as WooCommerce products. Upload files for membership/paid downloads from dedicated frontend pages. Micropayments allows users to do low value transactions from site wallet, without using a billing site each time. Increase user spending with easy instant payments, low friction, low billing fees, deposits on site. Leave a review if you find this free plugin idea useful and would like more updates!  <a href='https://wordpress.org/support/plugin/paid-membership/reviews/#new-post'>Review Plugin</a> |  <a href='https://ppvscript.com/micropayments/'>Plugin Homepage</a> | <a href='https://videowhisper.com/tickets_submit.php?topic=paid-membership'>Contact Developers</a>
Version: 2.9.26
Requires PHP: 7.4
Author: VideoWhisper.com
Author URI: https://videowhisper.com/
Contributors: videowhisper, VideoWhisper.com
Domain Path: /languages/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/inc/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '/inc/options.php';

const VW_PM_DEVMODE = 0;
if ( VW_PM_DEVMODE ) {
	ini_set( 'display_errors', 1 );
}

use VideoWhisper\PaidContent;

if ( ! class_exists( 'VWpaidMembership' ) ) {
	class VWpaidMembership {


		use VideoWhisper\PaidContent\Shortcodes;
		use VideoWhisper\PaidContent\Options;

		public function __construct() {
		}

		 function VWpaidMembership() {
			// old constructor
				self::__construct();
		}

		// ! Plugin Hooks
		function init() {
			// add_action('wp_loaded', array('VWpaidMembership','setupPages'));
			self::download_post();
		}


		static function activation() {
			wp_schedule_event( time(), 'daily', 'cron_membership_update' );
			wp_schedule_event( time(), 'daily', 'cron_subscriptions_process' );
			wp_schedule_event( time(), 'daily', 'cron_packages_process' );
		}


		static function deactivation() {
			wp_clear_scheduled_hook( 'cron_membership_update' );
			wp_clear_scheduled_hook( 'cron_subscriptions_process' );
			wp_clear_scheduled_hook( 'cron_packages_process' );
		}
		

//BuddyPress

static function bp_after_activity_post_form()
{
	$options = self::getOptions();
	$current_user = wp_get_current_user();
	
	if ($options['buddypressPost'] ?? false ) if (self::rolesUser( $options['rolesSeller'], $current_user ) ) 
	{
	
	 self::enqueueUI();
	
	 if ( $options['p_videowhisper_content_upload'] )
		 echo ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_content_upload'] ) . '"><i class="upload icon"></i>' . __( 'Upload New Content', 'paid-membership' ) . '</a>';

	 if ( $options['p_videowhisper_content_seller'] )
		 echo '<a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'Share available content to activity feed', 'paid-membership' ) . '" href="' . add_query_arg( 'bpID', -1, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'Share Content', 'paid-membership' ) . '</a>';
		 
 }
	 
	 
}

	static function bp_setup_nav() {
			 global $bp;

			 if ( !isset( $bp->displayed_user ) ) return;
			 if ( !isset( $bp->displayed_user->id ) ) return;
			 
			 $userID = $bp->displayed_user->id;
			 $user = get_userdata( $userID );
			 $options = self::getOptions();
	
			if ($options['buddypressProfile'] ?? false ) if (self::rolesUser( $options['rolesSeller'], $user ) )
			{
					
			bp_core_new_nav_item(
				array(
					'name'                    => 'Content',
					'slug'                    => 'content',
					'screen_function'         => array( 'VWpaidMembership', 'bp_content_screen' ),
					'position'                => 40,
					'show_for_displayed_user' => true,
					'parent_url'              => $bp->displayed_user->domain,
					'parent_slug'             => $bp->profile->slug,
					'default_subnav_slug'     => 'content',
				)
			);
			
			bp_core_new_nav_item(
				array(
					'name'                    => 'Subscribe',
					'slug'                    => 'subscribe',
					'screen_function'         => array( 'VWpaidMembership', 'bp_subscribe_screen' ),
					'position'                => 40,
					'show_for_displayed_user' => true,
					'parent_url'              => $bp->displayed_user->domain,
					'parent_slug'             => $bp->profile->slug,
					'default_subnav_slug'     => 'subscribe',
				)
			);
			
			}
			
		}

		static function bp_content_screen() {

			// Add title and content here - last is to call the members plugin.php template.

			add_action( 'bp_template_title', array( 'VWpaidMembership', 'bp_content_title' ) );
			add_action( 'bp_template_content', array( 'VWpaidMembership', 'bp_content_content' ) );
			bp_core_load_template( 'buddypress/members/single/plugins' );			
		}


		static function bp_subscribe_screen() {

			// Add title and content here - last is to call the members plugin.php template.
			
			add_action( 'bp_template_title', array( 'VWpaidMembership', 'bp_subscribe_title' ) );
			add_action( 'bp_template_content', array( 'VWpaidMembership', 'bp_subscribe_content' ) );
			bp_core_load_template( 'buddypress/members/single/plugins' );
		}


		static function bp_content_title() {
			echo __( 'Author Content', 'paid-membership' );
		}

		static function bp_subscribe_title() {
			echo __( 'Subscribe to Author', 'paid-membership' );
		}


		static function bp_content_content() {

			global $bp;
			$userID = $bp->displayed_user->id;

			echo do_shortcode( '[videowhisper_content_list menu="0" author_id="' . intval( $userID ) . '"]' ); //select_order="0" select_category="0" select_tags="0" select_name="0"

		}


		static function bp_subscribe_content() {

			global $bp;
			$userID = $bp->displayed_user->id;

			echo do_shortcode( '[videowhisper_client_subscribe author_id="' . intval( $userID ) . '"]' );

		}


		static function plugins_loaded() {
				
			// translations
			load_plugin_textdomain( 'paid-membership', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

			// admin users
			add_filter( 'manage_users_columns', array( 'VWpaidMembership', 'manage_users_columns' ) );
			add_action( 'manage_users_custom_column', array( 'VWpaidMembership', 'manage_users_custom_column' ), 10, 3 );
			add_filter( 'manage_users_sortable_columns', array( 'VWpaidMembership', 'manage_users_sortable_columns' ) );
			add_action( 'pre_user_query', array( 'VWpaidMembership', 'pre_user_query' ) );
	
			// settings link in plugins view
			$plugin = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$plugin", array( 'VWpaidMembership', 'settings_link' ) );

			$options = self::getOptions();
			
			add_filter( 'next_posts_link_attributes', array( 'VWpaidMembership', 'posts_link_attributes' ) );
			add_filter( 'previous_posts_link_attributes', array( 'VWpaidMembership', 'posts_link_attributes' ) );

			// shortcodes
			add_shortcode( 'videowhisper_packages_process', array( 'VWpaidMembership', 'videowhisper_packages_process' ) );

			add_shortcode( 'videowhisper_content_upload_guest', array( 'VWpaidMembership', 'videowhisper_content_upload_guest' ) );

			add_shortcode( 'videowhisper_transactions', array( 'VWpaidMembership', 'videowhisper_transactions' ) );

			add_shortcode( 'videowhisper_client_subscribe', array( 'VWpaidMembership', 'videowhisper_client_subscribe' ) );
			add_shortcode( 'videowhisper_client_subscriptions', array( 'VWpaidMembership', 'videowhisper_client_subscriptions' ) );
				
			add_shortcode( 'videowhisper_provider_subscriptions', array( 'VWpaidMembership', 'videowhisper_provider_subscriptions' ) );

			add_shortcode( 'videowhisper_content_upload', array( 'VWpaidMembership', 'videowhisper_content_upload' ) );
			add_shortcode( 'videowhisper_content_list', array( 'VWpaidMembership', 'videowhisper_content_list' ) );

			add_shortcode( 'videowhisper_content_seller', array( 'VWpaidMembership', 'videowhisper_content_seller' ) ); // seller list
			add_shortcode( 'videowhisper_content', array( 'VWpaidMembership', 'videowhisper_content' ) ); // buyer list

			add_shortcode( 'videowhisper_membership_buy', array( 'VWpaidMembership', 'videowhisper_membership_buy' ) );
			add_shortcode( 'videowhisper_content_edit', array( 'VWpaidMembership', 'videowhisper_content_edit' ) );
			add_shortcode( 'videowhisper_my_wallet', array( 'VWpaidMembership', 'videowhisper_my_wallet' ) );

			add_shortcode( 'videowhisper_wallet', array( 'VWpaidMembership', 'videowhisper_wallet' ) );
			add_shortcode( 'videowhisper_donate', array( 'VWpaidMembership', 'videowhisper_donate' ) );
			add_shortcode( 'videowhisper_donate_progress', array( 'VWpaidMembership', 'videowhisper_donate_progress' ) );

			//buddypress / BuddyBoss
			if ( function_exists( 'bp_is_active' ) ) {
				
				//bp_activity_post_form_options - in form
				add_action ( "bp_after_activity_post_form", array( 'VWpaidMembership', 'bp_after_activity_post_form' ) ); //after form

				add_action( 'bp_setup_nav', array( 'VWpaidMembership', 'bp_setup_nav' ) );

				if ( bp_is_active( 'activity' ) ) {
					add_action( 'bp_register_activity_actions', array( 'VWpaidMembership', 'bp_register_activity_actions' ) );
				}
			}

			// woocommerce product_info //https://businessbloomer.com/woocommerce-visual-hook-guide-single-product-page/
			add_action( 'woocommerce_before_add_to_cart_form', array( 'VWpaidMembership', 'product_info' ) );

			// membership content
			if ( $options['hidePostThumbnail'] ?? false ) add_filter( 'post_thumbnail_html', array( 'VWpaidMembership', 'post_thumbnail_html' ), 10, 3);

			add_action( 'add_meta_boxes', array( 'VWpaidMembership', 'add_meta_boxes' ) );
			add_action( 'save_post', array( 'VWpaidMembership', 'save_post' ) );

			add_filter( 'the_content', array( 'VWpaidMembership', 'the_content' ), 1000 ); // high priority to run at end

			// downloads
			add_action( 'before_delete_post', array( 'VWpaidMembership', 'download_delete' ) );

			// download post page
			if ( $options['downloads'] ) {
				add_filter( 'the_content', array( 'VWpaidMembership', 'download_page' ) );
			}

			// ! download shortcodes
			add_shortcode( 'videowhisper_downloads', array( 'VWpaidMembership', 'videowhisper_downloads' ) );
			add_shortcode( 'videowhisper_download', array( 'VWpaidMembership', 'videowhisper_download' ) );
			add_shortcode( 'videowhisper_download_preview', array( 'VWpaidMembership', 'videowhisper_download_preview' ) );

			add_shortcode( 'videowhisper_download_upload', array( 'VWpaidMembership', 'videowhisper_download_upload' ) );
			add_shortcode( 'videowhisper_download_import', array( 'VWpaidMembership', 'videowhisper_download_import' ) );

			add_shortcode( 'videowhisper_postdownloads', array( 'VWpaidMembership', 'videowhisper_postdownloads' ) );
			add_shortcode( 'videowhisper_postdownloads_process', array( 'VWpaidMembership', 'videowhisper_postdownloads_process' ) );

			// ! widgets
			//wp_register_sidebar_widget( 'videowhisper_downloads', 'downloads', array( 'VWpaidMembership', 'widget_downloads' ), array( 'description' => 'List downloads and updates using AJAX.' ) );
			//wp_register_widget_control( 'videowhisper_downloads', 'videowhisper_downloads', array( 'VWpaidMembership', 'widget_downloads_options' ) );

			// plupload
			add_action( 'wp_ajax_vwpm_plupload', array( 'VWpaidMembership', 'vwpm_plupload' ) );
			add_action( 'wp_ajax_nopriv_vwpm_plupload', array( 'VWpaidMembership', 'vwpm_plupload' ) );

			// donate ajax
			add_action( 'wp_ajax_vwpm_donate', array( 'VWpaidMembership', 'vwpm_donate' ) );
			add_action( 'wp_ajax_nopriv_vwpm_donate', array( 'VWpaidMembership', 'vwpm_donate' ) );

			// content ajax
			add_action( 'wp_ajax_vwpm_content', array( 'VWpaidMembership', 'vwpm_content' ) );
			add_action( 'wp_ajax_nopriv_vwpm_content', array( 'VWpaidMembership', 'vwpm_content' ) );

			// ! downloads ajax

			// ajax downloads
			add_action( 'wp_ajax_vwpm_downloads', array( 'VWpaidMembership', 'vwpm_downloads' ) );
			add_action( 'wp_ajax_nopriv_vwpm_downloads', array( 'VWpaidMembership', 'vwpm_downloads' ) );

			// upload downloads
			add_action( 'wp_ajax_vwpm_upload', array( 'VWpaidMembership', 'vwpm_upload' ) );
			
			// check db
			$db_current_version = '2.4.7';

			$db_installed_ver = get_option( 'vw_micropayments_db' );

			if ( $db_installed_ver != $db_current_version ) {
				global $wpdb;

				$table_transactions = $wpdb->prefix . 'vw_micropay_transactions';
				
				$wpdb->flush();

				$sql = "
DROP TABLE IF EXISTS `$table_transactions`;
CREATE TABLE `$table_transactions` (
  `transaction_id` bigint(20) UNSIGNED NOT NULL auto_increment,
  `blog_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `currency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `details` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `initiator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
   PRIMARY KEY (`transaction_id`),
   KEY `user_id` (`user_id`),
   KEY `blog_id` (`blog_id`),
   KEY `date` (`date`)  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MicroPayments Transactions 2021@videowhisper.com' AUTO_INCREMENT=1;
";
				require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				dbDelta( $sql );

				if ( ! $db_installed_ver ) {
					add_option( 'vw_micropayments_db', $db_current_version );
				} else {
					update_option( 'vw_micropayments_db', $db_current_version );
				}

				$wpdb->flush();
				
				//end updating db
				}

			
		}
		
		static function post_thumbnail_html( $html, $post_id, $post_image_id ) 
		{
			return is_single() ? '' : $html;
		}

		static function bp_register_activity_actions() {
			bp_activity_set_action(
				/*
				 your component's id : same value as the "component" field you will use in
			the {$wpdb->prefix}bp_activity MySQL table */
				'micropayments',
				/*
				 your component's activity type :
				- same value as the "type" field you will use in the {$wpdb->prefix}bp_activity MySQL table
				- it will be used in the value attribute of the plugin option in the activity selectbox
				*/
				'donation',
				/*
				 your component's caption :
				- it will be displayed to the user in the activity selectbox
				*/
				__( 'Made a Donation', 'paid-membership' )
			);

				bp_activity_set_action(
					'micropayments',
					'product_new',
					__( 'Added a product', 'paid-membership' )
				);

				bp_activity_set_action(
					'micropayments',
					'content_new',
					__( 'Added content', 'paid-membership' )
				);

				bp_activity_set_action(
					'micropayments',
					'subscription_new',
					__( 'Subscribed to author ', 'paid-membership' )
				);
		}


		static function download_delete( $download_id ) {
			$options = get_option( 'VWpaidMembershipOptions' );
			if ( get_post_type( $download_id ) != $options['custom_post'] ) {
				return;
			}

			// delete source & thumb files
			$filePath = get_post_meta( $post_id, 'download-source-file', true );
			if ( file_exists( $filePath ) ) {
				unlink( $filePath );
			}
			$filePath = get_post_meta( $post_id, 'download-thumbnail', true );
			if ( file_exists( $filePath ) ) {
				unlink( $filePath );
			}
		}


		static function archive_template( $archive_template ) {
			global $post;

			$options = get_option( 'VWpaidMembershipOptions' );

			if ( get_query_var( 'taxonomy' ) != $options['custom_taxonomy'] ) {
				return $archive_template;
			}

			if ( $options['taxonomyTemplate'] == '+plugin' ) {
				$archive_template_new = dirname( __FILE__ ) . '/taxonomy-collection.php';
				if ( file_exists( $archive_template_new ) ) {
					return $archive_template_new;
				}
			}

			$archive_template_new = get_template_directory() . '/' . sanitize_text_field( $options['taxonomyTemplate'] );
			if ( file_exists( $archive_template_new ) ) {
				return $archive_template_new;
			} else {
				return $archive_template;
			}
		}


		static function posts_link_attributes() {
				  return 'class="ui button small"';
		}

		// ! Widgets

		static function widgetSetupOptions() {
			$widgetOptions = array(
				'title'           => '',
				'perpage'         => '8',
				'perrow'          => '',
				'collection'      => '',
				'order_by'        => '',
				'category_id'     => '',
				'select_category' => '1',
				'select_tags'     => '1',
				'select_name'     => '1',
				'select_order'    => '1',
				'select_page'     => '1',
				'include_css'     => '0',

			);

			$options = get_option( 'VWpaidMembershipWidgetOptions' );

			if ( ! empty( $options ) ) {
				foreach ( $options as $key => $option ) {
					$widgetOptions[ $key ] = $option;
				}
			}

			update_option( 'VWpaidMembershipWidgetOptions', $widgetOptions );

			return $widgetOptions;
		}


		static function widget_downloads_options( $args = array(), $params = array() ) {

			$options = self::widgetSetupOptions();

			if ( isset( $_POST ) ) {
				foreach ( $options as $key => $value ) {
					if ( isset( $_POST[ $key ] ) ) {
						$options[ sanitize_text_field( $key ) ] = trim( $_POST[ sanitize_text_field( $key ) ] );
					}
				}
					update_option( 'VWpaidMembershipWidgetOptions', $options );
			}
			?>

			<?php _e( 'Title', 'paid-membership' ); ?>:<br />
	<input type="text" class="widefat" name="title" value="<?php echo stripslashes( esc_attr( $options['title'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Collection', 'paid-membership' ); ?>:<br />
	<input type="text" class="widefat" name="collection" value="<?php echo stripslashes( esc_attr( $options['collection'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Category ID', 'paid-membership' ); ?>:<br />
	<input type="text" class="widefat" name="category_id" value="<?php echo stripslashes( esc_attr( $options['category_id'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Order By', 'paid-membership' ); ?>:<br />
	<select name="order_by" id="order_by">
  <option value="post_date" <?php echo $options['order_by'] == 'post_date' ? 'selected' : ''; ?>><?php _e( 'Date', 'paid-membership' ); ?></option>
	<option value="download-views" <?php echo $options['order_by'] == 'download-views' ? 'selected' : ''; ?>><?php _e( 'Views', 'paid-membership' ); ?></option>
	<option value="download-lastview" <?php echo $options['order_by'] == 'download-lastview' ? 'selected' : ''; ?>><?php _e( 'Recently Watched', 'paid-membership' ); ?></option>
</select><br /><br />

			<?php _e( 'Downloads per Page', 'paid-membership' ); ?>:<br />
	<input type="text" class="widefat" name="perpage" value="<?php echo stripslashes( esc_attr( $options['perpage'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Downloads per Row', 'paid-membership' ); ?>:<br />
	<input type="text" class="widefat" name="perrow" value="<?php echo stripslashes( esc_attr( $options['perrow'] ) ); ?>" />
	<br /><br />

			<?php _e( 'Category Selector', 'paid-membership' ); ?>:<br />
	<select name="select_category" id="select_category">
  <option value="1" <?php echo $options['select_category'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_category'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Tags Selector', 'paid-membership' ); ?>:<br />
	<select name="select_tags" id="select_order">
  <option value="1" <?php echo $options['select_tags'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_tags'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Name Selector', 'paid-membership' ); ?>:<br />
	<select name="select_name" id="select_name">
  <option value="1" <?php echo $options['select_name'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_name'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Order Selector', 'paid-membership' ); ?>:<br />
	<select name="select_order" id="select_order">
  <option value="1" <?php echo $options['select_order'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_order'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Page Selector', 'paid-membership' ); ?>:<br />
	<select name="select_page" id="select_page">
  <option value="1" <?php echo $options['select_page'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['select_page'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />

			<?php _e( 'Include CSS', 'paid-membership' ); ?>:<br />
	<select name="include_css" id="include_css">
  <option value="1" <?php echo $options['include_css'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['include_css'] ? '' : 'selected'; ?>>No</option>
</select><br /><br />
			<?php
		}


		static function widget_downloads( $args = array(), $params = array() ) {

			$options = get_option( 'VWpaidMembershipWidgetOptions' );

			echo esc_html( stripslashes( $args['before_widget'] ) );

			echo esc_html( stripslashes( $args['before_title'] ) );
			echo esc_html( stripslashes( esc_html( $options['title'] ) ) );
			echo esc_html( stripslashes( $args['after_title'] ) );

			echo do_shortcode( '[videowhisper_downloads collection="' . esc_attr( $options['collection'] ) . '" category_id="' . esc_attr( $options['category_id'] ) . '" order_by="' . esc_attr( $options['order_by'] ) . '" perpage="' . esc_attr( $options['perpage'] ) . '" perrow="' . esc_attr( $options['perrow'] ) . '" select_category="' . esc_attr( $options['select_category'] ) . '" select_order="' . esc_attr( $options['select_order'] ) . '" select_page="' . esc_attr( $options['select_page'] ) . '" include_css="' . esc_attr( $options['include_css'] ) . '"]' );

			echo esc_html( stripslashes( $args['after_widget'] ) );
		}


		// widgets:end


		// ! AJAX implementation
		static function scripts() {
			wp_enqueue_script( 'jquery' );
		}


		static function vwpm_downloads() {
			$options = get_option( 'VWpaidMembershipOptions' );

			$perPage = (int) $_GET['pp'];
			if ( ! $perPage ) {
				$perPage = intval( $options['perPage'] );
			}

			$collection = sanitize_file_name( $_GET['collection'] );

			$id = sanitize_file_name( $_GET['id'] );

			$category = (int) $_GET['cat'];

			$page   = (int) $_GET['p'];
			$offset = $page * $perPage;

			$perRow = (int) $_GET['pr'];

			// order
			$order_by = sanitize_file_name( $_GET['ob'] );
			if ( ! $order_by ) {
				$order_by = 'post_date';
			}

			// options
			$selectCategory = (int) $_GET['sc'];
			$selectOrder    = (int) $_GET['so'];
			$selectPage     = (int) $_GET['sp'];

			$selectName = (int) $_GET['sn'];
			$selectTags = (int) $_GET['sg'];

			// tags,name search
			$tags = sanitize_text_field( $_GET['tags'] );
			$name = sanitize_file_name( $_GET['name'] );
			if ( $name == 'undefined' ) {
				$name = '';
			}
			if ( $tags == 'undefined' ) {
				$tags = '';
			}

			// query
			$args = array(
				'post_type'      => $options['custom_post'],
				'post_status'    => 'publish',
				'posts_per_page' => $perPage,
				'offset'         => $offset,
				'order'          => 'DESC',
			);

			switch ( $order_by ) {
				case 'post_date':
					$args['orderby'] = 'post_date';
					break;

				case 'rand':
					$args['orderby'] = 'rand';
					break;

				default:
					$args['orderby']  = 'meta_value_num';
					$args['meta_key'] = $order_by;
					break;
			}

			if ( $collection ) {
				$args['collection'] = $collection;
			}

			if ( $category ) {
				$args['category'] = $category;
			}

			if ( $tags ) {
				$tagList = explode( ',', $tags );
				foreach ( $tagList as $key => $value ) {
					$tagList[ $key ] = trim( $tagList[ $key ] );
				}

				$args['tax_query'] = array(
					array(
						'taxonomy' => 'post_tag',
						'field'    => 'slug',
						'operator' => 'AND',
						'terms'    => $tagList,
					),
				);
			}

			if ( $name ) {
				$args['s'] = $name;
			}

			// user permissions
			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();
				if ( in_array( 'administrator', $current_user->roles ) ) {
					$isAdministrator = 1;
				}
				$isID = $current_user->ID;

				if ( is_plugin_active( 'paid-membership/paid-membership.php' ) ) {
					$pmEnabled = 1;
				}
			}

			// get items

			$postslist = get_posts( $args );

			ob_clean();
			// output

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_downloads&pp=' . $perPage . '&pr=' . $perRow . '&collection=' . urlencode( $collection ) . '&sc=' . $selectCategory . '&so=' . $selectOrder . '&sn=' . $selectName . '&sg=' . $selectTags . '&sp=' . $selectPage . '&id=' . $id;

			// without page: changing goes to page 1 but selection persists
			$ajaxurlC = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // sel ord
			$ajaxurlO = $ajaxurl . '&ob=' . $order_by . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // sel cat

			$ajaxurlCO = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by; // select tag name

			$ajaxurlA = $ajaxurl . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name );

			// options
			// echo '<div class="videowhisperListOptions">';

			// $htmlCode .= '<div class="ui form"><div class="inline fields">';
			echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' tiny equal width form"><div class="inline fields">';

			if ( $selectCategory ) {
				echo '<div class="field">' . wp_dropdown_categories( 'show_count=0&echo=0&name=category' . esc_attr( $id ) . '&hide_empty=1&class=ui+dropdown+fluid&show_option_all=' . __( 'All', 'paid-membership' ) . '&selected=' . esc_attr( $category ) ) . '</div>';
				echo '<script>var category' . esc_attr( $id ) . ' = document.getElementById("category' . esc_attr( $id ) . '"); 			category' . esc_attr( $id ) . '.onchange = function(){aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlO ) . '&cat=\'+ this.value; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading category...</div>\')}
			</script>';
			}

			if ( $selectOrder ) {
				echo '<div class="field"><select class="ui dropdown v-select fluid" id="order_by' . esc_attr( $id ) . '" name="order_by' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlC ) . '&ob=\'+ this.value; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Ordering downloads...</div>\')">';
				echo '<option value="">' . __( 'Order By', 'paid-membership' ) . ':</option>';
				echo '<option value="post_date"' . ( $order_by == 'post_date' ? ' selected' : '' ) . '>' . __( 'Date Added', 'paid-membership' ) . '</option>';
				echo '<option value="download-views"' . ( $order_by == 'download-views' ? ' selected' : '' ) . '>' . __( 'Views', 'paid-membership' ) . '</option>';
				echo '<option value="download-lastview"' . ( $order_by == 'download-lastview' ? ' selected' : '' ) . '>' . __( 'Viewed Recently', 'paid-membership' ) . '</option>';

				if ( $options['rateStarReview'] ) {
					echo '<option value="rateStarReview_rating"' . ( $order_by == 'rateStarReview_rating' ? ' selected' : '' ) . '>' . __( 'Rating', 'paid-membership' ) . '</option>';
					echo '<option value="rateStarReview_ratingNumber"' . ( $order_by == 'rateStarReview_ratingNumber' ? ' selected' : '' ) . '>' . __( 'Ratings Number', 'paid-membership' ) . '</option>';
					echo '<option value="rateStarReview_ratingPoints"' . ( $order_by == 'rateStarReview_ratingPoints' ? ' selected' : '' ) . '>' . __( 'Rate Popularity', 'paid-membership' ) . '</option>';
				}

				echo '<option value="rand"' . ( $order_by == 'rand' ? ' selected' : '' ) . '>' . __( 'Random', 'paid-membership' ) . '</option>';

				echo '</select></div>';
			}

			if ( $selectTags || $selectName ) {
				echo '<div class="field"></div>'; // separator

				if ( $selectTags ) {
					echo '<div class="field" data-tooltip="Tags, Comma Separated"><div class="ui left icon input fluid"><i class="tags icon"></i><INPUT class="videowhisperInput" type="text" size="12" name="tags" id="tags" placeholder="' . __( 'Tags', 'paid-membership' ) . '" value="' . esc_attr( htmlspecialchars( $tags ) ) . '">
					</div></div>';
				}

				if ( $selectName ) {
					echo '<div class="field"><div class="ui left corner labeled input fluid"><INPUT class="videowhisperInput" type="text" size="12" name="name" id="name" placeholder="' . __( 'Name', 'paid-membership' ) . '" value="' . esc_attr( htmlspecialchars( $name ) ) . '">
  <div class="ui left corner label">
    <i class="asterisk icon"></i>
  </div>
					</div></div>';
				}

				// search button
				echo '<div class="field" data-tooltip="Search by Tags and/or Name"><button class="ui icon button" type="submit" name="submit" id="submit" value="' . __( 'Search', 'paid-membership' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlCO ) . '&tags=\' + document.getElementById(\'tags\').value +\'&name=\' + document.getElementById(\'name\').value; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Searching downloads...</div>\')"><i class="search icon"></i></button></div>';
			}

			// reload button
			if ( $selectCategory || $selectOrder || $selectTags || $selectName ) {
				echo '<div class="field"></div> <div class="field" data-tooltip="Reload"><button class="ui icon button" type="submit" name="reload" id="reload" value="' . __( 'Reload', 'paid-membership' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '\'; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Reloading downloads...</div>\')"><i class="sync icon"></i></button></div>';
			}

			echo '</div></div>';

			// list
			if ( count( $postslist ) > 0 ) {
				$k = 0;
				foreach ( $postslist as $item ) {
					if ( $perRow ) {
						if ( $k ) {
							if ( $k % $perRow == 0 ) {
														echo '<br>';
							}
						}
					}

						$views = get_post_meta( $item->ID, 'download-views', true );
					if ( ! $views ) {
						$views = 0;
					}

					$age = self::humanAge( time() - strtotime( $item->post_date ) );

					$info   = '' . __( 'Title', 'paid-membership' ) . ': ' . sanitize_text_field( $item->post_title ) . "\r\n" . __( 'Age', 'paid-membership' ) . ': ' . sanitize_text_field( $age ) . "\r\n" . __( 'Views', 'paid-membership' ) . ': ' . sanitize_text_field( $views );
					$views .= ' ' . __( 'views', 'paid-membership' );

					$canEdit = 0;
					if ( $options['editContent'] ) {
						if ( $isAdministrator || $item->post_author == $isID ) {
							$canEdit = 1;
						}
					}

						echo '<div class="videowhisperDownload">';
					echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '"><div class="videowhisperDownloadTitle">' . esc_html( $item->post_title ) . '</div></a>';
					echo '<div class="videowhisperDownloadDate">' . esc_html( $age ) . '</div>';
					echo '<div class="videowhisperDownloadViews">' . esc_html( $views ) . '</div>';

					$ratingCode = '';
					if ( $options['rateStarReview'] ) {
						$rating = floatval( get_post_meta( $item->ID, 'rateStarReview_rating', true ) );
						$max    = 5;

						if ( $rating > 0 ) {
							echo '<div class="videowhisperDownloadRating"><div class="ui star rating readonly" data-rating="' . round( $rating * $max ) . '" data-max-rating="' . esc_attr( $max ) . '"></div></div>';
						}
					}

					if ( $pmEnabled && $canEdit ) {
						echo '<a href="' . add_query_arg( 'editID', $item->ID, get_permalink( $options['p_videowhisper_content_edit'] ) ) . '"><div class="videowhisperDownloadEdit">' . __( 'EDIT', 'paid-membership' ) . '</div></a>';
					}

					$imagePath = get_post_meta( $item->ID, 'download-thumbnail', true );
					if ( ! $imagePath || ! file_exists( $imagePath ) ) { // video thumbnail?
						$imagePath = plugin_dir_path( __FILE__ ) . 'default_picture.png';
						self::updatePostThumbnail( $item->ID );
					} else // what about featured image?
						{
						$post_thumbnail_id = get_post_thumbnail_id( $item->ID );
						if ( $post_thumbnail_id ) {
							$post_featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'featured_preview' );
						}

						if ( ! $post_featured_image ) {
							self::updatePostThumbnail( $item->ID );
						}
					}

					echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '"><IMG src="' . esc_url_raw( self::path2url( $imagePath ) . $noCache ) . '" width="' . esc_attr( $options['thumbWidth'] ) . 'px" height="' . esc_attr( $options['thumbHeight'] ) . 'px" ALT="' . esc_attr( $info ) . '"></a>';

					echo '</div>
					';

					$k++;
				}
			} else {
				echo __( 'No downloads.', 'paid-membership' );
			}

			// pagination
			if ( $selectPage ) {
				echo '<BR style="clear:both"><div class="ui form"><div class="inline fields">';

				if ( $page > 0 ) {
					echo ' <a class="ui labeled icon button" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '&p=' . intval( $page - 1 ) . '\'; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading previous page...</div>\');"><i class="left arrow icon"></i> ' . __( 'Previous', 'paid-membership' ) . '</a> ';
				}

				echo '<a class="ui labeled button" href="#"> ' . __( 'Page', 'paid-membership' ) . ' ' . intval( $page + 1 ) . ' </a>';

				if ( count( $postslist ) >= $perPage ) {
					echo ' <a class="ui right labeled icon button" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '&p=' . intval( $page + 1 ) . '\'; loadDownloads' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading next page...</div>\');">' . __( 'Next', 'paid-membership' ) . ' <i class="right arrow icon"></i></a> ';
				}
			}

			// output end
			die;
		}


		// ajax:end


		// ! Download Shortcodes

		static function videowhisper_downloads( $atts ) {

			$options = get_option( 'VWpaidMembershipOptions' );

			$atts = shortcode_atts(
				array(
					'perpage'         => $options['perPage'],
					'perrow'          => '',
					'collection'      => '',
					'order_by'        => '',
					'category_id'     => '',
					'select_category' => '1',
					'select_order'    => '1',
					'select_page'     => '1', // pagination
					'select_tags'     => '1',
					'select_name'     => '1',
					'include_css'     => '1',
					'tags'            => '',
					'name'            => '',
					'id'              => '',
				),
				$atts,
				'videowhisper_downloads'
			);

			$id = $atts['id'];
			if ( ! $id ) {
				$id = uniqid();
			}

			self::enqueueUI();

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_downloads&pp=' . $atts['perpage'] . '&pr=' . $atts['perrow'] . '&collection=' . urlencode( $atts['collection'] ) . '&ob=' . $atts['order_by'] . '&cat=' . $atts['category_id'] . '&sc=' . $atts['select_category'] . '&so=' . $atts['select_order'] . '&sp=' . $atts['select_page'] . '&sn=' . $atts['select_name'] . '&sg=' . $atts['select_tags'] . '&id=' . $id . '&tags=' . urlencode( $atts['tags'] ) . '&name=' . urlencode( $atts['name'] );

			$htmlCode = <<<HTMLCODE
<script type="text/javascript">
var aurl$id = '$ajaxurl';
var \$j = jQuery.noConflict();
var loader$id;

	function loadDownloads$id(message){

	if (message)
	if (message.length > 0)
	{
	  \$j("#videowhisperDownloads$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = \$j.ajax({
			url: aurl$id,
			success: function(data) {
				\$j("#videowhisperDownloads$id").html(data);
				jQuery(".ui.dropdown").dropdown();
				jQuery(".ui.rating.readonly").rating("disable");
			}
		});
	}


	\$j(function(){
		loadDownloads$id();
		setInterval("loadDownloads$id('')", 60000);
	});

</script>

<div id="videowhisperDownloads$id">
    Loading downloads...
</div>

HTMLCODE;

			if ( $atts['include_css'] ) {
				$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['downloadsCSS'] ) ) . '</STYLE>';
			}

			return $htmlCode;
		}


		static function videowhisper_download( $atts ) {
			$atts = shortcode_atts( array( 'download' => '0' ), $atts, 'videowhisper_download' );

			$download_id = intval( $atts['download'] );
			if ( ! $download_id ) {
				return 'shortcode_preview: Missing download id!';
			}

			$download = get_post( $download_id );
			if ( ! $download ) {
				return 'shortcode_preview: download #' . $download_id . ' not found!';
			}

			$options = get_option( 'VWpaidMembershipOptions' );

			// Access Control
			$deny = '';

			// global
			if ( ! self::hasPriviledge( $options['watchList'] ) ) {
				$deny = 'Your current membership does not allow accessing downloads.';
			}

			// by collections
			$lists = wp_get_post_terms( $download_id, $options['custom_taxonomy'], array( 'fields' => 'names' ) );

			if ( ! is_array( $lists ) ) {
				if ( is_wp_error( $lists ) ) {
					echo 'Error: Can not retrieve "' . esc_html( $options['custom_taxonomy'] ) . '" terms for video post: ' . esc_html( $lists->get_error_message() );
				}

				$lists = array();
			}

			// collection role required?
			if ( $options['role_collection'] ) {
				foreach ( $lists as $key => $collection ) {
					$lists[ $key ] = $collection = strtolower( trim( $collection ) );

					// is role
					if ( get_role( $collection ) ) { // video defines access roles
						$deny = 'This download requires special membership. Your current membership: ' . self::getRoles() . '.';
						if ( self::hasRole( $collection ) ) { // has required role
							$deny = '';
							break;
						}
					}
				}
			}

			// exceptions
			if ( in_array( 'free', $lists ) ) {
				$deny = '';
			}

			if ( in_array( 'registered', $lists ) ) {
				if ( is_user_logged_in() ) {
					$deny = '';
				} else {
					$deny = 'Only registered users can watch this download. Please login first.' . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
				}
			};

			if ( in_array( 'unpublished', $lists ) ) {
				$deny = 'This download has been unpublished.';
			}

			if ( $deny ) {
				$htmlCode .= str_replace( '#info#', $deny, html_entity_decode( stripslashes( $options['accessDenied'] ) ) );
				$htmlCode .= '<br>';
				$htmlCode .= do_shortcode( '[videowhisper_download_preview download="' . $download_id . '"]' ) . self::poweredBy();
				return $htmlCode;
			}

			// update stats
			$views = get_post_meta( $download_id, 'download-views', true );
			if ( ! $views ) {
				$views = 0;
			}
			$views++;
			update_post_meta( $download_id, 'download-views', $views );
			update_post_meta( $download_id, 'download-lastview', time() );

			// display download:
			$thumbPath = get_post_meta( $download_id, 'download-thumbnail', true );

			// download
			$downloadPath = get_post_meta( $download_id, 'download-source-file', true );
			if ( $downloadPath ) {
				if ( file_exists( $downloadPath ) ) {
					$downloadURL = self::path2url( $downloadPath );
				}
			}

			$htmlCode .= '<div class="ui ' . $options['interfaceClass'] . ' segment">';

			$htmlCode .= '<IMG class="ui left floated image" SRC="' . self::path2url( $thumbPath ) . '" width="' . $options['thumbWidth'] . 'px" height="' . $options['thumbHeight'] . 'px" />';
			$htmlCode .= '<p>' . $download->post_content . '</p>';

			$htmlCode .= '<div class="ui divider"></div> <A class="ui button primary" HREF="' . $downloadURL . '"> <i class="cloud download icon"></i>' . __( 'Download', 'paid-membership' ) . '</A>';

			$htmlCode .= '</div>';

			return $htmlCode;
		}


		// ! update this
		static function videowhisper_download_preview( $atts ) {
			$atts = shortcode_atts( array( 'download' => '0' ), $atts, 'videowhisper_download_preview' );

			$download_id = intval( $atts['download'] );
			if ( ! $download_id ) {
				return 'shortcode_preview: Missing download id!';
			}

			$download = get_post( $download_id );
			if ( ! $download ) {
				return 'shortcode_preview: download #' . $download_id . ' not found!';
			}

			$options = get_option( 'VWpaidMembershipOptions' );

			// res
			$vWidth  = $options['thumbWidth'];
			$vHeight = $options['thumbHeight'];

			// snap
			$imagePath = get_post_meta( $download_id, 'download-snapshot', true );
			if ( $imagePath ) {
				if ( file_exists( $imagePath ) ) {
					$imageURL = self::path2url( $imagePath );
				} else {
					self::updatePostThumbnail( $update_id );
				}
			}

			if ( ! $imagePath ) {
				$imageURL = self::path2url( plugin_dir_path( __FILE__ ) . 'default_picture.png' );
			}
				$download_url = get_permalink( $download_id );

			$htmlCode = "<a href='$download_url'><IMG SRC='$imageURL' width='$vWidth' height='$vHeight'></a>";

			return $htmlCode;
		}



		static function videowhisper_download_import( $atts ) {

			if ( ! is_user_logged_in() ) {
				return __( 'Login is required to import downloads!', 'paid-membership' );
			}

			$current_user = wp_get_current_user();

			$options = get_option( 'VWpaidMembershipOptions' );

			if ( ! $options['downloads'] ) {
				return __( 'Downloads are disabled from plugin settings!', 'paid-membership' );
			}

			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return __( 'You do not have permissions to share downloads!', 'paid-membership' );
			}

			$atts = shortcode_atts(
				array(
					'category'    => '',
					'collection'  => '',
					'owner'       => '',
					'path'        => '',
					'prefix'      => '',
					'tag'         => '',
					'picture'     => '',
					'description' => '',
				),
				$atts,
				'videowhisper_download_import'
			);

			if ( ! $atts['path'] ) {
				return 'videowhisper_download_import: Path required!';
			}

			if ( ! file_exists( $atts['path'] ) ) {
				return 'videowhisper_download_import: Path not found!';
			}

			if ( $atts['category'] ) {
				$categories = '<input type="hidden" name="category" id="category" value="' . $atts['category'] . '"/>';
			} else {
				$categories = '<label for="category">' . __( 'Category', 'paid-membership' ) . ': </label><div class="">' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown' ) . '</div>';
			}

			if ( $atts['collection'] ) {
				$collections = '<br><label for="collection">' . __( 'collection', 'paid-membership' ) . ': </label>' . $atts['collection'] . '<input type="hidden" name="collection" id="collection" value="' . $atts['collection'] . '"/>';
			} elseif ( current_user_can( 'edit_posts' ) ) {
				$collections = '<br><label for="collection">collection(s): </label> <br> <input size="48" maxlength="64" type="text" name="collection" id="collection" value="' . $username . '"/> ' . __( '(comma separated)', 'paid-membership' );
			} else {
				$collections = '<br><label for="collection">' . __( 'collection', 'paid-membership' ) . ': </label> ' . $username . ' <input type="hidden" name="collection" id="collection" value="' . $username . '"/> ';
			}

			if ( $atts['owner'] ) {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $atts['owner'] . '"/>';
			} else {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $current_user->ID . '"/>';
			}

			if ( $atts['tag'] != '_none' ) {
				if ( $atts['tag'] ) {
					$tags = '<br><label for="collection">' . __( 'Tags', 'paid-membership' ) . ': </label>' . $atts['tag'] . '<input type="hidden" name="tag" id="tag" value="' . $atts['tag'] . '"/>';
				} else {
					$tags = '<br><label for="tag">' . __( 'Tag(s)', 'paid-membership' ) . ': </label> <br> <input size="48" maxlength="64" type="text" name="tag" id="tag" value=""/> (comma separated)';
				}
			}

			if ( $atts['picture'] != '_none' ) {
				if ( $atts['picture'] ) {
					$pictures = '<input type="hidden" name="picture" id="picture" value="' . $atts['picture'] . '"/>';
				} else {
					$pictures = '<div class="field><label for="picture">' . __( 'Picture', 'paid-membership' ) . ' </label> ' . self::pictureDropdown( $current_user->ID, 0 ) . '</div>';
				}
			} else {
				$pictures = '<input type="hidden" name="picture" id="picture" value="0"/>';
			}

			if ( $atts['description'] != '_none' ) {
				if ( $atts['description'] ) {
					$descriptions = '<br><label for="description">' . __( 'Description', 'paid-membership' ) . ': </label>' . $atts['description'] . '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/>';
				} else {
					$descriptions = '<br><label for="description">' . __( 'Description', 'paid-membership' ) . ': </label> <br> <input size="48" maxlength="256" type="text" name="description" id="description" value=""/>';
				}
			}

						$url = get_permalink();

					$htmlCode .= '<h3>' . __( 'Import downloads', 'paid-membership' ) . '</h3>' . $atts['path'] . $atts['prefix'];

				$htmlCode .= '<form action="' . $url . '" method="post">';

				$htmlCode .= $categories;
				$htmlCode .= $collections;
				$htmlCode .= $tags;
				$htmlCode .= $pictures;
				$htmlCode .= $descriptions;
				$htmlCode .= $owners;

				$htmlCode .= '<br>' . self::importFilesSelect( $atts['prefix'], self::extensions_download(), $atts['path'] );

				$htmlCode .= '<INPUT class="button button-primary" TYPE="submit" name="import" id="import" value="Import">';

				$htmlCode .= '<INPUT class="button button-primary" TYPE="submit" name="delete" id="delete" value="Delete">';

				$htmlCode .= '</form>';

			// $htmlCode .= html_entity_decode(stripslashes($options['customCSS']));

				return $htmlCode;
		}


		static function pictureDropdown( $userID, $default ) {
			$htmlCode = '';
			$htmlCode .= '<select class="ui dropdown v-select fluid" name="picture" id="picture">';
			$htmlCode .= '<option value="0" ' . ( $default ? '' : 'selected' ) . '>' . __( 'Default', 'paid-membership' ) . '</option>';

			$optionsPictures = get_option( 'VWpictureGalleryOptions' );

			if ( $optionsPictures ) {
				$args = array(
					'post_type'      => $optionsPictures['custom_post'],
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'post_author'    => $userID,
				);

				$postslist = get_posts( $args );

				if ( count( $postslist ) > 0 ) {
					$k = 0;
					foreach ( $postslist as $item ) {
						$htmlCode .= '<option value="' . $item->ID . '" ' . ( $default == $item->ID ? 'selected' : '' ) . '>' . esc_html( $item->post_title ) . '</option>';
					}
				}
			} else {
				$htmlCode .= '<option value="0" disabled>' . __( 'Install Picture Gallery plugin', 'paid-membership' ) . '</option>';
			}

			$htmlCode .= '</select>';

			return $htmlCode;
		}


		static function videowhisper_download_upload( $atts ) {

			if ( ! is_user_logged_in() ) {
				return __( 'Login is required to add downloads!', 'paid-membership' );
			}

			$options = self::getOptions();
			
			$current_user = wp_get_current_user();
			$userName     = $options['userName'];
			if ( ! $userName ) {
				$userName = 'user_nicename';
			}
			$username = $current_user->$userName;


			if ( ! $options['downloads'] ) {
				return __( 'Downloads are disabled from plugin settings!', 'paid-membership' );
			}

			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return __( 'You do not have permissions to share downloads!', 'paid-membership' );
			}

			$atts = shortcode_atts(
				array(
					'category'    => '',
					'collection'  => '',
					'owner'       => '',
					'tag'         => '',
					'picture'     => '',
					'description' => '',
				),
				$atts,
				'videowhisper_download_upload'
			);

			$htmlCode = '';

			self::enqueueUI();

			$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_upload';

			if ( $atts['category'] ) {
				$categories = '<input type="hidden" name="category" id="category" value="' . $atts['category'] . '"/>';
			} else {
				$categories = '<div class="field><label for="category">' . __( 'Category', 'paid-membership' ) . ' </label> ' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown+fluid' ) . '</div>';
			}

			if ( $atts['collection'] ) {
				$collections = '<label for="collection">' . __( 'collection', 'paid-membership' ) . '</label>' . $atts['collection'] . '<input type="hidden" name="collection" id="collection" value="' . $atts['collection'] . '"/>';
			} elseif ( current_user_can( 'edit_users' ) ) {
				$collections = '<br><label for="collection">' . __( 'Collection(s)', 'paid-membership' ) . '</label> <br> <input size="48" maxlength="64" type="text" name="collection" id="collection" value="' . $username . '" class="text-input"/> (comma separated)';
			} else {
				$collections = '<label for="collection">' . __( 'collection', 'paid-membership' ) . '</label> ' . $username . ' <input type="hidden" name="collection" id="collection" value="' . $username . '"/> ';
			}

			if ( $atts['owner'] ) {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $atts['owner'] . '"/>';
			} else {
				$owners = '<input type="hidden" name="owner" id="owner" value="' . $current_user->ID . '"/>';
			}

			if ( $atts['tag'] != '_none' ) {
				if ( $atts['tag'] ) {
					$tags = '<br><label for="collection">' . __( 'Tags', 'paid-membership' ) . '</label>' . $atts['tag'] . '<input type="hidden" name="tag" id="tag" value="' . $atts['tag'] . '"/>';
				} else {
					$tags = '<br><label for="tag">' . __( 'Tag(s)', 'paid-membership' ) . '</label> <br> <input size="48" maxlength="64" type="text" name="tag" id="tag" value="" class="text-input"/> (comma separated)';
				}
			}

			if ( $atts['picture'] != '_none' ) {
				if ( $atts['picture'] ) {
					$pictures = '<input type="hidden" name="picture" id="picture" value="' . $atts['picture'] . '"/>';
				} else {
					$pictures = '<div class="field><label for="picture">' . __( 'Picture', 'paid-membership' ) . ' </label> ' . self::pictureDropdown( $current_user->ID, 0 ) . '</div>';
				}
			} else {
				$pictures = '<input type="hidden" name="picture" id="picture" value="0"/>';
			}

			if ( $atts['description'] != '_none' ) {
				if ( $atts['description'] ) {
					$descriptions = '<br><label for="description">' . __( 'Description', 'paid-membership' ) . '</label>' . $atts['description'] . '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/>';
				} else {
					$descriptions = '<br><label for="description">' . __( 'Description', 'paid-membership' ) . '</label> <br> <input size="48" maxlength="256" type="text" name="description" id="description" value="" class="text-input"/>';
				}
			}

						$iPod = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPod' );
					$iPhone   = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' );
				$iPad         = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPad' );
				$Android      = stripos( $_SERVER['HTTP_USER_AGENT'], 'Android' );

			if ( $iPhone || $iPad || $iPod || $Android ) {
				$mobile = true;
			} else {
				$mobile = false;
			}

			$accepts = '';

			if ( $mobile ) {
				// https://mobilehtml5.org/ts/?id=23
				$mobiles = 'capture="camera"';
				// $accepts = 'accept="image/*;capture=camera"';
				$multiples = '';
				$filedrags = '';
			} else {
				$mobiles = '';
				// $accepts = 'accept="image/*"';
				$multiples = 'multiple="multiple"';
				$filedrags = '<div id="filedrag">' . __( 'or Drag & Drop files to this upload area<br>(select rest of options first)', 'paid-membership' ) . '</div>';
			}

				wp_enqueue_script( 'vwpm-upload', plugin_dir_url( __FILE__ ) . 'upload.js' );

				$submits = '<div id="submitbutton">
	<button class="ui button" type="submit" name="upload" id="upload">' . __( 'Upload Files', 'paid-membership' ) . '</button>';

				$htmlCode .= <<<EOHTML
<form class="ui form" id="upload" action="$ajaxurl" method="POST" enctype="multipart/form-data">

<fieldset>
$categories
$collections
$tags
$pictures
$descriptions
$owners
<input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="128000000" />
EOHTML;

				$htmlCode .= '<legend><h3>' . __( 'Add Download: File Upload', 'paid-membership' ) . '</h3></legend><div> <label for="fileselect">' . __( 'Files to upload', 'paid-membership' ) . '</label>';

				$htmlCode .= <<<EOHTML
	<br><input class="ui button" type="file" id="fileselect" name="fileselect[]" $mobiles $multiples $accepts />
$filedrags
$submits
</div>
EOHTML;

				$htmlCode .= '<p>' . __( 'Supported Extensions', 'paid-membership' ) . ': ' . $options['download_extensions'] . '</p>';

				$htmlCode .= <<<EOHTML
<div id="progress"></div>

</fieldset>
</form>

<script>
jQuery(document).ready(function(){
jQuery(".ui.dropdown").dropdown();
});
</script>


<STYLE>

#filedrag
{
 height: 100px;
 border: 1px solid #AAA;
 border-radius: 9px;
 color: #333;
 background: #eee;
 padding: 5px;
 margin-top: 5px;
 text-align:center;
}

#progress
{
padding: 4px;
margin: 4px;
}

#progress div {
	position: relative;
	background: #555;
	-moz-border-radius: 9px;
	-webkit-border-radius: 9px;
	border-radius: 9px;

	padding: 4px;
	margin: 4px;

	color: #DDD;

}

#progress div > span {
	display: block;
	height: 20px;

	   -webkit-border-top-right-radius: 4px;
	-webkit-border-bottom-right-radius: 4px;
	       -moz-border-radius-topright: 4px;
	    -moz-border-radius-bottomright: 4px;
	           border-top-right-radius: 4px;
	        border-bottom-right-radius: 4px;
	    -webkit-border-top-left-radius: 4px;
	 -webkit-border-bottom-left-radius: 4px;
	        -moz-border-radius-topleft: 4px;
	     -moz-border-radius-bottomleft: 4px;
	            border-top-left-radius: 4px;
	         border-bottom-left-radius: 4px;

	background-color: rgb(43,194,83);

	background-image:
	   -webkit-gradient(linear, 0 0, 100% 100%,
	      color-stop(.25, rgba(255, 255, 255, .2)),
	      color-stop(.25, transparent), color-stop(.5, transparent),
	      color-stop(.5, rgba(255, 255, 255, .2)),
	      color-stop(.75, rgba(255, 255, 255, .2)),
	      color-stop(.75, transparent), to(transparent)
	   );

	background-image:
		-webkit-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-moz-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-ms-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	background-image:
		-o-linear-gradient(
		  -45deg,
	      rgba(255, 255, 255, .2) 25%,
	      transparent 25%,
	      transparent 50%,
	      rgba(255, 255, 255, .2) 50%,
	      rgba(255, 255, 255, .2) 75%,
	      transparent 75%,
	      transparent
	   );

	position: relative;
	overflow: hidden;
}

#progress div.success
{
    color: #DDD;
	background: #3C6243 none 0 0 no-repeat;
}

#progress div.failed
{
 	color: #DDD;
	background: #682C38 none 0 0 no-repeat;
}
</STYLE>
EOHTML;

			// $htmlCode .= html_entity_decode(stripslashes($options['customCSS']));

				return $htmlCode;
		}


			static function generateName( $fn ) {
				$ext = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );

				// unpredictable name
				return md5( uniqid( $fn, true ) ) . '.' . $ext;
			}
			
		static function vwpm_upload() {
			ob_clean();

			echo 'Upload completed... ';

			$options = get_option( 'VWpaidMembershipOptions' );
			if ( ! $options['downloads'] ) {
				return __( 'Downloads are disabled from plugin settings!', 'paid-membership' );
			}

			$current_user = wp_get_current_user();

			if ( ! is_user_logged_in() ) {
				echo 'Login required!';
				exit;
			}

			$owner = $_SERVER['HTTP_X_OWNER'] ? intval( $_SERVER['HTTP_X_OWNER'] ) : intval( $_POST['owner'] );

			if ( $owner && ! current_user_can( 'edit_users' ) && $owner != $current_user->ID ) {
				echo 'Only admin can upload for others!';
				exit;
			}
			if ( ! $owner ) {
				$owner = $current_user->ID;
			}

			$collection = sanitize_text_field( $_SERVER['HTTP_X_COLLECTION'] ? $_SERVER['HTTP_X_COLLECTION'] : $_POST['collection'] );

			// if csv sanitize as array
			if ( strpos( $collection, ',' ) !== false ) {
				$collections = explode( ',', $collection );
				foreach ( $collections as $key => $value ) {
					$collections[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$collection = $collections;
			}

			if ( ! $collection ) {
				echo 'Collection required!';
				exit;
			}

			$category = $_SERVER['HTTP_X_CATEGORY'] ? sanitize_file_name( $_SERVER['HTTP_X_CATEGORY'] ) : sanitize_file_name( $_POST['category'] );

			$picture = $_SERVER['HTTP_X_PICTURE'] ? sanitize_file_name( $_SERVER['HTTP_X_PICTURE'] ) : sanitize_file_name( $_POST['picture'] );

			$tag = sanitize_text_field( $_SERVER['HTTP_X_TAG'] ? $_SERVER['HTTP_X_TAG'] : $_POST['tag'] );

			// if csv sanitize as array
			if ( strpos( $tag, ',' ) !== false ) {
				$tags = explode( ',', $tag );
				foreach ( $tags as $key => $value ) {
					$tags[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$tag = $tags;
			}

			$description = sanitize_text_field( $_SERVER['HTTP_X_DESCRIPTION'] ? $_SERVER['HTTP_X_DESCRIPTION'] : $_POST['description'] );

			$dir = sanitize_text_field( $options['uploadsPath'] );
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/uploads';
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/';

			ob_clean();
			$fn = ( isset( $_SERVER['HTTP_X_FILENAME'] ) ? $_SERVER['HTTP_X_FILENAME'] : false );

			$path = '';

			if ( $fn ) { // filename
				$ext = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, self::extensions_download() ) ) {
					echo 'Extension not allowed: ' . esc_html( $ext );
					exit;
				}

				// AJAX call
				$rawdata = $GLOBALS['HTTP_RAW_POST_DATA'] ?? null;
				if ( ! $rawdata ) {
					$rawdata = file_get_contents( 'php://input' );
				}

				if ( ! $rawdata ) {
					echo 'Raw post data missing!';
					exit;
				}

				file_put_contents( $path = $dir . self::generateName( $fn ), $rawdata );

				$el    = array_shift( explode( '.', $fn ) );
				$title = ucwords( str_replace( '-', ' ', sanitize_file_name( $el ) ) );

				echo esc_html( $title ) . ' ';

				echo wp_kses_post( self::importFile( $path, $title, $owner, $collection, $category, $tag, $description, $picture ) );
			} else {
				// form submit
				$files = $_FILES['fileselect'];

				if ( $files['error'] ?? false ) {
					if ( is_array( $files['error'] ) ) {
						foreach ( $files['error'] as $id => $err ) {
							if ( $err == UPLOAD_ERR_OK ) {
								$fn = $files['name'][ $id ];

								$ext = strtolower( pathinfo( $fn, PATHINFO_EXTENSION ) );
								if ( ! in_array( $ext, self::extensions_download() ) ) {
									echo 'Extension not allowed: ' . esc_html( $ext );
									exit;
								}

								$path = $dir . self::generateName( $fn );
								move_uploaded_file( $files['tmp_name'][ $id ], $path );
								$title = ucwords( str_replace( '-', ' ', sanitize_file_name( array_shift( explode( '.', $fn ) ) ) ) );

								echo esc_html( $title ) . ' ';

								echo wp_kses_post( self::importFile( $path, $title, $owner, $collection, $category, $picture ) ) . '<br>';
							}
						}
					}
				}
			}

			die;
		}


		public function importFile( $path, $name, $owner, $collections, $category = '', $tags = '', $description = '', $picture = '', &$post_id = null, $guest = false) {
			
			if (!$guest)
			{
				if ( ! $owner ) {
					return '<br>Missing owner! Specify owner id or guest mode.';
				}
				if ( ! $collections ) {
					return '<br>Missing collections!';
				}
			}

			if (!$owner) $owner = 0;
			if (!$collections) $collections = 'Guest';

			$options = get_option( 'VWpaidMembershipOptions' );
			if ( ! self::hasPriviledge( $options['shareList'] ) ) {
				return '<br>' . __( 'You do not have permissions to share downloads!', 'paid-membership' );
			}

			if ( ! file_exists( $path ) ) {
				return "<br>$name: File missing: $path";
			}

			$download = intval( $download );

			// handle one or many collections
			if ( is_array( $collections ) ) {
				$collection = sanitize_file_name( current( $collections ) );
			} else {
				$collection = sanitize_file_name( $collections );
			}

			if ( ! $collection ) {
				return '<br>Missing collection!';
			}

			$htmlCode .= 'File import: ';

			// uploads/owner/collection/src/file
			$dir = sanitize_text_field( $options['uploadsPath'] );
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/' . $owner;
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			$dir .= '/' . $collection;
			if ( ! file_exists( $dir ) ) {
				mkdir( $dir );
			}

			// $dir .= '/src';
			// if (!file_exists($dir)) mkdir($dir);

			if ( ! $ztime = filemtime( $path ) ) {
				$ztime = time();
			}

			$ext     = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
			$newFile = md5( uniqid( $owner, true ) ) . '.' . $ext;
			$newPath = $dir . '/' . $newFile;

			// $htmlCode .= "<br>Importing $name as $newFile ... ";

			if ( $options['deleteOnImport'] ) {
				if ( ! rename( $path, $newPath ) ) {
					$htmlCode .= 'Rename failed. Trying copy ...';
					if ( ! copy( $path, $newPath ) ) {
						$htmlCode .= 'Copy also failed. Import failed!';
						return $htmlCode;
					}
					// else $htmlCode .= 'Copy success ...';

					if ( ! unlink( $path ) ) {
						$htmlCode .= 'Removing original file failed!';
					}
				}
			} else {
				// just copy
				if ( ! copy( $path, $newPath ) ) {
					$htmlCode .= 'Copy failed. Import failed!';
					return $htmlCode;
				}
			}

			// $htmlCode .= 'Moved source file ...';

			$timeZone = get_option( 'gmt_offset' ) * 3600;
			$postdate = date( 'Y-m-d H:i:s', $ztime + $timeZone );

			$post = array(
				'post_name'    => $name,
				'post_title'   => $name,
				'post_author'  => $owner,
				'post_type'    => $options['custom_post'],
				'post_status'  => 'publish',
				// 'post_date'   => $postdate,
				'post_content' => $description,
			);

			if ( ! self::hasPriviledge( $options['publishList'] ) ) {
				$post['post_status'] = 'pending';
			}

			$post_id = wp_insert_post( $post );
			if ( $post_id ) {
				update_post_meta( $post_id, 'download-source-file', $newPath );

				wp_set_object_terms( $post_id, $collections, $options['custom_taxonomy'] );

				if ( $tags ) {
					wp_set_object_terms( $post_id, $tags, 'post_tag' );
				}

				if ( $category ) {
					wp_set_post_categories( $post_id, array( $category ) );
				}

				update_post_meta( $post_id, 'download-picture', $picture );

				self::updatePostThumbnail( $post_id, true, false );

				if ( $post['post_status'] == 'pending' ) {
					$htmlCode .= __( 'Download was submitted and is pending approval.', 'paid-membership' );
				} else {
					$htmlCode .= '<br>' . __( 'Download was published', 'paid-membership' ) . ': <a href=' . get_post_permalink( $post_id ) . '> #' . $post_id . ' ' . $name . '</a>';
				}
			} else {
				$htmlCode .= '<br>Picture post creation failed!';
			}

			return $htmlCode . ' .';
		}


		static function imagecreatefromfile( $filename ) {
			if ( ! file_exists( $filename ) ) {
				throw new InvalidArgumentException( 'File "' . $filename . '" not found.' );
			}

			switch ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
				case 'jpeg':
				case 'jpg':
					return $img = @imagecreatefromjpeg( $filename );
				break;

				case 'png':
					return $img = @imagecreatefrompng( $filename );
				break;

				case 'gif':
					return $img = @imagecreatefromgif( $filename );
				break;

				default:
					throw new InvalidArgumentException( 'File "' . $filename . '" is not valid jpg, png or gif image.' );
				break;
			}

			return $img;
		}


		static function generateThumbnail( $src, $dest, $post_id = 0 ) {
			// png with alpha
			if ( ! file_exists( $src ) ) {
				return;
			}

			$options = get_option( 'VWpaidMembershipOptions' );

			// generate thumb
			$thumbWidth  = $options['thumbWidth'];
			$thumbHeight = $options['thumbHeight'];

			$srcImage = self::imagecreatefromfile( $src );
			if ( ! $srcImage ) {
				return;
			}

			list($width, $height) = @getimagesize( $src );
			if ( ! $width ) {
				return;
			}

			$destImage = imagecreatetruecolor( $thumbWidth, $thumbHeight );
			imagealphablending( $destImage, false );
			imagesavealpha( $destImage, true );
			$transparent = imagecolorallocatealpha( $destImage, 255, 255, 255, 127 );
			imagefilledrectangle( $destImage, 0, 0, $thumbWidth, $thumbHeight, $transparent );

			imagecopyresampled( $destImage, $srcImage, 0, 0, 0, 0, $thumbWidth, $thumbHeight, $width, $height );
			imagepng( $destImage, $dest );

			if ( $post_id ) {
				update_post_meta( $post_id, 'download-thumbnail', $dest );
				if ( $width ) {
					update_post_meta( $post_id, 'download-width', $width );
				}
				if ( $height ) {
					update_post_meta( $post_id, 'download-height', $height );
				}
			}

			// return source dimensions
			return array( $width, $height );
		}


		static function updatePostThumbnail( $post_id, $overwrite = false, $verbose = false ) {
			$options = get_option( 'VWpaidMembershipOptions' );

			// update post image
			$picture = get_post_meta( $post_id, 'download-picture', true );

			if ( $picture ) {
				$imagePath = get_post_meta( intval( $picture ), 'picture-source-file', true );
			}

			if ( ! $imagePath ) {
				$imagePath = plugin_dir_path( __FILE__ ) . 'default_picture.png';
			}

			$thumbPath = get_post_meta( $post_id, 'download-thumbnail', true );

			if ( $verbose ) {
				echo '<br>' . esc_html( "Updating thumbnail ($post_id, $imagePath,  $thumbPath) uploadsPath=" ) . esc_html( $options['uploadsPath'] );
			}

			if ( ! $imagePath ) {
				return;
			}
			if ( ! file_exists( $imagePath ) ) {
				return;
			}
			if ( filesize( $imagePath ) < 5 ) {
				return; // too small
			}

			if ( $overwrite || ! $thumbPath || ! file_exists( $thumbPath ) ) {
				// $path =  dirname($imagePath);
				// $thumbPath =  $path . '/' . $post_id . '_thumbDownload.jpg';
				$thumbPath = sanitize_text_field( $options['uploadsPath'] ) . '/' . $post_id . '_thumbDownload.jpg';

				list($width, $height) = self::generateThumbnail( $imagePath, $thumbPath, $post_id );
				if ( ! $width ) {
					return;
				}

				$thumbPath = get_post_meta( $post_id, 'picture-thumbnail', true );
			}

			if ( ! get_the_post_thumbnail( $post_id ) ) { // insert if missing
				$wp_filetype = wp_check_filetype( basename( $thumbPath ), null );

				$attachment = array(
					'guid'           => $thumbPath,
					'post_mime_type' => $wp_filetype['type'],
					'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $thumbPath, '.jpg' ) ),
					'post_content'   => '',
					'post_status'    => 'inherit',
				);

				// Insert the attachment.
				$attach_id = wp_insert_attachment( $attachment, $thumbPath, $post_id );
				set_post_thumbnail( $post_id, $attach_id );
			} else // just update
				{
				$attach_id = get_post_thumbnail_id( $post_id );
				// $thumbPath = get_attached_file($attach_id);
			}

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			include_once ABSPATH . 'wp-admin/includes/image.php';

			if ( file_exists( $thumbPath ) ) {
				if ( filesize( $thumbPath ) > 5 ) {
					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $thumbPath );
					wp_update_attachment_metadata( $attach_id, $attach_data );

					if ( $verbose ) {
						var_dump( $attach_data );
					}

					if ( $width ) {
						update_post_meta( $post_id, 'picture-width', $width );
					}
					if ( $height ) {
						update_post_meta( $post_id, 'picture-height', $height );
					}
				}
			}
		}


		static function videowhisper_postdownloads( $atts ) {

			$options = get_option( 'VWpaidMembershipOptions' );

			$atts = shortcode_atts(
				array(
					'post'    => '',
					'perpage' => '8',
					'path'    => '',
				),
				$atts,
				'videowhisper_postdownloads'
			);

			if ( ! $atts['post'] ) {
				return 'No post id was specified, to manage post associated downloads.';
			}

			if ( $_GET['collection_upload'] ) {
				$htmlCode .= '<A class="ui button" href="' . remove_query_arg( 'collection_upload' ) . '">Done Uploading Downloads</A>';
			} else {
				$htmlCode .= '<div class="w-actionbox color_alternate"><h3>Manage Downloads</h3>';

				$channel = get_post( $atts['post'] );

				if ( $atts['path'] ) {
					$htmlCode .= '<p>Available ' . esc_html( $channel->post_title ) . ' downloads: ' . self::importFilesCount( sanitize_text_field( $channel->post_title ), self::extensions_download(), $atts['path'] ) . '</p>';
				}

				$link  = add_query_arg( array( 'collection_import' => sanitize_text_field( $channel->post_title ) ), get_permalink() );
				$link2 = add_query_arg( array( 'collection_upload' => sanitize_text_field( $channel->post_title ) ), get_permalink() );

				if ( $atts['path'] ) {
					$htmlCode .= ' <a class="ui button" href="' . $link . '">Import</a> ';
				}
				$htmlCode .= ' <a class="ui button" href="' . $link2 . '">Upload</a> ';

				$htmlCode .= '</div>';
			}

			$htmlCode .= '<h4>Downloads</h4>';

			$htmlCode .= do_shortcode( '[videowhisper_downloads perpage="' . esc_attr( $atts['perpage'] ) . '" collection="' . esc_attr( $channel->post_name ) . '"]' );

			return $htmlCode;
		}


		static function videowhisper_postdownloads_process( $atts ) {

			$atts = shortcode_atts(
				array(
					'post'      => '',
					'post_type' => '',
					'path'      => '',
				),
				$atts,
				'videowhisper_postdownloads_process'
			);

			self::importFilesClean();

			$htmlCode = '';

			if ( $channel_upload = sanitize_file_name( $_GET['collection_upload'] ) ) {
				$htmlCode .= do_shortcode( '[videowhisper_download_upload collection="' . $channel_upload . '"]' );
			}

			if ( $channel_name = sanitize_file_name( $_GET['collection_import'] ) ) {
				$options = get_option( 'VWpaidMembershipOptions' );

				$url = add_query_arg( array( 'collection_import' => $channel_name ), get_permalink() );

				$htmlCode .= '<form id="videowhisperImport" name="videowhisperImport" action="' . $url . '" method="post">';

				$htmlCode .= '<h3>Import <b>' . $channel_name . '</b> Downloads to Collection</h3>';

				$htmlCode .= self::importFilesSelect( $channel_name, self::extensions_download(), $atts['path'] );

				$htmlCode .= '<input type="hidden" name="collection" id="collection" value="' . $channel_name . '">';

				// same category as post
				if ( $atts['post'] ) {
					$postID = $atts['post'];
				} else { // search by name
					global $wpdb;
					if ( $atts['post_type'] ) {
						$cfilter = "AND post_type='" . $atts['post_type'] . "'";
					}
					$postID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $channel_name . "' $cfilter LIMIT 0,1" );
				}

				if ( $postID ) {
					$cats = wp_get_post_categories( $postID );
					if ( count( $cats ) ) {
						$category = array_pop( $cats );
					}
					$htmlCode .= '<input type="hidden" name="category" id="category" value="' . $category . '">';
				}

				$htmlCode .= '<INPUT class="ui g-btn type_primary button button-primary" TYPE="submit" name="import" id="import" value="Import">';

				$htmlCode .= ' <INPUT class="ui g-btn type_primary button button-primary" TYPE="submit" name="delete" id="delete" value="Delete">';

				$htmlCode .= '</form>';
			}

			return $htmlCode;
		}



		// download shortcodes: end


		// !permission functions

		// if any key matches any listing
		static function inList( $keys, $data ) {
			if ( ! $keys ) {
				return 0;
			}

			$list = explode( ',', strtolower( trim( $data ) ) );

			foreach ( $keys as $key ) {
				foreach ( $list as $listing ) {
					if ( strtolower( trim( $key ) ) == trim( $listing ) ) {
						return 1;
					}
				}
			}

					return 0;
		}


		static function hasPriviledge( $csv ) {
			// determines if user is in csv list (role, id, email)

			if ( strpos( $csv, 'Guest' ) !== false ) {
				return 1;
			}

			if ( is_user_logged_in() ) {
				$current_user = wp_get_current_user();

				// access keys : roles, #id, email
				if ( $current_user ) {
					$userkeys   = $current_user->roles;
					$userkeys[] = $current_user->ID;
					$userkeys[] = $current_user->user_email;
				}

				if ( self::inList( $userkeys, $csv ) ) {
					return 1;
				}
			}

			return 0;
		}

		static function hasRole( $role ) {

			if ( ! is_user_logged_in() ) {
				return false;
			}

			$current_user = wp_get_current_user();

			$role = strtolower( $role );

			if ( in_array( $role, $current_user->roles ) ) {
				return true;
			} else {
				return false;
			}
		}


		static function getRoles() {
			if ( ! is_user_logged_in() ) {
				return 'None';
			}

			$current_user = wp_get_current_user();

			return implode( ', ', $current_user->roles );
		}


		static function poweredBy() {

			$options = self::getOptions();

			$htmlCode = '';
			$state = 'block';
			if ( ! $options['videowhisper'] ) {
				$state = 'none';
			}

			$htmlCode .= '<div id="VideoWhisper" style="display: ' . $state . ';"><div class="ui ' . esc_html( $options['interfaceClass'] ) . ' message tiny"><i class="question icon"></i>This featured is powered by the free <a href="https://wordpress.org/plugins/paid-membership/">MicroPayments/FansPaysite: Wallet, Creator Subscriptions, Digital Assets, Membership</a> .</div></div>';

			$htmlCode .=  self::scriptThemeMode($options);

			return $htmlCode;
		}


		// ! Custom Post Page

		static function single_template( $single_template ) {

			if ( ! is_single() ) {
				return $single_template;
			}

			$options = get_option( 'VWpaidMembershipOptions' );

			$postID = get_the_ID();
			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $single_template;
			}

			if ( $options['postTemplate'] == '+plugin' ) {
				$single_template_new = dirname( __FILE__ ) . '/template-picture.php';
				if ( file_exists( $single_template_new ) ) {
					return $single_template_new;
				}
			}

			$single_template_new = get_template_directory() . '/' . sanitize_text_field( $options['postTemplate'] );

			if ( file_exists( $single_template_new ) ) {
				return $single_template_new;
			} else {
				return $single_template;
			}
		}



		static function download_page( $content ) {
			if ( ! is_single() ) {
				return $content;
			}
			$postID = get_the_ID();

			$options = get_option( 'VWpaidMembershipOptions' );

			if ( get_post_type( $postID ) != $options['custom_post'] ) {
				return $content;
			}

			if ( $options['pictureWidth'] ) {
				$wCode = ' width="' . trim( $options['pictureWidth'] ) . '"';
			} else {
				$wCode = '';
			}

			$addCode .= '' . '[videowhisper_download download="' . $postID . '" embed="1"' . $wCode . ']';

			// collection
			global $wpdb;

			$terms = get_the_terms( $postID, $options['custom_taxonomy'] );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$addCode .= '<div class="w-actionbox">';
				foreach ( $terms as $term ) {
					if ( class_exists( 'VWliveStreaming' ) ) {
						if ( $options['vwls_channel'] ) {
							$channelID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $term->slug . "' and post_type='channel' LIMIT 0,1" );

							if ( $channelID ) {
								$addCode .= ' <a title="' . __( 'Channel', 'paid-membership' ) . ': ' . $term->name . '" class="ui button" href="' . get_post_permalink( $channelID ) . '">' . sanitize_text_field( $term->name ) . ' Channel</a> ';
							}
						}
					}

					if ( class_exists( 'VWliveStreaming' ) ) {
						if ( $options['vwls_channel'] ) {
							$channelID = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $term->slug . "' and post_type='channel' LIMIT 0,1" );

							if ( $channelID ) {
								$addCode .= ' <a title="' . __( 'Channel', 'video-share-vod' ) . ': ' . $term->name . '" class="ui videowhisper_playlist_channel button g-btn type_red size_small mk-button dark-color  mk-shortcode two-dimension small" href="' . get_post_permalink( $channelID ) . '">' . $term->name . ' Channel</a> ';

								if ( ! VWliveStreaming::userPaidAccess( $current_user->ID, $channelID ) ) {
									return '<h4>Paid Channel Item</h4><p>This video is only accessible after paying for channel: <a class="button" href="' . get_permalink( $channelID ) . '">' . sanitize_text_field( $term->slug ) . '</a></p>';
								}
							}
						}
					}

					$addCode .= ' <a title="' . __( 'Collection', 'paid-membership' ) . ': ' . $term->name . '" class="ui button" href="' . get_term_link( $term->slug, $options['custom_taxonomy'] ) . '">' . sanitize_text_field( $term->name ) . '</a> ';
				}
				$addCode .= '</div>';
			}

			$views = get_post_meta( $postID, 'download-views', true );
			if ( ! $views ) {
				$views = 0;
			}

			$addCode .= '<div class="videowhisper_views">' . __( 'Download Views', 'paid-membership' ) . ': ' . $views . '</div>';

			// ! show reviews
			if ( $options['rateStarReview'] ) {
				// tab : reviews
				if ( shortcode_exists( 'videowhisper_review' ) ) {
					$addCode .= '<h3>' . __( 'My Review', 'paid-membership' ) . '</h3>' . do_shortcode( '[videowhisper_review content_type="picture" post_id="' . $postID . '" content_id="' . $postID . '"]' );
				} else {
					$addCode .= 'Warning: shortcodes missing. Plugin <a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> should be installed and enabled or feature disabled.';
				}

				if ( shortcode_exists( 'videowhisper_reviews' ) ) {
					$addCode .= '<h3>' . __( 'Reviews', 'paid-membership' ) . '</h3>' . do_shortcode( '[videowhisper_reviews post_id="' . $postID . '"]' );
				}
			}

			return $addCode . $content;
		}


		// end: download page

		// Register Custom Post Type
		static function download_post() {

			$options = get_option( 'VWpaidMembershipOptions' );

			// only if missing
			if ( post_type_exists( $options['custom_post'] ?? 'download' ) ) {
				return;
			}

			if ( $options['downloads'] ?? false ) {
				if ( ! post_type_exists( $options['custom_post'] ) ) {
					$labels = array(
						'name'                     => _x( 'Downloads', 'Post Type General Name', 'paid-membership' ),
						'singular_name'            => _x( 'Download', 'Post Type Singular Name', 'paid-membership' ),
						'menu_name'                => __( 'Downloads', 'paid-membership' ),
						'parent_item_colon'        => __( 'Parent Download:', 'paid-membership' ),
						'all_items'                => __( 'All Downloads', 'paid-membership' ),
						'view_item'                => __( 'View Download', 'paid-membership' ),
						'add_new_item'             => __( 'Add New Download', 'paid-membership' ),
						'add_new'                  => __( 'New Download', 'paid-membership' ),
						'edit_item'                => __( 'Edit Download', 'paid-membership' ),
						'update_item'              => __( 'Update Download', 'paid-membership' ),
						'search_items'             => __( 'Search Downloads', 'paid-membership' ),
						'not_found'                => __( 'No Downloads found', 'paid-membership' ),
						'not_found_in_trash'       => __( 'No Downloads found in Trash', 'paid-membership' ),

						// BuddyPress Activity
						'bp_activity_admin_filter' => __( 'New download published', 'paid-membership' ),
						'bp_activity_front_filter' => __( 'Downloads', 'paid-membership' ),
						'bp_activity_new_post'     => __( '%1$s posted a new <a href="%2$s">download</a>', 'paid-membership' ),
						'bp_activity_new_post_ms'  => __( '%1$s posted a new <a href="%2$s">download</a>, on the site %3$s', 'paid-membership' ),

					);

					$args = array(
						'label'               => __( 'download', 'paid-membership' ),
						'description'         => __( 'Downloads', 'paid-membership' ),
						'labels'              => $labels,
						'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'comments', 'custom-fields', 'page-attributes', 'buddypress-activity' ),
						'taxonomies'          => array( 'category', 'post_tag' ),
						'hierarchical'        => false,
						'public'              => true,
						'show_ui'             => true,
						'show_in_menu'        => true,
						'show_in_nav_menus'   => true,
						'show_in_admin_bar'   => true,
						'menu_position'       => 6,
						'can_export'          => true,
						'has_archive'         => true,
						'exclude_from_search' => false,
						'publicly_queryable'  => true,
						'menu_icon'           => 'dashicons-paperclip',
						'capability_type'     => 'post',
					);

					// BuddyPress Activity
					if ( function_exists( 'bp_is_active' ) ) {
						if ( bp_is_active( 'activity' ) ) {
											$args['bp_activity'] = array(
												'component_id' => buddypress()->activity->id,
												'action_id' => 'new_download',
												'contexts' => array( 'activity', 'member' ),
												'position' => 41,
											);
						}
					}

					register_post_type( $options['custom_post'], $args );

					// Add new taxonomy, make it hierarchical (like categories)
					$labels = array(
						'name'              => _x( 'Collections', 'taxonomy general name' ),
						'singular_name'     => _x( 'Collection', 'taxonomy singular name' ),
						'search_items'      => __( 'Search Collections', 'paid-membership' ),
						'all_items'         => __( 'All Collections', 'paid-membership' ),
						'parent_item'       => __( 'Parent Collection', 'paid-membership' ),
						'parent_item_colon' => __( 'Parent Collection:', 'paid-membership' ),
						'edit_item'         => __( 'Edit Collection', 'paid-membership' ),
						'update_item'       => __( 'Update Collection', 'paid-membership' ),
						'add_new_item'      => __( 'Add New Collection', 'paid-membership' ),
						'new_item_name'     => __( 'New Collection Name', 'paid-membership' ),
						'menu_name'         => __( 'Collections', 'paid-membership' ),
					);

					$args = array(
						'hierarchical'          => true,
						'labels'                => $labels,
						'show_ui'               => true,
						'show_admin_column'     => true,
						'update_count_callback' => '_update_post_term_count',
						'query_var'             => true,
						'rewrite'               => array( 'slug' => $options['custom_taxonomy'] ),
					);
					register_taxonomy( $options['custom_taxonomy'], array( $options['custom_post'] ), $args );
				}
			}
		}


		// ! Content Access by Membership
		static function add_meta_boxes() {

			$options = get_option( 'VWpaidMembershipOptions' );

			$postTypes = explode( ',', $options['postTypesRoles'] );

			foreach ( $postTypes as $postType ) {
				if ( post_type_exists( trim( $postType ) ) ) {
					add_meta_box(
						'videowhisper_paid_membership',           // Unique ID
						'Requires Membership / Role by MicroPayments plugin',  // Box title
						array( 'VWpaidMembership', 'meta_box_html' ),  // Content callback, must be of type callable
						trim( $postType )                   // Post type
					);
				}
			}
		}


		static function meta_box_html( $post ) {

			$postRoles = get_post_meta( $post->ID, 'vwpm_roles', true );

			// var_dump($postRoles);

			if ( ! $postRoles ) {
				$postRoles = array();
			}

			$checkedCode0 = '';
			if ( empty( $postRoles ) ) {
				$checkedCode0 = 'checked';
			}

			$checkedCode1 = '';
			if ( in_array( 'any-member', $postRoles ) ) {
				$checkedCode1 = 'checked';
			}

			?>
	<div>
	<input type="checkbox" id="vwpmRoles0" name="vwpmRoles[]" value="" class="postbox" <?php echo esc_attr( $checkedCode0 ); ?>>
	<label for="vwpmRoles0">No (Visitor) - Leave other boxes unchecked to activate this.</label>
	</div>
	<div>
	<input type="checkbox" id="vwpmRoles1" name="vwpmRoles[]" value="any-member" class="postbox" <?php echo esc_attr( $checkedCode1 ); ?>>
	<label for="vwpmRoles1">Any (Member) - Any registered member, indifferent of role.</label>
	</div>
			<?php

			global $wp_roles;
			$all_roles = $wp_roles->roles;

			foreach ( $all_roles as $roleName => $role ) {
				$roleLabel = sanitize_text_field( $role['name'] );

				$checkedCode = '';
				if ( in_array( $roleName, $postRoles ) ) {
					$checkedCode = 'checked';
				}
				?>
<div>
<input type="checkbox" id="vwpmRoles<?php echo esc_attr( $roleName ); ?>" name="vwpmRoles[]" value="<?php echo esc_attr( $roleName ); ?>" class="postbox" <?php echo esc_attr( $checkedCode ); ?>>
<label for="vwpmRoles<?php echo esc_attr( $roleName ); ?>"><?php echo esc_html( $roleName ); ?> (<?php echo esc_html( $roleLabel ); ?>) </label>
</div>
				<?php
			}

			?>
	Use content Update button to save changes. Create new roles from <A href="admin.php?page=paid-membership&tab=membership">MicroPayments - Membership & Content : Site Membership Levels</a>.
			<?php
		}


		static function save_post( $post_id ) {
			if ( array_key_exists( 'vwpmRoles', $_POST ) ) {
				$vwpmRoles = (array) $_POST['vwpmRoles'];

				if ( ! is_array( $vwpmRoles ) ) {
					$vwpmRoles = array();
				}

				foreach ( $vwpmRoles as $key => $value ) {
					$vwpmRoles[ sanitize_text_field( $key ) ] = sanitize_file_name( $value );
					if ( ! $vwpmRoles[ sanitize_text_field( $key ) ] ) {
						unset( $vwpmRoles[ sanitize_text_field( $key ) ] );
					}
				}

				update_post_meta( $post_id, 'vwpm_roles', $vwpmRoles );
			}
		}


		static function durationLabel( $days, $recurring = 1 ) {
			
			switch ( $recurring )
			{
				case 1:
			
			switch ( $days ) {
				case 0:
					return __( 'One time', 'paid-membership' );

				case 7:
					return __( 'Weekly', 'paid-membership' );

				case 14:
					return __( 'Biweekly', 'paid-membership' );

				case 30:
					return __( 'Monthly', 'paid-membership' );

				case 365:
					return __( 'Yearly', 'paid-membership' );

				default:
					return sprintf( ___( 'Each %s Days', 'paid-membership' ), $days );
			}
				case 0:
					switch ( $days ) {
				case 0:
					return __( 'Lifetime', 'paid-membership' );

				case 7:
					return __( '1 Week', 'paid-membership' );

				case 14:
					return __( '2 Weeks', 'paid-membership' );

				case 30:
					return __( '1 Month', 'paid-membership' );

				case 365:
					return __( '1 Year', 'paid-membership' );

				default:
					return sprintf( ___( '%s Days', 'paid-membership' ), $days );
			}
				}
		}

		static function humanHours( $hours ) {
			if ( ! $hours ) {
				return __( 'lifetime', 'video-share-vod' );
			}
			return $hours . ' ' . __( 'hours', 'video-share-vod' );
		}

		static function the_comments_hide($comments)
		{
			return [];
		}

		static function limitComments($postID, $options = null)
		{
			if ( ! $options ) {
				$options = self::getOptions();
			}

			if ($options['commentsLimit'] ?? false) 
			{

			//get post owner id : author can add unlimited comments
			$post = get_post($postID);
			if (get_current_user_id() == $post->post_author ) return '<div class="ui message">' . __( 'As content owner, you can add unlimited comments.', 'paid-membership' ) . '</div>';  //author can post unlimited comments

			//comments limit
			$commentsLimit = intval($options['commentsLimit']);

			//count user comments
			$args = array(
				'user_id' => get_current_user_id(),
				'post_id' => $postID,
			);

			//get number of comments for $args 
			$comments = get_comments($args);
			$commentsCount = count($comments);

			if ($commentsCount >= $commentsLimit) {
				add_filter( 'comments_open', '__return_false' );
				return '<div class="ui warning message" ><i class="comments icon"></i>' . __( 'You can not add more comments. Comments limit:', 'paid-membership' ) . ' ' . $commentsLimit . '</div>';
			}

			}

			return; //no limit 
		}

		static function hideComments($postID, $options = null)
		{
			if ( ! $options ) {
				$options = self::getOptions();
			}

			if ($options['commentsAccess'] ?? false) return '<div class="ui message"><i class="comments icon"></i>' . __( 'Comments are shown.', 'paid-membership' ) . '</div>';

			add_filter( 'the_comments', array( 'VWpaidMembership', 'the_comments_hide') );
			add_filter( 'comments_open', '__return_false' );

			return '<div class="ui message"><i class="comments icon"></i>' . __( 'Comments are also hidden, with content.', 'paid-membership' ) . '</div>';
		}

		static function the_content( $content ) {
			// ! content page

			if ( ! is_single() && ! is_page() ) {
				return $content; // listings
			}

			$postID = get_the_ID();
			$type = get_post_type( $postID );

			$options = self::getOptions();

			$preContent  = '';
			$preContent2 = '';
			$afterContent = '';

			$current_user = wp_get_current_user();


			//if paid content type, check if premium
			$postTypes = explode( ',', $options['postTypesPaid'] );
			foreach ( $postTypes as $postType ) if ( $type == trim( $postType ) ) self::isPremium($postID);

			//donations
			$vw_donations = get_post_meta( $postID, 'vw_donations', true );

			// donate for content
			if ( $options['postTypesDonate'] ) {
				if ( $vw_donations != 'disabled' ) {
					$author = get_post_field( 'post_author', $postID );

					$postTypes = explode( ',', $options['postTypesDonate'] );
					foreach ( $postTypes as $postType ) {
						if ( $type == trim( $postType ) ) {
							self::enqueueUI();
							$preContent .= do_shortcode( '[videowhisper_donate userid="' . $author . '"]' );

							if ( $vw_donations == 'goal' || $vw_donations == 'crowdfunding' ) {
								$preContent2 .= do_shortcode( '[videowhisper_donate_progress postid="' . $postID . '"]' );
							}
							break;
						}
					}
				}
			}

				//authors box
				if ( $options['postTypesAuthors'] ) {	
	
					$postTypes = explode( ',', $options['postTypesAuthors'] );
					foreach ( $postTypes as $postType ) {
						if ( $type == trim( $postType ) ) {

							$afterContent .= '<div class="ui ' . $options['interfaceClass'] . ' small segment">' . __( 'Author', 'paid-membership' ) . ': <i class="user md icon"></i>';

							$authorID = get_post_field( 'post_author', $postID );
							if (function_exists('bp_core_get_userlink')) $afterContent .= bp_core_get_userlink( $authorID );
							else $afterContent.= '<a href="' . get_author_posts_url( $authorID ) . '">' . get_the_author_meta( 'display_name', $authorID ) . '</a>';

							//remove this user as coauthor, also checking wp nonce
							if ( isset($_GET['removeCoauthor']) ) {
								if ( wp_verify_nonce( $_GET['nonce'], 'removeCoauthor' ) ) {
									$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
									if (!is_array($coauthors)) $coauthors = array();
									if (isset($coauthors[$current_user->ID])) unset($coauthors[$current_user->ID]);
									update_post_meta( $postID, 'vw_coauthors', $coauthors );
									$afterContent .= '<br>' . __( 'You removed yourself from coauthors.', 'paid-membership' );
								}
							}

							$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
							if (!is_array($coauthors)) $coauthors = array();

							if (count($coauthors))
							{
								$afterContent .= '<br>' . __( 'Coauthors', 'paid-membership' ) . ': ';
								foreach ($coauthors as $coauthorID => $coauthorPercent)
								{
									$afterContent .= '<i class="user md icon"></i>';
									if (function_exists('bp_core_get_userlink')) $afterContent .= bp_core_get_userlink( $coauthorID );
									else $afterContent.= '<a href="' . get_author_posts_url( $coauthorID ) . '">' . get_the_author_meta( 'display_name', $coauthorID ) . '</a>';
									$afterContent .= ' '. $coauthorPercent . '%, ';
								}
								$afterContent = substr($afterContent, 0, -2);

								//if current user is coauthor add a link to remove himself, from this page, also using wp nonce
								//if (in_array($current_user->ID, array_keys($coauthors)))
								if (self::isCoauthor($current_user->ID , $postID))
								{
									$afterContent .= '<br><a href="' . add_query_arg( array( 'removeCoauthor' => $postID, 'coauthorID' => $current_user->ID, 'nonce' => wp_create_nonce( 'removeCoauthor' ) ), get_permalink( $postID ) ) . '"><i class="delete icon"></i>' . __( 'Remove me as coauthor', 'paid-membership' ) . '</a>';
								}
							}

						

							$afterContent .= '</div>';
						} 
					}
				}
	

			// edit content
			if ( $current_user->ID && $options['postTypesEdit'] && ( $options['p_videowhisper_content_edit'] ?? false )) {
				$author = get_post_field( 'post_author', $postID );

				if ( $author == $current_user->ID ) {

					$postTypes = explode( ',', $options['postTypesEdit'] );
					foreach ( $postTypes as $postType ) {
						if ( $type == trim( $postType ) ) {
							self::enqueueUI();
							$preContent .= '<a class="ui ' . $options['interfaceClass'] . ' button tiny compact" href="' . add_query_arg( 'editID', $postID, get_permalink( $options['p_videowhisper_content_edit'] ) ) . '"><i class="edit icon"></i> ' . __( 'Edit', 'paid-membership' ) . ' ' . ucwords( $type ) . ' </a>';

							if ( $options['p_videowhisper_content_seller'] ) {
								$preContent .= ' <a class="ui ' . $options['interfaceClass'] . ' button tiny compact" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a> ';
							}

							// owner gets content
							if ( ! strstr( $content, $preContent ) ) {
								return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' small message"><i class="user md icon"></i>' . __( 'You are the owner of this content.', 'paid-membership' ). '</div>' . $afterContent . self::poweredBy();
							} else {
								return '<!-- VideoWhisper.com MicroPayments : Duplicate Filter Detected -->' . $content;
							}
						}
					}
				}
			}

			//external manage: display content and buying options as restrictions are externally managed
			$externalManage  = 0 ;
			$postTypes = explode( ',', $options['postTypesExternal'] );
			foreach ( $postTypes as $postType ) if ( $type == trim( $postType ) ) $externalManage = 1;


			// subscription to author required 
			$content_tier = get_post_meta( $postID, 'vw_subscription_tier', true );
			if ( $content_tier ) {
				$authorID = get_post_field( 'post_author', $postID );

				if ( $current_user ) {
					$client_tier = get_user_meta( $current_user->ID, 'vw_client_subscription_' . $authorID, true );
				} else {
					$client_tier = 0;
				}

				if ( $client_tier >= $content_tier ) { // client gets content per subscription, higher tiers include lower content
					$afterContent .= self::limitComments($postID, $options); //also limit comments
					$afterContent = '<div class="ui ' . $options['interfaceClass'] . ' message"><i class="unlock icon"></i>' . __( 'You have access to this content, by subscription.', 'paid-membership' ). '</div>' . $afterContent;
					return $preContent . $preContent2 . $content . $afterContent . self::poweredBy();
				}

				// else show subscription info
				$subscriptions = get_user_meta( $authorID, 'vw_provider_subscriptions', true );

				self::enqueueUI();

				$author   = get_userdata( $authorID );
				$userCode = $author->display_name ? $author->display_name : $author->user_login;
				if ( function_exists( 'bp_members_get_user_url' ) ) {
					$userCode = '<a href="' . bp_members_get_user_url( $author->ID ) . '"  title="' . bp_core_get_user_displayname( $author->ID ) . '"> ' . bp_core_fetch_avatar(
						array(
							'item_id' => $author->ID,
							'type'    => 'full',
							'class'   => 'ui middle aligned tiny rounded image',
						)
					) . ' ' . $author->display_name . ' </a>';
				}

				$preContent3 = '<div class="ui ' . $options['interfaceClass'] . ' orange segment"> <i class="lock icon"></i> ' . __( 'This content is available with author subscription.', 'paid-membership' ) . '<br>' . $userCode;
				if ( is_array( $subscriptions ) && array_key_exists( $content_tier, $subscriptions ) ) {
					$preContent3 .= '<div class="header">' . $subscriptions[ $content_tier ]['name'] . ( $content_tier > 1 ? ' <div class="ui small label">(' . __( 'tier', 'paid-membership' ) . ' ' . $content_tier . ')</div>' : '' ) . ' <div class="ui green tag label"> <i class="unlock icon"></i>' . $subscriptions[ $content_tier ]['price'] . $options['currency'] . ' ' . self::durationLabel( $subscriptions[ $content_tier ]['duration'] ) . '</div></div> <div>' . htmlspecialchars( $subscriptions[ $content_tier ]['description'] ) . '</div>';
				} else {
					$preContent3 .= '</h4>';
				}

				if ( $options['p_videowhisper_client_subscribe'] ) {
					$preContent3 .= '<br><a class="ui ' . $options['interfaceClass'] . ' button" href="' . add_query_arg( 'creator', $author->user_login, get_permalink( $options['p_videowhisper_client_subscribe'] ) ) . '"><i class="unlock icon"></i>' . __( 'See Subscriptions', 'paid-membership' ) . '</a>';
				}

				$preContent3 .= '</div>';

				$preContent2 = $preContent3 . $preContent2;
				
				// do not returt yet, as content could be purchased
			}

			$requiresPurchase = 0;
			
			// micropayments paid content
			$mPriceM = get_post_meta( $postID, 'micropayments_price', true );
			if ( $mPriceM ) {
				$mPrice = number_format( $mPriceM, 2, '.', ''  );
			} else {
				$mPrice = 0;
			}

			if ( $mPrice ) {
				
				self::enqueueUI();

				$requiresPurchase = 1;

				$mDuration = intval( get_post_meta( $postID, 'micropayments_duration', true ) ); // hours

				if ( 0 == $current_user->ID ) { 

					//hide comments
					$afterContent .= self::hideComments($postID, $options);

					// visitor gets login button
					return $preContent . $preContent2 . '<div class="ui inverted segment red"> <i class="shopping cart icon"></i> &nbsp; ' . $options['paidMessage'] . '<div> ' . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) . '</div><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>' . '</div>' . $afterContent . self::poweredBy();
				}


				  if ( isset($_GET['purchaseID']) && $_GET['purchaseID'] == $postID && $_GET['verifyPurchase'] ) {
					//start purchase
						  
					if ( ! wp_verify_nonce( $_GET['verifyPurchase'], 'purchase' . $postID ) ) {
						
						$afterContent .= self::hideComments($postID, $options); //also hide comments

						return 'Security Error: Incorrect verification nonce!';
					}

				//detect purchase, avoid duplicate
				$clientPurchase = intval( get_user_meta( $current_user->ID, 'vw_client_purchase_' . $postID, true ) ); // get purchase expiration
				if ( $clientPurchase > 0 ) {
					if ( $clientPurchase < time() ) 
					{  // expired
						delete_user_meta( $current_user->ID, 'vw_client_purchase_' . $postID );
						$clientPurchase = 0;
					}
				}
				
				//already purchased: return
					if ($clientPurchase) {
						$afterContent .= self::limitComments($postID, $options); //also hide comments
						return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' message"><i class="shopping cart icon"></i>' . __('You already purchased this content.', 'paid-membership') . ' ' . __('Access Price', 'paid-membership') . ': ' . $mPrice . ' ' . __('Duration', 'paid-membership') . ': ' . self::humanHours($mDuration) . '</div>' . $afterContent .  self::poweredBy();
					}

					//check balance
					if ( self::balance( $current_user->ID ) < $mPrice ) {
						self::enqueueUI();

						$afterContent .= self::hideComments($postID, $options); //also hide comments
						return $preContent . $preContent2 . '<div class="ui ' . $options['interfaceClass'] . ' segment red warning"> <i class="shopping cart icon"></i>' . __( 'Insufficient funds!', 'paid-membership' ) . '</div>' . $afterContent . self::poweredBy();
					}
						$authorID = get_post_field( 'post_author', $postID );
						
					//ratio paid to author (rest is site profit)
					$contentRatio = round( $options['contentRatio'], 3) ;
					if (!$contentRatio) $contentRatio = 1;

					$receivable =  $mPrice * $contentRatio;
					$remaining = $receivable; //all amount if there's no coauthors
					
					// transactions
						self::transaction( 'micropayments_purchase', $current_user->ID, - $mPrice, 'Purchase of <a href="' . get_permalink( $postID ) . '">#' . $postID . '</a> ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) );

						//coauthors
						$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
						if (!is_array($coauthors)) $coauthors = array();

						$coauthorsTotal = 0;
						if (count($coauthors)) foreach ($coauthors as $coauthorID => $coauthorPercent)
						{
							$coreceivable = $receivable * $coauthorPercent / 100;

							if ( $coauthorsTotal + $coreceivable < $receivable ) //double check to avoid paying more than available
							{
							self::transaction( 'micropayments_cosale', $authorID,  $coreceivable , ' Coauthor ' . $coauthorPercent . '% sale to ' . $current_user->display_name . ' of <a href="' . get_permalink( $postID ) . '">#' . $postID . '</a> ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) );

							$coauthorsTotal += $coreceivable;
							$remaining -= $coreceivable; //reduce remaining
							}
						}
			
						self::transaction( 'micropayments_sale', $authorID, $remaining , ' Sale to ' . $current_user->display_name . ' of <a href="' . get_permalink( $postID ) . '">#' . $postID . '</a> ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) . ( $coauthorsTotal ? ' - Coauthors: ' . $coauthorsTotal : '' ) ) ;

						$newPurchase = -1;  // lifetime
					if ( $mDuration ) {
						$newPurchase = time() + $mDuration * 3600;
					}
						update_user_meta( $current_user->ID, 'vw_client_purchase_' . $postID, $newPurchase );
						
						//update purchase list
						$purchases = get_user_meta( $current_user->ID, 'vw_client_purchase_list', true );
						if (!is_array($purchases)) $purchases = array();
						$purchases[$postID] = $newPurchase;
						update_user_meta( $current_user->ID, 'vw_client_purchase_list', $purchases );
						
				} //end pruchase

					// owner gets content without purchasing
					$authorID = get_post_field( 'post_author', $postID );
				if ( $current_user->ID == $authorID ) {
					return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="user md icon"></i>' . __( 'Accessing as content owner.', 'paid-membership' ) . '<br><i class="shopping cart icon"></i>' . __( 'Other users can see this content after purchasing access.', 'paid-membership' ) . ( isset($mPrice) ?  ' ' . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) : '' ) . '</div>' . $afterContent . self::poweredBy();
				}
				
				if (self::isModerator($current_user->ID , $options , $current_user)) return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="user md icon"></i>' . __( 'Accessing as moderator.', 'paid-membership' ) . '<br><i class="shopping cart icon"></i>' . __( 'Other users can see this content after purchasing access.', 'paid-membership' ) . ( isset($mPrice) ?  ' ' . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) : '' ) . '</div>' . $afterContent . self::poweredBy();

				if (self::isCoauthor($current_user->ID , $postID)) return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="user md icon"></i>' . __( 'Accessing as coauthor.', 'paid-membership' ) . '<br><i class="shopping cart icon"></i>' . __( 'Other users can see this content after purchasing access.', 'paid-membership' ) . ( isset($mPrice) ?  ' ' . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) : '' ) . '</div>' . $afterContent . self::poweredBy();
				
				//detect purchase, again after buying
				$clientPurchase = intval( get_user_meta( $current_user->ID, 'vw_client_purchase_' . $postID, true ) ); // get purchase expiration
				if ( $clientPurchase > 0 ) {
					if ( $clientPurchase < time() ) 
					{  // expired
						delete_user_meta( $current_user->ID, 'vw_client_purchase_' . $postID );
						$clientPurchase = 0;
					}
				}
				

				//buyer gets content
				if ( $clientPurchase ) {
					$afterContent .= self::limitComments($postID, $options); //also limit comments
					return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' message"> <i class="shopping cart icon"></i>' . __( 'You purchased this content.', 'paid-membership' ) . ' ' . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) . '</div>' . $afterContent . self::poweredBy();
				} else {

					//hide comments
					$afterContent .= self::hideComments($postID, $options);

					//rest get buy button
					if ( self::balance( $current_user->ID ) < $mPrice ) {
						$buttonCode = '<div class="ui label">' .  __( 'Insufficient Funds', 'paid-membership' ) . '</div>';
					} else {
								$purchaseURL = wp_nonce_url( add_query_arg( 'purchaseID', $postID, self::getCurrentURL() ), 'purchase' . $postID, 'verifyPurchase' );
								$buttonCode  = '<a class="ui button" href="' . $purchaseURL . '"><i class="shopping cart icon"></i>' . __( 'Purchase Access', 'paid-membership' ) . '</a>';
					}
					
					if (! self::rolesUser( $options['rolesBuyer'], $current_user ) ) $buttonCode = 'Buyer features not enabled for your role!';

					//user buy button
					return $preContent . $preContent2 . '<div class="ui inverted segment red warning"> <i class="shopping cart icon"></i> &nbsp; ' . $options['paidMessage'] . '<br>' . do_shortcode( '[videowhisper_wallet]' ) . ' ' . $buttonCode . __( 'Access Price', 'paid-membership' ) . ': ' . $mPrice . ' ' . __( 'Duration', 'paid-membership' ) . ': ' . self::humanHours( $mDuration ) . '</div>' . $afterContent . self::poweredBy();
				}
			}

			// woocommerce paid content
			if ( $options['paid_handler'] == 'woocommerce' ) { // only if enabled to prevent extra load
				if ( function_exists( 'wc_get_order' ) ) { // only if woocommerce available
					$product_id = get_post_meta( $postID, 'vw_micropay_productid', true );
					$price      = get_post_meta( $postID, 'vw_micropay_price', true );

					if ( $product_id && $price ) { // paid item
						
						$requiresPurchase = 1;
						
						if ( 0 == $current_user->ID ) { // visitor
							self::enqueueUI();
							$afterContent .= self::hideComments($postID, $options); //also hide comments
							return $preContent . $preContent2 . '<div class="ui ' . $options['interfaceClass'] . ' segment red warning"> <i class="shopping cart icon"></i> ' . $options['paidMessage'] . '<p><a class="ui button" href="' . get_permalink( $product_id ) . '"><i class="shopping cart icon"></i> ' . __( 'View Product', 'paid-membership' ) . '</a></p>' . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>' . '</div>' . $afterContent . self::poweredBy();
						}

						// owner gets content without purchasing
						$authorID = get_post_field( 'post_author', $postID );
						if ( $current_user->ID == $authorID ) {
							return $preContent . $preContent2 . $content . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="shopping cart icon"></i>' . __( 'Other users can see your content after purchasing access product.', 'paid-membership' ) . '<p><a class="ui button" href="' . get_permalink( $product_id ) . '"><i class="shopping cart icon"></i> ' . __( 'View Product', 'paid-membership' ) . '</a></p>' . '</div>' . $afterContent . self::poweredBy();
						}

						// GET USER ORDERS (COMPLETED)
						$customer_orders = get_posts(
							array(
								'numberposts' => -1,
								'meta_key'    => '_customer_user',
								'meta_value'  => $current_user->ID,
								'post_type'   => wc_get_order_types(),
								// 'post_status' => array_keys( wc_get_is_paid_statuses() ),
								'post_status' => 'wc-completed', // Only orders with "completed" status
							)
						);

						$purchasedProduct = 0;

						// LOOP THROUGH ORDERS AND GET PRODUCT IDS
						if ( $customer_orders ) {
							$product_ids = array();
							foreach ( $customer_orders as $customer_order ) {
								$order = wc_get_order( $customer_order->ID );
								$items = $order->get_items();
								foreach ( $items as $item ) {
									if ( $product_id == $item->get_product_id() ) {
										$purchasedProduct = 1;
										break;
									}
								}
							}
						}

						if ( $purchasedProduct ) {
							$afterContent .= self::limitComments($postID, $options); //also limit comments
							return $preContent . $preContent2 . $content . '<div class="ui message"><i class="shopping cart icon"></i>' . __( 'You purchased this content product.', 'paid-membership' ) . '</div>' . $afterContent . self::poweredBy();
						} else {
							self::enqueueUI();
							$afterContent .= self::hideComments($postID, $options); //also hide comments
							return $preContent . $preContent2 . '<div class="ui ' . $options['interfaceClass'] . ' segment red warning"> <i class="shopping cart icon"></i> ' . $options['paidMessage'] . '<br><a class="ui button" href="' . get_permalink( $product_id ) . '"><i class="shopping cart icon"></i>' . __( 'Buy Product', 'paid-membership' ) . '</a></div>' . $afterContent . self::poweredBy();
						}
					}
				}
			}

			// if requires subscription and not purchased
			if ( $content_tier) 
			{
				if (self::isModerator($current_user->ID , $options , $current_user)) return $preContent . $preContent2 . $content . $afterContent . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="unlock icon"></i>' . __( 'Accessing as moderator.', 'paid-membership' ) . ' ' . __( 'Other users can see this content with subscription.', 'paid-membership' ) . '</div>' . self::poweredBy(); //moderator can access

				if (self::isCoauthor($current_user->ID , $postID)) return $preContent . $preContent2 . $content . $afterContent . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="unlock icon"></i>' . __( 'Accessing as coauthor.', 'paid-membership' ) . ' ' . __( 'Other users can see this content with subscription.', 'paid-membership' ) . '</div>' . self::poweredBy(); //moderator can access

				$afterContent .= self::hideComments($postID, $options); //also hide comments (if not owner/moderator/coauthor)
				return $preContent . $preContent2 . ( ( $externalManage && !$requiresPurchase) ? $content : '' ) . $afterContent . self::poweredBy();
			}

			//regular content

			//moderators and coauthors are not limited by role or comments
			if (self::isModerator($current_user->ID , $options , $current_user)) return $preContent . $preContent2 . $content . $afterContent . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="unlock icon"></i>' . __( 'Accessing as moderator.', 'paid-membership' ) . ' ' . __( 'This content is free.', 'paid-membership' ) . '</div>' . self::poweredBy(); //moderator can access

			if (self::isCoauthor($current_user->ID , $postID)) return $preContent . $preContent2 . $content . $afterContent . '<div class="ui ' . $options['interfaceClass'] . ' small segment"> <i class="unlock icon"></i>' . __( 'Accessing as coauthor.', 'paid-membership' ) . ' ' . __( 'This content is free.', 'paid-membership' ) . '</div>' . self::poweredBy(); //coauthor can access

			//limit comments for everybody else
			$afterContent .= self::limitComments($postID, $options); //also limit comments (if not owner/moderator/coauthor)

			// add progress
			$preContent .= $preContent2;

			// role access

			$postRoles = get_post_meta( $postID, 'vwpm_roles', true );

			// return $content .'...'. $postRoles;

			if ( ! $postRoles ) {
				return $preContent . $content . $afterContent ;
			}
			if ( ! is_array( $postRoles ) ) {
				return $preContent . $content . $afterContent;
			}
			if ( empty( $postRoles ) ) {
				return $preContent . $content . $afterContent;
			}

			if ( ! is_user_logged_in() ) {
				self::hideComments($postID, $options); //also hide comments
				return '<div class="ui ' . $options['interfaceClass'] . ' segment red warning">' . $options['visitorMessage'] . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>' . '</div>' . self::poweredBy();
			} else {
				if ( in_array( 'any-member', $postRoles ) ) {
					return $preContent . $content . $afterContent;
				}

				$current_user = wp_get_current_user();

				if ( self::any_in_array( $postRoles, $current_user->roles ) ) {
					return $preContent . $content . $afterContent;
				} else {
					self::enqueueUI();
					$preContent .= self::hideComments($postID, $options); //also hide comments
					return $preContent . '<div class="ui ' . $options['interfaceClass'] . ' segment red warning">' . $options['roleMessage'] . self::poweredBy();
				}
			}

			// otherwise return content
			if ( ! strstr( $content, $preContent ) ) {
				return $preContent . $content . $afterContent;
			} else {
				return '<!-- VideoWhisper.com MicroPayments : Duplicate Filter Detected -->' . $content;
			}
		}


		static function get_current_user_role() {
			global $wp_roles;
			$current_user = wp_get_current_user();
			$roles        = $current_user->roles;
			$role         = array_shift( $roles );
			return isset( $wp_roles->role_names[ $role ] ) ? translate_user_role( $wp_roles->role_names[ $role ] ) : false;
		}


		static function getCurrentURL() {
			$currentURL  = ( @$_SERVER['HTTPS'] == 'on' ) ? 'https://' : 'http://';
			$currentURL .= $_SERVER['SERVER_NAME'];

			if ( $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ) {
				$currentURL .= ':' . $_SERVER['SERVER_PORT'];
			}

			$uri_parts = explode( '?', $_SERVER['REQUEST_URI'], 2 );

			$currentURL .= $uri_parts[0];
			return $currentURL;
		}


		static function humanAge( $t ) {
			if ( $t < 30 ) {
				return 'NOW';
			}
			return sprintf( '%d%s%d%s%d%s', floor( $t / 86400 ), 'd ', floor( $t / 3600 ) % 24, 'h ', floor( $t / 60 ) % 60, 'm' );
		}


		static function humanFilesize( $bytes, $decimals = 2 ) {
			$sz     = 'BKMGTP';
			$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
			return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . @$sz[ $factor ];
		}


		static function path2url( $file, $Protocol = 'http://' ) {
			if ( is_ssl() && $Protocol == 'http://' ) {
				$Protocol = 'https://';
			}

			$url = $Protocol . $_SERVER['HTTP_HOST'];

			// on godaddy hosting uploads is in different folder like /var/www/clients/ ..
			$upload_dir = wp_upload_dir();
			if ( strstr( $file, $upload_dir['basedir'] ) ) {
				return $upload_dir['baseurl'] . str_replace( $upload_dir['basedir'], '', $file );
			}

			// folder under WP path
			include_once ABSPATH . 'wp-admin/includes/file.php';
			if ( strstr( $file, get_home_path() ) ) {
				return get_home_url() . str_replace( get_home_path(), '/', $file );
			}

			if ( strstr( $file, $_SERVER['DOCUMENT_ROOT'] ) ) {
				return $url . str_replace( $_SERVER['DOCUMENT_ROOT'], '', $file );
			}

			return $url . $file;
		}


		static function product_info() {
			global $product;

			$product_id = $product->get_id();

			$contentID = get_post_meta( $product_id, 'videowhisper_content', true );

			if ( $contentID ) {
				echo '<p><a class="ui button" href="' . get_permalink( $contentID ) . '">Access Content</a></p>';
			}
		}


		// ! Shortcodes


		// ! Membership Processing

		static function membership_update_all() {
			$users = get_users(
				array(
					'meta_key' => 'vw_paid_membership',
					'fields'   => 'ID',
				)
			);

			foreach ( $users as $user ) {
				self::membership_update( $user );
			}
		}


		// update membership: process recurring / end
		static function membership_update( $user_ID ) {
			$membership = get_user_meta( $user_ID, 'vw_paid_membership', true );

			if ( $membership['expires'] > time() ) {
				return 0; // still valid
			}

			// end if not recurring
			if ( ! $membership['recurring'] ) {
				self::membership_end( $user_ID );
				return 0;
			}

			// recurr
			if ( self::membership_apply( $membership, $user_ID ) ) {
				$membership['lastCharge'] = time();
				$membership['expires']    = time() + ( $membership['expire'] * 86400 );

				update_user_meta( $user_ID, 'vw_paid_membership', $membership );

				return 1;
			} else {
				self::membership_end( $user_ID );
				return 0;
			}
		}


		static function membership_end( $user_ID ) {
			$options = get_option( 'VWpaidMembershipOptions' );
			if ( ! $options['freeRole'] ) {
				return;
			}

			// create role if missing
			if ( ! get_role( $options['freeRole'] ) ) {
				add_role( $options['freeRole'], ucwords( $options['freeRole'] ), array( 'read' => true ) );
			}

			$user_ID = wp_update_user(
				array(
					'ID'   => $user_ID,
					'role' => $options['freeRole'],
				)
			);

			delete_user_meta( $user_ID, 'vw_paid_membership' );
		}


		static function membership_cancel( $user_ID ) {
			$membership = get_user_meta( $user_ID, 'vw_paid_membership', true );

			if ( ! $membership ) {
				return;
			}
			if ( ! $membership['recurring'] ) {
				return;
			}

			$membership['recurring'] = 0;
			update_user_meta( $user_ID, 'vw_paid_membership', $membership );
		}


		static function membership_setup( $membership, $user_ID ) {
			if ( self::membership_apply( $membership, $user_ID ) ) {
				$membership['firstCharge'] = time();
				$membership['lastCharge']  = time();
				$membership['expires']     = time() + ( $membership['expire'] * 86400 );

				update_user_meta( $user_ID, 'vw_paid_membership', $membership );

				return 1;
			} else {
				return 0;
			}
		}


		static function membership_apply( $membership, $user_ID ) {
			$balance = self::balance( $user_ID );
			if ( $membership['price'] > $balance ) {
				return 0;
			}

			// create role if missing
			if ( ! get_role( $membership['role'] ) ) {
				add_role( $membership['role'], ucwords( $membership['role'] ), array( 'read' => true ) );
			}

			$user_ID = wp_update_user(
				array(
					'ID'   => $user_ID,
					'role' => $membership['role'],
				)
			);

			self::transaction( 'paid_membership', $user_ID, - $membership['price'], $membership['label'] . '- Paid Membership Fee.', null, $membership );
			return 1;
		}


		// ! Billing Integration: MyCred, WooWallet, MicroPayments (internal)
		
		static function micropayments_transaction ($userID, $type, $amount, $details = '', $postID = 0, $initiatorID = 0)
		{
			/*
  `transaction_id` bigint(20) UNSIGNED NOT NULL,
  `blog_id` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `balance` decimal(10,2) NOT NULL,
  `currency` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `details` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `post_id` int(11) UNSIGNED NOT NULL DEFAULT 0,
  `initiator_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `date` timestamp NOT NULL DEFAULT current_timestamp()
				*/
				
				$options = self::getOptions();
				
				global $wpdb;
				$table_transactions = $wpdb->prefix . 'vw_micropay_transactions';

				$newBalance = $amount;
				$last = $wpdb->get_row( "SELECT * FROM $table_transactions WHERE user_id = '$userID' ORDER BY date DESC LIMIT 1" );
				if ($last) $newBalance = number_format( floatval($last->balance) + $amount, 2, '.', '' );

				$wpdb->insert($table_transactions, ['blog_id' => get_current_blog_id(), 'user_id' => $userID, 'type' => $type, 'amount' => $amount, 'balance' => $newBalance, 'currency' => $options['currency'], 'details' => $details, 'post_id' => $postID, 'initiator_id' => $initiatorID ], [ '%d','%d', '%s', '%f', '%f', '%s','%s','%d','%d']);
				
				update_user_meta($userID, 'micropayments_balance', $newBalance );
				
				return  $wpdb->insert_id;
				
		}
		
			static function micropayments_balance( $userID = 0) {

			if ( ! $userID ) {
				
				if (!is_user_logged_in()) return 0;
				$userID = get_current_user_id();
			}
			
				return round ( floatval(get_user_meta($userID, 'micropayments_balance', true)), 2 );;
			}



		static function balances( $userID, $options = null ) {
			// get html code listing balances
			if ( ! $options ) {
				$options = get_option( 'VWpaidMembershipOptions' );
			}
			
			if ( ! $options['walletMulti'] ) {
				return ''; // disabled
			}

			$htmlCode = '<!--VideoWhisper.com/MicroPayments/balances-->';

			$balances = self::walletBalances( $userID, '', $options );

			$walletTransfer = sanitize_text_field( $_GET['walletTransfer'] ?? '' );
			$walletTransfer2 = sanitize_text_field( $_GET['walletTransfer2'] ?? '' );

			global $wp;
			foreach ( $balances as $key => $value ) {
				$htmlCode .= '<br>';
				if ($key == $options['wallet']) $htmlCode .= '<i class="money bill alternate outline icon"></i> ';
				else $htmlCode .= '<i class="money alternate bill icon"></i> ';
					
				$htmlCode .= $key . ': ' . $value ;

				if ( $options['walletMulti'] == 2 && $walletTransfer != $key && $options['wallet'] != $key && $value > 0 ) {
					$htmlCode .= ' <a class="ui button compact tiny" href=' . add_query_arg( array( 'walletTransfer' => $key ), $wp->request ) . ' data-tooltip="' . __('Transfer to Active Balance', 'paid-membership') . '">' . __('Transfer to', 'video-share-vod') . ' ' . $options['wallet'] . ' </a>';
				}
				
				if ($options['wallet2'] && $options['walletMulti'] == 2 && $walletTransfer2 != $key && $options['wallet2'] != $key && $value > 0 ) {
					$htmlCode .= ' <a class="ui button compact tiny" href=' . add_query_arg( array( 'walletTransfer2' => $key ), $wp->request ) . ' data-tooltip="' . __('Transfer to Secondary Balance', 'paid-membership') . '">' . __('Transfer to', 'video-share-vod') . ' ' . $options['wallet2'] .  ' </a>';
				}
				
				//transfer to primary or auto
				if ( $walletTransfer == $key || ( $value > 0 && $options['walletMulti'] == 3 && $options['wallet'] != $key && $options['wallet2'] != $key ) ) {
					self::walletTransfer( $key, $options['wallet'], get_current_user_id(), $options );
					$htmlCode .= ' ' . __('Transferred to active balance.', 'paid-membership') . ' (' . $options['wallet'] . ')';
				}
				
				//transfer to secondary
				if ( $walletTransfer2 == $key ) {
					self::walletTransfer( $key, $options['wallet2'], get_current_user_id(), $options );
					$htmlCode .= ' ' . __('Transferred to secondary balance.', 'paid-membership') . ' (' . $options['wallet2'] . ')';
				}

			}

			return $htmlCode;
		}


		static function walletBalances( $userID, $view = 'view', $options = null ) {
			$balances = array();
			if ( ! $userID ) {
				return $balances;
			}
			
			//micropayments: snapshot in user meta
			$balances['MicroPayments'] = number_format( floatval ( get_user_meta($userID, 'micropayments_balance', true) ), 2, '.', '' );

			// woowallet
			if ( $GLOBALS['woo_wallet'] ?? false ) {
				$wooWallet             = $GLOBALS['woo_wallet'];
				if($wooWallet->wallet) $balances['WooWallet'] = $wooWallet->wallet->get_wallet_balance( $userID, $view );
			}

			// mycred
			if ( function_exists( 'mycred_get_users_balance' ) ) {
				$balances['MyCred'] = mycred_get_users_balance( $userID );
			}

			return $balances;
		}


		static function walletTransfer( $source, $destination, $userID, $options = null ) {
			// transfer balance from a wallet to another wallet

			if ( $source == $destination ) {
				return;
			}

			if ( ! $options ) {
				$options = get_option( 'VWpaidMembershipOptions' );
			}

			$balances = self::walletBalances( $userID, '', $options );

			if ( $balances[ $source ] > 0 ) {
				self::walletTransaction( $destination, $balances[ $source ], $userID, __('Wallet balance transfer', 'paid-membership') . " $source - $destination.", 'wallet_transfer' );
				self::walletTransaction( $source, - $balances[ $source ], $userID, __('Wallet balance transfer', 'paid-membership') . " $source - $destination.", 'wallet_transfer' );
			}
		}


		static function walletTransaction( $wallet, $amount, $user_id, $entry, $ref, $ref_id = null, $data = null ) {
			// transactions on all supported wallets
			// $wallet : MyCred/WooWallet

			if ( $amount == 0 ) {
				return; // no transaction
			}
			
			//micropayments			
			if ( $wallet == 'MicroPayments' )
			{
				self::micropayments_transaction ($user_id, $ref, $amount, $entry);
			} 

			// mycred
			if ( $wallet == 'MyCred' ) {
				if ( $amount > 0 ) {
					if ( function_exists( 'mycred_add' ) ) {
						mycred_add( $ref, $user_id, $amount, $entry, $ref_id, $data );
					}
				} else {
					if ( function_exists( 'mycred_subtract' ) ) {
						mycred_subtract( $ref, $user_id, $amount, $entry, $ref_id, $data );
					}
				}
			}

			// woowallet
			if ( $wallet == 'WooWallet' ) {
				if ( $GLOBALS['woo_wallet'] ?? false ) {
					$wooWallet = $GLOBALS['woo_wallet'];

					if ( $amount > 0 ) {
						$wooWallet->wallet->credit( $user_id, $amount, $entry );
					} else {
						$wooWallet->wallet->debit( $user_id, -$amount, $entry );
					}
				}
			}
		}


		static function option( $option )
		{
			$options = self::getOptions();

			if ( isset($options[$option]) ) return $options[$option];
			else return '';
		}
		
		static function balance( $userID = 0, $live = false, $options = null ) {
			// get user balance (as value), current user if not provided
			// $live also estimates active (incomplete) session costs for client

			if ( ! $userID ) {
				
				if (!is_user_logged_in()) return 0;
				$userID = get_current_user_id();
			}

			if ( ! $options ) {
				$options = self::getOptions();
			}

			$balance = 0;

			$balances = self::walletBalances( $userID, '', $options );

			if ( $options['wallet'] ) {
				if ( array_key_exists( $options['wallet'], $balances ) ) {
					$balance = $balances[ $options['wallet'] ];
				}
			}

			if ( $live ) { // live ppv costs estimation
					$temp = get_user_meta( $userID, 'vw_ppv_temp', true );
				$balance  = $balance - $temp; // deduct temporary charge
			}

			return $balance;
		}


		static function transaction( $ref = 'paid_membership', $user_id = 1, $amount = 0, $entry = 'MicroPayments', $ref_id = null, $data = null, $options = null ) {
			// ref = explanation ex. ppv_client_payment
			// entry = explanation ex. PPV client payment in room.
			// utils: ref_id (int|string|array) , data (int|string|array|object)

			if ( $amount == 0 ) {
				return; // nothing
			}

			if ( ! $options ) {
				$options = get_option( 'VWpaidMembershipOptions' );
			}

			// active wallet
			if ( $options['wallet'] ) {
				$wallet = $options['wallet'];
			}
			if ( ! $wallet ) {
				$wallet = 'MicroPayments';
			}
						
			self::walletTransaction( $wallet, $amount, $user_id, $entry, $ref, $ref_id, $data );
		}
	
	
	static function timeTo( $action, $expire = 60, $options = '' ) {
			// if $action was already done in last $expire, return false

			if ( ! $options ) {
				$options = self::getOptions();
			}

			$cleanNow = false;

			$ztime = time();

			$lastClean = 0;

			// saves in specific folder
			$timersPath = $options['uploadsPath'];
			if ( ! file_exists( $timersPath ) ) {
				mkdir( $timersPath );
			}
			$timersPath .= '/_timers/';
			if ( ! file_exists( $timersPath ) ) {
				mkdir( $timersPath );
			}

			$lastCleanFile = $timersPath . $action . '.txt';

			if ( ! file_exists( $dir = dirname( $lastCleanFile ) ) ) {
				mkdir( $dir );
			} elseif ( file_exists( $lastCleanFile ) ) {
				$lastClean = file_get_contents( $lastCleanFile );
			}

			if ( ! $lastClean ) {
				$cleanNow = true;
			} elseif ( $ztime - $lastClean > $expire ) {
				$cleanNow = true;
			}

			if ( $cleanNow ) {
				file_put_contents( $lastCleanFile, $ztime );
			}

				return $cleanNow;

		}



		static function template_redirect()
		{
			//fix rewrite rules if 404 but page exists (instead of saving permalinks manually from backend)
			if (is_404())	
			{
			global $wp_query, $wp_rewrite;
			$page_slug = $wp_query->query_vars['name'];
			if ($page_slug) 
				{ 
					
				$page = get_page_by_path($page_slug);
			    if ($page) 
			    {
				    if (self::timeTo('fix_rewrite_rules'))  //rate limit to prevent resource abuse

				    {
					    $wp_rewrite->flush_rules(true); 					    
					    wp_redirect( get_permalink( $page->ID ) );
						exit();
					 }
					echo '<!-- VideoWhisper/MicroPayments template_redirect is_404 --> <h4>Page exists but rewrite rules missing. Contact site administrator to save permalinks or troubleshoot!</h4>';
			    }
			    
				}				
			}	
		}
			
	}


}


// instantiate
if ( class_exists( 'VWpaidMembership' ) ) {
	$paidMembership = new VWpaidMembership();
}

// Actions and Filters
if ( isset( $paidMembership ) ) {
	register_activation_hook( __FILE__, array( &$paidMembership, 'activation' ) );
	register_deactivation_hook( __FILE__, array( &$paidMembership, 'deactivation' ) );

	add_action( 'init', array( &$paidMembership, 'init' ) );
	add_action( 'plugins_loaded', array( &$paidMembership, 'plugins_loaded' ) );

  //  add_action( 'wp_loaded', array( &$paidMembership, 'wp_loaded' ) );
	add_action( 'template_redirect', array( &$paidMembership, 'template_redirect' ) );

	add_action( 'cron_membership_update', array( &$paidMembership, 'membership_update_all' ) );
	add_action( 'cron_subscriptions_process', array( &$paidMembership, 'subscriptions_process' ) );
	add_action( 'cron_packages_process', array( &$paidMembership, 'packages_process' ) );

	// admin
	add_action( 'admin_menu', array( &$paidMembership, 'admin_menu' ) );
	add_action( 'admin_bar_menu', array( &$paidMembership, 'admin_bar_menu' ), 100 );
}

?>
