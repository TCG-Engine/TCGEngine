# IgnoreAspectPenalty
#// LAW_264 From a Certain Point of View (neutral event, cost 1) — "Play a card from your hand, ignoring
#// its aspect penalties." With a Cunning/Villainy leader+base, SOR_095 (Command,Heroism, cost 2) is
#// fully off-aspect (+4 -> would cost 6). After the event waives the penalty it costs just 2: it plays
#// with only 2 ready resources left (proving the waiver), leaving 0.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
WithP1Hand: SOR_095
WithP1Hand: LAW_264

## WHEN
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target —
#// only SOR_095 remains in hand after playing the event, so it is named explicitly.)
- P1>PlayHand:1
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0

---

# PlayEventIgnoringPenalty
#// LAW_264 From a Certain Point of View — the "play a card ignoring aspect penalties" also covers events.
#// With a Command/Heroism leader+base (ggw), Waylay (SOR_222, Cunning, cost 3) is off-aspect (+2 -> would
#// cost 5). Played via this event the penalty is waived so Waylay costs just 3. It bounces the lone enemy
#// SEC_080 back to P2's hand. Resources 4: event(1) + Waylay(3) = 4 spent (0 left), proving the waiver
#// (without it Waylay would be 5 and unaffordable).

## GIVEN
CommonSetup: ggw/bgw/{myResources:4}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: SOR_222
WithP1Hand: LAW_264

## WHEN
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target — only
#// SOR_222 remains in hand after the event, so it is named explicitly. The lone enemy is the only
#// Waylay target and that mandatory choose still auto-resolves.)
- P1>PlayHand:1
- P1>AnswerDecision:myHand-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1RESAVAILABLE:0

---

# PlayUpgradeIgnoringPenalty
#// LAW_264 From a Certain Point of View — also covers upgrades. With a Command/Heroism leader+base (ggw),
#// Mastery (LAW_129, Vigilance, cost 4) is off-aspect (+2 -> would cost 6). Played via this event onto the
#// friendly non-unique host SOR_095 the penalty is waived so Mastery costs its full 4 (no unique discount).
#// Resources 5: event(1) + Mastery(4) = 5 spent (0 left), proving the waiver (without it Mastery would be
#// 6 and unaffordable).

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_129
WithP1Hand: LAW_264

## WHEN
#// (Extra answer since 2026-08-14: this "you may" offer no longer auto-resolves a lone target — only
#// LAW_129 remains in hand after the event, so it is named explicitly. SOR_095 is the only unit, so
#// the mandatory attach-target choose still auto-resolves.)
- P1>PlayHand:1
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:LAW_129
P1RESAVAILABLE:0

---

# ChooseNothing_NoPlay
#// LAW_264 From a Certain Point of View — the player may choose to play nothing. With two other playable
#// cards in hand the play-choice is a real decision; declining it (answer "-") leaves both cards in hand and
#// only the event itself is discarded. Just its own 1 resource is spent (of 5).

## GIVEN
CommonSetup: yyk/bgw/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_264
WithP1Hand: SOR_095
WithP1Hand: SOR_237

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1
P1RESAVAILABLE:4

---

# Decline_SingleTarget_NoCardPlayed
#// LAW_264 From a Certain Point of View — declining is now possible even when exactly ONE card is
#// left in hand to play (since 2026-08-14 a lone target no longer auto-resolves). Mirrors
#// IgnoreAspectPenalty but P1 answers "-": SOR_095 is never played (it stays in hand, ground arena
#// empty) while the event's own cost was still paid — LAW_264 left hand for the discard pile and 1
#// of the 3 resources is spent, leaving 2.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: LAW_264

## WHEN
- P1>PlayHand:1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:2
P1NODECISION
