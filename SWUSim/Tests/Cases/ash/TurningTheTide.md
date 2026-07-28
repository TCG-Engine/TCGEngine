# DamagePerFriendly
#// ASH_138 Turning the Tide (Event, cost 3) — Choose a unit. Deal 1 damage to it for each friendly unit.
#// P1 controls 3 units, so the chosen enemy SEC_080 (3/3) takes 3 damage and is defeated.
## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:ASH_138}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0

---

# DamageToFriendlyUnit
#// ASH_138 Turning the Tide — "Choose a unit" is not restricted to enemies; a friendly unit is a legal
#// target. P1 controls 3 friendly units, so the chosen friendly SOR_046 (3/7) takes 3 damage (and survives).
## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:ASH_138}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
