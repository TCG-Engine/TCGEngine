<?php
// SHD_209
// Cost 1 - Criminal Muscle - [Cunning] - Power 2 - HP 1
// Text: When Played: You may return a non-unique upgrade to its owner's hand.

// ─── SHD_209 Criminal Muscle ──────────────────────────────────────────────────
// When Played: You may return a non-unique upgrade to its owner's hand.
$whenPlayedAbilities["SHD_209:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $entries = []; $cids = [];
    foreach (GetLiveSeatsArray() as $p) {
        foreach (GetUnitsInPlay($p) as $u) {
            if (!empty($u->removed)) continue;
            $huid = intval($u->UniqueID ?? 0);
            foreach (GetUpgradesOnUnit($u) as $up) {
                $ucid = is_array($up) ? ($up['CardID'] ?? '') : ($up->CardID ?? '');
                if ($ucid === '' || strpos(CardType($ucid) ?? '', 'Upgrade') === false) continue;  // real upgrades
                if (CardUnique($ucid)) continue;                                                    // non-unique only
                $entries[] = $huid . ':' . $ucid;
                $cids[]    = $ucid;
            }
        }
    }
    if (empty($entries)) return;
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    foreach ($cids as $cid) AddTempZone(intval($player), $cid);
    $tempMZs = [];
    for ($k = 0; $k < count($cids); $k++) $tempMZs[] = "myTempZone-" . $k;
    SWUQueueMayChooseTarget(intval($player), $tempMZs,
        "Return_a_non-unique_upgrade_to_its_owner's_hand?", "Choose_an_upgrade", "SHD_209#0|" . implode(",", $entries));
};

$customDQHandlers["SHD_209#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $entries = ($parts[0] ?? '') !== '' ? explode(",", $parts[0]) : [];
    $temp = &GetTempZone(intval($player));
    while (count($temp) > 0) array_pop($temp);
    if (!$lastDecision || !preg_match('/myTempZone-(\d+)/', (string)$lastDecision, $m)) return;
    $idx = intval($m[1]);
    if (!isset($entries[$idx])) return;
    [$huid, $ucid] = explode(':', $entries[$idx], 2);
    $hostMz = SWUFindMzByUID(intval($huid));
    if ($hostMz === null) return;
    SWUReturnUpgradeToHand($hostMz, $ucid, intval($player));
};
