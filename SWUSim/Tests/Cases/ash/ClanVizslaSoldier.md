# WhenDefeated_DefeatUpgrade
#// ASH_165 Clan Vizsla Soldier (Ground, 2/3, cost 2) — When Defeated: you may defeat an upgrade. The
#// Soldier attacks SOR_046 (3/7 + SOR_120 = 5/9), dies to the 5 counter, and its When Defeated defeats
#// SOR_120 (SOR_046 reverts to 3 power).
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_165:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:POWER:3

---

# WhenDefeated_DefeatUpgrade
#// ASH_165 Clan Vizsla Soldier — When Defeated: you may defeat an upgrade. It dies attacking SOR_046 (which
#// wears SOR_120); its When Defeated defeats that upgrade, reverting SOR_046 to 3 power.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_165:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# WhenDefeated_DefeatFriendlyUpgrade
#// ASH_165 Clan Vizsla Soldier — When Defeated: you may defeat AN upgrade (any upgrade, including a friendly
#// one). Clan Vizsla (with a friendly SOR_046 wearing SOR_120 alongside it) attacks the enemy SOR_046 (3/7)
#// and dies to the 3 counter; its When Defeated defeats the FRIENDLY SOR_120, reverting the friendly SOR_046
#// from 5 back to 3 power.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_165:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# WhenDefeated_NoUpgrades_NoTrigger
#// ASH_165 Clan Vizsla Soldier — When Defeated is optional and needs an upgrade to defeat. With no upgrades
#// anywhere, Clan Vizsla dies attacking SOR_046 (3/7) and its When Defeated simply does nothing (no prompt).
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_165:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1NODECISION
