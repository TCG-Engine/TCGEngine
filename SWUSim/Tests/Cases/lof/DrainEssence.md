# Deal2AndForce
#// LOF_041 Drain Essence — "Deal 2 damage to a unit. The Force is with you." With one enemy unit the
#// deal-2 auto-resolves onto it, and P1 gains the Force.

## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# CanDamageFriendly
#// LOF_041 — the deal-2 can target a FRIENDLY unit; with only a friendly unit present it auto-resolves
#// onto it and P1 still gains the Force.
## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
WithP1GroundArena: SHD_027:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASFORCE
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# AlreadyHasForce_StillDeals
#// LOF_041 — if P1 already has the Force, the "create your Force token" is redundant but the 2 damage
#// still lands.
## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoUnits_StillGainsForce
#// LOF_041 — with no units in play the damage has no target, but P1 still gains the Force.
## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASFORCE

---

# NoUnits_AlreadyForce_NoOp
#// LOF_041 — no units to damage and P1 already has the Force: the card resolves with no observable change.
## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
WithP1Force: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASFORCE
P1HANDCOUNT:0
