# WhenDefeated_SacForBaseDamage
#// SEC_136 Arihnda Pryce (Ground, 4/4) — When Defeated: you may defeat another friendly unit; if you do,
#//   deal 4 to each enemy base. SEC_136 (idx1) attacks LAW_124 and dies → defeat SOR_095 → 4 to P2 base.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_136:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION

---

# WhenDefeated_DeclineSacrifice
#// SEC_136 Arihnda Pryce — the When Defeated is a "may": declining leaves the friendly SOR_095 alive
#//   and deals no base damage. Arihnda (idx1) attacks LAW_124 (4/7) and dies to the 4 counter-damage.

## GIVEN
CommonSetup: rrk/grw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_136:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2BASEDMG:0
P1NODECISION

---

# WhenDefeated_UnderEnemyControl_NGOR
#// SEC_136 Arihnda Pryce — when P2 takes control of Arihnda with No Glory, Only Results (JTL_043) and
#//   defeats her, the When Defeated resolves under P2's control: P2 must defeat another of THEIR OWN
#//   units (SOR_095) and deals 4 to each ENEMY base — i.e. P1's base, not P2's.

## GIVEN
CommonSetup: rrk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SEC_136:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Resources: 6
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1BASEDMG:4
