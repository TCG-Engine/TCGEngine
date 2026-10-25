<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/World.php';
$lock=null;
try{
    session_name('DFGAdventure');session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','path'=>'/']);
    if(!session_start())throw new RuntimeException('Unable to open session');
    $_SESSION['adToken']??=bin2hex(random_bytes(24));$token=$_SESSION['adToken'];
    $saveId=$_COOKIE['DFGChallenger']??'';
    if(!is_string($saveId)||!preg_match('/^[a-f0-9]{32}$/D',$saveId)){
        $saveId=bin2hex(random_bytes(16));setcookie('DFGChallenger',$saveId,['expires'=>time()+31536000,'path'=>'/','httponly'=>true,'samesite'=>'Strict','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']);
    }
    // The private save capability is an HttpOnly cookie; save files also exit if requested as PHP.
    $dir=__DIR__.'/.saves';if(!is_dir($dir)&&!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Unable to create save folder');
    $lock=fopen($dir.'/'.$saveId.'.lock.php','c+');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Unable to lock save');
    $file=$dir.'/'.$saveId.'.php';$prefix="<?php exit; ?>\n";$fresh=!is_file($file);
    $journey=$fresh?ADNewJourney():json_decode(substr(file_get_contents($file),strlen($prefix)),true,64,JSON_THROW_ON_ERROR);
    if(($journey['version']??0)!==AD_VERSION)throw new LogicException('This journey needs an update. Your saved progress has been preserved.');
    $originalRevision=$journey['revision'];$method=$_SERVER['REQUEST_METHOD']??'GET';if(!in_array($method,['GET','POST'],true)){http_response_code(405);header('Allow: GET, POST');throw new InvalidArgumentException('Use GET or POST.');}
    if($method==='POST'){
        $body=json_decode(file_get_contents('php://input',false,null,0,16385),true,32,JSON_THROW_ON_ERROR);
        if(!is_array($body)||!is_string($body['token']??null)||!hash_equals($token,$body['token'])){http_response_code(403);throw new InvalidArgumentException('Reload to refresh your journey token.');}
        if(($body['runId']??$body['journeyId']??null)!==$journey['id']||($body['revision']??null)!==$journey['revision']){http_response_code(409);throw new LogicException('Your journey changed in another tab. Reload to continue from its latest position.');}
        if(($body['command']??'')!=='action'||!is_array($body['action']??null))throw new InvalidArgumentException('Invalid adventure action.');
        $journey=ADApply($journey,$body['action']);
    }
    $journey=ADUpgradeStarter($journey);
    if($fresh||$method==='POST'||$journey['revision']!==$originalRevision){
        $temporary=$dir.'/'.$saveId.'.'.bin2hex(random_bytes(5)).'.tmp.php';
        if(file_put_contents($temporary,$prefix.json_encode($journey,JSON_THROW_ON_ERROR),LOCK_EX)===false)throw new RuntimeException('Unable to save journey');
        if(!rename($temporary,$file)){@unlink($temporary);throw new RuntimeException('Unable to finish saving journey');}
    }
    $view=ADObserve($journey);
    echo json_encode(['ok'=>true,'token'=>$token,'journey'=>$view,'cards'=>array_values(array_map('ADPublicCard',DFGCatalog())),
        'colors'=>DFG_STYLES,'items'=>ADItems(),'enemies'=>ADPublicEnemies(),'baselineCards'=>ADBaselineCards(),'animations'=>ADAnimations(),'map'=>ADMap($view['zone'])],JSON_THROW_ON_ERROR);
}catch(InvalidArgumentException|LogicException|JsonException|TypeError $e){if(http_response_code()<400)http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('DFG adventure: '.$e->getMessage());http_response_code(500);echo '{"ok":false,"error":"Unable to load your journey. Your saved progress is preserved."}';}
finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}
