# SHD_030 Death Trooper — the friendly-ground target is mandatory and Death Trooper itself always
# qualifies, so with no other friendly unit it auto-resolves onto itself (takes 2, survives 3 HP). With
# no enemy ground unit the enemy half fizzles cleanly (no crash, no dangling decision).

## GIVEN
CommonSetup: brk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_030

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_030
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1NODECISION
