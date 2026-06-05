# SOR_135 Emperor Palpatine (Unit, 6/6, Overwhelm) — When Played: deal 6 damage divided as you
# choose among enemy units. P1 plays Palpatine (cost 8, Aggression/Villainy) and splits 4 to an
# enemy GROUND unit + 2 to an enemy SPACE unit, proving the split spans both enemy arenas.
# Neither target dies (SOR_046 is 3/7, SOR_237 is 2/3). Overwhelm is auto-wired and not tested here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0    # 3/7 — takes 4, survives
WithP2SpaceArena: SOR_237:1:0     # 2/3 — takes 2, survives

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:4,theirSpaceArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2SPACEARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:CARDID:SOR_135
