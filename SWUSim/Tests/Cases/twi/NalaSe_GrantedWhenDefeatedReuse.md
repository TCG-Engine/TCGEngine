# Thrawn_ReusesNalaSeGrantedWhenDefeated
#// Bug report P-6 (game 911). TWI_001 Nala Se (deployed): "Each friendly Clone unit gains: 'When Defeated:
#// Heal 2 damage from your base.'" That is a When Defeated ability the Clone GAINED, so JTL_002 Grand Admiral
#// Thrawn ("When you use a 'When Defeated' ability: you may exhaust this leader. If you do, use that ability
#// again.") must be able to use it again. Pre-fix the heal resolved inline as a leave-play side effect, never
#// as a When Defeated use, so Thrawn was never offered. Twin Suns seat: Nala Se deployed + Thrawn ready.
#// TWI_109 (Clone) attacks SOR_046 (3/7) and dies to the counter: heal 2 (6 → 4), Thrawn again (4 → 2).

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1; myLeader2:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:2
P1NODECISION

---

# Thrawn_OfferNamesNalaSe
#// Same board, offer left pending: the first heal has landed (6 → 4) and Thrawn's offer names the ability
#// by the card that supplies it (Nala Se), as every granted When Defeated does.

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1; myLeader2:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:4
P1DECISIONTOOLTIP:Use_Nala_Se's_When_Defeated_ability_again_(Thrawn)?

---

# Thrawn_DeclineHealsOnce
#// Control: declining Thrawn's offer leaves the single granted heal (6 → 4).

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1; myLeader2:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:4
P1NODECISION

---

# ShadowCaster_ReusesNalaSeGrantedWhenDefeated
#// JTL_169 Shadow Caster: "When a friendly unit is defeated: You may use all of its 'When Defeated'
#// abilities again." The Clone's Nala Se-granted heal is one of its When Defeated abilities, so the defeat
#// heals 2 (6 → 4) and Shadow Caster uses it again (4 → 2). Same root cause as the Thrawn report.

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:2
P1NODECISION

---

# Chimaera_UsesNalaSeGrantedWhenDefeatedOnLivingClone
#// JTL_039 Chimaera: "When Played: You may use a 'When Defeated' ability on another friendly unit." A living
#// Clone holds the Nala Se-granted heal (TWI_109 has no When Defeated of its own), so it is a legal pick and
#// using it heals 2 (6 → 4). The Clone stays in play. Pre-fix the Clone was not offered at all.

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1GroundArena: TWI_109:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_109
P1SPACEARENAUNIT:0:CARDID:JTL_039
P1BASEDMG:4
P1NODECISION

---

# Thrawn_EnemyAttackDefeatsClone
#// Cross-frame: P2's SOR_046 (3/7) attacks P1's Clone TWI_109 (3/3) and defeats it. The granted heal and
#// Thrawn's offer belong to P1 (the Clone's controller), drained on P1's own queue: 6 → 4 → 2.

## GIVEN
CommonSetup: yyk/rrk/{myBaseDamage:6;myLeader:TWI_001:1:1; myLeader2:JTL_002}
SkipPreGame: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:2
P1NODECISION
