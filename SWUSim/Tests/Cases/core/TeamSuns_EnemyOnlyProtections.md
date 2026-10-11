# TeamSuns_Bounce_ATeammatesBounceIsNotAnEnemyAbility
#// ─── "… by ENEMY card abilities" in a TEAM game — the whole family ──────────────────────────────
#//
#// Every protection in this file is worded against ENEMY card abilities (or, for LAW_149's take-control
#// clause, against OPPONENTS). In Team Suns a teammate is neither: `OpponentsOf()` excludes them and
#// CR treats them as friendly. But these gates were written at two seats, where "not me" and "enemy"
#// are the same set, so they compare raw SEAT IDENTITY — and a TEAMMATE's ability is wrongly refused.
#//
#// The DEFEAT gate was converted to SWUIsEnemySeat first (2026-09-15, found via HMW_099 Always a Bigger
#// Fish + IBH_095); its pair lives in twi/ShadowedIntentions.md. The remaining verbs — capture, exhaust,
#// take control, ability damage, bounce — are converted here, each with its OWN pair, because each
#// reads a different helper and one working conversion is not evidence about the next.
#//
#// ⚠ EVERY SECTION NEEDS AN UNQUALIFIED EFFECT. A card that says "exhaust an ENEMY unit" can never
#// target a teammate at all, so it would pass whatever the gate does. TWI_226 Waylay ("return a
#// non-leader unit"), JTL_262 Evasive Maneuver ("exhaust a unit") and their kin are chosen precisely
#// because their pools span the whole table.
#//
#// ⚠ WithTeams seats 1+3 against 2+4. P3 is therefore P1's TEAMMATE and P2 is a true enemy; the two
#// halves of each pair differ ONLY in which seat holds the protected unit.
#//
#// Each protected unit is the only non-leader unit on the table, so the effect auto-resolves onto it
#// and the section asserts the OUTCOME rather than a pick.
#//
#// ── BOUNCE — TWI_220 Shadowed Intentions grants "can't be … returned to its owner's hand by enemy
#// card abilities". P1 Waylays teammate P3's protected unit: it IS returned. Returning it to hand
#// defeats the attached upgrade (CR 7.11), so P3's discard grows by one as well.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: TWI_226
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1

---

# TeamSuns_Bounce_AnEnemysBounceIsStillRefused
#// The other half: in the same team game an OPPOSING seat's bounce is still refused, so the conversion
#// cannot have over-widened into "nobody is an enemy". Identical board, P2 instead of P3.

## GIVEN
CommonSetup: rrk/bbw/{myResources:7}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: TWI_226
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# TeamSuns_Exhaust_ATeammatesExhaustIsNotAnEnemyAbility
#// ── EXHAUST — LOF_040 Kylo Ren's Lightsaber: "If attached unit is a Force unit, it gains: 'This unit
#// can't be exhausted by enemy card abilities.'" SOR_061 Guardian of the Whills is a Force ground unit,
#// so the grant is live. JTL_262 Evasive Maneuver ("Exhaust a unit") is unqualified and costs no
#// aspects, so it can reach a teammate's board. A teammate exhausting it must succeed.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_262
WithP3GroundArena: SOR_061:1:0
WithP3GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:EXHAUSTED

---

# TeamSuns_Exhaust_AnEnemysExhaustIsStillRefused
#// The enemy half: same grant, opposing seat, still refused — the unit stays READY.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: JTL_262
WithP2GroundArena: SOR_061:1:0
WithP2GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2GROUNDARENAUNIT:0:READY

---

