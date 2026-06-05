# SOR_135 — split that DEFEATS one enemy and damages another. Divided damage is dealt
# SIMULTANEOUSLY: 3 to theirGroundArena-0 (a 3/3, dies) and 3 to theirGroundArena-1 (a 3/7,
# survives) are applied at the same time, THEN defeats resolve. So the survivor must take its
# full 3 even though its co-target was defeated — the processor must apply all assigned damage
# before any defeat/reindex, not deal-then-cleanup by stale mzID (the index-shift trap).
# Full 6 is assigned (3+3), per "all damage must be assigned."

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # 3/3 — killed by the 3 assigned to idx 0
WithP2GroundArena: SOR_046:1:0    # 3/7 — must still take 3 after the reindex

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3,theirGroundArena-1:3

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
