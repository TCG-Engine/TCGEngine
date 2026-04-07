<?php

function GetDeterministicRandomCounter() {
  global $gRandomCounter;
  if (!isset($gRandomCounter) || !is_numeric($gRandomCounter)) {
    $gRandomCounter = 0;
  }
  return intval($gRandomCounter);
}

function SetDeterministicRandomCounter($value) {
  global $gRandomCounter;
  $gRandomCounter = intval($value);
}

function IncrementDeterministicRandomCounter() {
  $next = GetDeterministicRandomCounter() + 1;
  SetDeterministicRandomCounter($next);
  return $next;
}

function EngineSnapshotState() {
  global $currentPlayer, $updateNumber;

  $snapshot = [
    'currentPlayer' => isset($currentPlayer) ? $currentPlayer : 0,
    'updateNumber' => isset($updateNumber) ? $updateNumber : 0,
    'randomCounter' => GetDeterministicRandomCounter(),
    'zones' => []
  ];

  if (function_exists('GetAllZones') && function_exists('GetZone')) {
    foreach (GetAllZones() as $zoneName) {
      $snapshot['zones'][$zoneName] = GetZone($zoneName);
    }
    return $snapshot;
  }

  foreach ($GLOBALS as $key => $value) {
    if ($key === 'GLOBALS') continue;
    if (preg_match('/^p[12][A-Z]/', $key) || preg_match('/^g[A-Z]/', $key)) {
      $snapshot['zones'][$key] = $value;
    }
  }

  ksort($snapshot['zones']);
  return $snapshot;
}

function EngineDeterministicHashMaterial() {
  return serialize(EngineSnapshotState());
}

function EngineDeterministicBytes($length) {
  $bytes = '';
  $counter = GetDeterministicRandomCounter();
  $material = EngineDeterministicHashMaterial();

  while (strlen($bytes) < $length) {
    $bytes .= hash('sha256', $material . '|' . $counter, true);
    ++$counter;
  }

  IncrementDeterministicRandomCounter();
  return substr($bytes, 0, $length);
}

function EngineRandomInt($min, $max) {
  $min = intval($min);
  $max = intval($max);
  if ($max < $min) {
    throw new InvalidArgumentException('EngineRandomInt max must be >= min.');
  }
  if ($min === $max) return $min;

  $range = $max - $min + 1;
  $limit = intdiv(0x100000000, $range) * $range;

  do {
    $bytes = EngineDeterministicBytes(4);
    $value = unpack('N', $bytes)[1];
  } while ($value >= $limit);

  return $min + ($value % $range);
}

function EngineShuffle(&$array, $useStrongEntropy = false) {
  if (!is_array($array)) return;

  for ($i = count($array) - 1; $i > 0; --$i) {
    $swapIndex = $useStrongEntropy ? random_int(0, $i) : EngineRandomInt(0, $i);
    if ($swapIndex === $i) continue;

    $tmp = $array[$i];
    $array[$i] = $array[$swapIndex];
    $array[$swapIndex] = $tmp;
  }
}
