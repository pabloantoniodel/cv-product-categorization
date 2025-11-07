# 🔒 CV Firewall Geográfico - Documentación

## Descripción

Sistema de protección de seguridad para WordPress que incluye:

1. **Firewall Geográfico**: Bloquea accesos a `wp-admin` desde fuera de España
2. **Anti-Spam**: Bloquea registro automático de usuarios spam (subscribers)
3. **Protección CAPTCHA**: Añade protección adicional en formularios

---

## 🌍 Firewall Geográfico

### ¿Qué protege?

- **wp-admin** (Panel de administración)
- **wp-login.php** (Página de login)
- **URLs con `?reauth=1`** (Intentos de reautenticación)

### Comportamiento por país:

- 🚫 **Desde fuera de España**: BLOQUEADO con código 403
- 🔄 **Desde España (sin login admin)**: REDIRIGIDO a `/shop`
- ✅ **Administradores logueados**: PERMITIDO (cualquier país)

### ¿Qué NO bloquea?

- ✅ Administradores ya logueados (siempre permitidos)
- ✅ Administradores usando "Login as User" (pueden volver a su cuenta)
- ✅ Peticiones AJAX del frontend (`admin-ajax.php`)
- ✅ IPs locales (desarrollo en localhost)

### Países Permitidos

Por defecto, solo se permite acceso desde:
- 🇪🇸 **España (ES)**

Para añadir más países, edita el archivo y modifica:
```php
private $allowed_countries = array('ES', 'PT', 'FR'); // Ejemplo: España, Portugal, Francia
```

---

## 🔑 Excepción: Plugin "Login as User"

El firewall detecta automáticamente cuando un administrador está usando el plugin **"Login as User"** para loguearse como otro usuario.

### ¿Por qué esta excepción?

Cuando un admin usa "Login as User" para ver la experiencia de un cliente:
1. Se loguea como el cliente (ej: `laura_montero87@hotmail.com`)
2. El sistema detecta que NO es admin (porque está usando la cuenta del cliente)
3. **SIN LA EXCEPCIÓN**: Lo redirigiría a `/shop` y no podría volver
4. **CON LA EXCEPCIÓN**: Puede acceder a `wp-admin` para hacer clic en "← Volver Administrador"

### ¿Cómo detecta el firewall esta situación?

El sistema verifica **múltiples indicadores**:

1. **URL con acción de volver:**
   ```
   /wp-login.php?action=login_as_olduser&_wpnonce=xxxxx
   ```

2. **Función del plugin activa:**
   ```php
   login_as_user_get_olduser_id() // Retorna ID del admin original
   ```

3. **Cookie del plugin:**
   ```
   login_as_user_olduser_id = [ID del admin original]
   ```

4. **Meta del usuario:**
   ```php
   get_user_meta($user_id, '_login_as_user_switched', true)
   ```

### Ejemplo visual del botón protegido:

```html
<div class="login-as-user-content">
    <span class="cv-login-email">laura_montero87@hotmail.com</span>
    <a class="button" href="/wp-login.php?action=login_as_olduser">
        ← Volver Administrador
    </a>
</div>
```

**Comportamiento:**
- Si este div está presente en la página → Acceso a wp-admin PERMITIDO
- El admin puede hacer clic en "Volver Administrador" sin problemas
- Una vez vuelve a su cuenta de admin, funciona normalmente

---

## 🔍 Detección de País

El sistema usa **doble método** para detectar el país:

1. **IP2Location Plugin** (si está instalado)
   - Base de datos local: `/wp-content/uploads/ip2location/IP2LOCATION-LITE-DB1.BIN`
   - Respuesta instantánea

2. **API ip-api.com** (fallback gratuito)
   - Límite: 45 peticiones/minuto
   - Cache de 1 hora por IP

### Obtención de IP Real

El sistema detecta correctamente la IP incluso detrás de:
- ☁️ **Cloudflare** (`HTTP_CF_CONNECTING_IP`)
- 🔄 **Proxies/Load Balancers** (`HTTP_X_FORWARDED_FOR`)
- 🌐 **Nginx Reverse Proxy** (`HTTP_X_REAL_IP`)

---

## 📋 Registro de Actividad

Todos los intentos bloqueados se registran en el log de WordPress:

```
[CV Firewall] 🚫 ACCESO BLOQUEADO | IP: 123.45.67.89 | País: CN | URI: /wp-admin/ | User-Agent: Mozilla/5.0...
```

### Ver logs en tiempo real

```bash
tail -f /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/debug.log | grep "CV Firewall"
```

---

## 🛠️ Comandos WP-CLI

### 1. Ver intentos de acceso bloqueados y redirigidos

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store

# Ver todos los eventos (bloqueados + redirigidos)
wp cv-firewall logs

# Ver últimos 50 eventos
wp cv-firewall logs --lines=50

