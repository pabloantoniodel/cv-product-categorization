<?php
/**
 * Script para descargar backups de CiudadVirtual
 * Proporciona una interfaz web para descargar los archivos de backup
 */

// Configuración de seguridad
$BACKUP_DIR = '/home/ciudadvirtual/backups';
$ALLOWED_EXTENSIONS = ['tar.gz', 'sql.gz'];
$MAX_DOWNLOAD_SIZE = 500 * 1024 * 1024; // 500MB máximo

// Función para sanitizar nombres de archivos
function sanitize_filename($filename) {
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
}

// Función para obtener tamaño de archivo en formato legible
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Función para listar archivos de backup
function list_backups() {
    global $BACKUP_DIR, $ALLOWED_EXTENSIONS;
    
    $backups = [];
    
    if (is_dir($BACKUP_DIR)) {
        $files = scandir($BACKUP_DIR);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $file_path = $BACKUP_DIR . '/' . $file;
            if (is_file($file_path)) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                if (in_array($extension, $ALLOWED_EXTENSIONS) || 
                    in_array(pathinfo($file, PATHINFO_FILENAME) . '.' . $extension, $ALLOWED_EXTENSIONS)) {
                    
                    $backups[] = [
                        'name' => $file,
                        'size' => filesize($file_path),
                        'date' => filemtime($file_path),
                        'type' => strpos($file, 'db_') !== false ? 'Base de Datos' : 
                                 (strpos($file, 'files_') !== false ? 'Archivos' : 'Completo')
                    ];
                }
            }
        }
    }
    
    // Ordenar por fecha (más recientes primero)
    usort($backups, function($a, $b) {
        return $b['date'] - $a['date'];
    });
    
    return $backups;
}

// Función para descargar archivo
function download_file($filename) {
    global $BACKUP_DIR, $MAX_DOWNLOAD_SIZE;
    
    $filename = sanitize_filename($filename);
    $file_path = $BACKUP_DIR . '/' . $filename;
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('Archivo no encontrado');
    }
    
    if (filesize($file_path) > $MAX_DOWNLOAD_SIZE) {
        http_response_code(413);
        die('Archivo demasiado grande para descarga');
    }
    
    // Configurar headers para descarga
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Leer y enviar archivo
    readfile($file_path);
    exit;
}

// Procesar solicitudes
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'download':
            if (isset($_GET['file'])) {
                download_file($_GET['file']);
            } else {
                http_response_code(400);
                die('Archivo no especificado');
            }
            break;
            
        case 'list':
            header('Content-Type: application/json');
            echo json_encode(list_backups());
            exit;
            break;
            
        default:
            http_response_code(400);
            die('Acción no válida');
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descarga de Backups - CiudadVirtual</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .backup-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .backup-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            transition: transform 0.2s;
        }
        .backup-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .backup-type {
            font-weight: bold;
            color: #2c5aa0;
            margin-bottom: 10px;
        }
        .backup-name {
            font-family: monospace;
            background: #e9e9e9;
            padding: 5px;
            border-radius: 3px;
            margin: 10px 0;
            word-break: break-all;
        }
        .backup-info {
            color: #666;
            font-size: 0.9em;
            margin: 5px 0;
        }
        .download-btn {
            background: #2c5aa0;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: background 0.2s;
        }
        .download-btn:hover {
            background: #1e3d6f;
        }
        .refresh-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .refresh-btn:hover {
            background: #218838;
        }
        .status {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Descarga de Backups - CiudadVirtual</h1>
        
        <button class="refresh-btn" onclick="loadBackups()">🔄 Actualizar Lista</button>
        
        <div id="status" class="status">Cargando backups...</div>
        <div id="backups-container"></div>
    </div>

    <script>
        function formatBytes(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = Math.max(bytes, 0);
            let unitIndex = 0;
            
            while (size >= 1024 && unitIndex < units.length - 1) {
                size /= 1024;
                unitIndex++;
            }
            
            return size.toFixed(2) + ' ' + units[unitIndex];
        }
        
        function formatDate(timestamp) {
            return new Date(timestamp * 1000).toLocaleString('es-ES');
        }
        
        function loadBackups() {
            const statusDiv = document.getElementById('status');
            const containerDiv = document.getElementById('backups-container');
            
            statusDiv.textContent = 'Cargando backups...';
            containerDiv.innerHTML = '';
            
            fetch('?action=list')
                .then(response => response.json())
                .then(backups => {
                    if (backups.length === 0) {
                        statusDiv.innerHTML = '<div class="error">No hay backups disponibles</div>';
                        return;
                    }
                    
                    statusDiv.textContent = `Se encontraron ${backups.length} backups`;
                    
                    const grid = document.createElement('div');
                    grid.className = 'backup-grid';
                    
                    backups.forEach(backup => {
                        const card = document.createElement('div');
                        card.className = 'backup-card';
                        
                        card.innerHTML = `
                            <div class="backup-type">📦 ${backup.type}</div>
                            <div class="backup-name">${backup.name}</div>
                            <div class="backup-info">📅 ${formatDate(backup.date)}</div>
                            <div class="backup-info">💾 ${formatBytes(backup.size)}</div>
                            <a href="?action=download&file=${encodeURIComponent(backup.name)}" 
                               class="download-btn">⬇️ Descargar</a>
                        `;
                        
                        grid.appendChild(card);
                    });
                    
                    containerDiv.appendChild(grid);
                })
                .catch(error => {
                    statusDiv.innerHTML = `<div class="error">Error al cargar backups: ${error.message}</div>`;
                });
        }
        
        // Cargar backups al iniciar
        loadBackups();
    </script>
</body>
</html>


