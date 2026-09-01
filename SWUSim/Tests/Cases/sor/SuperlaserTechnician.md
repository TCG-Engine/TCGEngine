# WhenDefeated_PutsSelfAsResource
#// COVERAGE: offer=N/A (no target choice — the ability moves only "this unit") · decline=N/A by
#//           standing ruling — the "you may" ramp AUTO-RESOLVES (free ready resource is always
#//           taken; deliberate product call, same as SHD_085) · boundary=WhenDefeated_PutsSelfAsResource
#//           (card still in discard → ramps, READY) vs MovedToResourcesByEnemySteal_WhenDefeatedFizzles
#//           (card already gone from discard → no ramp) · control=MovedToResourcesByEnemySteal_
#//           WhenDefeatedFizzles (the stolen card ends owned by P2 but controlled by P1; the ramp is
#//           keyed to the DEFEATED unit's controller and finds nothing) · reqboundary=N/A (the ramp
#//           resolves in the defeat drain with no intervening decision)
#// SOR_083 Superlaser Technician (2/1, Ground) — When Defeated: You may put this unit into
#// play as a resource AND READY IT. It attacks Battlefield Marine (3/3): it deals 2 (Marine
#// survives) and takes 3 (1 HP → defeated). The ramp auto-resolves (nobody declines it), moving
#// a SOR_083 copy from discard into the resource zone READY (explicit "and ready it"): resources 0 → 1.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_083:1:0    # Superlaser Technician (ready) — attacker, dies
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine (3/3) — kills it back

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:1

---

# MovedToResourcesByEnemySteal_WhenDefeatedFizzles
#// SOR_083 Superlaser Technician — the When Defeated ramp moves "this unit" from its owner's
#// discard; if the card has already LEFT the discard when the trigger resolves, it fizzles.
#// P1's Arquitens Assault Cruiser (SHD_122, 7/8: attacks-and-defeats a non-leader unit → put the
#// defeated unit into play as a resource under P1's control) attacks the Technician via Swoop Down
#// (SHD_230: space unit gains Saboteur + can attack ground, +2/+0 and defender -2/-0). The 0-power
#// Technician dies dealing nothing back; the steal takes the card out of P2's discard, so the
#// Technician's own ramp finds nothing: P2 gains NO resource. The stolen card sits in P1's resource
#// zone exhausted (steal has no "ready it" rider), owned by P2 but controlled by P1.

## GIVEN
CommonSetup: yyk/ggk/{myResources:1;handCardIds:SHD_230}
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0     # Arquitens Assault Cruiser — attacker
WithP2GroundArena: SOR_083:1:0    # Superlaser Technician 2/1 — defeated, then stolen

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:2
P1RESAVAILABLE:0
P2RESCOUNT:0
P2DISCARDCOUNT:0
P2GROUNDARENACOUNT:0
P1NODECISION

---

# TwoCopiesInDiscard_OnlyOneBecomesAResource
#// SOR_083 Superlaser Technician — quantity discrimination on "put THIS UNIT into play as a resource".
#// The ability moves exactly ONE card, the one that was just defeated, even when identical copies are
#// already sitting in the discard pile. P1 starts with a SOR_083 already in the discard and a second
#// SOR_083 on the board; the board copy attacks a Battlefield Marine (3/3) and dies. The discard
#// momentarily holds TWO copies and exactly one leaves it, so the discard settles at 1 and the resource
#// zone at 1 (READY). A "move every matching copy" implementation would ramp 2 and empty the discard.

## GIVEN
CommonSetup: ggw/ggw/{myResources:0;discardCardIds:SOR_083}
P1OnlyActions: true
WithP1GroundArena: SOR_083:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_083
P1NODECISION

---

# ReturnedToHand_IsNotDefeated_NoRamp
#// SOR_083 Superlaser Technician — the trigger word "When DEFEATED" is load-bearing: merely LEAVING
#// play is not enough. P1 plays Waylay (SOR_222, "Return a non-leader unit to its owner's hand") on its
#// own Technician. The unit leaves the arena for P1's hand, which is not a defeat, so no When Defeated
#// fires: the resource count stays where it started and the discard holds only the spent Waylay.
#// Negative partner of WhenDefeated_PutsSelfAsResource.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5;handCardIds:SOR_222}
P1OnlyActions: true
WithP1GroundArena: SOR_083:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_083
P1RESCOUNT:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_222

---

# CloneCopyOfTechnician_RampsTheCloneCard
#// SOR_083 Superlaser Technician — a unit that entered play AS a Technician still has the Technician's
#// printed When Defeated, and "put THIS UNIT into play as a resource" means the physical card that was
#// that unit. P1 plays Clone (TWI_116) copying the enemy Superlaser Technician, so P1 now controls a
#// 2/1 Technician whose card is really a Clone; P1 then shoots its own copy with Open Fire (4 damage
#// vs 1 HP). Intended: a copy has the copied card's printed abilities, and "this unit" means the
#// physical card that WAS that unit — which is the Clone card, since a Clone copy leaves play as the
#// real card. So the When Defeated resolves off the copied identity and puts the Clone card into P1's
#// resource zone
#// READY — resources 20 → 21, and nothing of the copy is left in P1's discard except Open Fire.
#// Passing control for the same clause with no Clone involved: WhenDefeated_PutsSelfAsResource.
#// ⚠ RED — CANDIDATE ENGINE BUG (2026-09-01). Observed: resources stay 20 and P1's discard ends with
#// TWO cards (Open Fire + the Clone card), i.e. the ramp silently no-ops. The trigger DOES fire —
#// OnWhenDefeated is dispatched on the COPIED identity (SOR_083) — but the handler re-finds "this unit"
#// by scanning the discard for a card whose printed CardID is literally SOR_083, and a Clone copy
#// leaves play as the REAL card (TWI_116), so the scan returns null. Verified separately that the copy
#// really is a 2/1 Superlaser Technician while in play, and that the Clone card really is the second
#// discard entry after the defeat. Same printed-text family (a leave-play handler that re-finds itself
#// in the discard by hardcoded CardID, therefore blind to a Clone copy): SHD_085 (same file),
#// LAW_159 Expendable Mercenary, LOF_097 Eeth Koth, SHD_107 Enterprising Lackeys. Fix shape: pass the
#// defeated object (or its post-revert CardID) into the lookup instead of a literal.

## GIVEN
CommonSetup: ggk/ggk/{myResources:20}
P1OnlyActions: true
WithP1Hand: [TWI_116 SOR_172]
WithP2GroundArena: SOR_083:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:21
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_172
P2GROUNDARENACOUNT:1
