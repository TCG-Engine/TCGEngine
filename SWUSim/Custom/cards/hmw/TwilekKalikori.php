<?php
// HMW_265
// Cost 4 - Twi'lek Kalikori - [Heroism] - Upgrade (+2/+2)
// Traits: Item
// Text: When Played: If attached unit is a Twi'lek, search the top 8 cards of your deck for any number
//       of Twi'lek units with a combined costs 5 or less and play each of them for free.
//
// The card prints NO attach restriction, so under CR 2.e it may be played on ANY unit — friendly or
// enemy — and SWUGetUpgradeValidTargets' default (any unit) is already that. Nothing to register.
//
// Not a Pilot, so HasWhenPlayedAsUpgradeAbility is false and CollectWhenPlayedAsUpgradeTriggers falls
// back to the plain WhenPlayed window, handing this closure the HOST unit's mzID as $mzID. $player is
// whoever PLAYED the Kalikori, which is what "your deck" means — NOT the host's controller. Those two
// differ whenever the upgrade is attached to an enemy unit (guarded by
// EnemyTwilekHost_SearchesTheKalikoriControllersDeck, which asserts both decks).
//
// The search itself is the released "search top N, play for free within a combined-cost budget" family
// verbatim (SOR_087 Vader, SOR_104 U-Wing Reinforcement, LAW_063 L3-37, ASH_110 Ackbar). DoTopDeckPlay
// stores the legal-ID list and the "cost:5" constraint as DQ VARIABLES before queueing the search, so
// both survive the request boundary and are re-enforced server-side in _topDeckResolveFromIDs — an
// illegal or over-budget pick is dropped to the bottom of the deck rather than played.

$whenPlayedAbilities["HMW_265:0"] = function($player, $mzID = '') {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if ($host === null || !empty($host->removed)) return;
    // Object-aware trait read: a trait can be granted or suppressed on the LIVE object, so this must not
    // be a bare-CardID HasTrait (the deployed-leader / granted-trait override family).
    if (!TraitContains($host, "Twi'lek")) return;
    // "any number of Twi'lek UNITS with a combined cost 5 or less" — both conjuncts of the filter are
    // load-bearing and each has its own refusal guard (NonTwilekPickIsRefused, TwilekLeaderPickIsRefused).
    DoTopDeckPlay(intval($player), 8,
        fn($c) => CardType($c) === 'Unit' && HasTrait($c, "Twi'lek"), 5);
};
