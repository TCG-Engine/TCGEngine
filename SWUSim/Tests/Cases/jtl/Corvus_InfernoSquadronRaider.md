# AttachPilotDefeatsUpgrades
#// JTL_038 Corvus — When Played: may attach a friendly Pilot unit/upgrade to this. (Defeat all upgrades on
#// that Pilot and remove all damage from it.) P1 has Paige (JTL_046) as a UNIT with 2 damage and a normal
#// upgrade (SOR_120). Corvus enters and attaches Paige → her SOR_120 upgrade is defeated (to discard) and
#// her damage cleared; Paige becomes Corvus's only pilot subcard; the ground arena empties.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: JTL_046:1:2
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# KeepsCaptiveTucked
#// Captive edge: a unit holding a captive moves to Corvus. JTL_046 Paige first captures SOR_095 (via
#// SHD_131 Take Captive), tucking it facedown under her. Then Corvus attaches Paige → her NORMAL upgrades
#// + damage are removed, but the captive stays tucked on the Paige pilot subcard (it is NOT released).
#// Proof: the captured SOR_095 does NOT return to P2's arena.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 16
WithP1Hand: SHD_131 JTL_038
WithP1GroundArena: JTL_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# RelocatesExistingPilotUpgrade
#// JTL_038 Corvus — "attach a friendly Pilot unit OR upgrade." Here the Pilot (JTL_046 Paige) is ALREADY
#// an upgrade on the Vehicle SEC_214. Corvus relocates that pilot upgrade onto itself: SEC_214 stays as a
#// unit but loses its pilot, and Corvus gains Paige as a pilot subcard. (The Vehicle SEC_214 in P1's
#// ground arena represents "the pilot upgrade on it" in the choose.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Restore2_HealsBaseOnAttack
#// JTL_038 Corvus has Restore 2 — when it attacks it heals 2 damage from your base. P1's base starts with 3
#// damage; Corvus (4/5, in space) attacks P2's base, dealing 4 there and restoring 2 at home (3 → 1).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myBaseDamage:3
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_038:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:1
P2BASEDMG:4

---

# DeclineAttach_EntersAlone
#// JTL_038 Corvus — the When-Played attach is a MAY. With a friendly Pilot (JTL_046 Paige) in play P1
#// declines (Pass): Corvus enters with no pilot subcard and Paige is untouched.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_038
WithP1GroundArena: JTL_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_046

---

# NoFriendlyPilot_EntersAlone
#// JTL_038 Corvus — with NO friendly Pilot in play, no attach is offered and Corvus simply enters the space
#// arena on its own.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_038

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# AttachLeaderPilot
#// JTL_038 Corvus — Major Vonreg (JTL_011 "Red Baron", a
#// pilot-capable leader) is deployed as a Pilot upgrade onto the Escort Skiff (SOR_114) vehicle host.
#// Corvus enters and relocates the leader-pilot onto itself: Corvus gains Vonreg as its pilot subcard,
#// and the Skiff reverts to a plain unit with no pilot. The leader stays Deployed.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:12;
  myLeader:JTL_011;
  myLeaderDeployedPilot:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_038
WithP1GroundArena: SOR_114:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_011
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_114
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:DEPLOYED

---

# AttachPilotLeaderUnit_DamageOverPilotHP
#// JTL_038 Corvus — Asajj Ventress (JTL_001 "I Work Alone") is deployed as a real ground UNIT (4/6) carrying
#// 4 damage. Corvus grabs her as a pilot: her damage is removed as she becomes Corvus's pilot subcard,
#// so she is NOT defeated even though 4 damage would exceed her lower pilot HP. Leader stays Deployed.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:12;
  myLeader:JTL_001:1:1::4
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_038

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_001
P1GROUNDARENACOUNT:0
P1LEADER:DEPLOYED

---

# AttachPilotNonLeaderUnit_DamageOverPilotHP
#// JTL_038 Corvus
#// Dagger Squadron Pilot (JTL_196, a 2/1 Pilot unit) holds two Experience tokens (SOR_T01) and 2 damage
#// — surviving only thanks to the +HP from Experience. A Green Squadron A-Wing (SOR_141) carries the
#// Determined Recruit (JTL_159) pilot in space, so two pilots are selectable. P1 picks Dagger: its
#// Experience upgrades are defeated and its damage removed as it becomes Corvus's pilot — no defeat.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: JTL_196:1:2
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1SpaceArena: SOR_141:1:1
WithP1SpaceArenaUpgrade: 0:JTL_159

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_038
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_196
P1SPACEARENAUNIT:0:CARDID:SOR_141
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_159
P1GROUNDARENACOUNT:0

---

# StolenHostPilotNotFriendly_CannotMove
#// RULING (confirmed): taking control of a unit does NOT grant you control of the upgrades on it. P1 plays
#// Change of Heart (SOR_224) to steal P2's Skyhopper (SEC_214), which carries P2's Sullustan Spacer pilot
#// (JTL_255). P1 now controls the Skyhopper, but the pilot upgrade is STILL P2-controlled — so when P1
#// plays Corvus, Corvus's "attach a friendly Pilot to this" finds no friendly pilot (the enemy pilot on the
#// friendly host is not eligible), enters space with NO upgrade, and the pilot stays on the Skyhopper.
#// (Contrast: a friendly Pilot UNIT you control CAN be absorbed. The enemy-host pilot is correctly not
#// offered here.)

## GIVEN
CommonSetup: yyk/ggk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: SOR_224
WithP1Hand: JTL_038
WithP2GroundArena: SEC_214:1:0
WithP2GroundArenaUpgrade: 0:JTL_255

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_255
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
