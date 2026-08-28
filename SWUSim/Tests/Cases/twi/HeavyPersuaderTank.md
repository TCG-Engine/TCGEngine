# WhenPlayed_Deal2Ground
#// TWI_167 Heavy Persuader Tank (Unit 6/5, Ground, cost 7) — "Exploit 2. When Played: You may deal 2
#// damage to a ground unit." Played with no friendly units (Exploit auto-skips); the When Played may-deal
#// targets the enemy SOR_046 for 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_167}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ExploitUnderChoose_StillUnaffordable_NoFodderDies
#// ⚠ THE HALF-APPLIED PLAY, Exploit's instance — the same defect reported on HMW_125 The Marauder
#// (2026-08-28), and worse here because the fodder is DEFEATED rather than damaged.
#// TWI_167 costs 7 with Exploit 2. P1 holds 3 resources and two friendly units, so the best price is
#// 7 - 2*2 = 3 and the card legitimately glows. Confirming only ONE pick prices it at 5, which
#// ActivateCard rejects with "Not enough ready resources" — at which point the unit is already dead and
#// the Tank is still in hand.
#// Playing a card is atomic: the arena count is the sharp assertion (2 = nothing died, 1 = the leak).

## GIVEN
CommonSetup: rrk/grw/{myResources:3;handCardIds:TWI_167}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1HANDCOUNT:1
P1RESAVAILABLE:3

---

# ExploitEnoughToAfford_FodderDiesAndTheTankLands
#// ⚠ THE BOUNDARY PARTNER for the section above. Identical fixture, but P1 confirms BOTH picks: the
#// price drops to 7 - 2*2 = 3, exactly what P1 can pay. Both fodder units die and the Tank lands.
#// Without this pair the gate above passes for an implementation that simply refuses every Exploit play.

## GIVEN
CommonSetup: rrk/grw/{myResources:3;handCardIds:TWI_167}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_167
P1HANDCOUNT:0
P1RESAVAILABLE:0
