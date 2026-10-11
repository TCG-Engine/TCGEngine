<?php
/**
 * Fixtures for cards starting with 'I', written with the scenario builder (see Scenario.php).
 * Migrated from inline definitions in create-prd-ability-fixtures.php; their comments moved with them.
 */

// --- Incendiary Shot: load into an unloaded Gun weapon ---
// Incendiary Shot's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Framework Sidearm (a REGALIA,WEAPON with the GUN subtype and no Subcards, i.e.
// unloaded) is seeded onto the field as the [REST] Load ability's target, same pattern as
// penetrator-round-load-gun/anathemas-end-load-gun. Only the always-available Load half is
// covered; the [Class Bonus] On Hit damage trigger requires a real combat hit and is out of
// scope.
// Notes carried over from the original definition:
//   - Framework Sidearm (unloaded GUN weapon) - Load target
gaScenario('incendiary-shot-load-gun')
    ->standardDeck('Incendiary Shot')
    ->seed('myField', 'Framework Sidearm')
    ->seed('myHand', 'Incendiary Shot')
    ->castSpell('Incendiary Shot', pay: 1)
    ->action(1, 10001, 'myField-2!CustomInput!Activate:0')->target('myField-1')
    ->expectProperty('p1Field-1', 'Subcards', '["3qu7d6sopo"]', 'Loading appends the bullet\'s CardID to the target Gun weapon\'s Subcards')
    ->expectZoneCount('myField', 2, 1, 'The bullet is removed from the field entirely, not moved to another zone, once loaded')
    ->expectDqEmpty()
    ->register();

// --- Imperial Countermeasure: prevent the next 4 damage to target unit + draw into memory ---
// Imperial Countermeasure's element is EXALTED,NORM -- EXALTED auto-enables whenever any
// OTHER advanced element is unlocked in lineage (GetPlayerEnabledElements(), GameLogic.php),
// so the starting champion's Subcards are patched with a real UMBRA champion (Tristan,
// Shadowdancer, reused from anathemas-end-load-gun) purely to trigger that auto-enable, not
// because the card itself needs UMBRA. Both clauses (prevention + draw into memory) are
// covered in one activation.
// Notes carried over from the original definition:
//   - Any advanced element auto-enables EXALTED
gaScenario('imperial-countermeasure-prevent-draw')
    ->standardDeck('Imperial Countermeasure')
    ->champion(lineage: ['Tristan, Shadowdancer'])
    ->seed('myHand', 'Imperial Countermeasure')
    ->castSpell('Imperial Countermeasure', pay: 1, window: 'both', target: 'myField-0')
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED","IMPERIAL_COUNTERMEASURE_4"]', 'The targeted champion is protected by an IMPERIAL_COUNTERMEASURE_4 shield')
    ->expectZoneCount('myDeck', 8, 1, 'The draw-into-memory clause reduces the deck by one')
    ->expectDqEmpty()
    ->register();

// --- Intangible Geist: On Enter may return regalia from banishment to material deck ---
// Intangible Geist is a CRUX (advanced element) ALLY card, so the starting champion's
// Subcards are patched with a real CRUX champion (Lorraine, Crux Knight, reused from
// sabela-gossamer-penance-enter/spirit-blade-infusion-combat-discount). Backup Charger (a
// REGALIA,ITEM) is seeded directly into myBanish as the On Enter's optional target. Only the
// On Enter is covered; the [Class Bonus] combat-damage-prevention static ability is out of
// scope.
// Notes carried over from the original definition:
//   - CRUX lineage/element unlock
//   - Backup Charger (REGALIA,ITEM) - On Enter optional target
gaScenario('intangible-geist-banish-to-material')
    ->standardDeck('Intangible Geist')
    ->readyFor('Intangible Geist')
    ->seed('myBanish', 'Backup Charger')
    ->seed('myHand', 'Intangible Geist')
    ->castSpell('Intangible Geist', pay: 4, target: 'myBanish-0')
    ->register();

// --- Immolation Trap: destroy target damaged ally ---
// Immolation Trap's element is FIRE (matching the starting champion), so no lineage patch is
// needed. Dungeon Guide is seeded onto the opponent's field with 1 pre-existing Damage so it
// qualifies as a legal "damaged ally" target. Without the [Class Bonus] discount, the full
// 2-reserve cost is paid.
// Notes carried over from the original definition:
//   - Dungeon Guide, pre-damaged - destroy target
gaScenario('immolation-trap-destroy-damaged')
    ->standardDeck('Immolation Trap')
    ->seed('theirField', 'Dungeon Guide', properties: ['Damage' => 1])
    ->seed('myHand', 'Immolation Trap')
    ->castSpell('Immolation Trap', pay: 2, window: 'both', target: 'theirField-1')
    ->register();

// --- Inspiring Call: allies +1 power until end of turn, draw a card into memory ---
gaScenario('inspiring-call-buff-draw')
    ->standardDeck('Inspiring Call')
    ->seed('myHand', 'Inspiring Call')
    ->castSpell('Inspiring Call', pay: 3, window: 'both')
    ->register();

// --- Into the Fray: target ally gets +1 POWER per other ally until end of turn ---
// Notes carried over from the original definition:
//   - Dungeon Guide 1/2 - buff target
//   - Formidable Youxia - other ally, makes otherCount=1
gaScenario('into-the-fray-buff')
    ->standardDeck('Into the Fray')
    ->seed('myField', 'Dungeon Guide')
    ->seed('myField', 'Formidable Youxia')
    ->seed('myHand', 'Into the Fray')
    ->castSpell('Into the Fray', pay: 2, window: 'both', target: 'myField-1')
    ->register();

