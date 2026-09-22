# TakingTheBlastCounter_LogsTheCounter_NotAPass
#// Player report (Twin Suns, 2026-09-21): "an extraneous log after someone takes a token … it says 'P1 passed'".
#// Taking a counter IS the seat's pass for the rest of the round (CR §12.5), and the claim is already logged
#// ("P1 took the blast counter"). SWUPassAction suppressed the "passed" line only for the INITIATIVE claim
#// (INITIATIVECOUNTER == P<n>_CLAIMED), so a Blast / Plan taker got a "P1 passed" line at the moment of taking it
#// and again every time the rotation skipped over it. The suppression now follows _SWUSeatTookCounterThisRound,
#// which covers all three counters.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:P1
LOGCOUNT:1:P1 took the blast counter
LOGCOUNT:0:P1 passed

---

# TakingThePlanCounter_LogsTheCounter_NotAPass
#// The same for the Plan counter (its draw/bottom prompt is declined here).
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:plan
- P1>Pass
## EXPECT
PLANCOUNTER:P1
LOGCOUNT:1:P1 took the plan counter
LOGCOUNT:0:P1 passed

---

# ARealPassIsStillLogged
#// Control: a seat that genuinely chooses to pass still gets its "passed" line — only counter-takers are silent.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
- P2>Pass
## EXPECT
LOGCOUNT:1:P2 passed
LOGCOUNT:0:P1 passed

---

# InitiativeClaim_StillNoPassedLine
#// Unchanged behaviour for the initiative claim (what the old check already covered).
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>Claim
## EXPECT
INITIATIVECOUNTER:P1_CLAIMED
LOGCOUNT:1:P1 took the initiative
LOGCOUNT:0:P1 passed
