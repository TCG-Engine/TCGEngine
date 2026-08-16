# BounceAnotherFriendly
#// LAW_240 Milodon Rider (Cunning, cost 6, Ambush) — When Played: you may return another friendly
#// non-leader unit to its owner's hand. No enemy (Ambush no trigger); return SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_240
P1HANDCOUNT:1

---

# DeclineReturn
#// LAW_240 Milodon Rider — the When Played return is optional ("you may"). Decline it: nothing is
#// returned, SEC_080 stays in play, and Milodon Rider resolves normally alongside it.

## GIVEN
CommonSetup: yyk/bgw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1HANDCOUNT:0

---

# OfferPool_AnotherFriendlyNonLeaderUnit
#// LAW_240 Milodon Rider — offer assertion for "you may return ANOTHER FRIENDLY NON-LEADER unit to its
#// owner's hand". Three restriction words, so the board carries a violator for each:
#//   Milodon Rider itself (myGroundArena-2)      → OUT  ("another")
#//   P1's DEPLOYED leader (myGroundArena-1)      → OUT  ("non-leader")
#//   enemy ground SOR_046                        → OUT  ("friendly")
#//   friendly ground SEC_080 (myGroundArena-0)   → IN
#//   friendly SPACE  SOR_178 (mySpaceArena-0)    → IN   (no arena word in the text)
#// ⚠ Seeded arena units are placed BEFORE the CommonSetup leader deploy, so the deployed leader lands at
#// myGroundArena-1, not -0. With an enemy unit on the board Ambush also triggers, so the two When-Played
#// triggers first raise an EffectStack ordering choice — EffectStack-0 is the return ability. The return
#// pick is then left UNANSWERED so the pending pool can be read.
#// COVERAGE: offer=OfferPool_AnotherFriendlyNonLeaderUnit (pending SELECTABLEEXACT with a self, a
#//           deployed-leader and an enemy violator, plus cross-arena inclusion) ·
#//           reqboundary=NOT COVERED (the pick runs the shared BOUNCE_UNIT continuation) ·
#//           control=NOT COVERED ("its owner's hand" is asserted only for a self-owned unit; no section
#//           bounces a unit whose owner is not its controller) · boundary pair=BounceAnotherFriendly
#//           (one legal target) vs this section (two legal targets, three excluded) ·
#//           decline=DeclineReturn

## GIVEN
CommonSetup: yyk/bgw/{myResources:6; myLeader:SOR_010; myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
P1GROUNDARENAUNIT:1:CARDID:SOR_010
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:2:CARDID:LAW_240
P2GROUNDARENAUNIT:0:CARDID:SOR_046
