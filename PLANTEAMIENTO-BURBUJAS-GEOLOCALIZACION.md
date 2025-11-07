# 🎯 Planteamiento: Sistema de Burbujas Animadas para Geolocalización de Tiendas

## 📋 Requisitos del Cliente

### Funcionalidades Solicitadas:
1. ✅ **Burbujas animadas** en lugar de mapa tradicional
2. ✅ **Nombres de comercios** visibles en las burbujas
3. ✅ **Distancias** mostradas dinámicamente
4. ✅ **Ordenar por proximidad** (más cercanos primero)
5. ✅ **Burbujas móviles** (animación de movimiento)
6. ✅ **Fotos de comercios** visibles en las burbujas
7. ✅ **Click → ir a la tienda** (navegación directa)
8. ✅ **Opción de ver mapa original** (toggle entre vistas)
9. ✅ **Mapa puede empezar oculto** (burbujas como vista principal)
10. ✅ **Dinámico** (actualización en tiempo real)

---

## 🔍 Análisis del Sistema Actual

### Sistema Existente:
- **Plugin**: WCFM Marketplace + wcfm-radius-persistence
- **Mapa**: Leaflet (OpenStreetMap) o Google Maps
- **Filtro**: Búsqueda por radio (km)
- **Vista**: Mapa + lista de tiendas
- **Datos**: Lat/Lng de cada tienda, dirección, info de vendedor

### Archivos Clave:
```
wp-content/plugins/wc-multivendor-marketplace/
├── views/store-lists/
│   ├── wcfmmp-view-store-lists.php (vista principal)
│   ├── wcfmmp-view-store-lists-map.php (mapa actual)
│   ├── wcfmmp-view-store-lists-loop.php (listado de tiendas)
│   └── wcfmmp-view-store-lists-card.php (tarjeta de tienda)
├── assets/js/store-lists/
│   └── wcfmmp-script-store-lists.js (lógica del mapa)

wp-content/plugins/wcfm-radius-persistence/
└── assets/js/radius-filter-enhance.js (filtro de radio)
```

---

## 🎨 Propuesta de Solución: Sistema de Burbujas Flotantes

### Concepto Visual:

```
┌─────────────────────────────────────────────────────────────┐
│  [Burbujas] [Mapa]  [Filtros: Radio, Categoría]            │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│    🟢                    🔵         🟣                      │
│   (Foto)               (Foto)     (Foto)                    │
│  Tienda A              Tienda B   Tienda C                  │
│  📍 0.5 km            📍 1.2 km   📍 2.5 km                 │
│                                                             │
│        🟡                   🔴                              │
│       (Foto)               (Foto)                           │
│      Tienda D             Tienda E                          │
│      📍 3.1 km           📍 5.0 km                          │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Comportamiento de las Burbujas:

#### 1. **Movimiento Orgánico (Física)**
```javascript
// Simulación de física con repulsión entre burbujas
- Cada burbuja tiene posición (x, y)
- Movimiento aleatorio suave (floating)
- Repulsión entre burbujas cercanas (no se solapan)
- Gravedad suave hacia el centro
- Velocidad proporcional a la distancia (más cercanas = más rápidas)
```

#### 2. **Ordenamiento Visual**
```javascript
// Tamaño y posición basados en distancia
- Más cercanas: Burbujas más grandes + posición superior/izquierda
- Más lejanas: Burbujas más pequeñas + posición inferior/derecha
- Z-index dinámico (más cercanas al frente)
```

#### 3. **Interactividad**
```javascript
// Eventos de usuario
- Hover: Pausa movimiento + resalta + muestra info adicional
- Click: Redirige a la tienda
- Drag: Mover burbuja manualmente (opcional)
```

---

## 🛠️ Opciones Técnicas

### **Opción 1: Canvas HTML5 + JavaScript Vanilla** ⭐ RECOMENDADA
**Ventajas:**
- ✅ Máximo rendimiento (60 FPS con 100+ burbujas)
- ✅ Control total de la animación
- ✅ Física personalizada
- ✅ Bajo peso (sin librerías pesadas)
- ✅ Compatible con todos los navegadores

**Implementación:**
```javascript
// Librería: Custom o Matter.js para física
- Canvas 2D context
- RequestAnimationFrame para animación
- Física de partículas con repulsión
- Renderizado de imágenes circulares (fotos)
- Eventos click/hover sobre burbujas
```

**Peso estimado**: ~15-20 KB JavaScript + 5 KB CSS

---

### **Opción 2: D3.js Force Simulation**
**Ventajas:**
- ✅ Física de fuerzas muy robusta
- ✅ Animaciones suaves
- ✅ SVG (escalable)
- ✅ Muchos ejemplos disponibles

**Desventajas:**
- ⚠️ Librería pesada (~70 KB)
- ⚠️ Curva de aprendizaje más alta

**Implementación:**
```javascript
// Librería: D3.js v7
- forceSimulation() para física
- forceCollide() para evitar solapamiento
- forceManyBody() para repulsión
- forceCenter() para centrado
```

---

### **Opción 3: Three.js 3D (Avanzada)**
**Ventajas:**
- ✅ Efecto WOW (burbujas en 3D)
- ✅ WebGL optimizado
- ✅ Posibilidad de efectos especiales

**Desventajas:**
- ⚠️ Muy pesada (~150 KB)
- ⚠️ Overkill para este caso
- ⚠️ Complejidad innecesaria

**NO RECOMENDADA** para este caso

---

## ⭐ Recomendación: Opción 1 (Canvas + JavaScript Vanilla)

### Arquitectura Propuesta:

```
store-bubbles-view/
├── cv-store-bubbles.php          # Plugin principal
├── assets/
│   ├── js/
│   │   ├── bubble-engine.js      # Motor de física y animación
│   │   ├── bubble-renderer.js    # Renderizado en canvas
│   │   └── bubble-controller.js  # Controlador principal
│   └── css/
│       └── bubbles.css           # Estilos del contenedor
└── views/
    └── bubbles-view.php          # Template HTML
