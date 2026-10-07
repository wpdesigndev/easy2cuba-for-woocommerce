=== Easy2Cuba for WooCommerce ===
Contributors: gmeti
Tags: woocommerce, checkout, cuba, shipping, envios
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

La forma más fácil de vender y enviar a Cuba.

== Description ==

**Easy2Cuba for WooCommerce** sustituye el checkout de WooCommerce por uno pensado para vender desde cualquier país y entregar en Cuba. Quien paga puede estar en Miami, Madrid o La Habana; quien recibe está en Cuba, y el checkout pide exactamente lo que el mensajero necesita.

= Quién compra =

* Nombres, apellidos y correo, donde llega la confirmación del pedido.
* Teléfono opcional con prefijo libre.
* Dirección y país obligatorios: el país se elige en la lista o se pone solo al escribir el prefijo del teléfono.

= Quién recibe en Cuba =

* Nombres y apellidos de quien recibe.
* Teléfono móvil (+53) obligatorio y fijo opcional.
* Carné de identidad opcional, para verificar a la persona en la entrega.
* Las 15 provincias, el municipio especial Isla de la Juventud y sus 168 municipios.
* Calle y número, entre calles, reparto y referencias para el mensajero.
* Casilla de permiso de quien recibe (se puede desactivar).

= Envío por provincia =

* Pones el precio de envío de cada provincia desde la administración.
* Activas solo las provincias donde entregas.
* El costo aparece al elegir la provincia y se suma al total.

= Resumen del pedido claro =

* Foto del producto, nombre y cantidad (x2).
* Precio por unidad: `$14.00 c/u × 2 = $28.00`.

= PDF de entrega automático =

* Cuando se confirma el pago se envía por correo un documento PDF informativo (FAC-0001, FAC-0002…) con todos los datos de la entrega, listo para reenviar al mensajero o al dueño.
* Con tu logo, correo de destino configurable y pie de página propio.
* Registro de documentos enviados: verlos o eliminarlos cuando quieras.
* Envío de prueba y SMTP opcional, con el motivo exacto si el correo falla.

= Compatible =

* Cualquier pasarela de pago de WooCommerce: PayPal, Stripe, tarjetas, transferencias…
* Elementor, plugins de multimoneda (WPML/WCML, CURCY) y pedidos HPOS.
* Español e inglés, según el idioma de tu WordPress.

= Privacidad =

* Se integra con las herramientas de exportar y borrar datos personales de WordPress.
* Propone un texto para tu política de privacidad.
* Puede borrar automáticamente los documentos antiguos del registro.

Desarrollado por [GMETI](https://gmeti.com). Página del plugin: [wpdesigndev.github.io/easy2cuba-for-woocommerce](https://wpdesigndev.github.io/easy2cuba-for-woocommerce/).

== Installation ==

1. Descarga `easy2cuba-for-woocommerce.zip` desde la [última versión en GitHub](https://github.com/wpdesigndev/easy2cuba-for-woocommerce/releases/latest).
2. En WordPress ve a Plugins › Añadir nuevo › Subir plugin, elige el zip y actívalo.
3. Ve a **Easy2Cuba** en el menú lateral y pon el costo de envío de cada provincia.
4. Si te avisa de que tu página de checkout usa bloques, pulsa **Cambiar a checkout clásico**.
5. En la pestaña **Facturas** revisa el correo que recibirá los PDF y haz un envío de prueba.

**Requisitos:** WordPress 6.0+, WooCommerce 7.0+, PHP 7.4+.

== Frequently Asked Questions ==

= ¿Es gratis? =

Sí, es gratis y de código abierto (GPLv2 o posterior).

= ¿Funciona con mi método de pago? =

Sí. Easy2Cuba solo cambia los datos que se piden en el checkout; el pago lo sigue haciendo la pasarela que tengas instalada.

= ¿El PDF es una factura fiscal? =

No. Es un documento informativo que reúne en una sola hoja todos los datos del pedido y de la entrega.

= El correo de prueba no llega, ¿qué hago? =

Muchos servidores no envían correo por sí solos. Activa el SMTP en la pestaña Facturas con los datos de tu correo; si falla, el plugin te muestra el motivo exacto.

= ¿Cómo se actualiza? =

Solo. Cuando se publica una versión nueva en GitHub aparece el aviso en Plugins y se actualiza con un clic. El plugin solo consulta cuál es la última versión (como mucho cada 6 horas) y no envía datos de la tienda ni de los clientes.

= ¿Dónde pido ayuda? =

Escribe a easy2cubaforwoo@gmeti.com.

== Screenshots ==

1. Checkout: quién compra, quién recibe en Cuba y resumen del pedido.
2. Administración: precio de envío por provincia.
3. Administración: documentos PDF, registro y envío de prueba.
4. Documento PDF con todos los datos de la entrega.

== Changelog ==

= 1.6.3 =
* País, Provincia y Municipio tienen el mismo espacio interior, fondo y letra que los demás campos, con cualquier tema.

= 1.6.2 =
* El campo País tiene el mismo aspecto que los demás campos (bordes, tamaño y color verde al estar bien).

= 1.6.1 =
* «Comprobar de nuevo» en Escritorio › Actualizaciones busca la versión nueva al momento, sin esperar 6 horas.
* El cuadro «Datos de entrega en Cuba» (pedido en la administración, página de gracias y correos de WooCommerce) sigue el mismo orden que el checkout.

= 1.6.0 =
* Nuevo campo País en «Quién compra», con todos los países: se elige en la lista o se pone solo al escribir el prefijo del teléfono.
* La dirección y el país de quien compra son obligatorios.
* El PDF muestra los datos en el mismo orden que el checkout.
* Pestaña Información: GMETI enlazado, «Creado por Chuck Gomez» y la página del plugin.

= 1.5.1 =
* "Ver detalles" en Plugins ahora muestra la descripción completa, instalación, preguntas frecuentes, capturas y novedades de cada versión, en español o inglés.

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
