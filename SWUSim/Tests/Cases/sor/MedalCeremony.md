# NoRebelAttacked_Fizzles
#// COVERAGE: offer=RebelAttackers_GetExperience + NonAttackingRebel_Excluded +
#//           EnemyRebelAttacker_InPool_OfferAsserted (trait + attacked-this-phase filters; enemy
#//           Rebel attackers included since the 2026-08-14 fix — the text has no "friendly")
#//           · decline=ChooseNothing_NoTokens ("up to 3" includes zero) + PickFewerThanAvailable
#//           · boundary=RebelAttackers_GetExperience (3 attacks, non-Rebel excluded, damage
#//           pre-token) · control=EnemyRebelAttacker_* (cross-seat flag read: the SWU_ATTACKED flag
#//           lives on the attacker's controller's seat) · reqboundary=
#//           PickFewerThanAvailable (the multi-pick pends across the boundary before resolution)
#// SOR_245 Medal Ceremony — guard: no eligible target. Only a non-Rebel Imperial Trooper (SOR_128)
#// attacked, so the Rebel-attacked target list is empty → the event fizzles with no decision and no
#// token. The event still resolves into the discard pile.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# NonAttackingRebel_Excluded
#// SOR_245 Medal Ceremony — the "attacked this phase" filter. Two Rebel Troopers (SOR_046); only idx0
#// attacks. Medal Ceremony's target list is just idx0 (the attacker) — idx1 is a Rebel but did NOT
#// attack, so it's excluded and gets no token.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# RebelAttackers_GetExperience
#// SOR_245 Medal Ceremony (Event, cost 0, Heroism) — "Give an Experience token to each of up to 3
#// Rebel units that attacked this phase." Two Rebel Troopers (SOR_046, 3/7) and one non-Rebel Imperial
#// Trooper (SOR_128) all attack the base this phase. Medal Ceremony's target list is ONLY the two
#// Rebels that attacked — the Imperial (idx 2) attacked but is not a Rebel, so it's excluded. Choosing
#// both Rebels gives each an Experience token (+1/+1): idx0/idx1 → UPGRADECOUNT 1 and 4/8; idx2 → none.
#// Base damage (9 = 3+3+3) is dealt by the attacks BEFORE the tokens, so it reflects un-buffed power.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:2:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P2BASEDMG:9
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0

---

# PickFewerThanAvailable
#// SOR_245 Medal Ceremony — "up to 3" allows choosing FEWER than the eligible count. Both friendly
#// Rebels attacked the base (pool of two); P1 gives a token to only idx0: idx1 stays bare.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# ChooseNothing_NoTokens
#// SOR_245 Medal Ceremony — "up to 3" includes ZERO: declining the multi-pick gives no tokens to
#// anyone; the event still resolves to the discard.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# LeaderUnitRebelAttacker_Counts
#// SOR_245 Medal Ceremony — a deployed Rebel LEADER unit that attacked this phase is a legal
#// recipient. Deployed Sabine (SOR_014, Rebel, seats after the seeded trooper → index 1) attacks
#// the base alongside the Rebel trooper; both are picked and both get an Experience token.

## GIVEN
CommonSetup: byw/byw/{myResources:0; myLeader:SOR_014:1:1}
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:ISLEADERUNIT

---

# EnemyRebelAttacker_InPool_OfferAsserted
#// Candidate #6 fix guard (offer): the text has NO "friendly" — an ENEMY Rebel that attacked this
#// phase is a legal target too. Both Rebels attacked; the pool is exactly both, asserted pending.
#// Also exercises the flag-seat defect: the enemy attacker's SWU_ATTACKED flag lives on P2's seat,
#// so a caster-seat-only read misses it even with a widened pool.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# EnemyRebelAttacker_GetsExperience
#// Candidate #6 fix guard (resolution): choosing both the friendly and the ENEMY Rebel attacker
#// gives each an Experience token.

## GIVEN
CommonSetup: byw/byw/{myResources:0}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_245

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
