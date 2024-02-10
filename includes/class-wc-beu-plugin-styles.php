<?php


class WC_Beu_Plugin_Styles {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_plugin_styles' ) );
    }

    public function register_plugin_styles() {
        wp_enqueue_style( 'woocommerce-beu-payment-gateway', plugin_dir_path( '../assets/css/beu-style.css'), __FILE__ );
    }
}
