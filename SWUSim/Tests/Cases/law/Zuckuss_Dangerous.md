# OnAttackDealPowerIfBountyHunter
#// LAW_064 Zuckuss (3/5, Saboteur) — On Attack: if you control another Bounty Hunter unit, you may deal
#// damage equal to this unit's power to a ground unit. P1 controls LAW_124 (Bounty Hunter); Zuckuss
#// attacks the base and deals 3 to the enemy SOR_046.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# MayPassAbility
#// LAW_064 Zuckuss — the On Attack damage is a "you may", so it can be declined. Zuckuss attacks the
#// base while controlling LAW_124 (a Bounty Hunter); pass the ability -> the enemy Wampa takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# NoOtherBountyHunter
#// LAW_064 Zuckuss — with NO other friendly Bounty Hunter, the On Attack ability does not fire. Zuckuss
#// attacks the base alone; the enemy Wampa takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# UpgradesIncreaseDamage
#// LAW_064 Zuckuss — the damage equals this unit's CURRENT power. With two Experience upgrades Zuckuss is
#// 5/7, so he deals 5 to the enemy AT-ST (6/7, survives).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# OpponentBountyHunterNotCounted
#// LAW_064 Zuckuss — only a unit YOU control counts as "another Bounty Hunter". Zuckuss attacks alone while
#// the opponent controls a Bounty Hunter (Greedo); the ability does not fire and Greedo takes no damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP2GroundArena: SOR_204:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_204
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OfferPool_AnyGroundUnitEitherSideIncludingSelf
#// LAW_064 Zuckuss — offer assertion for "you may deal damage equal to this unit's power to A GROUND
#// UNIT". Note the wording: the CONDITION is controller-scoped ("if you control another Bounty Hunter")
#// but the TARGET is not — "a ground unit" names no controller and carries no "another", so the pool is
#// every ground unit in play including Zuckuss himself and his own Bounty Hunter enabler. The only real
#// filter is the arena. Discriminating board — friendly Zuckuss (IN), friendly LAW_124 Industrious Team
#// (IN), enemy SOR_046 (IN), friendly SPACE SOR_178 (OUT) and enemy SPACE SEC_213 (OUT). A pool narrowed
#// to "enemy" (the intuitive mis-read, and the shape LAW_057/LAW_184 actually use) fails here. The pick
#// is left UNANSWERED so the pending pool can be read.
#// COVERAGE: offer=OfferPool_AnyGroundUnitEitherSideIncludingSelf (pending SELECTABLEEXACT; space units
#//           on both sides are the "out", both sides' ground plus the source itself are the "in") ·
#//           reqboundary=NOT COVERED (the power is baked into the continuation amount before the pick;
#//           no section forces a SimulateRequestBoundary across it) · control=OpponentBountyHunterNot
#//           Counted (the "another Bounty Hunter" gate is proven controller-scoped — an enemy Bounty
#//           Hunter does not enable it) · boundary pair=OnAttackDealPowerIfBountyHunter (gate met) vs
#//           NoOtherBountyHunter (gate unmet, no trigger), plus UpgradesIncreaseDamage for the
#//           current-power read · decline=MayPassAbility

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0
P1SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# SaboteurIgnoresSentinelAndPopsTheDefendersShield
#// LAW_064 Zuckuss — his printed SABOTEUR keyword has no section of its own: every existing section is
#// about the On Attack damage clause. Saboteur does two things when he attacks, and both are asserted
#// here. The enemy fields a SENTINEL (SOR_063 Cloud City Wing Guard) alongside a shielded SOR_046, and
#// Zuckuss attacks the SHIELDED unit — a target Sentinel would normally forbid. The Shield is defeated by
#// Saboteur rather than absorbing the hit, so the 3/7 defender takes the full 3 combat damage and lives to
#// be counted.
#// The On Attack damage clause is declined so the only damage on the board is combat damage.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_064:1:0
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:0
