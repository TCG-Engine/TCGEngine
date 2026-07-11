# SHD_172 Krayt Dragon — CR 7.6.10 ordering with a simultaneous trigger from the OTHER player. P1 controls
# Cad Bane (SHD_014, undeployed) and plays SOR_247 (Underworld, printed cost 2). P1's Cad Bane ("when you
# play an Underworld card") AND P2's Krayt ("when an opponent plays a card") both trigger. As the active
# player, P1 first answers "Resolve Which Player First?" (YES = P1's own trigger first). Both resolve:
#   - Cad Bane: P1 exhausts it → P2 chooses their unit (Krayt) → Krayt takes 1.
#   - Krayt: P2 may deal SOR_247's printed cost (2) to P1's base.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SOR_247;myLeader:SHD_014}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_172
P2GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:2
