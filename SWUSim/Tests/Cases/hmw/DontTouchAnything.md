# DealsThreeToTheOnlyEnemyUnit
#// COVERAGE: offer=N/A — STRUCTURAL: the target is chosen at RANDOM by the engine, so no decision is
#//           ever raised and there is no pool to inspect with SELECTABLEEXACT. The pool is therefore
#//           asserted BEHAVIOURALLY instead, by what it must and must not reach:
#//           FriendlyUnitsAreNOTInThePool · BasesAreNOTInThePool · SpaceArenaEnemyIsALegalTarget ·
#//           EnemyDeployedLeaderUnitIsALegalTarget · TwinSuns_AFarSeatsUnitIsInThePool ·
#//           TeamSuns_ATeammateIsNOTAnEnemy.
#//           decline=N/A — STRUCTURAL: no "you may", no "up to", and no decision of any kind; the
#//           effect is mandatory and fully automatic. The no-target case is NoEnemyUnits_CleanFizzle.
#//           boundary=LethalAtThreeRemainingHP / NotLethalAtFourHP (3 is the threshold, as a PAIR)
#//           control=N/A — STRUCTURAL: an Event, so the caster is fixed, and the only seat-relative
#//           word is "enemy", which is resolved from the CASTER at resolution time. There is no
#//           owner-scoped zone (no hand/deck/discard/base reference) for owner-vs-controller to split.
#//           reqboundary=SurvivesTheRequestBoundary
#//           modes=2P,TwinSuns,TeamSuns — "ENEMY" is friendly/enemy wording, so the pool must fan out
#//           across EVERY opponent (Twin Suns) and must exclude a TEAMMATE (Team Suns). Both get a
#//           section that cannot pass at two seats.
#//
#// HMW_217 Don't Touch Anything — Event, cost 2, [Cunning][Heroism], Trick.
#//   "Deal 3 damage to a random enemy unit."
#// ⚠ PREVIEW SET — no official rulings exist for HMW. Read from the CR plus the closest released
#// analogue, TWI_202 Jar Jar Binks ("Deal 2 damage to a random unit or base"), which is the engine's
#// only other random-TARGET card. Two deliberate differences from it, and both are load-bearing:
#//   (a) TWI_202 says "a random unit or base" — BASES ARE IN ITS POOL. This card says "unit", so they
#//       are not. Copying Jar Jar's pool wholesale is the obvious way to get this wrong.
#//   (b) TWI_202 says "a random unit" (unqualified, both sides). This card says "a random ENEMY unit",
#//       so the caster's own board is excluded.
#// It says neither "non-leader" nor an arena, so a DEPLOYED ENEMY LEADER UNIT and an enemy SPACE unit
#// are both legal targets.
#//
#// THE BASE CASE, and it pins the AMOUNT at 3. SOR_046 Consular Security Force is 3/7, so it survives
#// and the damage is readable as a number rather than as a death — a 2 copied from Jar Jar reads
#// DAMAGE:2 here. One enemy unit means the random pick is degenerate and the section is deterministic.
#// ⚠ `:HP:` is CURRENT MAX HP and does NOT subtract damage, so survival is asserted with ARENACOUNT +
#// DAMAGE, never with HP alone.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# FriendlyUnitsAreNOTInThePool
#// SCOPE, half one — the word "ENEMY". P1 controls a unit of its own on an otherwise identical board.
#// An unqualified pool (the TWI_202 shape, "a random unit") would sometimes hit P1's own Marine, so
#// this section would be FLAKY rather than reliably red — which is itself the tell. It is made
#// deterministic in the correct implementation because the enemy pool has exactly one member.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# BasesAreNOTInThePool
#// SCOPE, half two — the word "UNIT", and the single sharpest difference from TWI_202, whose pool
#// deliberately contains both bases. Neither base may take any damage; a pool built by copying Jar
#// Jar's would put the enemy base in it and this section would fail roughly half the time.
#// The enemy unit is the 3/7, so its own 3 damage is visible and cannot be confused with a base hit.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SpaceArenaEnemyIsALegalTarget
#// ARENA SCOPE. The card names no arena, so the pool spans both. Every other behavioural section seats
#// its enemy on the GROUND, so a pool that scanned only the ground arena would pass all of them and
#// fail only here. JTL_069 Munificent Frigate is 4/7, so the 3 is readable rather than lethal.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2SpaceArena: JTL_069:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:3

---

