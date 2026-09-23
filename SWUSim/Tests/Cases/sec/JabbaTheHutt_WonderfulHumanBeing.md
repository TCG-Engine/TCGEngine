# Deployed_FriendlyDamagedSurvives_DealsBack
#// SEC_002 Jabba the Hutt (deployed) — "When another friendly unit is dealt damage and survives: You may
#// have that unit deal that much damage to an enemy unit. Once each round."
#// P1's SEC_080 (3/3) attacks the enemy SOR_063 (2/4 Sentinel): deals 3 (SOR_063 survives at 4 HP),
#// takes 2 counter-damage and survives. SEC_002 (deployed) reacts → SEC_080 deals that much (2) to an
#// enemy unit. Only enemy = SOR_063 → 3 + 2 = 5 damage on 4 HP → defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# LeaderAction_3PlusDamageDeals2
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals damage
#// to an enemy unit; if the friendly unit has 3 or more damage on it, it deals 2 instead of 1.
#// Friendly LAW_124 (4/7) carries 3 damage → deals 2 to the only enemy (SOR_095, 3/3 survives at 2).
#// Proves the 3+-damage → 2 branch (vs the 1 in the sibling test).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: LAW_124:1:3
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_DamagedUnitDeals1
#// SEC_002 Jabba the Hutt (leader) — Action [1 resource, Exhaust]: A friendly damaged unit deals 1
#// damage to an enemy unit. (If it has 3+ damage it deals 2 instead — see the other test.)
#// Friendly SEC_080 has 1 damage → deals 1 to the only enemy (SOR_095). Both picks auto-resolve.
#// Costs 1 resource (2 ready → 1), leader exhausts.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_NoEnemy_UsableNoEffect
#// SEC_002 Jabba the Hutt (leader) — CR 6.4.587.c: the [1 resource, Exhaust] cost changes game state, so the
#// Action is usable even with no enemy unit for the friendly damaged unit to hit. It pays the cost (exhaust +
#// 1 resource) and does nothing.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# Deployed_UnitDoesNotSurvive_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction needs the friendly unit to SURVIVE the damage. Death
#// Star Stormtrooper (SOR_128, 3/1) attacks Consular Security Force (SOR_046, 3/7): it deals 3 (SOR_046
#// survives) but takes 3 counter-damage and is defeated. Because the friendly unit did not survive, Jabba
#// offers nothing.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:1
P1LEADER:DEPLOYED

---

# Deployed_MayDecline
#// SEC_002 Jabba the Hutt (deployed) — the reaction is optional. Imperial Dark Trooper (SEC_080, 3/3)
#// attacks a Sentinel (SOR_063, 2/4) and survives the 2 counter-damage; Jabba offers to deal 2 to an
#// enemy unit, but P1 declines, so no extra damage is dealt (SOR_063 keeps only its 3 combat damage).
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:DEPLOYED

---

