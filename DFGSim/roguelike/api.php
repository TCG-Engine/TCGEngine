<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
require_once __DIR__.'/Run.php';
try{
    session_name('DFGCloudbreak');session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','path'=>'/']);
    if(!session_start())throw new RuntimeException('Unable to open run session');
    $_SESSION['rgToken']??=bin2hex(random_bytes(24));$run=$_SESSION['rgRun']??null;
    $method=$_SERVER['REQUEST_METHOD']??'GET';
    if(!in_array($method,['GET','POST'],true)){http_response_code(405);header('Allow: GET, POST');throw new InvalidArgumentException('Use GET or POST');}
    if($method==='POST'){
        $body=json_decode(file_get_contents('php://input',false,null,0,8193),true,32,JSON_THROW_ON_ERROR);
        if(!is_array($body)||!is_string($body['token']??null)||!hash_equals($_SESSION['rgToken'],$body['token'])){http_response_code(403);throw new InvalidArgumentException('Reload to refresh your run token');}
        // New runs also require the last seen revision, so another tab cannot reset a newer run.
        if(($body['runId']??null)!==($run['id']??null)||($body['revision']??null)!==($run['revision']??null)){http_response_code(409);throw new LogicException('This run changed in another tab. Reload it.');}
        if(($body['command']??'')==='new')$run=RGNewRun();
        elseif(($body['command']??'')==='action'){
            if(!$run)throw new LogicException('Start a pilgrimage first');
            if(($run['version']??0)!==RG_VERSION)throw new LogicException('This prototype updated. Start a new pilgrimage.');
            if(!is_array($body['action']??null))throw new InvalidArgumentException('Invalid action');
            $run=RGApply($run,$body['action']);
        }else throw new InvalidArgumentException('Unknown command');
        $_SESSION['rgRun']=$run;
    }
    echo json_encode(['ok'=>true,'token'=>$_SESSION['rgToken'],'run'=>$run?RGObserve($run):null,
        'cards'=>array_values(array_map('DFGCardPublic',DFGCatalog())),'colors'=>DFG_STYLES,'items'=>RGItems(),'enemies'=>RGEnemies(),'animations'=>RGAnimations()],JSON_THROW_ON_ERROR);
}catch(InvalidArgumentException|LogicException|JsonException|TypeError $e){if(http_response_code()<400)http_response_code(400);echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}
catch(Throwable $e){error_log('DFG roguelike: '.$e->getMessage());http_response_code(500);echo '{"ok":false,"error":"Run error. Check the server log."}';}
