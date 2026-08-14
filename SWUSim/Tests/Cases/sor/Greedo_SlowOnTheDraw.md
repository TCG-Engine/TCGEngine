# WhenDefeated_NonUnitDeals2
#// COVERAGE: offer=WhenDefeated_Offer_AllGroundUnits (pending choice: ground units on BOTH sides,
#//           space unit excluded) · decline=WhenDefeated_EmptyDeck_NoEffect (YES with an empty deck is
#//           a no-op; the NO branch of the may-discard is the same single YESNO answered '-'-equivalent
#//           and is exercised implicitly by every non-YES flow) · control=NGOR_OpponentResolvesWhenDefeated
#//           (defeat under the opponent's control: THEIR deck is milled, THEY aim the damage)
#//           · boundary=WhenDefeated_NonUnitDeals2 vs WhenDefeated_UnitNoDamage (card-type gate on/off
#//           pair) · reqboundary=N/A (mill, type check and damage all resolve inside one defeat window)
#// SOR_204 Greedo (3/1) — "When Defeated: You may discard a card from your deck. If it's not a unit,
#// deal 2 damage to a ground unit." Greedo attacks a 3/7 and dies; his When Defeated discards an
#// EVENT (Open Fire) from the top of P1's deck → deals 2 to the only ground unit (the 3/7, which
#// already has 3 from combat → 5).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenDefeated_UnitNoDamage
#// SOR_204 Greedo — the 2 damage only triggers if the discarded card is NOT a unit. Here the top of
#// P1's deck is a UNIT (SOR_095), so no damage: the 3/7 has only its 3 combat damage.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenDefeated_Offer_AllGroundUnits
#// SOR_204 Greedo — "deal 2 damage to a ground unit" targets ANY ground unit, friendly or enemy;
#// space units are never offered. Greedo trades into the 3/7; after YES the milled Open Fire is an
#// event, and the damage choose is left PENDING: the pool is P1's surviving Battlefield Marine
#// (arena compacted after Greedo's death) plus the enemy 3/7 — P1's space TIE excluded.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# WhenDefeated_FriendlyGroundTargetable
#// SOR_204 Greedo — the 2 damage may be aimed at Greedo's controller's OWN ground unit. Same trade
#// as above; the pending choose is answered with P1's Battlefield Marine, which takes the 2.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenDefeated_EmptyDeck_NoEffect
#// SOR_204 Greedo — with an EMPTY deck there is nothing to discard: accepting the may-discard is a
#// no-op (nothing milled, no type check, no damage step). The 3/7 keeps only its 3 combat damage,
#// P1's discard holds Greedo alone, and nothing is left pending.
#// Intended: with no deck the ability should not even prompt; the YESNO still appearing is a
#// cosmetic divergence noted in the port report — the resolved game state is identical.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# NGOR_OpponentResolvesWhenDefeated
#// SOR_204 Greedo × JTL_043 No Glory, Only Results — P2 takes control of P1's Greedo and defeats
#// him, so P2 resolves the When Defeated as the controller: P2's OWN deck is milled (Open Fire —
#// an event) and P2 aims the 2 damage. The only ground unit left is P1's Wampa (sole candidate →
#// auto-target) → Wampa takes 2. Greedo still goes to his OWNER's (P1) discard; JTL_043 costs
#// 5+2 off-aspect (Vigilance uncovered under the rk leader) = 7.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 7
WithP2Hand: JTL_043
WithP2Deck: SOR_172
WithP1GroundArena: SOR_204:1:0
WithP1GroundArena: SOR_164:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_204
P2DECKCOUNT:0
P2DISCARDCOUNT:2

---

# WhenDefeated_EmptyDeck_NoPrompt
#// Pointless-prompt doctrine: with an EMPTY deck the "discard the top?" YES can only no-op — no
#// prompt appears at all when Greedo dies.

## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_204:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
