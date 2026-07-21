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
