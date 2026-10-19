<?php
declare(strict_types=1);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/Bot/HeuristicBot.php';
require_once __DIR__ . '/Custom/Tutorial.php';
try {
    session_name('DFGSimLocal');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'path' => '/']);
    if (!session_start()) throw new RuntimeException('Cannot open local session');
    $_SESSION['dfgToken'] ??= bin2hex(random_bytes(24));
    $isTutorial = isset($_GET['tutorial']);
    $tutorial = $isTutorial ? ($_SESSION['dfgTutorial'] ?? DFGTutorialStart()) : null;
    if ($isTutorial && (($tutorial['version'] ?? 0) !== DFG_TUTORIAL_VERSION || ($tutorial['state']['rulesVersion'] ?? 0) !== DFG_RULES_VERSION)) $tutorial = DFGTutorialStart();
    $state = $isTutorial ? $tutorial['state'] : ($_SESSION['dfgState'] ?? null);
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, ['GET', 'POST'], true)) { http_response_code(405); header('Allow: GET, POST'); throw new InvalidArgumentException('Use GET or POST'); }
    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input', false, null, 0, 8193), true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($body) || !is_string($body['token'] ?? null) || !hash_equals($_SESSION['dfgToken'], $body['token'])) { http_response_code(403); throw new InvalidArgumentException('Invalid session token; reload the page'); }
        if ($isTutorial) {
            if (($body['matchId'] ?? null) !== $tutorial['matchId'] || ($body['revision'] ?? null) !== $tutorial['revision']) { http_response_code(409); throw new LogicException('The lesson changed; refresh and try again'); }
            $command = $body['command'] ?? '';
            if ($command === 'action' || $command === 'continue') {
                if ($command === 'action' && !is_array($body['action'] ?? null)) throw new InvalidArgumentException('Invalid action');
                if ($command === 'continue' && DFGTutorialPayload($tutorial)['action'] !== null) throw new InvalidArgumentException('Use the match controls to complete this step.');
                $tutorial = DFGTutorialAdvance($tutorial, $command === 'action' ? $body['action'] : null);
            } elseif ($command === 'lesson') $tutorial = DFGTutorialStart(intval($body['lesson'] ?? -1), intval($body['step'] ?? 0));
            else throw new InvalidArgumentException('Unknown tutorial command');
            $state = $tutorial['state'];
        } elseif (($body['command'] ?? '') === 'new') {
            $settings = $body['settings'] ?? [];
            if (!is_array($settings)) throw new InvalidArgumentException('Invalid match settings');
            $seed = $settings['seed'] ?? random_int(1, 2147483646);
            if (!is_int($seed)) throw new InvalidArgumentException('Seed must be an integer');
            $state = DFGNewGame($settings['humanColor'] ?? 'Red', $settings['botColor'] ?? 'Purple', $seed, $settings['first'] ?? 0, $settings['difficulty'] ?? 'tactical');
            $state['matchId'] = bin2hex(random_bytes(12));
        } elseif (($body['command'] ?? '') === 'action') {
            if ($state === null) throw new LogicException('Start a match first');
            if (($state['rulesVersion'] ?? 0) !== DFG_RULES_VERSION) { http_response_code(409); throw new LogicException('Rules or card balance changed: start a new match'); }
            if (($body['matchId'] ?? null) !== $state['matchId'] || ($body['revision'] ?? null) !== $state['revision']) { http_response_code(409); throw new LogicException('The match changed; refresh and choose again'); }
            if (!is_array($body['action'] ?? null)) throw new InvalidArgumentException('Invalid action');
            $state = DFGApply($state, 0, $body['action']);
        } else throw new InvalidArgumentException('Unknown command');
        if (!$isTutorial) {
            $state = DFGRunBot($state);
            $_SESSION['dfgState'] = $state;
        }
    }
    if ($isTutorial) {
        $_SESSION['dfgTutorial'] = $tutorial;
        $state['matchId'] = $tutorial['matchId'];
        $state['revision'] = $tutorial['revision'];
    }
    $observation = $state === null ? null : DFGObserve($state, 0);
    if ($observation !== null) $observation['needsNewMatch'] = ($state['rulesVersion'] ?? 0) !== DFG_RULES_VERSION;
    echo json_encode(['ok' => true, 'token' => $_SESSION['dfgToken'], 'state' => $observation,
        'tutorial' => $isTutorial ? DFGTutorialPayload($tutorial) : null,
        'cards' => array_values(array_map('DFGCardPublic', DFGCatalog())), 'colors' => DFG_STYLES], JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException | LogicException | JsonException | TypeError $error) {
    if (http_response_code() < 400) http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $error->getMessage()], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('DFGSim: ' . $error->getMessage());
    http_response_code(500); echo '{"ok":false,"error":"Simulator error; check server logs"}';
}
