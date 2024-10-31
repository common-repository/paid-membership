<?php
// Options & Admin Backend

namespace VideoWhisper\PaidContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// ini_set('display_errors', 1); //debug only

trait Options {

	// ! Admin Side
	static function isModerator($userID, $options = null, $user = null, $roles = null)
	{
		if ( !$userID ) return false;
		
		if ( !$options ) $options = self::getOptions();
		
		if ( !$user) $user = get_userdata( $userID );
		
		if ( !$roles )
		{
			$roles = explode( ',', $options['roleModerators'] );
			foreach ( $roles as $key => $value )$roles[ $key ] = trim( $value );
		}

		if ( self::any_in_array( $roles, $user->roles ) ) return true;

		return false;
	}
	
	static function isCoauthor($userID, $postID)
	{
		$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
		if (!is_array($coauthors)) $coauthors = array();
		if (array_key_exists($userID, $coauthors)) return true;
	}

		// left menu
	function admin_menu() {

		add_menu_page( 'MicroPayments', 'MicroPayments', 'manage_options', 'paid-membership', array( 'VWpaidMembership', 'adminOptions' ), 'dashicons-awards', 83 );
		add_submenu_page( 'paid-membership', 'MicroPayments', 'Settings', 'manage_options', 'paid-membership', array( 'VWpaidMembership', 'adminOptions' ) );

		$options = get_option( 'VWpaidMembershipOptions' );
		if ( $options['downloads'] ?? false ) {
			add_submenu_page( 'paid-membership', 'MicroPayments', 'Downloads Add', 'manage_options', 'paid-membership-upload', array( 'VWpaidMembership', 'adminUpload' ) );
			add_submenu_page( 'paid-membership', 'MicroPayments', 'Downloads Import', 'manage_options', 'paid-membership-import', array( 'VWpaidMembership', 'adminImport' ) );
		}

		add_submenu_page( 'paid-membership', 'MicroPayments', 'Documentation', 'manage_options', 'paid-membership-doc', array( 'VWpaidMembership', 'adminDocs' ) );
		add_submenu_page( 'paid-membership', 'MicroPayments', 'Transactions', 'manage_options', 'micropayments-transactions', array( 'VWpaidMembership', 'adminTransactions' ) );

	}

