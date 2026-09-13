# TakeInitiative_DealThreeToBase_CreateBeast
#// HMW_168 Ezra Bridger — "When you take the initiative: You may deal 3 damage to your base. If you do,
#// create a Beast token." Taking the initiative (Claim) with Ezra in play offers the choice; accepting
#// deals 3 to your own base and creates a Beast (HMW_T03, 3/3 ground) at the next arena index.

## GIVEN
CommonSetup: rrw/bbk
WithActivePlayer: 1
WithP1GroundArena: HMW_168:1:0

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
WithP1GroundArena: HMW_168:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1

---

# TakeInitiative_DamagePrevented_StillCreatesTheBeast
#// ★ JUDGE RULING 2026-09-14: prevented damage satisfies "If you do" — "you still tried to damage it"
#// (CR 9.2; the Malakili ruling). With Close the Shield Gate armed on P1's own base, the 3 is prevented
#// (base stays on 0) and the Beast is STILL created. Flipped that day: this section used to be
#// TakeInitiative_DamagePrevented_NoBeast and assert the opposite. Decks are seeded so the round-end
#// regroup draw (Claim ends the round) doesn't add the empty-deck base penalty and mask the assertion.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
WithActivePlayer: 1
WithP1GroundArena: HMW_168:1:0
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
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

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
WithP1GroundArena: HMW_168:1:0

## WHEN
- P1>Claim
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1

---

# StolenEzra_DamagesTheNEWControllersBase
#// THE CONTROL-CHANGE CELL. "When YOU take the initiative: you may deal 3 damage to YOUR base" resolves
#// for whoever CONTROLS Ezra, and hits THAT player's base. Every existing section leaves him on his
#// owner's board, where the two readings are indistinguishable.
#// P1 controls an Ezra that P2 still OWNS. P1 claims initiative, accepts, and it is P1's base that takes
#// the 3 — with P2's left clean, so an owner-scoped implementation is visible in both numbers at once.
#// The Beast token also arrives on P1's board, not P2's.
## GIVEN
CommonSetup: rrw/bbk
WithActivePlayer: 1
WithP1GroundArenaControlled: HMW_168:2
## WHEN
- P1>Claim
- P1>AnswerDecision:YES
## EXPECT
P1BASEDMG:3
P2BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
