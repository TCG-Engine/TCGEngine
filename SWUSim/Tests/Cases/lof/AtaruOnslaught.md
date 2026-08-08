# ReadyForceUnit
#// LOF_174 Ataru Onslaught — Ready a Force unit with 4 or less power. The exhausted LOF_055 (Force, power 2)
#// is readied.

## GIVEN
CommonSetup: rrw/ggk/{myResources:2;handCardIds:LOF_174}
P1OnlyActions: true
WithP1GroundArena: LOF_055:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# NoExhaustedTargets_AutoDiscard
#// LOF_174 Ataru Onslaught — if there are no exhausted Force units (with 4 or less power) to ready, target
#// selection is skipped and the event just resolves to the discard. Here Dume (LOF_055, Force 2) is READY,
#// so it is not a valid target; the card discards with no prompt. Intended: "should skip target
#// selection if there are no exhausted targets".

## GIVEN
CommonSetup: rrw/ggk/{myResources:2;handCardIds:LOF_174}
P1OnlyActions: true
WithP1GroundArena: LOF_055:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# OnlyValidForceTargetReadied
#// LOF_174 Ataru Onslaught — the ready target must be an EXHAUSTED Force unit with 4 or less power. P1 has
#// three exhausted units: Dume (LOF_055, Force 2 — valid), Plo Koon (LOF_050, Force 6 — excluded by power),
#// and Imperial Dark Trooper (SEC_080, non-Force — excluded). Only Dume is a legal target, so it auto-resolves
#// and is readied while the other two stay exhausted. Intended: "should ready a Force unit with 4 or less
#// power" (Savage/Mace excluded for >4 power, non-Force excluded).

## GIVEN
CommonSetup: rrw/ggk/{myResources:2;handCardIds:LOF_174}
P1OnlyActions: true
WithP1GroundArena: LOF_055:0:0
WithP1GroundArena: LOF_050:0:0
WithP1GroundArena: SEC_080:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:EXHAUSTED
