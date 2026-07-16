# DefeatsLowHpUnit
#// SOR_077 Takedown (Event, cost 4) — "Defeat a unit with 5 or less remaining HP."
#// P2 has Battlefield Marine (SOR_095, 3/3 → 3 remaining HP, targetable) and Consular
#// Security Force (SOR_046, 3/7 → 7 remaining HP, NOT targetable). Only the Marine
#// qualifies, so it's the sole target → auto-defeated; SOR_046 is left untouched.

## GIVEN
CommonSetup: bbk/bbk/{myResources:4;handCardIds:SOR_077}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0    # remaining HP 3 ≤ 5 → targetable, index 0
WithP2GroundArena: SOR_046:1:0    # remaining HP 7 > 5 → not targetable, index 1

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