// --- Idle Thoughts: Glimpse 4, keep original order (shared with Jin Starter Deck) ---
// Notes carried over from the original definition:
//   - pay 1-reserve cost
//   - MZREARRANGE response: submit the same "Top=...;Bottom=" param verbatim to keep original
//   - order (same no-op default GoldfishChooseAction uses for this decision type,
//   - GrandArchiveSim/Custom/GameLogic.php:254-255) -- confirmed via direct probe against this
//   - exact deck/seed since the glimpsed card IDs depend on the deterministic shuffle.
gaScenario('idle-thoughts-glimpse-4')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Idle Thoughts')
    ->play('Idle Thoughts')->pay(1)->answer(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom=')
    ->expectZoneCount('myDeck', 5, 1, 'Glimpse 4 only looks and reorders -- deck count is unchanged')
    ->expectZoneCount('myGraveyard', 1, 1, 'Idle Thoughts itself resolves to the graveyard')
    ->expectDqEmpty()
    ->register();

// --- Idle Thoughts: Glimpse 4 -- a forged MZREARRANGE answer (unoffered / dropped / duplicated cards) is rejected ---
// Idle Thoughts glimpses 4 ("Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY;Bottom="). ENGINE BUG (found
// and FIXED alongside the MZMULTICHOOSE one): GameValidateDecisionAnswer() (Custom/GameLogic.php) skipped
// MZREARRANGE too, so GlimpseApply rebuilt the deck from whatever card ids the client sent -- unoffered
// ids silently deleted the four glimpsed cards (the answer's ids matched nothing in the deck), and the
// apply paths that build cards from ids (new Deck($cid)) could create cards from nothing. The answer must
// now contain exactly the offered card ids (any arrangement across the offered piles). Three illegal
// answers are rejected, the prompt stays pending, and a legal reordering still resolves.
// Notes carried over from the original definition:
//   - pay 1-reserve cost
//   - The prompt is still pending: a legal reordering (Fairy Whispers to the front, one Dungeon Guide to the bottom) resolves.
gaScenario('idle-thoughts-glimpse-4-rejects-forged-rearrange')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Idle Thoughts')
    ->castSpell('Idle Thoughts', pay: 1)
    ->rejects(1, 'Top=ZZZZZZZZZZ,ZZZZZZZZZZ,ZZZZZZZZZZ,ZZZZZZZZZZ;Bottom=', 'Card ids that were never glimpsed are rejected')
    ->rejects(1, 'Top=em6eEh9q8y;Bottom=', 'An answer that drops three of the four glimpsed cards is rejected')
    ->rejects(1, 'Top=em6eEh9q8y,em6eEh9q8y,em6eEh9q8y,n8wyfG9hbY,n8wyfG9hbY;Bottom=', 'An answer that duplicates a glimpsed card (five cards for four) is rejected')
    ->answer(1, 'Top=n8wyfG9hbY,em6eEh9q8y,em6eEh9q8y;Bottom=em6eEh9q8y')
    ->register();

// --- Imperial Rifleman: [Class Bonus] On Enter: becomes distant ---
// Imperial Rifleman's On Enter trigger only fires on a REAL materialize (BridgeAddToZone
// seeds silently, no Enter trigger), so it must be played from hand while a RANGER Class
// Bonus is already active. Seed the RANGER champion first, then Imperial Rifleman into a
// known hand slot; its 3-reserve cost needs 3 reps of the myHand-0 reserve-payment decision.
// Notes carried over from the original definition:
//   - Diana, Keen Huntress (RANGER champion) - Class Bonus source
gaScenario('imperial-rifleman-class-bonus-enter-distant')
    ->standardDeck('Imperial Rifleman')
    ->seed('myField', 'Diana, Keen Huntress')
    ->seed('myHand', 'Imperial Rifleman')
    ->castSpell('Imperial Rifleman', pay: 3)
    ->expectDqEmpty()
    ->expectProperty('p1Field-2', 'CardID', '17fzcyfrzr', 'Imperial Rifleman is at p1Field-2')
    ->expectProperty('p1Field-2', 'TurnEffects', '["ENTERED_THIS_TURN","DISTANT"]', '[Class Bonus] On Enter: Imperial Rifleman becomes distant')
    ->register();

// --- Imperial Recruit: Foster; gets +1 POWER as long as it's fostered ---
// "Fostered" is a plain (persistent) TurnEffect (IsFostered() in CardLogic.php just checks
// for the tag), so the static +1 POWER while fostered can be tested directly by patching
// TurnEffects at setup, without needing a real recollection-phase Foster transition.
// Notes carried over from the original definition:
//   - Imperial Recruit, fostered
//   - harmless no-op click
gaScenario('imperial-recruit-fostered-power')
    ->standardDeck('Imperial Recruit')
    ->seed('myField', 'Imperial Recruit', properties: ['TurnEffects' => ['FOSTERED']])
    ->target('myField-0')
    ->tested('Imperial Recruit')
    ->register();

// --- Imperial Sentry: [Class Bonus] Intercept redirect ---
// Same shape as swift-recruit-intercept-redirect, except Imperial Sentry's Intercept is
// [Class Bonus]-gated (HasKeyword_Intercept, GeneratedKeywordCode.php ~487, checks
// IsClassBonusActive($player, CardClasses($cardId))), so player 1 (the defender)'s champion is
// patched to a GUARDIAN champion (Tonoris, Lone Mercenary) to satisfy it -- unlike Swift
// Recruit's unconditional Intercept, a plain field seed alone is not enough here.
// GetAvailableInterceptRedirectTargets (CombatLogic.php:605-624) only offers a redirect when
// the attack's TARGET is a CHAMPION, so this needs the opponent (P2) attacking P1's champion.
// P1 ends turn 1 (nothing to do), P2 declines their MAT-phase offer, then P2's champion attacks
// P1's champion using a weapon (Spirit of Fire has no base POWER). The resulting
// "Choose_an_interceptor" MZMAYCHOOSE for P1 offers Imperial Sentry (awake, ALLY, HasIntercept
// now satisfied); redirecting moves the attack's target to it, and the following "Retaliate?"
// decision is declined so the resulting combat damage lands on Imperial Sentry instead of the
// champion.
// Notes carried over from the original definition:
//   - Tonoris, Lone Mercenary (GUARDIAN) - Class Bonus source
//   - Imperial Sentry, awake by default
//   - Executioner's Spear (1 POWER), P2's own field -- Spirit of Fire has no base POWER, so a weapon is needed for a legal attack
//   - P1 declines their own MAT-phase materialize offer first
//   - P1 formally ends turn 1 (nothing to do)
//   - P2 declines their MAT-phase materialize offer
//   - P2's champion attacks
//   - choose Executioner's Spear as the weapon
//   - target P1's champion
//   - redirect to Imperial Sentry
//   - decline Retaliate
gaScenario('imperial-sentry-class-bonus-intercept')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Tonoris, Lone Mercenary')
    ->seed('myField', 'Imperial Sentry')
    ->seed('myField', 'Executioner\'s Spear', player: 2)
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-0')->target('myField-1')
    ->answer(1, '-')
    ->tested('Imperial Sentry')
    ->expectDamage('p1Field-0', 0, 'champion takes no damage, attack redirected')
    ->expectDamage('p1Field-1', 1, '[Class Bonus] Intercept redirects the attack onto Imperial Sentry instead')
    ->register();

// --- Ingredient Pouch: (1), REST: Gather ---
// Ingredient Pouch's REST ability pays 1 reserve then Gathers (summons one of six possible
// resource tokens at random -- resolves to Blightroot with this fixture's seed, same as
// foraging-servant-enter-gather). Field items with an activated ability are not clickable via a
// plain myField-N!FSM! action -- their ability is offered as a fast-action MZMAYCHOOSE
// opportunity once the turn player attempts to pass, answered with the encoded
// "{mzID}@Activate-{abilityIndex}@{label}" choice string (same shape as sweet-ambrosia-banish-
// recover). Blightroot itself carries a fast "Sacrifice:" ability, so the AbilityOpportunity
// window that opens after Gather resolves re-offers it once more even after the first decline
// (a fresh priority round re-checks the still-available fast ability) -- a second explicit
// decline is needed to fully close out and leave the decision queue empty.
// Notes carried over from the original definition:
//   - Ingredient Pouch
//   - attempt to pass -> offers the fast-action opportunity
//   - decline the gathered Blightroot's own fast Sacrifice ability (1/2)
//   - decline again -- the same fast ability is re-offered in the next priority round (2/2)
gaScenario('ingredient-pouch-rest-gather')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Ingredient Pouch')
    ->endTurn()->answer(1, '-')->answer(1, 'myField-1@Activate-0@Gather')->pay(1)->pass(1)->pass(1)
    ->tested('Ingredient Pouch')
    ->register();

// --- Invoke Dominance: champion +3 level, can't activate non-ally cards this turn ---
// Invoke Dominance is TERA -- the starting champion's Subcards are patched with a real TERA
// champion (Silvie, Loved by All) to unlock element access. Unlike the "+1 level while Animal/
// Beast ally" static clauses tested indirectly via Glimpse LV elsewhere this session, this +3
// level is applied via a direct AddTurnEffect("PLljzdiMmq") on the champion (GeneratedMacroCode.
// php ~20641-20652), so it is directly observable via TurnEffects without needing the indirect
// proof technique. The "can't activate non-ally cards this turn" restriction is a silent no-op
// check embedded inside DoActivateCard itself (GameLogic.php ~2008-2014), not a legality
// rejection CanActivateCard/expectFailure would catch -- confirmed instead via the global effect
// flag it sets (AddGlobalEffects "PLljzdiMmq_NO_NONALLY") being present in myGlobalEffects.
// Preserve (this card returning to the material deck as it resolves, rather than the graveyard)
// is a separate, out-of-scope mechanic.
// Notes carried over from the original definition:
//   - Silvie, Loved by All -- unlocks TERA
gaScenario('invoke-dominance-level-lock-non-ally')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(lineage: ['Silvie, Loved by All'])
    ->seed('myHand', 'Invoke Dominance')
    ->castSpell('Invoke Dominance', pay: 1)
    ->register();

// --- Impassioned Tutor: On Attack: champion +1 level until end of turn ---
// Impassioned Tutor is NORM. Same real attack-declaration sequence as ardent-cloudstriker-west-
// attack-champion-buff: seeded directly onto the field (bypassing its reserve cost) with Status
// patched awake so it can declare an attack immediately. Its On Attack fires as soon as the
// attack is declared (AddGlobalEffects "MECS7RHRZ8", GeneratedMacroCode.php ~24673-24677),
// before damage resolves.
// Notes carried over from the original definition:
//   - Impassioned Tutor
//   - awake, can attack
//   - decline materialize offer
//   - declare attack with Impassioned Tutor
//   - target opponent's champion
gaScenario('impassioned-tutor-on-attack-level')
    ->deckTemplate('rai-windslice', 'Dungeon Guide')
    ->seed('myField', 'Impassioned Tutor')
    ->patch('myField-1', ['Status' => 2])
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->action(1, 10002, 'myField-1!FSM!')->target('theirField-0')
    ->tested('Impassioned Tutor')
    ->expectDqEmpty()
    ->expectProperty('p1Field-1', 'Status', '1')
    ->expectProperty('p1Field-1', 'TurnEffects', '[]')
    ->expectProperty('p1GlobalEffects-0', 'CardID', 'MECS7RHRZ8')
    ->expectDamage('p2Field-0', 1)
    ->expectProperty('p2Field-0', 'Counters', '{"_champDamageThisTurn":1}')
    ->expectProperty('p2Field-0', 'TurnEffects', '["WAS_ATTACKED","DAMAGED_SINCE_LAST_TURN"]')
    ->expectZoneCount('myHand', 8, 1)
    ->expectZoneCount('theirHand', 8, 1)
    ->register();

// --- Intrepid Spearman: dealDamageAbilities dispatch coverage (pal7cpvn96) ---
// Effects Stack fixture-coverage gap fill: dealDamageAbilities["pal7cpvn96:0"] is itself an
// intentional no-op stub (its real logic is a synchronous replacement effect inlined in
// OnDealDamage(), GrandArchiveSim/Custom/CombatLogic.php ~line 4925, guarded by
// DecisionQueueController::GetVariable("CombatAttacker") !== null -- i.e. it only applies to
// REAL COMBAT damage, unlike Powered Defender's dispatch-table trigger above). No prior fixture
// deals real combat damage to this card, so this fixture exercises the replacement effect
// itself: [Level 1+] once per turn, reveal a random memory card when combat damage would be
// dealt; if wind element, prevent 3 of that damage.
//
// Requires: (1) champion at Level 1+ (PlayerLevel() reads ObjectCurrentLevel(), which adds a
// "level" counter on top of the card's printed level -- patched directly via setProperties
// rather than scripting a real level-up), (2) a real attack that deals combat damage to this
// ally specifically. GetValidAttackTargets() (CombatLogic.php) allows a champion to attack an
// opposing ALLY directly (ZoneSearch("theirField", ["ALLY","CHAMPION"])), so no
// intercept/redirect dance is needed -- P2's champion just targets theirField-1 (P1's Intrepid
// Spearman) directly. P2's champion (Spirit of Fire, 0 base power) needs a weapon to have any
// attack power; Executioner's Spear (zv6yp6q7zw, printed 1 POWER, no durability requirement --
// verified live, unlike Tideholder Claymore which requires durability counters to be a legal
// weapon choice per GetAvailableWeapons()) is seeded onto P2's field. (3) Exactly one memory
// card, and it must be WIND element, for a deterministic array_rand() reveal -- Vainglory
// Retribution (qtzsekkjn3, WIND) is reused as the memory card. With 1 combat damage and -3
// prevention, final Damage clamps to 0 -- verified live: TurnEffects gains the "pal7cpvn96"
// once-per-turn marker and FlashMessage is set to "REVEAL:qtzsekkjn3" regardless, which is
// exactly the code path that (per CombatLogic.php) sets TurnEffects *before* the amount<=0
// early-return, so both survive as independent proof the replacement effect actually ran.
// Notes carried over from the original definition:
//   - sole memory card, WIND element
//   - champion Level 1+
//   - Executioner's Spear, 1 POWER
//   - P1 has nothing playable this turn (no hand cards seeded) -- a single health-pass ends
//   - the whole turn and hands priority straight to P2's MAIN phase (verified live).
//   - decline P2's own MAT-phase materialize offer
//   - P2's champion attacks
//   - choose Executioner's Spear as the weapon
//   - target P1's Intrepid Spearman directly (not the champion)
//   - decline Retaliate
gaScenario('intrepid-spearman-combat-damage-reveal-wind-prevent')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Intrepid Spearman')
    ->seed('myMemory', 'Vainglory Retribution')
    ->champion(level: 1)
    ->seed('myField', 'Executioner\'s Spear', player: 2)
    ->endTurn()->pass(2)
    ->action(2, 10002, 'myField-0!FSM!')->answer(2, 'myField-1')->answer(2, 'theirField-1')->pass(1)
    ->tested('Intrepid Spearman')
    ->register();

// --- Imperial Accord: negate target advanced-element activation unless controller pays 6 (1S7Q5fqX5u) ---
// Play Neos Sight (free, reserve 0) -- its Opportunity window offers Imperial Accord as a fast
// response. Once Imperial Accord's own 2 reserve is paid, its on-play trigger fires immediately
// and queues the MZCHOOSE target choice, answered as the very next action. Imperial Accord's
// "pay 6 to prevent" check needs the target's controller (self, player 1) to have >= 6
// reserve-payable cards; after paying for both cards, far fewer than 6 remain, so the engine
// auto-negates with no further YES/NO prompt.
// Notes carried over from the original definition:
//   - Permanent lineage unlocks (see astral-seal-negate-activation-banish's note on why not
//   - PRISMATIC_CODEX_IGNORE_ELEMENT): NEOS for the bait, EXALTED + WATER for Imperial Accord
//   - itself (a dual-element card).
//   - Neos Sight (NEOS, advanced element), bait activation to negate
gaScenario('imperial-accord-negate-advanced-element')
    ->deck($fillerDeck)
    ->champion(lineage: ['Tonoris, Creation\'s Will', 'Mordred, Fated Luminary', 'Spirit of Water'])
    ->seed('myHand', 'Neos Sight')
    ->seed('myHand', 'Imperial Accord')
    ->play('Neos Sight')->target('myHand-7')->pay(2)->answer(1, 'EffectStack-0')
    ->tested('Imperial Accord')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '1S7Q5fqX5u')
    ->expectProperty('p1Graveyard-1', 'CardID', '4n1n3gygoj', 'Playing Imperial Accord fires its on-play trigger (playCardAbilities["1S7Q5fqX5u:0"]); the target\'s controller has fewer than 6 available reserve payments, so the targeted advanced-element activation is auto-negated into the graveyard instead of resolving')
    ->expectProperty('p1Graveyard-1', 'Counters', '[]')
    ->expectProperty('p1Graveyard-1', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 5, 1)
    ->expectZoneCount('myGraveyard', 2, 1)
    ->expectZoneCount('myMemory', 2, 1)
    ->register();

