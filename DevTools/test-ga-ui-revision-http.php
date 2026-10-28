<?php
// Isolated HTTP regression for the real ProcessInput.php endpoint.
// Run: php DevTools/test-ga-ui-revision-http.php
// Requires generated GA runtime and permission to bind a loopback socket.
// Only the auth cache is substituted (this works without the APCu extension).
chdir(dirname(__DIR__));
$repo = getcwd();
$name = '98' . random_int(100000000000, 999999999999);
$dir = "$repo/GrandArchiveSim/Games/$name";
$tmp = sys_get_temp_dir() . '/ga_ui_http_' . $name;
if (file_exists($dir) || file_exists($tmp)) throw new RuntimeException('Test directory collision');
mkdir($dir, 0700, true);
mkdir($tmp, 0700);
$server = null;
$lock = null;
$checks = 0;
function checkUIHttp($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
try {
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', "$dir/Gamestate.txt");
    $original = file_get_contents("$dir/Gamestate.txt");
    $revision = intval(explode("\n", $original)[1]);
    $key = bin2hex(random_bytes(16));
    // The router exists only in a temporary directory. No test endpoint is deployed.
    $router = '<?php ' . "\n" . '$repo = ' . var_export($repo, true) . ';' . "\n"
        . '$game = ' . var_export($name, true) . ';' . "\n"
        . '$key = ' . var_export($key, true) . ';' . "\n" . <<<'ROUTER'
$route = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/GrandArchiveSim/WebpImages/[A-Za-z0-9_-]+\.webp$~D', $route)) return false;
if (in_array($route, ['/GrandArchiveSim/UIPrototype/', '/GrandArchiveSim/UIPrototype/dist/app.js', '/GrandArchiveSim/UIPrototype/dist/app.css'], true)) {
    return false;
}
if (!in_array($route, ['/ProcessInput.php', '/GrandArchiveSim/UIState.php', '/GrandArchiveSim/UIAction.php'], true)) {
    http_response_code(404); exit;
}
// Disable the development bypass so the real seat-auth comparison is exercised.
putenv('DEVENV=false');
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
function apcu_store($key, $value, $ttl = 0) { $GLOBALS['testCache'][$key] = $value; return true; }
function apcu_fetch($key, &$hit = null) { $hit = array_key_exists($key, $GLOBALS['testCache'] ?? []); return $hit ? $GLOBALS['testCache'][$key] : false; }
function apcu_exists($key) { return array_key_exists($key, $GLOBALS['testCache'] ?? []); }
function apcu_delete($key) { unset($GLOBALS['testCache'][$key]); return true; }
chdir($repo);
require 'Core/NetworkingLibraries.php';
$APCuEnabled = true;
require_once 'Core/GameAuth.php';
SimGameWriteAuthKeys('GrandArchiveSim', $game, ['p1' => $key, 'p2' => $key]);
require ltrim($route, '/');
ROUTER;
    file_put_contents("$tmp/router.php", $router);
    // Ask the OS for a free port; the server startup check catches a lost reservation.
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$socket) throw new RuntimeException('Cannot reserve local test port');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $server = proc_open([PHP_BINARY, '-d', 'disable_functions=apcu_store,apcu_fetch,apcu_exists,apcu_delete', '-d', 'display_errors=0', '-S', $address, "$tmp/router.php"],
        [0 => ['pipe', 'r'], 1 => ['file', "$tmp/server.log", 'a'], 2 => ['file', "$tmp/server.log", 'a']], $pipes, $repo);
    if (!is_resource($server)) throw new RuntimeException('Cannot start PHP test server');
    fclose($pipes[0]);
    $ready = false;
    for ($i = 0; $i < 50; ++$i) {
        $probe = @stream_socket_client("tcp://$address", $errno, $error, .1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) throw new RuntimeException('PHP test server did not start');
    $request = function ($changes, $endpoint = 'ProcessInput.php', $post = false) use ($address, $name, $key, $revision) {
        $query = array_replace(['folderPath' => 'GrandArchiveSim', 'gameName' => $name,
            'playerID' => '1', 'authKey' => $key, 'mode' => '10000', 'buttonInput' => 'Shuffle',
            'inputText' => 'myHand', 'responseFormat' => 'json', 'expectedRevision' => (string)$revision], $changes);
        if ($query['expectedRevision'] === null) unset($query['expectedRevision']);
        $body = file_get_contents("http://$address/$endpoint" . ($post ? '' : '?' . http_build_query($query)), false,
            stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true, 'method' => $post ? 'POST' : 'GET', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $post ? http_build_query($query) : '']]));
        $reply = json_decode($body, true);
        if (!is_array($reply)) throw new RuntimeException('Endpoint did not return JSON; inspect server prerequisites');
        return $reply;
    };
    $posted = $request([], 'GrandArchiveSim/UIState.php', true);
    checkUIHttp(($posted['revision'] ?? null) === $revision, 'POST credentials return viewer projection');
    $denied = $request(['authKey' => 'wrong'], 'GrandArchiveSim/UIState.php', true);
    checkUIHttp(($denied['error'] ?? '') === 'Access denied', 'POST credentials enforce auth');
    if (in_array('--browser', $argv, true)) {
        $browser = proc_open(['node', 'GrandArchiveSim/UIPrototype/live-server-check.mjs'],
            [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $browserPipes, $repo);
        if (!is_resource($browser)) throw new RuntimeException('Cannot start browser checks');
        // Credentials travel through stdin, never command arguments or a URL.
        fwrite($browserPipes[0], json_encode(['url' => "http://$address/GrandArchiveSim/UIPrototype/", 'game' => $name, 'key' => $key, 'statePath' => "$dir/Gamestate.txt"]));
        fclose($browserPipes[0]);
        checkUIHttp(proc_close($browser) === 0, 'real browser-to-PHP viewer checks');
        checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $original, 'browser viewer leaves game unchanged');
    }
    $view = $request([], 'GrandArchiveSim/UIState.php');
    checkUIHttp(($view['revision'] ?? null) === $revision && in_array('end-main', array_column($view['commands'], 'id'), true), 'read-only projection has current revision');
    checkUIHttp($view['players'][1]['zones']['Hand']['cards'] === [], 'opponent hand identities excluded over HTTP');
    checkUIHttp($view['players'][0]['zones']['Deck']['cards'] === [], 'own deck identities excluded over HTTP');
    checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $original, 'projection does not mutate state');
    $view = $request(['authKey' => 'wrong'], 'GrandArchiveSim/UIState.php');
    checkUIHttp(($view['error'] ?? '') === 'Access denied', 'projection rejects invalid auth');
    $view = $request(['gameName' => '../123'], 'GrandArchiveSim/UIState.php');
    checkUIHttp(($view['error'] ?? '') === 'Invalid request', 'projection rejects malformed game path');
    foreach ([
        [['authKey' => 'wrong'], 'Invalid auth key'],
        [['expectedRevision' => (string)($revision - 1)], 'changed'],
        [['expectedRevision' => (string)($revision + 1)], 'changed'],
        [['expectedRevision' => ['bad']], 'Invalid'],
        [['expectedRevision' => '01'], 'Invalid'],
        [['playerID' => '3'], 'locked'],
    ] as [$changes, $message]) {
        $reply = $request($changes);
        checkUIHttp($reply['success'] === false && str_contains($reply['message'], $message), "refuses $message: " . json_encode($reply));
        checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $original, 'refusal leaves saved game unchanged');
    }
    $lock = fopen("$dir/.writelock", 'c');
    flock($lock, LOCK_EX);
    $reply = $request(['lockWaitMs' => '0']);
    checkUIHttp($reply['success'] === false && str_contains($reply['message'], 'busy'), 'overlapping writer is refused');
    flock($lock, LOCK_UN); fclose($lock); $lock = null;
    $reply = $request([]);
    checkUIHttp($reply['success'] === true, 'matching revision reaches real engine action');
    $after = file_get_contents("$dir/Gamestate.txt");
    checkUIHttp(intval(explode("\n", $after)[1]) > $revision, 'accepted action advances revision');
    $reply = $request([]);
    checkUIHttp($reply['success'] === false && str_contains($reply['message'], 'changed'), 'duplicate old command is refused');
    checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $after, 'duplicate does not write state');
    $reply = $request(['expectedRevision' => null]);
    checkUIHttp($reply['success'] === true, 'legacy request without revision remains supported');
    file_put_contents("$dir/Gamestate.txt", $original);
    $reply = $request(['command' => 'decline-choice'], 'GrandArchiveSim/UIAction.php', true);
    checkUIHttp($reply['success'] === false, 'unavailable projected action refused');
    checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $original, 'unavailable action leaves state unchanged');
    require_once 'Core/EngineActionRunner.php';
    EngineLoadRootRuntime('GrandArchiveSim');
    $gameName = $name;
    $playerID = 1;
    ParseGamestate('GrandArchiveSim/');
    $p1DecisionQueue = [new DecisionQueue('MZMAYCHOOSE myHand 1 Take_a_fast_action? 0', 'DecisionQueue', 1, 0)];
    $currentPlayer = 1;
    WriteGamestate('GrandArchiveSim/');
    $optional = file_get_contents("$dir/Gamestate.txt");
    if (in_array('--browser', $argv, true)) {
        $browser = proc_open(['node', 'GrandArchiveSim/UIPrototype/live-server-check.mjs'],
            [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $browserPipes, $repo);
        fwrite($browserPipes[0], json_encode(['url' => "http://$address/GrandArchiveSim/UIPrototype/", 'game' => $name, 'key' => $key, 'statePath' => "$dir/Gamestate.txt", 'action' => true]));
        fclose($browserPipes[0]);
        checkUIHttp(proc_close($browser) === 0, 'browser optional pass and refresh');
    }
    $view = $request([], 'GrandArchiveSim/UIState.php', true);
    checkUIHttp(($view['commands'][0]['label'] ?? '') === 'Pass priority', 'optional response projects pass command');
    foreach ([['playerID' => '2'], ['command' => 'forged'], ['authKey' => 'wrong'], ['expectedRevision' => null]] as $changes) {
        $reply = $request(array_replace(['command' => 'decline-choice'], $changes), 'GrandArchiveSim/UIAction.php', true);
        checkUIHttp($reply['success'] === false, 'invalid action refused');
        checkUIHttp(file_get_contents("$dir/Gamestate.txt") === $optional, 'invalid action preserves optional window');
    }
    $reply = $request(['command' => 'decline-choice'], 'GrandArchiveSim/UIAction.php', true);
    checkUIHttp($reply['success'] === true, 'optional pass executes through real engine');
    $afterPass = file_get_contents("$dir/Gamestate.txt");
    checkUIHttp(intval(explode("\n", $afterPass)[1]) > $revision, 'optional pass advances revision');
    $reply = $request(['command' => 'decline-choice'], 'GrandArchiveSim/UIAction.php', true);
    checkUIHttp($reply['success'] === false && file_get_contents("$dir/Gamestate.txt") === $afterPass, 'duplicate optional pass cannot advance twice');
    file_put_contents("$dir/Gamestate.txt", $original);
    ParseGamestate('GrandArchiveSim/');
    $p1DecisionQueue = [new DecisionQueue('MZCHOOSE myHand 1 Choose_a_card 0', 'DecisionQueue', 1, 0)];
    $currentPlayer = 1;
    WriteGamestate('GrandArchiveSim/');
    $choiceState = file_get_contents("$dir/Gamestate.txt");
    $view = $request([], 'GrandArchiveSim/UIState.php', true);
    $choiceRevision = strval($view['revision']);
    $command = $view['commands'][0]['id'] ?? '';
    checkUIHttp(str_starts_with($command, 'select:p1Hand-'), 'mandatory visible choice projects selection');
    if (in_array('--browser', $argv, true)) {
        $browser = proc_open(['node', 'GrandArchiveSim/UIPrototype/live-server-check.mjs'],
            [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $browserPipes, $repo);
        fwrite($browserPipes[0], json_encode(['url' => "http://$address/GrandArchiveSim/UIPrototype/", 'game' => $name, 'key' => $key, 'statePath' => "$dir/Gamestate.txt", 'action' => true, 'selectionLabel' => $view['commands'][0]['label']]));
        fclose($browserPipes[0]);
        checkUIHttp(proc_close($browser) === 0, 'browser card selection and refresh');
    }
    foreach (['select:p2Hand-0', 'select:p1Deck-0', 'select:p1Hand-999', 'decline-choice'] as $forged) {
        $reply = $request(['command' => $forged, 'expectedRevision' => $choiceRevision], 'GrandArchiveSim/UIAction.php', true);
        checkUIHttp($reply['success'] === false && file_get_contents("$dir/Gamestate.txt") === $choiceState, 'unoffered choice rejected without mutation');
    }
    $reply = $request(['command' => $command, 'expectedRevision' => $choiceRevision], 'GrandArchiveSim/UIAction.php', true);
    checkUIHttp($reply['success'] === true, 'visible choice executes through engine validator');
    $afterChoice = file_get_contents("$dir/Gamestate.txt");
    checkUIHttp(intval(explode("\n", $afterChoice)[1]) > intval($choiceRevision), 'selection advances revision');
    $reply = $request(['command' => $command, 'expectedRevision' => $choiceRevision], 'GrandArchiveSim/UIAction.php', true);
    checkUIHttp($reply['success'] === false && file_get_contents("$dir/Gamestate.txt") === $afterChoice, 'duplicate selection cannot advance twice');
    // New opaque decision submissions reject duplicates, unknown choices and bad shapes
    // before the engine sees any answer; every accepted request uses a fresh revision.
    file_put_contents("$dir/Gamestate.txt", $choiceState);
    foreach ([[], ['999'], ['0','0'], 'myHand-0'] as $bad) {
        $reply=$request(['command'=>'answer-decision','answer'=>json_encode($bad),'expectedRevision'=>$choiceRevision],'GrandArchiveSim/UIAction.php',true);
        checkUIHttp($reply['success']===false && file_get_contents("$dir/Gamestate.txt")===$choiceState,'opaque decision rejects forged answer without mutation');
    }
    $reply=$request(['command'=>'answer-decision','answer'=>'["0"]','expectedRevision'=>$choiceRevision],'GrandArchiveSim/UIAction.php',true);
    checkUIHttp($reply['success']===true,'opaque selection executes through engine');
    foreach ([['MZMODAL','1|2|Draw&Heal',['1']],['NUMBERCHOOSE','0|4','3'],['YESNO','', ['NO']],['MZMULTICHOOSE','1|2|myHand',['0']]] as [$type,$param,$answer]) {
        file_put_contents("$dir/Gamestate.txt",$original); ParseGamestate('GrandArchiveSim/');
        $p1DecisionQueue=[new DecisionQueue('MZCHOOSE myHand 1 Choose_now 0','DecisionQueue',1,0)];
        $p1DecisionQueue[0]->Type=$type; $p1DecisionQueue[0]->Param=$param; $currentPlayer=1;
        WriteGamestate('GrandArchiveSim/');
        $view=$request([],'GrandArchiveSim/UIState.php',true);
        checkUIHttp(($view['controls']['decision']['type'] ?? '')===$type,'HTTP projects '.$type);
        $reply=$request(['command'=>'answer-decision','answer'=>json_encode($answer),'expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
        checkUIHttp($reply['success']===true,'HTTP accepts '.$type);
    }
    file_put_contents("$dir/Gamestate.txt",$original);
    $view=$request([],'GrandArchiveSim/UIState.php',true);
    $plays=array_values(array_filter($view['commands'],fn($c)=>str_starts_with($c['id'],'play:')));
    checkUIHttp(!empty($plays),'real hand exposes a playable card');
    if (in_array('--browser', $argv, true)) {
        $browser=proc_open(['node','GrandArchiveSim/UIPrototype/live-server-check.mjs'],[0=>['pipe','r'],1=>STDOUT,2=>STDERR],$browserPipes,$repo);
        fwrite($browserPipes[0],json_encode(['url'=>"http://$address/GrandArchiveSim/UIPrototype/",'game'=>$name,'key'=>$key,'statePath'=>"$dir/Gamestate.txt",'action'=>true,'playLabel'=>$plays[0]['label']]));
        fclose($browserPipes[0]);
        checkUIHttp(proc_close($browser)===0,'browser card inspection and explicit play');
    }

    $reply=$request(['command'=>$plays[0]['id'],'expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
    checkUIHttp($reply['success']===true && file_get_contents("$dir/Gamestate.txt")!==$original,'projected play reaches real activation pipeline');
    file_put_contents("$dir/Gamestate.txt",$original);
    $reply=$request(['command'=>'end-main','expectedRevision'=>(string)$revision],'GrandArchiveSim/UIAction.php',true);
    checkUIHttp($reply['success']===true && file_get_contents("$dir/Gamestate.txt")!==$original,'end main uses engine priority transition');
    file_put_contents("$dir/Gamestate.txt",$original); ParseGamestate('GrandArchiveSim/');
    $beforeMaterialCount=count($p1Field); $p1Material=[]; $p1DecisionQueue=[]; $p2DecisionQueue=[];
    SetTurnNumber(3); SetTurnPlayer(1); SetCurrentPhase('MAT'); $playerID=1;
    MZAddZone(1,'myMaterial',CardNameToID('Orb of Sealing'));
    MaterializePhase(); WriteGamestate('GrandArchiveSim/');
    $view=$request([],'GrandArchiveSim/UIState.php',true);
    checkUIHttp(!empty($view['controls']['decision']['options']),'real materialization offers a card');
    $reply=$request(['command'=>'answer-decision','answer'=>'["0"]','expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
    ParseGamestate('GrandArchiveSim/');
    checkUIHttp($reply['success']===true && count(GetField(1))>$beforeMaterialCount,'materialization puts regalia onto field');
    file_put_contents("$dir/Gamestate.txt",$original); ParseGamestate('GrandArchiveSim/');
    SetTurnNumber(3); SetTurnPlayer(1); SetCurrentPhase('MAIN'); $playerID=1;
    $ally=MZAddZone(1,'myField',CardNameToID('Esteemed Knight')); $ally->Status=2;
    WriteGamestate('GrandArchiveSim/');
    $view=$request([],'GrandArchiveSim/UIState.php',true);
    $attack='attack:p1Field-'.$ally->mzIndex;
    checkUIHttp(in_array($attack,array_column($view['commands'],'id'),true),'ready ally exposes attack command');
    $reply=$request(['command'=>$attack,'expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
    ParseGamestate('GrandArchiveSim/');
    checkUIHttp($reply['success']===true && (GetCombatAttackerMZ()!==null || count(GetDecisionQueue(1))>0),'attack enters combat decision pipeline');
    file_put_contents("$dir/Gamestate.txt",$original); ParseGamestate('GrandArchiveSim/');
    SetTurnPlayer(2); DecisionQueueController::StoreVariable('GameMode','bot'); SetGABotPlayers([2]);
    WriteGamestate('GrandArchiveSim/');
    $view=$request([],'GrandArchiveSim/UIState.php',true);
    checkUIHttp(in_array('bot-step',array_column($view['commands'],'id'),true),'human seat can request configured bot step');
    $reply=$request(['command'=>'bot-step','expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
    checkUIHttp($reply['success']===true,'bot step uses existing engine controller');
    file_put_contents("$dir/Gamestate.txt",$original);
    $reply=$request(['command'=>'concede','expectedRevision'=>(string)$revision],'GrandArchiveSim/UIAction.php',true);
    $view=$request([],'GrandArchiveSim/UIState.php',true);
    checkUIHttp($reply['success']===true && $view['winner']===2 && $view['commands']===[],'concession declares winner and removes actions');
    // Complete a short two-seat game through only the new HTTP controls. Small
    // decks make the engine's normal draw-loss condition deterministic.
    file_put_contents("$dir/Gamestate.txt",$original); ParseGamestate('GrandArchiveSim/');
    $p1Deck=array_slice($p1Deck,0,2); $p2Deck=array_slice($p2Deck,0,2);
    WriteGamestate('GrandArchiveSim/'); $finished=false;
    for($step=0;$step<100;++$step) {
        $acted=false;
        foreach([1,2] as $seat) {
            $view=$request(['playerID'=>(string)$seat],'GrandArchiveSim/UIState.php',true);
            if(!empty($view['winner'])) { $finished=true; break 2; }
            $ids=array_column($view['commands'],'id');
            if(in_array('end-main',$ids,true)) $args=['command'=>'end-main'];
            elseif(isset($view['controls']['decision'])) {
                $d=$view['controls']['decision'];
                if(in_array($d['type'],['MZCHOOSE','MZMAYCHOOSE','MZMULTICHOOSE','MZMODAL'],true)) $answer=array_slice(array_column($d['options'],'id'),0,$d['minimum']);
                elseif($d['type']==='YESNO') $answer=['NO'];
                elseif($d['type']==='NUMBERCHOOSE') $answer=(string)$d['minimum'];
                else throw new RuntimeException('Unexpected short-game decision: '.$d['type']);
                $args=['command'=>'answer-decision','answer'=>json_encode($answer)];
            } else continue;
            $reply=$request($args+['playerID'=>(string)$seat,'expectedRevision'=>(string)$view['revision']],'GrandArchiveSim/UIAction.php',true);
            if($reply['success']!==true) throw new RuntimeException('Short-game action refused: '.($reply['message'] ?? ''));
            $acted=true; break;
        }
        if(!$acted) { ParseGamestate('GrandArchiveSim/'); throw new RuntimeException('No control available at step '.$step.' phase '.GetCurrentPhase().' current '.$currentPlayer.' heads '.json_encode([GetDecisionQueue(1)[0] ?? null,GetDecisionQueue(2)[0] ?? null])); }
    }
    checkUIHttp($finished,'two-seat game reaches victory using only live UI commands');
    echo "$checks HTTP checks passed\n";
} catch (Throwable $error) {
    foreach (is_file("$tmp/server.log") ? (file("$tmp/server.log") ?: []) : [] as $line) {
        if (str_contains($line, 'Fatal error')) fwrite(STDERR, $line);
    }
    throw $error;
} finally {
    if (is_resource($lock)) fclose($lock);
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    // Only remove the unique directories this test created.
    foreach ([$dir, $tmp] as $owned) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($owned, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        rmdir($owned);
    }
}
