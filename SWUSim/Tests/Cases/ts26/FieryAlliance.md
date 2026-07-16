# Deal1ToAnotherAndAttack
#// TS26_025 Fiery Alliance (Upgrade, cost 2) — When Played: you may deal 1 damage to another friendly unit
#// and attack with it. Attached to SEC_080; the "another" unit SOR_046 takes 1 and attacks the enemy base
#// for 3.
## GIVEN
CommonSetup: grk/rrk/{myResources:2;handCardIds:TS26_025}
WithP1GroundArena: [SEC_080:1:0 SOR_046:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:3
