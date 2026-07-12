# TWI_149 Low Altitude Gunship (Unit 6/5, Ground, cost 6, Aggression/Heroism, Republic/Vehicle/Transport)
# — Overwhelm + "When Played: Choose an enemy unit. Deal 1 damage to it for each friendly Republic unit."
# P1 controls TWI_065 (Republic) and SOR_095 (Rebel, does NOT count). Playing the gunship (itself Republic)
# makes 2 friendly Republic units → 2 damage to the lone enemy (SOR_046). Base r + leader rw cover the pips.

## GIVEN
CommonSetup: rrw/bbw/{myResources:6;handCardIds:TWI_149}
P1OnlyActions: true
WithP1GroundArena: TWI_065:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:2:CARDID:TWI_149
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
