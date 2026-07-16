# ClonePenaltyWaived
#// SHD_198 passive — "Ignore the aspect penalty on the first Clone unit you play each round." P1
#// (Cunning/Heroism) controls SHD_198 and plays an off-aspect Clone unit (SOR_160, Aggression, cost 2).
#// Normally the off-aspect play would cost 2 + 2 penalty = 4; the waiver drops it to 2, so exactly 2
#// ready resources suffice — the play succeeds and all resources are spent.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_198:1:0
WithP1Hand: SOR_160
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_160
P1RESAVAILABLE:0

---

# NoWaiverWithoutClone198
#// SHD_198 passive is gated on controlling SHD_198. Without it in play, the off-aspect Clone (SOR_160,
#// Aggression, cost 2) pays the +2 aspect penalty → total 4. With only 2 ready resources the play is
#// unaffordable: it stays in hand and no resources are spent.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1Hand: SOR_160
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2

---

# WaiverConsumed_SecondClonePays
#// SHD_198 passive is once per round ("the first Clone unit you play each round"). P1 controls SHD_198
#// and plays TWO off-aspect Clone units (SOR_160, Aggression, cost 2 each). The first is waived (cost 2);
#// the second pays the +2 penalty (cost 4). Total spent = 6, so 6 resources are exactly consumed — if the
#// charge weren't consumed, the second would also be waived and 2 would remain.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SHD_198:1:0
WithP1Hand: SOR_160
WithP1Hand: SOR_160
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:0

---

# WhenPlayed_SearchClone
#// SHD_198 (2-cost 2/2 Cunning/Heroism) — "When Played: Search the top 5 cards of your deck for a Clone
#// card, reveal it, and draw it." Top of deck has a Clone card (SHD_095, Fringe/Clone) among fillers →
#// drawn.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_198
WithP1Deck: SHD_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_095

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_198
P1HANDCOUNT:1
