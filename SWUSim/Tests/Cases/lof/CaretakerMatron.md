# PlayedForce_Draw
#// LOF_243 Caretaker Matron — Action [Exhaust]: if you played a Force card this phase, draw a card. P1
#// plays the Force unit Youngling Padawan, then activates the Matron to draw.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:LOF_193}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# NoForcePlayed_ExhaustsButNoDraw
#// LOF_243 Caretaker Matron — the [Exhaust] Action still resolves when NO Force card was played this phase,
#// but has no effect: the Matron exhausts and NO card is drawn (hand stays empty, deck untouched). The
#// ability is usable ("Use it anyway") but draws nothing when no Force trait card has been played this phase.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP1Deck: SOR_095

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DECKCOUNT:1
