# Hunter_Deployed_OnAttack_ReturnAndRamp
#// SHD_009 Hunter (deployed On Attack) — "You may reveal a resource you control. If it shares a name with
#// a friendly unique unit, return it to hand and put the top card of your deck into play as a resource."
#// Deployed (7 resources, incl. a SOR_179 resource), Hunter attacks the base; his On Attack reveals the
#// SOR_179 resource (matches the unique SOR_179 unit) → returned to hand + top card (SOR_095) ramped.
#// COVERAGE: offer=Hunter_Front_OfferIsEveryResourceControlled (every resource is a legal reveal, incl. the
#//           one exhausted to pay the ability's own cost) · reqboundary=Hunter_Front_NameMatch_SurvivesRequestBoundary
#//           control=Hunter_Front_ForeignOwnedResource_ReturnsToItsOWNERSHand (a P2-OWNED resource in P1's
#//           resource row returns to P2's hand) · boundary=Hunter_Front_NameMatch_ReturnAndRamp vs
#//           Hunter_Front_NameMatch_EmptyDeck_ReturnsButNoRamp (return happens, ramp has no card) ·
#//           decline=Hunter_Deployed_OnAttack_Decline_NoEffect (deployed side only — the front's reveal is
#//           mandatory, so a front decline branch is N/A)

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 6:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-6

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# Hunter_Front_NameMatch_ReturnAndRamp
#// SHD_009 Hunter (front Action [1 resource, Exhaust]) — "Reveal a resource you control. If it shares a
#// name with a friendly unique unit, return the resource to its owner's hand and put the top card of your
#// deck into play as a resource." P1 controls the unique SOR_179 (Boba Fett) and a SOR_179 resource;
#// revealing it returns the resource to hand and ramps the top card (SOR_095) into a new resource. Net
#// resource count unchanged (2 → SOR_179 returned → SOR_095 ramped = 2); SOR_179 now in hand; deck empty.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:0
P1GROUNDARENACOUNT:1

---

# Hunter_Front_NoNameMatch_NoEffect
#// SHD_009 Hunter — the return+ramp is gated on the revealed resource sharing a name with a friendly
#// UNIQUE unit. P1 controls the unique SOR_179 but reveals a generic (non-Boba-Fett) resource → no name
#// match → nothing happens (resource count unchanged, deck untouched, hand empty).

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 2
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1

---

# Hunter_Front_NonUniqueNameMatch_NoEffect
#// SHD_009 Hunter — the load-bearing negative: the name match must ALSO require the matched friendly unit
#// to be UNIQUE. P1 controls BOTH a unique SOR_179 (Boba Fett) and a NON-unique SOR_046 (Consular Security
#// Force), and reveals the SOR_046 resource. Its name matches a friendly unit, but that unit is not unique
#// → nothing moves. The unique SOR_179 is on the board at the same time, so the no-op proves the
#// uniqueness gate specifically, not "there is no unique unit anywhere".

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 SOR_179:1:0]
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1
P1GROUNDARENACOUNT:2

---

# Hunter_Front_OfferIsEveryResourceControlled
#// SHD_009 Hunter — "Reveal A RESOURCE you control" is unrestricted: EVERY resource is a legal reveal,
#// including the one just exhausted to pay the ability's own 1-resource cost and ones whose name matches
#// nothing. Three resources are seated and the pick is left PENDING so the offer itself can be read.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1,1:SOR_095:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SELECTABLEEXACT:myResources-0&myResources-1&myResources-2
P1RESCOUNT:3
P1RESAVAILABLE:2

---

# Hunter_Deployed_NoNameMatch_NoEffect
#// SHD_009 Hunter (deployed On Attack) — pre-deployed leader attacks the base and reveals the SOR_179
#// resource while NO friendly unit shares that name (only the non-unique SOR_046 is on the board) →
#// no return, no ramp. Deck and resource count untouched.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-1

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1

---

# Hunter_Deployed_NonUniqueNameMatch_NoEffect
#// SHD_009 Hunter (deployed On Attack) — the deployed side carries the same uniqueness gate as the front.
#// A unique SOR_179 and a non-unique SOR_046 are both friendly; revealing the SOR_046 resource matches a
#// friendly unit that is NOT unique → nothing moves.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 SOR_179:1:0]
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:2:BASE
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1

---

# Hunter_Deployed_OnAttack_Decline_NoEffect
#// SHD_009 Hunter — the deployed side adds "YOU MAY reveal a resource", a decline branch the front's
#// mandatory reveal does not have. Declining the On Attack offer leaves the SOR_179 resource in place and
#// the deck untouched even though the reveal WOULD have matched the friendly unique SOR_179.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009:1:1:1}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1

---

# Hunter_Front_NameMatch_SurvivesRequestBoundary
#// SHD_009 Hunter — the reveal is answered in a SEPARATE request from the one that started the ability, so
#// the return+ramp resolves against a gamestate that has been serialized and read back. Same fixture and
#// same end state as Hunter_Front_NameMatch_ReturnAndRamp, with the boundary forced in between.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:0
P1GROUNDARENACOUNT:1

---

# Hunter_Front_ForeignOwnedResource_ReturnsToItsOWNERSHand
#// SHD_009 Hunter — "return the resource to ITS OWNER's hand". P1 CONTROLS a SOR_179 resource that P2
#// OWNS (the end state after an enemy card was resourced into P1's zone); it still matches the friendly
#// unique SOR_179 unit, so it is revealed and returned — but to P2's hand, not P1's. P1 still ramps the
#// top of their own deck, so P1's resource count is unchanged and P1's hand stays empty.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1ResourceControlled: SOR_179:2
WithP1Resources: 1:SOR_046:1
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P1RESCOUNT:2
P1DECKCOUNT:0

---

# Hunter_Front_NameMatch_EmptyDeck_ReturnsButNoRamp
#// SHD_009 Hunter — boundary pair with Hunter_Front_NameMatch_ReturnAndRamp: the return and the ramp are
#// two separate effects, and with an EMPTY deck the return still happens while the ramp simply has no card
#// to put into play. Net: the resource count DROPS to 1 instead of holding at 2.

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 1:SOR_046:1,1:SOR_179:1

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_179
P1RESCOUNT:1
P1DECKCOUNT:0
