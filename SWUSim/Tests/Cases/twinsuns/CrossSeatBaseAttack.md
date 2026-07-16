# P3AttacksACrossSeatBase
#// Twin Suns: a non-1/2 seat (P3) attacking an opponent's BASE must resolve. Regression for the
#// GetOpponent()-returns-null fatal in CollectCombatStep1Triggers (TS26_078 Barriss fires on ANY
#// attack; TS26_073 Moralo on a base attack) — GetOpponent only knew seats 1/2, so a P3 attack fatal'd
#// with "_SWUCountUnitsWithCardID(): $player must be int, null given". Fixed by iterating OpponentsOf
#// (Barriss) and resolving the base owner via SWUMzOwner (Moralo). SOR_032 has power 1 → P2 base +1.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 3
WithInitiativePlayer: 3
WithP3GroundArena: [SOR_032:1:0]
WithP2GroundArena: [SOR_034:1:0]
WithP2Base: SOR_024:0

## WHEN
- P3>AttackGroundArena:0:P2B

## EXPECT
P2BASEDMG:1
