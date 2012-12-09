<?php
/*
 * HypnoticContainer
 *
 * HypnoticPackage represent a shipping box that contains items
 *
 * @class       HypnoticPackage
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
*/

class HypnoticContainer {

    /**
    * @var int
    */
    var $width = 0;

    /**
    * @var int
    */
    var $length = 0;

    /**
    * @var int
    */
    var $height = 0;


    /**
    * @var int
    */
    var $weight = 0;

    /**
    * @var int
    */
    var $max_weight = 0;

    /**
    * @var int
    */
    var $max_unit = 0;

    /**
    * @var array
    */
    var $items = array();

    public function __construct ( $container = NULL ) {
        if ( $container != NULL ) {

            foreach ( $container as $key => $value ) {
                $property = str_replace('box_', '', $key);
                $this->$property = (int) $value;
            }

        }

    }

    /**
    * function to put an item into the box
    */
    public function put ( $item ) {
        $this->items[] = $item;
    }

    /**
    * function to get box's current unit count
    */
    public function get_unit_count () {
        return count( $this->items );
    }

    /**
    * function to get box's current weight
    */
    public function get_weight () {

        $weight = 0;
        foreach( $this->items as $packed_item ) {
            $weight += $packed_item->get_weight();
        }

        return $weight;
    }
}

?>