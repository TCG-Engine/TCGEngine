# Ambush_IsPrinted
#// IC27_142 Greedo — Big-Timer (5/3 Ground, Cunning/Villainy, Underworld/Bounty Hunter, unique)
#// "Ambush
#//  While attacking or defending, this unit deals combat damage after the other attacking or defending unit."
#// Ambush is printed (auto-wired). The ordering reuses the two existing sequential paths in SWUCombatDamage: an
#// attacking Greedo takes the defender-first path (LAW_086's), a defending Greedo puts the attacker on the
#// attacker-first path (Shoot First's). The unit dealing second must survive to deal (CR 6.3.5.c). Conflicts (CR
#// 6.3.5.c — "its controller decides"): an attacking Greedo that also deals first (Shoot First, Carson Teva's
#// Support grant, Anakin's Podracer) asks his controller which to use; Greedo against Greedo cancels out to
#// simultaneous damage; a defending Greedo against The Stranger's "defender deals first" choice follows The
#// Stranger's choice. A Greedo that has lost his abilities deals damage normally.
#//
#// COVERAGE: keyword=Ambush_IsPrinted + Ambush_AttacksAndDiesFirst (the Ambush dispatch path) ·
#//           attacking=Attacking_DefenderFirst_GreedoDiesDealingNothing + Attacking_SurvivesThenDeals ·
#//           defending=Defending_AttackerFirst_GreedoDiesDealingNothing + Defending_SurvivesThenCounters ·
#//           base=AttackingABase_Unaffected ·
#//           conflict=ShootFirst_Offer + ShootFirst_ChooseFirst + ShootFirst_ChooseAfter ·
#//           mirror=GreedoVsGreedo_Simultaneous ·
#//           stranger=DefendingVsStranger_StrangersChoiceStands ·
#//           lost-abilities=LostAbilities_SimultaneousAgain ·
#//           reqboundary=ShootFirst_AnsweredAcrossRequestBoundary · seat=ShootFirst_ChooseFirst_SeatTwo ·
#//           modes=2P only (no player reference; "the other attacking or defending unit" is determined)

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_142

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_142
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# Ambush_AttacksAndDiesFirst
#// Played and Ambushing into a Battlefield Marine (3/3): the Marine deals its 3 first, Greedo (3 HP) is
#// defeated and deals nothing — the Marine is untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_142
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Attacking_DefenderFirst_GreedoDiesDealingNothing
#// Simultaneous damage would trade (5 ≥ 3 and 3 ≥ 3); with Greedo second, only Greedo dies.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Attacking_SurvivesThenDeals
#// Into an Underworld Thug (2/3): the Thug deals 2 first, Greedo survives on 3 HP and deals 5.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Defending_AttackerFirst_GreedoDiesDealingNothing
#// P2's Marine attacks Greedo: the Marine deals first, Greedo dies, the Marine takes nothing.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Defending_SurvivesThenCounters
#// P2's Thug (2/3) attacks Greedo: 2 first, Greedo survives and counters for 5.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_247:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# AttackingABase_Unaffected
#// No other unit in the combat: Greedo simply deals 5 to the base.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ShootFirst_Offer
#// Shoot First lets him deal first; his own text says after. CR 6.3.5.c: his controller decides — a YES/NO
#// prompt. Left pending.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP1Hand: SOR_217
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Deal_Greedo's_combat_damage_first_instead_of_after?

---

# ShootFirst_ChooseFirst
#// YES: Greedo (5+1) deals first, kills the Marine and takes nothing.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP1Hand: SOR_217
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ShootFirst_ChooseAfter
#// NO: his own "after" is used — the Marine deals first and Greedo dies dealing nothing.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP1Hand: SOR_217
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ShootFirst_AnsweredAcrossRequestBoundary
#// ShootFirst_ChooseFirst with a request boundary before the answer: the choice rides a turn effect.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP1Hand: SOR_217
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# GreedoVsGreedo_Simultaneous
#// Each Greedo deals after the other — the two cancel out to simultaneous damage: both die. (Attacker-first
#// would leave the defender standing; defender-first the attacker.)

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: IC27_142:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# DefendingVsStranger_StrangersChoiceStands
#// P2's The Stranger (1/7, Grit) attacks Greedo and chooses to have the defender deal first. That choice stands:
#// Greedo deals 5 first, Grit takes The Stranger to 6, and it kills Greedo. (Greedo's "after" instead: The
#// Stranger deals 1 first and Greedo survives.)

## GIVEN
CommonSetup: yyk/bbk
SkipPreGame: true
WithP1GroundArena: IC27_142:1:0
WithP2GroundArena: LAW_086:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# LostAbilities_SimultaneousAgain
#// Force Lightning's "loses all abilities" (the SOR_138 turn effect) on Greedo: ordinary simultaneous damage, so
#// he trades with the Marine.

## GIVEN
CommonSetup: yyk/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_142:1:0:SOR_138
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# ShootFirst_ChooseFirst_SeatTwo
#// The conflict choice from seat 2 (the YES marker is stored in the attacker's frame): P2's Greedo, given Shoot
#// First by P2, chooses to deal first and kills P1's Marine untouched.

## GIVEN
CommonSetup: bbw/yyk/{theirResources:1}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: IC27_142:1:0
WithP2Hand: SOR_217

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0
