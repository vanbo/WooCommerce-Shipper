<?php
/**
 * Add girth to product meta box.
 *
 * @access public
 * @return void
 */

function woocommerce_product_girth() {
    global $post, $thepostid, $woocommerce;
    $thepostid = $post->ID;

    if ( get_option( 'woocommerce_enable_dimensions', true ) !== 'no' ) {
?>
        <p class="form-field dimensions_field">
            <label for="product_girth"><?php echo __( 'Girth', 'woocommerce' ); ?></label>
            <input id="product_girth" placeholder="<?php _e( 'Girth', 'woocommerce' ); ?>" class="input-text sized" size="6" type="text" name="_girth" value="<?php echo get_post_meta( $thepostid, '_girth', true ); ?>" />
            <span class="description"><?php _e( 'If product is not rectangle, you can define girth for shipping calculation purpose.', 'woocommerce' ); ?></span>
        </p>
        <?php
        
    } else {
        echo '<input type="hidden" name="_girth" value="' . get_post_meta( $thepostid, '_girth', true ) . '" />';
    }
}

/**
 * Process product girth meta.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */

function woocommerce_process_product_girth_metabox( $post_id ) {

    add_post_meta( $post_id, '_girth', '0', true );

    $is_virtual = ( isset( $_POST['_virtual'] ) ) ? 'yes' : 'no';

    if ( isset( $_POST['_girth'] ) && is_numeric( $_POST['_girth'] ) ) {

        if ( $is_virtual == 'no' )
            update_post_meta( $post_id, '_girth', stripslashes( $_POST['_girth'] ) );
        else
            update_post_meta( $post_id, '_girth', '' );

    }
}

/**
 * Add lettermail to product meta box.
 *
 * @access public
 * @return void
 */

function woocommerce_product_lettermail() {
    global $post, $thepostid, $woocommerce;
    $thepostid = $post->ID;

    $send_by_mail = (get_post_meta( $thepostid, '_lettermail', true ) == "yes") ? 'true' : 'false';

    if ( get_option( 'woocommerce_enable_dimensions', true ) !== 'no' ) {
?>
        <p class="form-field dimensions_field">
            <label for="product_lettermail"><?php echo __( 'Send as Letter', 'woocommerce' ); ?></label>
            <input id="product_lettermail" class="input-text sized" type="checkbox" name="_lettermail" <?php if( $send_by_mail ): ?> checked <?php endif; ?> />
            <span class="description"><?php _e( 'Ship this product as letter instead of in box.', 'woocommerce' ); ?></span>
        </p>
        <?php
        
    } else {
        echo '<input type="hidden" name="_lettermail" value="' . $send_by_mail == 'true' . '" />';
    }
}

/**
 * Process product lettermail meta.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post
 * @return void
 */

function woocommerce_process_product_lettermail_metabox( $post_id ) {

    add_post_meta( $post_id, '_lettermail', '0', true );

    $is_virtual = ( isset( $_POST['_virtual'] ) ) ? 'yes' : 'no';

    if ( isset( $_POST['_lettermail'] ) and $_POST['_lettermail'] ) {
        update_post_meta( $post_id, '_lettermail', stripslashes( 'yes' ));
    } else {
        update_post_meta( $post_id, '_lettermail', stripslashes( 'no' ));
    }
}
?>