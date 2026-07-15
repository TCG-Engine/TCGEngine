# TS26_015 C-3P0 — Action [Exhaust]: deal damage equal to this unit's power (2) to another ground unit;
# only opponents may use. P1 plays C-3P0 → P2 takes control (enters exhausted, like any played unit). Both
# pass to the regroup; the ready phase readies C-3P0 under P2. In the next round P2 (the controller, an
# opponent of the owner) activates C-3P0 and deals 2 to P1's SOR_095, exhausting C-3P0 as the cost.
## GIVEN
CommonSetup: gbw/rrk/{handCardIds:TS26_015;myResources:6}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:CARDID:TS26_015
P2GROUNDARENAUNIT:0:EXHAUSTED
