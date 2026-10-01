<?php
// Local hotseat or human-versus-bot. CLI remains the privileged state transport.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) { http_response_code(403); exit; }
header('Content-Type: application/json; charset=utf-8');
try {
    $sessionPath = getenv('POKESIM_SESSION_PATH') ?: sys_get_temp_dir().'/TCGEngine-PokeSim-sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath,0700,true)) throw new RuntimeException('Cannot create local session directory');
    session_save_path($sessionPath);
    session_name('PokeSimLocal');
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','path'=>'/']);
    if (!session_start()) throw new RuntimeException('Cannot open local session');
    require __DIR__.'/Runtime.php';
    require __DIR__.'/Bot/HeuristicBot.php';
    $_SESSION['pokeToken'] ??= bin2hex(random_bytes(24));
    $seat = (int)($_GET['player'] ?? 1);
    if (!in_array($seat,[1,2],true)) throw new InvalidArgumentException('Invalid player');
    if (isset($_SESSION['pokeState'])) PokeStateImport($_SESSION['pokeState']);
    $botSeat = (int)($_SESSION['pokeBotSeat'] ?? 0);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        if (!hash_equals($_SESSION['pokeToken'], (string)($body['token'] ?? ''))) throw new InvalidArgumentException('Invalid session token');
        if (($body['command'] ?? '') === 'reset') {
            $default = file_get_contents(__DIR__.'/Decks/sinistcha.txt');
            $bot = ($body['mode'] ?? 'hotseat') === 'bot';
            PokeCreateGame(PokeParseDeckText($body['deck1'] ?? $default), PokeParseDeckText($bot ? $default : ($body['deck2'] ?? $default)), (int)($body['seed'] ?? 1), (int)($body['firstPlayer'] ?? 0));
            $botSeat = $bot ? 2 : 0;
        } else {
            if (!isset($_SESSION['pokeState'])) throw new LogicException('Create a game first');
            if (($body['revision'] ?? null) !== ($GLOBALS['updateNumber'] ?? 0)) throw new LogicException('The game changed; refresh and choose again');
            if (!empty($_SESSION['pokeBotSeat']) && ($body['action']['player'] ?? null) !== 1) throw new InvalidArgumentException('Player 2 is controlled by the bot');
            PokeApplyAction($body['action']);
        }
        if ($botSeat) PokeRunBot($botSeat);
        $_SESSION['pokeState'] = PokeStateExport();
        $_SESSION['pokeBotSeat'] = $botSeat;
    }
    $botMode = !empty($_SESSION['pokeBotSeat']);
    $state = isset($_SESSION['pokeState']) ? PokeObservation($botMode ? 1 : $seat) : null;
    if ($state !== null) $state += ['mode'=>$botMode ? 'bot' : 'hotseat','botName'=>$botMode ? 'Sinistcha heuristic bot' : null];
    echo json_encode(['ok'=>true,'token'=>$_SESSION['pokeToken'],'state'=>$state], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    http_response_code(400); echo json_encode(['ok'=>false,'error'=>$error->getMessage()]);
}