```

---

## 📐 Especificaciones Técnicas

### **Datos Necesarios por Burbuja:**

```javascript
{
    id: 123,                    // ID de la tienda
    name: "Tienda XYZ",         // Nombre del comercio
    logo: "https://...",        // URL de la foto/logo
    distance: 1.5,              // Distancia en km
    lat: 40.416775,             // Latitud
    lng: -3.703790,             // Longitud
    url: "/store/tienda-xyz/",  // URL de la tienda
    rating: 4.5,                // Valoración (opcional)
    products_count: 45          // Número de productos (opcional)
}
```

### **Motor de Física:**

```javascript
class BubblePhysics {
    constructor(bubbles, canvasWidth, canvasHeight) {
        this.bubbles = bubbles;
        this.width = canvasWidth;
        this.height = canvasHeight;
    }
    
    update(deltaTime) {
        // 1. Aplicar velocidad aleatoria (floating)
        this.applyRandomMovement();
        
        // 2. Repulsión entre burbujas
        this.applyCollisionRepulsion();
        
        // 3. Gravedad al centro
        this.applyCenterGravity();
        
        // 4. Mantener dentro del canvas
        this.applyBoundaries();
        
        // 5. Actualizar posiciones
        this.updatePositions(deltaTime);
    }
    
    applyRandomMovement() {
        this.bubbles.forEach(bubble => {
            // Movimiento Browniano (aleatorio suave)
            bubble.vx += (Math.random() - 0.5) * 0.1;
            bubble.vy += (Math.random() - 0.5) * 0.1;
        });
    }
    
    applyCollisionRepulsion() {
        for (let i = 0; i < this.bubbles.length; i++) {
            for (let j = i + 1; j < this.bubbles.length; j++) {
                const b1 = this.bubbles[i];
                const b2 = this.bubbles[j];
                
                const dx = b2.x - b1.x;
                const dy = b2.y - b1.y;
                const distance = Math.sqrt(dx*dx + dy*dy);
                const minDist = b1.radius + b2.radius + 10;
                
                if (distance < minDist) {
                    // Repulsión
                    const force = (minDist - distance) / minDist;
                    const fx = (dx / distance) * force * 2;
                    const fy = (dy / distance) * force * 2;
                    
                    b1.vx -= fx;
                    b1.vy -= fy;
                    b2.vx += fx;
                    b2.vy += fy;
                }
            }
        }
    }
}
```

### **Renderizado de Burbujas:**

```javascript
class BubbleRenderer {
    drawBubble(ctx, bubble) {
        // 1. Sombra
        ctx.shadowColor = 'rgba(0,0,0,0.2)';
        ctx.shadowBlur = 10;
        ctx.shadowOffsetY = 5;
        
        // 2. Círculo de fondo
        ctx.beginPath();
        ctx.arc(bubble.x, bubble.y, bubble.radius, 0, Math.PI * 2);
        ctx.fillStyle = bubble.color;
        ctx.fill();
        
        // 3. Foto circular (clip)
        ctx.save();
        ctx.beginPath();
        ctx.arc(bubble.x, bubble.y, bubble.radius - 10, 0, Math.PI * 2);
        ctx.clip();
        if (bubble.image && bubble.image.complete) {
            ctx.drawImage(
                bubble.image,
                bubble.x - bubble.radius + 10,
                bubble.y - bubble.radius + 10,
                (bubble.radius - 10) * 2,
                (bubble.radius - 10) * 2
            );
        }
        ctx.restore();
        
        // 4. Nombre del comercio
        ctx.fillStyle = '#333';
        ctx.font = 'bold 14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText(
            bubble.name,
            bubble.x,
            bubble.y + bubble.radius + 20
        );
        
