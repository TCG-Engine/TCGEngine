# AdvantageBuffedDefeat_BeforeShed_Distribute12
#// ASH_195 Helgait — When Defeated resolves BEFORE the defender's Advantage tokens shed at "defense end",
#// so its power still counts those tokens. Their Helgait carries 6 Advantage tokens (ASH_T02, +1/+0 each →
#// 12/4) and 2 damage (2 HP left); my Spy (SEC_T01, 0/2 Raid 2) defeats it. In CollectCombatStep3Triggers
#// the When Defeated snapshot is taken before _SWUDefeatAllAdvantageTokens sheds the defender's tokens, so
#// the distribute pool is 12, not 6.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0          # my Spy (0/2, Raid 2) — the attacker
WithP2GroundArena: ASH_195:1:2          # their Helgait (6/4) + 6 Advantage below → 12/4, 2 damage (2 HP left)
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2GroundArenaUpgrade: 0:ASH_T02
WithP2SpaceArena: JTL_039:1:0           # their surviving unit that receives the Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_all_12_Advantage_among_friendly_units_or_none

---

# DefenderDefeat_FullPower
#// ASH_195 Helgait (Ground, 6/4) — When Defeated distributes Advantage equal to THIS unit's power (6),
#// regardless of how or where it was defeated. Regression for game 2622: Helgait is the DEFENDER at
#// P2 ground-0, killed by a SEC_T01 Spy (0/2, Raid 2 → attacks at power 2) into its 2 remaining HP.
#//
#// The bug: the When-Defeated closure runs under Helgait's controller's frame (P2) but re-resolves the
#// defeated unit from the mzID "theirGroundArena-0" captured in the ACTIVE player's (P1) frame. Under
#// P2's frame that string points to P1's ground-0 — here LAW_039 Latts Razzi (power 2) — so it offered
#// only 2 Advantage instead of Helgait's 6. Assert on the distribute prompt, which embeds the pool.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: LAW_039:1:0          # Latts Razzi (power 2) — survives; P1 ground-0 (the frame-bug decoy)
WithP1GroundArena: SEC_T01:1:0          # Spy (0/2, Raid 2) — the attacker at P1 ground-1
WithP2GroundArena: ASH_195:0:2          # Helgait (6/4) exhausted, 2 damage → 2 HP left (the defender)
WithP2SpaceArena: JTL_039:1:0           # Chimaera — the surviving friendly unit that would receive Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_all_6_Advantage_among_friendly_units_or_none

---

# DistributePowerOnDefeat
#// ASH_195 Helgait (Ground, 6/4, cost 5) — When Defeated: you may distribute a number of Advantage tokens
#// equal to this unit's power (6) among friendly units. Helgait attacks SOR_038 (7/4) and dies to the 7
#// counter; its 6 Advantage are piled onto SOR_095 (now 3 + 6 = 9 power, 6 Advantage tokens).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_195:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_038:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0:6
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:6
P1GROUNDARENAUNIT:0:POWER:9

---

# ExperienceBuffedDefeat_Distribute12
#// ASH_195 Helgait — When Defeated distributes Advantage equal to THIS unit's power, INCLUDING buffs from
#// subcards, snapshotted at defeat time. Their Helgait carries 6 Experience tokens (SOR_T01, +1/+1 each →
#// 12/10) and 8 damage (2 HP left). My measly Spy (SEC_T01, 0/2 Raid 2 → attacks at 2) finishes it. The
#// distribute pool must be 12 (Helgait's buffed power), assigned among their surviving units.
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0          # my Spy (0/2, Raid 2) — the attacker
WithP2GroundArena: ASH_195:1:8          # their Helgait (6/4) + 6 Experience below → 12/10, 8 damage (2 HP left)
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArenaUpgrade: 0:SOR_T01
WithP2SpaceArena: JTL_039:1:0           # their surviving unit that receives the Advantage
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
## EXPECT
P2GROUNDARENACOUNT:0
P2DECISIONTOOLTIP:Distribute_all_12_Advantage_among_friendly_units_or_none

---

