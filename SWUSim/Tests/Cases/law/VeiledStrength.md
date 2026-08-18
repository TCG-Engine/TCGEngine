# GrantsGrit
#// LAW_128 Veiled Strength (Upgrade, +0/+0) — "Attached unit gains Grit." SEC_080 (3/3) with 2 damage
#// and Veiled Strength gains Grit (+1/+0 per damage) → power 3+2 = 5. Without the grant it would be 3.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1GroundArenaUpgrade: 0:LAW_128

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5

---

# AttachOffer_NonLeaderBothSidesLeaderExcluded
#// "Attach to a non-leader unit" — the pool spans BOTH sides and BOTH arenas but excludes leader
#// units: the friendly marine and the enemy space A-Wing are offered; the deployed friendly leader is
#// not. Offer asserted while pending.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3; myLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_128

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0

---

# GritScalesWithTheDamageOnTheHost
#// LAW_128 Veiled Strength — Grit is "+1/+0 for each damage on this unit", so the grant is only really
#// tested by moving the damage. The SEC_080 host that reads 5 power at 2 damage (GrantsGrit) reads its
#// printed 3 at ZERO damage, while a 3-power SOR_046 carrying 3 damage reads 6. A flat +2 would satisfy
#// GrantsGrit on its own; only varying the damage separates the two.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_128
WithP1GroundArena: SOR_046:1:3
WithP1GroundArenaUpgrade: 1:LAW_128

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:POWER:6

---

# UpgradeRemoved_GritIsGone
#// LAW_128 Veiled Strength — Grit is granted BY the upgrade, so defeating the upgrade takes it away and
#// the damaged host drops back to its printed power. P1 plays SOR_251 Confiscate on its own Veiled
#// Strength: the 2-damage SEC_080 goes from 5 power back to 3.

## GIVEN
CommonSetup: bbk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1GroundArenaUpgrade: 0:LAW_128
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
