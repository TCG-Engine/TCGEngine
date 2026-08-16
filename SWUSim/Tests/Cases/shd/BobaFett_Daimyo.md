# Deployed_KeywordAura
#// SHD_008 Boba Fett (deployed) — "Each OTHER friendly unit that has 1 or more keywords gets +1/+0."
#// Deployed as a unit, Boba buffs the friendly Sentinel SOR_063 (2 power → 3) but not the vanilla SOR_210
#// (4 power → 4), and not himself.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008;myLeaderDeployed:true}
WithP1GroundArena: SOR_063:1:0
WithP1GroundArena: SOR_210:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:SOR_210
P1GROUNDARENAUNIT:1:POWER:4

---

# Front_Decline
#// SHD_008 Boba Fett (front) — the reaction is a "may": declining leaves Boba ready and applies no buff.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_ExhaustToBuff
#// SHD_008 Boba Fett (front, undeployed) — "When you play a unit that has 1 or more keywords: You may
#// exhaust this leader. If you do, give a friendly unit +1/+0 for this phase." P1 plays SOR_063 (Sentinel,
#// keyword-only), accepts the reaction (exhausting Boba), and buffs its existing SOR_046 (3/7) to 4 power.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_063
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:POWER:4

---

# Front_NoKeyword_NoReaction
#// SHD_008 Boba Fett (front) — a played unit with NO keywords does not trigger the reaction: Boba stays
#// ready and no buff is offered (SOR_046 is vanilla).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_008}
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_PilotPlayedAsAnUpgrade_DoesNotTrigger
#// SHD_008 (front) triggers on "when you PLAY A UNIT that has 1 or more keywords". A Pilot card played
#// for its Piloting cost enters as an UPGRADE, not as a unit, so the trigger must stay silent even
#// though the card itself carries the Piloting keyword.
#// This is the pilot-as-upgrade dispatch path, which is a distinct code path from a normal unit play.
#// JTL_196 Dagger Squadron Pilot: unit cost 1 / Piloting cost 1, aspects Cunning+Heroism. Under
#// Catacombs of Cadera (Aggression) + Boba (Command/Heroism) the Cunning half is uncovered, so BOTH
#// prices are 1+2 = 3 and 5 resources make both affordable — which is why the Unit/Pilot choice is
#// offered at all and this section can pick the upgrade branch deliberately.
#// SOR_237 Alliance X-Wing (2/3 Vehicle) is the host: +2/+1 from the upgrade → 4/4.
#// COVERAGE: offer=Front_BuffOfferSpansBothArenasAndIncludesTheJustPlayedUnit (pending SELECTABLEEXACT
#//           over every friendly unit) · decline=Front_Decline (pre-existing, the "you may exhaust" no)
#//           boundary=this section vs Front_SamePilotPlayedAsAUnit_DoesTrigger (same card, same fixture,
#//           upgrade vs unit) and Front_ConditionalKeywordInactive_DoesNotTrigger vs
#//           Front_ConditionalKeywordActive_DoesTrigger (same card, condition off vs on)
#//           reqboundary=Front_ExhaustedLeader_SecondKeywordedPlayDoesNotTrigger (the leader's exhausted
#//           state is written by one action and re-read by the next play's trigger check)
#//           control=N/A — both sides read "friendly"/"you play", resolved from the leader's own
#//           controller; the card has no clause that can reach a unit whose control changed, and the
#//           deployed aura is recomputed from live controllership every time power is read
#//           (Deployed_AuraRecomputesWhenAConditionalKeywordTurnsOn is the recompute proof)

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: JTL_196
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1LEADER:READY
P1NODECISION
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_196
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:4

---

