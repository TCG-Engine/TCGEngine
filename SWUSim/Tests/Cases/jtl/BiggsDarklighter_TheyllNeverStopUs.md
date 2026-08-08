# FighterGainsOverwhelm
#// JTL_150 Biggs Darklighter (pilot) — If the attached unit is a Fighter, it gains Overwhelm. The Fighter
#// host SOR_237 with the pilot gains Overwhelm.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm

---

# TransportGainsPlus01
#// JTL_150 Biggs (pilot, +2/+1) — "If attached unit is a Transport, it gets +0/+1." Host SOR_193 Millennium
#// Falcon (3/4 Transport) becomes 5 power / 6 HP (3+2 / 4+1+1: the +1 pilot HP plus the +1 Transport HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_193:1:0
WithP1SpaceArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_193
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6

---

# SpeederGainsGrit
#// JTL_150 Biggs (pilot) — "If attached unit is a Speeder, it gains Grit." Host SEC_214 (Speeder) gains Grit.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit

---

# NonMatchingTrait_NoConditionalGrant
#// JTL_150 Biggs — the trait grants are conditional. On a Capital Ship host (SOR_052, neither Fighter,
#// Transport, nor Speeder) Biggs gives only his flat +2/+1 (8/10) and NO Overwhelm or Grit.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_052:1:0
WithP1SpaceArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:8
P1SPACEARENAUNIT:0:HP:10
P1SPACEARENAUNIT:0:NOTKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:NOTKEYWORD:Grit

---

# WalkerHost_NoConditionalGrant
#// JTL_150 Biggs — the second non-matching-trait case. The existing negative uses a Capital Ship; this
#// one uses a WALKER (SOR_037, Imperial/Vehicle/Walker, 5/5 ground), confirming the grants key off the
#// specific traits Fighter / Transport / Speeder rather than merely "is it one particular other trait".
#// Biggs still contributes his flat +2/+1 -> 7/6, but grants neither Overwhelm nor Grit.
#// SOR_037's printed Sentinel is untouched, which also shows the host keeps its own keywords.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0
WithP1GroundArenaUpgrade: 0:JTL_150

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:0:NOTKEYWORD:Overwhelm
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
