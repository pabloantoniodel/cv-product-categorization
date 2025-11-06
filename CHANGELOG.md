# Changelog

Todos los cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/).

---

## [2025-11-06] - Configuración inicial y correcciones críticas

### Añadido
- ✅ Repositorio Git inicializado
- ✅ `.gitignore` configurado para WordPress
- ✅ Plugin `cv-category-search.php` v3.2.0
  - Modal de búsqueda de categorías para WCFM
  - Búsqueda jerárquica en tiempo real
  - Visualización de categorías seleccionadas
- ✅ Plugin `cv-stats` v1.3.1
  - Dashboard centralizado de estadísticas
  - Módulos de comisiones MLM
  - Push notifications con Firebase
  - Rastreador de tickets y consultas
- ✅ Plugin `cv-yoast-sitemap-config.php` v1.0.0
  - Personalización de prioridades del sitemap
  - **DESACTIVADO** por error crítico

### Corregido
- 🐛 **CRÍTICO:** Firewall geográfico bloqueaba bots de búsqueda
  - Googlebot, Bingbot y otros ahora permitidos
  - Causaba 0 visitas desde motores de búsqueda
  - Se mantiene protección geográfica para usuarios
- 🐛 WP Statistics no mostraba búsquedas internas
  - Activada opción `pages` en configuración
  - Búsquedas ahora visibles en Top Pages
- 🐛 Yoast SEO sitemap causaba error crítico
  - Plugin desactivado temporalmente
  - Sitemap predeterminado funcionando

### Cambiado
- 🔧 `cv-anti-spam-protection.php` actualizado a v1.4.0
  - Añadida función `is_search_engine_bot()`
  - Lista de 17 bots de búsqueda permitidos
  - Mantiene protección para wp-admin desde fuera de España
- 🔧 Debug log movido a ubicación privada
  - Antes: `wp-content/debug.log` (público)
  - Ahora: `/home/ciudadvirtual/logs/wordpress-debug.log` (privado)
  - Rotación diaria configurada

### Seguridad
- 🔒 Firewall geográfico activo
  - Bloquea wp-admin desde fuera de España
  - Permite bots de búsqueda globalmente
  - Redirige usuarios ES no autorizados a /shop
- 🔒 IP2Location Country Blocker
  - Modo WHITELIST (solo EU + US)
  - 4 IPs bloqueadas hoy
- 🔒 Anti-spam protection
  - Subscribers bloqueados
  - Customers y vendors permitidos

---

## Commits

### 56e2579 - docs: Añadir documentación completa del proyecto
- README.md con toda la información del proyecto
- Guías de uso y configuración
- URLs importantes

### 820ed94 - feat: Plugin de configuración de Yoast SEO (desactivado)
- Personalización de sitemap (desactivado)
- Mantener para referencia futura

### 15420d1 - feat: Plugin CV Stats para dashboard de estadísticas
- 87 archivos añadidos
- Sistema completo de comisiones MLM
- Dashboard de estadísticas
- Notificaciones push

### 195e53a - feat: Modal de búsqueda de categorías para WCFM
- Búsqueda jerárquica de categorías
- Compatible con WCFM productos

### b4ef287 - fix: Añadir excepción para bots de búsqueda en firewall geográfico
- **CORRECCIÓN CRÍTICA**
- Permite Googlebot, Bingbot, etc.
- Mantiene protección geográfica

### 78afcb8 - chore: Añadir .gitignore para WordPress
- Configuración inicial de Git
- Exclusión de archivos de WordPress core

---

## Próximos pasos

### Por hacer
- [ ] Verificar tráfico de buscadores en 24-48 horas
- [ ] Investigar error de Yoast SEO sitemap
- [ ] Reactivar plugin de sitemap cuando esté corregido
- [ ] Considerar reactivar CV Search Referral Tracker

### En investigación
- ⏳ Yoast SEO sitemap - Error crítico al personalizar prioridades
- ⏳ Tráfico de buscadores - Esperando rastreo (24-48h)

---

## Notas técnicas

### Firewall - Bots permitidos
```
googlebot, bingbot, slurp (Yahoo), duckduckbot,
baiduspider, yandexbot, sogou, exabot, ia_archiver,
msnbot, applebot, facebookexternalhit, linkedinbot,
twitterbot, whatsapp, gptbot, perplexity
```

### WP Statistics - Ver búsquedas
**Desde motores externos:**
```
wp-admin/admin.php?page=wps_referrals_page&tab=search-engines
```

**Internas (en el sitio):**
```
wp-admin/admin.php?page=wps_pages_page
Filtrar por: /?s=
```

### Debug Log
**Ubicación:** `/home/ciudadvirtual/logs/wordpress-debug.log`  
**Rotación:** Diaria (mantiene 1 día)  
**Configurado en:** `wp-config.php`

---

**Última actualización:** 2025-11-06

