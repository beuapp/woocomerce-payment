<?php
/**
 * Plugin Name:     WooCommerce Beu Payment Gateway
 * Plugin URI :     https://github.com/beu/woocommerce-beu
 * Description:     Plugin de integración Woocommerce en Wordpress para aceptar pagos pagos con la plataforma de Beu
 * Version:         0.0.1
 * Author:          Beu
 * Author URI:      http://beu.is/
 * Text Domain:     woocommerce-beu
 * 
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

    require_once( WCBEU_ABSPATH . '/includes/class-wc-beu-payment.php' );

    $beu_gateway = new WC_Beu_Payment();
 }
add_action( 'plugins_loaded' , 'init_beu_payment_gateway', 0 );


