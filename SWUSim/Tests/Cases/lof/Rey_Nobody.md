# Deployed_WhenDeployed_DiscardHandDraw2
#// LOF_012 Rey — When Deployed: you may discard your hand. If you do, draw 2 cards. Rey deploys
#// (7 resources), discards her 2-card hand, draws 2 → hand 2, discard 2.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:LOF_012
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:2
P1DISCARDCOUNT:2
P1DECKCOUNT:1

---

# NonUnitForceDeal1
#// LOF_012 Rey — Action [Exhaust]: If you played a non-unit Force card this phase, deal 1 damage to a unit.
#// P1 plays LOF_074 (a Force upgrade) onto Plo Koon, then the leader deals 1 to SOR_046.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LOF_012;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_074
WithP1Resources: 1
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ForcePilotUpgrade_CountsAsNonUnitForce
#// LOF_012 Rey — a Force PILOT played AS AN UPGRADE counts as "playing a non-unit Force card." Anakin
#// (JTL_197, a Force pilot) is played as an upgrade on the TIE Advanced; Rey's ability then deals 1 to
#// SOR_046. Regression: pilots route through the Piloting path, which previously didn't set the
#// SWU_PLAYED_NONUNIT_FORCE flag that the generic upgrade path sets.
## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_197
WithP1SpaceArena: SOR_231:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ForcePilotPlayedAsUnit_DoesNotCount
#// The same Force pilot played AS A UNIT is a Force UNIT card, not a non-unit — so it does NOT enable
#// Rey's ability (SOR_046 stays at 0 damage).
## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_197
WithP1SpaceArena: SOR_231:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>UseLeaderAbility
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_NoForcePlayed_UsableButNoDamage
#// LOF_012 Rey FRONT — Action [Exhaust]: "If you played a non-unit Force card this phase, deal 1 damage to
#// a unit." The ability is still USABLE when no Force non-unit card was played, it just has no effect. P1
#// played nothing this phase; using the ability exhausts Rey and deals no damage. (Intended: "should only have an
#// effect if the controller played a Force non-unit card this phase, but still be usable otherwise".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_ForceEvent_Deal1
#// LOF_012 Rey FRONT — a Force EVENT played this phase satisfies "played a non-unit Force card". P1 plays
#// Drain Essence (LOF_041, a Force event: deal 2 to a unit + create Force) onto the enemy SOR_046, then uses
#// Rey to deal 1 damage to her own Plo Koon (LOF_050). (Intended: "should damage a unit if a Force event card was
#// played by controller".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: LOF_041
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Front_ForceUnitPlayed_NoEffect
#// LOF_012 Rey FRONT — a Force UNIT card does NOT satisfy "non-unit Force card". P1 plays Jedi Guardian
#// (LOF_049, a Force UNIT), then uses Rey: the flag is not set, so the ability exhausts Rey with no damage.
#// (Intended: "should not be able to damage a unit as a Force unit card was played".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: LOF_049
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Front_OpponentForceNonUnit_NoEffect
#// LOF_012 Rey FRONT — the "played a non-unit Force card this phase" flag is per-player. The OPPONENT (P2)
#// plays Drain Essence (LOF_041, a Force event) at P1's Plo Koon; that sets P2's flag, not P1's. P1 then
#// uses Rey and it has no effect (Plo Koon shows only Drain Essence's 2 damage, no extra 1). (Intended: "should not
#// be able to damage a unit as a Force non-unit card was played by the opponent".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Hand: LOF_041
WithP2Resources: 3
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Deployed_EmptyHand_DiscardDraw2
#// LOF_012 Rey DEPLOYED — When Deployed: "you may discard your hand. If you do, draw 2 cards." With an EMPTY
#// hand P1 still triggers: discards 0, draws 2 from a 3-card deck → hand 2, deck 1, discard 0. (Intended: "should
#// be able to discard its controller's 0 hand size hand and draw two cards".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Deck: SOR_237
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:1
P1DISCARDCOUNT:0

---

# Deployed_Decline_NoDraw
#// LOF_012 Rey DEPLOYED — the discard-and-draw is a "you may": P1 declines. Hand (2) is untouched, nothing is
#// discarded, and the deck (3) is not drawn from. (Intended: "should be able to pass discard and draw two cards
#// ability".)

## GIVEN
CommonSetup: rrw/bbk/{myLeader:LOF_012}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 7
WithP1Hand: SOR_095
WithP1Hand: SEC_080
WithP1Deck: SOR_237
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:2
P1DECKCOUNT:3
P1DISCARDCOUNT:0
