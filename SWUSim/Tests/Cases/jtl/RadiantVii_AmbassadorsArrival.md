# DamageDebuffAura
#// JTL_226 Radiant VII — Each enemy non-leader unit gets -1/-0 for each damage on it. P2's SOR_046
#// (power 3) with 2 damage is reduced to power 1.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_226:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1

---

# WhenPlayed_5Indirect
#// JTL_226 Radiant VII — When Played: Deal 5 indirect to a player. P1 deals 5 indirect to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_226
WithP1Resources: 15

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5
