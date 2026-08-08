# NextUnitAmbush
#// LOF_180 Deceptive Shade (2/3) — When Defeated: the next unit you play this phase gains Ambush for this
#// phase. Deceptive Shade trades with the enemy 3/1 (both defeated), then the next unit P1 plays (SOR_095)
#// gains Ambush. (No enemy remains, so Ambush has no attack target — the keyword is just present.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:SOR_095}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NextUnitAmbush_SurvivesEventInBetween
#// LOF_180 Deceptive Shade — an EVENT played between the defeat and the next unit does NOT consume the "next
#// unit gains Ambush" charge (an event is not a unit entering play). Shade trades with SOR_128; P1 plays
#// Resupply (SOR_126, an event); the next UNIT (SOR_095) still gains Ambush. (Intended: "gives Ambush to the next
#// unit, even if an event is played in between".)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_126
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NoAmbush_ForOpponentsNextUnit
#// LOF_180 Deceptive Shade — the "next unit gains Ambush" charge belongs to Shade's CONTROLLER (P1), not the
#// opponent. Shade (P1) attacks and trades with P2's SOR_128 (3/1), so Shade is defeated under P1's control;
#// the turn passes to P2, who plays Battlefield Marine (SOR_095) — it does NOT gain Ambush. (Intended: "does not
#// give Ambush to the next unit played by the opponent".)

## GIVEN
CommonSetup: yyk/ggw/{myResources:3;theirResources:6;theirBase:SOR_021;myBase:SOR_021}
SkipPreGame: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# Ambush_ExpiresAtEndOfPhase
#// LOF_180 Deceptive Shade — the "next unit gains Ambush this phase" charge EXPIRES at end of phase. Shade is
#// defeated, then both players pass to the next action phase without P1 having played a unit; the unit P1
#// plays in the NEW phase (SOR_095) does NOT gain Ambush. (Intended: "the effect expires at the end of the phase".)

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_095
WithP1Deck: SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# NoAmbush_ForDeployedLeaderUnit
#// LOF_180 Deceptive Shade — deploying a leader is NOT "playing a unit", so it does not consume the charge
#// nor gain Ambush. Cad Bane (SHD_014) deploys (6+ resources) after Shade is defeated and does NOT gain
#// Ambush. (Intended: "does not give Ambush to deployed leader units".)

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;myLeader:SHD_014;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:SHD_014
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# NoAmbush_ForUnitPlayedAfterTheNextUnit
#// LOF_180 Deceptive Shade — only the FIRST unit played after the defeat gets Ambush. Shade is defeated;
#// P1 plays SOR_095 (gains Ambush, consumes the charge); a SECOND unit (SOR_128) played afterward does NOT
#// gain Ambush. (Intended: "does not give Ambush to friendly units played after the next unit played this phase".)

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_095
WithP1Hand: SOR_128

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush

---

# Ambush_FollowsControllerAfterNGORSteal
#// LOF_180 Deceptive Shade — when the enemy steals-and-defeats Shade with No Glory, Only Results (JTL_043:
#// take control of a non-leader unit, then defeat it), P2 controlled Shade at defeat time, so the charge is
#// P2's: P1's next unit (SOR_095) gets NO Ambush, but P2's next unit (Wampa SOR_164) DOES. (Intended: "if defeated
#// with NGOR, should give Ambush to the next unit played by the player who stole the Deceptive Shade".)

## GIVEN
CommonSetup: yyk/bbk/{myResources:6;theirResources:12;theirBase:SOR_021;myBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LOF_180:1:0
WithP1Hand: SOR_095
WithP2Hand: JTL_043
WithP2Hand: SOR_164

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# NextUnitAmbush_SurvivesPilotUpgradeInBetween
#// LOF_180 Deceptive Shade — a Pilot played AS AN UPGRADE between the defeat and the next unit does NOT
#// consume the "next unit gains Ambush" charge (it's not a unit entering play). Shade trades with SOR_128;
#// then Astromech Pilot (JTL_057) is played as a pilot on the TIE Advanced; the next UNIT (SOR_095) still
#// gains Ambush. (Intended: "gives ambush to the next unit, even if a piloting upgrade is played in between.")
## GIVEN
CommonSetup: ggw/rrk/{myResources:8;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: LOF_180:1:0
WithP1SpaceArena: SOR_231:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: JTL_057
WithP1Hand: SOR_095
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:-
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
