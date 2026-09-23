#// Darth Vader piloting (JTL_142: "On Attack: You may deal 1 damage to a unit. If a unit is defeated this way, you may
#// deal 1 damage to a unit or base.") on Punishing One (SEC_171: "This unit gains Raid 1 for each damaged enemy unit.
#// / When Played/On Attack: You may deal 1 damage to a unit."). Both On Attacks are P1's, so P1 orders them (CR
#// 7.6.9) — and both resolve BEFORE combat damage, so every enemy unit they leave damaged raises the attack by 1
#// (Raid, counted while attacking). Same family as VaderPilot_OnAttack_vsFiresprayAndJustifier.md.
#//
#// FOUND BY: sweep run 5, retro #4 (2026-09-14): ORDER JTL_142:OnAttackFromUpgrade + SEC_171:OnAttack (1×,
#//   dedra_colossus.boba_lakecountry.s047).
#//
#// Cards: Punishing One 3/5 space + Vader (+3/+3) = 6 · SOR_095 Battlefield Marine 3/3 · SOR_164 Wampa 4/5 (both P2,
#//   ground; a ping damages but does not defeat them) · SOR_225 TIE/ln 2/1 (P2, space; a ping defeats it). On the
#//   ordering prompt EffectStack-0 is the host's On Attack and EffectStack-1 Vader's.
#//
# BothOnAttacks_ThePlayerIsAskedWhichResolvesFirst
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_171:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve

---

# PunishingOneFirst_TwoDamagedEnemies_RaidTwo_EightToTheBase
#// Punishing One pings the Marine, Vader pings the Wampa (no defeat, no bonus): two damaged enemy units → Raid 2.
#//   Base: 6 + 2.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_171:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:8
P1NODECISION

---

# VaderFirst_DefeatsTheTIE_BonusOnTheMarine_ThenPunishingOneOnTheWampa_EightToTheBase
#// Vader's 1 defeats the TIE (a defeated unit is not a damaged one), his bonus 1 damages the Marine; Punishing One
#//   damages the Wampa → Raid 2. Base: 6 + 2.
## GIVEN
CommonSetup: rrk/grw/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_171:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:8
P1NODECISION
