<?php
// ─── Game-log events: WHO did it, and player CHOICES (game-log sweep, 2026-09-11) ─────────────────────────
//
// THE LOG-SOURCE CONTEXT. Effect lines name the ability that caused them ("P1's Vader dealt 2 damage to
// Battlefield Marine"; "P2 named Hold For Questioning (Garindan)"), but the ~30 effect funnels
// (SWUDealDamageToUnit, OnHealUnit, SWUBounceUnit, …) do not know their source card, and threading one
// through every call site would touch all 1600 card files. Instead, the CURRENT source is recorded at the
// handful of places an ability begins to resolve:
//   • the trigger dispatchers — OnWhenPlayed / OnWhenDefeated / OnAttackTrigger / OnDefenseTrigger /
//     OnAttackEndTrigger / OnWhenPlayedAsUpgrade (every When Played, event effect, On Attack, …);
//   • leader, unit and base Actions (SWULeaderAction / SWUUnitAction / SWUBaseAction);
//   • every card-named CUSTOM continuation ("SEC_186#0" names its card) via GameBeforeCustomHandler, which
//     is what re-establishes the source in a LATER request, after a decision.
// It lives in an SWUVar, so it survives the request boundary with the gamestate. It is cleared at the end
// of every action and at phase boundaries, so a regroup draw is never attributed to the last card played.
// Funnels read it with SWULogSourcePhrase() ("P2's [[SEC_186|Garindan]]") / SWULogSourceSuffix().
// A universal continuation (DEAL_UNIT_DAMAGE, GIVE_SHIELD, …) names no card, so it inherits whatever the
// card that queued it set — which is exactly the attribution we want.

function SWULogSetSource(int $player, string $cardID): void {
    if ($player <= 0 || $cardID === '') return;
    SetSWUVar('SWU_LOG_SRC', $player . ',' . $cardID);
    // Remember WHOSE ability this card is, so a later continuation on ANOTHER seat's queue (an opponent's
    // pick inside the caster's event) re-establishes the caster, not the seat that happens to be deciding.
    SetSWUVar('SWU_LOG_SRCP_' . $cardID, (string)$player);
}

function SWULogClearSource(): void {
    if (GetSWUVar('SWU_LOG_SRC', '') !== '') SetSWUVar('SWU_LOG_SRC', '');
}

// [player, cardID] of the ability currently resolving, or [0, ''] when none is.
function SWULogSource(): array {
    $v = GetSWUVar('SWU_LOG_SRC', '');
    if ($v === '' || strpos($v, ',') === false) return [0, ''];
    [$p, $c] = explode(',', $v, 2);
    return [intval($p), (string)$c];
}

// "P2's [[SEC_186|Garindan]]" — the subject of an effect line — or '' when nothing is resolving.
function SWULogSourcePhrase(): string {
    [$p, $c] = SWULogSource();
    return ($p > 0 && $c !== '') ? 'P' . $p . "'s " . GameLogCardRef($c) : '';
}

// " ([[SEC_186|Garindan]])" — appended to a CHOICE line, whose subject is the choosing player.
function SWULogSourceSuffix(): string {
    [$p, $c] = SWULogSource();
    return ($c !== '') ? ' (' . GameLogCardRef($c) . ')' : '';
}

// A CardID at the head of a continuation name: "SEC_186#0" / "SEC_186" → "SEC_186"; universal handlers
// ("DEAL_UNIT_DAMAGE", "SWU_AFTER_ACTION") → ''. Tokens ("HMW_T02") count as cards.
function _SWULogCardFromHandler(string $handler): string {
    return preg_match('/^([A-Z][A-Z0-9]{1,4}_(?:T\d\d|\d{2,3}))(?:#|$)/', $handler, $m) ? $m[1] : '';
}

// Core hook (Core/DecisionQueueController::ExecuteStaticMethods), called before every CUSTOM handler.
// A card-named continuation re-establishes its card as the source. When the current source is ALREADY
// that card, its player is kept: a cross-player continuation (the opponent's pick inside the caster's
// ability) runs on the OTHER seat, and must not re-attribute the caster's card to that seat.
function GameBeforeCustomHandler(int $player, string $handlerName): void {
    $card = _SWULogCardFromHandler($handlerName);
    if ($card === '') return;
    [, $cur] = SWULogSource();
    if ($cur === $card) return;
    // The source may have been cleared or replaced before this continuation ran (the action closed while
    // an opponent's decision was pending — SOR_187 I Had No Choice). Its owner is the player it was last
    // dispatched for, NOT the seat running this CUSTOM.
    $owner = intval(GetSWUVar('SWU_LOG_SRCP_' . $card, '0'));
    SWULogSetSource($owner > 0 ? $owner : $player, $card);
}

