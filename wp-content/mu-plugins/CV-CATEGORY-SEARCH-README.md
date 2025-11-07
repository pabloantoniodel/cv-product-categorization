# 🔍 Buscador de Categorías para Vendedores

Plugin que añade un buscador inteligente de categorías/subcategorías en la gestión de productos WCFM.

---

## 📋 Características

### ✅ Funcionalidades principales

1. **Búsqueda en tiempo real**
   - Filtra categorías mientras escribes
   - Mínimo 2 caracteres para iniciar la búsqueda
   - Busca tanto en nombres como en rutas jerárquicas

2. **Interfaz intuitiva**
   - Diseño moderno con gradiente
   - Iconos visuales (📂 categoría principal, 📁 subcategoría)
   - Indicadores de estado (seleccionada/no seleccionada)

3. **Feedback visual**
   - Resalta las categorías ya seleccionadas
   - Muestra la ruta completa jerárquica
   - Notificaciones al seleccionar/deseleccionar
   - Contador de resultados

4. **Optimizado para vendedores**
   - Solo visible para roles de vendedor
   - Los administradores no lo ven (no lo necesitan)
   - Compatible con formato checklist y select

---

## 🎨 Diseño

```
┌─────────────────────────────────────────────┐
│  🔍 Buscador rápido de categorías           │
│  ┌───────────────────────────────────────┐  │
│  │ 🔍 Escribe para buscar...          🔎│  │
│  └───────────────────────────────────────┘  │
│                                              │
│  ┌─────────────────────────────────────────┐│
│  │ 📂 BEBIDAS                      [✓]     ││
│  │    BEBIDAS                              ││
│  ├─────────────────────────────────────────┤│
│  │ 📁 Alcohol                              ││
│  │    BEBIDAS → Alcohol                    ││
│  └─────────────────────────────────────────┘│
│                                              │
│  💡 2 categorías encontradas                │
└─────────────────────────────────────────────┘
```

---

## 🔧 Funcionamiento Técnico

### 1. **Hooks de WordPress**

```php
add_action('wcfm_products_manage_form_load_scripts', 'enqueue_category_search_script');
add_action('before_wcfm_products_manage_taxonomies', 'add_category_search_box');
```

### 2. **Detección de categorías**

El plugin escanea automáticamente:
- `#product_cats` (select múltiple)
- `#product_cats_checklist` (checklist)

Construye un mapa con:
```javascript
{
    id: "585",
    name: "ACADEMIA",
    path: "ACADEMIA",
    element: <jQuery object>
}
```

Para subcategorías:
```javascript
{
    id: "587",
    name: "Curso idiomas",
    path: "ACADEMIA → Curso idiomas",
    element: <jQuery object>
}
```

### 3. **Algoritmo de búsqueda**

```javascript
// Filtra por nombre O ruta
var matches = allCategories.filter(function(cat) {
    return cat.name.toLowerCase().indexOf(search) !== -1 || 
           cat.path.toLowerCase().indexOf(search) !== -1;
});
```

### 4. **Selección/Deselección**

Al hacer click en un resultado:
1. Localiza el elemento (checkbox o option)
2. Cambia su estado (checked/selected)
3. Dispara el evento change
4. Actualiza el mapa de categorías
5. Re-ejecuta la búsqueda para actualizar badges

---

## 📊 Ejemplos de Uso

### Ejemplo 1: Buscar "comida"
```
Entrada: "comida"
Resultados:
- 📂 Comida
- 📁 Bocadillos (Comida → Bocadillos)
- 📁 Carne (Comida → Carne)
- 📁 Pizzas (Comida → Pizzas)
... (hasta 20 resultados)
```

### Ejemplo 2: Buscar subcategoría
```
Entrada: "formacion"
Resultados:
- 📁 FORMACION (ACADEMIA → FORMACION)
- 📁 CURSOS (ACADEMIA → FORMACION → CURSOS)
- 📁 LIBROS (ACADEMIA → FORMACION → LIBROS)
```

### Ejemplo 3: Buscar por ruta
```
Entrada: "academia curso"
Resultados:
- 📁 Curso Ingles (ACADEMIA → Curso Ingles)
- 📁 Curso idiomas (ACADEMIA → Curso idiomas)
```

---

## 🎯 Roles Afectados

✅ **Visible para TODOS:**
- `administrator` ✨
- `dc_vendor`
- `seller`
- `wcfm_vendor`
- Cualquier usuario que acceda a `/store-manager/products-manage/`

**Beneficios para administradores:**
- Facilita la selección rápida de categorías
- Útil cuando hay muchas categorías (100+)
- Mejora la eficiencia al crear productos de prueba
- Ayuda a encontrar categorías por nombre parcial

---

## 🚀 Rendimiento

### Optimizaciones

1. **Cache de categorías**
   - Se construye una vez al cargar la página
   - Se actualiza solo cuando se selecciona/deselecciona

2. **Límite de resultados**
   - Máximo 20 resultados simultáneos
   - Evita sobrecargar el DOM

3. **Debouncing implícito**
   - La búsqueda se ejecuta en cada tecla
   - Pero solo renderiza si hay cambios

### Métricas esperadas

- **Tiempo de búsqueda:** < 50ms (hasta 500 categorías)
- **Renderizado:** < 100ms (20 resultados)
- **Uso de memoria:** ~2KB por categoría

