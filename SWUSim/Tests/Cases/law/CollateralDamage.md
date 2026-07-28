# TwoThenTwo
#// LAW_208 Collateral Damage (Aggression event, cost 3) — "Deal 2 damage to a unit. Then, deal 2 damage
#// to a base or another unit in the same arena." Deal 2 to SOR_046, then 2 to the other ground unit SOR_095.

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
