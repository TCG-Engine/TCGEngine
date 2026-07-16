# ReturnNonUniqueUpgrades
#// LOF_205 Force Speed — Attack with a unit; for this attack it gains "On Attack: return any number of
#// non-unique upgrades attached to the defender to their owners' hands." Plo Koon attacks SOR_046, which
#// carries SOR_054 (non-unique) and SOR_053 (unique); only SOR_054 returns to P2's hand.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_205}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_054
WithP2GroundArenaUpgrade: 0:SOR_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:1
