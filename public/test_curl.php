<?php
// Tester cURL avec SSL
$ch = curl_init();

// URL avec SSL pour tester la connexion
curl_setopt($ch, CURLOPT_URL, "https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Vérification SSL

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch); // Affiche les erreurs de cURL
} else {
    echo 'cURL a fonctionné avec SSL : ' . $response; // Affiche la réponse de Google
}

curl_close($ch);
?>
