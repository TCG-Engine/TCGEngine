# SHD_028 Doctor Pershing — the cost "deal 1 damage to a friendly unit" can always be paid
# because Pershing (0/5) is himself a friendly unit. With no other friendly units he is the
# sole target → auto-resolves: he takes 1 self-damage, is exhausted, and P1 still draws.
# (Mirrors the Count Dooku insight: the source is always its own valid target.)

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_028:1:0    # Doctor Pershing alone (ready) — index 0
WithP1Deck: SOR_095

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1HANDCOUNT:1
P1DECKCOUNT:0
