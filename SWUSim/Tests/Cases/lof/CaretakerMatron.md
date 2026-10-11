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

---

# PlayedForceUPGRADE_Draw
#// ⚠ FAMILY SWEEP (2026-09-17, from the TWI_017 "Craving Power" report): the condition is "if you played
#// a Force CARD this phase" — ANY card type. SWU_PLAYED_FORCE_CARD was armed only in the UNIT-ENTRY
#// branch of the play pipeline (its own comment: "an event, an upgrade, and a Piloting card played as a
#// pilot do not" reach it), so playing a Force UPGRADE armed nothing and the Action drew no card.
#// The sibling PlayedForce_Draw section plays a Force UNIT (LOF_193) — same shape, different funnel,
#// which is why it always passed.
#// ⚠ FIXTURE TRAP: LOF_074 Bolstered Endurance says "Attach to a Force unit", and the Matron herself is
#// FRINGE, not Force. With no legal host the play FIZZLES (the card stays in hand) — and the first draft
#// of this section still "drew", because SWUCommitPlay runs BEFORE the upgrade branch validates hosts.
#// SOR_061 Guardian of the Whills (Force) is therefore seeded as the host so the play really resolves.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:LOF_074}
P1OnlyActions: true
WithP1GroundArena: LOF_243:1:0
WithP1GroundArena: SOR_061:1:0
WithP1Deck: SOR_128
## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
