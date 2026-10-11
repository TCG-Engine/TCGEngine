# Grant_ReturnUpgradeOnDefeat
#// SHD_001 Gar Saxon (deployed grant) — "Each friendly upgraded unit gains: When Defeated: You may return
#// an upgrade that was attached to this unit to its owner's hand." P1's SOR_128 wears SOR_069; on P2's
#// turn, P2's SOR_015 (4/7) attacks and defeats it. P1 (controlling Gar Saxon) gets SOR_069 back to hand.
#// (Resolved as a benefit-only auto-return so it works on the ENEMY's turn — a controller-side When-Defeated
#// trigger wouldn't drain there.)
#//
#// ⚠ `myLeaderDeployed:true` IS LOAD-BEARING — this section originally omitted it. The grant lives on the
#// DEPLOYED side only (the front side is just "+1/+0"), but the engine gated on "is your leader SHD_001"
#// with no deployed check, so the section passed with him still on his leader side and the bug and its
#// test were born together. Paired with FrontSide_NoReturn_TheGrantIsDEPLOYEDOnly, which is the identical
#// board WITHOUT the deploy: no gate can satisfy both, so the pair pins the side, not just the effect.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_001; myLeaderDeployed:true}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_015:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
# Deploying him puts Gar Saxon HIMSELF in the ground arena, so "SOR_128 died" is one unit left, not zero —
# and naming the survivor is what keeps this an assertion about SOR_128 rather than about a count.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_001
P1HANDCOUNT:1
P1HANDCARD:0:SOR_069

---

# NoLeader_NoReturn
#// SHD_001 Gar Saxon — the grant is gated on controlling Gar Saxon. Without him (default leader), a
#// defeated upgraded unit's upgrade simply goes to the discard: SOR_128 + SOR_069 both end in P1's discard,
#// nothing returns to hand.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_015:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# GarSaxon_UpgradedUnitsBuff
#// SHD_001 Gar Saxon (front passive) — "Each friendly upgraded unit gets +1/+0." An upgraded SOR_046
#// (3 base + SHD_072 which is +0/+0) gets +1 → power 4; an identical non-upgraded SOR_046 stays at 3.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_001}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3

---

# FrontSide_NoReturn_TheGrantIsDEPLOYEDOnly
#// ⚠ THE TWO SIDES OF SHD_001 DIFFER, AND ONLY ONE GRANTS THE RETURN:
#//   FRONT  : "Each friendly upgraded unit gets +1/+0."                      <- buff only
#//   DEPLOY : "Each friendly upgraded unit gets +1/+0 AND GAINS: 'When Defeated: You may return an
#//             upgrade that was attached to this unit to its owner's hand.'" <- buff + the grant
#// So with Gar Saxon still on his LEADER side the upgrade must go to the discard like any other.
#// Reported by a player: "Gar Saxon's on-deploy effect of returning upgrades to hand seems to work even
#// when he isn't deployed."
#//
#// Identical board to Grant_ReturnUpgradeOnDefeat — the ONLY difference is that this section does not
#// deploy him. That is deliberate: the two sections are a discriminating pair, so a gate that ignores
#// the deployed state cannot satisfy both.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_001}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_015:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
# Nothing returns: the unit AND its upgrade both land in the discard.
P1HANDCOUNT:0
P1DISCARDCOUNT:2
