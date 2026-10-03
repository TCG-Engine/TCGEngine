# UpgradeDefeated_DealBase
#// ASH_161 Zeb Orrelios — "When a friendly upgrade is defeated: deal 1 damage to a base." With Zeb in play,
#// SOR_095 (wearing SOR_120) dies attacking SOR_046; SOR_120 is defeated, so Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:3
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:1

---

# WhenPlayed_ThreeAdvantage
#// ASH_161 Zeb Orrelios (Ground, 5/7, cost 7) — When Played: give 3 Advantage tokens to another unit. Zeb
#// enters and piles 3 Advantage onto SOR_095 (the only other unit, auto-resolved).
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_161}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3

---

# WhenPlayed_ThreeAdvantage_ToEnemy
#// ASH_161 Zeb — "another unit" may be an ENEMY unit. With a friendly and an enemy unit present, Zeb
#// prompts; P1 chooses the enemy SOR_046 and piles all 3 Advantage there.
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_161}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# UpgradeDefeated_DealOwnBase
#// ASH_161 Zeb — the "deal 1 to a base" target is the controller's choice; P1 may point it at their OWN
#// base. Same defeat as above, but P1 chooses myBase.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:3
WithP1GroundArenaUpgrade: 1:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myBase-0
## EXPECT
P1BASEDMG:1
P2BASEDMG:0

---

# FriendlyUnitNoUpgradeDefeated_NoTrigger
#// ASH_161 Zeb — triggers only on a friendly UPGRADE being defeated, not on a bare friendly unit dying.
#// SOR_095 (no upgrade) dies attacking SOR_046; Zeb does nothing — no base damage, no prompt.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# EnemyUpgradeDefeated_NoTrigger
#// ASH_161 Zeb — an ENEMY-controlled upgrade being defeated is not "friendly," so Zeb does not trigger.
#// P1's SOR_046 kills a pre-damaged enemy SOR_095 wearing the enemy's SOR_120; that upgrade is defeated
#// but belongs to P2, so no base damage and no prompt for P1.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:0:2
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P2GROUNDARENACOUNT:0

---

# FriendlyUpgradeDefeatedDirectly_HostSurvives
#// ASH_161 Zeb Orrelios — the trigger fires whenever a friendly upgrade is defeated, even if its host
#// survives. SOR_095 wears SOR_120 (Academy Training); P1 plays SOR_251 Confiscate to defeat that lone
#// upgrade. The host lives on with no upgrade and Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:SOR_251}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:1

---

# MultipleFriendlyUpgradesDefeated_OnceEach
#// ASH_161 Zeb Orrelios — the trigger fires once PER friendly upgrade defeated. SOR_095 wears two real
#// upgrades (SOR_120 + SOR_069); SHD_079 Rival's Fall defeats the host, so both upgrades are defeated
#// simultaneously (CR 9.3) and Zeb deals 1 to a base twice = 2 damage on P2's base. Zeb himself survives.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:SHD_079}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP1GroundArenaUpgrade: 1:SOR_069
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:2

---

# FriendlyUpgradeReturnedToHand_NoTrigger
#// ASH_161 Zeb Orrelios — the trigger requires the upgrade to be DEFEATED, not merely to leave play. P1
#// plays SEC_200 Junior Senator ("may return an upgrade that costs 3 or less to its owner's hand") to
#// bounce SOR_120 off SOR_095 back to hand. Returning is not a defeat, so Zeb does nothing.
## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:SEC_200}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1BASEDMG:0
P2BASEDMG:0

---

# ProtectedUpgrade_NoTrigger
#// ASH_161 Zeb Orrelios — if a friendly upgrade is protected from defeat by a replacement effect, it is
#// never defeated, so Zeb does not trigger. P1's SEC_061 Willrow Hood carries exactly 1 friendly upgrade
#// (SOR_120), which can't be defeated by an enemy ability. P2's SOR_251 Confiscate is spent for nothing:
#// the upgrade survives and Zeb deals no base damage.
## GIVEN
CommonSetup: rrk/grw/{theirResources:1;theirHandCardIds:SOR_251}
WithActivePlayer: 2
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SEC_061:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1BASEDMG:0
P2BASEDMG:0

---

# TookControlEnemyUpgrade_Defeated
#// ASH_161 Zeb Orrelios — friendliness is judged at the moment of defeat by who CONTROLS the upgrade.
#// P1 plays SHD_077 Evidence of the Crime to take control of P2's SOR_120 and attach it to friendly
#// SOR_095; it is now a friendly upgrade. P1 then plays SOR_251 Confiscate to defeat it, and Zeb deals
#// 1 to P2's base.
## GIVEN
CommonSetup: bbw/rrk/{myResources:4;handCardIds:SHD_077,SOR_251}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:1

