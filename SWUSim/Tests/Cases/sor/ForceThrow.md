# ChooseOpponent_DeclineDamage
#// SOR_167 Force Throw — the "may deal damage" half is OPTIONAL: caster controls a Force unit (SOR_051)
#// and the discarded card has cost > 0, so the damage is OFFERED, but the caster DECLINES it (AnswerDecision:-).
#// Opponent holds 1 card (SOR_128) so the discard auto-resolves; nothing takes damage.
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2Hand: SOR_128
WithP1Hand: SOR_167
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:-
## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# ChooseOpponent_NoForceUnit
#// SOR_167 Force Throw — choosing the OPPONENT routes the discard to them (they discard their own card;
#// here their only card, SOR_128, auto-resolves). P1 controls no Force unit, so the "may deal damage"
#// half is skipped. P2's hand empties; only Force Throw itself is in P1's discard.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP2Hand: SOR_128
WithP1Hand: SOR_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1

---

# ChooseOpponent_TwoCards_DealsDamage
#// SOR_167 Force Throw — choose the OPPONENT when they hold 2+ cards: THEY choose which to discard
#// (SEC_144 Tempest Assault, cost 4), then the caster (controlling Force unit SOR_051 Luke) may deal
#// that cost as damage to a unit — 4 onto the enemy SOR_046 (3/7, survives). Exercises the async
#// cross-player discard path: the opponent's choice resolves BEFORE the cost is read / damage offered.
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SEC_142
WithP2Hand: SEC_144
WithP1Hand: SOR_167
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myHand-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:SEC_142
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1

---

# ChooseSelf_DiscardThenDamage
#// SOR_167 Force Throw (Event, cost 1, Aggression) — "Choose a player. That player discards a card from
#// their hand. Then, if you control a FORCE unit, you may deal damage to a unit equal to the cost of the
#// discarded card." P1 chooses ITSELF → P1 discards its only remaining hand card (SEC_080, cost 2) →
#// controls a Force unit (SOR_051 Luke) → may deal 2 to the enemy SOR_046 (3/7, survives). Discard pile holds
#// both Force Throw and SEC_080.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_167
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ChooseSelf_TwoCards_DealsDamage
#// SOR_167 Force Throw — choose YOURSELF while holding 2+ other cards: the caster picks which to discard
#// (SEC_080, cost 2), then (controlling Force unit SOR_051 Luke) may deal 2 to a unit — onto the enemy
#// SOR_046 (3/7, survives). Exercises the same-player 2-card discard path (caster's own MZCHOOSE).
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_167
WithP1Hand: SEC_080
WithP1Hand: SOR_128
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:2
