# NoEnemyUnits_Fizzle
#// SOR_135 — When Played with no enemy units: the split fizzles (no decision queued, no damage),
#// and Palpatine still enters play. Absence guard for the empty-target early-return.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_135
P1NODECISION

---

# SplitAllOnOneUnit
#// SOR_135 — only one enemy unit on the board: all 6 must be assigned to it (overkill is legal).
#// A single 3/3 takes the full 6 and is defeated. Confirms the full pool can pile onto one target.

## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:SOR_135}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0    # 3/3 — takes all 6, defeated

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:6

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_135

---

# SplitClearsSixUnits
#// SOR_135 — split 6 as 1 damage onto each of six 1-HP enemy units (3 ground + 3 space), clearing
#// both arenas. The strongest simultaneity / index-safety guard: all six lethal hits are applied at
#// once, so every unit is defeated regardless of order — a deal-then-cleanup-by-mzID implementation
#// would stale later indices after the first defeat and leave units alive. All 6 damage assigned.

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

---

# SplitDamage
#// SOR_135 Emperor Palpatine (Unit, 6/6, Overwhelm) — When Played: deal 6 damage divided as you
#// choose among enemy units. P1 plays Palpatine (cost 8, Aggression/Villainy) and splits 4 to an
#// enemy GROUND unit + 2 to an enemy SPACE unit, proving the split spans both enemy arenas.
#// Neither target dies (SOR_046 is 3/7, SOR_237 is 2/3). Overwhelm is auto-wired and not tested here.

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

---

# SplitDefeatsAndDamages
#// SOR_135 — split that DEFEATS one enemy and damages another. Divided damage is dealt
#// SIMULTANEOUSLY: 3 to theirGroundArena-0 (a 3/3, dies) and 3 to theirGroundArena-1 (a 3/7,
#// survives) are applied at the same time, THEN defeats resolve. So the survivor must take its
#// full 3 even though its co-target was defeated — the processor must apply all assigned damage
#// before any defeat/reindex, not deal-then-cleanup by stale mzID (the index-shift trap).
#// Full 6 is assigned (3+3), per "all damage must be assigned."

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
