# Modal_PowerStrikeAndExperience
#// COVERAGE: offer=PowerStrike_FriendlyOfferIsAllFriendlyUnits (dealer pool, both arenas) +
#//           PowerStrike_EnemyPoolExcludesUniques (non-unique filter on the enemy pool)
#//           decline=N/A ("Choose two" is mandatory; the nothing-to-do branches are the two
#//           PowerStrike_*Fizzles* sections — empty pools silently no-op and the modal still owes its
#//           second pick) · control=Control_StolenUnitIsTheFriendlyDealer_MyOwnedUnitIsTheEnemyTarget
#//           (friendly/enemy follow CONTROL, not ownership — a P2-owned unit in P1's arena is the
#//           legal dealer, a P1-owned unit in P2's arena is the legal damage target)
#//           boundary=N/A (no duration effect) · reqboundary=all sections (each mode pick and target
#//           answer is its own request; Resource moves the event out of the discard after it already
#//           sat there across a request in Modal_ResourceAndReturn)
#//
#// SOR_107 Command (event, cost 4) — "Choose two." PowerStrike (a friendly unit deals its power to a
#// non-unique enemy unit): SEC_080 (3 power) deals 3 to LAW_124 (non-unique). Then Experience: give 2
#// Experience tokens to SEC_080 (UPGRADECOUNT 2).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1

---

# Modal_ResourceAndReturn
#// SOR_107 Command — Resource (put this event into play as a resource) + Return (return a unit from your
#// discard to hand). After playing SOR_107 the discard holds [SEC_080, SOR_107]; Resource moves SOR_107
#// to the resource row (count 6→7), Return moves SEC_080 to hand. Discard ends empty.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:SOR_107;discardCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Resource
- P1>AnswerDecision:Return

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESCOUNT:7

---

# PowerStrike_FriendlyOfferIsAllFriendlyUnits
#// SOR_107 Command — PowerStrike's first choice is the friendly dealer, offered across BOTH arenas.
#// With two friendly units (ground SOR_095 + space SOR_237) the choice is left pending to assert the
#// exact pool. The enemy unit is not in this first pool.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# PowerStrike_EnemyPoolExcludesUniques
#// SOR_107 Command — the damage target must be a NON-UNIQUE enemy unit. P1's lone SOR_095 (3 power)
#// auto-resolves as the dealer; the enemy pool is left pending: the two non-unique ground units
#// (SOR_128, SEC_080) are offered, the unique SOR_196 Chewbacca is NOT.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_196:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# PowerStrike_NoNonUniqueEnemy_FizzlesThenResource
#// SOR_107 Command — if every enemy unit is unique the damage mode has no legal target: the dealer
#// (lone SOR_095, auto) is chosen but the enemy pick silently fizzles and no damage is dealt. The
#// modal then still owes a second pick — P1 takes Resource, and Command moves from the discard into
#// the resource row (entering exhausted: RESCOUNT 5, none ready after paying 4).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_196:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Resource

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESCOUNT:5
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
P1NODECISION

---

# PowerStrike_NoFriendlyUnit_FizzlesThenExperience
#// SOR_107 Command — with NO friendly unit the damage mode fizzles at the dealer step (empty pool →
#// silent no-op; the non-unique enemy is untouched). The second pick still resolves: Experience goes
#// to the only unit in play (P2's SEC_080, mandatory single-target auto-resolve → 2 Experience
#// tokens, no prompt).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Experience

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1
P1NODECISION

---

# Control_StolenUnitIsTheFriendlyDealer_MyOwnedUnitIsTheEnemyTarget
#// Intended: "A FRIENDLY unit deals damage equal to its power to a non-unique ENEMY unit" —
#// friendly/enemy are decided by CONTROL, never by ownership (CR: a unit is friendly to the player
#// who controls it). Both units here have owner and controller split, in opposite directions:
#//   • P1's arena holds SOR_128 Death Star Stormtrooper (3/1, non-unique) OWNED BY P2 — friendly to
#//     P1, so it is the only legal DEALER and is NOT a legal damage target.
#//   • P2's arena holds SOR_046 Consular Security Force (3/7, non-unique) OWNED BY P1 — enemy to P1,
#//     so it is the only legal TARGET and is NOT a legal dealer.
#// Each pool therefore holds exactly one candidate and both picks auto-resolve; the end state IS the
#// assertion. An ownership-framed read inverts the two roles entirely: SOR_046 would deal 3 to the
#// 1-HP Stormtrooper and kill it, leaving P2's arena empty and P1's holding a defeated-unit hole —
#// a completely different board from the 3 damage on a surviving 7-HP defender asserted below.
#// Second mode is Resource (no further targets): Command moves from the discard into P1's resource
#// row exhausted → RESCOUNT 5 with none ready after paying 4, and the discard ends empty.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 4
WithP1GroundArenaControlled: SOR_128:2    # P1 controls, P2 owns — the friendly dealer
WithP2GroundArenaControlled: SOR_046:1    # P2 controls, P1 owns — the enemy target

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Resource

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESCOUNT:5
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
P1NODECISION
