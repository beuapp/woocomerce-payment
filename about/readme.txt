=== WooCommerce Beu Payment Gateway ===
Contributors: @johnhack10
Tags: Pasarela de pagos, Beu
Stable tag: 0.0.1
Autor: Beu
Versión: 0.0.2
Requires at least: 6.3.2
Tested up to : 6.3.2
WC Requires at least: 8.2.0
Requires PHP: 7.4.33

Un nuevo plugin que te ayuda hacer pagos a través de la pasarela BEU.

== Descripción ==
Acepta pagos de American Express, Master Card, Visa, Diners Club International,
PSE, Bancolombia y Nequi en tu tienda con la pasarela de pagos Beu.

Esté plugin permite a los clientes realizar el pago por medio de la pasarela de pagos Beu.

Funcionalidades:

* Posibilidad de agregar comisiones al total del carrito.
* Integración con pasarelas de pago BEU Tarjeta de crédito.
* Integración con pasarelas de pago BEU PSE.
* Agregada la funcionalidad de procesar el pago correctamente.
* Implementada la opción de mostrar mensajes de respuesta al usuario.
* Mejoras en la interfaz de usuario para mostrar los mensajes de respuesta.


== Instalación ==

Subir directorio:
1. Sube la carpeta `woocommerce-beu-gateway` al directorio `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Configura el plugin a través de la página de ajustes

Accede a las administración de WordPress
1. La carpeta woocommerce-beu-gateway debe estár comprimida en formato ZIP (woocommerce-beu-gateway.zip)
2. Ve a **Plugins**>**Añadir nuevo.**s
3. De click en subir plugin
4. De click en Examintar y debe ubicar el archivo comprimido llamado woocommerce-beu-gateway.zip
5. Haz clic en **Instalar ahora**, debe esperar a que termine la instalación
6. Proceder activar el plugin dando clic en **Activar**. Para una instalación mas tarde puedes hacerlo a través de **Plugins**>**Plugins instalados**


== Configuration ==

Seguir los siguientes pasos para conectar el plugin con tu configuración Beu:

1.  Ir a **WooCommerce**>**Ajustes**.
2.  Dar clic en la pestaña de **Pagos.**
3.  En la lista de los métodos de pago has clic en **Bue Tarjeta de Crédito** y/o **Beu PSE**.


== Screenshots ==
1. Pantalla con los métodos de pago, acá encontraras a Beu Tarjeta de Crédito y Beu PSE. ![Pantalla métodos de pago](assets/images/screenshots/screenshot_config_woo_beu_payment_method.png)
2. Pantalla de ajuste de la pasarela de pago Beu útilizada para realizar el pago por Tarjeta de Crédito ![Pantalla configuración Tarjeta de Crédito](assets/images/screenshots/screenshot_config_woo_beu_tc.png)
3. Pantalla de ajuste de la pasarela de pago Beu útilizada para realizar la configuración del pago por PSE ![Pantalla configuración PSE](assets/images/screenshots/screenshot_config_woo_beu_tc.pse)

== Frequently Asked Questions ==

== Upgrade Notice ==


== Changelog ==

= [0.0.2] =
* Se cambia mensajes de usuario a ingles por defecto y se deja para la traducción al español
* Se adiciona mensaje al usuario cuando la transación está en estado pendiente.

= [0.0.1] =

* Versión inicial
* Funcionalidad inicial del plugin.


== Créditos ==


== Contacto ==
Puedes contactarme en [tu dirección de correo electrónico] para reportar errores, sugerir mejoras o para cualquier otra consulta relacionada con el plugin.


== Agradecimientos ==
