# TakeInitiative_DealThreeToBase_CreateBeast
#// HMW_158 Ezra Bridger — "When you take the initiative: You may deal 3 damage to your base. If you do,
#// create a Beast token." Taking the initiative (Claim) with Ezra in play offers the choice; accepting
#// deals 3 to your own base and creates a Beast (HMW_T03, 3/3 ground) at the next arena index.

## GIVEN
CommonSetup: rrw/bbk
WithActivePlayer: 1
WithP1GroundArena: HMW_158:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

---

# TakeInitiative_Decline_NoDamageNoBeast
#// The "may" decline: no self-damage, no Beast.

## GIVEN
CommonSetup: rrw/bbk
WithActivePlayer: 1
WithP1GroundArena: HMW_158:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1

---

# TakeInitiative_DamagePrevented_NoBeast
#// "If you do" is gated on the damage landing: with the self-base-damage prevented (Close the Shield Gate
#// armed on P1's own base), the 3 is prevented and no Beast is created. Decks are seeded so the round-end
#// regroup draw (Claim ends the round) doesn't add the empty-deck base penalty and mask the assertion.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1GroundArena: HMW_158:1:0
WithP1Hand: JTL_074
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0
- P2>Pass
- P1>Claim
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1

---

# TakeInitiative_Decline_NoDamageNoBeast_DeclinedWithNO
#// Same decline as the section above, but answering **NO** — the token the real client
#// submits for a YESNO's No button. The '-' variant is the MZMAYCHOOSE pass token and can
#// never reach this handler in a real game, so it could not catch SWUDecisionDeclined()
#// omitting 'NO' (which made a real decline resolve the effect anyway).
#// The "may" decline: no self-damage, no Beast.

## GIVEN
CommonSetup: rrw/bbk
WithActivePlayer: 1
WithP1GroundArena: HMW_158:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
