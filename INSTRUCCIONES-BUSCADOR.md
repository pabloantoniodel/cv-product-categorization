# 🔍 Instrucciones: Buscador de Categorías

## 🐛 Problema Identificado

El buscador no aparecía porque tu sitio usa el **modo CHECKLIST** para las categorías, pero el plugin solo estaba registrado para el **modo SELECT**.

---

## ✅ Solución Aplicada

He actualizado el plugin para que funcione en **AMBOS MODOS**:

1. **Modo SELECT** → Hook: `before_wcfm_products_manage_taxonomies`
2. **Modo CHECKLIST** → Hook: `after_wcfm_products_manage_pricing_fields`

---

## 📋 Pasos para Verificar

### 1. Limpiar la caché del navegador

**Chrome/Firefox:**
```
Ctrl + Shift + R (Windows/Linux)
Cmd + Shift + R (Mac)
```

O:
```
Ctrl + Shift + Delete → Borrar todo
```

### 2. Ir a la página de productos

Ve a: `https://ciudadvirtual.app/store-manager/products-manage/`

O en el menú WCFM: **Store Manager → Productos**

### 3. Buscar el buscador

Deberías ver un **cuadro azul/morado** justo **ANTES** de la lista de categorías:

```
┌────────────────────────────────────────────┐
│  🔍 Buscador rápido de categorías          │ ← AQUÍ
│  ┌──────────────────────────────────────┐  │
│  │ Escribe para buscar...             🔎│  │
│  └──────────────────────────────────────┘  │
│                                             │
│  💡 Escribe al menos 2 caracteres...       │
└────────────────────────────────────────────┘

Categorías  ← Debajo está el checklist normal
□ ACADEMIA
  □ Curso Ingles
  □ FORMACION
...
```

---

## 🧪 Test de Diagnóstico

### Paso 1: Activar modo debug

Ve a: `https://ciudadvirtual.app/wp-admin/?cv_debug_search=1`

Deberías ver un **aviso azul** con información de diagnóstico:

```
🔍 Diagnóstico: Buscador de Categorías

Modo de categorías:    CHECKLIST
Clase cargada:         ✅ SÍ
Hooks registrados:     Prioridad 5: CV_Category_Search::add_category_search_box_checklist
Archivo existe:        ✅ SÍ (13 KB)
Usuario actual:        tu_usuario (administrator)
```

### Paso 2: Revisar la consola del navegador

1. Presiona **F12** (o clic derecho → Inspeccionar)
2. Ve a la pestaña **Console**
3. Busca mensajes que digan:

```javascript
[CV Category Search] Debug Info
Plugin cargado: true
Modo: CHECKLIST
Usuario: tu_usuario
✅ Buscador encontrado en el DOM
```

Si ves **❌ Buscador NO encontrado**, sigue leyendo...

---

## 🔧 Soluciones si NO aparece

### Solución 1: Verificar que el archivo existe

```bash
ls -lh /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php
```

Debería mostrar: `-rw-rw-r-- 1 root root 13K Nov 5 ...`

### Solución 2: Verificar sintaxis PHP

```bash
php -l /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php
```

Debería decir: `No syntax errors detected`

### Solución 3: Verificar que está en mu-plugins

Los archivos en `mu-plugins/` se cargan automáticamente. NO hace falta activarlos.

Verifica la carpeta:
```bash
ls /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-*
```

Deberías ver:
```
cv-anti-spam-protection.php
cv-category-search.php
cv-category-search-debug.php
```

### Solución 4: Limpiar caché de WordPress

Si usas algún plugin de caché:

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp cache flush --allow-root
```

O desde el admin: **Plugins → Tu plugin de caché → Limpiar caché**

### Solución 5: Verificar permisos

```bash
chown -R www-data:www-data /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php
chmod 644 /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php
```

---

## 🎯 Probar la Funcionalidad

Una vez que veas el buscador:

### Test 1: Buscar "comida"
```
Entrada: "comida"
Esperado:
- 📂 Comida
- 📁 Bocadillos (Comida → Bocadillos)
- 📁 Carne (Comida → Carne)
- ...
```

### Test 2: Buscar subcategoría
```
Entrada: "curso"
Esperado:
- 📁 Curso Ingles (ACADEMIA → Curso Ingles)
- 📁 Curso idiomas (ACADEMIA → Curso idiomas)
- 📁 CURSOS (ACADEMIA → FORMACION → CURSOS)
```

### Test 3: Seleccionar categoría
```
1. Busca "hamburguesa"
2. Click en el resultado
3. Debería aparecer un badge verde: "✓ Seleccionada"
4. El checkbox en la lista debería marcarse automáticamente
```

---

## 📞 ¿Sigue sin funcionar?

### Envíame esta información:

1. **Screenshot** de `/store-manager/products-manage/`
2. **Console output** (F12 → Console, copia todo)
3. **Resultado del diagnóstico** (ve a `/?cv_debug_search=1`)
4. **Salida de este comando:**

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp eval "
echo 'Modo: ' . (apply_filters('wcfm_is_category_checklist', true) ? 'CHECKLIST' : 'SELECT') . PHP_EOL;
echo 'Clase existe: ' . (class_exists('CV_Category_Search') ? 'SÍ' : 'NO') . PHP_EOL;
echo 'Archivo existe: ' . (file_exists(WPMU_PLUGIN_DIR . '/cv-category-search.php') ? 'SÍ' : 'NO') . PHP_EOL;
" --allow-root
```

---

## 🗑️ Eliminar archivos de debug

Una vez que funcione, elimina:

```bash
rm /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search-debug.php
rm /home/ciudadvirtual/htdocs/ciudadvirtual.store/INSTRUCCIONES-BUSCADOR.md
```

---

**Última actualización:** 5 de noviembre de 2025  
**Autor:** Ciudad Virtual

