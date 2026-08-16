# InspiringMentor_OnAttack_Exp
#// SHD_104 Inspiring Mentor — attached unit gains "On Attack: Give an Experience token to another
#// friendly unit." The host (SOR_046 + SHD_104 = 4 power) attacks the base; its On Attack gives an
#// Experience token to the only other friendly unit (SOR_095 3/3 → 4/4).
#// COVERAGE: offer=OnAttack_OfferExcludesTheHostItself ("ANOTHER friendly unit" — host out, enemy out) ·
#//           control=EnemyHost_TheHostsControllerResolvesTheGrant (the grant belongs to the HOST's
#//           controller, not the upgrade's owner — CR 2.e) +
#//           ControlChange_WhenDefeatedResolvesForTheNewController (control moves an instant before the
#//           host dies, and the token follows the new controller) · boundary pair=OnAttack_Exp (one
#//           other friendly unit → the token lands) paired with
#//           OnAttack_NoOtherFriendlyUnit_FizzlesCleanly (zero → clean fizzle, nothing on the host) ·
#//           dispatch=both granted paths are exercised: On Attack (OnAttack_Exp) and When Defeated
#//           (WhenDefeated_Exp, plus the cross-controller ControlChange section) ·
#//           decline=N/A (the granted ability is a mandatory "give", not a "you may") ·
#//           reqboundary=N/A (one decision — which friendly unit gets the token; nothing is carried
#//           between two requests)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:POWER:4

---

# InspiringMentor_WhenDefeated_Exp
#// SHD_104 Inspiring Mentor — the granted "When Defeated" half (also exercises On Attack in the same
#// combat). The host (SOR_046 + SHD_104 = 4/8, pre-damaged 5 → 3 effective HP) attacks a Wampa (SOR_164
#// 4/5): its On Attack gives SOR_095 one Experience (→4/4), then it deals 4 (Wampa survives), counters 4
#// → the host dies, and its When Defeated gives SOR_095 a SECOND Experience (→5/5). Power 5 confirms the
#// When Defeated half fired (On Attack alone would leave it at 4). SOR_095 is now the sole ground unit.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:5
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5

---

# InspiringMentor_OnAttack_OfferExcludesTheHostItself
#// SHD_104 — "give an Experience token to ANOTHER friendly unit": the attacking host is excluded from
#// its own pool. Two other friendly units are seated so the pick stays interactive and the pool is what
#// the end state exposes; the enemy Wampa is there to prove the pool is friendly-only. Decision left
#// PENDING — the resolution itself is covered by the sections above.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2

---

# InspiringMentor_OnAttack_NoOtherFriendlyUnit_FizzlesCleanly
#// SHD_104 — the empty-pool edge of "another friendly unit": the host is the only friendly unit on the
#// board, so the granted On Attack has nowhere to put the token. It must fizzle silently — no prompt
#// left hanging, no token on the host itself — while the attack resolves for the host's 3 + the
#// Mentor's +1/+1.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1NODECISION

---

# InspiringMentor_EnemyHost_TheHostsControllerResolvesTheGrant
#// SHD_104 — the Mentor grants the ability to the ATTACHED UNIT, so per CR 2.e it is the HOST's
#// controller who resolves it and "another friendly unit" is read from THEIR seat. Here the Mentor sits
#// on a P2 unit: when P2 attacks, the Experience token goes to P2's other unit (3/3 → 4/4), and P1 —
#// who would have been the beneficiary if the grant followed the upgrade's owner — gets nothing but the
#// 4 damage to their base.

## GIVEN
CommonSetup: bbw/bbw
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_104
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P2GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_T01
P1GROUNDARENACOUNT:0

---

# InspiringMentor_ControlChange_WhenDefeatedResolvesForTheNewController
#// SHD_104 — the granted When Defeated is read from whoever controls the host AT THE MOMENT IT DIES.
#// P2 plays No Glory, Only Results (JTL_043, "take control of a non-leader unit, then defeat it") on
#// P1's Mentor-wearing unit: control moves to P2 first, so when it is defeated an instant later the
#// Experience token goes to P2's own Battlefield Marine (3/3 → 4/4) rather than back to P1's board. The
#// host is the sole P1 unit, so P1's arena empties and its owner's discard takes the remains.

## GIVEN
CommonSetup: bbk/bbk/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Hand: JTL_043
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_104
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01

---

# InspiringMentor_AttachOffer_AnyNonVehicleUnitEitherSide
#// SHD_104 — "Attach to a non-Vehicle unit" names no controller, so per CR 2.e an ENEMY non-Vehicle is a
#// legal host just as a friendly one is (and the granted ability then belongs to that unit's controller).
#// The Vehicle in P1's own space arena is the negative: friendly is not enough, it must be a non-Vehicle.
#// Decision left PENDING so the pool itself is the assertion.

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_104
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
