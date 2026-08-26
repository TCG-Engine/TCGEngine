# DoubleAttach_NormalPower_NoDisclose
#// SEC_038 Condemn — the multi-copy interaction. P1's SOR_141 (1/3, Raid 2) bears TWO Condemns and
#//   attacks P2's base. Each Condemn grants its own On Attack AND "loses all OTHER abilities" — so each
#//   Condemn's granted On Attack is itself suppressed by the other Condemn. Result: NO disclose is offered
#//   (P2NODECISION), and the unit's Raid 2 is also suppressed, so it attacks for its normal power 1.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:1
P2NODECISION
P1SPACEARENAUNIT:0:UPGRADECOUNT:2

---

# NotAttacking_KeepsSentinel
#// SEC_038 Condemn — the suppression is attack-scoped ("WHILE attached unit is attacking"). A Condemn-
#//   bearing Sentinel unit that is NOT attacking keeps all its abilities: SOR_063 (2/4 Sentinel) with a
#//   Condemn still HAS Sentinel while idle. Guard against the lose-abilities applying continuously.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SentinelUnit_LosesSentinelMidAttack
#// SEC_038 Condemn — Sentinel loss is immediate at attack declaration; the -6/-0 is NOT (it only lands
#//   if/after the defender discloses). P1's SOR_063 (2/4 Sentinel) bears 1 Condemn and attacks P2's base.
#//   The granted On Attack queues P2's disclose, which pauses combat. Mid-attack (disclose still pending):
#//     - the attacker has LOST Sentinel (lose-all-other-abilities is active from declaration), and
#//     - its power is STILL 2 (the -6/-0 only applies once the disclose resolves, not yet).
#//   P2 still has the pending disclose decision.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:2
P2HASDECISION

---

# SingleAttach_DefenderDeclines_NoDebuff
#// SEC_038 Condemn — the granted disclose is "may". P2 (defending player) declines (AnswerDecision:-),
#//   so no -6/-0 is applied: SEC_118 deals its full 6 to the base. Proves the decline path no-ops.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:POWER:6

---

# SingleAttach_DefenderDiscloses_Debuff
#// SEC_038 Condemn (Upgrade, Vigilance/Villainy, no attach restriction) — "While attached unit is
#//   attacking, it gains: 'On Attack: the defending player may disclose VigilanceVillainy → this unit
#//   gets -6/-0 for this attack' and loses all other abilities."
#// P1's SEC_118 (6/5, vanilla) bears 1 Condemn and attacks P2's base. The granted On Attack lets the
#// DEFENDING player (P2) disclose; P2 discloses SEC_038 (Vigilance,Villainy → covers VigilanceVillainy),
#// so the attacker gets -6/-0 → power max(0, 6-6) = 0 → deals 0 to the base. After the attack the
#// attack-duration debuff expires, so the attacker's power is back to 6.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# SingleAttach_SuppressesOwnRaid
#// SEC_038 Condemn — "loses all other abilities" while attacking. P1's SOR_141 (1/3, innate Raid 2)
#//   bears 1 Condemn and attacks P2's base from space. P2 declines the granted disclose (so no -6/-0),
#//   but the unit's OWN Raid 2 is suppressed by Condemn, so it deals just its base power 1 (not 1+2=3).
#//   Proves the lose-all-other-abilities suppresses the host's own keywords.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:-

## EXPECT
P2BASEDMG:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# GrantedDisclose_AutoSkip_NoAspectCards
#// SEC_038 Condemn — the granted "On Attack: defending player may disclose VigilanceVillainy → -6/-0" is
#//   auto-skipped when the defender can't cover those aspects (CR 38.3). P1's SEC_118 (6/5) bears 1 Condemn
#//   and attacks P2's base; P2 has an empty hand → no disclose prompt, full 6 to the base.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P2NODECISION

---

# SuppressesOverwhelm_NoSpillToBase
#// SEC_038 Condemn — "loses all other abilities" while attacking suppresses keyword abilities like
#//   Overwhelm. P1's SOR_164 (Wampa, 4/5 Overwhelm) bears 1 Condemn and attacks P2's TWI_T02 (2/2). The
#//   defender has no disclose cards (auto-skip). Wampa defeats the 2/2, but with Overwhelm suppressed the
#//   2 excess damage does NOT spill to the base. Wampa takes the 2 counter damage.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP2GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# BlanksWhenDefeated_IfAttackerDiesDuringTheAttack
#// SEC_038 Condemn — "loses all other abilities" for the duration of the attack, which includes a WHEN
#// DEFEATED ability if the attacker dies during that attack. P1's SEC_205 Obi-Wan (4/5) wears Condemn and
#// attacks P2's LAW_124 Industrious Team (4/7): Obi-Wan is blanked, so his "deals combat damage to a base"
#// mill does not apply, and more importantly a Creditor's Claim (SEC_039) also on him grants a When
#// Defeated that must NOT fire when he trades. He dies to the 4 counter with 4 damage dealt; P2 keeps its
#// unit and gets no When-Defeated prompt even after draining its queue. (Obi-Wan is 7 HP with both
#// upgrades attached, so he is pre-damaged to 3 and the 4-power counter is exactly lethal.)
## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:3
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1GroundArenaUpgrade: 0:SEC_039
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>Drain
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P1NODECISION

