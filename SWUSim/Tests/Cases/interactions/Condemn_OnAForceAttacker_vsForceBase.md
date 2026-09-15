#// SEC_038 Condemn on a FORCE unit whose controller's base is a Force base ("When a friendly Force unit
#// attacks: The Force is with you"). While attacking, the Condemned unit gains Condemn's On Attack and LOSES all
#// its other abilities. The base's trigger belongs to the BASE, not the unit, so it still fires. The attacker's
#// own On Attack must not (Condemn ruling 10/31/2025: "The attached unit can't gain abilities while attacking";
#// its own are lost by the printed text).
#//
#// FOUND BY: sweep retro #2–#4 of run 2 (2026-09-13): ORDER LOF_020:LOF_020 + SEC_038:OnAttackFromUpgrade
#//   (98×/41×/78×) from normal_maul_blueforce, and ORDER LOF_029 + SEC_038:OnAttackFromUpgrade (31×) from
#//   normal_talzin_force. The Condemn was played by the control decks (Aurra, Dedra) onto the Force attacker.
#//   All sections were green on first run: this file is REGRESSION COVERAGE, not a bug.
#//
#// Cards: SEC_038 Condemn ("While attached unit is attacking, it gains: 'On Attack: The defending player may
#//   disclose VigilanceVillainy. If they do, this unit gets -6/-0 for this attack' and loses all other
#//   abilities.") · LOF_009 Darth Maul deployed 5/6 ("On Attack: Deal 1 damage to a unit and 1 damage to a
#//   different unit") · LOF_020 Nightsister Lair / LOF_029 Crystal Caves (both: "When a friendly Force unit
#//   attacks: The Force is with you") · LOF_031 Karis · SEC_028 Trayus Acolyte 2/4 (Force, vanilla) · SOR_095
#//   Battlefield Marine. P2 discloses with a Condemn from hand (Vigilance, Villainy).
#// On the ordering prompt EffectStack-0 is the base and EffectStack-1 is Condemn's granted On Attack.
#//
# CondemnedMaul_HisOwnOnAttackIsLost_OnlyTheBaseAndCondemnAreOrdered
#// Exactly two triggers. If Maul kept his On Attack there would be three.
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_031:1:0
WithP1GroundArenaUpgrade: 1:SEC_038
WithP2GroundArena: SOR_095:1:0
WithP2Hand: [SEC_038 SOR_095]
## WHEN
- P1>AttackGroundArena:1:BASE
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# LairFirst_TheForce_ThenP2DisclosesCondemn_MaulHitsFor0_AndPingsNobody
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_031:1:0
WithP1GroundArenaUpgrade: 1:SEC_038
WithP2GroundArena: SOR_095:1:0
WithP2Hand: [SEC_038 SOR_095]
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:EffectStack-0
- P2>AnswerDecision:myHand-0
## EXPECT
P1HASFORCE
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:POWER:5

---

# CondemnFirst_P2Declines_MaulHitsFor5_ThenTheLairStillGivesTheForce
## GIVEN
CommonSetup: rrk/ggw/{myLeader:LOF_009:1:1:1;myBase:LOF_020;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_031:1:0
WithP1GroundArenaUpgrade: 1:SEC_038
WithP2GroundArena: SOR_095:1:0
WithP2Hand: [SEC_038 SOR_095]
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:EffectStack-1
- P2>AnswerDecision:-
## EXPECT
P1HASFORCE
P2BASEDMG:5
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# CrystalCaves_ACondemnedForceUnitAttacks_TheBaseStillGivesTheForce
#// The same shape on the Talzin list's base, with a vanilla Force unit: P2 discloses, the Acolyte hits for 0,
#//   and Crystal Caves still gives the Force.
## GIVEN
CommonSetup: rrk/ggw/{myBase:LOF_029;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_028:1:0
WithP1GroundArenaUpgrade: 0:SEC_038
WithP2Hand: [SEC_038 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:EffectStack-1
- P2>AnswerDecision:myHand-0
## EXPECT
P1HASFORCE
P2BASEDMG:0
