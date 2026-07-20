# AttachToEnemyVehicle
#// JTL_213 Sidon Ithano — "When played as a unit: You may attach this unit as an upgrade to an enemy
#// Vehicle unit without a Pilot on it." Played as a unit (no friendly Vehicle → no Pilot option), Sidon
#// attaches onto the enemy SOR_237 (2/3 X-Wing). As a Pilot he is −2/−2, so the enemy ship drops to 0/1.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_213
P2SPACEARENAUNIT:0:POWER:0
P2SPACEARENAUNIT:0:HP:1

---

# PlayedAsUnit_NoEnemyVehicle
#// JTL_213 Sidon Ithano — the "attach to an enemy Vehicle without a Pilot" is a WHEN-PLAYED "may". With no
#// eligible enemy Vehicle (the opponent has only a ground non-Vehicle unit), Sidon simply enters as a normal
#// ground unit.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:JTL_213}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_213
