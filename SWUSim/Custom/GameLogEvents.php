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
    $GLOBALS['gSWULogCombatNotes'] = [];   // an attack that aborted before its ATTACK line must not leak notes
    if (GetSWUVar('SWU_LOG_LASTPLAY', '') !== '') SetSWUVar('SWU_LOG_LASTPLAY', '');
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

// DispatchTrigger's source (2026-09-11 follow-up). Only ~11 of its 134 cases go through a dispatcher that
// sets the source (OnWhenPlayed, OnWhenDefeated, …); the ~120 REACTIVE cases (leader reactions, "when an
// enemy unit is defeated", bounties, upgrade-granted triggers, Shielded) called their bespoke function with
// no source, so "P1 drew 1 card" after Jango Fett named nobody — or, worse, kept the PREVIOUS ability's
// source. DispatchTrigger now sets one up front; the dispatchers that set their own simply overwrite it.
// The trigger key carries a form suffix ("ASH_005#1" deployed side, "TWI_018D"), normalized to the printed
// card. Non-card keys (the Plot window, an Advantage shed) return ''.
function SWULogTriggerSource(string $triggerType, string $cardID): string {
    if ($triggerType === 'SWU_PLOT_WINDOW' || $triggerType === 'AdvantageShed') return '';
    if ($triggerType === 'JTL_169G') return 'JTL_169';        // Shadow Caster re-using a GRANTED When Defeated
    $c = preg_replace('/#\d+$/', '', $cardID);
    if ($c === '') return '';
    if (CardTitle($c) !== null && CardTitle($c) !== '') return $c;
    $c = preg_replace('/[A-Z]$/', '', $c);                   // "TWI_018D" / "LOF_017D" → the leader
    return (CardTitle($c) !== null && CardTitle($c) !== '') ? $c : '';
}

