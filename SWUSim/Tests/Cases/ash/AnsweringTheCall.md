
---

# FourUnits_DealThreeToEachEnemy
#// ASH_112 (Answering the Call) — When Played: if you control at least 4 units, deal 3 damage to each enemy
#// unit. Playing it as the 4th unit clears the enemy SOR_128 (3/1).
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
