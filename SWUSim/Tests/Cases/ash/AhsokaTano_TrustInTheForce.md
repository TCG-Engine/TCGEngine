# Deployed_OnAttack_BuffWeakerUnit
#// ASH_009 Ahsoka Tano (deployed, 5 power) — On Attack: may give a unit with less power than
#// this unit +2/+0 for this phase. The X-Wing (power 2 < 5) qualifies → power 4.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4

---

# BuffLowerPowerUnit
#// ASH_009 Ahsoka Tano — Leader Action [Exhaust]: choose a unit with less power than a friendly unit; it
#// gets +2/+0 for this phase. SOR_038 (5 power) is the high friendly; SOR_095 (3 power < 5) is the only
#// valid target (auto-resolved) and is buffed to 5.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_009
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_038:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:1:POWER:5
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_Decline
#// ASH_009 Ahsoka Tano (deployed) — the On Attack buff is optional. Declining leaves the X-Wing at its base
#// power 2.
## GIVEN
CommonSetup: ggw/brk/{myLeader:ASH_009:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENAUNIT:0:POWER:2

---

# LeaderAction_TargetEnemyUnit
#// ASH_009 Ahsoka Tano — Leader Action [Exhaust]: the target may be an ENEMY unit, so long as it has less
#// power than a friendly unit. Friendly SOR_038 (5 power) sets the max; the enemy SEC_080 (3 power < 5) is
#// the only valid target (SOR_038 itself is not < 5) → auto-resolved and buffed to power 5.
## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_009
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_038:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:POWER:5
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_UpgradeRaisesThreshold
#// ASH_009 Ahsoka Tano (deployed) — the On Attack threshold is Ahsoka's OWN power. With Mastery (LAW_129,
#// +3/+3) she is 8 power, so SOR_232 AT-ST (6 power < 8) becomes a valid target and is buffed to 8.
#// The seated units are index 0 = SOR_232, index 1 = the deployed leader (leader is appended last).
## GIVEN
CommonSetup: ggw/brk/{myLeader:ASH_009:1:1:0:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArenaUpgrade: 1:LAW_129
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:POWER:8

---

# Support_LeaderDeploy_GrantsOnAttackToChosenUnit
#// ASH_009 Ahsoka Tano has Support (when deployed, may attack with another unit; it gains her other
#// abilities for that attack). Deploying her lets SOR_232 AT-ST (6 power) make the attack and inherit her
#// On Attack buff. The threshold is now the ATTACKER's power (6), so SOR_095 Battlefield Marine (3 < 6) is
#// a valid target and is buffed to 5. AT-ST hits P2's base for 6.
## GIVEN
CommonSetup: ggw/brk/{myLeader:ASH_009;myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:POWER:5

---

# Deployed_OnAttack_WithRaid_NeverOffersItself
#// ASH_009 Ahsoka Tano (deployed) — "give a unit with less power than THIS unit +2/+0": a unit never has less power than
#// itself. ⚠ Found 2026-10-02 (bot work on the flip turn): the threshold is her IN-ATTACK power (Raid included, ruling
#// 07/21/2026) while each candidate is read at its CURRENT power, so with SEC_099 Naboo Royal Starship's lent Raid 2 she is
#// 7 in the attack and 5 as a candidate — and she was offered to herself. The Starship (2 < 7) is a real target.
## GIVEN
CommonSetup: ggw/brk/{myLeader:ASH_009:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_099:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1SELECTABLEHAS:mySpaceArena-0
P1SELECTABLENOT:myGroundArena-0

---

# Support_GrantedOnAttack_WithRaid_NeverOffersTheAttacker
#// ASH_009 Ahsoka Tano — the On Attack her Support lends uses the ATTACKER as "this unit", and the attacker also borrows
#// the Starship's Raid 2 (Support lends gained abilities): SOR_232 AT-ST is 8 in the attack, 6 as a candidate, and was
#// offered to itself. The Marine (3 < 8) is a real target.
## GIVEN
CommonSetup: ggw/brk/{myLeader:ASH_009;myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SEC_099:1:0
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SELECTABLEHAS:myGroundArena-1
P1SELECTABLENOT:myGroundArena-0
