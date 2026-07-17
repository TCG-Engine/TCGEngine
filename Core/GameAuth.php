<?php

function SimGameSanitizeRootName($rootName)
{
  return preg_replace('/[^A-Za-z0-9_]/', '', strval($rootName));
}

function SimGameAuthKeysPath($rootName, $gameName)
{
  $rootName = SimGameSanitizeRootName($rootName);
  $gameName = strval($gameName);
  if ($rootName === '' || $gameName === '') return '';

  return dirname(__DIR__) . '/' . $rootName . '/Games/' . $gameName . '/AuthKeys.json';
}

function SimGameWriteAuthKeys($rootName, $gameName, $authKeys)
{
  $path = SimGameAuthKeysPath($rootName, $gameName);
  if ($path === '' || !is_array($authKeys)) return false;

  $payload = [
    'p1' => strval($authKeys['p1'] ?? ''),
    'p2' => strval($authKeys['p2'] ?? ''),
    'p3' => strval($authKeys['p3'] ?? ''),
    'p4' => strval($authKeys['p4'] ?? ''),
    'spectator' => strval($authKeys['spectator'] ?? ''),
    'isPrivate' => !empty($authKeys['isPrivate']),
    'updatedAt' => time(),
  ];

  $encoded = json_encode($payload);
  if ($encoded === false) return false;

  return file_put_contents($path, $encoded, LOCK_EX) !== false;
}

function SimGameBuildAuthKeysFromLobby($lobby)
{
  $authKeys = [
    'p1' => '', 'p2' => '', 'p3' => '', 'p4' => '',
    'spectator' => '',
    'isPrivate' => is_object($lobby) && !empty($lobby->isPrivate),
  ];
  if (!is_object($lobby) || !isset($lobby->players) || !is_array($lobby->players)) return $authKeys;

  foreach ($lobby->players as $index => $player) {
    if (!is_object($player) || !method_exists($player, 'getAuthKey')) continue;

    $seat = 0;
    if (method_exists($player, 'getGamePlayerID')) $seat = intval($player->getGamePlayerID());
    if ($seat <= 0 && method_exists($player, 'getPlayerID')) $seat = intval($player->getPlayerID());
    if ($seat <= 0) $seat = intval($index) + 1;
    if ($seat < 1 || $seat > 4) continue;   // Twin Suns: up to 4 seats

    $authKeys['p' . $seat] = strval($player->getAuthKey());
  }

  // Hotseat: one person plays both seats from one browser, so both seats share P1's key.
  if (is_object($lobby) && isset($lobby->format) && strtolower((string)$lobby->format) === 'hotseat') {
    $authKeys['p2'] = $authKeys['p1'];
  }

  if (!empty($authKeys['isPrivate'])) {
    $authKeys['spectator'] = bin2hex(random_bytes(16));
  }

  return $authKeys;
}

function SimGameWriteAuthKeysFromLobby($rootName, $gameName, $lobby)
{
  return SimGameWriteAuthKeys($rootName, $gameName, SimGameBuildAuthKeysFromLobby($lobby));
}

function SimGameReadAuthKeys($rootName, $gameName)
{
  $path = SimGameAuthKeysPath($rootName, $gameName);
  if ($path === '' || !is_file($path)) {
    return ['p1' => '', 'p2' => '', 'p3' => '', 'p4' => '', 'spectator' => '', 'isPrivate' => false];
  }

  $decoded = json_decode(file_get_contents($path), true);
  if (!is_array($decoded)) {
    return ['p1' => '', 'p2' => '', 'p3' => '', 'p4' => '', 'spectator' => '', 'isPrivate' => false];
  }

  return [
    'p1' => strval($decoded['p1'] ?? ''),
    'p2' => strval($decoded['p2'] ?? ''),
    'p3' => strval($decoded['p3'] ?? ''),
    'p4' => strval($decoded['p4'] ?? ''),
    'spectator' => strval($decoded['spectator'] ?? ''),
    'isPrivate' => !empty($decoded['isPrivate']),
  ];
}

function SimGameGetSeatAuthKey($rootName, $gameName, $playerID)
{
  $seat = intval($playerID);
  if ($seat < 1 || $seat > 4) return '';

  $authKeys = SimGameReadAuthKeys($rootName, $gameName);
  return strval($authKeys['p' . $seat] ?? '');
}

function SimGameResolvePresentedAuthKey($authKey = '')
{
  $authKey = trim(strval($authKey));
  if ($authKey !== '') return $authKey;
  if (isset($_COOKIE['lastAuthKey'])) return trim(strval($_COOKIE['lastAuthKey']));
  return '';
}

function SimGameIsPrivateGame($rootName, $gameName)
{
  $authKeys = SimGameReadAuthKeys($rootName, $gameName);
  return !empty($authKeys['isPrivate']);
}

function SimGameGetSpectatorAuthKey($rootName, $gameName)
{
  $authKeys = SimGameReadAuthKeys($rootName, $gameName);
  return strval($authKeys['spectator'] ?? '');
}

