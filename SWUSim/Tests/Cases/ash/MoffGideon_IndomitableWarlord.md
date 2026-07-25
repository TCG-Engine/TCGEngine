# ImperialDefeated_DiscountedPlay
#// ASH_008 Moff Gideon — Leader Action [Exhaust]: if a friendly Imperial unit was defeated this phase, play
#// a unit from your hand costing 1 less. A SEC_080 (Imperial) dies attacking SOR_038, then Gideon plays the
#// hand SEC_080 (cost 2, Command/Villainy — on-aspect) for 1 resource (proving the -1 discount: 2 → 1 left).
## GIVEN
CommonSetup: ggk/brk/{
  myLeader:ASH_008
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_080
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_038:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# NoImperialDefeated_NoPlay
#// ASH_008 Moff Gideon — the discounted play requires a friendly Imperial unit to have been defeated this
#// phase. With none defeated, using the ability plays nothing; the hand unit stays put.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SEC_080
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# Deployed_CopySentinel_FromImperialDiscard
#// ASH_008 Moff Gideon (DEPLOYED) — gains Sentinel while an Imperial unit with Sentinel (SOR_229 Cell Block
#// Guard) is in P1's discard pile.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_229}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_008
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Deployed_CopyOverwhelm_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Overwhelm from an Imperial Overwhelm unit (SOR_232 AT-ST) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_232}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# Deployed_CopyShielded_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Shielded from an Imperial Shielded unit (SOR_180) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_180}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded

---

# Deployed_NonImperialSentinelInDiscard_NoCopy
#// ASH_008 Moff Gideon (deployed) — a NON-Imperial Sentinel unit (SOR_063) in discard does NOT grant Sentinel.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_063}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Deployed_OpponentDiscardSentinel_NoCopy
#// ASH_008 Moff Gideon (deployed) — an Imperial Sentinel unit in the OPPONENT's discard does not grant Sentinel
#// (only YOUR discard pile counts).
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;theirDiscardCardIds:SOR_229}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# Deployed_CopyAmbush_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Ambush from an Imperial Ambush unit (SOR_115 Agent Kallus) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_115}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_008
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# Deployed_CopyGrit_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Grit from an Imperial Grit unit (SOR_165 Occupier Siege Tank) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_165}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# Deployed_CopyHidden_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Hidden from an Imperial Hidden unit (LOF_132 Grand Inquisitor) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:LOF_132}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Hidden

---

# Deployed_CopySaboteur_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Saboteur from an Imperial Saboteur unit (SOR_133 Seventh Sister) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_133}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# Deployed_CopySupport_FromImperialDiscard
#// ASH_008 Moff Gideon (deployed) — gains Support from an Imperial Support unit (ASH_036 Rukh) in discard.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:ASH_036}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Support

---

# Deployed_RaidRestorePiloting_NotCopied
#// ASH_008 Moff Gideon (deployed) — only combat-relevant keywords are copied. Imperial units carrying Raid
#// (SHD_187 Lurking TIE Phantom), Restore (LOF_032 Magistrate's Scout), and Piloting (JTL_036 Iden Versio) in
#// discard grant NONE of those keywords to Moff Gideon.
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SHD_187,LOF_032,JTL_036}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:NOTKEYWORD:Restore
P1GROUNDARENAUNIT:0:NOTKEYWORD:Piloting

---

# Deployed_CopyEveryValidKeyword_MultiImperialDiscard
#// ASH_008 Moff Gideon (deployed) — copies EVERY valid keyword across multiple Imperial units in discard at
#// once: Grit (SOR_165), Ambush (SOR_115), Saboteur (SOR_133), Support (ASH_036), and Overwhelm (SOR_232 AT-ST).
## GIVEN
CommonSetup: ggk/brk/{myLeader:ASH_008:1:1;discardCardIds:SOR_165,SOR_115,SOR_133,ASH_036,SOR_232}
SkipPreGame: true
P1OnlyActions: true
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:0:HASKEYWORD:Support
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm
