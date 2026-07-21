# PlayedUnitDealsPower
#// ASH_102 Ravager (Space, 8/10, Restore 2) — When you play a unit: you may have it deal damage equal to
#// its power to a unit in the same arena. With Ravager in play, P1 plays SOR_095 (3 power); it deals 3 to
#// the enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0

---

# PlayedUnit_Pass
#// ASH_102 Ravager — the deal is optional. P1 plays SOR_095 with an enemy present but passes; no damage.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# PlayedUnit_TargetFriendly
#// ASH_102 Ravager — "a unit in the same arena" may be a FRIENDLY one. Played SOR_095 (3 power) deals 3 to
#// the friendly SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095

---

# PlaySpaceUnit_DealsInSpace
#// ASH_102 Ravager — the "same arena" is the played unit's arena. A played SPACE unit SOR_237 (2 power)
#// deals 2 to the enemy space token JTL_T02 (2/2), defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_237}
WithP1SpaceArena: ASH_102:1:0
WithP2SpaceArena: JTL_T02:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0

---

# UpgradePlay_NoTrigger
#// ASH_102 Ravager — the trigger is "when you play a UNIT." Playing an upgrade (SOR_120 on SOR_095) is not a
#// unit play, so Ravager does not fire and the enemy takes no damage.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_120}
WithP1SpaceArena: ASH_102:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
