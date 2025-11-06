# Ciudad Virtual - WordPress Site

Sitio web de Ciudad Virtual basado en WordPress + WooCommerce + WCFM.

## 📋 Plugins Custom

### MU-Plugins (Must-Use)

#### `cv-anti-spam-protection.php` (v1.4.0)
**Protección Anti-Spam y Firewall Geográfico**

- ✅ Bloquea registro de usuarios subscriber
- ✅ Firewall geográfico para wp-admin (solo España)
- ✅ Excepción para bots de búsqueda (Google, Bing, etc.)
- ✅ Compatible con "Login as User"
- ✅ Redirige usuarios españoles no autorizados a /shop

**Bots permitidos:**
- Googlebot, Bingbot, Yahoo Slurp
- DuckDuckGo, Baidu, Yandex
- Apple, Facebook, LinkedIn, Twitter
- ChatGPT, Perplexity, WhatsApp

#### `cv-category-search.php` (v3.2.0)
**Modal de Búsqueda de Categorías para WCFM**

- ✅ Modal con búsqueda en tiempo real
- ✅ Búsqueda jerárquica (padre → hijo → nieto)
- ✅ Visualización de categorías seleccionadas
- ✅ Compatible con WCFM productos

#### `cv-yoast-sitemap-config.php.disabled` (v1.0.0)
**Configuración de Yoast SEO Sitemap**

- ⚠️ DESACTIVADO por error crítico
- Personaliza prioridades del sitemap según menú principal
- Mantener para referencia futura

### Plugins Regulares

#### `cv-stats` (v1.3.1)
**Dashboard de Estadísticas**

- ✅ Dashboard centralizado con métricas clave
- ✅ Tarjetas de usuarios, productos, tiendas
- ✅ Gráficos de consultas de contacto
- ✅ Estadísticas de WooCommerce
- ⚠️ Rastreador de búsquedas (desactivado)

**Módulos incluidos:**
- CV Commission Calculator
- CV MLM (Multi-Level Marketing)
- CV Firebase Push Notifications
- CV Wallet Integration
- CV Ticket Capture
- CV Product Filters

## 🔧 Configuraciones

### WordPress Debug Log
```
Ubicación: /home/ciudadvirtual/logs/wordpress-debug.log
Rotación: Diaria (mantiene 1 día)
```

### WP Statistics
```
Búsquedas internas: ✅ Activadas (opción 'pages')
Búsquedas externas: ✅ Rastreando desde Google/Bing
```

### IP2Location Country Blocker
```
Modo: WHITELIST
Permitidos: EU + US
Frontend: ✅ Activado
Skip Bots: ✅ Activado
```

## 🚀 URLs Importantes

- **Admin:** https://ciudadvirtual.app/wp-admin/
- **Shop:** https://ciudadvirtual.app/shop/
- **Sitemap:** https://ciudadvirtual.app/sitemap_index.xml
- **WP Statistics:** wp-admin/admin.php?page=wps_overview_page
- **CV Stats:** wp-admin/admin.php?page=cv-stats

## 📊 Estadísticas

### WP Statistics - Ver Búsquedas

**Búsquedas desde Google/Bing:**
```
WP Statistics → Referrals → Tab "Search Engines"
URL: wp-admin/admin.php?page=wps_referrals_page&tab=search-engines
```

**Búsquedas internas (en el sitio):**
```
WP Statistics → Top Pages → Buscar "/?s="
URL: wp-admin/admin.php?page=wps_pages_page
```

## 🛡️ Seguridad

### Firewall Geográfico
- Bloquea wp-admin desde fuera de España
- Permite bots de búsqueda globalmente
- Redirige usuarios ES no autorizados a /shop

### Anti-Spam
- Bloquea registro de subscribers
- Permite customers y vendors

## 📝 Historial de Cambios

### 2025-11-06
- ✅ Añadida excepción para bots de búsqueda en firewall
- ✅ Activadas búsquedas internas en WP Statistics
- ⚠️ Desactivado plugin de Yoast SEO sitemap (error crítico)
- ✅ Configurado debug log en ubicación privada

## 🔗 Repositorio Git

```bash
# Ver estado
git status

# Ver historial
git log --oneline --graph

# Ver cambios
git diff
```

## 📧 Contacto

**Admin:** admin@ciudadvirtual.app  
**Site:** https://ciudadvirtual.app

