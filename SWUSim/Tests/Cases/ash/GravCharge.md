# AttackEndDamageAndDefeatUpgrade
#// ASH_085 Grav Charge (Upgrade/Condition) — "When attached unit's attack ends: deal 4 damage to it and
#// defeat this upgrade." P2's SOR_046 (3/7) carries Grav Charge and attacks P1's base; afterward it takes
#// 4 damage and Grav Charge is defeated (UPGRADECOUNT 0).
## GIVEN
CommonSetup: bbk/bbw
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:ASH_085
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AttackEnd_DealsFourAndDefeatsSelf
#// ASH_085 Grav Charge — "When attached unit's attack ends: deal 4 damage to it and defeat this upgrade."
#// The host SOR_095 attacks the base, then takes 4 and the upgrade is gone.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:ASH_085
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
