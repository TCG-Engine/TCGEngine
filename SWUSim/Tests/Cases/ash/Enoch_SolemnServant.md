# DefeatDiscountsNextUnit
#// ASH_027 Enoch (Ground, 4/5) — When Defeated: you may deal up to 6 damage to your base; the next unit
#// you play this phase costs 1 less for every 2 damage dealt. Enoch (pre-damaged to 2 HP) attacks SEC_080
#// and dies; the player deals 6 to its own base (= 3 charges), then plays SOR_046 (cost 4) for 1 resource.
## GIVEN
CommonSetup: bbw/bbk/{myResources:1;handCardIds:SOR_046}
WithP1GroundArena: ASH_027:1:3
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:6
- P1>PlayHand:0
## EXPECT
P1BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1RESAVAILABLE:0
