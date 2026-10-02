<?php
/** JSON Lines transport. A fresh process starts without a game; send reset first. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/Runtime.php';
require __DIR__ . '/Bot/HeuristicBot.php';
$created = false;
while (($line = fgets(STDIN)) !== false) {
    $before = $created ? PokeStateExport() : null;
    try {
        $request = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        $seat = (int)($request['player'] ?? 1);
        if (!in_array($seat,[1,2],true)) throw new InvalidArgumentException('player must be 1 or 2');
        switch ($request['command'] ?? '') {
            case 'reset':
                $deck1=isset($request['deck1'])?PokeParseDeckText($request['deck1']):PokeNamedDeck($request['deckKey1']??'sinistcha');
                $deck2=isset($request['deck2'])?PokeParseDeckText($request['deck2']):PokeNamedDeck($request['deckKey2']??'sinistcha');
                PokeCreateGame($deck1,$deck2,(int)($request['seed']??1),(int)($request['firstPlayer']??0));
                $created = true; break;
            case 'step': if (!$created) throw new LogicException('Send reset first'); PokeApplyAction($request['action']); break;
            case 'bot-step':
                if (!$created) throw new LogicException('Send reset first');
                $action = PokeBotChoose(PokeObservation($seat));
                if ($action === null) throw new LogicException('No bot action for this player');
                PokeApplyAction($action); break;
            case 'bot-run': if (!$created) throw new LogicException('Send reset first'); PokeRunBot($seat); break;
            case 'load': PokeStateImport($request['state']); $created = true; break;
            case 'observe': case 'actions': case 'export': if (!$created) throw new LogicException('Send reset first'); break;
            default: throw new InvalidArgumentException('Commands: reset, observe, actions, step, bot-step, bot-run, export, load');
        }
        $result = ($request['command'] === 'export') ? PokeStateExport() : (($request['command'] === 'actions') ? PokeLegalActions($seat) : PokeObservation($seat));
        echo json_encode(['ok'=>true,'result'=>$result], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    } catch (Throwable $error) {
        if ($before !== null) PokeStateImport($before);
        echo json_encode(['ok'=>false,'error'=>$error->getMessage()])."\n";
    }
}
