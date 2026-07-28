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
  $jMz = SWUFindMzByUID($jUID);
  if ($jMz === null || $tokenID === '' || $count < 1)
    return;   // Jerjerrod gone → can't pay
  SWUDefeatUnit(intval($player), $jMz);
  if ($kind === 'credit') {
    SWUCreateCreditToken(intval($player), $count, false);   // create $count MORE Credits (net 2×); no re-offer
    return;
  }
  for ($i = 0; $i < $count; $i++) {
    $uid = _SWUCreateOneToken(intval($player), $tokenID, $ready);
    if ($turnEffect !== '') {
      $mz = SWUFindMzByUID($uid);
      if ($mz !== null)
        AddTurnEffect($mz, $turnEffect);
    }
  }
};