# Front_SamePilotPlayedAsAUnit_DoesTrigger
#// Boundary partner of the section above: IDENTICAL fixture, the other branch of the Unit/Pilot choice.
#// Played as a unit, JTL_196 brings its Piloting keyword onto the board as a unit play, so the trigger
#// fires and the exhaust is offered. The +1/+0 goes to the X-Wing (2 power → 3); the newly played Pilot
#// keeps its printed 2 power, proving the buff landed on the chosen unit and not on the trigger's source.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: JTL_196
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:YES
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1GROUNDARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# Front_PlayedUnitGainsAKeywordFromAnAbility_DoesTrigger
#// "has 1 or more keywords" is read off the unit AS IT ENTERS PLAY, not off its printed keyword line.
#// SOR_237 Alliance X-Wing is printed vanilla, but SOR_144 Red Three grants Raid 1 to each other
#// friendly Heroism unit, so the X-Wing arrives already carrying a keyword and the trigger fires.
#// Raid only adds power while attacking, so the X-Wing's printed 2 power is untouched by the grant —
#// the +1/+0 assertion below is Boba's buff alone (2 → 3).
#// Red Three is unique, so exactly one copy is seated.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_237
WithP1SpaceArena: SOR_144:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1LEADER:EXHAUSTED
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:POWER:3
P1SPACEARENAUNIT:0:CARDID:SOR_144
P1SPACEARENAUNIT:0:POWER:2

---

# Front_ConditionalKeywordInactive_DoesNotTrigger
#// The other half of "read the keywords the unit actually has": SHD_168 Hunting Nexu only gains Raid 2
#// "while you control another Aggression unit". The only other friendly unit here is SOR_095 Battlefield
#// Marine (Command/Heroism), so the condition is false, the Nexu enters with NO keyword, and the trigger
#// must not fire. An implementation that matched on the card's printed keyword TEXT would fire here.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SHD_168
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1LEADER:READY
P1NODECISION
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:CARDID:SHD_168
P1GROUNDARENAUNIT:1:NOTKEYWORD:Raid

---

# Front_ConditionalKeywordActive_DoesTrigger
#// Boundary partner: the same Hunting Nexu play with the condition SATISFIED. SOR_157 Cantina Braggart
#// is an Aggression unit, so the Nexu enters with Raid 2 live and the trigger fires.
#// Only the seated unit differs from the section above, so the change in behaviour isolates the
#// conditional keyword.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SHD_168
WithP1GroundArena: SOR_157:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_157
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:1:CARDID:SHD_168
P1GROUNDARENAUNIT:1:HASKEYWORD:Raid
P1GROUNDARENAUNIT:1:POWER:4

---

# Front_OpponentPlaysAKeywordedUnit_DoesNotTrigger
#// "When YOU play a unit" — an opponent's play of a keyworded unit is not our play. P2 plays SOR_141
#// Green Squadron A-Wing (Raid 2) while P1 has both a ready Boba and a friendly unit that would be a
#// legal buff target, so a seat-blind trigger would visibly fire here.
#// P2 is seated on Catacombs of Cadera + an Aggression/Heroism leader so the A-Wing is on-aspect and
#// actually gets played (an off-aspect play would silently no-op and fake a pass).

## GIVEN
CommonSetup: rgw/rrw/{myLeader:SHD_008}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 5
WithP2Resources: 5
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SOR_141

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1LEADER:READY
P1NODECISION
P2NODECISION
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_141
P2SPACEARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:POWER:3

---

# Front_ExhaustedLeader_SecondKeywordedPlayDoesNotTrigger
#// The trigger's own cost gates it: "You may EXHAUST this leader." Once the first keyworded play has
#// spent the exhaust, a second keyworded play in the same turn cycle offers nothing at all — not a
#// prompt that then fails, but no prompt.
#// SOR_141 Green Squadron A-Wing (Raid 2) buys the buff for SOR_095 Battlefield Marine (3 → 4); SOR_157
#// Cantina Braggart (Raid 2) is the second keyworded play and is met with silence. The Marine's power
#// staying at 4 rather than 5 is the proof no second buff was applied.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [SOR_141 SOR_157]
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1LEADER:EXHAUSTED
P1NODECISION
P1HANDCOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:CARDID:SOR_157
P1GROUNDARENAUNIT:1:POWER:0
P1SPACEARENACOUNT:1

---

