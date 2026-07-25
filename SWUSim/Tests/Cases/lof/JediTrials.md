# OnAttackExp
#// LOF_052 Jedi Trials — Attach to a Force unit; attached gains "On Attack: give an Experience token to
#// this unit." Plo Koon (with Jedi Trials) attacks the base and gains an Experience token (2 subcards: the
#// Trials upgrade + the new Experience).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_052

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# GrantsJediTraitAtFourUpgrades
#// LOF_052 Jedi Trials — "While attached unit has 4 or more upgrades on it, it gains the Jedi trait."
#// SOR_061 Guardian of the Whills (Force/Fringe — not printed Jedi) with LOF_052 + 3 Shield tokens = 4
#// upgrades gains the Jedi trait. (Regression: this trait-grant clause was unimplemented; only the
#// On-Attack Experience ability + the Force-host restriction were wired.)

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: SOR_061:1:0
WithP1GroundArenaUpgrade: 0:LOF_052
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:HASTRAIT:Jedi

---

# NoJediTraitBelowFourUpgrades
#// Control: with only 3 upgrades (LOF_052 + 2 Shields), the host does NOT gain the Jedi trait — proving
#// the grant is gated on the 4-upgrade threshold, not on merely having Jedi Trials attached.

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: SOR_061:1:0
WithP1GroundArenaUpgrade: 0:LOF_052
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArenaUpgrade: 0:SOR_T02

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:NOTTRAIT:Jedi