# Deployed_JabbaHimselfDamaged_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction is for ANOTHER friendly unit; Jabba being dealt
#// damage himself does not trigger it. P2's Imperial Dark Trooper (SEC_080) attacks the deployed Jabba
#// (2/8), who survives with 3 damage; no reaction is offered.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_002
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_DamageFromEnemyAbility_ReactionDealsThatMuch
#// SEC_002 Jabba the Hutt (deployed) — the reaction also fires on non-combat damage. P2 plays Open Fire
#// (SOR_172, deal 4) onto P1's Consular Security Force (SOR_046, 3/7), which survives. Jabba then has that
#// unit deal 4 to an enemy unit; P1 targets Death Star Stormtrooper (SOR_128, 3/1), defeating it.
#// ⚠ The `P1>Drain` is a HARNESS step, not a game step: Jabba's trigger is queued as a static CUSTOM on
#// the damaged unit's controller, who here is the NON-acting player. Production runs
#// ProcessGoldfishAutomation after every action, which drains every live seat's static queue in the same
#// request, so the offer reaches P1 immediately; the harness only drains the acting seat and has to be
#// told. Same shape as core/SplitDamageFiresOnUnitDamagedObservers.md's `P2>Drain`.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP2Hand: SOR_172
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# Deployed_EnemyUnitDamaged_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction is only for FRIENDLY units taking damage. P1's own
#// Daring Raid (SHD_178) deals 2 to the enemy Consular Security Force (SOR_046), which survives; since the
#// damaged unit is an enemy, Jabba offers nothing.
## GIVEN
CommonSetup: bbk/bbk/{myLeader:SEC_002:1:1:1;myBase:JTL_019;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SHD_178
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
P1LEADER:DEPLOYED

---

# Deployed_ShieldedFriendlyTakesNoDamage_NoReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction needs a friendly unit to actually BE DEALT damage. A
#// Shield absorbs the whole instance, so a shielded friendly that "survives" an attack was never dealt
#// damage and Jabba does not fire. P1's SEC_080 (3/3) carries a Shield and attacks SOR_063 (2/4 Sentinel):
#// the 2 counter-damage is absorbed by the Shield, so SEC_080 ends at 0 damage with no Shield, SOR_063
#// survives at 3 damage, and no reaction prompt appears.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_063:1:0
## WHEN
- P1>AttackGroundArena:0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Deployed_OnceEachRound_SecondDamageEventDoesNotReact
#// SEC_002 Jabba the Hutt (deployed) — "Once each round." After the reaction fires on the first friendly
#// unit to be damaged and survive, a SECOND such event in the same round must not offer it again. P1's
#// SEC_080 attacks SOR_063 and the reaction fires (killing SOR_063); P1's second unit (SOR_046) then
#// attacks P2's LAW_124 and survives its counter — no second prompt, so LAW_124 keeps just the combat
#// damage.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Deployed_ReactionWorksAcrossArenas
#// SEC_002 Jabba the Hutt (deployed) — "have that unit deal that much damage to an ENEMY unit" is not
#// arena-restricted: the damaged friendly is on the ground and the only enemy is in space, and the
#// reaction still reaches it. P1's ground SEC_080 (3/3) trades with P2's ground SOR_063 and survives on
#// 2 damage, then deals that 2 to P2's SPACE unit JTL_069.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_063:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Deployed_DamageFromAFRIENDLYAbilitySourceAlsoArmsTheReaction
#// SEC_002 Jabba the Hutt (deployed) — the reaction is on "a friendly unit is dealt damage and
#// survives", with no restriction on who dealt it. P1 hits its OWN SEC_080 with SHD_178 Daring Raid for
#// 2; SEC_080 survives, and Jabba lets it fire that 2 back into the enemy JTL_069.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_178
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:DAMAGE:2

---

# Deployed_DECLINING_DoesNotSpendTheRound
#// ⚠ USER RULING 2026-09-07 — the partner to Deployed_OnceEachRound_SecondDamageEventDoesNotReact
#// above, and the branch that section cannot see. "Use this ability only once each round" is spent by
#// USING the ability; a triggered "you may" whose entire effect is the optional part was never used if
#// it was declined, so a later qualifying event the same round still offers. (Contrast an ACTION, where
#// the player already paid an activation and refusing a sub-choice cannot refund it.)
#// Two Consular Security Forces (3/7) each attack one of two Massassi Group Marines (4/7). Every unit
#// survives both combats: the attacker deals 3 and takes 4. The FIRST reaction is declined; the second
#// must therefore still be offered, and its 4 finishes the first Marine (3 + 4 on 7 HP).
#// That Marine being GONE is the whole assertion — without the ruling there is no second offer and both
#// Marines end the round alive on 3.
#// ⚠ Board notes, both learned the hard way here: no unit may DIE before the offer is minted (the pool
#// is built pre-cleanup, so a death shifts the survivor's positional mzID between the offer and the
#// continuation), and no enemy may have SENTINEL — a Sentinel defender forces BOTH attacks onto itself,
#// which silently turns this into a different combat whose wrong-amount offer reads exactly like the
#// ruling not having landed.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: LAW_124:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
- P1>AttackGroundArena:1:1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:DAMAGE:4

---

# Deployed_ONEEffectDamagesTWOFriendlies_StillOnlyONEUse
#// ★ BUG (game 1110356). The once-each-round gate was read when the trigger was QUEUED but only written
#// when the offer RESOLVED, so a single effect that damages SEVERAL friendly units at once observed each
#// one before any offer had resolved: every hit saw an unspent round and every hit got its own offer.
#// Live report: Twin Suns, P1 deployed Qi'ra (SHD_002 — "deal damage to each unit equal to half its
#// remaining HP"), which damaged Bazine Netal AND Qi'ra herself in one event, and deployed Jabba let P1
#// ping for 1 and then for 4.
#// Driver here is the same divided-damage funnel the core file uses: P2's SHD_177 Vambrace Flamethrower
#// splits 3 among P1's two Consular Security Forces (2 + 1, both survive on 7 HP). That is TWO qualifying
#// "another friendly unit dealt damage and survives" hits from ONE effect, and Jabba may be used for only
#// one of them — P1 takes the 2 and there must be no second prompt left over.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_177
## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
- P1>Drain
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NODECISION
P2NODECISION
#// ⚠ These two guard the OBSERVER continuation on the trigger resume. The batch is flushed from the DAMAGE
#// funnel, where no action of its own is open — a bare resume would call SWUAfterAction here, closing P2's
#// attack a second time and swapping the turn twice (which with two seats lands back on P2 and reads as a
#// free extra action). Deliberately no P1OnlyActions in this section, or TURNPLAYER is unobservable.
NOEXTRAACTION
TURNPLAYER:1

---

# Deployed_TWOSimultaneousTriggers_TheOTHEROneIsChoosable
#// ⚠ THE MIRROR of the section above, and the only thing that can tell a real CHOICE from a relabelled
#// fixed order: same board, same split (2 onto P1's first Security Force, 1 onto the second), but P1
#// picks the unit that took 1 instead of the one that took 2, so the enemy takes 1 rather than 2.
#// CR 7.6.9 — two triggers of the same ability go off in one window and the controlling player chooses
#// which resolves first; Jabba's "only once each round" then makes the loser fizzle. Without the
#// ordering prompt P1 is silently locked into whichever unit the damage funnel happened to hit first.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_177
## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
- P1>Drain
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
P2NODECISION

---

# Deployed_TWOSimultaneousTriggers_TheChoiceIsACTUALLYOffered
#// The prompt itself, asserted before it is answered: with two simultaneous Jabba triggers pending, the
#// FIRST thing P1 is asked is which damaged unit deals its damage — not an enemy target. Leaving the
#// decision unanswered is what makes the tooltip readable; the two sections above then drive it.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_177
## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
- P1>Drain
## EXPECT
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# Deployed_TWOSimultaneousTriggers_DecliningTheCHOSENOneStillOffersTheOther
#// The branch the batching exists for, and the one the fizzle fix must NOT eat. USER RULING 2026-09-07:
#// declining a triggered "you may" does not spend "once each round", so the trigger P1 did not pick is
#// still owed to them. P1 picks the unit that took 2, DECLINES its offer (the round stays unspent), and
#// the other trigger is then offered for 1 — which P1 takes.
#// ⚠ Mirror-read this against Deployed_ONEEffectDamagesTWOFriendlies_StillOnlyONEUse: identical up to the
#// decline, and there the second trigger must NOT appear. Accept → fizzle, decline → offered.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_002:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SHD_177
## WHEN
- P2>AttackGroundArena:0:BASE
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
- P1>Drain
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
P2NODECISION
