# Deployed_DeclineRecollect
#// SHD_010 Bossk (deployed) — the re-collect is a "may": collecting the bounty (draw 1) then declining
#// Bossk's re-offer leaves P1 with just the 1 card.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095 SOR_095 SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1

---

# Deployed_RecollectBounty
#// SHD_010 Bossk (deployed) — "When you collect a bounty: You may collect that bounty again. Use this
#// ability only once each round." P1's deployed Bossk-controller defeats the enemy SHD_095 (Clone Deserter,
#// draw-1 Bounty) with SOR_046, collects the bounty (draw 1), then Bossk lets P1 collect it AGAIN (draw 1
#// more) — 2 cards drawn total.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: SOR_095 SOR_095 SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:2

---

# Front_DealAndBuffBountyUnit
#// SHD_010 Bossk (front, undeployed) — "Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may
#// give it +1/+0 for this phase." P1 uses the action on its own SHD_167 (4/4, printed Bounty — the sole
#// Bounty unit, so the target auto-resolves): it takes 1 damage and is buffed to 5 power. Bossk exhausts.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010}
P1OnlyActions: true
WithP1GroundArena: SHD_167:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:5

---

# Front_OnlyBountyUnitsAreOffered
#// SHD_010 Bossk (front) — "Deal 1 damage to a UNIT WITH A BOUNTY". The offer is asserted here with the
#// decision left pending: SHD_195 Cartel Turncoat (P1 space, printed Bounty) and SHD_095 Clone Deserter
#// (P2 ground, printed Bounty) are the only legal picks. SOR_046 and SOR_164 Wampa are bounty-less and
#// must stay out of the pool, and the pool spans BOTH players and BOTH arenas.
#// COVERAGE: offer=Front_OnlyBountyUnitsAreOffered (pending SELECTABLEEXACT across both sides/arenas)
#//           decline=Front_DeclineTheBuff · Deployed_DeclineTheBountyItself_NoRecollectOffer (declining the
#//           BOUNTY) · Deployed_DeclineRecollect (declining the RE-OFFER)
#//           control=Deployed_OpponentCollectsOnOurUnit_BosskDoesNotTrigger (the bounty is collected by
#//           the OPPONENT of the bountied unit's controller, so Bossk's controller must not see the re-offer)
#//           boundary=Front_DealAndBuffBountyUnit (buff taken, power 5) vs Front_DeclineTheBuff (power 4);
#//           Deployed_RecollectBounty (2 cards) vs Deployed_DeclineRecollect (1 card);
#//           Deployed_TwoBounties_DeclineOnTheFirstLeavesItForTheSecond (declined on the draw, taken on the
#//           base damage) vs Deployed_RecollectTargetedBounty_GuildTargetTwice (taken on the only bounty)
#//           reqboundary=Deployed_OncePerRound_SecondBountyGetsNoReoffer and
#//           Deployed_RecollectResourceBounty_EmptyDeck (the once-each-round flag is written during one
#//           action and read on a LATER action, i.e. across the request boundary)

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SHD_195:1:0
WithP2GroundArena: [SOR_164:1:0 SHD_095:1:0]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-1

---

# Front_DeclineTheBuff
#// SHD_010 Bossk (front) — "You may give it +1/+0 for this phase" is optional. The decline branch of the
#// existing Front_DealAndBuffBountyUnit section: the 1 damage still lands, but SHD_167 Wanted Insurgents
#// stays at its printed 4 power instead of 5. Bossk still exhausts (the buff is not the cost).

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010}
P1OnlyActions: true
WithP1GroundArena: SHD_167:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:4

---

# Deployed_RecollectTargetedBounty_GuildTargetTwice
#// SHD_010 Bossk (deployed) — the re-collect re-runs a bounty that has its own TARGET choice, not just a
#// no-target one. SHD_173 Guild Target grants "Bounty - Deal 2 damage to a base (3 if unique)". P1's
#// deployed Bossk (4 power) defeats the non-unique 3/3 host, collects for 2 to P2's base, then Bossk
#// re-collects for another 2 — the base choice is offered again, independently. 4 total.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_173

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION

---

# Deployed_OncePerRound_SecondBountyGetsNoReoffer
#// SHD_010 Bossk (deployed) — "Use this ability only once each round." Two bounty collections in the SAME
#// round, on two separate actions: SOR_046 defeats SHD_095 Clone Deserter (draw 1, doubled to 2), then
#// Bossk himself defeats SHD_134 Guavian Antagonizer (draw 1) — and the re-offer does NOT appear, so P1
#// ends on 3 cards, not 4. The once-per-round flag is written on the first action and read on the second,
#// i.e. across the request boundary.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SHD_095:1:0 SHD_134:1:0]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:3
P1NODECISION

---

