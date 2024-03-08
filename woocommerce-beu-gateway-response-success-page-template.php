<?php
get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        $transaction_successful = $payment_response = WC()->session->get( 'payment_result' );

        if ($transaction_successful === 'completed') :
            ?>
            <h1>¡Transacción Exitosa!</h1>
            <p>Su pago ha sido procesado exitosamente.</p>


        <?php else : ?>
            <h1>Error en la Transacción</h1>
            <p>Lo sentimos, ha ocurrido un error durante el procesamiento de su pago.</p>
        <?php endif; ?>

    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
