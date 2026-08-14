# WhenPlayed_Decline_NoReturn
#// SOR_099 Bright Hope — the return is optional ("You may"). Declining means no unit is
#// returned and NO card is drawn. The friendly ground unit stays, hand holds only what's left
#// after playing Bright Hope (0), and the deck is untouched.
#// COVERAGE: offer=Offer_FriendlyGroundOnly_NoSpaceNoEnemyNoLeader (pending SELECTABLEEXACT) ·
#//           decline=WhenPlayed_Decline_NoReturn (and the "if you do" rider correctly withholds the
#//           draw) · reqboundary=WhenPlayed_ReturnGroundDraw (the return answer arrives on a later
#//           request than the play) · boundary pair=WhenPlayed_ReturnGroundDraw (return → draw) +
#//           WhenPlayed_Decline_NoReturn (no return → no draw), plus the token variant
#//           ReturnTokenGroundUnit_TokenCeases_StillDraws (returned-token cease still counts as
#//           "if you do") · control=N/A (one-shot When Played; the pool reads live control at
#//           resolution — no lingering marker)

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# WhenPlayed_ReturnGroundDraw
#// SOR_099 Bright Hope (2/6, Space, Sentinel) — When Played: You may return a friendly
#// non-leader GROUND unit to its owner's hand. If you do, draw a card. P1 returns its
#// Battlefield Marine and draws. Net hand: played Bright Hope (-1), Marine back (+1), draw (+1)
#// = 2; deck -1; the ground arena is emptied.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0    # friendly non-leader ground unit — returned
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# Offer_FriendlyGroundOnly_NoSpaceNoEnemyNoLeader
#// Intended: "return a friendly non-leader GROUND unit" — the pool is exactly P1's two ordinary
#// ground units. Excluded: P1's own space unit (wrong arena), the enemy Wampa (not friendly), the
#// deployed leader unit (non-leader only), and Bright Hope itself (space). The decision is left
#// pending so the offer itself is asserted.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;myLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1GROUNDARENAUNIT:2:ISLEADERUNIT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# ReturnTokenGroundUnit_TokenCeases_StillDraws
#// Intended (per CR — a token that leaves play by a non-defeat route is removed from the game, but
#// still counts as having been returned to hand): returning the friendly Clone Trooper TOKEN makes
#// it cease — it never reaches the hand and is not in the discard — yet the "if you do" rider is
#// satisfied and a card is still drawn. Hand ends with exactly the drawn card.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SOR_099
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_232 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DISCARDCOUNT:0
P1NODECISION
