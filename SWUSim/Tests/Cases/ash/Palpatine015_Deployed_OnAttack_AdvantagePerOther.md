# ASH_015 Emperor Palpatine (deployed) — On Attack: may choose another exhausted friendly unit;
# if you do, give it an Advantage token for each OTHER friendly unit. Choosing the exhausted Dark
# Trooper: other friendly units = Palpatine + the space TIE = 2 → 2 Advantage tokens.

## GIVEN
CommonSetup: gyk/brk/{
  myLeader:ASH_015:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:0:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
