# OnAttackExpSelf
#// TS26_52 Sith Traditions (Upgrade +1/+1) — attached unit gains "On Attack: give an Experience token to
#// this unit." SEC_080 (3/3 + upgrade = 4/4) attacks the enemy base; the On-Attack Experience makes it
#// 5/5, so it deals 5 to the base.
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_52
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P2BASEDMG:5

---

# WhenDefeatedExpFriendly
#// TS26_52 Sith Traditions — attached unit also gains "When Defeated: give an Experience token to a
#// friendly unit." SEC_080 (wearing it, pre-damaged) attacks LAW_124 and dies to the counter; its
#// When-Defeated gives 1 Experience to the surviving friendly SOR_046 (3 power → 4).
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [SEC_080:1:3 SOR_046:1:0]
WithP1GroundArenaUpgrade: 0:TS26_52
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:4
