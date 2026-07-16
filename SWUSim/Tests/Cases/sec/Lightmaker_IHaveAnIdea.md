# WhenDefeated_ExhaustArena
#// SEC_207 Lightmaker (Space, 3/4) — Raid 4 + When Defeated: choose an arena; exhaust each enemy unit in
#//   that arena. SEC_207 (pre-damaged to 1 HP) attacks SOR_237 (kills it, Raid → 7) and dies to the
#//   counter; on defeat P1 chooses Space → the surviving enemy space unit JTL_069 is exhausted.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_207:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Space

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:EXHAUSTED
