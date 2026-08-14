# Deals2BaseAnd2Unit
#// SOR_134 Ruthless Raider (Space, cost 6) — When Played: deal 2 to an enemy base AND
#// 2 to an enemy unit. P2's base takes 2; P2's only unit (Consular Security Force) is
#// auto-chosen and takes 2.
#// COVERAGE: offer=WhenPlayed_Offer_EnemyUnitsOnly (pending SELECTABLEEXACT: enemy units only,
#//           both arenas) · decline=N/A (mandatory damage, no "you may") ·
#//           control=NGOR_WhenDefeated_ResolvesForNewController_HitsOriginalOwner ("enemy" is
#//           read from the defeat-time controller's seat) · boundary=WhenDefeated_Deals2BaseAnd2Unit
#//           (the trigger's other half, fired from a removal event) · reqboundary=covered by the
#//           pending-offer section (the pick survives to the end-state read)

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# WhenPlayed_Offer_EnemyUnitsOnly
#// SOR_134 Ruthless Raider — Intended: the 2-damage unit pick is ENEMY units only, either arena;
#// friendly units are never candidates. P1 plays the Raider with a friendly trooper on the
#// ground and two P2 units in play; the pick is left PENDING — exactly the two enemy units.
#// (The 2 to the enemy base is dealt unconditionally alongside.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
P2BASEDMG:2

---

# WhenDefeated_Deals2BaseAnd2Unit
#// SOR_134 Ruthless Raider — Intended: the WHEN DEFEATED half mirrors the When Played. P2 kills
#// the Raider with Rival's Fall (SHD_079, defeat a unit); the Raider's controller (P1) then
#// deals 2 to the enemy base and 2 to the sole enemy unit. The cross-player reaction queues on
#// P1's seat → P1>Drain surfaces it.

## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 6
WithP1SpaceArena: SOR_134:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NGOR_WhenDefeated_ResolvesForNewController_HitsOriginalOwner
#// SOR_134 Ruthless Raider — Intended: No Glory, Only Results (JTL_043: take control, then
#// defeat) makes P2 the Raider's controller at the defeat, so the When Defeated resolves for
#// P2 — "an enemy base and an enemy unit" are now P1's. P1's base takes 2 and P1's sole unit
#// takes 2; the Raider still goes to its OWNER's (P1) discard.

## GIVEN
CommonSetup: rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP2Resources: 5
WithP1SpaceArena: SOR_134:1:0
WithP1GroundArena: SOR_046:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1BASEDMG:2
P1GROUNDARENAUNIT:0:DAMAGE:2
