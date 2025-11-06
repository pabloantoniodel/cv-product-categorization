# Ciudad Virtual - Frontend Enhancements

Plugin de mejoras visuales para el frontend de Ciudad Virtual.

## 📋 Características

### ✅ Sistema de Burbujas de Geolocalización

Visualización innovadora de tiendas cercanas con burbujas flotantes animadas.

**Shortcode**: `[cv_store_bubbles]`

**Características**:
- 🫧 Burbujas animadas con física de movimiento
- 📍 Ordenadas por distancia (más cercanas más grandes)
- 🖼️ Fotos circulares de las tiendas
- 🎯 Click para ir a la tienda
- 🗺️ Toggle entre vista burbujas y mapa original
- 📱 Totalmente responsive
- ⚡ Optimizado con Canvas HTML5

---

## 🚀 Uso

### Shortcode Básico

```
[cv_store_bubbles]
```

### Shortcode con Parámetros

```
[cv_store_bubbles radius="5" limit="30" view="bubbles"]
```

**Parámetros:**
- `radius`: Radio de búsqueda en km (default: 10)
- `limit`: Máximo de tiendas a mostrar (default: 50)
- `view`: Vista inicial - "bubbles" o "map" (default: bubbles)

---

## 📊 Tecnologías

- **Canvas HTML5** - Renderizado de alto rendimiento
- **JavaScript Vanilla** - Sin dependencias pesadas
- **Física de partículas** - Movimiento orgánico
- **Geolocalización API** - Ubicación del usuario
- **Fórmula Haversine** - Cálculo preciso de distancias
- **WordPress AJAX** - Carga dinámica de tiendas

---

## 🎨 Funcionalidades

### Vista Burbujas

- Burbujas flotantes con movimiento continuo
- Tamaño dinámico basado en distancia
- Colores por proximidad:
  - 🟢 Verde: < 1 km
  - 🟣 Morado: 1-3 km
  - 🌸 Rosa: 3-5 km
  - 🔵 Azul: > 5 km
- Repulsión entre burbujas (no se solapan)
- Hover: tooltip con info completa
- Click: navegación directa a la tienda

### Tooltip Interactivo

Al pasar el mouse sobre una burbuja:
- Foto grande de la tienda
- Nombre del comercio
- Distancia exacta
- Ubicación
- Botón "Ver Tienda"

### Toggle de Vista

Botones para cambiar entre:
- 🫧 Vista Burbujas (animada)
- 🗺️ Vista Mapa (WCFM original)

---

## 📁 Estructura

```
cv-front/
├── cv-front.php                     # Plugin principal
├── includes/
│   └── class-cv-store-bubbles.php   # Clase del sistema de burbujas
├── views/
│   └── bubbles-view.php             # Template HTML
├── assets/
│   ├── js/
│   │   └── bubble-engine.js         # Motor de animación
│   ├── css/
│   │   └── store-bubbles.css        # Estilos
│   └── images/
│       └── default-store-logo.png   # Logo por defecto
└── README.md                        # Esta documentación
```

---

## 🔧 Instalación

1. Subir carpeta `cv-front` a `/wp-content/plugins/`
2. Activar el plugin en WordPress
3. Insertar shortcode `[cv_store_bubbles]` en cualquier página

---

## 📖 Desarrollo

### Query de Tiendas Cercanas

```sql
SELECT 
    u.ID, u.display_name,
    lat.meta_value as store_lat,
    lng.meta_value as store_lng,
    (6371 * acos(
        cos(radians($user_lat)) * 
        cos(radians(store_lat)) * 
        cos(radians(store_lng) - radians($user_lng)) + 
        sin(radians($user_lat)) * 
        sin(radians(store_lat))
    )) AS distance
FROM wp_users u
WHERE ...
HAVING distance < $radius_km
ORDER BY distance ASC
```

### Motor de Física

```javascript
// Movimiento flotante
bubble.x += bubble.vx;
bubble.y += bubble.vy;

// Repulsión entre burbujas
if (distance < minDist) {
    applyRepulsionForce();
}

// Mantener dentro del canvas
boundaryCheck();
```

---

## 🎯 Próximas Mejoras

- [ ] Filtros por categoría de tienda
- [ ] Búsqueda por nombre
- [ ] Favoritos guardados
- [ ] Compartir ubicación de tienda
- [ ] Animación de ruta al click
- [ ] Modo 3D (Three.js)
- [ ] Clusters para muchas tiendas
- [ ] Vista de lista alternativa

---

## 📝 Changelog

### 1.0.0 - 2025-10-22
- ✅ Implementación inicial
- ✅ Sistema de burbujas con Canvas
- ✅ Física de movimiento básica
- ✅ Toggle burbujas/mapa
- ✅ Tooltip interactivo
- ✅ Responsive design
- ✅ Integración WCFM

---

## 🐛 Soporte

Para reportar bugs o solicitar features:
- GitHub: https://github.com/pabloantoniodel/cv-front
- Email: soporte@ciudadvirtual.app

---

## 📄 Licencia

GPL v2 or later





