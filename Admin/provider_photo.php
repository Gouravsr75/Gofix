<?php
// Hinglish: Login wale admin ke liye provider ki profile photo dikhata hai.
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
    'SELECT provider_photo_file
     FROM service_providers
     WHERE id = ?
     LIMIT 1'
);

if (!$stmt) {
    http_response_code(500);
    exit('Unable to retrieve photo.');
}

$stmt->bind_param('i', $providerId);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$provider || empty($provider['provider_photo_file'])) {
    http_response_code(404);
    exit('Photo not found.');
}

$storageDirectory =
    dirname(__DIR__, 4) .
    DIRECTORY_SEPARATOR . 'fixlify_private' .
    DIRECTORY_SEPARATOR . 'provider_photos';
$storageRoot = realpath($storageDirectory);
$storedName = basename($provider['provider_photo_file']);
$photoPath = realpath($storageDirectory . DIRECTORY_SEPARATOR . $storedName);

if (!$storageRoot || !$photoPath) {
    http_response_code(404);
    exit('Photo not found.');
}

$storagePrefix = rtrim($storageRoot, '\\/') . DIRECTORY_SEPARATOR;
if (stripos($photoPath, $storagePrefix) !== 0 || !is_file($photoPath)) {
    http_response_code(404);
    exit('Photo not found.');
}

if (!function_exists('finfo_open')) {
    http_response_code(500);
    exit('Unable to verify photo type.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = $finfo ? finfo_file($finfo, $photoPath) : false;
if ($finfo) {
    finfo_close($finfo);
}

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp'
];

if (!isset($allowedTypes[$mimeType])) {
    http_response_code(415);
    exit('Unsupported photo type.');
}

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($photoPath));
header('Content-Disposition: inline; filename="provider-profile-photo.' . $allowedTypes[$mimeType] . '"');
header("Content-Security-Policy: default-src 'none'; sandbox");

readfile($photoPath);
exit;
