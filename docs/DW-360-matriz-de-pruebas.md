# Matriz de pruebas DW-360: textos con formato y emojis en la barra superior

Módulo: `LeanCommerce_LeanZote` (módulo 1.0.12). Ruta Admin: Content > Promociones del sitio > Barra superior de anuncios.

Estado: `Pendiente` en todas las pruebas. QA registra `OK`, `Falla` o `N/A` y la evidencia.

## 0. Preparación

| Paso | Detalle |
|---|---|
| Instalar | `composer install`, `bin/magento setup:upgrade` (aplica el upgrade 1.0.11), `setup:di:compile`, `setup:static-content:deploy -f es_MX`, `cache:flush` |
| Datos | Crear 3 banners activos a la vez: A (con contador y CTA), B (sin contador, con CTA), C (con contador, sin CTA). Cada uno con 3 mensajes |
| Dispositivos | Desktop 1440 px, tablet 768 y 1024 px, mobile 375 y 320 px |
| Navegadores | Chrome, Safari (iOS) y Firefox. Emojis también en Android |
| Respaldo | Respaldar la tabla `leanCommerce_leanZote` antes del upgrade |

## 1. Campos con formato en el Admin

| ID | CA | Prueba | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| T01 | CA01 | Mensajes con editor | Abrir un banner y revisar Mensaje 1, 2 y 3, Desktop y Mobile | Los 6 campos muestran el editor con tipo de letra, tamaño, negrita, cursiva, subrayado, color y alineación | Pendiente |
| T02 | CA02 | CTA con editor | Revisar CTA 1, 2 y 3 (Desktop y Mobile) y Texto CTA general (sección 4) | Los 7 campos muestran el mismo editor | Pendiente |
| T03 | CA03 | Contador con editor | Sección 5: revisar "Texto que acompaña al contador" y los formatos de números, etiquetas y separadores | Los 4 campos muestran el editor | Pendiente |
| T04 | CA04 | Lista de fuentes | Abrir el selector de tipo de letra en un campo | Solo aparecen las fuentes del editor de Magento. No hay fuentes nuevas | Pendiente |
| T05 | 6.1 | URLs sin cambio | Revisar URL CTA 1 a 3 (Desktop y Mobile) y URL CTA general | Siguen siendo campos de texto simple, sin editor | Pendiente |
| T06 | 6.1 | Vista previa en el editor | Aplicar negritas, color y tamaño en un fragmento | El editor muestra el formato antes de guardar | Pendiente |
| T07 | CA07 | Beneficio destacado eliminado | Revisar secciones 3 del formulario | No existen los 6 campos "Beneficio destacado" | Pendiente |
| T08 | 4.2 | Mensaje 1 Desktop obligatorio | Dejar Mensaje 1 - Desktop vacío y guardar | El Admin impide guardar y avisa que es obligatorio. Un mensaje con solo espacios o `<p></p>` tampoco guarda | Pendiente |

## 2. Guardado y publicación

