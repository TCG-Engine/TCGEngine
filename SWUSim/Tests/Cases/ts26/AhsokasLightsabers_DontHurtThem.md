# ShieldEnemyDiscountsNextEvent
#// TS26_035 Ahsoka's Lightsabers (Upgrade +2/+3) — attached unit gains "On Attack: you may give a Shield
#// to an enemy unit; if you do, the next event you play this phase costs 2 less." SEC_080 (wearing it)
#// attacks LAW_124, shields it, then Evade Arrest (cost 3) plays for 1 (3 resources → 2 left) — proving the
#// -2 discount, which only arms if the Shield was given.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_082}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_035
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1RESAVAILABLE:2
