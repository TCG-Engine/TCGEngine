# UnitFromResources_WhenPlayedDefeats
#// PLOT (CR §19) — SEC_034 Cad Bane (Unit, Plot, cost 5, Vigilance/Villainy)
#//   "When Played: You may defeat a unit with 2 or less remaining HP."
#// Proves that a card played via Plot fires its entry (When Played) triggers exactly as a
#// normal play does (CR 19.b — abilities triggered by playing a Plot card resolve before the
#// next Plot card is played).
#//
#// P1 controls SEC_034 as myResources-0 + 5 vanilla resources (6 ready — meets Iden's deploy
#// threshold of 6; SEC_034 costs 5). bk leader covers Vigilance+Villainy → no penalty.
#// An enemy Battlefield Marine sits damaged (3/3 with 1 damage → 2 remaining HP), a legal
#// target for Cad Bane's When Played.
#//
#// Flow: deploy → Plot offers SEC_034 → play it (cost 5, ready 6 → 1) → When Played MZMAYCHOOSE
#// → defeat the 2-HP enemy. Slot replaced by top of deck.

## GIVEN
CommonSetup: bbk/grw
P1OnlyActions: true
WithP1Resources: 1:SEC_034:1,5:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>DeployLeader
#// ⚠ ORDERING STEP (bug #1024). The leader's own When Deployed trigger and the CR 19 Plot window
#// are two simultaneous triggered abilities, so CR 7.6.9 gives the player the order. EffectStack-0
#// is the Plot window (armed first, in SWUDeployLeader); EffectStack-1 is the leader. Resolving the
#// leader FIRST is the sequence this section has always measured — the step was previously forced.
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_034
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1RESCOUNT:6
P1RESAVAILABLE:1
P1DECKCOUNT:1
P1NODECISION

---

# WhenPlayed_MayDecline
#// SEC_034 Cad Bane — "When Played: You may defeat a unit with 2 or less remaining HP" is OPTIONAL.
#//   Played from hand with a friendly 2-HP unit (SOR_140) and an enemy 2-HP unit (SOR_140) available,
#//   declining (Pass) defeats nothing; a high-HP enemy (SOR_164 Wampa) was never eligible.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_140:1:0
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_140:1:0
WithP1Hand: SEC_034

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION

---

# WhenPlayed_DefeatFriendly
#// SEC_034 Cad Bane — the "defeat a unit with 2 or less remaining HP" target may be a FRIENDLY unit.
#//   Cad Bane is played and defeats P1's own SOR_140 SpecForce Soldier (2/2). Cad Bane itself (5 HP) is
#//   never eligible.

## GIVEN
CommonSetup: bbk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_140:1:0
WithP1Hand: SEC_034

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_034
P1DISCARDCOUNT:1
P1NODECISION
