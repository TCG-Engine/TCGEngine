# Deployed_Action_PlayUnitReady
#// ASH_002 Fennec Shand (deployed) — Action [1 resource, exhaust a friendly unit]: play a unit from
#// your hand (paying its cost). It enters play ready. Fennec exhausts the Dark Trooper (cost), plays
#// SOR_128 (3/1) which enters ready; Fennec herself does NOT exhaust (no self-Exhaust on the deployed side).

## GIVEN
CommonSetup: brw/brk/{
  myLeader:ASH_002:1:1:1;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SOR_128
WithP1Resources: 6

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:SOR_128
P1GROUNDARENAUNIT:2:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY

---

# PlayUnitReady
#// ASH_002 Fennec Shand — Leader Action [1 resource, Exhaust, exhaust a friendly unit]: play a unit from
#// your hand (paying its cost); it enters play ready. P1 exhausts SEC_135 (the cost, auto-chosen) and plays
#// SOR_095 (auto-chosen), which enters the ground arena READY; Fennec exhausts.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_002
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP1GroundArena: SEC_135:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1LEADER:EXHAUSTED

---

# SelfExhaustAsCost
#// ASH_002 Fennec Shand (deployed) — the "exhaust a friendly unit" cost may be paid by exhausting Fennec
#// herself. With Battlefield Marine (SOR_095) also on board, P1 chooses Fennec (leader unit, index 1) as the
#// cost; the Marine stays ready and Wampa (SOR_164) enters play ready.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:ASH_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_164
## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:ASH_002
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:2:CARDID:SOR_164
P1GROUNDARENAUNIT:2:READY

---

# Saboteur_WhenDeployed
#// ASH_002 Fennec Shand — the deployed leader unit has Saboteur, so it ignores enemy Sentinels. With P2's
#// Echo Base Defender (SOR_098, Sentinel) present, Fennec can attack the enemy base directly for 3.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:ASH_002:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_098:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2BASEDMG:3
P2GROUNDARENACOUNT:1
