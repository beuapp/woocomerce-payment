<?php
/**
 * Plugin Name:     WooCommerce Beu Payment Gateway
 * Plugin URI :     https://github.com/beu/woocommerce-beu
 * Description:     Plugin de integración Woocommerce en Wordpress para aceptar pagos con la plataforma de Beu
 * Version:         0.0.2
 * Author:          Beu
 * WordPress requires: 6.3.2
 * WC requires: 8.2.0
 * PHP at least: 7.4.33
 * Author URI:      http://beu.is/
 * Text Domain:     woocommerce-beu
 * Domain Path: /languages
 * @package WooCommerce\Payments
 */

defined( 'ABSPATH' ) || exit;
define( 'WCBEU_PLUGIN_FILE', __FILE__ );
define('WCBEU_ABSPATH', __DIR__ . '/');


 if ( ! in_array('woocommerce/woocommerce.php', apply_filters(
     'active_plugins', get_option('active_plugins')
 ))) return;


if( isset($_GET['msg']) && !empty($_GET['msg']) ) {
    add_action('the_content', 'beu_gateway_show_message');
}

function beu_gateway_show_message($content) {
    return '<div class="'.htmlentities($_GET['type']).'">'.htmlentities(urldecode($_GET['msg'])).'</div>'.$content;
}


function init_beu_payment_gateway() {
   
   if ( ! class_exists('WC_Payment_Gateway' ) ) return;

    require_once WCBEU_ABSPATH . '/includes/class-wc-beu-payment.php';

    $beu_gateway = new WC_Beu_Payment();
 }
add_action( 'plugins_loaded' , 'init_beu_payment_gateway', 0 );

add_action( 'plugins_loaded', 'wc_beu_payment_load_plugin' );
function wc_beu_payment_load_plugin() {
	load_plugin_textdomain( 'woocommerce-beu', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}