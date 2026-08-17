# WhenPlayedBounceCheap
#// LAW_089 Kanan Jarrus (3/4, Restore 1) — When Played: you may return a non-leader unit that costs 2 or
#// less (4 or less if you control a Command or Aggression unit). P1 controls neither -> threshold 2;
#// return the enemy SEC_080 (cost 2).

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedThreshold4Command
#// LAW_089 Kanan Jarrus — the return threshold rises to 4 when you control a Command unit. P1 controls
#// SEC_080 (Command), so Kanan can return the enemy SHD_107 Enterprising Lackeys (cost 4), which would be
#// out of range at the base threshold of 2.

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SHD_107:1:0
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedThreshold4Aggression
#// LAW_089 Kanan Jarrus — the threshold also rises to 4 when you control an Aggression unit. P1 controls
#// SOR_128 (Aggression), so Kanan returns the cost-4 enemy SHD_107 to its owner's hand.

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SHD_107:1:0
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# WhenPlayedThreshold2ExcludesCost4
#// LAW_089 Kanan Jarrus — with no Command or Aggression unit controlled the threshold stays 2. The enemy
#// SHD_107 (cost 4) is NOT a legal return target; only the cost-2 SEC_080 is selectable.

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP2GroundArena: [SHD_107:1:0 SEC_080:1:0]
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1

---

# WhenPlayedReturnDeclined_UnitStaysInPlay
#// LAW_089 Kanan Jarrus — DECLINE branch. "You MAY return a non-leader unit that costs 2 or less":
#// with the enemy SEC_080 (cost 2) squarely inside the base threshold, the offer must still be
#// refusable. Declining leaves it in play and the opponent's hand empty — every other section in this
#// file takes the return, so a mandatory implementation would satisfy all of them.
#// Kanan himself is on the board afterwards, which confirms the play resolved.

## GIVEN
CommonSetup: byw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_089

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_089
P1NODECISION
