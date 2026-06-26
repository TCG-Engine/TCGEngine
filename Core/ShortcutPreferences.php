<?php

function DecodeShortcutWindowRegistryConfig($rawConfig) {
  if ($rawConfig === null || $rawConfig === '') return [];
  if (is_array($rawConfig)) $decoded = $rawConfig;
  else $decoded = json_decode(strval($rawConfig), true);
  if (!is_array($decoded)) return [];

  $registry = [];
  foreach ($decoded as $windowId => $entry) {
    $windowId = strval($windowId);
    if ($windowId === '') continue;
    if (!is_array($entry)) $entry = [];
    $registry[$windowId] = [
      'label' => isset($entry['label']) && trim(strval($entry['label'])) !== ''
        ? strval($entry['label'])
        : $windowId,
      'default' => !empty($entry['default']),
      'group' => isset($entry['group']) ? strval($entry['group']) : '',
      'order' => isset($entry['order']) ? intval($entry['order']) : 0,
    ];
  }

  uasort($registry, function($a, $b) {
    $orderCompare = intval($a['order']) <=> intval($b['order']);
    if ($orderCompare !== 0) return $orderCompare;
    return strcmp(strval($a['label']), strval($b['label']));
  });

  return $registry;
}

function GetShortcutWindowRegistry() {
  static $cachedRegistry = null;
  if ($cachedRegistry !== null) return $cachedRegistry;

  if (!function_exists('GetModuleConfig')) {
    $cachedRegistry = [];
    return $cachedRegistry;
  }

  $cachedRegistry = DecodeShortcutWindowRegistryConfig(GetModuleConfig('ShortcutWindows'));
  return $cachedRegistry;
}

function GetShortcutWindowDefaultMap() {
  $registry = GetShortcutWindowRegistry();
  $defaults = [];
  foreach ($registry as $windowId => $entry) {
    $defaults[$windowId] = !empty($entry['default']);
  }
  return $defaults;
}

function NormalizeShortcutPreferencesPayload($payload) {
  $registry = GetShortcutWindowRegistry();
  $defaults = GetShortcutWindowDefaultMap();

  if (is_string($payload)) {
    $decoded = json_decode($payload, true);
    $payload = is_array($decoded) ? $decoded : [];
  }
  if (!is_array($payload)) $payload = [];

  $incomingWindows = [];
  if (isset($payload['windows']) && is_array($payload['windows'])) {
    $incomingWindows = $payload['windows'];
  } else {
    $incomingWindows = $payload;
  }

  $normalizedWindows = [];
  foreach ($registry as $windowId => $entry) {
    if (array_key_exists($windowId, $incomingWindows)) {
      $normalizedWindows[$windowId] = !empty($incomingWindows[$windowId]);
    } else {
      $normalizedWindows[$windowId] = $defaults[$windowId] ?? false;
    }
  }

  return [
    'version' => 1,
    'windows' => $normalizedWindows,
  ];
}

function SerializeShortcutPreferencesPayload($payload) {
  $normalized = NormalizeShortcutPreferencesPayload($payload);
  $encoded = json_encode($normalized);
  return $encoded === false ? '{"version":1,"windows":{}}' : $encoded;
}

function GetShortcutPreferencesState($player) {
  $defaults = NormalizeShortcutPreferencesPayload([]);
  if (!function_exists('GetShortcutPreferences')) return $defaults;

  $raw = GetShortcutPreferences($player);
  if ($raw === null || $raw === '' || $raw === '-') return $defaults;
  return NormalizeShortcutPreferencesPayload($raw);
}

function SetShortcutPreferencesState($player, $payload) {
  $serialized = SerializeShortcutPreferencesPayload($payload);
  if (function_exists('SetShortcutPreferences')) {
    SetShortcutPreferences($player, $serialized);
    return;
  }
  if (function_exists('AddShortcutPreferences')) {
    AddShortcutPreferences($player, $serialized);
    return;
  }
  if (function_exists('GetShortcutPreferences')) {
    $zone = &GetShortcutPreferences($player);
    $zone = $serialized;
  }
}

function ShouldAutoPassShortcutWindow($player, $windowId) {
  $windowId = strval($windowId);
  if ($windowId === '') return false;

  $registry = GetShortcutWindowRegistry();
  if (!array_key_exists($windowId, $registry)) return false;

  $prefs = GetShortcutPreferencesState($player);
  return !empty($prefs['windows'][$windowId]);
}

