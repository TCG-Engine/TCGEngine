# OnAttack_NoUpgrades_JustCombat
#// ASH_156 R5-D4 (Ground, 3/4, Support) — On Attack: defeat all upgrades on the defending unit. When the
#// defender wears no upgrades the ability simply no-ops and combat proceeds: R5-D4 attacks SOR_046 (3/7),
#// deals 3, and takes 3 counter (survives at 4 HP).
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnAttack_Base_LeavesUpgradesUntouched
#// ASH_156 R5-D4 — the On Attack upgrade-defeat only fires against a defending UNIT. Attacking the enemy
#// base instead, the bystander SOR_046 keeps its SOR_120 upgrade; the base takes 3.
## GIVEN
CommonSetup: grk/grk
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Support_LendOnAttack_DefeatsDefenderUpgrade
#// ASH_156 R5-D4 — Support lends its On Attack to the borrowing attacker. R5-D4 is played from hand, the
#// Battlefield Marine (SOR_095, 3/3) is chosen to attack SOR_046 (3/7) wearing SOR_120 (+2/+2 → 5/9); the
#// lent On Attack defeats SOR_120 (back to 3/7) before combat, then the Marine deals 3.
## GIVEN
CommonSetup: rrw/grw/{myResources:9;handCardIds:ASH_156}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
