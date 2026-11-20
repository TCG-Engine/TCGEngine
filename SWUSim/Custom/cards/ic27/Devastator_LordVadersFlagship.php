<?php
// IC27_068
// Cost 10 - Devastator - Lord Vader's Flagship - [Command,Villainy] - Unit (Space) 9/9 (unique)
//   Traits: Imperial, Vehicle, Capital Ship
// Text: Overwhelm. When Played: Search the top 5 cards of your deck for a Villainy unit that costs 12 or less and
//       play it for free. (Put the other cards on the bottom of your deck in a random order.)
//
// Overwhelm is printed (auto-wired). DoTopDeckPlay — the SOR_087 search-and-play-free finalize — with a Villainy
// UNIT filter (printed cost <= 12) and a "cost:12:1" constraint (one pick). The filter and the constraint are both
// re-checked server-side when the answer comes back (_topDeckResolveFromIDs), so an illegal pick is not played; it
// goes to the bottom with the rest. A search may come up empty, so choosing none is legal.
// Tests: SWUSim/Tests/Cases/ic27/Devastator_LordVadersFlagship.md
$whenPlayedAbilities["IC27_068:0"] = function($player, $mzID) {
    DoTopDeckPlay(intval($player), 5,
        fn($c) => CardType($c) === 'Unit' && strpos(CardAspect($c) ?? '', 'Villainy') !== false && intval(CardCost($c)) <= 12,
        12, 'a Villainy unit that costs 12 or less', 1);
};