// Core hook (Core/EngineActionRunner + the schema harness), called with the player's answer BEFORE the
// answered decision is popped — so the decision itself is still at the head of the queue. Logs the
// CHOICES the game log should carry on their own line (user decision, 2026-09-11):
//   • NAMECARD     — "P1 named X (Garindan)" — every name-a-card card, from this one place;
//   • OPTIONCHOOSE — "P1 chose Ground (Outmaneuver)"; a chosen OPPONENT is an OPTIONCHOOSE over
//                    "P2&P3", so "P1 chose P3 (Garindan)" comes from the same line.
// Declined "you may"s, YESNOs and target picks are deliberately NOT logged: the effect lines already
// show what happened to what.
function GameOnDecisionAnswered(int $player, string $answer): void {
    $head = null;
    foreach (GetDecisionQueue($player) as $d) {
        if (empty($d->removed)) { $head = $d; break; }
    }
    if ($head === null) return;
    $answer = trim($answer);
    if ($answer === '' || $answer === '-' || $answer === 'PASS') return;
    $who = 'P' . $player;
    switch ((string)($head->Type ?? '')) {
        case 'NAMECARD':
            AddGameLogEntry('NAMECARD', $who . ' named ' . $answer . SWULogSourceSuffix(), 'ALL');
            break;
        case 'OPTIONCHOOSE':
            // Labels are single tokens on the wire ("Draw&Discard" separates options; "Your_deck" uses
            // underscores for spaces) — show them as words.
            AddGameLogEntry('CHOICE', $who . ' chose ' . str_replace('_', ' ', $answer) . SWULogSourceSuffix(), 'ALL');
            break;
    }
}

// ─── Phase 2: EFFECT lines ───────────────────────────────────────────────────────────────────────────────
// One line per effect (user decision, 2026-09-11), worded with the source when an ability is resolving —
// "P1's [[SOR_010|Darth Vader]] dealt 2 damage to P2's [[SOR_095|Battlefield Marine]]" — and passively
// when nothing is — "P2's [[SOR_095|Battlefield Marine]] took 2 damage" (a regroup deck-out, a phase-end
// sweep). COMBAT damage is NOT logged here: the ATTACK summary line carries the combat numbers, and a
// per-hit line would print BEFORE it (the summary is written after combat resolves).

// "P2's [[SOR_095|Battlefield Marine]]" for a unit, "P2's base" for a base.
// $baseOwner: a base object carries no Owner, so callers holding its mzID pass SWUMzOwner(...) — which is
// also what makes the label right at 3-4 seats.
function SWULogObjRef($obj, int $baseOwner = 0): string {
    if ($obj === null) return 'a unit';
    $cid  = (string)($obj->CardID ?? '');
    $ctrl = intval($obj->Controller ?? ($obj->Owner ?? 0));
    if (CardType($cid) === 'Base') {
        $owner = $baseOwner > 0 ? $baseOwner : intval($obj->Owner ?? 0);
        return ($owner > 0 ? 'P' . $owner . "'s " : '') . 'base';
    }
    return ($ctrl > 0 ? 'P' . $ctrl . "'s " : '') . GameLogCardRef($cid);
}

// Resolve a frame-relative mzID in $perspective's frame (the frame it was minted in) without disturbing
// the caller's $playerID.
function _SWULogResolve(string $relMzID, int $perspective) {
    global $playerID;
    $saved = $playerID;
    if ($perspective > 0) $playerID = $perspective;
    $obj = GetZoneObject($relMzID);
    $playerID = $saved;
    return $obj;
}

// The seat owning a base mzID (0 for anything that is not a base).
function _SWULogBaseOwner(string $relMzID, int $perspective): int {
    return (strpos($relMzID, 'Base') !== false && function_exists('SWUMzOwner')) ? intval(SWUMzOwner($relMzID, $perspective)) : 0;
}

// Write an effect line: "<source> <active>" while an ability resolves, "<passive>" otherwise.
function SWULogEffect(string $type, string $active, string $passive, string $vis = 'ALL'): void {
    $src = SWULogSourcePhrase();
    AddGameLogEntry($type, $src !== '' ? $src . ' ' . $active : $passive, $vis);
}

// True while combat damage is being applied — the ATTACK summary owns those numbers.
function _SWULogInCombatDamage(): bool {
    return !empty($GLOBALS['gInCombatDamage']) || !empty($GLOBALS['gSWULogCombatStep']);
}

// Called from SWUQueueDamageAnim — "the universal damage-application hook (base + combat + ability all
// route here)". Before its ConvertMzIDToAbsolute early-return, so it works headless (the harness stubs it).
function SWULogDamageEvent(string $relMzID, int $amount, int $perspective): void {
    if ($amount <= 0 || _SWULogInCombatDamage()) return;
    $ref = SWULogObjRef(_SWULogResolve($relMzID, $perspective), _SWULogBaseOwner($relMzID, $perspective));
    SWULogEffect('DAMAGE', "dealt {$amount} damage to {$ref}", "{$ref} took {$amount} damage");
}

