<?php
/**
 * Product Variation Class with girth and lettermail enabled
 *
 * @class       HypnoticProductVariation
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
 */
class HypnoticProductVariation extends WC_Product_Variation {

    function __construct( $variation_id, $parent_id = '', $parent_custom_fields = '' ) {

        $this->variation_id = $variation_id;

        $product_custom_fields = get_post_custom( $this->variation_id );

        $this->variation_data = array();

        foreach ( $product_custom_fields as $name => $value ) {

            if ( ! strstr( $name, 'attribute_' ) ) continue;

            $this->variation_data[$name] = $value[0];

        }

        /* Get main product data from parent */
        $this->id = ($parent_id>0) ? $parent_id : wp_get_post_parent_id( $this->variation_id );
        if (!$parent_custom_fields) $parent_custom_fields = get_post_custom( $this->id );

        // Define the data we're going to load from the parent: Key => Default value
        $load_data = array(
            'sku'           => '',
            'price'         => 0,
            'visibility'    => 'hidden',
            'stock'         => 0,
            'stock_status'  => 'instock',
            'backorders'    => 'no',
            'manage_stock'  => 'no',
            'sale_price'    => '',
            'regular_price' => '',
            'weight'        => '',
            'length'        => '',
            'width'         => '',
            'height'        => '',
            'tax_status'    => 'taxable',
            'tax_class'     => '',
            'upsell_ids'    => array(),
            'crosssell_ids' => array(),
            'girth'         => '',
            'letter_mail'   => 'no'
        );

        // Load the data from the custom fields
        foreach ( $load_data as $key => $default )
            $this->$key = ( isset( $parent_custom_fields['_' . $key][0] ) && $parent_custom_fields['_' . $key][0] !== '' ) ? $parent_custom_fields['_' . $key][0] : $default;

        $this->product_type = 'variable';

        $this->variation_has_sku = $this->variation_has_stock = $this->variation_has_weight = $this->variation_has_length = $this->variation_has_width = $this->variation_has_height = $this->variation_has_price = $this->variation_has_regular_price = $this->variation_has_sale_price = false;

        /* Override parent data with variation */
        if ( isset( $product_custom_fields['_sku'][0] ) && ! empty( $product_custom_fields['_sku'][0] ) ) {
            $this->variation_has_sku = true;
            $this->sku = $product_custom_fields['_sku'][0];
        }

        if ( isset( $product_custom_fields['_stock'][0] ) && $product_custom_fields['_stock'][0] !== '' ) {
            $this->variation_has_stock = true;
            $this->manage_stock = 'yes';
            $this->stock = $product_custom_fields['_stock'][0];
        }

        if ( isset( $product_custom_fields['_weight'][0] ) && $product_custom_fields['_weight'][0] !== '' ) {
            $this->variation_has_weight = true;
            $this->weight = $product_custom_fields['_weight'][0];
        }

        if ( isset( $product_custom_fields['_length'][0] ) && $product_custom_fields['_length'][0] !== '' ) {
            $this->variation_has_length = true;
            $this->length = $product_custom_fields['_length'][0];
        }

        if ( isset( $product_custom_fields['_width'][0] ) && $product_custom_fields['_width'][0] !== '' ) {
            $this->variation_has_width = true;
            $this->width = $product_custom_fields['_width'][0];
        }

        if ( isset( $product_custom_fields['_height'][0] ) && $product_custom_fields['_height'][0] !== '' ) {
            $this->variation_has_height = true;
            $this->height = $product_custom_fields['_height'][0];
        }
        
        if ( isset( $product_custom_fields['_downloadable'][0] ) && $product_custom_fields['_downloadable'][0] == 'yes' ) {
            $this->downloadable = 'yes';
        } else {
            $this->downloadable = 'no';
        }

        if ( isset( $product_custom_fields['_virtual'][0] ) && $product_custom_fields['_virtual'][0] == 'yes' ) {
            $this->virtual = 'yes';
        } else {
            $this->virtual = 'no';
        }

        if ( isset( $product_custom_fields['_tax_class'][0] ) ) {
            $this->variation_has_tax_class = true;
            $this->tax_class = $product_custom_fields['_tax_class'][0];
        }
        
        if ( isset( $product_custom_fields['_price'][0] ) && $product_custom_fields['_price'][0] !== '' ) {
            $this->variation_has_price = true;
            $this->price = $product_custom_fields['_price'][0];
        }
        
        if ( isset( $product_custom_fields['_regular_price'][0] ) && $product_custom_fields['_regular_price'][0] !== '' ) {
            $this->variation_has_regular_price = true;
            $this->regular_price = $product_custom_fields['_regular_price'][0];
        }
        
        if ( isset( $product_custom_fields['_sale_price'][0] ) && $product_custom_fields['_sale_price'][0] !== '' ) {
            $this->variation_has_sale_price = true;
            $this->sale_price = $product_custom_fields['_sale_price'][0];
        }
        
        // Backwards compat for prices
        if ( $this->variation_has_price && ! $this->variation_has_regular_price ) {
            update_post_meta( $this->variation_id, '_regular_price', $this->price );
            $this->variation_has_regular_price = true;
            $this->regular_price = $this->price;
            
            if ( $this->variation_has_sale_price && $this->sale_price < $this->regular_price ) {
                update_post_meta( $this->variation_id, '_price', $this->sale_price );
                $this->price = $this->sale_price;
            }
        }
        
        if ( isset( $product_custom_fields['_sale_price_dates_from'][0] ) )
            $this->sale_price_dates_from = $product_custom_fields['_sale_price_dates_from'][0];
            
        if ( isset( $product_custom_fields['_sale_price_dates_to'][0] ) )
            $this->sale_price_dates_from = $product_custom_fields['_sale_price_dates_to'][0];
        
        $this->total_stock = $this->stock;
        
        // Check sale dates
        $this->check_sale_price();
    }
}

?>