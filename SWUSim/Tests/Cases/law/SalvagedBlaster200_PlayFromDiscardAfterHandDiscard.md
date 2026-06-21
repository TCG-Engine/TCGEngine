# LAW_200 Salvaged Blaster (+2/+0 upgrade, Aggression) — "Action: If this upgrade was discarded from your
# hand or deck this phase, play it from your discard pile (paying its cost). Attach to a non-Vehicle unit."
# P1's Pillage (SHD_181) forces P2 to discard 2 from hand; P2 discards LAW_200 (→ TPP this phase) and a
# filler. P2 then plays LAW_200 from its discard (cost 2) onto SEC_080 (3/3, non-Vehicle) → 5/3, attached.

## GIVEN
CommonSetup: rrk/rrk/{handCardIds:SHD_181;myResources:4;theirHandCardIds:LAW_200,SOR_095;theirResources:2}
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0
- P2>AnswerDecision:myHand-0
- P1>Pass
- P2>PlayFromDiscard:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:5
P2DISCARDCOUNT:1
P2RESAVAILABLE:0
