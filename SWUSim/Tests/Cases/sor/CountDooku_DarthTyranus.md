# DefeatsLowHpUnit
#// SOR_038 Count Dooku (5/4, Shielded) — When Played: you may defeat a unit with 4 or
#// less remaining HP. Dooku has TWO entry triggers (Shielded + this WhenPlayed), so the
#// player first orders them (EffectStack-0 = WhenPlayed), then answers YES. Dooku himself
#// (4 remaining HP) AND P2's Battlefield Marine (3 HP) both qualify, so it's a real choice
#// — the player picks the Marine (theirGroundArena-0) to defeat it.

## GIVEN
CommonSetup: bbk/bbk/{myResources:7;handCardIds:SOR_038}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
