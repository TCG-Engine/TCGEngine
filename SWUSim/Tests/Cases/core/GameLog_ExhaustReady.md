# ForceIllusion_ExhaustIsLogged
#// Game-log follow-up (2026-09-11). ~20 card handlers exhausted with a raw `$o->Status = 0`, which never
#// reached OnExhaustCard — so the exhaust was not logged. LOF_223 Force Illusion (Cunning, 2): "Exhaust an
#// enemy unit. A friendly unit gains Sentinel for this phase."

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
LOGCONTAINS:P1's [[LOF_223|Force Illusion]] exhausted P2's [[SOR_059|2-1B Surgical Droid]]

---

# ForceIllusion_CantExhaustAnExhaustImmuneUnit
#// ⚠ RULES BUG found by the same survey: the raw write also skipped OnExhaustCard's "can't be exhausted by
#// enemy card abilities" check. LOF_040 Kylo Ren's Lightsaber on a Force unit (LAW_149 Rey) grants it — so
#// Force Illusion must leave Rey READY (her Sentinel clause still resolves).

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:LOF_223}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:READY
LOGCOUNT:0:exhausted

---

# MindTrick_BudgetExhaust_LogsEachUnit
#// LOF_202 Mind Trick — "Exhaust any number of units with a combined power of 4 or less." (The shared
#// SWU_BUDGET_EXHAUST continuation.) Fixture from lof/MindTrick.md.

## GIVEN
CommonSetup: yyw/ggk/{myResources:2;handCardIds:LOF_202}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
LOGCOUNT:2:P1's [[LOF_202|Mind Trick]] exhausted P2's

---

# PremonitionOfDoom_ExhaustAll_SkipsTheImmuneEnemy
#// LOF_203 Premonition of Doom — "The next time you take the initiative this phase, exhaust all units."
#// Both players' units are exhausted by P1's card — except P2's Rey, whose Kylo Ren's Lightsaber makes her
#// immune to ENEMY exhausts. (Fixture from lof/PremonitionOfDoom.md.)

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: LOF_203
WithP1Resources: 7
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 1:LOF_040

## WHEN
- P1>PlayHand:0
- P1>Claim

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY
LOGCONTAINS:P1's [[LOF_203|Premonition of Doom]] exhausted P2's [[SOR_046|Consular Security Force]]
LOGCONTAINS:P1's [[LOF_203|Premonition of Doom]] exhausted P1's [[LOF_050|Plo Koon]]

---

# MillenniumFalcon_ReadiesItself
#// IBH_031 Millennium Falcon (7): "When Played: If your base has more damage on it than an enemy base,
#// ready this unit." A raw `Status = 1` — now through OnReadyCard, so it is logged (and honours can't-ready).

## GIVEN
CommonSetup: yyw/rrk/{myResources:7;handCardIds:IBH_031;myBaseDamage:5}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:READY
LOGCONTAINS:P1's [[IBH_031|Millennium Falcon]] readied itself

---

# AurraSing_InlineReady_NamesHerself
#// TWI_166 Aurra Sing: "When an enemy ground unit attacks your base: Ready this unit." Resolved INLINE in
#// the attack (no trigger dispatch, so no source) — logged through SWULogWithSource.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: TWI_166:0:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:READY
LOGCONTAINS:P2's [[TWI_166|Aurra Sing]] readied itself

---

# CalKestis_OpponentPicksTheirOwnUnit_StillAnEnemyAbility
#// LOF_015 Cal Kestis (leader) Action [Exhaust, use the Force]: "An opponent chooses a ready unit they
#// control. Exhaust that unit." The OPPONENT makes the pick (so the pick resolves in their frame), but the
#// exhaust is still Cal's — an ENEMY card ability. P2's only ready units: Rey wearing Kylo Ren's Lightsaber
#// (immune) and a Marine. Both are offered; P2 picks Rey, and she stays ready.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:LOF_015}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
LOGCOUNT:0:exhausted P2's

---

# CalKestis_ExhaustIsLogged
#// Same Action, a non-immune pick: "P1's Cal Kestis exhausted P2's Battlefield Marine" — Cal's line, even
#// though P2 made the choice.

## GIVEN
CommonSetup: yyk/rrk/{myLeader:LOF_015}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: LAW_149:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:EXHAUSTED
LOGCONTAINS:P1's [[LOF_015|Cal Kestis]] exhausted P2's [[SOR_095|Battlefield Marine]]

---

# DarthTraya_ReadiesALeader
#// SEC_188 Darth Traya: "On Attack: You may ready a non-unit leader." An undeployed leader is readied via its
#// ->Ready flag, not ->Status, so it never passed through OnReadyCard — the ready was unlogged (only the
#// "chose P1" pick was). (Fixture from sec/DarthTraya_LordOfBetrayal.md.)

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_016:0}
WithActivePlayer: 1
WithP1GroundArena: SEC_188:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:P1

## EXPECT
P1LEADER:READY
LOGCONTAINS:P1's [[SEC_188|Darth Traya]] readied P1's [[SOR_016|

---

# CalKestis_Deployed_OpponentPicksAnImmuneUnit_StaysReady
#// The DEPLOYED side of LOF_015 Cal Kestis ("On Attack: An opponent chooses a ready unit they control.
#// Exhaust that unit.") got the same immunity fix as the front Action (the CASTER's ability exhausts, even
#// though the opponent picks) but had no test — a coverage gap from the first pass. P2 picks Rey wearing
#// Kylo Ren's Lightsaber; she stays ready and the refusal is logged. (Fixture from
#// lof/CalKestis_ICantKeepHiding.md, DeployedOnAttack.)

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:LOF_015;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP2GroundArena: LAW_149:1:0
WithP2GroundArenaUpgrade: 0:LOF_040
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
LOGCONTAINS:couldn't exhaust P2's [[LAW_149|Rey]]
