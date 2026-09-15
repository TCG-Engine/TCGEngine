# Front_PayTwoExhaustAndDiscard_CreatesABeast
#// HMW_010 Tarfful, Fighting from the Shadowlands (Command/Heroism, Rebel+Wookiee, 3/7, deploy 6) —
#// FRONT: "Action [2 resources, Exhaust, discard a card from your hand]: Create a Beast token."
#// DEPLOYED: "Sentinel / On Attack: You may pay 1 resource. If you do, create a Beast token."
#// COVERAGE (a LEADER — each side must clear the bar independently):
#//   FRONT  offer=Front_DiscardOfferIsYourWholeHand · cost=this section (2 spent, leader EXHAUSTED,
#//          hand -1) · unaffordable=Front_OneResource_ActionUnavailable +
#//          Front_EmptyHand_ActionUnavailable (BOTH must leave the leader READY) ·
#//          reqboundary=Front_SurvivesTheRequestBoundary
#//   DEPLOY keyword=Deployed_HasSentinel + Deployed_SentinelNarrowsTheEnemyAttackPool ·
#//          take=Deployed_OnAttack_PayOne_CreatesABeast · decline=Deployed_OnAttack_Declined_* ·
#//          cannot-pay=Deployed_OnAttack_NoResources_NoOffer
#//   token=Beast is HMW_T03, a 3/3 GROUND Creature — asserted here rather than assumed.
#// ⚠ The discard is a COST, not an effect: it must be payable for the action to be legal at all, which
#// is what the two "unavailable" sections pin.
#// P1 starts with 3 resources: 2 are spent, so RESAVAILABLE 1 proves the cost was actually charged.
#// COVERAGE: control=N/A — STRUCTURAL: this is a LEADER. A leader cannot change control, so "your hand"
#//           and the deployed side's "pay 1 resource" always resolve for its own player. The axis has no
#//           reachable case here.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:POWER:3
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# Front_DiscardOfferIsYourWholeHand
#// HMW_010 front — the discard cost is a CHOICE across your whole hand. Left unanswered so the pending
#// pool is the assertion; three cards in hand means three options and no auto-resolve.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_046
WithP1Hand: SOR_128

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1&myHand-2

---

# Front_EmptyHand_ActionUnavailable_LeaderStaysReady
#// HMW_010 front — ⚠ the discard is a MANDATORY COST. With an EMPTY hand it cannot be paid, so the
#// action is unavailable: no Beast, and critically the leader is NOT exhausted. An implementation that
#// treats the discard as an effect rather than a cost taps the leader for nothing.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:3}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:READY
P1RESAVAILABLE:3
P1NODECISION

---

# Front_OneResource_ActionUnavailable_LeaderStaysReady
#// HMW_010 front — the other half of the cost: ONE resource cannot pay a 2-resource action, so the
#// action is unavailable and the leader stays ready with its resource intact.
#// Boundary partner of the main section (3 resources → fires; 1 → does not).

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1LEADER:READY
P1RESAVAILABLE:1
P1HANDCOUNT:1

---

# Front_SurvivesTheRequestBoundary
#// HMW_010 front — the request-boundary cell: the discard choice is answered in a FRESH process, and
#// the Beast must still be created afterwards.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_046

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1LEADER:EXHAUSTED
P1HANDCOUNT:1

---

# Deployed_HasSentinel
#// HMW_010 deployed — the deployed side has Sentinel. Asserted directly on the leader unit.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_010
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Deployed_SentinelNarrowsTheEnemyAttackPool
#// HMW_010 deployed — Sentinel is a POOL restriction, so its real effect is on the enemy's legal
#// targets. P1 fields deployed Tarfful (Sentinel) AND a non-Sentinel Battlefield Marine, and has a
#// base: three things P2's attacker could otherwise choose. With Sentinel live it has exactly ONE.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN

## EXPECT
ATTACKTARGETS:2:G:0:1

---

# Deployed_OnAttack_PayOne_CreatesABeast
#// HMW_010 deployed — "On Attack: You may pay 1 resource. If you do, create a Beast token."
#// Tarfful attacks the base; P1 accepts, pays 1 of its 2 resources, and a Beast joins the ground arena.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1RESAVAILABLE:1
P2BASEDMG:3

---

# Deployed_OnAttack_Declined_NoBeastAndNoPayment
#// HMW_010 deployed — "YOU MAY pay… IF YOU DO, create". Declining costs nothing and creates nothing:
#// the resources are untouched, which is what separates a real decline from a paid no-op.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2
P2BASEDMG:3
P1NODECISION

---

# Deployed_OnAttack_NoResources_NoOffer
#// HMW_010 deployed — with ZERO resources the payment cannot be made, so no offer is raised at all.
#// P1NODECISION is the load-bearing assertion; the attack itself still resolves.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:0}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P2BASEDMG:3
P1NODECISION

---

# Front_DiscardsTheCHOSENCard_NotTheFirst
#// HMW_010 front — the discard cost honours the CHOICE. Every other front section either has one card
#// in hand or answers myHand-0, which a cost that always discarded the first card would satisfy just the
#// same. Two cards; P1 picks the SECOND (SOR_046): it lands in the discard and SOR_095 stays in hand.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-1

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED

---

# Deployed_OnAttack_ExactlyOneResource_StillOffered
#// HMW_010 deployed — the BOUNDARY of "pay 1 resource": exactly one ready resource is enough. The offer
#// is raised, the resource is spent (0 left) and the Beast is created. Pairs with
#// Deployed_OnAttack_NoResources_NoOffer (0 → no offer) to pin the threshold at 1.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:1}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1RESAVAILABLE:0
P1RESCOUNT:1
P2BASEDMG:3

---

# Deployed_OnAttack_ResourcesAllExhausted_NoOffer
#// HMW_010 deployed — cannot-pay is about READY resources, not resources controlled. P1 has 2 resources
#// but spends both playing SOR_095 Battlefield Marine (cost 2, on-aspect) first; Tarfful then attacks
#// with 0 ready. No offer, no Beast. Distinct from Deployed_OnAttack_NoResources_NoOffer, where the
#// resource ZONE is empty — a gate that counted total resources passes that one and fails only here.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1RESCOUNT:2
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_010
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2BASEDMG:3
P1NODECISION

---

# Deployed_OnAttack_AttackingAUnit_StillOffered
#// HMW_010 deployed — "On Attack" names no target, so attacking a UNIT triggers it exactly as attacking
#// a base does (every other deployed section attacks the base). Tarfful (3/7) hits P2's SEC_080 (3/3)
#// — the offer is taken, a Beast joins, and the defender is defeated by the 3 damage.

## GIVEN
CommonSetup: ggw/bgw/{myLeader:HMW_010:1:1;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:1
P2GROUNDARENACOUNT:0
P2BASEDMG:0
