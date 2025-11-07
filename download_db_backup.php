<?php
/**
 * Script simple para descargar backup de base de datos
 * Acceso: https://ciudadvirtual.app/download_db_backup.php
 */

// Seguridad básica - cambia esta clave
$SECRET_KEY = 'ciudadvirtual2025';

// Verificar acceso
if (!isset($_GET['key']) || $_GET['key'] !== $SECRET_KEY) {
    http_response_code(403);
    die('Acceso denegado. Usa: ?key=ciudadvirtual2025');
}

// Buscar el backup más reciente
$backup_file = '/tmp/ciudadvirtual_db_20251019_203944.sql.gz';

if (!file_exists($backup_file)) {
    // Buscar cualquier backup en /tmp
    $files = glob('/tmp/ciudadvirtual_db_*.sql.gz');
    if (empty($files)) {
        http_response_code(404);
        die('❌ No hay backup disponible. Ejecuta: ./backup_safe.sh database');
    }
    // Obtener el más reciente
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    $backup_file = $files[0];
}

// Acción: descargar o mostrar info
$action = $_GET['action'] ?? 'info';

if ($action === 'download') {
    // Descargar archivo
    header('Content-Type: application/gzip');
    header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
    header('Content-Length: ' . filesize($backup_file));
    header('Cache-Control: no-cache');
    
    readfile($backup_file);
    exit;
}

// Mostrar información
$size = filesize($backup_file);
$size_mb = round($size / 1024 / 1024, 2);
$date = date('Y-m-d H:i:s', filemtime($backup_file));
$filename = basename($backup_file);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descarga Backup - CiudadVirtual</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #555;
        }
        .info-value {
            color: #667eea;
            font-family: monospace;
        }
        .download-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
            width: 100%;
            text-align: center;
            margin-top: 20px;
        }
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
        }
        .commands {
            background: #2d2d2d;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 13px;
            margin-top: 20px;
            overflow-x: auto;
        }
        .cmd {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🗄️</div>
        <h1>Backup de Base de Datos</h1>
        <div class="subtitle">CiudadVirtual.app</div>
        
        <div class="success">
            ✅ Backup disponible para descarga
        </div>
        
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">📦 Archivo:</span>
                <span class="info-value"><?php echo $filename; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">💾 Tamaño:</span>
                <span class="info-value"><?php echo $size_mb; ?> MB</span>
            </div>
            <div class="info-row">
                <span class="info-label">📅 Creado:</span>
                <span class="info-value"><?php echo $date; ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">🗃️ Base de datos:</span>
                <span class="info-value">ciudadvirtual</span>
            </div>
        </div>
        
        <a href="?key=<?php echo $SECRET_KEY; ?>&action=download" class="download-btn">
            ⬇️ Descargar Backup (<?php echo $size_mb; ?> MB)
        </a>
        
        <div class="warning">
            ⚠️ <strong>Importante:</strong> Este archivo se encuentra en /tmp y se eliminará automáticamente al reiniciar el servidor.
        </div>
        
        <div class="commands">
            <div class="cmd"># Para restaurar el backup:</div>
            <div class="cmd">$ gunzip <?php echo $filename; ?></div>
            <div class="cmd">$ mysql -u root -p ciudadvirtual < ciudadvirtual_db_*.sql</div>
            <div class="cmd"></div>
            <div class="cmd"># O descargar por SCP:</div>
            <div class="cmd">$ scp root@ciudadvirtual.app:<?php echo $backup_file; ?> .</div>
        </div>
    </div>
</body>
</html>