        // 5. Distancia
        ctx.fillStyle = '#667eea';
        ctx.font = '12px Arial';
        ctx.fillText(
            '📍 ' + bubble.distance + ' km',
            bubble.x,
            bubble.y + bubble.radius + 35
        );
    }
}
```

### **Cálculo de Tamaño por Distancia:**

```javascript
function calculateBubbleSize(distance) {
    // Más cercano = más grande (60-120px radio)
    const minRadius = 60;
    const maxRadius = 120;
    
    // Escala logarítmica para mejor distribución
    const normalized = Math.log(distance + 1) / Math.log(10);
    const size = maxRadius - (normalized * (maxRadius - minRadius));
    
    return Math.max(minRadius, Math.min(maxRadius, size));
}

// Ejemplos:
// 0.5 km → 115px
// 1.0 km → 105px
// 2.0 km → 90px
// 5.0 km → 70px
// 10 km → 60px
```

---

## 🎨 Diseño Propuesto

### Vista Principal (Burbujas):

```html
<div class="cv-store-view-container">
    <!-- Toggle entre vistas -->
    <div class="cv-view-toggle">
        <button class="cv-toggle-btn active" data-view="bubbles">
            🫧 Vista Burbujas
        </button>
        <button class="cv-toggle-btn" data-view="map">
            🗺️ Vista Mapa
        </button>
    </div>
    
    <!-- Vista Burbujas (default visible) -->
    <div id="cv-bubbles-view" class="cv-bubbles-container">
        <canvas id="cv-bubbles-canvas"></canvas>
        
        <!-- Tooltip flotante al hover -->
        <div id="cv-bubble-tooltip" class="cv-tooltip">
            <img src="" class="cv-tooltip-photo">
            <h3 class="cv-tooltip-name"></h3>
            <p class="cv-tooltip-distance"></p>
            <p class="cv-tooltip-info"></p>
            <button class="cv-tooltip-btn">Ver Tienda →</button>
        </div>
    </div>
    
    <!-- Vista Mapa (oculta por defecto) -->
    <div id="cv-map-view" class="cv-map-container" style="display:none;">
        <!-- Mapa original de WCFM -->
    </div>
</div>
```

### Burbuja Individual:

```
     ┌─────────────┐
     │             │
     │   ┌─────┐   │  ← Foto circular (clip mask)
     │   │foto │   │
     │   │🏪  │   │
     │   └─────┘   │
     │             │
     │  Tienda A   │  ← Nombre (texto)
     │  📍 0.5 km  │  ← Distancia (texto con icono)
     │             │
     └─────────────┘
          ↑
    Círculo con gradiente
