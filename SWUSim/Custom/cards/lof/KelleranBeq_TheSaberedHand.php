<?php
// LOF_100
// Cost 7 - Kelleran Beq - The Sabered Hand - [Command,Heroism] - Power 7 - HP 7
// Text: When Played: Search the top 7 cards of your deck for a unit, reveal it, and play it. It costs 3 resources less. (Put the other cards on the bottom of your deck in a random order.)

// LOF_100 Kelleran Beq — When Played: search the top 7 for a unit, reveal it, and play it costing 3 less.
// Only offer units the player can actually pay for at the discounted price — otherwise the UI lets you
// pick an unaffordable unit and the play just fizzles at resolve.
// ⚠ THE FILTER MUST PRICE THROUGH THE SAME PIPELINE THAT WILL CHARGE THE PLAY. It used to compute
// `CardCost + SWUAspectPenalty - 3` by hand, which silently ignores every play-cost MODIFIER — so with
// a discounter in play the offer was too narrow and legal picks were missing from the menu. Found via
// HMW_145 Origin Tree Shyyyo (user ruling 2026-08-26): with Shyyyo out, the unit Kelleran fetches is
// the SECOND unit played that round and takes his -2 as well, so its ceiling is
// (ready + 2 + 3) — the hand-rolled formula capped it at (ready + 3) and hid a whole tier of picks.
// SWUComputePlayCost already includes the aspect penalty, so this is also one fewer thing to add twice.
// Guarded by hmw/OriginTreeShyyyo.md::Ruling3_KelleranBeqChainsBothDiscounts and its 3b control.
$whenPlayedAbilities["LOF_100:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready = SWUTotalPaymentCapacity(intval($player));
    _topDeckSearchBegin(intval($player), 7,
        fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false
                  && max(0, SWUComputePlayCost(intval($player), new Deck($c, 'Deck', intval($player))) - 3)
                     <= $ready,
        "count:1", "LOF_100#0");
};

// ⚠ THE CARD SAYS "PLAY IT", SO IT IS A REAL PLAY — not a put-into-play.
// This handler used to place the fetched unit with a bare AddGroundArena/AddSpaceArena, which skipped the
// entire play ceremony: the fetched unit's own **When Played never fired**, no entry triggers, no
// Shielded/Ambush, no uniqueness sweep. It was also the odd one out in its own wording family — SHD_194
// Triple Dark Raid and LAW_074 Maz Kanata are printed identically ("search … and play it. It costs N
// less") and both route through the real play path.
// ⚠ BUT IT IS PLAYED AS A UNIT — $asUnitOnly. A Piloting card IS a unit card, so it is a legal FIND, and
// the naive read is that "play it" then lets you choose the pilot-upgrade mode. It does not: the ability
// searched for "a UNIT" and plays THAT, so no Unit-vs-Pilot choice is offered even with a legal Vehicle
// host in play. The whole search-and-play family behaves this way (SOR_104, LAW_063, LAW_074).
// (Contrast ASH_090 Reforge, which searches for "an UPGRADE": there a Piloting card must not be FOUND at
// all, because that filter is a card-TYPE test — see Reforge.md::SearchExcludesPILOTUnitCards.)
$customDQHandlers["LOF_100#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $chosen   = $resolved['drawn'];   // up to 1 unit
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
    if (empty($chosen)) return;       // declined / nothing found
    $cardID = $chosen[0];
    if (SWUCardPlayBlocked(intval($player), $cardID)) {   // SOR_062 Regional Governor
        _topDeckPutRemainingToBottom(intval($player), [$cardID]);
        return;
    }
    // Put the chosen card on top of the deck so SWUPlayTopDeckCard plays IT (the LAW_074 idiom).
    $deck   = &GetDeck(intval($player));
    $topObj = new Deck($cardID, 'Deck', intval($player));
    array_unshift($deck, $topObj);
    foreach ($deck as $i => $c) { $c->mzIndex = $i; }
    // Affordability at the -3 price, computed through the same pipeline that will charge it. If the
    // player cannot pay, it is not played and goes to the bottom rather than being lost.
    $eff = max(0, SWUComputePlayCost(intval($player), $topObj) - 3);
    if (SWUTotalPaymentCapacity(intval($player)) < $eff) {
        $deck[0]->removed = true;
        DecisionQueueController::CleanupRemovedCards();
        _topDeckPutRemainingToBottom(intval($player), [$cardID]);
        return;
    }
    SWUPlayTopDeckCard(intval($player), false, 3, true);   // asUnitOnly
};
