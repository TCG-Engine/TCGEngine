# DeclineDisclose_NoAmbush
#// SEC_109 Diplomatic Envoy — decline the disclose → the next unit does NOT gain Ambush.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_109
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# Disclose_NextUnitAmbush
#// SEC_109 Diplomatic Envoy (Space, 2/2, Command) — When Played: you may disclose Command → the next
#//   unit you play this phase gains Ambush for this phase.
#// Play SEC_109 → disclose SEC_080 (Command) → arm the "next unit gains Ambush" charge. Then play
#// SOR_095 → it enters with Ambush (HASKEYWORD:Ambush, the granted phase keyword).

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_109
WithP1Hand: SOR_095
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1SPACEARENAUNIT:0:CARDID:SEC_109

---

# Disclose_AmbushChargeExpiresNextPhase
#// SEC_109 Diplomatic Envoy — the armed "next unit gains Ambush" is "for this phase". If P1 discloses but
#//   does NOT play a unit before the phase ends, the charge is gone: a unit played on the NEXT action phase
#//   does not gain Ambush.
#// Play SEC_109 (space) → disclose SEC_080 (Command) to arm the charge → pass to the next action phase →
#// play SOR_095 → it enters WITHOUT Ambush.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_109
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
