<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Beu_Credit_Card_Payment_Gateway extends WC_Payment_Gateway {
    private $helper;
    private $test_mode;
    private $payment_url;
    private $return_url;
    private $checkout_url;
    private $flow;
    private $profile_id;
    private $short_id;


    public function __construct() {
        $this->helper              =    new WC_Beu_Payment_Gateway_Helper();

        $this->id                  =    'beu_tc';
        $this->icon                =    apply_filters('woocommerce_payment_beu_icon', $this->helper->beu_get_icon_card($this->get_option( 'test_mode' )));
        $this->has_fields          =    false;
        $this->title               =    __('Pagos con BEU');
        $this->method_title        =    __('Beu Tarjeta de Crédito');
        $this->method_description  =    __('Pagar con Tarjeta de Crédito');

        $this->init_form_fields();
        $this->init_settings();

        $this->test_mode            =   $this->get_option( 'test_mode' );
        $this->enabled              =   $this->get_option( 'enabled' );
        $this->title                =   $this->get_option( 'title' );
        $this->payment_url          =   $this->helper->beu_get_payment_url($this->test_mode);
        $this->return_url           =   $this->get_option( 'return_url' );
        $this->checkout_url         =   $this->helper->beu_get_checkout_url($this->test_mode);
        $this->flow                 =   $this->helper->beu_get_flow($this->test_mode);
        $this->profile_id           =   $this->get_option( 'profile_id' );
        $this->short_id             =   $this->get_option( 'short_id' );
        $this->private_token        =   $this->get_option( 'private_token' );
        $this->description          =   $this->get_option( 'description' );
        $this->percentage_beu       =   $this->get_option( 'percentage_beu_cd' );

        $this->msg['message'] 	= "";
        $this->msg['class'] 	= "";

        add_action( 'init', array( $this, 'beu_tc_successful_request') );
//        add_action( 'init', array( $this, 'beu_tc_create_response_pages') );
        add_action( 'woocommerce_api_'.  strtolower( get_class( $this ) ) , array( $this, 'beu_tc_check_beu_response'));

        if ( is_admin() ) {
            if ( version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=') ) {
                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
            } else {
                add_action( 'woocommerce_update_options_payment_gateways', array( $this, 'process_admin_options' ));
            }
        }

        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'beu_tc_add_commission'));
        add_action( 'woocommerce_review_order_before_payment', array( $this, 'beu_tc_change_payment_method'));
        add_action( 'woocommerce_receipt_' . $this->id , array( $this, 'beu_tc_process_payment_page'));
    }

    function beu_tc_add_commission_to_cart_total($cart ) {

        $commission_percentage = $this->get_option( 'percentage_beu_cd' );

        if (isset($percentage_beu_cd) && $percentage_beu_cd !== null) {
            $commission_percentage = floatval(str_replace('%', '', $percentage_beu_cd));
        }

        if (! is_numeric($commission_percentage)) return 0;

        $commission_amount = $cart->get_subtotal() * ( $commission_percentage / 100 );

        $cart_total = $cart->get_subtotal() + $commission_amount;

        $formatted_cart_total = wc_price( $cart_total );

        return $formatted_cart_total . ' (' . $commission_percentage . '%)';
    }

    function beu_tc_successful_request($value) {
        global $woocommerce;
        $this->beu_tc_logger('check_beu_response', 'init check_beu_response');
        $this->beu_tc_logger('$value', $value);
        $this->beu_tc_logger('check_beu_response', $_REQUEST);
        $this->msg['message'] = 'TC Beu request';
        $this->msg['class'] = 'woocommerce-message';

        $redirect_url = ($this->return_url=="" || $this->return_url==0)?get_site_url() . "/":get_permalink($this->return_url);

        //For wooCoomerce 2.0
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            $redirect_url = add_query_arg(array('msg' => urlencode($this->msg['message']), 'type' => $this->msg['class']), $redirect_url);
        }

        wp_redirect( $redirect_url );
        exit;

    }

    private function beu_tc_check_gateway_order_status($order_id)
    {
        $response = wp_remote_get($this->payment_url . '/payment-gateway/transaction/' . $order_id . '/' . $this->profile_id);

        if (is_wp_error($response) ) {
            return [
                    'statusCode'=> '500',
                    'message' => 'Error al conectar con la pasarela de pagos.'
            ];
        } else {
            $gateway_order_status = json_decode( wp_remote_retrieve_body($response), true );
            $response_code = wp_remote_retrieve_response_code($response);
            $result='';
            switch ($response_code) {
                case '200':
                    $result = [
                        'statusCode' => $response_code,
                        'message'=> $gateway_order_status['transactionStatus'],
                    ];
                    break;
                case '404':
                    $result = [
                        'statusCode' => $response_code,
                        'message'=> $gateway_order_status['message'],
                    ];
                    break;
            };
            return $result;
        }
    }

    private function beu_tc_get_gateway_order_status($order_id) {
        $gateway_order_status = $this->beu_tc_check_gateway_order_status($order_id);

        if ($gateway_order_status['message'] === 'PROCESSING') {
            $max_retries = 5;
            $retry_interval_seconds = 5;

            for($retry_count = 1; $retry_count <= $max_retries; $retry_count++) {

                sleep($retry_interval_seconds);

                $gateway_order_status = $this->beu_tc_check_gateway_order_status($order_id);

                if ($gateway_order_status !== 'PROCESSING') {
                    return $gateway_order_status;
                    break;
                }
            }
        }
        return $gateway_order_status;
    }


    private function beu_tc_array_to_query_string(array $beu_parameters ) {
        $beu_params = array();
        foreach ($beu_parameters as $key => $value) {
            $beu_params[] = $key . '=' .$value;
        }
        return $beu_params;
    }


    private function beu_tc_get_products_description(array $products )
    {
        foreach($products as $product) {
            $description .= $product['name'] . ',';
        }

        if (strlen($description) > 255){
            $description = substr($description,0,240).' y otros...';
        }

        return $description;
    }

    function beu_tc_get_return_url($order)
    {
        if ( $this->return_url == "" || $this->return_url == 0 ) {
            $redirect_url = $order->get_checkout_order_received_url();
        } else {
            $redirect_url = get_permalink($this->return_url);
        }
        
        //For wooCoomerce 2.0
        if ( version_compare( WOOCOMMERCE_VERSION, '2.0.0', '>=' ) ) {
            $redirect_url = add_query_arg( 'wc-api', get_class( $this ), $redirect_url );
            $redirect_url = add_query_arg( 'order_id', $order->id, $redirect_url );
        }


        return $redirect_url;
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'test_mode' => array(
                'title'         =>      __('Modo de Desarrollo'),
                'label'         =>      __('Habilitar modo de desarrollo'),
                'type'          =>      'checkbox',
                'description'   =>      __('Marca para habilitar el modo de desarrollo.'),
                'default'       =>      'no',
                'desc_tip'      =>      true,
            ),
            'enabled' => array(
                'title'         =>      __('Habilitar/Desabilitar'),
                'label'         =>      __('Habilitar BEU'),
                'type'          =>      'checkbox',
                'description'   =>      __('Activa o Inactiva el componente'),
                'default'       =>      'no',
                'desc_tip'      =>      true,
            ),
            'title' => array(
                'title' => __('Título'),
                'type'=> 'text',
                'description'   =>      __('Título que el usuario verá durante checkout.'),
                'default'       =>      __('Tarjeta de Crédito')),
            'description' => array(
                'title'         =>      __('Descripción'),
                'type'          =>      __('textarea'),
                'description'   =>      __('Mensaje que se le va a mostrar al usuario durante el pago'),
                'default'       =>      __('Pagar con Tarjeta de Crédito'),
                'desc_tip'      =>      true,
            ),
            'return_url' => array(
                'title'         =>      __('URL de Retorno'),
                'type'          =>      'select',
                'options' 		=>      $this->helper->get_pages(__('Select Page')),
                'description'   =>      __('Seleccioné URL de retorno después del pago.'),
                'desc_tip'      =>      true,
            ),
            'percentage_beu_cd' => array(
                'title'         =>      __('% Beu'),
                'type'          =>      'text',
                'description'   =>      __('Ingresa el % en Beu, ej: 3%'),
                'default'       =>      '0%',
                'desc_tip'      =>      true,
                'custom_attributes' => array(
                    'pattern'   => '^(\d{1,2}(\.\d{1,2})?|100(\.0{1,2})?)%?$',
                    'required'  => 'required',
                ),
            ),
            'profile_id' => array(
                'title'         =>      __('ProfileId'),
                'type'          =>      'text',
                'description'   =>      __('Ingresa el ProfileId'),
                'default'       =>      '',
                'desc_tip'      =>      true,
            ),
            'short_id' => array(
                'title'         =>      __('ShortId'),
                'type'          =>      'text',
                'description'   =>      __('Ingresa el ShortId.'),
                'default'       =>      '',
                'desc_tip'      =>      true,
            ),
            'token' => array(
                'title'         =>      __('Token'),
                'type'          =>      'text',
                'description'   =>      __('Ingresa tu token aquí.'),
                'default'       =>      '',
                'desc_tip'      =>      true,
            ),
        );
    }

    public function admin_options() {
        ?>
        <h3><?php _e( 'Custom Payment Settings', 'woocommerce-beu-payment-gateway' ); ?></h3>
        <div id="poststuff">
            <?php
            if ($this->test_mode == 'yes') {
                echo '<h1 style="color: #8d0a0a;" class="wc-payment-gateway-beu-test-mode">' .__('EL MODO TEST ESTÁ HABILITADO', 'woocommerce-beu-payment-gateway').'</h1>';
            }
            ?>
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <table class="form-table">
                        <?php $this->generate_settings_html();?>
                    </table>
                </div>
                <div id="postbox-container-1" class="postbox-container">
                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                        <div class="postbox ">
                            <h3 class="hndle"><span><i class="dashicons dashicons-editor-help"></i>&nbsp;&nbsp;Soporte Plugin</span></h3>
                            <hr>
                            <div class="inside">
                                <div class="support-widget">
                                    <p>
                                        <img class="wc-payment-gateway-beu-logo" alt="Beu" src="<?php echo plugins_url('assets/images/beu.svg', WCBEU_PLUGIN_FILE) ?>" />
                                        <br/>
                                        Quieres ser parte, contáctanos</p>
                                    <ul>
                                        <li>» <a href="https://beu.is/submit-ticket/" target="_blank">Soporte</a></li>
                                        <li>» <a href="https://beu.is/woocommerce-beu-payment-gateway/" target="_blank">Documentación.</a></li>
                                    </ul>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="clear"></div>
        <?php
    }

    function is_available()
    {
        global $woocommerce;

        if ( $this->enabled === 'no' ) return false;

        if( empty( $this->flow ) || empty( $this->profile_id ) || empty( $this->short_id )  ) {
            return false;
        }

        if( ! $this->test_mode && ! is_ssl() ) {
            return false;
        }

        return true;
    }

    function beu_tc_payment_scripts()
    {
        require_once( WCBEU_PLUGIN_FILE . '/includes/class-wc-beu-plugin-styles.php');
        $beu_styles_gateway = new WC_Beu_Plugin_Styles();
    }

    function beu_tc_get_parameters_settings($order_id ) {
        global $woocommerce;
        $order              =       new WC_Order( $order_id );
        $flow               =       $this->flow;
        $price              =       $this->helper->convert_cop_to_usd( $order->get_total() );
        $externalReference  =       $order_id;
        $returnUrl          =       self::beu_tc_get_return_url($order);
        $shortId            =       $this->short_id;

        return array(
            'flow'                  =>  $flow,
            'price'                 =>  $price,
            'externalReference'     =>  $externalReference,
            'returnUrl'             =>  $returnUrl,
            'shortId'               =>  $shortId,
        );
    }

    function beu_tc_get_parameters_args($order_id ) {
        global $woocommerce;
        $order              =       new WC_Order( $order_id );
        $price              =       $this->helper->convert_cop_to_usd( $order->get_total() );
        $products           =       $order->get_items();
        $tax                =       $order->get_total_tax();
        $taxReturnBase      =       $price - $tax;
        if ($tax == 0) $taxReturnBase = 0;

        foreach($products as $product) {
            $description .= $product['name'] . ',';
        }
        if (strlen($description) > 255){
            $description = substr($description,0,240).' y otros...';
        }

        return array(
            'description'           =>  $description,
            'price'                 =>  $price,
            'tax'                   =>  $tax,
            'taxReturnBase'         =>  $taxReturnBase,
            'buyerEmail'            => $order-> billing_email,
            'shippingAddress'       => $order->shipping_address_1,
            'shippingCountry'       => $order->shipping_country,
            'shippingCity'          => $order->shipping_city,
            'billingAddress'        => $order->billing_address_1,
            'billingCountry'        => $order->billing_country,
            'billingCity'           => $order->billing_city,
        );
    }

    private function beu_tc_chosen_payment_method()
    {
        return WC()->session->get('chosen_payment_method');
    }

    function beu_tc_add_commission() {
        global $woocommerce;

        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        $fee_name = "Comisión TC Beu";

        if (self::beu_tc_chosen_payment_method() !== $this->id) return;

        $percentage_beu_cd = $this->get_option( 'percentage_beu_cd' );

        if (isset($percentage_beu_cd) && $percentage_beu_cd !== null) {
            $percentage_beu_cd = floatval(str_replace('%', '', $percentage_beu_cd));
        }

        if (! is_numeric($percentage_beu_cd)) return;
        $commission_fee = ($woocommerce->cart->cart_contents_total + $woocommerce->cart->shipping_total ) * $percentage_beu_cd / 100;

        if ($commission_fee > 0) {
            $woocommerce->cart->add_fee($fee_name, $commission_fee, false, 'standard');
        }
    }

    function beu_tc_change_payment_method() {
        ?><script type="text/javascript">
            (function($){
                $('form.checkout').on('change', 'input[name^="payment_method"]', function() {
                    $('body').trigger('update_checkout');
                });
            })(jQuery);
        </script><?php
    }
    function beu_tc_display_commission_old() {

        $percentage_beu_cd = $this->get_option( 'percentage_beu_cd' );
        $this->beu_tc_logger('beu_tc_display_commission_old', $percentage_beu_cd);
        $commission = WC()->cart->get_fee_total('Comisión TC Beu');
        if ($commission > 0) {
            ?>
            <tr>
                <th><?php esc_html_e('Comisión Beu '. $percentage_beu_cd . '%'); ?></th>
                <td><?php echo wc_price($commission); ?></td>
            </tr>
            <?php
        }
    }

    function beu_tc_logger($option, $message)
    {
        wc_get_logger()->debug( $option . ' ' . print_r( $message, true ), array( 'source' => 'beu-log' ) );
    }


    function payment_fields() {
        if( $this->description ) echo wpautop( wptexturize( $this->description ) );
    }

    function process_payment( $order_id ) {
        global $woocommerce;
        $order = new WC_Order( $order_id );

        if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '>=' ) ) {
            /* 2.1.0 */
            $checkout_payment_url = $order->get_checkout_payment_url( true );
        } else {
            /* 2.0.0 */
            $checkout_payment_url = get_permalink( get_option ( 'woocommerce_pay_page_id' ) );
        }

        $parameters_beu_settings = $this->beu_tc_get_parameters_settings( $order_id );
        $beu_query_string = http_build_query( $parameters_beu_settings );
        $beu_url_checkout = $this->checkout_url . '?' . $beu_query_string;

        $order->update_status('processing', __('Esperando el proceso del pago.'));

        return array(
            'result' => 'success',
            'redirect' => add_query_arg(
                'order',
                $order->id,
                add_query_arg(
                    'key',
                    $order->order_key,
                    $beu_url_checkout
                )
            )
        );
    }

    function beu_tc_process_payment_page($order_id ) {
        $order = new WC_Order( $order_id );
        echo '<p>' . __( 'Redireccionando al proveedor de pagos.') . '</p>';

        $order->add_order_note( __( 'Pedido realizado y el usuario será redirigido a la pasarela de pagos.') );

        echo '<p>'.__('Gracias por su pedido, de clic en el botón que aparece para continuar el pago con Beu.', 'woocommerce-beu-payment-gateway').'</p>';
        echo $this -> beu_tc_generate_beu_payment_data_form( $order_id );
    }


    function beu_tc_generate_beu_payment_data_form($order_id ) {
        global $woocommerce;
        $order                      = new WC_Order( $order_id );
        $parameters_beu_settings    = $this->beu_tc_get_parameters_settings( $order_id );

        $beu_params_array[] = [];
        foreach($parameters_beu_settings as $key => $value){
            $beu_params_array[] = '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"/>';
        }

        $beu_query_string = http_build_query($parameters_beu_settings);
        $beu_url_checkout = $this->checkout_url . '?' . $beu_query_string;

        $order->update_status('processing', __('Esperando el proceso del pago.'));

//        $code='jQuery("#submit_beu_form").click();';
//
//        if (version_compare( WOOCOMMERCE_VERSION, '2.1', '>=')) {
//            wc_enqueue_js($code);
//        } else {
//            $woocommerce->add_inline_js($code);
//        }

        return '<form action="'.$beu_url_checkout.'" method="get" id="beu_form" target="_top">' . implode('', $beu_params_array )
            . '<input type="submit" id="submit_beu_form" value="' .__('Pagar', 'woocommerce-beu-payment-gateway').'" /><a href="'.esc_url( $order->get_cancel_order_url() ).'">'.__( 'Cancelar orden', 'woocommerce-beu-payment-gateway').'</a></form>';

    }

    function beu_tc_get_transaction_status($transaction_status)
    {
        switch ($transaction_status) {
            case 'SUCCEEDED':
                return 'completed';
            case 'FAILED':
                return 'failed';
            default:
                return 'processing';
        }
    }


    public function beu_tc_check_beu_response(){
        global $woocommerce;
        $this->beu_tc_logger('beu_tc_check_beu_response', 'init');
        $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
        $order_key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
        if (!empty($order_id) && !empty($order_key)) {

            $order = new WC_Order($order_id);
            $gateway_order_status = $this->beu_tc_get_gateway_order_status($order_id);
            $order_status = $order->get_status();
            $transaction_status = "";
            if ( $order_status !== 'completed' ) {
                if ($gateway_order_status['statusCode'] == 200) {
                    $transaction_status = $this->beu_tc_get_transaction_status($gateway_order_status['message']);
                    switch ($transaction_status) {
                        case 'completed':
                            $this->msg['message'] = "Gracias por comprar con nosotros. ¡El pago se ha procesado exitosamente!.";
                            $this->msg['class'] = 'woocommerce-message';
                            $order->update_status('completed');
                            $order->reduce_order_stock();
                            $order->add_order_note('Beu aprobó el pago con éxito. Orden: '.$order_id);
                            $woocommerce->cart->empty_cart();
                            break;
                        case 'failed':
                            $order->update_status('failed', 'Gracias por comprar con nosotros, la transacción ha sido declinada. Resultado: '. $gateway_order_status['message']);
                            $this->msg['message'] = "Error en la validación con Beu: ". $gateway_order_status['message'];
                            $this->msg['class'] = 'woocommerce-error';
                            $order->add_order_note('Error transacción declinada con Beu. Orden:'.$order_id . ' ' . $gateway_order_status['message']);
                            break;
                    }

                }

                if ($gateway_order_status['statusCode'] == 404) {
                    $order->update_status('failed', 'Gracias por comprar con nosotros. Resultado: '. $gateway_order_status['message']);
                    $this->msg['message'] = "Error en la validación con Beu: ". $gateway_order_status['message'];
                    $this->msg['class'] = 'woocommerce-error';
                    $order->add_order_note('Error en la validación con Beu. Orden:'.$order_id . ' ' . $gateway_order_status['message']);
                }

                if ($gateway_order_status['statusCode'] == 500) {
                    $order->update_status('on-hold', 'El pago actualmente se encuentra en espera.');
                    $order->reduce_order_stock();
                    $order->add_order_note('Beu el pago actualmente está en espera. Orden:'.$order_id);
                    $this->msg['message'] = "Gracias por comprar con nosotros. En estos momentos su transacción se encuentra en espera.";
                    $this->msg['class'] = 'woocommerce-info';
                }
            }

            $redirect_url = ($this->return_url == 'default' || $this->return_url == "" || $this->return_url == 0) ? $order->get_checkout_order_received_url() : get_permalink($this->return_url);
            //For wooCoomerce 2.0
            $redirect_url = add_query_arg(array('msg' => urlencode($this->msg['message']), 'type' => $this->msg['class']), $redirect_url);

            wp_redirect($redirect_url);
//            $this->beu_tc_process_payment_response($transaction_status);
            exit;
        }
    }

    function beu_tc_create_response_pages($success_content = '', $error_content = '') {
        $this->beu_tc_logger('beu_tc_create_response_pages', 'init');
        $success_page_exists = get_page_by_title('Resultado transacción exitosa Beu');
        $error_page_exists = get_page_by_title('Resultado transacción fallida Beu');

        $this->beu_tc_logger('beu_tc_create_response_pages', $success_page_exists);
        $this->beu_tc_logger('$error_page_exists', $error_page_exists);

        if (!$success_page_exists) {
            $success_page = array(
                'post_title'   => 'Resultado transacción exitosa Beu',
                'post_content' => $success_content,
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'page'
            );
            $beu_success_page_id = wp_insert_post($success_page);
            update_option('beu_success_page_id', $beu_success_page_id);
        }

        if (!$error_page_exists) {
            $error_page = array(
                'post_title'   => 'Resultado transacción fallida Beu',
                'post_content' => $error_content,
                'post_status'  => 'publish',
                'post_author'  => 1,
                'post_type'    => 'page'
            );
            $error_page_id = wp_insert_post($error_page);
            update_option('error_page_id', $error_page_id);
        }
    }

    function beu_tc_process_payment_response($transaction_status) {
        WC()->session->set( 'payment_result', $transaction_status );

        $response_content_success = '<p>¡El pago se ha procesado exitosamente!</p>';
        $response_content_error = '<p>Hubo un error al procesar el pago.</p>';
        $this->beu_tc_create_response_pages($response_content_success, $response_content_error);

        $page_id = ($transaction_status === 'completed') ? get_option('beu_success_page_id') : get_option('error_page_id');

        $redirect_url = get_permalink($page_id);
        $this->beu_tc_logger('process_payment_response', $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }
}