// --- Insignia of the Corhazi: (3), REST: put a preparation counter on your champion ---
// Insignia of the Corhazi is seeded directly onto the field (same pattern as
// necklace-of-foresight-banish-glimpse) rather than played from hand, so its materialize flow
// is out of scope -- this fixture is only about the always-available "(3), [REST]: put a
// preparation counter" activated ability (CardActivated macro 52u81v4c0z:0 in
// GeneratedMacroCode.php). Its element is LUXEM, and DoActivateCard()'s
// CanPlayerUseCardElement() gate applies to a field item's reserve-cost activated ability the
// same as a hand play (verified live -- with the default FIRE/NORM starting champion, the
// whole activation silently no-ops before ever reaching MZMove to the EffectStack), so the
// starting champion's CardID is patched directly to Zander, Blinding Steel (LUXEM, ASSASSIN).
// Notes carried over from the original definition:
//   - Zander, Blinding Steel (LUXEM) - element-requirement precondition
//   - Insignia of the Corhazi, seeded straight onto the field
gaScenario('insignia-of-corhazi-rest-prepare')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->champion(cardName: 'Zander, Blinding Steel')
    ->seed('myField', 'Insignia of the Corhazi')
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->passBoth()
    ->tested('Insignia of the Corhazi')
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'Counters', '{"preparation":1}', '(3), [REST] activated ability puts a preparation counter on the champion')
    ->expectProperty('p1Field-1', 'TurnEffects', '["ENTERED_THIS_TURN"]')
    ->expectProperty('p1Field-1', 'Status', '1', 'Insignia of the Corhazi is rested by its [REST] cost')
    ->register();

