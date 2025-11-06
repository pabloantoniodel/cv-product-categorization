# 🎨 Generar Imágenes de Categorías con IA

## 📋 Resumen
Necesitas generar imágenes para **204 categorías** sin thumbnail.

## 🎯 Opción 1: Leonardo.AI (RECOMENDADO - GRATIS)

### Paso 1: Regístrate
1. Ve a https://leonardo.ai/
2. Crea cuenta gratis (150 créditos diarios)
3. Cada imagen cuesta ~1 crédito

### Paso 2: Configuración
- **Modelo**: Leonardo Diffusion XL
- **Tamaño**: SQUARE (1:1) - 512x512 px
- **Estilo**: Photorealistic
- **Quality**: High

> **Nota**: Las imágenes se auto-redimensionarán a 300x300 al subirlas a WordPress

### Paso 3: Usar los Prompts
El archivo `category-image-prompts.json` contiene todos los prompts.

**Top 30 categorías por productos:**
1. **Peluquería** (43 productos): _"Professional hair salon interior, modern styling chairs, mirrors, hair products, bright and clean, photorealistic"_
2. **Moda** (35 productos): _"Fashion boutique interior, clothing racks with trendy clothes, mannequins, modern retail store, photorealistic"_
3. **Telefonia** (24 productos): _"Modern mobile phone store, smartphones display, latest technology devices, clean retail environment, photorealistic"_
4. **Alcohol** (14 productos): _"Premium liquor store shelf, bottles of wine and spirits, elegant display, warm lighting, photorealistic"_
5. **Mujer** (14 productos): _"Women fashion store, elegant dresses and accessories, modern boutique interior, photorealistic"_
6. **Pasteleria** (13 productos): _"Bakery display with delicious pastries, cakes, desserts, warm inviting atmosphere, photorealistic"_
7. **RECORDATORIOS** (10 productos): _"Gift shop with souvenirs, keepsakes, decorative items, colorful display, photorealistic"_
8. **Carne** (9 productos): _"Butcher shop display, fresh meat cuts, professional meat counter, clean environment, photorealistic"_
9. **Tailandeses** (9 productos): _"Thai massage spa interior, relaxing atmosphere, massage beds, zen decoration, photorealistic"_
10. **Zapatos** (9 productos): _"Shoe store interior, shelves with various footwear, modern retail display, photorealistic"_

... y 194 más en el archivo JSON.

### Paso 4: Descargar y Organizar
- Descarga las imágenes generadas
- Renómbralas según el `filename` del JSON
- Guárdalas en: `wp-content/uploads/category-images/`

Ejemplo:
```
peluqueria.jpg
moda.jpg
telefonia.jpg
alcohol.jpg
...
```

### Paso 5: Subir a WordPress
```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/cv-front
php upload-category-images.php
```

---

## 🎯 Opción 2: Generar con Gradientes (Automático, Sin IA)

Si prefieres imágenes con gradientes de colores y texto:

```bash
cd /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/cv-front
php generate-category-images.php
```

Esto generará imágenes con:
- Gradientes de colores vibrantes
- Nombre de la categoría en texto grande
- Estilo moderno y limpio

---

## 🎯 Opción 3: Otras IAs

### DALL-E 3 (OpenAI)
- Costo: ~$0.04 por imagen
- Calidad: Excelente
- https://platform.openai.com/

### Midjourney
- Costo: $10/mes (plan básico)
- Calidad: Excelente
- https://www.midjourney.com/

### Stable Diffusion (Local)
- Gratuito
- Requiere GPU potente
- https://stability.ai/

---

## 📊 Estadísticas

- **Total categorías sin imagen**: 204
- **Top 30 categorías**: 364 productos (prioridad alta)
- **Tamaño de generación**: 512x512 (SQUARE 1:1)
- **Tamaño final en WordPress**: 300x300 px (auto-redimensionado)
- **Formato**: JPG (calidad 90%)

---

## ✅ Checklist

- [ ] Generar prompts: `php generate-ai-prompts.php`
- [ ] Generar imágenes con IA (Leonardo.AI)
- [ ] Descargar y renombrar imágenes
- [ ] Copiar a `wp-content/uploads/category-images/`
- [ ] Subir a WordPress: `php upload-category-images.php`
- [ ] Verificar en admin: `/wp-admin/edit-tags.php?taxonomy=product_cat`

---

## 🎨 Tips para mejores resultados

1. **Añade "photorealistic, 4k, professional photography" al final de cada prompt**
2. **Evita texto en las imágenes** (Leonardo a veces genera mal el texto)
3. **Usa SQUARE (1:1)** - Las categorías se ven mejor con imágenes cuadradas
4. **Genera 2-4 variantes** y escoge la mejor
5. **Mantén estilo consistente** en todas las categorías
6. **El script auto-redimensiona** a 300x300, así que genera en 512x512 o mayor

---

## 📝 Ver el JSON completo

```bash
cat /home/ciudadvirtual/htdocs/ciudadvirtual.store/wp-content/plugins/cv-front/category-image-prompts.json
```

O ábrelo en cualquier editor JSON.

---

¡Buena suerte! 🚀

