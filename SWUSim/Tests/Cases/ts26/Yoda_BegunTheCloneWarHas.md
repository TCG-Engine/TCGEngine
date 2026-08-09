# CostReducedWith7Resources
#// TS26_14 Yoda — "If you control 7 or more resources, this unit costs 2 resources less to play." With 7
#// resources Yoda costs 3 (5 - 2), leaving 4 ready.
## GIVEN
CommonSetup: bgw/rrk/{myResources:7;handCardIds:TS26_14}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:4

---

# WhenPlayedCloneWithSentinel
#// TS26_14 Yoda (Unit 4/4, cost 5) — When Played/When Defeated: create a Clone Trooper token and give it
#// Sentinel for this phase. Playing Yoda creates a Clone (TS26_T02) with Sentinel.
## GIVEN
CommonSetup: bgw/rrk/{myResources:5;handCardIds:TS26_14}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel

---

# FullCostWhenControllingFewerThan7Resources
#// TS26_14 Yoda — the FALSE side of "if you control 7 or more resources, this costs 2 less". At 6
#// resources the discount does not apply, so Yoda costs the printed 5 and leaves 1.
#// Boundary partner to CostReducedWith7Resources, which pays 3 at exactly 7.

## GIVEN
CommonSetup: bgw/rrk/{myResources:6;handCardIds:TS26_14}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1

---

# WhenPlayedSentinelExpiresAtTheEndOfThePhase
#// TS26_14 Yoda — the Clone keeps Sentinel only "for this phase". After both players pass out the action
#// phase and decline the next round's resource step, the token is still there but no longer a Sentinel.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5;handCardIds:TS26_14}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# WhenDefeatedInCombatCreatesACloneWithSentinel
#// TS26_14 Yoda — the second half of "When Played/WHEN DEFEATED". Yoda (4/4) attacks Army of the Dead
#// (7/6) and dies to the counter-damage; the arena is left holding exactly the Clone Trooper token he
#// made on the way out, carrying Sentinel for the phase.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TS26_14:1:0
WithP2GroundArena: LOF_236:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T02
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeatedSentinelAlsoExpiresAtTheEndOfThePhase
#// TS26_14 Yoda — the duration is the same whichever window created the token. Yoda dies in combat, then
#// the phase is passed out and the next round's resource step declined: the Clone survives without Sentinel.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1GroundArena: TS26_14:1:0
WithP2GroundArena: LOF_236:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TS26_T02
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# DefeatedAfterAControlChange_TheNEWControllerGetsTheClone
#// TS26_14 Yoda — "When Defeated" resolves for whoever controls him at that moment. P2 plays No Glory,
#// Only Results (JTL_043, "take control of a non-leader unit, then defeat it") on Yoda: P1 is left with
#// only their SOR_095, and the Clone Trooper token — with Sentinel — appears on P2's side of the board.

## GIVEN
CommonSetup: bgw/bbk/{theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [TS26_14:1:0 SOR_095:1:0]
WithP2Hand: JTL_043
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TS26_T02
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# DoubledTokensBothGetSentinel
#// TS26_14 Yoda with ASH_094 Moff Jerjerrod ("if you would create a number of tokens, you may defeat this
#// unit; if you do, create twice that number instead"). Accepting the offer defeats Jerjerrod and Yoda's
#// one Clone becomes two — and the Sentinel grant reaches BOTH, not just the first. The arena ends as
#// Yoda plus the two Clones.

## GIVEN
CommonSetup: bgw/rrk/{myResources:5;handCardIds:TS26_14}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_094:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TS26_14
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:2:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:2:HASKEYWORD:Sentinel
