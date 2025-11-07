#!/bin/bash

# Script de configuración del sistema de backup
# Configura cron jobs y permisos necesarios

SCRIPT_DIR="/home/ciudadvirtual/htdocs/ciudadvirtual.store"
BACKUP_DIR="/home/ciudadvirtual/backups"
CRON_USER="ciudadvirtual"

echo "=== CONFIGURACIÓN DEL SISTEMA DE BACKUP ==="
echo ""

# Crear directorio de backups
echo "1. Creando directorio de backups..."
mkdir -p "$BACKUP_DIR"
chmod 755 "$BACKUP_DIR"
echo "   ✓ Directorio creado: $BACKUP_DIR"

# Configurar permisos
echo "2. Configurando permisos..."
chmod +x "$SCRIPT_DIR/backup_script.sh"
chmod +x "$SCRIPT_DIR/run_backup.sh"
chmod +x "$SCRIPT_DIR/setup_backup.sh"
echo "   ✓ Permisos configurados"

# Crear archivo de configuración de cron
echo "3. Configurando tareas automáticas..."
CRON_FILE="/tmp/ciudadvirtual_backup_cron"

cat > "$CRON_FILE" << EOF
# Backups automáticos de CiudadVirtual
# Backup diario a las 2:00 AM
0 2 * * * $SCRIPT_DIR/run_backup.sh start >/dev/null 2>&1

# Backup semanal completo los domingos a las 3:00 AM
0 3 * * 0 $SCRIPT_DIR/backup_script.sh >/dev/null 2>&1

# Limpieza de backups antiguos (más de 30 días) los lunes a las 4:00 AM
0 4 * * 1 find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete >/dev/null 2>&1
EOF

echo "   ✓ Archivo de cron creado"

# Instalar cron job
echo "4. Instalando tareas automáticas..."
crontab -u "$CRON_USER" "$CRON_FILE" 2>/dev/null || {
    echo "   ⚠️  No se pudo instalar cron automáticamente"
    echo "   📝 Instala manualmente con: crontab -e"
    echo "   📝 Agrega estas líneas:"
    cat "$CRON_FILE"
}

# Limpiar archivo temporal
rm -f "$CRON_FILE"

# Crear script de monitoreo
echo "5. Creando script de monitoreo..."
cat > "$SCRIPT_DIR/monitor_backup.sh" << 'EOF'
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
EOF

chmod +x "$SCRIPT_DIR/monitor_backup.sh"
echo "   ✓ Script de monitoreo creado"

# Ejecutar backup inicial
echo "6. Ejecutando backup inicial..."
echo "   Iniciando backup en segundo plano..."
nohup "$SCRIPT_DIR/run_backup.sh" start >/dev/null 2>&1 &
echo "   ✓ Backup inicial iniciado"

echo ""
echo "=== CONFIGURACIÓN COMPLETADA ==="
echo ""
echo "📁 Directorio de backups: $BACKUP_DIR"
echo "🔧 Scripts disponibles:"
echo "   - $SCRIPT_DIR/run_backup.sh start    (iniciar backup)"
echo "   - $SCRIPT_DIR/run_backup.sh status  (ver estado)"
echo "   - $SCRIPT_DIR/run_backup.sh logs    (ver logs)"
echo "   - $SCRIPT_DIR/run_backup.sh list    (listar backups)"
echo "   - $SCRIPT_DIR/monitor_backup.sh     (monitoreo completo)"
echo ""
echo "🌐 Interfaz web de descarga:"
echo "   https://ciudadvirtual.app/download_backup.php"
echo ""
echo "⏰ Backups automáticos configurados:"
echo "   - Diario a las 2:00 AM"
echo "   - Semanal completo los domingos a las 3:00 AM"
echo "   - Limpieza automática los lunes a las 4:00 AM"
echo ""
echo "✅ Sistema de backup configurado exitosamente!"