function SimGameValidateSeatAuth($rootName, $gameName, $playerID, $authKey = '')
{
  $expectedKey = SimGameGetSeatAuthKey($rootName, $gameName, $playerID);
  if ($expectedKey === '') return true;

  $presentedKey = SimGameResolvePresentedAuthKey($authKey);
  if ($presentedKey === '') return false;

  return hash_equals($expectedKey, $presentedKey);
}

// True when the current browser is authenticated as a logged-in account. Self-contained so it
// works from every caller (NextTurn.php closes its session before the auth check; GetNextTurn.php
// never starts one): it opens the session only if needed, reads the flag, then releases the lock
// it opened so it stays safe inside GetNextTurn's long-poll loop.
function SimGameSpectatorIsLoggedIn()
{
  $started = false;
  if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
    $started = true;
  }
  $loggedIn = isset($_SESSION['useruid']);
  if ($started) session_write_close();
  return $loggedIn;
}

// True when a viewer is being denied specifically because SWUSim public spectating now requires a
// logged-in account (used by NextTurn.php to redirect to login instead of the generic auth page).
function SimGameSpectatorLoginRequiredMissing($rootName, $gameName, $viewerInfo)
{
  if ($rootName !== 'SWUSim') return false;
  if (!is_array($viewerInfo) || empty($viewerInfo['isSpectator'])) return false;
  if (SimGameIsPrivateGame($rootName, $gameName)) return false; // private games gate on the shared key, not login
  return !SimGameSpectatorIsLoggedIn();
}

function SimGameValidateSpectatorAuth($rootName, $gameName, $authKey = '')
{
  if (!SimGameIsPrivateGame($rootName, $gameName)) {
    // Public game: SWUSim requires a logged-in account to spectate; other sims stay open to all.
    if ($rootName === 'SWUSim') return SimGameSpectatorIsLoggedIn();
    return true;
  }

  $expectedKey = SimGameGetSpectatorAuthKey($rootName, $gameName);
  if ($expectedKey === '') return false;

  $presentedKey = SimGameResolvePresentedAuthKey($authKey);
  if ($presentedKey === '') return false;

  return hash_equals($expectedKey, $presentedKey);
}

function SimGameValidateViewerAuth($rootName, $gameName, $viewerInfo, $authKey = '')
{
  if (!is_array($viewerInfo)) return false;
  if (!empty($viewerInfo['isSpectator'])) {
    return SimGameValidateSpectatorAuth($rootName, $gameName, $authKey);
  }

  return SimGameValidateSeatAuth($rootName, $gameName, $viewerInfo['viewerSeat'] ?? 0, $authKey);
}

