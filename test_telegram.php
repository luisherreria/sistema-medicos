<?php
// Configuración de prueba directa
$bot_token = '8973344719:AAGaLHqO7ADz2WqaMDBZhP_kAIFClYoVa0M';
$chat_id   = '1956619586';

$mensaje = "🏥 <b>vfpmedicos</b>: ¡Conexión exitosa! Tu bot de Telegram está funcionando correctamente.";

// Envío del mensaje mediante cURL
$url = "https://api.telegram.org/bot{$bot_token}/sendMessage";

$data = [
    'chat_id'    => $chat_id,
    'text'       => $mensaje,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

// Mostrar la respuesta de Telegram en pantalla
echo "<h3>Resultado del envío:</h3>";
echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";
?>