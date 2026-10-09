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
    if ($player < 1 || $player > 4 || in_array($player, $normalized, true)) continue;
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
  // Optional pacing: a game may define GameBotControllerStepDelayMs() so the client waits that long after
  // rendering before it steps the bot, letting a human see each bot move. Games that don't define it step at once.
  $stepDelayMs = $mode !== '' && function_exists('GameBotControllerStepDelayMs')
    ? max(0, min(10000, intval(GameBotControllerStepDelayMs())))
    : 0;

  return [
    'enabled' => $mode !== '' && !empty($players),
    'mode' => $mode,
    'folderPath' => strval($folderPath),
    'players' => $players,
    'pendingPlayer' => $pendingPlayer,
    'stepDelayMs' => $stepDelayMs,
  ];
}

function EncodeBotControllerClientPayload($folderPath = '', $gameName = '') {
  $encoded = json_encode(BuildBotControllerClientState($folderPath, $gameName), JSON_UNESCAPED_SLASHES);
  if (!is_string($encoded)) $encoded = '{"enabled":false,"mode":"","folderPath":"","players":[],"pendingPlayer":0}';
  return BOT_CONTROLLER_PAYLOAD_PREFIX . $encoded;
}