function SimGameRenderInvalidAuthPage($rootName, $gameName, $playerID)
{
  http_response_code(403);

  $isSpectator = strtoupper(strval($playerID)) === 'S';
  $isPrivateGame = SimGameIsPrivateGame($rootName, $gameName);
  $safeRootName = htmlspecialchars(strval($rootName), ENT_QUOTES, 'UTF-8');
  $safeGameName = htmlspecialchars(strval($gameName), ENT_QUOTES, 'UTF-8');
  $safePlayerID = htmlspecialchars(strval($playerID), ENT_QUOTES, 'UTF-8');
  $menuHref = './SharedUI/Sites/' . rawurlencode(strval($rootName)) . '/MainMenu.php';
  $spectatePerspective = $isSpectator ? '1' : strval($playerID);
  $spectateHref = './NextTurn.php?playerID=S&viewerPerspective=' . rawurlencode($spectatePerspective) . '&gameName=' . rawurlencode(strval($gameName)) . '&folderPath=' . rawurlencode(strval($rootName));
  $heading = $isSpectator ? 'This spectator link is no longer valid.' : 'This seat link is no longer valid.';
  $primaryText = $isSpectator
    ? 'The game is still there, but this browser is not currently authenticated to spectate this private match.'
    : 'The game is still there, but this browser is not currently authenticated as player ' . $safePlayerID . '.';
  $secondaryText = $isSpectator
    ? 'This usually happens after opening an old private spectate link or after the game was recreated.'
    : 'This usually happens after opening an old link, switching devices, or starting a newer session for the same seat.';
  $spectatorButtonLabel = $isPrivateGame ? 'Open with Spectator Link' : 'Open as Spectator';
  $showSpectatorButton = !$isPrivateGame;
  $footnote = $isPrivateGame
    ? 'For private games, a player must share a fresh spectate link from inside the match.'
    : 'If you expected to keep playing, reopen the game from the matching sim menu so it can use your current saved session.';

  echo "<!doctype html>\n";
  echo "<html lang=\"en\">\n";
  echo "<head>\n";
  echo "  <meta charset=\"utf-8\">\n";
  echo "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
  echo "  <title>Session Expired</title>\n";
  echo "  <style>\n";
  echo "    :root { color-scheme: dark; }\n";
  echo "    body {\n";
  echo "      margin: 0;\n";
  echo "      min-height: 100vh;\n";
  echo "      display: flex;\n";
  echo "      align-items: center;\n";
  echo "      justify-content: center;\n";
  echo "      padding: 24px;\n";
  echo "      font-family: Roboto, Barlow, system-ui, sans-serif;\n";
  echo "      background:\n";
  echo "        radial-gradient(circle at top, rgba(201, 168, 76, 0.18), transparent 38%),\n";
  echo "        linear-gradient(180deg, #08131f 0%, #10243a 100%);\n";
  echo "      color: #f2ead7;\n";
  echo "    }\n";
  echo "    .card {\n";
  echo "      width: min(680px, 100%);\n";
  echo "      padding: 32px;\n";
  echo "      border-radius: 18px;\n";
  echo "      border: 1px solid rgba(201, 168, 76, 0.28);\n";
  echo "      background: rgba(8, 19, 31, 0.86);\n";
  echo "      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.38);\n";
  echo "      backdrop-filter: blur(12px);\n";
  echo "    }\n";
  echo "    .eyebrow {\n";
  echo "      display: inline-block;\n";
  echo "      margin-bottom: 12px;\n";
  echo "      padding: 6px 10px;\n";
  echo "      border-radius: 999px;\n";
  echo "      background: rgba(201, 168, 76, 0.14);\n";
  echo "      color: #e8d8a0;\n";
  echo "      font-size: 12px;\n";
  echo "      font-weight: 700;\n";
  echo "      letter-spacing: 0.08em;\n";
  echo "      text-transform: uppercase;\n";
  echo "    }\n";
  echo "    h1 {\n";
  echo "      margin: 0 0 12px 0;\n";
  echo "      font-size: clamp(28px, 5vw, 42px);\n";
  echo "      line-height: 1.05;\n";
  echo "    }\n";
  echo "    p {\n";
  echo "      margin: 0 0 14px 0;\n";
  echo "      color: #d7d7d7;\n";
  echo "      line-height: 1.55;\n";
  echo "      font-size: 16px;\n";
  echo "    }\n";
  echo "    .meta {\n";
  echo "      margin: 18px 0 22px 0;\n";
  echo "      padding: 14px 16px;\n";
  echo "      border-radius: 12px;\n";
  echo "      background: rgba(255, 255, 255, 0.04);\n";
  echo "      border: 1px solid rgba(255, 255, 255, 0.08);\n";
  echo "      color: #cfcfcf;\n";
  echo "      font-size: 14px;\n";
  echo "    }\n";
  echo "    .actions {\n";
  echo "      display: flex;\n";
  echo "      gap: 12px;\n";
  echo "      flex-wrap: wrap;\n";
  echo "      margin-top: 22px;\n";
  echo "    }\n";
  echo "    .button {\n";
  echo "      appearance: none;\n";
  echo "      display: inline-flex;\n";
  echo "      align-items: center;\n";
  echo "      justify-content: center;\n";
  echo "      min-height: 44px;\n";
  echo "      padding: 0 16px;\n";
  echo "      border-radius: 10px;\n";
  echo "      border: 1px solid rgba(201, 168, 76, 0.3);\n";
  echo "      background: #c9a84c;\n";
  echo "      color: #0d1b2a;\n";
  echo "      font-weight: 700;\n";
  echo "      text-decoration: none;\n";
  echo "    }\n";
  echo "    .button.secondary {\n";
  echo "      background: rgba(29, 58, 94, 0.92);\n";
  echo "      color: #f2ead7;\n";
  echo "    }\n";
  echo "    .button.ghost {\n";
  echo "      background: transparent;\n";
  echo "      color: #c7d8ff;\n";
  echo "      border-color: rgba(150, 183, 235, 0.28);\n";
  echo "      cursor: pointer;\n";
  echo "    }\n";
  echo "    .footnote {\n";
  echo "      margin-top: 18px;\n";
  echo "      font-size: 13px;\n";
  echo "      color: #aab6c4;\n";
  echo "    }\n";
  echo "  </style>\n";
  echo "</head>\n";
  echo "<body>\n";
  echo "  <div class=\"card\">\n";
  echo "    <div class=\"eyebrow\">Session Expired</div>\n";
  echo "    <h1>" . $heading . "</h1>\n";
  echo "    <p>" . $primaryText . "</p>\n";
  echo "    <p>" . $secondaryText . "</p>\n";
  echo "    <div class=\"meta\">Game <strong>" . $safeGameName . "</strong> in <strong>" . $safeRootName . "</strong></div>\n";
  echo "    <div class=\"actions\">\n";
  echo "      <a class=\"button\" href=\"" . htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8') . "\">Back to Main Menu</a>\n";
  if ($showSpectatorButton) {
    echo "      <a class=\"button secondary\" href=\"" . htmlspecialchars($spectateHref, ENT_QUOTES, 'UTF-8') . "\">" . $spectatorButtonLabel . "</a>\n";
  }
  echo "      <button class=\"button ghost\" type=\"button\" onclick=\"try { localStorage.removeItem('tcgengine:lastSimGame:" . $safeRootName . "'); } catch (e) {} window.location.href='" . htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8') . "';\">Clear Saved Rejoin</button>\n";
  echo "    </div>\n";
  echo "    <div class=\"footnote\">" . $footnote . "</div>\n";
  echo "  </div>\n";
  echo "</body>\n";
  echo "</html>\n";
  exit;
}

?>
