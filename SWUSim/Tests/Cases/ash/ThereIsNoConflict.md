# ReturnNone
#// ASH_199 There Is No Conflict — "any number" includes zero. Declining the multi-select returns no
#// upgrades, so SOR_095 keeps SOR_120 and ends at 3 + SOR_120(+2) + ASH_199(+2) = 7 power (nothing in hand).
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_199}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1HANDCOUNT:0

---

# ReturnOtherUpgrades
#// ASH_199 There Is No Conflict (Upgrade, +2/+2, cost 2) — When Played: return any number of OTHER upgrades
#// on the attached unit to their owners' hands. Played onto SOR_095 (which already carries SOR_120), it
#// returns SOR_120 to hand. SOR_095 ends at 3 + ASH_199(+2) = 5 power, with SOR_120 back in hand.
## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_199}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1HANDCOUNT:1

---

# WhenPlayed_ReturnsOtherUpgrade
#// ASH_199 There Is No Conflict — When Played: return any number of OTHER upgrades on the attached unit to
#// their owners' hands. Played on SOR_095 (which wears SOR_120), it returns SOR_120 to hand; SOR_095 is left
#// wearing only There Is No Conflict.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_199}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
