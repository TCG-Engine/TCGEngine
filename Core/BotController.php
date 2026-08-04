<?php

include_once __DIR__ . '/MatchReplay.php';

const BOT_CONTROLLER_PAYLOAD_PREFIX = 'BOTCONTROLLER:';

// Games opt in by defining GameBotControllerMode(), GetBotControllerPlayers(),
// BotControllerPendingPlayerForClient(), and ProcessBotControllerStep(). The
// shared client never needs the controlled seats' authentication credentials.

function NormalizeBotControllerPlayers($players) {
  if (!is_array($players)) return [];

  $normalized = [];
  foreach ($players as $player) {
    $player = intval($player);
    if (($player !== 1 && $player !== 2) || in_array($player, $normalized, true)) continue;
    $normalized[] = $player;
  }
  sort($normalized);
  return $normalized;
}

function BuildBotControllerClientState($folderPath = '', $gameName = '') {
  $isReplayPlayback = function_exists('MatchReplayIsPlaybackSession') && MatchReplayIsPlaybackSession();
  $mode = !$isReplayPlayback && function_exists('GameBotControllerMode') ? strval(GameBotControllerMode()) : '';
  $players = $mode !== '' && function_exists('GetBotControllerPlayers')
    ? NormalizeBotControllerPlayers(GetBotControllerPlayers())
    : [];
  $pendingPlayer = $mode !== '' && function_exists('BotControllerPendingPlayerForClient')
    ? intval(BotControllerPendingPlayerForClient())
    : 0;

  if (!in_array($pendingPlayer, $players, true)) $pendingPlayer = 0;

  return [
    'enabled' => $mode !== '' && !empty($players),
    'mode' => $mode,
    'folderPath' => strval($folderPath),
    'players' => $players,
    'pendingPlayer' => $pendingPlayer,
  ];
}

function EncodeBotControllerClientPayload($folderPath = '', $gameName = '') {
  $encoded = json_encode(BuildBotControllerClientState($folderPath, $gameName), JSON_UNESCAPED_SLASHES);
  if (!is_string($encoded)) $encoded = '{"enabled":false,"mode":"","folderPath":"","players":[],"pendingPlayer":0}';
  return BOT_CONTROLLER_PAYLOAD_PREFIX . $encoded;
}
