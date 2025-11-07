# 🗄️ Sistema de Backup CiudadVirtual

Sistema completo de backup automático para el sitio web CiudadVirtual, incluyendo archivos y base de datos MySQL.

## 📋 Características

- ✅ **Backup completo**: Archivos del sitio + Base de datos MySQL
- ✅ **Backup individual**: Archivos y base de datos por separado
- ✅ **Compresión automática**: Archivos optimizados para descarga
- ✅ **Ejecución en segundo plano**: No interrumpe el funcionamiento del sitio
- ✅ **Limpieza automática**: Elimina backups antiguos automáticamente
- ✅ **Interfaz web**: Descarga fácil desde el navegador
- ✅ **Logs detallados**: Seguimiento completo de todas las operaciones
- ✅ **Programación automática**: Backups diarios y semanales

## 🚀 Uso Rápido

### Iniciar Backup Manual
```bash
# Iniciar backup en segundo plano
./run_backup.sh start

# Ver estado del backup
./run_backup.sh status

# Ver logs en tiempo real
./run_backup.sh logs

# Listar backups disponibles
./run_backup.sh list
```

### Descargar Backups
```
🌐 Interfaz web: https://ciudadvirtual.app/download_backup.php
```

## 📁 Estructura de Archivos

```
/home/ciudadvirtual/htdocs/ciudadvirtual.store/
├── backup_script.sh          # Script principal de backup
├── run_backup.sh             # Controlador de backups
├── download_backup.php       # Interfaz web de descarga
├── setup_backup.sh           # Configuración inicial
├── monitor_backup.sh         # Monitoreo del sistema
└── BACKUP_README.md          # Este archivo

/home/ciudadvirtual/backups/
├── ciudadvirtual_backup_YYYYMMDD_HHMMSS.tar.gz    # Backup completo
├── ciudadvirtual_db_YYYYMMDD_HHMMSS.sql.gz        # Solo base de datos
├── ciudadvirtual_files_YYYYMMDD_HHMMSS.tar.gz     # Solo archivos
├── backup.log                                      # Logs del sistema
└── backup.pid                                      # PID del proceso activo
```

## 🔧 Comandos Disponibles

### Control de Backups
```bash
# Iniciar backup
./run_backup.sh start

# Detener backup en ejecución
./run_backup.sh stop

# Ver estado actual
./run_backup.sh status

# Ver logs recientes
./run_backup.sh logs

# Listar todos los backups
./run_backup.sh list
```

### Monitoreo del Sistema
```bash
# Reporte completo del sistema
./monitor_backup.sh
```

## ⏰ Programación Automática

El sistema está configurado con las siguientes tareas automáticas:

- **Backup diario**: Todos los días a las 2:00 AM
- **Backup semanal completo**: Domingos a las 3:00 AM  
- **Limpieza automática**: Lunes a las 4:00 AM (elimina backups > 30 días)

## 📊 Tipos de Backup

### 1. Backup Completo (`ciudadvirtual_backup_*.tar.gz`)
- ✅ Todos los archivos del sitio web
- ✅ Base de datos MySQL completa
- ✅ Archivo de información del backup
- ✅ Listo para restauración completa

### 2. Backup de Base de Datos (`ciudadvirtual_db_*.sql.gz`)
- ✅ Solo la base de datos MySQL
- ✅ Incluye estructura y datos
- ✅ Comprimido para descarga rápida

### 3. Backup de Archivos (`ciudadvirtual_files_*.tar.gz`)
- ✅ Solo archivos del sitio web
- ✅ Excluye archivos temporales y logs
- ✅ Optimizado para transferencia

## 🔒 Seguridad

- ✅ Archivos de backup protegidos con permisos restrictivos
- ✅ Validación de archivos en descargas web
- ✅ Límite de tamaño para descargas (500MB)
- ✅ Sanitización de nombres de archivos
- ✅ Logs de todas las operaciones

## 📈 Monitoreo

### Ver Estado en Tiempo Real
```bash
# Estado del backup actual
./run_backup.sh status

# Logs detallados
./run_backup.sh logs

# Reporte completo del sistema
./monitor_backup.sh
```

### Interfaz Web
- **URL**: `https://ciudadvirtual.app/download_backup.php`
- **Funciones**: Listar, descargar y gestionar backups
- **Seguridad**: Validación de archivos y límites de descarga

## 🛠️ Configuración Técnica

### Base de Datos
- **Host**: 127.0.0.1:3306
- **Usuario**: root
- **Base de datos**: ciudadvirtual
- **Método**: mysqldump con transacciones

### Directorio del Sitio
- **Ruta**: `/home/ciudadvirtual/htdocs/ciudadvirtual.store`
- **Exclusiones**: logs, cache, archivos temporales

### Compresión
- **Método**: gzip
- **Formato**: .tar.gz para archivos, .sql.gz para BD
- **Optimización**: Máxima compresión

## 🔄 Restauración

### Restaurar Backup Completo
1. Descargar `ciudadvirtual_backup_*.tar.gz`
2. Extraer el archivo
3. Copiar archivos al directorio web
4. Importar `database_backup.sql` a MySQL

### Restaurar Solo Base de Datos
1. Descargar `ciudadvirtual_db_*.sql.gz`
2. Descomprimir: `gunzip ciudadvirtual_db_*.sql.gz`
3. Importar: `mysql -u root -p ciudadvirtual < *.sql`

### Restaurar Solo Archivos
1. Descargar `ciudadvirtual_files_*.tar.gz`
2. Extraer: `tar -xzf ciudadvirtual_files_*.tar.gz`
3. Copiar archivos al directorio web

## 📞 Soporte

### Logs del Sistema
```bash
# Ver logs completos
tail -f /home/ciudadvirtual/backups/backup.log

# Ver logs de errores
grep -i error /home/ciudadvirtual/backups/backup.log
```

### Verificar Espacio en Disco
```bash
# Espacio disponible
df -h /home/ciudadvirtual/backups

# Tamaño de backups
du -sh /home/ciudadvirtual/backups/*
```

### Reiniciar Sistema de Backup
```bash
# Detener backup actual
./run_backup.sh stop

# Iniciar nuevo backup
./run_backup.sh start
```

## ✅ Estado del Sistema

- 🟢 **Sistema configurado**: Backups automáticos activos
- 🟢 **Directorio creado**: `/home/ciudadvirtual/backups`
- 🟢 **Permisos configurados**: Scripts ejecutables
- 🟢 **Cron jobs instalados**: Programación automática
- 🟢 **Interfaz web activa**: Descarga desde navegador
- 🟢 **Monitoreo disponible**: Scripts de seguimiento

---

**Sistema de Backup CiudadVirtual** - Configurado y listo para usar 🚀


