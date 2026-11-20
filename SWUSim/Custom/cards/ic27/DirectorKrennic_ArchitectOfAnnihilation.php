<?php
// IC27_057
// Cost 2 - Director Krennic - Architect of Annihilation - [Command,Villainy] - Unit (Ground) 0/4
//   Traits: Imperial, Official - unique
// Text: Action [Exhaust]: Play a unit that costs 7 or more from your hand. It costs [2 resources] less.
//
// SOR_093 Alliance Dispatcher's action with a cost floor and a bigger discount — the shared discount-play family
// (SWUOfferDiscountPlay in CardHelpers.php).
//   • "costs 7 or more" reads the PRINTED cost (CardCost), so an off-aspect cost-6 unit is not offered even though it
//     would cost 8, and an off-aspect cost-7 unit is offered and still pays its aspect penalty.
//   • 'may' => true — play-from-hand is ALWAYS declinable (the hand is hidden); declining still spends the exhaust.
//   • 'ownsClose' — the Action is the action; the nested unit play's own close is refused.
//   • NO availability gate in SWUUnitActionAffordable (CR 6.4.587.c — the whole discount-play family since 2026-11-03):
//     the [Exhaust] cost changes game state, so with nothing playable the Action is still usable and resolves to nothing.
// Tests: SWUSim/Tests/Cases/ic27/DirectorKrennic_ArchitectOfAnnihilation.md

$unitAbilities["IC27_057"] = fn($player, $mzID) => SWUOfferDiscountPlay($player,
    ['discount' => 2, 'types' => ['Unit'], 'may' => true, 'ownsClose' => true,
     'filter' => fn($cid) => intval(CardCost($cid)) >= 7,
     'prompt' => "Play_a_unit_that_costs_7_or_more_from_your_hand_(it_costs_2_less)"]);
