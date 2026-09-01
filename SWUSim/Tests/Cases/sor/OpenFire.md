# Deals4ToUnit
#// SOR_172 Open Fire (Event, cost 3) — "Deal 4 damage to a unit." P1 plays it and
#// targets the enemy Consular Security Force (SOR_046, 3/7), which takes 4 and
#// survives at 4 damage.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:1

---

# TokenUnitIsALegalTarget
#// BUG REPORT (2026-08-13): Open Fire could not hit TOKEN units — "deal 4 damage to a UNIT" is
#// unqualified, and a hand-built ["Unit","Leader Unit"] pool silently dropped the "Token Unit" type
#// (six files shared the miss; all now use AnyUnitFilter). The offer must hold the real unit AND the
#// TIE Fighter token, and picking the token defeats it (1 HP vs 4).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# TokenUnitTakesTheFourAndDies
#// The branch partner: actually shooting the token. JTL_T01 (1/1) is defeated — tokens are set aside,
#// never discarded, so P2's discard stays empty and only the event is in P1's.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_T01:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:1

---

# ShieldAbsorbsAllFour_UnitTakesNothing
#// SOR_172 Open Fire — a Shield token prevents ALL the damage from one source and is then defeated, so
#// a 4-damage event aimed at a shielded unit removes the shield and leaves the unit untouched. P1 plays
#// Seventh Fleet Defender (SOR_180, 3/2, Shielded → shields itself on play), then plays Open Fire at its
#// OWN Defender. Without the shield the 4 would kill a 2-HP unit; with it, the Defender survives on 0
#// damage with 0 upgrades. This also exercises the unqualified target word: "deal 4 damage to A UNIT"
#// names no controller, so a FRIENDLY unit is a legal target.
#// COVERAGE: offer=Offer_SpansBothSides_SentinelNoProtection_NoBases (pending exact pool: friendly and
#//           enemy units in BOTH arenas, a Sentinel-protected unit and the Sentinel itself, no bases) +
#//           TokenUnitIsALegalTarget (token units in the pool) · decline=N/A (a played event's damage is
#//           mandatory once the target is chosen; the play itself is the only choice) ·
#//           boundary=TokenUnitTakesTheFourAndDies (HP 1 < 4 → defeated) vs Deals4ToUnit (HP 7 > 4 →
#//           survives at 4) vs ShieldAbsorbsAllFour_UnitTakesNothing (shield → 0) ·
#//           control=N/A (one-shot damage with no persistent state and no zone word to mis-resolve) ·
#//           reqboundary=N/A (the play and its target pick are a single request; no state is carried)

## GIVEN
CommonSetup: rrk/rrk/{myResources:12}
P1OnlyActions: true
WithP1Hand: [SOR_180 SOR_172]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_180
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:SHIELDCOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Offer_SpansBothSides_SentinelNoProtection_NoBases
#// SOR_172 Open Fire — "Deal 4 damage to A UNIT" is unqualified in three ways at once, and the pending
#// offer must show all three: (1) it names no CONTROLLER, so P1's own Battlefield Marine is selectable
#// alongside P2's units; (2) it names no ARENA, so the space TIE/ln is in the pool with the ground
#// units; (3) it is not an ATTACK, so Sentinel (SOR_063 Cloud City Wing Guard) protects nothing — both
#// the Sentinel and the non-Sentinel Consular Security Force behind it are legal targets. Bases are NOT
#// units and are absent from the pool, and neither leader is deployed so no leader unit appears. The
#// pick is left PENDING so the exact pool can be read.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_063:1:0 SOR_046:1:0]
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0
