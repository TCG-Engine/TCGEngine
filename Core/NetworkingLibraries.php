<?php

include_once __DIR__ . '/GameAuth.php';

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

function GetChatMessagesCacheKey($gameName)
{
  return "chat_" . strval($gameName);
}

function GetChatVersionCacheKey($gameName)
{
  return "chat_version_" . strval($gameName);
}

function GetChatUpdateVersion($gameName)
{
  global $APCuEnabled;
  if(!$APCuEnabled || !function_exists('apcu_fetch')) return 0;
  $version = apcu_fetch(GetChatVersionCacheKey($gameName));
  if($version === false) return 0;
  return intval($version);
}

function HasChatUpdate($gameName, $lastChatVersion)
{
  return intval($lastChatVersion) < GetChatUpdateVersion($gameName);
}

function IncrementChatUpdateVersion($gameName)
{
  global $APCuEnabled;
  if(!$APCuEnabled || !function_exists('apcu_inc') || !function_exists('apcu_store')) return 0;
  $success = false;
  $key = GetChatVersionCacheKey($gameName);
  $newVersion = apcu_inc($key, 1, $success, 3600);
  if(!$success) {
    apcu_store($key, 1, 3600);
    return 1;
  }
  return intval($newVersion);
}

function GetChatMessagesSince($gameName, $lastChatID = 0)
{
  global $APCuEnabled;
  if(!$APCuEnabled || !function_exists('apcu_fetch')) return [];
  $messages = apcu_fetch(GetChatMessagesCacheKey($gameName));
  if($messages === false || !is_array($messages)) return [];
  $lastChatID = intval($lastChatID);
  return array_values(array_filter($messages, function($message) use ($lastChatID) {
    return isset($message['id']) && intval($message['id']) > $lastChatID;
  }));
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

function ActiveGameIndexKey()
{
  return "tcgengine:activegames:index";
}

function ReadActiveGameIndex()
{
  global $APCuEnabled;
  if(!$APCuEnabled || !function_exists('apcu_fetch')) return [];
  $index = apcu_fetch(ActiveGameIndexKey());
  return (is_array($index) ? $index : []);
}

function WriteActiveGameIndex($index, $ttl = 60)
{
  global $APCuEnabled;
  if(!$APCuEnabled || !function_exists('apcu_store')) return;
  if(!is_array($index)) $index = [];
  apcu_store(ActiveGameIndexKey(), $index, $ttl);
}

function RegisterActiveGame($rootName, $gameName, $isPrivate = false)
{
  if($rootName === "" || $gameName === "") return;
  $now = time();
  $key = strval($rootName) . ":" . strval($gameName);
  $index = ReadActiveGameIndex();
  $existing = isset($index[$key]) && is_array($index[$key]) ? $index[$key] : [];
  $index[$key] = [
    'rootName' => strval($rootName),
    'gameName' => strval($gameName),
    'isPrivate' => boolval($isPrivate),
    'createdAt' => isset($existing['createdAt']) ? intval($existing['createdAt']) : $now,
    'lastUpdatedAt' => $now,
  ];
  WriteActiveGameIndex($index);
}

function TouchActiveGame($rootName, $gameName)
{
  if($rootName === "" || $gameName === "") return;
  $key = strval($rootName) . ":" . strval($gameName);
  $index = ReadActiveGameIndex();
  if(!isset($index[$key]) || !is_array($index[$key])) {
    RegisterActiveGame($rootName, $gameName, SimGameIsPrivateGame($rootName, $gameName));
    return;
  }
  $index[$key]['isPrivate'] = SimGameIsPrivateGame($rootName, $gameName);
  $index[$key]['lastUpdatedAt'] = time();
  WriteActiveGameIndex($index);
}

?>
