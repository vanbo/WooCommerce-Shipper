<?php
/**
 * Product Class with girth and lettermail enabled
 *
 * @class       HypnoticProduct
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
 */
class HypnoticProduct extends WC_Product {

    /**
     * Loads all product data from custom fields.
     *
     * @param int $id ID of the product to load
     */
    function __construct( $id ) {

        $this->id = (int) $id;

        $this->product_custom_fields = get_post_custom( $this->id );

        // Define the data we're going to load: Key => Default value
        $load_data = array(
            'sku'           => '',
            'downloadable'  => 'no',
            'virtual'       => 'no',
            'price'         => '',
            'visibility'    => 'hidden',
            'stock'         => 0,
            'stock_status'  => 'instock',
            'backorders'    => 'no',
            'manage_stock'  => 'no',
            'sale_price'    => '',
            'regular_price' => '',
            'weight'        => '',
            'length'        => '',
            'width'     => '',
            'height'        => '',
            'tax_status'    => 'taxable',
            'tax_class'     => '',
            'upsell_ids'    => array(),
            'crosssell_ids' => array(),
            'sale_price_dates_from' => '',
            'sale_price_dates_to'   => '',
            'min_variation_price'   => '',
            'max_variation_price'   => '',
            'min_variation_regular_price'   => '',
            'max_variation_regular_price'   => '',
            'min_variation_sale_price'  => '',
            'max_variation_sale_price'  => '',
            'featured'      => 'no',
            'girth'         => '',
            'letter_mail'   => 'no'
        );

        // Load the data from the custom fields
        foreach ($load_data as $key => $default) $this->$key = (isset($this->product_custom_fields['_' . $key][0]) && $this->product_custom_fields['_' . $key][0]!=='') ? $this->product_custom_fields['_' . $key][0] : $default;

        // Get product type
        $transient_name = 'wc_product_type_' . $this->id;

        if ( false === ( $this->product_type = get_transient( $transient_name ) ) ) :
            $terms = wp_get_object_terms( $id, 'product_type', array('fields' => 'names') );
            $this->product_type = (isset($terms[0])) ? sanitize_title($terms[0]) : 'simple';
            set_transient( $transient_name, $this->product_type );
        endif;

        // Check sale dates
        $this->check_sale_price();
    }

}

?>