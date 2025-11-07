#!/bin/bash

# Script de Backup Seguro para CiudadVirtual
# Estrategia optimizada para no llenar el disco

# Configuración
SITE_DIR="/home/ciudadvirtual/htdocs/ciudadvirtual.store"
DATE=$(date +%Y%m%d_%H%M%S)

# Configuración de la base de datos
DB_NAME="ciudadvirtual"
DB_USER="root"
DB_PASSWORD="mL8i0WeYvUjwtvBam2pd"
DB_HOST="127.0.0.1"
DB_PORT="3306"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para logging
log_message() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

# Función para verificar espacio disponible
check_space() {
    local required_gb=$1
    local available_gb=$(df / | tail -1 | awk '{print int($4/1024/1024)}')
    
    if [ $available_gb -lt $required_gb ]; then
        log_error "Espacio insuficiente. Necesitas al menos ${required_gb}GB, disponibles: ${available_gb}GB"
        return 1
    fi
    
    log_message "Espacio disponible: ${available_gb}GB"
    return 0
}

# OPCIÓN 1: Backup solo de base de datos (más ligero)
backup_database_only() {
    log_message "=== BACKUP SOLO BASE DE DATOS ==="
    
    # Verificar espacio (solo necesita ~500MB)
    if ! check_space 1; then
        return 1
    fi
    
    OUTPUT_FILE="/tmp/ciudadvirtual_db_${DATE}.sql.gz"
    
    log_message "Exportando base de datos..."
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        "$DB_NAME" | gzip > "$OUTPUT_FILE"
    
    if [ $? -eq 0 ]; then
        SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        log_message "✓ Backup de BD completado: $OUTPUT_FILE"
        log_message "✓ Tamaño: $SIZE"
        echo ""
        echo "📥 Para descargar ejecuta:"
        echo "   scp root@ciudadvirtual.app:$OUTPUT_FILE ."
        echo ""
        echo "⚠️  Este archivo se eliminará automáticamente en /tmp al reiniciar"
        return 0
    else
        log_error "Fallo en backup de base de datos"
        return 1
    fi
}

# OPCIÓN 2: Backup de archivos esenciales (sin uploads/cache)
backup_essentials_only() {
    log_message "=== BACKUP ARCHIVOS ESENCIALES ==="
    
    # Verificar espacio (necesita ~2GB)
    if ! check_space 3; then
        return 1
    fi
    
    OUTPUT_FILE="/tmp/ciudadvirtual_essentials_${DATE}.tar.gz"
    
    log_message "Comprimiendo archivos esenciales..."
    tar -czf "$OUTPUT_FILE" \
        -C "$SITE_DIR" \
        --exclude="wp-content/uploads" \
        --exclude="wp-content/cache" \
        --exclude="wp-content/ewww" \
        --exclude="wp-content/upgrade*" \
        --exclude="*.log" \
        --exclude="*.sql" \
        . 2>/dev/null
    
    if [ $? -eq 0 ]; then
        SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        log_message "✓ Backup de archivos completado: $OUTPUT_FILE"
        log_message "✓ Tamaño: $SIZE"
        echo ""
        echo "📥 Para descargar ejecuta:"
        echo "   scp root@ciudadvirtual.app:$OUTPUT_FILE ."
        echo ""
        echo "⚠️  Este archivo se eliminará automáticamente en /tmp al reiniciar"
        return 0
    else
        log_error "Fallo en backup de archivos"
        return 1
    fi
}

# OPCIÓN 3: Backup directo por streaming (sin almacenar en disco)
backup_stream_database() {
    log_message "=== BACKUP STREAMING (BASE DE DATOS) ==="
    
    OUTPUT_FILE="/tmp/ciudadvirtual_stream_${DATE}.sql.gz"
    
    log_message "Generando backup en streaming..."
    log_message "Este archivo se puede descargar directamente sin ocupar espacio permanente"
    
    mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction \
        --quick \
        --lock-tables=false \
        "$DB_NAME" | gzip > "$OUTPUT_FILE"
    
    if [ $? -eq 0 ]; then
        SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        log_message "✓ Backup listo para descargar: $OUTPUT_FILE"
        log_message "✓ Tamaño: $SIZE"
        echo ""
        echo "📥 Descarga inmediatamente con:"
        echo "   scp root@ciudadvirtual.app:$OUTPUT_FILE . && ssh root@ciudadvirtual.app 'rm $OUTPUT_FILE'"
        echo ""
        echo "⚡ Esto descarga y elimina el archivo automáticamente"
        return 0
    else
        log_error "Fallo en backup streaming"
        return 1
    fi
}

