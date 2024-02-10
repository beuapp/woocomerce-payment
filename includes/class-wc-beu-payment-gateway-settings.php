<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Beu_Payment_Gateway_Settings {
    private $settings;

    public function __construct()
    {
        $this->init_settings();
    }

    public function init_settings()
    {
        $this->settings = get_option('beu_payment_settings');

        if (empty($this->settings)) {
            $this->settings = array(
                'test_payment_card_url' => 'https://payments-stg.beu.is',
                'production_payment_card_url' => 'https://payments.beu.is',
                'test_checkout_card_url' => 'https://beu-link-git-staging-beu.vercel.app/checkout',
                'production_checkout_card_url' => 'https://beu-link-git-beu.vercel.app/checkout',
                'test_payment_pse_url' => 'https://payments-stg.beu.is',
                'production_payment_pse_url' => 'https://payments.beu.is',
                'test_checkout_pse_url' => 'https://beu-link-git-staging-beu.vercel.app/checkout',
                'production_checkout_pse_url' => 'https://beu-link-git-beu.vercel.app/checkout',
                'test_flow' => 'PAYMENT_LINK',
                'production_flow' => 'PAYMENT_LINK',
            );
        }
    }

    public function get_setting($name)
    {
        return $this->settings[$name] ?? '';
    }

}