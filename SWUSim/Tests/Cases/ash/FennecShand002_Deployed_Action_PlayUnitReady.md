# ASH_002 Fennec Shand (deployed) — Action [1 resource, exhaust a friendly unit]: play a unit from
# your hand (paying its cost). It enters play ready. Fennec exhausts the Dark Trooper (cost), plays
# SOR_128 (3/1) which enters ready; Fennec herself does NOT exhaust (no self-Exhaust on the deployed side).

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
