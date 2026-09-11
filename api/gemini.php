<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_role('profesor');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

csrf_verify();

$user = current_user();
$materia = (string) $user['materia'];

$cantidad = (int) ($_POST['cantidad'] ?? 5);
$dificultad = (string) ($_POST['dificultad'] ?? 'facil');
$tema = trim((string) ($_POST['tema'] ?? ''));

if ($cantidad < 3) $cantidad = 3;
if ($cantidad > 10) $cantidad = 10;

if (!in_array($dificultad, ['facil', 'medio', 'dificil'], true)) {
    $dificultad = 'facil';
}

$difLabel = match ($dificultad) {
    'facil' => 'Fácil',
    'medio' => 'Medio',
    'dificil' => 'Difícil',
    default => 'Fácil',
};

$apiKey = env('GEMINI_API_KEY', '');
if ($apiKey === '' || $apiKey === null) {
    echo json_encode(['ok' => false, 'error' => 'No hay clave de API configurada. Agrega GEMINI_API_KEY en .env']);
    exit;
}

$temaStr = $tema !== '' ? " sobre el tema: {$tema}" : '';

if ($materia === 'ingles') {
    $prompt = "Eres un profesor de inglés para estudiantes de 4° de primaria en Colombia. Genera {$cantidad} ejercicios de opción múltiple de nivel {$difLabel}{$temaStr}. Cada ejercicio debe tener una pregunta, 4 opciones y la respuesta correcta indicada con el número de opción (1-4). Los ejercicios deben ser apropiados para niños de 9-10 años.";
} else {
    $prompt = "Eres un profesor de matemáticas para estudiantes de 4° de primaria en Colombia. Genera {$cantidad} ejercicios de opción múltiple de nivel {$difLabel}{$temaStr}. Cada ejercicio debe tener una pregunta, 4 opciones y la respuesta correcta indicada con el número de opción (1-4). Los ejercicios deben ser apropiados para niños de 9-10 años.";
}

$prompt .= "\n\nResponde ÚNICAMENTE con un JSON válido con esta estructura exacta, sin texto adicional:\n";
$prompt .= '{"ejercicios":[{"pregunta":"...","opcion_1":"...","opcion_2":"...","opcion_3":"...","opcion_4":"...","respuesta_correcta":1}]}';

$payload = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature'      => 0.8,
        'maxOutputTokens'  => 4096,
        'responseMimeType' => 'application/json',
    ],
], JSON_UNESCAPED_UNICODE);

$endpoints = [
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent',
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent',
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.7-flash:generateContent',
    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent',
];

$lastError = null;

foreach ($endpoints as $baseUrl) {
    $apiUrl = $baseUrl . '?key=' . $apiKey;

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $shortUrl = str_replace('https://generativelanguage.googleapis.com/', '', $baseUrl);

    if ($curlError) {
        $lastError = "{$shortUrl}: " . $curlError;
        continue;
    }

    if ($httpCode === 404 || $httpCode === 400 || $httpCode === 403) {
        $errData = json_decode($response, true);
        $msg = $errData['error']['message'] ?? "HTTP {$httpCode}";
        $lastError = "{$shortUrl}: {$msg}";
        continue;
    }

    if ($httpCode !== 200) {
        $errData = json_decode($response, true);
        $msg = $errData['error']['message'] ?? "HTTP {$httpCode}";
        $lastError = "{$shortUrl}: {$msg}";
        continue;
    }

    $data = json_decode($response, true);
    if (!$data) {
        $lastError = "{$shortUrl}: Respuesta no es JSON válido.";
        continue;
    }

    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ($text === '') {
        $reason = $data['candidates'][0]['finishReason'] ?? 'desconocido';
        $lastError = "{$shortUrl}: No generó texto (reason: {$reason})";
        continue;
    }

    $text = trim($text);
    if (str_starts_with($text, '```')) {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
    }

    $ejercicios = json_decode($text, true);
    if (!$ejercicios || !isset($ejercicios['ejercicios']) || !is_array($ejercicios['ejercicios'])) {
        $lastError = "{$shortUrl}: No pudo interpretar el JSON de respuesta.";
        continue;
    }

    $validos = [];
    foreach ($ejercicios['ejercicios'] as $ej) {
        if (empty($ej['pregunta']) || empty($ej['opcion_1']) || empty($ej['opcion_2'])) {
            continue;
        }
        $correcta = (int) ($ej['respuesta_correcta'] ?? 1);
        if ($correcta < 1 || $correcta > 4) $correcta = 1;
        $validos[] = [
            'pregunta'           => mb_substr(trim($ej['pregunta']), 0, 2000),
            'opcion_1'           => mb_substr(trim($ej['opcion_1']), 0, 255),
            'opcion_2'           => mb_substr(trim($ej['opcion_2']), 0, 255),
            'opcion_3'           => mb_substr(trim($ej['opcion_3'] ?? ''), 0, 255),
            'opcion_4'           => mb_substr(trim($ej['opcion_4'] ?? ''), 0, 255),
            'respuesta_correcta' => $correcta,
        ];
    }

    if (empty($validos)) {
        $lastError = "{$shortUrl}: Ejercicios generados pero ninguno válido.";
        continue;
    }

    echo json_encode(['ok' => true, 'ejercicios' => $validos]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Ningún modelo funcionó. ' . $lastError]);
