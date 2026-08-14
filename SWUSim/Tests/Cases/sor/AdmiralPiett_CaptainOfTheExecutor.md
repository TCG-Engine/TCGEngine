# CostSixFriendly_GainsAmbush_ReadyAndAttack
#// SOR_079 Admiral Piett, Captain of the Executor (Ground, 1/4 UQ, Command/Villainy) — "Each friendly
#//   non-leader unit that costs 6 or more gains Ambush." With Piett in play, P1 plays an AT-ST
#//   (cost 6, 6/7): the Ambush prompt appears, the AT-ST readies and attacks the lone enemy Consular
#//   Security Force (auto-target, pool of one), dealing 6 and taking 3, then ends exhausted.
#// COVERAGE: offer=AmbushTargetOffer_EnemyUnitsInArena (pending SELECTABLEEXACT over the enemy ground
#//           units; the space unit and base are excluded) · reqboundary=this section (the Ambush
#//           YES/NO and its target resolve on separate serialized actions after the play) ·
#//           control=ControlledEnemyPiett_GrantsToControllersUnits (the aura follows Piett's
#//           CONTROLLER, not his owner) · boundary pair=this section vs CostFiveFriendly_NoAmbush
#//           (printed cost 6 vs 5) · decline=AmbushDeclined_UnitStaysExhausted (NO branch)

## GIVEN
CommonSetup: ggk/ggk/{myResources:6;myhandCardIds:SOR_232}
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:6
P2BASEDMG:0

---

# AmbushDeclined_UnitStaysExhausted
#// The granted Ambush is optional: P1 answers NO and the AT-ST simply enters play exhausted with no
#//   attack.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6;myhandCardIds:SOR_232}
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AmbushTargetOffer_EnemyUnitsInArena
#// Offer shape: with two enemy ground units and an enemy space unit on board, the granted Ambush
#//   attack's target pick is left PENDING — the pool is exactly the two enemy ground units (a ground
#//   attacker cannot reach the space arena, and Ambush targets units, never the base).

## GIVEN
CommonSetup: ggk/ggk/{myResources:6;myhandCardIds:SOR_232}
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# CostFiveFriendly_NoAmbush
#// Printed-cost boundary: Rugged Survivors (cost 5, no printed Ambush) played with Piett out gets NO
#//   Ambush prompt and no keyword — one below the cost-6 line. (Vigilance off-aspect for this leader
#//   pair adds +2 to pay, hence 8 resources; the GRANT check reads the printed cost, not the paid
#//   total, so even the 7 actually paid stays below nothing — cost 5 is simply under the line.)

## GIVEN
CommonSetup: ggk/ggk/{myResources:8;myhandCardIds:SOR_067}
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:1:CARDID:SOR_067
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# SeatedCostSix_HasAmbushKeyword_PiettDoesNot
#// The grant is a static aura, visible on seated units too: a seated AT-ST (cost 6) shows the Ambush
#//   keyword while Piett (cost 2) does not grant it to himself.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 1
WithP1GroundArena: [SOR_079:1:0 SOR_232:1:0]

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:HASKEYWORD:Ambush
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush

---

# EnemyCostSix_NotGranted
#// "Each FRIENDLY non-leader unit" — the aura never reaches the opponent: P2 plays an AT-ST (cost 6)
#//   while P1 controls Piett. No Ambush prompt for P2, the AT-ST enters exhausted with no attack,
#//   and it does not carry the keyword.

## GIVEN
CommonSetup: ggk/ggk/{theirResources:6;theirhandCardIds:SOR_232}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_079:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P2NODECISION
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DeployedLeaderCostSix_NoAmbush
#// "non-leader" — a cost-6 LEADER unit is excluded: P1 deploys Hera Syndulla (cost 6) with Piett and
#//   an enemy unit on board. No Ambush prompt, no attack, and the leader unit does not carry the
#//   keyword.

## GIVEN
CommonSetup: ggw/ggk/{myResources:6;myLeader:SOR_008}
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1NODECISION
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# PiettDefeated_GrantEnds
#// The aura dies with Piett: P2's Wampa kills him (4 power vs 4 HP), and P1's follow-up AT-ST play
#//   gets no Ambush prompt.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6;myhandCardIds:SOR_232}
WithActivePlayer: 2
WithP1GroundArena: SOR_079:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# ControlledEnemyPiett_GrantsToControllersUnits
#// Control change end-state: P1 CONTROLS a Piett OWNED by P2. "Friendly" follows the controller —
#//   P1's seated AT-ST has Ambush.

## GIVEN
CommonSetup: ggk/ggk
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_079:2
WithP1GroundArena: SOR_232:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_079
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
