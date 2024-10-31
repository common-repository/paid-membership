<?php
namespace VideoWhisper\PaidContent;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// ini_set('display_errors', 1); //debug only

trait Shortcodes {


	static function enqueueUI() {
		wp_enqueue_script( 'jquery' );

		wp_enqueue_style( 'semantic', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/semantic/semantic.min.css' );
		wp_enqueue_script( 'semantic', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/semantic/semantic.min.js', array( 'jquery' ) );


		//fix rules if not present
		global $wp_rewrite;
		if (!$wp_rewrite->rules) $wp_rewrite->flush_rules( true );
	}


	static function getCurrentURLfull() {
		$currentURL  = ( @$_SERVER['HTTPS'] == 'on' ) ? 'https://' : 'http://';
		$currentURL .= $_SERVER['SERVER_NAME'];

		if ( $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ) {
			$currentURL .= ':' . $_SERVER['SERVER_PORT'];
		}

		$currentURL .= $_SERVER['REQUEST_URI'];

		return $currentURL;
	}


	 static function rolesUser( $csvRoles, $user)
		{
			// user has any of the listed roles
			// if (self::rolesUser( $option['rolesDonate'], wp_get_current_user() )

			if (!$csvRoles) return true; //all allowed if not defined
			if (!$user) return false;
			if (!isset($user->roles)) return false;
			if (!is_array($user->roles)) return false;

			$roles = explode(',', $csvRoles);
			foreach ($roles as $key => $value) $roles[$key] = trim($value);

			if ( self::any_in_array( $roles, $user->roles ) ) return true;

			return false;
		}

		static function any_in_array( $array1 = null, $array2 = null ) {

			if (!is_array($array1)) return false;
			if (!is_array($array2)) return false;

			foreach ( $array1 as $value ) {
				if ( in_array( $value, $array2 ) ) {
					return true;
				}
			}
				return false;
		}




	static function isPremium($postID)
	{

		$isPremium = false;
		$isPaid = false;
		$isFree = true;

		if ( get_post_meta($postID, 'vw_subscription_tier', true) ) $isPremium = true;
		if ( get_post_meta($postID, 'micropayments_price', true) || get_post_meta($postID, 'vw_micropay_price', true) || get_post_meta($postID, 'myCRED_sell_content', true) )
		{
		$isPremium = true;
		$isPaid = true;
		}

		if ($isPremium) $isFree = false;

		//update if incorrect
		if ( $isPremium && !get_post_meta($postID, 'vw_premium', true) ) update_post_meta($postID, 'vw_premium', 1);
		if ( !$isPremium && get_post_meta($postID, 'vw_premium', true) ) delete_post_meta($postID, 'vw_premium');

		if ( $isPaid && !get_post_meta($postID, 'vw_paid', true) ) update_post_meta($postID, 'vw_paid', 1);
		if ( !$isPaid && get_post_meta($postID, 'vw_paid', true) ) delete_post_meta($postID, 'vw_paid');

		if ( $isFree && !get_post_meta($postID, 'vw_free', true) ) update_post_meta($postID, 'vw_free', 1);
		if ( !$isFree && get_post_meta($postID, 'vw_free', true) ) delete_post_meta($postID, 'vw_free');

		return $isPremium;
	}

	// content list
	static function videowhisper_content_list( $atts ) {

		$options = self::getOptions();

		$atts = shortcode_atts(
			array(
				'menu'            => sanitize_text_field( $options['listingsMenu'] ?? 'auto' ),
				'ids'             => '',
				'perpage'         => $options['perPageContent'],
				'perrow'          => '',
				'collection'      => '',
				'order_by'        => '',
				'category_id'     => '',
				'tabs' 			  => sanitize_text_field( $options['listingsTabs'] ?? 'auto' ),
				'select_type'     => '1',
				'select_access'   => '1',
				'select_category' => '1',
				'select_order'    => '1',
				'select_page'     => '1', // pagination
				'select_tags'     => '1',
				'select_name'     => '1',
				'include_css'     => '1',
				'tags'            => '',
				'name'            => '',
				'id'              => '',
				'author_id' 	  => '',
			),
			$atts,
			'videowhisper_downloads'
		);

		$id = $atts['id'];
		if ( ! $id ) {
			$id = uniqid();
		}

		self::enqueueUI();

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_content&menu=' . $atts['menu'] . '&pp=' . $atts['perpage'] . '&pr=' . $atts['perrow'] . '&collection=' . urlencode( $atts['collection'] ) . '&ob=' . $atts['order_by'] . '&cat=' . $atts['category_id'] . '&tabs=' . $atts['tabs'] . '&st=' . $atts['select_type']. '&sa=' . $atts['select_access']. '&sc=' . $atts['select_category'] . '&so=' . $atts['select_order'] . '&sp=' . $atts['select_page'] . '&sn=' . $atts['select_name'] . '&sg=' . $atts['select_tags'] . '&id=' . $id . '&tags=' . urlencode( $atts['tags'] ) . '&name=' . urlencode( $atts['name'] ) . '&ids=' . urlencode( $atts['ids'] ) ;

		if ($atts['author_id']) $ajaxurl .= '&author_id=' . intval($atts['author_id']);

		$htmlCode = <<<HTMLCODE
<script type="text/javascript">
var aurl$id = '$ajaxurl';
var \$j = jQuery.noConflict();
var loader$id;

	function loadContent$id(message){

	if (message)
	if (message.length > 0)
	{
	  \$j("#videowhisperContent$id").html(message);
	}

		if (loader$id) loader$id.abort();

		loader$id = \$j.ajax({
			url: aurl$id,
			success: function(data) {
				\$j("#videowhisperContent$id").html(data);
				jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
				jQuery(".ui.rating.readonly").rating("disable");
			}
		});
	}


	\$j(function(){
		loadContent$id();
		setInterval("loadContent$id('')", 30000);
	});

</script>

<div id="videowhisperContent$id">
 <div class="ui active inline text large loader">Loading content ... </div>
</div>

HTMLCODE;

		if ( $atts['include_css'] ) {
			$htmlCode .= '<!-- Content CSS -->' .'<STYLE>' .  html_entity_decode( stripslashes( $options['contentCSS'] ) ) . '</STYLE>';
		}

		//once per day check for new content not marked as premium/free
		if ( self::timeTo('updatePremium', 86400 ) ) self::updatePremium($options);

		return $htmlCode;
	}

	static function updatePremium($options)
	{
		//checks for new unmarked content

		//all paid content types
		$post_type = array();
		$postTypes         = explode( ',', $options['postTypesPaid'] );
		foreach ( $postTypes as $postType ) {
			$post_type[] = trim( $postType );
		}

	// query
	$args = array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'numberposts'      => 100,
	);

	//not premium or free = not marked, yet
	$args['meta_query'] = array(
		'relation'    => 'AND',
		array(
			'key'     => 'vw_premium',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => 'vw_free',
			'compare' => 'NOT EXISTS',
		)
	);

	// get items
	$postslist = get_posts( $args );

	foreach ( $postslist as $post ) {
		self::isPremium($post->ID);
	}

	}

	static function vwpm_content() {
		$options = self::getOptions();

		$perPage = (int) $_GET['pp'];
		if ( ! $perPage ) {
			$perPage = $options['perPageContent'];
		}

		$collection = sanitize_file_name( $_GET['collection'] );

		$id = sanitize_text_field( $_GET['id'] ?? '' );

		$menu             = sanitize_text_field( $_GET['menu'] ?? 'auto');
		$isMobile = (bool) preg_match( '#\b(ip(hone|od|ad)|android|opera m(ob|in)i|windows (phone|ce)|blackberry|tablet|s(ymbian|eries60|amsung)|p(laybook|alm|rofile/midp|laystation portable)|nokia|fennec|htc[\-_]|mobile|up\.browser|[1-4][0-9]{2}x[1-4][0-9]{2})\b#i', $_SERVER['HTTP_USER_AGENT'] );
		if ( $menu == 'auto' && $isMobile )  $menu = false;		//auto disables on mobile

		$category = intval( $_GET['cat'] ?? 0 );

		$page   = intval( $_GET['p'] ?? 0 );
		$offset = $page * $perPage;

		$perRow = intval( $_GET['pr'] ?? 0);

		// order
		$order_by = sanitize_file_name( $_GET['ob'] ?? '' );
		if ( ! $order_by ) {
			$order_by = 'post_date';
		}

		// options
		$tabs = sanitize_text_field( $_GET['tabs'] ?? 'auto' );
		if ( $tabs == 'auto' && $isMobile )  $tabs = false;		//auto disables on mobile

		$selectType     = intval( $_GET['st'] ?? 0 );
		$selectAccess   = intval( $_GET['sa'] ?? 0 );

		$selectCategory = intval( $_GET['sc'] ?? 0 );
		$selectOrder    = intval( $_GET['so'] ?? 0 );
		$selectPage     = intval( $_GET['sp'] ?? 0 );

		$selectName = intval( $_GET['sn'] ?? 0 );
		$selectTags = intval( $_GET['sg'] ?? 0 );


		// ids
		$id_in = [];
		$ids = sanitize_text_field( $_GET['ids'] ?? '' );
		if ( $ids ) {
			$id_in = explode( ',', $ids );
		}

		if ( ! is_array( $id_in ) ) {
			$id_in = array();
		}

		// tags,name search
		$tags = sanitize_text_field( $_GET['tags'] ?? '' );
		$name = sanitize_file_name( $_GET['name'] ?? '' );
		if ( $name == 'undefined' ) {
			$name = '';
		}
		if ( $tags == 'undefined' ) {
			$tags = '';
		}

		$author_id = intval( $_GET['author_id'] ?? 0 );



		//tabs
		$tabType = sanitize_text_field( $_GET['ttype'] ?? '' );
		$tabAccess = sanitize_text_field( $_GET['tacc'] ?? '' );

		//selected type
		if ( $tabType && $tabType !='all' ) $post_type = trim($tabType);
		else
		{
			//all paid content types
			$post_type = array();

			if ($tabType =='all' ) $postTypes = explode( ',', $options['postTypesPaid'] );
			else $postTypes = explode( ',', $options['postTypesPopular'] );
			if (!count($postTypes)) $postTypes = explode( ',', $options['postTypesPaid']); //fallback to all

			foreach ( $postTypes as $postType ) {
				$post_type[] = trim( $postType );
			}
		}

		// query
		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $perPage,
			'offset'         => $offset,
			'order'          => 'DESC',
		);

		if ($author_id) $args['author'] = $author_id;

		if ( $ids ) {
			$args['post__in'] = $id_in;
		}

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

		switch ( $tabAccess )
		{
			case 'subscription':
				$args['meta_query'] = array(
					'relation'    => 'AND',
					array(
						'key'     => 'vw_subscription_tier',
						'compare' => 'EXISTS',
					)
					/*,
					array(
						'key'     => 'vw_subscription_tier',
						'value'   => '0',
						'compare' => '!=',
					)
					*/
				);



				break;

				case 'paid':

					$args['meta_query'] = array(
						'relation'    => 'AND',
						array(
							'key'     => 'vw_paid',
							'compare' => 'EXISTS',
						)
					);
					/*
					$args['meta_query'] = array(
						'relation'    => 'OR',
						array(
							'key'     => 'micropayments_price',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => 'vw_micropay_price',
							'compare' => 'EXISTS',
						),
						array(
							'key'     => 'myCRED_sell_content',
							'compare' => 'EXISTS',
						)
					);*/
					break;

					case 'premium':

						$args['meta_query'] = array(
							'relation'    => 'AND',
							array(
								'key'     => 'vw_premium',
								'compare' => 'EXISTS',
							)
						);

						/*
						$args['meta_query'] = array(
							'relation'    => 'OR',
							array(
								'key'     => 'vw_subscription_tier',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => 'micropayments_price',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => 'vw_micropay_price',
								'compare' => 'EXISTS',
							),
							array(
								'key'     => 'myCRED_sell_content',
								'compare' => 'EXISTS',
							)
						);
						*/

						break;

				case 'free':

					$args['meta_query'] = array(
						'relation'    => 'AND',
						array(
							'key'     => 'vw_free',
							'compare' => 'EXISTS',
						)
					);
					/*
					$args['meta_query'] = array(
						'relation'    => 'AND',
						array(
							'key'     => 'vw_subscription_tier',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'micropayments_price',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'vw_micropay_price',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'myCRED_sell_content',
							'compare' => 'NOT EXISTS',
						)
					);
					*/
					break;

			default:
			//no access args
			break;
		}

		// user permissions
		$isAdministrator = 0;
		$isID = 0;
		$pmEnabled = 0;

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

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_content&menu=' . $menu . '&pp=' . $perPage . '&pr=' . $perRow . '&collection=' . urlencode( $collection ) . '&tabs=' . $tabs . '&st=' . $selectType . '&sa=' . $selectAccess . '&sc=' . $selectCategory . '&so=' . $selectOrder . '&sn=' . $selectName . '&sg=' . $selectTags . '&sp=' . $selectPage . '&id=' . $id;

		if ($author_id) $ajaxurl .= '&author_id=' . $author_id;

		// without page: changing goes to page 1 but selection persists
		$ajaxurlC = $ajaxurl . '&ttype=' . $tabType . '&tacc=' . $tabAccess . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // sel ord
		$ajaxurlO = $ajaxurl . '&ttype=' . $tabType . '&tacc=' . $tabAccess . '&ob=' . $order_by . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); // sel cat

		$ajaxurlCO = $ajaxurl . '&ttype=' . $tabType . '&tacc=' . $tabAccess . '&cat=' . $category . '&ob=' . $order_by; // select tag name

		$ajaxurlA = $ajaxurl . '&ttype=' . $tabType . '&tacc=' . $tabAccess . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); //reload, page


		$ajaxurlT = $ajaxurl . '&tacc=' . $tabAccess . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); //type
		$ajaxurlAC = $ajaxurl . '&ttype=' . $tabType . '&cat=' . $category . '&ob=' . $order_by . '&tags=' . urlencode( $tags ) . '&name=' . urlencode( $name ); //access

