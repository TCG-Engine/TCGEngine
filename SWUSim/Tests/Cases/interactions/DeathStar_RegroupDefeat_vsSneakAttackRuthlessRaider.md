# Setup_SplashBaseSneakAttack_RaiderHitsTheBaseTo18
#// ETERNAL deck: HMW_004 Grand Moff Tarkin deployed as The Death Star ([Vigilance][Villainy]) on a red
#// LAW splash base, LAW_025 Contested Caverns ([Aggression], "Epic Action: Play a card from your hand,
#// ignoring 1 of its Vigilance, Command, Aggression, or Cunning aspect penalties").
#//   The Death Star (deployed): "When the regroup phase starts: You may defeat a base with 10 or less
#//     remaining HP."
#//   SOR_219 Sneak Attack ([Cunning], 2): "Play a unit from your hand. It costs 3 less and enters play ready.
#//     At the start of the regroup phase, defeat it."
#//   SOR_134 Ruthless Raider ([Aggression][Villainy], 6, space): "When Played/When Defeated: Deal 2 damage to
#//     an enemy base and 2 damage to an enemy unit."
#// The opponent's base is 30 HP on 16 damage. P1 has 7 resources.
#//
#// Step 1 (this section): the splash base plays Sneak Attack ignoring its Cunning penalty (2), Sneak Attack
#// plays the Raider (6 − 3 = 3), and the Raider's When Played hits the enemy base for 2 → 18 damage, 12
#// remaining. The opponent has no units, so the "2 to an enemy unit" half has nothing to hit. 2 resources left.
#//
#// Step 2 (the next sections): at the start of the regroup phase Sneak Attack's "defeat it" is a DELAYED
#// effect (CR 4.b) — it resolves automatically, first, and the Raider dies. That triggers the Raider's When
#// Defeated (2 more to the enemy base → 20 damage, 10 remaining), which waits beside The Death Star's "When the
#// regroup phase starts" base defeat. Both are P1's triggered abilities, so P1 ORDERS them (CR 7.9):
#//   • Death Star first → the base is on 12 remaining, no legal target, nothing happens; then the Raider dies
#//     and the base ends on 20 — too late.
#//   • Raider first → its When Defeated takes the base to 10 remaining; the Death Star then defeats it.
#// On the ordering prompt: EffectStack-0 is the Raider's When Defeated, EffectStack-1 is The Death Star.

## GIVEN
CommonSetup: rrk/rrk/{myBase:LAW_025;myLeader:HMW_004;myLeaderDeployed:true;myResources:7;theirBaseDamage:16;myhandCardIds:SOR_219,SOR_134}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:18
P1HANDCOUNT:0
P1SPACEARENACOUNT:2
P1RESAVAILABLE:2
P1BASE:EPICUSED

---

# RegroupStart_DeathStarAndSneakAttack_ThePlayerOrdersThem
#// The Raider's When Defeated and The Death Star's trigger are both P1's and waiting together, so P1 is asked
#// which resolves first.

## GIVEN
CommonSetup: rrk/rrk/{myBase:LAW_025;myLeader:HMW_004;myLeaderDeployed:true;myResources:7;theirBaseDamage:16;myhandCardIds:SOR_219,SOR_134}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>Pass

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# DeathStarFirst_NoBaseAtTenOrLess_NothingHappens
#// TEST 1: The Death Star resolves first. The enemy base is on 18 damage (12 remaining) — not a legal
#// target, so nothing is defeated. Then the Raider's When Defeated takes the base to 20 damage (10 remaining)
#// — but The Death Star has already resolved this regroup. No winner.

## GIVEN
CommonSetup: rrk/rrk/{myBase:LAW_025;myLeader:HMW_004;myLeaderDeployed:true;myResources:7;theirBaseDamage:16;myhandCardIds:SOR_219,SOR_134}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>Pass
- P1>AnswerDecision:EffectStack-1

## EXPECT
P2BASEDMG:20
P1SPACEARENACOUNT:1
P1DISCARDUNIT:1:CARDID:SOR_134
NOWINNER

---

# RaiderFirst_ItsWhenDefeatedEnablesTheDeathStar_BaseDefeated
#// TEST 2: the Raider's When Defeated resolves first, taking the enemy base to 20 damage — 10 remaining — and
#// The Death Star then offers it: P1 defeats the base and wins.

## GIVEN
CommonSetup: rrk/rrk/{myBase:LAW_025;myLeader:HMW_004;myLeaderDeployed:true;myResources:7;theirBaseDamage:16;myhandCardIds:SOR_219,SOR_134}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>Pass
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1WIN
