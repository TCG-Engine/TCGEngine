<?php
if(!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)){http_response_code(403);exit;}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if($_SERVER['REQUEST_METHOD']!=='POST')throw new InvalidArgumentException('Use POST');
    $sessionPath=getenv('POKESIM_SESSION_PATH')?:sys_get_temp_dir().'/TCGEngine-PokeSim-sessions';
    if(!is_dir($sessionPath))throw new RuntimeException('Open PokeSim first');
    session_save_path($sessionPath);session_name('PokeSimLocal');
    if(!session_start(['read_and_close'=>true]))throw new RuntimeException('Cannot open local session');
    $body=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if(!isset($_SESSION['pokeToken'])||!hash_equals($_SESSION['pokeToken'],(string)($body['token']??'')))
        throw new InvalidArgumentException('Invalid session token');
    $seed=filter_var($body['seed']??null,FILTER_VALIDATE_INT);
    $pairs=filter_var($body['pairs']??null,FILTER_VALIDATE_INT);
    if($seed===false||$pairs===false||$pairs<1||$pairs>5||$seed<1||$seed>2147483647-$pairs+1)
        throw new InvalidArgumentException('Use 1–5 seed pairs per request and a positive 32-bit seed');
    require __DIR__.'/Runtime.php';
    require __DIR__.'/Bot/HeuristicBot.php';
    require __DIR__.'/Bot/Simulation.php';
    $policy=PokeSimulationPolicy();
    if(isset($body['policy'])&&!hash_equals($policy,(string)$body['policy']))
        throw new RuntimeException('The bot or rules changed during this batch. Start a new batch.');
    $rows=PokeSimulatePairs($seed,$pairs,1500,$body['deck1']??'sinistcha',$body['deck2']??'sinistcha');
    echo json_encode(['ok'=>true,'policy'=>$policy,'results'=>$rows,'summary'=>PokeSimulationSummary($rows)],JSON_THROW_ON_ERROR);
} catch(Throwable $error){
    http_response_code(400);echo json_encode(['ok'=>false,'error'=>$error->getMessage()],JSON_THROW_ON_ERROR);
}
