# WhenDrawn_RevealDealDamage
#// LOF_148 — "When you draw this card during the action phase: if you control an Aggression leader or
#// base, you may reveal it. If you do, deal 2 damage to a unit and 2 damage to a base." P1 (Aggression
#// leader+base) plays SOR_111 (When Played: draw a card) with LOF_148 on top of the deck; drawing it
#// triggers the reveal → 2 to the enemy unit + 2 to the enemy base.

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# NoAggressionLeaderOrBase_DoesNothing
#// LOF_148 — the draw trigger requires "an Aggression leader OR base". P1 here has a Command base AND a
#// Command/Heroism leader (aspect string ggw), NO Aggression. Drawing Rey via SOR_111 (When Played: draw a
#// card) does NOT offer the reveal: no prompt, no damage, Rey sits in hand. (Intended: "should do nothing if no
#// leader or base with Aggression aspect".)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# AggressionLeaderOnly_Fires
#// LOF_148 — the trigger fires off an Aggression LEADER even when the base is not Aggression. Aspect string
#// grw = Command base + Aggression/Heroism leader. Drawing Rey → reveal → deal 2 to the enemy unit + 2 to
#// the enemy base. (Intended: "should deal 2 damage ... with an Aggression leader".)

## GIVEN
CommonSetup: grw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# AggressionBaseOnly_Fires
#// LOF_148 — the trigger fires off an Aggression BASE even when the leader is not Aggression. Aspect string
#// rgw = Aggression base + Command/Heroism leader. Drawing Rey → reveal → deal 2 to the enemy unit + 2 to
#// the enemy base. (Intended: "should deal 2 damage ... with an Aggression base".)

## GIVEN
CommonSetup: rgw/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# DeclineReveal_Passable
#// LOF_148 — the reveal is a "you may": P1 has Aggression (rrk) and draws Rey, but DECLINES the reveal.
#// No damage is dealt and Rey stays in hand. (Intended: "should be passable".)

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: LOF_148
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# DifferentCardDrawn_DoesNotTrigger
#// LOF_148 — "When you DRAW THIS card" only fires when Rey herself is the drawn card. Rey already sits in
#// P1's hand; P1 plays SOR_111 to draw a DIFFERENT card (SOR_046 on top of deck). Rey's trigger does not
#// fire, no damage, Rey remains in hand. (Intended: "should do nothing if in hand and a different card is drawn".)

## GIVEN
CommonSetup: rrk/ggw/{myResources:6;handCardIds:SOR_111,LOF_148}
P1OnlyActions: true
WithP1Deck: SOR_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# RegroupDraw_DoesNotTrigger
#// LOF_148 — the trigger is "during the ACTION phase" only. P1 has Aggression (rrk) and Rey on top of the
#// deck. Both players pass to reach the regroup phase, where P1 draws Rey as part of the regroup draw.
#// Because it's not the action phase, the reveal does NOT fire: no damage. (Intended: "should do nothing if drawn
#// during the regroup phase".)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Deck: LOF_148 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1BASEDMG:0

---

# PilotLeaderOnStolenHost_StillCountsAsYourAggressionLeader
#// LOF_148 — "if you control an Aggression LEADER or base". A leader deployed as a PILOT upgrade is still
#// your leader, and it stays yours even when an opponent takes control of the unit it is attached to
#// (a control change transfers non-pilot upgrades only; pilots keep their own controller).
#// P1's base is Data Vault (JTL_024, Command — NOT Aggression), so Poe JTL_013 (Aggression/Heroism) is the
#// only possible source. Poe is piloting SHD_256 Mercenary Gunship, and P2 uses the Gunship's own
#// "Action [4 resources]: take control of this unit" to steal the host — the Gunship is not a leader unit
#// for P2, so it is not defeated. Poe remains P1's Aggression leader, so drawing Rey still offers the
#// reveal and she deals 2 to a unit and 2 to a base.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_013;myLeaderDeployedPilot:true;myBase:JTL_024;myResources:6;theirResources:4}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SHD_256:1:0
WithP1Hand: SOR_111
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>UseUnitAbility:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SHD_256
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# SearchAndDraw_TriggersWhenDrawn
#// LOF_148 — "When you DRAW this card during the action phase". A search-and-draw is a draw: SOR_123
#// Recruit reads "Search the top 5 cards of your deck for a unit, reveal it, and DRAW it", so pulling Rey
#// that way must fire her reveal exactly as a normal draw does.
#// REGRESSION GUARD: TOPDECKSEARCH_FINALIZE used to put the picks into hand with a bare AddHand(), firing
#// no when-you-draw observers at all, so Rey was silently skipped on every search-and-draw effect.

## GIVEN
CommonSetup: grw/ggw/{myResources:6;handCardIds:SOR_123}
P1OnlyActions: true
WithP1Deck: [LOF_148 SOR_095 SOR_128 SOR_046 SOR_063]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_148
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2

---

# VehicleHostingLeaderPilot_CountsAsAnAggressionLeader
#// LOF_148 — a Vehicle carrying a leader-Pilot upgrade IS a leader unit, and it counts as "an Aggression
#// leader" using its OWN aspects. Neither other source here is Aggression: the leader is Kazuda Xiono
#// (JTL_018, Cunning/Heroism) and the base is Command. Kazuda deploys as a Pilot onto Red Five (JTL_151,
#// Aggression/Heroism), which makes Red Five a leader unit — so P1 now controls a red leader and drawing
#// Rey offers the reveal, dealing 2 to a unit and 2 to a base.
#// Complements the stolen-host section: that one proves the leader CARD is still yours while flying an
#// enemy-controlled unit; this one proves the HOST's own aspects count while it is a leader unit. Neither
#// rule subsumes the other — Kazuda alone would give no Aggression here.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_018;myLeaderDeployedPilot:true;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_151:1:0
WithP1Hand: SOR_111
WithP1Deck: [LOF_148 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_151
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_018
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
