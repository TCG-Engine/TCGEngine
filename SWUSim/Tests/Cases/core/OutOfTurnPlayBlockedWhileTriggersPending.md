# OpponentCannotPlayFromHandWhileYourTriggersResolve
#// ⚠ PRIORITY GUARD (live bug report #964): "while P1 is resolving their triggers, P2 can spam clicking
#// their Jod Na Nawood in hand to interrupt".
#// Clicking a hand card routes through `ActionMap`, which refuses the play twice over: the FSM opens with
#// "block all FSM actions while ANY player has pending DQ decisions", and the `myHand` case additionally
#// requires `$playerID == $turnPlayer`. The harness's PlayHand goes through the SAME `ActionMap` entry as a
#// real click (GameTestAdapter::playCardFromHand), so this exercises the production guard, not a mock.
#// FLOW: P1 plays SEC_051 Bo-Katan Kryze (Alone) — "When Played: give each enemy unit -3/-3 for this
#// phase" wipes P2's SOR_128 (3/1) and SOR_095 (3/3), and her own "When an enemy unit is defeated: give an
#// Experience token to a friendly unit" therefore has two defeats to react to. P1 holds TWO friendly units
#// (Bo-Katan + SOR_046), so the Experience target does NOT auto-resolve and a real decision sits pending.
#// P2 then tries to play ASH_219 Jod Na Nawood (Cunning, cost 3) out of turn, mid-resolution.
#// EXPECT: the play is a silent no-op — Jod stays in hand, P2's arena stays empty, and P1's Experience
#// decision is untouched and still pending.

## GIVEN
CommonSetup: bbw/yyk/{myResources:9;theirResources:5}
P1OnlyActions: false
WithP1Hand: SEC_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2Hand: ASH_219

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P1HASDECISION

---

# OpponentCanStillPlayOnceTheTriggersAreDone
#// The control, and the half that proves the guard is a TIMING gate rather than a card being permanently
#// unplayable: same board, but P1 answers the pending Experience decisions first. Once the queues are
#// empty and the turn has passed, P2's Jod Na Nawood plays normally.
#// Without this, a bug that made ASH_219 unplayable outright (wrong aspects, unaffordable, blocked) would
#// satisfy the section above for entirely the wrong reason.

## GIVEN
CommonSetup: bbw/yyk/{myResources:9;theirResources:5}
P1OnlyActions: false
WithP1Hand: SEC_051
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_128:1:0 SOR_095:1:0]
WithP2Hand: ASH_219

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0

## EXPECT
P2HANDCOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:ASH_219
