# ReliefFrigate_HealsEVERYOtherBase_FourSeats
#// TS26_42 Relief Frigate — "When Played: Choose a base. Heal 3 damage from each OTHER base."
#//
#// THE BUG THIS PINS (found sweeping the LAW_058 Honor-Bound Partisan report, 2026-08-21): the handler
#// looped the literal ['myBase-0','theirBase-0'] — exactly TWO bases — so in Twin Suns seats 3 and 4
#// were never healed at all, and the healed player was derived with OtherPlayer() rather than from the
#// chosen mzID. Both halves are fixed: the pool comes from SWUAllBaseMzIDs (which routes through
#// ZoneSearch's per-seat union) and the owner from SWUMzOwner.
#//
#// P1 chooses its OWN base, so P1 keeps all 5 damage and every OTHER seat heals 3 (5 -> 2). Choosing
#// your own base is the discriminating pick: it proves "each other" is enforced by IDENTITY rather than
#// by assuming the chosen base is the enemy's.
#// ⚠ A 2-seat version of this test cannot fail — with only two bases, "each other base" is one base,
#// which the old literal already handled. The seat count IS the test.
#// ⚠ FIXTURE: TS26_42 costs 5, so myResources MUST be seeded or PlayHand silently no-ops; and seats 1-2
#//   take their base damage from the CommonSetup myBaseDamage/theirBaseDamage opts — WithP1Base/
#//   WithP2Base accept only a cardID and DROP a ":dmg" suffix (only WithP3Base/WithP4Base carry it).
#// COVERAGE: offer=N/A (the choose is over all 4 bases; the pool is asserted by ChoosingSeatThree below
#//           landing on a seat the old code could not reach) · negative=P1 keeps its damage (the chosen
#//           base is excluded) · boundary=N/A (flat 3, no threshold) · control=N/A (bases have one owner)
#//           · reqboundary=N/A (one answer, resolved inline) · decline=N/A (mandatory choose)

## GIVEN
CommonSetup: bbw/ggk/{myResources:6; myBaseDamage:5; theirBaseDamage:5}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: TS26_42
WithP3Base: SOR_019:5
WithP4Base: SOR_019:5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:5
P2BASEDMG:2
P3BASEDMG:2
P4BASEDMG:2

---

# ReliefFrigate_ChoosingSeatThree_SparesOnlyThatBase
#// TS26_42 — the mirror, and the half that proves a SEAT-3 target is reachable at all. P1 chooses P3's
#// base (mzID p3Base-0, which the old two-entry pool could never offer): P3 keeps its 5 and everyone
#// else — including P1 itself — heals to 2.

## GIVEN
CommonSetup: bbw/ggk/{myResources:6; myBaseDamage:5; theirBaseDamage:5}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: TS26_42
WithP3Base: SOR_019:5
WithP4Base: SOR_019:5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3Base-0

## EXPECT
P3BASEDMG:5
P1BASEDMG:2
P2BASEDMG:2
P4BASEDMG:2
