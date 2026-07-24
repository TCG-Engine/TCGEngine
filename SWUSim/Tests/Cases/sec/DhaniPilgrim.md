# WhenDefeated_HealBase
#// SEC_055 Dhani Pilgrim — When Defeated: heal 1 damage from your base. SEC_055 (1/3) attacks SOR_046
#//   (3/7) and dies to the counter; on defeat (within P1's own action) the base heals 1 (3 → 2).

## GIVEN
CommonSetup: bbk/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SEC_055:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2

---

# WhenPlayed_HealBase
#// SEC_055 Dhani Pilgrim (Ground, 1/3) — When Played: heal 1 damage from your base. Base starts at 3
#//   damage → 2 after playing SEC_055.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: SEC_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2

---

# WhenDefeated_HealsController_AfterControlChange
#// SEC_055 Dhani Pilgrim — the When Defeated "heal 1 from your base" heals the CONTROLLER's base, not the
#//   owner's. P2 owns Dhani; P1 plays JTL_043 No Glory Only Results (Vigilance/Villainy) to take control of
#//   Dhani and immediately defeat it. Because P1 controls it at the moment of defeat, P1's base heals 3 -> 2
#//   (P2's base is untouched). Lone enemy non-leader target auto-resolves, so no target answer is needed.

## GIVEN
CommonSetup: bbk/rrk/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP2GroundArena: SEC_055:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:2
