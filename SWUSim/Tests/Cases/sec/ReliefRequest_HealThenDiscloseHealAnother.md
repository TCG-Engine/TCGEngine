# SEC_074 Relief Request (Event, cost 2, Vigilance) — "Heal 3 damage from a unit. You may disclose
#   Vigilance → heal 3 damage from ANOTHER unit."
# Two damaged SOR_046 (3/7) friendly units (3 damage each). Play SEC_074 → heal idx0 (choose) → 0;
# disclose SEC_059 (Vigilance) → second heal auto-targets the only other damaged unit (idx1) → 0.

## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_046:1:3
WithP1Hand: SEC_074
WithP1Hand: SEC_059

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1HANDCOUNT:1
P1NODECISION
