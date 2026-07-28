# BuffsEnteringUnit
#// ASH_041 Outcast (Space, 1/4) — When a friendly unit enters play (including this one): it gets +1/+0 for
#// this phase. With ASH_041 in play, P1 plays SOR_095 (3/3); it enters at power 4.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_041:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4

---

# SelfBuffOnEntry
#// ASH_041 Outcast — "including this one": when ASH_041 itself enters play it buffs itself +1/+0 for this
#// phase, so it enters at power 2 (base 1 + 1).
## GIVEN
CommonSetup: ryk/ryk/{myResources:2;handCardIds:ASH_041}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_041
P1SPACEARENAUNIT:0:POWER:2

---

# EachEnteringUnitBuffed
#// ASH_041 Outcast — the buff fires for EACH friendly unit that enters. P1 plays SOR_095 then SEC_080; both
#// enter at power 4 (base 3 + 1).
## GIVEN
CommonSetup: yyw/yyk/{myResources:12;handCardIds:SOR_095,SEC_080}
WithP1SpaceArena: ASH_041:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4

---

# EnemyUnit_NoBuff
#// ASH_041 Outcast — the +1/+0 fires only for FRIENDLY units entering play. When the opponent plays
#// LAW_211 Black Sun Patroller (2/2), it enters at its printed power 2 (no buff) and ASH_041 is unchanged.
## GIVEN
CommonSetup: ryk/yyk/{theirhandCardIds:LAW_211;theirResources:2}
WithP1SpaceArena: ASH_041:1:0
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
## WHEN
- P2>PlayHand:0
## EXPECT
P2SPACEARENAUNIT:0:CARDID:LAW_211
P2SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:POWER:1

---

# LeaderDeploy_Buffed
#// ASH_041 Outcast — deploying a friendly leader makes it enter play, triggering the buff. SOR_011 Grand
#// Inquisitor (printed power 3) deploys and gets +1/+0 for this phase → power 4.
## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myLeader:SOR_011}
WithP1SpaceArena: ASH_041:1:0
P1OnlyActions: true
## WHEN
- P1>DeployLeader:0
## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:POWER:4

---

# PilotUpgrade_NoTrigger
#// ASH_041 Outcast — playing a Pilot as an UPGRADE does not put a unit into play, so the buff does not fire.
#// JTL_211 Independent Smuggler is played with Piloting onto LOF_192 N-1 Starfighter (printed power 3): the
#// host gains +1 from the pilot → power 4, and ASH_041 is unaffected (stays power 1).
## GIVEN
CommonSetup: ryk/ryk/{myResources:2}
WithP1Hand: JTL_211
WithP1SpaceArena: ASH_041:1:0
WithP1SpaceArena: LOF_192:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_211
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:0:POWER:1

---

# TokenUnitsEntering_Buffed
#// ASH_041 Outcast — a CREATED token unit enters play, so it gets the +1/+0 too. With Outcast in play, playing
#// SEC_191 (When Played: create 2 Spy tokens, SEC_T01 0/2) buffs each entering Spy to power 1 (and SEC_191
#// itself to 4).
## GIVEN
CommonSetup: yyw/yyk/{myResources:8;handCardIds:SEC_191}
WithP1SpaceArena: ASH_041:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:1
P1GROUNDARENAUNIT:2:POWER:1

---

# TakeControlOfEnemyUnit_NoTrigger
#// ASH_041 Outcast — taking control of an enemy unit is a control change, not an enters-play event, so the
#// +1/+0 buff does NOT fire. P1 uses SHD_256 Mercenary Gunship's "Action [4]: take control of this unit" on
#// the P2-controlled Gunship: it moves to P1's space arena at its printed power 3 (no buff) and Outcast is
#// unchanged (power 1). P1 spends 4 of 5 resources.
## GIVEN
CommonSetup: yyw/yyk/{myResources:5}
WithP1SpaceArena: ASH_041:1:0
WithP2SpaceArena: SHD_256:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:theirSpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_041
P1SPACEARENAUNIT:0:POWER:1
P1SPACEARENAUNIT:1:CARDID:SHD_256
P1SPACEARENAUNIT:1:POWER:3
P2SPACEARENACOUNT:0
P1RESAVAILABLE:1
