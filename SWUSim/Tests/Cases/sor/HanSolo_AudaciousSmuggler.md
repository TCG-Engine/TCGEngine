# Falcon_Combo
#// SOR_017 Han Solo + SOR_193 Millennium Falcon — the combo.
#//
#// Han's leader action ramps a resource and leaves a pending "defeat a resource you control at
#// the start of the next action phase." The Falcon's regroup trigger lets you pay 1 resource to
#// keep her — exhausting a resource. The synergy: the resource you exhaust to keep the Falcon
#// becomes the one you feed to Han's mandatory defeat, so you keep the Falcon "for free" and
#// never have to defeat a ready resource.
#//
#// Sequence:
#//   1. Han leader action: hand card → ready resource (2 → 3), pending defeat armed.
#//   2. Both pass → regroup. During the Ready step the Falcon asks pay-or-bounce; pay 1 resource
#//      (exhaust resource 0) to keep her.
#//   3. Next action phase starts → Han's pending trigger: defeat resource 0 (the exhausted one).
#//   Net: Falcon stays, resources 3/1 → 3/0 (all 3 ready), one resource in discard.
#//
#// NOTE (phase-crossing): both players answer the Resource-step MZMAYCHOOSE by resourcing their first hand card
#// the Ready step (Falcon trigger) and the next Action phase (Han's pending defeat) are reached.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_193
WithP1Resources:2
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE
- P1>Pass
- P1>ResourceHand:0
- P2>ResourceHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1

---

# LeaderAction_EmptyHand_NoOp
#// SOR_017 Han Solo — Leader Action requires a card in hand to put into play as a resource.
#// With an empty hand there is nothing to resource, so the action is a complete no-op:
#// Han stays ready, resources unchanged, and the player keeps their action (no decision pending).

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1RESCOUNT:2
P1RESAVAILABLE:2
P1NODECISION

---

# LeaderAction_PendingDefeatNextActionPhase
#// SOR_017 Han Solo — Leader Action delayed downside:
#// "...At the start of the next action phase, defeat a resource you control."
#// Han ramps (hand card → ready resource, 3 → 4). Both players pass → regroup phase runs
#// (draw, resource, ready). At the start of the NEXT action phase Han's pending trigger fires:
#// the player must defeat one resource they control (mandatory, player chooses which).
#// Resources 4 → 3, defeated resource goes to discard.
#//
#// NOTE (phase-crossing): ending the action phase pauses auto-advance at the Resource step
#// (each player has a "resource up to 1 card" MZMAYCHOOSE that does not auto-resolve), so both
#// players must answer with ResourcePass before the cycle reaches Ready → next Action phase.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3
P1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
P2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>AnswerDecision:myResources-0

## EXPECT
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:1

---

# LeaderAction_RampFromHand
#// SOR_017 Han Solo "Audacious Smuggler" — Leader Action [exhaust]:
#// "Put a card from your hand into play as a resource and ready it."
#// One hand card (SOR_095) auto-resolves → becomes a READY resource. Han exhausts.
#// Resources go 3 → 4, all 4 ready (the new one entered READY, not exhausted).

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:4
P1HANDCOUNT:0

---

# OnAttack_RampFromDeck
#// SOR_017 Han Solo (deployed leader unit) — On Attack:
#// "Put the top card of your deck into play as a resource and ready it."
#// Han is deployed (free, 6 resources), then attacks P2's base. OnAttack puts the top deck
#// card into play as a READY resource (mandatory — no "may"). Resources 6 → 7, deck 3 → 2,
#// P2 base takes 4 (Han's power). Han is exhausted from attacking.

## GIVEN
CommonSetup: gyw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
P1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SOR_017
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:4
P1RESCOUNT:7
P1RESAVAILABLE:7
P1DECKCOUNT:2