---

# StacksWithExiledFromTheForce_FullBlanking
#// SEC_038 Condemn — combining Condemn's attack-duration blanking with SEC_054 Exiled from the Force
#// ("attached unit loses all abilities except for Grit") must not fight each other: the unit is blanked
#// either way. P1's SEC_118 (6/5) wears BOTH; it attacks P2's base and P2 discloses for Condemn's -6/-0,
#// so 0 damage lands. Guards the interaction rather than either card alone.
## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SEC_038}
P1OnlyActions: true
WithP1GroundArena: SEC_118:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1GroundArenaUpgrade: 0:SEC_054
## WHEN
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myHand-0
## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# SuppressesSaboteursShieldDefeat
#// SEC_038 Condemn — "loses all other abilities while attacking" takes Saboteur with it, so the
#// attacker no longer defeats the defender's Shields on the way in. P1's SEC_250 Rebel Pathfinder
#// (Saboteur) wears Condemn and attacks SOR_095, which carries two Shield tokens: instead of both being
#// defeated, one absorbs the entire hit. SOR_095 ends with 1 Shield and 0 damage.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SEC_250:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# SaboteurStillDeclaresPastSentinel_ButLosesTheShieldDefeat
#// SEC_038 Condemn — the two halves of Saboteur split across the attack steps, and Condemn only reaches
#// the second one. Target declaration is CR 3.2.b (Check restrictions: a Sentinel must be chosen
#// "unless the attacker has Saboteur"); the shield-defeat is a triggered ability at CR 3.3 (Begin
#// attack), which is also where "while attacking" abilities — Condemn's blanking — become active.
#// So a Condemned Saboteur can still declare past a Sentinel but no longer defeats the Shields.
#//
#// P1's SEC_250 Rebel Pathfinder wears Condemn. P2's SHD_029 Pyke Sentinel is at index 0 and the real
#// target SOR_095 (carrying two Shields) at index 1, so the declaration must genuinely SKIP the Sentinel
#// rather than merely happening to pick the first legal unit. It is legal, and one Shield then absorbs
#// the hit instead of both being defeated: SOR_095 keeps 1 Shield on 0 damage, the Sentinel is untouched.
#//
#// Regression (A/B verified): applying Condemn's blanking at DECLARATION instead of at Begin attack
#// stripped Saboteur before the legal-target list was built — SOR_095 became unattackable and the attack
#// was forced onto the Sentinel, which took 2, with both Shields surviving.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SEC_250:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP2GroundArena: SHD_029:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 1:SOR_T02
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_029
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# CannotGainAbilitiesWhileAttacking_GrantedSaboteurIsBlanked
#// SEC_038 Condemn — official ruling (2025-10-31): "the attached unit CAN'T GAIN abilities while
#// attacking." So an ability handed to it for this very attack is lost along with its printed ones.
#// P1's SOR_095 wears Condemn and is sent in by SOR_168 Precision Fire, which grants Saboteur for the
#// attack. The defender SOR_046 carries two Shields: Saboteur would defeat BOTH, but the grant does not
#// survive Condemn, so one Shield simply absorbs the hit — SOR_046 keeps 1 Shield and takes 0 damage.
#// See CannotGainAbilitiesWhileAttacking_ControlWithoutCondemn for the same board without the upgrade.

## GIVEN
CommonSetup: rrw/grk
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP1Hand: SOR_168
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# CannotGainAbilitiesWhileAttacking_ControlWithoutCondemn
#// The positive control for the section above: identical board and identical play, with Condemn NOT
#// attached. SOR_168 Precision Fire's granted Saboteur is live, so BOTH Shields are defeated and the
#// attack lands for 5 (3 printed + Precision Fire's +2/+0 Trooper bonus). This is what the Condemned
#// version would look like if the grant survived — so the paired assertions pin the ruling rather than
#// just observing that a Shield happened to absorb something.

## GIVEN
CommonSetup: rrw/grk
P1OnlyActions: true
WithP1Resources: 4
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_168
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# TwinSuns_DiscloseIsOfferedToTheACTUALDefendingPlayer
#// "On Attack: THE DEFENDING PLAYER may disclose VigilanceVillainy…" — the disclose was queued for
#// OtherPlayer($player), so at four seats seat 2 was asked to disclose for an attack aimed at seat 4:
#// a bystander got a prompt, and the real defender got no chance to reduce the attack.
#//
#// The assertion is WHO HOLDS THE DECISION. Seat 4 must, and seats 2 and 3 must not — the legacy build
#// fails all three at once. Left pending so the routing itself is what is measured.
#// ⚠ FIXTURE: the disclose is only OFFERED to a player holding a card that shows both named aspects,
#// so both seat 2 and seat 4 hold a SEC_038 (Vigilance/Villainy) — meaning either seat could legally be
#// prompted and the section measures routing alone, not who happened to have the right card.

## GIVEN
CommonSetup: ggw/grk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1SpaceArena: SOR_141:1:0
WithP1SpaceArenaUpgrade: 0:SEC_038
WithP2Hand: [SEC_038 SEC_038]
WithP4Hand: [SEC_038 SEC_038]

## WHEN
- P1>AttackSpaceArena:0:P4B

## EXPECT
SEATCOUNT:4
P4HASDECISION
P2NODECISION
P3NODECISION
