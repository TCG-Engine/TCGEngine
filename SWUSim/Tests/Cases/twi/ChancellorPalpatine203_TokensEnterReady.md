# TWI_203 Chancellor Palpatine (Unit, Ground) — "Each token unit you create enters play ready." Playing
# TWI_144 (creates a Clone Trooper) makes the Clone enter READY instead of exhausted.
## GIVEN
CommonSetup: rrw/bbw/{myResources:3;handCardIds:TWI_144}
P1OnlyActions: true
WithP1GroundArena: TWI_203:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:2:CARDID:TWI_T02
P1GROUNDARENAUNIT:2:READY