# OPCIÓN 4: Backup incremental (solo cambios recientes)
backup_recent_changes() {
    log_message "=== BACKUP INCREMENTAL (ÚLTIMOS 7 DÍAS) ==="
    
    # Verificar espacio
    if ! check_space 2; then
        return 1
    fi
    
    OUTPUT_FILE="/tmp/ciudadvirtual_incremental_${DATE}.tar.gz"
    
    log_message "Buscando archivos modificados en los últimos 7 días..."
    
    # Crear backup solo de archivos modificados recientemente
    find "$SITE_DIR" -type f -mtime -7 \
        ! -path "*/wp-content/cache/*" \
        ! -path "*/wp-content/upgrade*" \
        ! -name "*.log" \
        -print0 | tar -czf "$OUTPUT_FILE" --null -T - 2>/dev/null
    
    if [ $? -eq 0 ]; then
        SIZE=$(du -h "$OUTPUT_FILE" | cut -f1)
        COUNT=$(tar -tzf "$OUTPUT_FILE" 2>/dev/null | wc -l)
        log_message "✓ Backup incremental completado: $OUTPUT_FILE"
        log_message "✓ Archivos incluidos: $COUNT"
        log_message "✓ Tamaño: $SIZE"
        echo ""
        echo "📥 Para descargar ejecuta:"
        echo "   scp root@ciudadvirtual.app:$OUTPUT_FILE ."
        return 0
    else
        log_error "Fallo en backup incremental"
        return 1
    fi
}

# OPCIÓN 5: Información de backup (sin archivos)
show_backup_info() {
    echo ""
    log_message "=== INFORMACIÓN DEL SITIO ==="
    echo ""
    
    echo "📊 ESTADÍSTICAS:"
    echo "   Directorio: $SITE_DIR"
    echo "   Tamaño total: $(du -sh "$SITE_DIR" | cut -f1)"
    echo "   Base de datos: $DB_NAME"
    
    echo ""
    echo "📁 DESGLOSE POR CARPETAS:"
    du -sh "$SITE_DIR"/* 2>/dev/null | sort -rh | head -10
    
    echo ""
    echo "💾 ESPACIO EN SERVIDOR:"
    df -h / | tail -1
    
    echo ""
    log_message "Contando archivos por tipo..."
    echo "   PHP: $(find "$SITE_DIR" -name "*.php" | wc -l) archivos"
    echo "   JS: $(find "$SITE_DIR" -name "*.js" | wc -l) archivos"
    echo "   CSS: $(find "$SITE_DIR" -name "*.css" | wc -l) archivos"
    echo "   Imágenes: $(find "$SITE_DIR/wp-content/uploads" -type f 2>/dev/null | wc -l) archivos"
    
    echo ""
    log_message "Para hacer backup, ejecuta:"
    echo "   $0 database    - Solo base de datos (~500MB)"
    echo "   $0 essentials  - Archivos sin uploads (~2GB)"
    echo "   $0 stream      - Streaming directo (óptimo)"
    echo "   $0 incremental - Solo cambios recientes"
}

# Menú principal
case "$1" in
    database|db)
        backup_database_only
        ;;
    essentials|files)
        backup_essentials_only
        ;;
    stream)
        backup_stream_database
        ;;
    incremental|inc)
        backup_recent_changes
        ;;
    info)
        show_backup_info
        ;;
    *)
        echo "================================================"
        echo "  🛡️  BACKUP SEGURO DE CIUDADVIRTUAL"
        echo "================================================"
        echo ""
        echo "Uso: $0 [opción]"
        echo ""
        echo "Opciones disponibles:"
        echo ""
        echo "  database     - Backup solo de base de datos (~ 500MB)"
        echo "                 ✓ Más rápido"
        echo "                 ✓ Menos espacio"
        echo "                 ✓ Guarda en /tmp"
        echo ""
        echo "  essentials   - Archivos esenciales sin uploads (~ 2GB)"
        echo "                 ✓ Configuración y código"
        echo "                 ✓ Sin imágenes/medios"
        echo "                 ✓ Guarda en /tmp"
        echo ""
        echo "  stream       - Streaming directo (RECOMENDADO)"
        echo "                 ✓ No ocupa espacio permanente"
        echo "                 ✓ Descarga y elimina automáticamente"
        echo "                 ✓ Base de datos completa"
        echo ""
        echo "  incremental  - Solo cambios de últimos 7 días"
        echo "                 ✓ Muy ligero"
        echo "                 ✓ Para backups frecuentes"
        echo ""
        echo "  info         - Ver información sin hacer backup"
        echo "                 ✓ Estadísticas del sitio"
        echo "                 ✓ Tamaños de carpetas"
        echo ""
        echo "Ejemplos:"
        echo "  $0 database   # Backup rápido de BD"
        echo "  $0 stream     # Mejor opción para descarga"
        echo "  $0 info       # Ver información primero"
        echo ""
        echo "================================================"
        exit 1
        ;;
esac


