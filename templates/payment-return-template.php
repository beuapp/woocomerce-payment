<?php

get_header();

echo '<div id="primary" class="content-area">';
echo '<main id="main" class="site-main" role="main">';


echo '<section class="woocommerce-order-details">';
echo '<h1 class="woocommerce-order-details__title">' . esc_html__('Estado de la Transacción') . '</h1>';

echo '<p>' . esc_html__('¡Gracias por tu compra!') . '</p>';

echo '</section>';
echo '</main>';
echo '</div>';

get_footer();
