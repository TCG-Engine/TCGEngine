# WhenPlayed_Deal4Ground
#// SEC_142 Fulminatrix (Space, 9/7, Aggression/Villainy, cost 8) — When Played / On Attack: you may deal
#//   4 to a ground unit. Hits the enemy SOR_046.

## GIVEN
CommonSetup: rrk/grw/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_142

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION

---

# OnAttack_Deal4Ground
#// SEC_142 Fulminatrix — On Attack: you may deal 4 to a ground unit. Fulminatrix (seated in space) attacks
#//   P2's base; on attack it may deal 4 to a ground unit → hits the enemy SOR_046 (0 -> 4 damage).

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithActivePlayer: 1
WithP1SpaceArena: SEC_142:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1NODECISION
