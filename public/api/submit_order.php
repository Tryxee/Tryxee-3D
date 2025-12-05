<?php
// public/api/submit_order.php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php'; // doit démarrer la session et définir $pdo

// ---- dev mode ----
$devMode = (getenv('APP_ENV') === 'development');

// ---- logging helper ----
$logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs_debug';

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);  // pas de @ pour voir les erreurs
}

function api_debug_log($msg) {
    global $logDir;

    // Format Windows-safe log line
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;

    $file = $logDir . DIRECTORY_SEPARATOR . 'api_debug.log';

    // Test direct
    $success = file_put_contents($file, $line, FILE_APPEND | LOCK_EX);

    if ($success === false) {
        error_log("[LOG ERROR] Impossible d'écrire dans: $file");
    }
}

api_debug_log("Script lancé !");
api_debug_log("TEST : écriture OK");

// safe header
header('Content-Type: application/json; charset=utf-8');

// log request meta (method, uri, IP)
$meta = sprintf("REQUEST %s %s from %s", $_SERVER['REQUEST_METHOD'] ?? '??', $_SERVER['REQUEST_URI'] ?? '??', $_SERVER['REMOTE_ADDR'] ?? '?.?.?.?');
api_debug_log($meta);

// log request headers (getallheaders may not exist in some SAPIs)
$headers = function_exists('getallheaders') ? getallheaders() : (function() {
    $h = [];
    foreach ($_SERVER as $k => $v) {
        if (substr($k, 0, 5) === 'HTTP_') {
            $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k,5)))));
            $h[$name] = $v;
        }
    }
    return $h;
})();
api_debug_log("HEADERS: " . json_encode($headers, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    api_debug_log("Method not allowed: " . ($_SERVER['REQUEST_METHOD'] ?? ''));
    echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
    exit;
}

// read raw - log first N chars
$raw = file_get_contents('php://input');
$raw_preview = $raw !== null ? substr($raw, 0, 4000) : '';
api_debug_log("RAW_BODY (preview): " . $raw_preview);

// decode JSON, check errors
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $msg = 'JSON decode error: ' . json_last_error_msg();
    api_debug_log($msg . " RAW_LEN=" . strlen($raw));
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid payload','detail'=>$msg]);
    exit;
}

// sanitize payload for logging (redact fields if present)
$loggedData = $data;
if (is_array($loggedData)) {
    if (isset($loggedData['password'])) $loggedData['password'] = '[REDACTED]';
    if (isset($loggedData['card_number'])) $loggedData['card_number'] = '[REDACTED]';
}
api_debug_log("PAYLOAD: " . json_encode($loggedData, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

// CSRF check: try common session keys but avoid generating token accidentally
$sessionCsrf = $_SESSION['csrf_token'] ?? ($_SESSION['csrf'] ?? null);
$payloadCsrf = $data['csrf'] ?? null;
api_debug_log("SESSION_CSRF: " . ($sessionCsrf ? '[present]' : '[missing]') . " PAYLOAD_CSRF: " . ($payloadCsrf ? '[present]' : '[missing]'));

if (empty($payloadCsrf) || empty($sessionCsrf) || !hash_equals((string)$sessionCsrf, (string)$payloadCsrf)) {
    api_debug_log("CSRF mismatch or missing");
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'CSRF token invalide']);
    exit;
}

// Required fields
$firstname = trim((string)($data['firstname'] ?? ''));
$lastname  = trim((string)($data['lastname'] ?? ''));
$email     = trim((string)($data['email'] ?? ''));
$phone     = trim((string)($data['phone'] ?? ''));
$printer_id = intval($data['printer_id'] ?? 0);

if ($firstname === '' || $lastname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $printer_id <= 0) {
    api_debug_log("Validation failed: firstname/lastname/email/printer_id missing or invalid");
    http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>'Champs requis manquants ou invalides']);
    exit;
}

// optional
$upload_id = isset($data['upload_id']) ? (is_numeric($data['upload_id']) ? intval($data['upload_id']) : null) : null;
$total_estimate = isset($data['total_estimate']) ? floatval($data['total_estimate']) : 0.0;

// compose details safely
$details = [
    'firstname' => $firstname,
    'lastname' => $lastname,
    'phone' => $phone,
    'printer_id' => $printer_id,
    'length_mm' => intval($data['length_mm'] ?? 0),
    'width_mm' => intval($data['width_mm'] ?? 0),
    'height_mm' => intval($data['height_mm'] ?? 0),
    'filament_id' => intval($data['filament_id'] ?? 0),
    'nozzle_size' => floatval($data['nozzle_size'] ?? 0.4),
    'layer_height' => floatval($data['layer_height'] ?? 0.2),
    'qty' => intval($data['qty'] ?? 1),
    'promo' => trim((string)($data['promo'] ?? '')),
    'manual_desc' => trim((string)($data['manual_desc'] ?? '')),
    'upload_id' => $upload_id
];

api_debug_log("DETAILS: " . json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));

try {
    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, guest_email, total, shipping, status, details) VALUES (?, ?, ?, ?, ?, ?)");
    $uid = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
    $status = 'quote_requested';
    $shipping = 0.0;

    $params = [$uid, $email, $total_estimate, $shipping, $status, json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
    api_debug_log("SQL EXECUTE: INSERT orders params: " . json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    $stmt->execute($params);

    $order_id = (int)$pdo->lastInsertId();
    api_debug_log("Order inserted id={$order_id}");

    // Notify admin (best-effort) - log outcome
    $adminEmail = 'thomas.duchemin.2008@gmail.com';
    $subject = "Nouvelle demande de devis #{$order_id} — Tryxee 3D";
    $body = "Nouvelle demande :\n\nID : {$order_id}\nClient : {$firstname} {$lastname}\nEmail : {$email}\nEstimation : ".number_format($total_estimate,2)." €\n\nDétails :\n".print_r($details, true)."\n\nConnecte-toi au dashboard pour gérer la commande.";
    $headers = "From: no-reply@localhost\r\nReply-To: {$email}\r\n";
    $mailOk = @mail($adminEmail, $subject, $body, $headers);
    api_debug_log("MAIL admin to {$adminEmail} sent? " . ($mailOk ? 'yes' : 'no'));

    // confirmation email to client (best-effort)
    $subject2 = "Nous avons bien reçu votre demande — Tryxee 3D (#{$order_id})";
    $body2 = "Bonjour {$firstname},\n\nMerci pour votre demande. Nous avons bien reçu votre demande (ID: {$order_id}). Nous reviendrons vers vous avec un devis par e-mail.\n\nCordialement,\nTryxee 3D";
    @mail($email, $subject2, $body2, "From: no-reply@localhost\r\n");

    // final response
    echo json_encode(['ok'=>true,'order_id'=>$order_id]);
    exit;
} catch (Throwable $e) {
    // log exception and optionally return trace in dev
    api_debug_log("UNCAUGHT EXCEPTION: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    api_debug_log("TRACE: " . $e->getTraceAsString());
    http_response_code(500);
    if ($devMode) {
        echo json_encode(['ok'=>false,'error'=>'Erreur serveur','exception'=>$e->getMessage(),'trace'=>$e->getTraceAsString()]);
    } else {
        echo json_encode(['ok'=>false,'error'=>'Erreur serveur. Voir logs.']);
    }
    exit;
}
