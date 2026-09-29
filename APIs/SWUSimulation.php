<?php
/** Authenticated server-to-server SWUSim bot execution endpoint. */
declare(strict_types=1);

require_once __DIR__ . '/../SWUDeck/SimulationRuntime.php';

function swuSimulationApiRespond(int $status, array $payload): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') swuSimulationApiRespond(405, ['success' => false, 'message' => 'Use POST.']);
$config = swuSimulationConfig();
$secret = (string)($config['simulation_secret'] ?? getenv('SWU_SIMULATION_API_SECRET'));
if (strlen($secret) < 32) swuSimulationApiRespond(503, ['success' => false, 'message' => 'Remote simulation is not configured.']);
$body = file_get_contents('php://input', false, null, 0, 131073);
if ($body === false || strlen($body) > 131072) swuSimulationApiRespond(413, ['success' => false, 'message' => 'Simulation request is too large.']);
$timestamp = (string)($_SERVER['HTTP_X_SWU_SIM_TIMESTAMP'] ?? '');
$signature = (string)($_SERVER['HTTP_X_SWU_SIM_SIGNATURE'] ?? '');
if (!ctype_digit($timestamp) || abs(time() - (int)$timestamp) > 120 || !preg_match('/^[a-f0-9]{64}$/', $signature)
    || !hash_equals(hash_hmac('sha256', $timestamp . "\n" . $body, $secret), $signature)) {
    swuSimulationApiRespond(403, ['success' => false, 'message' => 'Invalid simulation authorization.']);
}
$request = json_decode($body, true);
if (!is_array($request) || !is_array($request['deck'] ?? null)) swuSimulationApiRespond(400, ['success' => false, 'message' => 'Invalid simulation request.']);
$opponent = (string)($request['opponent'] ?? '');
$seed = (string)($request['seed'] ?? '');
$samples = $request['samples'] ?? null;
if (!is_int($samples) || $samples < 1 || $samples > 20 || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $seed)) {
    swuSimulationApiRespond(400, ['success' => false, 'message' => 'Choose a seed and 1–20 games.']);
}

// Each request runs at most four games concurrently. Limit the host to two requests.
$slot = null;
for ($index = 0; $index < 2; ++$index) {
    $candidate = fopen(sys_get_temp_dir() . '/swu-simulation-api-' . $index . '.lock', 'c');
    if (!$candidate) continue;
    if (flock($candidate, LOCK_EX | LOCK_NB)) { $slot = $candidate; break; }
    fclose($candidate);
}
if (!$slot) swuSimulationApiRespond(429, ['success' => false, 'message' => 'The bot server is busy. Try again shortly.']);
try {
    swuSimulationDeckText($request['deck']);
    swuSimulationOpponentPath($opponent);
    set_time_limit(60 + 125 * (int)ceil($samples / 4));
    $result = swuSimulationRunLocal($request['deck'], $opponent, $seed, $samples, '', '', true);
    $status = 200;
    $payload = ['success' => true, 'result' => $result];
} catch (InvalidArgumentException $exception) {
    $status = 400;
    $payload = ['success' => false, 'message' => $exception->getMessage()];
} catch (Throwable $exception) {
    $status = 500;
    $payload = ['success' => false, 'message' => $exception->getMessage()];
} finally {
    flock($slot, LOCK_UN);
    fclose($slot);
}
swuSimulationApiRespond($status, $payload);
