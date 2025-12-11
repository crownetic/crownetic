<?php
// email.php - simpele API endpoint voor contactformulier
// Zorg dat dit bestand op een server staat met PHP en mail() ondersteuning.

// Optioneel: als je dit vanaf een andere origin aanroept (bijv. vanaf crownetic.com):
// header('Access-Control-Allow-Origin: https://crownetic.com');
// header('Access-Control-Allow-Methods: POST');
// header('Access-Control-Allow-Headers: Content-Type');

header('Content-Type: application/json; charset=utf-8');

// Alleen POST toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Helper om veilig values op te halen
function field($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$name      = field('name');
$email     = field('email');
$company   = field('company');
$challenge = field('challenge');

// Basic validation
if ($name === '' || $email === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Naam en e-mail zijn verplicht.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Voer een geldig e-mailadres in.'
    ]);
    exit;
}

// Bouw de mail
$to      = 'crownetic@hotmail.com';
$subject = 'Nieuwe uitdaging via crownetic.com';

// Simpele header injection protectie
$nameSafe    = str_replace(["\r", "\n"], ' ', $name);
$emailSafe   = str_replace(["\r", "\n"], ' ', $email);
$companySafe = str_replace(["\r", "\n"], ' ', $company);

$lines = [];
$lines[] = "Naam: {$nameSafe}";
$lines[] = "E-mailadres: {$emailSafe}";
if ($companySafe !== '') {
    $lines[] = "Bedrijf: {$companySafe}";
}
$lines[] = "";
$lines[] = "Uitdaging:";
$lines[] = $challenge !== '' ? $challenge : '(geen tekst ingevuld)';

$body = implode("\n", $lines);

// Pas dit aan naar iets wat je host toestaat:
$fromEmail = 'no-reply@crownetic.com'; // of bv. no-reply@jouwhostdomein

$headers   = [];
$headers[] = 'From: ' . $fromEmail;
$headers[] = 'Reply-To: ' . $emailSafe;
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=utf-8';
$headers[] = 'X-Mailer: PHP/' . phpversion();

$success = @mail($to, $subject, $body, implode("\r\n", $headers));

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => 'Bedankt, je bericht is verstuurd.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Er ging iets mis bij het versturen. Probeer het later opnieuw of mail direct.'
    ]);
}
