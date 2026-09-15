# Accept_GivesItselfAWeakness_CreatesOneBeast
#// HMW_067 The Great Progenitor, First of the Drengir (Vigilance/Villainy, Creature, 5-cost 4/7 Ground,
#// unique) — "When Attack Ends: You may give a Weakness token to this unit. If you do, create a Beast token
#// for each Weakness token on this unit."
#// COVERAGE: offer=N/A (structural: the only possible recipient is "this unit" — the choice is a YESNO,
#//           there is no pool to assert) · decline=Decline_NoTokenNoBeast ·
#//           boundary=N/A (no numeric threshold; CountsOnlyWeaknessTokens_IncludingOnesAlreadyThere is the
#//           quantity cell) · control=ChangeOfHeart_StolenProgenitor_BeastsForTheThief ·
#//           reqboundary=RequestBoundaryBeforeTheAnswer (the unit rides the continuation by UniqueID) ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "this unit" and "create")
#// ⚠ PREVIEW-SET ASSUMPTIONS (HMW is absent from card-specific-rulings.md):
#//   • If the Progenitor died in its own attack, the ability still TRIGGERS (CR 7.6.16.c) but there is no
#//     unit to give a token to, so nothing is offered (a fizzle-only "you may" is never asked).
#//   • If the Weakness it gives itself DEFEATS it, the tokens are counted as they were the instant it left
#//     play (CR 11, Last Known Information) — WeaknessDefeatsIt_BeastsStillCreated_LastKnown is the one
#//     section to change if that is ever ruled the other way.
#// Here: a base attack for 4, then YES → one Weakness (4/7 → 3/6) → one Beast (HMW_T03 3/3, created
#// exhausted like every token unit).

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# Decline_NoTokenNoBeast
#// HMW_067 — "you may": NO gives no token, and "If you do" then creates nothing.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:4

---

# CountsOnlyWeaknessTokens_IncludingOnesAlreadyThere
#// HMW_067 — the quantity cell. The Progenitor already carries two Weakness tokens and an Experience
#// token (4/7 -2+1 = 3/6, so it attacks for 3). Accepting adds a third Weakness → THREE Beasts:
#//   • "one Beast per trigger" reads 1;  • counting BEFORE the new token reads 2;
#//   • counting every token upgrade (Experience too) reads 4.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:0
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:HMW_T02
WithP1GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:0:CARDID:HMW_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:3:CARDID:HMW_T03

---

# DiedInItsOwnAttack_NoPrompt_NoBeast
#// HMW_067 — CR 7.6.16.c: the ability still triggers when its unit is defeated by combat damage, but with
#// the Progenitor gone there is nothing to give a token to, so "you may" can only fizzle and is not asked.
#// On 4 damage (3 remaining) it attacks SEC_080 (3/3): both die.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:4
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# WeaknessDefeatsIt_BeastsStillCreated_LastKnown
#// HMW_067 — the sharp case. On 3 damage it attacks SOR_095 (3/3): kills it, takes 3 → 6 damage, 1 left.
#// Accepting is still a real choice: the Weakness drops it to 6 HP with 6 damage and it is defeated — but
#// the token WAS given ("If you do" is met), and the one Weakness on it when it left play makes one Beast
#// (Last Known Information, CR 11). The Beast is the only unit left; the Progenitor is in the discard.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:3
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_067

---

# Jerjerrod_DoublesTheWholeBatch
#// HMW_067 — "create a Beast token for each …" is ONE create instruction of N, so ASH_094 Moff Jerjerrod
#// ("If you would create a number of tokens … create twice that number instead") doubles the WHOLE batch.
#// One Weakness already on it → two after accepting → 2 Beasts, doubled to 4; Jerjerrod is defeated.
#// Creating the Beasts one at a time would offer Jerjerrod per token and end on 3.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: [HMW_067:1:0 ASH_094:1:0]
WithP1GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:5
P1GROUNDARENAUNIT:0:CARDID:HMW_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_094

---

# BlankedProgenitor_NoTrigger
#// HMW_067 — a Progenitor that has lost all abilities (the SOR_138 Force Lightning marker) has no When
#// Attack Ends: no prompt, no token, no Beast.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:0:SOR_138

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# ChangeOfHeart_StolenProgenitor_BeastsForTheThief
#// HMW_067 — control: P2 owns a ready Progenitor; P1 plays SOR_224 Change of Heart (Cunning, 6 — yyk
#// covers it; it is the only non-leader unit, so the pick auto-resolves) and attacks with it. The ability's
#// controller creates the Beast, so it lands in P1's arena, not the owner's.

## GIVEN
CommonSetup: yyk/bbk/{myResources:6}
P1OnlyActions: true
WithP2GroundArena: HMW_067:1:0
WithP1Hand: SOR_224

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_067
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

---

# RequestBoundaryBeforeTheAnswer
#// HMW_067 — the accept section with a request boundary before the answer: the unit is carried to the
#// continuation by UniqueID, so it must still be found in a fresh process.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_067:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
