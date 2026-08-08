# OnAttack_CreatesForce
#// LOF_129 Acolyte of the Beyond — "On Attack/When Defeated: The Force is with you." On Attack half: the
#// 2/3 Acolyte attacks P2's base → P1 gains the Force.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:2

---

# WhenDefeated_CreatesForce
#// LOF_129 Acolyte of the Beyond — When Defeated half: the 2/3 Acolyte attacks a 3/3 unit and dies to the
#// 3 counter-damage. Its When Defeated triggers → its controller (P1) gains the Force.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_129:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:0

---

# WhenDefeated_ByNoGlory_OpponentGetsForce
#// LOF_129 Acolyte of the Beyond — "When Defeated: The Force is with you (create YOUR Force token)" gives the
#// Force to whoever CONTROLS the Acolyte at the moment of defeat. P2 plays No Glory, Only Results (JTL_043,
#// "Take control of a non-leader unit, then defeat it") on P1's Acolyte → P2 controls it, defeats it, and the
#// When Defeated token goes to P2. (Intended: "should allow the opponent to create a Force token when defeated by
#// No Glory Only Results".)

## GIVEN
CommonSetup: rrk/bbk/{myResources:2;theirResources:5}
SkipPreGame: true
WithP1GroundArena: LOF_129:1:0
WithP2Hand: JTL_043
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true

## WHEN
- P2>PlayHand:0

## EXPECT
P2HASFORCE
P1GROUNDARENACOUNT:0
