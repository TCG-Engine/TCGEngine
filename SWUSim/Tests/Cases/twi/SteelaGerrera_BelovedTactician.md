# Decline_NoDamageNoSearch
#// TWI_146 Steela Gerrera — declining the "may" (AnswerDecision:NO) deals no base damage and runs no
#// search: base stays clean, hand ends empty (only the played Steela left hand), deck unchanged.

## GIVEN
CommonSetup: rrw/bbw/{myResources:4;handCardIds:TWI_146}
P1OnlyActions: true
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P1HANDCOUNT:0
P1DECKCOUNT:4

---

# WhenDefeated_DamageBaseSearchTactic
#// TWI_146 Steela Gerrera — the SAME ability also fires from the When Defeated window. Steela (4/3)
#// attacks SOR_046 (3/7) and dies to the 3 counter-damage; her When Defeated then (option taken) deals 2
#// to P1's own base and draws the Tactic (TWI_099) from the top 8.

## GIVEN
CommonSetup: rrw/bbw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: TWI_146:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:TWI_099

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:3

---

# WhenPlayed_DamageBaseSearchTactic
#// TWI_146 Steela Gerrera (Unit 4/3, Ground, cost 4, Aggression/Heroism, Fringe) — "When Played/When
#// Defeated: You may deal 2 damage to your base. If you do, search the top 8 cards of your deck for a
#// Tactic card, reveal it, and draw it." Taking the option deals 2 to P1's own base and draws the Tactic
#// (TWI_099 Synchronized Strike) off the top 8; the 3 non-Tactic cards go to the bottom. Base r + leader
#// rw cover both Aggression/Heroism pips.

## GIVEN
CommonSetup: rrw/bbw/{myResources:4;handCardIds:TWI_146}
P1OnlyActions: true
WithP1Deck: [TWI_099 SOR_095 SOR_128 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:TWI_099

## EXPECT
P1BASEDMG:2
P1HANDCOUNT:1
P1DECKCOUNT:3
