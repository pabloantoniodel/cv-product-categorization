# Análisis de Campos WCFM - Descripción de Tienda

## Objetivo
Identificar qué campos se usan para la descripción en las tarjetas digitales

## Usuario 9 (ciudadvirtual.net - Inmobiliaria)
- **Nombre**: Asociación de Propietarios
- **Descripción visible**: "Venta y alquileres de viviendas y locales, trabajamos con financieras y alquiler seguro."
- **Videos**: XQm4faYxZgg, udXEQqs-C3g

## Usuario 290 (franciscosp71@gmail.com - Francisco)
- **Nombre**: Francisco Sanchez
- **Descripción visible**: "¡Hola! Soy Francisco, una persona apasionada y siempre curiosa..." (texto largo con proyectos)
- **Videos**: d1MikwjnpCc, X54xM3nnb2o

## Campos WCFM Relevantes Encontrados

### Campos de Descripción:
1. **`_store_description`** ← CAMPO PRINCIPAL (contiene la descripción larga HTML)
2. **`description`** (descripción de WordPress, puede estar vacío o con otros datos)
3. **`wcfmmp_profile_settings`** (array serializado con múltiples campos)

### Campos de Videos:
1. **`video-youtube-1`**
2. **`video-youtube-2`**
3. **`youtube`** (canal de YouTube)
4. **`wcfmvm_custom_infos`** (array que contiene video-youtube-1 y video-youtube-2)

### Campos de Ubicación:
1. **`_wcfm_street_1`**
2. **`_wcfm_street_2`**
3. **`_wcfm_city`**
4. **`_wcfm_country`**
5. **`_wcfm_zip`**
6. **`_wcfm_store_location`** (texto completo de dirección)
7. **`_wcfm_store_lat`** (latitud)
8. **`_wcfm_store_lng`** (longitud)

### Campos de Configuración:
1. **`wcfm_policy_vendor_options`** (políticas)
2. **`wcfm_seo_vendor_options`** (SEO)
3. **`wcfm_vendor_store_hours`** (horarios)
4. **`wcfmmp_profile_settings`** (configuración completa del perfil)
5. **`wcfmvm_custom_infos`** (información personalizada)
6. **`wcfmvm_static_infos`** (información estática)

## Estado Actual de la Copia

### Campos que SÍ se copian actualmente (15 campos):
✅ `description`
✅ `_store_description` (AGREGADO RECIENTEMENTE)
✅ `_wcfm_street_1`
✅ `_wcfm_street_2`
✅ `_wcfm_city`
✅ `_wcfm_country`
✅ `_wcfm_zip`
✅ `wcfmvm_custom_infos`
✅ `pagina-web`
✅ `video-youtube-1`
✅ `video-youtube-2`
✅ `link-a-videoconferencia`
✅ `imagen-superior-tarjeta`
✅ `texto-saludo-envio-tarjeta`
✅ `youtube`

### Campos que NO se copian (pero podrían ser útiles):
❌ `_wcfm_store_location` (ubicación completa)
❌ `_wcfm_store_lat` (latitud)
❌ `_wcfm_store_lng` (longitud)
❌ `wcfmmp_profile_settings` (configuración de perfil completo)
❌ `wcfmvm_static_infos` (información estática)
❌ `wcfm_policy_vendor_options` (políticas de la tienda)

## Conclusión

El campo **`_store_description`** es el campo PRINCIPAL que contiene la descripción larga de la tienda que se muestra en la tarjeta digital.

**Estado**: ✅ Ya se agregó a la lista de campos a copiar

**Próximos pasos**: 
- Verificar que el campo se copia correctamente en registros reales
- Confirmar que la descripción se muestra en la tarjeta del nuevo usuario


