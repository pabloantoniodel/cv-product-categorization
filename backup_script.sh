#!/bin/bash

# Script de Backup Completo para CiudadVirtual
# Incluye backup de archivos y base de datos MySQL
# Autor: Sistema de Backup Automatizado
# Fecha: $(date)

# Configuración
SITE_DIR="/home/ciudadvirtual/htdocs/ciudadvirtual.store"
BACKUP_DIR="/home/ciudadvirtual/backups"
LOG_FILE="/home/ciudadvirtual/backups/backup.log"
DATE=$(date +%Y%m%d_%H%M%S)

# Configuración de la base de datos (desde wp-config.php)
DB_NAME="ciudadvirtual"
DB_USER="root"
DB_PASSWORD="mL8i0WeYvUjwtvBam2pd"
DB_HOST="127.0.0.1:3306"

# Crear directorio de backups si no existe
mkdir -p "$BACKUP_DIR"

# Función para logging
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Función para limpiar backups antiguos (mantener solo los últimos 7 días)
cleanup_old_backups() {
    log_message "Limpiando backups antiguos (más de 7 días)..."
    find "$BACKUP_DIR" -name "ciudadvirtual_backup_*.tar.gz" -mtime +7 -delete
    find "$BACKUP_DIR" -name "ciudadvirtual_db_*.sql.gz" -mtime +7 -delete
    log_message "Limpieza completada"
}

# Función para backup de base de datos
backup_database() {
    log_message "Iniciando backup de base de datos..."
    
    DB_BACKUP_FILE="$BACKUP_DIR/ciudadvirtual_db_${DATE}.sql"
    DB_COMPRESSED_FILE="$BACKUP_DIR/ciudadvirtual_db_${DATE}.sql.gz"
    
    # Extraer puerto del host si está especificado
    if [[ "$DB_HOST" == *":"* ]]; then
        DB_HOST_ONLY=$(echo "$DB_HOST" | cut -d: -f1)
        DB_PORT=$(echo "$DB_HOST" | cut -d: -f2)
    else
        DB_HOST_ONLY="$DB_HOST"
        DB_PORT="3306"
    fi
    
    # Crear backup de la base de datos
    mysqldump -h "$DB_HOST_ONLY" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_NAME" > "$DB_BACKUP_FILE"
    
    if [ $? -eq 0 ]; then
        # Comprimir el backup de la base de datos
        gzip "$DB_BACKUP_FILE"
        log_message "Backup de base de datos completado: $DB_COMPRESSED_FILE"
        
        # Mostrar tamaño del archivo
        DB_SIZE=$(du -h "$DB_COMPRESSED_FILE" | cut -f1)
        log_message "Tamaño del backup de BD: $DB_SIZE"
        
        return 0
    else
        log_message "ERROR: Fallo en el backup de la base de datos"
        return 1
    fi
}

# Función para backup de archivos
backup_files() {
    log_message "Iniciando backup de archivos..."
    
    FILES_BACKUP_FILE="$BACKUP_DIR/ciudadvirtual_files_${DATE}.tar.gz"
    
    # Crear backup de archivos (excluyendo archivos temporales y logs)
    tar -czf "$FILES_BACKUP_FILE" \
        --exclude="*.log" \
        --exclude="debug-*.log" \
        --exclude="wp-content/cache" \
        --exclude="wp-content/upgrade" \
        --exclude="wp-content/upgrade-temp-backup" \
        --exclude="wp-content/ewww" \
        --exclude="wp-content/jetpack-waf" \
        --exclude="*.sql" \
        --exclude="*.sql.gz" \
        --exclude="backup_script.sh" \
        --exclude=".htaccess" \
        -C "$(dirname "$SITE_DIR")" \
        "$(basename "$SITE_DIR")"
    
    if [ $? -eq 0 ]; then
        log_message "Backup de archivos completado: $FILES_BACKUP_FILE"
        
        # Mostrar tamaño del archivo
        FILES_SIZE=$(du -h "$FILES_BACKUP_FILE" | cut -f1)
        log_message "Tamaño del backup de archivos: $FILES_SIZE"
        
        return 0
    else
        log_message "ERROR: Fallo en el backup de archivos"
        return 1
    fi
}

