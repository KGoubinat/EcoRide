<?php
declare(strict_types=1);
require __DIR__ . '/../../frontend/init.php';
require __DIR__ . '/../lib/mailer.php';

$ok = sendEmail('test@receiver.dev', '[EcoRide] Test Mailtrap', '<p>Bonjour, test OK ✅</p>');
echo $ok ? "OK\n" : "KO\n";
