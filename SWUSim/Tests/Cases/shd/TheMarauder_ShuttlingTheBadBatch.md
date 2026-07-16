# WhenPlayed_NoNameMatch_NoResource
#// SHD_102 The Marauder — the ramp is conditional on a shared name. P1 controls a Wampa (SOR_164) but
#// the discard card is a Battlefield Marine (SOR_095) — different names. The choose still resolves, but
#// the card is NOT put into play as a resource: it stays in the discard, resources unchanged.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_102
WithP1GroundArena: SOR_164:1:0
WithP1Discard: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1RESCOUNT:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095

---

# WhenPlayed_ResourceIfNameMatch
#// SHD_102 The Marauder (5-cost 4/5 space, Ambush) — "When Played: Choose a card in your discard pile.
#// Put it into play as a resource if it shares a name with a unit you control." P1 controls a Battlefield
#// Marine (SOR_095) and has another Battlefield Marine in discard. Playing The Marauder (P2 has no units
#// → Ambush skipped): the discard copy shares the name → it becomes a resource (exhausted). Net: 5
#// starting resources all spent on the play, +1 exhausted = 6; discard emptied.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_102
WithP1GroundArena: SOR_095:1:0
WithP1Discard: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_102
P1RESCOUNT:6
P1DISCARDCOUNT:0