# Deployed_DeclineTheBountyItself_NoRecollectOffer
#// SHD_010 Bossk (deployed) — Bossk keys off "when you COLLECT a bounty". Declining the bounty itself is
#// not a collection, so no re-offer follows: P1 draws nothing and is left with no pending decision. The
#// contrast with Deployed_DeclineRecollect (which declines the SECOND offer after collecting once) is
#// what separates "declined the bounty" from "declined the re-collect".

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:3
P1NODECISION

---

# Deployed_OpponentCollectsOnOurUnit_BosskDoesNotTrigger
#// SHD_010 Bossk (deployed) — a bounty is collected by the OPPONENT of the bountied unit's controller.
#// Here P1's own SHD_095 Clone Deserter dies attacking SOR_164 Wampa, so P2 collects the draw. Bossk is
#// P1's, and the re-offer is bound to the COLLECTOR, so neither player sees it: P2 draws exactly 1 and
#// both players end with no pending decision.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SHD_095:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P2HANDCOUNT:1
P1HANDCOUNT:0
P1NODECISION
P2NODECISION

---

# Deployed_TwoBounties_DeclineOnTheFirstLeavesItForTheSecond
#// SHD_010 Bossk (deployed) — a unit carrying TWO bounties (SHD_095 Clone Deserter's printed "draw a
#// card" plus SHD_173 Guild Target's granted "deal 2 damage to a base") offers each of them
#// independently (CR 13.b). Both are collected, Bossk's re-collect is DECLINED on the draw and TAKEN on
#// the base damage: P1 ends on 1 card but 4 base damage. The mirror of Deployed_DeclineRecollect, which
#// declines the only re-offer on the board; here declining one still leaves the once-per-round ability
#// available for the other bounty in the same window.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: SHD_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_173
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2BASEDMG:4
P1NODECISION

---

# Deployed_RecollectResourceBounty_EmptyDeck
#// SHD_010 Bossk (deployed) — a re-collect that cannot change the game state still COUNTS as used.
#// SHD_125 Price on Your Head grants "Bounty - Put the top card of your deck into play as a resource";
#// with an EMPTY deck there is nothing to resource, so P1's resource count never moves off 4. The second
#// half is the proof that the once-per-round ability was nevertheless spent: P1 defeats a second bountied
#// unit (SHD_173 Guild Target) later in the same round and gets no re-offer at all — 2 base damage, not 4.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP2GroundArenaUpgrade: 0:SHD_125
WithP2GroundArenaUpgrade: 1:SHD_173

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:4
P1DECKCOUNT:0
P2BASEDMG:2
P1NODECISION

---

# Deployed_RecollectSearchBounty_PlaysTwoUnitsForFree
#// SHD_010 Bossk (deployed) — the re-collect re-runs a bounty that SEARCHES and PLAYS a card.
#// SHD_123 Bounty Hunter's Quarry grants "Bounty - Search the top 5 cards of your deck for a unit that
#// costs 3 or less and play it for free"; collected twice, P1 plays TWO free SEC_237 Supreme Council
#// Aides and still has both starting resources available. The second search runs against the deck as it
#// stands after the first (the unpicked cards went to the bottom), so it is a genuinely fresh search.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SEC_237 SEC_237 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_237
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_237

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1RESAVAILABLE:2
P1NODECISION

---

# Deployed_RecollectDrawTwoFromABountiedEnemyLEADER
#// SHD_010 Bossk (deployed) — the bountied unit does not have to be a normal unit. SHD_176 Death Mark
#// ("Bounty - Draw 2 cards") rides P2's DEPLOYED leader; Bossk defeats it, the leader goes back to its
#// leader zone undeployed, and the draw-2 bounty is collected twice for 4 cards.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;theirLeader:SOR_014:1:1:1:3}
P1OnlyActions: true
WithP2GroundArenaUpgrade: 0:SHD_176
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2LEADER:NOTDEPLOYED
P1HANDCOUNT:4
P1DECKCOUNT:1
P1NODECISION

---

# Deployed_BosskDiesInTheAttack_NoRecollect
#// SHD_010 Bossk (deployed) — "When YOU collect a bounty" is bound to a Bossk that is still on the board
#// when the collection happens. Bossk (4/6) trades with SHD_140 Trandoshan Hunters (6/4) wearing SHD_071
#// Top Target: both die, P1 still collects the bounty ("Heal 4 damage from a unit or base" — 4, not 6,
#// because the non-unique host does not upgrade it) and heals its own base from 4 to 0, but there is no
#// re-offer because Bossk is no longer in play.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myBaseDamage:4}
P1OnlyActions: true
WithP2GroundArena: SHD_140:1:0
WithP2GroundArenaUpgrade: 0:SHD_071

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P1LEADER:NOTDEPLOYED
P1BASEDMG:0
P1NODECISION

---

