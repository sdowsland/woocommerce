<?php
/**
 * WooCommerce Page Functions
 *
 * Functions related to pages and menus.
 *
 * @author 		WooThemes
 * @category 	Core
 * @package 	WooCommerce/Functions
 * @version     2.1.0
 */

/**
 * Retrieve page ids - used for myaccount, edit_address, shop, cart, checkout, pay, view_order, terms. returns -1 if no page is found
 *
 * @param string $page
 * @return int
 */
function woocommerce_get_page_id( $page ) {

	if ( $page == 'pay' || $page == 'thanks' ) {
		_deprecated_argument( __CLASS__ . '->' . __FUNCTION__, '2.1', 'The "pay" and "thanks" pages are no-longer used - an endpoint is added to the checkout instead. To get a valid link use the WC_Order::get_checkout_payment_url() or WC_Order::get_checkout_order_received_url() methods instead.' );

		$page = 'checkout';
	}
	if ( $page == 'change_password' || $page == 'edit_address' || $page == 'lost_password' ) {
		_deprecated_argument( __CLASS__ . '->' . __FUNCTION__, '2.1', 'The "change_password", "edit_address" and "lost_password" pages are no-longer used - an endpoint is added to the my-account instead. To get a valid link use the woocommerce_customer_edit_account_url() function instead.' );

		$page = 'myaccount';
	}

	$page = apply_filters( 'woocommerce_get_' . $page . '_page_id', get_option('woocommerce_' . $page . '_page_id' ) );

	return $page ? $page : -1;
}

/**
 * Get endpoint URL
 *
 * Gets the URL for an endpoint, which varies depending on permalink settings.
 *
 * @param string $page
 * @return string
 */
function woocommerce_get_endpoint_url( $endpoint, $value = '', $permalink = '' ) {
	if ( ! $permalink )
		$permalink = get_permalink();

	if ( get_option( 'permalink_structure' ) )
		$url = trailingslashit( $permalink ) . $endpoint . '/' . $value;
	else
		$url = add_query_arg( $endpoint, $value, $permalink );

	return apply_filters( 'woocommerce_get_endpoint_url', $url );
}

/**
 * woocommerce_lostpassword_url function.
 *
 * @access public
 * @param mixed $url
 * @return void
 */
function woocommerce_lostpassword_url( $url ) {
    return woocommerce_get_endpoint_url( 'lost-password', '', get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );
}
add_filter( 'lostpassword_url',  'woocommerce_lostpassword_url' );


/**
 * Get the link to the edit account details page
 *
 * @return string
 */
function woocommerce_customer_edit_account_url() {
	$edit_account_url = woocommerce_get_endpoint_url( 'edit-account', '', get_permalink( woocommerce_get_page_id( 'myaccount' ) ) );

	return apply_filters( 'woocommerce_customer_edit_account_url', $edit_account_url );
}

/**
 * woocommerce_nav_menu_items function.
 *
 * @param array $items
 * @param mixed $args
 * @return array
 */
function woocommerce_nav_menu_items( $items, $args ) {
	if ( ! is_user_logged_in() ) {

		$hide_pages   = array();
		$hide_pages[] = (int) woocommerce_get_page_id( 'logout' );
		$hide_pages   = apply_filters( 'woocommerce_logged_out_hidden_page_ids', $hide_pages );

		foreach ( $items as $key => $item ) {
			if ( ! empty( $item->object_id ) && ! empty( $item->object ) && in_array( $item->object_id, $hide_pages ) && $item->object == 'page' ) {
				unset( $items[ $key ] );
			}
		}
	}
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'woocommerce_nav_menu_items', 10, 2 );


/**
 * Fix active class in nav for shop page.
 *
 * @param array $menu_items
 * @param array $args
 * @return array
 */
function woocommerce_nav_menu_item_classes( $menu_items, $args ) {

	if ( ! is_woocommerce() ) return $menu_items;

	$shop_page 		= (int) woocommerce_get_page_id('shop');
	$page_for_posts = (int) get_option( 'page_for_posts' );

	foreach ( (array) $menu_items as $key => $menu_item ) {

		$classes = (array) $menu_item->classes;

		// Unset active class for blog page
		if ( $page_for_posts == $menu_item->object_id ) {
			$menu_items[$key]->current = false;
			unset( $classes[ array_search('current_page_parent', $classes) ] );
			unset( $classes[ array_search('current-menu-item', $classes) ] );

		// Set active state if this is the shop page link
		} elseif ( is_shop() && $shop_page == $menu_item->object_id ) {
			$menu_items[$key]->current = true;
			$classes[] = 'current-menu-item';
			$classes[] = 'current_page_item';

		// Set parent state if this is a product page
		} elseif ( is_singular( 'product' ) && $shop_page == $menu_item->object_id ) {
			$classes[] = 'current_page_parent';
		}

		$menu_items[$key]->classes = array_unique( $classes );

	}

	return $menu_items;
}
add_filter( 'wp_nav_menu_objects',  'woocommerce_nav_menu_item_classes', 2, 20 );


/**
 * Fix active class in wp_list_pages for shop page.
 *
 * https://github.com/woothemes/woocommerce/issues/177
 *
 * @author Jessor, Peter Sterling
 * @param string $pages
 * @return string
 */
function woocommerce_list_pages( $pages ){
    global $post;

    if (is_woocommerce()) {
        $pages = str_replace( 'current_page_parent', '', $pages); // remove current_page_parent class from any item
        $shop_page = 'page-item-' . woocommerce_get_page_id('shop'); // find shop_page_id through woocommerce options

        if (is_shop()) :
        	$pages = str_replace($shop_page, $shop_page . ' current_page_item', $pages); // add current_page_item class to shop page
    	else :
    		$pages = str_replace($shop_page, $shop_page . ' current_page_parent', $pages); // add current_page_parent class to shop page
    	endif;
    }
    return $pages;
}
add_filter( 'wp_list_pages', 'woocommerce_list_pages' );
