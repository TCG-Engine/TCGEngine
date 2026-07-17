# Deployed_ReuseGrantedWhenDefeated
#// JTL_002 Grand Admiral Thrawn (deployed) — an UPGRADE-GRANTED When Defeated ability counts as
#// the unit's own and is reusable. LAW_141 Targeted For Removal grants the host
#// "When Defeated: An opponent creates Credit tokens equal to this unit's cost."
#// P1's SOR_128 (cost 1) carries LAW_141 and attacks LAW_124 (4/7) — it survives the hit and
#// counters for lethal, so SOR_128 dies as the attacker (When Defeated resolves inline).
#// The granted When Defeated gives P2 1 Credit; Thrawn (deployed, free, once/round) uses it again
#// → P2 gets a second Credit. P2 ends with 2 Credit tokens.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2CREDITCOUNT:2

---

# Deployed_ReuseWhenDefeated
#// JTL_002 Grand Admiral Thrawn (deployed leader unit) — When you use a "When Defeated" ability:
#// you may use that ability again (no exhaust; once each round).
#// Thrawn is deployed in the ground arena. JTL_087 dies attacking SOR_044 in space → its When
#// Defeated creates a TIE (use #1); Thrawn lets P1 use it again → a 2nd TIE (use #2).
#// Space arena = 2 TIEs (squadron died); ground arena keeps the Thrawn leader unit.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002:1:1:1;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2

---

# Undeployed_DeclineReuse
#// JTL_002 Grand Admiral Thrawn (undeployed) — declining the reuse.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates one TIE (use #1). Thrawn's
#// "may exhaust to use again" is declined → only one TIE. Arena = 1 TIE.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1

---

# Undeployed_ReuseWhenDefeated
#// JTL_002 Grand Admiral Thrawn (undeployed) — When you use a "When Defeated" ability:
#// you may exhaust this leader to use that ability again.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates a TIE (use #1); Thrawn exhausts
#// to use it again → a 2nd TIE (use #2). Squadron died, so arena = 2 TIEs.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:2

---

# UseWhenDefeatedThreeTimes
#// JTL_002 Thrawn (undeployed) + JTL_169 Shadow Caster together — a single "When Defeated"
#// ability used THREE times. JTL_087 dies attacking SOR_044:
#//   use #1 — original When Defeated (create a TIE)
#//   use #2 — Thrawn exhausts to use it again
#//   use #3 — Shadow Caster uses it again (reacts to the defeat)
#// Arena = Shadow Caster + 3 TIEs = 4.

