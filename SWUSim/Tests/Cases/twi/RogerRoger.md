# HostDefeated_ReattachToToken
#// TWI_069 Roger Roger (Upgrade +1/+1, attach to a Battle Droid token) — "When Defeated: Attach this
#// upgrade to a friendly Battle Droid token." Roger Roger's host token (2/2 with it) attacks a 3/3 and
#// dies; instead of going to discard, Roger Roger re-attaches to the other friendly Battle Droid token,
#// which becomes a 2/2 (1/1 token + Roger Roger's +1/+1).
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2

---

# NoToken_GoesToDiscard
#// TWI_069 Roger Roger — with NO other friendly Battle Droid token to receive it, the "When Defeated"
#// re-attach has no target, so Roger Roger goes to its owner's discard normally when its host is defeated.
#// The defeated host is a TOKEN, which CEASES to exist rather than entering a discard pile, so the discard
#// holds ONLY Roger Roger (a real card) at idx0.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:TWI_069

---

# HostDefeated_ReattachToTs26BattleDroid
#// TWI_069 Roger Roger — "a friendly Battle Droid token" means ANY Battle Droid token, not one printing of
#// it. Twin Suns prints its own Battle Droid token (TS26_T01 — same title, traits and stats as TWI_T01,
#// made by e.g. TS26_13 Count Dooku). Bug #1117 (game 1438045, 4P Twin Suns): the re-attach searched for
#// CardID 'TWI_T01' only, so with TS26 droids on the board Roger Roger went to the discard instead —
#// twice, once from an Executioner's Arena defeat and once from an Exploit sacrifice. Same board as
#// HostDefeated_ReattachToToken, but the receiving droid is the TS26 printing.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:0

---

# Ts26HostDefeated_ReattachToTs26BattleDroid
#// TWI_069 Roger Roger — host AND receiver are both the TS26 Battle Droid printing. The host is excluded by
#// its UniqueID, not by CardID, so the second TS26 droid (same CardID as the host) must receive it.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TS26_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:0

---

# OtherTokenNotABattleDroid_GoesToDiscard
#// TWI_069 Roger Roger — the match is "Battle Droid token", not "any token": a friendly Clone Trooper token
#// (TS26_T02 — another Twin Suns token unit) is not a legal receiver, so Roger Roger goes to its owner's
#// discard. Guards the TS26 fix against widening to every token unit.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T02:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T02
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:TWI_069

---

# ExploitSacrifice_ReattachToTs26BattleDroid
#// TWI_069 Roger Roger — the report's second route: the host Battle Droid is defeated as an EXPLOIT cost
#// (game 1438045: "P1's Battle Droid was defeated (Exploit, MagnaGuard Wing Leader)"), not in combat. Exploit
#// defeats through its own path, so the combat sections alone do not prove this one. In the game Exploit
#// came from TWI_005 Count Dooku's leader action; here TWI_182 carries a printed Exploit 1 (the same
#// generic keyword path) and exploits the Roger Roger host. The TS26 Battle Droid must receive it.
## GIVEN
CommonSetup: yyk/rrk/{myResources:10;handCardIds:TWI_182}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:TWI_182
P1DISCARDCOUNT:0

---

# Matrix1_NonTokenHostDefeated_MovesToBattleDroid
#// TWI_069 Roger Roger — owner's matrix #1: a NON-TOKEN host is defeated. When a unit leaves play its upgrades
#// are defeated with it, so Roger Roger's When Defeated fires and it MUST (not "may") attach to the friendly
#// Battle Droid. SOR_095 (3/3, pre-damaged 2; 4/4 with Roger Roger) attacks P2's SOR_095 and takes 3 → dies.
#// The host itself is a real card, so it alone goes to the discard; Roger Roger does not.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P1NODECISION

---

# Matrix2_TokenSpaceHostDefeated_MovesToBattleDroid
#// TWI_069 Roger Roger — matrix #2: a TOKEN SPACE host. JTL_T01 TIE Fighter (1/1; 2/2 with Roger Roger) attacks
#// P2's SOR_237 Alliance X-Wing (3/2) and dies. The token ceases to exist; Roger Roger crosses arenas to the
#// friendly (ground) TS26 Battle Droid. Nothing reaches the discard.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
WithP2SpaceArena: SOR_237:1:0
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:0
P1NODECISION

---

# Matrix3_ConfiscateOnNonTokenHost_MovesToBattleDroid
#// TWI_069 Roger Roger — matrix #3: Roger Roger itself is DEFEATED by SOR_251 Confiscate ("Defeat an upgrade")
#// while its non-token host survives. Its When Defeated fires: it moves to the friendly Battle Droid. The host
#// keeps standing with no upgrade; nothing reaches the discard except Confiscate (an event).
## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_251
P1NODECISION

---

# Matrix4_ConfiscateOnTokenSpaceHost_MovesToBattleDroid
#// TWI_069 Roger Roger — matrix #4: Confiscate defeats Roger Roger on a TOKEN SPACE host (JTL_T01 TIE Fighter).
#// The TIE survives as a bare 1/1; Roger Roger moves across arenas to the friendly TS26 Battle Droid.
## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_251
P1NODECISION

---

