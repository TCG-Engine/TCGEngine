# VISUAL CHECK — HMW_036 Kelnacca: the strikes are assigned in STEPS of its power
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then play Kelnacca from P1's hand.
#
# WHY THIS EXISTS (2026-09-14)
# Kelnacca: "When Played: You may pay any number of resources. For every 3 resources paid this way, deal
# damage equal to this unit's power to an enemy unit." A "for every" effect resolves all its instances
# together (CR 34.1 / 34.1.a), so the strikes are chosen in ONE divided-damage prompt and dealt at once —
# a single Shield then stops every strike aimed at its unit. USER DECISION: the prompt is the ordinary
# MZSPLITASSIGN overlay, but each −/+ moves ONE STRIKE (Kelnacca's power), not one point. The param
# carries the step as a 4th segment ("8|theirGroundArena-0&theirGroundArena-1|ALL|4"); older params have
# no 4th segment and must behave exactly as before (step 1).
#
# THE BOARD — P1 has 10 resources; Kelnacca costs 4 on this Command/Vigilance board. P2 has two ground
# units: Consular Security Force (3/7, with a Shield) and Ravenous Rathtar (8/5).
#
# WHAT TO LOOK AT — play Kelnacca, choose to pay 6 (the number picker offers 0..6):
#   1. The banner reads "Assign 2 strikes of 4 damage among enemy units", Remaining: 8, Confirm disabled.
#   2. ⚠ THE CORE CHECK: one + on a unit shows 4 (not 1), Remaining drops to 4. A second + on the SAME unit
#      shows 8 and Remaining 0 — stacking both strikes on one unit is legal.
#   3. With Remaining 0, every + is disabled; − on the 8 goes back to 4 (one strike), never to 7.
#   4. Confirm is enabled only at Remaining 0 (every strike must be placed).
#   5. Assign 4 + 4 (one strike each) and Confirm: the Consular's Shield pops and it takes 0; the Rathtar
#      takes 4. Both resolve at once.
#   6. Replay with both strikes on the Shielded Consular (8): the Shield pops and it takes 0 — one Shield
#      stops the whole assignment.
#   7. Regression check on an ordinary split (any "deal N damage divided as you choose" card, e.g. SHD_177
#      Vambrace Flamethrower): −/+ still move ONE point.
#   8. Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine, so
#      say so rather than implying it was covered.

## GIVEN
CommonSetup: gbw/gbw/{myResources:10}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Hand: [HMW_036]
WithP2GroundArena: [SOR_046:1:0 LOF_168:1:0]
WithP2GroundArenaUpgrade: 0:SOR_T02
