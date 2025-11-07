# Guía de Personalización - Shopper Modern

## 🎨 Cambiar Colores del Sitio

### Método 1: Variables CSS (Recomendado)

Edita `style.css` en la sección `:root` (líneas 13-35):

```css
:root {
    /* Cambia estos valores */
    --primary-color: #2196F3;      /* Color principal (botones, enlaces) */
    --secondary-color: #FF5722;    /* Color secundario (ofertas, destacados) */
    --accent-color: #00BCD4;       /* Color de acento */
}
```

### Colores Sugeridos por Estilo:

**Elegante y Profesional:**
```css
--primary-color: #1A237E;    /* Azul Oscuro */
--secondary-color: #FFC107;  /* Dorado */
--accent-color: #455A64;     /* Gris Azulado */
```

**Fresco y Natural:**
```css
--primary-color: #4CAF50;    /* Verde */
--secondary-color: #FF9800;  /* Naranja */
--accent-color: #00BCD4;     /* Cyan */
```

**Moderno y Tech:**
```css
--primary-color: #6200EA;    /* Púrpura Profundo */
--secondary-color: #00E676;  /* Verde Brillante */
--accent-color: #FF1744;     /* Rosa */
```

**Minimalista:**
```css
--primary-color: #212121;    /* Negro */
--secondary-color: #BDBDBD;  /* Gris */
--accent-color: #FFFFFF;     /* Blanco */
```

---

## 🔤 Cambiar Tipografía

### En `functions.php`, línea 35-40:

```php
// Reemplazar 'Poppins' por otra fuente de Google Fonts
wp_enqueue_style(
    'shopper-modern-fonts',
    'https://fonts.googleapis.com/css2?family=TuFuenteAqui:wght@400;500;600;700&display=swap',
    array(),
    null
);
```

### En `style.css`, línea 24:

```css
--font-heading: 'TuFuenteAqui', sans-serif;
```

### Fuentes Recomendadas:

- **Moderna**: Montserrat, Raleway, Nunito
- **Elegante**: Playfair Display, Cormorant, Crimson Text
- **Profesional**: Inter, Work Sans, DM Sans
- **Divertida**: Quicksand, Comfortaa, Varela Round

---

## 📦 Productos por Fila

### En `functions.php`, línea 93:

```php
add_filter('loop_shop_columns', function() {
    return 4; // Cambia a 3 o 5 según prefieras
});
```

---

## 🎭 Añadir Animaciones a Elementos

### Clases Disponibles:

```html
<!-- Fade In -->
<div class="animate-on-scroll">Contenido</div>

<!-- Hover Effects -->
<div class="hover-lift">Se eleva al hacer hover</div>
<div class="hover-glow">Brilla al hacer hover</div>
<div class="hover-scale">Crece al hacer hover</div>

<!-- Stagger Animation (para listas) -->
<ul>
    <li class="stagger-item">Item 1</li>
    <li class="stagger-item">Item 2</li>
    <li class="stagger-item">Item 3</li>
</ul>
```

---

## 🖼️ Personalizar Bordes Redondeados

### En `style.css`, líneas 36-40:

```css
:root {
    --radius-sm: 4px;      /* Pequeño */
    --radius-md: 8px;      /* Mediano */
    --radius-lg: 12px;     /* Grande */
    --radius-xl: 16px;     /* Extra grande */
}
```

Para esquinas más cuadradas: usa valores menores (2px, 4px)  
Para más redondeadas: usa valores mayores (16px, 24px)

---

## 🌑 Modo Oscuro (Dark Mode)

### Añadir en `style.css`:

```css
/* Al final del archivo */
@media (prefers-color-scheme: dark) {
    :root {
        --gray-50: #212121;
        --gray-100: #424242;
        --gray-900: #FAFAFA;
        /* Invierte los colores grises */
    }
    
    body {
        background: #121212;
        color: #E0E0E0;
    }
    
    .site-header {
        background: rgba(18, 18, 18, 0.95);
    }
}
```

---

## 📱 Ajustar Responsive

### Cambiar breakpoints en `style.css`:

```css
/* Tablet */
@media (max-width: 1024px) {
    /* Tus estilos para tablet */
}

/* Móvil */
@media (max-width: 768px) {
    /* Tus estilos para móvil */
}

/* Móvil pequeño */
@media (max-width: 480px) {
    /* Tus estilos para móviles pequeños */
}
```

---

## 🎯 Personalizar Header

### Header Transparente (en home):

Añade en `style.css`:

```css
.home .site-header {
    background: transparent;
    position: absolute;
    width: 100%;
    top: 0;
    left: 0;
}

.home .site-header.scrolled {
    background: rgba(255, 255, 255, 0.95);
}
```

### Cambiar altura del header:

```css
.site-header {
    padding: 2rem 0; /* Ajusta el padding */
}
```

---

## 🛍️ Personalizar Botones de WooCommerce

### Botones más grandes:

```css
.woocommerce a.button,
.woocommerce button.button {
    padding: 16px 32px; /* En lugar de 12px 24px */
    font-size: 18px;    /* En lugar de 16px */
}
```

### Botones con borde en lugar de relleno:

```css
.woocommerce a.button {
    background: transparent;
    border: 2px solid var(--primary-color);
    color: var(--primary-color);
}

.woocommerce a.button:hover {
    background: var(--primary-color);
    color: white;
}
```

---

## 🔧 Tips Avanzados

### 1. Usar tu propio Logo

Sube tu logo en: **Apariencia → Personalizar → Identidad del Sitio**

### 2. Cambiar Favicon

Sube en: **Apariencia → Personalizar → Identidad del Sitio → Icono del Sitio**

### 3. Widgets Personalizados

Crea áreas de widgets en `functions.php`:

```php
function shopper_modern_widgets_init() {
    register_sidebar(array(
        'name'          => 'Sidebar Personalizado',
        'id'            => 'custom-sidebar',
        'before_widget' => '<div class="widget">',
        'after_widget'  => '</div>',
    ));
}
add_action('widgets_init', 'shopper_modern_widgets_init');
```

### 4. CSS Personalizado Adicional

Crea `custom.css` en `/assets/css/` y añade en `functions.php`:

```php
wp_enqueue_style(
    'shopper-modern-custom',
    get_stylesheet_directory_uri() . '/assets/css/custom.css',
    array('shopper-modern-style'),
    wp_get_theme()->get('Version')
);
```

---

## 📞 Soporte

Si necesitas ayuda:
- Email: pablaontoniodel@gmail.com
- GitHub: https://github.com/pabloantoniodel

---

## 🎨 Inspiración de Diseño

**Sitios de referencia para estilos:**
- https://dribbble.com/tags/ecommerce
- https://www.awwwards.com/websites/e-commerce/
- https://www.pinterest.com/search/pins/?q=modern%20ecommerce

**Generadores de Paletas:**
- https://coolors.co/
- https://colorhunt.co/
- https://mycolor.space/

---

¡Que disfrutes personalizando tu tema! 🚀



