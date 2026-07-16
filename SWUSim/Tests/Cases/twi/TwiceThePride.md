# WhenPlayed_Deal2ToHost
#// TWI_155 Twice the Pride (Upgrade +4/+0, cost 2, Aggression/Aggression, Innate) — "When Played: Deal 2
#// damage to attached unit." Played onto the sole friendly unit (SOR_046 3/7), it auto-attaches (+4/+0)
#// and deals 2 to the host → SOR_046 ends at power 7, damage 2. Base r + leader rk cover both Aggression pips.

## GIVEN
CommonSetup: rrk/bbw/{myResources:2;handCardIds:TWI_155}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:POWER:7
