# OnAttack_CreatesReadyBeastTokenInGroundArena
#// HMW_047 Eravana — Hauling Rathtars. Space Unit, cost 5, 1/7, [Command][Cunning],
#// Underworld/Vehicle/Transport, unique.
#// Text: "On Attack: Create a Beast token and ready it."
#//
#// COVERAGE: offer=N/A (structural — the card selects nothing anywhere; the token is created, not chosen)
#//           decline=N/A (structural — no "may", no optional clause on either half)
#//           boundary=N/A (structural — no threshold, no count, no cost)
#//           control=ControlChange_NewControllerGetsTheToken
#//           reqboundary=ReadyStateSurvivesRequestBoundary
#//           modes=2P only (no player reference, no friendly/enemy wording — "create a Beast token
#//                 and ready it" is self-scoped in every format)
#//
#// ⚠ Eravana is a SPACE unit but HMW_T03 Beast is a GROUND token (3/3, Creature). The token does
#// NOT land beside its creator — every section asserts the ground arena, and that cross-arena hop
#// is the first thing a naive "put it next to the source" implementation would get wrong.
#//
#// ⚠ The "and ready it" half is the load-bearing one: a created token normally enters EXHAUSTED
#// (_SWUCreateOneToken defaults Status 0), so a handler that forgot the second half still creates a
#// Beast and every count assertion still passes. Sections 4 and 5 prove it behaviourally.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: HMW_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:1
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:READY
NOEXTRAACTION

---

# AnotherFriendlyUnitAttacks_NoBeastToken
#// NEGATIVE — proves the trigger is Eravana's OWN On Attack window and not a field observer on
#// "a friendly unit attacks". Eravana is on the board and ready the whole time; a different unit
#// swings, and no Beast appears.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: HMW_047:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1SPACEARENACOUNT:1

---

# EravanaIsAttacked_NoBeastToken
#// NEGATIVE — On Attack is not On Defense. Eravana is the DEFENDER here, so the window never
#// opens even though Eravana is in a combat. (CR 15.c puts On Attack and On Defense in the same
#// timing window, which is exactly why a handler wired to the wrong one still looks plausible.)
#//
#// P2 must genuinely act, so no P1OnlyActions — that directive would make P2 auto-pass.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;theirResources:6}
WithActivePlayer: 2
WithP1SpaceArena: HMW_047:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_047
P1SPACEARENAUNIT:0:DAMAGE:2

---

# BeastToken_IsReady_CanAttackTheSamePhase
#// The "and ready it" half, proven behaviourally rather than by the status flag alone. A token
#// created exhausted cannot attack, so the second attack landing 3 more damage is only possible
#// if the ready actually happened. This is the section that reds if the `true` is dropped from
#// SWUCreateUnitToken.
#//
#// (A created token is not a PLAYED unit, so the "played this turn can't attack" rule does not
#// apply to it — that is what makes "ready it" worth printing on the card.)

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: HMW_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ReadyStateSurvivesRequestBoundary
#// REQUEST-BOUNDARY CELL. The token is created mid-combat and its Status is written during the
#// attacker's action; the Beast's own attack happens in a LATER request. If anything about the
#// ready rode a transient in-memory global rather than the serialized zone field, the Beast would
#// come back exhausted in the next process and the second attack would not land.
#//
#// Same GIVEN and same answers as the section above — only the boundary line is inserted.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: HMW_047:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Jerjerrod_DoublesTheTokens_AndBOTHAreReady
#// ASH_094 Moff Jerjerrod: "If you would create a number of tokens, you may defeat this unit. If
#// you do, create twice that number of tokens instead." He makes his extra tokens LATER, inside
#// his own decision handler — so a card that stamps its rider on the UID returned by the original
#// call misses them entirely (the TS26_14 Yoda / TS26_55 Jedi General bug shape).
#//
#// HMW_047 is the first card in the game whose rider is READINESS rather than a token upgrade, so
#// this is the section that proves the `$ready` flag threads through _SWUMaybeOfferJerjerrodDouble
#// into the doubled batch. Jerjerrod is defeated as the cost, so the ground arena ends with
#// exactly the two Beasts.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: HMW_047:1:0
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:READY

---

# ControlChange_NewControllerGetsTheToken
#// CONTROL CELL. Eravana is OWNED by seat 1 but CONTROLLED by seat 2, so seat 2 attacks with her
#// and the ability resolves for seat 2. "Create a Beast token" is unqualified — it belongs to
#// whoever resolves the ability, not to the owner — so the Beast must appear in P2's ground arena
#// and P1 must get nothing.
#//
#// This is the reading that breaks if the handler reaches for the source object's Owner instead of
#// the $player the trigger passes it.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;theirResources:6}
WithActivePlayer: 2
WithP2SpaceArenaControlled: HMW_047:1

## WHEN
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:HMW_T03
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:0