---

# PilotUpgradeDefeated
#// ASH_161 Zeb Orrelios — a Pilot played as an upgrade is still an upgrade for this trigger. SOR_232
#// AT-ST carries JTL_046 Paige Tico as a Pilot upgrade; P1 plays SOR_251 Confiscate to defeat that pilot
#// upgrade. The Vehicle survives and Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk/{myResources:1;handCardIds:SOR_251}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_232:1:0
WithP1GroundArenaUpgrade: 1:JTL_046
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:1

---

# OwnUpgradeDefeated_ZebDiesInCombat
#// ASH_161 — Zeb's own attached upgrade defeated as Zeb himself dies in combat still fires his "deal 1 to a
#// base" (CR last-known-information). Zeb (5/7, pre-damaged 6) wears SOR_120 and attacks SOR_046; SOR_046's
#// 3 counter-damage defeats Zeb, defeating SOR_120 → Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: ASH_161:1:6
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# HostBouncedDefeatsUpgrade_ZebTriggers
#// ASH_161 — returning a friendly UPGRADED unit to hand (SOR_222 Waylay) defeats the attached upgrade (CR 9.3),
#// firing Zeb's "deal 1 to a base". A separate Zeb stays in play; SOR_095 wearing SOR_120 is bounced.
## GIVEN
CommonSetup: yyw/rrk/{myResources:6;handCardIds:SOR_222}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:1

---