// True when $obj IS the resolving source (same card, same controller): "P1's Gideon gave P1's Gideon an
// Experience token" reads as "P1's Gideon gave itself an Experience token". Callers use _SWULogActiveRef.
// (A passive "gained" was tried first and lost the attribution: Jar Jar's random hit on himself read "P1's
// Jar Jar took 2 damage", with no hint that his own ability did it.)
function _SWULogIsSource($obj): bool {
    if ($obj === null) return false;
    [$p, $c] = SWULogSource();
    return $c !== '' && (string)($obj->CardID ?? '') === $c && intval($obj->Controller ?? ($obj->Owner ?? 0)) === $p;
}
function _SWULogActiveRef($obj, string $ref): string {
    return _SWULogIsSource($obj) ? 'itself' : $ref;
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
function GameBeforeCustomHandler(int $player, string $handlerName, string $fullParam = ''): void {
    $card = _SWULogCardFromHandler($handlerName);
    if ($card === '') { SWULogRestoreQueuedSource($player, $fullParam); return; }
    [, $cur] = SWULogSource();
    if ($cur === $card) return;
    // The source may have been cleared or replaced before this continuation ran (the action closed while
    // an opponent's decision was pending — SOR_187 I Had No Choice). Its owner is the player it was last
    // dispatched for, NOT the seat running this CUSTOM.
    $owner = intval(GetSWUVar('SWU_LOG_SRCP_' . $card, '0'));
    SWULogSetSource($owner > 0 ? $owner : $player, $card);
}

// QUEUED-SOURCE STAMP (user decision 2026-09-11, gamelog-updates #6). A UNIVERSAL continuation
// (DEAL_UNIT_DAMAGE, GIVE_ADVANTAGE, …) names no card, so it used to inherit whatever source was current
// WHEN IT RAN — right when it runs straight after the ability that queued it, wrong when other abilities
// resolve in between (two regroup-start triggers: HMW_160 Noxious Refinery's "deal 1" ran after ASH_159's
// trigger had set its own source, or after the phase boundary had cleared it, and read "P2's X took 1
// damage"). Now the source current when a continuation is QUEUED is recorded (core hook
// GameOnDecisionAdded) and restored when it RUNS (GameBeforeCustomHandler).
// Decision rows are a fixed space-delimited format, so the stamp lives beside them: a per-seat FIFO list
// in an SWUVar (it must survive the request boundary), keyed by the exact decision param, reconciled
// against the live queue on every add so a skipped continuation (a declined "you may") can't leave a stale
// stamp for a later identical one.
function _SWULogSrcQueueGet(int $player): array {
    $j = GetSWUVar('SWU_LOG_SRCQ_' . $player, '');
    $l = ($j === '') ? [] : json_decode($j, true);
    return is_array($l) ? $l : [];
}
function _SWULogSrcQueueSet(int $player, array $l): void {
    $v = empty($l) ? '' : json_encode(array_values($l));
    if (GetSWUVar('SWU_LOG_SRCQ_' . $player, '') !== $v) SetSWUVar('SWU_LOG_SRCQ_' . $player, $v);
}
// Keep only stamps whose continuation is still queued (a multiset match on the param, oldest first).
// $unstamped: a param whose decision is ALREADY in the queue but not yet stamped — GameOnDecisionAdded runs
// after AddDecision spliced it in, so without discounting it a skipped continuation's stale stamp survived
// (the new decision "paid" for it) and the new one then resolved with the OLD card's source. Caught by
// SWUSim/DevTools/tests/gamelog_queued_source_test.php.
function _SWULogSrcQueueReconcile(int $player, array $l, string $unstamped = ''): array {
    $live = [];
    foreach (GetDecisionQueue($player) as $d) {
        if (!empty($d->removed) || (string)($d->Type ?? '') !== 'CUSTOM') continue;
        $p = (string)($d->Param ?? '');
        $live[$p] = ($live[$p] ?? 0) + 1;
    }
    if ($unstamped !== '' && isset($live[$unstamped])) $live[$unstamped]--;
    $counts = [];
    foreach ($l as $e) $counts[$e['p']] = ($counts[$e['p']] ?? 0) + 1;
    $out = [];
    foreach ($l as $e) {
        // Drop the OLDEST surplus stamps for a param (they belong to continuations already gone).
        if ($counts[$e['p']] > ($live[$e['p']] ?? 0)) { $counts[$e['p']]--; continue; }
        $out[] = $e;
    }
    return $out;
}
function GameOnDecisionAdded(int $player, string $type, string $param): void {
    if ($type !== 'CUSTOM') return;
    $handler = explode('|', $param, 2)[0];
    if (_SWULogCardFromHandler($handler) !== '') return;        // card-named: GameBeforeCustomHandler sets it
    $src = GetSWUVar('SWU_LOG_SRC', '');
    $l = _SWULogSrcQueueReconcile($player, _SWULogSrcQueueGet($player), $param);
    if ($src !== '') $l[] = ['p' => $param, 's' => $src];
    _SWULogSrcQueueSet($player, $l);
}
function SWULogRestoreQueuedSource(int $player, string $fullParam): void {
    if ($fullParam === '') return;
    $l = _SWULogSrcQueueGet($player);
    foreach ($l as $i => $e) {
        if ($e['p'] !== $fullParam) continue;
        array_splice($l, $i, 1);
        _SWULogSrcQueueSet($player, $l);
        if ((string)$e['s'] !== '') SetSWUVar('SWU_LOG_SRC', (string)$e['s']);
        return;
    }
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

// "P2's [[SOR_095|Battlefield Marine]]" for a unit, "P2's base" for a base. The seat comes from SWUObjSeat
// (SSOT #6): a base or an undeployed leader carries no Owner / Controller, so it is the seat whose zone holds
// the object — callers no longer derive it from an mzID. $seat: an explicit override, for a detached copy.
function SWULogObjRef($obj, int $seat = 0): string {
    if ($obj === null) return 'a unit';
    $cid  = (string)($obj->CardID ?? '');
    $who  = $seat > 0 ? $seat : SWUObjSeat($obj, CardType($cid) === 'Base');
    if (CardType($cid) === 'Base') return ($who > 0 ? 'P' . $who . "'s " : '') . 'base';
    return ($who > 0 ? 'P' . $who . "'s " : '') . GameLogCardRef($cid);
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
    $o   = _SWULogResolve($relMzID, $perspective);
    $ref = SWULogObjRef($o);
    SWULogEffect('DAMAGE', 'dealt ' . $amount . ' damage to ' . _SWULogActiveRef($o, $ref), "{$ref} took {$amount} damage");
}

// Called from SWUQueueHealAnim with the damage ACTUALLY removed.
function SWULogHealEvent(string $relMzID, int $healed, int $perspective): void {
    if ($healed <= 0) return;
    $o   = _SWULogResolve($relMzID, $perspective);
    $ref = SWULogObjRef($o);
    SWULogEffect('HEAL', 'healed ' . $healed . ' damage from ' . _SWULogActiveRef($o, $ref), "{$ref} healed {$healed} damage");
}

// A unit left play / changed state because of an effect. $obj is captured BEFORE the change.
function SWULogUnitEvent(string $type, $obj, string $active, string $passive): void {
    if ($obj === null) return;
    $ref = SWULogObjRef($obj);
    SWULogEffect($type, str_replace('{U}', _SWULogActiveRef($obj, $ref), $active), str_replace('{U}', $ref, $passive));
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
    if (isset($GLOBALS['gSWULogDrawCollect']) && is_array($GLOBALS['gSWULogDrawCollect'])) {   // SWULogDrawBatch
        foreach ($drawnMz as $mz) {
            $o = _SWULogResolve((string)$mz, $player);
            if ($o !== null && ($o->CardID ?? '') !== '') $GLOBALS['gSWULogDrawCollect'][] = (string)$o->CardID;
        }
        return;
    }
    AddGameLogEntry('DRAW', 'P' . $player . ' drew ' . $n . ' card' . ($n === 1 ? '' : 's') . SWULogSourceSuffix(), 'ALL');
    $refs = [];
    foreach ($drawnMz as $mz) {
        $o = _SWULogResolve((string)$mz, $player);
        if ($o !== null && ($o->CardID ?? '') !== '') $refs[] = GameLogCardRef((string)$o->CardID);
    }
    if (!empty($refs)) SWULogPrivate($player, 'DRAW', 'You drew ' . implode(', ', $refs));
}

// Several single-card draws that read as ONE event (an opening hand, a mulligan's redraw — each draws one
// card at a time): one public "P1 <what> of 6 cards" + one private "You drew …", instead of six
// "P1 drew 1 card" lines.
function SWULogDrawBatch(int $player, string $what, callable $fn): void {
    $GLOBALS['gSWULogDrawCollect'] = [];
    try { $fn(); } finally {
        $ids = (array)($GLOBALS['gSWULogDrawCollect'] ?? []);
        unset($GLOBALS['gSWULogDrawCollect']);
    }
    $n = count($ids);
    if ($n <= 0) return;
    AddGameLogEntry('DRAW', "P{$player} {$what} of {$n} card" . ($n === 1 ? '' : 's'), 'ALL');
    SWULogPrivate($player, 'DRAW', 'You drew ' . implode(', ', array_map('GameLogCardRef', $ids)));
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

// ─── Prevention and refusals ───────────────────────────────────────────────────────────────────────────
// Damage a Shield token PREVENTED left no line at all — a shielded unit hit by an ability read as if
// nothing happened, and in combat the ATTACK line just said "dealt 0". Outside combat it gets its own line
// (attributed to the ability whose damage it stopped); inside combat it is a note on the ATTACK summary,
// which is written after the damage (SWULogCombatNotes).
function SWULogShieldPrevented($obj): void {
    if ($obj === null) return;
    $text = SWULogObjRef($obj) . "'s Shield token prevented the damage";
    if (_SWULogInCombatDamage()) { $GLOBALS['gSWULogCombatNotes'][] = $text; return; }
    AddGameLogEntry('SHIELD', $text . SWULogSourceSuffix(), 'ALL');
}

// Damage PREVENTED by another card's ability (SEC_101 Queen Amidala, ASH_062 The Mandalorian) — only the
// "prevented" animation played before. Same combat/non-combat split as the Shield line.
function SWULogDamagePrevented($obj, string $byCardID): void {
    if ($obj === null) return;
    $ref = SWULogObjRef($obj);
    if (_SWULogInCombatDamage()) { $GLOBALS['gSWULogCombatNotes'][] = "damage to {$ref} was prevented (" . GameLogCardRef($byCardID) . ')'; return; }
    AddGameLogEntry('SHIELD', "Damage to {$ref} was prevented (" . GameLogCardRef($byCardID) . ')', 'ALL');
}

// Damage to a BASE prevented (JTL_074 Close the Shield Gate, HMW_081 Alliance Shield Generator) or capped
// (ASH_070 At Attin Safety Droid: "prevent all but 4"). These only flashed a message on ONE client, so the
// opponent saw their hit do nothing (or less) with no reason, and the Generator's draw looked unexplained.
function SWULogBaseDamagePrevented(int $owner, int $prevented, string $byCardID): void {
    if ($prevented <= 0 || $owner <= 0) return;
    $text = "{$prevented} damage to P{$owner}'s base was prevented (" . GameLogCardRef($byCardID) . ')';
    if (_SWULogInCombatDamage()) { $GLOBALS['gSWULogCombatNotes'][] = $text; return; }
    AddGameLogEntry('SHIELD', $text, 'ALL');
}

// A Shield token DEFEATED outright by an effect (SHD_045 Rose Tico, HMW_077 Boss Nass) — not a prevention.
function SWULogShieldDefeated($obj): void {
    if ($obj === null) return;
    $ref = SWULogObjRef($obj);
    SWULogEffect('DEFEAT', 'defeated a Shield token on ' . _SWULogActiveRef($obj, $ref), "A Shield token on {$ref} was defeated");
}

// An effect that DIDN'T happen because the target is immune ("can't be defeated / exhausted / damaged /
// captured / returned to hand by enemy card abilities", "can't ready"). $verb is the effect's bare verb
// ("defeat", "exhaust", "deal damage to", "return", "capture", "take control of").
// "P1's [[Force Illusion]] couldn't exhaust P2's [[Rey]]" — or passively "P2's [[Rey]] couldn't be affected".
function SWULogRefusal($obj, string $verb): void {
    if ($obj === null) return;
    $ref = SWULogObjRef($obj);
    SWULogEffect('EFFECT', "couldn't {$verb} {$ref}", "{$ref} was unaffected");
}

// The same for an UPGRADE that can't be defeated / returned (SEC_061 Willrow Hood's lone upgrade, JTL_012
// Luke as a Pilot): "P1's [[Confiscate]] couldn't defeat [[Jedi Lightsaber]] on P2's [[Willrow Hood]]".
function SWULogUpgradeRefusal($host, string $upgradeCardID, string $verb): void {
    if ($host === null || $upgradeCardID === '') return;
    $what = GameLogCardRef($upgradeCardID) . ' on ' . SWULogObjRef($host);
    SWULogEffect('EFFECT', "couldn't {$verb} {$what}", "{$what} was unaffected");
}

// " — P2's X's Shield token prevented the damage" notes collected during a combat, appended to its ATTACK
// line. One-shot: reading clears them (and SWULogClearSource clears any an aborted attack left behind).
function SWULogCombatNotes(): string {
    $n = (array)($GLOBALS['gSWULogCombatNotes'] ?? []);
    $GLOBALS['gSWULogCombatNotes'] = [];
    return empty($n) ? '' : ' — ' . implode(' — ', $n);
}

// ─── Credit tokens and returned upgrades ───────────────────────────────────────────────────────────────
// A Credit token DEFEATED by an effect (LAW_191 Arvel Skeen, LAW_040 Taramyn Barcona, LAW_018 Lando, a
// Credit-cost Action). Credits SPENT to pay a cost are one summary line instead (SWULogCreditsSpent) — the
// automatic shortfall payer sets gSWULogCreditPayment so its defeats don't each log. (The interactive
// CREDIT_PAY picker already wrote that same summary line; the automatic path wrote nothing.)
function SWULogCreditDefeated(int $controller): void {
    if (!empty($GLOBALS['gSWULogCreditPayment']) || $controller <= 0) return;
    SWULogEffect('DEFEAT', "defeated P{$controller}'s Credit token", "P{$controller}'s Credit token was defeated");
}

function SWULogCreditsSpent(int $player, int $n): void {
    if ($n <= 0) return;
    // Same wording as CREDIT_PAY's line.
    AddGameLogEntry('TOKEN', "P{$player} defeated {$n} Credit token" . ($n === 1 ? '' : 's') . " to pay {$n} less", 'ALL');
}

// An UPGRADE returned to its owner's hand (SWUReturnUpgradeToHand — Criminal Muscle, Jabba ASH_042, There
// Is No Conflict, …). $how: 'hand' (the card goes to hand), 'token' (a token upgrade ceases to exist),
// 'leader' (a Pilot leader goes back to its leader zone, defeated).
function SWULogUpgradeReturned($host, string $cardID, string $how): void {
    if ($host === null || $cardID === '') return;
    $hostRef = SWULogObjRef($host);
    if ($how === 'token') {
        $tok = _SWULogTokenPhrase($cardID);
        SWULogEffect('BOUNCE', "returned {$tok} on {$hostRef} (it left play)", ucfirst($tok) . " on {$hostRef} left play");
        return;
    }
    $ref  = GameLogCardRef($cardID);
    $dest = ($how === 'leader') ? " (a Pilot leader returns to its leader zone)" : " to its owner's hand";
    SWULogEffect('BOUNCE', "returned {$ref} on {$hostRef}{$dest}", "{$ref} on {$hostRef} was returned{$dest}");
}

// ─── Card moves that bypass the funnels (2026-09-11 follow-up) ─────────────────────────────────────────
// ~35 card handlers move cards with a raw AddHand / MZMove / array_unshift / AddResources, so the move never
// reached a logging funnel. These helpers give them the SAME wording as the funnels, and the same
// hidden-info rule: a card from a PUBLIC zone (a discard pile, play) is named; a card from a HIDDEN zone
// (hand, deck, resources) is a count — plus a private line to the one player who saw it.

// A card taken into hand from a deck by a search ("search … and draw it"): same wording as
// TOPDECKSEARCH_FINALIZE — named publicly only when the effect REVEALS it.
function SWULogSearchedToHand(int $player, array $cardIDs, bool $revealed): void {
    $cardIDs = array_values(array_filter($cardIDs, fn($c) => (string)$c !== ''));
    $n = count($cardIDs);
    if ($player <= 0 || $n <= 0) return;
    $refs = implode(', ', array_map('GameLogCardRef', $cardIDs));
    if ($revealed) {
        AddGameLogEntry('REVEAL', "P{$player} revealed and drew {$refs}" . SWULogSourceSuffix(), 'ALL');
        return;
    }
    AddGameLogEntry('DRAW', "P{$player} drew " . ($n === 1 ? 'a card' : "{$n} cards") . SWULogSourceSuffix(), 'ALL');
    SWULogPrivate($player, 'DRAW', "You drew {$refs}");
}

// A card returned from a DISCARD PILE to its owner's hand (public) — SWUReturnFromDiscardToHand's wording.
function SWULogDiscardToHand(int $owner, string $cardID): void {
    if ($owner <= 0 || $cardID === '') return;
    $ref = GameLogCardRef($cardID);
    SWULogEffect('RETURN', "returned {$ref} from P{$owner}'s discard pile to their hand", "P{$owner} returned {$ref} from their discard pile to their hand");
}

// A RESOURCE returned to hand — face down, so never named (SWUReturnResourceToHand's wording).
function SWULogResourceToHand(int $owner, int $n = 1): void {
    if ($owner <= 0 || $n <= 0) return;
    AddGameLogEntry('RESOURCE', "P{$owner} returned " . ($n === 1 ? 'a resource' : "{$n} resources") . ' to their hand' . SWULogSourceSuffix(), 'ALL');
}

// Cards put into a DECK. $from: 'hand' (hidden → a count), 'discard' / 'play' (face up → named).
// $where: 'top' / 'bottom' / 'shuffle' ("shuffled … into their deck").
function SWULogToDeck(int $owner, array $cardIDs, string $from, string $where): void {
    $cardIDs = array_values(array_filter($cardIDs, fn($c) => (string)$c !== ''));
    $n = count($cardIDs);
    if ($owner <= 0 || $n <= 0) return;
    // A HIDDEN card (from hand) is a numeral count in SWULogDeckPlacement's style — "P1 put 1 card on the top
    // of their deck (Yoda)" — so the top and bottom halves of one choice read alike (gamelog-updates #5).
    // A face-up card (discard pile / play) is named, with where it came from.
    // Hidden: from a hand, or straight from the deck (a search's rest). Named: a discard pile, play, or
    // cards the ability REVEALED (LOF_103 Following the Path).
    $hidden = in_array($from, ['hand', 'deck'], true);
    $what = $hidden ? "{$n} card" . ($n === 1 ? '' : 's') : implode(', ', array_map('GameLogCardRef', $cardIDs));
    $zone = ['discard' => ' from their discard pile'][$from] ?? '';
    $text = ($where === 'shuffle')
        ? "P{$owner} shuffled {$what}{$zone} into their deck"
        : "P{$owner} put {$what}{$zone} on the " . ($where === 'top' ? 'top' : 'bottom') . ' of their deck';
    AddGameLogEntry('DECK', $text . SWULogSourceSuffix(), 'ALL');
}

// ── WHO SEES A LOG LINE — SSOT #8 (gamelog-updates, 2026-09-11) ──────────────────────────────────────────
// A RESTRICTED line (one only some seats may see — "You drew X", "You saw X, Y") is written through these two
// and nothing else. AddGameLogEntry's raw visibility string is how two lines ended up shown to NOBODY (HMW_160
// passed 0, HMW_108 passed 1), and a hand-built 'P' . $seat with a seat of 0 does the same silently. These
// build the tag themselves and refuse to write a line no one could see. A PUBLIC line needs neither:
// AddGameLogEntry($type, $text) defaults to 'ALL'. Enforced by DevTools/tests/gamelog_visibility_arg_test.php —
// a computed visibility anywhere outside these helpers fails it.
function SWULogPrivate(int $seat, string $type, string $text): void {
    SWULogSeats([$seat], $type, $text);
}

function SWULogSeats(array $seats, string $type, string $text): void {
    $tags = [];
    foreach ($seats as $s) { $s = intval($s); if ($s > 0) $tags['P' . $s] = true; }
    if (empty($tags)) return;
    AddGameLogEntry($type, $text, implode(',', array_keys($tags)));
}

// A card put into play as a RESOURCE outside DoResourceCard / SWURampResource* (smuggle and Plot slot
// refills, "resource the top card of your deck", stealing an enemy resource). Resources are face down,
// so the card is never named. $what: "the top card of their deck", "a card from their hand", "P2's resource".
function SWULogResourced(int $player, string $what = 'a card'): void {
    if ($player <= 0) return;
    AddGameLogEntry('RESOURCE', "P{$player} resourced {$what}" . SWULogSourceSuffix(), 'ALL');
}

// A leader exhausted as the COST of its "you may exhaust this leader" reaction (Wicket, Hondo, Boba Daimyo,
// Cad Bane, …). User decision 2026-09-11: logged (an Action's own [Exhaust] cost stays covered by "P1 used
// X's Action"). The leader is usually the resolving source already; name another source when it isn't
// (ASH_203 Mando's N1 Starfighter paying with a friendly leader).
function SWULogLeaderExhaustCost(int $player, string $leaderCardID): void {
    if ($player <= 0 || $leaderCardID === '') return;
    [, $src] = SWULogSource();
    AddGameLogEntry('EXHAUST', "P{$player} exhausted " . GameLogCardRef($leaderCardID)
        . (($src !== '' && $src !== $leaderCardID) ? SWULogSourceSuffix() : ''), 'ALL');
}

// ─── Plays ─────────────────────────────────────────────────────────────────────────────────────────────
// THE play line: "P1 played X" + how, when it was not played from hand — "using Smuggle" / "using Plot"
// (a one-shot a caller sets just before ActivateCard, $GLOBALS['gSWULogPlayVia']) or the zone it came
// from. Before 2026-09-11 only ActivateCard wrote a play line, so a smuggled UNIT and a unit played from
// its owner's discard pile (both placed inline) entered play with no line at all, and Plot wrote TWO.
function SWULogPlay(int $player, string $cardID, string $how = ''): void {
    $via = (string)($GLOBALS['gSWULogPlayVia'] ?? '');
    $GLOBALS['gSWULogPlayVia'] = '';
    if ($via !== '') $how = ' using ' . $via;
    AddGameLogEntry('PLAY', 'P' . $player . ' played ' . GameLogCardRef($cardID) . $how, 'ALL');
    // Remember it (an SWUVar — an upgrade's host pick crosses a request boundary) so the attach step writes
    // "was attached to Y", not a second "played" line. SWULogAttach consumes it; SWULogClearSource clears it.
    SetSWUVar('SWU_LOG_LASTPLAY', $cardID);
}

// Where an upgrade / Pilot went (_SWUFinalizeUpgradeAttach). If this card's play line was already written
// (ActivateCard's commit point, a discard-pilot route, a card's own SWULogPlay), just name the host.
// Otherwise — a Pilot played from HAND never reaches ActivateCard's commit point, and neither do the
// "play an upgrade from X" card effects — this IS the play line. A leader deployed as a Pilot, or an
// upgrade MOVED from another unit, comes from no playable zone: host only.
function SWULogAttach(int $player, string $cardID, string $fromMz, $host, bool $isPilot, ?int $owner = null): void {
    if ($host === null || $cardID === '') return;
    $hostRef = SWULogObjRef($host);
    $played  = GetSWUVar('SWU_LOG_LASTPLAY', '') === $cardID;
    if ($played) SetSWUVar('SWU_LOG_LASTPLAY', '');
    $fromPlayableZone = (bool)preg_match('/Hand|Discard|Deck|Resources|TempZone/', $fromMz);
    if (!$played && $fromPlayableZone) {
        $zone = (strpos($fromMz, 'TempZone') !== false) ? '' : SWULogPlayZone($fromMz, $player, $owner);
        AddGameLogEntry('PLAY', "P{$player} played " . GameLogCardRef($cardID) . $zone . ($isPilot ? ' as a pilot on ' : ' on ') . $hostRef, 'ALL');
        return;
    }
    AddGameLogEntry('PLAY', GameLogCardRef($cardID) . ($isPilot ? ' was attached as a pilot to ' : ' was attached to ') . $hostRef, 'ALL');
}

// " from their discard pile" / " from P2's discard pile" / " from their deck" / " from their resources" for
// a play whose source mzID is not the hand. $owner: whose zone (a foreign-discard play passes it).
function SWULogPlayZone(string $mzID, int $player, ?int $owner = null): string {
    $whose = ($owner !== null && $owner > 0 && $owner !== $player) ? "P{$owner}'s" : 'their';
    if (strpos($mzID, 'Discard') !== false)   return " from {$whose} discard pile";
    if (strpos($mzID, 'Deck') !== false)      return " from {$whose} deck";
    if (strpos($mzID, 'Resources') !== false) return " from {$whose} resources";
    return '';
}

// A card's own effect that resolves INLINE — no trigger dispatch, so nothing set a source (TWI_166 Aurra
// Sing readying herself when an enemy attacks your base; a phase-start base ability). Runs $fn with that
// card as the source, then puts the previous source back so the surrounding ability keeps its attribution.
function SWULogWithSource(int $player, string $cardID, callable $fn) {
    $prev = GetSWUVar('SWU_LOG_SRC', '');
    SWULogSetSource($player, $cardID);
    try { return $fn(); } finally { SetSWUVar('SWU_LOG_SRC', $prev); }
}

// The opposite: run $fn with NO source — a rule, not an ability, is acting (deck-out damage from an empty
// deck is the game's, even when the draw that ran out was a Bounty's or a card's). Restores the source after.
function SWULogWithoutSource(callable $fn) {
    $prev = GetSWUVar('SWU_LOG_SRC', '');
    if ($prev !== '') SetSWUVar('SWU_LOG_SRC', '');
    try { return $fn(); } finally { if ($prev !== '') SetSWUVar('SWU_LOG_SRC', $prev); }
}

// A defeat that is not an ability's effect but a RULE or a COST — the uniqueness rule, Exploit fodder —
// read "P1's X was defeated (uniqueness rule)" instead of being credited to whatever ability last resolved
// ("P1's Vader defeated P1's Vader"). Runs $fn with no source and a one-shot reason that SWUDefeatUnit's
// defeat line consumes (SWULogTakeDefeatNote).
function SWULogWithDefeatNote(string $note, callable $fn) {
    $GLOBALS['gSWULogDefeatNote'] = $note;
    try { return SWULogWithoutSource($fn); } finally { $GLOBALS['gSWULogDefeatNote'] = ''; }
}
function SWULogTakeDefeatNote(): string {
    $n = (string)($GLOBALS['gSWULogDefeatNote'] ?? '');
    return $n === '' ? '' : " ({$n})";
}

// ─── "Had no effect" (user decision 2026-09-11, gamelog-updates #2) ───────────────────────────────────
// ~610 "no legal target → return" exits across 521 card files made an ability resolve SILENTLY: the play
// or trigger line, then nothing. Rather than touch every card, the dispatchers compare the WHOLE serialized
// gamestate (the serializer undo uses — zones, decision queues, SWUVars, the log itself) before and after
// the ability. Only a TRUE no-op logs "P1's X had no effect"; any change at all — a line, a queued "you
// may", a flag — counts as an effect. Keyword triggers (Shielded / Ambush / Support) are excluded: the unit
// is there, only its keyword found nothing, and "had no effect" would misread.
function SWULogNoEffectProbe(): string {
    return class_exists('Versions') ? md5(Versions::GetSerializedZones()) : '';
}
// An ability whose dispatched closure is an intentional EMPTY STUB because its effect is applied elsewhere
// (IBH_010 Han Solo / LOF_014 Grand Inquisitor / SHD_216 Chain Code Collector: the defender's -N/-0 is set
// synchronously in ExecuteSWUAttack). The closure never changes anything, so without this every use would
// log a false "had no effect". Registered at the stub itself:
//     $swuLogEffectAppliedElsewhere['IBH_010'] = true;
// Guarded by SWUSim/DevTools/tests/gamelog_noeffect_stub_test.php (every empty ability closure registered).
function SWULogNoEffectCheck(string $probe, int $player, string $cardID): void {
    global $swuLogEffectAppliedElsewhere;
    if ($probe === '' || $player <= 0 || $cardID === '') return;
    if (!empty($swuLogEffectAppliedElsewhere[$cardID])) return;
    if (SWULogNoEffectProbe() !== $probe) return;
    AddGameLogEntry('EFFECT', "P{$player}'s " . GameLogCardRef($cardID) . ' had no effect', 'ALL');
}
// Run ONE ability closure and log "had no effect" if it changed nothing. Called around the closure itself
// (never around a whole dispatcher): a dispatcher can be reached for a card with no closure of that kind,
// or whose effect was applied elsewhere (JTL_221 Stolen AT-Hauler stamps its free-play when the card is
// DISCARDED), and "had no effect" there would be false.
function SWULogNoEffectRun(int $player, string $cardID, callable $fn): void {
    $probe = SWULogNoEffectProbe();
    $fn();
    SWULogNoEffectCheck($probe, $player, $cardID);
}
// DispatchTrigger's own check covers only its CARD-KEYED reactive cases ('SOR_036', 'ASH_005#1', …); the
// generic trigger kinds ('WhenPlayed', 'OnAttack', …) are checked around the closure in their dispatchers.
function SWULogNoEffectEligible(string $triggerType): bool {
    return (bool)preg_match('/^[A-Z][A-Z0-9]{1,4}_(?:T?\d)/', $triggerType);
}

// A single card REVEALED through DoRevealCard (a hand card for ISB Agent / Queen Soruna / Lieutenant
// Childsen, a deck top for Vermillion). DoRevealCard only set a one-request flash message, so the reveal
// never reached the log. "P1's [[ISB Agent]] revealed [[X]] from P1's hand" / "… from the top of P2's deck".
function SWULogRevealed(int $owner, string $cardID, string $mzID): void {
    if ($owner <= 0 || $cardID === '') return;
    $ref  = GameLogCardRef($cardID);
    $from = (strpos($mzID, 'Deck') !== false) ? "from the top of P{$owner}'s deck"
          : ((strpos($mzID, 'Hand') !== false) ? "from P{$owner}'s hand" : '');
    $pfrom = (strpos($mzID, 'Deck') !== false) ? 'from the top of their deck' : ((strpos($mzID, 'Hand') !== false) ? 'from their hand' : '');
    SWULogEffect('REVEAL', trim("revealed {$ref} {$from}"), trim("P{$owner} revealed {$ref} {$pfrom}"));
}

// ─── Undo (user decision 2026-09-11, gamelog-updates #3: keep undone actions visible) ──────────────────
// The log lives in the gamestate, so an undo restore rewinds it — the undone actions' lines vanished, and
// with them every earlier "undid" line. Now the lines a restore erases are carried back, marked
// "(undone)" and with their ORIGINAL visibility (a private "You drew X" stays private), followed by the undo
// line. Consecutive undos by the same player fold into one "P1 undid their last N actions".
// Usage: $before = SWULogEntries(); …restore…; SWULogCarryUndone($before, $seat, $describe).
function SWULogEntries(): array {
    global $gGameLog;
    $l = (string)($gGameLog ?? '');
    return ($l === '' || $l === '-' || $l === '0') ? [] : explode('<NL>', $l);
}

// $describe(int $n): string — the undo line's text given how many consecutive undos this makes.
function SWULogCarryUndone(array $before, int $seat, callable $describe): void {
    global $gGameLog;
    $after = SWULogEntries();
    $carried = [];
    // Only when the restored log is a PREFIX of the old one (the normal case: the log only grows). A restore
    // that diverged (a different branch) carries nothing rather than guessing.
    if (count($after) <= count($before) && array_slice($before, 0, count($after)) === $after) {
        $carried = array_slice($before, count($after));
    }
    // This player's undo lines at the END of what was erased are the earlier presses of this same undo run:
    // drop them and count them into the new line instead.
    $n = 1;
    while (!empty($carried)) {
        $last = end($carried);
        if (!preg_match('/^UNDO\|ALL\|P' . $seat . ' undid /', $last)) break;
        array_pop($carried);
        $n += preg_match('/their last (\d+) actions/', $last, $m) ? intval($m[1]) : 1;
    }
    foreach ($carried as $e) {
        $p = explode('|', $e, 3);
        if (count($p) < 3) continue;
        // Already-carried lines (an earlier undo's) and other undo lines keep their form.
        AddGameLogEntry(in_array($p[0], ['UNDONE', 'UNDO'], true) ? $p[0] : 'UNDONE',
            in_array($p[0], ['UNDONE', 'UNDO'], true) ? $p[2] : '(undone) ' . $p[2], $p[1]);
    }
    AddGameLogEntry('UNDO', $describe($n), 'ALL');
}

// ─── Game end ──────────────────────────────────────────────────────────────────────────────────────────
// WIN / ELIMINATED lines. Written IMMEDIATELY (so a combat path that exits early can never lose one), but a
// base killed by COMBAT damage is decided before the ATTACK summary is written — "P1 wins" would print above
// the attack that won it. Such a line is remembered and moved to the end by SWULogFlushGameEndLines, which
// both combat resolvers call after their ATTACK (and Overwhelm) lines.
function SWULogGameEndLine(string $type, string $text): void {
    AddGameLogEntry($type, $text, 'ALL');
    if (_SWULogInCombatDamage()) $GLOBALS['gSWULogGameEndToMove'][] = $type . '|ALL|' . $text;
}

function SWULogFlushGameEndLines(): void {
    $move = (array)($GLOBALS['gSWULogGameEndToMove'] ?? []);
    $GLOBALS['gSWULogGameEndToMove'] = [];
    if (empty($move)) return;
    global $gGameLog;
    $parts = explode('<NL>', (string)$gGameLog);
    foreach ($move as $e) {
        $i = array_search($e, $parts, true);
        if ($i === false) continue;
        array_splice($parts, $i, 1);
        $parts[] = $e;
    }
    $gGameLog = implode('<NL>', $parts);
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
    SWULogEffect($ready ? 'READY' : 'EXHAUST', ($ready ? 'readied ' : 'exhausted ') . _SWULogActiveRef($obj, $ref), $ref . ($ready ? ' readied' : ' was exhausted'));
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
    SWULogEffect('EFFECT', 'gave ' . _SWULogActiveRef($obj, $ref) . " {$label}{$dur}", "{$ref} gets {$label}{$dur}");
}

// The Force token. Always logged (it is a visible state change even outside an ability).
function SWULogForce(int $player, bool $gained): void {
    AddGameLogEntry('FORCE', ($gained ? 'The Force is with P' . $player : 'P' . $player . ' used the Force') . SWULogSourceSuffix(), 'ALL');
}

// The Force token defeated WITHOUT Using the Force (a cost such as "[defeat a friendly token]").
function SWULogForceDefeated(int $player): void {
    AddGameLogEntry('FORCE', 'P' . $player . ' defeated their Force token' . SWULogSourceSuffix(), 'ALL');
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
    // USER DECISION 2026-09-11 (gamelog-updates #4): a peek whose card says "REVEAL the top N" (IBH_009 I've
    // Found Them) showed every card to the table — so the line names them all, publicly, with no private
    // twin. A "look at" / "search" that doesn't reveal keeps the public count + private cards.
    [, $lsSrc] = SWULogSource();
    if ($lsSrc !== '' && preg_match('/reveal the top/i', (string)(CardText($lsSrc) ?? ''))) {
        AddGameLogEntry('REVEAL', 'P' . $player . " revealed the top {$n} card" . ($n === 1 ? '' : 's') . ' of their deck: '
            . implode(', ', array_map('GameLogCardRef', $cardIDs)) . SWULogSourceSuffix(), 'ALL');
        return;
    }
    AddGameLogEntry('REVEAL', 'P' . $player . " {$verb} the top {$n} card" . ($n === 1 ? '' : 's') . ' of their deck' . SWULogSourceSuffix(), 'ALL');
    SWULogPrivate($player, 'REVEAL', 'You saw ' . implode(', ', array_map('GameLogCardRef', $cardIDs)));
}

// Cards put on the bottom / kept on top of a deck — face down, so a public COUNT only.
function SWULogDeckPlacement(int $player, int $bottom, int $top = -1): void {
    if ($bottom <= 0 && $top <= 0) return;
    $parts = [];
    if ($bottom > 0) $parts[] = "put {$bottom} card" . ($bottom === 1 ? '' : 's') . ' on the bottom';
    if ($top > 0)    $parts[] = ($bottom > 0 ? 'kept ' : 'kept ') . $top . ' on top';
    // Source suffix (user decision 2026-09-11, gamelog-updates #5): one style for every deck placement —
    // "P1 put 1 card on the bottom of their deck (Yoda)", matching SWULogToDeck's hand-origin line.
    AddGameLogEntry('REVEAL', 'P' . $player . ' ' . implode(' and ', $parts) . ' of their deck' . SWULogSourceSuffix(), 'ALL');
}

// ─── Reading the log back out ──────────────────────────────────────────────────────────────────
//
// THE game-log visibility rule: a line is visible when its visibility field is 'ALL', or when the
// viewer is a seated player named in the field's comma list.
//
// ⚠ A SECOND COPY OF THIS RULE LIVES IN GENERATED SWUSim/GetNextTurn.php and cannot be removed —
// that file is produced by zzGameCodeGenerator.php and must never be hand-edited. The two are pinned
// against each other by SWUSim/DevTools/tests/gamelog_visibility_parity_test.php. If you change one,
// change zzGameCodeGenerator.php too and re-run that test; a silent divergence means the Sideboard
// log starts showing a player their opponent's private draws.
//
// ⚠ A MISSING visibility field defaults to ALL, matching the generated reader exactly. Do not
// "harden" that to deny-by-default here alone — it would diverge, and the parity test would fail.
function SWUFilterGameLogForViewer($rawLog, $viewerSeat, $isSpectator) {
    $out = [];
    $vSeatTag = 'P' . intval($viewerSeat);
    foreach (explode('<NL>', strval($rawLog)) as $entry) {
        if ($entry === '') continue;
        $logVis = explode('|', $entry, 3)[1] ?? 'ALL';
        if ($logVis === 'ALL' || (!$isSpectator && in_array($vSeatTag, array_map('trim', explode(',', $logVis)), true))) {
            $out[] = $entry;
        }
    }
    return $out;
}
