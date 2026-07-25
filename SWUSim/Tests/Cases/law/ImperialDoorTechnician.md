# WhenDefeatedHealBase
#// LAW_097 Imperial Door Technician (2/2) — When Defeated: heal 2 damage from your base. Attacks SOR_046
#// (3/7) and dies; P1's base (damaged 2) heals to 0.

## GIVEN
CommonSetup: brk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:0

---

# WhenDefeatedHealsNewControllersBase
#// LAW_097 Imperial Door Technician — "When Defeated: Heal 2 damage from YOUR base." If an opponent
#// takes control of the technician (via No Glory, Only Results, JTL_043) and defeats it, the heal is
#// from the new controller's base. P2 steals & defeats P1's technician, healing P2's base 5 -> 3.

## GIVEN
CommonSetup: grk/bbk/{theirBaseDamage:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_097:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:3
