<?php
/*
 * HypnoticPackage
 *
 * HypnoticPackage contains the shopping cart items. It manage and packing the items
 * in certain way. 
 *
 * @class       HypnoticPackage
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
*/

class HypnoticPackage {

    /**
    * @var array
    */
    var $items = array();

    /**
    * @var array
    */
    var $containers = array();

    /**
    * @var array
    */
    var $packed_containers = array();

    /**
    * @var array
    */
    var $packed_items = array();

    /**
    * @var array
    */
    var $unpackable_items = array();

    /**
    * @var array
    */
    var $weight_limit = 0;

    /**
    * @var string
    */
    var $weight_unit = 'lbs';

    /**
    * @var string
    */
    var $dimension_unit = 'in';

    /**
    * The constructor takes cart and containers as parameters
    */
    public function __construct( $cart, $containers = array() ){

        // Get containers, with their reset dimensions
        foreach ( $containers as $container ) {
            $this->reset_position( $container );
            $this->containers[] = $container;
        }

        // Put shippable products into package regardless their order or details.
        foreach ( $cart as $product_id => $product ) {
            $item = $product['data'];
            $quantity = $product['quantity'];

            if ( $item->exists() && $quantity > 0 && $item->needs_shipping() ) {

                $this->reset_position( $item );

                for ($i = 0; $i < $quantity; $i++) {
                    $this->items[] = $item;
                }

            }

        }

        $this->items = $this->volume_sort( $this->items, 'desc' );
        $this->containers = $this->volume_sort( $this->containers );

    }

    /**
    * Packing follows following rules;
    * 1. Starts from largest item with smallest box
    * 2. Return packed boxes with total weight, and unpacked items
    */
    public function packing () {

        while( count( $this->items ) > count( $this->packed_items ) + count( $this->unpackable_items) ){

            foreach ( $this->items as $item ) {

                if ( $this->unpackable( $item ) ) {
                    $this->unpackable_items[] = $item;
                    continue;
                }

                // if it's the first box, pack the item straightway
                if( count($this->packed_containers) == 0 ) {

                    foreach ( $this->containers as $container ) {

                        if ( $this->can_put($item, $container) ) {
                            $container['items'][] = $item;
                            $this->packed_containers[] = $container;
                            break;
                        }
                    }

                } else {

                    $packed = false;

                    // try pack the item into a packed box first
                    foreach ( $this->packed_containers as $container ) {

                        if ( $this->can_put($item, $container) ) {
                            $container['items'][] = $item;
                            $packed = true;
                            break;
                        }

                    }

                    // if none packed box can hold this item, add a new box
                    if ( !$packed ) {

                        foreach ( $this->containers as $container ) {

                            if ( $this->can_put($item, $container) ) {
                                $container['items'][] = $item;
                                $this->packed_containers[] = $container;
                                break;
                            }

                        }

                    }

                }

                // add this item to packed item list
                $this->packed_items[] = $item;

            }

        }

    }


    /**
    * Set package weight unit and convert all items' weight to be usable
    */
    public function set_weight_unit( $unit ) {

        $this->weight_unit = $unit;

        foreach ( $this->items as $item ) {

            $this->unify_weight( $item['data'] );

        }
    }

    /**
    * Set package dimension unit and convert all items' dimensions to be usable
    */
    public function set_dimension_unit( $unit ) {

        $this->dimension_unit = $unit;

        foreach ( $this->items as $item ) {

            $this->unify_dimensions( $item['data'] );

        }
    }

    /**
    * convert weight to something usable by the package
    */
    public function unify_weight ( &$item ) {
        $item->weight = woocommerce_get_weight( $item->get_weight(), $this->weight_unit );
    }

    /**
    * convert dimensions to something usable by the package
    */
    public function unify_dimensions ( &$item ) {

        $dimensions = array( 'width', 'height', 'length' );
        foreach( $dimensions as $dimension ) {
            $item->$dimension = woocommerce_get_dimension( $item->$dimension, $this->dimension_unit );
        }

    }

    /**
    * Reset the entity to have its dimension in such order:
    * Height <= Width <= Length
    */
    public function reset_position ( &$entity ) {
        $dimensions = array($entity->height, $entity->width, $entity->length);
        sort($dimensions);
        list($entity->height, $entity->width, $entity->length) = $dimensions;
    }

    /**
    * Check if an item is unpackable for all containers
    */
    public function unpackable ( $item ) {
        foreach ( $this->containers as $container ) {
            if ( $this->can_fit ($item, $container) )
                return false;
        }
        return true;
    }

    /**
    * function to check if the box can pack anything
    */
    public function can_put ( $item, $box ) {

        if ( $this->weight_limit != 0 && $this->weight_limit < $box->get_weight() + $item->get_weight() )
            return false;

        if ( $box->max_weight < $box->get_weight() + $item->get_weight()
            || $box->max_unit < $box->get_unit_count() + 1 )
            return false;

        return $this->can_fit ( $item, $box );

    }

    /**
    * Check if an item can fit into the box
    */
    public function can_fit ( $item, $box ) {
        return $item->width <= $box->width && $item->length <= $box->length && $item->height <= $box->height;
    }

    /**
    * Get the volume of an item or a box
    */
    public static function get_volume ( $entity ) {
        return $entity->height * $entity->length * $entity->width;
    }

    public static function compare_volume ( $alpha, $omega ) {
        return ( self::get_volume( $alpha ) > self::get_volume( $omega ) ) ? 1 : -1;
    }

    /**
    * Sort items or boxes on their volume
    */
    public function volume_sort ( $entities, $order = 'asc' ) {
        usort ( $entities, array('HypnoticPackage', 'compare_volume') );

        if ( $order == 'desc' ) {
            return array_reverse( $entities );
        }

        return $entities;
    }
}

?>