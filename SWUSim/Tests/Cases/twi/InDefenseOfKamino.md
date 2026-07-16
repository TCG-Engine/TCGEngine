# GrantsRestore2
#// TWI_129 In Defense of Kamino (Event, cost 4, Command, Republic) — "For this phase, each friendly
#// Republic unit gains Restore 2 and: 'When Defeated: Create a Clone Trooper token.'" After playing it, the
#// Republic unit TWI_065 gains Restore 2; attacking the enemy base heals 2 from P1's base (5 → 3).

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;myBaseDamage:5;handCardIds:TWI_129}
P1OnlyActions: true
WithP1GroundArena: TWI_065:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_065
P1GROUNDARENAUNIT:0:HASKEYWORD:Restore
P1BASEDMG:3

---

# WhenDefeated_Clone
#// TWI_129 In Defense of Kamino — the granted "When Defeated: Create a Clone Trooper token" fires when a
#// marked Republic unit dies. TWI_109 (3/3 Republic) attacks SOR_046 (3/7) and dies to the 3 counter; its
#// granted When Defeated creates a Clone Trooper (TWI_T02) in its place.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_129}
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENAUNIT:0:DAMAGE:3
