<?php
// Hinglish: Login wale admin ke liye provider ka private document dikhata hai.
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');

$providerId = filter_var(
    $_GET['provider_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if (!$providerId) {
    http_response_code(400);
    exit('Invalid provider.');
}

$stmt = $conn->prepare(
    'SELECT government_document_file
     FROM service_providers
     WHERE id = ?
     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    exit('Unable to retrieve document.');
}

$stmt->bind_param('i', $providerId);
$stmt->execute();
$result = $stmt->get_result();
$provider = $result->fetch_assoc();
$stmt->close();

if (!$provider || empty($provider['government_document_file'])) {
    http_response_code(404);
    exit('Document not found.');
}

$storageDirectory =
    dirname(__DIR__, 4) .
    DIRECTORY_SEPARATOR . 'fixlify_private' .
    DIRECTORY_SEPARATOR . 'provider_documents';

$storageRoot = realpath($storageDirectory);
$storedName = basename($provider['government_document_file']);
$documentPath = realpath($storageDirectory . DIRECTORY_SEPARATOR . $storedName);

if (!$storageRoot || !$documentPath) {
    http_response_code(404);
    exit('Document not found.');
}

$storagePrefix = rtrim($storageRoot, '\\/') . DIRECTORY_SEPARATOR;
if (stripos($documentPath, $storagePrefix) !== 0 || !is_file($documentPath)) {
    http_response_code(404);
    exit('Document not found.');
}

if (!function_exists('finfo_open')) {
    http_response_code(500);
    exit('Unable to verify document type.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $documentPath) : false;
if ($finfo) {
    finfo_close($finfo);
}

$allowedTypes = [
    'application/pdf' => 'pdf',
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowedTypes[$mimeType])) {
    http_response_code(415);
    exit('Unsupported document type.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($documentPath));
header('Content-Disposition: attachment; filename="provider-government-document.' . $allowedTypes[$mimeType] . '"');
header("Content-Security-Policy: default-src 'none'; sandbox");

readfile($documentPath);
exit;
