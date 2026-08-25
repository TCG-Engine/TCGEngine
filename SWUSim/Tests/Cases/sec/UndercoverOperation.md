# ReadyPlayedUnit_CheapSpy
#// SEC_236 Undercover Operation (Event, cost 3, Cunning) — "Ready a unit that was played this phase. If
#//   it costs 3 or less, create a Spy token." P1 plays SOR_095 (cost 2, enters exhausted, marked
#//   played-this-phase), then plays SEC_236 → ready SOR_095 → cost 2 ≤ 3 → create a Spy.

## GIVEN
CommonSetup: gyw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SEC_236

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2
P1NODECISION

---

# ReadyPlayedUnit_ExpensiveNoSpy
#// SEC_236 Undercover Operation — readies a unit played this phase, but creates NO Spy token when that
#//   unit costs MORE than 3. P1 plays Wampa (SOR_164, cost 4, enters exhausted), then plays Undercover →
#//   ready Wampa → cost 4 > 3 → no Spy (ground arena holds only Wampa).

## GIVEN
CommonSetup: gyw/rrk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_164
WithP1Hand: SEC_236

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:1
P1NODECISION

---

# RescuedUnitWasNotPLAYEDThisPhase_NotAValidReadyTarget
#// SEC_236 Undercover Operation — "a unit that was PLAYED this phase". A unit that entered play by being
#// RESCUED from capture was not played, so it must not be offered. P1 plays SOR_095 (played this phase),
#// P1's base captures P2's SOR_046 with SEC_195 Arrest and it is rescued back at regroup — but that is a
#// later phase anyway, so within this phase the only played unit is SOR_095 and Undercover auto-resolves
#// onto it, leaving the enemy board alone.

## GIVEN
CommonSetup: gyw/rrk/{myResources:9}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SEC_236
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# UnqualifiedPool_OffersAnOpponentsPlayedUnit
#// SEC_236 reads "Ready A UNIT that was played this phase" — UNQUALIFIED, so it names no controller and
#// spans the WHOLE TABLE. USER RULING 2026-08-25. Previously narrowed to the caster's own board, which
#// was wrong in 2-player as well as Team Suns.
#// Both players play a unit this phase, so BOTH are eligible — two targets, so the choice cannot
#// auto-resolve and the offer is inspectable.
#// ⚠ theirResources:9 — SOR_095 is Command/Heroism and P2's rrk pair is Aggression/Villainy, so it
#// takes a DOUBLE aspect penalty (2 -> 6). At 5 resources P2 simply could not play it and the section
#// failed with "no pending decision", which reads like a broken assertion rather than a broke fixture.
#// ⚠ SWU_PLAYED_UNIT_{uid} lives on the unit's CONTROLLER; reverting SWUUnitPlayedThisPhase() to
#// GlobalEffectCount($player, …) drops the opponent's unit from this offer and reds this section.

## GIVEN
CommonSetup: gyw/rrk/{myResources:5; theirResources:9}
WithActivePlayer: 1
WithP1Hand: [SOR_095 SEC_236]
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
