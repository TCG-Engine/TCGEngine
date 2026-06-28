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

?>
