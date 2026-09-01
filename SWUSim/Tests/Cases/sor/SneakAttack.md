# DefeatedAtRegroup
#// SOR_219 Sneak Attack — "At the start of the regroup phase, defeat it." P1 plays Sneak Attack to
#// put SOR_095 into play (discounted, ready), then passes; with P1OnlyActions the opponent has already
#// auto-passed, so the single P1 pass ends the action phase and RegroupPhaseStart defeats the
#// Sneak-Attacked unit. The Marine leaves the arena (COUNT 0) and joins the event in P1's discard
#// (the event SOR_219 + the defeated SOR_095 = 2).
#// COVERAGE: offer=N/A (the hand-unit choose is a single-candidate mandatory pick in every section,
#//           so it auto-resolves and no pending offer exists; NoPlayableUnit_Fizzles proves the
#//           empty-pool branch) · reqboundary=WhenPlayedAndWhenDefeated_BothFire (nested play →
#//           trigger → phase cross, all across serialized actions) · control=
#//           Control_TakenOverBeforeRegroup_StillDefeated_IntoItsOWNERSDiscard (supersedes the earlier
#//           N/A: the regroup-defeat tag rides the UNIT, so a Change of Heart steal before the regroup
#//           does not save it, and the defeated unit lands in its OWNER's discard, not the thief's) ·
#//           boundary pair=DefeatedAtRegroup vs WaylayedAndReplayed_NotDefeated (tag consumed by
#//           leaving play) + AlreadyDefeated_NoRegroupDoubleDefeat · decline=N/A (the play is
#//           mandatory once the event resolves with a legal target; with none it fizzles —
#//           NoPlayableUnit_Fizzles)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# EntersReadyDiscounted
#// SOR_219 Sneak Attack (Cunning event, cost 2) — "Play a unit from your hand. It costs 3 less and
#// enters play ready." P1's leader is Han (Cunning+Heroism) so the event plays at its printed 2.
#// The hand's only unit is SOR_095 Battlefield Marine (Command,Heroism, printed 2 → +2 off-aspect
#// Command penalty = effective 4); the −3 discount drops it to 1. P1 has exactly 3 ready: 2 pays the
#// event, leaving 1 — exactly the discounted unit cost. The Marine enters READY (not exhausted) and
#// P1 ends with 0 ready resources. (Without the discount the Marine would cost 4 and could not be
#// paid from the leftover 1, so COUNT:1 + RESAVAILABLE:0 pins the discount at 3.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# NoPlayableUnit_Fizzles
#// SOR_219 Sneak Attack — with no unit in hand (only the event SOR_216) there is nothing to play:
#// the event resolves to no effect, goes to discard, and only its own cost is spent (3 → 1 ready).
#// No choice is raised.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_216}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1NODECISION

---

# AlreadyDefeated_NoRegroupDoubleDefeat
#// SOR_219 Sneak Attack — the Sneak-Attacked Marine dies IN COMBAT before the phase ends (the enemy
#// AT-ST runs it over). The start-of-regroup defeat then finds nothing to do and the game crosses
#// cleanly into the next action phase: the Marine is in discard once (with the event = 2), the
#// arena stays empty, and the AT-ST keeps its 3 combat damage.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,SOR_095;theirResources:2}
WithP2GroundArena: SOR_232:1:0
WithP1Deck: [SOR_171 SOR_171]
WithP2Deck: [SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# UniquenessDefeat_NoRegroupDoubleDefeat
#// SOR_219 Sneak Attack — playing a SECOND copy of the unique TWI_195 Sabine via Sneak Attack raises
#// the uniqueness choose-a-copy-to-defeat; defeating the JUST-PLAYED copy (myGroundArena-1) leaves
#// the original in play, and the start-of-regroup defeat must NOT then hit the surviving original.
#// After the phase cross: one Sabine still seated, the other in discard with the event (= 2).

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_219,TWI_195}
P1OnlyActions: true
WithP1GroundArena: TWI_195:0:0
WithP1Deck: [SOR_171 SOR_171]
WithP2Deck: [SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_195
P1DISCARDCOUNT:2

---

# WhenPlayedAndWhenDefeated_BothFire
#// SOR_219 Sneak Attack — the fetched unit's triggers fire at their normal timing points: Ruthless
#// Raider (SOR_134, "When Played/When Defeated: 2 to an enemy base and 2 to an enemy unit") pings the
#// enemy base and the lone Wampa for 2 each on the nested play (both single-candidate picks
#// auto-resolve), then the start-of-regroup defeat fires the same ability again: base 4, Wampa 4
#// (4/5 → survives at HP 1), Raider in discard with the event. Costs: event 2 + Raider (6−3) = 5.

## GIVEN
CommonSetup: ryk/rrk/{myResources:5;handCardIds:SOR_219,SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_171 SOR_171]
WithP2Deck: [SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:4
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2

---

# WaylayedAndReplayed_NotDefeated
#// SOR_219 Sneak Attack — the regroup defeat is bound to the unit's current STAY in play: when the
#// opponent Waylays the Sneak-Attacked Marine back to hand and P1 replays it normally the same
#// phase, the replayed Marine is a fresh object and survives the regroup. Next action phase: the
#// Marine is still seated; P1's discard holds only the event, P2's only the Waylay.

## GIVEN
CommonSetup: yyw/yyk/{myResources:7;handCardIds:SOR_219,SOR_095;theirResources:3;theirhandCardIds:SOR_222}
WithP1Deck: [SOR_171 SOR_171]
WithP2Deck: [SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# Control_TakenOverBeforeRegroup_StillDefeated_IntoItsOWNERSDiscard
#// Intended: "At the start of the regroup phase, defeat IT" is bound to the OBJECT, not to the seat
#// that played the event — a per-unit marker must survive a control change (CR: taking control does
#// not remove ongoing effects already applied to the unit). P1 Sneak-Attacks the Battlefield Marine
#// into play, then P2 plays SOR_224 Change of Heart ("Take control of a non-leader unit") and steals
#// it — the sole non-leader unit in play, so the pick auto-resolves. The regroup then starts.
#// Both regroup-start clauses touch the same unit (Change of Heart also returns control to its owner
#// at the start of the regroup phase), so the assertion is written to be order-independent: whichever
#// arena the Marine is defeated in, it must be DEFEATED, both arenas must be empty, and it must land
#// in its OWNER's discard — P1's, alongside the Sneak Attack event (2), while P2's discard holds only
#// Change of Heart (1). A marker that was dropped on the steal leaves the Marine alive in an arena.
#// Costs: P1 pays 2 for the event, then 1 for the Marine (printed 2, +2 for the uncovered Command
#// aspect under a Cunning/Heroism leader, −3 for Sneak Attack) out of 3. P2 pays 6 for Change of
#// Heart. Both decks are seeded so crossing the regroup does not trigger the empty-deck base damage.

## GIVEN
CommonSetup: yyw/yyk/{myResources:3;handCardIds:SOR_219,SOR_095;theirResources:6;theirhandCardIds:SOR_224}
WithP1Deck: [SOR_171 SOR_171]
WithP2Deck: [SOR_171 SOR_171]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
