# OppDraw_GiveExp
#// JTL_111 Seasoned Fleet Admiral — When an opponent draws 1+ cards during the action phase, you may give
#// an Experience token to a unit. P1 plays a filler, then SOR_190 (Lothal Insurgent) makes P2 draw; P1's
#// Admiral reacts and gives an Experience token to itself (1/4 → 2/5).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_063
WithP1Hand: SOR_190
WithP1Resources: 12
WithP2Hand: SOR_095
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# Raid1_AttackBonus
#// JTL_111 Seasoned Fleet Admiral has Raid 1 — while attacking it gets +1/+0. The 1-power Admiral attacks
#// P2's base for 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# OppDraw_DeclineExp
#// JTL_111 Seasoned Fleet Admiral — the give-Experience reaction is a MAY. P2 draws (via SOR_190), P1's
#// Admiral reacts but DECLINES (Pass), so no Experience is given and it stays at 1 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_063
WithP1Hand: SOR_190
WithP1Resources: 12
WithP2Hand: SOR_095
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
