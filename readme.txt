=== Easy2Cuba for WooCommerce ===
Contributors: gmeti
Tags: woocommerce, checkout, cuba, shipping, envios
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

La forma más fácil de vender y enviar a Cuba. / The easiest way to sell and ship to Cuba.

== Description ==

Easy2Cuba sustituye el checkout de WooCommerce por uno pensado para enviar a Cuba:

* Quién compra: nombres, apellidos, correo, teléfono con prefijo (el país se detecta solo) y dirección.
* Quién recibe en Cuba: nombres, apellidos, móvil, fijo, carné de identidad, las 15 provincias y el municipio especial Isla de la Juventud con sus 168 municipios, dirección y referencias.
* Costo de envío por provincia, configurable desde la administración, y provincias que se pueden activar o desactivar.
* Resumen del pedido con foto, cantidad y precio por unidad.
* Compatible con los métodos de pago instalados en WooCommerce (PayPal, Stripe, tarjetas, transferencias…).
* Factura en PDF que se envía por correo cuando se confirma el pago, con registro de facturas, logo propio y envío de prueba.
* En español o inglés según el idioma de WordPress.

== Actualizaciones ==

El plugin consulta las versiones publicadas en https://github.com/wpdesigndev/easy2cuba-for-woocommerce (como mucho cada 6 horas) para avisar de actualizaciones. No envía ningún dato de la tienda ni de los clientes.

== Installation ==

1. Plugins › Añadir nuevo › Subir plugin, elige el .zip y actívalo.
2. Ve a Easy2Cuba en el menú lateral y pon el costo de envío de cada provincia.
3. Si te avisa de que tu página de checkout usa bloques, pulsa "Cambiar a checkout clásico".

== Changelog ==

= 1.5.0 =
* Actualizaciones automáticas desde GitHub: cuando sale una versión nueva, aparece el aviso en Plugins y se actualiza con un clic.

= 1.4.2 =
* Enlace de Soporte en la lista de plugins.

= 1.4.1 =
* El enlace de GMETI en la lista de plugins vuelve y se abre en una pestaña nueva.

= 1.4.0 =
* Privacidad: los datos de Easy2Cuba se incluyen al exportar y borrar datos personales desde WordPress/WooCommerce.
* Texto sugerido para la política de privacidad (Ajustes › Privacidad).
* Borrado automático de facturas antiguas del registro (opcional).
* Casilla de permiso de quien recibe en el checkout (se puede desactivar).
* Descripción del plugin ampliada.

= 1.3.0 =
* Checkout a lo ancho de la pantalla (se puede desactivar).
* Provincia antes que municipio en el correo y en la factura.
* Mejor compatibilidad con pasarelas de pago (país de facturación siempre presente), Elementor y plugins de multimoneda.
* Nuevo icono en el menú de administración.

= 1.2.0 =
* Numeración de facturas FAC-0001…
* Envío de facturas por SMTP opcional y motivo del error cuando falla el correo.
* Descripciones de la administración a todo el ancho.
* Más espacio al final de cada sección del checkout y al final de la página.

= 1.1.0 =
* Facturas en PDF al confirmarse el pago, con registro, logo y prueba de envío.
* Administración en pestañas: Envíos, Facturas e Información.
* Más espacio en el resumen del pedido y la sección de pago.
* La administración ocupa todo el ancho.

= 1.0.0 =
* Primera versión.

== Créditos ==

Generación de PDF con FPDF (www.fpdf.org), licencia permisiva incluida en includes/lib/fpdf/license.txt.
