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

---

# WhenDefeated_ChooseGround_ExhaustsGroundOnly
#// SEC_207 Lightmaker — the arena is chosen by the controller, and ONLY enemy units in the chosen arena
#//   are exhausted. SEC_207 (pre-damaged to 1 HP) attacks SOR_237 (kills it via Raid, dies to counter);
#//   on defeat P1 chooses Ground → the enemy ground unit SOR_046 is exhausted while the surviving enemy
#//   space unit JTL_069 is left ready.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_207:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Ground

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:READY

---

# WhenDefeated_ChooseSpace_ExhaustsSpaceOnly
#// SEC_207 Lightmaker — the arena choice is real in both directions: picking Space exhausts every enemy
#// SPACE unit and leaves the enemy GROUND units ready. Companion to the Ground-side section.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1SpaceArena: SEC_207:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Space

## EXPECT
P2SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# WhenDefeated_OpponentControlsNoUnits_NoOp
#// SEC_207 Lightmaker — with the opponent controlling nothing there is no arena worth choosing, so the
#// When Defeated resolves with no effect and no dangling decision. SEC_207 is defeated by P2's SOR_078
#// Vanquish so that the trade leaves P2's board genuinely empty.

## GIVEN
CommonSetup: yyw/bbk
WithActivePlayer: 2
WithP2Resources: 5
WithP1SpaceArena: SEC_207:1:0
WithP2Hand: SOR_078

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDCOUNT:1
