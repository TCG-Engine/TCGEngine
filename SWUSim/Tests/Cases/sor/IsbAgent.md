# RevealEventDeal1
#// SOR_176 ISB Agent (cost 1) — When Played: you may reveal an event from your hand;
#// if you do, deal 1 to a unit. P1's hand has an event (Open Fire, SOR_172) to reveal.
#// Answering YES reveals it and deals 1 to the chosen enemy (Battlefield Marine).
#// COVERAGE: offer=DamageOffer_AllUnitsIncludingSelf (pending SELECTABLEEXACT: every unit
#//           both sides and both arenas, including the Agent itself) · reqboundary=
#//           RevealEventDeal1 (the target answer arrives in a separate request from the play)
#//           · control=RevealEventDeal1 (pool crosses the seat line; resolves onto an enemy)
#//           · boundary pair=NoEventInHand_NoPrompt (0 events → the ability never offers) +
#//           DamageOffer_AllUnitsIncludingSelf (1 event → it does) · decline=
#//           DeclinesReveal_NoDamage ("you may" answered '-': nothing revealed, no damage).
#//           Note: the reveal is modeled as the commitment on the single may-choose — which
#//           event gets revealed is not a separate pick (info-only, no board effect).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_172}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DamageOffer_AllUnitsIncludingSelf
#// Intended: "deal 1 damage to a unit" — the pool is EVERY unit: both players, both arenas,
#// including the just-played Agent itself. An event (SOR_172) is in hand so the ability
#// offers; the decision is left pending so the exact pool can be inspected.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_172}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0    # friendly ground — idx 0 (Agent seats at idx 1)
WithP1SpaceArena: SOR_044:1:0     # friendly space
WithP2GroundArena: SEC_080:1:0    # enemy ground
WithP2SpaceArena: SOR_225:1:0     # enemy space

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# DeclinesReveal_NoDamage
#// "You MAY reveal" — declining ('-') reveals nothing and deals no damage: every unit ends
#// on 0 damage and the event stays in hand.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_172}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1HANDCARD:0:SOR_172
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoEventInHand_NoPrompt
#// Intended: with no event in hand there is nothing to reveal — the ability does nothing:
#// no prompt is raised and no unit takes damage. The other hand card is a UNIT (Battlefield
#// Marine), which must not count as revealable.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;handCardIds:SOR_176,SOR_095}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
