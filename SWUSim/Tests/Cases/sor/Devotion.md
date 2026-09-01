# GrantsRestore
#// SOR_070 Devotion grants Restore 2 to its host (upgrade keyword-grant guard)
#// P1 has a vanilla Battlefield Marine (SOR_095, 3/3, no innate Restore) with
#// Devotion (SOR_070, +1/+1) attached → it gains Restore 2. When it attacks, heal 2
#// from its controller's base. P1 base starts at 3 damage → heals to 1. The host's
#// 3+1=4 power hits P2's base for 4.
#// (Contrast: without Devotion P1's base would stay at 3 and P2 base take 3 —
#// encoded as NoDevotion_HostDoesNotHeal.)
#// COVERAGE: offer=PlayedFromHand_AnyUnitIsALegalHost (the attach pool left PENDING and asserted
#//           exactly — Devotion prints no host restriction, so per CR 2.e friendly ground, friendly
#//           space and enemy units are all legal) · decline=N/A (neither clause carries a "you may":
#//           the grant is continuous and Restore fires automatically on every attack) ·
#//           boundary=GrantsRestore (base at 3 → 2 healed, 1 left) + HealClampedAtTheDamagePresent
#//           (base at 1 → only 1 healed, clamped at 0) · control=OwnerIsNotController_HealsThe-
#//           ControllersBase (host owned by P2 but controlled by P1 heals P1's base) +
#//           AttachedToAnEnemyUnit_HealsTheEnemyBase (the same rule read from the other seat) ·
#//           reqboundary=PlayedFromHand_AnyUnitIsALegalHost (the attach decision is still pending
#//           at the end of the play request; AttachedToAnEnemyUnit_HealsTheEnemyBase then reads a
#//           subcard-carrying unit back out of a rebuilt gamestate on the opponent's action)

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), ready
WithP1GroundArenaUpgrade: 0:SOR_070   # Devotion → host gains Restore 2

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:4

---

# NoDevotion_HostDoesNotHeal
#// Intended: the NEGATIVE that proves the grant is load-bearing. Identical board to GrantsRestore but
#// with no Devotion attached — the vanilla Battlefield Marine has no printed Restore, so P1's base
#// stays at 3 damage and the unbuffed 3 power (not 4) hits P2's base.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0    # Battlefield Marine (3/3), no upgrade

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# HostAttacksAUnit_StillHeals
#// Intended: Restore reads "when this unit ATTACKS", with no restriction on WHAT it attacks — the
#// heal fires on a unit attack just as it does on a base attack. The Devotion-carrying Marine (4/4)
#// attacks a Consular Security Force (3/7): P1's base heals 3 → 1, the defender takes 4, the Marine
#// takes 3, and P2's base takes nothing.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_070
WithP2GroundArena: SOR_046:1:0    # Consular Security Force (3/7) — survives

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# HostDefends_NoHeal
#// Intended: Restore is an ATTACK trigger, not a combat trigger — being attacked heals nothing. P2's
#// Battlefield Marine attacks the Devotion-carrying Marine; P1's base stays at 3 damage. The
#// exchange itself confirms the +1/+1 half is live: P1's host is 4/4 (survives 3 damage) and deals
#// 4, defeating the 3-HP attacker.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_070
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# HealClampedAtTheDamagePresent
#// Intended: "heal 2 damage" removes AT MOST what is there — it never drives the base below 0 and
#// never becomes a surplus. Boundary partner of GrantsRestore (3 damage → 2 healed → 1 left): here
#// the base carries 1 damage, so only 1 is healed and the base ends at 0.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:1}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_070

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2BASEDMG:4

---

# StacksWithPrintedRestore
#// Intended: Devotion GRANTS Restore 2, so a host that already prints Restore N heals N+2 — the two
#// sources add rather than the larger one replacing the smaller. Regional Sympathizers (SOR_243,
#// 3/4, printed Restore 2) carrying Devotion heals 4 from a base sitting at 5 damage → 1 left; a
#// non-stacking implementation would leave 3. The +1/+1 puts 4 on P2's base.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:5}
WithP1GroundArena: SOR_243:1:0    # Regional Sympathizers (3/4, Restore 2)
WithP1GroundArenaUpgrade: 0:SOR_070

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:4

---

# OwnerIsNotController_HealsTheControllersBase
#// Intended: the reminder text says "heal 2 damage from ITS CONTROLLER'S base", so on a unit whose
#// owner and controller differ the heal follows the CONTROLLER. P1 controls a Battlefield Marine
#// OWNED by P2 (the end state after a take-control effect) and attaches Devotion to it. Attacking
#// P2's base heals P1's base 3 → 1 and leaves P2's base at its seeded 3 plus the 4 combat damage.
#// A controller/owner mix-up would heal P2's base instead, leaving it at 3 + 4 - 2 = 5.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3;theirBaseDamage:3}
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArenaUpgrade: 0:SOR_070

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:7
P1GROUNDARENAUNIT:0:POWER:4

---

# PlayedFromHand_AnyUnitIsALegalHost
#// Intended: Devotion prints no host restriction, so per CR 2.e it may be attached to ANY unit in
#// play — friendly or enemy, either arena. Three legal hosts keep the pick interactive; the decision
#// is left PENDING so the exact pool can be inspected (the resolution is a separate section).

## GIVEN
CommonSetup: bbw/grw/{myResources:3;handCardIds:SOR_070}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0

---

# AttachedToAnEnemyUnit_HealsTheEnemyBase
#// Intended: the resolution half of PlayedFromHand_AnyUnitIsALegalHost, and the sharpest reading of
#// "its CONTROLLER'S base" — a Devotion sitting on an ENEMY unit heals the ENEMY's base when that
#// unit attacks. P2's Battlefield Marine carries Devotion (4/4) and attacks P1's base: P1's base
#// takes 4 (3 seeded + 4 = 7) while P2's base heals 3 → 1.

## GIVEN
CommonSetup: ggw/grw/{myBaseDamage:3;theirBaseDamage:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_070

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:7
P2BASEDMG:1
P2GROUNDARENAUNIT:0:POWER:4
