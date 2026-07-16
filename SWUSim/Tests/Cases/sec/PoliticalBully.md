# NoOfficial_NoDeal
#// SEC_241 Political Bully — no other Official controlled → no damage offered.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_241

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WithOfficial_Deal2
#// SEC_241 Political Bully (Ground, 2/3, Villainy, cost 3) — When Played: if you control another Official
#//   unit, you may deal 2 to a ground unit. SEC_041 (Official) is in play → deal 2 to the enemy.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_241

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