function UpgradeLegacyGamestateText($gamestateText, $rootName = '') {
  if ($rootName !== 'GrandArchiveSim') return $gamestateText;

  $newline = (strpos($gamestateText, "\r\n") !== false) ? "\r\n" : "\n";
  $trimmed = preg_replace("/\r\n|\r|\n/", "\n", $gamestateText);
  $hadTrailingNewline = substr($trimmed, -1) === "\n";
  if ($hadTrailingNewline) $trimmed = substr($trimmed, 0, -1);
  $lines = $trimmed === '' ? [] : explode("\n", $trimmed);
  if (count($lines) < 4) return $gamestateText;

  $shortcutInsertIndex = GrandArchiveShortcutPreferencesInsertIndex($lines);
  if ($shortcutInsertIndex === null) return $gamestateText;

  $firstShortcut = trim($lines[$shortcutInsertIndex] ?? '');
  $secondShortcut = trim($lines[$shortcutInsertIndex + 1] ?? '');
  if ($firstShortcut !== '' && $secondShortcut !== ''
      && preg_match('/^-?\d+$/', $firstShortcut)
      && preg_match('/^-?\d+$/', $secondShortcut)) {
    array_splice($lines, $shortcutInsertIndex, 0, ['-', '-']);
  }

  $matchReplayInsertIndex = GrandArchiveMatchReplayInsertIndex($lines);
  if ($matchReplayInsertIndex === null) return $gamestateText;

  $firstReplay = trim($lines[$matchReplayInsertIndex] ?? '');
  if ($firstReplay !== '' && preg_match('/^-?\d+$/', $firstReplay)) {
    array_splice($lines, $matchReplayInsertIndex, 0, ['-', '-']);
  }

  $rebuilt = implode($newline, $lines);
  if ($hadTrailingNewline) $rebuilt .= $newline;
  return $rebuilt;
}

function GrandArchiveShortcutPreferencesInsertIndex($lines) {
  $index = 2;

  for ($i = 0; $i < 20; ++$i) {
    if (!AdvanceLegacyCountedGamestateSection($lines, $index)) return null;
  }

  for ($i = 0; $i < 2; ++$i) {
    if (!AdvanceLegacyValueGamestateSection($lines, $index)) return null;
  }

  for ($i = 0; $i < 6; ++$i) {
    if (!AdvanceLegacyCountedGamestateSection($lines, $index)) return null;
  }

  return $index;
}

function GrandArchiveMatchReplayInsertIndex($lines) {
  $index = 2;

  for ($i = 0; $i < 20; ++$i) {
    if (!AdvanceLegacyCountedGamestateSection($lines, $index)) return null;
  }

  for ($i = 0; $i < 2; ++$i) {
    if (!AdvanceLegacyValueGamestateSection($lines, $index)) return null;
  }

  for ($i = 0; $i < 6; ++$i) {
    if (!AdvanceLegacyCountedGamestateSection($lines, $index)) return null;
  }

  for ($i = 0; $i < 9; ++$i) {
    if (!AdvanceLegacyValueGamestateSection($lines, $index)) return null;
  }

  if (!AdvanceLegacyCountedGamestateSection($lines, $index)) return null;

  for ($i = 0; $i < 3; ++$i) {
    if (!AdvanceLegacyValueGamestateSection($lines, $index)) return null;
  }

  return $index;
}

function NormalizeGrandArchiveMatchReplayFieldsForComparison($gamestateText) {
  $newline = (strpos($gamestateText, "\r\n") !== false) ? "\r\n" : "\n";
  $trimmed = preg_replace("/\r\n|\r|\n/", "\n", $gamestateText);
  $hadTrailingNewline = substr($trimmed, -1) === "\n";
  if ($hadTrailingNewline) $trimmed = substr($trimmed, 0, -1);
  $lines = $trimmed === '' ? [] : explode("\n", $trimmed);

  $insertIndex = GrandArchiveMatchReplayInsertIndex($lines);
  if ($insertIndex === null) return $gamestateText;
  if ($insertIndex + 1 >= count($lines)) return $gamestateText;

  $lines[$insertIndex] = '-';
  $lines[$insertIndex + 1] = '-';

  $rebuilt = implode($newline, $lines);
  if ($hadTrailingNewline) $rebuilt .= $newline;
  return $rebuilt;
}

function AdvanceLegacyCountedGamestateSection($lines, &$index) {
  if ($index >= count($lines)) return false;
  $count = intval(trim($lines[$index]));
  ++$index;
  $index += max(0, $count);
  return $index <= count($lines);
}

function AdvanceLegacyValueGamestateSection($lines, &$index) {
  if ($index >= count($lines)) return false;
  ++$index;
  return true;
}

?>