# TeamSuns_TakeControl_ATeammatesTakeControlIsNotAnOpponents
#// ── TAKE CONTROL — LAW_149 Rey, Skywalker: "OPPONENTS can't take control of this unit." ⚠ Note the
#// wording differs from the rest of the family: "opponents", not "enemy card abilities". It lands in
#// the same place, because a Team Suns teammate is not an opponent either (OpponentsOf excludes them).
#//
#// ⚠ This gate compares against the unit's OWNER, not its controller, and that is deliberate — the
#// protection is owed to Rey's owner, so an opponent who already stole her cannot be shielded by it,
#// and her owner can always take her back. The conversion swaps only the comparison, never the operand.
#//
#// SOR_224 Change of Heart ("Take control of a non-leader unit") is unqualified, and Rey is a non-leader
#// Unit, so a teammate can legally target her. Taking control moves her into P1's ground arena.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_224
WithP3GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_149
P3GROUNDARENACOUNT:0

---

# TeamSuns_TakeControl_AnOpponentsTakeControlIsStillRefused
#// The enemy half: a true opponent's take-control is still refused and Rey stays put.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_224
WithP2GroundArena: LAW_149:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_149

---

# TeamSuns_AbilityDamage_ATeammatesDamageIsNotAnEnemyAbility
#// ── ABILITY DAMAGE — SHD_187 Lurking TIE Phantom (2/2 space): "can't be captured, damaged, or
#// defeated by enemy card abilities." SOR_172 Open Fire deals 4 to an unqualified "a unit", which is
#// more than enough to defeat a 2/2 once the damage is actually allowed through.
#//
#// ⚠ The flag this reads ($isEnemyAbility) also drives SEC_042 Cassian Andor's "prevent 2 of that
#// damage", so the conversion moves two cards at once — see the SEC_042 pair below.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_172
WithP3SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3SPACEARENACOUNT:0

---

# TeamSuns_AbilityDamage_AnEnemysDamageIsStillPrevented
#// The enemy half: the whole instance is still prevented, so the Phantom is not merely alive but
#// UNDAMAGED — a partial-prevention regression would leave damage on it and fail here.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_172
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0

---

# TeamSuns_AbilityDamage_SEC042_TeammateDealsFull_EnemyIsReducedByTwo
#// The SECOND consumer of the same flag, in one section because the two halves are the same board seen
#// from two seats. SEC_042 Cassian Andor, Lay Low (2/2): "If an ENEMY card ability would deal damage
#// to this unit, prevent 2 of that damage."
#//   • teammate P3's copy takes the full 4 and dies;
#//   • enemy P2's copy has 2 prevented, takes 2, and also dies — so the ARENA COUNT cannot tell them
#//     apart. The discriminator has to be damage dealt, so P2's copy is given enough HP to survive:
#//     it is the DAMAGE value that proves the reduction still applies to a true enemy.
#// Covered separately from SHD_187 because a conversion that fixed prevention but not reduction would
#// leave this red while every other damage section passed.

## GIVEN
CommonSetup: rrk/bbw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SOR_172
WithP2GroundArena: SEC_042:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
# 4 dealt, 2 prevented by an ENEMY ability -> 2 lands on a 2/2, which defeats it.
P2GROUNDARENACOUNT:0

---

# TeamSuns_Evacuate_ReturnsTheTeammatesProtectedUnitButNotTheEnemys
#// ── SHD_233 Evacuate — "Return EACH non-leader unit to its owner's hand." The mass-bounce path does
#// NOT go through SWUBounceUnit's guard: it pre-filters the table itself (it has to, because the
#// returns are simultaneous and bouncing a Mythosaur first would strip protection off units that
#// should have been kept). So it carries its own copy of the comparison and needs its own fix.
#//
#// Because the effect spans the whole table, ONE section holds both halves: the teammate's protected
#// unit goes home, the enemy's stays. A blind sweep that converted SWUBounceUnit but missed this file
#// reds here and nowhere else.

## GIVEN
CommonSetup: rrk/bbw/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SHD_233
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:TWI_220
WithP3GroundArena: SOR_095:1:0
WithP3GroundArenaUpgrade: 0:TWI_220

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
# Teammate's protected unit IS returned...
P3GROUNDARENACOUNT:0
P3HANDCOUNT:1
# ...the enemy's is not.
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0
