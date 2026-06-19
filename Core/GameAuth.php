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
    'updatedAt' => time(),
  ];

  $encoded = json_encode($payload);
  if ($encoded === false) return false;

  return file_put_contents($path, $encoded, LOCK_EX) !== false;
}

function SimGameBuildAuthKeysFromLobby($lobby)
{
  $authKeys = ['p1' => '', 'p2' => ''];
  if (!is_object($lobby) || !isset($lobby->players) || !is_array($lobby->players)) return $authKeys;

  foreach ($lobby->players as $index => $player) {
    if (!is_object($player) || !method_exists($player, 'getAuthKey')) continue;

    $seat = 0;
    if (method_exists($player, 'getGamePlayerID')) $seat = intval($player->getGamePlayerID());
    if ($seat <= 0 && method_exists($player, 'getPlayerID')) $seat = intval($player->getPlayerID());
    if ($seat <= 0) $seat = intval($index) + 1;
    if ($seat !== 1 && $seat !== 2) continue;

    $authKeys['p' . $seat] = strval($player->getAuthKey());
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
  if ($path === '' || !is_file($path)) return ['p1' => '', 'p2' => ''];

  $decoded = json_decode(file_get_contents($path), true);
  if (!is_array($decoded)) return ['p1' => '', 'p2' => ''];

  return [
    'p1' => strval($decoded['p1'] ?? ''),
    'p2' => strval($decoded['p2'] ?? ''),
  ];
}

function SimGameGetSeatAuthKey($rootName, $gameName, $playerID)
{
  $seat = intval($playerID);
  if ($seat !== 1 && $seat !== 2) return '';

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

function SimGameValidateSeatAuth($rootName, $gameName, $playerID, $authKey = '')
{
  $expectedKey = SimGameGetSeatAuthKey($rootName, $gameName, $playerID);
  if ($expectedKey === '') return true;

  $presentedKey = SimGameResolvePresentedAuthKey($authKey);
  if ($presentedKey === '') return false;

  return hash_equals($expectedKey, $presentedKey);
}

function SimGameRenderInvalidAuthPage($rootName, $gameName, $playerID)
{
  http_response_code(403);

  $safeRootName = htmlspecialchars(strval($rootName), ENT_QUOTES, 'UTF-8');
  $safeGameName = htmlspecialchars(strval($gameName), ENT_QUOTES, 'UTF-8');
  $safePlayerID = htmlspecialchars(strval($playerID), ENT_QUOTES, 'UTF-8');
  $menuHref = './SharedUI/Sites/' . rawurlencode(strval($rootName)) . '/MainMenu.php';
  $spectateHref = './NextTurn.php?playerID=S&viewerPerspective=' . rawurlencode(strval($playerID)) . '&gameName=' . rawurlencode(strval($gameName)) . '&folderPath=' . rawurlencode(strval($rootName));

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
  echo "    <h1>This seat link is no longer valid.</h1>\n";
  echo "    <p>The game is still there, but this browser is not currently authenticated as player " . $safePlayerID . ".</p>\n";
  echo "    <p>This usually happens after opening an old link, switching devices, or starting a newer session for the same seat.</p>\n";
  echo "    <div class=\"meta\">Game <strong>" . $safeGameName . "</strong> in <strong>" . $safeRootName . "</strong></div>\n";
  echo "    <div class=\"actions\">\n";
  echo "      <a class=\"button\" href=\"" . htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8') . "\">Back to Main Menu</a>\n";
  echo "      <a class=\"button secondary\" href=\"" . htmlspecialchars($spectateHref, ENT_QUOTES, 'UTF-8') . "\">Open as Spectator</a>\n";
  echo "      <button class=\"button ghost\" type=\"button\" onclick=\"try { localStorage.removeItem('tcgengine:lastSimGame:" . $safeRootName . "'); } catch (e) {} window.location.href='" . htmlspecialchars($menuHref, ENT_QUOTES, 'UTF-8') . "';\">Clear Saved Rejoin</button>\n";
  echo "    </div>\n";
  echo "    <div class=\"footnote\">If you expected to keep playing, reopen the game from the matching sim menu so it can use your current saved session.</div>\n";
  echo "  </div>\n";
  echo "</body>\n";
  echo "</html>\n";
  exit;
}

?>
