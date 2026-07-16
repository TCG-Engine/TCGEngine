# EnemyDefeatedByGideon_GivesExp
#// SOR_036 Gideon Hask (5/5) — "When an enemy unit is defeated: Give an Experience token to a
#// friendly unit." Gideon attacks and defeats P2's 3/1; the reactive trigger gives an Experience
#// token to the only friendly unit (himself) → 6/6 (with 3 combat damage on him).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# EnemyDefeatedWithEvent_GivesExp
#// SOR_036 Gideon Hask (5/5) — "When an enemy unit is defeated: Give an Experience token to a
#// friendly unit." Gideon attacks and defeats P2's 3/1; the reactive trigger gives an Experience
#// token to the only friendly unit (himself) → 6/6 (with 3 combat damage on him).

## GIVEN
CommonSetup: bbk/grk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SOR_036:1:0
WithP1GroundArena: SEC_080
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_077

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>ChooseMyGroundUnit:1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
