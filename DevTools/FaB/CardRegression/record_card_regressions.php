<?php
/** Record reviewed declarative scenarios through the existing engine/recorder. */
declare(strict_types=1);
$repo = getenv('FAB_REGRESSION_REPO') ?: __DIR__;
while(!is_file($repo.'/Core/EngineActionRunner.php')&&dirname($repo)!==$repo)$repo=dirname($repo);
if(!is_file($repo.'/Core/EngineActionRunner.php'))$repo=str_replace('\\','/',getcwd());
chdir($repo);
require_once $repo.'/Core/EngineActionRunner.php';
EngineLoadRootRuntime('FaBSim');
require_once __DIR__.'/initial_snapshot_guard.php';
set_error_handler(function($level,$message,$file,$line){throw new ErrorException($message,0,$level,$file,$line);});
$input = $argv[1] ?? __DIR__.'/card-regression-scenarios.json';
$cases = json_decode(file_get_contents($input),true,512,JSON_THROW_ON_ERROR);
$filter = $argv[2] ?? '';
$replaceOwn=in_array('--rerecord-authored',$argv,true);
$out=[];
function FCRAction(int $seat,int $mode,string $card='',string $text=''):void {
    $result=EngineRunAction(['playerID'=>$seat,'mode'=>$mode,'cardID'=>$card,'inputText'=>$text],'FaBSim',$GLOBALS['gameName'],['updateCache'=>false,'createdBy'=>'FaBSim card regression authoring']);
    if(!$result['success'])throw new RuntimeException($result['message'] ?: 'Engine action rejected');
}
function FCRCheck(array $check):void {
    $kind=$check['kind']??'assertion';
    if($kind==='assertion'){
        $assertion=$check;unset($assertion['kind']);$assertion['viewerPlayerID']=$assertion['viewerPlayerID']??1;
        [$ok,$message]=RegressionEvaluateAssertion($assertion);
        if(!$ok)throw new RuntimeException($message);
        FCRAction(intval($assertion['viewerPlayerID']),11002,'',json_encode($assertion,JSON_THROW_ON_ERROR));
        return;
    }
    $expected=$check['value'];$state=FaBGetState();
    if($kind==='state')$actual=$state[$check['key']]??null;
    elseif($kind==='power')$actual=FaBAttackPower($state);
    elseif($kind==='defense')$actual=FaBDefenseValue($state);
    elseif($kind==='go_again'){
        $found=FaBFindUID(intval($state['attackUID']));
        $actual=$found!==null&&FaBAttackHasGoAgain($state,$found['object']);
    }
    elseif($kind==='can_activate')$actual=FaBWTRCanActivate(intval($check['player']),$check['mzID']);
    elseif($kind==='can_play')$actual=CanPlayCard(intval($check['player']),$check['mzID']);
    elseif($kind==='deck_ids')$actual=array_values(array_map(fn($o)=>$o->CardID,array_filter(GetDeck(intval($check['player'])),fn($o)=>empty($o->removed))));
    elseif($kind==='hand_ids')$actual=array_values(array_map(fn($o)=>$o->CardID,array_filter(GetHand(intval($check['player'])),fn($o)=>empty($o->removed))));
    else throw new RuntimeException('Unknown semantic check '.$kind);
    if($actual!==$expected)throw new RuntimeException($kind.' '.($check['key']??'').' expected '.json_encode($expected).' got '.json_encode($actual));
    if($kind==='state'){
        // Old null diagnostics deliberately accepted either absent or null fields.
        // Persist that policy explicitly; callers can request strict equals/null.
        $comparison=array_key_exists('comparison',$check)?$check['comparison']:($expected===null?'missing_or_null':'equals');
        $assertion=['type'=>'game_state_field','rootName'=>'FaBSim','path'=>[$check['key']],'comparison'=>$comparison];
        if($comparison==='equals')$assertion['valueJson']=json_encode($expected,JSON_THROW_ON_ERROR|JSON_PRESERVE_ZERO_FRACTION);
        FCRCheck($assertion);
    }
    if($kind==='power'){
        $found=FaBFindUID(intval($state['attackUID']));
        if($found===null)throw new RuntimeException('Expected current attack for saved power assertion');
        FCRCheck(['type'=>'computed_power_equals','mzId'=>$found['mzID'],'value'=>intval($expected)]);
    }
    if($kind==='go_again'){
        if($found===null)throw new RuntimeException('Expected current attack for saved go-again assertion');
        FCRCheck(['type'=>'computed_go_again_equals','mzId'=>$found['mzID'],'value'=>intval($expected)]);
    }
    if(in_array($kind,['hand_ids','deck_ids'],true)){
        $zone='p'.$check['player'].($kind==='hand_ids'?'Hand':'Deck');
        FCRCheck(['type'=>'zone_count','zone'=>$zone,'value'=>count($expected)]);
        // Hand/Deck arrays can retain removed tombstones until the next request.
        // Assert live membership here; full saved snapshots also pin deck order.
        foreach($expected as $id)FCRCheck(['type'=>'card_exists','zone'=>$zone,'cardID'=>$id]);
    }
}
/** Record only an actually executed, exact mode-100 rejection, never a prediction. */
function FCRRecordExpectedRejection(array $step): void {
    if (($step['expectFailure'] ?? null) !== true) throw new RuntimeException('Expected rejection requires strict expectFailure=true');
    $input = $step['action'] ?? null; $message = $step['expectedMessage'] ?? null;
    $checks = $step['unchangedChecks'] ?? null;
    if (!is_array($input) || array_is_list($input) || !is_string($message) || $message === ''
        || !is_array($checks) || !array_is_list($checks) || count($checks) === 0) {
        throw new RuntimeException('Expected rejection requires an action, exact message and unchanged native checks');
    }
    $allowed = ['playerID','mode','buttonInput','cardID','chkInput','inputText'];
    foreach ($input as $key => $value) if (!in_array($key, $allowed, true)) throw new RuntimeException('Unexpected rejection action field ' . $key);
    if (!is_int($input['playerID'] ?? null) || $input['playerID'] < 1 || $input['playerID'] > 4
        || ($input['mode'] ?? null) !== 100 || !is_string($input['cardID'] ?? null) || $input['cardID'] === '') {
        throw new RuntimeException('Expected rejection is bounded to a seat1..4 mode100 answer payload');
    }
    foreach (['buttonInput','inputText'] as $key) if (array_key_exists($key, $input) && !is_string($input[$key])) throw new RuntimeException('Rejection payload text must be a string');
    if (array_key_exists('chkInput', $input)) {
        if (!is_array($input['chkInput']) || !array_is_list($input['chkInput'])) throw new RuntimeException('Rejection chkInput must be a string list');
        foreach ($input['chkInput'] as $value) if (!is_string($value)) throw new RuntimeException('Rejection chkInput must be a string list');
    }
    foreach ($checks as $check) {
        if (!is_array($check) || !in_array($check['kind'] ?? 'assertion', ['assertion','state','power','go_again','hand_ids','deck_ids'], true)) {
            throw new RuntimeException('Expected rejection requires persisted native unchanged checks');
        }
    }
    $gameName = (string)($GLOBALS['gameName'] ?? '');
    $recording = RegressionReadRecording('FaBSim', $gameName);
    if (!str_starts_with($gameName, 'regression_fab_author_') || !is_array($recording) || empty($recording['active'])
        || !is_array($recording['actions'] ?? null) || !array_is_list($recording['actions'])) {
        throw new RuntimeException('Expected rejection requires an active owned authoring recording');
    }
    foreach ($checks as $check) FCRCheck($check);
    $recording = RegressionReadRecording('FaBSim', $gameName);
    if (!is_array($recording) || empty($recording['active']) || !is_array($recording['actions'] ?? null)
        || !array_is_list($recording['actions'])) throw new RuntimeException('Expected rejection prechecks changed the active recording');
    $beforeCount = count($recording['actions']);
    $payload = RegressionNormalizeAction($input);
    // The normal runner would sometimes auto-record a failed mode100 request.
    // Disable that path, then append once only after the observed result is exact.
    $result = EngineRunAction($payload, 'FaBSim', $gameName, [
        'updateCache'=>false, 'disableRecording'=>true, 'createdBy'=>'FaBSim card regression authoring',
    ]);
    if (($result['success'] ?? null) !== false) throw new RuntimeException('Expected rejection unexpectedly accepted or returned no explicit failure');
    if (($result['message'] ?? null) !== $message) throw new RuntimeException('Expected rejection message mismatch');
    $after = RegressionReadRecording('FaBSim', $gameName);
    if (!is_array($after) || empty($after['active']) || !is_array($after['actions'] ?? null)
        || count($after['actions']) !== $beforeCount) {
        throw new RuntimeException('Expected rejection recording changed during disabled automatic recording');
    }
    $payload['expectFailure'] = true;
    RegressionRecordAction('FaBSim', $gameName, $payload);
    $after = RegressionReadRecording('FaBSim', $gameName);
    if (!is_array($after) || empty($after['active']) || !is_array($after['actions'] ?? null) || count($after['actions']) !== $beforeCount + 1
        || ($after['actions'][$beforeCount]['expectFailure'] ?? null) !== true
        || $after['actions'][$beforeCount] !== RegressionNormalizeAction($payload)) {
        throw new RuntimeException('Expected rejection was not appended exactly once with its strict marker');
    }
    // Existing 11002 assertion recording assigns prechecks to N, postchecks N+1.
    foreach ($checks as $check) FCRCheck($check);
}

