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

class HypnoticPackage{

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
        global $woocommerce;

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
    * Reset the entity to have its dimension in such order:
    * Height <= Width <= Length
    */
    public function reset_position ( &$entity ) {
        $dimensions = array($entity['height'], $entity['width'], $entity['length']);
        sort($dimensions);
        list($entity['height'], $entity['width'], $entity['length']) = $dimensions;
    }

    /**
    * Check if an item is unpackable for all containers
    */
    public function unpackable ( $item ) {
        foreach ( $this->containers as $container ) {
            if ( !$this->can_fit ($item, $container) )
                return false;
        }
        return true;
    }

    /**
    * function to get box's current unit count
    */
    public function get_box_unit_count ( $box ) {
        if ( !isset($box['items']) ) return 0;
        return count( $box['items'] );
    }

    /**
    * function to get box's current weight
    */
    public function get_box_weight ( $box ) {
        if ( !isset($box['items']) ) return 0;

        $weight = 0;
        foreach( $box['items'] as $packed_item ) {
            $weight += $packed_item->get_weight();
        }

        return $weight;
    }

    /**
    * function to check if the box can pack anything
    */
    public function can_put ( $item, $box ) {

        if ($box['box_max_weight'] < $this->get_box_weight($box) + $item->get_weight()
            || $box['box_max_unit'] < $this->get_box_unit_count($box) + 1)
            return false;

        return $this->can_fit ( $item, $box );

    }

    /**
    * Check if an item can fit into the box
    */
    public function can_fit ( $item, $box ) {
        return $item['width'] <= $box['width'] && $item['length'] <= $box['length'] && $item['height'] <= $box['height'];
    }

    /**
    * Get the volume of an item or a box
    */
    public function get_volume ( $entity ) {
        return $entity['height'] * $entity['length'] * $entity['width'];
    }

    public static function compare_volume ( $alpha, $omega ) {
        return ( $this->get_volume( $alpha ) > $this->get_volume( $omega ) ) ? 1 : -1;
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