# Función para crear backup completo
create_complete_backup() {
    log_message "Iniciando backup completo de CiudadVirtual..."
    
    COMPLETE_BACKUP_FILE="$BACKUP_DIR/ciudadvirtual_backup_${DATE}.tar.gz"
    
    # Crear directorio temporal para el backup completo
    TEMP_DIR="/tmp/ciudadvirtual_backup_${DATE}"
    mkdir -p "$TEMP_DIR"
    
    # Copiar archivos del sitio
    log_message "Copiando archivos del sitio..."
    cp -r "$SITE_DIR" "$TEMP_DIR/"
    
    # Crear backup de la base de datos en el directorio temporal
    log_message "Creando backup de base de datos..."
    DB_TEMP_FILE="$TEMP_DIR/$(basename "$SITE_DIR")/database_backup.sql"
    
    # Extraer puerto del host si está especificado
    if [[ "$DB_HOST" == *":"* ]]; then
        DB_HOST_ONLY=$(echo "$DB_HOST" | cut -d: -f1)
        DB_PORT=$(echo "$DB_HOST" | cut -d: -f2)
    else
        DB_HOST_ONLY="$DB_HOST"
        DB_PORT="3306"
    fi
    
    mysqldump -h "$DB_HOST_ONLY" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_NAME" > "$DB_TEMP_FILE"
    
    if [ $? -eq 0 ]; then
        # Crear archivo de información del backup
        INFO_FILE="$TEMP_DIR/backup_info.txt"
        cat > "$INFO_FILE" << EOF
Backup de CiudadVirtual
======================

Fecha de creación: $(date)
Sitio: ciudadvirtual.store
Directorio: $SITE_DIR
Base de datos: $DB_NAME

Contenido del backup:
- Archivos completos del sitio WordPress
- Base de datos MySQL (database_backup.sql)

Para restaurar:
1. Extraer el archivo tar.gz
2. Copiar los archivos al directorio web
3. Importar database_backup.sql a MySQL

Tamaño total: $(du -sh "$TEMP_DIR" | cut -f1)
EOF
        
        # Comprimir todo
        log_message "Comprimiendo backup completo..."
        tar -czf "$COMPLETE_BACKUP_FILE" -C "$(dirname "$TEMP_DIR")" "$(basename "$TEMP_DIR")"
        
        if [ $? -eq 0 ]; then
            # Limpiar directorio temporal
            rm -rf "$TEMP_DIR"
            
            # Mostrar información del backup
            BACKUP_SIZE=$(du -h "$COMPLETE_BACKUP_FILE" | cut -f1)
            log_message "Backup completo creado: $COMPLETE_BACKUP_FILE"
            log_message "Tamaño del backup completo: $BACKUP_SIZE"
            
            return 0
        else
            log_message "ERROR: Fallo al comprimir el backup completo"
            rm -rf "$TEMP_DIR"
            return 1
        fi
    else
        log_message "ERROR: Fallo en el backup de la base de datos para backup completo"
        rm -rf "$TEMP_DIR"
        return 1
    fi
}

# Función principal
main() {
    log_message "=== INICIANDO BACKUP DE CIUDADVIRTUAL ==="
    
    # Verificar que el directorio del sitio existe
    if [ ! -d "$SITE_DIR" ]; then
        log_message "ERROR: El directorio del sitio no existe: $SITE_DIR"
        exit 1
    fi
    
    # Verificar conexión a la base de datos
    log_message "Verificando conexión a la base de datos..."
    if [[ "$DB_HOST" == *":"* ]]; then
        DB_HOST_ONLY=$(echo "$DB_HOST" | cut -d: -f1)
        DB_PORT=$(echo "$DB_HOST" | cut -d: -f2)
    else
        DB_HOST_ONLY="$DB_HOST"
        DB_PORT="3306"
    fi
    
    mysql -h "$DB_HOST_ONLY" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1;" > /dev/null 2>&1
    if [ $? -ne 0 ]; then
        log_message "ERROR: No se puede conectar a la base de datos"
        exit 1
    fi
    log_message "Conexión a la base de datos verificada"
    
    # Crear backup completo
    create_complete_backup
    
    if [ $? -eq 0 ]; then
        log_message "Backup completo exitoso"
        
        # Crear backups individuales también
        backup_database
        backup_files
        
        # Limpiar backups antiguos
        cleanup_old_backups
        
        log_message "=== BACKUP COMPLETADO EXITOSAMENTE ==="
        log_message "Archivos disponibles en: $BACKUP_DIR"
        
        # Mostrar archivos creados
        log_message "Archivos de backup creados:"
        ls -lh "$BACKUP_DIR"/*${DATE}* 2>/dev/null | while read line; do
            log_message "  $line"
        done
        
    else
        log_message "ERROR: Fallo en el backup completo"
        exit 1
    fi
}

# Ejecutar función principal
main "$@"