// Called from SWUQueueHealAnim with the damage ACTUALLY removed.
function SWULogHealEvent(string $relMzID, int $healed, int $perspective): void {
    if ($healed <= 0) return;
    $ref = SWULogObjRef(_SWULogResolve($relMzID, $perspective), _SWULogBaseOwner($relMzID, $perspective));
    SWULogEffect('HEAL', "healed {$healed} damage from {$ref}", "{$ref} healed {$healed} damage");
}

// A unit left play / changed state because of an effect. $obj is captured BEFORE the change.
function SWULogUnitEvent(string $type, $obj, string $active, string $passive): void {
    if ($obj === null) return;
    $ref = SWULogObjRef($obj);
    SWULogEffect($type, str_replace('{U}', $ref, $active), str_replace('{U}', $ref, $passive));
}

// "a Shield token" / "an Experience token" — token upgrades are KINDS (every set reprints them), so name
// them by title, never by CardID.
function _SWULogTokenPhrase(string $tokenID): string {
    $t = (string)(CardTitle($tokenID) ?? $tokenID);
    // "an" before a vowel, and before a letter SPOKEN with a vowel sound when it is read as a letter
    // ("an X-Wing", "an R2 unit").
    $a = preg_match('/^([AEIOU]|[FHLMNRSX](?=[-\d\s]))/i', $t) ? 'an' : 'a';
    return "{$a} {$t} token";
}

// ─── Phase 3: deck / hand / discard ─────────────────────────────────────────────────────────────────────
// DRAWS: the COUNT is public ("P1 drew 2 cards"), the CARDS are the drawer's alone ("You drew …", visible
// to that seat only — GetNextTurn filters by the entry's visibility). User decision, 2026-09-11.
function SWULogDraw(int $player, array $drawnMz): void {
    $n = count($drawnMz);
    if ($n <= 0) return;
    AddGameLogEntry('DRAW', 'P' . $player . ' drew ' . $n . ' card' . ($n === 1 ? '' : 's') . SWULogSourceSuffix(), 'ALL');
    $refs = [];
    foreach ($drawnMz as $mz) {
        $o = _SWULogResolve((string)$mz, $player);
        if ($o !== null && ($o->CardID ?? '') !== '') $refs[] = GameLogCardRef((string)$o->CardID);
    }
    if (!empty($refs)) AddGameLogEntry('DRAW', 'You drew ' . implode(', ', $refs), 'P' . $player);
}

// DISCARDS (from a hand, or from a deck — a mill). Called from SWUAddToDiscard for 'HAND' and 'DECK' (the
// one funnel all 14 previously hand-logged discard sites used) and from DoDiscardCard (the self-chosen
// path, which moves the card directly). A discarded card is revealed, so the line is public.
// One-shot note: a caller can set $GLOBALS['gSWULogDiscardNote'] ('at random') just before discarding.
function SWULogDiscard(int $owner, string $cardID, string $from): void {
    if ($owner <= 0 || $cardID === '') return;
    $note = (string)($GLOBALS['gSWULogDiscardNote'] ?? '');
    $GLOBALS['gSWULogDiscardNote'] = '';
    $ref  = GameLogCardRef($cardID);
    $zone = ($from === 'DECK') ? 'deck' : 'hand';
    [$sp] = SWULogSource();
    if ($sp > 0 && $sp !== $owner) {
        // Another player's ability took it: "P1's [[Garindan]] discarded X from P2's hand".
        $text = SWULogSourcePhrase() . " discarded {$ref} from P{$owner}'s {$zone}";
    } else {
        $text = "P{$owner} discarded {$ref}" . ($from === 'DECK' ? ' from their deck' : '') . SWULogSourceSuffix();
    }
    if ($note !== '') $text .= " ({$note})";
    AddGameLogEntry('DISCARD', $text, 'ALL');
}

// ─── Phase 3b: state changes, the Force, Actions ────────────────────────────────────────────────────────
// Exhaust / ready. Logged ONLY while an ability resolves: an Action's own exhaust COST is paid before its
// source is set, attacking exhausts the attacker directly (never here), and the regroup ready step clears the
// source — so this catches effects ("exhaust a unit", "ready a unit") and nothing else.
// ⚠ Only ARENA units and leaders: these two functions also flip RESOURCES, which are face-down, and a line
// naming one would reveal it to the opponent.
function SWULogExhaustReady($obj, string $mzID, bool $ready): void {
    if ($obj === null) return;
    [$sp] = SWULogSource();
    if ($sp <= 0) return;
    if (strpos($mzID, 'Arena') === false && strpos($mzID, 'Leader') === false) return;
    $ref = SWULogObjRef($obj);
    SWULogEffect($ready ? 'READY' : 'EXHAUST', ($ready ? 'readied ' : 'exhausted ') . $ref, $ref . ($ready ? ' readied' : ' was exhausted'));
}