/** Opt-in only: existing scenarios retain their exact two-seat initialization. */
function FCRCaseSetup(array $case): array {
    if (!array_key_exists('activeSeats', $case)) {
        return ['seats'=>[1,2], 'seatOrder'=>'12', 'turnPlayer'=>1,
            'priority'=>1, 'state'=>$case['state'] ?? [], 'fourLive'=>false];
    }
    if ($case['activeSeats'] !== [1,2,3,4]) {
        throw new RuntimeException('Explicit activeSeats currently requires exactly [1,2,3,4]; omit it for legacy two-seat scenarios');
    }
    foreach (['heroes','health','resources','actionPoints'] as $field) {
        if (!isset($case[$field]) || !is_array($case[$field]) || !array_is_list($case[$field]) || count($case[$field]) !== 4) {
            throw new RuntimeException('Four live players require four explicit values for '.$field);
        }
    }
    foreach ([1,2,3,4] as $seat) {
        $hero = $case['heroes'][$seat-1];
        if (!is_string($hero) || $hero === '' || (!in_array('Hero', (array)CardTypes($hero), true) || !in_array('Young', (array)CardTypes($hero), true))) {
            throw new RuntimeException('Four live players require a valid young Hero card for seat '.$seat);
        }
        foreach (['health','resources','actionPoints'] as $field) {
            $value = $case[$field][$seat-1];
            if (!is_int($value) || $value < ($field === 'health' ? 1 : 0)) {
                throw new RuntimeException('Invalid four-player '.$field.' for seat '.$seat);
            }
        }
    }
    $state = $case['state'] ?? [];
    if (!is_array($state) || (array_key_exists('gameMode', $state) && $state['gameMode'] !== 'UPF')
        || (array_key_exists('passiveSeats', $state) && $state['passiveSeats'] !== [])) {
        throw new RuntimeException('Four live players require UPF gameMode and no passive seats');
    }
    $state['gameMode'] = 'UPF'; $state['passiveSeats'] = [];
    $turn = $case['turnPlayer'] ?? 1; $priority = $case['priority'] ?? $turn;
    if (!is_int($turn) || !is_int($priority) || !in_array($turn, [1,2,3,4], true) || !in_array($priority, [1,2,3,4], true)) {
        throw new RuntimeException('Four-player turnPlayer and priority must be active integer seats');
    }
    foreach ($case['zones'] ?? [] as $entry) {
        if (!is_array($entry) || !is_int($entry['player'] ?? null) || !in_array($entry['player'], [1,2,3,4], true)) {
            throw new RuntimeException('Four-player zone entry must belong to an active integer seat');
        }
    }
    foreach ($case['steps'] ?? [] as $step) {
        $kind = $step['kind'] ?? '';
        $actor = $kind === 'expect_rejection' ? ($step['action']['playerID'] ?? null) : ($step['player'] ?? null);
        if (in_array($kind, ['click','answer','expect_rejection'], true)
            && (!is_int($actor) || !in_array($actor, [1,2,3,4], true))) {
            throw new RuntimeException('Four-player action must name an active integer actor');
        }
        $checks = $kind === 'expect_rejection' ? ($step['unchangedChecks'] ?? []) : ($step['checks'] ?? []);
        foreach ($checks as $check) {
            if (($check['type'] ?? '') === 'decision_queue_empty' && strtolower((string)($check['player'] ?? 'all')) === 'all') {
                throw new RuntimeException('Four-player recipes must assert each decision queue explicitly; existing native all currently checks only seats1/2');
            }
        }
    }
    return ['seats'=>[1,2,3,4], 'seatOrder'=>'1234', 'turnPlayer'=>$turn,
        'priority'=>$priority, 'state'=>$state, 'fourLive'=>true];
}

