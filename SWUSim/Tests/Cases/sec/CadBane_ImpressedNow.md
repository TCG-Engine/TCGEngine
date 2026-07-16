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
