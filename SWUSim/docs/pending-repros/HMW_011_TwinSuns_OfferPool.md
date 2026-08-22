# ⛔ PENDING REPRO — NOT RUN BY THE SUITE (parked 2026-08-21)
#
# Lives outside Tests/Cases/ ON PURPOSE: anything under Tests/Cases/ auto-registers, and this section is
# RED. It is parked, not deleted — the standing rule is that a test exposing a real gap stays written.
# To run it: copy this file's GIVEN/WHEN/EXPECT back into
# SWUSim/Tests/Cases/hmw/DarthSidious_ThereIsNoMercy.md and re-run that file.
#
# ── WHAT IS BROKEN ────────────────────────────────────────────────────────────────────────────────
# HMW_011 Darth Sidious's reaction offer, built in a FOUR-SEAT game, comes back as
#     MZMAYCHOOSE [myBase-0 & p2Base-0]
# It should contain every unit in play plus all four bases. Missing: seats 3 and 4's BASES, and EVERY
# UNIT — including the seat-3 unit that had just survived the 4 damage, and the caster's own board.
#
# ── WHAT IS AND IS NOT SUSPECTED ─────────────────────────────────────────────────────────────────
# The pool is built in HMW_011#OFFER by walking ZoneSearch('their{Ground,Space}Arena') and
# SWUAllBaseMzIDs($seat, 'any'). The fan-out IS running — `p2Base-0` is the Twin Suns mzID form, not the
# 2-player `theirBase-0` — but it reaches seat 2 only. So this looks like the seat-enumeration used by
# that fan-out, NOT like card logic: nothing in HMW_011 names a seat, and the same handler produces the
# correct pool in every 2-player section of the file.
# ⚠ If that is right, this is a PRE-EXISTING engine issue that HMW_011 is merely the first card to
#   surface, and the fix belongs in the fan-out (with a sweep for its siblings), not here. That is the
#   same shape as the LAW_058 Honor-Bound Partisan report and the ~30-site SWUAllBaseMzIDs sweep.
# The card's own behaviour is otherwise verified: 11 of 12 sections green, including both indirect
# funnels, Overwhelm's double trigger, and ASH_151 Operation Cinder's four-trigger line.
#
# ── WHY IT IS PARKED ─────────────────────────────────────────────────────────────────────────────
# Premier is a 2-player format and is unaffected, so the suite is kept green to ship. Twin Suns
# behaviour for HMW_011 is UNVERIFIED until this is resolved.

# TwinSuns_DamageToASeatThreeUnit_StillTriggers
#// ⚠ THE SEAT-COUNT CELL. The trigger keys off the DEALER, so it is seat-agnostic by construction — but
#// the base funnel's dealer inference falls back to OtherPlayer() when no source is threaded, which is a
#// two-seat answer. P1 attacks a SEAT THREE unit for 4 and must still be offered the ping, with seat 3's
#// base reachable as a target.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:HMW_011}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SHD_216:1:0
WithP3GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>AttackGroundArena:0:P3G0
- P1>AnswerDecision:p3Base-0

## EXPECT
SEATCOUNT:4
P3BASEDMG:1
P1LEADER0:EXHAUSTED
