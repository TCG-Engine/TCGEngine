# SHD_171 Covetous Rivals (6-cost 5/5 ground) — Grit + "When Played/On Attack: You may deal 2 damage to a
# unit with a Bounty." Two enemies are present — SHD_095 (a Bounty unit) and SOR_046 (no Bounty). Only the
# Bounty unit is offered; P1 deals 2 to it, leaving SOR_046 untouched (proves the Bounty filter).

## GIVEN
CommonSetup: rrk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_171
WithP2GroundArena: SHD_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:DAMAGE:0