# Deployed_RecollectPlayDiscountBounty_TwoDiscountsStack
#// SHD_010 Bossk (deployed) — the re-collect also re-arms a DELAYED reward. SHD_006 Jabba the Hutt's
#// granted bounty is "The next unit you play this phase costs 1 resource less" (seeded here directly as
#// the phase grant on P2's marine). Collecting it twice arms the discount twice, and the two stack: P1's
#// TWI_230 Super Battle Droid (cost 3) is then played for 1, leaving 2 of 3 resources ready.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:3;myhandCardIds:TWI_230}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0:SHD_006-1

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_230
P1RESAVAILABLE:2

---

# Deployed_RecollectSearchBounty_SecondUnitsWhenPlayedFires
#// SHD_010 Bossk (deployed) — the unit fetched by the RE-COLLECTED search is PLAYED, not just moved, so
#// its "When Played" ability resolves. SHD_236 Snowtrooper Lieutenant's "When Played: You may attack with
#// a unit" fires off the second SHD_123 Bounty Hunter's Quarry search and hands P1 a bonus attack. The
#// only legal attacker is SOR_164 Wampa: Bossk exhausted making the attack that started all this, and
#// neither freshly played unit may attack the turn it arrives — so the offer being exactly [Wampa] is
#// itself the proof the ability resolved from inside the re-collect. Wampa is a Creature, not Imperial,
#// so it swings for its printed 4 with no bonus.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:2}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1GroundArena: SOR_164:1:0
WithP1Deck: [SEC_237 SHD_236 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SEC_237
- P1>AnswerDecision:YES
- P1>AnswerDecision:SHD_236
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:2:CARDID:SEC_237
P1GROUNDARENAUNIT:3:CARDID:SHD_236
P2BASEDMG:4
P1NODECISION

---

# Deployed_NestedBounty_ReCollectableAfterDecliningTheOuterOne
#// SHD_010 Bossk (deployed) — a bounty collected INSIDE another bounty's resolution is still "a bounty
#// you collect", and Bossk's once-per-round re-collect can be spent on it. Bossk defeats the SHD_123
#// Bounty Hunter's Quarry host; the search plays SHD_236 Snowtrooper Lieutenant, whose "When Played: You
#// may attack with a unit" sends SOR_164 Wampa into SHD_095 Clone Deserter — a SECOND, nested bounty.
#// P1 declines Bossk's re-offer on the outer (search) bounty and takes it on the nested draw instead,
#// ending on 2 cards. Nothing outside the nesting distinguishes this from the flat case, which is the
#// point: the entitlement is not consumed by merely being offered.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_095:1:0 SHD_095:1:0]
WithP2GroundArenaUpgrade: 0:SHD_123
WithP1Deck: [SHD_236 SOR_171 SOR_171 SOR_171 SOR_171]

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:SHD_236
- P1>AnswerDecision:NO
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SHD_236
P1GROUNDARENAUNIT:0:DAMAGE:2
P1HANDCOUNT:2
P1NODECISION

---

# Deployed_TwoBounties_DoubleTheFirstThenSecondNotDoubled
#// SHD_010 Bossk (deployed) — a unit carrying TWO bounties offers each independently (CR 13.b), but
#// Bossk's re-collect is "only once each round", so it can double ONE of them and no more. SHD_027
#// Hylobon Enforcer's printed "Bounty - Draw a card" plus SHD_176 Death Mark's granted "Bounty - Draw 2"
#// give 1 + 2 = 3 cards; doubling the FIRST adds 1 more, for 4. If the once-each-round limit leaked, the
#// second bounty would double too and the hand would be 6 — both bounties are draws precisely so the
#// difference lands in one assertion with no target picks to desync the flow.
#// ⚠ The second re-offer is still PRESENTED (both were queued before either was answered) — the last YES
#// is that prompt, and accepting it must do NOTHING. Suppressing the prompt itself is a separate change.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true}
P1OnlyActions: true
WithP2GroundArena: SHD_027:1:0
WithP2GroundArenaUpgrade: 0:SHD_176
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:4

---

# Deployed_RecollectResourceBounty_PriceOnYourHead
#// SHD_010 Bossk (deployed) — the re-collect also re-runs a bounty that MOVES cards between zones.
#// SHD_125 Price on Your Head grants "Bounty - Put the top card of your deck into play as a resource";
#// collected twice, P1 goes from 4 resources to 6 and the deck drops from 3 cards to 1. The two
#// resources arrive EXHAUSTED (the bounty has no "and ready it" clause), so P1 still has 4 available.
#// This is the zone-compaction case: the first collection leaves the old top slot marked removed, and if
#// that dead slot is still what "the top card" resolves to, the second collection silently does nothing.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010;myLeaderDeployed:true;myResources:4}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_125
WithP1Deck: [SOR_095 SOR_046 SOR_171]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:6
P1RESAVAILABLE:4
P1DECKCOUNT:1
