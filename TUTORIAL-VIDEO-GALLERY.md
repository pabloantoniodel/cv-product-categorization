# 📺 Tutorial: Galería de Videos de YouTube

## Tu canal: @economiacolaborativacircular

URL: https://www.youtube.com/@economiacolaborativacircular

## 🚀 Cómo usar el shortcode

### En WordPress (ciudadvirtual.es)

1. Ve a la página: https://ciudadvirtual.es/index.php/comercio/tutorial-marketplace/
2. Edita la página
3. Añade este shortcode:

```
[cv-video-gallery channel="economiacolaborativacircular" max="12" columns="3"]
```

### Parámetros disponibles

- `channel` - Nombre del canal (sin @)
- `max` - Número máximo de videos (por defecto: 12)
- `columns` - Columnas en desktop: 2, 3 o 4 (por defecto: 3)

## ✨ Ejemplos

### Mostrar 9 videos en 3 columnas
```
[cv-video-gallery channel="economiacolaborativacircular" max="9" columns="3"]
```

### Mostrar 8 videos en 4 columnas
```
[cv-video-gallery channel="economiacolaborativacircular" max="8" columns="4"]
```

### Mostrar 6 videos en 2 columnas
```
[cv-video-gallery channel="economiacolaborativacircular" max="6" columns="2"]
```

## 🔄 Actualización automática

- Los videos se actualizan **automáticamente** cada 30 minutos
- Cuando subes un nuevo video a YouTube, aparecerá solo en la galería
- No necesitas hacer nada, todo es automático

## 🧹 Limpiar caché manualmente

Si quieres que un nuevo video aparezca inmediatamente (sin esperar 30 minutos):

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp transient delete cv_video_gallery_channel_$(echo -n "economiacolaborativacircular" | md5sum | cut -d' ' -f1) --allow-root
```

O desde PHP (en WordPress admin > Herramientas > Salud del sitio > Información):
```php
delete_transient('cv_video_gallery_channel_' . md5('economiacolaborativacircular'));
```

## 🎨 Características visuales

✅ Miniaturas grandes de alta calidad
✅ Icono de play de YouTube
✅ Títulos automáticos desde YouTube
✅ Modal fullscreen al hacer click
✅ Reproductor YouTube integrado
✅ Responsive (móvil/tablet/desktop)
✅ Hover effects modernos
✅ Gradiente morado Ciudad Virtual

## 📱 Responsive

- **Desktop (>768px)**: 2, 3 o 4 columnas según configuración
- **Tablet (768px)**: 2 columnas automático
- **Móvil (<768px)**: 1 columna automático

## 🎯 También puedes usar playlists

Si prefieres organizar por playlist:

```
[cv-video-gallery playlist="PLxxxxxxxxx" max="12" columns="3"]
```

## 📝 Videos manuales

Si prefieres control total sobre qué videos mostrar:

```
[cv-video-gallery videos="ID1,ID2,ID3" titles="Tutorial 1,Tutorial 2,Tutorial 3" columns="3"]
```

Para obtener el ID de un video de YouTube:
- URL: `https://www.youtube.com/watch?v=dQw4w9WgXcQ`
- ID: `dQw4w9WgXcQ` (lo que viene después de `v=`)

## 🔧 Solución de problemas

### No se ven los videos
1. Verifica que el canal sea público
2. Verifica que el nombre del canal esté correcto (sin @)
3. Limpia la caché (ver arriba)

### Los videos no se actualizan
1. Espera 30 minutos desde la última carga
2. Limpia la caché manualmente
3. Verifica que los nuevos videos sean públicos

### Error de conexión
1. Verifica que el servidor tenga acceso a YouTube
2. Verifica que `wp_remote_get` esté habilitado

## 📞 Soporte

Si tienes problemas, contacta al equipo de desarrollo con:
- URL de la página donde pusiste el shortcode
- Mensaje de error (si lo hay)
- Nombre del canal o playlist