```

### Estados de las Burbujas:

1. **Normal**: Flotando suavemente
2. **Hover**: 
   - Pausa movimiento
   - Escala 1.1x
   - Muestra tooltip completo
   - Brillo/glow alrededor
3. **Click**: 
   - Animación de "pop"
   - Redirige a la tienda

---

## 🚀 Roadmap de Implementación

### **Fase 1: MVP (Mínimo Viable)** - 1 semana

**Objetivo**: Burbujas básicas funcionando

- [ ] Canvas con burbujas estáticas
- [ ] Fotos circulares renderizadas
- [ ] Nombres y distancias visibles
- [ ] Click redirige a tienda
- [ ] Ordenar por distancia (más cercanos más grandes)

**Archivos a crear:**
- `cv-store-bubbles.php` (plugin)
- `assets/js/bubble-engine.js`
- `assets/css/bubbles.css`

---

### **Fase 2: Animación** - 3-5 días

**Objetivo**: Burbujas en movimiento

- [ ] Movimiento aleatorio suave (floating)
- [ ] Física de repulsión entre burbujas
- [ ] RequestAnimationFrame optimizado
- [ ] Pausa al hover

---

### **Fase 3: Interactividad Avanzada** - 3-5 días

**Objetivo**: UX mejorada

- [ ] Tooltip flotante al hover
- [ ] Toggle entre vista burbujas y mapa
- [ ] Filtro de búsqueda en tiempo real
- [ ] Animación de entrada (burbujas desde centro)
- [ ] Responsive (móvil, tablet, desktop)

---

### **Fase 4: Integración WCFM** - 2-3 días

**Objetivo**: Reemplazar vista actual

- [ ] Hook en WCFM store lists
- [ ] Mantener filtros existentes (radio, categoría)
- [ ] Sincronizar con búsqueda actual
- [ ] Guardar preferencia de vista (burbujas vs mapa)

---

### **Fase 5: Optimización y Pulido** - 2-3 días

**Objetivo**: Performance y detalles

- [ ] Lazy loading de imágenes
- [ ] Virtualización (solo renderizar burbujas visibles)
- [ ] Animación de carga (skeleton)
- [ ] Estados vacíos
- [ ] Accesibilidad (ARIA labels)

---

## 💻 Stack Tecnológico Recomendado

### **Frontend:**
```javascript
// Core
- HTML5 Canvas API
- RequestAnimationFrame
- Intersection Observer (lazy load)

// Opcional (física avanzada)
- Matter.js (solo 87 KB) si necesitas física muy realista
- O implementación custom (más ligero, ~5 KB)

// Estilos
- CSS3 Transforms
- CSS Grid/Flexbox para layout
- CSS Animations para efectos
```

### **Backend:**
```php
// WordPress/PHP
- Query de tiendas con geolocalización
- Cálculo de distancias (Haversine formula)
- Endpoint AJAX para búsqueda dinámica
- Caché de resultados (Transients API)
```

### **Datos:**
```sql
-- Query optimizada
SELECT 
    p.ID as store_id,
    p.post_title as store_name,
    m1.meta_value as store_lat,
    m2.meta_value as store_lng,
    m3.meta_value as store_logo,
    (
        6371 * acos(
            cos(radians($user_lat)) * 
            cos(radians(m1.meta_value)) * 
            cos(radians(m2.meta_value) - radians($user_lng)) + 
            sin(radians($user_lat)) * 
            sin(radians(m1.meta_value))
        )
    ) AS distance
