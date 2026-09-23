#// Darth Vader piloting (JTL_142) gives the host "On Attack: You may deal 1 damage to a unit. If a unit is defeated
#// this way, you may deal 1 damage to a unit or base." The host's OWN On Attack triggers on the same attack, both
#// are P1's, and P1 orders them (CR 7.6.9). Each host's ability CHANGES THE BOARD the other one reads, so the two
#// orders end differently. Same family as PilotOnAttack_vsFighterOnAttack_CrossSeatIndirect.md.
#//
#// FOUND BY: sweep run 3, retro #2 (2026-09-13): ORDER JTL_142:OnAttackFromUpgrade + JTL_240:OnAttack (e.g.
#//   boba_lakecountry.lando_blue.s010) · ORDER ASH_146:OnAttack + JTL_142:OnAttackFromUpgrade (e.g.
#//   boba_lakecountry.dedra_colossus.s029). Not seen in run 2.
#//
#// Cards: JTL_142 Darth Vader (pilot upgrade +3/+3) · JTL_240 Fett's Firespray 4/4 space ("When Played/On Attack:
#//   Deal 1 indirect damage to a player. If you control Boba Fett … 2 instead." — no Boba here, so 1) · ASH_146
#//   Justifier 4/5 space ("When Played/On Attack: You may deal 1 damage to a unit. If that unit is defeated this
#//   way, give an Advantage token to a unit.") · ASH_T02 Advantage (+1/+0; defeated when the attack ends) ·
#//   SOR_128 Death Star Stormtrooper 3/1 · SOR_095 Battlefield Marine 3/3 · SOR_225 TIE/ln Fighter 2/1.
#//   Either host with Vader attacks for 7. On the ordering prompt EffectStack-0 is the host's On Attack and
#//   EffectStack-1 Vader's.
#//
# Firespray_BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# Firespray_IndirectFirst_P2LosesTheStormtrooper_VaderOnlyNicksTheMarine_NoBonus
#// P2 puts the 1 indirect on its Stormtrooper (defeated). Vader then pings the Marine, which survives: no bonus.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:7
P1NODECISION

---

# Firespray_VaderFirst_DefeatsTheStormtrooper_BonusToTheBase_ThenTheIndirect
#// Vader's 1 defeats the Stormtrooper → his bonus 1 to the base; then P2 assigns the indirect to its base.
#//   Base: 7 combat + 1 bonus + 1 indirect.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:9
P1NODECISION

---

# Justifier_BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: ASH_146:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# Justifier_First_AdvantageOnTheAttacker_ThenVaderChains_NineToTheBase
#// Justifier's 1 defeats the Stormtrooper → Advantage on Justifier itself (+1 for this attack). Vader's 1 defeats
#//   the TIE → bonus 1 to the base. Base: 7 +1 Advantage +1 bonus. The Advantage is gone when the attack ends.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: ASH_146:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:9
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1NODECISION

---

# Justifier_VaderFirst_BonusFinishesTheTIE_JustifierDeclines_SevenToTheBase
#// Vader's 1 defeats the Stormtrooper and his bonus 1 defeats the TIE. Justifier is then left with only P1's own
#//   unit to ping, and declines: no Advantage. Base: 7.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: ASH_146:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:PASS
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2BASEDMG:7
P1SPACEARENAUNIT:0:DAMAGE:0
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0
P1NODECISION
