<?php
/**
 * procesar-formulario.php - RYSA
 * Procesa el formulario de contacto y redirige a gracias.html
 */

// ===== CONFIGURACIÓN INICIAL =====
// NO PONGAS NADA (ni espacios, ni saltos) ANTES de <?php

// Para depuración - desactivar en producción
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Iniciar buffer para capturar cualquier output accidental
ob_start();

// ===== FUNCIÓN PARA REDIRIGIR CON MENSAJE =====
function redirectWithMessage($success, $message = '') {
    // Limpiar buffer
    if (ob_get_length()) ob_clean();
    
    // Parámetros para la página de gracias
    $params = [
        'success' => $success ? 'true' : 'false',
        'message' => urlencode($message)
    ];
    
    // Redirigir a gracias.html
    header('Location: gracias.html?' . http_build_query($params));
    exit;
}

// ===== VALIDACIÓN DE MÉTODO =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage(false, 'Método no permitido. Usa POST.');
}

// ===== RECEPCIÓN Y VALIDACIÓN DE DATOS =====
$errores = [];

// Campos requeridos
$camposRequeridos = ['nombre', 'email', 'mensaje'];
foreach ($camposRequeridos as $campo) {
    if (empty($_POST[$campo] ?? '')) {
        $errores[] = ucfirst($campo) . ' es requerido';
    }
}

// Validar email
if (!empty($_POST['email'])) {
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Email no válido';
    }
} else {
    $errores[] = 'Email es requerido';
}

// Validar servicios
$servicios = $_POST['servicio'] ?? [];
if (empty($servicios) || (is_array($servicios) && count($servicios) === 0)) {
    $errores[] = 'Selecciona al menos un servicio';
}

// Validar privacidad
if (!isset($_POST['privacidad'])) {
    $errores[] = 'Debes aceptar la política de privacidad';
}

// Si hay errores, redirigir con mensaje de error
if (!empty($errores)) {
    redirectWithMessage(false, '❌ ' . implode(', ', $errores));
}

