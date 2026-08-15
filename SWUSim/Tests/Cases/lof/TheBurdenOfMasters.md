# DiscardPlayExp
#// LOF_125 The Burden of Masters — Put a Force unit from discard on the bottom of your deck. If you do, play
#// a unit from your hand and give it 2 Experience tokens. P1 banks LOF_050 from discard, then plays SOR_059
#// (1/3) which enters with 2 Experience → 3/5.
#// (Answer list updated 2026-08-14: the discard "put a Force unit on the bottom" step is MANDATORY, so a
#// lone Force unit still auto-resolves; only the optional "play a unit" offer now prompts.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,SOR_059;discardCardIds:LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_059
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# ChooseNothingFromDiscard_NoForceUnit
#// LOF_125 The Burden of Masters — the discard-return targets only FORCE units. With just a non-Force unit
#// (SOR_128) in the discard there is nothing to bank; the "if you do... play a unit" clause is gated on
#// actually banking a Force unit, so no unit is played and both hand units stay put. (Intended: "should do nothing
#// when choosing nothing from discard"; SWUSim fizzles the whole event with no Play-Anyway prompt.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_125,SOR_059,LOF_050;discardCardIds:SOR_128}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2

---

# ChooseNothingFromHand
#// LOF_125 The Burden of Masters — after banking a Force unit from discard, the follow-up "play a unit" is a
#// may. P1 banks Plo Koon (LOF_050, Force) to the bottom of the deck, then declines to play a unit. The hand
#// unit stays put and nothing enters play. (Intended: "should do nothing when choosing nothing from hand".)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,SOR_059,SEC_080;discardCardIds:SOR_128,LOF_050}
P1OnlyActions: true
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_111

---

# PlayPilotAsUnit
#// LOF_125 The Burden of Masters — the played card enters as a UNIT even when it is a Pilot. P1 banks Plo
#// Koon (LOF_050, Force) to the bottom, then plays the Piloting card Astromech Pilot (JTL_057) from hand; it
#// enters the ground arena as a unit with 2 Experience tokens (1/3 -> 3/5). (Intended: "should only play pilots as
#// units".)
#// (Answer list updated 2026-08-14: the discard "put a Force unit on the bottom" step is MANDATORY, so a
#// lone Force unit still auto-resolves; only the optional "play a unit" offer now prompts.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,JTL_057;discardCardIds:LOF_050}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_057
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5

---

# CantAffordHandUnit_NotSelectable
#// LOF_125 The Burden of Masters — the "play a unit" step can only target units you can afford. P1 banks Plo
#// Koon (LOF_050, Force) to the bottom, but the only unit in hand is Industrious Team (LAW_124, cost 8) which
#// is unaffordable on the remaining resources, so it is not offered and nothing is played. (Intended: "should not
#// allow selecting targets that can't be afforded".)
#// (The lone Force unit in the discard auto-resolves — that step is mandatory — and the unaffordable
#// hand unit is never offered, so this section answers nothing. A stale answer here used to sit
#// unconsumed and silently ignored; removed 2026-08-14.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_125,LAW_124;discardCardIds:LOF_050}
P1OnlyActions: true
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_111

---

# Decline_SingleTarget_NoUnitPlayed
#// LOF_125 The Burden of Masters — new since 2026-08-14: the optional "play a unit from your hand" offer
#// with exactly ONE legal target now prompts instead of auto-resolving, so the lone affordable hand unit
#// can be declined. P1 banks LOF_050 (the only Force unit in discard, a MANDATORY step that still
#// auto-resolves) to the bottom of the deck, then declines to play SOR_059. Nothing enters play, SOR_059
#// stays in hand, and the banking still happened (deck 2, top still SOR_111, LOF_050 on the bottom).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:LOF_125,SOR_059;discardCardIds:LOF_050}
P1OnlyActions: true
WithP1Deck: SOR_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_111
P1DISCARDCOUNT:1
P1RESAVAILABLE:7