# EnemyDeployedLeaderUnitIsALegalTarget
#// VALUE-CLASS VARIANT. The text says "a unit", NOT "a non-leader unit", so a deployed enemy leader
#// unit is in the pool. A pool built with NonLeaderUnitFilter passes every other section in this file
#// and empties completely here — which would present as the card fizzling, not as a filter bug.
#// P2's leader under `rrk` is SOR_010 Darth Vader, a 5/8 deployed, so he survives and the damage reads
#// as a number. He is the ONLY enemy unit, so the pick is degenerate.
## GIVEN
CommonSetup: yyw/rrk/{theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_010
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ExactlyONEEnemyIsHit_TwoIdenticalBodiesOneDies
#// THE RANDOMNESS ITSELF, made deterministic. Two IDENTICAL 3/3 enemies: whichever one the engine
#// picks takes exactly lethal damage and dies, so the arena goes 2 -> 1 no matter which was chosen.
#// The outcome is therefore fixed while the choice is genuinely random — the only shape that can
#// assert "a random ONE" without asserting which.
#// This is the section that discriminates the two plausible misreadings: dealing to EVERY enemy unit
#// leaves COUNT 0, and dealing to none leaves COUNT 2.
#// ⚠ Two copies of one CardID need SEPARATE `WithP2GroundArena:` lines — the bracket form silently
#// seats only one.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1

---

# LethalAtThreeRemainingHP
#// BOUNDARY, half one. 3 damage against a 3-HP body is exactly lethal, and the state-based defeat must
#// actually remove it.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# NotLethalAtFourHP
#// BOUNDARY, half two — one point of HP the other way. SOR_063 Cloud City Wing Guard is 2/4, so the
#// same 3 damage leaves it alive on 1. Neither half pins the amount alone: at 2 damage the 3-HP body
#// above would survive, and at 4 this one would die, so only the PAIR fixes the number at 3.
#// (Its printed Sentinel is irrelevant here — Sentinel redirects ATTACKS, not ability damage.)
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ShieldAbsorbsTheWholeHit
#// INTERACTION WITH THE STANDARD MODIFIERS. A Shield token prevents ALL damage from one source, so a
#// 3-damage hit is fully absorbed and costs the token — not 3 damage minus something. This is the cell
#// that proves the damage goes through the ordinary damage funnel (SWUDealDamageToUnit) rather than
#// writing `Damage` directly, which would sail straight past the shield.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# NoEnemyUnits_CleanFizzle
#// NO VALID TARGET. The event still costs its 2 resources and still reaches the discard — the cost
#// buys the ability, not the effect resolving — but nothing happens, no base is hit as a consolation,
#// and no decision is left dangling for either player.
#// P1 controls a unit of its own, so this is also a second, independent proof that the friendly board
#// is not a fallback pool: an implementation that widened to "a random unit" when no enemy exists
#// would damage P1's own Marine here.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0

---

# SurvivesTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. This card raises no interactive decision, so the boundary goes between
#// the two player ACTIONS instead — an attack, then the event. In production every action ends the
#// request and the next one runs in a fresh process, so anything the random pick depends on that is
#// held only in memory would be gone by the time the event resolves.
#// That is not hypothetical for this card: the engine's random draw is seeded from serialized
#// gamestate material (`$gRandomCounter` and the zone payload), so the pick is exactly the kind of
#// thing a boundary can break — and its failure mode would be a silent no-op, not an error.
#// Same board as the base case plus an attacker, so the expected damage is unchanged.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>PlayHand:0
## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# TwinSuns_AFarSeatsUnitIsInThePool
#// TWIN SUNS — CANNOT PASS AT TWO SEATS. "Enemy" at four seats means EVERY opponent, so seat 3's unit
#// must be in seat 1's pool. Seats 2 and 4 are deliberately empty, which makes seat 3's unit the only
#// enemy unit on the table and the pick degenerate.
#// The failure this pins is the one TWI_202 Jar Jar actually shipped and had to be fixed for: a pool
#// hand-built from `theirGroundArena-N` names SEAT 2 and nothing else, so above two seats a far seat's
#// units are both unreachable AND absent from the odds. Here that reads as the event fizzling entirely.
#// ⚠ CommonSetup dresses seats 1-2 only, so seat 3's unit needs its own WithP3GroundArena line.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP3GroundArena: [SOR_046:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:3
P1BASEDMG:0
P2BASEDMG:0

---

# TeamSuns_ATeammateIsNOTAnEnemy_NoOtherEnemyMeansFIZZLE
#// TEAM SUNS, and this is the section that actually PINS the teammate exclusion — the positive one
#// below cannot.
#// ⚠ It exists because of a GREEN MUTATION. Replacing the pool with "every live seat that is not me"
#// — which is what the plain Twin Suns fan-out gives you, and the obvious way to write this card —
#// left the positive section green: with the teammate wrongly in a two-member pool the pick is a coin
#// flip, and a coin flip is not a test. The fix is to make BOTH readings deterministic instead of
#// making the wrong one merely likely.
#// Teams are seat parity, so seat 1's teammate is seat 3 and its enemies are seats 2 and 4. Here seats
#// 2 and 4 are EMPTY and the teammate is the only unit on the table, so:
#//   correct pool ("their", teammate excluded) -> EMPTY -> clean fizzle, nobody takes damage;
#//   wrong pool ("not me")                     -> exactly one member -> the teammate takes 3.
#// Neither outcome depends on the random draw, in either direction.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP3GroundArena: [SOR_046:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# TeamSuns_AnEnemyOnTheOtherTeamIsStillHit
#// TEAM SUNS, the POSITIVE half — an actual enemy is still reachable in a team game. This one is not
#// the discriminator (see the fizzle section above, which is); it is here so that the exclusion above
#// cannot be satisfied by a pool that has simply gone empty in a team game. Seat 2 is the only enemy
#// unit, so the correct pick is degenerate and the outcome is deterministic.
#// Seat 3 (the teammate) holds a body purely so a teammate-including pool would have a second
#// candidate to draw from.
## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 4
WithP1Hand: [HMW_217]
WithP2GroundArena: [SOR_046:1:0]
WithP3GroundArena: [SOR_095:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:DAMAGE:3
P3GROUNDARENAUNIT:0:CARDID:SOR_095
P3GROUNDARENAUNIT:0:DAMAGE:0
