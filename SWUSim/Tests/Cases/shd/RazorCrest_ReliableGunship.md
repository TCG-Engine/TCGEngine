# 174_SmuggleOntoExhaustedHost_NoAttack
#// SHD_174 smuggled onto an EXHAUSTED host — the granted attack can't happen (only ready units
#// attack): the upgrade still attaches, no attack, no stuck action (the game cleanly passes on).

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1NODECISION

---

# 174_SmugglePlay_AttacksWithHost
#// SHD_174 Hotshot DL-44 Blaster (+2/+0 upgrade, "Attach to a non-VEHICLE unit", Smuggle 3
#// [Cunning]) — "When played using Smuggle: Attack with attached unit." Smuggled from resources onto
#// the ready marine (single valid host → auto), which then attacks: base takes 3+2 = 5. The spent
#// slot is replaced from the deck.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:0
P1DECKCOUNT:0

---

# WhenPlayed_Decline
#// SHD_044 Razor Crest — the return is a "may": declining (AnswerDecision:-) leaves the upgrade in the
#// discard pile and nothing in hand.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_120

---

# WhenPlayed_NoUpgrade_Fizzle
#// SHD_044 Razor Crest — with only a non-upgrade (SOR_095 unit) in the discard pile, the "return an upgrade"
#// offer has no valid target and fizzles cleanly (no decision, discard untouched).

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_095}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION

---

# WhenPlayed_ReturnUpgrade
#// SHD_044 Razor Crest (4-cost 3/4 space, Vigilance/Heroism) — Restore 2 + "When Played: You may return an
#// upgrade from your discard pile to your hand." An upgrade (SOR_120 Academy Training) sits in P1's discard;
#// on play, the MZMAYCHOOSE offers it and P1 takes it → it moves discard → hand.

## GIVEN
CommonSetup: bbw/bbw/{myResources:4;discardCardIds:SOR_120}
P1OnlyActions: true
WithP1Hand: SHD_044

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_044
P1HANDCOUNT:1
P1DISCARDCOUNT:0