| ID | CA | Prueba | Pasos | Resultado esperado | Estado |
|---|---|---|---|---|---|
| T09 | CA05, Caso 1 | Negritas parciales | Mensaje 1 Desktop: "CUPÓN EXCLUSIVO! $1,500 de regalo en compras mayores a $14,999". Negritas solo en "$1,500 de regalo". Guardar | El sitio muestra solo ese fragmento en negritas, igual que en el Admin | Pendiente |
| T10 | CA05 | Fuente, tamaño, cursiva, subrayado | Aplicar cada opción a una parte del mensaje y guardar | El sitio refleja cada opción en el mismo fragmento | Pendiente |
| T11 | CA05 | Color de una parte | Colorear una palabra | La palabra conserva su color. El resto usa el color "Texto de la barra" de Apariencia | Pendiente |
| T12 | CA05 | Alineación | Mensaje de varias líneas o dos párrafos con alineación izquierda, centro y derecha | Cada párrafo se alinea como se capturó. El grupo del mensaje sigue centrado en la barra | Pendiente |
| T13 | CA06, Caso 2 | Emoji en mensaje | Agregar 🎉🔥 al inicio y dentro del mensaje. Guardar y reabrir el banner | El Admin guarda sin error de validación. Al reabrir y en el sitio aparecen en su posición, sin `?` ni caracteres extraños | Pendiente |
| T14 | CA06 | Emoji en CTA | CTA 1 Desktop: "Comprar 🛒". CTA 1 Mobile y Texto CTA general con emoji | Se guardan y se muestran en el botón en Desktop y Mobile | Pendiente |
| T15 | CA06 | Emoji tras reabrir y guardar de nuevo | Reabrir el banner con emojis, guardar sin cambios, revisar el sitio | Los emojis no se pierden ni se duplican | Pendiente |
| T16 | 4.4 | Emoji en el texto del contador | "Termina en: 🔥" en "Texto que acompaña al contador". Guardar y reabrir | Se guarda y se muestra junto al contador | Pendiente |
| T16b | Chat 24/11 | Emojis de 4 bytes | En mensaje y CTA capturar ⚡ ✨ 🔥 🚀 🎁 💥 ❤️ | Todos se guardan y se muestran, no solo ⚡ y ✨ | Pendiente |
| T18b | Chat 24/11 | Tamaño por elemento del contador | Formato de números 24 px, etiquetas 9 px y separadores 20 px. Guardar | Cada elemento cambia de tamaño por separado. Horas, minutos y segundos comparten el formato de su tipo | Pendiente |
| T17 | Caso 3 | CTA personalizado | CTA 1 Desktop con fuente, tamaño, estilo y emoji | Cambia solo el texto. El botón conserva forma, colores de Apariencia y URL | Pendiente |
| T18 | Caso 4, CA03 | Contador con formato | Texto: "Termina en:" en negritas. Formato de números: "00" con fuente, tamaño 20 px y color distinto. Guardar | Texto antes de los números. Horas, minutos y segundos usan el formato. La cuenta regresiva sigue corriendo | Pendiente |
| T19 | Caso 4 | Contador sin formato | Dejar vacíos ambos campos del contador | El contador se ve igual que antes: horas, minutos y segundos separados por `:`, etiquetas HRS/MIN/SEGS | Pendiente |
| T20 | Caso 5 | Mensaje Mobile vacío | Mensaje 1 Mobile vacío y Mensaje 1 Desktop con negritas y color | En mobile se muestra el copy Desktop con su formato | Pendiente |
| T21 | 4.5 | CTA vacío | CTA 1 Desktop vacío con CTA general desactivado | No aparece botón en ese mensaje | Pendiente |
| T22 | 4.5 | CTA general fallback | Activar CTA general con formato y dejar CTA 1 vacío | El botón usa el texto con formato del CTA general y su URL | Pendiente |
| T23 | 4.5 | CTA específico gana | CTA 2 con texto propio y CTA general activo | El mensaje 2 usa su propio texto. Los demás usan el general | Pendiente |

## 3. Regresión (no debe cambiar)

| ID | CA | Prueba | Resultado esperado | Estado |
|---|---|---|---|---|
| R01 | CA08 | URL de CTA internas (`/promocion`) y externas (`https://`) | Los enlaces resuelven igual que antes. Una URL `javascript:` sigue sin generar botón | Pendiente |
| R02 | CA08 | Fechas del contador (inicio y fin) | El contador aparece y desaparece en sus fechas y respeta el rango del banner | Pendiente |
| R03 | CA08 | Colores de Apariencia (fondo, texto, botón, contador) | Se aplican igual que antes a las partes sin color propio | Pendiente |
| R04 | CA08 | Reglas avanzadas (URLs donde se oculta) | El banner se oculta en las rutas configuradas | Pendiente |
| R05 | CA08 | Mostrar CTA general antes del contador | Se conserva el orden configurado | Pendiente |
| R06 | CA08 | Carrusel de hasta 3 mensajes e intervalo configurado | Rota solo, se pausa con el mouse encima. No hay flechas de navegación | Pendiente |
| R07 | CA08 | Cierre del banner (X) | Cierra y no vuelve a aparecer en la sesión | Pendiente |
| R08 | CA08 | Asignación por store view y All Store Views | Cada store muestra su banner. El específico tiene prioridad | Pendiente |
| R09 | CA09 | Diseño, proporciones y botones | Sin textos con formato, la barra se ve igual que antes (alto, CTA a 90°, contador compacto) | Pendiente |
| R10 | - | Banners existentes con texto plano | Se abren en el Admin, se muestran igual en el sitio y se pueden guardar | Pendiente |
| R11 | - | Listado del Admin | Las columnas Content y Button Text muestran texto plano, sin etiquetas HTML | Pendiente |

## 4. Responsive (CA10, CA11, Caso 6)

