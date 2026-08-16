# Grit_Undamaged_PrintedOneOverFour
#// SHD_027 Hylobon Enforcer — Unit, cost 1, 1/4, Ground, [Villainy][Vigilance], trait Underworld,
#// non-unique.
#// "Grit (This unit gets +1/+0 for each damage on it.)
#//  Bounty — Draw a card. (When this unit is defeated or captured, your opponent collects its bounty.)"
#// COVERAGE: offer=N/A on both clauses (Grit is a passive stat modifier with no target, and the Bounty's
#//           only decision is the collect-or-decline YES/NO — there is no target pool to assert) ·
#//           decline=Bounty_Declined_NoCardDrawn · boundary=Grit_BoundaryPair_TwoVersusThreeDamage (N vs
#//           N+1 damage across the +1/+0 step) AND Bounty_OneCardDeck_DrawsWithNoBaseDamage vs
#//           Bounty_EmptyDeck_ThreeDamageToTheCollectorsBase (the CR 6.1 empty-deck boundary) ·
#//           control=Bounty_ControlledByTheOpponentOfItsOwner (owner and controller split: the collector
#//           follows the CONTROLLER's opponent while the card itself returns to its OWNER's discard) ·
#//           reqboundary=Grit_SurvivesTheRequestBoundary
#// The undamaged floor: 0 damage means +0/+0, so the printed 1/4 stands and an attack on the base for
#// exactly 1 is the behavioural proof (a stat readout alone could be a display-only value).

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_027
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:4
P2BASEDMG:1

---

# Grit_BoundaryPair_TwoVersusThreeDamage
#// SHD_027 Hylobon Enforcer — Grit is "+1/+0 for EACH damage", so the boundary between N and N+1 damage
#// must move the power by exactly one and never touch HP. Two copies are seated (SHD_027 is non-unique, so
#// doubling it is safe) at 2 and 3 damage: 1+2 = 3 power and 1+3 = 4 power, both still 4 HP. The 3-damage
#// copy attacks the base for its full scaled 4 — the behavioural half of the pair.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: [SHD_027:1:2 SHD_027:1:3]

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:1:HP:4
P2BASEDMG:4

---

# Grit_SurvivesTheRequestBoundary
#// SHD_027 Hylobon Enforcer — Grit is recomputed from the unit's live damage, so it must read the same
#// after the gamestate has been serialized and re-parsed. An explicit request boundary is driven between
#// seating the damaged Hylobon and attacking with it; the base still takes the scaled 4, not the printed 1.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:3

## WHEN
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2BASEDMG:4

---

# Grit_ScalesBackDownWhenTheDamageIsHealed
#// SHD_027 Hylobon Enforcer — Grit tracks the damage rather than banking a one-time buff, so healing has to
#// walk the power back down. The Hylobon starts on 3 damage (4 power); SOR_074 Repair heals 3 from it,
#// returning it to 0 damage and its printed 1 power. It then attacks the base for 1, not 4.

## GIVEN
CommonSetup: bbk/bbk/{myResources:1;myhandCardIds:SOR_074}
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:3

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_027
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:1
P2BASEDMG:1

---

# Bounty_OnDefeat_TheOpponentDrawsTheCard
#// SHD_027 Hylobon Enforcer — "Bounty — Draw a card" is collected by the bounty unit's controller's
#// OPPONENT, so P1's Hylobon dying hands the card to P2. The Hylobon (1/4, undamaged when combat damage is
#// dealt, so 1 power) attacks P2's SOR_164 Wampa (4/5): the Wampa takes 1 and deals 4 back, exactly lethal
#// on the Hylobon's 4 HP. P2 answers the collect prompt and draws the single seeded card; P1 draws nothing.
#// This is also the deck-not-empty half of the CR 6.1 boundary pair — a 1-card deck draws with no base
#// damage at all.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:1
P2HANDCOUNT:1
P2DECKCOUNT:0
P2BASEDMG:0
P1HANDCOUNT:0

---

# Bounty_Declined_NoCardDrawn
#// SHD_027 Hylobon Enforcer — collecting a Bounty is optional. Identical fixture to the draw section, but
#// P2 declines the prompt: the deck keeps its card, P2's hand stays empty and nothing else moves.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:1
P2BASEDMG:0
P1HANDCOUNT:0

---

# Bounty_EmptyDeck_ThreeDamageToTheCollectorsBase
#// SHD_027 Hylobon Enforcer — the Bounty's draw is a normal draw, so per CR 6.1 a collector with an empty
#// deck takes 3 damage to their own base instead of drawing. P2 collects with nothing left in the deck: no
#// card appears in hand and P2's base takes 3. The 3 lands on the COLLECTOR's base even though the Hylobon
#// that died belonged to P1 — P1's base is untouched.
#// (Deliberately the empty-deck half of the pair; the 1-card half is Bounty_OnDefeat_TheOpponentDrawsTheCard.)

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:0
P2BASEDMG:3
P1BASEDMG:0
P1HANDCOUNT:0

---

# Bounty_OnCapture_TheOpponentDrawsTheCard
#// SHD_027 Hylobon Enforcer — the Bounty reminder text is "when this unit is defeated OR CAPTURED", two
#// separate dispatch paths. Here the capture leg: P1 plays SHD_131 Take Captive so a friendly SOR_095
#// captures P2's Hylobon. The Hylobon is genuinely captured (it ends as a facedown subcard on the captor,
#// not in a discard pile) and the collect still fires for the controller's opponent — P1 — who draws.

## GIVEN
CommonSetup: ggk/bbk/{myResources:3;myhandCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_027:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_027
P1HANDCOUNT:1
P1DECKCOUNT:0
P2HANDCOUNT:0

---

# Bounty_ControlledByTheOpponentOfItsOwner
#// SHD_027 Hylobon Enforcer — owner and controller are split: the Hylobon is OWNED by P2 but CONTROLLED by
#// P1. Two different seats therefore have to be resolved correctly when it dies:
#//   * the collector is the CONTROLLER's opponent — P2 — who answers the prompt and draws;
#//   * the defeated card itself goes to its OWNER's discard, so SHD_027 lands in P2's discard even though
#//     it died out of P1's ground arena.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArenaControlled: SHD_027:2
WithP2GroundArena: SOR_164:1:0
WithP2Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2DISCARDUNIT:0:CARDID:SHD_027
P1DISCARDCOUNT:0
P2HANDCOUNT:1
P2DECKCOUNT:0
P1HANDCOUNT:0
