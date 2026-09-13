# OnePlayer_TwoDifferentTriggers_OrderingPrompt
#// THE REGROUP-START TRIGGER WINDOW (2026-09-14). "When the regroup phase starts" abilities used to be
#// hardcoded one after another, so nobody could order them. CR 7.9: a player with several triggered abilities
#// waiting at once chooses their order. P1 controls TS26_23 Assault Lander LAAT ("deal 4 damage to this unit")
#// and JTL_198 Fireball ("deal 1 damage to this unit") — two DIFFERENT triggers, so P1 is asked which first.
#// (EffectStack-0 = the Lander, EffectStack-1 = Fireball — collection order.) Copies of ONE ability for one
#// player are not a real choice and resolve without a prompt (hmw/DarkSanctum.md::Regroup_TwoCopiesFireTwice).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: TS26_23:1:0
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Choose_trigger_to_resolve
P1SELECTABLEEXACT:EffectStack-0&EffectStack-1

---

# OnePlayer_TwoDifferentTriggers_BothResolveInTheChosenOrder
#// Fireball first (EffectStack-1), then the Lander resolves on its own: Fireball 1 damage, the Lander 4.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: TS26_23:1:0
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P1>AnswerDecision:EffectStack-1

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# BothPlayers_TheInitiativeHolderPicksWhoResolvesFirst
#// CR 7.10: when both players have triggered abilities waiting, the active player — at regroup, the player
#// with the initiative (P2 here) — chooses which player resolves first. Each player's Fireball then resolves.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP2SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P2HASDECISION
P2DECISIONTOOLTIP:Resolve_Which_Player_First?

---

# BothPlayers_AfterThePick_BothResolve

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP2SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:1

---

# OneTrigger_NoPrompt_ResolvesAsBefore
#// A lone regroup-start trigger is not ordered against anything: no prompt, it just resolves.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
