# ToroCalican_PlayBountyHunter_Deal1Ready
#// SHD_239 Toro Calican — "When you play another Bounty Hunter unit: You may deal 1 damage to it. If you
#// do, ready this unit. Once each round." Toro starts exhausted; playing SHD_138 (a Bounty Hunter unit)
#// deals it 1 and readies Toro.

## GIVEN
CommonSetup: yyk/yyk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_239:0:0
WithP1Hand: SHD_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:READY

---

# OncePerRound_SecondBountyHunterRaisesNoOffer
#// "Use this ability only once each round." P1 plays Reputable Hunter (SHD_117, a non-unique Bounty
#// Hunter) and accepts: it takes 1, Toro readies. Toro then attacks (exhausting himself), and a second
#// Reputable Hunter the same round raises NO offer — Toro stays exhausted and the new Hunter undamaged.
## GIVEN
CommonSetup: yyk/yyk/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: SHD_239:0:0
WithP1Hand: [SHD_117 SHD_117]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:2:DAMAGE:0

---

# Declining_DoesNotSpendTheRound
#// USER RULING 2026-09-07: declining a triggered "you may" never used it. P1 declines on the first
#// Reputable Hunter, so the second one the same round is still offered — accepting it readies Toro.
## GIVEN
CommonSetup: yyk/yyk/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: SHD_239:0:0
WithP1Hand: [SHD_117 SHD_117]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:2:DAMAGE:1
