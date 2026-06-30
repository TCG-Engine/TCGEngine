# SOR_135 — split 6 as 1 damage onto each of six 1-HP enemy units (3 ground + 3 space), clearing
# both arenas. The strongest simultaneity / index-safety guard: all six lethal hits are applied at
# once, so every unit is defeated regardless of order — a deal-then-cleanup-by-mzID implementation
# would stale later indices after the first defeat and leave units alive. All 6 damage assigned.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0    # 3/1 — 1 HP
WithP2GroundArena: SOR_128:1:0    # 3/1 — 1 HP
WithP2GroundArena: SOR_128:1:0    # 3/1 — 1 HP
WithP2SpaceArena: SOR_225:1:0     # 2/1 — 1 HP
WithP2SpaceArena: SOR_225:1:0     # 2/1 — 1 HP
WithP2SpaceArena: SOR_225:1:0     # 2/1 — 1 HP

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:1,theirGroundArena-2:1,theirSpaceArena-0:1,theirSpaceArena-1:1,theirSpaceArena-2:1

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_135
