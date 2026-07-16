# AltCostNo
#// SOR_199 Bamboozle — alternate cost offered but declined; pays normal cost
#// P1 has 2 resources and Waylay (Cunning) in hand. Chooses NO → pays 2R normally.
#// Waylay remains in hand; only Bamboozle goes to discard.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# AltCostYes
#// SOR_199 Bamboozle — alternate cost: discard Cunning card instead of paying 2
#// P1 has 1 resource (can't afford normal cost). Waylay (SOR_222, Cunning) is in hand.
#// Player chooses YES → Waylay discarded, resource NOT spent, effect still fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199,SOR_222}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:1
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# ExhaustsUnit
#// SOR_199 Bamboozle — exhausts target unit (normal cost, no other Cunning card)
#// Single target → auto-resolves without player choice.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# NoAltCostUnplayable
#// SOR_199 Bamboozle — unplayable: 1 resource, no other Cunning card in hand
#// Alternate cost condition not met (no Cunning card to discard). Normal cost (2)
#// cannot be paid. Card stays in hand; no effect fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# StripsUpgradeToHand
#// SOR_199 Bamboozle — returns upgrade on exhausted unit to owner's hand
#// Upgrade goes to P2's hand (not discard). Unit is also exhausted.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:1

---

# TokenUpgradeSetAside
#// SOR_199 Bamboozle — token upgrades are set aside, not returned to hand
#// P2 unit has a Shield token (SOR_T02). Bamboozle bounces upgrades, but tokens
#// are set aside (out of game), so P2 hand stays empty and no discard entry.

## GIVEN
CommonSetup: ygw/grw/{myResources:2;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
