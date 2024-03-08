<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Beu_Payment {
    public function __construct() {
        require_once WCBEU_ABSPATH . '/includes/class-wc-beu-gateway-helper.php';
        require_once WCBEU_ABSPATH . '/includes/class-wc-beu-payment-gateway-settings.php';
        require_once WCBEU_ABSPATH . '/includes/class-wc-beu-gateway-credit-card-payment.php';
        require_once WCBEU_ABSPATH . '/includes/class-wc-beu-gateway-pse-payment.php';

	    register_activation_hook( __FILE__, 'register_beu_response_pages' );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ),  array( $this, 'WC_Beu_Action_Links') );
        add_filter( 'woocommerce_payment_gateways', array( $this, 'WC_Add_Beu_Gateway') );
    }

    function WC_Beu_Action_Links( $links ) {
        $plugin_links = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">Ajustes</a>';
        array_push($links, $plugin_links);
        return $links;
    }

    public function WC_Add_Beu_Gateway($gateways) {
            $gateways[] = 'WC_Beu_Credit_Card_Payment_Gateway';
            $gateways[] = 'WC_Beu_Pse_Payment_Gateway';

        return $gateways;
    }


    function register_beu_response_pages() {


            $pages = array(
                array(
                    'title'    => __('Response transaction successful Beu', 'woocommerce-beu'),
                    'template' => 'woocommerce-beu-gateway-response-success-page-template.php',
                ),
                array(
                    'title'    => __('Respuesta transacción fallida Beu', 'woocommerce-beu'),
                    'template' => 'woocommerce-beu-gateway-response-error-page-template.php',
                ),
            );


            foreach ( $pages as $page ) {
                $post_id = wp_insert_post(array(
                    'post_title' => $page['title'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_content' => '',
                ));

                update_post_meta($post_id, '_wp_page_template', $page['template']);
            }

        $success_page = get_page_by_title('Response transaction successful Beu');
        $error_page = get_page_by_title('Respuesta transacción fallida Beu');

        if ($success_page) {
            update_option('success_page_id', $success_page->ID);
        }

        if ($error_page) {
            update_option('error_page_id', $error_page->ID);
        }
    }
}

