<?php
declare(strict_types=1);
require __DIR__ . '/../../frontend/init.php';
require __DIR__ . '/../lib/ride_notifications.php';

$pdo = getPDO();
// ➜ remplace par un ride_id réel existant dans ta BDD
$rideId = 1; 
$triggerUserId = (int)($_SESSION['user_id'] ?? 0);

notifyRideEvent($pdo, $rideId, 'start', $triggerUserId, true);

header('Content-Type: text/plain; charset=UTF-8');
echo "OK: notifications 'start' envoyées (voir boîte Mailtrap).";
