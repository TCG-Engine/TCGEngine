# PlayUpgradeDiscounted_ReactiveDebuff
#// SHD_067 Fenn Rau (6-cost ground) — "When Played: You may play an upgrade from your hand. It costs 2 less."
#// + "When you play an upgrade on this unit: Give an enemy unit -2/-2 for this phase." Playing Fenn Rau, P1
#// plays SOR_120 (cost 2 → 0) which auto-attaches to Fenn Rau (the only host); that upgrade-play triggers the
#// reactive, giving the enemy SOR_046 -2/-2 (3/7 → 1/5). Total cost 6 → 0 resources left.
#// COVERAGE: offer=WhenPlayed_OfferIsUpgradesInHandOnly (clause 1's hand pool, units excluded) +
#//           Debuff_OfferSpansBothEnemyArenas (clause 2's target pool) ·
#//           decline=WhenPlayed_DeclineTheDiscountedUpgrade ("You MAY play an upgrade") ·
#//           boundary pair=Debuff_DefeatsAUnitLeftWithNoRemainingHP (a shrink to no remaining HP kills)
#//           paired with the healthy 3/7 → 1/5 case here, and Debuff_WearsOffWhenThePhaseEnds paired
#//           with the same-phase sections (the "for this phase" duration from both sides) ·
#//           negatives=UpgradePlayedOnAnotherUnit_NoDebuff (wrong host) +
#//           UpgradeMovedOntoHim_IsNotAPlay_NoDebuff (right host, not a play) +
#//           NoEnemyUnit_UpgradeStillResolvesCleanly (empty target pool) ·
#//           control=N/A (neither clause reads or changes control; Fenn Rau's own controller is the
#//           only seat either clause resolves from) ·
#//           reqboundary=SimulateRequestBoundary_AttachHostPickAfterTheUpgradePick (the chosen hand
#//           upgrade and its −2 discount are carried from one request to the next)

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# FennRau_WhenPlayed_DeclineTheDiscountedUpgrade
#// SHD_067 — "You MAY play an upgrade from your hand": the decline branch. P1 answers the optional pick
#// with the choose-nothing token, so Academy Training stays in hand, Fenn Rau lands bare, and the second
#// clause never gets a chance to fire (the enemy keeps its printed 3/7). Only Fenn Rau's own 6 is spent.

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_120
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# FennRau_WhenPlayed_OfferIsUpgradesInHandOnly
#// SHD_067 — "play an UPGRADE from your hand": the offer must contain the hand's upgrades and nothing
#// else. Two upgrades (Academy Training SOR_120, Resilient SOR_069) plus a UNIT (SOR_095) are held; the
#// unit must not be offered, and having two upgrades keeps the pick interactive. Fenn Rau himself has
#// already left hand by then, so the two upgrades are myHand-0 / myHand-1. Decision left PENDING.

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP1Hand: SOR_069
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# FennRau_Debuff_OfferSpansBothEnemyArenas
#// SHD_067 — "Give an ENEMY unit -2/-2": the second clause's target pool is every enemy unit, in either
#// arena, not just the ground arena Fenn Rau stands in. Two enemy units (one per arena) keep the pick
#// interactive; the discounted Academy Training auto-attaches to Fenn Rau (its only legal host) and the
#// debuff target choice is left PENDING.

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# FennRau_UpgradePlayedOnAnotherUnit_NoDebuff
#// SHD_067 — "When you play an upgrade ON THIS UNIT". Fenn Rau is already on the board, and P1 plays
#// Academy Training onto a DIFFERENT friendly unit; the second clause must stay silent. The enemy keeps
#// its printed 3/7 and no target prompt is raised at all.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SHD_067:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# FennRau_UpgradeMovedOntoHim_IsNotAPlay_NoDebuff
#// SHD_067 — the clause keys on PLAYING an upgrade on him, not on one arriving by any means. Survivors'
#// Gauntlet (SHD_064) attacks and its On Attack MOVES Academy Training off Battlefield Marine onto Fenn
#// Rau; the upgrade ends up on him, but nothing was played, so no -2/-2 is handed out.

## GIVEN
CommonSetup: bgk/bgk
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SHD_067:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 2:SOR_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-2.u0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# FennRau_NoEnemyUnit_UpgradeStillResolvesCleanly
#// SHD_067 — the second clause with an EMPTY target pool. P1 plays Academy Training onto Fenn Rau with
#// P2 fielding nothing at all: the trigger fires, finds no enemy unit, and must fizzle silently rather
#// than hang on a prompt. The upgrade still lands and still buffs him (+2/+2 → 7/8).

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SHD_067:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:8
P1NODECISION

---

# FennRau_Debuff_DefeatsAUnitLeftWithNoRemainingHP
#// SHD_067 — -2/-2 is a shrink, and a shrink that leaves a unit with no remaining HP defeats it. The
#// enemy Battlefield Marine is 3/3 carrying 2 damage (1 HP remaining); the debuff drops its HP to 1
#// against 2 damage, so the sweep on the attach path must defeat it outright and send it to its owner's
#// discard. Sole enemy unit, so the debuff auto-targets it.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SHD_067:1:0
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# FennRau_Debuff_WearsOffWhenThePhaseEnds
#// SHD_067 — "-2/-2 for THIS PHASE". Same play as the section above but against a healthy Consular
#// Security Force, then both players pass out of the action phase and through regroup: the enemy must be
#// back to its printed 3/7 in the next action phase. Both decks are seeded so the regroup draws don't
#// damage a base off an empty deck and confuse the picture.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SOR_120
WithP1GroundArena: SHD_067:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# SimulateRequestBoundary_AttachHostPickAfterTheUpgradePick
#// SHD_067 — the "which upgrade" pick and the "which unit to attach it to" pick are separate requests
#// in production, so the chosen card and its −2 discount have to survive a round-trip through the
#// serialized gamestate. Two hand upgrades keep the first pick interactive and a second friendly unit
#// keeps the second one interactive; the boundary sits between them. Academy Training must still cost
#// 2 − 2 = 0 (6 resources, all six spent on Fenn Rau), still land on Fenn Rau, and still fire the
#// −2/−2 onto the sole enemy unit (3/7 → 1/5).

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_067
WithP1Hand: SOR_120
WithP1Hand: SOR_069
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SHD_067
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P1NODECISION

---

# FennRau_OpponentPlaysAnUpgradeOnHim_NoDebuff
#// SHD_067 — "When YOU play an upgrade on this unit." An OPPONENT may legally attach an upgrade to your
#// Fenn Rau (Entrenched SOR_072 has no printed attach restriction), and when they do this clause must
#// stay silent — it is not their trigger to fire. Fenn Rau keeps the upgrade and the +3/+3 it brings
#// (5/6 -> 8/9) and NOTHING takes -2/-2: not P2's own unit, and not Fenn Rau himself. Before the fix the
#// trigger fired for P2, whose "enemy unit" pool contained only Fenn Rau, so he debuffed himself to 6/7.

## GIVEN
CommonSetup: bgk/bgk/{theirResources:2}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: SOR_072
WithP1GroundArena: SHD_067:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:9
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P2NODECISION
