<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists('HipperShipper') ) { // Exit if framework alreay in use.

class HipperShipper {

    /**
     * @var string
     */
    var $version = '1.0';


    /**
     * HipperShipper Constructor.
     *
     * @access public
     * @return void
     */
    function __construct() {

        // Define version constant
        define( 'HIPPERSHIPPER_VERSION', $this->version );

        // Include required files
        $this->includes();
        $this->additional_product_meta();

    }


    /**
     * Include required core files.
     *
     * @access public
     * @return void
     */
    function includes() {
        include( 'classes/class-shipper.php' );     //contains shipping class skeleton
        include( 'classes/class-container.php' );   //contains container class
        include( 'classes/class-package.php' );     //contains package class
        include( 'classes/class-xmlparser.php' );   //contains xmlparser class
        include( 'product-meta.php' );              //contains extra product meta processors
    }

    function additional_product_meta(){

        add_action( 'woocommerce_product_options_dimensions', 'woocommerce_product_girth', 10 );
        add_action( 'woocommerce_product_options_dimensions', 'woocommerce_product_lettermail', 10 );

        add_action( 'woocommerce_process_product_meta', 'woocommerce_process_product_girth_metabox', 1 );
        add_action( 'woocommerce_process_product_meta', 'woocommerce_process_product_lettermail_metabox', 1 );

    }
}

$GLOBALS['hippershipper'] = new HipperShipper();

}
?>