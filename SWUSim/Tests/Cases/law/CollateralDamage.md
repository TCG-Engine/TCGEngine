# TwoThenTwo
#// LAW_208 Collateral Damage (Aggression event, cost 3) — "Deal 2 damage to a unit. Then, deal 2 damage
#// to a base or another unit in the same arena." Deal 2 to SOR_046, then 2 to the other ground unit SOR_095.
#// COVERAGE: offer=proven by targeted picks in TwoThenTwo + SpaceArenaTwoUnits (an out-of-pool answer
#//           throws; the second pick is what exercises the same-arena restriction) · decline=N/A (both
#//           clauses are mandatory, no "you may") · control=N/A (pure damage event, no control-change
#//           interaction) · boundary=FirstTargetDefeated + LastUnitDefeatedThenBase + EmptyBoard_DealsToBase
#//           + FirstTargetCannotBeDamaged_SecondClauseStillResolves (defeat / zero-unit / damage-immune
#//           edges) · reqboundary=every two-clause section crosses a request boundary between the first
#//           and second target answer

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# UnitThenBase
#// LAW_208 second half may hit a base: deal 2 to the lone ground unit SOR_046, then 2 to the enemy base.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# SpaceArenaTwoUnits
#// LAW_208 in the space arena: deal 2 to a space unit, then the second half is restricted to the SAME
#// (space) arena — deal 2 to another space unit.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SHD_060:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:1:DAMAGE:2

---

# FirstTargetDefeated
#// LAW_208 first half defeats a 1-HP unit; the second half still resolves against another unit in that arena.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:DAMAGE:2

---

# LastUnitDefeatedThenBase
#// LAW_208 first half defeats the only unit; the second half then falls to a base target.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2
P1BASEDMG:0

---

# EmptyBoard_DealsToBase
#// LAW_208 Collateral Damage — with NO units in play the first "deal 2 to a unit" clause has no target,
#// but the second clause still resolves to a base ("a base or another unit in the same arena" reduces to a
#// base). P1 chooses P2's base → 2 damage to it.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1BASEDMG:0

---

# FirstTargetCannotBeDamaged_SecondClauseStillResolves
#// LAW_208 Collateral Damage — the first target may be a unit that CAN'T be damaged: SHD_187 Lurking
#// TIE Phantom ("can't be captured, damaged, or defeated by enemy card abilities") is still a legal
#// pick, the 2 damage is simply prevented (stays at 0), and the second clause still resolves in the
#// same (space) arena — 2 damage to the other space unit.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2SpaceArena: SHD_187:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:CARDID:SOR_237
P2SPACEARENAUNIT:1:DAMAGE:2
P1NODECISION

---

# FirstTargetOffer_AnyUnitEitherArenaNoBase
#// LAW_208 Collateral Damage — OFFER assertion for the FIRST clause, "Deal 2 damage to a unit." The word
#// is bare "a unit": no controller scope, no arena scope, and NOT a base. Discriminating board: a friendly
#// ground unit, a friendly space unit, two enemy ground units and an enemy space unit are all IN, while
#// neither base is offered. (Prior offer evidence was only in-pool picks; this pins the whole pool.)
#// COVERAGE-UPDATE (offer axis): the TwoThenTwo ledger's "offer=proven by targeted picks" is now upgraded
#// to real pending-pool assertions — this section for clause 1 and
#// SecondTargetOffer_SameArenaAnotherUnitOrEitherBase for clause 2.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# SecondTargetOffer_SameArenaAnotherUnitOrEitherBase
#// LAW_208 Collateral Damage — OFFER assertion for the SECOND clause, "deal 2 damage to a base or ANOTHER
#// unit in the SAME arena." Same five-unit two-arena board; the first 2 damage is aimed at the enemy
#// SOR_046 (theirGroundArena-0). The resulting pool discriminates three restrictions at once: the first
#// target itself is OUT ("another"), BOTH space units are OUT (same-arena), and both bases are IN ("a
#// base" names no controller) alongside the remaining ground unit on each side.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0]
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myBase-0&myGroundArena-0&theirBase-0&theirGroundArena-1