FROM wp_posts p
JOIN wp_postmeta m1 ON p.ID = m1.post_id AND m1.meta_key = 'wcfm_store_lat'
JOIN wp_postmeta m2 ON p.ID = m2.post_id AND m2.meta_key = 'wcfm_store_lng'
LEFT JOIN wp_postmeta m3 ON p.ID = m3.post_id AND m3.meta_key = 'store_logo'
WHERE p.post_type = 'wcfm_vendor'
HAVING distance < $radius_km
ORDER BY distance ASC
LIMIT 50
```

---

## 🎯 Configuración Recomendada

### **Parámetros Ajustables:**

```php
// En admin de WordPress
$config = array(
    // Física
    'bubble_min_size' => 60,           // Radio mínimo (px)
    'bubble_max_size' => 120,          // Radio máximo (px)
    'movement_speed' => 1.0,           // Velocidad de movimiento
    'repulsion_strength' => 2.0,       // Fuerza de repulsión
    
    // Visual
    'show_photos' => true,             // Mostrar fotos
    'show_names' => true,              // Mostrar nombres
    'show_distances' => true,          // Mostrar distancias
    'default_view' => 'bubbles',       // 'bubbles' o 'map'
    'enable_map_toggle' => true,       // Permitir cambiar a mapa
    
    // Performance
    'max_bubbles' => 50,               // Máximo de burbujas simultáneas
    'fps_target' => 60,                // FPS objetivo
    'pause_on_hover' => true,          // Pausar al hover
    
    // Colores (gradientes por distancia)
    'near_color' => '#43e97b',         // < 1km
    'medium_color' => '#667eea',       // 1-5km
    'far_color' => '#fa709a',          // > 5km
);
```

---

## 🎪 Animaciones Sugeridas

### 1. **Movimiento Base (Floating)**
```javascript
// Movimiento sinusoidal suave
bubble.x += Math.sin(time * bubble.floatSpeed) * 0.5;
bubble.y += Math.cos(time * bubble.floatSpeed * 0.7) * 0.3;
```

### 2. **Repulsión entre Burbujas**
```javascript
// Mantiene separación mínima
if (distance < minDistance) {
    // Aplicar fuerza repulsiva
    const force = (minDistance - distance) / minDistance;
    bubble1.vx -= dx * force;
    bubble1.vy -= dy * force;
}
```

### 3. **Animación de Entrada**
```javascript
// Burbujas aparecen desde el centro
- Escala: 0 → 1 (0.5s con easing)
- Opacidad: 0 → 1
- Posición: centro → posición final
```

### 4. **Hover Effect**
```javascript
// Al pasar el mouse
- Escala: 1 → 1.15 (0.3s cubic-bezier)
- Glow: box-shadow aumenta
- Pausa movimiento (velocity = 0)
- Z-index al frente
```

---

## 📱 Responsive Design

### Desktop (1200px+):
```
Canvas: 100% width × 600px height
Burbujas: 60-120px radio
Grid mental: ~6-8 burbujas visibles
```

### Tablet (768-1199px):
```
Canvas: 100% width × 500px height
Burbujas: 50-100px radio
Grid mental: ~4-6 burbujas visibles
```

### Móvil (<768px):
```
Canvas: 100% width × 400px height
Burbujas: 40-80px radio
Grid mental: ~2-3 burbujas visibles
Vista por defecto: Lista (opcional)
```

---

## ⚡ Optimizaciones de Performance

### 1. **Lazy Loading de Imágenes**
```javascript
const imageCache = new Map();

function loadImage(url) {
    if (imageCache.has(url)) {
        return imageCache.get(url);
    }
    
    const img = new Image();
    img.src = url;
    img.onload = () => imageCache.set(url, img);
    return img;
}
```

### 2. **Virtualización (Off-screen Culling)**
```javascript
// Solo procesar burbujas visibles en viewport
function isVisible(bubble, canvasWidth, canvasHeight) {
    return bubble.x + bubble.radius > 0 &&
           bubble.x - bubble.radius < canvasWidth &&
           bubble.y + bubble.radius > 0 &&
           bubble.y - bubble.radius < canvasHeight;
}
```

### 3. **Throttling de Física**
```javascript
// Actualizar física cada 2 frames para ahorrar CPU
let physicsCounter = 0;
function update() {
    physicsCounter++;
    if (physicsCounter % 2 === 0) {
        updatePhysics();
    }
    render();
}
```

### 4. **RequestIdleCallback para tareas secundarias**
```javascript
// Cargar imágenes cuando el navegador esté idle
requestIdleCallback(() => {
    preloadNextBatch();
});
```

---

## 🎨 Ejemplos de Diseño

### **Paleta de Colores por Distancia:**

```javascript
function getBubbleColor(distance) {
    if (distance < 1) {
        return 'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)'; // Verde
    } else if (distance < 3) {
        return 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; // Morado
    } else if (distance < 5) {
        return 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)'; // Rosa
    } else {
        return 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'; // Azul
    }
}
```

### **Tooltip al Hover:**

```css
.cv-tooltip {
    position: absolute;
    background: white;
    border-radius: 12px;
    padding: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    pointer-events: none;
    z-index: 1000;
}
```

```html
┌────────────────────┐
│  [Foto Grande]     │
│  Tienda XYZ        │
│  📍 0.5 km         │
│  ⭐⭐⭐⭐⭐ 4.5    │
│  🛍️ 45 productos  │
│  [Ver Tienda →]    │
└────────────────────┘
```

---

## 🔄 Integración con Sistema Actual

### **Hooks de WCFM:**

```php
// Reemplazar template de mapa
add_filter('wcfmmp_store_list_view_map', 'cv_replace_with_bubbles', 10, 1);

