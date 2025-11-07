# PLAN DE LIMPIEZA Y REORGANIZACIÓN DE CATEGORÍAS

## 📊 SITUACIÓN ACTUAL

### Categorías NUEVAS (correctas) - A MANTENER:
- **Alimentación y Restauración** (746)
- **Bebé e Infantil** (754)
- **Belleza y Estética** (748)
- **Deportes y Ocio** (757)
- **Ferretería y Bricolaje** (758)
- **Flores y Eventos** (756)
- **Hogar y Decoración** (749)
- **Inmobiliaria** (745) + sus 12 subcategorías
- **Moda y Calzado** (747)
- **Mascotas** (755)
- **Otros Productos y Servicios** (759)
- **Salud y Bienestar** (753)
- **Servicios Profesionales** (752)
- **Tecnología e Informática** (750)
- **Vehículos y Motor** (751)

### Categorías ANTIGUAS (a eliminar):
- ACADEMIA (585)
- ALARMAS (704)
- AYUDAS (606)
- BEBE (520)
- Belleza (216)
- Calzado (260)
- Comida (211)
- DEPORTES (674)
- Desconocido (672)
- Flores (312)
- HOGAR (544)
- Joyas (317)
- Lenceria (320)
- MASAJES (581)
- Moda (214)
- MOVILES (527)
- PELUQUERIA (689)
- Perfumes (473)
- PLANTAS (692)
- PLAYA (512)
- RECORDATORIOS (519)
- REPORTAJES (479)
- Rotulación (711)
- Salud (744)
- SERVICIOS (667)
- TAROT (593)
- TATTOO (576)
- Telefonia (90)
- VEHICULOS (449)

## 🎯 PLAN DE ACCIÓN

### FASE 1: BACKUP Y ANÁLISIS
1. ✅ Crear backup completo de la base de datos
2. Identificar productos que SOLO tienen categorías antiguas
3. Identificar productos que tienen MIX de categorías (antiguas + nuevas)
4. Generar reporte detallado

### FASE 2: REASIGNACIÓN AUTOMÁTICA
1. Para productos SIN categorías nuevas:
   - Analizar título + descripción corta
   - Asignar automáticamente a categorías nuevas usando IA/keywords
   - Mantener log de cambios

2. Para productos CON categorías nuevas:
   - Eliminar solo las categorías antiguas
   - Mantener las nuevas intactas

### FASE 3: LIMPIEZA DE CATEGORÍAS ANTIGUAS
1. Verificar que ningún producto tenga SOLO categorías antiguas
2. Eliminar las categorías antiguas de la base de datos
3. Limpiar metadatos huérfanos

### FASE 4: VERIFICACIÓN
1. Verificar que todos los productos tengan al menos 1 categoría
2. Generar reporte final
3. Recalcular contadores de términos

## 📋 COMANDOS A EJECUTAR

### 1. Backup
```bash
wp db export /home/ciudadvirtual/backups/pre-category-cleanup-$(date +%Y%m%d-%H%M%S).sql --allow-root
```

### 2. Análisis
```bash
# Productos SOLO con categorías antiguas
# Productos con MIX de categorías
# Productos sin categorías
```

### 3. Reasignación
```bash
# Script PHP personalizado
```

### 4. Limpieza
```bash
# Eliminar categorías antiguas
# Limpiar relaciones huérfanas
```

### 5. Verificación
```bash
wp term recount product_cat --allow-root
```

## ⚠️ RIESGOS Y MITIGACIONES

### Riesgo 1: Productos sin categorías
- **Mitigación**: Asignar a "Otros Productos y Servicios" (759) por defecto

### Riesgo 2: Pérdida de información
- **Mitigación**: Backup completo antes de empezar + log detallado

### Riesgo 3: Categorización incorrecta
- **Mitigación**: Modo prueba primero + revisión manual de casos dudosos

## 📊 MÉTRICAS A MONITOREAR

- Total de productos procesados
- Productos reasignados automáticamente
- Productos que necesitan revisión manual
- Categorías eliminadas
- Productos sin categorías (debe ser 0)

## 🚀 PRÓXIMOS PASOS

1. Aprobar el plan
2. Crear backup
3. Ejecutar análisis
4. Revisar reporte
5. Ejecutar reasignación en modo prueba
6. Revisar resultados
7. Ejecutar reasignación en producción
8. Limpiar categorías antiguas
9. Verificar y recalcular

