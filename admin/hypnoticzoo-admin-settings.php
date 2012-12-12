<?php
/**
 * WooCommerce-Shipper Admin Settings
 *
 *
 *
 * @author      Andy Zhang
 * @category    Admin
 * @package     WooCommerce-Shipper/Admin
 * @version     1.0
 */

/**
* Load assets for specific method
*/
function load_hypnoticzoo_shipping_settings( $id ) {
    global $woocommerce;

    $methods = $woocommerce->shipping->load_shipping_methods();

    foreach ( $methods as $method ) {
        if ( $method->id == $id ) {
            $params['available_boxes'] = json_encode($method->available_boxes);
            $params['renamed_methods'] = json_encode($method->renamed_methods);
            break;
        }
    }

    return $params;

}

/**
* Load shipping categories for each framework enabled method.
*/
function load_hypnoticzoo_shipping_categories() {

    global $woocommerce;

    $methods = $woocommerce->shipping->load_shipping_methods();

    foreach ( $methods as $method ) {
        if (  method_exists ( $method , 'install_category' ) ) {
            $method->install_category();
        }
    }

}

add_filter( 'hypnoticzoo_shipping_assets', 'load_hypnoticzoo_shipping_settings' );
add_action( 'woocommerce_register_post_type', 'load_hypnoticzoo_shipping_categories' );

?>