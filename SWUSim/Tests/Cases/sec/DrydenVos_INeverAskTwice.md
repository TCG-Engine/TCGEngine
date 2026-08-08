# Deployed_Action_DiscardPlayAmbush
#// SEC_007 Dryden Vos (deployed) — Action [discard a card from your hand]: play a unit from your hand
#// (paying its cost). It gains Ambush this phase. Dryden discards SOR_095, plays SOR_128 (3/1) which
#// gains Ambush.

## GIVEN
CommonSetup: bgk/brk/{
  myLeader:SEC_007:1:1:1;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_128
WithP1Resources: 6

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# LeaderAction_DiscardPlayAmbush
#// SEC_007 Dryden Vos (leader) — Action [Exhaust, discard a card that costs 6 or more]: Play a unit that
#// costs 5 or less from your hand (paying its cost). It gains Ambush for this phase.
#// P1 discards SOR_049 (cost 6), then plays SEC_080 (cost 2, Command/Villainy — no penalty under the C/V
#// leader) for 2, and SEC_080 gains Ambush this phase. Enemy board empty → Ambush has no target (no attack),
#// the unit just enters.

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:SEC_007;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_049
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_NoSixCostCard_NoAction
#// SEC_007 Dryden Vos (leader front) — the action requires discarding a card that costs 6 or more. With
#// only cheap cards in hand, there is nothing to discard, so the action is unavailable: activating does
#// nothing (leader stays ready, hand intact, no decision).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SOR_095
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:READY
P1HANDCOUNT:2
P1NODECISION

---

# Deployed_EmptyHand_NoAction
#// SEC_007 Dryden Vos (deployed) — with an empty hand there is nothing to discard or play, so the action
#// is unavailable and activating does nothing.
## GIVEN
CommonSetup: bgk/brk/{myLeader:SEC_007:1:1:1;myBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION

---

# Deployed_UsableWhileExhausted
#// SEC_007 Dryden Vos (deployed) — the deployed ability is not an Action, so it works even while Dryden is
#// exhausted. Dryden attacks P2's base (5), exhausting himself, then still uses the ability: discard
#// Obi-Wan (SOR_049) to play Imperial Dark Trooper (SEC_080), which gains Ambush (no enemy, so it just
#// enters).
## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_049
WithP1Hand: SEC_080
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# Deployed_NoPlayableUnit_DiscardsAsCost
#// SEC_007 Dryden Vos (deployed) — CR 6.4.587.c: the [discard a card] cost is a game-state change, so the
#// Action is usable even with no unit affordable to play. The handler discards the chosen card (cost) and
#// plays nothing. Here P1 has 0 resources and only SOR_251 in hand (nothing affordable).

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021;myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# LeaderAction_AmbushAttackResolves
#// SEC_007 Dryden Vos (leader front) — the granted Ambush is not just a keyword on the card, it produces a
#// real attack. P1 discards SOR_232 (cost 6) as the additional cost, plays SEC_080 Imperial Dark Trooper
#// (3/3, on-aspect for the Command/Villainy leader) for 2, and takes the Ambush attack against P2's
#// SpecForce Soldier (SOR_140, 2/2): the Soldier is defeated and the Dark Trooper comes back with 2 damage.
#// Dryden himself is exhausted (the Action's [Exhaust] cost) and both resources are spent.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_232
WithP1Hand: SEC_080
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_AmbushDeclined_UnitStillEnters
#// SEC_007 Dryden Vos (leader front) — the Ambush attack is a "may". Declining it leaves the played unit in
#// the arena undamaged and the enemy untouched, which separates "the unit was played" from "the attack
#// happened" in the section above.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_232
WithP1Hand: SEC_080
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED

---

# LeaderAction_UnaffordableUnitIsNotOffered
#// SEC_007 Dryden Vos (leader front) — "Play a unit that costs 5 or less from your hand (PAYING ITS COST)",
#// so the offer is filtered by what P1 can actually afford, not just by the printed 5-or-less gate. After
#// discarding SOR_232, P1 has 2 resources and two legal-by-cost units in hand: SEC_080 (Command/Villainy,
#// on-aspect, 2) and SOR_128 (printed 1, but Aggression/Villainy — Aggression is uncovered under this
#// leader, so +2 makes it 3). Only SEC_080 qualifies, so it is auto-played with NO choice raised and
#// SOR_128 is left in hand. The paired section below sets resources to 3 and does get a two-way choice —
#// together they prove the filter is affordability, not just the printed cost.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_232
WithP1Hand: SEC_080
WithP1Hand: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1HANDCOUNT:1
P1RESAVAILABLE:0

---

# LeaderAction_BothAffordable_BothOffered
#// SEC_007 Dryden Vos (leader front) — the control for the section above. With 3 resources the +2
#// aspect-penalised SOR_128 becomes affordable too, so BOTH units are offered and P1 gets a real choice.
#// (Same hand, same 5-or-less gate — only the resource count differs.)

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_232
WithP1Hand: SEC_080
WithP1Hand: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# LeaderAction_NothingAffordableAfterTheDiscard_CostStillPaid
#// SEC_007 Dryden Vos (leader front) — the discard and the exhaust are the Action's COSTS, so they are paid
#// even when the effect can play nothing. P1 has 1 resource; after discarding SOR_232 the only unit left
#// (SEC_080, cost 2) is unaffordable, so nothing is played — but SOR_232 is in the discard, Dryden is
#// exhausted and SEC_080 is still in hand.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007;myBase:JTL_019;theirBase:SOR_021;myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_232
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Deployed_UsableTwiceInTheSameTurn_NoLimit
#// SEC_007 Dryden Vos (deployed) — the deployed Action has no [Exhaust] and no once-per-turn clause, so it
#// can be used repeatedly in the same turn as long as the discard cost can be paid. P1 uses it twice:
#// discard SOR_095 → play SEC_080 (2), then discard SOR_232 → play SOR_128 (printed 1, +2 for the uncovered
#// Aggression aspect = 3). Both units land, all 5 resources are spent, and Dryden is still READY at the end
#// — he was never exhausted by either use.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Hand: SOR_232
WithP1Hand: SOR_128

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SEC_007
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:2:CARDID:SOR_128
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# Deployed_SingleCardInHand_IsSpentAsTheDiscardCost
#// SEC_007 Dryden Vos (deployed) — with exactly ONE card in hand that card is the only thing that can pay
#// the discard cost, so paying it empties the hand and there is nothing left to play. The cost is still
#// paid (CR 6.4.587.c — the discard changes the game state, so the Action is usable): SEC_080 ends in the
#// discard, not the arena. Distinct from Deployed_NoPlayableUnit_DiscardsAsCost, where the leftover card
#// was merely unaffordable rather than absent.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_080

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_007
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1RESAVAILABLE:5

---

# Deployed_ShieldedUnit_ResolveShieldBeforeAmbush_ShieldEatsTheCounterDamage
#// SEC_007 Dryden Vos (deployed) — a unit played by the ability that ALSO has its own entry trigger raises
#// two simultaneous triggers, and their order is the player's choice with a real consequence. P1 discards
#// SOR_095 to play SOR_085 Rukh (3/6, Shielded, Command/Villainy so on-aspect at 5) and resolves the
#// SHIELD first: Rukh enters with a Shield, then Ambush-attacks SOR_140 SpecForce Soldier (2/2). Rukh kills
#// it and the Shield absorbs the 2 counter-damage — Rukh ends with 0 damage and 0 Shields (the Shield was
#// spent). Compare the paired section below, which picks the other order.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_095
WithP1Hand: SOR_085
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_085
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# Deployed_ShieldedUnit_ResolveAmbushBeforeShield_UnitTakesTheCounterDamage
#// SEC_007 Dryden Vos (deployed) — the same board with the trigger order reversed. Taking the Ambush
#// attack FIRST means Rukh fights without a Shield: he still kills the Soldier but keeps the 2 counter-
#// damage, and the Shielded trigger then resolves afterwards and leaves him holding a Shield. Ordering the
#// triggers the other way (section above) trades that Shield for the 2 damage — proof the choice is live
#// and not cosmetic.

## GIVEN
CommonSetup: bgk/bbk/{myLeader:SEC_007:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_095
WithP1Hand: SOR_085
WithP2GroundArena: SOR_140:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_085
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:DAMAGE:2
P2GROUNDARENACOUNT:0