# NoGloryTakeControlDefeat_DistributeMine
#// ASH_195 Helgait defeated via JTL_043 No Glory, Only Results — "Take control of a non-leader unit, then
#// defeat it." I take their Helgait (it becomes mine), then defeat it, so its When Defeated fires under MY
#// control and distributes its power (6) Advantage among MY units. Here I spread the 6 across two of my
#// Battlefield Marines (4 + 2), proving the take-control-then-defeat frame is consistent (controller ==
#// defeating player == me) and the distribution lands on my side, divided as I choose.
## GIVEN
CommonSetup: yyk/yyk/{myResources:13;handCardIds:JTL_043}
WithP1GroundArena: SOR_095:1:0          # my Marine A — receives 4 Advantage
WithP1GroundArena: SOR_095:1:0          # my Marine B — receives 2 Advantage
WithP2GroundArena: ASH_195:1:0          # their Helgait (6/4)
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0:4,myGroundArena-1:2
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:4
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:2
P1GROUNDARENAUNIT:0:POWER:7

---

# EqualNotUpTo_APartialSplitIsREFUSED
#// ⚠ THE WORDING IS "YOU MAY … EQUAL TO", NOT "UP TO". Those are different offers and the card only
#// makes the first: "You may" makes the ABILITY optional, so declining outright is legal; "equal to
#// this unit's power" fixes the AMOUNT once you engage. There is no middle — you cannot bank part of
#// the pool and keep the rest.
#//
#// Helgait was queued as UPTO, so a player could place 2 of 6 Advantage and confirm, pocketing the
#// other 4. Here the answer assigns 2 of 6; the split must be REFUSED outright, leaving every unit
#// with no Advantage — not partially applied, which is the tempting-but-wrong "clamp" behaviour.
#//
#// ⚠ Asserted on the SERVER path deliberately. The client's confirm button is UX — it cannot be the
#// guard, because the answer reaches the handler as a plain "mz:n" string however it was produced,
#// and SPLIT_ADVANTAGE applied whatever arrived with no check on the total at all.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0
WithP2GroundArena: ASH_195:1:2
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P2>AnswerDecision:myGroundArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# EqualNotUpTo_DecliningEntirelyIsSTILLLegal
#// The other endpoint, and the reason this is ALLORNONE rather than a flat "must assign all": the
#// ability is prefixed "You may", so assigning ZERO is a legal, complete answer. A fix that only
#// enforced "exactly N" would break this half and force an unwilling player to place tokens.

## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: SEC_T01:1:0
WithP2GroundArena: ASH_195:1:2
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# ThrawnReuse_SecondTriggerUsesHELGAITSBUFFEDPower
#// ⚠ THE FIXTURE MUST MAKE SNAPSHOT != PRINTED, or it cannot fail. My first version used a BARE
#// Helgait: snapshot 6 and printed fallback 6 are the same number, so the section passed whether or
#// not the snapshot survived. Four Experience tokens make it 10 vs 6 and the bug becomes visible.
#//
#// REPORTED BOARD (2026-08-30): Helgait (6/4) + 4 Experience -> 10/8, 3 damage; four Battle Droid
#// tokens; JTL_002 Thrawn DEPLOYED. Their Kelleran Beq (LOF_100, 7/7) attacks Helgait: 7 damage on
#// 8 HP with 3 already there kills it, and Helgait's 10 power kills the 7-HP Beq.
#//
#// BOTH prompts must offer 10. The first does. The second read 6 (the PRINTED power) because
#// $gAsh195DefeatSnapshot is a per-request global and Thrawn's "use it again?" YESNO is answered in a
#// LATER request — so the lookup missed and the code fell through to its fallback. Before the
#// identity guard it was worse still: the fallback re-resolved the raw mzID after the arena had
#// COMPACTED and read a Battle Droid's power, offering 1.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:JTL_002:1:1;myResources:6;theirResources:6}
WithP1GroundArena: ASH_195:1:3
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP2GroundArena: LOF_100:1:0
WithActivePlayer: 2
WithInitiativePlayer: 2

## WHEN
- P2>AttackGroundArena:0:0
- P1>Pass
- P1>AnswerDecision:-
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1DECISIONTOOLTIP:Distribute_all_10_Advantage_among_friendly_units_or_none
