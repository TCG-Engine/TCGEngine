# Hunter_Deployed_OnAttack_ReturnAndRamp
#// SHD_009 Hunter (deployed On Attack) — "You may reveal a resource you control. If it shares a name with
#// a friendly unique unit, return it to hand and put the top card of your deck into play as a resource."
#// Deployed (7 resources, incl. a SOR_179 resource), Hunter attacks the base; his On Attack reveals the
#// SOR_179 resource (matches the unique SOR_179 unit) → returned to hand + top card (SOR_095) ramped.

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
