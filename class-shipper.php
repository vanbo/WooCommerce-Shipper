<?php
/*
 * HypnoticShipper
 *
 * The HypnoticShipper class is skeleton class to be inherented by actual shipping extension.
 * It handle some basics such as settings, availabilities etc
 *
 * @class       HypnoticShipper
 * @version     1.0
 * @package     WooCommerce-Shipper/Classes
 * @author      Andy Zhang
*/

class HypnoticShipper extends WC_Shipping_Method{

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

    function __construct(){
        global $woocommerce;

        // Load the form fields.
        $this->init_form_fields();
        $this->add_form_fields();
        $this->sort_form_fiels();

        // Load the settings.
        $this->init_settings();

        foreach($this->settings as $key => $value){
            if(array_key_exists($key, $this->form_fields)) $this->$key = $value;
        }

        $this->shipping_methods = array_merge($this->package_shipping_methods, $this->letter_shipping_methods);
        $this->origin_country = $woocommerce->countries->get_base_country();
        $this->currency = get_woocommerce_currency();

        $this->custombox_form_fields();

        add_action('admin_notices', array(&$this, 'notification'));

    }

    /**
    * Notification upon condition checks
    */
    function notification($issues=array()) {

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
    * Fields for custom box
    */
    function custombox_form_fields() {
        $this->box_form_fields = array(
            'box_id' => array(
                'type' => 'hidden'
            ),
            'box_label' => array(
                'title' => __('Label', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('Label your box for easier management.', 'hypnoticshipper'),
            ),
            'box_width' => array(
                'title' => __('Width', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            ),
            'box_length' => array(
                'title' => __('Length', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            ),
            'box_height' => array(
                'title' => __('Height', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            ),
            'box_girth' => array(
                'title' => __('Height', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->dimension_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            ),
            'box_max_weight'  => array(
                'title' => __('Max weight can hold', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('in ' . strtoupper($this->weight_unit), 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            ),
            'box_max_unit'  => array(
                'title' => __('Max units can hold', 'hypnoticshipper'),
                'type' => 'text',
                'description' => __('The maxiumum number of items can be put into the box.', 'hypnoticshipper'),
                'css' => 'width: 5em;',
                'class' => 'small'
            )
        );
    }

    /**
     * Initialise Gateway Settings Form Fields
     */
    function init_form_fields() {
        global $woocommerce;

        $this->form_fields = array(

            'enabled' => array(
                'title' => __('Enable/Disable', 'hypnoticshipper'),
                'type' => 'checkbox',
                'label' => __('Enable ' . $this->carrier, 'hypnoticshipper'),
                'default' => 'yes'
            ),
            'debug' => array(
                'title' => __('Debug mode', 'hypnoticzoo'),
                'type' => 'checkbox',
                'label' => __('Enable debug mode', 'hypnoticzoo'),
                'default' => 'no'
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
            'fee_to_ship' => array(
                'title' => __('Apply handling fee to shipping rate.', 'hypnoticzoo'),
                'type' => 'checkbox',
                'description' => __('Instead of applying handling fee to product value, apply it to shipping rate.', 'hypnoticzoo'),
                'default' => ''
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

        );

        if ( empty($this->letter_shipping_methods) ) {
            unset($this->form_fields['letter_methods']);
        }

    }

    /**
    * Add additional form fields
    */
    function add_form_fields(){}

    /**
    * Sort admin fields for displaying in order
    */
    function sort_form_fiels(){

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
    function is_available() {

        if (!in_array($this->currency, $this->allowed_currencies))
            return false;

        if (!in_array($this->origin_country, $this->allowed_origin_countries))
            return false;

        if (empty($this->letter_shipping_methods) && empty($this->package_shipping_methods))
            return false;

        return parent::is_available();

    }

    /**
    * Encode request
    */
    function encode( $request ) {
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
    function decode( $response ) {
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
     * Validate Settings Field Data.
     *
     * Validate the data on the "Settings" form.
     *
     * @since 1.0.0
     * @uses method_exists()
     * @param bool $form_fields (default: false)
     * @return void
     */
    public function validate_settings_fields( $form_fields = false, &$other_sanitized_fields = none ) {

        if ( ! $form_fields )
            $form_fields = $this->form_fields;

        $sanitized_fields = array();

        foreach ( $form_fields as $k => $v ) {
            if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) ) { $v['type'] == 'text'; } // Default to "text" field type.

            if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
                $field = $this->{'validate_' . $v['type'] . '_field'}( $k );
                $sanitized_fields[$k] = $field;
            } else {
                $sanitized_fields[$k] = $this->settings[$k];
            }
        }

        if ( $other_sanitized_fields != none ) {
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
        $this->validate_settings_fields($this->box_form_fields, $custom_box_fields);

        // Validate normal setting fields
        $this->validate_settings_fields();

        if ( !empty($custom_box_fields) )
            array_merge($this->sanitized_fields, array('custom_boxes' => $custom_box_fields));

        if ( count( $this->errors ) > 0 ) {
            $this->display_errors();
            return false;
        } else {
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
            table.form-table { clear: none; float: left; }
            table.wide { width: 650px; border-right: thin solid #DFDFDF; margin-right: 15px !important; }
            table.narrow { width: 500px; }
            
        </style>
        <h3><?php _e($this->carrier, 'hypnoticzoo'); ?></h3>
        <p><?php _e($this->description, 'hypnoticzoo'); ?></p>
        <table class="form-table wide">

            <?php
            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            ?>

        </table><!--/.form-table-->

        <h4><?php _e('Container Settings', 'hypnoticzoo'); ?></h4>
        <table class="form-table narrow">

            <tbody>
                <input type="hidden" placeholder="" value="" style="" id="woocommerce_<?php echo $this->id; ?>_box_id" name="woocommerce_<?php echo $this->id; ?>_box_label" class="input-text regular-input ">

                <tr valign="top">
                    <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_label">Label</label></th>
                    <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span>Label</span></legend>
                        <input type="text" placeholder="" value="" style="" id="woocommerce_<?php echo $this->id; ?>_box_label" name="woocommerce_<?php echo $this->id; ?>_box_label" class="input-text regular-input "> <p class="description">Label your box for easier management.</p>
                    </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_width">Dimensions</label></th>
                    <td class="forminp">
                        <fieldset>
                            <input type="text" placeholder="Width" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_width" name="woocommerce_<?php echo $this->id; ?>_box_width" class="input-text regular-input small">
                            <input type="text" placeholder="Length" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_length" name="woocommerce_<?php echo $this->id; ?>_box_length" class="input-text regular-input small">
                            <input type="text" placeholder="Height" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_height" name="woocommerce_<?php echo $this->id; ?>_box_height" class="input-text regular-input small">
                            <input type="text" placeholder="Girth" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_girth" name="woocommerce_<?php echo $this->id; ?>_box_girth" class="input-text regular-input small">
                            <span class="description">in Inch.</span>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_max_weight">Max weight can hold</label></th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Max weight can hold</span></legend>
                            <input type="text" placeholder="" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_max_weight" name="woocommerce_<?php echo $this->id; ?>_box_max_weight" class="input-text regular-input small"> <span class="description">in LBS</span>
                        </fieldset>
                    </td>
                </tr>
                <tr valign="top">
                    <th class="titledesc" scope="row"><label for="woocommerce_<?php echo $this->id; ?>_box_max_unit">Max units can hold</label></th>
                    <td class="forminp">
                        <fieldset>
                            <legend class="screen-reader-text"><span>Max units can hold</span></legend>
                            <input type="text" placeholder="" value="" style="width: 5em;" id="woocommerce_<?php echo $this->id; ?>_box_max_unit" name="woocommerce_<?php echo $this->id; ?>_box_max_unit" class="input-text regular-input small"> <p class="description">The maxiumum number of items can be put into the box.</p>
                        </fieldset>
                    </td>
                </tr>

        </tbody>

        </table><!--/.form-table-->
        <div class="clear"></div>
        <?php
    }

    /************** Help functions *****************/

    /**
     * Use WooCommerce logger if debug is enabled.
     */
    function add_log( $message, $debug_on = true ) {
        global $woocommerce;
        if ( $this->debug=='yes' || !$debug_on ) {
            if ( !$this->log ) $this->log = $woocommerce->logger();

            $this->log->add( $file=$this->id, $message );
        }
    }

    /**
    * Show response returns when debug is on
    */
    function show_response( $response ){
        global $woocommerce;
        if ( $this->debug == 'yes' ) {
                $woocommerce->clear_messages();
                $woocommerce->add_message('<p>'. $this->carrier .' Response:</p><ul><li>' . implode('</li><li>', $response) . '</li></ul>');
        }
    }

}
?>