// --- Increasing Danger: Draw a card. Each player draws a card into their memory ---
// Increasing Danger's element is FIRE, matching the starting champion, so no lineage patch is
// needed.
gaScenario('increasing-danger-draw-memory')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Increasing Danger')
    ->castSpell('Increasing Danger', pay: 2)
    ->expectDqEmpty()
    ->expectProperty('p1Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-3', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-4', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', '7tUvIHeo0i')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Memory-1', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Memory-0', 'CardID', 'em6eEh9q8y')
    ->expectZoneCount('myHand', 6, 1, 'Draws a card (5 after paying costs, +1 draw = 6)')
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 3, 1, 'Own player draws a card into memory (2 from reserve payment, +1 from the card\'s own effect)')
    ->expectZoneCount('theirMemory', 1, 1, 'Opponent also draws a card into their memory')
    ->register();

// --- Imperial Spy: On Kill, put a preparation counter on your champion ---
// Imperial Spy's element is NORM, so no lineage patch is needed. Rule 1.h blocks the game's
// first player from attacking on turn 1, so player 1 ends turn 1 and player 2 attacks with
// Imperial Spy on their own turn 1 instead. A directly-patched Damage precondition on a
// freshly-seeded ally does NOT survive ending the turn -- GA's recollection/end-of-turn
// processing clears Damage back to 0 for any object whose Damage was set outside a real
// damage event (verified live: a Dungeon Guide seeded with Damage=2 read back as Damage=0
// immediately after the very next "end turn" action, before combat ever ran) -- so instead of
// pre-damaging a 3-Life ally, Wandering Glaivier (1 Life, from this same Pantheon pool) is
// used as the kill target: Imperial Spy's printed 2 POWER is lethal on a single fresh hit with
// no precondition needed. The defender's "Retaliate?" MZMAYCHOOSE (Wandering Glaivier, being
// the target with positive POWER, is eligible) must be explicitly declined so
// CombatApplyAttackerDamage actually lands and kills it, firing Imperial Spy's On Kill trigger.
// Notes carried over from the original definition:
//   - Imperial Spy
//   - awake, can attack
//   - Wandering Glaivier (1 Life) - kill target
//   - ends turn 1 (first-player attack lock)
//   - declare attack with Imperial Spy
//   - target Wandering Glaivier
//   - decline Retaliate so CombatApplyAttackerDamage actually lands the kill
gaScenario('imperial-spy-onkill-prepare')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myField', 'Imperial Spy', player: 2)
    ->patch('myField-1', ['Status' => 2], player: 2)
    ->seed('myField', 'Wandering Glaivier')
    ->endTurn()
    ->action(2, 10002, 'myField-1!FSM!')->answer(2, 'theirField-1')->answer(1, '-')
    ->tested('Imperial Spy')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Hand-8', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'p6120p3f5d', 'Imperial Spy\'s attack kills the target')
    ->expectProperty('p1Graveyard-0', 'Status', '2')
    ->expectDamage('p1Graveyard-0', 2)
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '["DAMAGED_SINCE_LAST_TURN"]')
    ->expectProperty('p2Field-0', 'Counters', '{"preparation":1}', 'On Kill puts a preparation counter on the champion')
    ->expectProperty('p2Field-1', 'Status', '1')
    ->expectProperty('p2Field-1', 'TurnEffects', '[]')
    ->expectProperty('p2Hand-7', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-8', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-9', 'CardID', 'n8wyfG9hbY')
    ->expectZoneCount('myHand', 9, 1)
    ->expectZoneCount('myField', 1, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('theirHand', 10, 1)
    ->register();

// --- Iridescent Resurgence: return a crux non-champion non-regalia card from GY to memory ---
// Iridescent Resurgence (fK1IQGsUeh, CRUX) needs the CRUX element, which the base Spirit of
// Wind starting champion doesn't have -- the field-0 champion is CardID-patched directly to
// Lorraine, Spirit Ruler (n2TKqNaODR, CRUX) to unlock it (same technique used throughout this
// file for advanced-element cards, bypassing a real level-up sequence). Ethereal Slime
// (n06zlhihka, CRUX ALLY) is seeded into the graveyard as the target. cardActivatedAbilities
// ["fK1IQGsUeh:0"] (GeneratedMacroCode.php) offers only CRUX, non-champion, non-regalia
// graveyard/banishment cards as MZCHOOSE targets; its handler moves the chosen card to myMemory.
gaScenario('iridescent-resurgence-return-to-memory')
    ->deck(GA_LORRAINE_PANTHEON_DECK)
    ->champion(cardName: 'Lorraine, Spirit Ruler')
    ->seed('myGraveyard', 'Ethereal Slime')
    ->seed('myHand', 'Iridescent Resurgence')
    ->castSpell('Iridescent Resurgence', pay: 2, window: 'both', target: 'myGraveyard-0')
    ->expectDqEmpty()
    ->expectProperty('p1Memory-2', 'CardID', 'n06zlhihka', 'The targeted graveyard card moves to memory.')
    ->expectZoneCount('myGraveyard', 1, 1)
    ->register();

// --- Incapacitate: Negate target action card activation ---
// Incapacitate's activated ability (GeneratedMacroCode.php cardActivatedAbilities
// ["szene5o32m:0"]) calls QueueNegateActivation($player, ["types" => ["ACTION"]], "default",
// -1) -- negate target ACTION card activation, no pay-to-save (payAmount -1 short-circuits
// straight to NegateCardActivation in OpportunityLogic.php). Player 1 activates Escharotomy
// (CIU4gT14EE, reserve 1) as an ordinary main-phase ACTION; instead of passing the resulting
// opportunity window, player 2 responds with Incapacitate (itself Speed=true / Fast) targeting
// Escharotomy's still-pending effect-stack entry. Incapacitate resolves first (LIFO) and is
// itself moved off the stack to player 2's graveyard by OnCardActivated's own MZMove before
// cardActivatedAbilities runs (GameLogic.php ~5335), so GetEffectStackActivationTargets sees
// only Escharotomy as a legal ACTION target -- Escharotomy is negated straight to player 1's
// graveyard without ever resolving (its own "target player can't recover" modal never fires).
// Notes carried over from the original definition:
//   - Escharotomy, the target activation
//   - Incapacitate, the negator
//   - play Escharotomy
//   - pay 1 reserve
//   - player 1 passes their own opportunity
//   - Player 2's pending decision here is an MZMAYCHOOSE "Take_a_fast_action?" offering
//   - myHand-7 (Incapacitate) as the only eligible response -- answered by selecting that
//   - mzID directly with mode 100 (NOT the mode 10002 "!FSM!" main-phase-play variant, which
//   - is for the wrong decision entirely and leaves this MZMAYCHOOSE unresolved/loops).
//   - player 2 takes a fast action instead of passing: Incapacitate
//   - pay reserve 1/4
//   - pay reserve 2/4
//   - pay reserve 3/4
//   - pay reserve 4/4
//   - Incapacitate resolves immediately (it was moved off the stack to player 2's graveyard by
//   - OnCardActivated's own MZMove BEFORE cardActivatedAbilities ran), leaving only
//   - Escharotomy's still-pending entry as the sole legal ACTION target for
//   - "Choose_activation_to_negate".
//   - choose Escharotomy's activation to negate
gaScenario('incapacitate-negate-action-activation')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->seed('myHand', 'Escharotomy')
    ->seed('myHand', 'Incapacitate', player: 2)
    ->play('Escharotomy')->pay(1)->pass(1)->answer(2, 'myHand-7')->pay(4, 2)->answer(2, 'EffectStack-0')
    ->tested('Incapacitate')
    ->expectDqEmpty()
    ->expectProperty('p1Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-2', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p1Hand-4', 'CardID', 'px60u5n1do')
    ->expectProperty('p1Hand-5', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p1Graveyard-0', 'CardID', 'CIU4gT14EE', 'Incapacitate\'s activated ability (cardActivatedAbilities["szene5o32m:0"]) calls QueueNegateActivation targeting ACTION-type effect-stack entries; Escharotomy was still pending (not yet resolved) when Incapacitate was chosen instead of passing, so it lands in player 1\'s graveyard via NegateCardActivation\'s own MZMove -- never having reached resolution (its \'target player can\'t recover\' modal never fired, and no CANT_RECOVER global effect exists in this final state)')
    ->expectProperty('p1Graveyard-0', 'Counters', '[]')
    ->expectProperty('p1Graveyard-0', 'TurnEffects', '[]')
    ->expectProperty('p1Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Field-0', 'TurnEffects', '["vz4kc558yx-ACTION_ACTIVATED"]')
    ->expectProperty('p2Hand-0', 'CardID', 'n8wyfG9hbY')
    ->expectProperty('p2Hand-1', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Hand-2', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Graveyard-0', 'CardID', 'szene5o32m')
    ->expectProperty('p2Memory-0', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-1', 'CardID', 'px60u5n1do')
    ->expectProperty('p2Memory-2', 'CardID', 'em6eEh9q8y')
    ->expectProperty('p2Memory-3', 'CardID', 'px60u5n1do')
    ->expectZoneCount('myHand', 6, 1)
    ->expectZoneCount('myGraveyard', 1, 1)
    ->expectZoneCount('myMemory', 1, 1)
    ->expectZoneCount('theirHand', 3, 1)
    ->expectZoneCount('theirGraveyard', 1, 1)
    ->expectZoneCount('theirMemory', 4, 1)
    ->register();

// Incandescent Reliquary: At the beginning of your recollection phase, if you have the least
// influence, draw a card.
// Notes carried over from the original definition:
//   - Seeded directly onto the field (GameLogic.php's RecollectionPhase() checks CardID on
//   - the turn player's field via a plain switch, not a materialized Enter macro).
//   - Reach player 1's own turn-2 recollection phase: P1 ends turn 1, P2 ends their turn.
//   - Decline the material-phase champion level-up offer to reach the steady state.
gaScenario('incandescent-reliquary-recollection-least-influence-draw')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
DECK
    )
    ->seed('myField', 'Incandescent Reliquary')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->tested('Incandescent Reliquary')
    ->expectProperty('myField-1', 'TurnEffects', '["wsycqp2l90_DRAW_ANIM"]', 'Incandescent Reliquary\'s least-influence recollection draw fires (marked by its draw-anim turn effect)', step: 2)
    ->register();

// --- Invigorated Slash: +2 POWER as long as your champion has leveled up this turn ---
// Invigorated Slash (W1g0hNzXAC, NORM WARRIOR/SWORD attack, reserve 2, power 2): "As long as your
// champion has leveled up this turn, Invigorated Slash gets +2 POWER." The champion is patched to
// Mordred, Flawless Blade (level 2, lineage without Burnished Avenger so its inherited discount is
// not involved) and performs a REAL level-up into Mordred, Fated Luminary (3 memory cards) in the
// turn-3 materialize phase; Invigorated Slash is then played (2 reserve) and hits player 2's
// champion for 2 + 2 = 4.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - level-up memory cost 1/3
//   - level-up memory cost 2/3
//   - level-up memory cost 3/3
//   - Invigorated Slash -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - real level-up into Mordred, Fated Luminary
//   - activate Invigorated Slash
//   - attack player 2's champion
gaScenario('invigorated-slash-plus-two-power-after-champion-leveled-up')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Spirit of Water'])
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myMemory', 'Fairy Whispers')
    ->seed('myHand', 'Invigorated Slash')
    ->endTurn()
    ->endTurn(2)->target('myMaterial-2')
    ->castSpell('Invigorated Slash', pay: 2, target: 'theirField-0')
    ->expectProperty('p1Field-0', 'CardID', 'KqBosnU7pU', 'The champion really leveled up this turn (Flawless Blade -> Fated Luminary)')
    ->expectDamage('p2Field-0', 4, 'Invigorated Slash dealt 2 + 2 = 4 damage')
    ->expectZoneCount('myMemory', 2, 1, 'Exactly 2 reserve payments (printed cost 2; the three level-up memory cards were banished)')
    ->expectDqEmpty()
    ->register();

// --- Invigorated Slash: no bonus when the champion has NOT leveled up this turn ---
// Invigorated Slash (W1g0hNzXAC): the +2 POWER needs a level-up THIS turn. Same lineage as
// invigorated-slash-plus-two-power-after-champion-leveled-up, but the materialize offer is declined:
// the attack deals only its printed 2.
// Notes carried over from the original definition:
//   - Mordred, Flawless Blade (level 2)
//   - Invigorated Slash -> p1 myHand-7
//   - end player 1 turn 1
//   - end player 2 turn 2 -> player 1 turn 3 materialize phase
//   - decline the level-up offer
//   - activate Invigorated Slash
//   - attack player 2's champion
gaScenario('invigorated-slash-no-bonus-without-levelup')
    ->deck($mordredDeck)
    ->champion(cardName: 'Mordred, Flawless Blade', lineage: ['Spirit of Water'])
    ->seed('myHand', 'Invigorated Slash')
    ->endTurn()
    ->endTurn(2)->pass(1)
    ->castSpell('Invigorated Slash', pay: 2, target: 'theirField-0')
    ->expectProperty('p1Field-0', 'CardID', 'WI2owxIw0z', 'The champion did not level up this turn')
    ->expectDamage('p2Field-0', 2, 'Invigorated Slash dealt only its printed 2 damage')
    ->expectDqEmpty()
    ->register();

// --- Immaterial Dissolution: declining a further token still destroys the tokens already chosen (dontSkipOnPass regression) ---
// Immaterial Dissolution (55d9w9uuvq): "Destroy up to three target non-regalia token objects with total reserve cost 4 or less." Choosing fewer than three (declining the 'Destroy another token?' prompt) must still destroy the tokens already chosen.
// Regression: ImmaterialDissolveSelect's loop re-queue (Custom/GameLogic.php) omitted dontSkipOnPass, so ExecuteStaticMethods() skipped the handler when the player answered PASS and its PASS branch (destroy all accumulated targets) never ran: a token was chosen and then nothing was destroyed. (The first prompt, queued by the generated ability, needs no flag: declining it with nothing chosen destroys nothing either way.)
// Two Herb tokens (Blightroot, Fraysia, reserve cost 1 each) are seeded on the field; the first is chosen, the second declined with PASS.
// Notes carried over from the original definition:
//   - Blightroot (token) -> myField-1
//   - Fraysia (token) -> myField-2
//   - reserve 1/4
//   - reserve 2/4
//   - reserve 3/4
//   - reserve 4/4
//   - pass the fast-action window so the spell resolves
//   - choose Blightroot as the first token
//   - decline choosing another token
gaScenario('immaterial-dissolution-decline-further-token-still-destroys')
    ->deckTemplate('clarent-windslice', 'Dungeon Guide')
    ->seed('myField', 'Blightroot')
    ->seed('myField', 'Fraysia')
    ->seed('myHand', 'Immaterial Dissolution')
    ->play('Immaterial Dissolution')->pay(4)->pass(1)->target('myField-1')->pass(1)
    ->expectZoneCount('myField', 2, 1, 'The chosen token (Blightroot) was destroyed even though the second offer was declined: champion + the unchosen Fraysia remain')
    ->expectProperty('p1Field-1', 'CardID', 'soporhlq2k', 'The remaining token is the unchosen Fraysia')
    ->expectDqEmpty()
    ->register();

// --- Ignited Stab (GRkBQ1Uvir): Prepare 1; [Class Bonus] On Attack: if prepared, +2 POWER ---
gaScenario('ignited-stab-prepare-1-paid-plus-2-power')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'LMyKyVC2O9'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myHand', 'Ignited Stab', player: 2)
    ->endTurn()
    ->play('Ignited Stab', 2)->pay(1, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

gaScenario('ignited-stab-prepare-1-declined-base-power')
    ->deck($gaPrepareDeck)
    ->patch('myField-0', ['CardID' => 'he6kd7hocc', 'Subcards' => ['gt7lh9v221', 'bjlwabipl6', 'pNiyaGlIe7', 'LMyKyVC2O9'], 'Counters' => ['preparation' => 2]], player: 2)
    ->seed('myHand', 'Ignited Stab', player: 2)
    ->endTurn()
    ->play('Ignited Stab', 2)->pay(1, 2)->answer(2, 'NO')->answer(2, 'theirField-0')
    ->register();

// Ignited Stab is FIRE and [Class Bonus] needs an ASSASSIN champion. Player 2 keeps the deck's
// default Spirit of Fire champion (class SPIRIT, FIRE) -- NOT an Assassin -- with 1 preparation
// counter: Prepare 1 is still payable (it is not class-gated), but the +2 POWER rider is.
gaScenario('ignited-stab-prepare-1-paid-non-assassin-no-class-bonus')
    ->deckTemplate('lorraine-windslice', 'Dungeon Guide')
    ->patch('myField-0', ['Counters' => ['preparation' => 1]], player: 2)
    ->seed('myHand', 'Ignited Stab', player: 2)
    ->endTurn()
    ->play('Ignited Stab', 2)->pay(1, 2)->answer(2, 'YES')->answer(2, 'theirField-0')
    ->register();

// top six: Dungeon Guide, Charge the Soul (AE), Fluffy Shopkeep, Prudent Nock (AE), Dungeon Guide, Fluffy Shopkeep
// Notes carried over from the original definition:
//   - banish Charge the Soul (2nd of the six)
//   - banish Prudent Nock (4th of the six; now 3rd after Charge the Soul left the temp zone)
//   - the other four to the bottom, chosen order
gaScenario('ignition-draw-look-six-banish-two-aethercharge-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw')
    ->deckTop(['Dungeon Guide', 'Charge the Soul', 'Fluffy Shopkeep', 'Prudent Nock', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->play('Ignition Draw')->pay(3)->target('myTempZone-1')->target('myTempZone-2')
    ->answer(1, 'Top=;Bottom=px60u5n1do,px60u5n1do,em6eEh9q8y,em6eEh9q8y')
    ->register();

// the ONLY Aethercharge is the sixth card from the top: it can only be offered if all six were looked at
// Notes carried over from the original definition:
//   - banish Charge the Soul (6th of the six)
gaScenario('ignition-draw-look-six-deep-aethercharge-offered')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw')
    ->deckTop(['Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Fluffy Shopkeep', 'Dungeon Guide', 'Charge the Soul'])
    ->castSpell('Ignition Draw', pay: 3, target: 'myTempZone-5')
    ->answer(1, 'Top=;Bottom=em6eEh9q8y,px60u5n1do,em6eEh9q8y,px60u5n1do,em6eEh9q8y')
    ->register();

// Notes carried over from the original definition:
//   - "up to two": banish none
//   - all six to the bottom, chosen order
gaScenario('ignition-draw-look-six-decline-bottoms-all-six')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw')
    ->deckTop(['Dungeon Guide', 'Charge the Soul', 'Fluffy Shopkeep', 'Prudent Nock', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->castSpell('Ignition Draw', pay: 3, window: 'me')
    ->answer(1, 'Top=;Bottom=0w5bp5nuae,px60u5n1do,em6eEh9q8y,ra9950o14t,px60u5n1do,em6eEh9q8y')
    ->register();

// Notes carried over from the original definition:
//   - banish Charge the Soul
//   - decline the second banish: Prudent Nock stays among the cards put on the bottom
gaScenario('ignition-draw-look-six-banish-one-then-decline-second')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw')
    ->deckTop(['Dungeon Guide', 'Charge the Soul', 'Fluffy Shopkeep', 'Prudent Nock', 'Dungeon Guide', 'Fluffy Shopkeep'])
    ->play('Ignition Draw')->pay(3)->target('myTempZone-1')->pass(1)
    ->answer(1, 'Top=;Bottom=em6eEh9q8y,px60u5n1do,0w5bp5nuae,em6eEh9q8y,px60u5n1do')
    ->register();

// three Aethercharges (slots 0, 2, 4): "up to two" -> the third is never offered and goes to the bottom
// Notes carried over from the original definition:
//   - banish Charge the Soul
//   - banish Prudent Nock (now the 2nd card: Dungeon Guide, Prudent Nock, ...)
gaScenario('ignition-draw-look-six-three-aethercharge-banish-capped-at-two')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw')
    ->deckTop(['Charge the Soul', 'Dungeon Guide', 'Prudent Nock', 'Fluffy Shopkeep', 'Aetheric Calibration', 'Dungeon Guide'])
    ->play('Ignition Draw')->pay(3)->target('myTempZone-0')->target('myTempZone-1')
    ->answer(1, 'Top=;Bottom=7l9th23niu,em6eEh9q8y,px60u5n1do,em6eEh9q8y')
    ->register();

// PLAYER 2 seating (perspective check): the stack sits under player 2's turn-start draw (a filler Dungeon Guide)
gaScenario('ignition-draw-player-two-look-six-banish-two-aethercharge-rest-bottom')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignition Draw', player: 2)
    ->deckTop(['Dungeon Guide', 'Dungeon Guide', 'Charge the Soul', 'Fluffy Shopkeep', 'Prudent Nock', 'Dungeon Guide', 'Fluffy Shopkeep'], player: 2)
    ->endTurn()->pass(2)->pass(2)
    ->play('Ignition Draw', 2)->pay(3, 2)->answer(2, 'myTempZone-1')->answer(2, 'myTempZone-2')
    ->answer(2, 'Top=;Bottom=px60u5n1do,em6eEh9q8y,em6eEh9q8y,px60u5n1do')
    ->register();

gaScenario('icebound-slam-on-attack-mills-five-water-gets-plus-five-power')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myHand', 'Icebound Slam', player: 2)
    ->deckTop(['Dungeon Guide', 'Spirit of Water', 'Spirit of Water', 'Spirit of Water', 'Spirit of Water', 'Spirit of Water', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->play('Icebound Slam', 2)->pay(7, 2)->answer(2, 'theirField-0')
    ->register();

gaScenario('icebound-slam-on-attack-mills-five-four-water-no-bonus')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->champion(lineage: ['Spirit of Water'], player: 2)
    ->seed('myHand', 'Icebound Slam', player: 2)
    ->deckTop(['Dungeon Guide', 'Spirit of Water', 'Spirit of Water', 'Spirit of Water', 'Spirit of Water', 'Dungeon Guide', 'Charge the Soul'], player: 2)
    ->endTurn()
    ->play('Icebound Slam', 2)->pay(7, 2)->answer(2, 'theirField-0')
    ->register();

gaAbilitySandbox('intwined-bangle-ability-1-pays-its-printed-cost', 'Intwined Bangle', classes: 'TAMER', cardPatch: ['Status' => 2])
    ->action(1, 10001, 'myField-1!CustomInput!Activate:0')->pay(3)->target('myField-6')->pass(1)
    ->tested('Intwined Bangle')
    ->register();

// --- Inquisitive Magician (cbxpjya4u1): "On Enter: You may reveal two Mage cards from your hand and/or memory. If you do, put an enlighten counter on your champion." Picking the first card
// was a fatal (array_filter on an undefined $mageCards). Two Convergent Beams (Mage) are in hand. ---
gaScenario('inquisitive-magician-enter-reveals-two-mage-cards-for-an-enlighten-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Wind
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Inquisitive Magician')
    ->seed('myHand', 'Convergent Beam')
    ->seed('myHand', 'Convergent Beam')
    ->play('Inquisitive Magician')->pay(3)->pass(1)->target('myHand-4')->target('myHand-5')
    ->tested('Inquisitive Magician', 'Convergent Beam')
    ->register();

// Ignite the Soul (rXHo9fLU32): "Deal 1 damage to target unit." The target list was the opponent's WHOLE field (items included, own units excluded) and the damage came from an undefined source.
// Here the opponent controls a Dungeon Guide and an item (Forest Cake); the item is not a legal target, the ally is.
// Notes carried over from the original definition:
//   - Forest Cake (item) -> theirField-1
//   - Dungeon Guide -> theirField-2
gaScenario('ignite-the-soul-targets-units-only-and-deals-one-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Ignite the Soul')
    ->seed('myField', 'Forest Cake', player: 2)
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->castSpell('Ignite the Soul', pay: 1)
    ->rejects(1, 'theirField-1', 'An item is not a unit: not a legal target')->target('theirField-2')
    ->register();

// Incinerator Felindroid (Vl03t5rMSA): "[Class Bonus] On Enter: You may banish two fire element cards from your graveyard. When you do, as a Spell, deal 3 damage to target ally." The second step read
// $fireCards (undefined): the two cards could never be chosen. Cleric champion, two fire cards in the graveyard, a Dungeon Guide (3 life) on the other side.
// Notes carried over from the original definition:
//   - Slime Eruption (fire)
//   - Broken Promises (fire)
//   - Dungeon Guide -> theirField-1
gaScenario('incinerator-felindroid-enter-banishes-two-fire-cards-and-deals-three-damage')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myHand', 'Incinerator Felindroid')
    ->champion(classes: 'CLERIC')
    ->seed('myGraveyard', 'Slime Eruption')
    ->seed('myGraveyard', 'Broken Promises')
    ->seed('myField', 'Dungeon Guide', player: 2)
    ->play('Incinerator Felindroid')->pay(3)->answer(1, 'A')->answer(1, 'myGraveyard-0&myGraveyard-1')
    ->target('theirField-1')
    ->expectZoneCount('myBanish', 2, 1, 'The two fire cards were banished from the graveyard')
    ->expectZoneCount('myGraveyard', 0, 1, 'Nothing left in the graveyard')
    ->expectZoneCount('theirGraveyard', 1, 1, 'The Dungeon Guide (3 life) took 3 damage and died')
    ->expectDqEmpty()
    ->register();

// Induction Strike (WvWRLuPmDG): "(1), Banish this card from your graveyard: Put a static counter on target arcane element object you control and each object linked to it." The ability never paid the (1)
// nor banished the card. A Storm Slime (arcane ally) is the target.
// Notes carried over from the original definition:
//   - Induction Strike -> myGraveyard-0
//   - Storm Slime (ARCANE) -> myField-1
gaScenario('induction-strike-graveyard-ability-pays-one-and-adds-a-static-counter')
    ->deck(<<<'DECK'
# Material
1 Spirit of Fire
# Main
10 Dungeon Guide
10 Fluffy Shopkeep

DECK
    )
    ->seed('myGraveyard', 'Induction Strike')
    ->seed('myField', 'Storm Slime')
    ->action(1, 10002, 'myGraveyard-0!FSM!')->pay(1)->target('myField-1')
    ->tested('Induction Strike', 'Storm Slime')
    ->register();
