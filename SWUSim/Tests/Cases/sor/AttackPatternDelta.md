# BuffsThreeUnits
#// SOR_106 Attack Pattern Delta — Event, cost 3, double Command (Command/Command).
#// "Give a friendly unit +3/+3. Give another friendly unit +2/+2. Give a third friendly unit +1/+1."
#// Three distinct friendly units (3x SOR_088, 9/9). Player assigns the buffs:
#//   idx0 → +3/+3 = 12/12, idx1 → +2/+2 = 11/11, idx2 (last remaining) → +1/+1 = 10/10.
#// Note: ggw/ggw gives Command from BOTH base and leader so the double-Command cost is unpenalized.
#// COVERAGE: offer=FirstPickOffer_BothArenas_EnemyExcluded + SecondPickOffer_ExcludesFirstPick (both
#//           pending SELECTABLEEXACT) · reqboundary=BuffsThreeUnits (each pick is its own request) ·
#//           boundary pair=OneFriendlyEnemyUnaffected (1 target: +3/+3 only) +
#//           TwoTargets_PlusThreeAndPlusTwo_NoThird (2 targets: no third buff) + BuffsThreeUnits
#//           (full 3) · decline=N/A (no "you may" — every clause is mandatory while targets exist;
#//           short boards fizzle the remainder instead) · control=N/A (one-shot event; phase buffs
#//           carry no per-unit marker beyond the phase — expiry pinned by BuffsExpireAtEndOfPhase)

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:12
P1GROUNDARENAUNIT:1:POWER:11
P1GROUNDARENAUNIT:1:HP:11
P1GROUNDARENAUNIT:2:POWER:10
P1GROUNDARENAUNIT:2:HP:10

---

# OneFriendlyEnemyUnaffected
#// SOR_106 Attack Pattern Delta — friendly-only, and graceful fizzle when not enough friendly units.
#// One friendly unit (SOR_088, 9/9) + one ENEMY unit (SOR_088, 9/9).
#// Only the friendly unit is a valid target → auto-takes +3/+3 = 12/12.
#// The "another"/"a third" buffs have no remaining friendly target → fizzle (no crash, no choice).
#// The enemy unit is never eligible → stays 9/9.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
WithP1GroundArena: SOR_088:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:12
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:9
P2GROUNDARENAUNIT:0:HP:9

---

# FirstPickOffer_BothArenas_EnemyExcluded
#// Intended: "a friendly unit" — the first pick's pool spans BOTH arenas and only P1's units:
#// two ground units plus the space A-Wing; the enemy Wampa is out. The decision is left pending
#// so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0

---

# SecondPickOffer_ExcludesFirstPick
#// Intended: "give ANOTHER friendly unit +2/+2" — after the Marine takes the +3/+3, the second
#// pick's pool is everyone else: the Security Force and the A-Wing, but NOT the Marine again.
#// The second decision is left pending; the Marine already reads 6/6.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# TwoTargets_PlusThreeAndPlusTwo_NoThird
#// Intended: with exactly TWO friendly units the +3/+3 goes to the picked Wampa (4/5 → 7/8), the
#// +2/+2 auto-resolves onto the only remaining unit (Marine 3/3 → 5/5), and the "third" clause
#// fizzles cleanly — no pending decision.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:5
P1DISCARDCOUNT:1
P1NODECISION

---

# BuffsExpireAtEndOfPhase
#// Intended: the buffs last "for this phase" — after the action phase ends and the game crosses
#// regroup, the Wampa is back to its printed 4/5. The lone friendly unit auto-takes the +3/+3
#// (7/8 during the phase); both decks are seeded so the regroup draws don't ping the bases.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5
P1BASEDMG:0
P2BASEDMG:0

---

# SimulateRequestBoundary_ThreePickChain
#// SOR_106 Attack Pattern Delta — each of the three buff picks is its own request in production, so the
#// answer to pick #2 arrives in a FRESH process. The "which units already took a buff" exclusion and the
#// remaining-clause amount (+2/+2 then +1/+1) must therefore live in the serialized gamestate, not a
#// transient global. Mirrors BuffsThreeUnits with a boundary before each answer.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3;handCardIds:SOR_106}
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0
WithP1GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:POWER:12
P1GROUNDARENAUNIT:0:HP:12
P1GROUNDARENAUNIT:1:POWER:11
P1GROUNDARENAUNIT:1:HP:11
P1GROUNDARENAUNIT:2:POWER:10
P1GROUNDARENAUNIT:2:HP:10
