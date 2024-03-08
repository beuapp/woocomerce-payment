<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


add_action( 'woocommerce_cart_calculate_fees',
	'beu_tc_add_commission_fee');



function beu_tc_add_commission_fee($cart) {

//	$gateway_beu = new WC_Beu_Credit_Card_Payment_Gateway();

//	$percentage_beu_cd= $gateway_beu->beu_tc_get_percentaje_value();

	echo 'aaaaaaaaaaaaaaa---- ' . ' @@@@@@   ' ;
	WC()->cart->add_fee('Beu Comisión', 6000, false, 'standard');
//	$fee_name = "Comisión TC";
//
//	if (isset($percentage_beu_cd)) {
//		$percentage_beu_cd = floatval(str_replace('%', '', $percentage_beu_cd));
//	}
//
//	if (! is_numeric($percentage_beu_cd)) return;
//	$commission_fee = ($cart->cart_contents_total + $cart->shipping_total ) * $percentage_beu_cd / 100;
//
//	if ($commission_fee > 0) {
//		WC()->cart->add_fee($fee_name, $commission_fee, false, 'standard');
//	}
}
