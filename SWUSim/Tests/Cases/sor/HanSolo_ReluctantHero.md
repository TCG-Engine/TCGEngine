# ShootFirst_DefeatsNoCounter
#// SOR_198 Han Solo (6/6) — "While attacking, this unit deals combat damage before the defender."
#// He attacks a 3/3: his 6 damage defeats it BEFORE it can strike back, so Han takes 0 counter-damage
#// (vs 3 with simultaneous combat).
#// COVERAGE: offer=Ambush_Offer_EnemyGroundUnitsOnly (pending SELECTABLEEXACT: exactly P2's two
#//           ground units — the enemy space unit and the enemy base excluded) ·
#//           decline=Ambush_Declined_NoAttack (Ambush is "he MAY"; the deal-first clause is a
#//           static combat rule with no branch to decline) · boundary pair=
#//           ShootFirst_ExactlyLethalHP_NoCounter (6 HP, exactly lethal, no counter) vs
#//           ShootFirst_SurvivorCountersNoBonus (7 HP, survives, counters for 3) — N vs N+1 on the
#//           defender's HP against Han's 6 power; plus Ambush_ReadiesAndAttacksEnemyUnit vs
#//           Ambush_NoEnemyGroundUnit_NoOffer (offer vs no-legal-target) ·
#//           reqboundary=Ambush_ReadiesAndAttacksEnemyUnit (the play, the Ambush YES and the
#//           attack-target pick are three serialized answers; the deal-first grant must survive
#//           from the play into the attack resolution) · control=N/A — neither clause reads a
#//           seat or a seat-owned zone: Ambush is resolved by whoever PLAYED the unit inside that
#//           play's own ceremony (it cannot outlive it onto a new controller), and "while
#//           attacking, this unit deals combat damage before the defender" is a property of the
#//           combat, evaluated from the unit object, so owner-vs-controller and who-resolves-it
#//           both read the same. Defending_NoFirstStrike_DamageIsSimultaneous is the standing
#//           proof that the clause follows the ATTACKING role, not a side

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ShootFirst_SurvivorCountersNoBonus
#// SOR_198 Han Solo — when the defender SURVIVES his first strike, it still deals its counter-damage.
#// Han (6/6) attacks a 3/7: it takes 6 (NOT 7 — his deal-first is the innate version with NO +1/+0,
#// unlike Shoot First the event), survives, and counters for 3.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Ambush_ReadiesAndAttacksEnemyUnit
#// SOR_198 Han Solo — Intended: the Ambush half. Units enter play exhausted, so without Ambush a
#// freshly-played Han could not attack at all this phase. Played for his full 7 (Cunning/Heroism,
#// on-aspect under a Cunning/Heroism leader), Ambush readies him and he attacks the lone enemy
#// Battlefield Marine. His OTHER clause rides along: he deals his 6 first, the 3/3 dies before it
#// can answer, and Han takes 0. The attack leaves him exhausted again, and P2's base is untouched
#// (Ambush attacks a UNIT, never the base).

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018;
  myResources:7;
  handCardIds:SOR_198
}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2BASEDMG:0

---

# Ambush_Declined_NoAttack
#// SOR_198 Han Solo — Intended: Ambush is "he MAY ready and attack". Declining leaves Han as any
#// other freshly-played unit: exhausted, undamaged, no attack made. The enemy Marine stays ready
#// and undamaged, which is the observable difference from Ambush_ReadiesAndAttacksEnemyUnit.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018;
  myResources:7;
  handCardIds:SOR_198
}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# Ambush_NoEnemyGroundUnit_NoOffer
#// SOR_198 Han Solo — Intended no-valid-target branch: Ambush attacks "an enemy UNIT", and an
#// attack is arena-bound, so an enemy SPACE unit is not reachable by a ground Han and the enemy
#// base is not a unit. With P2 fielding only a TIE fighter in space there is nothing Han could
#// attack, so no Ambush offer is raised at all (a pay/choose that could only fizzle is not
#// prompted). Han simply enters play exhausted.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018;
  myResources:7;
  handCardIds:SOR_198
}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY
P2BASEDMG:0

---

# Ambush_Offer_EnemyGroundUnitsOnly
#// SOR_198 Han Solo — Intended: the Ambush attack pool. Left PENDING after the YES so the offer
#// itself can be read: it must hold exactly P2's two GROUND units — not P2's space TIE fighter
#// (wrong arena), not P2's base ("an enemy unit"), and not Han himself.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018;
  myResources:7;
  handCardIds:SOR_198
}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_210:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Defending_NoFirstStrike_DamageIsSimultaneous
#// SOR_198 Han Solo — Intended: the gate is "WHILE ATTACKING". As the DEFENDER Han gets no such
#// priority, so combat damage is simultaneous per CR: P2's Battlefield Marine (3/3) attacks him,
#// Han's 6 kills it AND its 3 still lands on Han. If the deal-first clause leaked onto defence the
#// Marine would have died before dealing anything and Han would sit at 0 damage — that 3 vs 0 is
#// the negative that proves the gate is load-bearing.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018
}
SkipPreGame: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_198
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# ShootFirst_ExactlyLethalHP_NoCounter
#// SOR_198 Han Solo — Intended boundary on the defender's HP against Han's 6 power, the N side of
#// the N vs N+1 pair whose N+1 half is ShootFirst_SurvivorCountersNoBonus (7 HP, survives and
#// counters for 3). Here the defender has EXACTLY 6 HP (Vandor Range Troopers, 4/6): Han's 6
#// damage is precisely lethal, it is defeated in the deal-first step, and its 4 power never
#// resolves — Han takes 0.

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:SOR_018
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_198:1:0
WithP2GroundArena: LAW_098:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
