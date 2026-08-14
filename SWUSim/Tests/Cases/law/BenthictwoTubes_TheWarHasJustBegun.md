# OnAttackDealGround
#// LAW_057 Benthic "Two Tubes" (3/2) — On Attack: deal 1 damage to an enemy ground unit. Attacks the
#// base; deal 1 to the enemy SOR_046.
#// COVERAGE: offer=both branches of the When-Defeated base pool are exercised (WhenDefeatedDealBase enemy
#//           pick, WhenDefeatedDealFriendlyBase own pick, NGOR section new-controller pick); no pending
#//           SELECTABLE section · reqboundary=N/A (each choice is raised and resolved inside its own
#//           trigger; no state written before a decision is re-read after it) ·
#//           control=NGORDefeat_NewControllerMakesTheBaseChoice · boundary=OnAttackNoTargetsNoOp (empty
#//           pool no-op) vs OnAttackDealGround · decline=N/A (the On-Attack ping is MANDATORY per
#//           printed text — user ruling 2026-08-13; the When-Defeated base pick is likewise mandatory)

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# WhenDefeatedDealBase
#// LAW_057 Benthic "Two Tubes" (3/2) — When Defeated: deal 1 damage to a base. Benthic attacks SOR_046
#// (3/7) and dies to the counter (the MANDATORY On-Attack ping auto-resolves onto the lone SOR_046);
#// its When Defeated deals 1 to P2's base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# OnAttackNoTargetsNoOp
#// LAW_057 Benthic "Two Tubes" — On Attack: with NO enemy ground unit, the "deal 1 to an enemy ground unit"
#// ability does nothing (it never redirects to friendly units). Benthic attacks the base for 3; friendly
#// units (SOR_095 ground, SOR_178 space) stay undamaged.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_057:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENAUNIT:0:DAMAGE:0

---

# WhenDefeatedDealFriendlyBase
#// LAW_057 Benthic "Two Tubes" — When Defeated: deal 1 damage to a base; you may choose YOUR OWN base.
#// Benthic attacks SOR_046 (3/7; the mandatory On-Attack ping auto-resolves onto it) and dies to the
#// counter; its When Defeated deals 1 to P1's own base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_057:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:1
P2BASEDMG:0

---

# NGORDefeat_NewControllerMakesTheBaseChoice
#// LAW_057 Benthic "Two Tubes" — the When Defeated "deal 1 damage to a base" resolves for whoever
#// controls the unit at defeat. P2 plays No Glory, Only Results (JTL_043) to take control of P1's
#// Benthic and defeat it, so P2 (not P1, the owner) makes the base choice — and may pick P2's OWN base:
#// P2's base takes the 1, P1's base is untouched.

## GIVEN
CommonSetup: bbw/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_057:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1
P1BASEDMG:0
