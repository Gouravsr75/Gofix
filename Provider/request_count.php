<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/request_notifications.php';

// Hinglish: Provider ke khule requests ka count polling ke liye JSON mein dete hain.
$providerId = (int) $_SESSION['provider_id'];
syncProviderBookingNotifications($conn, $providerId);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$stmt = $conn->prepare(
    "SELECT COUNT(*) AS total, COALESCE(MAX(pn.id), 0) AS latest_id
     FROM provider_notifications pn
     INNER JOIN bookings b ON b.id = pn.booking_id
     WHERE pn.provider_id = ?
       AND pn.notification_type = 'new_booking'
       AND pn.response_status = 'pending'
       AND (b.provider_id IS NULL OR b.provider_id = pn.provider_id)
       AND b.booking_status IN ('pending', 'provider_notified')"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['count' => 0, 'latest_id' => 0]);
    exit;
}

$stmt->bind_param('i', $providerId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

echo json_encode([
    'count' => (int) ($row['total'] ?? 0),
    'latest_id' => (int) ($row['latest_id'] ?? 0),
]);