## GIVEN
CommonSetup: gbk/bbk/{
  myLeader:JTL_002;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:4

---

# DoNothing_WhenEnemyWhenDefeatedUsed
#// JTL_002 Thrawn — "When YOU use a When Defeated ability" reacts only to the controller's own abilities.
#// P1's Daring Raid (TWI_170) defeats P2's Rhokai Gunship (SHD_164); Rhokai's When Defeated belongs to P2,
#// so Thrawn (P1's undeployed leader) offers no reuse — it stays ready, and Rhokai's ability fires once
#// (P2 → P1's base for 1).

## GIVEN
CommonSetup: rrk/rrk/{myResources:3;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_170
WithP2SpaceArena: SHD_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>Drain
- P2>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:1
P1LEADER:READY
P1NODECISION

---

# DoNothing_WhenUnitWithoutWhenDefeatedDies
#// JTL_002 Thrawn only reacts to a When Defeated ability actually being used. A friendly unit with no When
#// Defeated (SOR_225 TIE/ln Fighter) attacks into a lethal counter and dies; there is no When Defeated to
#// reuse, so Thrawn stays ready and no prompt appears.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:2
P1LEADER:READY
P1NODECISION

---

# Undeployed_ReuseClonedWhenDefeated
#// JTL_002 Thrawn (undeployed) reuses a When Defeated the dying unit gained by being a COPY (TWI_116
#// Clone as TWI_131 OOM-Series Officer, "When Defeated: Deal 2 damage to a base"). P2's Daring Raid
#// (TWI_170) defeats the Clone; its copied When Defeated deals 2 to P2's base, then Thrawn exhausts to
#// use it again for 2 more → 4 total. The Clone's controller (P1) is non-active, so the trigger is
#// drained with P1>Drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;theirResources:3;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_116
WithP1GroundArena: TWI_131:1:0
WithP2Hand: TWI_170

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# Undeployed_NoOfferWhenPlayed
#// JTL_002 Thrawn only reacts to a "When Defeated" ability being used, never a "When Played" one — even
#// on a card with a combined "When Played/When Defeated" ability (SOR_134 Ruthless Raider, "deal 2 to an
#// enemy base and 2 to an enemy unit"). Playing it from hand fires the WhenPlayed side (2 to P2's base;
#// no enemy unit in play so the unit-damage half fizzles with no target), and Thrawn offers no reuse.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myLeader:JTL_002;handCardIds:SOR_134}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1LEADER:READY
P1NODECISION

---

# Undeployed_ReuseNoOpWhenDefeated
#// JTL_002 Thrawn — reusing a When Defeated that finds no valid target still costs the exhaust; it just
#// resolves as a no-op. TWI_164 Hevy (4 HP, pre-damaged to 2 remaining) ("When Defeated: Deal 1 damage
#// to each enemy ground unit") is finished off by P2's Daring Raid (2 damage); its WD kills P2's only
#// ground unit, a 1/1 Battle Droid token (TWI_T01, removed from the game as a token, not discarded).
#// Thrawn's reuse offer still appears and can be accepted — the second resolution finds no enemy ground
#// unit left and does nothing further, but Thrawn still exhausts.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;theirResources:3;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TWI_164:1:2
WithP2GroundArena: TWI_T01:1:0
WithP2Hand: TWI_170

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseUpgradeGrantedWhenDefeated
#// JTL_002 Thrawn reuses a When Defeated GAINED FROM AN UPGRADE (TWI_218 Droid Cohort, "Attached unit
#// gains: 'When Defeated: Create a Battle Droid token.'"). SOR_095 (+1/+1 from Droid Cohort → 4/4,
#// pre-damaged to 1) attacks SOR_046 (3/7): deals 4, dies to the 3 counter; the granted When Defeated
#// creates a Battle Droid, then Thrawn exhausts to use it again for a second Battle Droid.
#// Regression for a real bug: the granted-WD dispatch case for TWI_218 was missing the
#// SWUCollectThrawnReuse hook every other granted-WD case has — Thrawn silently could not reuse it.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1
WithP1GroundArenaUpgrade: 0:TWI_218
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseUnitGrantedWhenDefeated
#// JTL_002 Thrawn reuses a When Defeated GAINED FROM ANOTHER UNIT (SOR_105 General Krell, "Each other
#// friendly unit gains: 'When Defeated: You may draw a card.'"). P1's Battlefield Marine (granted by
#// Krell) attacks into a lethal counter and dies; its granted When Defeated lets P1 draw a card, then
#// Thrawn exhausts to use it again for a second draw. Krell itself survives.
#// Regression for a real bug: the SOR_105 dispatch case was missing the SWUCollectThrawnReuse hook.

## GIVEN
CommonSetup: ggw/brw/{theirBase:SOR_021;myLeader:JTL_002}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_105:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_105
P1HANDCOUNT:2
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseEventGrantedWhenDefeated
#// JTL_002 Thrawn reuses a When Defeated GAINED FROM AN EVENT (TWI_129 In Defense of Kamino, "For this
#// phase, each friendly Republic unit gains: 'When Defeated: Create a Clone Trooper token.'"). TWI_109
#// (Republic) attacks SOR_046 (3/7) and dies to the counter; its granted When Defeated creates a Clone
#// Trooper, then Thrawn exhausts to use it again for a second Clone Trooper.
#// Regression for a real bug: the TWI_129 dispatch case was missing the SWUCollectThrawnReuse hook.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_129;myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: TWI_109:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1LEADER:EXHAUSTED

---

# Undeployed_ChooseEachWhenDefeatedAsResolved
#// JTL_002 Thrawn offers the reuse SEPARATELY for each When Defeated ability as it resolves, when a
#// single defeat fires MORE THAN ONE (SHD_164 Rhokai Gunship's own innate "deal 1 damage to a unit or
#// base" PLUS an attached TWI_218 Droid Cohort's granted "create a Battle Droid token"). Rhokai (3/2 with
#// the upgrade) attacks SOR_046 (3/7) and dies to the 3 counter. Its own WD resolves first (targets P2's
#// base for 1) and offers a reuse — DECLINED. The granted Droid Cohort WD then auto-resolves (creates a
#// Battle Droid) and offers its OWN separate reuse — ACCEPTED, creating a second Battle Droid.
#// Regression for a real bug found while writing this test: the Thrawn PENDING guard was a single flag
#// per player, so the second (still-unanswered) trigger's offer was silently swallowed by the first
#// offer's still-pending guard — only the FIRST simultaneous When-Defeated ever got offered a reuse. Fix:
#// SWUCollectThrawnReuse's guard is now keyed per (cardID, mzID, grantedType), not just $owner.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: SHD_164:1:0
WithP1GroundArenaUpgrade: 0:TWI_218
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:NO
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseWithSuperlaserTechnician
#// JTL_002 Thrawn interacting with SHD_085 Superlaser Technician ("When Defeated: You may put this unit
#// into play as a resource and ready it") — a self-referential WD that removes its OWN source from the
#// discard on first use. P1's Technician (2/1) attacks a Wampa (SOR_164, 4/5): deals 2 (Wampa survives),
#// Wampa counters 4 → Technician (1 HP) dies. Its WD resolves (YES) → it leaves the discard and enters as
#// a ready resource. Thrawn then offers to reuse that same WD; accepting still exhausts Thrawn, but the
#// ability itself finds the Technician no longer in discard and is a safe no-op (no second copy created,
#// no crash).

## GIVEN
CommonSetup: ggk/ggk/{myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: SHD_085:1:0
WithP1Resources: 2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:3
P1RESAVAILABLE:3
P1DISCARDCOUNT:0
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseWithShuttleSt149
#// JTL_002 Thrawn interacting with JTL_242 Shuttle ST-149 ("When Played/When Defeated: You may take
#// control of a token upgrade on a unit and attach it to a different eligible unit") — a multi-step
#// ability (choose the token, then the destination). P2's Vanquish defeats P1's Shuttle (non-active P1
#// drains the trigger). Note the resolution ORDER: Thrawn's reuse offer is queued right after the first
#// (token-choice) decision, so it appears BEFORE the ability's own destination pick resolves — here the
#// destination auto-resolves regardless (only one other eligible unit in play). P1 moves the first of two
#// Experience tokens off Alliance X-Wing (SOR_237) onto Green Squadron A-Wing (SOR_141), accepts the
#// reuse (exhausting Thrawn), then moves the second Experience token the same way — SOR_141 ends with
#// both.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: [JTL_242:1:0 SOR_237:1:0 SOR_141:1:0]
WithP1SpaceArenaUpgrade: 1:SOR_T01
WithP1SpaceArenaUpgrade: 1:SOR_T01
WithP2Hand: TWI_077

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_141
P1SPACEARENAUNIT:1:UPGRADECOUNT:2
P1LEADER:EXHAUSTED
P1DISCARDCOUNT:1

---

# Undeployed_ReuseStatReferencingWhenDefeated
#// JTL_002 Thrawn reusing a When Defeated whose EFFECT AMOUNT is derived from the source unit's own
#// stats (JTL_104 Raddus, "When Defeated: Deal damage equal to this unit's power to an enemy unit").
#// Raddus (power 8) attacks Annihilator (power 12/hp 12) and dies to the lethal counter; its WD deals 8
#// (its power) to Krayt Dragon (chosen target 1). Thrawn exhausts to reuse it — the SECOND use again
#// deals Raddus's power (8), this time at Annihilator (already carrying 8 combat damage), for 16 total,
#// defeating it.
#// NOTE (found, NOT fixed — out of scope for Thrawn): with an Experience token attached, Raddus's WD
#// deals its BASE printed power (8) instead of the buffed power (9) — GetZoneObject($mzID) after a
#// combat defeat appears to lose the unit's Subcards/buffs before the WD closure reads
#// ObjectCurrentPower. This is a pre-existing bug in JTL_104's own handler (Custom/CardDQHandlers.php),
#// reproducible with no Thrawn involved at all — not part of the WD-reuse family this session covers.
#// Filed here for a future JTL_104 fix; this test deliberately uses an unbuffed Raddus to isolate
#// Thrawn's reuse behavior from that unrelated bug.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_002}
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP2SpaceArena: JTL_041:1:0
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:8
P2SPACEARENACOUNT:0
P1LEADER:EXHAUSTED
P1DISCARDCOUNT:1

---

# Undeployed_ReuseWithOpponentDoesSomethingWhenDefeated
#// JTL_002 Thrawn reusing a When Defeated that makes the OPPONENT make a choice (JTL_183 Zygerrian
#// Starhopper, "When Defeated: Deal 2 indirect damage divided as you choose between yourself and an
#// opponent" — the controller picks who takes it, then THAT player distributes it). Starhopper dies in
#// combat; P1 sends the 2 indirect to the opponent (P2), who assigns it to their own base. Thrawn then
#// exhausts to use it again — P1 again sends it to the opponent, who assigns another 2 to their base, for
#// 4 total. Proves the reuse re-collects the SAME cross-player choice sequence each time, not just a
#// same-player effect.

## GIVEN
CommonSetup: ggk/ggk/{myLeader:JTL_002}
WithActivePlayer: 1
WithP1SpaceArena: JTL_183:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2
- P1>AnswerDecision:YES
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:2

## EXPECT
P2BASEDMG:4
P1LEADER:EXHAUSTED

---

# Undeployed_NoOfferForUpgradeReattachWhenDefeated
#// JTL_002 Thrawn does NOT currently offer a reuse for TWI_069 Roger Roger's "When Defeated: Attach this
#// upgrade to a friendly Battle Droid token" — a documented gap, not a translation choice. Roger Roger's
#// re-attach is special-cased as an INLINE synchronous operation inside defeat processing
#// (`_SWURogerRogerReattach`, called from `SWUDiscardHostSubcards` in Custom/CombatLogic.php)
#//TODO: fix it so that we can re-attach no matter how pointless that would be

## GIVEN
CommonSetup: rrk/rrk/{myLeader:JTL_002}
P1OnlyActions: true
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_069
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:TWI_069
P1LEADER:READY
P1NODECISION

---

# Undeployed_ReuseDoesNotDuplicateBounty
#// JTL_002 Thrawn reusing a unit's own When Defeated is INDEPENDENT of that unit's Bounty reward — the
#// bounty (collected by whoever DEFEATED it) fires exactly once no matter how many times Thrawn replays
#// the controller's own WD. SHD_058 Val ("Bounty — Deal 3 damage to a unit" / "When Defeated: Give 2
#// Experience tokens to a friendly unit") is defeated outright by P2's Vanquish. Val's own WD (give 2
#// Experience to Wampa, her only friendly unit — auto-resolves as the sole legal target) resolves under
#// P1 (drained, since P1 is non-active); Thrawn exhausts to reuse it for 2 more Experience (4 total on
#// Wampa). Separately, P2 (the defeater) collects Val's Bounty exactly once — 3 damage to Wampa — and has
#// no further decision (P2NODECISION): the bounty reward is NOT part of the WhenDefeated trigger chain
#// Thrawn hooks into, so it isn't offered a second time.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6;myLeader:JTL_002}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SHD_058:1:0
WithP1GroundArena: SOR_164:1:0
WithP2Hand: TWI_077

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1LEADER:EXHAUSTED
P2NODECISION

---

# Undeployed_ReuseActivatedByChimaera
#// JTL_002 Thrawn reuses a When Defeated that was ACTIVATED (not triggered by a real defeat) by JTL_039
#// Chimaera's When Played, "you may use a 'When Defeated' ability on another friendly unit." P1 plays
#// Chimaera and uses JTL_087 TIE Ambush Squadron's own When Defeated ("Create a TIE Fighter token") —
#// JTL_087 stays in play (never defeated), one TIE is created. Thrawn then exhausts to use that same
#// ability again for a second TIE. Arena = JTL_087 + Chimaera + 2 TIEs = 4.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_002;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:4
P1LEADER:EXHAUSTED

---

# Undeployed_ReuseReevaluatesSourcePower
#// JTL_002 Thrawn re-evaluates the source unit's CURRENT state when reusing a When Defeated whose amount
#// depends on it (ASH_195 Helgait, "distribute Advantage equal to this unit's power"), activated by
#// JTL_039 Chimaera on a LIVING Helgait (power 6, no real defeat — CollectWhenDefeatedTriggers' snapshot
#// never runs). First use distributes 6 Advantage, all onto Helgait itself (now power 12). Thrawn
#// exhausts to reuse the SAME ability — the second use must offer 12 (Helgait's now-buffed power), not
#// the stale printed 6, and piling all 12 more onto Helgait brings it to power 24.
#// Regression for a real bug found while writing this test: ASH_195's handler unconditionally fell back
#// to CardPower('ASH_195') (printed base) whenever its defeat-time snapshot was empty — which is ALWAYS
#// true for a Chimaera-activated living unit (no defeat ever snapshotted it) and also true on every
#// SECOND+ reuse (the snapshot was consumed and cleared after the first read). Fix, in
#// Custom/CardDQHandlers.php: the snapshot is no longer cleared after use (a dead Helgait's power is
#// frozen, so repeat reuses of the same real defeat keep reading the correctly-captured value), and the
#// fallback now re-reads the unit's LIVE current power via GetZoneObject($mzID) when it's still in play —
#// safe here because Chimaera/Thrawn/Helgait are always the same player's own frame, unlike the
#// cross-player defeat case the snapshot exists to protect against.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_002;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1GroundArena: ASH_195:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0:6
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0:12

## EXPECT
P1GROUNDARENAUNIT:0:POWER:24
P1LEADER:EXHAUSTED

---

# Undeployed_NoOfferForWhenPlayedOfPreviouslyDefeatedUnit
#// JTL_002 Thrawn — replaying a unit whose combined "When Played/When Defeated" ability already fired
#// once this phase (as a When Defeated) does NOT get a reuse offer when it fires again as a When Played.
#// SOR_134 Ruthless Raider (Vehicle) is defeated by P2's Vanquish — its WD deals 2 to P2's base (Thrawn's
#// reuse offer is DECLINED). P1 then plays JTL_121 Salvage, replaying Ruthless Raider from discard (a
#// Vehicle) — its WhenPlayed side fires again, dealing 2 more to P2's base (4 total), but Thrawn offers no
#// reuse for it: WhenPlayed is never eligible, regardless of the card having triggered a WhenDefeated
#// earlier the same phase.

## GIVEN
CommonSetup: rrk/bbk/{myResources:10;theirResources:5;myLeader:JTL_002;handCardIds:JTL_121}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: SOR_134:1:0
WithP2Hand: TWI_077

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:NO
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_134
P2BASEDMG:4
P1LEADER:READY
P1NODECISION

---

# Deployed_ReuseOncePerRound
#// JTL_002 Thrawn (deployed leader unit) — the free reuse is capped at once EACH ROUND (leader NumUses
#// budget), even across TWO DIFFERENT units' When Defeated triggers. Two JTL_087 TIE Ambush Squadrons
#// (pre-damaged to 1, so a 2-power counter kills each) attack two separate SOR_044 defenders in
#// consecutive actions. The FIRST defeat's "Create a TIE Fighter token" WD is reused (free, accepted) —
#// a second TIE. The SECOND defeat's own WD fires normally (a third TIE) but offers NO reuse — the
#// once-per-round budget was already spent on the first. Final space arena = 3 TIE tokens (both
#// JTL_087s died; no fourth TIE from a second reuse).

## GIVEN
CommonSetup: gbk/bbk/{myLeader:JTL_002:1:1:1;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P1SPACEARENAUNIT:2:CARDID:JTL_T01
P1NODECISION
