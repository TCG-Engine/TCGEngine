# Bounty_Ready2Resources
#// SHD_221 Wanted — Upgrade, cost 0, [Cunning], traits Bounty/Condition. Attached unit gains
#// "Bounty - Ready 2 friendly resources." (Bounty = when the attached unit is defeated or captured, ITS
#// opponent collects.) "Friendly" is read from the COLLECTOR's seat, so the readied resources are the
#// bounty-collector's, not the host controller's.
#// COVERAGE: offer=N/A (no target pick — "ready 2 friendly resources" takes no choice; SWUSim readies
#//           exhausted resources automatically) ·
#//           request boundary=N/A (the collect decision is a single YES; nothing is re-read afterwards) ·
#//           control=Bounty_Ready2Resources — the upgrade is on a unit P2 controls and it is P1's
#//           resources that ready, which is the seat-crossing case ·
#//           boundary pair=Bounty_Ready2Resources (3 exhausted → exactly 2 ready, it does NOT ready all)
#//           + Bounty_Ready1Resource_WhenOnly1Exhausted (fewer exhausted than 2 → readies what exists
#//           and does not error or over-ready) ·
#//           decline=Bounty collection is prompted (the YES answer in both sections); a pass branch is
#//           N/A because declining leaves the board identical to never having collected.
#// P2's marine wears it; LAW_124 defeats it; P1 collects: exactly 2 of P1's 3 exhausted resources ready.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_221
WithP1Resources: 3:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:2

---

# Bounty_Ready1Resource_WhenOnly1Exhausted
#// SHD_221 Wanted — "Ready 2 friendly resources" is an UP-TO in practice: with only 1 exhausted resource
#// to work with, the collect readies that one and stops. Same fixture as Bounty_Ready2Resources except
#// P1's resource row is 2 ready + 1 exhausted; after collecting, all 3 are ready (not 4 — the count
#// cannot grow) and nothing errors on the unfillable second ready.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_221
WithP1Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:3
