# MultiCapture
#// SHD_092 Finalizer (Unit, cost 11, Command/Villainy, Space) — "When Played: Choose any number of friendly
#// units. Each of those units captures an enemy non-leader unit in the same arena." P1 has two ground units
#// (SOR_046, SOR_095); playing Finalizer, P1 chooses both, and each captures one of P2's two ground enemies
#// (SOR_128, SOR_160) — clearing P2's ground arena. (Finalizer itself is in space with no space enemy, so
#// it isn't offered as a captor.)
#// COVERAGE: offer=MultiCapture (the captor pick is answered out of a two-unit friendly pool and the
#//           victim pick out of a two-unit enemy pool) · decline=N/A ("choose ANY NUMBER" including
#//           zero is not a "you may" prompt; the no-legal-target end of the range is asserted by
#//           WhenPlayed_NoCaptureTargets_NoPrompt) · control=N/A (capture moves the victim under the
#//           captor's guard; no controller change of a unit in play is involved) ·
#//           boundary=MultiCapture (same-arena victims exist → captures) vs
#//           WhenPlayed_NoCaptureTargets_NoPrompt (victims only in a DIFFERENT arena → nothing) ·
#//           reqboundary=MultiCapture (the victim pick is read after the captor pick is answered)

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_092
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_160:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2

---

# WhenPlayed_NoCaptureTargets_NoPrompt
#// SHD_092 Finalizer — the capture is arena-matched: each chosen friendly unit must capture an enemy
#// non-leader unit IN THE SAME ARENA. Here P1's only units are in space and P2's only units are on the
#// ground, so no friendly unit has a legal victim → the When Played finds nothing to offer, no decision
#// is raised, and both boards are untouched.

## GIVEN
CommonSetup: ggk/ggk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_092
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
P1GROUNDARENACOUNT:0
