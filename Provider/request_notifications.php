<?php

/**
 * Recover notifications for open bookings that match this provider.
 * The existence check makes this safe to call on each dashboard/inbox visit.
 */
function syncProviderBookingNotifications(mysqli $conn, int $providerId): void
{
    $sql = "INSERT INTO provider_notifications
                (provider_id, booking_id, notification_type, message, is_read)
            SELECT
                sp.id,
                b.id,
                'new_booking',
                CONCAT('New ', s.service_name, ' booking available in ', b.area,
                       '. Booking ID: ', b.booking_id),
                0
            FROM service_providers sp
            INNER JOIN bookings b
                ON b.booking_status IN ('pending', 'provider_notified')
               AND (b.provider_id IS NULL OR b.provider_id = sp.id)
            INNER JOIN services s ON s.id = b.service_id
            WHERE sp.id = ?
              AND sp.status = 'active'
              AND FIND_IN_SET(
                    LOWER(TRIM(
                        CASE
                            WHEN LOWER(TRIM(s.category)) = 'appliance'
                            THEN 'Appliance Repair'
                            ELSE s.category
                        END
                    )),
                    LOWER(REPLACE(COALESCE(sp.service_categories, ''), ', ', ','))
                  ) > 0
              AND (
                    LOWER(TRIM(COALESCE(sp.area, '')))
                        = LOWER(TRIM(COALESCE(b.area, '')))
                 OR LOWER(TRIM(COALESCE(sp.village, '')))
                        = LOWER(TRIM(COALESCE(b.village, '')))
                 OR LOWER(TRIM(COALESCE(sp.city, '')))
                        = LOWER(TRIM(COALESCE(b.city, '')))
                 OR TRIM(COALESCE(sp.pincode, ''))
                        = TRIM(COALESCE(b.pincode, ''))
              )
              AND NOT EXISTS (
                    SELECT 1
                    FROM provider_notifications existing
                    WHERE existing.provider_id = sp.id
                      AND existing.booking_id = b.id
                      AND existing.notification_type = 'new_booking'
              )";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $stmt->close();
}
