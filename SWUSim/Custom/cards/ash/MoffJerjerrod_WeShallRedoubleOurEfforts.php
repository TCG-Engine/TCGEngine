<?php
// ASH_094
// Cost 2 - Moff Jerjerrod - We Shall Redouble Our Efforts - [Command,Villainy] - Power 1 - HP 3
// Text: If you would create a number of tokens, you may defeat this unit. If you do, create twice that number of tokens instead.

// ASH_094 Moff Jerjerrod — accept the doubling: defeat Jerjerrod (by UID) and create $count MORE of the
// same token (the first $count were already made, so the total is twice the original number). Decline, or
// Jerjerrod already gone (can't pay the cost) → nothing.
$customDQHandlers["ASH_094#0"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  if ($lastDecision !== 'YES')
    return;
  $tokenID = $parts[0] ?? '';
  $count = intval($parts[1] ?? 0);
  $ready = intval($parts[2] ?? 0) === 1;
  $jUID = intval($parts[3] ?? 0);
  $turnEffect = $parts[4] ?? '';
  $kind = $parts[5] ?? 'unit';
  $upgradeToken = $parts[6] ?? '';   // persistent rider (Experience/Shield) — see SWUCreateUnitTokens
  $jMz = SWUFindMzByUID($jUID);
  if ($jMz === null || $tokenID === '' || $count < 1)
    return;   // Jerjerrod gone → can't pay
  SWUDefeatUnit(intval($player), $jMz);
  _SWUDropPendingJerjerrodOffers(intval($player));   // he is gone: any other queued doubling can't be paid
  if ($kind === 'credit') {
    SWUCreateCreditToken(intval($player), $count, false);   // create $count MORE Credits (net 2×); no re-offer
    return;
  }
  for ($i = 0; $i < $count; $i++) {
    $uid = _SWUCreateOneToken(intval($player), $tokenID, $ready);
    $mz = ($turnEffect !== '' || $upgradeToken !== '') ? SWUFindMzByUID($uid) : null;
    if ($turnEffect !== '' && $mz !== null)
      AddTurnEffect($mz, $turnEffect);
    if ($upgradeToken !== '' && $mz !== null)
      _SWUApplyTokenRider(intval($player), $mz, $upgradeToken);
  }
  _SWUAfterTokensCreated();   // the doubled tokens meet any continuous shrink too (SHD_037 / HMW_065)
};

// ASH_094 Moff Jerjerrod — TOKEN UPGRADES (queued by _SWUJerjerrodNoteUpgradeGiven, GameLogic.php). A given
// token upgrade is a created token (CR), so accepting defeats Jerjerrod and gives each host the same number
// AGAIN — the instruction's N tokens become 2N on the units it put them on.
// $parts = [tokenCardID, jerjerrodUID, "hostUID:n,hostUID:n"]; $lastDecision = the YESNO answer.
$customDQHandlers["ASH_094#1"] = function ($player, $parts, $lastDecision) {
  global $playerID;
  $playerID = intval($player);
  $tokenID = $parts[0] ?? '';
  unset($GLOBALS['SWU_JERJERROD_UPGRADE_BATCH'][intval($player) . '|' . $tokenID]);   // the batch is closed
  if ($lastDecision !== 'YES' || $tokenID === '')
    return;
  $jMz = SWUFindMzByUID(intval($parts[1] ?? 0));
  if ($jMz === null)
    return;   // Jerjerrod gone → can't pay
  $hosts = [];
  foreach (array_filter(explode(',', $parts[2] ?? '')) as $pair) {
    [$uid, $n] = array_map('intval', explode(':', $pair));
    if ($uid > 0 && $n > 0) $hosts[$uid] = $n;
  }
  SWUDefeatUnit(intval($player), $jMz);
  _SWUDropPendingJerjerrodOffers(intval($player));
  $GLOBALS['SWU_JERJERROD_NO_UPGRADE_OFFER'] = true;   // the doubled tokens are this offer's, never a new one
  try {
    foreach ($hosts as $uid => $n) {
      $playerID = intval($player);
      $mz = SWUFindMzByUID($uid);
      if ($mz === null) continue;               // host left play since: nothing to attach to
      for ($i = 0; $i < $n; $i++) _SWUGiveTokenUpgradeOfKind(intval($player), $mz, $tokenID);
    }
  } finally {
    unset($GLOBALS['SWU_JERJERROD_NO_UPGRADE_OFFER']);
  }
};