function FCRDecisionQueues(array $seats, bool $keyed = true): array {
    $queues = []; foreach ($seats as $seat) $queues[$seat] = GetDecisionQueue($seat);
    return $keyed ? $queues : array_values($queues);
}

function FCRHandRefs(array $seats): array {
    $refs = []; foreach ($seats as $seat) $refs[$seat] = FaBChoiceRefs($seat, 'Hand');
    return $refs;
}

/** Capture only the actual owned initial file, after native recording starts. */
function FCRFailureInitialSnapshot(bool $fourLive, string $gameName): ?array {
    if (!$fourLive) return null;
    $path = RegressionRecordingInitialStatePath('FaBSim', $gameName);
    if (!is_file($path)) throw new RuntimeException('Four-player recording initial snapshot is missing');
    $bytes = file_get_contents($path);
    if (!is_string($bytes) || $bytes === '') throw new RuntimeException('Four-player recording initial snapshot is empty or unreadable');
    return ['initial_gamestate_text'=>$bytes, 'initial_gamestate_sha256'=>hash('sha256', $bytes)];
}

function FCRReady(array $until):bool {
    $state=FaBGetState();
    foreach($until as $key=>$value){
        if($key==='stackEmpty'){$actual=FaBStackTop()===null;}
        elseif($key==='stackCount'){$actual=FaBStackCount();}
        elseif($key==='decision'){$queue=GetDecisionQueue(intval($value['player']));if(!$queue||$queue[0]->Type!==$value['type'])return false;continue;}
        elseif($key==='priority')$actual=intval(GetPriorityPlayer());
        else $actual=$state[$key]??null;
        if($actual!==$value)return false;
    }
    return true;
}
$failed=0;
foreach($cases as $case){
    $slug=$case['slug'];if($filter!==''&&!str_contains($slug,$filter))continue;
    $gameDir=null; $activeSeats=[1,2]; $failureInitial=null;
    try{
        $fixtureDir=RegressionFixtureDir('FaBSim',$slug);
        if(is_dir($fixtureDir)){
            $meta=json_decode(file_get_contents($fixtureDir.'/meta.json'),true);
            if(!$replaceOwn||($meta['createdBy']??'')!=='FaBSim card regression authoring'){echo '[EXISTS] '.$slug."\n";continue;}
        }
        $setup=FCRCaseSetup($case);$activeSeats=$setup['seats'];
        InitializeGamestate();$GLOBALS['playerID']=1;$GLOBALS['currentPlayer']=1;$GLOBALS['updateNumber']=0;
        $GLOBALS['gameName']='regression_fab_author_'.uniqid();
        SetSeatOrder($setup['seatOrder']);SetLiveSeats($setup['seatOrder']);SetTurnPlayer($setup['turnPlayer']);SetTurnNumber(2);SetCurrentPhase('MAIN');SetPriorityPlayer($setup['priority']);
        FaBSetState(array_replace(FaBStateDefaults(),$setup['state']));
        foreach($activeSeats as $seat){
            $hero=$case['heroes'][$seat-1]??'ira_crimson_haze';
            AddHero($seat,CardID:$hero,Owner:$seat,Controller:$seat);
            AddHealth($seat,$case['health'][$seat-1]??20);AddActionPoints($seat,$case['actionPoints'][$seat-1]??($seat===1?1:0));AddResources($seat,$case['resources'][$seat-1]??0);
            $preferences=['windows'=>array_fill_keys(array_keys(GetShortcutWindowRegistry()),false)];
            if($setup['fourLive'])$preferences['holdPriority']=true;
            SetShortcutPreferencesState($seat,$preferences);
        }
        foreach($case['zones']??[] as $entry){
            $fn='Add'.$entry['zone'];$seat=intval($entry['player']);
            $obj=$entry['zone']==='Soul'?$fn($seat,CardID:$entry['cardID']):$fn($seat,CardID:$entry['cardID'],Owner:$seat,Controller:$seat);
            foreach($entry['properties']??[] as $key=>$value)$obj->$key=$value;
        }
        if(isset($case['priority']))SetPriorityPlayer(intval($case['priority']));
        $gameDir=$repo.'/FaBSim/Games/'.$GLOBALS['gameName'];RegressionEnsureDir($gameDir);WriteGamestate('./FaBSim/');
        FCRAction(1,11000);
        $failureInitial=FCRFailureInitialSnapshot($setup['fourLive'],$GLOBALS['gameName']);
        if(array_key_exists('expectedInitialGamestateSHA256',$case)){
            FCRValidateInitialSnapshotHash($case['expectedInitialGamestateSHA256'],RegressionRecordingInitialStatePath('FaBSim',$GLOBALS['gameName']));
        }
        foreach($case['steps'] as $index=>$step){
            $kind=$step['kind'];
            if($kind==='click'){
                FCRAction(intval($step['player']),10002,$step['mzID'].'!FSM');
            }elseif($kind==='expect_rejection'){
                FCRRecordExpectedRejection($step);
            }elseif($kind==='answer'){
                $queue=GetDecisionQueue(intval($step['player']));
                if(!$queue)throw new RuntimeException('Expected decision before answer at step '.$index);
                if(isset($step['type'])&&$queue[0]->Type!==$step['type'])throw new RuntimeException('Wrong decision type '.$queue[0]->Type);
                FCRAction(intval($step['player']),100,$step['value']);
            }elseif($kind==='pass_until'){
                for($guard=0;!FCRReady($step['until']);++$guard){
                    if($guard>=40)throw new RuntimeException('Pass limit before '.json_encode($step['until']));
                    if(FaBHasPendingDecision())throw new RuntimeException('Unexpected decision '.json_encode(FCRDecisionQueues($activeSeats,$activeSeats!==[1,2])));
                    FCRAction(intval(GetPriorityPlayer()),10001,'!CustomInput!Pass');
                }
            }elseif($kind==='checks'){
                foreach($step['checks'] as $check)FCRCheck($check);
            }else throw new RuntimeException('Unknown scenario step '.$kind);
        }
        FCRAction(1,11001);
        $recording=RegressionReadRecording('FaBSim',$GLOBALS['gameName']);
        if(empty($recording['actions']))throw new RuntimeException('No recorded game actions');
        FCRAction(1,11003,'',json_encode(['slug'=>$slug,'name'=>$case['name'],'notes'=>$case['oracle'].' Sources: '.implode(' ',$case['sources']??[])],JSON_THROW_ON_ERROR));
        $out[]=['slug'=>$slug,'status'=>'recorded_semantically_checked','actions'=>count($recording['actions']),'assertions'=>count($recording['assertions'])];
        echo '[RECORDED] '.$slug.' actions='.count($recording['actions'])."\n";
    }catch(Throwable $error){
        ++$failed;$recording=isset($GLOBALS['gameName'])?RegressionReadRecording('FaBSim',$GLOBALS['gameName']):null;
        $failure=['slug'=>$slug,'status'=>'blocked_or_incorrect','error'=>$error->getMessage(),'attempted_actions'=>$recording['actions']??[],'observed_state'=>FaBGetState(),'observed_decisions'=>FCRDecisionQueues($activeSeats),'observed_hand_refs'=>FCRHandRefs($activeSeats)];
        if($failureInitial!==null)$failure+=$failureInitial;
        $out[]=$failure;
        echo '[BLOCKED] '.$slug.': '.$error->getMessage()."\n";
    }finally{
        // Only delete the unique fixture game created above, never a user game.
        if($gameDir!==null&&str_starts_with($gameDir,$repo.'/FaBSim/Games/regression_fab_author_')){
            RegressionClearGamestateMemory($GLOBALS['gameName']);RegressionDeleteDirRecursive($gameDir);
        }
    }
}
// Retain an immutable per-run audit trail before refreshing latest-result views.
$archiveDir=dirname($input).'/recording-runs';
RegressionEnsureDir($archiveDir);
$archivePath=$archiveDir.'/'.pathinfo($input,PATHINFO_FILENAME).'-'.gmdate('Ymd\THis\Z').'-'.uniqid().'.json';
$archive=['completed_at_utc'=>gmdate('c'),'input_file'=>basename($input),
    'input_sha256'=>hash_file('sha256',$input),'filter'=>$filter,'results'=>$out];
file_put_contents($archivePath,json_encode($archive,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n",LOCK_EX);
file_put_contents(dirname($input).'/last-recording-results.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
file_put_contents(preg_replace('/\.json$/','',$input).'.recording-results.json',json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
exit($failed>0?1:0);
