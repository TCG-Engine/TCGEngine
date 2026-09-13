#// Nightsister Lair (LOF_020) + a Force unit's own On Attack: two triggers of the same attack, both P1's, so P1
#// orders them. The ORDER is load-bearing when the On Attack defeats a friendly unit whose When Defeated spends
#// the Force: that When Defeated is NESTED and resolves before the Lair's Force token exists.
#//
#// FOUND BY: sweep retro #1 of run 2 (2026-09-13). Uncovered combo shapes ORDER LOF_009:OnAttack + LOF_020 (156×)
#//   and ORDER LOF_020 + LOF_160:OnAttack (142×), both from normal_maul_blue (the Maul fixture runs Karis too).
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// THE RULES.
#//   - LOF_020 Nightsister Lair: "When a friendly Force unit attacks: The Force is with you (create your Force
#//     token)." It triggers in the Begin-attack step, together with the attacker's On Attack (CR 7.4 step 3).
#//   - Simultaneous triggers of one player: that player picks the order (CR 7.6.9).
#//   - CR 7.6.11: "After resolving a triggered ability 'A', if any new abilities were triggered while resolving
#//     it, the new abilities are considered 'nested abilities' and must be resolved before any other abilities
#//     triggered at the same time as ability 'A'."
#// Cards: LOF_009 Darth Maul (deployed: "On Attack: Deal 1 damage to a unit and 1 damage to a different unit")
#//   · LOF_031 Karis 2/4 Force ("When Defeated: You may use the Force. If you do, give a unit -2/-2 for this
#//   phase") — seeded with 3 damage, so 1 ping defeats her · LOF_160 Merrin 2/5 Force ("On Attack: You may
#//   discard a card from your hand. If you do, deal 2 damage to a unit") · SOR_095 Battlefield Marine 3/3 ·
#//   SOR_108 Vanguard Infantry (discard fodder).
#// Maul's board: Karis is myGroundArena-0 and the deployed Maul is myGroundArena-1. On the ordering prompt,
#//   EffectStack-0 is the attacker's On Attack and EffectStack-1 is the Lair.
#//
# MaulAttacks_HisOnAttackAndTheLair_ThePlayerOrdersThem
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_031:1:3
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1NOFORCE

---

# LairFirst_MaulsPingDefeatsKaris_HerWhenDefeatedSpendsTheNewForce
#// Lair first: the Force token exists. Maul pings Karis (defeated) and the Marine. Karis's When Defeated waits
#//   for Maul's ability to finish (CR 7.6.11), then spends the Force: the Marine, 3/3 with 1 damage, gets -2/-2
#//   and is defeated. Maul hits the base for 5. No P1OnlyActions, so the turn really passes to P2.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: LOF_031:1:3
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_009
P2BASEDMG:5
TURNPLAYER:2

---

# MaulFirst_KarisWhenDefeatedIsNested_ResolvesBeforeTheLair_NoForceToSpend
#// Maul first: his ping defeats Karis, and her When Defeated is NESTED, so it resolves before the Lair (CR
#//   7.6.11). The Force token does not exist yet, so she cannot spend it and there is no prompt. Only then does
#//   the Lair create the Force. The Marine survives with Maul's 1 damage.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_031:1:3
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1NODECISION
P1HASFORCE
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENACOUNT:1
P2BASEDMG:5

---

# CONTROL_MaulFirst_WithTheForceAlready_KarisSpendsIt_ThenTheLairMakesANewOne
#// The control that makes the section above mean something: the same Maul-first order, but P1 already has the
#//   Force. Karis's When Defeated DOES prompt and spends it (the Marine is defeated), so the missing prompt
#//   above is about the Force, not about her When Defeated failing to fire. The Lair then creates a new token.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_031:1:3
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HASFORCE
P2GROUNDARENACOUNT:0
P2BASEDMG:5

---

# MerrinAttacks_HerOnAttackAndTheLair_ThePlayerOrdersThem
## GIVEN
CommonSetup: rrk/ggw/{myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP1Hand: SOR_108
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# MerrinFirst_DiscardsForTwoDamage_ThenTheLairGivesTheForce
## GIVEN
CommonSetup: rrk/ggw/{myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP1Hand: SOR_108
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1HASFORCE
P2BASEDMG:2

---

# LairFirst_MerrinDeclinesTheDiscard_TheForceIsStillCreated
#// Merrin's discard is optional; declining it deals no damage and keeps the card, and the Lair still resolves.
## GIVEN
CommonSetup: rrk/ggw/{myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_160:1:0
WithP1Hand: SOR_108
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:1
P1HASFORCE
P2BASEDMG:2
