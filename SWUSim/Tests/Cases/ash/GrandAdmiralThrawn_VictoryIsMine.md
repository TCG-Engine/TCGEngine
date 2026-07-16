# EqualUnits_Restore
#// ASH_004 Grand Admiral Thrawn — Leader Action [Exhaust]: attack with a unit; it gains Restore 2 for this
#// attack if you control the same number of units as the defending player. P1 (1 unit) and P2 (1 unit) are
#// equal, so SOR_095's attack heals 2 from P1's base (5 → 3 damage) as it attacks SOR_046.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P1LEADER:EXHAUSTED

---

# UnequalUnits_NoRestore
#// ASH_004 Grand Admiral Thrawn — the Restore 2 is gated on equal unit counts. P1 controls 1 unit but P2
#// controls 2, so no Restore is granted and P1's base stays at 5 damage when SOR_095 attacks.
## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004;
  myBaseDamage:5
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_Decline
#// ASH_004 Grand Admiral Thrawn (deployed) — On Attack defeat is a "may"; declining ('-')
#// leaves the enemy unit alive.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1

---

# Deployed_OnAttack_DefeatEnemyUnit
#// ASH_004 Grand Admiral Thrawn (deployed) — On Attack: if you control more units than the
#// defending player, you may defeat a non-leader unit they control. P1 has 2 units (Thrawn +
#// Dark Trooper), P2 has 1 → may defeat the enemy unit.

## GIVEN
CommonSetup: gbk/brk/{
  myLeader:ASH_004:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
