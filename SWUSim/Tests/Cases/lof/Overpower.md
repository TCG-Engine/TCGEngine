# BuffOverwhelm
#// LOF_126 Overpower — Give a unit +3/+3 and Overwhelm for this phase. SOR_046 (3/7) becomes 6/10 with
#// Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_126}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:10
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# BuffEnemyUnit
#// LOF_126 Overpower — "give a unit" is unrestricted; it can target an enemy unit. P1 plays Overpower on
#// the opponent's SOR_095 (3/3), buffing it to 6/6 with Overwhelm — an enemy ground unit gets +3/+3 and Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_126}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:6
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# BuffExpiresNextPhase
#// LOF_126 Overpower — the +3/+3 and Overwhelm last only "for this phase". P1 buffs SOR_046 (3/7 → 6/10
#// Overwhelm), then both players pass so the action phase ends and regroup runs turn-effect expiry. Next
#// phase the unit is back to a printed 3/7 with no Overwhelm.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:LOF_126}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
