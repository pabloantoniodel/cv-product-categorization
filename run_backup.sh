#!/bin/bash

# Script para ejecutar backup en segundo plano
# Este script ejecuta el backup principal en background

SCRIPT_DIR="/home/ciudadvirtual/htdocs/ciudadvirtual.store"
BACKUP_SCRIPT="$SCRIPT_DIR/backup_script.sh"
LOG_FILE="/home/ciudadvirtual/backups/backup.log"
PID_FILE="/home/ciudadvirtual/backups/backup.pid"

# Función para mostrar ayuda
show_help() {
    echo "Uso: $0 [opciones]"
    echo ""
    echo "Opciones:"
    echo "  start     - Iniciar backup en segundo plano"
    echo "  stop      - Detener backup en ejecución"
    echo "  status    - Ver estado del backup"
    echo "  logs      - Ver logs del backup"
    echo "  list      - Listar backups disponibles"
    echo "  help      - Mostrar esta ayuda"
    echo ""
}

# Función para iniciar backup
start_backup() {
    if [ -f "$PID_FILE" ] && kill -0 `cat "$PID_FILE"` 2>/dev/null; then
        echo "Ya hay un backup en ejecución (PID: $(cat "$PID_FILE"))"
        return 1
    fi
    
    echo "Iniciando backup en segundo plano..."
    nohup "$BACKUP_SCRIPT" > "$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"
    echo "Backup iniciado con PID: $!"
    echo "Puedes monitorear el progreso con: $0 logs"
    echo "Ver estado con: $0 status"
}

# Función para detener backup
stop_backup() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if kill -0 "$PID" 2>/dev/null; then
            echo "Deteniendo backup (PID: $PID)..."
            kill "$PID"
            rm -f "$PID_FILE"
            echo "Backup detenido"
        else
            echo "No hay backup en ejecución"
            rm -f "$PID_FILE"
        fi
    else
        echo "No hay backup en ejecución"
    fi
}

# Función para ver estado
show_status() {
    if [ -f "$PID_FILE" ]; then
        PID=$(cat "$PID_FILE")
        if kill -0 "$PID" 2>/dev/null; then
            echo "Backup en ejecución (PID: $PID)"
            echo "Tiempo de ejecución: $(ps -o etime= -p "$PID" 2>/dev/null || echo 'Desconocido')"
        else
            echo "Backup no está en ejecución (archivo PID obsoleto)"
            rm -f "$PID_FILE"
        fi
    else
        echo "No hay backup en ejecución"
    fi
}

# Función para ver logs
show_logs() {
    if [ -f "$LOG_FILE" ]; then
        echo "=== ÚLTIMOS LOGS DE BACKUP ==="
        tail -n 50 "$LOG_FILE"
    else
        echo "No hay archivo de logs disponible"
    fi
}

# Función para listar backups
list_backups() {
    BACKUP_DIR="/home/ciudadvirtual/backups"
    if [ -d "$BACKUP_DIR" ]; then
        echo "=== BACKUPS DISPONIBLES ==="
        echo ""
        echo "Backups completos:"
        ls -lh "$BACKUP_DIR"/ciudadvirtual_backup_*.tar.gz 2>/dev/null | while read line; do
            echo "  $line"
        done
        echo ""
        echo "Backups de base de datos:"
        ls -lh "$BACKUP_DIR"/ciudadvirtual_db_*.sql.gz 2>/dev/null | while read line; do
            echo "  $line"
        done
        echo ""
        echo "Backups de archivos:"
        ls -lh "$BACKUP_DIR"/ciudadvirtual_files_*.tar.gz 2>/dev/null | while read line; do
            echo "  $line"
        done
    else
        echo "No hay directorio de backups"
    fi
}

# Procesar argumentos
case "$1" in
    start)
        start_backup
        ;;
    stop)
        stop_backup
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    list)
        list_backups
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo "Opción no válida: $1"
        echo ""
        show_help
        exit 1
        ;;
esac


