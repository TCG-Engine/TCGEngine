# WhenDefeated_DealsThreeToBase
#// SOR_145 K-2SO (4/4, Overwhelm) — "When Defeated: For each opponent, choose one: either deal 3 damage
#// to that player's base, or that player discards a card from their hand." K-2SO attacks a 4/7 wall and
#// dies to the 4 counter-damage; its controller (P1) chooses Base → 3 damage to P2's base.
#// COVERAGE: offer=Offer_TheChoiceIsExactlyBaseOrDiscard_AndNeitherHasHappenedYet (decision left
#//           pending; the fork is asserted by its tooltip plus the fact that neither branch has taken
#//           effect — an option list exposes no selectable targets) ·
#//           decline=N/A ("choose one" is mandatory: there is no printed "you may" and no pass branch;
#//           the closest thing to a decline is picking Discard against an empty hand, which is covered
#//           by WhenDefeated_DiscardBranch_EmptyHand_NothingHappensAndTheBaseIsSTILLSafe) ·
#//           control=NoGloryOnlyResults_NewControllerResolvesIt (the taker resolves the When Defeated,
#//           and "for each opponent" is read from the taker's seat, while the card still goes to its
#//           OWNER's discard) ·
#//           boundary pair=WhenDefeated_BaseBranch_ADDSExactly3_ToTheDamageAlreadyThere (2 + 3 = 5, so
#//           the 3 is additive) + Overwhelm_ExcessSpillsToTheEnemyBase paired with
#//           Overwhelm_DefenderSURVIVES_NoBaseDamage (defeated → 1 excess, not defeated → 0) ·
#//           reqboundary=WhenDefeated_DiscardBranch_TheOPPONENTPicksWhichCard_AndExactlyOne — the
#//           branch chosen by P1 on one request is read back when P2 answers the hand pick on the next,
#//           and the effect resolves against the OTHER seat entirely

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Base

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenDefeated_OpponentDiscards
#// SOR_145 K-2SO — the other branch of the When Defeated choice: P1 chooses Discard, so the opponent
#// discards a card from their hand (here their only card, auto-discarded). The base is untouched.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0

---

# NoGloryOnlyResults_NewControllerResolvesIt
#// SOR_145 K-2SO — a take-control-then-defeat (JTL_043) defeats the unit under the TAKER's control,
#// so the TAKER resolves the When Defeated and "for each opponent" is read from the TAKER's seat:
#// P1 takes and defeats P2's K-2SO, chooses Base, and the 3 damage lands on P2's base. K-2SO still
#// goes to its OWNER P2's discard.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5;handCardIds:JTL_043}
P1OnlyActions: true
WithP2GroundArena: SOR_145:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Base

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:3
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_145

---

# Overwhelm_ExcessSpillsToTheEnemyBase
#// Intended: "Overwhelm" — K-2SO's first clause, which none of the When Defeated sections touch.
#// K-2SO (4/4) attacks a Battlefield Marine (3/3): 4 power defeats the 3-HP Marine and the 1 excess is
#// dealt to P2's base. K-2SO takes the Marine's 3 and survives on 4 HP, so his own When Defeated never
#// enters the picture and no prompt is raised.

## GIVEN
CommonSetup: ggw/brw
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0    # K-2SO 4/4, Overwhelm
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine 3/3

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Overwhelm_DefenderSURVIVES_NoBaseDamage
#// The load-bearing negative of the section above: excess only exists when the defender is DEFEATED.
#// K-2SO attacks a Consular Security Force (3/7); 4 damage does not defeat it, so P2's base takes
#// nothing at all — not "4 minus 7 clamped to 0" by accident, but nothing because the defender lived.
#// K-2SO takes 3 back and survives, so again there is no When Defeated prompt.

## GIVEN
CommonSetup: ggw/brw
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0    # K-2SO 4/4, Overwhelm
WithP2GroundArena: SOR_046:1:0    # Consular Security Force 3/7

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:4
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_TheChoiceIsExactlyBaseOrDiscard_AndNeitherHasHappenedYet
#// Intended: "choose one: EITHER deal 3 damage to that player's base, OR that player discards a card" —
#// a genuine two-way fork, and neither half may resolve before it is answered. The decision is left
#// PENDING here: K-2SO has already died in the trade (his arena is empty), a decision is waiting on
#// P1 with the two-branch prompt, and P2 is untouched on both axes — base still 0, hand still 2 cards.
#// The two branches themselves are resolved in separate sections, since answering would only prove one.
#// (The prompt is an option list rather than a target list, so it exposes a tooltip, not selectables.)

## GIVEN
CommonSetup: ggw/brw
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0    # 4/7 — kills K-2SO on the counter-swing
WithP2Hand: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Deal_3_to_their_base_or_make_them_discard?
P1GROUNDARENACOUNT:0
P2BASEDMG:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# WhenDefeated_DiscardBranch_TheOPPONENTPicksWhichCard_AndExactlyOne
#// Intended: "that player discards A card from their hand" — one card, and the discarding player is the
#// one who chooses it. P1 picks the Discard branch; the pick then belongs to P2, who is offered their
#// own hand and discards the Consular Security Force at index 1, keeping the Battlefield Marine. Hand
#// 2 → 1 (exactly one card, not the whole hand) and P2's base is untouched, which is what separates
#// this branch from the Base branch.

## GIVEN
CommonSetup: ggw/brw
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard
- P2>AnswerDecision:myHand-1

## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:SOR_095
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_046
P2BASEDMG:0
P1NODECISION
P2NODECISION

---

# WhenDefeated_DiscardBranch_EmptyHand_NothingHappensAndTheBaseIsSTILLSafe
#// Intended no-valid-target case: P1 may pick the Discard branch even when the opponent's hand is
#// EMPTY, and it simply does nothing. The important half is that the effect must not silently fall
#// through to the other branch — P2's base stays on 0 damage rather than taking the 3 — and that the
#// resolution does not stall waiting for a card that cannot be chosen: both seats end with no pending
#// decision.

## GIVEN
CommonSetup: ggw/brw
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Discard

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P2BASEDMG:0
P1NODECISION
P2NODECISION

---

# WhenDefeated_BaseBranch_ADDSExactly3_ToTheDamageAlreadyThere
#// Intended quantity/boundary check on the Base branch: "deal 3 damage to that player's base" adds 3 to
#// whatever is already there — it does not set the base to 3, and it is not the attacker's power. P2's
#// base starts on 2 damage; after K-2SO trades and P1 picks Base it reads exactly 5. The existing
#// WhenDefeated_DealsThreeToBase starts from an undamaged base, where "set to 3" and "add 3" are
#// indistinguishable.

## GIVEN
CommonSetup: ggw/brw/{theirBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:Base

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenDefeated_FiresWhenDefeatedByAnABILITY_NotOnlyInCombat
#// Intended: "When Defeated" is not "when defeated in combat". P1 plays Open Fire (SOR_172, "Deal 4
#// damage to a unit") and aims it at their OWN K-2SO (4/4), who is defeated by the ability alone. His
#// controller still resolves the choice and picks Base, so P2's base takes 3 even though no attack ever
#// happened and P2's Industrious Team is untouched. The enemy unit is on the board so the event's
#// target choice is a real pick rather than an auto-resolve.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3;myhandCardIds:SOR_172}
P1OnlyActions: true
WithP1GroundArena: SOR_145:1:0    # K-2SO 4/4
WithP2GroundArena: LAW_124:1:0    # second candidate for Open Fire

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Base

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:0
