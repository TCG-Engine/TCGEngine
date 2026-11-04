<?php
// IC27_087
// Cost 4 - Issue a Command - [Command,Command] - Event - Trait: Plan
// Text: Reveal the top card of your deck. If it's a unit, draw it.
//       Give an Experience token to a friendly unit.
//       Resource this event.
//
// Three separate sentences, each unconditional on the others:
//   1. HMW_148 Local Support's reveal-and-draw — the top card is revealed; a unit is DRAWN through
//      SWUDrawTopCardFront (the draw funnel: observers, counters, CR 7.6.8 trigger timing); anything else stays on top.
//      An empty deck reveals nothing and draws nothing, so it deals no empty-deck damage.
//   2. A mandatory Experience pick over FRIENDLY units — the team in Team Suns (SWUFriendlyUnits); none → skipped.
//   3. HMW_151 Overgrowth's "Resource this card": the spent event, already filed in its OWNER's discard by
//      ActivateCard, goes into the caster's resource zone exhausted (CR 7.7), owner preserved.
//      It runs INLINE while sentence 2's pick is still queued — the reverse of printed order — which nothing observes:
//      the pick reads only the board, the resource only the discard.
// Tests: SWUSim/Tests/Cases/ic27/IssueACommand.md

$whenPlayedAbilities["IC27_087:0"] = function($player, $mzID = '') {
    global $playerID;
    $me = intval($player);
    $playerID = $me;

    // ── 1. Reveal the top card; draw it if it's a unit.
    $idx = _SWUTopDeckFrontIdx($me);
    if ($idx !== -1) {
        $topID = GetDeck($me)[$idx]->CardID;
        AddGameLogEntry('REVEAL', 'P' . $me . ' revealed ' . GameLogCardRef($topID) . ' (top of deck)', 'ALL');
        if (strpos((string)(CardType($topID) ?? ''), 'Unit') !== false) SWUDrawTopCardFront($me, true);
    }

    // ── 2. Give an Experience token to a friendly unit.
    $playerID = $me;
    $friendly = SWUFriendlyUnits();
    if (!empty($friendly)) SWUQueueChooseTarget($me, $friendly, "Give_an_Experience_token_to_a_friendly_unit", "GIVE_EXPERIENCE|1");

    // ── 3. Resource this event.
    $owner = $me;
    $evMz  = _SWUFindDiscardMzID($me, 'IC27_087');
    if ($evMz === null) {
        foreach (GetLiveSeatsArray() as $seat) {
            if ($seat === $me) continue;
            $evMz = _SWUFindDiscardMzID($seat, 'IC27_087');
            if ($evMz !== null) { $owner = $seat; break; }
        }
    }
    if ($evMz === null) return;
    $playerID = $me;
    if ($owner !== $me) $evMz = SWUForeignMzID($me, $owner, 'Discard', intval(substr($evMz, strlen('myDiscard-'))));
    $r = MZMove($me, $evMz, "myResources");
    if ($r !== null) {
        $r->Status     = 0;
        $r->Owner      = $owner;
        $r->Controller = $me;
        SWUKeepCreditTokensLast($me);
        SWULogResourced($me, 'this event');   // game log: face down, never named
        if (function_exists('_SWUIc27089OnResourced')) _SWUIc27089OnResourced($me);   // IC27_089 Maz Kanata
    }
};
