# 🔒 Flujo del Firewall Geográfico

## Diagrama de Decisión

```
┌─────────────────────────────────────┐
│  Usuario intenta acceder a:        │
│  • /wp-admin/                       │
│  • /wp-login.php                    │
│  • URL con ?reauth=1                │
└──────────────┬──────────────────────┘
               │
               ▼
    ┌──────────────────────┐
    │ ¿Es admin logueado?  │
    └──────────┬───────────┘
               │
         ┌─────┴─────┐
         │           │
        SÍ          NO
         │           │
         ▼           ▼
    ┌────────┐   ┌──────────────┐
    │ ACCESO │   │ Detectar país│
    │  OK ✅  │   │   por IP     │
    └────────┘   └──────┬───────┘
                        │
                  ┌─────┴─────┐
                  │           │
               ESPAÑA      EXTRANJERO
                  │           │
                  ▼           ▼
         ┌────────────┐  ┌────────────┐
         │ REDIRIGIR  │  │  BLOQUEAR  │
         │ a /shop 🔄 │  │  (403) 🚫  │
         └────────────┘  └────────────┘
```

---

## 📊 Tabla de Acciones

| Origen | Usuario | Acción | Código HTTP | Log |
|--------|---------|--------|-------------|-----|
| 🇪🇸 España | Admin logueado | ✅ Permitir acceso | 200 | - |
| 🇪🇸 España | Admin usando "Login as User" | ✅ Permitir acceso | 200 | - |
| 🇪🇸 España | Sin login | 🔄 Redirigir a /shop | 302 | `REDIRIGIDO A SHOP` |
| 🌍 Extranjero | Admin logueado | ✅ Permitir acceso | 200 | - |
| 🌍 Extranjero | Admin usando "Login as User" | ✅ Permitir acceso | 200 | - |
| 🌍 Extranjero | Sin login | 🚫 Bloquear | 403 | `ACCESO BLOQUEADO` |
| 🏠 Localhost | Cualquiera | ✅ Permitir acceso | 200 | - |

---

## 🎯 Ejemplos Prácticos

### Escenario 1: Bot desde China
```
IP: 123.45.67.89
País: CN (China)
URL: /wp-admin/?reauth=1
Estado: NO logueado

➜ RESULTADO: 🚫 BLOQUEADO (403)
➜ LOG: "[CV Firewall] 🚫 ACCESO BLOQUEADO | IP: 123.45.67.89 | País: CN"
```

### Escenario 2: Usuario español sin login
```
IP: 88.26.227.134
País: ES (España)
URL: /wp-admin/
Estado: NO logueado

➜ RESULTADO: 🔄 REDIRIGIDO a https://ciudadvirtual.app/shop
➜ LOG: "[CV Firewall] 🔄 REDIRIGIDO A SHOP | IP: 88.26.227.134 | País: ES"
```

### Escenario 3: Admin desde Francia
```
IP: 195.154.123.45
País: FR (Francia)
URL: /wp-admin/
Estado: Logueado como admin

➜ RESULTADO: ✅ ACCESO PERMITIDO
➜ LOG: (sin registro, acceso normal)
```

### Escenario 4: Usuario español logueado como cliente
```
IP: 88.26.227.134
País: ES (España)
URL: /wp-admin/
Estado: Logueado como "customer" (no admin)

➜ RESULTADO: 🔄 REDIRIGIDO a /shop
➜ LOG: "[CV Firewall] 🔄 REDIRIGIDO A SHOP | IP: 88.26.227.134 | País: ES"
```

### Escenario 5: Admin usando "Login as User"
```
IP: 195.154.123.45
País: FR (Francia)
URL: /wp-admin/
Estado: Admin logueado como "laura_montero87@hotmail.com" (customer)
Botón visible: "← Volver Administrador"

➜ RESULTADO: ✅ ACCESO PERMITIDO
➜ LOG: (sin registro, acceso permitido por excepción "Login as User")
➜ MOTIVO: El admin original necesita acceder a wp-admin para volver a su cuenta
```

---

## 🔍 Detección de País

### Métodos (en orden de prioridad):

