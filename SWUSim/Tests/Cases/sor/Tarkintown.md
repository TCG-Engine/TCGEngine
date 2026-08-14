# Deals3ToDamagedUnit
#// SOR_025 Tarkintown (Base) — "Epic Action: Deal 3 damage to a damaged non-leader
#// unit." P1's base is Tarkintown. P2 has a damaged Consular Security Force (SOR_046,
#// 3/7, 2 damage → targetable) and an undamaged Battlefield Marine (SOR_095, 0 damage
#// → not targetable). The damaged unit is the sole target → auto-takes 3 (2+3 = 5);
#// the undamaged Marine is untouched.
#// COVERAGE: offer=Offer_DamagedNonLeadersOnly_LeaderAndUndamagedExcluded (pending
#//           SELECTABLEEXACT: damaged non-leaders only; a damaged deployed leader is excluded) ·
#//           decline=NoValidTarget_StillUsable_EpicSpent (empty pool: the epic can still be
#//           spent; there is no "you may" on the damage itself) · control=N/A (a base never
#//           changes control) · boundary=EpicStaysSpentAcrossRegroup (once per game — the spent
#//           flag survives the regroup) · reqboundary=EpicStaysSpentAcrossRegroup (the flag is
#//           read back after multiple request boundaries)

## GIVEN
CommonSetup: rrw/grw/{
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:2    # damaged (2) → targetable, index 0
WithP2GroundArena: SOR_095:1:0    # undamaged → not targetable, index 1

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:0
P1BASE:EPICUSED

---

# Offer_DamagedNonLeadersOnly_LeaderAndUndamagedExcluded
#// SOR_025 Tarkintown — Intended: the pool is "a damaged NON-LEADER unit" — a damaged DEPLOYED
#// LEADER is not a legal target, nor is any undamaged unit. P2 has two damaged non-leaders
#// (idx 0-1), an undamaged trooper (idx 2), and a damaged deployed Darth Vader leader (idx 3).
#// The pick is left PENDING: exactly the two damaged non-leaders.

## GIVEN
CommonSetup: rrw/grw/{
  myBase:SOR_025;
  theirLeader:SOR_010:1:1:0:1:3
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:2
WithP2GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P2GROUNDARENAUNIT:3:ISLEADERUNIT

---

# NoValidTarget_StillUsable_EpicSpent
#// SOR_025 Tarkintown — Intended: the Epic Action can be spent even with NO valid target on the
#// board (undamaged units and a damaged deployed leader only): nothing takes damage and the epic
#// action is consumed.

## GIVEN
CommonSetup: rrw/grw/{
  myBase:SOR_025;
  theirLeader:SOR_010:1:1:0:1
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASE:EPICUSED
P1NODECISION

---

# EpicStaysSpentAcrossRegroup
#// SOR_025 Tarkintown — Intended: the Epic Action is once per GAME, not per round — after the
#// use, crossing the regroup into the next action phase leaves it spent. P1 deals 3 to the
#// damaged Security Force (2+3=5), the round is passed out, and in the new action phase the
#// base is still EPICUSED and the target took no further damage.

## GIVEN
CommonSetup: rrw/grw/{
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046]
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>UseBaseAbility
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
PHASE:MAIN
P2GROUNDARENAUNIT:0:DAMAGE:5
P1BASE:EPICUSED
