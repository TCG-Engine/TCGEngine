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