# ShieldTokenConsumedInCombat_ZebTriggers
#// ASH_161 — a Shield token (SOR_T02) consumed to absorb combat damage is a DEFEATED friendly upgrade, so Zeb
#// fires "deal 1 to a base". P2's SOR_046 attacks P1's shielded SOR_095; the shield pops → Zeb deals 1 to P2 base.
## GIVEN
CommonSetup: rrw/rrk
WithP1GroundArena: [ASH_161:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
## WHEN
- P2>AttackGroundArena:0:1
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2BASEDMG:1

---

# FriendlyUpgradeOnEnemyHost_Trigger
#// ASH_161 Zeb Orrelios — friendliness is judged by who CONTROLS the upgrade, not who controls the host.
#// P1 plays SEC_038 Condemn onto the enemy LOF_254 Porg (a friendly upgrade on an enemy unit). P1's SOR_046
#// then attacks and defeats that Porg; the friendly Condemn is defeated with it, so Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: bbk/rrw/{myResources:3;handCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: [ASH_161:1:0 SOR_046:1:0]
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# FriendlyUnitEnemyUpgradeDefeated_NoTrigger
#// ASH_161 Zeb Orrelios — a friendly UNIT dying while it carries only an ENEMY-controlled upgrade does not
#// trigger Zeb: the defeated upgrade is not friendly. P2 plays SEC_038 Condemn onto P1's SOR_095, then P1
#// plays SHD_079 Rival's Fall to defeat that SOR_095. Only the enemy Condemn is defeated, so Zeb does nothing.
## GIVEN
CommonSetup: bbw/bbk/{myResources:6;handCardIds:SHD_079;theirResources:3;theirHandCardIds:SEC_038}
WithActivePlayer: 2
WithP1GroundArena: [ASH_161:1:0 SOR_095:1:0]
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:0
P1GROUNDARENACOUNT:1

---

# ZebDefeatedByAbility_OwnUpgrade
#// ASH_161 Zeb Orrelios — Zeb's own attached upgrade defeated as he leaves play still fires his ability
#// (CR last-known-information). Zeb wears SOR_120; P1 plays SHD_079 Rival's Fall to defeat Zeb himself. His
#// SOR_120 is defeated with him, so Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:SHD_079}
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# ZebDefeatedByAbility_MultipleOwnUpgrades_OnceEach
#// ASH_161 Zeb Orrelios — with MULTIPLE of his own upgrades, his ability fires once per upgrade defeated.
#// Zeb wears SOR_120 and SOR_069; P1 plays SHD_079 Rival's Fall to defeat Zeb. Both upgrades are defeated
#// simultaneously, so Zeb deals 1 to a base twice = 2 on P2's base.
## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:SHD_079}
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArenaUpgrade: 0:SOR_069
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:2

---

# ZebReturnedToHand_OwnUpgrade_Trigger
#// ASH_161 Zeb Orrelios — returning Zeb to hand defeats his own attached upgrade (CR 9.3), firing his
#// ability. Zeb wears SOR_120; P1 plays SOR_222 Waylay to bounce Zeb to hand. His SOR_120 is defeated, so
#// Zeb deals 1 to P2's base.
## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:SOR_222}
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# ZebCaptured_OwnUpgrade_Trigger
#// When Zeb (ASH_161) is CAPTURED, his own attached upgrade (SOR_120) is defeated as he leaves play. His
#// "when a friendly upgrade is defeated" fires from last-known information (own-upgrade OR-clause), so his
#// controller (P1) deals 1 to a base. P2 plays Take Captive (SHD_131): P2's ground unit captures P1's Zeb.
## GIVEN
CommonSetup: rrk/ggk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 3
WithP2Hand: SHD_131
WithP2GroundArena: SOR_046:1:0
WithP1GroundArena: ASH_161:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P2>PlayHand:0
- P1>Drain
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# NGORTakeControlThenDefeat_RealUpgrade_ZebFires
#// A unit P1 takes control of (JTL_043 No Glory, Only Results) carries its own upgrade into P1's control —
#// taking control of a unit transfers control of its upgrades (CR). When P1 then defeats it, the now-friendly
#// upgrade (SOR_120) is defeated → Zeb (ASH_161) deals 1 to a base. P1 NGORs the enemy SOR_046 (upgraded).
## GIVEN
CommonSetup: bbk/ryk/{myResources:12;handCardIds:JTL_043}
WithP1GroundArena: ASH_161:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# TokenUpgrade_ExperienceOnACombatDefeatedHost_FiresZeb
#// ASH_161 — a TOKEN upgrade is still an UPGRADE, so when its host is defeated the token is defeated with
#// it and "when a friendly upgrade is defeated" fires. SOR_095 carrying an Experience token attacks a 4/7
#// and dies to the counter; Zeb deals 1 to a base.
#// CONTROL for this and the two below: FriendlyUpgradeDefeatedDirectly_HostSurvives and
#// ShieldTokenConsumedInCombat_ZebTriggers already prove Zeb fires for a REAL upgrade and for a Shield
#// consumed in combat — so a failure here is about the TOKEN-on-host-death path only.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T01
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:1

---

# TokenUpgrade_AdvantageOnACombatDefeatedHost_FiresZeb
#// ASH_161 — same rule for an Advantage token riding a host that dies. (The SHED path was fixed
#// separately; this is the host-death path, which is a different loop.)

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:ASH_T02
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:1

---

# TokenUpgrade_DefeatedDIRECTLYWhileTheHostSurvives_FiresZeb
#// ASH_161 — the third leave-play path: an upgrade defeated on its own, host untouched. P2 spends SOR_251
#// Confiscate on the Shield token riding P1's SOR_095; the token is defeated (not consumed by damage), so
#// Zeb fires once.
#// ⚠ Distinct from ShieldTokenConsumedInCombat_ZebTriggers: that one goes through SWUConsumeShieldToken
#// (which always fired); this one goes through the generic defeat-an-upgrade path, which did not.
#// The host survives, so the ONLY thing that left play is the token.

## GIVEN
CommonSetup: rrw/grw/{theirResources:4; theirHandCardIds:SOR_251}
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_T02
WithActivePlayer: 2

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2BASEDMG:1

---

# TokenUpgrade_DefeatedByASWEEPOnAnENEMYUnit_FiresTHATUnitsControllersZeb
#// ASH_161 — the third code path (the shared defeat-all-upgrades-on-a-unit sweep, reached here by
#// ASH_156 R5-D4's "On Attack: defeat all upgrades on the defending unit").
#// Two things are asserted at once:
#//   • a TOKEN upgrade swept off a unit is defeated, so the observer fires; and
#//   • it fires for the UPGRADE'S controller — P2's Zeb, not the attacking player's — so P2 chooses the
#//     base and puts the damage on P1.
#// P1 attacks P2's shielded SOR_046; R5-D4 sweeps the Shield token off it before damage. P2's Zeb then
#// deals 1 to a base of P2's choosing.

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1GroundArena: ASH_156:1:0
WithP2GroundArena: ASH_161:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP1Deck: [SOR_063 SOR_063]
WithP2Deck: [SOR_063 SOR_063]

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1
- P2>Drain
- P2>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1BASEDMG:1
P2BASEDMG:0

---

# LeaderPilotReturnedByBamboozle_DefeatedInstead_ZebTriggers
#// CR 3.4.6 (v9): a leader UPGRADE that would move to an out-of-play zone "is defeated instead". P2's Bamboozle
#// returns each upgrade on P1's SEC_214 (a ground Vehicle), which carries P1's Pilot leader JTL_012 Luke: Luke is
#// DEFEATED (back to the leader zone), so a friendly upgrade was defeated — P1's Zeb deals 1 damage to a base.
#// ⚠ FIXTURE: myLeaderDeployedPilot attaches to the FIRST GROUND unit, so the host is listed before Zeb.
## GIVEN
CommonSetup: gyw/yyw/{myLeader:JTL_012;myLeaderDeployedPilot:true;theirResources:2}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [SEC_214:1:0 ASH_161:1:0]
WithP2Hand: SOR_199
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:1

---

# LeaderPilotDefeatedByConfiscate_ZebTriggers
#// A leader upgrade defeated DIRECTLY (SOR_251 Confiscate, "Defeat an upgrade") is a friendly upgrade being
#// defeated (CR 1.5.5.d / 3.4.5): P1's Zeb deals 1 damage to a base. Kazuda (JTL_018) is the only upgrade, so it
#// auto-targets. (Not Luke JTL_012 — he can't be defeated by enemy abilities, so Confiscate rightly fails on him.)
## GIVEN
CommonSetup: gyw/yyw/{myLeader:JTL_018;myLeaderDeployedPilot:true;theirResources:1}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [SEC_214:1:0 ASH_161:1:0]
WithP2Hand: SOR_251
## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:1

---

# PilotedHostDefeated_LeaderUpgradeDefeated_ZebTriggers
#// CR 1.5.5.d: when a unit leaves play its upgrades are defeated simultaneously — a leader PILOT included (it goes
#// to its leader zone rather than a discard, CR 3.4.5). P1's SEC_214 (1/4, +4/+5 from Pilot Luke = 5/9, 6 damage
#// already) attacks P2's SOR_046 (3 power) and dies; Zeb deals 1 damage for the defeated leader upgrade.
## GIVEN
CommonSetup: gyw/yyw/{myLeader:JTL_012;myLeaderDeployedPilot:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_214:1:6 ASH_161:1:0]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_161
P2BASEDMG:1

---

# Wipe_ZebListedFirst_StillSeesTheOtherUpgradeDefeated
#// CR 1.5.5.d: an upgrade is defeated SIMULTANEOUSLY with its host leaving play, and a wipe defeats every unit at
#// once. SOR_043 Superlaser Blast ("Defeat all units") with Zeb at ground 0 and SOR_095 + SOR_120 at ground 1:
#// SOR_120 is defeated in the same event Zeb leaves play, so Zeb (last known information) still deals 1.
#// The wipe walks the board one unit at a time, so Zeb is already gone when SOR_095's upgrade is defeated — the
#// check must use the pre-wipe snapshot (SWUSimulDefeatBegin), not the live board. Board order is the trap.
## GIVEN
CommonSetup: bbk/bgw/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 1:SOR_120
WithP1Hand: SOR_043
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# Wipe_ZebListedSecond_StillSeesTheOtherUpgradeDefeated
#// Control for the section above: the same wipe with Zeb AFTER the upgraded unit, so Zeb is still on the board when
#// SOR_120 is defeated. Both orders must give the same single ping.
## GIVEN
CommonSetup: bbk/bgw/{myResources:12}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1GroundArena: ASH_161:1:0
WithP1Hand: SOR_043
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:1

---

# CombatTrade_ZebAndTheHostOfAFriendlyUpgradeDieTogether
#// Combat damage is simultaneous (CR 1.9.10), so a TRADE defeats both units in the same event. P1 plays SEC_038
#// Condemn onto P2's LOF_254 Porg (a friendly upgrade on an enemy unit); Zeb, on 1 remaining HP, attacks the Porg
#// and both die. The friendly Condemn is defeated simultaneously with Zeb leaving play, so Zeb still deals 1.
#// Combat removes the ATTACKER first, so a live-board check missed it.
## GIVEN
CommonSetup: bbk/rrw/{myResources:3;handCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:6
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# GravChargeDefeatsItself_ZebTriggers
#// ASH_085 Grav Charge — "When attached unit's attack ends: Deal 4 damage to it and defeat this upgrade." The
#// upgrade defeating ITSELF is a friendly upgrade being defeated. Zeb (5/7) carries it and attacks P2's base for 5;
#// at attack end Zeb takes 4 (survives) and Grav Charge is defeated, so Zeb deals 1 more: 6 total.
## GIVEN
CommonSetup: bbk/rrw
P1OnlyActions: true
WithP1GroundArena: ASH_161:1:0
WithP1GroundArenaUpgrade: 0:ASH_085
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:6
