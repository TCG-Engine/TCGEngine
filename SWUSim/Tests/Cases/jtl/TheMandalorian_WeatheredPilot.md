# AsUnit_ExhaustTwoGround
#// JTL_210 The Mandalorian — When played as a unit: Exhaust up to 2 ground units. P1 plays it and exhausts
#// both enemy ground units.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# AsPilot_ExhaustOneEnemyGround
#// JTL_210 The Mandalorian — played as a PILOT (onto a Vehicle), the when-played effect exhausts ONE enemy
#// GROUND unit (vs up to 2 when played as a unit). Piloted onto the friendly AT-ST, P1 exhausts the enemy
#// SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_210
