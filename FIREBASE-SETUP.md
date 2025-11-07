# 🔥 Configuración de Firebase Cloud Messaging

## ✅ Firebase ya está integrado

Tu proyecto: **ciudadvirtual-48edd**

## 📋 Pasos para activar las notificaciones push

### 1️⃣ Obtener Server Key

1. Ve a: https://console.firebase.google.com/project/ciudadvirtual-48edd/settings/cloudmessaging
2. En la sección **Cloud Messaging API (Legacy)**
3. Copia el **Server Key** (empieza con `AAAA...`)

### 2️⃣ Obtener VAPID Key (Web Push certificate)

En la misma página:
1. Baja hasta **Web Push certificates**
2. Si no hay ninguno, haz click en **Generate key pair**
3. Copia el **Key pair** generado

### 3️⃣ Configurar en WordPress

Ejecuta estos comandos:

```bash
# Configurar Server Key
wp option update cv_firebase_server_key "TU_SERVER_KEY_AQUI" --allow-root

# Configurar VAPID Key
wp option update cv_firebase_vapid_key "TU_VAPID_KEY_AQUI" --allow-root
```

O desde PHP (wp-admin > Herramientas > Salud del sitio > Info):

```php
update_option('cv_firebase_server_key', 'TU_SERVER_KEY_AQUI');
update_option('cv_firebase_vapid_key', 'TU_VAPID_KEY_AQUI');
```

### 4️⃣ Verificar Service Worker

El archivo `firebase-messaging-sw.js` DEBE estar en la raíz:
- ✅ Ya está en: `/home/ciudadvirtual/htdocs/ciudadvirtual.store/firebase-messaging-sw.js`
- ✅ Accesible en: https://ciudadvirtual.app/firebase-messaging-sw.js

### 5️⃣ Activar Cloud Messaging API

1. Ve a: https://console.firebase.google.com/project/ciudadvirtual-48edd/settings/cloudmessaging
2. Si dice "Cloud Messaging API (Legacy) is deprecated", haz click en el enlace
3. Activa **Cloud Messaging API** en Google Cloud Console

## 🧪 Probar las notificaciones

1. **Loguéate como vendedor** en WCFM
2. **Permite las notificaciones** cuando Chrome te pregunte
3. **Cierra el navegador completamente**
4. **Envía un ticket** a ese vendedor desde otro dispositivo
5. **Deberías recibir** una notificación del sistema incluso con Chrome cerrado

## 📊 Cómo funciona

### Notificaciones normales (actuales):
- ❌ Solo funcionan con navegador abierto
- ❌ Solo si estás en la pestaña
- ✅ No requieren configuración

### Notificaciones Firebase (nuevas):
- ✅ Funcionan con navegador cerrado
- ✅ Funcionan en background
- ✅ Funcionan en cualquier pestaña
- ✅ Notificaciones del sistema operativo
- ⚙️ Requieren Server Key y VAPID Key

## 🔍 Debug

Ver logs en consola del navegador:
```
🔥 Firebase Push: Inicializando...
✅ Firebase Push: Firebase inicializado
🔔 Firebase: Solicitando permiso...
✅ Firebase: Token FCM obtenido
💾 Firebase: Guardando token...
```

Ver logs en PHP:
```bash
tail -f /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/debug.log | grep Firebase
```

## 🆘 Solución de problemas

### No recibo notificaciones
1. Verifica Server Key y VAPID Key configurados
2. Verifica que Firebase Cloud Messaging API esté activa
3. Verifica permisos de notificaciones en el navegador
4. Verifica que `firebase-messaging-sw.js` sea accesible

### Error al obtener token
1. Verifica VAPID Key
2. Verifica que el dominio coincida con Firebase
3. Revisa la consola de Firebase por errores

### Notificaciones solo funcionan con navegador abierto
1. Verifica que el Service Worker esté registrado
2. Verifica que Firebase esté correctamente inicializado
3. Revisa logs del Service Worker en DevTools > Application > Service Workers

## 📞 Comandos útiles

```bash
# Ver Server Key configurada
wp option get cv_firebase_server_key --allow-root

# Ver VAPID Key configurada
wp option get cv_firebase_vapid_key --allow-root

# Ver token FCM de un usuario
wp user meta get USER_ID cv_fcm_token --allow-root

# Limpiar todos los tokens (forzar re-registro)
wp db query "DELETE FROM wp_usermeta WHERE meta_key = 'cv_fcm_token'" --allow-root
```

