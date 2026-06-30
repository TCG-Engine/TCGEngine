# IBH_065 Admiral Piett (reprint of IBH_060) — On Attack with NO Aggression unit controlled: no draw
#   (Piett is Vigilance/Villainy, not Aggression). Also confirms the duplicate is wired.

## GIVEN
CommonSetup: bbk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: IBH_065:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirBase-0

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1
P2BASEDMG:2
P1NODECISION
