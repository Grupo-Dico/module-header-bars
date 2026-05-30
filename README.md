# LeanCommerce_LeanZote

## Descripcion

`LeanCommerce_LeanZote` administra banners promocionales para la barra superior del frontend en Magento 2.4.x.

Cada banner puede tener texto principal, boton de accion, contador regresivo, rango de publicacion, asignacion por store view, reglas de ocultamiento por URL y colores de apariencia.

El frontend no renderiza el banner activo desde HTML cacheado. El template solo monta el contenedor raiz y carga el JavaScript del modulo. El JavaScript consulta el endpoint `/leanzote/banner/active` por AJAX, y el endpoint responde JSON con headers `no-cache`.

## Objetivo

Permitir que el equipo admin publique promociones visibles en la barra superior del sitio sin cambios de codigo, manteniendo comportamiento correcto con cache, multistore, fechas, mobile y cierre por sesion.

## Arquitectura actual

- `view/frontend/templates/banner.phtml` monta `#leanzote-banner-root` y registra `LeanCommerce_LeanZote/js/banner` con `x-magento-init`.
- `view/frontend/web/js/banner.js` consulta `/leanzote/banner/active` por AJAX.
- `Controller/Banner/Active.php` devuelve JSON y agrega headers `Cache-Control`, `Pragma` y `Expires` para evitar cache del endpoint.
- `Model/Banner/ActiveBannerProvider.php` resuelve los banners activos segun estado, fechas, store view actual y URLs bloqueadas.
- Las fechas que llegan al frontend se envian en UTC como ISO 8601 con `Z`.
- El cierre del banner se maneja solo en navegador con `sessionStorage`.

Esta arquitectura evita que FPC/Varnish conserve un banner vencido o desactivado dentro del HTML de pagina. Al guardar un banner desde admin tambien se limpian los tipos de cache de bloque y page cache.

## Como usar el modulo

1. Ingresar al panel administrativo de Magento.
2. Ir a **Content > Promociones del sitio > Barra superior de anuncios**.
3. En el listado, hacer clic en **Add New Banner**.
4. Completar el formulario **Anuncio de barra superior**.
5. Guardar con **Guardar** o **Guardar y continuar editando**.
6. Revisar el frontend en una tienda asignada al banner.

Para editar un banner existente, ingresar al mismo listado y usar la accion de edicion del registro correspondiente.

## Campos del formulario

### 1. Publicacion y alcance

**Mostrar banner en frontend** (`is_active`)

Activa o desactiva manualmente el banner. Si esta apagado, el banner no se muestra aunque sus fechas esten vigentes.

**Mostrar en tiendas** (`store_id`)

Define en que store views se mostrara el banner.

- `0` representa **All Store Views**.
- Si se selecciona **All Store Views**, el banner aplica a todas las tiendas.
- Si se seleccionan stores especificos, el banner solo aplica a esos stores.
- Si no llega una seleccion valida, el modulo normaliza el valor a **All Store Views**.

### 2. Programacion

**Publicar desde** (`start_date`)

Fecha y hora local de Magento desde la que el banner puede aparecer.

**Publicar hasta** (`end_date`)

Fecha y hora local de Magento en la que el banner deja de mostrarse.

La fecha final no puede ser anterior a la fecha inicial. En base de datos las fechas se guardan en UTC. Al abrir el formulario, el DataProvider convierte las fechas guardadas en UTC a hora local para mostrarlas correctamente. El grid usa columnas de fecha de Magento y muestra las fechas en hora local.

### 3. Mensaje principal

**Texto visible en la barra superior** (`rich_text_content`)

Mensaje principal que vera el cliente. En frontend se inserta como texto, no como HTML.

### 4. Boton de accion

**Agregar boton al banner** (`button_enabled`)

Activa o desactiva el boton. Para que se renderice, tambien deben existir `button_text` y una URL valida en `button_link`.

**Texto del boton** (`button_text`)

Texto visible dentro del boton. El frontend lo inserta como texto y genera una sola etiqueta `<a>`.

**URL de destino del boton** (`button_link`)

Enlace al que se enviara al cliente al hacer clic.

Comportamiento implementado:

- URLs absolutas con `http://` o `https://` se conservan sin cambios.
- URLs relativas que empiezan con `/`, por ejemplo `/sucursales`, se resuelven contra el store view actual. En un store bajo `/df/`, el resultado esperado es `/df/sucursales`.
- Anclas que empiezan con `#` se conservan.
- Protocolos inseguros o no permitidos como `javascript:`, `data:` o `vbscript:` no generan link util, por lo que el boton no se muestra.
- URLs protocol-relative como `//dominio.com/ruta` no se tratan como rutas internas.

**Mostrar boton antes del contador** (`button_before`)

Controla el orden visual cuando hay boton y contador:

- Apagado: contador primero y boton despues.
- Encendido: boton primero y contador despues.

