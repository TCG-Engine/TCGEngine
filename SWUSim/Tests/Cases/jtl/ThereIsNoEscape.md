# LoseAbilities
#// JTL_244 There Is No Escape — Choose up to 3 units; they lose all abilities and can't gain abilities
#// this round. P1 targets the enemy SHD_147, which loses its Saboteur keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# MultipleUnitsMixed_LoseAbilities
#// JTL_244 There Is No Escape — "Choose UP TO 3 units" spans BOTH sides. P1 targets its OWN Sentinel unit
#// (SOR_035) AND the enemy Saboteur unit (SHD_147); both lose their keyword this round.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: SOR_035:1:0
WithP2GroundArena: SHD_147:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_035
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:CARDID:SHD_147
P2GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# StatModifyingAbilityRemoved
#// JTL_244 There Is No Escape — "lose all abilities" also removes a unit's OWN constant STAT-modifying
#// passive, not just keywords/triggers. IG-88 (JTL_141, "+3/+0 while an enemy unit is damaged") is at power
#// 7 with the enemy SOR_046 damaged; after P1 targets IG-88 it loses the ability and drops to its printed 4.
#// (Regression guard for the ObjectCurrentPower/HP LostAbilities gate.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_244
WithP1Resources: 6
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_141
P1GROUNDARENAUNIT:0:POWER:4