// ===== SANITIZAR DATOS =====
$nombre = htmlspecialchars(trim($_POST['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$telefono = htmlspecialchars(trim($_POST['telefono'] ?? ''), ENT_QUOTES, 'UTF-8');
$empresa = htmlspecialchars(trim($_POST['empresa'] ?? ''), ENT_QUOTES, 'UTF-8');
$mensaje = htmlspecialchars(trim($_POST['mensaje'] ?? ''), ENT_QUOTES, 'UTF-8');
$presupuesto = htmlspecialchars(trim($_POST['presupuesto'] ?? ''), ENT_QUOTES, 'UTF-8');

// ===== PREPARAR DATOS PARA EMAIL =====
$fecha = date('d/m/Y H:i:s');

// Mapear servicios
$serviciosMap = [
    'web' => '🌐 Desarrollo Web',
    'branding' => '🎨 Branding',
    'cm' => '📱 Community Management',
    'mantenimiento' => '🔧 Mantenimiento',
    'sistema' => '⚙️ Sistema a medida',
    'no-se' => '🤔 Necesito asesoramiento'
];

$serviciosTexto = [];
foreach ($servicios as $servicio) {
    if (isset($serviciosMap[$servicio])) {
        $serviciosTexto[] = $serviciosMap[$servicio];
    }
}

// Mapear presupuesto
$presupuestosMap = [
    'menos-200' => '💰 Menos de $200.000',
    '200-400' => '💰💰 $200.000 - $400.000',
    '400-700' => '💰💰💰 $400.000 - $700.000',
    '700-1m' => '💰💰💰💰 $700.000 - $1.000.000',
    'mas-1m' => '💰💰💰💰💰 Más de $1.000.000',
    'no-se' => '🤔 No sé, necesito asesoramiento'
];

$presupuestoTexto = $presupuestosMap[$presupuesto] ?? 'No especificado';

// ===== CONSTRUIR EMAIL =====
$contenidoEmail = "
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Nueva Consulta - Rysa</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .email-header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .email-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .email-content {
            padding: 30px;
        }
        
        .info-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .field-group {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .field-group:last-child {
            border-bottom: none;
        }
        
        .field-label {
            font-weight: 600;
            color: #4f46e5;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .field-value {
            font-size: 16px;
            color: #1e293b;
        }
        
        .badge {
            display: inline-block;
            background: #e0e7ff;
            color: #4f46e5;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            margin: 2px;
        }
        
        .budget-badge {
            background: #dcfce7;
            color: #166534;
        }
        
        .footer {
            background: #f1f5f9;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='email-header'>
            <h1>🚀 NUEVA CONSULTA - RYSA</h1>
            <p style='opacity: 0.9; margin: 5px 0 0;'>Fecha: {$fecha}</p>
        </div>
        
        <div class='email-content'>
            <div class='info-section'>
                <div class='field-group'>
                    <div class='field-label'>INFORMACIÓN PERSONAL</div>
                    <div class='field-value'>
                        <strong>{$nombre}</strong><br>
                        📧 <a href='mailto:{$email}' style='color: #4f46e5;'>{$email}</a><br>
                        📱 " . ($telefono ?: 'No proporcionado') . "
                    </div>
                </div>
                
                <div class='field-group'>
                    <div class='field-label'>EMPRESA / PROYECTO</div>
                    <div class='field-value'>" . ($empresa ?: 'No especificada') . "</div>
                </div>
                
                <div class='field-group'>
                    <div class='field-label'>SERVICIOS REQUERIDOS</div>
                    <div class='field-value'>";

// Mostrar servicios como badges
foreach ($serviciosTexto as $servicio) {
    $contenidoEmail .= "<span class='badge'>{$servicio}</span> ";
}

$contenidoEmail .= "
                    </div>
                </div>
                
                <div class='field-group'>
                    <div class='field-label'>PRESUPUESTO ESTIMADO</div>
                    <div class='field-value'>
                        <span class='badge budget-badge'>{$presupuestoTexto}</span>
                    </div>
                </div>
                
                <div class='field-group'>
                    <div class='field-label'>MENSAJE</div>
                    <div class='field-value' style='background: #f8fafc; padding: 15px; border-radius: 6px; margin-top: 10px; white-space: pre-line;'>
                        " . nl2br($mensaje) . "
                    </div>
                </div>
            </div>
            
            <div style='text-align: center; margin-top: 30px;'>
                <a href='mailto:{$email}' style='background: #4f46e5; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;'>
                    ✉️ Responder a {$nombre}
                </a>
            </div>
        </div>
        
        <div class='footer'>
            <p>📧 Este email fue generado automáticamente desde el formulario de contacto de <strong>Rysa.site</strong></p>
            <p>🕒 Fecha de recepción: {$fecha}</p>
            <p>📍 IP del remitente: " . ($_SERVER['REMOTE_ADDR'] ?? 'No disponible') . "</p>
        </div>
    </div>
</body>
</html>
";

// ===== ENVIAR EMAIL =====
$destinatarios = ["rysa.site@gmail.com"]; // Solo a Gmail por ahora
$asunto = "🚀 Nueva consulta de {$nombre} - Rysa.site";

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: Rysa.site <no-reply@rysa.site>',
    'Reply-To: ' . $nombre . ' <' . $email . '>',
    'X-Mailer: PHP/' . phpversion()
];

$enviado = false;
foreach ($destinatarios as $destinatario) {
    if (mail($destinatario, $asunto, $contenidoEmail, implode("\r\n", $headers))) {
        $enviado = true;
        break;
    }
}

// ===== GUARDAR EN LOG =====
$logEntry = date('Y-m-d H:i:s') . " | {$nombre} | {$email} | " . 
            implode(', ', $serviciosTexto) . " | " . 
            ($enviado ? 'ENVIADO' : 'FALLÓ') . PHP_EOL;
            
@file_put_contents('contactos_log.txt', $logEntry, FILE_APPEND);

// Si falla el email, guardar backup
if (!$enviado) {
    $backupFile = 'contactos_pendientes_' . date('Y-m-d') . '.txt';
    $backupData = "[{$fecha}]\nNombre: {$nombre}\nEmail: {$email}\nTelefono: {$telefono}\nEmpresa: {$empresa}\nServicios: " . 
                  implode(', ', $serviciosTexto) . "\nPresupuesto: {$presupuestoTexto}\nMensaje: {$mensaje}\n---\n\n";
    
    @file_put_contents($backupFile, $backupData, FILE_APPEND);
}

// ===== REDIRIGIR A PÁGINA DE GRACIAS =====
// Limpiar buffer completamente
if (ob_get_length()) ob_clean();

// Preparar mensaje para la página de gracias
if ($enviado) {
    $mensajeGracias = "✅ ¡Consulta enviada con éxito! Te contactaremos en breve.";
    
    // Guardar datos en sesión para mostrar en gracias.html
    session_start();
    $_SESSION['form_data'] = [
        'nombre' => $nombre,
        'email' => $email,
        'servicios' => $serviciosTexto,
        'fecha' => $fecha,
        'enviado' => true
    ];
} else {
    $mensajeGracias = "⚠️ El mensaje fue recibido pero hubo un error técnico. Te contactaremos por email.";
    
    session_start();
    $_SESSION['form_data'] = [
        'nombre' => $nombre,
        'email' => $email,
        'enviado' => false,
        'backup_saved' => true
    ];
}

// Redirigir a gracias.html
header('Location: gracias.html');
exit;
?>