1. **IP2Location Database Local** (más rápido)
   - Archivo: `/wp-content/uploads/ip2location/IP2LOCATION-LITE-DB1.BIN`
   - Si existe: respuesta instantánea
   - Si no existe: pasa al método 2

2. **API ip-api.com** (fallback)
   - Límite: 45 peticiones/minuto
   - Cache: 1 hora por IP
   - Gratuito, sin clave API

3. **Fallback de seguridad**
   - Si ambos fallan: **NO bloquea** (asume ES)
   - Previene bloqueos accidentales

---

## 🔑 Permisos Necesarios

Para acceder a wp-admin necesitas:

```php
is_user_logged_in() && current_user_can('manage_options')
```

**Roles con `manage_options`:**
- ✅ `administrator`

**Roles SIN `manage_options` (bloqueados):**
- ❌ `customer` → Redirigidos a /shop
- ❌ `subscriber` → Redirigidos a /shop
- ❌ `dc_vendor` → Redirigidos a /shop
- ❌ `shop_manager` → Redirigidos a /shop (a menos que tengan el capability)

---

## 🌐 Detección de IP Real

El sistema detecta la IP correcta incluso detrás de:

```
1. Cloudflare    → HTTP_CF_CONNECTING_IP
2. Nginx Proxy   → HTTP_X_REAL_IP
3. Load Balancer → HTTP_X_FORWARDED_FOR
4. Directo       → REMOTE_ADDR
```

### Ejemplo con Cloudflare:
```
HTTP_CF_CONNECTING_IP: 88.26.227.134 (IP real del usuario)
HTTP_X_FORWARDED_FOR: 88.26.227.134, 104.21.48.22 (chain)
REMOTE_ADDR: 104.21.48.22 (IP de Cloudflare)

➜ SE USA: 88.26.227.134 (primera IP válida detectada)
```

---

## ⚡ Rendimiento

### Cache de Geolocalización:
- **Duración:** 1 hora por IP
- **Storage:** WordPress transients
- **Clave:** `cv_geoip_[md5_de_ip]`

### Ejemplo:
```
Primera visita de 88.26.227.134:
  1. Consultar API (2 segundos)
  2. Guardar en cache
  3. Total: 2 segundos

Siguientes visitas (próxima hora):
  1. Leer desde cache
  2. Total: 0.001 segundos (1000x más rápido)
```

---

## 📝 Logs Generados

### Log de bloqueo (extranjero):
```
[03-Nov-2025 17:30:45 UTC] [CV Firewall] 🚫 ACCESO BLOQUEADO | IP: 123.45.67.89 | País: CN | URI: /wp-admin/?reauth=1 | User-Agent: Mozilla/5.0 (Windows NT 10.0) Bot/1.0
```

### Log de redirección (España sin login):
```
[03-Nov-2025 17:31:20 UTC] [CV Firewall] 🔄 REDIRIGIDO A SHOP | IP: 88.26.227.134 | País: ES | URI: /wp-admin/
```

### Ver logs filtrados:
```bash
# Solo bloqueados
tail -f wp-content/debug.log | grep "BLOQUEADO"

# Solo redirigidos
tail -f wp-content/debug.log | grep "REDIRIGIDO"

# Todos
tail -f wp-content/debug.log | grep "CV Firewall"
```

---

## 🎨 Páginas que Verá el Usuario

### Desde país extranjero:
```html
🚫 Acceso Denegado

El acceso al panel de administración está restringido geográficamente.

País detectado: CN
IP: 123.45.67.89

Si eres administrador legítimo, contacta con soporte técnico.

HTTP 403 Forbidden
```

### Desde España sin login:
```
HTTP 302 Redirect
Location: https://ciudadvirtual.app/shop

(El usuario es redirigido automáticamente a la tienda)
```

---

## 🔧 Configuración Avanzada

### Añadir países permitidos:
```php
// Archivo: cv-anti-spam-protection.php (línea 18)
private $allowed_countries = array(
    'ES', // España
    'PT', // Portugal
    'FR', // Francia
);
```

### Cambiar página de redirección:
```php
// Archivo: cv-anti-spam-protection.php (línea 74)
wp_redirect(home_url('/shop'));  // Cambiar '/shop' por la URL deseada
```

---

**Última actualización:** 3 de noviembre de 2025  
**Versión:** 1.2.0