// "Gave P1's X +2/+2 for this phase" / "… Sentinel for this phase" / "… loses all abilities for this phase".
// Hooked in AddTurnEffect, which EVERY grant, buff, debuff and blank goes through (card code mostly calls it
// directly, not the buff helpers), and called only when the token is NEWLY added. Only registered,
// player-facing kinds, never $backendOnlyTurnEffects, and only while an ability resolves — so fixture
// seeding, engine markers and state restoration never log.
function SWULogTurnEffect($obj, string $effectID): void {
    global $turnEffectRegistry, $backendOnlyTurnEffects;
    if ($obj === null || $effectID === '' || !function_exists('SWUParseTurnEffect')) return;
    [$sp] = SWULogSource();
    if ($sp <= 0) return;
    $te  = SWUParseTurnEffect($effectID);
    $row = $turnEffectRegistry[$te['base']] ?? null;
    if ($row === null || in_array($te['base'], (array)($backendOnlyTurnEffects ?? []), true)) return;
    $kind = (string)($row['kind'] ?? '');
    if (!in_array($kind, ['STAT_BUFF', 'STAT_DEBUFF', 'GRANT_KEYWORD', 'GRANT_KEYWORD_VALUE', 'LOSE_ABILITIES'], true)) return;
    $params = $te['params'];
    if (empty($params) && isset($row['amount'])) $params = [$row['amount']];
    $label = (string)($row['label'] ?? $te['base']);
    foreach ($params as $i => $v) $label = str_replace('{' . $i . '}', (string)$v, $label);
    $label = preg_replace('/\s*\{\d+\}/', '', $label);          // an unfilled placeholder: drop it
    $dur = ['attack' => ' for this attack', 'phase' => ' for this phase', 'round' => ' for this round'][$te['duration']] ?? '';
    $ref = SWULogObjRef($obj);
    if ($kind === 'LOSE_ABILITIES') {
        AddGameLogEntry('EFFECT', "{$ref} loses all abilities{$dur}" . SWULogSourceSuffix(), 'ALL');
        return;
    }
    SWULogEffect('EFFECT', "gave {$ref} {$label}{$dur}", "{$ref} gets {$label}{$dur}");
}

// The Force token. Always logged (it is a visible state change even outside an ability).
function SWULogForce(int $player, bool $gained): void {
    AddGameLogEntry('FORCE', ($gained ? 'The Force is with P' . $player : 'P' . $player . ' used the Force') . SWULogSourceSuffix(), 'ALL');
}

// Leader / unit / base Actions: "P1 used [[JTL_016|Admiral Ackbar]]'s Action" — and it becomes the source
// of everything that Action does. Called at each invocation point (the direct path and the alternative-
// payment continuations are separate sites, so each logs exactly once).
function SWULogBeginAction(int $player, string $cardID, string $kind = 'Action'): void {
    SWULogSetSource($player, $cardID);
    AddGameLogEntry('ACTION', 'P' . $player . ' used ' . GameLogCardRef($cardID) . "'s {$kind}", 'ALL');
}

// ─── Phase 3c: deck peeks, searches, moves, returns ─────────────────────────────────────────────────────
// A PEEK (scry or search): the COUNT is public, the CARDS the peeker's alone — same rule as draws.
function SWULogPeek(int $player, string $verb, array $cardIDs): void {
    $n = count($cardIDs);
    if ($n <= 0) return;
    AddGameLogEntry('REVEAL', 'P' . $player . " {$verb} the top {$n} card" . ($n === 1 ? '' : 's') . ' of their deck' . SWULogSourceSuffix(), 'ALL');
    AddGameLogEntry('REVEAL', 'You saw ' . implode(', ', array_map('GameLogCardRef', $cardIDs)), 'P' . $player);
}

// Cards put on the bottom / kept on top of a deck — face down, so a public COUNT only.
function SWULogDeckPlacement(int $player, int $bottom, int $top = -1): void {
    if ($bottom <= 0 && $top <= 0) return;
    $parts = [];
    if ($bottom > 0) $parts[] = "put {$bottom} card" . ($bottom === 1 ? '' : 's') . ' on the bottom';
    if ($top > 0)    $parts[] = ($bottom > 0 ? 'kept ' : 'kept ') . $top . ' on top';
    AddGameLogEntry('REVEAL', 'P' . $player . ' ' . implode(' and ', $parts) . ' of their deck', 'ALL');
}
