# SEC_122 Vuutun Palaa — Droids pay full cost of a unit → resources-paid = 0
# "Each friendly Droid unit may be exhausted to pay costs as if it were a resource."
# P1 controls SEC_122 in the Space arena and 2 ready TWI_T01 Battle Droids in the Ground
# arena. LAW_231 Weequay Pirate (cost 2, Cunning) is in hand. P1 has 0 ready resources.
# Playing LAW_231 triggers the Droid alt-pay MZMULTICHOOSE (max 2). P1 exhausts both Droids
# → cost 2 fully covered by Droids, real resources paid = 0.
# LAW_231 "When Played: If no resources were paid to play this unit, give it an Experience
# token." → SWUUnitResourcesPaid returns 0 == 0 → LAW_231 gets +1/+1 (3/2 → 4/3).
# Assertion: P1RESAVAILABLE:0 (no resources spent), LAW_231 Power:3/HP:4 (base 3/2 + token).
# Both Battle Droids are EXHAUSTED (Status:0). No bounce or other side effects.
#
# Leader: yyk = Cunning+Villainy (SOR_016 Thrawn) + Cunning base (SOR_029).
# LAW_231 is Cunning — Thrawn covers that aspect → no penalty (effective cost = 2).
# SEC_122 is placed directly via WithP1SpaceArena (no play cost incurred here).
#
# LAW_231 is the only unit in hand (auto-selected → no WhenPlayed target prompt needed).
# MZMULTICHOOSE answer: both TWI_T01s at myGroundArena-0 and myGroundArena-1.

## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:LAW_231
P1GROUNDARENAUNIT:2:POWER:3
P1GROUNDARENAUNIT:2:HP:4
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1RESAVAILABLE:0
P1RESCOUNT:0
P1HANDCOUNT:0