# Front_BuffOfferSpansBothArenasAndIncludesTheJustPlayedUnit
#// "give A FRIENDLY UNIT +1/+0" — the pool is every friendly unit in either arena, INCLUDING the unit
#// whose play raised the trigger (it is already in play by the time the buff resolves), and it excludes
#// enemy units. SOR_164 Wampa sits on P2's board carrying Overwhelm precisely so a keyword-based rather
#// than controller-based pool would drag it in.
#// The decision is left PENDING — an offer can only be asserted before something consumes it. The
#// resolution of this same offer is covered by the sections that answer it.
#// mySpaceArena-1 is SOR_141 Green Squadron A-Wing, the unit just played.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_141
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&mySpaceArena-1
P1SPACEARENAUNIT:1:CARDID:SOR_141
P2GROUNDARENAUNIT:0:CARDID:SOR_164

---

# Deployed_AuraBuffsAPilotUnitBecausePilotingIsAKeyword
#// The deployed aura reads "each other friendly unit that has 1 or more keywords". Piloting IS a
#// keyword, so a Pilot card that is in play AS A UNIT qualifies — the contrast case to
#// Front_PilotPlayedAsAnUpgrade_DoesNotTrigger, where the same card as an upgrade is not a unit at all.
#// JTL_196 Dagger Squadron Pilot is 2/1 → 3/1 under the aura. The aura is +1/+0, so the HP assertion is
#// what keeps a "+1/+1" implementation from passing.
#// A deployed leader seats at the END of the ground arena, so Boba is index 1 — and his own 4 power is
#// the self-exclusion negative ("each OTHER friendly unit").

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_196:1:0

## WHEN
- P1>Pass

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:1:CARDID:SHD_008
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:POWER:4

---

# Deployed_AuraIgnoresAnInactiveConditionalKeyword
#// The aura counts the keywords a unit ACTUALLY has right now. SHD_168 Hunting Nexu has Raid 2 only
#// "while you control another Aggression unit"; deployed Boba is Command/Heroism and is the only other
#// friendly unit, so the condition is false and the Nexu is keywordless and unbuffed at 4 power.
#// This is the pre-state of the section below.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SHD_168:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_168
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
P1GROUNDARENAUNIT:0:POWER:4

---

# Deployed_AuraRecomputesWhenAConditionalKeywordTurnsOn
#// The aura is a continuous effect, not a one-shot stamped when a unit enters: playing SOR_157 Cantina
#// Braggart (an Aggression unit) switches the Nexu's conditional Raid 2 ON, and the aura must pick that
#// up on the very next power read — 4 → 5 — without the Nexu itself having moved or been replayed.
#// The Braggart arrives with printed Raid 2 and is buffed too (0 → 1); Boba stays at his own 4.
#// Boba is DEPLOYED here, so his undeployed "when you play a unit" reaction is inactive and no exhaust
#// prompt appears for this play — the buffs below are the aura alone.

## GIVEN
CommonSetup: rgw/rgw/{myLeader:SHD_008;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_157
WithP1GroundArena: SHD_168:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SHD_168
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:CARDID:SHD_008
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:2:CARDID:SOR_157
P1GROUNDARENAUNIT:2:POWER:1

---

# Front_UnitGainsAKeywordFromTheEffectThatPlaysIt_DoesTrigger
#// A third way for a played unit to "have 1 or more keywords": the effect that PLAYS it hands it one on
#// the way in. SOR_095 Battlefield Marine is printed vanilla, but the base's Epic Action gives the unit
#// it plays Ambush for the phase, so the Marine arrives keyworded and Boba's reaction is offered.
#// Two triggers land in the same window (the Ambush attack and Boba's exhaust), so the engine first asks
#// which to resolve — EffectStack-1 is Boba's, taken first so the buff is live before the attack.
#// The single friendly unit means the buff's target auto-resolves onto the Marine (3 → 4 power).
#// The payoff is the damage number: TWI_054 Duchess's Champion (1/8) takes 4, not 3, which is only
#// possible if the +1/+0 was applied before the Ambush attack resolved. The Marine takes 1 back.
#// TWI_054 only gains Sentinel while its opponent controls 3+ units; P1 controls one, so it is a plain
#// 1/8 body here and does not redirect the attack.
#// The base is overridden to SOR_022 (a Command base), which also keeps SOR_095 (Command/Heroism)
#// on-aspect alongside Boba.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:SHD_008;myBase:SOR_022}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: SOR_095
WithP2GroundArena: TWI_054:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:TWI_054
P2GROUNDARENAUNIT:0:DAMAGE:4