	// top bar menu w. user menu
	function admin_bar_menu( $wp_admin_bar ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$options = get_option( 'VWpaidMembershipOptions' );

		// plugin-install.php?s=videowhisper&tab=search&type=term

		if ( current_user_can( 'editor' ) || current_user_can( 'administrator' ) ) {

			// find VideoWhisper menu
			$nodes = $wp_admin_bar->get_nodes();
			if ( ! $nodes ) {
				$nodes = array();
			}
			$found = 0;
			foreach ( $nodes as $node ) {
				if ( $node->title == 'VideoWhisper' ) {
					$found = 1;
				}
			}

			if ( ! $found ) {
				$wp_admin_bar->add_node(
					array(
						'id'    => 'videowhisper',
						'title' => '👁 VideoWhisper',
						'href'  => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);

				// more VideoWhisper menus
				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-add',
						'title'  => __( 'Add Plugins', 'paid-membership' ),
						'href'   => admin_url( 'plugin-install.php?s=videowhisper&tab=search&type=term' ),
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-consult',
						'title'  => __( 'Consult Developers', 'paid-membership' ),
						'href'   => 'https://consult.videowhisper.com/'),
					);

				$wp_admin_bar->add_node(
					array(
						'parent' => 'videowhisper',
						'id'     => 'videowhisper-contact',
						'title'  => __( 'Contact Support', 'paid-membership' ),
						'href'   => 'https://videowhisper.com/tickets_submit.php?topic=WordPress+Plugins+' . urlencode( $_SERVER['HTTP_HOST'] ),
					)
				);
			}

			$menu_id = 'videowhisper';

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id,
						'id'     => $menu_id . '-micropayments',
						'title'  => '💸 ' . __( 'MicroPayments', 'paid-membership' ),
						'href'   => admin_url( 'admin.php?page=paid-membership' ),
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-micropayments-transactions',
						'title'  => __( 'Transactions', 'paid-membership' ),
						'href'   => admin_url( 'admin.php?page=micropayments-transactions' ),
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-micropayments-pages',
						'title'  => __( 'Frontend Pages', 'paid-membership' ),
						'href'   => admin_url( 'admin.php?page=paid-membership&tab=pages' ),
					)
				);

				$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id . '-micropayments',
					'id'     => $menu_id . '-micropayments-wallets',
					'title'  => __( 'Billing Wallets', 'paid-membership' ),
					'href'   => admin_url( 'admin.php?page=paid-membership&tab=billing' ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'parent' => $menu_id . '-micropayments',
					'id'     => $menu_id . '-micropayments-content',
					'title'  => __( 'Paid Content', 'paid-membership' ),
					'href'   => admin_url( 'admin.php?page=paid-membership&tab=content' ),
				)
			);

					$wp_admin_bar->add_node(
						array(
							'parent' => $menu_id . '-micropayments',
							'id'     => $menu_id . '-micropayments-packages',
							'title'  => __( 'Token Packages', 'paid-membership' ),
							'href'   => admin_url( 'admin.php?page=paid-membership&tab=packages' ),
						)
					);

					$wp_admin_bar->add_node(
						array(
							'parent' => $menu_id . '-micropayments',
							'id'     => $menu_id . '-micropayments-subscriptions',
							'title'  => __( 'Author Subscriptions', 'paid-membership' ),
							'href'   => admin_url( 'admin.php?page=paid-membership&tab=subscriptions' ),
						)
					);

					$wp_admin_bar->add_node(
						array(
							'parent' => $menu_id . '-micropayments',
							'id'     => $menu_id . '-micropayments-donations',
							'title'  => __( 'Donations Funding', 'paid-membership' ),
							'href'   => admin_url( 'admin.php?page=paid-membership&tab=donations' ),
						)
					);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-micropayments-doc',
						'title'  => __( 'Documentation', 'paid-membership' ),
						'href'   => admin_url( 'admin.php?page=paid-membership-doc' ),
					)
				);
				

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-wpdiscuss',
						'title'  => __( 'Discuss WP Plugin', 'paid-membership' ),
						'href'   => 'https://wordpress.org/support/plugin/paid-membership/',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-wpreview',
						'title'  => __( 'Review WP Plugin', 'paid-membership' ),
						'href'   => 'https://wordpress.org/support/plugin/paid-membership/reviews/#new-post',
					)
				);

				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-vsv',
						'title'  => __( 'Start Video Site', 'paid-membership' ),
						'href'   => 'https://videosharevod.com/hosting/',
					)
				);
				
				$wp_admin_bar->add_node(
					array(
						'parent' => $menu_id . '-micropayments',
						'id'     => $menu_id . '-fps',
						'title'  => __( 'Start Live Streaming Site', 'paid-membership' ),
						'href'   => 'https://fanspaysite.com/',
					)
				);
				
		}

		$current_user = wp_get_current_user();

		// show wallet page if user has a balance
		if ( $options['p_videowhisper_my_wallet'] ?? false ) {
			$balance = self::balance( $current_user->ID );
			if ( intval($balance) ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_my_wallet',
						'title'  =>  '💵 ' . __( 'My Wallet', 'paid-membership' ) . ' ' . $balance . esc_attr($options['currency']),
						'href'   => get_permalink( $options['p_videowhisper_my_wallet'] ),
					)
				);
			}
		}
		
		if (self::rolesUser( $options['rolesSeller'] ?? '' , $current_user ) )
		{
			
		if ( $options['p_videowhisper_content_seller'] ?? false ) {
			if ( self::balance( $current_user->ID ) ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_content_seller',
						'title'  => '📁 ' . __( 'Manage Assets', 'paid-membership' ),
						'href'   => get_permalink( $options['p_videowhisper_content_seller'] ),
					)
				);
			}
		}
	
			if ( $options['p_videowhisper_provider_subscriptions'] ?? false ) {
			if ( self::balance( $current_user->ID ) ) {
				$wp_admin_bar->add_node(
					array(
						'parent' => 'my-account',
						'id'     => 'videowhisper_provider_subscriptions',
						'title'  => '🔒 ' . __( 'Setup Subscriptions', 'paid-membership' ),
						'href'   => get_permalink( $options['p_videowhisper_provider_subscriptions'] ),
					)
				);
			}
		}
			
		}

		if (self::rolesUser( $options['rolesBuyer'] ?? '' , $current_user ) )
		{
			
			if ( $options['p_videowhisper_client_subscriptions'] ?? false ) 
			$wp_admin_bar->add_node(
				array(
					'parent' => 'my-account',
					'id'     => 'videowhisper_client_subscriptions',
					'title'  => '🔓 ' . __( 'My Subscriptions', 'paid-membership' ),
					'href'   => get_permalink( $options['p_videowhisper_client_subscriptions'] ),
				)
			);

			if ( $options['p_videowhisper_content'] ?? false ) 
			$wp_admin_bar->add_node(
				array(
					'parent' => 'my-account',
					'id'     => 'videowhisper_content',
					'title'  => '📂 ' . __( 'My Content', 'paid-membership' ),
					'href'   => get_permalink( $options['p_videowhisper_content'] ),
				)
			);
			
		}



	}



	static function settings_link( $links ) {
		$settings_link = '<a href="admin.php?page=paid-membership">' . __( 'Settings' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}


	static function adminDocs() {      ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
	<h2>Paid Membership, Content, Downloads by VideoWhisper</h2>
</div>

<a href="https://ppvscript.com/micropayments/" class="button" > MicroPayments Plugin Homepage </a>  
<a href="<?php echo admin_url( 'admin.php?page=paid-membership' ); ?>" class="button" >Plugin Setup & Settings </a>

<p> If you find this plugin idea useful or interesting, <a href="https://profiles.wordpress.org/videowhisper/#content-plugins">leave a review</a> to help us drive more resources into further development and improvements. After posting a review, you can also <a href="https://videowhisper.com/tickets_submit.php">submit a ticket</a> with review link to VideoWhisper support, to claim a gift.</p>

<p> If you need changes, extra features or permission to remove any developer references, <a href="https://videowhisper.com/tickets_submit.php">submit a ticket</a>. </p>

	
<h3>Shortcodes</h3>

<h4>[videowhisper_my_wallet]</h4>
Shows user wallet(s) and options to buy credits/tokens. Available in a  My Wallet page after Setup Pages.

<h4>[videowhisper_wallet]</h4>
Shows wallet button with current balance, linking to My Wallet page.

<h4>[videowhisper_packages_process user_id='0' verbose='1']</h4>
Add to a thank you page or page where users get redirected after making WooCommerce purchases, to process token packages. Set user_id as -1 to process only for currently logged in user or specify a user id number, otherwise it will process all packages. Set verbose to 1 to include processing debug info as comment in HTML source code (visible in browser source code view).

<h4>[videowhisper_donate userid="" wallet="1"]</h4>
Show a button to allow donation for that user (by user ID). Also shows wallet button with current balance. 

<h4>[videowhisper_membership_buy]</h4>
Shows membership info and upgrade options for user. Available in a page after Setup Pages.

<h3>Content Shortcodes</h3>

<h4>[videowhisper_content_upload category="" playlist=""  owner=""  tag=""  description="" picture=""]</h4>
Plupload multi uploader, allows uploading various types of files, depeding on active options and plugins (downloads, VideoShareVOD, Picture Gallery). Available in a Content Upload page after Setup Pages.


<h4>[videowhisper_content_upload_guest gallery="" category="" tags="" description="" owner="" terms="" email=""]</h4>
		Displays interface to upload pictures, for guests with special features like Google reCaptcha integration (configurable from settings).
		<br>category: ID of category. If not defined a dropdown is shown.
		<br>tags: Tags to be assigned to picture. Input field will be shown if not provided.
		<br>description: Description to be assigned to picture. Input field will be shown if not provided.
		<br>owner: Owner user id. You can set the id of user where guest content should be assigned.
		<br>gallery: A special taxonomy for pictures, videos (playlist), downloads (collection). 
		<br>email: If nothing is provided user will be asked to fill an email. For registertered users, the user email is used.
		<br>terms: Terms of Use page URL. Can be configured from settings to a site page.


<h4>[videowhisper_content_list tabs="1" author_id="" ids="" category_id="" name="" tags="" order_by="" perpage="" perrow="" menu="1" tabs="1" select_type="1" select_access="1" select_category="1" select_tags="1" select_name="1" select_order="1" select_page="1" include_css="1" id=""]</h4>
Display all paid content types as configured in backed. Can include a filtered list of id numbers as csv in "id". Available in a Content page after Setup Pages. Integrated as "content" section in BuddyPress/BuddyBoss profile, if available.

<h4>[videowhisper_content_seller]</h4>
Enables provider/seller to manage cotent (digital assets). Available in a Manage Assets page after Setup Pages.

<h4>[videowhisper_content_edit]</h4>
Enables editing digital content (custom post) price and settings. Available in Edit Content page after Setup Pages. Content id is passed by GET parameter editID like /content-edit?editID=[$postID].

<h4>[videowhisper_content]</h4>
Pages lists digital content purchased by current user, by MicroPayments and/or WooCommerce products. An easy way for clients to access paid content they purchased. Available in My Content page after Setup Pages.


<h3>Subscription Shortcodes</h3>

<h4>[videowhisper_provider_subscriptions]</h4>
Enables provider (author/creator) to create and manage subscriptions.

<h4>[videowhisper_client_subscribe author_id="" author_login=""]</h4>
Show author subscriptions, for client to subscribe.  Integrated as "subscribe" section in BuddyPress/BuddyBoss profile, if available.

<h4>[videowhisper_client_subscriptions client_id=""]</h4>
List/mange subscriptions of client.


<h3>Downloads Shortcodes</h3>

		<h4>[videowhisper_downloads collections="" category_id="" order_by="" perpage="" perrow="" select_category="1" select_tags="1" select_name="1" select_order="1" select_page="1" include_css="1" id=""]</h4>
		Displays downloads list. Loads and updates by AJAX. Optional parameters: download collection name, maximum downloads per page, maximum downloads per row.
		<br>order_by: post_date / download-views / download-lastview
		<br>select attributes enable controls to select category, order, page
		<br>include_css: includes the styles (disable if already loaded once on same page)
		<br>id is used to allow multiple instances on same page (leave blank to generate)

		<h4>[videowhisper_download_upload collection="" category="" owner="" picture=""]</h4>
		Displays interface to upload downloads.
		<br>collection: If not defined owner name is used as collection for regular users. Admins with edit_users capability can write any collection name. Multiple collections can be provided as comma separated values.
		<br>category: If not define a dropdown is listed.
		<br>owner: User is default owner. Only admins with edit_users capability can use different.

	   <h4>[videowhisper_download_import path="" collection="" category="" owner=""]</h4>
		Displays interface to import downloads.
		<br>path: Path where to import from.
		<br>collection: If not defined owner name is used as collection for regular users. Admins with edit_users capability can write any collection name. Multiple collections can be provided as comma separated values.
		<br>category: If not define a dropdown is listed.
		<br>owner: User is default owner. Only admins with edit_users capability can use different.

		<h4>[videowhisper_download download="0" player="" width=""]</h4>
		Displays video player. Video post ID is required.
		<br>Player: html5/html5-mobile/strobe/strobe-rtmp/html5-hls/ blank to use settings & detection
		<br>Width: Force a fixed width in pixels (ex: 640) and height will be adjusted to maintain aspect ratio. Leave blank to use video size.

		<h4>[videowhisper_download_preview video="0"]</h4>
		Displays video preview (thumbnail) with link to download post. download post ID is required.

	<h4>[videowhisper_postdownloads post="post id"]</h4>
		Manage post associated downloads. Required: post

	<h4>[videowhisper_postdownloads_process post="" post_type=""]</h4>
		Process post associated downloads (needs to be on same page with [videowhisper_postdownloads] for that to work).

<h3>Custom SQL Tables</h3>
You can empty these tables after testing or develop code working with the custom data:
vw_micropay_transactions

		<?php
	}


		// ! Feature Pages and Menus

	static function setupPagesList( $options = null ) {

		if ( ! $options ) {
			$options = get_option( 'VWpaidMembershipOptions' );
		}

		// shortcode pages
		 $pages = array(
			 'videowhisper_content_list'           => 'Content',
			 'videowhisper_my_wallet'              => 'My Wallet',
			 'videowhisper_content'                => 'My Content',
			 'videowhisper_content_seller'         => 'Manage Assets',
			 'videowhisper_content_upload'         => 'Content Upload',
			 'videowhisper_content_upload_guest'         => 'Guest Upload',
			 'videowhisper_content_edit'           => 'Edit Content',
			 'videowhisper_membership_buy'         => 'Membership',
			 'videowhisper_provider_subscriptions' => 'Setup Subscriptions',
			 'videowhisper_client_subscribe'       => 'Subscribe to Author',
			 'videowhisper_client_subscriptions'   => 'My Subscriptions',
		 );

		 if ( $options['downloads'] ) {
			 $pages['videowhisper_downloads']       = 'Downloads';
			 $pages['videowhisper_download_upload'] = 'Add Download';

		 }

		 if ( shortcode_exists( 'videowhisper_html5recorder' ) ) {
			 $pages['videowhisper_provider_record'] = 'Add Recording';
		 }

			return $pages;
	}

	static function setupPagesContent( $options = null ) {

		if ( ! $options ) {
			$options = get_option( 'VWpaidMembershipOptions' );
		}

		if ( $options['p_videowhisper_content_seller'] ?? false ) {
			return array(
				'videowhisper_provider_record' => '[videowhisper_html5recorder exiturl="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"]',
			);
		} else {
			return array();
		}

	}


	static function setupPages() {
		$options = get_option( 'VWpaidMembershipOptions' );
		if ( $options['disableSetupPages'] ) {
			return;
		}

		$pages = self::setupPagesList();

		$noMenu = array( 'videowhisper_content_edit', 'videowhisper_client_subscribe' );

		$parents = array(
			'videowhisper_content_list'           => array( 'Webcams', 'Channels', 'Videos', 'Client', 'Client Dashboard', 'Content' ),
			'videowhisper_my_wallet'              => array( 'Client', 'Fan', 'Client Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_content'                => array( 'Client', 'Fan', 'Client Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_membership_buy'         => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_content_seller'         => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_content_upload'         => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_content_upload_guest'   => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_provider_subscriptions' => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_provider_record'        => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_client_subscriptions'   => array( 'Client', 'Fan', 'Client Dashboard', 'Channels', 'Videos', 'Content' ),		
			'videowhisper_downloads'        => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content' ),
			'videowhisper_download_upload'        => array( 'Peformer', 'Provider', 'Author', 'Performer Dashboard', 'Channels', 'Videos', 'Content', 'Downloads' ),	
		);

		$duplicate = array();

		// create a menu and add pages
		$menu_name   = 'VideoWhisper';
		$menu_exists = wp_get_nav_menu_object( $menu_name );

		if ( ! $menu_exists ) {
			$menu_id = wp_create_nav_menu( $menu_name );
		} else {
			$menu_id = $menu_exists->term_id;
		}

		// create pages if not created or existant
		foreach ( $pages as $key => $value ) {

			$pid = $options[ 'p_' . $key ] ?? 0;
			if ( $pid ) {
				$page = get_post( $pid );
			} else $page = null;
			
			if ( ! $page ) {
				$pid = 0;
			}

			// custom content (not shortcode) - updates each time as it uses previous pages in latest $options
			$content = self::setupPagesContent( $options );

			if ( ! $pid ) {
				// page exists (by shortcode title)
				global $wpdb;
				$pidE = $wpdb->get_var( "SELECT ID FROM $wpdb->posts WHERE post_name = '" . $value . "'" );

				if ( $pidE ) {
					$pid = $pidE;
				} else {

					$page                   = array();
					$page['post_type']      = 'page';
					$page['post_parent']    = 0;
					$page['post_status']    = 'publish';
					$page['post_title']     = $value;
					$page['comment_status'] = 'closed';

					if ( array_key_exists( $key, $content ) ) {
						$page['post_content'] = $content[ $key ]; // custom content
					} else {
						$page['post_content'] = '[' . $key . ']';
					}

					$pid = wp_insert_post( $page );
				}

				$options[ 'p_' . $key ] = $pid;
				$link                   = get_permalink( $pid );

				// get updated menu
				$menuItems = wp_get_nav_menu_items( $menu_id, array( 'output' => ARRAY_A ) );

				// find if menu exists, to update
				$foundID = 0;
				foreach ( $menuItems as $menuitem ) {
					if ( $menuitem->title == $value ) {
						$foundID = $menuitem->ID;
						break;
					}
				}

				if ( ! in_array( $key, $noMenu ) ) {
					if ( $menu_id ) {
						// select menu parent
						$parentID = 0;
						if ( array_key_exists( $key, $parents ) ) {
							foreach ( $parents[ $key ] as $parent ) {
								foreach ( $menuItems as $menuitem ) {
									if ( $menuitem->title == $parent ) {
										$parentID = $menuitem->ID;
										break 2;
									}
								}
							}
						}
							
						// update menu for page
						$updateID = wp_update_nav_menu_item(
							$menu_id,
							$foundID,
							array(
								'menu-item-title'     => $value,
								'menu-item-url'       => $link,
								'menu-item-status'    => 'publish',
								'menu-item-object-id' => $pid,
								'menu-item-object'    => 'page',
								'menu-item-type'      => 'post_type',
								'menu-item-parent-id' => $parentID,
							)
						);

						// duplicate menu, only first time for main menu
						if ( ! $foundID ) {
							if ( ! $parentID ) {
								if ( intval( $updateID ?? 0 ) ) {
									if ( in_array( $key, $duplicate ) ) {
										wp_update_nav_menu_item(
											$menu_id,
											0,
											array(
												'menu-item-title'  => $value,
												'menu-item-url'    => $link,
												'menu-item-status' => 'publish',
												'menu-item-object-id' => $pid,
												'menu-item-object' => 'page',
												'menu-item-type'   => 'post_type',
												'menu-item-parent-id' => $updateID,
											)
										);
									}
								}
							}
						}
					}
				}
			}
		}

		update_option( 'VWpaidMembershipOptions', $options );
	}


		// ! Options

	static function extensions_download() {
		 // allowed file extensions
		$options = self::getOptions();

		if ( $options['download_extensions'] ) {
			$extensions = explode( ',', $options['download_extensions'] );

			if ( is_array( $extensions ) ) {
				foreach ( $extensions as $key => $value ) {
					$extensions[ $key ] = trim( $value );
				}
				return $extensions;
			}
		}

		return array();
	}


	static function adminOptionsDefault() {
		$upload_dir = wp_upload_dir();
		$root_url   = plugins_url();
		$root_ajax  = admin_url( 'admin-ajax.php?action=vmls&task=' );

		return array(
			'titleClean' => 'on,off,and,or,for,with,at,by,in,of,the,to,from,as,an,a',
			'titleSize' => 48,
			'userTitle' => 'display_name',
			'visitorUpload' => 0,

			'guestStatus' => 'pending', // 'publish', 'pending', 'draft', 'private'
			'guestMessage' 					=> 'File was successfully uploaded and is pending approval.',
			'guestSubject'                 => 'Approve Guest Upload',
			'guestText'                    => 'A guest uploaded digital content. Open this link to review and Publish: ',
			'moderatorEmail'               => get_bloginfo('admin_email'),
	
			'uploadsIPlimit' => 3 ,
			'termsPage'                     => 0,
			'recaptchaSite'                   => '',
			'recaptchaSecret'                 => '',

			'commentsAccess' => 0,
			'commentsLimit' => 0,

			'buddypressProfile' => 1,
			'buddypressPost' => 1,
			
			'hidePostThumbnail' => 0,
			
			'contentMin' =>0,
			'contentMax' =>100,

			'subscriptionMin' => 0,
			'subscriptionMax' => 200,
			
			'donationRatio'   => 0.9,
			'contentRatio'   => 0.8,

			'listingsTabs' => 'auto', //show tabs section for listings
			'listingsMenu'                    => 'auto', //show menu section for listings
			
			'postTypesExternal' => 'webcam, room',
			'roleModerators' 	  => 'administrator, editor, moderator',
			'rolesBuyer' 		  => 'administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan',
			'rolesSeller' 		  => 'administrator, editor, author, contributor, performer, creator, studio',
			'rolesPackages' 	=> 'administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan',
 
			'rolesDonate' 		  => 'administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan',

			'tiersMax'            => 3,
			'subscriptionRatio'   => 0.8,

			'donationMin'         => '1',
			'donationMax'         => '31',
			'donationDefault'     => '5',
			'donationStep'        => '2',

			'currency'            => 'tk$',
			'currencyLong'        => 'tokens',

			'jquery_theme'        => 'base',
			'interfaceClass'      => '',
			'themeMode' 		  => '',
			'userName'            => 'user_nicename',

			'downloads'           => '',
			'download_extensions' => 'pdf,doc,docx,odt,rtf,tex,txt,ppt,pptx,key,odp,xls,xlsx,csv,sql,zip,tar,gz,rar,psd,ttf,otf,fon,fnt,mp3,ogg,wav',
			'custom_post'         => 'download',
			'custom_taxonomy'     => 'collection',

			'rateStarReview'      => '1',

			'editContent'         => 'all',

			'vwls_collection'     => '1',

			'importPath'          => '/home/[your-account]/public_html/streams/',
			'importClean'         => '45',
			'deleteOnImport'      => '1',

			'vwls_channel'        => '1',

			'postTemplate'        => '+plugin',
			'taxonomyTemplate'    => '+plugin',

			'pictureWidth'        => '',

			'thumbWidth'          => '256',
			'thumbHeight'         => '256',

			'perPage'             => '8',
			'perPageContent'      => '8',

			'shareList'           => 'Super Admin, Administrator, Editor, Author, Contributor, Performer, Creator',
			'publishList'         => 'Super Admin, Administrator, Editor, Author',

			'role_collection'     => '1',

			'watchList'           => 'Super Admin, Administrator, Editor, Author, Contributor, Subscriber, Guest',
			'accessDenied'        => '<h3>Access Denied</h3>
<p>#info#</p>',

			'uploadsPath'         => $upload_dir['basedir'] . '/vw_downloads',

			'wallet'              => 'MicroPayments',
			'wallet2'              => '',

			'walletMulti'         => '2',

			'freeRole'            => 'subscriber',
			'memberships'         => unserialize( 'a:3:{i:0;a:5:{s:5:"label";s:5:"Basic";s:4:"role";s:5:"Basic";s:5:"price";s:1:"8";s:6:"expire";s:2:"30";s:9:"recurring";s:1:"1";}i:1;a:5:{s:5:"label";s:8:"Standard";s:4:"role";s:8:"Standard";s:5:"price";s:2:"10";s:6:"expire";s:2:"30";s:9:"recurring";s:1:"1";}i:2;a:5:{s:5:"label";s:7:"Premium";s:4:"role";s:7:"Premium";s:5:"price";s:2:"12";s:6:"expire";s:2:"30";s:9:"recurring";s:1:"1";}}' ),

			'disableSetupPages'   => '0',
			'paid_handler'        => 'micropayments',
			'postTypesAuthors'       => 'post, video, picture, download',
			'postTypesRoles'      => 'page, post, channel, webcam, conference, presentation, videochat, video, picture, download, room',
			'postTypesPopular'	 =>  'video, picture, download',
			'postTypesPaid'       => 'post, channel, webcam, conference, presentation, videochat, video, picture, download, room',
			'postTypesEdit'       => 'post, channel, conference, presentation, videochat, video, picture, download, room',
			'postTypesDonate'     => 'post, channel, conference, presentation, videochat, video, picture, download, room',

			'loginMessage'        => 'Upgrading membership is only available for existing users. Please <a class="ui button" href="' . wp_registration_url() . ' ">register</a> or <a class="ui button" href="' . wp_login_url() . '">login</a>!',
			'visitorMessage'      => 'This content is only available for registered members.',
			'roleMessage'         => 'This content is not available for your current membership.',
			'paidMessage'         => 'This content is available after purchase.',
			'customCSS'           => '
.paid_membership_listing
{
padding: 5px;
margin: 5px;
border: solid 1px #AAA;
}
',
			'downloadsCSS'        => '
			.videowhisperDownload
{
position: relative;
display:inline-block;

border:1px solid #aaa;
background-color:#777;
padding: 0px;
margin: 2px;

width: 256;
height: 256;
}

.videowhisperDownload:hover {
	border:1px solid #fff;
}

.videowhisperDownload IMG
{
padding: 0px;
margin: 0px;
border: 0px;
}

.videowhisperDownloadTitle
{
position: absolute;
top:0px;
left:0px;
margin:8px;
font-size: 14px;
color: #FFF;
text-shadow:1px 1px 1px #333;
}

.videowhisperDownloadEdit
{
position: absolute;
top:34px;
right:0px;
margin:8px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;
background: rgba(0, 100, 255, 0.7);
padding: 3px;
border-radius: 3px;
}

.videowhisperDownloadDuration
{
position: absolute;
bottom:5px;
left:0px;
margin:8px;
font-size: 14px;
color: #FFF;
text-shadow:1px 1px 1px #333;
}

.videowhisperDownloadDate
{
position: absolute;
bottom:5px;
right:0px;
margin: 8px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;
}

.videowhisperDownloadViews
{
position: absolute;
bottom:16px;
right:0px;
margin: 8px;
font-size: 10px;
color: #FFF;
text-shadow:1px 1px 1px #333;
}

.videowhisperDownloadRating
{
position: absolute;
bottom: 5px;
left:5px;
font-size: 15px;
color: #FFF;
text-shadow:1px 1px 1px #333;

z-index: 10;
}
		',

			'contentCSS'          => '
.videowhisperContent
{
position: relative;
display:inline-block;

border:1px solid #aaa;
background-color:#777;
padding: 0px;
margin: 2px;

width: 256px;
height: 256px;

overflow:hidden;
}

.videowhisperPreviewVideo, .videowhisperPreviewImg {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}

.videowhisperContent:hover {
border:1px solid #fff;
}

.videowhisperContentTitle
{
position: absolute;
top:0px;
left:0px;
margin:8px;

font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;

max-width: 256px;
max-height: 18px;
overflow: hidden;

z-index: 10;
}

.videowhisperContentType
{
position: absolute;
top:20px;
left:0px;
margin:8px;
font-size: 12px;
color: #FFF;
text-shadow:1px 1px 1px #333;

z-index: 10;
}

.videowhisperContentSubscription
{
position: absolute;
top:25px;
right:0px;
margin:2px;
font-size: 11px;

z-index: 10;
}

.videowhisperContentPrice
{
position: absolute;
top:50px;
right:0px;
margin:2px;
font-size: 11px;

z-index: 10;
}


.videowhisperContentEdit
{
position: absolute;
top:50px;
left:0px;
margin:8px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;
background: rgba(0, 100, 255, 0.7);
padding: 3px;
border-radius: 3px;

z-index: 10;
}

.videowhisperContentDuration
{
position: absolute;
bottom:5px;
left:0px;
margin:8px;
font-size: 14px;
color: #FFF;
text-shadow:1px 1px 1px #333;

z-index: 10;
}

.videowhisperContentDate
{
position: absolute;
bottom:5px;
right:0px;
margin: 8px;
font-size: 11px;
color: #FFF;
text-shadow:1px 1px 1px #333;

z-index: 10;
}

.videowhisperContentViews
{
position: absolute;
bottom:16px;
right:0px;
margin: 8px;
font-size: 10px;
color: #FFF;
text-shadow:1px 1px 1px #333;

z-index: 10;
}

.videowhisperContentRating
{
position: absolute;
bottom: 5px;
left:5px;
font-size: 15px;
color: #FFF;
text-shadow:1px 1px 1px #333;
z-index: 10;
}		
',
			'videowhisper'        => 1,

		);

	}


	static function getOptions() {
		 $options = get_option( 'VWpaidMembershipOptions' );
		if ( ! $options ) {
			$options = self::adminOptionsDefault();
		}

		return $options;
	}

	static function getAdminOptions() {

		$adminOptions = self::adminOptionsDefault();

		$options = get_option( 'VWpaidMembershipOptions' );
		if ( ! empty( $options ) ) {
			foreach ( $options as $key => $option ) {
				$adminOptions[ $key ] = $option;
			}
		}

		update_option( 'VWpaidMembershipOptions', $adminOptions );
		return $adminOptions;
	}



	static function adminOptions() {
		$options        = self::getAdminOptions();
		$optionsDefault = self::adminOptionsDefault();

		if ( isset( $_POST ) ) {
			if ( ! empty( $_POST ) ) {
				$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) ) {
					echo 'Invalid nonce!';
					exit;
				}

				foreach ( $options as $key => $value ) {
					if ( isset( $_POST[ $key ] ) ) {
						
						if ( in_array( $key, [ 'loginMessage', 'roleMessage', 'visitorMessage', 'accessDenied'] ) ) $options[ $key ] = trim( wp_kses_post(  $_POST[ $key ] ) ); //filtered html
						else $options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
					
					}
				}		
				
				update_option( 'VWpaidMembershipOptions', $options );

			}
		}

		// self::setupPages();

		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'overview';

		?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
<h2>MicroPayments - Paid Membership, Content, Downloads by VideoWhisper</h2>
</div>

<nav class="nav-tab-wrapper wp-clearfix" aria-label="Secondary menu">
	<a href="admin.php?page=paid-membership&tab=overview" class="nav-tab <?php echo $active_tab == 'overview' ? 'nav-tab-active' : ''; ?>">Overview</a>
	<a href="admin.php?page=paid-membership&tab=integration" class="nav-tab <?php echo $active_tab == 'integration' ? 'nav-tab-active' : ''; ?>">Content Integration</a>

	<a href="admin.php?page=paid-membership&tab=pages" class="nav-tab <?php echo $active_tab == 'pages' ? 'nav-tab-active' : ''; ?>">Setup Pages</a>

	<a href="admin.php?page=paid-membership&tab=appearance" class="nav-tab <?php echo $active_tab == 'appearance' ? 'nav-tab-active' : ''; ?>">Appearance</a>

	
	<a href="admin.php?page=paid-membership&tab=billing" class="nav-tab <?php echo $active_tab == 'billing' ? 'nav-tab-active' : ''; ?>">Billing Wallets</a>
	<a href="admin.php?page=paid-membership&tab=packages" class="nav-tab <?php echo $active_tab == 'packages' ? 'nav-tab-active' : ''; ?>">Token Packages</a>


	<a href="admin.php?page=paid-membership&tab=content" class="nav-tab <?php echo $active_tab == 'content' ? 'nav-tab-active' : ''; ?>">Paid Content</a>
	<a href="admin.php?page=paid-membership&tab=listings" class="nav-tab <?php echo $active_tab == 'listings' ? 'nav-tab-active' : ''; ?>">Listings</a>

	<a href="admin.php?page=paid-membership&tab=guest" class="nav-tab <?php echo $active_tab == 'guest' ? 'nav-tab-active' : ''; ?>">Guest Upload</a>

	<a href="admin.php?page=paid-membership&tab=donations" class="nav-tab <?php echo $active_tab == 'donations' ? 'nav-tab-active' : ''; ?>">Donations & CrowdFunding</a>
	<a href="admin.php?page=paid-membership&tab=subscriptions" class="nav-tab <?php echo $active_tab == 'subscriptions' ? 'nav-tab-active' : ''; ?>">Author Subscriptions</a>

	
	<a href="admin.php?page=paid-membership&tab=content-membership" class="nav-tab <?php echo $active_tab == 'content-membership' ? 'nav-tab-active' : ''; ?>">Content by Membership</a>	
	
	<a href="admin.php?page=paid-membership&tab=import" class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>">Import/Export Options</a>
	<a href="admin.php?page=paid-membership&tab=reset" class="nav-tab <?php echo $active_tab == 'reset' ? 'nav-tab-active' : ''; ?>">Reset Options</a>

	<a href="admin.php?page=paid-membership&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Site Membership Settings</a>
	<a href="admin.php?page=paid-membership&tab=membership" class="nav-tab <?php echo $active_tab == 'membership' ? 'nav-tab-active' : ''; ?>">Site Membership Levels</a>
	<a href="admin.php?page=paid-membership&tab=users" class="nav-tab <?php echo $active_tab == 'users' ? 'nav-tab-active' : ''; ?>">Site Membership Users</a>

		<a href="admin.php?page=paid-membership&tab=downloads" class="nav-tab <?php echo $active_tab == 'downloads' ? 'nav-tab-active' : ''; ?>">Downloads</a>
		<a href="admin.php?page=paid-membership&tab=share" class="nav-tab <?php echo $active_tab == 'share' ? 'nav-tab-active' : ''; ?>">Downloads Share</a>
		<a href="admin.php?page=paid-membership&tab=access" class="nav-tab <?php echo $active_tab == 'access' ? 'nav-tab-active' : ''; ?>">Downloads Access</a>

</nav>

<form method="post" action="<?php echo wp_nonce_url( 'admin.php?page=paid-membership&tab=' . esc_attr( $active_tab ), 'vwsec' ); ?>">
		<?php

		$hideSubmit = false;

		switch ( $active_tab ) {

			case 'listings':
				?>
				<h3>Listings</h3>
				Configuration for listings (mainly for [videowhisper_content_list] shortcode).

<h4>Listings Tabs: Type, Access (Monetization)</h4>
<select name="listingsTabs" id="listingsTabs">
  <option value="auto" <?php echo $options['listingsTabs'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
  <option value="1" <?php echo $options['listingsTabs'] == '1' ? 'selected' : ''; ?>>Tabs</option>
  <option value="0" <?php echo $options['listingsTabs'] == '0' ? 'selected' : ''; ?>>Dropdowns</option>
</select>
<br>Show content types and monetization filtering as tabs. Auto will disable tabs on mobile devices (and show dropdowns).

<h4>Listings Menu: Categories, Order</h4>
<select name="listingsMenu" id="listingsMenu">
  <option value="auto" <?php echo $options['listingsMenu'] == 'auto' ? 'selected' : ''; ?>>Auto</option>
  <option value="1" <?php echo $options['listingsMenu'] == '1' ? 'selected' : ''; ?>>Menu</option>
  <option value="0" <?php echo $options['listingsMenu'] == '0' ? 'selected' : ''; ?>>Dropdowns</option>
</select>
<br>Show categories and order options as menu. Auto will disable menu on mobile devices.

<h4>Content Listings CSS</h4>
				<?php
				$options['contentCSS'] = htmlentities( stripslashes( $options['contentCSS'] ) );

				?>
<textarea name="contentCSS" id="contentCSS" cols="100" rows="8"><?php echo esc_textarea( $options['contentCSS'] ); ?></textarea>
<br>Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['contentCSS'] ); ?></textarea>


<h4><?php _e( 'Default Content Listings Per Page', 'paid-membership' ); ?></h4>
<input name="perPageContent" type="text" id="perPageContent" size="3" maxlength="3" value="<?php echo esc_attr( $options['perPageContent'] ); ?>"/>

<?php
							break;

							case 'guest':
								?>
								<h3>Guest Content Upload</h3>
								Enable guests (regular users or even visitors) to upload content with a simpler interface and extra security features. That includes reCaptcha, approval, to prevent flood/spam. Shortcode parameters (fill predefined values to hide these fields):  
			<pre>
			<code>[videowhisper_content_upload_guest category="" gallery="" owner="" tag="" description="" terms="" email=""]</code>
			category = category id, a dropdown will show if not provided
			gallery = galleries (csv), Guest will be used for visitors if not provided
			owner = owner id, 0 will be used for guest if not provided
			tag = tags (csv), an input will show if not provided
			description = post contents (text), a textarea field will show if not provided
			terms = Terms of Use url, select default from settings below
			email = uploader email, to associate with picture, a field will show if not provided
			</pre>
			
			<h4>Visitor Upload</h4>
			<select name="visitorUpload" id="visitorUpload">
			<option value='1' <?php  echo ( $options['visitorUpload'] ?? false ? 'selected' : '' ); ?>>Enabled</option>
			<option value='0' <?php  echo ( $options['visitorUpload'] ?? false ? '' : 'selected' ); ?>>Disabled</option>
			</select>
			<BR>Allow visitors to upload files. Not recommended until you setup reCaptcha. Default: <?php echo esc_html( $optionsDefault['visitorUpload'] ); ?>
		
		
			<h4>User in Title</h4>
			<select name="userTitle" id="userTitle">
			<option value='username' <?php  echo ( $options['userTitle'] == 'username' ? 'selected' : '' ); ?>>Username</option>
			<option value='display_name' <?php  echo ( $options['userTitle'] == 'display_name' ? 'selected' : '' ); ?>>WP Display Name</option>
			<option value='user_login' <?php  echo ( $options['userTitle'] == 'user_login' ? 'selected' : '' ); ?>>WP Login</option>
			<option value='user_nicename' <?php  echo ( $options['userTitle'] == 'user_nicename' ? 'selected' : '' ); ?>>WP Nice Name</option>
			<option value='5' <?php  echo ( $options['userTitle'] == '5' ? 'selected' : '' ); ?>>5 Chars from Username</option>
			<option value='' <?php  echo ( $options['userTitle'] ?? false ? '' : 'selected' ); ?>>Disabled</option>
			</select>
			<br/>Prefix content title with username. Default: <?php echo esc_html( $optionsDefault['userTitle'] ); ?>

			<h4>Title Clean</h4>
			<input name="titleClean" type="text" id="titleClean" size="100" maxlength="256" value="<?php echo esc_attr( $options['titleClean'] ); ?>"/>
			<br>Comma separated list of words to remove from title, when generated from filename. Default: <?php echo esc_html( $optionsDefault['titleClean'] ); ?>

			<h4>Title Max Size</h4>
			<input name="titleSize" type="text" id="titleSize" size="5" maxlength="5" value="<?php echo esc_attr( $options['titleSize'] ); ?>"/>
			<br>Maximum title size (characters). Set 0 to disable limitation. Default: <?php echo esc_html( $optionsDefault['titleSize'] ); ?>

			<h4>Google reCAPTCHA v3: Site Key</h4>
			<input name="recaptchaSite" type="text" id="recaptchaSite" size="100" maxlength="256" value="<?php echo esc_attr( $options['recaptchaSite'] ); ?>"/>
			<br>Register your site for free for using <a href="https://www.google.com/recaptcha/admin/create">Google reCAPTCHA v3</a> to protect your site from spam bot uploads and brute force form submissions. This is highly recommended if you allow guest uploads.
			
			<h4>Google reCAPTCHA v3: Secret Key</h4>
			<input name="recaptchaSecret" type="text" id="recaptchaSite" size="100" maxlength="256" value="<?php echo esc_attr( $options['recaptchaSecret'] ); ?>"/>
			
			<h4>Limit Uploads per IP</h4>
			<input name="uploadsIPlimit" type="text" id="uploadsIPlimit" size="5" maxlength="32" value="<?php echo esc_attr( $options['uploadsIPlimit'] ); ?>"/>
			<br>Prevent multiple uploads from same IP, using this plugin. Tracking applies for guest upload shortcode only. Set 0 to disable. Default: <?php echo esc_html( $optionsDefault['uploadsIPlimit'] ); ?>
			
			<h4>Terms Page</h4>
			<select name="termsPage" id="termsPage">
			<option value='0'
							<?php
						if ( $options['termsPage'] == 0 )
						{
							echo 'selected';}
			?>
			>Select</option>
							<?php
			
						$args   = array(
							'sort_order'   => 'asc',
							'sort_column'  => 'post_title',
							'hierarchical' => 1,
							'post_type'    => 'page',
							'post_status'  => 'publish',
						);
						$sPages = get_pages( $args );
						foreach ( $sPages as $sPage )
						{
							echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( $options['termsPage'] == ( $sPage->ID ) || ( !$options['termsPage'] && $sPage->post_title == 'Terms of Use' ) ? 'selected' : '' ) . '>' . esc_html( $sPage->post_title ) . '</option>' . "\r\n";
						}
			?>
			</select>
			<br>Site Terms of Use page, to include terms in file upload form.
			
			<h4>Success Message to Uploader</h4>
			<textarea name="guestMessage" id="guestMessage" cols="100" rows="3"><?php echo esc_textarea( $options['guestMessage'] ); ?></textarea>
			
			<h4>Initial Upload Status</h4>
			<select name="guestStatus" id="guestStatus">
			<option value='' <?php  echo ( $options['guestStatus'] == '' ? 'selected' : '' ); ?>>Default</option>
			<option value='pending' <?php  echo ( $options['guestStatus'] == 'pending' ? 'selected' : '' ); ?>>Pending</option>
			<option value='publish' <?php  echo ( $options['guestStatus'] == 'publish' ? 'selected' : '' ); ?>>Publish</option>
			<option value='draft' <?php  echo ( $options['guestStatus'] == 'draft' ? 'selected' : '' ); ?>>Draft</option>
			<option value='private' <?php  echo ( $options['guestStatus'] == 'private' ? 'selected' : '' ); ?>>Private</option>
			</select>
			<br>When using Default, upload status will depend on configuration in specific plugin (i.e. VideoShareVOD, Picture Gallery). Default: <?php echo esc_html( ucwords( $optionsDefault['guestStatus'] ) ); ?>


			<h4>Moderator Email</h4>
			<input name="moderatorEmail" type="text" id="moderatorEmail" size="64" maxlength="64" value="<?php echo esc_attr( $options['moderatorEmail'] ); ?>"/>
			<br>An email is sent to admin/moderator to approve picture. Set blank to disable notifications (in example when upload status is set to Publish and no review is required). Default: <?php echo esc_html( $optionsDefault['moderatorEmail'] ); ?>
			
			<h4>Subject for Guest Upload Notification</h4>
			<input name="guestSubject" type="text" id="guestSubject" size="100" maxlength="256" value="<?php echo esc_attr( $options['guestSubject'] ); ?>"/>
			<br> Default: <?php echo esc_html( $optionsDefault['guestSubject'] ); ?>
			
			<h4>Text for Guest Upload Notification Email</h4>
			<textarea name="guestText" id="guestText" cols="100" rows="3"><?php echo esc_textarea( $options['guestText'] ); ?></textarea>
			<br> Default: <?php echo esc_html( $optionsDefault['guestText'] ); ?>
			
								<?php
							break;

		case 'reset':
?>
<h3><?php _e( 'Reset Options', 'ppv-live-webcams' ); ?></h3>
This resets some options to defaults. Useful when upgrading plugin and new defaults are available for new features and for fixing broken installations.
				<?php

			$confirm = ( isset( $_GET['confirm'] ) && '1' === $_GET['confirm'] ) ? true : false;

			if ( $confirm )
			{
				echo '<h4>Resetting...</h4>';
			} else
			{
				echo '<p><A class="button" href="admin.php?page=paid-membership&tab=reset&confirm=1">Yes, Reset These Settings!</A></p>';
			}

			$resetOptions = array( 'customCSS', 'contentCSS', 'downloadCSS' );

			foreach ( $resetOptions as $opt )
			{
				echo '<BR> - ' . esc_html( $opt ) ;
				if ( $confirm && isset( $optionsDefault[ $opt ] ) )
				{
					$options[ $opt ] =  $optionsDefault[ $opt ] ?? '';
				}
			}

			if ( $confirm )
			{
				update_option( 'VWpaidMembershipOptions', $options );
			}

			break;


			case 'integration':
				?>
<h3>Content Integration</h3>
This plugin can generically manage custom posts as <a href="admin.php?page=paid-membership&tab=content">configured</a>. Additionally, can provide advanced integration for some content types implement by specific <a href="plugin-install.php?s=videowhisper&tab=search&type=term">VideoWhisper plugins</a>.

<h4>Video Share VOD</h4>
<a target="_plugin" href="https://videosharevod.com/">VideoShareVOD Plugin</a> enables uploading, converting and managing videos. 
				<?php
				if ( is_plugin_active( 'video-share-vod/video-share-vod.php' ) ) {
					echo 'Detected:  <a href="admin.php?page=video-share">Configure</a> | <a href="https://videosharevod.com/features/quick-start-tutorial/">Tutorial</a>';
				} else {
					echo 'Not detected. Please install and activate <a target="_videosharevod" href="https://wordpress.org/plugins/video-share-vod/">VideoShareVOD Plugin</a> from <a href="plugin-install.php?s=videowhisper&tab=search&type=term">Plugins > Add New</a>!';
				}
				?>

<h4>Webcam Recorder</h4>
				<?php
				if ( is_plugin_active( 'video-posts-webcam-recorder/videoposts.php' ) ) {
					echo 'Detected:  <a href="admin.php?page=recorder">Configure</a>';
				} else {
					echo 'Not detected. Please install and activate <a target="_videosharevod" href="https://wordpress.org/plugins/video-posts-webcam-recorder/">Webcam Recorder Plugin</a> from <a href="plugin-install.php?s=videowhisper&tab=search&type=term">Plugins > Add New</a>!';
				}
				?>
<h4><a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> - Enable Reviews</h4>
				<?php
				if ( is_plugin_active( 'rate-star-review/rate-star-review.php' ) ) {
					echo 'Detected:  <a href="admin.php?page=rate-star-review">Configure</a>';
				} else {
					echo 'Not detected. Please install and activate Rate Star Review by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
				}
				?>
<BR><select name="rateStarReview" id="rateStarReview">
  <option value="0" <?php echo $options['rateStarReview'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['rateStarReview'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Rate Star Review integration. Shows star ratings on listings and review form, reviews on item pages.

<h4>Troubleshooting</h4>
Known conflicts: rtMedia uploader seems to break the file multi uploader. 

				<?php

				break;

			case 'packages':
				$hideSubmit = 1;
				?>
<h3>Token Packages</h3>

Add custom token packages as WooCommerce products. When buying the product (listed in <a href="<?php echo get_permalink( $options['p_videowhisper_my_wallet'] ?? 0 )?>"> My Wallet page</a>, if setup), user gets the custom amount of tokens. This enables custom exchange ratios, volume discounts, other offers that can be setup with WooCommerce. Requires WooCommerce and can be used with any integrated wallet plugin.
<br>When token packages are defined, clients will see token packages on My Wallet page. Options to fund with other methods (like at 1:1 ration with the TeraWallet topup option) should be removed, to avoid confusing users.

				<?php
				// add new tokens package
				if ( isset( $_POST['package_name'] ) ) {
					if ( $_POST['package_name'] !='' ) {
										 $product = array();

							$product['post_title']   = sanitize_text_field( $_POST['package_name'] );
							$product['post_author']  = get_current_user_id();
							$product['post_content'] = sanitize_textarea_field( $_POST['package_description'] );
							$product['post_excerpt'] = wp_trim_excerpt( sanitize_textarea_field( $_POST['package_description'] ) );
							$product['post_type']    = 'product';
							$product['post_status']  = 'publish';

							$product_id = wp_insert_post( $product );

						if ( $product_id ) {
							update_post_meta( $product_id, 'micropayments_tokens', floatval( $_POST['package_tokens'] ) );
							update_post_meta( $product_id, '_price', floatval( $_POST['package_price'] ) );
							update_post_meta( $product_id, '_regular_price', floatval( $_POST['package_price'] ) );

							wp_set_object_terms( $product_id, 'simple', 'product_type' );
							update_post_meta( $product_id, '_visibility', 'visible' );
							update_post_meta( $product_id, '_stock_status', 'instock' );
							update_post_meta( $product_id, '_virtual', 'yes' );
							update_post_meta( $product_id, '_purchase_note', '' );

							update_post_meta( $product_id, '_sku', 'micropayments_tokens' );
							update_post_meta( $product_id, '_product_attributes', array() );
							update_post_meta( $product_id, '_sold_individually', '' );
							update_post_meta( $product_id, '_manage_stock', 'no' );
							update_post_meta( $product_id, '_backorders', 'no' );
						}
					}
				}
				?>
<h3>Add New Token Package</h3>

<h4>Name</h4>
<input name="package_name" type="text" id="package_name" size="64" maxlength="128" value=""/>

<h4>Description (optional)</h4>
<textarea name="package_description" id="package_description" cols="100" rows="2"></textarea>

<h4>Tokens</h4>
<input name="package_tokens" type="text" id="package_tokens" size="6" maxlength="10" value="10"/><?php echo esc_html( $options['currency'] ); ?>

<h4>Price</h4>
<input name="package_price" type="text" id="package_price" size="6" maxlength="10" value="10"/> in <a href="admin.php?page=wc-settings">configured WooCommerce currency</a>

				<?php
				submit_button( __( 'Add Package', 'paid-membership' ) );

				?>

<h3>Packages</h3>
				<?php

				$packages = get_posts(
					array(
						'numberposts' => -1,
						'meta_query'  => array(
							array(
								'key'     => 'micropayments_tokens',
								'compare' => 'EXISTS',
							),
						),
						'post_type'   => 'product',
						'post_status' => 'publish',
					)
				);

				if ( ! $packages || empty( $packages ) ) {
					echo 'No token packages published, yet.';
				} else {
					
					foreach ( $packages as $package ) {
										echo '<a class="secondary button" href="' . get_permalink( intval( $package->ID ) ) . '">' . esc_html( $package->post_title ) . '</a> ';
										echo '<a href="post.php?action=edit&post=' . esc_html( $package->ID ) . '"><span class="dashicons dashicons-edit"></span></a> ';
										echo esc_html( get_post_meta( intval( $package->ID ) , 'micropayments_tokens', true ) ) . esc_html( $options['currency'] );
										echo ' = ' . esc_html( get_post_meta( intval( $package->ID ), '_price', true ) );
										echo '<br>';
					}
				}
				?>
<br>*<span class="dashicons dashicons-edit"></span> When editing a product, enable Screen Options > Custom Fields checkbox to be able to edit "micropayments_tokens" field, where you can change number of tokens that will be allocated.
<br>Token Package products are listed on My Wallet page, if available and WooCommerce is active.
<br>*  As WooCommerce requires processing of orders (to get tokens allocated), use a plugin like <a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a> or <a href="https://wordpress.org/plugins/autocomplete-woocommerce-orders/">Autocomplete WooCommerce Orders</a> (and <a href="admin.php?page=wc-settings&tab=silkwave_aco">enable it</a>) to automatically do Processing to Completed . Or manually process from <a href="edit.php?post_status=wc-processing&post_type=shop_order">Orders</a> by editing and changing Processing to Completed. Tokens are not allocated until order is Completed.
 
<h4>Process Packages</h4>
				<small>
				<?php
				echo wp_kses_post( nl2br( self::packages_process(0, 1) ) );
				?>
				</small>
				<p>How does this work?
				<br>Clients can see available token packages (WooCommerce products) in <a href="<?php echo get_permalink( $options['p_videowhisper_my_wallet'] ?? 0 )?>"> My Wallet page</a>, if available and WooCommerce is active.
				<br>When client buys the product with WooCommerce, client gets directed to gateway website you setup (CCBill/Paypal) and makes a payment to your account on that website. After transaction is processed, the billing website notifies WooCommerce that order was paid. Then MicroPayments allocates the virtual tokens to the client wallet, for the order that was processed, on wallet page or by cron. Tokens are allocated to primary wallet configured in <a href="admin.php?page=paid-membership&tab=billing">billing settings</a>.
				<br>When using with PaidVideochat plugin, primary wallet should be the same in in MicroPayments & PaidVideochat, so tokens added by MicroPayments packages are available to spend in PaidVideochat right away2.
				</p> 
				<?php
				break;

			case 'subscriptions':
				?>
<h3>Author Subscriptions</h3>		
Authors can setup subscriptions and content that can be accessed with these subscriptions. <a href="admin.php?page=paid-membership&tab=pages">Setup Pages</a> to enable: <a href="<?php echo get_permalink( $options['p_videowhisper_provider_subscriptions'] ); ?>">Setup Subscriptions</a>, <a href="<?php echo get_permalink( intval( $options['p_videowhisper_content_seller'] ) ); ?>">Manage Assets</a> for sellers to assign content to tiers.


<h4>Author Subscription Earnings Ratio</h4>
<input name="subscriptionRatio" type="text" id="subscriptionRatio" size="5" maxlength="10" value="<?php echo esc_attr( $options['subscriptionRatio'] ); ?>"/>
<br>Ratio author gets from subscription payments (between 0 and 1.0). Remaining amount is site profit as spent by client and not being given to author.
<br>Example: Set 0.8 if you want author to get 80% from client payment. 


<h4>Maximum Tiers</h4>
<input name="tiersMax" type="text" id="tiersMax" size="5" maxlength="10" value="<?php echo esc_attr( $options['tiersMax'] ); ?>"/>
<br>Maximum subscription tiers an author can setup.

<h4>Subscription Minimum Cost</h4>
<input name="subscriptionMin" type="text" id="subscriptionMin" size="5" maxlength="10" value="<?php echo esc_attr( $options['subscriptionMin'] ); ?>"/><?php echo esc_html( $options['currency'] ); ?>
<br>Minimum configurable cost of subscription. Minimum is 0.

<h4>Subscription Maximum Cost</h4>
<input name="subscriptionMax" type="text" id="subscriptionMax" size="5" maxlength="10" value="<?php echo esc_attr( $options['subscriptionMax'] ); ?>"/><?php echo esc_html( $options['currency'] ); ?>
<br>Maximum configurable cost of subscription. Setting 0 will remove the limit.

<h4>Buyer User Roles</h4>
<input name="rolesBuyer" type="text" id="rolesBuyer" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesBuyer'] ); ?>"/>
<BR>Comma separated roles allowed to buy content, subscriptions. Ex: administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan 
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for all.
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>

<h4>Seller User Roles</h4>
<input name="rolesSeller" type="text" id="rolesSeller" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesSeller'] ); ?>"/>
<BR>Comma separated roles allowed to sell/manage content/subscriptions in frontend. Ex: administrator, editor, author, contributor, performer, creator, studio
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for all.

<h4>BP Profile Tabs for Sellers</h4>
<select name="buddypressProfile" id="buddypressProfile">
  <option value="1" <?php echo $options['buddypressProfile'] == '1' ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['buddypressProfile'] == '0' ? 'selected' : ''; ?>>Disabled</option>
</select>
<br>BuddyPress/BuddyBoss: Content sellers roles get special BP profile sections listing their content and subscriptions.

<h4>Moderator User Roles</h4>
<input name="roleModerators" type="text" id="roleModerators" size="40" maxlength="64" value="<?php echo esc_attr( $options['roleModerators'] ); ?>"/>
<br>Comma separated roles, allowed to access content without paying.

<h4>Externally Managed Post Types</h4>
<input name="postTypesExternal" type="text" id="postTypesExternal" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesExternal'] ); ?>"/>
<BR>Show subscription options and also content as benefits are externally managed. In example for PaidVideochat when you want all users to access but subscribers to get free cost per minute in group chat. Comma separated content/post types. Ex: webcam, room
<br>Applies to subscriptions, not access price. If access price is set and there's no subscription, content is not displayed.


<h4>Process Subscriptions</h4>
Subscriptions are processed by WP cron, daily and each time when accessing this section, with output.
<br>
				<?php

				echo esc_html( self::subscriptions_process() );

				break;

			case 'overview':
				$hideSubmit = 1;

				?>
<h3>Overview</h3>
<strong>MicroPayments - Paid Author Subscriptions, Content, Downloads, Membership</strong>: Sell subscription to author content, membership, content access or downloads with micropayments. Reduce billing fees, processing time and friction with micropayments using virtual wallet credits/tokens. Authors can accept donations, setup donation goals, crowdfunding for each content item. Credits/tokens can be purchased with real money using TeraWallet for WooCommerce or MyCred - multiple billing gateways supported. Control access to content (including custom posts) by site membership, author subscription. Sell digital assets (videos, pictures, documents, custom posts) as WooCommerce products. Upload files for membership/paid downloads from dedicated frontend pages. Micropayments allows users to do low value transactions from site wallet, without using a billing site each time. Increase user spending with easy instant payments, low friction, low billing fees, deposits on site. Leave a review if you find this free plugin idea useful and would like more updates!  <a href='https://wordpress.org/support/plugin/paid-membership/reviews/#new-post'>Review Plugin</a> |  <a href='https://ppvscript.com/micropayments/'>Plugin Homepage</a> | <a href='https://videowhisper.com/tickets_submit.php?topic=paid-membership'>Contact Developers</a>

<br><br>To use these features:
<br><a class="button secondary" href="admin.php?page=paid-membership&tab=pages">Setup MicroPayments Pages</a>
<br><a class="button secondary" href="admin.php?page=paid-membership&tab=billing">Setup Billing Wallets</a>

<p>If you find this plugin idea useful or interesting, <a href="https://profiles.wordpress.org/videowhisper/#content-plugins">leave a review</a> to help us drive more resources into further development and improvements. After posting a review, you can also <a href="https://videowhisper.com/tickets_submit.php">submit a ticket</a> with review link to VideoWhisper support, to claim a gift.</p>

<p>Plugin is free to use and developed, improved by VideoWhisper and customer funding.
VideoWhisper clients also have permission to remove developer notices, attribution. <a href="https://consult.videowhisper.com">Ask How</a></p>


<h3>Turnkey Feature Plugins</h3>
<ul>
	<LI><a href="https://paidvideochat.com">Paid Videochat</a> Run a turnkey pay per minute videochat site where performers can archive live shows or upload videos for their fans.</LI>
	<LI><a href="https://broadcastlivevideo.com">Broadcast Live Video</a> Broadcast live video channels from webcam, IP cameras, desktop/mobile encoder apps. Archive these videos, import and publish on site.</LI>
	<LI><a href="https://wordpress.org/plugins/video-share-vod/">Video Share VOD</a> Video sharing site.</LI>
<LI><a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> Picture sharing site.</LI>

	 <li><a href="https://woocommerce.com/?aff=18336&amp;cid=2828082">WooCommerce</a> : <em>ecommerce</em> platform</li>
	 <li><a href="https://buddypress.org/">BuddyPress</a> : <em>community</em> (member profiles, activity streams, user groups, messaging)</li>
	 <li><a href="https://www.buddyboss.com/platform/">BuddyBoss</a> : <em>BuddyPress fork</em>, available as free platform and premium Pro</li> 	
	 <li><a href="https://woocommerce.com/products/sensei/?aff=18336&amp;cid=2828082">Sensei LMS</a> : <em>learning</em> management system</li>
	 <li><a href="https://bbpress.org/">bbPress</a>: clean discussion <em>forums</em></li>
</ul>

<h3>Feature Integration Plugins (Recommended)</h3>
<UL>
<li><a href="https://wordpress.org/plugins/rate-star-review/" title="Rate Star Review - AJAX Reviews for Content with Star Ratings">Rate Star Review – AJAX Reviews for Content with Star Ratings</a> plugin, integrated for content reviews and ratings.</li>
<li><a href="https://wordpress.org/plugins/mycred/">myCRED</a> and/or <a href="https://wordpress.org/plugins/woo-wallet/">WooCommerce TeraWallet</a>, integrated for tips.  Configure as described in Tips settings tab.</li>
</UL>

<h3>Premium Plugins / Addons</h3>
<ul>
	<LI><a href="http://themeforest.net/popular_item/by_category?category=wordpress&ref=videowhisper">Premium Themes</a> Professional WordPress themes.</LI>
	<LI><a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&amp;cid=2828082">WooCommerce Memberships</a> Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.</LI>

	<LI><a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&amp;cid=2828082">WooCommerce Subscriptions</a> Setup subscription products, content. Leverages Membership plugin to setup membership subscriptions.</LI>

<li><a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&amp;cid=2828082">WooCommerce Bookings</a> Setup booking products with calendar, <a href="https://woocommerce.com/products/bookings-availability/?aff=18336&amp;cid=2828082">availability</a>, <a href="https://woocommerce.com/products/woocommerce-deposits/?aff=18336&amp;cid=2828082">booking deposits</a>, confirmations for 1 on 1 or group bookings. Include performer room link.</li>

	<LI><a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&amp;cid=2828082">WooCommerce Follow Up</a> Follow Up by emails and twitter automatically, drip campaigns.</LI>

		<LI><a href="https://woocommerce.com/products/product-vendors/?aff=18336&amp;cid=2828082">WooCommerce Product Vendors</a> Allow multiple vendors to sell via your site and in return take a commission on sales. Leverage with <a href="https://woocommerce.com/products/woocommerce-product-reviews-pro/?aff=18336&amp;cid=2828082">Product Reviews Pro</a>.</LI>


	<LI><a href="https://updraftplus.com/?afref=924">Updraft Plus</a> Automated WordPress backup plugin. Free for local storage. For production sites external backups are recommended (premium).</LI>
</ul>

<h3>VideoWhisper</h3>
Contact for consultation, clarifications, suggestions.
<br>

<a class="button" href='https://videowhisper.com/tickets_submit.php?topic=MicroPayments+WordPress+Plugin'>Contact Technical Support</a>
				<?php

				break;

			case 'import':
				?>
<h3><?php _e( 'Import Options', 'paid-membership' ); ?></h3>
Import/Export plugin settings and options.
				<?php
				if ( $importConfig = sanitize_textarea_field( $_POST['importConfig'] ?? '' ) ) {
					echo '<br>Importing: ';
					$optionsImport = parse_ini_string( stripslashes( $importConfig ), false );

					foreach ( $optionsImport as $key => $value ) {
						echo '<br> - ' . esc_html( $key ) . '  =  ' . esc_html( $value );
						$options[ $key ] = sanitize_text_field( $value );
					}
					update_option( 'VWpaidMembershipOptions', $options );
				}
				?>
<h4>Import Plugin Settings</h4>
<textarea name="importConfig" id="importConfig" cols="120" rows="12"></textarea>
<br>Quick fill settings as option = "value".

<h4>Export Current Plugin Settings</h4>
<textarea readonly cols="120" rows="12">[Plugin Settings]
				<?php
				foreach ( $options as $key => $value ) {
					if ( ! strstr( $key, 'videowhisper' ) ) {
						if ( ! is_array( $value ) ) {
							echo "\n" . esc_html( $key ) . ' = ' . '"' . esc_html( htmlentities( stripslashes( $value ) ) ) . '"';
						} else {
							echo "\n" . esc_html( $key ) . ' = ' . '"' . esc_html( htmlentities( stripslashes( serialize( $value ) ) ) );
						}
					}
				}
				?>
			</textarea>

<h4>Export Default Plugin Settings</h4>
<textarea readonly cols="120" rows="10">[Plugin Settings]
				<?php
				foreach ( $optionsDefault as $key => $value ) {
					if ( ! strstr( $key, 'videowhisper' ) ) {
								echo esc_html( "\n$key = " . '"' . htmlentities( stripslashes( is_array($value) ? serialize($value) : $value  ) ) . '"' );
					}
				}
				?>
			</textarea>

<h5>Warning: Saving will set settings provided in Import Plugin Settings box.</h5>
				<?php
				break;

			case 'pages':
				?>
<h3><?php _e( 'Setup Pages', 'paid-membership' ); ?></h3>
				<?php
				if ( isset($_POST['submit']) ) {
					echo '<p>Saving pages setup.</p>';
					self::setupPages();
				}

				submit_button( __( 'Update Pages', 'live-streaming' ) );
				?>

<h4>Setup Pages</h4>
<select name="disableSetupPages" id="disableSetupPages">
  <option value="0" <?php echo $options['disableSetupPages'] ? '' : 'selected'; ?>>Yes</option>
  <option value="1" <?php echo $options['disableSetupPages'] ? 'selected' : ''; ?>>No</option>
</select>
<br>Create pages for main functionality. Also creates a menu with these pages (VideoWhisper) that can be added to themes. If you delete the pages this option recreates these if not disabled.
<br>If you also use WooCommerce you could <a href="https://woocommerce.com/document/customize-my-account-for-woocommerce/">add some of these pages in the My Account menu</a>, depending on your project.

<h3>Feature Pages</h3>
				<?php

				$pages = self::setupPagesList();

				// get all pages
				$args   = array(
					'sort_order'   => 'asc',
					'sort_column'  => 'post_title',
					'hierarchical' => 1,
					'post_type'    => 'page',
				);
				$sPages = get_pages( $args );

				foreach ( $pages as $shortcode => $title ) {
					echo '<h4>' . esc_html( $title ) . '</h4>';
					$pid = intval( $options[ 'p_' . $shortcode ] ?? 0 );
					if ( $pid != '' ) {
						echo '<select name="p_' . esc_attr( $shortcode ) . '" id="p_' . esc_attr( $shortcode ) . '">';
						echo '<option value="0">Undefined: Reset</option>';
						foreach ( $sPages as $sPage ) {
							echo '<option value="' . esc_attr( $sPage->ID ) . '" ' . ( ( $pid == $sPage->ID ) ? 'selected' : '' ) . '>' . esc_attr( $sPage->ID ) . '. ' . esc_html( $sPage->post_title ) . ' - ' . esc_html( $sPage->post_status ) . '</option>' . "\r\n";
						}
						echo '</select><br>';
						if ( $pid ) {
							echo '<a href="' . get_permalink( $pid ) . '">view</a> | ';
						}
						if ( $pid ) {
							echo '<a href="post.php?post=' . esc_attr( $pid ) . '&action=edit">edit</a> | ';
						}
					} else echo 'Not configured.';

					echo ' Default content: [' . esc_html( $shortcode ) . ']';
				}

				echo '<h3>MicroPayments Frontend Feature Pages</h3>';

				$noMenu = array( 'videowhisper_content_edit', 'videowhisper_client_subscribe' );

				foreach ( $pages as $shortcode => $title ) {
					if ( ! in_array( $shortcode, $noMenu ) ) {
							$pid = sanitize_text_field( $options[ 'p_' . $shortcode ] ?? 0 );
						if ( $pid ) {
							$url = get_permalink( $pid );
							echo '<p> - ' . esc_html( $title ) . ':<br>';
							echo '<a href="' . esc_attr( $url ) . '">' . esc_html( $url ) . '</a></p>';
						}
					}
				}


				echo '<h4>WP Rewrite Rules (Troubleshooting)</h4>';
				
				//$rules = get_option( 'rewrite_rules' );
				echo '$wp_rewrite->rules:<br><textarea cols="100" rows="5" readonly>';
		
				global $wp_rewrite;			
				if (!$wp_rewrite->rules) {
					$wp_rewrite->flush_rules( true );
					}
					var_dump($wp_rewrite->rules);
					echo '</textarea><br>';
				break;

			case 'appearance':
				?>

<h4>Theme Mode (Dark/Light/Auto)</h4> 
<select name="themeMode" id="themeMode">
  <option value="" <?php echo $options['themeMode'] ? '' : 'selected'; ?>>None</option>
  <option value="light" <?php echo $options['themeMode'] == 'light' ? 'selected' : ''; ?>>Light Mode</option>
  <option value="dark" <?php echo $options['themeMode'] == 'dark' ? 'selected' : ''; ?>>Dark Mode</option>
  <option value="auto" <?php echo $options['themeMode'] == 'auto' ? 'selected' : ''; ?>>Auto Mode</option>
</select>
<br>This will use JS to apply ".inverted" class to Fomantic ".ui" elements mainly on AJAX listings. When using the <a href="https://fanspaysite.com/theme">FansPaysSite theme</a> this will be discarded and the dynamic theme mode will be used.

<h4>Interface Class(es)</h4>
<input name="interfaceClass" type="text" id="interfaceClass" size="30" maxlength="128" value="<?php echo esc_attr( $options['interfaceClass'] ); ?>"/>
<br>Extra class to apply to interface (using Semantic UI). You can use a static "inverted" class when theme uses a dark mode (a dark background with white text) or for contrast, if not using theme Mode. Ex: inverted
<br>Some common Semantic UI classes: inverted = dark mode or contrast, basic = no formatting, secondary/tertiary = greys, red/orange/yellow/olive/green/teal/blue/violet/purple/pink/brown/grey/black = colors . Multiple classes can be combined, divided by space. Ex: inverted, basic pink, secondary green, secondary


<h4>Hide Post Thumbnails</h4>
<select name="hidePostThumbnail" id="hidePostThumbnail">
  <option value="0" <?php echo ( ! $options['hidePostThumbnail'] ? 'selected' : '' ); ?>>Default</option>
  <option value="1" <?php echo ( $options['hidePostThumbnail'] ? 'selected' : '' ); ?>>Hide Thumbnail</option>
</select>
<br>Enable when theme adds post thumbnail to post page and you don't want that.
<?php 
	/*
	
<h4>Plupload jQuery UI Theme</h4>

<select name="jquery_theme" id="jquery_theme">
				<?php
				$themes = array( 'base', 'black-tie', 'blitzer', 'cupertino', 'dark-hive', 'dot-luv', 'eggplant', 'excite-bike', 'flick', 'hot-sneaks', 'humanity', 'le-frog', 'mint-choc', 'overcast', 'pepper-grinder', 'redmond', 'smoothness', 'south-street', 'start', 'sunny', 'swanky-purse', 'trontastic', 'ui-darkness', 'ui-lightness', 'vader' );

				foreach ( $themes as $theme ) {
					echo '<option value="' . esc_attr( $theme ) . '"' . ( $options['jquery_theme'] == $theme ? 'selected' : '' ) . '>' . esc_html( $theme ) . '</option>';
				}

				?>
</select>
<br>To preview default jQuery UI themes, see Gallery in <a href="https://jqueryui.com/themeroller/">jQuery UI ThemeRoller</a>.				
*/
				break;

			case 'downloads':
				$options['custom_post']     = preg_replace( '/[^\da-z]/i', '', strtolower( $options['custom_post'] ) );
				$options['custom_taxonomy'] = preg_replace( '/[^\da-z]/i', '', strtolower( $options['custom_taxonomy'] ) );

				?>
<h3><?php _e( 'Downloads', 'paid-membership' ); ?></h3>
Enable downloads: uploading content (files) that can be accessed by membership or sold. 
Downloads feature enables upload and management of additional file types, not handled by dedicated plugins (like VideoShareVOD videos, Picture Gallery images).

<h4>Downloads</h4>
<select name="downloads" id="downloads">
  <option value="0" <?php echo ( ! $options['downloads'] ? 'selected' : '' ); ?>>Disabled</option>
  <option value="1" <?php echo ( $options['downloads'] ? 'selected' : '' ); ?>>Enabled</option>
</select>

<h4>Extensions Allowed</h4>
<textarea name="download_extensions" id="download_extensions" cols="100" rows="3"><?php echo esc_textarea( $options['download_extensions'] ); ?></textarea>
<br>Warning: Depending on server configuration, allowing frontend users to upload files can result in security risks. Do not allow uploading script extensions like PHP that could be used to compromise your account or HTML pages that could be used to conduct malicious activity using your domain.
<br>Enabling VideoShareVOD & Picture Gallery plugin automatically adds video & picture extensions and those file types are handled/managed by those plugins after upload. Do NOT add video/picture extensions in this list again.
<br>Default: <?php echo esc_html( $optionsDefault['download_extensions'] ); ?>

<h4>Download Post Name</h4>
<input name="custom_post" type="text" id="custom_post" size="12" maxlength="32" value="<?php echo esc_attr( $options['custom_post'] ); ?>"/>
<br>Custom post name for downloads (only alphanumeric, lower case). Will be used for download urls. Ex: download
<br><a href="options-permalink.php">Save permalinks</a> to activate new url scheme.
<br>Warning: Changing post type name at runtime will hide previously added items. Previous posts will only show when their post type name is restored.

<h4>Download Post Taxonomy Name</h4>
<input name="custom_taxonomy" type="text" id="custom_taxonomy" size="12" maxlength="32" value="<?php echo esc_attr( $options['custom_taxonomy'] ); ?>"/>
<br>Special taxonomy for organising downloads. Ex: collection


<h4>Download Post Template Filename</h4>
<input name="postTemplate" type="text" id="postTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['postTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render webcam post page. Ex: page.php, single.php
				<?php
				if ( $options['postTemplate'] != '+plugin' ) {
					$single_template = get_template_directory() . '/' . sanitize_text_field( $options['postTemplate'] );
					echo '<br>' . esc_html( $single_template ) . ' : ';
					if ( file_exists( $single_template ) ) {
						echo 'Found.';
					} else {
						echo 'Not Found! Use another theme file!';
					}
				}
				?>
<br>Set "+plugin" to use a template provided by this plugin, instead of theme templates.


<h4>Collection Template Filename</h4>
<input name="taxonomyTemplate" type="text" id="taxonomyTemplate" size="20" maxlength="64" value="<?php echo esc_attr( $options['taxonomyTemplate'] ); ?>"/>
<br>Template file located in current theme folder, that should be used to render collection post page. Ex: page.php, single.php
				<?php
				if ( $options['postTemplate'] != '+plugin' ) {
					$single_template = get_template_directory() . '/' . sanitize_text_field( $options['taxonomyTemplate'] );
					echo '<br>' . esc_html( $single_template ) . ' : ';
					if ( file_exists( $single_template ) ) {
						echo 'Found.';
					} else {
						echo 'Not Found! Use another theme file!';
					}
				}

				$current_user = wp_get_current_user();
				?>
<br>Set "+plugin" to use a template provided by this plugin, instead of theme templates.

<h4>Username</h4>
<select name="userName" id="userName">
  <option value="display_name" <?php echo $options['userName'] == 'display_name' ? 'selected' : ''; ?>>Display Name (<?php echo esc_html( $current_user->display_name ); ?>)</option>
  <option value="user_login" <?php echo $options['userName'] == 'user_login' ? 'selected' : ''; ?>>Login (<?php echo esc_html( $current_user->user_login ); ?>)</option>
  <option value="user_nicename" <?php echo $options['userName'] == 'user_nicename' ? 'selected' : ''; ?>>Nicename (<?php echo esc_html( $current_user->user_nicename ); ?>)</option>
  <option value="ID" <?php echo $options['userName'] == 'ID' ? 'selected' : ''; ?>>ID (<?php echo esc_html( $current_user->ID ); ?>)</option>
</select>
<br>Used for default user collection. Your username with current settings:
				<?php
				$userName = sanitize_text_field( $options['userName'] );
				if ( ! $userName ) {
					$userName = 'user_nicename';
				}
				echo esc_html( $username = $current_user->$userName );
				?>

<h4><?php _e( 'Uploads Path', 'picture-gallery' ); ?></h4>
<p><?php _e( 'Path where video files will be stored. Make sure you use a location outside plugin folder to avoid losing files on updates and plugin uninstallation.', 'paid-membership' ); ?></p>
<input name="uploadsPath" type="text" id="uploadsPath" size="80" maxlength="256" value="<?php echo esc_attr( $options['uploadsPath'] ); ?>"/>
<br>Ex: /home/-your-account-/public_html/wp-content/uploads/vw_downloads
<br>If you ever decide to change this, previous files must remain in old location.

<h3><?php _e( 'Plugin Integrations', 'paid-membership' ); ?></h3>
<h4><a target="_plugin" href="https://wordpress.org/plugins/rate-star-review/">Rate Star Review</a> - Enable Reviews</h4>
				<?php
				if ( is_plugin_active( 'rate-star-review/rate-star-review.php' ) ) {
					echo 'Detected:  <a href="admin.php?page=rate-star-review">Configure</a>';
				} else {
					echo 'Not detected. Please install and activate Rate Star Review by VideoWhisper.com from <a href="plugin-install.php">Plugins > Add New</a>!';
				}
				?>
<BR><select name="rateStarReview" id="rateStarReview">
  <option value="0" <?php echo $options['rateStarReview'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['rateStarReview'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Enables Rate Star Review integration. Shows star ratings on listings and review form, reviews on item pages.


<h4><?php _e( 'Show VideoWhisper Powered by', 'paid-membership' ); ?></h4>
<select name="videowhisper" id="videowhisper">
  <option value="0" <?php echo $options['videowhisper'] ? '' : 'selected'; ?>>No</option>
  <option value="1" <?php echo $options['videowhisper'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>
				<?php
				_e(
					'Show a mention that items were posted with VideoWhisper plugin.
',
					'paid-membership'
				);
				?>
				<?php

				break;

			case 'share':
				// ! share options
				?>
<h3><?php _e( 'Download Sharing', 'paid-membership' ); ?></h3>

<h4><?php _e( 'Users allowed to upload and share files', 'paid-membership' ); ?></h4>
<textarea name="shareList" cols="64" rows="2" id="shareList"><?php echo esc_textarea( $options['shareList'] ); ?></textarea>
<BR><?php _e( 'Who can share downloads: comma separated Roles, user Emails, user ID numbers.', 'paid-membership' ); ?>

<h4><?php _e( 'Users allowed to directly publish downloads', 'paid-membership' ); ?></h4>
<textarea name="publishList" cols="64" rows="2" id="publishList"><?php echo esc_textarea( $options['publishList'] ); ?></textarea>
<BR><?php _e( 'Users not in this list will add downloads as "pending".', 'paid-membership' ); ?>
<BR><?php _e( 'Who can publish items: comma separated Roles, user Emails, user ID numbers.', 'paid-membership' ); ?>


<h4><?php _e( 'Default Downloads Per Page', 'paid-membership' ); ?></h4>
<input name="perPage" type="text" id="perPage" size="3" maxlength="3" value="<?php echo esc_attr( $options['perPage'] ); ?>"/>


<h4><?php _e( 'Thumbnail Width', 'paid-membership' ); ?></h4>
<input name="thumbWidth" type="text" id="thumbWidth" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbWidth'] ); ?>"/>

<h4><?php _e( 'Thumbnail Height', 'paid-membership' ); ?></h4>
<input name="thumbHeight" type="text" id="thumbHeight" size="4" maxlength="4" value="<?php echo esc_attr( $options['thumbHeight'] ); ?>"/>


<h4>Downloads Listings CSS</h4>
				<?php
				$options['downloadsCSS'] = htmlentities( stripslashes( $options['downloadsCSS'] ) );

				?>
<textarea name="downloadsCSS" id="downloadsCSS" cols="100" rows="8"><?php echo esc_textarea( $options['downloadsCSS'] ); ?></textarea>
<br>Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['downloadsCSS'] ); ?></textarea>

				<?php
				break;

			case 'access':
				// ! vod options
				$options['accessDenied'] = stripslashes( $options['accessDenied'] ) ;

				?>
<h3>Membership / Content On Demand</h3>

<h4>Members allowed to access download</h4>
<textarea name="watchList" cols="64" rows="3" id="watchList"><?php echo esc_textarea( $options['watchList'] ); ?></textarea>
<BR>Global download access list: comma separated Roles, user Emails, user ID numbers. Ex: <i>Subscriber, Author, submit.ticket@videowhisper.com, 1</i>
<BR>"Guest" will allow everybody including guests (unregistered users) to access downloads.

<h4>Role collections</h4>
Enables access by role collections: Assign download to a collection that is a role name.
<br><select name="role_collection" id="role_collection">
  <option value="1" <?php echo $options['role_collection'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['role_collection'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Multiple roles can be assigned to same download. User can have any of the assigned roles, to watch. If user has required role, access is granted even if not in global access list.
<br>Downloads without role collections are accessible as per global download access.

<h4>Exceptions</h4>
Assign downloads to these collections:
<br><b>free</b> : Anybody can watch, including guests.
<br><b>registered</b> : All members can watch.
<br><b>unpublished</b> : Download is not accessible.

<h4>Access denied message</h4>
<textarea name="accessDenied" cols="64" rows="3" id="accessDenied"><?php echo wp_kses_post( $options['accessDenied'] ); ?>
</textarea>
<BR>HTML info, shows with preview if user does not have access to access download.
<br>Including #info# will mention rule that was applied.

<h4>Frontend Contend Edit</h4>
<select name="editContent" id="editContent">
  <option value="0" <?php echo $options['editContent'] ? '' : 'selected'; ?>>No</option>
  <option value="all" <?php echo $options['editContent'] ? 'selected' : ''; ?>>Yes</option>
</select>
<br>Allow owner and admin to edit content options for videos, from frontend. This will show an edit button on listings that can be edited by current user.


				<?php
				break;

			break;

			case 'settings':
				$options['loginMessage'] =  stripslashes( $options['loginMessage'] ) ;
				$options['visitorMessage'] = stripslashes( $options['visitorMessage'] ) ;
				$options['roleMessage']    = stripslashes( $options['roleMessage'] ) ;

				?>
<h3>Membership Settings</h3>
Configure general membership settings.

<h4>Login Message</h4>
<textarea name="loginMessage" id="loginMessage" cols="100" rows="3"><?php echo wp_kses_post( $options['loginMessage'] ); ?></textarea>
<br>Message for site visitors when trying to access membership upgrade without login.

<h4>Free Role</h4>
<input name="freeRole" type="text" id="freeRole" size="16" maxlength="64" value="<?php echo esc_attr( $options['freeRole'] ); ?>"/>
<BR>Role when membership expires. Ex: subscriber

<h4>Membership Listings CSS</h4>
				<?php
				$options['customCSS'] = htmlentities( stripslashes( $options['customCSS'] ) );

				?>
<textarea name="customCSS" id="customCSS" cols="100" rows="8"><?php echo esc_textarea( $options['customCSS'] ); ?></textarea>
<br>Default:<br><textarea readonly cols="100" rows="3"><?php echo esc_textarea( $optionsDefault['customCSS'] ); ?></textarea>

				<?php
				break;

			case 'content-membership';
				?>
<h3>Content Access by Membership</h3>
Configure content access by membership.

<h4>Content Types for Role Access</h4>
<input name="postTypesRoles" type="text" id="postTypesRoles" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesRoles'] ); ?>"/>
<BR>Comma separated content/post types. Ex: page, post, video, picture, download
<BR>A special metabox will show up when editing these content types from backend, to configure access by membership.

<h4>Visitor Message</h4>
<textarea name="visitorMessage" id="visitorMessage" cols="100" rows="3"><?php echo wp_kses_post( $options['visitorMessage'] ); ?></textarea>
<br>Message for site visitors when trying to access content that requires login.

<h4>Role Message</h4>
<textarea name="roleMessage" id="roleMessage" cols="100" rows="3"><?php echo wp_kses_post( $options['roleMessage'] ); ?></textarea>
<br>Message for site users when trying to access content that requires specific membership.


				<?php
				break;

			case 'membership':
				$memberships = $options['memberships'];
				if ( ! is_array( $memberships ) )$memberships = array();						

				if ( isset($_POST['importMemberships']) && !isset($_POST['label_new']) ) 
				if ($_POST['importMemberships']) {
					echo 'Importing Memberships... Save if everything shows fine.';
					$memberships = unserialize( stripslashes( sanitize_textarea_field( $_POST['importMemberships'] ) ) );
				}

				if ( isset($_POST['label_new']) ) {
					$i = count( $memberships );

					foreach ( array( 'label', 'role', 'price', 'expire', 'recurring' ) as $varName ) {
						if ( isset( $_POST[ $varName . '_new' ] ) ) {
							$memberships[ $i ][ $varName ] = sanitize_text_field( $_POST[ $varName . '_new' ] );
						}
					}
				}

				if ( isset($_GET['add']) ) {
					$i = '_new';

					?>

					 <h3>Add New Membership #<?php echo (count($memberships) + 1)?></h3>
										 Label <input name="label<?php echo esc_attr( $i ); ?>" type="text" id="label<?php echo esc_attr( $i ); ?>" size="16" maxlength="64" value="Membership"/>
					 <BR>Role <input name="role<?php echo esc_attr( $i ); ?>" type="text" id="role<?php echo esc_attr( $i ); ?>" size="16" maxlength="64" value="Author"/>
					 <BR>Price <input name="price<?php echo esc_attr( $i ); ?>" type="text" id="price<?php echo esc_attr( $i ); ?>" size="4" maxlength="10" value="5"/>
					 <BR>Expire <input name="expire<?php echo esc_attr( $i ); ?>" type="text" id="expire<?php echo esc_attr( $i ); ?>" size="4" maxlength="10" value="30"/> days
					 <BR>Recurring <select name="recurring<?php echo esc_attr( $i ); ?>" id="recurring<?php echo esc_attr( $i ); ?>">
					  <option value="1" selected>Yes</option>
					  <option value="0" >No</option>
					</select>
					<br> This new membership will be added after current <?php echo count($memberships) ?> memberships. 
					<hr>
					<?php
				} else {
					?>
<br><a class="button" href="admin.php?page=paid-membership&tab=membership&add=1">Add New Membership</a>

					 <h3>Current Memberships</h3>
					<?php

					if ( ! count( $memberships ) ) {
						echo 'No memberships defined!';
					} else {
						foreach ( $memberships as $i => $membership ) {
							if ( isset( $_POST[ 'delete' . $i ] ) ) {
								unset( $memberships[ $i ] );
							} else {

								foreach ( array( 'label', 'role', 'price', 'expire', 'recurring' ) as $varName ) {
									if ( isset( $_POST[ $varName . $i ] ) ) {
										$memberships[ $i ][ $varName ] = sanitize_text_field( $_POST[ $varName . $i ] );
									}
								}

								?>
					 <h4>Membership # <?php echo intval( $i + 1 ); ?> </h4>
					 Label <input name="label<?php echo esc_attr( $i ); ?>" type="text" id="label<?php echo esc_attr( $i ); ?>" size="16" maxlength="64" value="<?php echo esc_attr( $memberships[ $i ]['label'] ); ?>"/>
					 <BR>Role <input name="role<?php echo esc_attr( $i ); ?>" type="text" id="role<?php echo esc_attr( $i ); ?>" size="16" maxlength="64" value="<?php echo esc_attr( $memberships[ $i ]['role'] ); ?>"/>
					 <BR>Price <input name="price<?php echo esc_attr( $i ); ?>" type="text" id="price<?php echo esc_attr( $i ); ?>" size="4" maxlength="10" value="<?php echo esc_attr( $memberships[ $i ]['price'] ); ?>"/>
					 <BR>Expire <input name="expire<?php echo esc_attr( $i ); ?>" type="text" id="expire<?php echo esc_attr( $i ); ?>" size="4" maxlength="10" value="<?php echo esc_attr( $memberships[ $i ]['expire'] ); ?>"/> days
					 <BR>Recurring <select name="recurring<?php echo esc_attr( $i ); ?>" id="recurring<?php echo esc_attr( $i ); ?>">
  <option value="1" <?php echo $memberships[ $i ]['recurring'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $memberships[ $i ]['recurring'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
					<BR>Delete <input name="delete<?php echo esc_attr( $i ); ?>" type="checkbox" id="delete<?php echo esc_attr( $i ); ?>" />
					<hr>
								<?php
							}
						}
					}

						$options['memberships'] = $memberships;
					update_option( 'VWpaidMembershipOptions', $options );
				}

				?>

				<p>
				Recurring: Auto renew membership when it expires (if credits are available).
				<br>Label: Label to show to users.
				</p>

				<?php submit_button(); ?>

				<H4>Current Memberships Data (Export)</H4>
				<textarea readonly cols="120" rows="3"><?php echo esc_html( htmlspecialchars( serialize( $memberships ) ) ); ?></textarea>

				<H4>Default Memberships Data</H4>
				<textarea readonly cols="120" rows="3"><?php echo esc_html( htmlspecialchars( serialize( $optionsDefault['memberships'] ) ) ); ?></textarea>
				<?php

				// echo '<br>m: ';
				// var_dump($optionsDefault['memberships']);

				?>
				;

				<H4>Import Memberships Data</H4>
				<textarea cols="120" name="importMemberships" id="importMemberships" rows="4"></textarea>
				<br>If everything shows fine after import, Save Changes to apply.

				<?php
				break;

			case 'billing':
				?>
<h3>Billing Settings</h3>
Payments (real money) go into accounts configured by site owner, setup with billing gateways (like Paypal, Zombaio, Stripe).
<BR>Documentation:  <a target="_read" href="https://paidvideochat.com/features/pay-per-view-ppv/#billing">billing features and gateways</a>, <a target="_read" href="https://paidvideochat.com/features/quick-setup-tutorial/#ppv">billing setup</a>.


<h4>Active Wallet</h4>
<select name="wallet" id="wallet">
  <option value="MicroPayments" <?php echo $options['wallet'] == 'MicroPayments' ? 'selected' : ''; ?>>MicroPayments (internal)</option>
  <option value="MyCred" <?php echo $options['wallet'] == 'MyCred' ? 'selected' : ''; ?>>MyCred</option>
  <option value="WooWallet" <?php echo $options['wallet'] == 'WooWallet' ? 'selected' : ''; ?>>TeraWallet (WooCommerce)</option>
</select>

<h4>Secondary Wallet</h4>
<select name="wallet2" id="wallet2">
  <option value="" <?php echo !$options['wallet2'] ? 'selected' : ''; ?>>None</option>
  <option value="MicroPayments" <?php echo $options['wallet2'] == 'MicroPayments' ? 'selected' : ''; ?>>MicroPayments (internal)</option>
  <option value="MyCred" <?php echo $options['wallet2'] == 'MyCred' ? 'selected' : ''; ?>>MyCred</option>
  <option value="WooWallet" <?php echo $options['wallet2'] == 'WooWallet' ? 'selected' : ''; ?>>TeraWallet (WooCommerce)</option>
</select>
<br>Some setups may require features from 2 different wallets.

<h4>Multi Wallet</h4>
<select name="walletMulti" id="walletMulti">
  <option value="0" <?php echo $options['walletMulti'] == '0' ? 'selected' : ''; ?>>Disabled</option>
  <option value="1" <?php echo $options['walletMulti'] == '1' ? 'selected' : ''; ?>>Show</option>
  <option value="2" <?php echo $options['walletMulti'] == '2' ? 'selected' : ''; ?>>Manual</option>
  <option value="3" <?php echo $options['walletMulti'] == '3' ? 'selected' : ''; ?>>Auto</option>
</select>
<BR>Show will display balances for available wallets, manual will allow transferring to active/secondary wallet (one way), auto will automatically transfer all to active wallet, unless in secondary wallet. Tokens are transferred at 1:1 rate.
<br>Multiple wallets can be used to quickly add extra integrations/features, like bonus for referral or product review available in TeraWallet.
<br>Multiple token types can be confusing to users unless each has specific usage. Suggested usage: MicroPayments (internal) for site transactions, with tokens at custom exchange ratios or offers with WooCommerce token packages.  Optionally use TeraWallet for tokens at 1:1 in WooCommerce currency you decide to use on site. 

<h4>Credits/Tokens Currency Label</h4>
<input name="currency" type="text" id="currency" size="8" maxlength="12" value="<?php echo esc_attr( $options['currency'] ); ?>"/>

				<?php

				submit_button();
				?>
<h3>MicroPayments - VideoWhisper (internal wallet)</h3>
Your balance: <?php
	echo self::micropayments_balance() . esc_html( $options['currency'] );
?>
<BR>Internal MicroPayments (VideoWhisper) wallet does not rely on other plugins for internal transactions. WooCommerce <a href="admin.php?page=paid-membership&tab=packages">product token packages</a> can be used for integrating billing gateways.

<h3>TeraWallet - WooCommerce Wallet (WooWallet)</h3>
				<?php
				if ( is_plugin_active( 'woo-wallet/woo-wallet.php' ) ) {
					echo 'TeraWallet (WooWallet) Plugin Detected';

					if ( $GLOBALS['woo_wallet'] ) {
						$wooWallet = $GLOBALS['woo_wallet'];
						$userID    = get_current_user_id();

						if ( $wooWallet->wallet ) {
							echo '<br>Testing balance: You have: ' . wp_kses_post( $wooWallet->wallet->get_wallet_balance( $userID ) );
						} else {
							echo '<br>Wallet not available, yet. Configure?';
						}

						?>
	<ul>
		<li><a class="secondary button" href="admin.php?page=woo-wallet">User Credits History & Adjust</a></li>
		<li><a class="secondary button" href="users.php">User List with Balance</a></li>
	</ul>
						<?php

					} else {
						echo 'Error: woo_wallet not found!';
					}
				} else {
					echo 'Not detected. Please install and activate <a target="_plugin" href="https://wordpress.org/plugins/woo-wallet/">WooCommerce Wallet</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
				}

				?>
WooCommerce Wallet plugin is based on WooCommerce plugin and allows customers to store their money in a digital wallet. The customers can add money to their wallet using various payment methods set by the admin, available in WooCommerce. The customers can also use the wallet money for purchasing products from the WooCommerce store.
<br> + Configure WooCommerce payment gateways from <a target="_gateways" href="admin.php?page=wc-settings&tab=checkout">WooCommerce > Settings, Payments tab</a>.
<br>
                         * WooCommerce Gateways: PayPal Standard/Checkout, Stripe Card/SEPA/Bancontact/SOFORT/Giropay/EPS/iDeal/Przelewy24-P24/Alipay/Multibanco, CCBill (WP plugin) .
                        <br>
                         * 
                        <a href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/">WooCommerce Free Gateway Extensions</a>
                        : Square, Amazon Pay, PayFast, Venmo, eWay, Klarna, Sofort, Bambora, Bolt, Paypal Venmo/Checkout/Credit.
                        <br>
                         * 
                        <a href="https://woocommerce.com/product-category/woocommerce-extensions/payment-gateways/">WooCommerce Premium Gateway Extensions</a>
                        : Authorize.Net (adult), FirstData, SagePay, WorldPay, Intuit, Elavon, Moneris, USA ePay, Payson, GoCardless, Paytrace, Ogone, NAB Transact, Payment Express, Pin Payments, Alipay, SnapScan, Paytrail, Affirm, Cybersource, Chase Paymentech, RedSys, PayWay, iPay88, PaySafe, MyGate, PayPoint, PayU, PsiGate, TrustCommerce, Merchant Warrior, e-Path, ePay, CardStream, everiPay, PencePay.
                         <br>
                         - For adult related sites, try the 
                        <a href="https://wordpress.org/plugins/woocommerce-payment-gateway-ccbill/">CCBill - WooCommerce Payment Gateway Plugin</a>
                        . After activating plugin and registering with CCBILL, configure it from 
                        <a href="admin.php?page=wc-settings&tab=checkout&section=ccbill">CCBill WooCommerce Settings</a>
                        .
                        <br>
                         + Enable payment gateways from 
                        <a target="_gateways" href="admin.php?page=woo-wallet-settings">Woo Wallet Settings</a>
                        .
                        <br> + If you don't use My Wallet frontend page, setup a page for users to buy credits with shortcode [woo-wallet]. My Wallet section is also available in WooCommerce My Account page (/my-account).
<br> + Also enable paying with Wallet for WooCommerce products from <a href="admin.php?page=wc-settings&tab=checkout">WooCommerce > Settings, Payments tab</a>.
                         <br> + As WooCommerce requires processing of orders (to get tokens allocated), use a plugin like 
                        <a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a>
                         or 
                        <a href="https://wordpress.org/plugins/autocomplete-woocommerce-orders/">Autocomplete WooCommerce Orders</a>
                         (and 
                        <a href="admin.php?page=wc-settings&tab=silkwave_aco">enable it</a>
                        ) to automatically do Processing to Completed . Or manually process from 
                        <a href="edit.php?post_type=shop_order">Orders</a>
                         .

                        <h4>Premium WooCommerce Plugins</h4>
                        <ul>

                            <li>
                                <a href="https://woocommerce.com/products/woocommerce-order-status-control/?aff=18336&amp;cid=2828082">Order Status Control</a>
                                 Control which Paid WooCommerce Orders are Automatically Completed so you don't have to manually Process payments. Order processing is required to get tokens allocated automatically when using TeraWallet and also to enable access for content purchased using the MicroPayments integration for selling content as WooCommerce products.
                            </li>

                            <li>
                                <a href="https://woocommerce.com/products/woocommerce-memberships/?aff=18336&amp;cid=2828082">WooCommerce Memberships</a>
                                 Setup paid membership as products. Leveraged with Subscriptions plugin allows membership subscriptions.
                            </li>
                            <li>
                                <a href="https://woocommerce.com/products/woocommerce-subscriptions/?aff=18336&amp;cid=2828082">WooCommerce Subscriptions</a>
                                 Setup subscription products, content. Leverages Membership plugin to setup membership subscriptionsSetup at least 1 paid role that members get by purchasing membership.
                            </li>

                            <li>
                                <a href="https://woocommerce.com/products/woocommerce-bookings/?aff=18336&amp;cid=2828082">WooCommerce Booking</a>
                                 Setup booking products with calendar, 
                                <a href="https://woocommerce.com/products/bookings-availability/?aff=18336&amp;cid=2828082">availability</a>
                                , 
                                <a href="https://woocommerce.com/products/woocommerce-deposits/?aff=18336&amp;cid=2828082">booking deposits</a>
                                , confirmations for 1 on 1 or group bookings.
                                <br/>

                                Include the room link or video call link in booking product description.
                            </li>
                            <li>
                                <a href="https://woocommerce.com/products/product-vendors/?aff=18336&amp;cid=2828082">WooCommerce Product Vendors</a>
                                 Allow multiple vendors to sell via your site and in return take a commission on sales. Leverage with 
                                <a href="https://woocommerce.com/products/woocommerce-product-reviews-pro/?aff=18336&amp;cid=2828082">Product Reviews Pro</a>
                                .
                            </li>

                            <LI>
                                <a href="https://woocommerce.com/products/follow-up-emails/?aff=18336&amp;cid=2828082">WooCommerce Follow Up</a>
                                 Follow Up by emails and twitter automatically, drip campaigns.
                            </LI>

                        </ul>



<h3>myCRED Wallet (MyCred)</h3>

<h4>1) myCRED</h4>
				<?php
				if ( is_plugin_active( 'mycred/mycred.php' ) ) {
					echo 'MyCred Plugin Detected';
				} else {
					echo 'Not detected. Please install and activate <a target="_mycred" href="https://wordpress.org/plugins/mycred/">myCRED</a> from <a href="plugin-install.php">Plugins > Add New</a>!';
				}

				if ( function_exists( 'mycred_get_users_balance' ) ) {
					echo '<br>Testing balance: You have ' . mycred_get_users_balance( get_current_user_id() ) . ' ' . wp_kses_post( htmlspecialchars( $options['currencyLong'] ) ) . '.';
					?>
	<ul>
		<li><a class="secondary button" href="admin.php?page=mycred">Transactions Log</a></li>
		<li><a class="secondary button" href="users.php">User Credits History & Adjust</a></li>
	</ul>
					<?php
				}
				?>
<a target="_mycred" href="https://wordpress.org/plugins/mycred/">myCRED</a> is a stand alone adaptive points management system that lets you award / charge your users for interacting with your WordPress powered website. The Buy Content add-on allows you to sell any publicly available post types, including webcam posts created by this plugin. You can select to either charge users to view the content or pay the post's author either the whole sum or a percentage.

	<br> + After installing and enabling myCRED, activate these <a href="admin.php?page=mycred-addons">addons</a>: buyCRED, Sell Content are required and optionally Notifications, Statistics or other addons, as desired for project.

	<br> + Configure in <a href="admin.php?page=mycred-main">Core Setting > Format > Decimals</a> at least 2 decimals to record fractional token usage. With 0 decimals, any transactions under 1 token will not be recorded.


<h4>2) myCRED buyCRED Module</h4>
				<?php
				if ( class_exists( 'myCRED_buyCRED_Module' ) ) {
					echo 'Detected';
					?>
	<ul>
		<li><a class="secondary button" href="edit.php?post_type=buycred_payment">Pending Payments</a></li>
		<li><a class="secondary button" href="admin.php?page=mycred-purchases-mycred_default">Purchase Log</a> - If you enable BuyCred separate log for purchases.</li>
		<li><a class="secondary button" href="edit-comments.php">Troubleshooting Logs</a> - MyCred logs troubleshooting information as comments.</li>
	</ul>
					<?php
				} else {
					echo 'Not detected. Please install and activate myCRED with <a href="admin.php?page=mycred-addons">buyCRED addon</a>!';
				}
				?>

<p> + myCRED <a href="admin.php?page=mycred-addons">buyCRED addon</a> should be enabled and at least 1 <a href="admin.php?page=mycred-gateways">payment gateway</a> configured, for users to be able to buy credits.
<br> + Setup a page for users to buy credits with shortcode <a target="mycred" href="http://codex.mycred.me/shortcodes/mycred_buy_form/">[mycred_buy_form]</a>.
<br> + Also "Thank You Page" should be set to "Webcams" and "Cancellation Page" to "Buy Credits" from <a href="admin.php?page=mycred-settings">buyCred settings</a>.</p>
<p>Troubleshooting: If you experience issues with IPN tests, check recent access logs (recent Visitors from CPanel) to identify exact requests from billing site, right after doing a test.</p>
<h4>3) myCRED Sell Content Module</h4>
				<?php
				if ( class_exists( 'myCRED_Sell_Content_Module' ) ) {
					echo 'Detected';
				} else {
					echo 'Not detected. Please install and activate myCRED with <a href="admin.php?page=mycred-addons">Sell Content addon</a>!';
				}
				?>
<p>
myCRED <a href="admin.php?page=mycred-addons">Sell Content addon</a> should be enabled as it's required to enable certain stat shortcodes. Optionally select "<?php echo esc_attr( ucwords( $options['custom_post'] ) ); ?>" - I Manually Select as Post Types you want to sell in <a href="admin.php?page=mycred-settings">Sell Content settings tab</a> so access to webcams can be sold from backend. You can also configure payout to content author from there (Profit Share) and expiration, if necessary.


<h3>Brave Tips and Rewards in Cryptocurrencies</h3>
<a href="https://brave.com/vid857">Brave</a> is a special build of the popular Chrome browser, focused on privacy and speed, already used by millions. Users get airdrops and rewards from ads they are willing to watch and content creators (publishers) like site owners get tips and automated revenue from visitors. This is done in $BAT and can be converted to other cryptocurrencies like Bitcoin or withdrawn in USD, EUR.
	<p>How to receive contributions and tips for your site:
	<br>+ Get the <a href="https://brave.com/vid857">Brave Browser</a>. You will get a browser wallet, airdrops and get to see how tips and contributions work.
	<br>+ Join <a href="https://creators.brave.com/">Brave Creators Publisher Program</a> and add your site(s) as channels. If you have an established site, you may have automated contributions or tips already available from site users that accessed using Brave. Your site(s) will show with a Verified Publisher badge in Brave browser and users know they can send you tips directly.
	<br>+ Add a new channel as new website, get <a href="https://wordpress.org/plugins/brave-payments-verification/">Brave Payments Verification</a> plugin for your WP site and copy the verification code to Settings > Brave Payments Verification.
	<br>+ You can setup and connect an Uphold wallet to receive your earnings and be able to withdraw to bank account or different wallet. You can select to receive your deposits in various currencies and cryptocurrencies (USD, EUR, BAT, BTC, ETH and many more).
</p>
				<?php

				$hideSubmit = 1;

				break;

			case 'users':
				?>
<h3>Users</h3>
Users with membership managed with this plugin:
<br>
				<?php

				if ( $delete_membership = intval( $_GET['delete_membership'] ?? 0 ) ) {
					delete_user_meta( $delete_membership, 'vw_paid_membership' );
					echo 'Deleted membership for: ' . esc_html( $delete_membership );
				}

				self::membership_update_all();

				$users = get_users(
					array(
						'meta_key' => 'vw_paid_membership',
						'fields'   => 'ID',
					)
				);

				if ( count( $users ) ) {
					foreach ( $users as $user ) {
						$user_info = get_userdata( $user );
						echo '<br>' . esc_html( $user_info->user_login ) . ' : ';
						echo self::membership_info( $user );
						echo ' - <a href="admin.php?page=paid-membership&tab=users&delete_membership=' . esc_attr( $user_info->ID ) . '">Delete Membership</a>';
					}
				} else {
					echo '<BR>Currently, no users have paid membership setup with this plugin.';
				}

				$hideSubmit = 1;

				break;

			case 'donations':
				?>
<h3>Donations & CrowdFunding</h3>
Show a Donate/Fund button on content pages (blog posts, videos, pictures), for authors to receive donations or crowdfunding.
When editing own content (digital asset) author can configure donations as: Enabled, Disabled, Goal, CrowdFunding.


<h4>Donate Content Types</h4>
<input name="postTypesDonate" type="text" id="postTypesDonate" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesDonate'] ); ?>"/>
<BR>Comma separated content/post types. Ex: page, post, video, picture, download, webcam, channel
<BR>User will see a button to Donate and own Wallet to add funds.

<h4>Donate User Roles</h4>
<input name="rolesDonate" type="text" id="rolesDonate" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesDonate'] ); ?>"/>
<BR>Comma separated roles allowed to donate. Ex: administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan 
<br>Leave empty to allow anybody.
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>

<h4>Author Donation Earnings Ratio for MicroPayments</h4>
<input name="donationRatio" type="text" id="donationRatio" size="5" maxlength="10" value="<?php echo esc_attr( $options['donationRatio'] ); ?>"/>
<br>Ratio author gets from donations (greater than 0 and less than 1.0, max 3 decimals). Remaining amount is site profit as spent by client and not being given to author.
<br>Only applies for tips/gifts handled by MicroPayments plugin. Other donations/tips/gifts features like from PaidVideochat are configured from their plugins.
<br>Example: Set 0.9 if you want author to get 90% from client payment. 



<h4>Donation Default Value</h4>
<input name="donationDefault" type="text" id="donationDefault" size="5" maxlength="10" value="<?php echo esc_attr( $options['donationDefault'] ); ?>"/>
<br>Donation dialog shows as slider.

<h4>Donation Minimum</h4>
<input name="donationMin" type="text" id="donationMin" size="5" maxlength="10" value="<?php echo esc_attr( $options['donationMin'] ); ?>"/>

<h4>Donation Maximum</h4>
<input name="donationMax" type="text" id="donationMax" size="5" maxlength="10" value="<?php echo esc_attr( $options['donationMax'] ); ?>"/>

<h4>Donation Step</h4>
<input name="donationStep" type="text" id="donationStep" size="5" maxlength="10" value="<?php echo esc_attr( $options['donationStep'] ); ?>"/>

<h3>Enable Site Tips and Automated Revenue in Cryptocurrencies with Brave</h3>
<a href="https://brave.com/vid857">Brave</a> is a special build of the popular Chrome browser, focused on privacy and speed, already used by millions. Users get airdrops and rewards from ads they are willing to watch and content creators (publishers) like site owners get tips and automated revenue from visitors. This is done in $BAT and can be converted to other cryptocurrencies like Bitcoin or withdrawn in USD, EUR.
	<p>How to receive contributions and tips for your site:
	<br>+ Get the <a href="https://brave.com/vid857">Brave Browser</a>. You will get a browser wallet, airdrops and get to see how tips and contributions work.
	<br>+ Join <a href="https://creators.brave.com/">Brave Creators Publisher Program</a> and add your site(s) as channels. If you have an established site, you may have automated contributions or tips already available from site users that accessed using Brave. Your site(s) will show with a Verified Publisher badge in Brave browser and users know they can send you tips directly.
	<br>+ You can setup and connect an Uphold wallet to receive your earnings and be able to withdraw to bank account or different wallet. You can select to receive your deposits in various currencies and cryptocurrencies (USD, EUR, BAT, BTC, ETH and many more).
</p>

				<?php

				break;

			case 'content':
				$options['paidMessage'] = htmlspecialchars( stripslashes( $options['paidMessage'] ) );

				?>
<h3>Paid Content</h3>
Enable content (posts) that require purchase with micropayments for access. <a href="admin.php?page=paid-membership&tab=pages">Setup Pages</a> to enable a <a href="<?php echo get_permalink( $options['p_videowhisper_content_seller'] ); ?>">Manage Assets</a> pages for sellers to setup pricing and  <a href="<?php echo get_permalink( $options['p_videowhisper_content'] ); ?>">My Content</a> page for buyers to easily access their purchases. <a href="<?php echo get_permalink( $options['p_videowhisper_content_list'] ); ?>">Content</a> page aggregates the digital content (post types) available for sale.

<h4>Paid Content Handling : WooCommerce / MyCRED / MicroPayments (internal)</h4>
<select name="paid_handler" id="paid_handler">
  <option value="micropayments" <?php echo $options['paid_handler'] == 'micropayments' ? 'selected' : ''; ?>>MicroPayments</option>
  <option value="woocommerce" <?php echo $options['paid_handler'] == 'woocommerce' ? 'selected' : ''; ?>>WooCommerce</option>
  <option value="mycred" <?php echo $options['paid_handler'] == 'mycred' ? 'selected' : ''; ?>>MyCred</option>
  <option value="none" <?php echo $options['paid_handler'] == 'none' ? 'selected' : ''; ?>>None</option>
</select>
<br> - MicroPayments: Micropayments plugin (by VideoWhisper) shows a buy button, manages transfer using internal handler. Allows configuring access price and duration. Tokens are deducted from buyer wallet and added to owner wallet, using currently configured billing wallet.
<br>Additionally, MicroPayments powers Coauthors feature where owner can add couthors that receive custom percents of the earnings when content is sold. Coauthors can be added when editing assets in frontend, from Monetization section with price, access duration. Coauthors also receive access to that asset.
<br> - WooCommerce: MicroPayments plugin creates a WooCommerce product for each item (custom post). Client buys the product and gets access to content. Useful for adding multiple items to cart. Requires <a href="https://woocommerce.com/?aff=18336&amp;cid=2828082">WooCommerce</a>.  Does not support access durations (all purchases are lifetime). Warning: WooCommerce product purchase is managed by WooCommerce per its billing gateway configuration and payments don't get credited to an internal wallet. Should be used in combination with a <a href="https://woocommerce.com/products/product-vendors/?aff=18336&cid=2828082">multi Vendors</a> plugin. Buyer pays per WooCommerce billing setup and does not credit content owner token wallet.
<br> - MyCred: MicroPayments plugin sets a price in MyCred Sell Content addon. Requires <a target="_mycred" href="https://wordpress.org/plugins/mycred/">myCRED</a> with Sell Content <a href="admin.php?page=mycred-addons">addon</a> enabled and configured for that content type. Select "I Manually Select" for Post Types you want to sell in <a href="admin.php?page=mycred-settings">Sell Content settings tab</a> so access to those item can be sold from backend. You can also configure payout to content author from that section (Profit Share) and expiration, if necessary. Tokens are deducted from buyer wallet and commissions are added to owner wallet depending on settings.
<br>If you want to use site microtransactions where tokens are given to the content owner wallet, use a wallet handler like MicroPayments (recommended) or MyCred.


				<?php
				if ( $options['paid_handler'] == 'mycred' ) {
					if ( class_exists( 'myCRED_Sell_Content_Module' ) ) {
						echo '<br>myCRED Sell Content Addon Detected';
					} else {
						echo '<br>myCRED Sell Content Addon  NOT detected. Please install and activate myCRED with <a href="admin.php?page=mycred-addons">Sell Content Addon</a>!';
					}
				}
				?>

<h4>Author Content Earnings Ratio for MicroPayments</h4>
<input name="contentRatio" type="text" id="contentRatio" size="5" maxlength="10" value="<?php echo esc_attr( $options['contentRatio'] ); ?>"/>
<br>Ratio author gets from content sales (greater than 0 and less than 1.0, max 3 decimals). Remaining amount is site profit as spent by client and not being given to author.
<br>Only applies when paid content sales are handled by MicroPayments plugin. Other methods or selling content are configured from their plugins.
<br>Example: Set 0.8 if you want author to get 80% from client payment. 


<h4>Content Minimum Cost</h4>
<input name="contentMin" type="text" id="contentMin" size="5" maxlength="10" value="<?php echo esc_attr( $options['contentMin'] ); ?>"/><?php echo esc_html( $options['currency'] ); ?>
<br>Minimum configurable cost for content. Minimum is 0. 

<h4>Content Maximum Cost</h4>
<input name="contentMax" type="text" id="contentMax" size="5" maxlength="10" value="<?php echo esc_attr( $options['contentMax'] ); ?>"/><?php echo esc_html( $options['currency'] ); ?>
<br>Maximum configurable cost for content. Setting 0 will remove the limit.

<h4>Paid Content Types</h4>
<input name="postTypesPaid" type="text" id="postTypesPaid" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesPaid'] ); ?>"/>
<BR>Comma separated content/post types that can be sold. Ex: page, post, video, picture, download, webcam, channel
<br>Owner can access own content without purchasing. 

<h4>Popular Paid Content Types</h4>
<input name="postTypesPopular" type="text" id="postTypesPopular" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesPopular'] ); ?>"/>
<BR>Popular paid content types to list by default. Ex: video, picture, download

<h4>Edit Content Types</h4>
<input name="postTypesEdit" type="text" id="postTypesEdit" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesEdit'] ); ?>"/>
<BR>Comma separated content/post types that can be edited, usually same as paid content types or more. Ex: page, post, video, picture, download, webcam, channel
<BR>Owner will see an Edit button on content page and listings, to edit price, donation options, subscription tier, title, description. 

<h4>Author Box Content Types</h4>
<input name="postTypesAuthors" type="text" id="postTypesAuthors" size="100" maxlength="250" value="<?php echo esc_attr( $options['postTypesAuthors'] ); ?>"/>
<BR>Comma separated content/post types that will show a box with author and coauthors if any. Ex: post, video, picture, download, webcam, channel

<h4>Buyer User Roles</h4>
<input name="rolesBuyer" type="text" id="rolesBuyer" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesBuyer'] ); ?>"/>
<BR>Comma separated roles allowed to buy content. Ex: administrator, editor, author, contributor, subscriber, performer, creator, studio, client, fan 
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for all.
<br> - Your roles (for troubleshooting):
				<?php
			global $current_user;
			foreach ( $current_user->roles as $role )
			{
				echo esc_html( $role ) . ' ';
			}
?>
			<br> - Current WordPress roles:
				<?php
			global $wp_roles;
			foreach ( $wp_roles->roles as $role_slug => $role )
			{
				echo esc_html( $role_slug ) . '= "' . esc_html( $role['name'] ) . '" ';
			}
?>

<h4>Seller User Roles</h4>
<input name="rolesSeller" type="text" id="rolesSeller" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesSeller'] ); ?>"/>
<BR>Comma separated roles allowed to sell/manage content in frontend. Ex: administrator, editor, author, contributor, performer, creator, studio
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for all.

<h4>Token Package User Roles</h4>
<input name="rolesPackages" type="text" id="rolesPackages" size="100" maxlength="250" value="<?php echo esc_attr( $options['rolesPackages'] ); ?>"/>
<BR>Comma separated roles that will see token packages in My Wallet. Ex: administrator, editor, author, contributor, performer, creator, studio, client, subscriber, fan, viewer
<br>Leave empty to allow anybody or only an inexistent role (none) to disable for all.


<h4>Comments Access</h4>
<select name="commentsAccess" id="commentsAccess">
  <option value="1" <?php echo $options['commentsAccess'] == '1' ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['commentsAccess'] == '0' ? 'selected' : ''; ?>>No</option>
</select>
<br>Allow access to comments on paid posts. Recommended: No. 

<h4>Comments Limit</h4>
<input name="commentsLimit" type="text" id="commentsLimit" size="10" maxlength="30" value="<?php echo esc_attr( $options['commentsLimit'] ); ?>"/>
<br>Limit comments per user (except content author). Set 0 to disable.

<h4>Content Upload/Share in BP Post Form for Sellers</h4>
<select name="buddypressPost" id="buddypressPost">
  <option value="1" <?php echo $options['buddypressPost'] == '1' ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['buddypressPost'] == '0' ? 'selected' : ''; ?>>Disabled</option>
</select>
<br>BuddyPress/BuddyBoss: Content sellers roles get buttons to upload/share content in the BP post form. 

<h4>BP Profile Tabs for Sellers</h4>
<select name="buddypressProfile" id="buddypressProfile">
  <option value="1" <?php echo $options['buddypressProfile'] == '1' ? 'selected' : ''; ?>>Enabled</option>
  <option value="0" <?php echo $options['buddypressProfile'] == '0' ? 'selected' : ''; ?>>Disabled</option>
</select>
<br>BuddyPress/BuddyBoss: Content sellers roles get special BP profile sections listing their content and subscriptions.


<h4>Uploader Roles</h4>
<textarea name="shareList" cols="64" rows="2" id="shareList"><?php echo esc_textarea( $options['shareList'] ); ?></textarea>
<BR>Who can upload content: comma separated Roles, user Emails, user ID numbers. Usually same as sellers.

<h4>Moderator User Roles</h4>
<input name="roleModerators" type="text" id="roleModerators" size="40" maxlength="64" value="<?php echo esc_attr( $options['roleModerators'] ); ?>"/>
<br>Comma separated roles, allowed to access content without paying.

<h4>Paid Content Message</h4>
<textarea name="paidMessage" id="paidMessage" cols="100" rows="3"><?php echo esc_textarea( $options['paidMessage'] ); ?></textarea>
<br>Message for site users when trying to access content that requires purchase.


<h4>WooCommerce Content Products</h4>
				<?php
				$posts = get_posts(
					array(
						'meta_key'         => 'vw_micropay_productid',
						'post_type'        => 'any',
						'post_status'      => 'any',
						'orderby'          => 'date',
						'order'            => 'DESC',
						'suppress_filters' => true,
					)
				);

				foreach ( $posts as $post ) {
					$product_id = get_post_meta( $post->ID, 'vw_micropay_productid', true );
					$price      = get_post_meta( $post->ID, 'vw_micropay_price', true );

					echo '<p>';
					echo '<a target="_content" href="' . get_post_permalink( $post->ID ) . '">' . ucwords( esc_html( $post->post_type ) ) . ': ' . esc_html( $post->post_name ) . '</a> ';
					echo ' Price: ' . esc_html( $price ) . ', <a target="_product" href="' . get_permalink( $product_id ) . '">Product #' . esc_html( $product_id ) . '</a>';
					echo '</p>';
				}
				?>
<h4>MyCred Sell Content</h4>
				<?php
				$posts = get_posts(
					array(
						'meta_key'         => 'myCRED_sell_content',
						'post_type'        => 'any',
						'post_status'      => 'any',
						// 'post_type'     => array ( 'post', 'page', 'presentation', 'channel', 'webcam', 'video'),
						'orderby'          => 'date',
						'order'            => 'DESC',
						'suppress_filters' => true,
					)
				);

				foreach ( $posts as $post ) {
					$meta = get_post_meta( $post->ID, 'myCRED_sell_content', true );
					echo '<p>';
					echo '<a target="_content" href="' . get_post_permalink( $post->ID ) . '">' . ucwords( esc_html( $post->post_type ) ) . ': ' . esc_html( $post->post_name ) . '</a> ';
					echo ' Price: ' . esc_html( $meta['price'] ) . ', Duration: ' . ( $meta['expire'] ? ( esc_html( $meta['expire'] ) . ' h' ) : 'unlimited' ) . ', ' . ( $meta['recurring'] ? 'recurring.' : 'one time fee.' );
					echo '</p>';
				}

				break;
		}

		// extra check for unmarked content in backed, paid content section (once per hour)
		if ( self::timeTo('updatePremiumAdmin', 3600) ) self::updatePremium($options);

		if ( ! $hideSubmit ?? false ) {
			submit_button();
		}
		echo '</form>';

	}


	static function adminUpload() {
		?>
		<div class="wrap">
		<?php screen_icon(); ?>
		<h2>Upload / Downloads / MicroPayments - Paid Membership/Content/Downloads by VideoWhisper.com</h2>
		<?php
			echo do_shortcode( '[videowhisper_download_upload]' );
		?>
		Use this page to upload one or multiple downloads to server. Configure category, collections and then choose files or drag and drop files to upload area.
		<br>Collection(s): Assign downloads to multiple collections, as comma separated values. Ex: subscriber, premium

		</div>
		<?php
	}


	static function adminImport() {
		 $options = self::getOptions();

		if ( isset( $_POST ) ) {
			if ( ! empty( $_POST ) ) {
						$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) ) {
					echo 'Invalid nonce!';
					exit;
				}

				foreach ( $options as $key => $value ) {
					if ( isset( $_POST[ $key ] ) ) {
						$options[ $key ] = trim( sanitize_textarea_field( $_POST[ $key ] ) );
					}
				}
				update_option( 'VWpaidMembershipOptions', $options );
			}
		}

		screen_icon();
		?>
<h2>Import / Downloads / MicroPayments - Paid Membership/Content/Downloads by VideoWhisper.com</h2>
	Use this to mass import any number of files already existent on server, as downloads.

		<?php
		if ( file_exists( sanitize_text_field( $options['importPath'] ) ) ) {
			echo do_shortcode( '[videowhisper_downloads_import path="' . esc_attr( $options['importPath'] ) . '"]' );
		} else {
			echo 'Import folder not found on server: ' . esc_html( $options['importPath'] );
		}
		?>
<h3>Import Settings</h3>
<form method="post" action="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'vwsec' ); ?>">
<h4>Import Path</h4>
<p>Server path to import downloads from</p>
<input name="importPath" type="text" id="importPath" size="100" maxlength="256" value="<?php echo esc_attr( $options['importPath'] ); ?>"/>
<br>Ex: /home/[youraccount]/public_html/streams/
<h4>Delete Original on Import</h4>
<select name="deleteOnImport" id="deleteOnImport">
  <option value="1" <?php echo $options['deleteOnImport'] ? 'selected' : ''; ?>>Yes</option>
  <option value="0" <?php echo $options['deleteOnImport'] ? '' : 'selected'; ?>>No</option>
</select>
<br>Remove original file after copy to new location.
<h4>Import Clean</h4>
<p>Delete downloads older than:</p>
<input name="importClean" type="text" id="importClean" size="5" maxlength="8" value="<?php echo esc_attr( $options['importClean'] ); ?>"/>days
<br>Set 0 to disable automated cleanup. Cleanup does not occur more often than 10h to prevent high load.
		<?php submit_button(); ?>
</form>
		<?php

	}

// !backend user listings
		static function manage_users_columns( $columns ) {
			$columns['micropayments_balance'] =  __('Balance', 'paid-membership')  ;
			return $columns;
		}


		static function manage_users_sortable_columns( $columns ) {
			$columns['micropayments_balance'] = 'micropayments_balance';
			return $columns;
		}


		static function pre_user_query( $user_search ) {
			global $wpdb, $current_screen;

			if ( ! $current_screen ) {
				return;
			}
			if ( 'users' != $current_screen->id ) {
				return;
			}

			$vars = $user_search->query_vars;

			if ( 'micropayments_balance' == $vars['orderby'] ) {
				$user_search->query_from   .= " LEFT JOIN {$wpdb->usermeta} m1 ON {$wpdb->users}.ID=m1.user_id AND (m1.meta_key='micropayments_balance')";
				$user_search->query_orderby = ' ORDER BY COALESCE(m1.meta_value, 0) * 1 ' . $vars['order'];
			}

		}


		static function manage_users_custom_column( $value, $column_name, $user_id ) {
			if ( $column_name == 'micropayments_balance' ) {
				$micropayments_balance  = floatval( get_user_meta( $user_id, 'micropayments_balance', true ) );
				
				$options = self::getOptions();

				$htmlCode = $micropayments_balance . $options['currency'] ;
				$htmlCode .= '<div class="row-actions"><span><a href="admin.php?page=micropayments-transactions&user_id=' . $user_id . '">Transactions</a></span></div>';

				return $htmlCode;
			} else {
				return $value;
			}
		}

	static function adminTransactions() {      ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br></div>
	<h2>MicroPayments - Wallet Transactions</h2>
</div>
<?php
	

 	 // Adjustments form
	  if (isset($_REQUEST['user_id'])) if ($userID = intval($_REQUEST['user_id']))
	  {
		  	$user = get_userdata( $userID );
            if ($user) echo  '<h3><a href="user-edit.php?user_id=' . intval( $userID ) . '">' .  esc_html( $user->display_name ? $user->display_name : $user->user_login ) . '</a></h3>';
						
		  	if ( isset( $_POST ) ) {
			if ( ! empty( $_POST ) ) {
				$nonce = $_REQUEST['_wpnonce'];
				if ( ! wp_verify_nonce( $nonce, 'vwsec' ) ) {
					echo 'Invalid nonce!';
					exit;
				}
				//
				
				$amount = number_format(floatval($_POST['amount']), 2, '.', '' );
				if ($amount != 0) self::micropayments_transaction ($userID, 'admin_adjustment', $amount, sanitize_textarea_field( $_POST['details'] ), 0, get_current_user_id() );
				echo 'Adjustment applied: ' . $amount;
				}
				}
				
		  ?>
		  <h3>New Adjustment</h3>
		  <form method="post" action="<?php echo wp_nonce_url( $_SERVER['REQUEST_URI'], 'vwsec' ); ?>">
			  <h4>Transaction Amount</h4>
			  <input name="amount" type="text" id="amount" size="10" maxlength="20" value="0"/> Use minus (-) before value to deduct amount (negative transaction). 
			  <h4>Transaction Description</h4>
			  <textarea name="details" id="details" cols="100" rows="1"></textarea>
			  		
			  					<?php submit_button(__('Apply Adjustment', 'paid-membership')); ?>
 
		  </form>

		  <h3>Transactions List</h3>
		  <?php

			echo '<a class="button" href="admin.php?page=micropayments-transactions">' . __('Transactions For All Users', 'paid-membership') . '</a>';

	  }; 

			  		  echo '<a class="button" href="users.php?orderby=micropayments_balance&order=desc">' . __('All User Balances', 'paid-membership') . '</a>';
	  
	//transactions table
	      $transactionsTable = new Transactions_Table();
		  $transactionsTable->prepare_items();
      
		  $transactionsTable->display();  
		  
		  ?>
		  * This table only shows transactions in MicroPayments (internal) wallet. If you have other wallet plugins installed, each plugin manages own transactions. 
		  <?php
			  
}

}

// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) ) 
{
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Transactions_Table extends \WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();

        $data = $this->table_data();
        
		$perPage = $this->get_items_per_page('records_per_page', 20);
		$currentPage = $this->get_pagenum();
		$totalItems = self::record_count();
 
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );

		$data = self::get_records($perPage, $currentPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'transaction_id' => 'Transaction ID',
            'user_id'       => 'User',
            'amount'        => 'Amount',
            'date'      => 'Date',
            'details' => 'Details',
            'type'    => 'Type',
            'balance'    => 'End Balance',
        );

        return $columns;
    }

  /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

	/**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'transaction_id':
            case 'amount':
            case 'date':
            case 'details':    
            case 'balance':
            case 'type':

                return $item[ $column_name ];
            
            case 'user_id':
            $user = get_userdata( intval( $item[ $column_name ] ) );
            if ($user) return  '<a href="admin.php?page=micropayments-transactions&user_id=' . intval( $item[ $column_name ] ) . '">' .  ($user->display_name ? $user->display_name : $user->user_login ) . '</a>';
            else return $item[ $column_name ];

            default:
                return print_r( $item, true ) ;
        }
    }


    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('transaction_id' => array('transaction_id', false), 'date' => array('date', false), 'amount' => array('amount', false),  'balance' => array('balance', false), 'type' => array('type', false));
    }
    

/** 
* Returns the count of records in the database. 
* * @return null|string 
*/
public static function record_count()
{
    global $wpdb;
    
    $table_transactions = $wpdb->prefix . 'vw_micropay_transactions';
    $sql = "SELECT COUNT(*) FROM $table_transactions";
    
    if (isset($_REQUEST['user_id'])) {
    $sql.= ' WHERE user_id = "' . intval($_REQUEST['user_id']) . '"';
    }
    
    return $wpdb->get_var($sql);
}


	/**
     * Get the table data
     *
     * @return Array
     */
    public static function get_records($per_page = 20, $page_number = 1)
    {
        $data = array();
        
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
			
	global $wpdb;
	$table_transactions = $wpdb->prefix . 'vw_micropay_transactions';

	$sql = "SELECT * FROM $table_transactions";
				    
	if (isset($_REQUEST['user_id'])) {
    $sql.= ' WHERE user_id = "' . intval($_REQUEST['user_id']) . '"';
    }
    
    if (!empty($_REQUEST['orderby'])) {
          $sql.= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
        $sql.= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
    } else  $sql.= ' ORDER BY date DESC';
    
    
    $sql.= " LIMIT $per_page";
    $sql.= ' OFFSET ' . ($page_number - 1) * $per_page;
    
    $result = $wpdb->get_results($sql, 'ARRAY_A');
    
    return $result;
    
	}

}