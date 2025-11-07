# 🔍 DEBUG - Buscador de Categorías v1.0.5

## 📊 Mejoras en esta versión

### ✅ Case-Insensitive
- "COMIDA" = "comida" = "CoMiDa"
- Usa `toLowerCase()` en la búsqueda

### ✅ Carga Mejorada
- Espera a que WCFM cargue las categorías
- Verifica cada 100ms si existen
- Reintenta si no se cargan

### ✅ Debug Completo
- Mensajes claros en consola
- Muestra qué categorías se cargaron
- Indica si algo falla

---

## 🧪 CÓMO PROBAR CON DEBUG

### Paso 1: Abre la consola
```
F12 → Pestaña "Console"
```

### Paso 2: Ve a la página
```
https://ciudadvirtual.app/store-manager/products-manage/
```

### Paso 3: Mira los mensajes en consola

Deberías ver (en orden):

```javascript
✅ Selector de categorías encontrado
✅ CV Category Search: 347 categorías cargadas
📋 Primeras categorías: ["ACADEMIA", "Alimentacion", "AYUDAS", "BEBE", "BEBIDAS"]
```

**Si ves eso:** ✅ Las categorías se cargaron correctamente

**Si ves esto:**
```
⚠️ No se cargaron categorías. Reintentando en 1s...
```
→ Espera 1 segundo más

**Si ves esto:**
```
❌ No se pudieron cargar las categorías después de 10s
```
→ Hay un problema, lee la sección "Problemas Comunes" abajo

---

### Paso 4: Escribe en el buscador

```
Escribe: "co"
```

Deberías ver en consola:

```javascript
📝 Escrito: "c" (length: 1)
📝 Escrito: "co" (length: 2)
🔍 Búsqueda automática con: "co" en 347 categorías
✅ Mostrando 15 resultados
```

**Si ves eso:** ✅ El buscador funciona

**Si NO ves nada:** Lee "Problemas Comunes" abajo

---

### Paso 5: Verifica que aparece la lista

Debajo del input deberían aparecer resultados como:

```
📂 Comida
📁 Cojines (HOGAR → Cojines)
📁 Colonia (Perfumes → Colonia)
...
```

---

## 🔍 Verificaciones Manuales

### Verificación 1: ¿Existen las categorías?

En consola (F12), ejecuta:

```javascript
// Copiar y pegar esto:
console.log('Total categorías:', allCategories.length);
console.log('Primeras 10:', allCategories.slice(0, 10).map(c => c.name));
```

**Resultado esperado:**
```
Total categorías: 347
Primeras 10: ["ACADEMIA", "Alimentacion", ...]
```

**Si sale `undefined` o `0`:** Las categorías no se cargaron

---

### Verificación 2: ¿El evento se dispara?

En consola, ejecuta:

```javascript
$('#cv-category-search').on('input', function() {
    console.log('✅ Evento input disparado');
});
```

Luego escribe en el buscador.

**Resultado esperado:**
```
✅ Evento input disparado
```

**Si NO aparece:** El evento no está funcionando

---

### Verificación 3: ¿El selector existe?

En consola:

```javascript
console.log('Input existe:', $('#cv-category-search').length);
console.log('Checklist existe:', $('#product_cats_checklist').length);
console.log('Select existe:', $('#product_cats').length);
```

**Resultado esperado:**
```
Input existe: 1
Checklist existe: 1  (o Select existe: 1)
```

---

## ❌ Problemas Comunes

### Problema 1: "No hay categorías cargadas todavía"

**Causas posibles:**
1. WCFM no ha terminado de cargar
2. El selector no existe
3. Los selectores tienen nombres diferentes

**Solución:**

En consola ejecuta:
```javascript
// Buscar todos los selectores de categorías
$('[id*="cat"]').each(function() {
    console.log('Selector encontrado:', this.id, $(this).length);
});
```

---

### Problema 2: No aparecen resultados al escribir

**Causa:** La búsqueda funciona pero no se muestran

**Debug:**

En consola:
```javascript
$('#cv-category-search').val('co');
$('#cv-category-search').trigger('input');
```

Mira si aparecen los mensajes de debug.

---

### Problema 3: La lista está vacía

**Causa:** El filtro no encuentra coincidencias

**Debug:**

```javascript
var search = 'co';
var matches = allCategories.filter(function(cat) {
    return cat.name.toLowerCase().indexOf(search) !== -1;
});
console.log('Matches:', matches.length, matches.slice(0, 5));
```

---

## 🎯 ¿Qué debe pasar?

### Comportamiento correcto:

1. **Cargas la página**
   ```
   Console: ✅ Selector encontrado
   Console: ✅ 347 categorías cargadas
   ```

2. **Escribes "c"**
   ```
   Console: 📝 Escrito: "c" (length: 1)
   Mensaje: ⌨️ Escribe 1 carácter más...
   ```

3. **Escribes "co"**
   ```
   Console: 📝 Escrito: "co" (length: 2)
   Console: 🔍 Búsqueda automática con: "co"
   Console: ✅ Mostrando 15 resultados
   Lista: Comida, Cojines, Colonia, etc.
   ```

4. **Haces CLICK en un resultado**
   ```
   Se marca el checkbox
   ```

5. **Presionas ENTER**
   ```
   Console: ❌ ENTER bloqueado
   NO pasa nada
   ```

---

## 📝 Checklist de Debug

Marca cada uno mientras pruebas:

- [ ] Abrí F12 → Console
- [ ] Estoy en `/store-manager/products-manage/`
- [ ] Veo: `✅ Selector de categorías encontrado`
- [ ] Veo: `✅ CV Category Search: XXX categorías cargadas`
- [ ] Veo las primeras categorías en la lista
- [ ] Escribo "co" en el buscador
- [ ] Veo: `📝 Escrito: "co"`
- [ ] Veo: `🔍 Búsqueda automática con: "co"`
- [ ] Veo: `✅ Mostrando X resultados`
- [ ] **Aparece la lista con resultados debajo**
- [ ] Hago click en un resultado
- [ ] Se marca el checkbox
- [ ] Presiono ENTER
- [ ] Veo: `❌ ENTER bloqueado`
- [ ] NO se guarda el producto

**Si todos están marcados:** ✅ ¡Funciona perfectamente!

---

## 🆘 Si NADA funciona

### Último recurso:

```bash
# 1. Verificar archivo
ls -lh /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php

# 2. Verificar versión
grep "Version: 1.0.5" /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/mu-plugins/cv-category-search.php

# 3. Limpiar todo
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store
wp cache flush --allow-root

# 4. En navegador
Ctrl + Shift + Delete → Borrar TODO
Cerrar navegador completamente
Abrir de nuevo
```

---

## 📞 Información para Reportar

Si sigue sin funcionar, copia y pega esto desde la consola:

```javascript
console.log('=== DEBUG INFO ===');
console.log('Input existe:', $('#cv-category-search').length);
console.log('Total categorías:', typeof allCategories !== 'undefined' ? allCategories.length : 'undefined');
console.log('Checklist:', $('#product_cats_checklist').length);
console.log('Select:', $('#product_cats').length);
console.log('jQuery version:', $.fn.jquery);
console.log('==================');
```

Envíame el resultado completo de la consola.

---

**Versión:** 1.0.5  
**Fecha:** 5 de noviembre de 2025  
**Mejoras:** Carga mejorada + Debug completo + Case-insensitive confirmado

