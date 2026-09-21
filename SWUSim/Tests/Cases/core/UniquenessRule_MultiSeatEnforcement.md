# ThreeSeat_NonActingFarSeatIsEnforced
#// Uniqueness rule COVERAGE across seats (CR 29.3.3 + 29.3.5). The rule binds EVERY player, not just
#// whoever is acting: a control-give or an exchange can hand a duplicate unique to a seat that is not
#// taking the action, and that seat must still be made to choose.
#//
#// SWUAfterAction enforced for the acting seat and then for `OtherPlayer($player)` — literally
#// `$p === 1 ? 2 : 1`. At two seats that IS every other player, so the rule looked complete. At three
#// or four it covers exactly one opponent and silently skips the rest: with P1 acting, seat 2 was
#// checked and seat 3 was not, so P3 could sit on two copies of a unique unit.
#//
#// ⚠ THE DUPLICATE IS BUILT BY THE FIXTURE ON PURPOSE. Routing it through a real control-give
#// (LAW_085 You Hold This, TS26_15 C-3PO) would prove nothing about this gap any more, because the
#// mid-resolution enforcement added for CR 29.3.3 now fires inside SWUTakeControlOfUnit for whoever
#// GAINS control — seat-correct by construction. What is under test here is the action-close BACKSTOP,
#// which is what catches every other route a duplicate can arrive by. Seeding the state directly is
#// the only way to reach the backstop without the inline path resolving it first.
#//
#// LOF_093 Gungi - Finding Himself: unique, ground, no card text at all, so nothing but the
#// uniqueness rule can fire. P1 attacks P2's base purely to close an action; the attack itself is
#// incidental and touches neither P3 nor either Gungi.
#//
#// The prompt parks on P3's OWN queue (they are the player who must choose), which is why the
#// assertion is a P3 decision rather than anything on the acting seat.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: LOF_093:1:0
WithP3GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0

## EXPECT
# P3 — a seat that did not act and is not OtherPlayer(1) — is asked to resolve its own violation.
P3DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
# Still two copies: the choice is pending, not yet answered.
P3GROUNDARENACOUNT:2

---

# ThreeSeat_NonActingFarSeatResolves_LeavingOneCopy
#// The same board, carried through to the answer: P3 picks a copy and it is defeated to P3's own
#// discard, leaving the singleton the rule requires. Proves the continuation runs on the far seat's
#// queue rather than merely being queued there and stranded — a decision parked on a seat that is not
#// otherwise draining is a known way for this to silently do nothing.

## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: LOF_093:1:0
WithP3GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:p2Base-0
- P3>AnswerDecision:myGroundArena-1

## EXPECT
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:LOF_093
P3DISCARDCOUNT:1
P3DISCARDUNIT:0:CARDID:LOF_093
P3DISCARDUNIT:0:FROM:PLAY

---

# TwoSeat_OpponentStillEnforced
#// ⚠ THE NON-REGRESSION CONTROL. The seat-aware loop must still cover the ordinary two-player case
#// that `OtherPlayer()` was covering — the opponent of the acting seat. Without this, a loop that
#// accidentally skipped every non-acting seat would look correct to both sections above only if they
#// happened to cover seat 3, and Premier would break in silence.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LOF_093:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat
P2GROUNDARENACOUNT:2
