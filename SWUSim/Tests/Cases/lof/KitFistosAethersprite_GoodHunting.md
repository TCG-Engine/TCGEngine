# DefeatUpgrades
#// LOF_147 Kit Fisto's Aethersprite — Saboteur + When Played: may defeat any number of upgrades on a
#// unit. P1 defeats both upgrades on the enemy 3/7.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0&myTempZone-1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# DefeatSelectedUpgrades
#// LOF_147 Kit Fisto's Aethersprite — "any number" means the player may pick a subset. P1 selects the enemy
#// unit but defeats only ONE of its two upgrades; the other remains.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# SelectUnit_DefeatNone
#// LOF_147 Kit Fisto's Aethersprite — after selecting a unit, "any number" allows choosing zero upgrades;
#// declining the upgrade selection leaves both upgrades intact.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# PassUnitSelection
#// LOF_147 Kit Fisto's Aethersprite — the whole When-Played is optional; declining the unit selection does
#// nothing and both upgrades remain.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# FortifyUpgradeOnABase_IsNotAHost
#// LOF_147 Kit Fisto's Aethersprite — "You may defeat any number of upgrades ON A UNIT." The base carrying
#// a Fortify upgrade is not a unit, so it is never offered (sibling of the P-23 System Shock report). With
#// the base upgrade as the only upgrade on the table there is no offer and the Fortify stays.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5;handCardIds:LOF_147}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2BaseUpgrade: HMW_126

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2BASE:UPGRADECOUNT:1
