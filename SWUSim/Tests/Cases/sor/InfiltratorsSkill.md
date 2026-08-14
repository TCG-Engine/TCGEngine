# Saboteur_IgnoresSentinel_AttacksBase
#// SOR_166 Infiltrator's Skill — Upgrade, cost 1, [Aggression], +1/+1, trait Learned, non-unique.
#// "Attached unit gains Saboteur. (When this unit attacks, ignore Sentinel and defeat the defender's
#//  Shields.)"
#// COVERAGE: offer=PlayFromHand_HostOffer (attach host pool left PENDING) · decline=N/A (no "you may";
#//           the keyword grant is a constant ability) · boundary=Saboteur_IgnoresSentinel_AttacksBase
#//           (defender's Shield NOT defeated when the base is attacked) vs
#//           Saboteur_DefeatsShieldsBeforeCombat (Shield defeated when that unit IS the defender) ·
#//           control=EnemyHostedSkill_GrantsToHostController (enemy-hosted Skill: the keyword belongs
#//           to the HOST, so its controller's attack bypasses P1's Sentinel) ·
#//           reqboundary=PlayFromHand_GrantSurvivesBoundary (grant asserted after a boundary)
#// Saboteur ignores Sentinel: the Marine (3/3 → 4/4) attacks the base straight past the shielded Niima
#// Outpost Constables. Shields are only defeated on the unit the host actually attacks — Niima keeps
#// hers when the base is the defender.

## GIVEN
CommonSetup: rrk/grw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP2GroundArena: SHD_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Saboteur_DefeatsShieldsBeforeCombat
#// Attacking the shielded unit itself: Saboteur defeats the defender's Shield BEFORE combat damage, so
#// the full 4 lands on Niima (2/6) and her 2 counter hits the Marine. No shield remains afterwards.

## GIVEN
CommonSetup: rrk/grw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166
WithP2GroundArena: SHD_062:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# PlayFromHand_HostOffer
#// Playing the Skill from hand: every friendly unit — ground AND space — is offered as a host (no
#// printed arena/trait restriction). Left pending to assert the offer.

## GIVEN
CommonSetup: rrk/grw/{myResources:1;myhandCardIds:SOR_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# PlayFromHand_GrantSurvivesBoundary
#// Full play path: the Marine is the only friendly unit, so the attach auto-resolves. The granted
#// Saboteur persists across a request boundary and lets the freshly-upgraded Marine (4/4) attack the
#// base past the enemy Sentinel (Echo Base Defender) in the same phase.

## GIVEN
CommonSetup: rrk/grw/{myResources:1;myhandCardIds:SOR_166}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_098:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_166
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# SkillDefeated_SaboteurLost
#// The keyword lives on the attachment: once the Skill is defeated (Confiscate, auto-target — it is
#// the only upgrade in play), the host no longer has Saboteur and drops back to 3/3.

## GIVEN
CommonSetup: rrk/grw/{myResources:1;myhandCardIds:SOR_251}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_166

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1DISCARDCOUNT:2

---

# EnemyHostedSkill_GrantsToHostController
#// "Attached unit gains Saboteur" is host-bound: with the Skill on P2's Marine, P2's attack ignores
#// P1's Sentinel (Echo Base Defender) and hits P1's base for 4. The Sentinel takes no damage.

## GIVEN
CommonSetup: rrk/grw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_166
WithP1GroundArena: SOR_098:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Attach_HostOfferIncludesEnemyUnits
#// No printed attach restriction → both sides offered (CR 2.e). Pool asserted while pending: the
#// friendly Sentinel and the enemy marine.

## GIVEN
CommonSetup: rrk/bbk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SOR_166

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
