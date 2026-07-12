# TWI_042 Barriss Offee (Unit 1/1, Ground) — "Each friendly unit that was healed this phase gets
# +1/+0." Playing TWI_044 Kashyyyk Defender heals 2 from the damaged SOR_046 (marking it healed this
# phase); with Barriss in play, SOR_046 then gets +1/+0 → power 3 → 4. (SOR_095, never healed, stays 3.)

## GIVEN
CommonSetup: bbw/grw/{myResources:2;handCardIds:TWI_044}
P1OnlyActions: true
WithP1GroundArena: TWI_042:1:0
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:2:POWER:3
