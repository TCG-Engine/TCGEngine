# OnAttack_NotMoreResources_NoBuff
#// SHD_151 Valiant Assault Ship — when the defending player does NOT control more resources (P1 has 5, P2
#// has 1), the +2 does not apply: the base attack deals the printed 3.

## GIVEN
CommonSetup: rrw/rrw/{myResources:5;theirResources:1}
P1OnlyActions: true
WithP1SpaceArena: SHD_151:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# OnAttack_OppMoreResources_Buff
#// SHD_151 Valiant Assault Ship (4-cost 3/4 space) — Saboteur + "On Attack: If the defending player
#// controls more resources than you, this unit gets +2/+0 for this attack." P2 controls 5 resources vs P1's
#// 1, so the ship gets +2 → 5 power → its base attack deals 5 (proves the +2).

## GIVEN
CommonSetup: rrw/rrw/{myResources:1;theirResources:5}
P1OnlyActions: true
WithP1SpaceArena: SHD_151:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# TwinSuns_ComparesTheACTUALDefendingPlayersResources
#// "On Attack: If THE DEFENDING PLAYER controls more resources than you, this unit gets +2/+0."
#// A determined seat, resolved with OtherPlayer() — which at four seats always answers seat 2 for
#// seat 1, so the comparison was made against a player not in the combat.
#//
#// The fixture makes the two readings disagree in BOTH directions: seat 4 (the real defender) has 6
#// resources vs seat 1's 3, so the bonus MUST apply; seat 2 (the legacy answer) has 0, so the legacy
#// code sees 0 > 3 = false and applies nothing. 3 power + 2 = 5 on seat 4's base; the legacy number is 3.

## GIVEN
CommonSetup: bbk/bbk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 3
WithP2Resources: 0
WithP4Resources: 6
WithP1SpaceArena: SHD_151:1:0

## WHEN
- P1>AttackSpaceArena:0:P4B

## EXPECT
SEATCOUNT:4
P4BASEDMG:5
P2BASEDMG:0
