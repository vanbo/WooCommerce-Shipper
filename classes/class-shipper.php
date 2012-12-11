<?php
/**
 * HypnoticShipper
 *
 * The HypnoticShipper class is skeleton class to be inherented by actual shipping extension.
 * It handle some basics such as settings, availabilities etc
 *
 * @class       HypnoticShipper
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
 * @distributor www.hypnoticzoo.com
*/

class HypnoticShipper extends WC_Shipping_Method {

    /**
    * @var string
    */
    var $id = '';

    /**
     * @var string
     */
    var $carrier = '';

    /**
     * @var string
     */
    var $dimension_unit = 'in';

    /**
     * @var string
     */
    var $weight_unit = 'lbs';

    /**
     * @var string
     */
    var $description = '';

    /**
     * @var string
     */
    var $endpoint = '';

    /**
     * @var string
     */
    var $dev_endpoint = '';

    /**
    * @var array
    */
    var $carrier_boxes = array();

    /**
    * @var array
    */
    var $allowed_origin_countries = array();

    /**
    * @var array
    */
    var $allowed_currencies = array();

    /**
    * @var array
    */
    var $package_shipping_methods = array();

    /**
    * @var array
    */
    var $letter_shipping_methods = array();

    /**
    * @var array
    */
    var $settings_order = array();

    var $log = '';

