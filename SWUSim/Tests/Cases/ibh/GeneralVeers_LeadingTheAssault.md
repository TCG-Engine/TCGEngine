# Reprint088
#// IBH_088 General Veers (reprint of IBH_068) — if Vigilance unit: deal 2 enemy base + heal 2 own base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_088
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1BASEDMG:1
P1NODECISION

---

# WhenPlayed_VigilanceControlled
#// IBH_068 General Veers (Ground, 3/6, Aggression/Villainy, cost 5) — When Played: if you control a
#//   Vigilance unit, deal 2 to an enemy base and heal 2 from your base. P1 controls SOR_063 (Vigilance);
#//   P1's base starts at 3 damage → heals to 1; enemy base takes 2.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_068
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1BASEDMG:1
P1NODECISION
