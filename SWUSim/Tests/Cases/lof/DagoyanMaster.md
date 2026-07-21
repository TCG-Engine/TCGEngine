# UseForce_SearchForceUnit
#// LOF_115 Dagoyan Master (5/5) — When Played: may use the Force → search the top 5 for a Force unit,
#// reveal and draw it. P1 plays it with the Force, uses it, and draws the lone Force unit (LOF_050) from
#// the top 5; the 4 non-Force cards go to the bottom.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_115}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:LOF_050

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1

---

# WhenDefeated_SearchForceUnit
#// LOF_115 Dagoyan Master — the ability is "When Played/When Defeated"; this covers the WHEN DEFEATED half.
#// Dagoyan (5/5) attacks the enemy Plo Koon (6/8) and dies to the return damage; its When Defeated then uses
#// the Force to search the top 5, drawing the lone Force unit (LOF_050). The 4 non-Force cards go to the bottom.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_115:1:0
WithP2GroundArena: LOF_050:1:0
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:LOF_050

## EXPECT
P1NOFORCE
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0

---

# DeclineTheForce
#// LOF_115 Dagoyan Master — using the Force is a "may". P1 plays Dagoyan with the Force available but
#// declines; the Force token is retained, no card is drawn, and the deck is untouched.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_115}
P1OnlyActions: true
WithP1Force: true
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HASFORCE
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1DECKCOUNT:5

---

# NoForce_NoTrigger
#// LOF_115 Dagoyan Master — the ability requires losing the Force token; with no Force token the search
#// can't happen. P1 plays Dagoyan without the Force: it simply enters play, nothing is drawn, and the deck
#// is untouched — the ability is not triggered because the player does not have the Force.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_115}
P1OnlyActions: true
WithP1Deck: LOF_050
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1DECKCOUNT:5
