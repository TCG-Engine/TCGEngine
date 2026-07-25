# AlreadyExhaustedTarget_StaysExhausted
#// SOR_186 No Good to Me Dead — you may target an ALREADY-exhausted unit just to stop it readying.
#// SOR_046 starts exhausted; the exhaust is a no-op but the can't-ready flag still applies, so it stays
#// EXHAUSTED through regroup while the control SEC_080 readies.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_186}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# ExhaustReadyUnit_CantReadyRegroup
#// SOR_186 No Good to Me Dead — "Exhaust a unit. That unit can't ready this round (including regroup)."
#// P1 exhausts a READY enemy SOR_046; a separate exhausted enemy SEC_080 readies normally at regroup,
#// but SOR_046 stays EXHAUSTED (its can't-ready flag survives the regroup ready step).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_186}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# BravadoReadyBlocked_Logged
#// SOR_186 No Good to Me Dead — the "can't ready this round" lock blocks a mid-phase "Ready a unit" EFFECT,
#// not just the regroup ready step, and the prevention is written to the game log. P1 plays No Good to Me
#// Dead on the enemy Count Dooku (SOR_038), exhausting him and flagging him can't-ready-this-round. P2 then
#// plays Bravado (SHD_182, "Ready a unit") targeting Dooku — the ready is prevented, Dooku stays EXHAUSTED,
#// and the block is logged (interaction hardening: OnReadyCard's SOR_186 gate now emits a log entry).

## GIVEN
CommonSetup: yrk/brk/{myResources:12}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithActivePlayer: 1
WithP1Hand: SOR_186
WithP2Hand: SHD_182
WithP2Resources: 12
WithP2GroundArena: [SOR_226:1:0 SOR_038:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_038
P2GROUNDARENAUNIT:1:EXHAUSTED
LASTLOGCONTAINS:can't ready this round

---

# MottiWhenDefeatedReadyBlockedInRegroup_Logged
#// SOR_186 No Good to Me Dead — "this round" INCLUDES the regroup phase, so a "Ready a unit" effect that
#// triggers DURING regroup is also blocked (and logged). Full interaction: P1 plays No Good to Me Dead on
#// Dooku (exhaust + can't-ready this round); P2 plays Bravado on Dooku (blocked, above); P1 plays Sneak Attack
#// (SOR_219) to play Ruthless Raider (SOR_134) for 3 less, entering ready — When Played it deals 2 to P2's
#// base (auto) and 2 to Dooku (chosen). P2 takes the initiative (which also passes), P1 passes, and the
#// regroup phase begins: Sneak Attack defeats Ruthless Raider, whose When Defeated deals 2 to P2's base
#// (auto) and 2 to Admiral Motti (SOR_226, a 1/1 — chosen), defeating him. Motti's When Defeated ("you may
#// ready a Villainy unit") is drained; P2 chooses to ready Dooku (the lone Villainy unit) instead of
#// declining — but the ready is still blocked by No Good to Me Dead (regroup is part of the round) and logged.
#// After the pick, Dooku is still EXHAUSTED and Motti is gone.

## GIVEN
CommonSetup: yrk/brk/{myResources:12}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithActivePlayer: 1
WithP1Hand: [SOR_186 SOR_219 SOR_134]
WithP2Hand: SHD_182
WithP2Resources: 12
WithP2GroundArena: [SOR_226:1:0 SOR_038:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>Claim
- P1>Pass
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_038
P2GROUNDARENAUNIT:0:EXHAUSTED
LASTLOGCONTAINS:can't ready this round

---

# DookuRemainsExhaustedNextRound_P2FirstAction
#// SOR_186 No Good to Me Dead — end-to-end: after the two blocked ready attempts (Bravado, then Motti's
#// When Defeated in regroup), the regroup READY step ALSO can't ready Dooku, so he remains EXHAUSTED into
#// the next round. Both players decline the regroup resource, and because P2 took the initiative P2 has the
#// first action of the new round — with Dooku still exhausted. (Continues the interaction above through the
#// resource step.)

## GIVEN
CommonSetup: yrk/brk/{myResources:12}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithActivePlayer: 1
WithP1Hand: [SOR_186 SOR_219 SOR_134]
WithP2Hand: SHD_182
WithP2Resources: 12
WithP2GroundArena: [SOR_226:1:0 SOR_038:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>Claim
- P1>Pass
- P1>AnswerDecision:theirGroundArena-0
- P2>Drain
- P2>AnswerDecision:myGroundArena-0
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_038
P2GROUNDARENAUNIT:0:EXHAUSTED
TURNPLAYER:2
LOGCONTAINS:can't ready this round
