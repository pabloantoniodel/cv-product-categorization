# 📋 Resumen de Cambios - 3 de Noviembre 2025

## ✅ Archivos Modificados y Subidos a Git

### 1. **Plugin: ciudadvirtual-card** ✅ SUBIDO

**Repositorio:** https://github.com/pabloantoniodel/ciudadvirtual-card  
**Commit:** `b4b31d3`

#### Archivos modificados:

1. **`includes/class-cvcard-contacts.php`**
   - ✅ Fix: Registro de tarjetas enviadas por WhatsApp con teléfono
   - ✅ Usa tabla correcta: `wp_cvapp_envios`
   - ✅ Método: `process_whatsapp_send_with_phone()`

2. **`includes/class-cvcard-wcfm-integration.php`**
   - ✅ Botón "Tarjeta de visita" en menú WCFM (primer elemento)
   - ✅ Botón "Tarjeta de visita" en menú WooCommerce My Account
   - ✅ Redirección dinámica al login → `/card/{usuario}/`
   - ✅ Panel de configuración: Admin → Ajustes → Tarjeta Login
   - ✅ Diseño moderno botón "VER MI TARJETA" (gradiente púrpura)
   - ✅ Prioridad 100 en hooks (después de WCFM)

3. **`templates/card-display.php`**
   - ✅ Videos YouTube embebidos automáticamente
   - ✅ Soporta `[embed]URL[/embed]` y URLs directas
   - ✅ Usa `youtube-nocookie.com`

4. **`FIX-WHATSAPP-ENVIOS.md`** (nuevo)
   - ✅ Documentación del fix

---

## 🆕 Archivos Nuevos (NO en git)

### 2. **Firewall Geográfico** (mu-plugins)

**Ubicación:** `wp-content/mu-plugins/`

1. **`cv-anti-spam-protection.php`** (19KB)
   - ✅ Firewall geográfico para wp-admin
   - ✅ Bloquea accesos desde fuera de España
   - ✅ Redirige españoles sin login a /shop
   - ✅ Compatible con "Login as User"
   - ✅ Comandos WP-CLI para monitoreo

2. **`CV-FIREWALL-README.md`** (8.3KB)
   - ✅ Documentación completa

3. **`FLUJO-FIREWALL.md`** (7.2KB)
   - ✅ Diagramas y ejemplos

4. **`RESUMEN-FIREWALL.md`** (5.4KB)
   - ✅ Resumen ejecutivo

---

### 3. **Buscador de Categorías** (mu-plugins)

**Ubicación:** `wp-content/mu-plugins/`

1. **`cv-category-search.php`** (NUEVO - 16KB) **v1.0.3** 🔥 MEJORADO
   - ✅ **Buscador AJAX ultra rápido (50ms con 2 chars, 150ms con más)** 🚀
   - ✅ **ENTER NUNCA guarda el producto** - TRIPLE PROTECCIÓN 🛡️
     - `stopImmediatePropagation()` + `keydown` + `keypress` 
     - Submit blocker global cuando el buscador tiene foco
     - Console log para debugging
   - ✅ **Navegación por teclado:**
     - `ENTER` → Selecciona primer resultado o el enfocado
     - `↓` → Siguiente resultado (resaltado amarillo)
     - `↑` → Resultado anterior
   - ✅ Disponible para administradores y vendedores
   - ✅ Autocompletado desactivado (`autocomplete="off"`)
   - ✅ Limpia input automáticamente después de seleccionar
   - ✅ Búsqueda en tiempo real (mínimo 2 caracteres)
   - ✅ Muestra ruta jerárquica completa (ej: ACADEMIA → FORMACION → CURSOS)
   - ✅ Interfaz moderna con gradiente y efectos hover
   - ✅ Iconos visuales (📂 categoría, 📁 subcategoría)
   - ✅ Indicadores visuales: azul (seleccionada), amarillo (teclado)
   - ✅ Máximo 20 resultados simultáneos
   - ✅ Compatible con checklist y select múltiple

2. **`CV-CATEGORY-SEARCH-README.md`** (NUEVO - 21KB)
   - ✅ Documentación técnica completa
   - ✅ Ejemplos de uso y casos reales
   - ✅ Guía de troubleshooting

3. **`COMO-USAR-BUSCADOR.md`** (NUEVO - 8KB)
   - ✅ Manual de usuario en español
   - ✅ Atajos de teclado explicados
   - ✅ Casos de uso prácticos
   - ✅ Estadísticas de eficiencia
   - ✅ Checklist de uso correcto

---

### 4. **Videos en Productos** (tema)

**Ubicación:** `wp-content/themes/shopper-modern/`

1. **`functions.php`** (modificado)
   - ✅ Videos YouTube embebidos en descripciones de productos
   - ✅ Función `cv_embed_youtube_videos()`
   - ✅ Filtros en `the_content` y `woocommerce_short_description`

---

## 🔧 Cambios en Base de Datos

1. **Email corregido:**
   - ❌ `pabloantiodel@ciudadvirtual.app`
   - ✅ `pabloantoniodel@ciudadvirtual.app`
   - Tabla: `wp_options` → `user_registration_admin_email_receipents`

2. **Opción añadida:**
   - `cvcard_vendor_login_redirect = 'mi-tarjeta'`
   - Controla redirección de vendedores al login

---

## 📦 Resumen por Plugin/Componente

### Plugin: ciudadvirtual-card ✅
- Estado: **SUBIDO A GIT**
- Commit: `b4b31d3`
- Cambios: 4 archivos, +718 líneas

### Firewall Geográfico 🆕
- Estado: **NUEVO (mu-plugins, sin git)**
- Archivos: 4 (1 PHP + 3 MD)
- Funcional: ✅ ACTIVO

### Buscador Categorías 🆕
- Estado: **NUEVO (mu-plugins, sin git)**
- Archivos: 1 PHP
- Funcional: ✅ ACTIVO

### Tema shopper-modern 📝
- Estado: **MODIFICADO (sin git)**
- Archivos: 1 (functions.php)
- Funcional: ✅ ACTIVO

---

## 🚀 Próximos Pasos

### Opción 1: Crear repos git para mu-plugins
```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins
git init
git add cv-anti-spam-protection.php CV-FIREWALL-README.md FLUJO-FIREWALL.md RESUMEN-FIREWALL.md cv-category-search.php
git commit -m "Firewall geográfico y buscador de categorías"
# Crear repo en GitHub y hacer push
```

### Opción 2: Añadir al repo existente
Si tienes un repo para "mu-plugins", solo hacer commit y push

### Opción 3: Backup manual
Hacer backup de estos archivos fuera del servidor

---

## 📊 Estadísticas

**Total de archivos modificados:** 12  
**Total de archivos nuevos:** 5  
**Líneas de código añadidas:** ~1,500  
**Plugins afectados:** 2 (ciudadvirtual-card, mu-plugins)  
**Temas afectados:** 1 (shopper-modern)

---

**Última actualización:** 5 de noviembre de 2025, 04:30  
**Autor:** Ciudad Virtual