//start tabs
if ( $tabs && ( $selectType || $selectAccess) ) {

echo '<div class="ui tabular menu small stackable">';

	if ($selectType)
	{
	echo '<a class="' . ( $tabType ? '' : 'active' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'Popular', 'paid-membership' ) . '</a> ';

	if ( class_exists( 'VWvideoShare' ) )  echo '<a class="' . ( $tabType == 'all' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=' . 'all'  . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'All', 'paid-membership' ) . '</a> ';

	if ( class_exists( 'VWvideoShare' ) )  echo '<a class="' . ( $tabType == 'video' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=' . 'video'  . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'Videos', 'paid-membership' ) . '</a> ';

	if ( class_exists( 'VWpictureGallery' ) )  echo '<a class="' . ( $tabType == 'picture' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=' . 'picture'  . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'Pictures', 'paid-membership' ) . '</a> ';

	if ( class_exists( 'WooCommerce' ) ) echo '<a class="' . ( $tabType == 'product' ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=' . 'product'  . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'Products', 'paid-membership' ) . '</a> ';
	}

	//right access menu
	if ($selectAccess)
	{
	echo '<div class="right menu">';

		foreach ( [  __( 'premium', 'paid-membership' ), __( 'subscription', 'paid-membership' ), __( 'paid', 'paid-membership' ), __( 'free', 'paid-membership' ) ] as $access ) {
			echo '<a class="' . ( $tabAccess == $access? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlAC ) . '&tacc=' . esc_attr( $access ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . esc_html( ucwords($access) ) . '</a> ';
		}

		echo '<a class="' . ( $tabAccess ? '' : 'active' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlAC ) . '&tacc=\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading content', 'paid-membership' ) . '...</div>\')">' . __( 'Any', 'paid-membership' ) . '</a> ';
	echo '</div>';
	}

echo '</div>';

} 

//start menu
if ( $menu ) {
			echo '
<style>
	.vwItemsSidebar {
    grid-area: sidebar;
  }

  .vwItemsContent {
    grid-area: content;
  }

.vwItemsWrapper {
    display: grid;
    grid-gap: 4px;
    grid-template-columns: auto 120px;
    grid-template-areas: "content sidebar";
    color: #444;
  }

  .ui .title { height: auto !important; background-color: inherit !important}
  .ui .content {margin: 0 !important; }
  .vwItemsSidebar .accordion {padding: 0 !important; }
  .vwItemsSidebar .menu { max-width: 120px !important;}

  /* Hide sidebar on narrow screens */
@media (max-width: 768px) {
    .vwItemsSidebar {
        display: none;
    }

    /* Adjust the grid-template-areas to only show the content area on narrow screens */
    .vwItemsWrapper {
        grid-template-columns: auto; /* Use a single column layout */
        grid-template-areas: "content"; /* Only display the content area */
    }
}

 </style>
 <div class="vwItemsWrapper">
 <div class="vwItemsSidebar">';

			if ( $selectCategory ) {
				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small basic compact segment">

  <div class="active title">
    <i class="dropdown icon"></i>
    ' . __( 'Category', 'paid-membership' ) . ' ' . ( esc_html( $category ) ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="active content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small compact">
  ';
				echo '  <a class="' . ( $category == 0 ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlO ) . '&cat=0\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'paid-membership' ) . '...</div>\')">' . __( 'All Categories', 'paid-membership' ) . '</a> ';

				$categories = get_categories( array( 'taxonomy' => 'category' ) );
				foreach ( $categories as $cat ) {
					echo '  <a class="' . ( $category == $cat->term_id ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_html( $ajaxurlO ) . '&cat=' . esc_attr( $cat->term_id ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Loading category', 'paid-membership' ) . '...</div>\')">' . esc_html( $cat->name ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';
			}

			if ( $selectOrder ) {

				$optionsOrders = array(
					'post_date'  => __( 'Added Recently', 'paid-membership' ),
					'rand'       => __( 'Random', 'ppv-live-webcams' ),
				);

				if ( $options['rateStarReview'] ) {
					$optionsOrders['rateStarReview_rating']       = __( 'Rating', 'paid-membership' );
					$optionsOrders['rateStarReview_ratingNumber'] = __( 'Ratings Number', 'paid-membership' );
					$optionsOrders['rateStarReview_ratingPoints'] = __( 'Rate Popularity', 'paid-membership' );

					if ( $category ) {
						$optionsOrders[ 'rateStarReview_rating_category' . $category ]       = __( 'Rating', 'paid-membership' ) . ' ' . __( 'in Category', 'paid-membership' );
						$optionsOrders[ 'rateStarReview_ratingNumber_category' . $category ] = __( 'Ratings Number', 'paid-membership' ) . ' ' . __( 'in Category', 'paid-membership' );
						$optionsOrders[ 'rateStarReview_ratingPoints_category' . $category ] = __( 'Rate Popularity', 'paid-membership' ) . ' ' . __( 'in Category', 'paid-membership' );
					}
				}

				echo '
<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' accordion small basic compact segment">

  <div class="title ' . esc_attr( $options['interfaceClass'] ) . '">
    <i class="dropdown icon"></i>
    ' . __( 'Order By', 'paid-membership' ) . ' ' . ( $order_by != 'default' ? '<i class="check icon small"></i>' : '' ) . '
  </div>
  <div class="' . ( $order_by != 'default' ? 'active' : '' ) . ' content">
  <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' vertical menu small">
  ';

				foreach ( $optionsOrders as $key => $value ) {
					echo '  <a class="' . ( $order_by == $key ? 'active' : '' ) . ' item" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlC ) . '&ob=' . esc_attr( $key ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>' . __( 'Ordering Rooms', 'paid-membership' ) . '...</div>\')">' . esc_html( $value ) . '</a> ';
				}

				echo '</div>

  </div>
</div>';

			}

			echo '
<PRE style="display: none"><SCRIPT language="JavaScript">
jQuery(document).ready(function()
{
jQuery(".ui.accordion").accordion({exclusive:false});
});
</SCRIPT></PRE>
';
			echo '</div><div class="vwItemsContent">';
		}

		//end menu

		// options
		// echo '<div class="videowhisperListOptions">';
		// $htmlCode .= '<div class="ui form"><div class="inline fields">';

		echo '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' tiny equal width form"><div class="inline fields tiny">';


 if (!$tabs)
 {
	//display tab options as dropdowns 

	if ( $selectType ) {
	echo '<div class="field">';
	echo '<select class="ui dropdown v-select fluid" id="ttype' . esc_attr( $id ) . '" name="ttype' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlT ) . '&ttype=\'+ this.value; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading content...</div>\')">';
	echo '<option value="">' . __( 'Type', 'paid-membership' ) . '</option>';
	echo '<option value="all"' . ( $tabType == 'all' ? ' selected' : '' ) . '>' . __( 'All', 'paid-membership' ) . '</option>';
	if ( class_exists( 'VWvideoShare' ) )  echo '<option value="video"' . ( $tabType == 'video' ? ' selected' : '' ) . '>' . __( 'Videos', 'paid-membership' ) . '</option>';
	if ( class_exists( 'VWpictureGallery' ) ) echo '<option value="picture"' . ( $tabType == 'picture' ? ' selected' : '' ) . '>' . __( 'Pictures', 'paid-membership' ) . '</option>';
	if ( class_exists( 'WooCommerce' ) ) echo '<option value="product"' . ( $tabType == 'product' ? ' selected' : '' ) . '>' . __( 'Products', 'paid-membership' ) . '</option>';
	echo '</select>';
	echo '</div>';
	}

	if ( $selectAccess ) {
	echo '<div class="field">';
	echo '<select class="ui dropdown v-select fluid" id="tacc' . esc_attr( $id ) . '" name="tacc' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url( $ajaxurlAC ) . '&tacc=\'+ this.value; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading content...</div>\')">';
	echo '<option value="">' . __( 'Access', 'paid-membership' ) . '</option>';
	foreach ( [  __( 'premium', 'paid-membership' ), __( 'subscription', 'paid-membership' ), __( 'paid', 'paid-membership' ), __( 'free', 'paid-membership' ) ] as $access ) {
		echo '<option value="' . esc_attr( $access ) . '"' . ( $tabAccess == $access ? ' selected' : '' ) . '>' . esc_html( ucwords($access) ) . '</option>';
	}
	echo '<option value="">' . __( 'Any', 'paid-membership' ) . '</option>';
	echo '</select>';
	echo '</div>';
	}
}


		if ( $selectCategory && ! $menu ) {
			echo '<div class="field">' . wp_dropdown_categories( 'show_count=0&echo=0&name=category' . esc_attr( $id ) . '&hide_empty=1&class=ui+dropdown+fluid+v-select&show_option_all=' . __( 'All', 'paid-membership' ) . '&selected=' . esc_attr( $category ) ) . '</div>';
			echo '<script>var category' . esc_attr( $id ) . ' = document.getElementById("category' . esc_attr( $id ) . '"); 			category' . esc_attr( $id ) . '.onchange = function(){aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlO ) . '&cat=\'+ this.value; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading category...</div>\')}
			</script>';
		}

		if ( $selectOrder && ! $menu ) {
			echo '<div class="field"><select class="ui dropdown v-select fluid" id="order_by' . esc_attr( $id ) . '" name="order_by' . esc_attr( $id ) . '" onchange="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlC ) . '&ob=\'+ this.value; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Ordering content...</div>\')">';
			echo '<option value="">' . __( 'Order By', 'paid-membership' ) . ':</option>';
			echo '<option value="post_date"' . ( $order_by == 'post_date' ? ' selected' : '' ) . '>' . __( 'Date Added', 'paid-membership' ) . '</option>';

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
			echo '<div class="field" data-tooltip="Search by Tags and/or Name"><button class="ui icon button tiny" type="submit" name="submit" id="submit" value="' . __( 'Search', 'paid-membership' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlCO ) . '&tags=\' + document.getElementById(\'tags\').value +\'&name=\' + document.getElementById(\'name\').value; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Searching content...</div>\')"><i class="search icon"></i></button></div>';
		}

		// reload button
		if ( $selectCategory || $selectOrder || $selectTags || $selectName ) {
			echo '<div class="field"></div> <div class="field" data-tooltip="Reload"><button class="ui icon button tiny" type="submit" name="reload" id="reload" value="' . __( 'Reload', 'paid-membership' ) . '" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Reloading content...</div>\')"><i class="sync icon"></i></button></div>';
		}
		echo '</div></div>';

		// list CONTENT
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

				// $views = get_post_meta($item->ID, 'download-views', true) ;
				// if (!$views) $views = 0;

				$age = self::humanAge( time() - strtotime( $item->post_date ) );

				$info = '' . __( 'Title', 'paid-membership' ) . ': ' . esc_html( $item->post_title ) . "\r\n" . __( 'Age', 'paid-membership' ) . ': ' . esc_html( $age );
				// $views .= ' ' . __('views', 'paid-membership');

				$canEdit = 0;
				if ( $options['editContent'] ?? false ) {
					if ( $isAdministrator || ( $isID && $item->post_author == $isID ) ) {
						$canEdit = 1;
					}
				}

				//preview v

				$imageURL = get_the_post_thumbnail_url( $item->ID, 'post-thumbnail' );
				if ( ! $imageURL ) {
					$imageURL = plugin_dir_url( dirname( __FILE__ ) ) . 'default_picture.png';
				};
				$previewCode = '<IMG class="videowhisperPreviewImg" src="' . esc_url_raw( $imageURL ) . '" width="' . intval( $options['thumbWidth'] ) . 'px" height="' . intval( $options['thumbHeight'] ) . 'px" ALT="' . esc_attr( $info ) . '">';

						// get preview video
						$previewVideo  = '';

						if (!$isMobile)
						{

						$videoID = $item->ID;

						//PaidVideochat
						$video_teaser = get_post_meta(  $item->ID, 'video_teaser', true );
						if ($video_teaser) $videoID = $video_teaser;

						//Video Share VOD
						$videoAdaptive = get_post_meta( $videoID, 'video-adaptive', true );
						if ( is_array( $videoAdaptive ) ) 
							if ( array_key_exists( 'preview', $videoAdaptive ) ) 
								if ( $videoAdaptive['preview'] ?? false) 
									if ( $videoAdaptive['preview']['file'] ?? false) 
										if ( file_exists( $videoAdaptive['preview']['file'] ) ) {
											$previewVideo = $videoAdaptive['preview']['file'];
										} 
						
						if ($previewVideo)	$previewCode = '<video class="videowhisperPreviewVideo" muted poster="' . $imageURL . '" preload="none"><source src="' . self::path2url( $previewVideo ) . '" type="video/mp4">' . $previewCode . '</video>';

						}
													
				echo '<div class="videowhisperContent">';
				echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '"><div class="videowhisperContentTitle">' . esc_html( wp_trim_words( $item->post_title, 5) ) . '</div></a>';

				echo '<div class="videowhisperContentType">' . ucwords( esc_html( $item->post_type ) ) . '</div>';

				echo '<div class="videowhisperContentDate">' . esc_html( $age ) . '</div>';
				// echo '<div class="videowhisperContentViews">' . $views . '</div>';

				$data  = self::contentData( $item->ID, $options );
				$price = floatval( $data['price'] );
				if ( $price ) {
					echo '<div class="videowhisperContentPrice"><div class ="ui tag label tiny ' . ( $price ? 'red' : 'green' ) . '">' . ( $price ? '<i class="money bill alternate icon"></i> ' . esc_html( $data['price'] ) : 'Free' ) . '</div></div>';
				}

				if ( $data['subscription_tier'] ) {
					echo '<div class="videowhisperContentSubscription"><div class ="ui tag label tiny orange"> <i class="lock icon"></i> ' . __( 'Subscription', 'paid-membership' ) . '</div></div>';
				}
				$ratingCode = '';
				if ( $options['rateStarReview'] ) {
					$rating = floatval( get_post_meta( $item->ID, 'rateStarReview_rating', true ) );
					$max    = 5;
					// if ($rating > 0) $ratingCode = ''; // . number_format($rating * $max,1)  . ' / ' . $max
					if ( $rating > 0 ) {
						echo '<div class="videowhisperContentRating"><div class="ui yellow star rating readonly" data-rating="' . round( $rating * $max ) . '" data-max-rating="' . esc_attr( $max ) . '"></div></div>';
					}
				}

				if ( $pmEnabled && $canEdit ) {
					echo '<a href="' . add_query_arg( 'editID', intval( $item->ID ), get_permalink( $options['p_videowhisper_content_edit'] ) ) . '"><div class="videowhisperContentEdit"><i class="edit icon"></i> ' . __( 'Edit', 'paid-membership' ) . '</div></a>';
				}

				echo '<a href="' . get_permalink( $item->ID ) . '" title="' . esc_attr( $info ) . '">' . $previewCode. '</a>';

				echo '</div>
					';

				$k++;
			}
		} else {
			echo __( 'No content.', 'paid-membership' );
		}

		// pagination
		if ( $selectPage ) {
			echo '<BR style="clear:both"><div class="ui form"><div class="inline fields">';

			if ( $page > 0 ) {
				echo ' <a class="ui labeled icon button" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '&p=' . intval( $page - 1 ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading previous page...</div>\'); "><i class="left arrow icon"></i> ' . __( 'Previous', 'paid-membership' ) . '</a> ';
			}

			echo '<a class="ui labeled button" href="#"> ' . __( 'Page', 'paid-membership' ) . ' ' . intval( $page + 1 ) . ' </a>';

			if ( count( $postslist ) >= $perPage ) {
				echo ' <a class="ui right labeled icon button" href="JavaScript: void()" onclick="aurl' . esc_attr( $id ) . '=\'' . esc_url_raw( $ajaxurlA ) . '&p=' . intval( $page + 1 ) . '\'; loadContent' . esc_attr( $id ) . '(\'<div class=\\\'ui active inline text large loader\\\'>Loading next page...</div>\'); ">' . __( 'Next', 'paid-membership' ) . ' <i class="right arrow icon"></i></a> ';
			}
		}

		//close layout with menu
		if ( $menu ) echo '</div></div>';

		echo '
		<PRE style="display: none"><SCRIPT language="JavaScript">
		jQuery(document).ready(function()
		{
	
		var hHandlers = jQuery(".videowhisperContent").hover( hoverVideoWhisperContent, outVideoWhisperContent );
		
		function hoverVideoWhisperContent(e) {
		   var vid = jQuery(\'video\', this).get(0);
		   if (vid) vid.play();
		}
		
		function outVideoWhisperContent(e) {
			 var vid = jQuery(\'video\', this).get(0);
			 if (vid) vid.pause();
		}
		});
		</SCRIPT></PRE>
		';
		echo self::scriptThemeMode($options);

		// output end
		die;
	}


static function scriptThemeMode($options)
{
	$theme_mode = '';
	
	//check if using the FansPaysite theme and apply the dynamic theme mode
	if (function_exists('fanspaysite_get_current_theme_mode')) $theme_mode = fanspaysite_get_current_theme_mode();
	else $theme_mode = '';

	if (!$theme_mode) $theme_mode = $options['themeMode'] ?? '';

	if (!$theme_mode) return '<!-- No theme mode -->';

	// JavaScript function to apply the theme mode
	return '<script>
	if (typeof setConfiguredTheme !== "function")  // Check if the function is already defined
	{ 

		function setConfiguredTheme(theme) {
			if (theme === "auto") {
				if (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) {
					document.body.dataset.theme = "dark";
				} else {
					document.body.dataset.theme = "";
				}
			} else {
				document.body.dataset.theme = theme;
			}

			if (document.body.dataset.theme == "dark")
			{
			jQuery("body").find(".ui").addClass("inverted");
			jQuery("body").addClass("inverted");
			}else
			{
				jQuery("body").find(".ui").removeClass("inverted");
				jQuery("body").removeClass("inverted");
			}

			console.log("MicroPayments/setConfiguredTheme:", theme);
		}
	}	

	setConfiguredTheme("' . esc_js($theme_mode) . '");

	</script>';
}


	// subscribe to authors

	static function subscription_terminate( $clientID, $authorID ) {
		delete_user_meta( $clientID, 'vw_client_subscription_' . $authorID );

		$client_subscriptions = get_user_meta( $clientID, 'vw_client_subscriptions', true );
		if ( ! is_array( $client_subscriptions ) ) {
			$client_subscriptions = array();
		}
		unset( $client_subscriptions[ $authorID ] );
		update_user_meta( $clientID, 'vw_client_subscriptions', $client_subscriptions );

		$provider_subscribers = get_user_meta( $authorID, 'vw_provider_subscribers', true );
		if ( ! is_array( $provider_subscribers ) ) {
			$provider_subscribers = array();
		}
		unset( $provider_subscribers[ $clientID ] );
		update_user_meta( $authorID, 'vw_provider_subscribers', $provider_subscribers );

		// buddypress/buddyboss friendship
		if ( function_exists( 'friends_remove_friend' ) ) {
			friends_remove_friend( $clientID, $authorID );
		}
	}


	static function packages_process( $customer_id = 0, $verbose = 0 ) {
		// process all client token package purchases

		if ( ! function_exists( 'wc_get_order_types' ) || ! function_exists( 'wc_get_orders' ) || ! function_exists( 'wc_get_products' ) ) {
			return 'WooCommerce functions not detected!';
		}

		$htmlCode = '';

		$packages = wc_get_products(array(
			'limit'        => -1, // Retrieves all products
			'status'       => 'publish',
			'type'         => 'simple', // Add if you are looking for a specific type of product, e.g., 'simple', 'variable', etc.
			'meta_key'     => 'micropayments_tokens', // Specify the meta key to check for existence.
			'meta_value'   => '', // Required if 'meta_compare' is used.
			'meta_compare' => 'EXISTS' // Ensures only products with this meta key are returned.
		));

		$packageIDs    = array();
		$packageTokens = array();

		if ( $packages ) {
			foreach ( $packages as $package ) {
				$packageID =  $package->get_id();
				$packageIDs[]                    = $packageID;
				$packageTokens[$packageID ]   = $package->get_meta('micropayments_tokens');
				$packageProducts[ $packageID ] = $package;
			//	if ($verbose) $htmlCode .= 'Package #'  . $packageID . ' '. $package->get_name() . ' = ' . $packageTokens[ $packageID ] . 'tokens, ';
			//	if ($verbose) var_dump($package);
			}
		} else {
			return 'No token packages.';
			if ($verbose) $htmlCode .= ' Query Args: '. json_encode($args);
		}

		$htmlCode .= "\n" . count( $packageIDs ) . ' Package IDs: ' . implode( ',', $packageIDs ) .  "\n" . ' Orders: ';

		// GET USER ORDERS completed
		$args = array(
			'limit'        => -1, // Get all orders
			'orderby'      => 'date',
			'order'        => 'DESC',
			/*
			'meta_query'  => array(
				array(
					'key'     => 'micropayments_processed',
					'compare' => 'NOT EXISTS',
				),
			),*/
			// Only add customer user meta query if $customer_id is set
			'post_type'   => 'shop_order',
			'post_status' => 'wc-completed',
		);

		if ( $customer_id ) {
			// If customer ID is set, filter by customer ID
		    $args['customer'] = $customer_id;

			/*
			$args['meta_query'][] = array(
				'key'   => '_customer_user',
				'value' => $customer_id,
			);
			*/
		}

		$customer_orders = wc_get_orders( $args );

		// Filter orders to find those without 'micropayments_processed' meta key
		$customer_orders = array_filter($customer_orders, function ($order) {
			return !$order->get_meta('micropayments_processed', true);
		});

		// LOOP THROUGH ORDERS AND GET PRODUCT IDS
		if ( $customer_orders && is_array( $customer_orders ) ) {
			foreach ( $customer_orders as $customer_order ) {
				$order_id =  $customer_order->get_id(); //$customer_order->ID;
				$order   = wc_get_order( $order_id );
				$user_id = $order->get_user_id(); //$order->user_id;

				if ($verbose) $htmlCode .= 'Order #' . $order_id . ' by user #' . $user_id . ' ' ;

				if ( $order->get_meta('micropayments_processed') )
				{
					if ($verbose) $htmlCode .= ' = skipped because already processed! ';
					break;
				}

				if (!$user_id)
				{
					$htmlCode .= 'Unable to process: No user id for order #' . $order_id . ' ';
					$order->update_meta_data( 'micropayments_processed', 1 ); // mark order as processed by micropayments
					$order->update_meta_data( 'micropayments_skipped', 'no user id' ); // mark as skipped due to error (no user id)
					$order->save();
					break;
				}

				$items = $order->get_items();
				if ($verbose) $htmlCode .= 'contains ' . count( $items ) . ' order items: ';

				foreach ( $items as $item ) {
					$product_name = $item->get_name();
    				$product_id = $item->get_product_id();
					if ($verbose) $htmlCode .= 'Item #' . $item->get_id() . ' ' . $product_name . ' #' . $product_id . ', ';

					if ( $product_id && in_array( $product_id, $packageIDs ) ) {     // if product is package
						$tokens     = $packageTokens[ $product_id ];
						$product    = $packageProducts[ $product_id ];

						self::transaction( 'micropayments_package_order', $user_id, $tokens, __( 'Tokens Package', 'paid-membership' ) . ': <a href="' . $product->get_permalink() . '">' . esc_html( $product_name ) . '</a> ' . __( 'Order', 'paid-membership' ) . ' #' . $order_id , null, $product_id );
						if ($verbose) $htmlCode .= 'Transaction: ' . $tokens . 'tokens allocated for user #' . $user_id . '. ';
						$order->update_meta_data( 'micropayments_processed', 1 ); // mark order as processed by micropayments
						$order->save();

					} else {
						if ($verbose) $htmlCode .= 'No package for product #' . $product_id . ', ';
						$order->update_meta_data( 'micropayments_processed', 1 ); // mark order as processed by micropayments
						$order->update_meta_data( 'micropayments_skipped', 'no package' ); // mark as skipped due to error (not package)
						$order->save();
					}
				}

				//update_post_meta( $customer_order->ID, 'micropayments_processed', 1 );
			}
		} else {
			$htmlCode .= 'No orders to process.';
			if ($verbose) $htmlCode .= "\n" . 'Query args: '. json_encode($args);
		}

		return $htmlCode;
	}


	static function subscriptions_process() {
		// process all client subscriptions

		$options = get_option( 'VWpaidMembershipOptions' );

		$htmlCode = 'Process subscriptions: ';

		$args = array(
			'meta_key'     => 'vw_client_subscriptions',
			'meta_compare' => '>',
		);

		$users = get_users( $args );

		if ( $users ) {
			if ( is_array( $users ) ) {
				if ( count( $users ) ) {
					$htmlCode .= 'Users with subscriptions: ' . count( $users );
					foreach ( $users as $user ) {
						$htmlCode .= self::subscriptions_process_user( $user->ID, $options );
					}
				}
			}
		}

		if ( ! $users ) {
			$htmlCode .= 'No users subscribed, yet.';
		}

		return $htmlCode;
	}


	static function subscriptions_process_user( $clientID, $options = null ) {
		// process subscriptions for client

		if ( ! $options ) {
			$options = get_option( 'VWpaidMembershipOptions' );
		}

		$htmlCode = ' (c' . $clientID;

		$client_subscriptions = get_user_meta( $clientID, 'vw_client_subscriptions', true );
		if ( ! is_array( $client_subscriptions ) ) {
			return $htmlCode;
		}

		foreach ( $client_subscriptions as $authorID => $subscription ) {
			$htmlCode .= ' a' . $authorID;

			if ( $subscription['duration'] == 0 ) {
				break; // one time, no need to process
			}

			$duration = $subscription['duration'] * 86400; // days to seconds
			if ( $subscription['last'] + $duration > time() ) {
				break; // due date later, not yet
			}

			// if ( in_array($subscription['status'], ['cancelled', 'failed']) ) break;

			if ( $subscription['status'] == 'cancel' ) {
				self::subscription_terminate( $clientID, $authorID );
				$htmlCode .= 'cancel';
				// $client_subscriptions[$authorID]['status'] = 'cancelled';
				break;
			}

			if ( self::balance( $clientID ) < $subscription['price'] ) {
				self::subscription_terminate( $clientID, $authorID );
				$htmlCode .= 'fail';
				// $client_subscriptions[$authorID]['status'] = 'failed';
				break;
			}

			// apply new charge
			self::transaction( 'micropayments_subscription', $clientID, - $subscription['price'], 'Subscription renewal for' . ' ' . $author->display_name, null, $subscription );

			$revenue = $subscription['price'] * $options['subscriptionRatio'];
			self::transaction( 'micropayments_subscription_revenue', $authorID, $revenue, 'Subscription renewal from' . ' ' . $client->display_name, null, $subscription );

						$subscription['last'] = time();
			$subscription['totalCharge']     += $subscription['price'];
			$subscription['totalRevenue']    += $revenue;

			$client_subscriptions[ $authorID ] = $subscription;

			$htmlCode .= 'renew';
			// ($subscription['last'] + $duration) . '/' . time() . '/' . $duration
			// var_dump($subscription);
		}

		$htmlCode .= ')';

		update_user_meta( $clientID, 'vw_client_subscriptions', $client_subscriptions );

		return $htmlCode;
	}


	static function subscription_new( $clientID, $authorID, $tier, $recurring = 1 ) {
		$options = get_option( 'VWpaidMembershipOptions' );

		$htmlCode = '';

		$subscriptions = get_user_meta( $authorID, 'vw_provider_subscriptions', true );
		if ( ! is_array( $subscriptions ) ) {
			return 'No subscriptions available!';
		}
		if ( ! array_key_exists( $tier, $subscriptions ) ) {
			return 'Tier does not exist!';
		}

		$author = get_userdata( $authorID );
		if ( ! $author ) {
			return 'Author does not exist!';
		}
		$client = get_userdata( $clientID );
		if ( ! $client ) {
			return 'Client does not exist!';
		}

		if ( get_user_meta( $clientID, 'vw_client_subscription_' . $authorID, true ) ) {
			return '<div class="ui orange message">' . __( 'Already subscribed!', 'paid-membership' ) . '</div>';
		}

		$subscription = $subscriptions[ $tier ];
		if ( self::balance( $clientID ) < $subscription['price'] ) {
			return '<div class="ui orange message">' . __( 'Not enough funds!', 'paid-membership' ) . '</div>';
		}

		// apply first charge
		self::transaction( 'micropayments_subscription_new', $clientID, - $subscription['price'], __( 'Subscribed to', 'paid-membership' ) . ' ' . $author->display_name, null, $subscription );

		$revenue = $subscription['price'] * $options['subscriptionRatio'];
		self::transaction( 'micropayments_subscription_new_revenue', $authorID, $revenue, __( 'New subscription from', 'paid-membership' ) . '  ' . $client->display_name, null, $subscription );

		// setup subscription
		update_user_meta( $clientID, 'vw_client_subscription_' . $authorID, $tier );

		// add to client subscriptions list
		$client_subscriptions = get_user_meta( $clientID, 'vw_client_subscriptions', true );
		if ( ! is_array( $client_subscriptions ) ) {
			$client_subscriptions = array();
		}
		$subscription['start']        = time();
		$subscription['last']         = time();

		if ($recurring) $subscription['status']       = 'active';
		else $subscription['status']       = 'cancel';

		$subscription['totalCharge']  = $subscription['price'];
		$subscription['totalRevenue'] = $revenue;

		$client_subscriptions[ $authorID ] = $subscription;
		update_user_meta( $clientID, 'vw_client_subscriptions', $client_subscriptions );

		// add to provider subscribers list
		$provider_subscribers = get_user_meta( $authorID, 'vw_provider_subscribers', true );
		if ( ! is_array( $provider_subscribers ) ) {
			$provider_subscribers = array();
		}
		$provider_subscribers[ $clientID ] = $tier;
		update_user_meta( $authorID, 'vw_provider_subscribers', $provider_subscribers );

		// BuddyPress Activity
		if ( function_exists( 'bp_activity_add' ) ) {
			$user = get_userdata( $post->post_author );

			$args = array(
				'action'       => '<a href="' . bp_members_get_user_url( $clientID ) . '">' . sanitize_text_field( $user->display_name ) . '</a> ' . __( 'subscribed to', 'paid-membership' ) . ' <a href="' . bp_members_get_user_url( $authorID ) . '">' . sanitize_text_field( $author->display_name ) . '</a> / ' . sanitize_text_field( $subscription['name'] ),
				'component'    => 'micropayments',
				'type'         => 'subscription_new',
				'primary_link' => bp_members_get_user_url( $authorID ),
				'user_id'      => $clientID,
				'item_id'      => $authorID,
				'content'      => '',
			);

			$activity_id = bp_activity_add( $args );
		}

		// buddypress/buddyboss friendship
		if ( function_exists( 'friends_add_friend' ) ) {
			friends_add_friend( $clientID, $authorID, true );
		}

		return '<div class="ui success message">' . __( 'Subscription was setup.', 'paid-membership' ) . '</div>';

		return $htmlCode;
	}

	static function subscription_cancel( $clientID, $authorID )
	{

			$options = get_option( 'VWpaidMembershipOptions' );

			$htmlCode = '';

			$author = get_userdata( $authorID );
			if ( ! $author ) {
				return 'Author does not exist!';
			}
			$client = get_userdata( $clientID );
			if ( ! $client ) {
				return 'Client does not exist!';
			}

			$client_subscription = '';

			$client_subscriptions = get_user_meta( $clientID, 'vw_client_subscriptions', true );

			if ( is_array( $client_subscriptions ) )
				if ( array_key_exists( $authorID, $client_subscriptions ) )
					$client_subscription = $client_subscriptions[ $authorID ];

			if ( !$client_subscription ) return '<div class="ui orange message">' . __( 'Subscription not found!', 'paid-membership' ) . '</div>';

	//update subscription
			$client_subscription['status'] = 'cancel';
			$client_subscription['cancelDate'] = time();

			$client_subscriptions[$authorID] = $client_subscription;
			update_user_meta( $clientID, 'vw_client_subscriptions', $client_subscriptions );	//update client subscriptions

			return '<div class="ui message">' . __('Renewal was cancelled.', 'paid-membership') . '</div>';
	}

	// Shortcodes

	static function videowhisper_client_subscriptions( $atts )
	{

		self::enqueueUI();

		//list client subscriptions

		$atts = shortcode_atts(
			array(
				'client_id'    => get_current_user_id(),
			),
			$atts,
			'videowhisper_client_subscriptions'
		);


		$userID = intval($atts['client_id']);

		if (!$userID) return '<i class="lock icon"></i>' . __( 'Login to Manage!', 'paid-membership' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';

		$htmlCode = '';

		$client = get_userdata( $userID );
		if (!$client)
		{
			$htmlCode .= '<div class="ui red message"> videowhisper_client_subscriptions: user #' . $userID . ' not specified or found </div>';
			return $htmlCode;
		}

			$client_subscriptions = get_user_meta( $userID, 'vw_client_subscriptions', true );
			if ( is_array( $client_subscriptions ) ) {

						$this_page = self::getCurrentURL();

				foreach ( $client_subscriptions as $authorID => $client_subscription)  {

					$author = get_userdata( $authorID );
					if (!$author){
							$htmlCode .= '<div class="ui red message"> Author #' . $authorID . ' not found </div>';
							break;
					}

					$client_tier         = intval( get_user_meta( $userID, 'vw_client_subscription_' . $authorID, true ) );

					$manageCode = '<br>' . __('Until', 'paid-membership') . ' '. date("F j, Y, g:i a", $client_subscription['last'] + $client_subscription['duration'] * 86400) . '. ' .  ( $client_subscription['status'] == 'active' ? ' <div class="ui label">' . __('Recurring', 'paid-membership') . '</div> <a class="ui small button" href="' . wp_nonce_url(
					add_query_arg(
						array(
							'creator' => $author->user_login,
							'cancel_tier'   => $client_tier,
						),
						$this_page
					), 'cancel' . $authorID,
					'videowhisper'
				) . '"> <i class="close icon"></i> ' . __( 'Cancel', 'paid-membership' ) . ' </a>' : '<div class="ui label">' . __('One Time', 'paid-membership') . '</div>' );

				$userCode = $author->display_name;
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			$userCode = '<a href="' . bp_members_get_user_url( $authorID ) . '"  title="' . bp_core_get_user_displayname( $authorID ) . '"> ' . bp_core_fetch_avatar(
				array(
					'item_id' => $authorID,
					'type'    => 'full',
					'class'   => 'ui middle aligned small rounded image',
				)
			) . ' ' . $author->display_name . ' </a>';
		}


					$htmlCode .= '<div class="ui message" ><div class="header">' . $userCode . ' : ' . esc_html( $client_subscription['name'] ) . ( $client_tier > 1 ? ' <div class="ui small label">' . __( 'tier', 'paid-membership' ) . ' ' . $client_tier . '</div>' : '' ) . ' <div class="ui green tag label">' . esc_html( $client_subscription['price'] ) . esc_html( $options['currency'] ) . ' ' . self::durationLabel( $client_subscription['duration'] ) . '</div></div> <div>' . htmlspecialchars( $client_subscription['description'] ) . $manageCode .  '</div> </div>';
				}
			}
			else $htmlCode .= '<div class="ui message"> <i class="lock icon"></i> ' . __( 'You have no subscriptions, yet. If you subscribe to any creators, your subscriptions will show here.', 'paid-membership' ) . '</div>';

	return $htmlCode;


	}


	static function videowhisper_client_subscribe( $atts ) {
		// client subscribes to provider

		$atts = shortcode_atts(
			array(
				'author_id'    => '',
				'author_login' => '',
			),
			$atts,
			'videowhisper_client_subscribe'
		);

		$options = get_option( 'VWpaidMembershipOptions' );

		// if (!is_user_logged_in()) return '<i class="gift icon"></i>' . __('Login to Subscribe!','paid-membership') . '<BR><a class="ui button qbutton" href="' . wp_login_url() . '">' . __('Login', 'paid-membership') . '</a>  <a class="ui button qbutton" href="' . wp_registration_url() . '">' . __('Register', 'paid-membership') . '</a>';

		if ( $atts['author_id'] ) {
			$author = get_userdata( intval( $atts['author_id'] ) );
		}
		if ( ! $author ) {
			if ( $atts['author_login'] ) {
				$author = get_user_by( 'login', sanitize_text_field( $atts['author_login'] ) );
			}
		}

		if ( ! $author ) {
			if ( $_GET['author'] ) {
				$author = get_user_by( 'login', sanitize_text_field( $_GET['author'] ) );
			}
		}

		if ( ! $author ) {
			if ( $_GET['creator'] ) {
				$author = get_user_by( 'login', sanitize_text_field( $_GET['creator'] ) );
			}
		}


		self::enqueueUI();

		if ( ! $author ) {
			$htmlCode .= '<div class="ui red message"> videowhisper_client_subscribe: author not specified or found </div>';
			return $htmlCode;
		}

		$this_page = self::getCurrentURL();


		$htmlCode = '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment">';

		$userID = get_current_user_id();

		//new subscription
		$tier = intval( $_GET['tier'] ?? 0 );
		if ( $tier ) {
			if ( ! wp_verify_nonce( $_GET['videowhisper'], 'subscribe' . $author->ID ) ) {
				return 'Security Error: Incorrect verification nonce!';
			};

			if (! self::rolesUser( $options['rolesBuyer'], wp_get_current_user() ) ) return 'Buyer features are not enabled for your role!';

			if ( ! $userID ) {
				$htmlCode .= '<div class="ui error message">Login is required to subscribe!</div>';
			} else {
				$htmlCode .= self::subscription_new( $userID, $author->ID, $tier, intval( $_GET['recurring'] ) );
			}
		}

		//cancel subscription renewal
		$cancel_tier = intval( $_GET['cancel_tier'] ?? 0 );
		if ( $cancel_tier ) {
			if ( ! wp_verify_nonce( $_GET['videowhisper'], 'cancel' . $author->ID ) ) {
				return 'Security Error: Incorrect verification nonce!';
			};

		if ( ! $userID ) {
				$htmlCode .= '<div class="ui error message">Login is required to manage subscriptions!</div>';
			} else {
				$htmlCode .= self::subscription_cancel( $userID, $author->ID );
			}
		}

		$userCode = $author->display_name;
		if ( function_exists( 'bp_members_get_user_url' ) ) {
			$userCode = '<a href="' . bp_members_get_user_url( $author->ID ) . '"  title="' . bp_core_get_user_displayname( $author->ID ) . '"> ' . bp_core_fetch_avatar(
				array(
					'item_id' => $author->ID,
					'type'    => 'full',
					'class'   => 'ui middle aligned small rounded image',
				)
			) . ' ' . $author->display_name . ' </a>';
		}

		$htmlCode .= $userCode;

		if ( $userID ) {
			$client_subscriptions = get_user_meta( $userID, 'vw_client_subscriptions', true );
			if ( is_array( $client_subscriptions ) ) {
				if ( array_key_exists( $author->ID, $client_subscriptions ) ) {
					$client_subscription = $client_subscriptions[ $author->ID ];
					$client_tier         = intval( get_user_meta( $userID, 'vw_client_subscription_' . $author->ID, true ) );

					$manageCode = '<br>' . __('Until', 'paid-membership') . ' '. date("F j, Y, g:i a", $client_subscription['last'] + $client_subscription['duration'] * 86400) . '. ' .  ( $client_subscription['status'] == 'active' ? ' <div class="ui label">' . __('Recurring', 'paid-membership') . '</div> <a class="ui small button" href="' . wp_nonce_url(
					add_query_arg(
						array(
							'creator' => $author->user_login,
							'cancel_tier'   => $client_tier,
						),
						$this_page
					), 'cancel' . $author->ID,
					'videowhisper'
				) . '"> <i class="close icon"></i> ' . __( 'Cancel', 'paid-membership' ) . ' </a>' : '<div class="ui label">' . __('One Time', 'paid-membership') . '</div>' );

					$htmlCode .= '<div class="ui message" ><div class="header">My subscription: ' . esc_html( $client_subscription['name'] ) . ( $client_tier > 1 ? ' <div class="ui small label">' . __( 'tier', 'paid-membership' ) . ' ' . $client_tier . '</div>' : '' ) . ' <div class="ui green tag label">' . esc_html( $client_subscription['price'] ) . esc_html( $options['currency'] ) . ' ' . self::durationLabel( $client_subscription['duration'] ) . '</div></div> <div>' . htmlspecialchars( $client_subscription['description'] ) . $manageCode .  '</div> </div>';
				}
			}
		}

		$htmlCode .= ' <i class="lock icon"></i>' . __( 'Premium content can be accessed by subscription.', 'paid-membership' );

		$subscriptions = get_user_meta( $author->ID, 'vw_provider_subscriptions', true );

		if ( is_array( $subscriptions ) ) {
			foreach ( $subscriptions as $key => $subscription ) {
				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' message"> <div class="ui header"><b> ' . esc_html( $subscription['name'] ) . ' </b>' . ( $key > 1 ? ' <div class="ui small label"> ' . __( 'tier', 'paid-membership' ) . ' ' . $key . ' </div>' : '' ) . ' &nbsp; <div class="ui green tag small label"> <i class="unlock icon"></i>' . esc_html( $subscription['price'] ) . esc_html( $options['currency'] ) . ' ' . self::durationLabel( $subscription['duration'] ) . '</div> </div> <div>' . htmlspecialchars( $subscription['description'] ) . '</div>' . ( $subscription['duration']>0 ? ' <a class="ui button" href="' . wp_nonce_url(
					add_query_arg(
						array(
							'creator' => $author->user_login,
							'tier'   => $key,
							'recurring' => 1,
						),
						$this_page
					),
					'subscribe' . $author->ID,
					'videowhisper'
				) . '"> <i class="unlock icon"></i> ' . __( 'Subscribe', 'paid-membership' ) . ' ' . self::durationLabel( $subscription['duration'] )  .  ' </a>' : '' ). ' <a class="ui button" href="' . wp_nonce_url(
					add_query_arg(
						array(
							'creator' => $author->user_login,
							'tier'   => $key,
							'recurring' => 0,
						),
						$this_page
					),
					'subscribe' . $author->ID,
					'videowhisper'
				) . '"> <i class="unlock icon"></i> ' . __( 'Buy', 'paid-membership' ) . ' ' . self::durationLabel( $subscription['duration'], 0 ) . ' </a></div>';
			}
		}

		if ( is_array( $subscriptions ) ) {
			if ( count( $subscriptions ) > 1 ) {
				$htmlCode .= '<p>' . __( 'Higher tier subscriptions include access to content from lower tiers.', 'paid-membership' ) . '</p>';
			}
		}

		$htmlCode .= self::poweredBy() . '</div>';

		return $htmlCode;
	}


	static function videowhisper_provider_subscriptions( $atts ) {
		// providers manage subscription tiers

		$options = get_option( 'VWpaidMembershipOptions' );

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return '<i class="gift icon"></i>' . __( 'Login to Manage!', 'paid-membership' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$current_user = wp_get_current_user();

		if (! self::rolesUser( $options['rolesSeller'], $current_user ) )
		{
			$roles = '';
			foreach ( $current_user->roles as $role )
			{
				$roles .= esc_html( $role ) . ' ';
			}

			return 'Seller features not enabled for your role! ' . $roles;
		}

		$subscriptionMin = floatval( $options['subscriptionMin'] );
		$subscriptionMax = floatval( $options['subscriptionMax'] );
		$limitsInfo = $subscriptionMin . ( $subscriptionMax ? "-$subscriptionMax" : '+') ;

		$userID = get_current_user_id();

		$new_tiers = intval( $_POST['new_tiers'] ?? 0 );
		if ( $new_tiers ) {
			$new_subscriptions = array();
			for ( $i = 1; $i <= $new_tiers; $i++ ) {
				if ( ! isset( $_POST[ 'delete' . $i ] ) ) {
					$name = sanitize_text_field( $_POST[ 'name' . $i ] );
					if ( $name ) {
						$price = floatval( $_POST[ 'price' . $i ] );
						if ($price < $subscriptionMin) $price = $subscriptionMin;
						if ($subscriptionMax) if ($price > $subscriptionMax) $price = $subscriptionMax;

						$new_subscriptions[ $i ] = array(
							'name'        => $name,
							'price'       => $price,
							'duration'    => intval( $_POST[ 'duration' . $i ] ),
							'description' => wp_encode_emoji( sanitize_textarea_field( $_POST[ 'description' . $i ] ) ),
						);
					}
				}
			}
			update_user_meta( $userID, 'vw_provider_subscriptions', $new_subscriptions );
		}

		$tiers         = 0;
		$subscriptions = get_user_meta( $userID, 'vw_provider_subscriptions', true );
		if ( is_array( $subscriptions ) ) {
			$tiers = count( $subscriptions );
		}

		$this_page = self::getCurrentURL();
		$htmlCode = '';


		$htmlCode .= '<form class="ui form ' . esc_attr( $options['interfaceClass'] ) . ' segment" method="post" enctype="multipart/form-data" action="' . $this_page . '"  name="adminForm">';

		for ( $i = 1; $i <= $tiers + 1; $i++ ) {
			if ( $i <= $tiers || $options['tiersMax'] > $tiers ) {
				$htmlCode .= '
<h4 class="ui dividing header ' . esc_attr( $options['interfaceClass'] ) . '">Tier ' . $i . ( $i > $tiers ? '<div class="ui label">New</div>' : '' ) . ' </h4>
<div class="three fields">
    <div class="field">
      <label>' . __( 'Name', 'paid-membership' ) . '</label>
      <input type="text" placeholder="' . __( 'Name', 'paid-membership' ) . '" name="name' . $i . '" value="' . ( $i <= $tiers ? $subscriptions[ $i ]['name'] : '' ) . '">
    </div>
    <div class="field">
      <label>' . __('Price', 'video-share-vod') . ' </label>
      <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' right labeled input"> <input type="text" placeholder="' . __( 'Price', 'paid-membership' ) . ' (' . $limitsInfo . ')" name="price' . $i . '" value="' . ( $i <= $tiers ? $subscriptions[ $i ]['price'] : '' ) . '"> <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' basic label">' . esc_html( $options['currency'] ) . ' </div> </div>
    </div>
    <div class="field">
      <label>' . __( 'Duration', 'paid-membership' ) . '</label>
<select class="ui fluid dropdown v-select" name="duration' . $i . '">
            <option value="7" ' . ( $i <= $tiers ? ( $subscriptions[ $i ]['duration'] == 7 ? 'selected' : '' ) : '' ) . '>1 Week</option>
            <option value="14" ' . ( $i <= $tiers ? ( $subscriptions[ $i ]['duration'] == 14 ? 'selected' : '' ) : '' ) . '>2 Weeks</option>
            <option value="30" ' . ( $i <= $tiers ? ( $subscriptions[ $i ]['duration'] == 30 ? 'selected' : '' ) : '' ) . '>1 Month</option>
            <option value="365" ' . ( $i <= $tiers ? ( $subscriptions[ $i ]['duration'] == 365 ? 'selected' : '' ) : '' ) . '>1 Year</option>
            <option value="0" ' . ( $i <= $tiers ? ( $subscriptions[ $i ]['duration'] == 0 ? 'selected' : '' ) : '' ) . '>Lifetime</option>
          </select>
          </div>
   </div>
   <div class="field">
    <label>' . __( 'Description', 'paid-membership' ) . '</label>
    <textarea rows="2" name="description' . $i . '">' . ( $i <= $tiers ? htmlspecialchars( $subscriptions[ $i ]['description'] ) : '' ) . '</textarea>
  </div>' . ( $i <= $tiers ? '<div class="field"> <div class="ui checked checkbox">
  <input type="checkbox" name="delete' . $i . '">
  <label> ' . __( 'Remove', 'paid-membership' ) . ' </label>
</div></div>' : '' );
			}
		}

		$htmlCode .= '<input name="new_tiers" type="hidden" id="new_tiers" value="' . ( $tiers + 1 ) . '" />';

		$htmlCode .= '<p><input class="ui button" type="submit" name="save" id="save" value="Save" /></p>';
		$htmlCode .= '<form>';

		if ( $options['p_videowhisper_content_seller'] ?? false ) {
			$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' message" > <a class="ui compact button tiny" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a> ' . __( 'Higher tier subscriptions include access to content from lower tiers.', 'paid-membership' ) . ' </div>';
		}

		$htmlCode .= self::poweredBy();

		return $htmlCode;
	}


	static function videowhisper_donate_progress( $atts ) {

		$atts = shortcode_atts(
			array(
				'postid' => '',
			),
			$atts,
			'videowhisper_donate_progress'
		);

		if ( $atts['postid'] ) {
			$postID = intval( $atts['postid'] );
		}
		if ( ! $postID ) {
			$postID = get_the_ID();
		}

		if ( ! $postID ) {
			return 'videowhisper_donate_progress: postid parameter required to display progress!';
		}

		$options = get_option( 'VWpaidMembershipOptions' );

		$vw_donations = get_post_meta( $postID, 'vw_donations', true );

		self::enqueueUI();

		$htmlCode = '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment">';

		$goal = get_post_meta( $postID, 'goal', true );
		if ( ! is_array( $goal ) ) {
			$goal = array(
				'ix'          => 0,
				'name'        => '',
				'description' => '',
				'amount'      => 0,
				'current'     => 0,
				'cumulated'   => 0,
				'funders'     => array(),
			);
		}
		if ( ! is_array( $goal['funders'] ) ) {
			$goal['funders'] = array();
		}

		$amount = $goal['amount'];
		if ( ! $amount ) {
			$amount = 1000;
		}
				$htmlCode .= '<div class="ui header">' . $goal['name'] . ' ' . $goal['current'] . '/' . $goal['amount'] . ' ' . $options['currency'] . '</div>
<div class="ui indicating progress" data-value="' . $goal['current'] . '" data-total="' . $goal['amount'] . '" id="vw_progress">
  <div class="bar">
  <div class="progress"></div>
  </div>
</div>
<p>' . $goal['description'] . '</p>
<script>
jQuery(document).ready(function(){
jQuery("#vw_progress").progress();
});
</script>
';

		if ( $vw_donations == 'crowdfunding' ) {
			$funderCode = '';

			$stake = 100;
			if ( array_key_exists( 'stake', $goal ) ) {
				$stake = $goal['stake'];
			}

			foreach ( $goal['funders'] as $key => $value ) {
				$user        = get_userdata( $key );
				$funderCode .= $funderCode ? ', ' : '';

				if ( function_exists( 'bp_members_get_user_url' ) ) {
					$url = bp_members_get_user_url( $key ) . 'activity';
				}

				$funderCode .= '<a href="' . $url . '">' . $user->display_name . '</a> <i class="user plus icon"></i> ' . $value . $options['currency'] . ' (' . round( $value * $stake / $amount, 2 ) . '%)';
			};

			$htmlCode .= ( $stake ? '<i class="chart pie icon"></i>' . __( 'Crowdfunding', 'paid-membership' ) . ' ' . $stake . '%' : '' );
			$htmlCode .= ' <i class="chart bar icon"></i>' . __( 'Funders', 'paid-membership' ) . ': ' . $funderCode;
		}

		$htmlCode .= '</div>';

		// var_dump($goal);

		return $htmlCode;
	}


	static function videowhisper_donate( $atts ) {
		$options = get_option( 'VWpaidMembershipOptions' );

		$atts = shortcode_atts(
			array(
				'userid' => '0',
				'wallet' => '1',
				'postid' => '',
			),
			$atts,
			'videowhisper_donate'
		);

		$userID = intval( $atts['userid'] );

		if ( $atts['postid'] ) {
			$postID = intval( $atts['postid'] );
		} else $postID = 0;

		if ( ! $postID ) {
			$postID = get_the_ID();
		}

		if ( ! $userID ) {
			// try current post owner
			if ( $postID ) {
				$userID = get_post_field( 'post_author', $postID );
			}
		}

		self::enqueueUI();

		if ( ! $userID ) {
			return 'videowhisper_donate: Donation recipient "userid" parameter is required!';
		}

		if ( ! is_user_logged_in() ) {
			return '<span><i class="ui gift icon"></i> &nbsp;' . __( 'Login to Donate', 'paid-membership' ) . ': <a class="ui button primary qbutton tiny compact" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton tiny compact" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a> </span>';
		}

		// donation roles
		if (! self::rolesUser( $options['rolesDonate'], wp_get_current_user() ) ) return ;

		$htmlCode = '';

		if ( $atts['wallet'] ) {
			$htmlCode .= do_shortcode( '[videowhisper_wallet] ' );
		}

		$clientID = get_current_user_id();

		$user = get_user_by( 'id', $userID );

		$userName = $user->display_name;

		$userCode = $userName;
		$contentCode = '';

		if ( function_exists( 'bp_members_get_user_url' ) ) {
			$userCode = '<a href="' . bp_members_get_user_url( $userID ) . '"  title="' . bp_core_get_user_displayname( $userID ) . '"> ' . bp_core_fetch_avatar(
				array(
					'item_id' => $userID,
					'type'    => 'thumb',
				)
			) . ' </a>';
		}

		$post = get_post( $postID );

		if ( $post ) {
			$contentCode .= '<div class="ui header">' . esc_html( $post->post_title ) . '</div> <p>' . esc_html( $post->post_excerpt ) . '</p>';
		}

		$vw_donations = get_post_meta( $postID, 'vw_donations', true );
		if ( $vw_donations == 'crowdfunding' || $vw_donations == 'goal' ) {
			$goal = get_post_meta( $postID, 'goal', true );
			if ( is_array( $goal ) ) {
				$contentCode .= '<div class="ui header">' . esc_html( $goal['name'] ) . ' ' . esc_html( $goal['current'] ) . '/' . esc_html( $goal['amount'] ) . ' ' . esc_html( $options['currency'] ) . '</div>	' . esc_html( $goal['description'] ) . '</p>';
			}
		}

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_donate';

		$htmlCode .= '<div id="modal_result" class="ui modal">
<i class="close icon"></i>
  <div class="ui header">' . __( 'Donate', 'paid-membership' ) . '</div>
  <div id="content_result" class="content">X</div>
  <div class="actions">
    <div class="ui cancel button"><i class="close icon"></i></div>
  </div>
</div>

<div id="modal_donate" class="ui modal">
<i class="close icon"></i>
  <div class="header">' . __( 'Donate', 'paid-membership' ) . '</div>
  <div class="content">
    <p>' . $userCode . ' </p>

<div class="ui center aligned segment">
  <div class="ui right labeled input">
  <label for="amount" class="ui label"><i class="gift icon"></i></label>
  <input type="text" id="amount" value="5" disabled="">
  <div class="ui basic label">' . $options['currency'] . '</div>
   </div>
</div>

<p><div class="ui labeled slider" id="slider-1"></div></p>
 ' . $contentCode . '
  </div>
  </p>
  <div class="actions">
    <div class="ui orange approve button"><i class="gift icon"></i> ' . __( 'Donate', 'paid-membership' ) . '</div>
    <div class="ui cancel button"><i class="close icon"></i> ' . __( 'Cancel', 'paid-membership' ) . '</div>
  </div>
</div>
<button class="ui ' . esc_attr( $options['interfaceClass'] ) . ' orange button tiny compact" onclick="jQuery(\'#modal_donate\').modal({onApprove : function() {

jQuery.ajax({
  method: \'POST\',
  url: \'' . $ajaxurl . '\',
  data: { amount: jQuery(\'#amount\').val(), clientID: \'' . $clientID . '\', userID:  \'' . $userID . '\', postID:  \'' . $postID . '\'}
})
  .done( function( msg )
  {
	  jQuery(\'#modal_result\').modal(\'show\');
	  jQuery(\'#content_result\').html( msg );
  });

}}).modal(\'show\');"><i class="gift icon"></i>
' . ( $vw_donations == 'crowdfunding' ? __( 'Fund', 'paid-membership' ) : __( 'Donate to', 'paid-membership' ) ) . ' ' . $userName . '</button>

<script>
jQuery(document).ready(function(){
const slider1 = jQuery(\'#slider-1\');
slider1.slider({
    min: ' . $options['donationMin'] . ',
    max: ' . $options['donationMax'] . ',
    start: ' . $options['donationDefault'] . ',
    step: ' . $options['donationStep'] . ',
	smooth: true,
	showLabelTicks: true,
    onChange: () => { jQuery(\'#amount\').val(slider1.slider(\'get value\')) },
  });
});
</script>
';

		return $htmlCode;
	}



	static function vwpm_donate() {
		$options = get_option( 'VWpaidMembershipOptions' );

		ob_clean();

		$amount   = floatval( $_POST['amount'] );
		$clientID = intval( $_POST['clientID'] );
		$userID   = intval( $_POST['userID'] );
		$postID   = intval( $_POST['postID'] );

		if ( ! $amount || ! $clientID || ! $userID ) {
			echo 'Missing parameter error!';
			exit;
		};

		$current_user = wp_get_current_user();
		if ( $current_user->ID != $clientID ) {
			echo 'Only logged in user can send donation!';
			exit;
		}

		if (! self::rolesUser( $options['rolesDonate'], $current_user ) )
		{
			echo 'Donations not enabled for your role!';
			exit;
		}

		$user = get_userdata( $userID );
		if ( ! $user ) {
			echo esc_html( "User #$userID not found!" );
			exit;
		}

		$post = get_post( $postID );
		if ( ! $post ) {
			echo esc_html( "Post #$postID not found!" );
			exit;
		}
		$postCode = ' <a href="' . get_permalink( $postID ) . '">' . esc_html( $post->post_type ) . ' ' . esc_html( $post->post_title ) . '</a>';

		$tlabels      = array(
			'Donation' => __( 'Donation', 'paid-membership' ),
			'donated'  => __( 'donated', 'paid-membership' ),
		);
		$vw_donations = get_post_meta( $postID, 'vw_donations', true );
		if ( $vw_donations == 'crowdfunding' ) {
			$tlabels = array(
				'Donation' => __( 'Funding', 'paid-membership' ),
				'donated'  => __( 'funded', 'paid-membership' ),
			);
		}

		if ( self::balance( $clientID ) < $amount ) {
			echo __( 'Not enough funds available to make donation!', 'paid-membership' );
			exit;
		}


		//ratio paid to author (rest is site profit)
		$donationRatio = round( $options['donationRatio'], 3) ;
		if (!$donationRatio) $donationRatio = 1;


		// transactions
		self::transaction( 'micropayments_donation', $clientID, - $amount, $tlabels['Donation'] . ' for ' . sanitize_text_field( $user->display_name ) . ' on ' . $postCode );
		self::transaction( 'micropayments_donation_receive', $userID, ( $amount * $donationRatio ), $tlabels['Donation'] . ' from ' . sanitize_text_field( $current_user->display_name ) . ' on ' . $postCode );

		// goal
		$goal = get_post_meta( $postID, 'goal', true );
		if ( ! is_array( $goal ) ) {
			$goal = array(
				'ix'          => 0,
				'name'        => '',
				'description' => '',
				'amount'      => 0,
				'current'     => 0,
				'cumulated'   => 0,
				'funders'     => array(),
			);
		}
		if ( ! is_array( $goal['funders'] ) ) {
			$goal['funders'] = array();
		}

		$found = 0;
		foreach ( $goal['funders'] as $key => $value ) {
			if ( $key == $clientID ) {
				$goal['funders'][ $clientID ] += $amount;
				$found                         = 1;
			}
		}

		if ( ! $found ) {
			$goal['funders'][ $clientID ] = $amount;
		}

		$goal['current']   += $amount;
		$goal['cumulated'] += $amount;

		update_post_meta( $postID, 'goal', $goal );

		echo '<div class="ui segment">';
		echo esc_html( $tlabels['Donation'] ) . ' ' . __( 'completed', 'paid-membership' ) . esc_html( ": $amount " ) . esc_html( $options['currency'] ) . ' @ ' . esc_html( $user->display_name ) . ' .';
		echo '<p>' . __( 'Reload page to see update!', 'paid-membership' ) . '</p>';
		echo '</div>';
		// BuddyPress Activity
		if ( function_exists( 'bp_activity_add' ) ) {
			// sender
			$args        = array(
				'action'       => '<a href="' . bp_members_get_user_url( $clientID ) . 'activity">' . $current_user->display_name . '</a> ' . sanitize_text_field( $tlabels['donated'] ) . ' ' . sanitize_text_field( $amount ) . ' ' . $options['currency'] . ' to ' . '<a href="' . bp_members_get_user_url( $userID ) . 'activity">' . sanitize_text_field( $user->display_name ) . '</a> ' . ' on ' . $postCode,
				'component'    => 'micropayments',
				'type'         => 'donation',
				'primary_link' => get_permalink( $postID ),
				'user_id'      => $clientID,
				'item_id'      => $postID,
				'content'      => '<a href="' . get_permalink( $postID ) . '">' . get_the_post_thumbnail( $postID, array( 150, 150 ), array( 'class' => 'ui small rounded middle aligned spaced image' ) ) . '</a> ' . $post->post_excerpt,
			);
			$activity_id = bp_activity_add( $args );

			// recipient
		}

		exit;
	}


	static function contentEdit( $post, $data, $options = null ) {
		if ( ! $options ) {
			$options = get_option( 'VWpaidMembershipOptions' );
		}

		$postID = $post->ID;

		$htmlCode = '';

		// goal
		if ( array_key_exists( 'goal_amount', $data ) ) {
			$goal = get_post_meta( $postID, 'goal', true );
			if ( ! is_array( $goal ) ) {
				$goal = array(
					'ix'          => 0,
					'name'        => '',
					'description' => '',
					'amount'      => 0,
					'stake'       => 100,
					'current'     => 0,
					'cumulated'   => 0,
					'funders'     => array(),
				);
			}

			$goal['amount']      = floatval( $data['goal_amount'] );
			$goal['name']        = $data['goal_name'];
			$goal['description'] = $data['goal_description'];
			$goal['stake']       = $data['goal_stake'];

			update_post_meta( $postID, 'goal', $goal );
		}

		// price
		if ( array_key_exists( 'price', $data ) ) {
			// price
			$newPrice = floatval( $data['price'] );

			if ( $options['paid_handler'] == 'micropayments' ) {
				if ( $newPrice > 0 ) {
					update_post_meta( $postID, 'micropayments_price', $newPrice );
					update_post_meta( $postID, 'micropayments_duration', $data['priceExpire'] ?? 0 );
				} else {
					delete_post_meta( $postID, 'micropayments_price' );
					delete_post_meta( $postID, 'micropayments_duration' );
					$htmlCode .= 'Content is now free.<br>';
				}
			} elseif ( get_post_meta( $postID, 'micropayments_price', true ) ) {
				delete_post_meta( $postID, 'micropayments_price' );
				delete_post_meta( $postID, 'micropayments_duration' );
			}

			// mycred sell content setup
			if ( $options['paid_handler'] == 'mycred' ) {
				if ( $newPrice > 0 ) {
					if ( ! array_key_exists( 'priceExpire', $data ) ) {
						$data['priceExpire'] = 0;
					}

					$mCa = array(
						'status'       => 'enabled',
						'price'        => $newPrice,
						'button_label' => 'Buy Now', // default button label
						'expire'       => $data['priceExpire'],
						'recurring'    => 0,
					);

					update_post_meta( $postID, 'myCRED_sell_content', $mCa );
				} else {
					delete_post_meta( $postID, 'myCRED_sell_content' );
					$htmlCode .= 'Content is now free.<br>';
				}
			} elseif ( get_post_meta( $postID, 'myCRED_sell_content', true ) ) {
				delete_post_meta( $postID, 'myCRED_sell_content' ); // not using mycred
			}

			// mycred end

			// woocommerce product setup
			if ( $options['paid_handler'] == 'woocommerce' ) {
				$vw_micropay_productid = get_post_meta( $postID, 'vw_micropay_productid', true );

				$product_id = 0;
				if ( $vw_micropay_productid ) {
					$productPost = get_post( $vw_micropay_productid );
				}
				if ( ! $productPost ) {
					$product = array();
				} else {
					$product_id = $vw_micropay_productid;
				}

				if ( $newPrice > 0 ) {
					// update product
					$product['post_title']   = sanitize_text_field( $post->post_title );
					$product['post_author']  = intval( $post->post_author );
					$product['post_content'] = wp_filter_post_kses( $post->post_content );
					$product['post_excerpt'] = wp_trim_excerpt( wp_filter_post_kses( $post->post_content ) );
					$product['post_type']    = 'product';
					$product['post_status']  = 'publish';

					if ( $product_id ) {
						$product['ID'] = $product_id;
						$product_id    = wp_update_post( $product ); // updates this post (page) with no id
					} else {
						$product_id = wp_insert_post( $product );

						// BuddyPress Activity
						if ( function_exists( 'bp_activity_add' ) ) {
							$user = get_userdata( $post->post_author );

							$args = array(
								'action'       => '<a href="' . bp_members_get_user_url( $post->post_author ) . '">' . sanitize_text_field( $user->display_name ) . '</a> ' . __( 'added a new product', 'paid-membership' ) . ': <a href="' . get_permalink( $product_id ) . '">' . $product['post_title'] . '</a>',
								'component'    => 'micropayments',
								'type'         => 'product_new',
								'primary_link' => get_permalink( $product_id ),
								'user_id'      => intval( $post->post_author ),
								'item_id'      => intval( $product_id ),
								'content'      => '<a href="' . get_permalink( $product_id ) . '">' . get_the_post_thumbnail( $product_id, array( 150, 150 ), array( 'class' => 'ui small rounded middle aligned spaced image' ) ) . '</a> ' . $product['post_excerpt'],
							);

							$activity_id = bp_activity_add( $args );
						}
					}

					if ( $product_id ) {
						update_post_meta( $product_id, 'videowhisper_content', $postID );

						// woocommerce product meta
						wp_set_object_terms( $product_id, 'simple', 'product_type' );
						update_post_meta( $product_id, '_visibility', 'visible' );
						update_post_meta( $product_id, '_stock_status', 'instock' );
						// update_post_meta( $product_id, 'total_sales', '0' );
						// update_post_meta( $product_id, '_downloadable', 'no' );
						update_post_meta( $product_id, '_virtual', 'yes' );
						// update_post_meta( $product_id, '_regular_price', '' );
						// update_post_meta( $product_id, '_sale_price', '' );
						update_post_meta( $product_id, '_purchase_note', '' );
						// update_post_meta( $product_id, '_featured', 'no' );
						// update_post_meta( $post_id, '_weight', '11' );
						// update_post_meta( $post_id, '_length', '11' );
						// update_post_meta( $post_id, '_width', '11' );
						// update_post_meta( $post_id, '_height', '11' );
						update_post_meta( $product_id, '_sku', 'content' . $postID );
						update_post_meta( $product_id, '_product_attributes', array() );
						// update_post_meta( $post_id, '_sale_price_dates_from', '' );
						// update_post_meta( $post_id, '_sale_price_dates_to', '' );
						update_post_meta( $product_id, '_price', $newPrice );
						update_post_meta( $product_id, '_sold_individually', '' );
						update_post_meta( $product_id, '_manage_stock', 'no' );
						// wc_update_product_stock($post_id, $single['qty'], 'set');
						update_post_meta( $product_id, '_backorders', 'no' );

						// thumb & images
						$post_thumbnail_id = get_post_thumbnail_id( $postID );

						$args = array(
							'post_type'   => 'attachment',
							'numberposts' => -1,
							'posts_per_page' => -1,
							'post_status' => 'any',
							'post_parent' => $postID,
						);

						$attachmentIDs = '';
						$attachments   = get_posts( $args );
						if ( $attachments ) {
							foreach ( $attachments as $attachment ) {
								$attachmentIDs .= ( $attachmentIDs ? ',' : '' ) . $attachment->ID;
								if ( ! $post_thumbnail_id ) {
									   $post_thumbnail_id = $attachment->ID;
								}
							}
						}

						if ( $post_thumbnail_id ) {
							set_post_thumbnail( $product_id, $post_thumbnail_id );
						}
						update_post_meta( $product_id, '_product_image_gallery', $attachmentIDs );

						// update product categories
						$categories = wp_get_post_categories( $postID );
						if ( count( $categories ) ) {
							$tags = array();

							foreach ( $categories as $prod_cat ) {
								if ( ! term_exists( $prod_cat->name, 'product_cat' ) ) {
									   $term = wp_insert_term( $prod_cat->name, 'product_cat' );
									if ( is_array( $term ) ) {
										array_push( $tags, $term['term_id'] );
									}
								} else {
									$term_s = get_term_by( 'name', $prod_cat->name, 'product_cat' );
									array_push( $tags, $term_s->term_id );
								}
							}
							wp_set_post_categories( $product_id, $tags );
						}

						// copy tags
						$tags = wp_get_post_tags( $postID, array( 'fields' => 'ids' ) );
						wp_set_post_tags( $product_id, $tags );

						// update post
						update_post_meta( $postID, 'vw_micropay_productid', $product_id );
						update_post_meta( $postID, 'vw_micropay_price', $newPrice );

						$htmlCode .= __( 'Product was updated.', 'paid-membership' ) . '<br>';
					} else {
						$htmlCode .= 'Error updating product.';
					}
				} else {
					if ( $product_id ) {
						wp_delete_post( $product_id );
						$htmlCode .= 'Product was removed.<br>';
					}

					//update_post_meta( $postID, 'vw_micropay_price', 0 );
					delete_post_meta( $postID, 'vw_micropay_price');
					$product_id = 0;
				}

				if ( $product_id ) {
					$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment"> <a class="ui button" href="' . get_permalink( $product_id ) . '"><i class="shopping cart icon"></i> View Product</a> </div>';
				}
			} elseif ( get_post_meta( $postID, 'vw_micropay_productid', true ) ) {
				delete_post_meta( $postID, 'vw_micropay_productid' );
			}
			// woocommerce end

			// price end
		}

		self::isPremium($postID); //update premium content marker

		return $htmlCode;
	}


	public static function contentData( $postID, $options = null ) {

		if ( ! $options ) {
			$options = get_option( 'VWpaidMembershipOptions' );
		}

		$data = array();

		if ( $options['paid_handler'] == 'micropayments' ) {
			$price = get_post_meta( $postID, 'micropayments_price', true );
			if ( $price ) {
				$data['price'] = number_format( $price, 2, '.', ''  );
			} else {
				$data['price'] = 0;
			}

			$expire = get_post_meta( $postID, 'micropayments_duration', true );
			if ( $expire ) {
				$data['priceExpire'] = intval( $expire );
			} else {
				$data['priceExpire'] = 0;
			}
		}

		if ( $options['paid_handler'] == 'mycred' ) {
			$mCa = get_post_meta( $postID, 'myCRED_sell_content', true );
			if ( $mCa ) {
				$data['price']       = number_format( $mCa['price'], 2, '.', ''  );
				$data['priceExpire'] = $mCa['expire'];
			} else {
				$data['price']       = 0;
				$data['priceExpire'] = 0;
			}
		}

		$data['currency'] = $options['currency'];

		if ( $options['paid_handler'] == 'woocommerce' ) {
			$vw_micropay_productid = get_post_meta( $postID, 'vw_micropay_productid', true );

			$product_id = 0;
			if ( $vw_micropay_productid ) {
				$productPost = get_post( $vw_micropay_productid );
			} else $productPost = 0;
			
			if ( ! $productPost ) {
				$product = array();
			} else {
				$product_id = $vw_micropay_productid;
			}

			$data['price']      = number_format( get_post_meta( $product_id, '_price', true ), 2, '.', ''  );
			$data['product_id'] = $product_id;
		}

		$data['donations'] = get_post_meta( $postID, 'vw_donations', true );

		$data['goal'] = get_post_meta( $postID, 'goal', true );

		// $data['roles'] = get_post_meta( $postID, 'vwpm_roles', true );
		$data['subscription_tier'] = get_post_meta( $postID, 'vw_subscription_tier', true );

		return $data;
	}

	        //! frontend shortcode toolbox

    	/**
		 * Retrieves the best guess of the client's actual IP address.
		 * Takes into account numerous HTTP proxy headers due to variations
		 * in how different ISPs handle IP addresses in headers between hops.
		 */
		static function get_ip_address() {
			$ip_keys = array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' );
			foreach ( $ip_keys as $key ) {
				if ( array_key_exists( $key, $_SERVER ) === true ) {
					foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
						// trim for safety measures
						$ip = trim( $ip );
						// attempt to validate IP
						if ( self::validate_ip( $ip ) ) {
							return $ip;
						}
					}
				}
			}

			return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : false;
		}

        /**
		 * Ensures an ip address is both a valid IP and does not fall within
		 * a private network range.
		 */
		static function validate_ip( $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
				return false;
			}
			return true;
		}

	static function handle_upload( $file, $destination ) {
        // ex $_FILE['myfile']

        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }

        $movefile = wp_handle_upload( $file, array( 'test_form' => false ) );

        if ( $movefile && ! isset( $movefile['error'] ) ) {
            if ( ! $destination ) {
                return 0;
            }
            rename( $movefile['file'], $destination ); // $movefile[file, url, type]
            return 0;
        } else {
            /*
             * Error generated by _wp_handle_upload()
             * @see _wp_handle_upload() in wp-admin/includes/file.php
             */
            return $movefile['error']; // return error
        }

    }

	static function cleanAttachments( $id, $unlink = false ) {

        $htmlCode = '';

        $media = get_children(
            array(
                'post_parent' => $id,
                'post_type'   => 'attachment',
            )
        );

        if ( empty( $media ) ) {
            return $htmlCode;
        }

        foreach ( $media as $file ) {

            if ( $unlink ) {
                $filename  = get_attached_file( $file->ID );
              //  $htmlCode .= " Removing $filename #" . $file->ID;
                if ( file_exists( $filename ) ) {
                    unlink( $filename );
                }
            }

            wp_delete_attachment( $file->ID );
        }

        return $htmlCode;
    }

	public static function addFeaturedImage( $postID, $options)
    {
        //adds 'featured_image' upload to post

        $htmlCode = '';

        //clean attachments
        if (isset($_POST['remove_images'])) {
        $htmlCode .= self::cleanAttachments($postID, true);
        }

        if ( isset($_FILES['featured_image']) && $filename = $_FILES['featured_image']['tmp_name'] ) {

            $ext     = strtolower( pathinfo( $_FILES['featured_image']['name'], PATHINFO_EXTENSION ) );
            $allowed = array( 'jpg', 'jpeg', 'png', 'gif' );
            if ( ! in_array( $ext, $allowed ) ) {
                return 'Unsupported file extension!';
            }

            list($width, $height) = getimagesize( $filename );

            if ( $width && $height ) {

                    $dir = $options['uploadsPath'];
                    if ( ! file_exists( $dir ) ) {
                        mkdir( $dir );
                    }

                    $dir .= '/images' ;
                    if ( ! file_exists( $dir ) ) {
                        mkdir( $dir );
                    }

                    $dir .= '/';

                // save file
                $destination = "$dir/" . sanitize_file_name( $_FILES['featured_image']['name'] );
                // copy source

                $errorUp = self::handle_upload( $_FILES['featured_image'], $destination ); // handle trough wp_handle_upload()
                if ( $errorUp ) {
                    $htmlCode .= '<br>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp );
                }

                if ( file_exists( $destination ) ) {

                    if ( $postID ) {
                        update_post_meta( $postID, 'featured_image', $destination );
                    }

                    // update post image

                    if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
                        require ABSPATH . 'wp-admin/includes/image.php';
                    }

                    $wp_filetype = wp_check_filetype( basename( $destination ), null );

                    $attachment = array(
                        'guid'           => $destination,
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_title( sanitize_file_name( $_FILES['featured_image']['name'] ) ),
                        'post_content'   => '',
                        'post_status'    => 'inherit',
                    );

                    $attach_id = wp_insert_attachment( $attachment, $destination, $postID );
                    set_post_thumbnail( $postID, $attach_id );

                    // update post imaga data
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $destination );
                    wp_update_attachment_metadata( $attach_id, $attach_data );

                    if ( ! file_exists( $destination ) ) {
                        $htmlCode .= __( 'ERROR: Missing ', 'ppv-live-webcams' ) . $destination;
                    }

                } else {
                    $htmlCode .= __( 'ERROR: Upload copy failed. File does not exist ', 'ppv-live-webcams' ) . $destination;
                }
            } else {
                $htmlCode .= __( 'ERROR: Could not retrieve image size for ', 'ppv-live-webcams' ) . $filename;
            }
        }

        return $htmlCode;
    }



	static function videowhisper_content_edit( $atts ) {
		// Edit Content

		$options = self::getOptions();
		self::enqueueUI();

		// checks
		if ( ! is_user_logged_in() ) {
			return 'Only registered users can edit their content!' . '<BR><a class="ui button primary qbutton small" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton small" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$postID = intval( $_GET['editID'] ?? 0 );
		$saveID = intval( $_POST['saveID'] ?? 0 );

		if ( ! $postID ) {
			$postID = $saveID;
		}
		if ( ! $postID ) {
			return 'This is a system page for editing content, called from other sections. Content ID is required!';
		}

		$post = get_post( $postID );
		if ( ! $post ) {
			return 'Content not found!';
		}

		if ( $saveID ) {
			if ( $postID != $saveID ) {
				return 'Incorrect ID!';
			}
		}

		$current_user = wp_get_current_user();

		if (! self::rolesUser( $options['rolesSeller'], $current_user ) ) return 'Seller features not enabled for your role!';

		if ( $post->post_author != $current_user->ID && ! in_array( 'administrator', $current_user->roles ) ) {
			return 'Only owner and administrator can edit content!';
		}

		$htmlCode = '<!--VideoWhisper.com/MicroPayments/videowhisper_content_edit-->';


				$donationsEnabled = 0;
		$type                     = get_post_type( $postID );
		$postTypes                = explode( ',', $options['postTypesDonate'] );
		foreach ( $postTypes as $postType ) {
			if ( $type == trim( $postType ) ) {
				$donationsEnabled = 1;
				break;
			}
		}

		// everything fine: edit

		$htmlCode .= '<H4>' . __( 'Editing', 'paid-membership' ) . ' ' . ucwords( $post->post_type ) . ' #' . $post->ID . ': ' . $post->post_title . '</H4>';

		$newPrice = number_format( $_POST['price'] ?? 0, 2, '.', ''  );

		//content price limits
		$contentMin = floatval( $options['contentMin'] );
		$contentMax = floatval( $options['contentMax'] );
		$priceRangeInfo = $contentMin . ( $contentMax ? "-$contentMax" : '+') ;

		if ($contentMax) $newPrice = min($newPrice, $contentMax);
		$newPrice = max($newPrice, $contentMin);

		if ( $saveID ) {
			if ( ! wp_verify_nonce( $_GET['verify'], 'editContent' . $postID ) ) {
				return 'Incorrect content saving nonce!';
			}

			$htmlCode .= self::contentEdit(
				$post,
				array(
					'price'            => $newPrice,
					'priceExpire'      => intval( $_POST['duration'] ?? 0 ),
					'goal_amount'      => floatval( $_POST['goal_amount'] ?? 0 ),
					'goal_stake'       => floatval( $_POST['goal_stake'] ?? 0),
					'goal_name'        => sanitize_text_field( $_POST['goal_name'] ?? '' ),
					'goal_description' => wp_encode_emoji( sanitize_textarea_field( $_POST['goal_description'] ?? '' ) ),
				),
				$options
			);

			//coauthors

			//remove
			if ( isset( $_POST['coauthorRemove'] ) && is_array( $_POST['coauthorRemove'] ) ) {
				$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
				if (!$coauthors) $coauthors = array();

				foreach ( $_POST['coauthorRemove'] as $coauthorID ) {
					unset($coauthors[$coauthorID]);
					$htmlCode .= '<div>' . __( 'Coauthor removed:', 'paid-membership' ) .  '#' .  $coauthorID . '</div>';
				}
				update_post_meta( $postID, 'vw_coauthors', $coauthors );
			}

			//add
			$coauthor = trim(sanitize_text_field( $_POST['coauthor'] ?? '' ));
			$coauthor_percent = intval( $_POST['coauthor_percent'] ?? 0 );

			if ($coauthor && $coauthor_percent )
			{
				if (is_email($coauthor)) $coauthorUser = get_user_by( 'email', $coauthor );
				else $coauthorUser = get_user_by( 'login', $coauthor );

				if (!$coauthorUser) $htmlCode .= '<div>' . __( 'Coauthor user not found:', 'paid-membership' ) . ' ' .  $coauthor . '</div>';
				else
				{
					$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
					if (!$coauthors) $coauthors = array();
					$coauthors[$coauthorUser->ID] = $coauthor_percent;

					$totalPercent = 0;
					foreach ($coauthors as $coauthorID => $coauthorPercent)
					{
						$totalPercent += $coauthorPercent;
					}

					if ($totalPercent > 100) $htmlCode .= '<div>' . __( 'Total coauthor percent exceeds 100%:', 'paid-membership' ) . ' ' .  $totalPercent . '</div>';
					else
					{
						update_post_meta( $postID, 'vw_coauthors', $coauthors );
						$htmlCode .= '<div>' . __( 'Coauthor added:', 'paid-membership' ) . ' ' .  $coauthor . ':' . $coauthor_percent . '%</div>';
					}
				}
			}




			$title          = sanitize_text_field( $_POST['title'] ?? '' );
			$status         = sanitize_file_name( $_POST['status'] );
			$comment_status = sanitize_file_name( $_POST['comment_status'] );

			$description = wp_kses_post( $_POST['description'] ?? '' ) ;

			$category_id = intval( $_POST['category'] );

			$tag = sanitize_text_field( $_POST['tag'] );
			if ( strpos( $tag, ',' ) !== false ) {
				$tags = explode( ',', $tag );
				foreach ( $tags as $key => $value ) {
					$tags[ $key ] = sanitize_text_field( trim( $value ) );
				}
				$tag = $tags;
			} else {
				$tag = sanitize_text_field( trim( $tag ) );
			}

			wp_update_post(
				array(
					'ID'             => $postID,
					'post_title'     => $title,
					'post_content'   => $description,
					'post_status'    => $status,
					'comment_status' => $comment_status,
				)
			);

			if ( $tag ) {
				wp_set_post_tags( $postID, $tag );
			}

			if ( $category_id ) {
				wp_set_post_categories( $postID, array( $category_id ) );
			}

			update_post_meta( $postID, 'vw_donations', sanitize_file_name( $_POST['donations'] ?? '' ) );

			if ($_POST['subscription_tier'] ?? false)
			{
				update_post_meta( $postID, 'vw_subscription_tier', intval( $_POST['subscription_tier'] ?? 0 ) );
			}
			else
			{
				delete_post_meta( $postID, 'vw_subscription_tier' );
			}

			self::isPremium($postID); //update premium marker

			$htmlCode .= self::addFeaturedImage( $postID, $options );

			$post = get_post( $postID );
		}

		$data = self::contentData( $postID, $options );

		$this_page = self::getCurrentURL();

		$htmlCode .= '<form class="ui form ' . esc_attr( $options['interfaceClass'] ) . ' segment" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( add_query_arg( 'editID', $postID, $this_page ), 'editContent' . $postID, 'verify' ) . '"  name="adminForm">';

		$htmlCode .= '<h5>' . __( 'Title', 'paid-membership' ) . '</h5>
			<input name="title" type="text" id="title" value="' . esc_attr( $post->post_title ) . '" size="48" maxlength="128" />';

		$category_id = 1;
		$categories = wp_get_post_categories( $postID, array( 'fields' => 'ids' ) );
		if ( count( $categories ) ) {
			$category_id = $categories[0];
		}

		$htmlCode .= '<div class="field"><label for="category">' . __( 'Category', 'paid-membership' ) . ' </label>' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown+v-select&selected=' . $category_id ) . '</div>';

		$htmlCode .= '<div class="field"><label for="tag">' . __( 'Tag(s)', 'paid-membership' ) . '</label><input size="48" maxlength="64" type="text" name="tag" value="' . implode( ', ', wp_get_post_tags( $postID, array( 'fields' => 'names' ) ) ) . '" id="tag" class="text-input"/></div>';



		$htmlCode .= '<div class="field"><label for="description">' . __( 'Description Content', 'paid-membership' ) . ' </label>';

		$tinymce_options = array(
			'plugins'          => 'lists,link,textcolor,hr',
			'toolbar1'         => 'cut,copy,paste,|,undo,redo,|,fontsizeselect,forecolor,backcolor,bold,italic,underline,strikethrough',
			'toolbar2'         => 'alignleft,aligncenter,alignright,alignjustify,blockquote,hr,bullist,numlist,link,unlink',
			'fontsize_formats' => '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt',
		);

		ob_start();
		wp_editor(
			$post->post_content,
			'description',
			 array(
				'textarea_rows' => 5,
				'media_buttons' => false,
				'teeny'         => true,
				'wpautop'       => false,
				'tinymce'       => $tinymce_options,
			)
		);
		$htmlCode .= ob_get_clean();
		$htmlCode .= '</div>';

		//upload featured image
		$htmlCode .= '<div class="field"><label>' . __( 'Featured Image (Optional)', 'ppv-live-webcams' ) . '</label>';
		$htmlCode .= '<input type="file" name="featured_image" id="featured_image" placeholder="Featured Image">';
		if ($postID > 0 && has_post_thumbnail($postID)) {
			$htmlCode .= '<br>' . get_the_post_thumbnail($postID, 'thumbnail'); //show current featured image first

			//also list other post attachment thumbnails
			$attachments = get_posts(['post_type' => 'attachment', 'posts_per_page' => -1, 'post_parent' => $postID, 'exclude' => get_post_thumbnail_id($postID)]);
			if ($attachments) {
				foreach ($attachments as $attachment) {
					$htmlCode .= ' ' . wp_get_attachment_image($attachment->ID, 'thumbnail');
				}
			}

			$htmlCode .= '<br><div class="ui checkbox"><input type="checkbox" name="remove_images" value="1"> <label>' . __( 'Remove Image(s)', 'ppv-live-webcams' ). '</label></div>';
		}

		$htmlCode .= '</div>';

		$htmlCode .= '<div class="field"><label for="comment_status">' . __( 'Comments', 'paid-membership' ) . '</label><select class="ui dropdown v-select" name="comment_status" id="comment_status"/>
			<option value="open" ' . ( $post->comment_status == 'open' ? 'selected' : '' ) . '>' . __( 'Open', 'paid-membership' ) . '</option>
			<option value="closed" ' . ( $post->comment_status == 'closed' ? 'selected' : '' ) . '>' . __( 'Closed', 'paid-membership' ) . '</option>
			</select></div>';

		if ( $post->post_status == 'pending' ) {
			$htmlCode .= '<div class="field"><label for="status">' . __( 'Status', 'paid-membership' ) . '</label>' . __( 'Pending Review', 'paid-membership' ) . '</div>';
		}
		$htmlCode .= '<div class="field"><label for="status">' . __( 'Status', 'paid-membership' ) . '</label><select class="ui dropdown v-select" name="status" id="status"/>
			<option value="publish" ' . ( $post->post_status == 'publish' ? 'selected' : '' ) . '>' . __( 'Public', 'paid-membership' ) . '</option>
			<option value="private" ' . ( $post->post_status == 'private' ? 'selected' : '' ) . '>' . __( 'Private', 'paid-membership' ) . '</option>
			<option value="draft" ' . ( $post->post_status == 'draft' ? 'selected' : '' ) . '>' . __( 'Draft', 'paid-membership' ) . '</option>
			<option value="trash" ' . ( $post->post_status == 'trash' ? 'selected' : '' ) . '>' . __( 'Trash', 'paid-membership' ) . '* </option>
			</select>' . __( 'Move item to Trash for removal. Trash can be permanently removed by admins.', 'paid-membership' ) . '</div>';

		// <option value="pending" ' .($post->post_status=='pending'?'selected':'') . '>' . __('Pending Review', 'paid-membership') . '</option>

		$htmlCode .= '<div class="ui horizontal divider">' . __( 'Monetization', 'paid-membership' ) . '</div>';


//subscription tier
		$htmlCode     .= '<div class="field"><label for="subscription_tier">' . __( 'Subscription Tier', 'paid-membership' ) . '</label><select class="ui fluid dropdown v-select" name="subscription_tier" >
            <option value="0" ' . ( $data['subscription_tier'] == 0 ? 'selected' : '' ) . '>' . __( 'None', 'paid-membership' ) . '</option>';
		$subscriptions = get_user_meta( $post->post_author, 'vw_provider_subscriptions', true );
		if ( is_array( $subscriptions ) ) {
			foreach ( $subscriptions as $key => $subscription ) {
				$htmlCode .= '<option value="' . esc_attr( $key ) . '" ' . ( $data['subscription_tier'] == $key ? 'selected' : '' ) . '>#' . esc_html( $key ) . ' ' . esc_html( $subscription['name'] ) . '</option>';
			}
		}
		$htmlCode .= '</select>
			' . __( 'Higher tier subscriptions include access to content from lower tiers.', 'paid-membership' ) . ( $options['p_videowhisper_provider_subscriptions'] ? ' <a href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>' : '' ) . '
			</div>
			<script>
jQuery(document).ready(function(){
	jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
});
</script>';

		//price

		$htmlCode .= '<div class="field"><label for="price">' . __( 'Sell Price', 'paid-membership' ) . '</label>
			<div class="ui right labeled input"> <input name="price" type="text" id="price" value="' . esc_attr( $data['price'] ) . '" size="6" maxlength="6" /><div class="ui label">' .  esc_html( $options['currency'] )  .' <small>[' . $priceRangeInfo . ']</small></div></div>'. __( 'Users need to pay this price to access this content. Set 0 for free access or by subscription. An individual price can also be configured for subscription items: If content is part of a subscription tier, client can opt to get the author subscription instead of purchasing content individually.', 'paid-membership' ) . '</div>';

		if ( in_array( $options['paid_handler'], array( 'mycred', 'micropayments' ) ) ) {
			$htmlCode .= '<div class="field"><label for="duration">' . __( 'Access Duration', 'paid-membership' ) . '</label>
			<input name="duration" type="text" id="duration" value="' . esc_attr( $data['priceExpire'] ) . '" size="6" maxlength="6" /> hours.
			' . __( 'Set 720 for 30 days, 336 for 2 weeks, 168 for 1 week, 24 for 1 day, 0 for unlimited time access (one time fee).', 'paid-membership' ) . '</div>';
		}

		//Co-Authors
		if ( $options['paid_handler'] ==  'micropayments' )
		{
			$coauthors = get_post_meta( $postID, 'vw_coauthors', true );
			if (!$coauthors) $coauthors = array();

			if (count($coauthors))
			{
				$htmlCode .= '<div class="field"><label for="coauthor">' . __( 'Coauthors', 'paid-membership' ) . '</label>';
				$totalPercent = 0;
				foreach ($coauthors as $coauthorID => $coauthorPercent)
				{
					$user = get_user_by( 'id', $coauthorID );
					if ($user)
						$coauthorName = $user->user_login;
					else
						$coauthorName ='Error: Not Found #' . $coauthorID;

					$htmlCode .= '<div class="ui checkbox"><input type="checkbox" id="coauthorRemove" name="coauthorRemove[]" value="' . $coauthorID . '"> <label> ' . esc_html($coauthorName) . ': ' . $coauthorPercent . '%</label></div><br>' ;

					$totalPercent += $coauthorPercent;
				}
				$htmlCode .= '<br>' . __( 'Check and save to remove coauthors. Total distributed:', 'paid-membership' ) .' '. $totalPercent . '%</div>';
				$htmlCode .= '<div>';
			}

			//add coauthor with 2 fields: couauthor username/email & ratio/percent of earning
			$htmlCode .= '<div class="field"><label for="coauthor">' . __( 'Add New Coauthor', 'paid-membership' ) . '</label>
			<input name="coauthor" type="text" id="coauthor" value="" size="20" maxlength="64" placeholder="' . __( 'Username or email of user', 'paid-membership' ) . '" /> ' . __( 'Username or email of user to share earnings with.', 'paid-membership' ) . '<br><div class="ui right labeled input"> <input name="coauthor_percent" type="text" id="coauthor_percent" value="" size="6" maxlength="3" placeholder="' . __( 'Percent', 'paid-membership' ) . ' 1-100" /><div class="ui label">% <small>[1-100]</small></div></div> ' . __( 'Percent of earnings to share with coauthor. Sum of all coauthor percents should not be over 100.', 'paid-membership' ) . '</div>';
		}
		//donations

		if ( $donationsEnabled ) {
			$htmlCode .= '<div class="field"><label for="donations">' . __( 'Donations', 'paid-membership' ) . '</label><select class="ui dropdown v-select" name="donations" id="donations" onchange="showDonationOptions(this.value)"/>
			<option value="donations" ' . ( $data['donations'] == 'donations' ? 'selected' : '' ) . '>' . __( 'Enabled', 'paid-membership' ) . '</option>
			<option value="disabled" ' . ( $data['donations'] == 'disabled' ? 'selected' : '' ) . '>' . __( 'Disabled', 'paid-membership' ) . '</option>
			<option value="goal" ' . ( $data['donations'] == 'goal' ? 'selected' : '' ) . '>' . __( 'Goal', 'paid-membership' ) . '</option>
			<option value="crowdfunding" ' . ( $data['donations'] == 'crowdfunding' ? 'selected' : '' ) . '>' . __( 'Crowdfunding', 'paid-membership' ) . '</option>
			</select>' . __( 'Goal will show progress and Crowdfunding will also publicly list all funders.', 'paid-membership' ) . '</div>';

			$htmlCode .= '<div class="field" id="goal_amount_f" style="display:'. ( !in_array($data['donations'], ['goal', 'crowdfunding'] ) ? 'none' : 'block' ) .'"><label for="goal_amount">' . __( 'Goal Amount', 'paid-membership' ) . '</label>
			<input name="goal_amount" type="text" id="goal_amount" value="' . ( $data['goal'] ? esc_attr( $data['goal']['amount'] ) : 0 ) . '" size="6" maxlength="6" />
			</div>';

			$htmlCode .= '<div class="field" id="goal_name_f" style="display:'. ( !in_array($data['donations'], ['goal', 'crowdfunding'] ) ? 'none' : 'block' ) .'"><label for="goal_name" >' . __( 'Goal Name', 'paid-membership' ) . '</label>
			<input name="goal_name" type="text" id="goal_name" value="' . ( $data['goal'] ? esc_attr( $data['goal']['name'] ) : '' ) . '" size="48" maxlength="128" />
			</div>';

			$htmlCode .= '<div class="field" id="goal_description_f" style="display:'. ( !in_array($data['donations'], ['goal', 'crowdfunding'] ) ? 'none' : 'block' ) .'"><label for="goal_description">' . __( 'Goal Description', 'paid-membership' ) . ' </label><textarea rows="2" name="goal_description" id="goal_description" class="text-input" />' . htmlspecialchars( $data['goal'] ? $data['goal']['description'] : '' ) . '</textarea></div>';

			$htmlCode .= '<div class="field" id="goal_stake_f" style="display:'. ( !in_array($data['donations'], ['crowdfunding'] ) ? 'none' : 'block' ) .'"><label for="goal_stake">' . __( 'Crowdfunding Stake', 'paid-membership' ) . ' %</label>
			<input name="goal_stake" type="text" id="goal_stake" value="' . ( $data['goal'] ? esc_attr( $data['goal']['stake'] ) : 100 ) . '" size="6" maxlength="6" />' . __( 'Crowdfunding percent for backers.', 'paid-membership' ) . '(0-100)
			</div>';

				$htmlCode .= '<SCRIPT>function showDonationOptions(value)
			{

				if (value == "donations")
				{
					    document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";

				}

				if (value == "disabled")
				{
				   		document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "goal")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "crowdfunding")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "block";
				}
			}
			</SCRIPT>';

		}

		$htmlCode .= '<input class="ui compact" name="saveID" type="hidden" id="saveID" value="' . $postID . '" />';

		$htmlCode .= '<p><input class="ui button" type="submit" name="save" id="save" value="Save" /></p>';

		$htmlCode .= '<div class="ui horizontal divider">' . __( 'More', 'paid-membership' ) . '</div>';

		if ( class_exists( '\PeepSoActivity' ) ) {
			$htmlCode .= '<a class="ui small button" data-tooltip="' . __( 'Announce', 'paid-membership' ) . '" href="' . add_query_arg( 'peepsoID', $postID, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'PeepSo Stream', 'paid-membership' ) . '</a>';
		}
		if ( function_exists( 'bp_activity_add' ) ) {
			$htmlCode .= '<a class="ui small button" data-tooltip="' . __( 'Share this on your activity feed', 'paid-membership' ) . '" href="' . add_query_arg( 'bpID', $postID, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'Share to Feed', 'paid-membership' ) . '</a>';
		}

		if ( $options['p_videowhisper_content_seller'] ) {
			$htmlCode .= '<a class="ui small button" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a>';
		}
		if ( $options['p_videowhisper_provider_subscriptions'] ) {
			$htmlCode .= ' <a class="ui small button" href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>';
		}

		$htmlCode .= '</form>';

		// preview content
		$post_thumbnail_id = get_post_thumbnail_id( $postID );
		if ( $postID ) {
			$post_featured_image = wp_get_attachment_image_src( $post_thumbnail_id, 'thumbnail' );
		}

		if ( $post_featured_image ) {
			$htmlCode .= '<IMG class="ui small rounded middle aligned spaced image" SRC ="' . $post_featured_image[0] . '" WIDTH="' . $post_featured_image[1] . '" HEIGHT="' . $post_featured_image[2] . '">';
		}

		if ( array_key_exists( 'product_id', $data ) ) {
			if ( $data['product_id'] ) {
				$htmlCode .= '<a class="ui button" href="' . get_permalink( $data['product_id'] ) . '"><i class="cart icon"></i> View Product</A>';
			}
		}

		$htmlCode .= '<a class="ui button" href="' . get_permalink( $postID ) . '"><i class="zoom-in icon"></i> View Content</A><br>' . wp_trim_excerpt( $post->post_content ) . '<br>';

		$htmlCode .= self::poweredBy();

		return $htmlCode;
	}


	static function videowhisper_content_seller( $atts ) {
		// My Assets

		self::enqueueUI();

		// checks
		if ( ! is_user_logged_in() ) {
			return 'Only registered users can manage their content!' . '<BR><a class="ui button primary qbutton small" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton small" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$options      = get_option( 'VWpaidMembershipOptions' );

		$htmlCode = '<!--VideoWhisper.com/MicroPayments/videowhisper_content_seller-->';
		$current_user = wp_get_current_user();
		$userID       = $current_user->ID;

		if (! self::rolesUser( $options['rolesSeller'], $current_user ) )
		{
			$roles = '';
			foreach ( $current_user->roles as $role )
			{
				$roles .= esc_html( $role ) . ' ';
			}

			return 'Seller features not enabled for your role! ' . $roles;

		}


		if ( ! $options['postTypesPaid'] ) {
			return 'No paid post types configured from plugin settings!';
		}

		$post_type = array();
		$postTypes = explode( ',', $options['postTypesPaid'] );
		foreach ( $postTypes as $postType ) {
			$post_type[] = trim( $postType );
		}

		$htmlCode .= '<h4 class="ui ' . esc_attr( $options['interfaceClass'] ) . ' header">' . __( 'My Digital Content Assets', 'paid-membership' ) . '</h4>';

		// announce form

		if ( isset($_GET['peepsoID']) || isset($_GET['bpID']) ) {
			$announceType = '';

			if ( $peepsoID = intval( $_GET['peepsoID'] ?? 0 ) ) {
				$announceID   = $peepsoID;
				$announceType = 'peepso';
			};

			if ( $bpID = intval( $_GET['bpID'] ?? 0 ) ) {
				$announceID   = $bpID;
				$announceType = 'bp';
			};

			if ( $announceID && $announceType ) {
				$thumbnailCode = '';
				$thumbID       = 0;

				if ( $announceID > 0 ) {
					$sPost = get_post( $announceID );
					if ( ! $sPost ) {
						return 'Post not found: ' . $peepsoID;
					}
					if ( $sPost->post_author != $current_user->ID ) {
						return 'Not your post!';
					}

					$thumbnailCode = '<a href="' . get_permalink( $announceID ) . '">' . get_the_post_thumbnail( $announceID, array( 150, 150 ), array( 'class' => 'ui small rounded middle aligned spaced image' ) ) . '</a>';

					$thumbID = get_post_thumbnail_id( $announceID );
				}

				$this_page = self::getCurrentURL();

				$htmlCode .= '<form class="ui form ' . $options['interfaceClass'] . ' segment" method="post" enctype="multipart/form-data" action="' . wp_nonce_url( add_query_arg( 'announceID', $announceID, $this_page ), 'announce' . $announceID, 'verify' ) . '"  name="authorForm">';

				if ( $announceID > 0 ) {
					// default
					$format  = '%1$s: <a href="%2$s">%3$s</a>.';
					$content = sprintf( $format, ucwords( $sPost->post_type ), get_permalink( $announceID ), $sPost->post_title ) . ' ' . wp_trim_excerpt( wp_filter_post_kses( $sPost->post_content ) );
				} else {
					$content = '';
				}

				$htmlCode .= '<h5><i class="bullhorn icon"></i> ' . __( 'Post Update', 'paid-membership' ) . '</h5>';

				$tinymce_options = array(
					'plugins'  => 'link,textcolor',
					'toolbar1' => 'cut,copy,paste,|,undo,redo,|,bold,italic,underline,strikethrough,link,unlink',
				);

				ob_start();
				wp_editor(
					$content,
					'contentUpdate',
					$settings = array(
						'textarea_rows' => 3,
						'media_buttons' => false,
						'teeny'         => true,
						'wpautop'       => false,
						'tinymce'       => $tinymce_options,
					)
				);
				$htmlCode    .= '<div class="ui field" >' . ob_get_clean() . '</div>';

				if ( $announceID > 0 && $thumbID ) {
					   $htmlCode .= '<div class="ui checkbox checked">
  <input type="checkbox" checked="" name="thumbnail">
  <label>' . __( 'Include Thumbnail', 'paid-membership' ) . '</label>
</div>' . $thumbnailCode;
				}

				$htmlCode .= '<br><label>' . __( 'Mention Other Assets', 'paid-membership' ) . ':</label> <select class="ui fluid search dropdown multi" multiple="" id="include" name="include[]" ><option value="">Include</option>';

				$args = array(
					'author'      => $current_user->ID,
					'post_type'   => $post_type,
					'orderby'     => 'modified',
					'order'       => 'DESC',
					'post_status' => array( 'publish' ),
					'posts_per_page' => -1,
				);

				if ( $announceID > 0 ) {
					$args['post__not_in'] = array( $announceID );
				}

				$iPosts = get_posts( $args );
				if ( $iPosts ) {
					foreach ( $iPosts as $iPost ) {
						$htmlCode .= '<option value="' . $iPost->ID . '">' . ucwords( $iPost->post_type ) . ': ' . $iPost->post_title . '</option>';
					}
				}
				$htmlCode .= '</select>';

				$htmlCode .= '<input class="ui compact" name="announceID" type="hidden" id="saveID" value="' . $announceID . '" />';
				$htmlCode .= '<input class="ui compact" name="announce" type="hidden" id="announce" value="' . $announceType . '" />';

				$htmlCode .= '<p><input class="ui button" type="submit" name="save" id="save" value="' . __( 'Post to Feed', 'paid-membership' ) . '" /></p>';

				$htmlCode .= '</form>';
			}
		}

		// announce
		if ( isset($_POST['announce']) ) {
			if ( $announceID = intval( $_GET['announceID'] ) ) {
				if ( $announceID > 0 ) {
					$sPost = get_post( $announceID );
					if ( ! $sPost ) {
						return 'Post not found: ' . $peepsoID;
					}
					if ( $sPost->post_author != $current_user->ID ) {
						return 'Not your post!';
					}
				}

				$announce = sanitize_file_name( $_POST['announce'] );

				// text
				// allowed tags
				$allowedtags = array(
					'a'          => array(
						'href'  => true,
						'title' => true,
					),
					'abbr'       => array(
						'title' => true,
					),
					'acronym'    => array(
						'title' => true,
					),
					'b'          => array(),
					'blockquote' => array(
						'cite' => true,
					),
					'cite'       => array(),
					'code'       => array(),
					'del'        => array(
						'datetime' => true,
					),
					'em'         => array(),
					'i'          => array(),
					'q'          => array(
						'cite' => true,
					),
					'strike'     => array(),
					'strong'     => array(),

					'ul'         => array(),
					'ol'         => array(),
					'li'         => array(),

					'span'       => array(
						'style' => array(),
					),

					'p'          => array(
						'style' => array(),
					),
				);
				$content = wp_kses( $_POST['contentUpdate'], $allowed_tags );

				$thumbnailCode = '';

				if ( $announceID > 0 ) {
					// include thumb
					$thumbnail = boolval( $_POST['thumbnail'] );
					if ( $thumbnail ) {
						$thumbnailCode = '<a href="' . get_permalink( $announceID ) . '">' . get_the_post_thumbnail( $announceID, array( 150, 150 ), array( 'class' => 'ui small rounded spaced image' ) ) . '</a>';
					}
				}

				// include more posts
				if ( is_array( $_POST['include'] ) ) {
					foreach ( $_POST['include'] as $includeP ) {

						$include = intval( $includeP );

						$iPost = get_post( $include );
						if ( $iPost ) {
							   $thumbID = get_post_thumbnail_id( $include );

							if ( $thumbID ) {
								$thumbnailCode .= ' <a href="' . get_permalink( $include ) . '">' . get_the_post_thumbnail(
									$include,
									array( 150, 150 ),
									array(
										'class' => 'ui small rounded spaced image',
										'alt'   => ucwords( $iPost->post_type ) . ': ' . $iPost->post_title,
										'title' => ucwords( $iPost->post_type ) . ': ' . $iPost->post_title,
									)
								) . '</a>';
							} else {
								$thumbnailCode .= ' <a href="' . get_permalink( $include ) . '">' . sanitize_text_field( ucwords( $iPost->post_type ) ) . ': ' . sanitize_text_field( $iPost->post_title ) . '</a>';
							}
						}
					}
				}

				if ( $announce == 'peepso' ) {
					$act    = \PeepSoActivity::get_instance();
					$act_id = $act->add_post( $current_user->ID, $current_user->ID, $content . $thumbnailCode, array( 'show_preview' => 1 ) );
				}

				if ( $announce == 'bp' ) {
					if ( function_exists( 'bp_activity_add' ) ) {
						$args = array(
							'action'       => '<a href="' . bp_members_get_user_url( $sPost ? $sPost->post_author : $current_user->ID ) . 'activity">' . $user->display_name . '</a> ' . $content,
							'component'    => 'micropayments',
							'type'         => 'content_new',
							'primary_link' => get_permalink( $announceID ),
							'user_id'      => $sPost ? $sPost->post_author : $current_user->ID,
							'item_id'      => $announceID,
							'content'      => $thumbnailCode,
						);

						$activity_id = bp_activity_add( $args );
					}
				}

				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment"><i class="bullhorn icon"></i> ' . __( 'Posted Update', 'paid-membership' ) . ':<br><I>' . $content . $thumbnailCode . '</I>' . '</div>';
			}
		}






		// edits

		if ( isset($_POST['save']) ) {

			//content price limits
			$contentMin = floatval( $options['contentMin'] );
			$contentMax = floatval( $options['contentMax'] );

			if ( is_array( $_POST['selected'] ) ) {
				$price = floatval( $_POST['price'] );

				if ($contentMax) $price = min($price, $contentMax);
				$price = max($price, $contentMin);

				foreach ( $_POST['selected'] as $selected ) {
					$sPost = get_post( intval( $selected ) );
					if ( ! $sPost ) {
						return 'Content post not found!';
					}
					if ( $sPost->post_author != $userID ) {
						return 'Not your content!';
					}

					self::contentEdit( $sPost, array( 'price' => $price ), $options );
				}
			}
		}

		if ( isset($_POST['set_subscription']) ) {
			if ( is_array( $_POST['selected'] ) ) {
				$subscription_tier = intval( $_POST['subscription_tier'] );

				foreach ( $_POST['selected'] as $selected ) {
					$sPost = get_post( intval( $selected ) );
					if ( ! $sPost ) {
						return 'Content post not found!';
					}
					if ( $sPost->post_author != $userID ) {
						return 'Not your content!';
					}

					if ($subscription_tier)
					{
						update_post_meta( $sPost->ID, 'vw_subscription_tier', $subscription_tier );

					}
					else
					{
						delete_post_meta( $sPost->ID, 'vw_subscription_tier' );
					}

					self::isPremium($sPost->ID); //update premium marker

				}
			}
		}

		$args = array(
			'author'         => $current_user->ID,
			'post_type'      => $post_type,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'posts_per_page' => 15,
			'post_status'    => array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
			'paged'          => ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1,
		);

		$this_page = remove_query_arg( array( 'peepsoID', 'bpID' ), self::getCurrentURL() );

		/*
		$htmlCode .=  '<form class="ui form segment" method="post" enctype="multipart/form-data" action="' . $this_page .'"  name="adminForm">';

		$htmlCode .=  '<h5>Type</h5>
		<input name="price" type="text" id="price" value="'.$oldPrice.'" size="6" maxlength="6" />

		$htmlCode .=  '<p><input class="ui button" type="submit" name="save" id="save" value="Save" /></p>
		</form>';

		*/

		$postslist = new \WP_Query( $args );

		if ( $postslist->have_posts() ) :
			$htmlCode .= '<form class="ui form" method="post" enctype="multipart/form-data" action="' . $this_page . '"  name="adminForm">';

			// console.log(\'selectAll\');

			$htmlCode .= '<table class="ui celled striped padded table ' . $options['interfaceClass'] . '">
<thead>
    <tr>
    <th> <div class="ui fitted checkbox"><input type="checkbox" id="selectAll" name="selectAll" onchange="jQuery(\':checkbox[id=selected]\').prop(\'checked\', this.checked)"><label></label></div> </th>
    <th>' . __( 'Asset', 'paid-membership' ) . '</th>
    <th>' . __( 'Type', 'paid-membership' ) . ' / ' . __( 'Status', 'paid-membership' ) . ' / ' . __( 'Updated', 'paid-membership' ) . '</th>
    <th>' . __( 'Monetization', 'paid-membership' ) . '</th>
    <th>' . __( 'Action', 'paid-membership' ) . '</th>
    </tr>
</thead>
<tbody>';

			while ( $postslist->have_posts() ) :
				$postslist->the_post();

				$data = self::contentData( get_the_id(), $options );

				$htmlCode .= '<tr>';

				$htmlCode .= '<td> <div class="ui fitted checkbox"><input type="checkbox" id="selected" name="selected[]" value="' . get_the_id() . '"> <label></label> </div> </td>';

				$htmlCode .= '<td> <a href="' . get_permalink() . '" >' . get_the_post_thumbnail( get_the_id(), array( 150, 150 ), array( 'class' => 'ui small rounded middle aligned spaced image' ) ) . '<i class="box icon"></i> ' . get_the_title() . '</a></td>';
				$htmlCode .= '<td>' . get_post_type() . '<br>' . get_post_status() . ' <br>' . get_post_modified_time( 'M d, Y g:i a' ) . ' </td>';
				$htmlCode .= '<td>' . ( intval( $data['subscription_tier'] ) ? '<div>' . __( 'Subscription', 'paid-membership' ) . ' #' . $data['subscription_tier'] . '</div>' : '' ) . ( floatval( $data['price'] ) ? '<div>' . __( 'Price', 'paid-membership' ) . ': ' . $data['price'] . '</div>' : '' ) . ( $data['donations'] ? '<div>' . ucwords( $data['donations'] ) . '</div>' : '' ) . '</td>';
				$htmlCode .= '<td> <a class="ui tiny compact button" href="' . add_query_arg( 'editID', get_the_id(), get_permalink( $options['p_videowhisper_content_edit'] ) ) . '"><i class="edit icon"></i> ' . __( 'Edit', 'paid-membership' ) . '</a>';

				// shout
				if ( class_exists( '\PeepSoActivity' ) ) {
					$htmlCode .= '<br><a class="ui tiny compact button" data-tooltip="' . __( 'Announce', 'paid-membership' ) . '" href="' . add_query_arg( 'peepsoID', get_the_id(), $this_page ) . '"><i class="bullhorn icon"></i> ' . __( 'Post Update', 'paid-membership' ) . '</a>';
				}
				if ( function_exists( 'bp_activity_add' ) ) {
					$htmlCode .= '<br><a class="ui tiny compact button" data-tooltip="' . __( 'Announce', 'paid-membership' ) . '" href="' . add_query_arg( 'bpID', get_the_id(), $this_page ) . '"><i class="bullhorn icon"></i> ' . __( 'Post Update', 'paid-membership' ) . '</a>';
				}

				$htmlCode .= '</td>';

				$htmlCode .= '</tr>';
			endwhile;
			$htmlCode .= '</tbody>
  <tfoot>
  <tr><th colspan="5">';

			$subscriptionsCode = '<div style="min-width:150px; display:inline-block;"> <select class="ui fluid dropdown v-select" name="subscription_tier" >
            <option value="0">None</option>';
			$subscriptions     = get_user_meta( $userID, 'vw_provider_subscriptions', true );
			if ( is_array( $subscriptions ) ) {
				foreach ( $subscriptions as $key => $subscription ) {
						 $subscriptionsCode .= '<option value="' . $key . '">#' . $key . ' ' . $subscription['name'] . '</option>';
				}
			}
			$subscriptionsCode .= '</select></div>
			<script>
jQuery(document).ready(function(){

jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
jQuery(".ui.dropdown.multi").dropdown({
		clearable: true,
		maxSelections: 3
	  });
});

</script>';

			$htmlCode .= '<div class="field inline">  <button class="ui ' . esc_attr( $options['interfaceClass'] ) . ' labeled icon button" name="set_subscription" id="set_subscription" value="set_subscription" type="submit"><i class="lock icon"></i> ' . __( 'Set Subscription', 'paid-membership' ) . '</button> ' . $subscriptionsCode . ' </div>';

			$htmlCode .= '<div class="field inline">  <button class="ui ' . esc_attr( $options['interfaceClass'] ) . ' labeled icon button" name="save" id="save" value="save" type="submit"><i class="cart icon"></i> ' . __( 'Set Price', 'paid-membership' ) . '</button> <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' right labeled input"><input name="price" type="text" id="price" value="0" size="6" maxlength="6" /> <div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' basic label">' . esc_html( $options['currency'] ) . '</div> </div> </div>';

			$htmlCode .= get_previous_posts_link( '<i class="arrow left icon"></i>' . 'Previous Page' );
			$htmlCode .= get_next_posts_link( 'Next Page' . '<i class="arrow right icon"></i>', $postslist->max_num_pages );

			$htmlCode .= '</th>
  </tr></tfoot></table>';

			$htmlCode .= '</form><br>';

			wp_reset_postdata();
	 else :
		 $htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' message">' . __( 'You have no digital content assets, yet.', 'paid-membership' ) . '</div>';
	 endif;

	 // shout
	 if ( class_exists( '\PeepSoActivity' ) ) {
		 $htmlCode .= '<br><a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'PeepSo', 'paid-membership' ) . '" href="' . add_query_arg( 'peepsoID', -1, $this_page ) . '"><i class="bullhorn icon"></i> ' . __( 'Post Update', 'paid-membership' ) . '</a>';
	 }
	 if ( function_exists( 'bp_activity_add' ) ) {
		 $htmlCode .= '<br><a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'Share available content to activity feed', 'paid-membership' ) . '" href="' . add_query_arg( 'bpID', -1, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'Share Content', 'paid-membership' ) . '</a>';
	 }

	 if ( $options['p_videowhisper_content_upload'] ?? false ) {
		 $htmlCode .= ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_content_upload'] ) . '"><i class="upload icon"></i>' . __( 'Upload New Content', 'paid-membership' ) . '</a>';
	 }

	 if ( $options['p_videowhisper_provider_record'] ?? false ) {
		 $htmlCode .= ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_provider_record'] ) . '"><i class="video icon"></i>' . __( 'Record', 'paid-membership' ) . '</a>';
	 }

	 if ( $options['p_videowhisper_provider_subscriptions'] ?? false ) {
		 $htmlCode .= ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>';
	 }

	 $htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' message">' . __( 'This page displays your digital content assets.', 'paid-membership' ) . '</div>';

	 $htmlCode .= self::poweredBy();

	 return $htmlCode;
	}

	static function filename2title($filename, $options = null)
	{
		$filename = sanitize_title( $filename );
		//remove extension if any
		$filename = preg_replace('/\.[^.]*$/', '', $filename);

		//replace - and _ with spaces
		$filename = str_replace(array('-', '_'), ' ', $filename);

		//removed duplicate spaces
		$filename = preg_replace('/\s+/', ' ', $filename);

		if (!$options) $options = self::getOptions();

		//remove csv words defined in $options['titleClean'], only whole words with separators from filename like an space word space , case insensitive
		if ($options['titleClean']?? '')
		{
			$cleanWords = explode(',', $options['titleClean']);
			foreach ($cleanWords as $cleanWord)
			{
				$filename = preg_replace('/\b' . preg_quote($cleanWord, '/') . '\b/i', '', $filename);
			}
		}

		if ($options['titleSize'] ?? 0)
		{
			$filename = substr($filename, 0, $options['titleSize']);
		}

		return $filename;  
	}

	static function videowhisper_content_upload_guest( $atts ) {

        //visitor picture upload form shortcode (secure & simple)
        $options = self::getOptions();

        $atts = shortcode_atts(
            array(
                'category'    => '',
                'gallery'     => '',
                'owner'       => '',
                'tag'         => '',
                'description' => '',
                'terms'   => get_permalink( $options['termsPage'] ?? 0 ),
                'email' => '',
            ),
            $atts,
            'videowhisper_content_upload_guest'
        );

        $current_user = wp_get_current_user();
        $userID = $current_user->ID;
        $username = 'Guest';
        $useremail = '';

		$ownerID = $userID;
		if ( $atts['owner'] ) $ownerID = intval( $atts['owner'] );

        if ($userID)
        {
        $userName     = $options['userName'];
        if ( ! $userName ) {
        $userName = 'user_nicename';
        }
        $username = $current_user->$userName;
        $useremail = $current_user->user_email;
        }

		if (!$userID && !$options['visitorUpload'])
		{
			return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' message">' . __( 'Please login to upload content. Visitor uploads are currently disabled from backend settings.', 'paid-membership' ) .'<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a></div>';
		}

        self::enqueueUI();
        $htmlCode  = '';
		$error     = '';
        $status     = '';

	// file types support
	$extensions = array();
	if ( class_exists( 'VWvideoShare' ) ) $extensions = array_merge( $extensions, \VWvideoShare::extensions_video() );
	if ( class_exists( 'VWpictureGallery' ) ) $extensions = array_merge( $extensions, \VWpictureGallery::extensions_picture() );
	if ( $options['downloads'] ) $extensions = array_merge( $extensions, self::extensions_download() );

//process upload form
if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

    $email = sanitize_email( $_POST['email'] ?? '' );

    $terms = sanitize_text_field( $_POST['terms'] ?? '' );
    if ( ! $terms ) {
        $error .= '<li>' . __( 'Accepting Terms of Use is required.', 'picture-gallery' ) . '</li>';
    }

	if ( ! wp_verify_nonce( $_GET['videowhisper'], 'vw_upload' ) ) {
        $error .= '<li>' . __( 'Nonce incorrect for content upload.', 'picture-gallery' ) . '</li>';
    }

    if ( $options['uploadsIPlimit'] ?? false ) {
        $users = get_posts(
            array(
                'meta_key'     => 'ip_uploader',
                'meta_value'   => self::get_ip_address(),
                'meta_compare' => '=',
            )
        );
        if ( count( $users ) >= intval( $options['uploadsIPlimit'] ) ) {
            $error .= '<li>' . __( 'Uploads per IP limit reached.', 'picture-gallery' ) . ' #' . count( $users ) . '</li>';
        }
    }

    if ( !$email) $error .= '<li>Please specify an email address for this submission!</li>';

//recaptcha
if ( $options['recaptchaSite'] ?? false) {

    if ( isset( $_POST['recaptcha_response'] ) ) {
        if ( $_POST['recaptcha_response'] ) {
            // Build POST request:
            $recaptcha_response = sanitize_text_field( $_POST['recaptcha_response'] );

            // Make and decode POST request:
            // $recaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response);
            // $recaptchaD = json_decode($recaptcha);
            $response   = wp_remote_get( 'https://www.google.com/recaptcha/api/siteverify' . '?secret=' . $options['recaptchaSecret'] . '&response=' . $recaptcha_response );
            $body       = wp_remote_retrieve_body( $response );
            $recaptchaD = json_decode( $body );

            // Take action based on the score returned:
            if ( $recaptchaD->score >= 0.3 ) {
                // Verified
                $htmlCode .= '<!-- VideoWhisper reCAPTCHA v3 score: ' . $recaptchaD->score . '-->';

            } else {
                // Not verified - show form error
                $error .= '<li>Google reCAPTCHA v3 Failed. Score: ' . $recaptchaD->score . ' . Try again or using a different browser!</li>';
            }
        } else {
            $error .= '<li>Google reCAPTCHA v3 empty. Make sure you have JavaScript enabled or try a different browser!</li>';
        }
    } else {
        $error .= '<li>Google reCAPTCHA v3 missing. Make sure you have JavaScript enabled or try a different browser!</li>';
    }
}

if ( !isset($_FILES[ 'fileselect' ]) || !$_FILES[ 'fileselect']['name']) $error .= '<li>Please select a file to upload!</li>';

//process upload if no error
if (!$error)
{
    $dir = sanitize_text_field( $options['uploadsPath'] );
    if ( ! file_exists( $dir ) ) {
        mkdir( $dir );
    }
    $dir .= '/uploads';
    if ( ! file_exists( $dir ) ) {
        mkdir( $dir );
    }
    $dir .= '/_guest';
    if ( ! file_exists( $dir ) ) {
        mkdir( $dir );
    }

    $filename = sanitize_file_name( $_FILES[ 'fileselect' ]['name'] );
    $ext     = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( ! count($extensions) ) {
		$error .=  '<li>' . 'No content type is currently supported for upload. To support uploads, enable Downloads feature to support documents and/or <a href="https://videosharevod.com">VideoShareVOD</a> / <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> plugins!' . '</li>';
	}

	if ( ! in_array( $ext , $extensions ) )  $error .= '<li>' . 'Unsupported extension: ' . esc_html( $ext . ' / ' . implode( ',', $extensions) ) . '</li>';

    if (!$error)
    {
    $newpath = $dir . self::generateName( $filename );

    $errorUp = self::handle_upload( $_FILES[ 'fileselect' ], $newpath ); // handle trough wp_handle_upload()
	if ( $errorUp ) $error .= '<li>' . 'Error uploading ' . esc_html( $filename . ':' . $errorUp ) . '</li>';


    if (!$error)
    {
    //fields
    $title = sanitize_text_field( $_POST['title'] );
    if (!$title) $title = self::filename2title($filename);

    $owner = intval( sanitize_text_field( $_POST['owner'] ) );
    $category =  sanitize_text_field( $_POST['category'] );
    $tag =  sanitize_text_field( $_POST['tag'] );

    $gallery = sanitize_text_field( $_POST['gallery'] );
 		// if csv sanitize as array
        if ( strpos( $gallery, ',' ) !== false ) {
            $galleries = explode( ',', $gallery );
            foreach ( $galleries as $key => $value ) {
                $galleries[ $key ] = sanitize_file_name( trim( $value ) );
            }
            $gallery = $galleries;
        }
        if ( ! $gallery ) $gallery = 'Guest';

        $description =  sanitize_textarea_field( $_POST['description'] );

	//
	if ($options['userTitle'] ?? false)
		{
			$prefix = $username;
			switch ($options['userTitle'])
			{
				case 'username':
					$prefix = $username;
					break;
				case 'display_name':
						$prefix = $current_user->display_name;
						break;
				case 'user_login':
						$prefix = $current_user->user_login;
				break;
				case 'user_nicename':
					$prefix = $current_user->user_nicename;
					break;
				case '5':
					$prefix = substr($username, 0, 5);
				break;
			}
			$prefix .= ' - ';

			$title = $prefix . $title;
		}

    $postID = 0;
	$handleCode = '';

	if ( file_exists( $newpath ) ) {
		$ext = strtolower( pathinfo( $newpath, PATHINFO_EXTENSION ) );

		$handled = 0;
		$interfaceClass = esc_attr( $options['interfaceClass'] );

		//guest imports

		// VideoShareVOD plugin
		if ( class_exists( 'VWvideoShare' ) ) {
			if ( in_array( $ext, \VWvideoShare::extensions_video() ) ) {
				$handleCode .= '<div class="ui segment ' . $interfaceClass . '">' . \VWvideoShare::importFile( $newpath, $title, $userID, $gallery, $category, $tag, $description, $postID, true ) . '</div>';
				$handled = 'video';
			}
		}

		// Picture Gallery plugin
		if ( class_exists( 'VWpictureGallery' ) ) {
			if ( in_array( $ext, \VWpictureGallery::extensions_picture() ) ) {
				$handleCode .= '<div class="ui segment ' . $interfaceClass . '">' . \VWpictureGallery::importFile( $newpath, $title, $userID, $gallery, $category, $tag, $description, $postID, true ) . '</div>';
				$handled = 'picture';
			}
		}

	   // handle downloads using internal MicroPayments - Downloads feature
		if ( in_array( $ext, self::extensions_download() ) ) {
			if ( $options['downloads'] ) {
				$handleCode .= '<div class="ui segment ' . $interfaceClass . '">' . self::importFile( $newpath, $title, $userID, $gallery, $category, $tag, $description, 0, $postID, true ) . '</div>';
				$handled = 'download';
			} else {
				$error .= '<li>' . 'Downloads / documents content are disabled from plugin settings.</li>';
			}
		}

		if (!$handled) $error .= '<li>' . 'Extension not handled by current plugins, options: ' . $ext . '</li>';
	}




    if ($postID)
    {
        update_post_meta( $postID, 'ip_uploader', self::get_ip_address() );
        update_post_meta( $postID, 'email_uploader', $email );

		if ( $options['guestStatus'] )
		{
	    $post = array( 'ID' => $postID, 'post_status' => trim($options['guestStatus']) );
        wp_update_post($post);
		}

       // $link = get_edit_post_link( $postID );
        $link = get_admin_url() . 'post.php?post=' . $postID . '&action=edit&classic-editor';

	    if ($options['moderatorEmail'] ?? false) wp_mail( $options['moderatorEmail'], $options['guestSubject'] , $options['guestText'] . ' ' . $link );

        //show success message
        $htmlCode .= '<div class="ui segment">';
        $htmlCode .= ( $options['guestMessage'] ?? false ) ? $options['guestMessage'] : $handleCode . '<br>Your upload is under review.';
        $htmlCode .= '</div>';

		//handle monetization if $ownerID is available
		if ($ownerID)
		{
			$post = get_post($postID);
			if ($post)
			{

			$htmlCode .= self::contentEdit(
			$post,
			array(
				'price'            => number_format( $_POST['price'], 2, '.', ''  ),
				'priceExpire'      => intval( $_POST['duration'] ),
				'goal_amount'      => floatval( $_POST['goal_amount'] ),
				'goal_stake'       => floatval( $_POST['goal_stake'] ),
				'goal_name'        => sanitize_text_field( $_POST['goal_name'] ),
				'goal_description' => wp_encode_emoji( sanitize_textarea_field( $_POST['goal_description'] ) ),
			),
			$options
		);

			update_post_meta( $postID, 'vw_donations', sanitize_file_name( $_POST['donations'] ) );

			if ($_POST['subscription_tier'] ?? false)
			{
				update_post_meta( $postID, 'vw_subscription_tier', intval( $_POST['subscription_tier'] ) );

			}
			else
			{
				delete_post_meta( $postID, 'vw_subscription_tier' );
			}

			self::isPremium($postID); //update premium marker

			} else $error .= '<li>' .' Error: Could not find post to apply monetization options. #' . $postID . '</li>';
		}

    }
    else
    {
        $error .= '<li>' . 'Error importing (no post ID returned): ' . esc_html( $filename . " / $newpath, $title, $owner, $gallery, $category, $tag, $description ") . '</li>';
		if ($handleCode) $error .= '<li">' . $handleCode . '</li>';
    }

    }
    }
}

//end upload process
}

//upload form

if ( $_SERVER['REQUEST_METHOD'] != 'POST' || $error != '' ) {

    $this_page = get_permalink();
    $recaptchaInput = '';
    $recaptchaCode = '';

    if ( $options['recaptchaSite'] ?? false ) {
        wp_enqueue_script( 'google-recaptcha-v3', 'https://www.google.com/recaptcha/api.js?render=' . $options['recaptchaSite'], array() );

        $recaptchaInput = '<input type="hidden" name="recaptcha_response" id="recaptchaResponse">';

        $recaptchaCode = '<script>
function onSubmitClick(e) {

document.getElementById("loadingMessage").style.visibility = "visible";
document.getElementById("submitButton").disabled = true;

grecaptcha.ready(function() {
  grecaptcha.execute("' . $options['recaptchaSite'] . '", {action: "register"}).then(function(token) {
  var recaptchaResponse = document.getElementById("recaptchaResponse");
  recaptchaResponse.value = token;
  console.log("VideoWhisper Upload: Google reCaptcha v3 updated", token);
  var videowhisperUploadForm = document.getElementById("videowhisperUploadForm");
  videowhisperUploadForm.submit();
  });
});
}
</script>
<noscript>JavaScript is required to use <a href="https://videowhisper.com/">VideoWhisper Picture Uploader</a>. Contact <a href="https://consult.videowhisper.com/">VideoWhisper</a> for clarifications.</noscript>
';
    } else {
        // recaptcha disabled
        $recaptchaCode.= '<script>
function onSubmitClick(e) {

document.getElementById("loadingMessage").style.visibility = "visible";
document.getElementById("submitButton").disabled = true;

console.log("VideoWhisper Upload: Google reCaptcha v3 disabled");
  var videowhisperUploadForm = document.getElementById("videowhisperUploadForm");
  videowhisperUploadForm.submit();

}
</script>
<noscript>JavaScript is required to use <a href="https://videowhisper.com/">VideoWhisper Picture Uploader</a>. Contact <a href="https://consult.videowhisper.com/">VideoWhisper</a> for clarifications.</noscript>
';
    }

        //prefill or input fields
        if ( $atts['category'] ) {
            $categories = '<input type="hidden" name="category" id="category" value="' . $atts['category'] . '"/>';
        } else {
            $categories = '<div class="field><label for="category">' . __( 'Category', 'picture-gallery' ) . ' </label> ' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown+fluid&selected=' . ( isset($_POST['category']) ? intval($_POST['category']) : 0 ) ) . '</div>';
        }

		if ($options['userTitle'] ?? false)
		{
			$prefix = $username;
			switch ($options['userTitle'])
			{
				case 'username':
					$prefix = $username;
					break;
				case 'display_name':
					$prefix = $current_user->display_name;
					break;
				case 'user_login':
						$prefix = $current_user->user_login;
				break;
				case 'user_nicename':
					$prefix = $current_user->user_nicename;
					break;
				case '5':
					$prefix = substr($username, 0, 5);
				break;
			}
			$prefix .= ' - ';

			$title = '<div class="field><label for="title">' . __( 'Title', 'picture-gallery' ) . '</label><br/><div class="ui labeled input">
			<div class="ui label">
			 ' . $prefix . '
			</div><input size="48" maxlength="64" type="text" name="title" id="title" value="' . (  isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '' ) . '" placeholder="' . __( 'Title', 'picture-gallery' ) . '" class="text-input"/></div></div>';
		}
		else
        $title = '<div class="field><label for="title">' . __( 'Title', 'picture-gallery' ) . '</label> <br> <input size="48" maxlength="64" type="text" name="title" id="title" value="' . (  isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '' ) . '" placeholder="' . __( 'Title', 'picture-gallery' ) . '" class="text-input"/></div>';


        if ( $atts['gallery'] ) {
            $galleries = '<input type="hidden" name="gallery" id="gallery" value="' . $atts['gallery'] . '"/>';
        } elseif ( current_user_can( 'edit_users' ) ) {
            $galleries = '<br><label for="gallery">' . __( 'Gallery(s)', 'picture-gallery' ) . '</label> <br> <input size="48" maxlength="64" type="text" name="gallery" id="gallery" value="' . $username . '" class="text-input" placeholder="' . __( 'Gallery(s)', 'picture-gallery' ) . ' ('. __( 'comma separated', 'picture-gallery' ) . ')"/>';
        } else {
            $galleries = '<input type="hidden" name="gallery" id="gallery" value="' . $username . '"/>';
        }

        if ( $atts['owner'] ) {
            $owners = '<input type="hidden" name="owner" id="owner" value="' . $atts['owner'] . '"/>';
        } else {
            $owners = '<input type="hidden" name="owner" id="owner" value="' . $userID . '"/>';
        }

        if ( $atts['email'] || ($userID && $useremail) ) {
            $emails = '<input type="hidden" name="email" id="email" value="' . ( $atts['email'] ? $atts['email'] : $useremail ) . '"/>';
        } else {
            $emails = '<label for="email">' . __( 'Email', 'picture-gallery' ) . '</label><input input size="48" maxlength="64" type="text" name="email" id="email" value="' .(  isset($_POST['email']) ? sanitize_email( $_POST['email'] ) : $useremail ). '" placeholder="' . __( 'Email', 'picture-gallery' ) . '"/> ';
        }

        if ( $atts['tag'] != '_none' ) {
            if ( $atts['tag'] ) {
                $tags = '<input type="hidden" name="tag" id="tag" value="' . $atts['tag'] . '"/>';
            } else {
                $tags = '<br><label for="tag">' . __( 'Tag(s)', 'picture-gallery' ) . '</label> <br> <input size="48" maxlength="64" type="text" name="tag" id="tag" value="' . (  isset( $_POST['tag'] ) ? sanitize_text_field( $_POST['tag'] ) : '' ) . '" class="text-input" placeholder="' . __( 'Tag(s)', 'picture-gallery' ) .' (' . __( 'comma separated', 'picture-gallery' ) . ')"/>';
            }
        }

        if ( $atts['description'] != '_none' ) {
            if ( $atts['description'] ) {
                $descriptions = '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/>';
            } else {
                $descriptions = '<div class="field><label for="description">' . __( 'Description', 'picture-gallery' ) . '</label><textarea name="description" id="description" cols="72" rows="3" placeholder="' . __( 'Description', 'picture-gallery' ) . '">' . (  isset($_POST['description']) ? sanitize_textarea_field( $_POST['description'] ) : '' ) . '</textarea></div>';
            }
        }

    $iPod 	 = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPod' );
    $iPhone  = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPhone' );
    $iPad    = stripos( $_SERVER['HTTP_USER_AGENT'], 'iPad' );
    $Android = stripos( $_SERVER['HTTP_USER_AGENT'], 'Android' );

    if ( $iPhone || $iPad || $iPod || $Android ) {
        $mobile = true;
    } else {
        $mobile = false;
    }

        $actionURL =  wp_nonce_url( $this_page, 'vw_upload', 'videowhisper' ) ;

        $htmlCode .= '<form class="ui ' . $options['interfaceClass'] . ' form ' . $status . '" method="post" enctype="multipart/form-data" action="' .  $actionURL  . '" id="videowhisperUploadForm" name="videowhisperUploadForm">';

        if ( $error ) {
            $htmlCode .= '<div class="ui message">
    <div class="header">' . __( 'Could not submit upload', 'picture-gallery' ) . ':</div>
    <ul class="list">
    ' . $error . '
    </ul>
    </div>';
        }



if ($ownerID)
{
	//monetization options code if logged in user or guest upload with ownerID
		$monetizationCode ='';

	//content price limits
		$contentMin = floatval( $options['contentMin'] );
		$contentMax = floatval( $options['contentMax'] );
		$priceRangeInfo = $contentMin . ( $contentMax ? "-$contentMax" : '+') ;

		$monetizationCode .= '<div class="ui horizontal divider">' . __( 'Monetization', 'paid-membership' ) . '</div>';

//subscription tier
		$monetizationCode     .= '<div class="field"><label for="subscription_tier">' . __( 'Subscription Tier', 'paid-membership' ) . '</label><select class="ui fluid dropdown v-select" name="subscription_tier" >
            <option value="0">' . __( 'None', 'paid-membership' ) . '</option>';
		$subscriptions = get_user_meta( $ownerID, 'vw_provider_subscriptions', true );
		if ( is_array( $subscriptions ) ) {
			foreach ( $subscriptions as $key => $subscription ) {
				$monetizationCode .= '<option value="' . esc_attr( $key ) . '">#' . esc_html( $key ) . ' ' . esc_html( $subscription['name'] ) . '</option>';
			}
		}
		$monetizationCode .= '</select>
			' . __( 'Higher tier subscriptions include access to content from lower tiers.', 'paid-membership' ) . ( $options['p_videowhisper_provider_subscriptions'] ? ' <a href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>' : '' ) . '
			</div>
			<script>
jQuery(document).ready(function(){
	jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
});
</script>';

		//price

		$monetizationCode .= '<div class="field"><label for="price">' . __( 'Sell Price', 'paid-membership' ) . '</label>
			<div class="ui right labeled input"> <input name="price" type="text" id="price" value="0" size="6" maxlength="6" /><div class="ui label">' .  esc_html( $options['currency'] )  .' [' . $priceRangeInfo . ']</div></div>'. __( 'Users need to pay this price to access this content. Set 0 for free access or by subscription. An individual price can also be configured for subscription items: If content is part of a subscription tier, client can opt to get the author subscription instead of purchasing content individually.', 'paid-membership' ) . '</div>';

		if ( in_array( $options['paid_handler'], array( 'mycred', 'micropayments' ) ) ) {
			$monetizationCode .= '<div class="field"><label for="duration">' . __( 'Access Duration', 'paid-membership' ) . '</label>
			<input name="duration" type="text" id="duration" value="0" size="6" maxlength="6" /> hours.
			' . __( 'Set 720 for 30 days, 336 for 2 weeks, 168 for 1 week, 24 for 1 day, 0 for unlimited time access (one time fee).', 'paid-membership' ) . '</div>';
		}

		//donations
		if ( $options['postTypesDonate'] ) {
			$monetizationCode .= '<div class="field"><label for="donations">' . __( 'Donations', 'paid-membership' ) . '</label><select class="ui dropdown v-select" name="donations" id="donations" onchange="showDonationOptions(this.value)"/>
			<option value="donations">' . __( 'Enabled', 'paid-membership' ) . '</option>
			<option value="disabled">' . __( 'Disabled', 'paid-membership' ) . '</option>
			<option value="goal">' . __( 'Goal', 'paid-membership' ) . '</option>
			<option value="crowdfunding" >' . __( 'Crowdfunding', 'paid-membership' ) . '</option>
			</select>' . __( 'Goal will show progress and Crowdfunding will also publicly list all funders.', 'paid-membership' ) . '</div>';

			$monetizationCode .= '<div class="field" id="goal_amount_f" style="display:none"><label for="goal_amount">' . __( 'Goal Amount', 'paid-membership' ) . '</label>
			<input name="goal_amount" type="text" id="goal_amount" value="" size="6" maxlength="6" />
			</div>';

			$monetizationCode .= '<div class="field" id="goal_name_f" style="display:none"><label for="goal_name">' . __( 'Goal Name', 'paid-membership' ) . '</label>
			<input name="goal_name" type="text" id="goal_name" value="" size="48" maxlength="128" />
			</div>';

			$monetizationCode .= '<div class="field" id="goal_description_f" style="display:none"><label for="goal_description">' . __( 'Goal Description', 'paid-membership' ) . ' </label><textarea rows="2" name="goal_description" id="goal_description" class="text-input" /></textarea></div>';

			$monetizationCode .= '<div class="field" id="goal_stake_f" style="display:none"><label for="goal_stake">' . __( 'Crowdfunding Stake', 'paid-membership' ) . ' %</label>
			<input name="goal_stake" type="text" id="goal_stake" value="" size="6" maxlength="6" />' . __( 'Crowdfunding percent for backers.', 'paid-membership' ) . '(0-100)
			</div>';

			$monetizationCode .= '<SCRIPT>function showDonationOptions(value)
			{

				if (value == "donations")
				{
					    document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";

				}

				if (value == "disabled")
				{
				   		document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "goal")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "crowdfunding")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "block";
				}
			}
			</SCRIPT>';
		}
	}



    //    $htmlCode .= '<fieldset>';


	if (count($extensions)) $extensionInfo = __( 'Supported file types', 'paid-membership' ) . ': ' . implode(", ", $extensions);
	else $extensionInfo = 'Error: No file types are supported for upload. Administrator should enable Downloads feature to support documents and/or <a href="https://videosharevod.com">VideoShareVOD</a> / <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> plugins!';

	$htmlCode .= '<div class="ui horizontal divider">' . __( 'File', 'paid-membership' ) . '</div>';

	$htmlCode .= '<div class="field"> <label for="fileselect">' . __( 'File to upload', 'picture-gallery' ) . '</label><input class="ui button" type="file" id="fileselect" name="fileselect" $mobiles /><br><small>' . $extensionInfo . '</small></div>';

	$htmlCode .= '<div class="ui horizontal divider">' . __( 'Details', 'paid-membership' ) . '</div>';

$htmlCode .= <<<HTMLCODE
        $title
        $categories
        $galleries
        $tags
        $descriptions
        $owners
        $emails
        $monetizationCode
        </fieldset>
        HTMLCODE;

		$htmlCode .= '<div class="ui horizontal divider">' . __( 'Upload', 'paid-membership' ) . '</div>';

         $htmlCode .= '<div class="field">
         <div class="ui toggle checkbox">
           <input type="checkbox" name="terms" ' . ( isset( $terms ) && $terms ? 'checked' : '' ) . ' tabindex="0" class="hidden">
           <label>' . __( 'I accept the Terms of Use', 'picture-gallery' ) . ' </label>
         </div>
         <a class="ui tiny button" target="terms" href="' . $atts['terms'] . '"> <i class="clipboard list icon"></i> ' . __( 'Review', 'picture-gallery' ) . '</a>
       </div>';

	     $htmlCode .= $recaptchaInput;

		 $htmlCode .=  '<div id="loadingMessage" class="field" style="visibility:hidden;"><i class="spinner loading icon"></i>' . __( 'Uploading. Please wait...', 'paid-membership' ) . '</div>';

         $htmlCode .= '<div class="field">
         <input type="button" id="submitButton" name="submitButton" onclick="onSubmitClick()" class="ui submit button" value="' . __( 'Upload', 'picture-gallery' ) . '" />
         </div>';

         //end form
         $htmlCode .= '</div>';

       // $htmlCode .= '</fieldset>';

       //terms checkbox
       $htmlCode .= '</form> <script>
			jQuery(document).ready(function(){
jQuery(".ui.checkbox").checkbox();
});
			</script>';

        $htmlCode .= $recaptchaCode;
     }

        //$htmlCode .= '<style type="text/css">' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</style>';

        return $htmlCode;

    }


	static function videowhisper_content_upload( $atts ) {

		$options = get_option( 'VWpaidMembershipOptions' );

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Login is required to upload content!', 'paid-membership' ) . '</div>';
		}

		$current_user = wp_get_current_user();
		if (! self::rolesUser( $options['rolesSeller'], $current_user ) ) return 'Seller features not enabled for your role!';

		$userName     = sanitize_text_field( $options['userName'] );
		if ( ! $userName ) {
			$userName = 'user_nicename';
		}
		$username = $current_user->$userName;

		if ( ! self::hasPriviledge( $options['shareList'] ) ) {
			return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'You do not have permissions to share content!', 'paid-membership' ) . '</div>';
		}

		$atts = shortcode_atts(
			array(
				'multi_select'    => '1',
				'category'    => '',
				'playlist'    => '',
				'owner'       => '',
				'tag'         => '',
				'description' => '',
				'picture'     => '',
			),
			$atts,
			'videowhisper_content_upload'
		);

		$multi_selection = 'true';
		if ( !$atts['multi_select'] || $atts['multi_select'] == '0' ) $multi_selection = 'false';

		self::enqueueUI();

		// plupload + jquery ui  widget
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-progressbar' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'plupload' );

		wp_enqueue_style( 'jquery-ui-css', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui/jquery-ui.min.css' );
		wp_enqueue_style( 'plupload2-widget', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui.plupload/css/jquery.ui.plupload.css' );
		wp_enqueue_script( 'plupload2-widget', dirname( plugin_dir_url( __FILE__ ) ) . '/interface/jquery.ui.plupload/jquery.ui.plupload.min.js', array( 'plupload' ) );

		if ( $atts['category'] ) {
			$categories = '<input type="hidden" name="category" id="category" value="' . esc_attr( $atts['category'] ) . '"/>';
		} else {
			$categories = '<div class="field"><label for="category">' . __( 'Category', 'paid-membership' ) . ' </label>' . wp_dropdown_categories( 'show_count=0&echo=0&name=category&hide_empty=0&class=ui+dropdown' ) . '</div>';
		}

		if ( $atts['playlist'] ) {
			$playlists = '<div class="field"><label for="playlist">' . __( 'List', 'paid-membership' ) . ' </label>' . $esc_html( atts['playlist'] ) . '<input type="hidden" name="playlist" id="playlist" value="' . $atts['playlist'] . '"/></div>';
		} elseif ( current_user_can( 'edit_users' ) ) {
			$playlists = '<div class="field"><label for="playlist">' . __( 'List(s)', 'paid-membership' ) . ': </label> <input size="48" maxlength="64" type="text" name="playlist" id="playlist" value="' . $username . '" class="text-input" placehoder="(comma separated)"/> ';
		} else {
			$playlists = '<div class="field"><label for="playlist">' . __( 'List', 'paid-membership' ) . ' </label> ' . esc_html( $username ) . ' <input type="hidden" name="playlist" id="playlist" value="' . esc_attr( $username ) . '"/></div> ';
		}

		if ( $atts['owner'] ) {
			$owners = '<input type="hidden" name="owner" id="owner" value="' . esc_attr( $atts['owner'] ) . '"/>';
		} else {
			$owners = '<input type="hidden" name="owner" id="owner" value="' . intval( $current_user->ID ) . '"/>';
		}

		if ( $atts['tag'] != '_none' ) {
			if ( $atts['tag'] ) {
				$tags = '<div class="field"><label for="playlist">' . __( 'Tags', 'paid-membership' ) . ' </label>' . esc_html( $atts['tag'] ) . '<input type="hidden" name="tag" id="tag" value="' . esc_attr( $atts['tag'] ) . '"/></div>';
			} else {
				$tags = '<div class="field"><label for="tag">' . __( 'Tag(s)', 'paid-membership' ) . ' </label><input size="48" maxlength="64" type="text" name="tag" id="tag" value="" class="text-input" placeholder="comma separated tags, for all files that will be uploaded"/></div>';
			}
		}

		if ( $atts['description'] != '_none' ) {
			if ( $atts['description'] ) {
				$descriptions = '<div class="field"><label for="description">' . __( 'Description', 'paid-membership' ) . ' </label>' . esc_attr( $atts['description'] ) . '<input type="hidden" name="description" id="description" value="' . $atts['description'] . '"/></div>';
			} else {
				$descriptions = '<div class="field"><label for="description">' . __( 'Description', 'paid-membership' ) . ' </label><textarea rows="2" name="description" id="description" class="text-input" placeholder="description, for all files that will be uploaded"/></textarea></div>';
			}
		}

		if ( $atts['picture'] != '_none' ) {
			if ( $atts['picture'] ) {
				$pictures = '<input type="hidden" name="picture" id="picture" value="' . esc_attr( $atts['picture'] ) . '"/>';
			} else {
				$pictures = '<div class="field><label for="picture">' . __( 'Default Picture', 'paid-membership' ) . ' </label> ' . self::pictureDropdown( $current_user->ID, 0 ) . __( 'A previously uploaded picture can be associated to document uploads. Most content types like videos, pictures have own thumbnails.', 'paid-membership' ) . ' </div>';
			}
		} else {
			$pictures = '<input type="hidden" name="picture" id="picture" value="0"/>';
		}


//monetization
		$monetizationCode ='';

	//content price limits
		$contentMin = floatval( $options['contentMin'] );
		$contentMax = floatval( $options['contentMax'] );
		$priceRangeInfo = $contentMin . ( $contentMax ? "-$contentMax" : '+') ;

		$monetizationCode .= '<div class="ui horizontal divider">' . __( 'Monetization', 'paid-membership' ) . '</div>';

//subscription tier
		$monetizationCode     .= '<div class="field"><label for="subscription_tier">' . __( 'Subscription Tier', 'paid-membership' ) . '</label><select class="ui fluid dropdown v-select" name="subscription_tier" >
            <option value="0">' . __( 'None', 'paid-membership' ) . '</option>';
		$subscriptions = get_user_meta( $current_user->ID, 'vw_provider_subscriptions', true );
		if ( is_array( $subscriptions ) ) {
			foreach ( $subscriptions as $key => $subscription ) {
				$monetizationCode .= '<option value="' . esc_attr( $key ) . '">#' . esc_html( $key ) . ' ' . esc_html( $subscription['name'] ) . '</option>';
			}
		}
		$monetizationCode .= '</select>
			' . __( 'Higher tier subscriptions include access to content from lower tiers.', 'paid-membership' ) . ( $options['p_videowhisper_provider_subscriptions'] ? ' <a href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>' : '' ) . '
			</div>
			<script>
jQuery(document).ready(function(){
	jQuery(".ui.dropdown:not(.multi,.fpsDropdown)").dropdown();
});
</script>';

		//price

		$monetizationCode .= '<div class="field"><label for="price">' . __( 'Sell Price', 'paid-membership' ) . '</label>
			<div class="ui right labeled input"> <input name="price" type="text" id="price" value="0" size="6" maxlength="6" /><div class="ui label">' .  esc_html( $options['currency'] )  .' [' . $priceRangeInfo . ']</div></div>'. __( 'Users need to pay this price to access this content. Set 0 for free access or by subscription. An individual price can also be configured for subscription items: If content is part of a subscription tier, client can opt to get the author subscription instead of purchasing content individually.', 'paid-membership' ) . '</div>';

		if ( in_array( $options['paid_handler'], array( 'mycred', 'micropayments' ) ) ) {
			$monetizationCode .= '<div class="field"><label for="duration">' . __( 'Access Duration', 'paid-membership' ) . '</label>
			<input name="duration" type="text" id="duration" value="0" size="6" maxlength="6" /> hours.
			' . __( 'Set 720 for 30 days, 336 for 2 weeks, 168 for 1 week, 24 for 1 day, 0 for unlimited time access (one time fee).', 'paid-membership' ) . '</div>';
		}

		//donations
		if ( $options['postTypesDonate'] ) {
			$monetizationCode .= '<div class="field"><label for="donations">' . __( 'Donations', 'paid-membership' ) . '</label><select class="ui dropdown v-select" name="donations" id="donations" onchange="showDonationOptions(this.value)"/>
			<option value="donations">' . __( 'Enabled', 'paid-membership' ) . '</option>
			<option value="disabled">' . __( 'Disabled', 'paid-membership' ) . '</option>
			<option value="goal">' . __( 'Goal', 'paid-membership' ) . '</option>
			<option value="crowdfunding" >' . __( 'Crowdfunding', 'paid-membership' ) . '</option>
			</select>' . __( 'Goal will show progress and Crowdfunding will also publicly list all funders.', 'paid-membership' ) . '</div>';

			$monetizationCode .= '<div class="field" id="goal_amount_f" style="display:none"><label for="goal_amount">' . __( 'Goal Amount', 'paid-membership' ) . '</label>
			<input name="goal_amount" type="text" id="goal_amount" value="" size="6" maxlength="6" />
			</div>';

			$monetizationCode .= '<div class="field" id="goal_name_f" style="display:none"><label for="goal_name">' . __( 'Goal Name', 'paid-membership' ) . '</label>
			<input name="goal_name" type="text" id="goal_name" value="" size="48" maxlength="128" />
			</div>';

			$monetizationCode .= '<div class="field" id="goal_description_f" style="display:none"><label for="goal_description">' . __( 'Goal Description', 'paid-membership' ) . ' </label><textarea rows="2" name="goal_description" id="goal_description" class="text-input" /></textarea></div>';

			$monetizationCode .= '<div class="field" id="goal_stake_f" style="display:none"><label for="goal_stake">' . __( 'Crowdfunding Stake', 'paid-membership' ) . ' %</label>
			<input name="goal_stake" type="text" id="goal_stake" value="" size="6" maxlength="6" />' . __( 'Crowdfunding percent for backers.', 'paid-membership' ) . '(0-100)
			</div>';

			$monetizationCode .= '<SCRIPT>function showDonationOptions(value)
			{

				if (value == "donations")
				{
					    document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";

				}

				if (value == "disabled")
				{
				   		document.getElementById("goal_amount_f").style.display =  "none";
					    document.getElementById("goal_name_f").style.display =  "none";
					    document.getElementById("goal_description_f").style.display =  "none";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "goal")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "none";
				}

				if (value == "crowdfunding")
				{
				    	document.getElementById("goal_amount_f").style.display =  "block";
					    document.getElementById("goal_name_f").style.display =  "block";
					    document.getElementById("goal_description_f").style.display =  "block";
					    document.getElementById("goal_stake_f").style.display =  "block";
				}
			}
			</SCRIPT>';
		}


		$htmlCode       = '';
		$interfaceClass = $options['interfaceClass'];

		if ( isset( $_POST['upload'] ) ) if ( $_POST['upload'] == 'VideoWhisper' ) {
			$htmlCode .= '<div class ="ui message"> ' . __( 'Submission Results', 'paid-membership' ) . ':';

			$category_id = intval( $_POST['category'] ?? 0  );
			$owner_id    = intval( $_POST['owner'] ?? get_current_user_id() );
			$description = wp_encode_emoji( sanitize_text_field( $_POST['description'] ?? '' ) );
			$picture     = intval( $_POST['picture'] ?? 0 );

			// if csv sanitize as array
			$playlist = sanitize_text_field( $_POST['playlist'] ?? '' );
			if ( strpos( $playlist, ',' ) !== false ) {
				$playlists = explode( ',', $playlist );
				foreach ( $playlists as $key => $value ) {
					$playlists[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$playlist = $playlists;
			}

			$tag = sanitize_text_field( $_POST['$tag'] ?? ''  );
			if ( strpos( $tag, ',' ) !== false ) {
				$tags = explode( ',', $tag );
				foreach ( $tags as $key => $value ) {
					$tags[ $key ] = sanitize_file_name( trim( $value ) );
				}
				$tag = $tags;
			} else {
				$tag = sanitize_file_name( trim( $tag ) );
			}

			// checks
			if ( $owner_id && ! current_user_can( 'edit_users' ) && $owner_id != $current_user->ID ) {
				return '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . __( 'Only admin can upload for other users!', 'paid-membership' ) . '</div>';
			}
			if ( ! $owner_id ) {
				$owner = $current_user->ID;
			}

			if ( ! $playlist ) {
				$playlist = $current_user->user_nicename;
			}

			$uploader_count = intval( $_POST['uploader_count'] );
			// $htmlCode .= '<br>Files: ' . $uploader_count;

			$targetDir = sanitize_text_field( $options['uploadsPath'] ) . '/plupload/';
			if ( ! file_exists( $options['uploadsPath'] ) ) {
				mkdir( $options['uploadsPath'] );
			}
			if ( ! file_exists( $targetDir ) ) {
				mkdir( $targetDir );
			}

			if ( $uploader_count > 0 ) {
				for ( $i = 0; $i < $uploader_count; $i++ ) {
					$name   = sanitize_file_name( $_POST[ 'uploader_' . $i . '_name' ] );
					$status = sanitize_text_field( $_POST[ 'uploader_' . $i . '_status' ] );

					$el    = array_shift( explode( '.', $name ) );
					$title = self::filename2title($el);

					$path = $targetDir . $name;

					if ( file_exists( $path ) ) {
						$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

						$post_id = 0;

						$handled = 0;

						// VideoShareVOD plugin
						if ( class_exists( 'VWvideoShare' ) ) {
							if ( in_array( $ext, \VWvideoShare::extensions_video() ) ) {
								$htmlCode .= '<div class="ui segment ' . $interfaceClass . '">' . \VWvideoShare::importFile( $path, $title, $owner_id, $playlist, $category_id, $tag, $description, $post_id ) . '</div>';
								$handled = 'video';
							}
						}

						// Picture Gallery plugin
						if ( class_exists( 'VWpictureGallery' ) ) {
							if ( in_array( $ext, \VWpictureGallery::extensions_picture() ) ) {
								$htmlCode .= '<div class="ui segment ' . $interfaceClass . '">' . \VWpictureGallery::importFile( $path, $title, $owner_id, $playlist, $category_id, $tag, $description, $post_id ) . '</div>';
								$handled = 'picture';
							}
						}

					   // handle downloads using internal MicroPayments - Downloads feature
						if ( in_array( $ext, self::extensions_download() ) ) {
							if ( $options['downloads'] ) {
								$htmlCode .= '<div class="ui segment ' . $interfaceClass . '">' . self::importFile( $path, $title, $owner, $playlist, $category_id, $tag, $description, $picture, $post_id ) . '</div>';
								$handled = 'download';
							} else {
								$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'Downloads are disabled from plugin settings. </div>';
							}
						}

						if (!$handled) $htmlCode .= 'Error: Extension not handled: ' . $ext;


						if ($post_id)
						{
							$post = get_post($post_id);
							if ($post)
							{

							$htmlCode .= self::contentEdit(
							$post,
							array(
								'price'            => number_format( $_POST['price'], 2, '.', ''  ),
								'priceExpire'      => intval( $_POST['duration'] ),
								'goal_amount'      => floatval( $_POST['goal_amount'] ),
								'goal_stake'       => floatval( $_POST['goal_stake'] ),
								'goal_name'        => sanitize_text_field( $_POST['goal_name'] ),
								'goal_description' => wp_encode_emoji( sanitize_textarea_field( $_POST['goal_description'] ) ),
							),
							$options
						);

							update_post_meta( $post_id, 'vw_donations', sanitize_file_name( $_POST['donations'] ) );

							if ($_POST['subscription_tier'] ?? false)
							{
								update_post_meta( $post_id, 'vw_subscription_tier', intval( $_POST['subscription_tier'] ) );

							}
							else
							{
								delete_post_meta( $post_id, 'vw_subscription_tier' );
							}

							self::isPremium($post_id); //update premium marker

							} else $htmlCode .= ' Error: Could not find post #' . $post_id ;

						} else ' Error: No post id for item #' . $i;


					} else {
						$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'Missing upload: ' . $name . ' Upload can fail due to restricted filename (like extra dots or special characters), unsupported extension, incorrect upload path configuration or folder permissions, web hosting upload restrictions or upload timeout.</div>';
					}
				}
			} else {
				$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'No uploads received with form submission: uploader_count=0 . Try using Start Upload button from widget or a different browser.' . '</div>';

				foreach ( $_POST as $name => $value ) {
					$htmlCode .= '<br> - ' . esc_html( stripslashes( $name ) ) . ' = ';
					$htmlCode .= nl2br( esc_html( stripslashes( $value ) ) );
				}
			}

			if ( $options['p_videowhisper_content_seller'] ) {
				$htmlCode .= '<p> ' . ' <a class="ui label small" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a> ' . __( 'Details for each item can be later edited individually from assets page.', 'paid-membership' ) . '</p>';
			}

			$htmlCode .= '</div><h4 class="ui header">Add more:</H4>';
		}

		// file types support
		$extensions = array();

		$mime_types = '';

		if ( class_exists( 'VWvideoShare' ) ) {
			$mime_types .= ( $mime_types ? ', ' : '' ) . '{title : "Videos", extensions : "' . implode( ',', \VWvideoShare::extensions_video() ) . '"}';
			$extensions = array_merge( $extensions, \VWvideoShare::extensions_video() );
		}

		if ( class_exists( 'VWpictureGallery' ) ) {
			$mime_types .= ( $mime_types ? ', ' : '' ) . '{title : "Pictures", extensions : "' . implode( ',', \VWpictureGallery::extensions_picture() ) . '"}';
			$extensions = array_merge( $extensions, \VWpictureGallery::extensions_picture() );
		}

		if ( $options['downloads'] ) {
			$mime_types .= ( $mime_types ? ', ' : '' ) . '{title : "Documents", extensions : "' . implode( ',', self::extensions_download() ) . '"}';
			$extensions = array_merge( $extensions, self::extensions_download() );

		}

		if ( ! $mime_types ) {
			return $htmlCode . '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment orange">' . 'No content type is currently supported for upload. To support uploads, enable Downloads feature and/or <a href="https://videosharevod.com">VideoShareVOD</a> / <a href="https://wordpress.org/plugins/picture-gallery/">Picture Gallery</a> plugins!' . '</div>';
		}

		$ajaxurl = admin_url() . 'admin-ajax.php?action=vwpm_plupload';

		$thisPage = self::getCurrentURLfull();

		if ( $options['p_videowhisper_content_seller'] ) {
			$info = '<p> ' . ' <a class="ui label small" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a> ' . __( 'Details for each item can be later edited individually from assets page.', 'paid-membership' ) . '</p>';
		}

		$t_files   = __( 'Add Files', 'paid-membership' );
		$t_details = __( 'Add Common Details', 'paid-membership' );
		$t_submit  = __( 'Add Content', 'paid-membership' );
		$t_extensions = __( 'Supported file types', 'paid-membership' ) . ': ' . implode(", ", $extensions);


		$htmlCode .= <<<HTMLCODE
<form class="ui $interfaceClass form" id="uploaderForm" method="post" action="$thisPage">
<h4 class="ui header"><i class="file video icon"></i> 1. $t_files</H4>
	<div id="uploader">
		<p>PLupload could not be loaded or your browser doesn't have Flash, Silverlight or HTML5 support.</p>
	</div>
<small>$t_extensions</small>
<h4 class="ui header"><i class="list icon"></i> 2. $t_details</H4>

<fieldset>
$info
$categories
$playlists
$tags
$descriptions
$owners
$pictures
$monetizationCode
</fieldset>



<h4 class="ui header"><i class="save icon"></i> 3. $t_submit</H4>
<input type="hidden" id="upload" name="upload" value="VideoWhisper" />

<input class="ui button primary" type="submit" value="$t_submit" />

</form>

<script type="text/javascript">
// Initialize the widget when the DOM is ready
jQuery(function() {
	jQuery("#uploader").plupload({

		// General settings
		runtimes : 'html5,flash,silverlight,html4',
		url : '$ajaxurl',
		multi_selection: $multi_selection,

		// User can upload no more then 20 files in one go (sets multiple_queues to false)
		max_file_count: 20,

		chunk_size: '1mb',
		max_retries: 5,

		filters : {
			// Maximum file size
			max_file_size : '6000mb',

			// Specify what files to browse for
			mime_types: [
				$mime_types
			],
			prevent_duplicates: true
		},

		// Rename files by clicking on their titles
		rename: true,

		// Sort files
		sortable: true,

		// Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,

		// Views to activate
		views: {
			list: true,
			thumbs: true, // Show thumbs
			active: 'thumbs'
		},

	});


//	$('#uploader').plupload('notify', 'info', "This might be obvious, but you need to click 'Add Files' to add some files.");


jQuery('#uploader').on('error', function(event, args) {
			jQuery('#uploader').plupload('notify', 'error', args.error.message);
			//console.log(args, event);
							});

//	$('#uploader').plupload.ua = navigator.userAgent;

	// Handle the case when form was submitted before uploading has finished
	jQuery('#uploaderForm').submit(function(e) {

		// Files in queue upload them first
		if (jQuery('#uploader').plupload('getFiles').length > 0) {

			// When all files are uploaded submit form
			jQuery('#uploader').on('complete', function() {
				console.log('Plupload Complete. Submitting form ...');

				jQuery('#uploaderForm')[0].submit();
			});

			jQuery('#uploader').plupload('start');
			console.log('Uploading files ...');

		} else {
			alert("You must have at least one file in the queue.");
		}

		e.preventDefault();

		return false; // Keep the form from submitting
	});
});
</script>

HTMLCODE;


 // shout

 	 if ( $options['p_videowhisper_content_seller'] ) {
		 $htmlCode .= '<hr><a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'My Digital Content Assets', 'paid-membership' ) . '" href="' . get_permalink( $options['p_videowhisper_content_seller'] ) . '"><i class="boxes icon"></i>' . __( 'My Assets', 'paid-membership' ) . '</a>';
	 }

	 if ( class_exists( '\PeepSoActivity' ) ) {
		 $htmlCode .= '<a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'PeepSo', 'paid-membership' ) . '" href="' . add_query_arg( 'peepsoID', -1, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'Post Update', 'paid-membership' ) . '</a>';
	 }
	 if ( function_exists( 'bp_activity_add' ) ) {
		 $htmlCode .= '<a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" data-tooltip="' . __( 'Share available content to activity feed', 'paid-membership' ) . '" href="' . add_query_arg( 'bpID', -1, get_permalink( $options['p_videowhisper_content_seller'] ) ) . '"><i class="bullhorn icon"></i> ' . __( 'Share Content', 'paid-membership' ) . '</a>';
	 }


	 if ( $options['p_videowhisper_provider_record'] ?? false ) {
		 $htmlCode .= ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_provider_record'] ) . '"><i class="video icon"></i>' . __( 'Record', 'paid-membership' ) . '</a>';
	 }

	 if ( $options['p_videowhisper_provider_subscriptions'] ?? false ) {
		 $htmlCode .= ' <a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' small button" href="' . get_permalink( $options['p_videowhisper_provider_subscriptions'] ) . '"><i class="lock icon"></i>' . __( 'Setup Subscriptions', 'paid-membership' ) . '</a>';
	 }

	 $htmlCode .= self::poweredBy();

		return $htmlCode;
	}


	// ajax handler for plupload
	static function vwpm_plupload() {

		$options = get_option( 'VWpaidMembershipOptions' );

		// Make sure file is not cached (as it happens for example on iOS devices)
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s' ) . ' GMT' );
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Cache-Control: post-check=0, pre-check=0', false );
		header( 'Pragma: no-cache' );

		// Support CORS
		header( 'Access-Control-Allow-Origin: *' );
		header( 'Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method' );
		header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE' );
		header( 'Allow: GET, POST, OPTIONS, PUT, DELETE' );

		// other CORS headers if any...
		if ( $_SERVER['REQUEST_METHOD'] == 'OPTIONS' ) {
			exit; // finish preflight CORS requests here
		}

		if ( ! is_user_logged_in() ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 201, "message": "User login required for upload."}, "id" : "id"}' );
			exit;
		}

		// Settings
		$targetDir = sanitize_text_field( $options['uploadsPath'] ) . '/plupload';
		// $targetDir = 'uploads';
		$cleanupTargetDir = true; // Remove old files
		$maxFileAge       = 6 * 3600; // Temp file age in seconds 6h

		// Create target dir
		if ( ! file_exists( $options['uploadsPath'] ) ) {
			mkdir( $options['uploadsPath'] );
		}

		if ( ! file_exists( $options['uploadsPath'] ) ) {
			@mkdir( $options['uploadsPath'] );
		}

		if ( ! file_exists( $targetDir ) ) {
			@mkdir( $targetDir );
		}

		// Get a file name
		if ( isset( $_REQUEST['name'] ) ) {
			$fileName = sanitize_file_name( $_REQUEST['name'] );
		} elseif ( ! empty( $_FILES ) ) {
			$fileName = sanitize_file_name( $_FILES['file']['name'] );
		} else {
			$fileName = uniqid( 'file_' );
		}

		// double check extension server side
		$ext = strtolower( pathinfo( $fileName, PATHINFO_EXTENSION ) );

		$extensions = array();
		if ( $options['downloads'] ) {
			$extensions = array_merge( $extensions, self::extensions_download() );
		}
		if ( class_exists( 'VWvideoShare' ) ) {
			$extensions = array_merge( $extensions, \VWvideoShare::extensions_video() );
		}
		if ( class_exists( 'VWpictureGallery' ) ) {
			$extensions = array_merge( $extensions, \VWpictureGallery::extensions_picture() );
		}

		if ( ! in_array( $ext, $extensions ) ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 203, "message": "File extension is not supported. (' . esc_html( $ext ) . '/' . esc_html( implode( ',', $extensions ) ) . ' )"}, "id" : "id"}' );
			exit;
		}

		$filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

		// Chunking might be enabled
		$chunk  = isset( $_REQUEST['chunk'] ) ? intval( $_REQUEST['chunk'] ) : 0;
		$chunks = isset( $_REQUEST['chunks'] ) ? intval( $_REQUEST['chunks'] ) : 0;

		// Remove old temp files
		if ( $cleanupTargetDir ) {
			if ( ! is_dir( $targetDir ) || ! $dir = opendir( $targetDir ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory. ' . esc_html( $targetDir ) . '"}, "id" : "id"}' );
			}

			while ( ( $file = readdir( $dir ) ) !== false ) {
				$tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;

				// If temp file is current file proceed to the next
				if ( $tmpfilePath == "{$filePath}.part" ) {
					continue;
				}

				// Remove temp file if it is older than the max age and is not the current file
				if ( preg_match( '/\.part$/', $file ) && ( filemtime( $tmpfilePath ) < time() - $maxFileAge ) ) {
					@unlink( $tmpfilePath );
				}
			}
			closedir( $dir );
		}

		// Open temp file
		if ( ! $out = @fopen( "{$filePath}.part", $chunks ? 'ab' : 'wb' ) ) {
			die( '{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}' );
		}

		if ( ! empty( $_FILES ) ) {
			if ( $_FILES['file']['error'] || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}' );
			}

			// Read binary input stream and append it to temp file
			if ( ! $in = @fopen( $_FILES['file']['tmp_name'], 'rb' ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
			}
		} else {
			if ( ! $in = @fopen( 'php://input', 'rb' ) ) {
				die( '{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}' );
			}
		}

		while ( $buff = fread( $in, 4096 ) ) {
			fwrite( $out, $buff );
		}

		@fclose( $out );
		@fclose( $in );

		// Check if file has been uploaded
		if ( ! $chunks || $chunk == $chunks - 1 ) {
			// Strip the temp .part suffix off
			rename( "{$filePath}.part", $filePath );
			// completed
		}

		// Return Success JSON-RPC response
		die( '{"jsonrpc" : "2.0", "result" : null, "id" : "id"}' );
	}


	static function videowhisper_content( $atts ) {

		//list content purchased by user, by MicroPayments and/or WooCommerce products
		self::enqueueUI();

		// checks
		if ( ! is_user_logged_in() ) {
			return 'Only registered users can own content!' . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$options      = get_option( 'VWpaidMembershipOptions' );

		$htmlCode = '';
		$current_user = wp_get_current_user();


		//$htmlCode .= '<h3 class="ui ' . $options['interfaceClass'] . ' header">' . __( 'My Purchased Items', 'paid-membership' ) . '</h3>';


		$purchaseCode = '';

		//MicroPayments
		$purchases = get_user_meta( $current_user->ID, 'vw_client_purchase_list', true );
		if (!is_array($purchases)) $purchases = array();

		foreach ( $purchases as $product_id => $purchase_expiration )
		{
		$product = get_post($product_id);
		$purchaseCode  .= '<a class="ui button small" href="' . get_permalink( $product_id ) . '"> <i class="box icon"></i> ' . esc_html( $product->post_title ) . '</a> <i class="calendar icon"></i>' . ( $purchase_expiration == -1 ?  __('Lifetime', 'paid-membership')  : ' ' . __('until', 'paid-membership') . ' ' . date("D M j G:i:s T Y", $purchase_expiration ) ) . '<br>';
		}

		//WooCommerce
		if ( function_exists( 'wc_get_order_types' ) ) {
			// GET USER ORDERS (COMPLETED + PROCESSING)
			$customer_orders = get_posts(
				array(
					'numberposts' => -1,
					'posts_per_page' => -1,
					'meta_key'    => '_customer_user',
					'meta_value'  => $current_user->ID,
					'post_type'   => wc_get_order_types(),
					'post_status' => 'wc-completed', // Only orders with "completed" status
				// 'post_status' => array_keys( wc_get_is_paid_statuses()),
				)
			);

			$purchasedProduct = 0;

			// LOOP THROUGH ORDERS AND GET PRODUCT IDS
			if ( ! $customer_orders ) {
				//$htmlCode .= 'No purchases, yet.';
			} else {
				$product_ids = array();
				foreach ( $customer_orders as $customer_order ) {
					$order = wc_get_order( $customer_order->ID );
					$items = $order->get_items();
					foreach ( $items as $item ) {
						$product_id = $item->get_product_id();    // https://docs.woocommerce.com/wc-apidocs/class-WC_Order_Item_Product.html
						$product    = get_post( $product_id );
						$purchaseCode  .= '<a class="ui button small" href="' . get_permalink( $product_id ) . '"> <i class="box icon"></i> ' . esc_html( $product->post_title ) . '</a> <i class="money bill alternate icon"></i>' . $item->get_total() . '<br>';
					}
				}
			}
		} else {
			// $htmlCode .= 'WooCommerce is required for purchase list.';
		}

		if ($purchaseCode) $htmlCode .= $purchaseCode;
		else $htmlCode .= '<div class="ui message"> <i class="shopping cart icon"></i> ' . __('No purchases, yet. This section displays your digital content purchases, for easy access.', 'paid-membership') . '</div>';

		return $htmlCode;
	}

	static function videowhisper_wallet( $atts ) {
		$options = get_option( 'VWpaidMembershipOptions' );

		if ( ! is_user_logged_in() ) {
			return  '<a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' button primary qbutton tiny compact" href="' . wp_login_url() . '">' . __( 'Login for Wallet', 'paid-membership' )  . '</a>';
		}

		$current_user = wp_get_current_user();

		// process any package purchases for this user
		self::packages_process( $current_user->ID );

		$htmlCode = '
<a class="ui ' . esc_attr( $options['interfaceClass'] ) . ' labeled button tiny compact" tabindex="0" href="' . get_permalink( $options['p_videowhisper_my_wallet'] ) . '">
  <div class="ui green button tiny compact">
    <i class="money bill alternate icon"></i>
  </div>
  <div class="ui left pointing label label tiny compact">
  ' . self::balance( $current_user->ID ) . '
  </div>
</a>';

		return $htmlCode;
	}

	static function videowhisper_packages_process($atts)
	{
		$options = self::getOptions();

		$atts = shortcode_atts(
			array(
				'user_id' 	  => 0,
				'verbose' 	  => 1,
			),
			$atts,
			'videowhisper_packages_process'
		);

		$uid = intval( $atts['user_id'] ?? 0 );
		if ($uid == '-1') $uid = get_current_user_id();
		$verbose = intval( $atts['verbose'] ?? 0 );

		$info  = self::packages_process( $uid, $verbose);

		if ($verbose) return '<!-- videowhisper_packages_process: ' . $info . ' -->';
		else return '';
	}

	static function videowhisper_my_wallet( $atts ) {
		$options = self::getOptions();

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return __( 'Login to manage wallet!', 'paid-membership' ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$htmlCode = '';
		$user_ID = get_current_user_id();

		// process any package purchases for this user
		self::packages_process( $user_ID );

		$htmlCode .= '<div class="ui green ' . esc_attr( $options['interfaceClass'] ) . ' segment form">';
		$htmlCode .= '<h4>' . __( 'Active balance', 'paid-membership' ) . ': ' . self::balance( $user_ID ) . '</h4>';
		if ($options['walletMulti']) $htmlCode .= __( 'All Balances', 'paid-membership' ) . ': ' . self::balances( $user_ID );
		$htmlCode .= '</div>';

	//accordion

		$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment">';
		$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' fluid accordion">';


		//token packages
		if ( shortcode_exists( 'products' ) && function_exists('wc_get_products') )
		if ( !($options['rolesPackages'] ?? false) || self::rolesUser( $options['rolesPackages'], wp_get_current_user() ) )
		{
			$active = 'active';

			//deactivate if other used
			if ( isset( $_GET['orderby'] ) || isset( $_GET['paged'] ) ) $active = '';
			if ( isset( $_GET['page'] ) || get_query_var( 'page' ) ) $active = '';
			if ( isset( $_GET['wallet_action'] ) || get_query_var( 'wallet_action' ) ) $active = '';

				$packages = wc_get_products(array(
					'limit'        => -1, // Retrieves all products
					'status'       => 'publish',
					'type'         => 'simple', // Add if you are looking for a specific type of product, e.g., 'simple', 'variable', etc.
					'meta_key'     => 'micropayments_tokens', // Specify the meta key to check for existence.
					'meta_value'   => '', // Required if 'meta_compare' is used.
					'meta_compare' => 'EXISTS' // Ensures only products with this meta key are returned.
				));

		if ( $packages || !empty( $packages ) ) {
				$IDs = '';
				foreach ( $packages as $package ) $IDs .= ($IDs ? ',' : '') . intval( $package->get_id() );

			$htmlCode .= '<div class="ui ' . $active . ' title"><i class="dropdown icon"></i>' . __( 'Token Packages', 'paid-membership' ) . '</div>
			<div class="' .$active . ' content" style="overflow: auto">
			' . do_shortcode( '[products ids="' . $IDs . '"]' ) . '
			</div>';
				}
		}

		if ( shortcode_exists( 'videowhisper_transactions' ) ) {

			$active = '';
			if ( isset($_GET['orderby']) || isset($_GET['paged']) ) $active = 'active';

			$htmlCode .= '<div class="ui ' . $active . ' title"><i class="dropdown icon"></i>' . __( 'MicroPayments: Transactions', 'paid-membership' ) . '</div>
			<div class="' .$active . ' content" style="overflow: auto">
			' . do_shortcode( '[videowhisper_transactions user_id="' . $user_ID . '"]' ) . '
			</div>';
		};


		if ( shortcode_exists( 'mycred_buy_form' ) ) {

			$active = '';
			if ( isset( $_GET['page'] ) || get_query_var( 'page' ) ) $active = 'active';

			$htmlCode .= '<div class="ui ' . $active . ' title"><i class="dropdown icon"></i>' . __( 'MyCred: Transactions', 'paid-membership' ) . '</div>
			<div class="' .$active . ' content" style="overflow: auto">'
			. do_shortcode( '[mycred_buy_form]' ) .
			 '</div>';

			$htmlCode .= '<script>
var $jQ = jQuery.noConflict();
$jQ(document).ready(function(){
$jQ(":submit.btn").addClass("ui button");
$jQ("[name=mycred_buy]").addClass("ui dropdown v-select");
});
</script>';
		}

		if ( shortcode_exists( 'woo-wallet' ) ) {

			$active = '';
			if ( isset($_GET['wallet_action']) || get_query_var( 'wallet_action' ) ) $active = 'active';

			$htmlCode .= '<div class="ui ' . $active . ' title"><i class="dropdown icon"></i>' . __( 'TeraWallet: Transactions', 'paid-membership' ) . '</div>
			<div class="' .$active . ' content" style="overflow: auto">'
			 . do_shortcode( '[woo-wallet]' ) .
			'</div>';
		};

			$htmlCode .= '</div>
			</div>
<script>
var $jQ = jQuery.noConflict();
$jQ(document).ready(function(){
$jQ(".ui.accordion").accordion();
});
</script>
<STYLE>
.ui.title, .ui.label, .ui.compact.button{
  height: auto !important;
}
</STYLE>';

		return $htmlCode;
	}


	static function videowhisper_membership_buy( $atts ) {

		$options = self::getOptions();

		self::enqueueUI();

		if ( ! is_user_logged_in() ) {
			return stripslashes( $options['loginMessage'] ) . '<BR><a class="ui button primary qbutton" href="' . wp_login_url() . '">' . __( 'Login', 'paid-membership' ) . '</a>  <a class="ui button secondary qbutton" href="' . wp_registration_url() . '">' . __( 'Register', 'paid-membership' ) . '</a>';
		}

		$htmlCode = '';

		$user_ID = get_current_user_id();

		$memberships = $options['memberships'];

		$htmlCode .= '<div class="ui ' . esc_attr( $options['interfaceClass'] ) . ' segment form">';

		// setup membership
		if ( isset( $_POST['membership_id'] ) ) {
			$membership_id = (int) $_POST['membership_id'];
			if ( $memberships[ $membership_id ] ) {
				$membership = $memberships[ $membership_id ];

				if ( current_user_can( 'administrator' ) ) {
					$htmlCode .= '<h4>Error: Administrators can not purchase different role as that can disable backend access!</h4>';
				} elseif ( ! self::membership_setup( $membership, $user_ID ) ) {
					$htmlCode .= '<h4>Error: Your balance does not cover this membership!</h4>';
				} else {
					$htmlCode .= '<h4>Membership was activated: ' . $membership['label'] . '</h4>';
				}
			}
		}

		// cancel current membership
		if ( $_GET['cancel_membership'] ?? false ) if ( $_GET['cancel_membership'] == '1' ) {
			self::membership_cancel( $user_ID );
			$htmlCode .= '<h4>Membership was cancelled: automated renewal will no longer occur!</h4>';
		}

		global $wp;
		$current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );

		// current user membership
		$memInfo = self::membership_info( $user_ID );
		if ( $memInfo ) {
			$htmlCode .= 'Current Membership: ' . $memInfo;
		}

		$membership = get_user_meta( $user_ID, 'vw_paid_membership', true );
		if ( $membership ) {
			if ( $membership['recurring'] ) {
				$htmlCode .= '<BR><a href="' . add_query_arg( 'cancel_membership', '1', $current_url ) . '" class="ui button">Cancel Automated Renewal</a><BR>';
			}
		}

		$htmlCode .= '<BR>Current Role: ' . self::get_current_user_role();
		$htmlCode .= '<BR>Current Balance: ' . self::balance( $user_ID );

		if ( count( $memberships ) ) {
			$htmlCode .= '<BR><h4>Select Your Membership</h4>';
			if ( ! is_array( $memberships ) ) {
				$htmlCode .= 'No memberships defined, yet!';
			} else {
				foreach ( $memberships as $i => $membership ) {
					$htmlCode .= '<form class="paid_membership_listing ui ' . esc_attr( $options['interfaceClass'] ) . ' segment" action="' . esc_url_raw( $current_url ) . '" method="post">';
					$htmlCode .= '<h4>' . esc_html( $membership['label'] ) . '</h4>';
					$htmlCode .= 'Role: ' . esc_html( $membership['role'] );
					$htmlCode .= '<br>Duration: ' . esc_html( $membership['expire'] ) . ' days';
					$htmlCode .= '<br>' . ( $membership['recurring'] ? 'Automated Renewal' : 'One Time' );
					$htmlCode .= '<br>Price: ' . esc_html( $membership['price'] );
					$htmlCode .= '<input id="membership_id" name="membership_id" type="hidden" value="' . $i . '">';
					$htmlCode .= '<br><input class="ui button qbutton" id="submit" name="submit" type="submit" value="Buy Now">';
					$htmlCode .= '</form>';
				}
			}

			$htmlCode .= '<STYLE>' . html_entity_decode( stripslashes( $options['customCSS'] ) ) . '</STYLE>';
		} else {
			$htmlCode .= 'No memberships were setup from backend.';
		}

		$htmlCode .= '</div>';

		return $htmlCode;
	}


	static function membership_info( $user_ID ) {
		$membership = get_user_meta( $user_ID, 'vw_paid_membership', true );
		if ( ! $membership ) {
			return;
		}

		$htmlCode .= '<B>' . $membership['label'] . '</B> : ';
		$htmlCode .= ' ' . $membership['role'];
		$htmlCode .= ', ' . $membership['expire'] . ' days';
		$htmlCode .= ' until ' . date( 'M j G:i:s T Y', $membership['expires'] ) . '';

		$htmlCode .= ', ' . ( $membership['recurring'] ? 'recurring' : 'no renew' );
		if ( $membership['lastCharge'] ) {
			$htmlCode .= ', last paid ' . date( 'M j G:i:s T Y', $membership['lastCharge'] ) . '';
		}

		return $htmlCode;
	}


static function videowhisper_transactions( $atts ) {

		//MicroPayments wallet transactions

		$options = get_option( 'VWpaidMembershipOptions' );

		$atts = shortcode_atts(
			array(

				'user_id' 	  => 0,
			),
			$atts,
			'videowhisper_transactions'
		);

		if (!$atts['user_id']) return 'user_id required';

		self::enqueueUI();

 require_once(ABSPATH . 'wp-admin/includes/screen.php');
  require_once(ABSPATH . 'wp-admin/includes/class-wp-screen.php');
  require_once(ABSPATH . 'wp-admin/includes/template.php');


   // wp_enqueue_style( 'admin-css', admin_url( 'css/wp-admin.css' ), array(), '1.0' );

   //https://core.trac.wordpress.org/browser/trunk/src/wp-admin/css/list-tables.css

$tableCSS = '<STYLE>
.wp-list-table a {
        transition: none;
}

        .wp-list-table .column-primary .toggle-row {
                display: none;
        }

.widefat th.sortable,
.widefat th.sorted {
        padding: 0;
}
th.sortable a,
th.sorted a {
        display: block;
        overflow: hidden;
        padding: 8px;
}
.fixed .column-comments.sortable a,
.fixed .column-comments.sorted a {
        padding: 8px 0;
}
th.sortable a span,
th.sorted a span {
        float: left;
        cursor: pointer;
}
th.sorted .sorting-indicator,
th.desc:hover span.sorting-indicator,
th.desc a:focus span.sorting-indicator,
th.asc:hover span.sorting-indicator,
th.asc a:focus span.sorting-indicator {
        visibility: visible;
}

.tablenav-pages .current-page {
        margin: 0 2px 0 0;
        padding-top: 5px;
        padding-bottom: 5px;
        font-size: 13px;
        text-align: center;
}
.tablenav .total-pages {
        margin-right: 2px;
}
.tablenav #table-paging {
        margin-left: 2px;
}
.tablenav {
        clear: both;
        height: 30px;
        margin: 6px 0 4px;
        vertical-align: middle;
}
.tablenav.themes {
        max-width: 98%;
}
.tablenav .tablenav-pages {
        float: right;
        margin: 3px 0 9px;
}
.tablenav .no-pages,
.tablenav .one-page .pagination-links {
        display: none;
}
.tablenav .tablenav-pages .button,
.tablenav .tablenav-pages .tablenav-pages-navspan {
        display: inline-block;
        vertical-align: baseline;
        min-width: 28px;
        min-height: 28px;
        margin: 0;
        padding: 0 4px;
        font-size: 16px;
        line-height: 1.5;
        text-align: center;
}
.tablenav .displaying-num {
        margin-right: 7px;
}
.tablenav .one-page .displaying-num {
        display: inline-block;
        margin: 5px 0;
}
.tablenav .actions {
        overflow: hidden;
        padding: 2px 8px 0 0;
}
.wp-filter .actions {
        display: inline-block;
        vertical-align: middle;
}
.tablenav .delete {
        margin-right: 20px;
}
/* This view-switcher is still used on multisite. */
.tablenav .view-switch {
        float: right;
        margin: 0 5px;
        padding-top: 3px;
}
.sorting-indicator {
        display: block;
        visibility: hidden;
        width: 10px;
        height: 4px;
        margin-top: 8px;
        margin-left: 7px;
}
.sorting-indicator:before {
        content: "\f142";
        font: normal 20px/1 dashicons;
        speak: none;
        display: inline-block;
        padding: 0;
        top: -4px;
        left: -8px;
        color: #444;
        line-height: 10px;
        position: relative;
        vertical-align: top;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-decoration: none !important;
        color: #444;
}

</STYLE>';

//fix request for pagination
if(!isset($_REQUEST['paged'])) {
	$_REQUEST['paged'] = explode('/page/', $_SERVER['REQUEST_URI'], 2);
	if(isset($_REQUEST['paged'][1])) list($_REQUEST['paged'],) = explode('/', $_REQUEST['paged'][1], 2);
	if(isset($_REQUEST['paged']) and $_REQUEST['paged'] != '') {
		$_REQUEST['paged'] = intval($_REQUEST['paged']);
		if($_REQUEST['paged'] < 2) $_REQUEST['paged'] = '';
	} else {
		$_REQUEST['paged'] = '';
	}
}
		//transactions table
	      $transactionsTable = new TransactionsTableFront();

	      $transactionsTable->setUser( intval( $atts['user_id'] ) );

		  $transactionsTable->prepare_items();

		  ob_start();
		  $transactionsTable->display();
		  $tableCode = ob_get_clean();

		  return $tableCode . $tableCSS;

		}

}


// WP_List_Table is not loaded automatically so we need to load it in our application
if ( ! class_exists( 'WP_List_Table' ) )
{
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


class TransactionsTableFront extends \WP_List_Table
{
	public $user_id = 0;

	 public function setUser(int $user_id): void
    {
        $this->user_id = $user_id;
    }

	 public function getUser(): int
    {
        return intval($this->user_id);
    }

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

		$perPage = $this->get_items_per_page('records_per_page', 10);
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
            'amount'        => 'Amount',
            'date'      => 'Date',
            'details' => 'Details',
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

                return $item[ $column_name ];

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
        return array('transaction_id' => array('transaction_id', false), 'date' => array('date', false), 'amount' => array('amount', false),  'balance' => array('balance', false) );
    }


/**
* Returns the count of records in the database.
* * @return null|string
*/
public function record_count()
{
    global $wpdb;

    $table_transactions = $wpdb->prefix . 'vw_micropay_transactions';
    $sql = "SELECT COUNT(*) FROM $table_transactions";

    $sql.= ' WHERE user_id = "' . $this->getUser() . '"';

    return $wpdb->get_var($sql);
}


	/**
     * Get the table data
     *
     * @return Array
     */
    public function get_records($per_page = 20, $page_number = 1)
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
	$sql .= ' WHERE user_id = "' . $this->getUser() . '"';

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