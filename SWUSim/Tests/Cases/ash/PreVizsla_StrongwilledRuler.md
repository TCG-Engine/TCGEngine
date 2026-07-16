# BudgetDefeat_CreatesPerDefeat
#// ASH_053 Pre Vizsla (Ground, 6/6, cost 8) — When Played: defeat any number of non-leader units with a
#// COMBINED 6-or-less remaining HP; create a Mandalorian token for each one defeated. P2 has two 3/1
#// Stormtroopers (combined 2 HP). P1 defeats both (one at a time), then the loop ends (Pre Vizsla's own
#// 6 HP no longer fits the reduced budget) → 2 Mandalorian tokens created.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_053
