# OnAttack_NoReadyResources_Droid
#// TWI_183 Rush Clovis (Unit 3/5, Ground, cost 4, Separatist/Official) — Raid 2 + "On Attack: If the
#// defending player controls no ready resources, create a Battle Droid token." P2 has only exhausted
#// resources, so attacking the base creates a Battle Droid; combat deals 3 + Raid 2 = 5 to the base.

## GIVEN
CommonSetup: rrk/bbw/{theirResources:0:SOR_046:0}
P1OnlyActions: true
WithP1GroundArena: TWI_183:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P2BASEDMG:5

---

# TwinSuns_ChecksTheACTUALDefendingPlayersReadyResources
#// "On Attack: If THE DEFENDING PLAYER controls no ready resources, create a Battle Droid token."
#// Read as SWUResourceCount(OtherPlayer($player), true) — seat 2's resources, whoever was attacked.
#//
#// Seat 4 (the defender) holds 2 EXHAUSTED resources, so the condition is TRUE and a droid is created.
#// Seat 2 holds 5 READY ones, so the legacy read makes it FALSE and no droid appears. The two readings
#// differ in whether a token exists at all, which is why the arena COUNT is the assertion.
#// Clovis has Raid 2, so seat 4's base takes 3 + 2 = 5.

## GIVEN
CommonSetup: rrk/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: TWI_183:1:0
WithP2Resources: 5
WithP4Resources: 2:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:P4B

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:2
P4BASEDMG:5
