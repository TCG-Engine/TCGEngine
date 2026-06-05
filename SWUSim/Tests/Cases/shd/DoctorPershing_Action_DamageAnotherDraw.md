# SHD_028 Doctor Pershing (0/5) — Action [Exhaust, deal 1 damage to a friendly unit]: Draw
# a card. The additional cost (deal 1 to a friendly) is interactive: with two friendly
# units (Pershing + Battlefield Marine) the player chooses which takes the damage. Here
# the Marine is chosen → it takes 1 damage, Pershing is exhausted, and P1 draws 1 card.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SHD_028:1:0    # Doctor Pershing (ready) — index 0
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine — index 1 (chosen to take the damage)
WithP1Deck: SOR_095
WithP1Deck: SOR_095               # 2 cards to draw from

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1    # deal the 1 cost-damage to the Marine

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:1
P1HANDCOUNT:1
P1DECKCOUNT:1
