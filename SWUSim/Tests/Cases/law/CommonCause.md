# BuffPerAspect
#// LAW_167 Common Cause (Command event, cost 2) — "Give a unit +1/+1 for this phase for each different
#// aspect among units you control." P1 controls SOR_095 (Command,Heroism) + SOR_225 (Villainy) = 3
#// distinct aspects {Command,Heroism,Villainy} -> chosen SOR_095 gets +3/+3 (3/3 -> 6/6).

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# SingleAspect
#// LAW_167 Common Cause — +1/+1 per DIFFERENT aspect among units you control. P1 controls only
#// SOR_164 Wampa (Aggression) = 1 aspect, so the chosen Wampa gets +1/+1 (4/5 -> 5/6).

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SHD_029:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:6

---

# NoUnitsControlled
#// LAW_167 Common Cause — with NO units controlled, the buff is +0/+0. The lone enemy SOR_164 Wampa
#// auto-targets and stays at its base 4/5.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5

---

# NoAspectUnitControlled
#// LAW_167 Common Cause — a controlled unit with NO aspects (SOR_247 Underworld Thug) contributes no
#// aspect, so the buff is +0/+0. Target the enemy SOR_164 Wampa; it stays 4/5.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_247:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:5

---

# OnlyThisPhase
#// LAW_167 Common Cause — the +1/+1 (1 aspect, Aggression Wampa) lasts only for THIS phase. After the
#// action phase ends and the next one begins, Wampa is back to its base 4/5.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1Deck: SOR_095
WithP1Deck: SOR_237
WithP2Deck: SOR_095
WithP2Deck: SOR_237
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5

---

# BuffPerAspect_SurvivesTheRequestBoundary
#// LAW_167 Common Cause — request-boundary guard. The per-aspect amount is computed while the event resolves
#// and must survive to the target answer, which in production arrives in a fresh process. Same flow as
#// BuffPerAspect (3 distinct aspects across SOR_095 + SOR_225) with a serialize round-trip inserted before the
#// choose; the pending decision is real (MZCHOOSE over myGroundArena-0 & mySpaceArena-0). SOR_095 still ends
#// at 6/6, so the +3/+3 amount was not lost across serialization.

## GIVEN
CommonSetup: ggw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: LAW_167

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