    public function __construct(){
        global $woocommerce;

        // Load the form fields.
        $this->load_containers();
        $this->load_method_names();
        $this->init_form_fields();

        $this->add_form_fields();
        $this->sort_form_fiels();

        // Load the settings.
        $this->init_settings();

        foreach ( $this->settings as $key => $value ){
            if(array_key_exists($key, $this->form_fields)) $this->$key = $value;
        }

        // convert array containers to container objects
        $containers = array();
        if (is_array($this->selected_boxes)) {
            foreach ( $this->selected_boxes as $container ) {
                $containers[] = new HypnoticContainer($this->usable_boxes[$container]);
            }
            $this->selected_boxes = $containers;
        }

        $this->shipping_methods = array_merge($this->package_shipping_methods, $this->letter_shipping_methods);

        foreach ( $this->shipping_methods as $key => $method ) {
            if ( array_key_exists($key, $this->renamed_methods) ) {
                $this->package_shipping_methods[$key] = $this->renamed_methods[$key];
            }
        }

        if ( isset($this->package_methods) && (!is_array($this->package_methods) || empty($this->package_methods)) )
            $this->package_methods = array_keys( $this->package_shipping_methods );

        if ( isset($this->letter_methods) && (!is_array($this->letter_methods) || empty($this->letter_methods)) )
            $this->letter_methods = array_keys( $this->letter_shipping_methods );

        $this->origin_country = $woocommerce->countries->get_base_country();
        $this->currency = get_woocommerce_currency();

        $this->custombox_form_fields();
        $this->rename_method_form_fields();

        add_action( 'admin_notices', array(&$this, 'notification') );
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array(&$this, 'process_admin_options'), 1);

    }

    /**
    * Notification upon condition checks
    */
    public function notification($issues=array()) {

            $setting_url = 'admin.php?page=woocommerce_settings&tab=shipping&section=' . $this->id;
            $woocommerce_url = 'admin.php?page=woocommerce_settings&tab=general';

            if (!$this->origin && $this->enabled == 'yes'){
                $issues[] = 'no origin postcode entered';
            }

            if (!in_array($this->origin_country, $this->allowed_origin_countries)){
                $issues[] = 'base country is not correct';
            }

            if (!in_array($this->currency, $this->allowed_currencies)){
                $issues[] = 'currency is not accepted';
            }

            if (!empty($issues)){
                echo '<div class="error"><p>' . sprintf(__($this->carrier . ' is enabled, but %s. 
                Please update ' . $this->carrier .' settings <a href="%s">here</a> and WooCommerce settings <a href="%s">here</a>.', 'hypnoticzoo'),
            implode(", ", $issues), admin_url($setting_url), admin_url($woocommerce_url)) . '</p></div>';
            }
    }

    /**
    * Fields for rename shipping method
    */
    public function rename_method_form_fields() {
        $this->rename_method_form_fields = array(
            'shipping_method' => array(
                'title' => __('Pick a method to rename', 'hypnoticzoo'),
                'type' => 'select',
                'class' => 'chosen_select',
                'description' => __('Rename one of the ' . $this->carrier . ' shipping methods to suit your shop.', 'hypnoticzoo'),
                'css' => 'width: 25em;',
                'options' => array_merge(array('0' => ''), $this->shipping_methods)
            ),
            'new_name' => array(
                'title' => __('New Name', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('Leave empty will change it back to its original name.', 'hypnoticshipper'),
                'rules' => 'string none'
            ),
        );
    }

    /**
    * Fields for custom box
    */
    public function custombox_form_fields() {

        $available_boxes = array('0' => 'Add a new box');
        foreach( $this->available_boxes as $key => $box ){
            if ( $box['box_label'] ) {
                $available_boxes[$key] = $box['box_label'];
            } else {
                $available_boxes[$key] = $box['box_width'] . ' x ' . $box['box_length'] . ' x ' . $box['box_height'] . ' in ' . strtoupper($this->dimension_unit);
            }
        }
        $this->saved_boxes_field = array(
            'saved_boxes' => array(
                'title' => __('Add/Edit Box', 'hypnoticzoo'),
                'description' => __('These boxes will be used when packing your items.', 'hypnoticzoo'),
                'type' => 'select',
                'class' => 'chosen_select',
                'css' => 'width: 25em;',
                'options' => $available_boxes
            ),
        );

        $this->box_form_fields = array(
            'box_label' => array(
                'title' => __('Label', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('Label your box for easier management.', 'hypnoticshipper'),
                'rules' => 'string none'
            ),
            'box_width' => array(
                'title' => __('Width', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_length' => array(
                'title' => __('Length', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_height' => array(
                'title' => __('Height', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_girth' => array(
                'title' => __('Height', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_max_weight'  => array(
                'title' => __('Maximum weight', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('The maximum weight the box can hold, in ' . strtoupper($this->weight_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_max_unit'  => array(
                'title' => __('Max units can hold', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('The maximum number of items can be put into the box.', 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small',
                'rules' => 'int none'
            ),
            'box_remove' => array(
                'type' => 'checkbox'
            )
        );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields() {
        global $woocommerce;

        $usable_boxes = array();

        if ( is_array($this->usable_boxes) ){
            foreach ( $this->usable_boxes as $key => $box) {
                $usable_boxes[$key] = $box['box_label'];
            }
        }

        // Rename shipping methods
        foreach ( $this->package_shipping_methods as $key => $method ) {
            if ( array_key_exists($key, $this->renamed_methods) ) {
                $this->package_shipping_methods[$key] = $this->renamed_methods[$key];
            }
        }

        foreach ( $this->letter_shipping_methods as $key => $method ) {
            if ( array_key_exists($key, $this->renamed_methods) ) {
                $this->letter_shipping_methods[$key] = $this->renamed_methods[$key];
            }
        }

        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable/Disable', 'hypnoticshipper'),
                'type' => 'checkbox',
                'label' => __('Enable ' . $this->carrier, 'hypnoticshipper'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Method Title', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'hypnoticshipper'),
                'default' => __($this->carrier, 'hypnoticshipper')
            ),
            'origin' => array(
                'title' => __('Origin Postcode', 'hypnoticzoo'),
                'type' => 'text',
                'description' => __('Enter your origin post code.', 'hypnoticzoo'),
                'default' => __('', 'hypnoticzoo')
            ),
            'fee' => array(
                'title' => __('Handling Fee', 'hypnoticzoo'),
                'type' => 'text',
                'description' => __('Fee excluding tax. Enter an amount, e.g. 2.50, or a percentage, e.g. 5%.', 'hypnoticzoo'),
                'default' => '0'
            ),
            'fee_to_cart' => array(
                'title' => __('', 'hypnoticzoo'),
                'label' => __('Apply handling fee to the value of cart.', 'hypnoticzoo'),
                'type' => 'checkbox',
                'description' => __('Instead of applying handling fee to shipping rate, apply it to the value of cart.', 'hypnoticzoo'),
                'default' => 'no'
            ),
            'ship_type' => array(
                'title' => __('Your products will be shipped', 'hypnoticzoo'),
                'type' => 'select',
                'default' => 'all',
                'css' => 'width: 15em;',
                'class' => 'chosen_select',
                'options' => array(
                    'together' => __('Together', 'hypnoticzoo'),
                    'separate' => __('Separately', 'hypnoticzoo')
                )
            ),
            'selected_boxes' => array(
                'title' => __('Boxes for packing', 'hypnoticzoo'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 25em;',
                'description' => 'Select boxes you want use when packing your products. <br />For information about some predefined boxes, See <a target="blank" href="http://auspost.com.au/personal/packaging-materials.html">here</a>',
                'default' => array(),
                'options' => $usable_boxes
            ),
            'package_methods' => array(
                'title' => __('Shipping Methods For Packages', 'hypnoticzoo'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 25em;',
                'description' => 'Leave empty to enable all shipping methods',
                'default' => '',
                'options' => $this->package_shipping_methods
            ),
            'letter_methods' => array(
                'title' => __('Shipping Methods For Letters', 'hypnoticzoo'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 25em;',
                'description' => 'Leave empty to enable all shipping methods',
                'default' => '',
                'options' => $this->letter_shipping_methods
            ),
            'availability' => array(
                'title' => __('Method availability', 'hypnoticzoo'),
                'type' => 'select',
                'default' => 'all',
                'class' => 'availability',
                'options' => array(
                    'all' => __('All allowed countries', 'hypnoticzoo'),
                    'specific' => __('Specific Countries', 'hypnoticzoo')
                )
            ),
            'countries' => array(
                'title' => __('Specific Target Countries', 'hypnoticzoo'),
                'type' => 'multiselect',
                'class' => 'chosen_select',
                'css' => 'width: 25em;',
                'default' => '',
                'options' => $woocommerce->countries->countries
            ),
            'debug' => array(
                'title' => __('Debug mode', 'hypnoticzoo'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Mode', 'hypnoticzoo'),
                'description' => __('This will output some debug information on your cart page, remember to turn this off when you done testing.', 'hypnoticzoo'),
                'default' => 'no'
            ),
            'available_boxes' => array(
                'type' => 'hidden',
                'default' => array()
            ),
            'renamed_methods' => array(
                'type' => 'hidden',
                'default' => array()
            ),

        );

        if ( empty($this->letter_shipping_methods) ) {
            unset($this->form_fields['letter_methods']);
        }

    }

    /**
    * Add additional form fields
    */
    public function add_form_fields(){}

    /**
    * Sort admin fields for displaying in order
    */
    public function sort_form_fiels(){

        $fields = array();

        // Merge fields order
        if (empty($this->settings_order)){
            $this->settings_order = array_keys($this->form_fields);
        } else {
            $this->settings_order = array_merge($this->settings_order, array_keys($this->form_fields));
        }

        // Sorting
        foreach( $this->settings_order as $order ){

            if(isset($this->form_fields[$order])){
                $fields[$order] = $this->form_fields[$order];
            }

        }

        $this->form_fields = $fields;

    }

    /**
     * Shipping method available conditions
     */
    public function is_available( $package ) {
        if ( $this->ship_type == 'together' && (!is_array($this->selected_boxes) || empty($this->selected_boxes)) )
            return false;

        if ( !in_array($this->currency, $this->allowed_currencies) )
            return false;

        if ( !in_array($this->origin_country, $this->allowed_origin_countries) )
            return false;

        if ( empty($this->letter_shipping_methods) && empty($this->package_shipping_methods) )
            return false;

        return parent::is_available( $package );

    }

    /**
    * Check if this shippment is international shipping
    */
    public function is_intel_shipping() {
        global $woocommerce;

        $customer = $woocommerce->customer;
        $country = $customer->get_shipping_country();
        return !in_array( $country, $this->allowed_origin_countries );
    }

    /**
    * Encode request
    */
    public function encode( $request ) {
        global $hipperxmlparser;

        try {
            return $hipperxmlparser->toXML($request);
        } catch (Exception $e) {
            $this->add_log( $e->getMessage(), false );
        }
    }

    /**
    * Decode the response to an array
    */
    public function decode( $response ) {
        global $hipperxmlparser;

        try {
            return $hipperxmlparser->toArray($response);
        } catch (Exception $e) {
            $this->add_log( $e->getMessage(), false );
        }
    }

    /**
    * Prepare packages, split or not
    */
    public function prepare_packages(){

    }

    /**
    * Get a box from it's id
    */
    public function get_box( $id ) {
        return $this->available_boxes[$id];
    }

    /**
    * Validate a box
    */
    public function valid_box( $box ) {
        return $box['box_width'] && $box['box_length'] && $box['box_height'];
    }

    /**
    * Load containers
    */
    public function load_containers() {
        $available_boxes = array();

        $form_field_settings = ( array ) get_option( $this->plugin_id . $this->id . '_settings' );
        if ( isset($form_field_settings['available_boxes']) && !empty($form_field_settings['available_boxes']) ) {

            $available_boxes = $form_field_settings['available_boxes'];

            foreach ( $available_boxes as $key => $box ) {
                if ( $box['box_label'] == '' ) $available_boxes[$key]['box_label'] = $box['box_width'] . ' x ' . $box['box_length'] . ' x ' . $box['box_height'] . ' in ' . strtoupper($this->dimension_unit);
            }

        }

        // We use + because we want keep the index.
        $this->usable_boxes = $this->carrier_boxes + $available_boxes;
    }

    public function load_method_names() {
        $method_names = array();

        $form_field_settings = ( array ) get_option( $this->plugin_id . $this->id . '_settings' );
        if ( isset($form_field_settings['renamed_methods']) && !empty($form_field_settings['renamed_methods']) ) {
            $method_names = $form_field_settings['renamed_methods'];
        }
        $this->renamed_methods = $method_names;
    }

    /**
     * Validate Settings Field Data.
     *
     * Validate the data on the "Settings" form.
     *
     * @since 1.0.0
     * @uses method_exists()
     * @param bool $form_fields (default: false)
     * @return void
     */
    public function validate_settings_fields( $form_fields = false, &$other_sanitized_fields = NULL ) {

        if ( ! $form_fields )
            $form_fields = $this->form_fields;

        $sanitized_fields = array();

        foreach ( $form_fields as $k => $v ) {
            if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] = 'text'; } // Default to "text" field type.

            if ( ! isset( $v['rules'] ) || ( $v['rules'] == '' )  ) { $v['rules'] = 'none'; }

            $rules = explode(' ', $v['rules']);

            if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
                $field = $this->{'validate_' . $v['type'] . '_field'}( $k );

                if ( $field == '' &&  !in_array('none', $rules))
                    $this->errors['field_error_' . $k] = 'Value of ' . $v['title'] . ' can not be empty.';

                if ( in_array('int', $rules) && $field && !is_numeric($field))
                    $this->errors['field_error_' . $k] = 'Value of ' . $v['title'] . ' must be an integer number.';

                if ( in_array('float', $rules) && $field && !is_numeric($field))
                    $this->errors['field_error_' . $k] = 'Value of ' . $v['title'] . ' must be a float number.';

                if ( in_array('numeric', $rules) && $field && !is_numeric($field))
                    $this->errors['field_error_' . $k] = 'Value of ' . $v['title'] . ' must be a number.';

                if ( in_array('string', $rules) && $field && !is_string($field))
                    $this->errors['field_error_' . $k] = 'Value of ' . $v['title'] . ' must be a string.';

                $sanitized_fields[$k] = $field;
            } else {
                $sanitized_fields[$k] = $this->settings[$k];
            }
        }

        if ( is_array($other_sanitized_fields) ) {
            $other_sanitized_fields = $sanitized_fields;
        } else {
            $this->sanitized_fields = $sanitized_fields;
        }
    }

    /**
     * Admin Panel Options Processing
     * - Saves the options to the DB
     *
     * @since 1.0.0
     * @access public
     * @return bool
     */
    public function process_admin_options() {
        // Validate custom boxes and add to sanitized_fields
        $custom_box_fields = array();
        $selected_box = array();
        $renamed_method = array();

        $this->validate_settings_fields($this->box_form_fields, $custom_box_fields);
        $this->validate_settings_fields($this->saved_boxes_field, $selected_box);
        $this->validate_settings_fields($this->rename_method_form_fields, $renamed_method);

        // Validate normal setting fields
        $this->validate_settings_fields();

        if ( count( $this->errors ) > 0 ) {
            // $this->display_errors();
            foreach ($this->errors as $error)
                $this->add_log( $error, false );
            return false;
        } else {

            // Manipulate fields before save
            // Remove box settings to have it not saved as regular settings
            $remove_box_fields = array_keys($this->box_form_fields);
            $remove_naming_fields = array_keys($this->rename_method_form_fields);
            $remove_fields = array_merge($remove_box_fields, $remove_naming_fields);

            foreach ( $this->sanitized_fields as $field => $value ) {
                if ( in_array($field, $remove_fields) ) {
                    unset( $this->sanitized_fields[$field] );
                }
            }

            // Save renamed method
            $this->sanitized_fields['renamed_methods'] = $this->renamed_methods;
            if ($renamed_method['shipping_method'] != '') {
                $method = $renamed_method['shipping_method'];
                $new_name = $renamed_method['new_name'];
                if ( $new_name != '' )
                    $this->sanitized_fields['renamed_methods'][$method] = $new_name;
                else
                    unset($this->sanitized_fields['renamed_methods'][$method]);
            }

            // Save boxes settings
            $this->sanitized_fields['available_boxes'] = $this->available_boxes;
            $target_box = $selected_box['saved_boxes'];

            if ( $custom_box_fields['box_remove'] == 'yes' && $target_box != 0 ) {

                // Remove the box
                unset( $this->sanitized_fields['available_boxes'][$target_box] );

            } elseif ( $custom_box_fields['box_remove'] == 'no' && $this->valid_box($custom_box_fields) ) {

                // Add or update the box
                if ( $target_box == 0 )
                    $target_box = count($this->available_boxes) + 1;
                $this->sanitized_fields['available_boxes'][$target_box] = $custom_box_fields;

            } else {
                // Do nothing
            }

            update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
            return true;
        }
    }

    /**
     * Admin Panel Options
     */
    public function admin_options() {
        ?>
        <style>
            .left {float: left;}
            .right { float: left;}
            table.form-table { clear: none; }
            table.wide { width: 650px; border-right: thin solid #DFDFDF; margin-right: 15px !important; }
            table.narrow { width: 500px; }
        </style>

        <h3><?php _e($this->carrier, 'hypnoticzoo'); ?></h3>
        <p><?php _e($this->description, 'hypnoticzoo'); ?></p>

        <div class="left">
            <table class="form-table wide">

                <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                ?>

            </table><!--/.form-table-->
        </div>

        <div class="right">
            <h4><?php _e('Container Settings', 'hypnoticzoo'); ?></h4>
            <table class="form-table narrow">

                <?php
                    // Generate the HTML For the available box dropdown.
                    $this->generate_settings_html( $this->saved_boxes_field );
                ?>

                <tbody>
                    <tr valign="top">
                        <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_label">Label</label></th>
                        <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Label</span></legend>
                            <input type="text" placeholder="" id="woocommerce_<?php echo $this->id; ?>_box_label" name="woocommerce_<?php echo $this->id; ?>_box_label" class="input-text regular-input "> <p class="description">Label your box for easier management.</p>
                        </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_width">Dimensions</label></th>
                        <td class="forminp">
                            <fieldset>
                                <input type="text" placeholder="Width" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_width" name="woocommerce_<?php echo $this->id; ?>_box_width" class="input-text regular-input small">
                                <input type="text" placeholder="Length" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_length" name="woocommerce_<?php echo $this->id; ?>_box_length" class="input-text regular-input small">
                                <input type="text" placeholder="Height" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_height" name="woocommerce_<?php echo $this->id; ?>_box_height" class="input-text regular-input small">
                                <input type="text" placeholder="Girth" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_girth" name="woocommerce_<?php echo $this->id; ?>_box_girth" class="input-text regular-input small">
                                <span class="description">in <?php echo strtoupper($this->dimension_unit); ?>.</span>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_max_weight">Maximum weight</label></th>
                        <td class="forminp">
                            <fieldset>
                                <legend class="screen-reader-text"><span>Maximum weight</span></legend>
                                <input type="text" placeholder="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_max_weight" name="woocommerce_<?php echo $this->id; ?>_box_max_weight" class="input-text regular-input small"> <p class="description">The maximum weight the box can hold, in <?php echo strtoupper($this->weight_unit); ?></p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_max_unit">Maximum units</label></th>
                        <td class="forminp">
                            <fieldset>
                                <legend class="screen-reader-text"><span>Maximum units</span></legend>
                                <input type="text" placeholder="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_max_unit" name="woocommerce_<?php echo $this->id; ?>_box_max_unit" class="input-text regular-input small"> <p class="description">The maxiumum number of items can be put into the box.</p>
                            </fieldset>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th class="titledesc" scope="row"></th>
                        <td class="forminp">
                            <fieldset>
                                <input type="checkbox" class="" value="1" id="woocommerce_<?php echo $this->id; ?>_box_remove" name="woocommerce_<?php echo $this->id; ?>_box_remove">

                                <span class="description">Remove this box</span>

                                <input type="submit" value="Save" class="button-secondary" name="save">
                            </fieldset>
                        </td>
                    </tr>

                </tbody>

            </table><!--/.form-table-->

            <h4><?php _e('Rename shipping methods', 'hypnoticzoo'); ?></h4>
            <table class="form-table narrow">
                <?php
                    // Generate the HTML For the available shipping methods dropdown.
                    $this->generate_settings_html( $this->rename_method_form_fields );
                ?>
                <tbody>
                    <tr valign="top">
                        <th class="titledesc" scope="row"></th>
                        <td class="forminp">
                            <fieldset>
                                <input type="submit" value="Save" class="button-secondary" name="save">
                            </fieldset>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="clear"></div>


        <?php $js_data = apply_filters('hypnoticzoo_shipping_assets', $this->id); ?>
        <script type="text/javascript">
            jQuery(window).load(function(){
                var $method_id = '<?php echo $this->id; ?>';
                <?php 
                    foreach ( $js_data as $param => $data )
                        echo 'var $' . $param . ' = ' . $data . ';';
                ?>

                jQuery('select#woocommerce_' + $method_id + '_saved_boxes').change(function(){
                    box = $available_boxes[jQuery(this).val()];
                    for (var key in box) {
                        if (box.hasOwnProperty(key)) {

                            jQuery('#woocommerce_' + $method_id + '_' + key).val(box[key]);

                        }
                    }
                });

                jQuery('select#woocommerce_' + $method_id + '_shipping_method').change(function(){
                    method = $renamed_methods[jQuery(this).val()];
                    jQuery('#woocommerce_' + $method_id + '_new_name').val(method);
                });

            });
        </script>
        <?php
    }

    /************** Help functions *****************/

    /**
     * Use WooCommerce logger if debug is enabled.
     */
    public function add_log( $message='test', $debug_on = true ) {
        global $woocommerce;

        if ( $this->debug == 'yes' || !$debug_on ) {
            if ( !$this->log ) $this->log = $woocommerce->logger();

            $this->log->add( $file=$this->id, $message );
        }
    }

    /**
    * Show response returns when debug is on
    */
    public function show_response( $response ){
        global $woocommerce;
        if ( $this->debug == 'yes' ) {
                $woocommerce->clear_messages();
                $woocommerce->add_message('<p>'. $this->carrier .' Response:</p><ul><li>' . implode('</li><li>', $response) . '</li></ul>');
        }
    }

    /**
     * Generate Select HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_select_html ( $key, $data ) {
        $html = '';

        if ( isset( $data['title'] ) && $data['title'] != '' ) $title = $data['title']; else $title = '';
        $data['options'] = (isset( $data['options'] )) ? (array) $data['options'] : array();
        $data['class'] = (isset( $data['class'] )) ? $data['class'] : '';
        $data['css'] = (isset( $data['css'] )) ? $data['css'] : '';

        $html .= '<tr valign="top">' . "\n";
            $html .= '<th scope="row" class="titledesc">';
            $html .= '<label for="' . $this->plugin_id . $this->id . '_' . $key . '">' . $title . '</label>';
            $html .= '</th>' . "\n";
            $html .= '<td class="forminp">' . "\n";
                $html .= '<fieldset><legend class="screen-reader-text"><span>' . $title . '</span></legend>' . "\n";
                $html .= '<select name="' . $this->plugin_id . $this->id . '_' . $key . '" id="' . $this->plugin_id . $this->id . '_' . $key . '" style="'.$data['css'].'" class="select '.$data['class'].'">';

                foreach ($data['options'] as $option_key => $option_value) :
                    $html .= '<option value="'.$option_key.'" '.selected($option_key, esc_attr( isset($this->settings[$key]) ? $this->settings[$key] : '' ), false).'>'.$option_value.'</option>';
                endforeach;

                $html .= '</select>';
                if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= ' <p class="description">' . $data['description'] . '</p>' . "\n"; }
            $html .= '</fieldset>';
            $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";

        return $html;
    }

}
?>