# LeaderAction_UsedLine_AndTheExhaustItCauses
#// Game-log sweep, phase 3b — Actions used, exhaust/ready, buffs and grants, the Force (2026-09-11).
#// JTL_016 Admiral Ackbar's leader Action [1 resource, Exhaust]: "Exhaust a non-leader unit. If you do, its
#// controller creates an X-Wing token." The Action gets its own "used" line, then its effects are attributed
#// to it. The leader's own exhaust is the COST — paid before the source is set — so it is not an effect line.

## GIVEN
CommonSetup: gyw/rrk/{myLeader:JTL_016;myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS:P1 used [[JTL_016|Admiral Ackbar]]'s Action
LOGCONTAINS:P1's [[JTL_016|Admiral Ackbar]] exhausted P1's [[SOR_095|Battlefield Marine]]
LOGCOUNT:1:exhausted

---

# Ready_Bravado
#// SHD_182 Bravado (Aggression, 5): "Ready a unit."

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_182
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
LOGCONTAINS:P1's [[SHD_182|Bravado]] readied P1's [[SOR_095|Battlefield Marine]]

---

# Buff_TacticalAdvantage
#// SOR_124 Tactical Advantage (Command, 1): "Give a unit +2/+2 for this phase." Grants, buffs, debuffs and
#// blanks are logged from AddTurnEffect — the one function every card's turn effect goes through — using the
#// turn-effect registry's own label and duration.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_124
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
LOGCONTAINS:P1's [[SOR_124|Tactical Advantage]] gave P1's [[SOR_095|Battlefield Marine]] +2/+2 for this phase
LOGCOUNT:1:+2/+2

---

# Force_Used_ThenItsEffect
#// LOF_172 Sorcerous Blast (Aggression, 1): "Use the Force. If you do, deal 3 damage to a unit."

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_172
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
LOGCONTAINS:P1 used the Force ([[LOF_172|Sorcerous Blast]])
LOGCONTAINS:P1's [[LOF_172|Sorcerous Blast]] dealt 3 damage to P2's [[SOR_046|Consular Security Force]]

---

# Force_Gained_FromTheBase
#// LOF_021 Shadowed Undercity: "When a friendly Force unit attacks: The Force is with you." LOF_256 Gifted
#// Urchin is a VANILLA Force unit — a Force unit with its own On Attack (Yaddle) raises a second trigger and a
#// "choose which resolves first" prompt that stalls the scripted attack before it starts.

## GIVEN
CommonSetup: bbw/rrk/{myBase:LOF_021}
P1OnlyActions: true
WithP1GroundArena: LOF_256:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
LOGCONTAINS:The Force is with P1

---

# ReadyingAResource_NeverNamesIt
#// SHD_199 Coruscant Dissident (Cunning/Heroism, 3/4): "On Attack: You may ready a resource." Resources are
#// FACE-DOWN; OnReadyCard flips them too, and a "readied P1's [[card]]" line would reveal one to the
#// opponent. Exhaust/ready lines are for arena units and leaders only.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1Resources: 1:SOR_095:1,1:SEC_080:0
WithP1GroundArena: SHD_199:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myResources-1

## EXPECT
P1RESAVAILABLE:2
P2LOGNOTSEES:[[SEC_080
LOGCOUNT:0:readied

---

# DroidPayment_ExhaustingToPayIsNotAnEffect
#// Exhaust/ready lines are written only while an ability RESOLVES. Paying a cost is not an effect: with SEC_122
#// Vuutun Palaa, exhausting friendly Droids pays costs as if they were resources — through OnExhaustCard, during
#// payment, BEFORE the played card's When Played sets a source. Without the gate every Droid payment would log
#// "P1's Battle Droid was exhausted". (Fixture copied from sec/VuutunPalaa_DroidControlShip.md.)

## GIVEN
CommonSetup: yyk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SEC_122:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1Resources: 0
WithP1Hand: LAW_231

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
LOGCOUNT:0:exhausted
