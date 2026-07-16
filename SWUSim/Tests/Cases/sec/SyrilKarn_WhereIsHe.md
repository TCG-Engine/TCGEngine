# DeclineDisclose_NoEffect
#// SEC_133 Syril Karn — decline the On Attack disclose → no unit chosen, no damage (attack still lands).

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoCards_AutoDeals2
#// SEC_133 Syril Karn — the chosen unit's controller has no cards, so the 2 damage is dealt
#// automatically (no discard decision offered).

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION

---

# OnAttack_Disclose_DeclineDiscard_Deals2
#// SEC_133 Syril Karn (Ground, 2/3, Aggression/Villainy) — On Attack: you may disclose
#//   AggressionAggressionVillainy → choose a unit; deal 2 to it unless its controller discards a card.
#// Syril (idx0) attacks the base. On Attack: disclose two SEC_133 (Agg,Villainy) → choose the enemy
#// SOR_046 → its controller (P2) declines to discard → SOR_046 takes 2 damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:NO

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P2HANDCOUNT:1
P1NODECISION

---

# OnAttack_Disclose_Discard_PreventsDamage
#// SEC_133 Syril Karn — the chosen unit's controller discards a card to prevent the 2 damage.
#// P2 has exactly 1 card; answers YES → that card is discarded (auto, single card) → SOR_046 takes no damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_133:1:0
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
