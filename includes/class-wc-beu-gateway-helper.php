<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Beu_Payment_Gateway_Helper {
    private $settings_instance;


    public function __construct()
    {
        $this->settings_instance = new Beu_Payment_Gateway_Settings();
    }

    public function beu_get_payment_url($test_mode)
    {
        return $test_mode == 'yes' ? $this->settings_instance->get_setting('test_payment_card_url') : $this->settings_instance->get_setting('production_payment_card_url');
    }

    public function beu_get_checkout_url($test_mode)
    {
        return $test_mode == 'yes' ? $this->settings_instance->get_setting('test_checkout_card_url') : $this->settings_instance->get_setting('production_checkout_card_url');
    }

    public function beu_get_icon_card($test_mode) {
        return $test_mode == 'yes' ?
            plugins_url('assets/images/cards/card-test.png', WCBEU_PLUGIN_FILE):
            plugins_url('assets/images/cards/card-production.png', WCBEU_PLUGIN_FILE);
    }

    public function beu_get_icon_pse($test_mode) {
        return $test_mode == 'yes' ?
            plugins_url('assets/images/transfers/transfer-test.png', WCBEU_PLUGIN_FILE):
            plugins_url('assets/images/transfers/transfer-production.png', WCBEU_PLUGIN_FILE);
    }

    public function beu_get_flow($test_mode)
    {
        return $test_mode == 'yes' ? $this->settings_instance->get_setting('test_flow') : $this->settings_instance->get_setting('production_flow');
    }


    public function convert_cop_to_usd($currency_input) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://service-product.beu.is/trm',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            )
        );
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response) {
            $currency_json = json_decode($response, true);
            $currency_output = (float)$currency_input / $currency_json['COP']['openMarketCurrencyConversion'];
            return number_format($currency_output, 2, '.', '');
        } else
            return $currency_input;
    }

    function get_pages($title = false, $indent = true) {
        $wp_pages = get_pages('sort_column=menu_order');
        $page_list = array('default'=>__('Default Page'));
        if ($title) $page_list[] = $title;
        $page_list['orders'] = __('Orders Page');
        $page_list['view-order'] = __('View Order Page');
        foreach ($wp_pages as $page) {
            $prefix = '';
            if ($indent) {
                $has_parent = $page->post_parent;
                while($has_parent) {
                    $prefix .=  ' - ';
                    $next_page = get_page($has_parent);
                    $has_parent = $next_page->post_parent;
                }
            }
            $page_list[$page->ID] = $prefix . $page->post_title;
        }
        return $page_list;
    }

    function logger($option, $message)
    {
        wc_get_logger()->debug( $option . ' ' . print_r( $message, true ), array( 'source' => 'beu-log' ) );
    }
}