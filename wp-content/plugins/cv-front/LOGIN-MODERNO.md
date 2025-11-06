# 🎨 Login Moderno de WooCommerce

Sistema de diseño moderno y elegante para las páginas de login y registro de WooCommerce.

## 📋 Características

### ✨ Diseño Visual

- **Gradiente moderno**: Fondo con degradado de colores personalizable (púrpura a violeta por defecto)
- **Animaciones suaves**: Transiciones fluidas en todos los elementos
- **Diseño responsivo**: Perfectamente adaptado a móviles y tablets
- **Efectos de profundidad**: Sombras sutiles que dan sensación de profundidad
- **Fondo animado**: Patrón de puntos con animación continua

### 🎯 Componentes

1. **Cabecera de Marca**
   - Logo del sitio (si está configurado)
   - Nombre del sitio
   - Tagline/descripción

2. **Formularios Lado a Lado**
   - Login a la izquierda
   - Registro a la derecha
   - Iconos identificativos (🔐 y ✨)

3. **Campos de Entrada Mejorados**
   - Bordes redondeados
   - Fondo sutil
   - Efectos de foco con anillo de color
   - Transiciones suaves

4. **Botones Atractivos**
   - Gradiente de color
   - Efecto hover con elevación
   - Sombra dinámica
   - Animación al hacer clic

5. **Mensajes Visuales**
   - Errores en rojo suave
   - Éxitos en verde
   - Información en azul
   - Animación de aparición

## 🎨 Personalización

### Variables CSS

Puedes personalizar los colores editando las variables en `modern-login.css`:

```css
:root {
    --cv-primary: #667eea;        /* Color principal */
    --cv-primary-dark: #5568d3;   /* Color principal oscuro */
    --cv-secondary: #764ba2;      /* Color secundario */
    --cv-success: #10b981;        /* Color de éxito */
    --cv-danger: #ef4444;         /* Color de error */
    --cv-text: #1f2937;           /* Color de texto */
    --cv-text-light: #6b7280;     /* Color de texto claro */
    --cv-border: #e5e7eb;         /* Color de bordes */
    --cv-bg: #f9fafb;             /* Color de fondo */
    --cv-white: #ffffff;          /* Blanco */
    --cv-radius: 16px;            /* Radio de bordes */
}
```

### Ejemplos de Combinaciones de Colores

#### Azul Corporativo
```css
--cv-primary: #2563eb;
--cv-secondary: #1d4ed8;
```

#### Verde Naturaleza
```css
--cv-primary: #059669;
--cv-secondary: #047857;
```

#### Naranja Energético
```css
--cv-primary: #f59e0b;
--cv-secondary: #d97706;
```

#### Rosa Moderno
```css
--cv-primary: #ec4899;
--cv-secondary: #db2777;
```

## 📱 Responsive

El diseño se adapta automáticamente:

- **Desktop** (>768px): Formularios lado a lado
- **Tablet/Móvil** (<768px): Formularios apilados verticalmente

## 🔧 Compatibilidad

- ✅ WooCommerce 5.0+
- ✅ WordPress 5.0+
- ✅ Todos los navegadores modernos
- ✅ Compatible con temas de WordPress

## 🚀 Activación

El sistema se activa automáticamente al activar el plugin **CV Front**.

No requiere configuración adicional, pero puedes personalizar los colores editando el archivo CSS.

## 📍 Archivos del Sistema

```
wp-content/plugins/cv-front/
├── includes/
│   └── class-cv-modern-login.php    # Clase principal
└── assets/
    └── css/
        └── modern-login.css          # Estilos
```

## 🎯 Páginas Afectadas

- `/mi-cuenta/` - Página de Mi Cuenta de WooCommerce
- Formularios de login
- Formularios de registro
- Navegación del dashboard de usuario

## 💡 Notas Técnicas

- Los estilos solo se cargan en la página de mi cuenta (`is_account_page()`)
- Usa hooks de WooCommerce para inyectar HTML adicional
- No modifica funcionalidad, solo apariencia
- Compatible con otros plugins de WooCommerce

## 🔄 Actualización del Plugin

La versión del plugin se actualizó de 1.0.0 a 1.1.0 para incluir esta funcionalidad.

---

**Creado por**: Ciudad Virtual  
**Versión**: 1.1.0  
**Fecha**: Octubre 2025

