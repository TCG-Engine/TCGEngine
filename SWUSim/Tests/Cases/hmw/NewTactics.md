# Offer_NonLeaderUnitsEitherSide
#// COVERAGE: offer=this section · decline=N/A (STRUCTURAL: mandatory "Choose")
#//           boundary=N/A (STRUCTURAL: no number) · control=StolenUnit_GoesToItsOwnersDeck (OWNER vs controller)
#//           token=TokenUnitCeases · reqboundary=AcrossTheRequestBoundary
#//           modes=2P,TwinSuns ("its owner … their deck" is a determined seat — the owner decides)
#//
#// HMW_218 New Tactics — Event, cost 5, [Cunning][Heroism], Learned/Tactic.
#// "Choose a non-leader unit. Its owner puts it on the top or bottom of their deck. (It isn't defeated.)"

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirLeader:SOR_014:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# EnemyUnit_OwnerChoosesTop

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Top

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_046
P2DISCARDCOUNT:0

---

# EnemyUnit_OwnerChoosesBottom

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Bottom

## EXPECT
P2DECKCOUNT:2
P2DECKTOPCARD:SOR_128

---

# FriendlyUnit_ToYourOwnDeck

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Top

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKTOPCARD:SOR_095
P2DECKCOUNT:0

---

# StolenUnit_GoesToItsOwnersDeck
#// P1 controls SOR_046 owned by P2: P2 decides, and it lands in P2's deck.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SOR_046:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P2>AnswerDecision:Top

## EXPECT
P1GROUNDARENACOUNT:1
P2DECKTOPCARD:SOR_046
P1DECKCOUNT:0

---

# TokenUnitCeases

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: HMW_T03:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:Top

## EXPECT
P2GROUNDARENACOUNT:0
P2DECKCOUNT:0

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P2>AnswerDecision:Top

## EXPECT
P2DECKTOPCARD:SOR_046
