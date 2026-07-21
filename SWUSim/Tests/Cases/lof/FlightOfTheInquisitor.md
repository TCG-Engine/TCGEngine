# ReturnTwo
#// LOF_240 Flight of the Inquisitor — You may return a Force unit and a Lightsaber upgrade from your discard
#// to your hand. P1 returns LOF_050 (Force unit) and SOR_053 (Lightsaber upgrade); only the LOF_240 event
#// remains in discard.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:LOF_050,SOR_053}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:1

---

# OnlyLightsaberAvailable
#// LOF_240 — with NO Force unit in discard but two Lightsaber upgrades (SOR_137 Fallen Lightsaber, SOR_054
#// Jedi Lightsaber), the Force-unit half auto-skips and only the Lightsaber choice prompts. Both lightsabers
#// are selectable; P1 returns one, the other stays in discard alongside the LOF_240 event.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:SOR_137,SOR_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_Lightsaber
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# OnlyLightsaberAvailable_Return
#// LOF_240 — continuation: P1 returns SOR_137. Hand holds the returned Lightsaber (1); discard keeps the
#// non-returned SOR_054 plus the spent LOF_240 event (2).

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:SOR_137,SOR_054}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# OnlyForceUnitAvailable
#// LOF_240 — with NO Lightsaber in discard but two Force units (SOR_131 Fifth Brother, LOF_145 Jedi Knight),
#// the Lightsaber half auto-skips and only the Force-unit choice prompts. Both are selectable.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:SOR_131,LOF_145}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_Force_unit
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1

---

# OnlyForceUnitAvailable_Return
#// LOF_240 — continuation: P1 returns SOR_131. Hand holds the returned Force unit (1); discard keeps the
#// non-returned LOF_145 plus the spent LOF_240 event (2).

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:SOR_131,LOF_145}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# NonForceUnitNotSelectable
#// LOF_240 — a non-Force unit is never a valid Force-unit target. Discard holds LOF_050 (Force unit),
#// SOR_237 Alliance X-Wing (non-Force unit) and SOR_137 (Lightsaber). The Force-unit prompt offers ONLY
#// LOF_050 (myDiscard-0); the Alliance X-Wing is not selectable.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:LOF_050,SOR_237,SOR_137}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_Force_unit
P1SELECTABLEEXACT:myDiscard-0

---

# NoValidTargets_StillPlayable
#// LOF_240 — the ability is a "you may" with no valid targets (discard holds only SOR_232 AT-ST, neither a
#// Force unit nor a Lightsaber). The event is still playable: it resolves with no decision and goes to
#// discard, leaving AT-ST + LOF_240 (2).

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:SOR_232}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:2
