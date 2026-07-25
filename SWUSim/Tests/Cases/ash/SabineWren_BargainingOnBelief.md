# OppAdvantage_NextShielded
#// ASH_006 Sabine Wren — Leader Action [Exhaust]: an opponent gives 2 Advantage tokens to a unit they
#// control; if they do, the next unit you play this phase gains Shielded. P2's SOR_046 (its only unit,
#// auto-chosen) gets 2 Advantage; then P1 plays SOR_095, which enters with a Shield token.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_ShieldNextUnit
#// ASH_006 Sabine Wren (deployed) — On Attack: the next unit you play this phase gains Shielded
#// for this phase. Sabine attacks the base, then P1 plays an X-Wing → it enters with a Shield.

## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_237
WithP1Resources: 4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1

---

# Leader_NoEnemyUnit
#// ASH_006 Sabine Wren — Leader Action: an opponent gives 2 Advantage to a unit they control; "if they do"
#// the next unit you play gains Shielded. With NO enemy unit the opponent cannot give Advantage, so the
#// conditional Shielded never arms — the next unit P1 plays (SOR_095) enters with no Shield. The leader
#// still exhausts.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# Leader_OnlyFirstUnitShielded
#// ASH_006 Sabine Wren — only the NEXT unit played gains Shielded; later units this phase do not. After the
#// leader ability, SOR_095 (played first) enters with a Shield, but SOR_237 (played second) does not.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: [SOR_095 SOR_237]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>UseLeaderAbility
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:0

---

# Deployed_OnlyFirstUnitShielded
#// ASH_006 Sabine Wren (deployed) — the On Attack delayed effect Shields only the FIRST unit played this
#// phase. Sabine attacks, then SOR_095 (first) enters with a Shield while SOR_237 (second) does not.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_006:1:1:0
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: [SOR_095 SOR_237]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