function cv_replace_with_bubbles($template) {
    // Si el usuario prefiere burbujas
    $view_preference = get_user_meta(get_current_user_id(), 'store_view_preference', true);
    
    if ($view_preference === 'bubbles' || empty($view_preference)) {
        return CV_STORE_BUBBLES_PLUGIN_DIR . 'views/bubbles-view.php';
    }
    
    return $template; // Devolver mapa original
}
```

### **Mantener Filtros Existentes:**

```javascript
// Escuchar eventos de filtro de radio
$(document).on('wcfmmp_radius_filter_updated', function(e, data) {
    // data.radius, data.lat, data.lng
    bubbleController.updateStores(data);
});
```

---

## 📊 Comparación de Opciones

| Característica | Canvas Custom | D3.js | Three.js |
|---------------|---------------|-------|----------|
| **Performance** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Peso** | ~20 KB | ~70 KB | ~150 KB |
| **Complejidad** | Media | Media-Alta | Alta |
| **Flexibilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ |
| **Compatibilidad** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ | ⭐⭐⭐ |
| **Curva aprendizaje** | Media | Alta | Muy Alta |
| **Mantenimiento** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐ |

**Recomendación**: ⭐ **Canvas Custom** (mejor balance)

---

## 🎯 Mi Recomendación Final

### **Implementación en 2 Fases:**

#### **Fase 1: Sistema de Burbujas Básico (Prioritario)**

**Plugin**: `cv-store-bubbles`

**Features Core:**
1. Canvas con burbujas ordenadas por distancia
2. Fotos circulares (clip mask)
3. Nombres + distancias visibles
4. Click → navegar a tienda
5. Toggle burbujas ⇄ mapa
6. Responsive automático

**Tiempo estimado**: 5-7 días

#### **Fase 2: Animación Avanzada (Opcional)**

**Features Avanzadas:**
1. Física de partículas (repulsión, gravedad)
2. Movimiento continuo (floating)
3. Tooltip interactivo al hover
4. Filtros en tiempo real
5. Animaciones de transición

**Tiempo estimado**: 3-5 días adicionales

---

## 🛠️ Tecnologías Recomendadas

### **Stack Mínimo:**
```
- HTML5 Canvas
- JavaScript ES6+ (classes, arrow functions)
- CSS3 (gradients, transforms)
- WordPress AJAX
- Transients API (caché)
```

### **Stack Completo (con física):**
```
+ Matter.js (física 2D)
+ GSAP (animaciones suaves - opcional)
+ Intersection Observer (lazy loading)
```

---

## 📝 Próximos Pasos

### **Para Empezar:**

1. **Validar el diseño**: ¿Te gusta el concepto visual descrito arriba?
2. **Confirmar prioridades**: ¿Qué es más importante?
   - Animación suave
   - Performance con muchas tiendas
   - Compatibilidad móvil
3. **Decidir alcance inicial**:
   - ¿Empezamos con MVP estático?
   - ¿O vamos directo con animación?
4. **Ubicación**: ¿Dónde quieres las burbujas?
   - Página de tiendas (`/stores/`)
   - Homepage
   - Widget en sidebar
   - Shortcode personalizable

---

## 💡 Alternativas Híbridas

### **Opción Híbrida 1: Burbujas sobre Mapa**
- Mapa de fondo (difuminado)
- Burbujas flotando encima
- Click en burbuja → highlight en mapa

### **Opción Híbrida 2: Slider 3D de Burbujas**
- Carrusel circular de burbujas
- Más cercanas al centro (más grandes)
- Rotación automática
- Estilo "cover flow" de Apple

---

## ❓ Preguntas Clave

Antes de empezar la implementación, necesito saber:

1. **¿Cuántas tiendas aproximadamente** se mostrarán simultáneamente? (10, 50, 100+)
2. **¿Qué es más prioritario**: Animación espectacular o rendimiento?
3. **¿El mapa debe estar siempre disponible** o puede ser totalmente opcional?
4. **¿Preferencia de vista por defecto**: Burbujas (80% usuarios) o dejar que elijan?
5. **¿Móvil es crítico**? ¿Qué % de usuarios son móvil?
6. **¿Timeline deseado**? ¿Urgente o podemos hacerlo bien?

---

## 🎬 ¿Qué te parece este planteamiento?

**Opción A**: Empezar con **MVP Burbujas Básicas** (sin animación) y luego iterar

**Opción B**: Ir directo a **Burbujas Animadas Completas** (más tiempo pero mejor resultado)

**Opción C**: **Híbrido Burbujas sobre Mapa** (combina ambos mundos)

**¿Cuál prefieres? ¿Algún ajuste al planteamiento?** 🚀