Probar las 3 barras (A, B y C) con textos con formato y emojis en cada ancho. Resultado esperado en todas: barra completa y legible, sin cortes, encimados ni desbordes horizontales, con las proporciones actuales.

| ID | Dispositivo | Escenario | Estado |
|---|---|---|---|
| D01 | Desktop 1440 px | Mensaje + contador + CTA en una sola línea | Pendiente |
| D02 | Desktop 1440 px | Mensaje largo con negritas y emojis. Si no cabe, el mensaje se ajusta en dos líneas sin desbordar | Pendiente |
| D03 | Tablet 1024 px | Barras A, B y C con formato | Pendiente |
| D04 | Tablet 768 px | Barras A, B y C con formato | Pendiente |
| D05 | Mobile 375 px | Barras A, B y C con formato. CTA centrado debajo del mensaje | Pendiente |
| D06 | Mobile 320 px | Mensaje largo con emojis. El texto se ajusta, el botón no se corta | Pendiente |
| D07 | Todos | Tamaño de letra 72 px en el editor | Se reduce a 32 px y no desborda la barra | Pendiente |
| D08 | Todos | Con contador y sin CTA, con CTA y sin contador, sin ambos | La barra se recentra sin huecos (CA11) | Pendiente |
| D09 | Todos | Tres barras apiladas | El header fijo no queda tapado. El espacio superior de la página se ajusta a la altura real | Pendiente |
| D10 | iOS Safari / Android Chrome | Emojis | Se ven con el estilo propio del sistema, sin cuadros vacíos | Pendiente |

## 5. Seguridad y datos

| ID | Prueba | Resultado esperado | Estado |
|---|---|---|---|
| S01 | Pegar `<script>alert(1)</script>` en un mensaje (con "Código fuente" del editor) | El script no se guarda ni se ejecuta en el sitio | Pendiente |
| S02 | Pegar `<img src=x onerror=alert(1)>` y `<a href="javascript:...">` | Se eliminan. No hay imágenes ni enlaces en el texto | Pendiente |
| S03 | Estilo con `background:url(...)` o `expression(...)` | Se descarta la propiedad | Pendiente |
| S04 | Respuesta de `/leanzote/banner/active` | JSON con HTML ya saneado. Sin atributos `on*` ni `<script>` | Pendiente |
| S05 | Revisar tabla tras `setup:upgrade` | La tabla queda en `utf8mb4_unicode_ci`. Las columnas de CTA son `TEXT`. Existen `counter_text` y `counter_numbers_format`. No se pierden filas | Pendiente |
| S06 | Texto de CTA de más de 255 caracteres con HTML | Se guarda completo, sin truncarse | Pendiente |
| S07 | Beneficio destacado previo | Las columnas `*_highlight_*` siguen en BD y no se muestran. Se informa a ABC que debe recapturar ese texto en el Mensaje antes de liberar | Pendiente |

## 6. Criterios de aceptación y pruebas que los cubren

| CA | Pruebas |
|---|---|
| CA01 | T01, T09, T10, T11, T12 |
| CA02 | T02, T14, T17 |
| CA03 | T03, T18, T19 |
| CA04 | T04 |
| CA05 | T06, T09, T10, T11, T12 |
| CA06 | T13, T14, T15, S05 |
| CA07 | T07, S07 |
| CA08 | T05, T21, T22, T23, R01 a R08 |
| CA09 | R09, T19 |
| CA10 | D01 a D07, D09, D10 |
| CA11 | D08 y todas las pruebas de la sección 4 con las barras A, B y C |

## 7. Decisiones técnicas que QA y ABC deben confirmar

| Tema | Decisión implementada |
|---|---|
| Editor | Editor de texto de Magento (TinyMCE) sin Page Builder |
| Mensaje Mobile vacío | Se usa el copy Desktop con su formato |
| Color del editor vs. Apariencia | El color del editor gana en el fragmento donde se aplicó. El resto usa Apariencia |
| Tamaños grandes | Máximo 32 px. Valores mayores se reducen a 32 px |
| Texto que acompaña al contador | Campo nuevo opcional, antes de los números. Los formatos usan una muestra: "00" para números, "HRS" para etiquetas y ":" para separadores |
| Contador | Números, etiquetas (HRS, MIN, SEGS) y separadores tienen su propio campo de formato (muestra). El texto de las etiquetas sigue fijo |
| Alineación | Se aplica por párrafo. Con una sola línea el mensaje queda centrado en la barra |