### 5. Contador regresivo

**Agregar contador regresivo** (`counter_enabled`)

Activa o desactiva el contador. El contador muestra dias, horas, minutos y segundos restantes.

**Mostrar contador desde** (`start_date_button`)

Fecha y hora local desde la que el contador puede mostrarse. Debe estar dentro del rango de publicacion del banner.

**Contar hasta** (`end_date_button`)

Fecha y hora local hasta la que el contador cuenta. No puede ser posterior a `end_date`.

Si se captura una fecha del contador, tambien debe capturarse la otra. Si el contador esta activo y no se capturan fechas especificas para el contador, usa el rango general del banner.

Si el banner esta vigente pero el contador aun no llega a su fecha de inicio o ya paso su fecha final, el banner puede mostrarse sin contador. El contador fuera de rango no debe aparecer como `00:00:00` ni en desktop ni en mobile.

### 6. Apariencia

Los colores se administran con color pickers en admin y se normalizan antes de guardar. El frontend vuelve a sanearlos antes de enviarlos al JavaScript.

**Fondo de la barra** (`background_color`)

Color de fondo de la barra. Valor por defecto: `#FFFFFF`.

**Texto de la barra** (`text_color`)

Color del mensaje principal y del boton de cerrar. Valor por defecto: `#333333`.

**Fondo del boton** (`button_color_background`)

Color de fondo del boton. Valor por defecto: `#000000`.

**Texto del boton** (`button_color_text`)

Color del texto del boton. Valor por defecto: `#FFFFFF`.

**Fondo del contador** (`counter_background_color`)

Color de fondo del contador. Valor por defecto: `#000000`.

**Texto del contador** (`counter_color_text`)

Color de numeros, separadores y etiquetas del contador. Valor por defecto: `#FFFFFF`.

### 7. Reglas avanzadas

**Ocultar banner en estas URLs** (`banned_urls`)

Permite ocultar el banner en rutas especificas.

Formato implementado actualmente: entradas separadas por coma.

Ejemplo:

```text
/checkout, /cart, /customer/account
```

Tambien se aceptan URLs absolutas `http://` o `https://`; para comparar se usa solo el path. Las rutas relativas deben iniciar con `/`.

Reglas importantes:

- No separar entradas solo con saltos de linea; el parser actual usa comas como separador.
- No dejar comas al final. Un valor como `/checkout,` se rechaza al guardar porque genera una entrada vacia. Si un valor asi ya existiera guardado, la entrada vacia se ignora al evaluar frontend y no provoca ocultamiento global.
- No usar protocolos distintos de `http` o `https`.
- No usar URLs protocol-relative como `//dominio.com/ruta`.
- Si una entrada guardada no es valida, se ignora al evaluar frontend para evitar ocultamientos globales accidentales.

## Comportamiento en frontend

- El banner solo se muestra si esta activo, dentro de `start_date` / `end_date`, asignado al store actual y permitido para la URL actual.
- El endpoint AJAX usa el store actual del request.
- Si existen varios banners aplicables, pueden mostrarse juntos en la parte superior.
- Los banners asignados especificamente al store actual tienen prioridad visual sobre banners de **All Store Views**.
- En empate de prioridad, el banner con `banner_id` mas alto aparece primero.
- En mobile, multiples banners se apilan calculando la altura real de cada barra.
- La barra ajusta variables CSS para no tapar el header fijo.
- El boton de cerrar se muestra y funciona en mobile.
- El contador se renderiza solo si esta dentro de su propio rango vigente.

## Cierre del banner

Cuando el cliente cierra un banner, `banner.js` guarda un flag en `sessionStorage` con la llave:

```text
leanzote_banner_closed_{bannerId}
```

El cierre dura la sesion actual de la pestana/navegador. Al cerrar la pestana o iniciar una nueva sesion, el banner puede volver a mostrarse si sigue vigente.

El modulo no usa cookies ni `localStorage` para este comportamiento.

## Notas para QA

- Validar banners activos e inactivos manualmente.
- Validar banner vigente, vencido y programado a futuro.
- Validar que las fechas capturadas en admin se reabran igual en formulario y grid.
- Validar que la DB guarde UTC.
- Validar contador vigente, futuro y vencido en desktop y mobile.
- Validar CTA con URL absoluta y relativa en stores con path, por ejemplo `/df/`.
- Validar que protocolos inseguros en `button_link` no rendericen boton.
- Validar asignacion a un store, varios stores y **All Store Views**.
- Validar prioridad entre banners especificos de store y banners globales.
- Validar cierre con `sessionStorage` y que no reaparezca durante la misma sesion.
- Validar `banned_urls` con rutas validas y con entradas invalidas como `/checkout,`.
- Validar que guardar un banner eliminado por otro usuario muestre error y no cree un registro nuevo.