# Matrix5_WaylayNonTokenHost_MovesToBattleDroid
#// TWI_069 Roger Roger — matrix #5: the non-token host is BOUNCED by SOR_222 Waylay. A bounce is not a defeat of
#// the HOST, but its upgrades are still defeated as it leaves play — so Roger Roger's When Defeated fires and it
#// moves to the friendly Battle Droid rather than going to the discard. The host returns to P1's hand.
## GIVEN
CommonSetup: yyw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_222
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_222
P1NODECISION

---

# Matrix6_WaylayTokenSpaceHost_MovesToBattleDroid
#// TWI_069 Roger Roger — matrix #6: a TOKEN SPACE host (JTL_T01 TIE Fighter) is bounced by Waylay. A token
#// returned to hand ceases to exist; its upgrade is still defeated, so Roger Roger moves to the friendly TS26
#// Battle Droid. Nothing enters P1's hand.
## GIVEN
CommonSetup: yyw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_222
WithP1SpaceArena: JTL_T01:1:0
WithP1SpaceArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1HANDCOUNT:0
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_222
P1NODECISION

---

# Captured_HostsUpgradeMovesToBattleDroid
#// TWI_069 Roger Roger — a CAPTURED host. Capture defeats the captive's upgrades (CR 8.34.1), so Roger Roger's
#// When Defeated fires and it moves to its controller's other Battle Droid. Capture has its own copy of the
#// upgrade-defeat loop (DoCaptureUnit), which — like bounce — never carried the Roger Roger step. P1's Take
#// Captive has P1's SOR_046 capture P2's Roger Roger host (two enemy units, so the host is picked); P2's own
#// Battle Droid must receive it, not P2's discard.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:TWI_069
WithP2GroundArena: TWI_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2DISCARDCOUNT:0

---

# Choice_TwoDroids_OffersExactlyTheFriendlyDroids
#// TWI_069 Roger Roger — the re-attach is MANDATORY, but WHICH droid is the controller's choice when more than
#// one is eligible. P1's host TWI_T01 (with Roger Roger) dies attacking; P1 still has a TWI_T01 and a TS26_T01.
#// The prompt must offer exactly those two, at their POST-defeat positions (the host has left play, so the
#// survivors sit at ground 0 and 1). An offer built while the host was still in play would point one slot
#// too far and ring the wrong units. P2's enemy unit is never offered.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TS26_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:2
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Choice_TwoDroids_PickTheSecond
#// TWI_069 Roger Roger — the controller picks the SECOND droid (the TS26 one); only it receives Roger Roger.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TS26_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TS26_T01
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:2
P1DISCARDCOUNT:0
P1NODECISION

---

# Choice_TwoDroids_IsMandatory
#// TWI_069 Roger Roger — "Attach this upgrade" has no "may": the droid choice is a MANDATORY pick (MZCHOOSE —
#// the server refuses a pass on it), not a declinable MZMAYCHOOSE that could leave Roger Roger in the discard.
#// A decline cannot be written as a WHEN line (the harness refuses it as "not a candidate"), so the TYPE is the
#// assertion.
## GIVEN
CommonSetup: rrk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TS26_T01:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1DECISIONTYPE:MZCHOOSE
P1DECISIONTOOLTIP:Choose_a_friendly_Battle_Droid_token_to_attach_Roger_Roger_to
P1DISCARDCOUNT:1

---

# CapturedByBase_Arrest_MovesToBattleDroid
#// TWI_069 Roger Roger — the OTHER capture route: SEC_195 Arrest has P1's BASE capture P2's Roger Roger host
#// (_SWUBaseCaptureUnit, its own upgrade-defeat loop). The captive's upgrades are defeated (CR 8.34.1), so
#// Roger Roger moves to P2's Battle Droid rather than P2's discard.
## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_195
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:TWI_069
WithP2GroundArena: TWI_T01:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0

---

# CapturedTokenHost_Ceases_MovesToBattleDroid
#// TWI_069 Roger Roger — a TOKEN host that "would be captured" ceases instead (set aside, not a defeat). It still
#// leaves play, so its upgrades are defeated and Roger Roger moves to the controller's Battle Droid. P1's Take
#// Captive has P1's space unit capture P2's TIE Fighter token (same arena) carrying Roger Roger.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArenaUpgrade: 0:TWI_069
WithP2GroundArena: TS26_T01:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:TS26_T01
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:0

---

# OwnerPutsHostOnTopOfDeck_MovesToBattleDroid
#// TWI_069 Roger Roger — "its owner puts it on the top or bottom of their deck" (HMW_218 New Tactics). The host
#// leaves play without being defeated, but ITS UPGRADES are defeated, so Roger Roger moves to the friendly droid.
#// P1 targets their own SOR_095; P1 is the owner and picks Top. Only New Tactics reaches the discard.
## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TWI_T01:1:0
WithP1Deck: SOR_128
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Top
## EXPECT
P1DECKTOPCARD:SOR_095
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_218
P1NODECISION

---

# OwnerPutsHostOnBottomOfDeck_MovesToBattleDroid
#// TWI_069 Roger Roger — same as above, owner picks Bottom.
## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_218
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP1GroundArena: TS26_T01:1:0
WithP1Deck: SOR_128
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Bottom
## EXPECT
P1DECKTOPCARD:SOR_128
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_218
P1NODECISION
