# WhenPlayed_LeastHP_SelfExp
#// SEC_244 Darth Nihilus (Ground, 6/6, Villainy, cost 7) — When Played/On Attack: deal 3 to the OTHER
#//   unit with the least remaining HP; if it's a non-Vehicle unit, give an Experience token to this unit.
#//   Lowest is SOR_128 (1 HP, non-Vehicle) → defeated; Nihilus gets +1 Experience → 7 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENACOUNT:1
P1NODECISION

---

# WhenPlayed_TieVehicle_ChooseNoExp
#// SEC_244 Darth Nihilus — When Played: deal 3 to the OTHER unit with the least remaining HP; give Experience
#//   only if that unit is non-Vehicle. Friendly wampa (SOR_164, 5 HP) and enemy AT-ST (SOR_232, 7 HP with 2
#//   damage = 5 remaining) tie for least → choose. Pick AT-ST (a Vehicle): takes 3 more (total 5), and Nihilus
#//   gets NO Experience → stays 6 power.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:2
WithP1Hand: SEC_244

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:1:CARDID:SEC_244
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# WhenPlayed_MustChooseOwnWhenOppEmpty
#// SEC_244 Darth Nihilus — the deal-3 is mandatory. With no enemy units, the only OTHER unit is P1's own AT-ST
#//   (SOR_232 with 2 damage = 5 remaining, less than Nihilus' 6) → forced single target, auto-resolves. AT-ST
#//   takes 3 more (total 5); it is a Vehicle so Nihilus gets no Experience.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:2
WithP1Hand: SEC_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:1:CARDID:SEC_244
P1GROUNDARENAUNIT:1:POWER:6
P1NODECISION

---

# WhenPlayed_Alone_NoEffect
#// SEC_244 Darth Nihilus — with no OTHER unit in play, the When Played ability has no target and does nothing
#//   (it never targets Nihilus himself). Turn passes to P2.

## GIVEN
CommonSetup: rrk/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_244

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# OnAttack_LeastHP_SelfExp
#// SEC_244 Darth Nihilus — On Attack: deal 3 to the OTHER unit with the least remaining HP; Experience if it's
#//   non-Vehicle. Nihilus attacks the base; among others the enemy Scout Bike Pursuer (SOR_032, 4 HP) is the
#//   unique least → auto-target. It takes 3 (survives, non-Vehicle) → Nihilus gains an Experience → 7 power.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_244:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_032:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:7

---

# OnAttack_TieVehicle_ChooseNoExp
#// SEC_244 Darth Nihilus — On Attack tie: friendly wampa (SOR_164, 5 HP) and enemy AT-ST (SOR_232, 5 remaining
#//   after 2 damage) tie for least → choose AT-ST (Vehicle). It takes 3 more (total 5); no Experience → 6 power.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_244:1:0
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_232:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:6

---

# OnAttack_MustChooseOwnWhenOppEmpty
#// SEC_244 Darth Nihilus — On Attack with no enemy units: the only OTHER unit is P1's own AT-ST (SOR_232 with
#//   2 damage = 5 remaining) → forced single target. It takes 3 more (total 5); Vehicle so no Experience.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_244:1:0
WithP1GroundArena: SOR_232:1:2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:5
P1GROUNDARENAUNIT:0:CARDID:SEC_244
P1GROUNDARENAUNIT:0:POWER:6

---

# OnAttack_Alone_NoEffect
#// SEC_244 Darth Nihilus — On Attack with no OTHER unit in play: the ability has no target and does nothing;
#//   Nihilus deals his 6 combat damage to the base.

## GIVEN
CommonSetup: rrk/grw
WithActivePlayer: 1
WithP1GroundArena: SEC_244:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:6
P1NODECISION