# Ver solo bloqueados (países extranjeros)
wp cv-firewall logs --type=blocked

# Ver solo redirigidos (España sin login)
wp cv-firewall logs --type=redirect
```

### 2. Verificar país de una IP

```bash
# Verificar si una IP específica sería bloqueada
wp cv-firewall check-ip 8.8.8.8
wp cv-firewall check-ip 123.45.67.89
```

**Ejemplo de salida:**
```
Verificando IP: 8.8.8.8...

País: United States (US)
Ciudad: Mountain View
ISP: Google LLC
Warning: ✗ Esta IP sería BLOQUEADA (no es de España)
```

### 3. Limpiar caché de geolocalización

```bash
# Si necesitas forzar nueva detección de países
wp cv-firewall clear-cache
```

---

## 🧪 Pruebas

### Probar el firewall

1. **Desde España (debe permitir):**
   ```bash
   curl -I https://ciudadvirtual.app/wp-admin/
   # Debería responder 200 o 302 (redirect a login)
   ```

2. **Simulando IP extranjera:**
   ```bash
   # Usar un proxy/VPN de otro país
   # O modificar temporalmente el código para probar
   ```

3. **Ver logs:**
   ```bash
   wp cv-firewall logs --lines=10
   ```

---

## ⚙️ Configuración Avanzada

### Añadir más países permitidos

Edita `/wp-content/mu-plugins/cv-anti-spam-protection.php`:

```php
class CV_Geographic_Firewall {
    
    // Añadir más códigos de país ISO
    private $allowed_countries = array(
        'ES', // España
        'PT', // Portugal
        'FR', // Francia
        'IT', // Italia
    );
```

### Desactivar temporalmente el firewall

**Método 1: Comentar la línea de inicialización**
```php
// new CV_Geographic_Firewall(); // DESACTIVADO TEMPORALMENTE
```

**Método 2: Renombrar el archivo**
```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins
mv cv-anti-spam-protection.php cv-anti-spam-protection.php.disabled
```

---

## 🚨 Casos de Emergencia

### Si te bloqueas accidentalmente

**Opción 1: Desactivar vía SSH**
```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins
mv cv-anti-spam-protection.php cv-anti-spam-protection.php.disabled
```

**Opción 2: Whitelist tu IP en .htaccess** (antes del firewall PHP)
```apache
# Whitelist IP específica
<If "%{REMOTE_ADDR} != 'TU_IP_AQUI'">
    # Aplicar restricciones
</If>
```

**Opción 3: Añadir tu país al array de países permitidos**

---

## 📊 Estadísticas

### Consultar intentos bloqueados por país

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp cv-firewall logs --lines=100 | grep -oP 'País: \K\w+' | sort | uniq -c | sort -rn
```

**Salida ejemplo:**
```
     45 CN
     23 RU
     12 US
      8 IN
      3 BR
```

---

## 🔐 Seguridad Adicional

### Recomendaciones complementarias

1. **Limitar intentos de login** (plugin: Limit Login Attempts)
2. **Usar Cloudflare** (protección DDoS y firewall adicional)
3. **Actualizar base de datos IP2Location** mensualmente
4. **Revisar logs semanalmente**: `wp cv-firewall logs --lines=100`

---

## 📝 Notas Importantes

- ⚠️ **No se bloquean admins logueados**: Si ya estás dentro, puedes trabajar desde cualquier país
- ⚠️ **IPs locales permitidas**: localhost y redes privadas siempre funcionan
- ⚠️ **Fallback seguro**: Si falla la detección de país, **NO bloquea** (evita bloqueos accidentales)
- ⚠️ **Cache de 1 hora**: Cada IP se consulta una vez por hora (optimización)

---

## 📞 Soporte

Si tienes problemas o dudas:

1. **Ver logs**: `wp cv-firewall logs`
2. **Verificar tu IP**: `curl ifconfig.me` (desde tu máquina)
3. **Revisar país de tu IP**: `wp cv-firewall check-ip TU_IP`
4. **Contactar soporte**: soporte@ciudadvirtual.app

---

## 📜 Changelog

### v1.1.0 (2025-01-11)
- ✅ Añadido firewall geográfico para wp-admin
- ✅ Protección específica contra `?reauth=1`
- ✅ Comandos WP-CLI para monitoreo
- ✅ Detección de IP real (Cloudflare, proxies)
- ✅ Cache de geolocalización
- ✅ Logs detallados de intentos bloqueados

### v1.0.0 (2025-01-10)
- ✅ Protección anti-spam de registro de usuarios
- ✅ Bloqueo automático de subscribers
- ✅ Integración con CAPTCHA

---

**Última actualización:** 11 de enero de 2025  
**Autor:** Ciudad Virtual  
**Versión:** 1.1.0

