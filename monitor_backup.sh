#!/bin/bash
# Script de monitoreo de backups

BACKUP_DIR="/home/ciudadvirtual/backups"
LOG_FILE="$BACKUP_DIR/backup.log"

echo "=== ESTADO DEL SISTEMA DE BACKUP ==="
echo ""

# Verificar directorio de backups
if [ -d "$BACKUP_DIR" ]; then
    echo "✓ Directorio de backups: $BACKUP_DIR"
    echo "  Espacio disponible: $(df -h "$BACKUP_DIR" | tail -1 | awk '{print $4}')"
else
    echo "✗ Directorio de backups no encontrado"
fi

# Verificar logs
if [ -f "$LOG_FILE" ]; then
    echo ""
    echo "📊 Últimas actividades:"
    tail -n 10 "$LOG_FILE" 2>/dev/null || echo "  No hay logs disponibles"
fi

# Listar backups recientes
echo ""
echo "📦 Backups disponibles:"
ls -lh "$BACKUP_DIR"/*.tar.gz 2>/dev/null | tail -5 | while read line; do
    echo "  $line"
done

# Verificar cron jobs
echo ""
echo "⏰ Tareas programadas:"
crontab -l 2>/dev/null | grep -E "(backup|ciudadvirtual)" || echo "  No hay tareas de backup programadas"

echo ""
echo "=== FIN DEL REPORTE ==="
