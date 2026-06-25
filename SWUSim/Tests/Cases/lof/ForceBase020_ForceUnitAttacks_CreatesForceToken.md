# LOF_020 Nightsister Lair — same "When a friendly Force unit attacks: The Force is with you" trigger
# as the other common Force bases, on a different aspect (Vigilance). Proves the trigger is wired to the
# whole base set, not a single hard-coded card. Force unit LOF_112 attacks the base → P1 gains the Force.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:LOF_020;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_112:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:2
