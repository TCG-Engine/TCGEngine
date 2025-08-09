<?php

$APCuEnabled = extension_loaded('apcu');

function CheckUpdate($gameName, $lastNumber)
{
  //Check if cache exists, if not create it
  $lastUpdate = GetCachePiece($gameName, 1);
  if($lastUpdate == "") {
    return true;
  }
  return intval($lastNumber) < intval($lastUpdate);
}

/*
Old schema:
1 - Update Number
2 - P1 Last Connection Time
3 - P2 Last Connection Time
4 - Player 1 status
5 - Player 2 status
6 - Last gamestate update (time)
7 - P1 Hero
8 - P2 Hero
9 - Game visibility (1 = public, 0 = private)
10 - Is Replay
11 - Number P2 disconnects
12 - Current player status (0 = active, 1 = inactive)
13 - Format (see function FormatCode)
14 - Game status (see $GS_ constants)

New schema:
1 - Update Number
*/

// $useRedis = getenv('REDIS_ENABLED') ?? false;
$useRedis = false;
$redisHost = getenv("REDIS_HOST") ?: "127.0.0.1";
$redisPort = getenv("REDIS_PORT") ?: "6379";

if ($useRedis) {
  $redis = new Redis();
  $redis->connect($redisHost, $redisPort);
}

function InitializeCache($gameName)
{
  global $updateNumber;
  WriteCache($gameName, $updateNumber);
}

function WriteCache($name, $data)
{
  global $useRedis, $redis, $APCuEnabled;
  if($name == 0) return;
  $serData = serialize($data);

  if($useRedis)
  {
    $redis->set($name, $serData);
  }
  else if ($APCuEnabled) {
    apcu_store($name, $serData, 3600);
  }
  else {
    $id = shmop_open($name, "c", 0644, 128);
    if($id == false) {
      exit;
     } else {
        $rv = shmop_write($id, $serData, 0);
    }
  }
}

function ReadCache($name)
{
  global $useRedis, $redis, $APCuEnabled;
  if($name == 0) return "";

  $data = "";
  if($useRedis)
  {
    $data = RedisReadCache($name);
    if($data == "" && is_numeric($name))
    {
      $data = ShmopReadCache($name);
    }
  }
  else if ($APCuEnabled) {
    $data = apcu_fetch($name);
  }
  else {
    $data = ShmopReadCache($name);
  }

  return unserialize($data);
}

function ShmopReadCache($name)
{
  @$id = shmop_open($name, "a", 0, 0);
  if(empty($id) || $id == false)
  {
    return "";
  }
  $data = shmop_read($id, 0, shmop_size($id));
  $data = preg_replace_callback( '!s:(\d+):"(.*?)";!', function($match) {
    return ($match[1] == strlen($match[2])) ? $match[0] : 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
    }, $data);
  return $data;
}

function RedisReadCache($name)
{
  global $redis;
  if(!isset($redis)) $redis = RedisConnect();
  return $data = $redis->get($name);
}

function RedisConnect()
{
  global $redis, $redisHost, $redisPort;
  $redis = new Redis();
  $redis->connect($redisHost, $redisPort);
  return $redis;
}

function DeleteCache($name)
{
  global $useRedis, $redis, $APCuEnabled;
  if($useRedis)
  {
    $redis->del($name);
    $redis->del($name . "GS");
  }
  else if ($APCuEnabled) {
    apcu_delete($name);
  }
  else {
    $id=shmop_open($name, "w", 0644, 128);
    if($id)
    {
      shmop_delete($id);
      shmop_close($id); //shmop_close is deprecated
    }
  }
}

function SHMOPDelimiter()
{
  return "!";
}

function SetCachePiece($name, $piece, $value)
{
  $piece -= 1;
  $cacheVal = ReadCache($name);
  if($cacheVal == "") {
    InitializeCache($name);
    $cacheVal = ReadCache($name);
  }
  $cacheArray = explode("!", $cacheVal);
  $cacheArray[$piece] = $value;
  WriteCache($name, implode("!", $cacheArray));
}

function GetCachePiece($name, $piece)
{
  $piece -= 1;
  $cacheVal = ReadCache($name);
  $cacheArray = explode("!", $cacheVal);
  if($piece >= count($cacheArray)) return "";
  return $cacheArray[$piece];
}

function IncrementCachePiece($gameName, $piece)
{
  $oldVal = GetCachePiece($gameName, $piece);
  SetCachePiece($gameName, $piece, $oldVal+1);
  return $oldVal+1;
}

function GamestateUpdated($gameName)
{
  global $currentPlayer, $updateNumber;
  $cache = ReadCache($gameName);
  $cacheArr = explode(SHMOPDelimiter(), $cache);
  $cacheArr[0] = $updateNumber;
  $currentTime = round(microtime(true) * 1000);
  $cacheArr[5] = $currentTime;
  WriteCache($gameName, implode(SHMOPDelimiter(), $cacheArr));
}

?>