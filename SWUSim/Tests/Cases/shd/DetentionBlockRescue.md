# Deal3_NoCaptive
#// SHD_180 Detention Block Rescue (3-cost event, Aggression) — "Deal 3 damage to a unit. If that unit is
#// guarding any captured cards, deal 6 damage instead." Against a unit guarding nothing (SOR_046), it deals 3.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_180
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deal6_Guarding
#// SHD_180 Detention Block Rescue — a unit guarding a captured card takes 6 instead of 3. P1's Discerning
#// Veteran (SHD_120, 4 HP) first captures SOR_128; then Detention Block Rescue hits the Veteran for 6 (it is
#// guarding a captive), defeating it (3 would not have — proving the 6).

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_180
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
