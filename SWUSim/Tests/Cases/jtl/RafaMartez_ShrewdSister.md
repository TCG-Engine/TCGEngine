# WhenPlayed_DamageReadyResource
#// JTL_219 Rafa Martez — When Played: Deal 1 damage to a friendly unit and ready a resource. P1 deals 1
#// to SOR_046 and readies a resource (6 resources, 5 paid off-aspect, 1 readied → 2 available).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_219
WithP1Resources: 6
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:2

---

# OnAttack_DamageReadyResource
#// JTL_219 Rafa Martez — On Attack (same ability as When Played): deal 1 damage to a friendly unit and
#// ready a resource. Rafa attacks P2's base; the trigger deals 1 to the friendly SOR_237 and readies P1's
#// one exhausted resource (0 → 1 available).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_219:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 1:SOR_095:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:1
P2BASEDMG:3