---

## 🔍 Casos de Uso Reales

### Caso 1: Vendedor de comida
**Problema:** Tiene que scrollear entre 200+ categorías para encontrar "Hamburguesa"

**Solución:**
```
1. Escribe "hamb"
2. Ve solo "Hamburguesa" (Comida → Hamburguesa)
3. Click para seleccionar
4. ¡Listo!
```

**Antes:** 30 segundos de scroll  
**Después:** 3 segundos

### Caso 2: Vendedor de ropa
**Problema:** No recuerda si "COMUNION" está en Moda o en otra categoría

**Solución:**
```
1. Escribe "comunion"
2. Ve "COMUNION (Moda → COMUNION)"
3. Sabe exactamente dónde está
```

### Caso 3: Vendedor multiproducto
**Problema:** Necesita seleccionar 5 categorías diferentes de distintos niveles

**Solución:**
```
1. Busca "informatica" → Click
2. Busca "moviles" → Click
3. Busca "tablet" → Click
4. Busca "software" → Click
5. Busca "hardware" → Click
```

**Antes:** 2 minutos navegando por árbol  
**Después:** 20 segundos con búsqueda

---

## 📱 Responsive

El buscador es completamente responsive:

```css
/* Móvil */
@media (max-width: 768px) {
    .cv-category-search-container {
        padding: 15px;
    }
    #cv-category-search {
        font-size: 14px;
    }
}

/* Tablet */
@media (min-width: 769px) and (max-width: 1024px) {
    .cv-category-search-container {
        padding: 18px;
    }
}

/* Desktop */
@media (min-width: 1025px) {
    .cv-category-search-container {
        padding: 20px;
    }
}
```

---

## 🐛 Debugging

### Activar logs en consola

Añade esto al navegador:
```javascript
localStorage.setItem('cv-category-search-debug', 'true');
```

Verás:
```
CV Category Search: 347 categorías cargadas
CV Category Search: Buscando "comida"
CV Category Search: 23 resultados encontrados
```

### Verificar carga del plugin

```bash
wp plugin list --field=name,status | grep cv-category-search
```

### Test manual

1. Ir a `/store-manager/products-manage/`
2. Verificar que aparece el buscador arriba de "Categorías"
3. Escribir "test" → Debería mostrar resultados

---

## ⚙️ Configuración

### Cambiar máximo de resultados

En `cv-category-search.php` línea 205:
```php
matches.slice(0, 20)  // Cambiar 20 por el número deseado
```

### Cambiar mínimo de caracteres

En `cv-category-search.php` línea 185:
```php
if (search.length < 2)  // Cambiar 2 por el número deseado
```

### Cambiar colores

En `cv-category-search.php` línea 32:
```php
border: 2px solid #667eea;  // Color del borde
background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);  // Gradiente
```

---

## 🔄 Actualizaciones

### Versión 1.0.2 (5 Nov 2025)
- ✅ **AJAX optimizado con debounce (150ms)**
- ✅ **ENTER ya NO guarda el producto** cuando estás en el buscador
- ✅ **Navegación por teclado:**
  - `ENTER` → Selecciona el primer resultado (o el enfocado)
  - `↓` → Navegar al siguiente resultado
  - `↑` → Navegar al resultado anterior
- ✅ **Autocomplete desactivado** en el input
- ✅ Resaltado amarillo para navegación por teclado
- ✅ Limpia el input automáticamente después de seleccionar

### Versión 1.0.1 (5 Nov 2025)
- ✅ Disponible para administradores también
- ✅ Sin restricciones de rol
- ✅ Compatible con modo checklist

### Versión 1.0.0 (5 Nov 2025)
- ✅ Lanzamiento inicial
- ✅ Búsqueda en tiempo real
- ✅ Soporte checklist y select
- ✅ Interfaz moderna
- ✅ Notificaciones visuales

---

## 📝 Notas Técnicas

### Compatibilidad

- **WordPress:** 5.0+
- **WooCommerce:** 4.0+
- **WCFM:** 6.0+
- **PHP:** 7.4+
- **jQuery:** 1.11+

### Dependencias

- `jQuery` (incluido en WordPress)
- `WCFM` (plugin)
- `FontAwesome` (iconos, opcional)

### Conflictos conocidos

❌ **No funciona con:**
- Plugins de cache agresivos que minifican JavaScript inline
- Temas que sobrescriben completamente WCFM

✅ **Compatible con:**
- WooCommerce Vendors
- Dokan
- YITH Vendors
- Cualquier plugin de categorías personalizadas

---

## 🆘 Soporte

Si el buscador no aparece:

1. **Verificar que eres vendedor:**
   ```php
   echo current_user_can('manage_options') ? 'Admin (no visible)' : 'Vendedor (visible)';
   ```

2. **Verificar que WCFM está activo:**
   ```bash
   wp plugin is-active wc-frontend-manager
   ```

3. **Limpiar cache del navegador:**
   - Ctrl + Shift + R (Chrome/Firefox)
   - Cmd + Shift + R (Mac)

4. **Revisar errores de JavaScript:**
   - F12 → Consola
   - Buscar errores en rojo

---

**Última actualización:** 5 de noviembre de 2025  
**Autor:** Ciudad Virtual  
**Versión:** 1.0.2

