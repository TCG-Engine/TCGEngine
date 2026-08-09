# DefeatExpGiveExp
#// LOF_082 Vaneé — When Played: may defeat an Experience token on a friendly unit. If you do, give an
#// Experience token to a friendly unit. P1 defeats the Experience on SOR_095 and moves it to SOR_046.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_082}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# OnAttack_DefeatExpGiveToSelf
#// LOF_082 Vaneé — the ability is "When Played / On Attack". Here Vaneé is already in play and attacks the
#// base, triggering the On Attack version: P1 defeats the Experience on Darth Vader (SOR_087, myGroundArena-1)
#// and gives an Experience token to Vaneé HIMSELF (myGroundArena-0), a legal recipient. The On Attack resolves
#// BEFORE combat damage, so the now-buffed Vaneé (2 → 3 power) deals 3 to the base. Intended: "Vanee's on attack
#// ability ... give an Experience token to a friendly unit."

## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_082:1:0
WithP1GroundArena: SOR_087:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# WhenPlayed_EnemyExpTokenNotSelectable
#// LOF_082 Vaneé — the defeat targets an Experience token on a FRIENDLY unit. Friendly Darth Vader (SOR_087)
#// carries an Experience token and an enemy Green Squadron A-Wing (SOR_141) also carries one; only Vader's
#// token is offered (myGroundArena-0), the enemy's is excluded. Intended: "green squadron awing experience can't
#// be selected."

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_082}
P1OnlyActions: true
WithP1GroundArena: SOR_087:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2SpaceArena: SOR_141:1:0
WithP2SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>PlayHand:0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit
P1SELECTABLEEXACT:myGroundArena-0

---

# OnAttack_ExpOnEnemyUnit_NoValidTarget
#// LOF_082 Vaneé — the ability can only defeat an Experience token that is on a FRIENDLY unit. With the only
#// Experience token sitting on an ENEMY Darth Vader (SOR_087), there is no valid target: Vaneé attacks the
#// base (2 damage) and gains nothing, and the enemy keeps its Experience. Intended: "cannot defeat a friendly
#// Experience token on an enemy unit."

## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_082:1:0
WithP2GroundArena: SOR_087:1:0
WithP2GroundArenaUpgrade: 0:SOR_T01

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
