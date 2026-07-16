# AttackWithOtherUnit
#// SEC_103 Mon Mothma — When Played: you may attack with any number of OTHER units (even if exhausted;
#//   they can't attack bases). P1 plays Mon Mothma; her ready SOR_046 (3/7) attacks P2's SOR_128 (3/1),
#//   defeating it (and taking 3 counter). Then the loop re-offers but SOR_046 is excluded → loop ends.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DeclineImmediately_NoAttacks
#// SEC_103 Mon Mothma — the multi-attack is optional ("you may"). P1 plays Mon Mothma and declines the
#//   first offer → no attacks happen, the enemy unit is untouched, and the play finalizes cleanly.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:2

---

# ExhaustedUnitCanAttack
#// SEC_103 Mon Mothma — "even if those units are exhausted." The only other friendly unit (SOR_046) is
#//   EXHAUSTED, yet Mon Mothma lets it attack: it defeats P2's SOR_128. Proves exhausted units are offered
#//   and can attack via this ability.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:0:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# LoopsTwoUnits
#// SEC_103 Mon Mothma — the loop: "any number of other units, one at a time." P1 has two other units
#//   (both SOR_046, 3/7) and P2 has two 1-HP enemies. Mon Mothma's first SOR_046 attacks one enemy; the
#//   loop re-offers (the first attacker now excluded by UID); the second SOR_046 attacks the remaining
#//   enemy (auto-targeted, last one); the loop re-offers, finds no eligible unit, and ends. Both enemies
#//   are defeated; both attackers survive their 3 counter (7 HP). Proves the loop iterates and excludes
#//   already-attacked units.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:SEC_103}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:3
