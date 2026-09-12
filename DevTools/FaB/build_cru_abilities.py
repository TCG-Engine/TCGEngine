"""Reproducible CRU CardEditor bodies. Fail closed on any unhandled identity."""
import json, ast, re
from pathlib import Path
HERE=Path(__file__).parent
# Reuse the already-tested await templates, without executing ARC's builder.
source=(HERE/'build_arc_abilities.py').read_text()
for node in ast.parse(source).body:
    if isinstance(node,ast.FunctionDef):exec(ast.get_source_segment(source,node))
cards=json.loads((HERE/'cru_catalog.json').read_text())
existing={}
for name in ['wtr','arc','fai','professor']:
    existing.update({c['cardId']:c for c in json.loads((HERE/(name+'_abilities.json')).read_text())})
support_ids=['scabskin_leathers','barkbone_strapping','crazy_brew_blue','bone_head_barrier_yellow']
cards += [{'id':id,'pitch':0} for id in support_ids]
snapshot=[];pending=[]
def dice():
    return """$roller = $player;
$roll = EngineRandomInt(1, 6);
$seats = FaBLiveSeats();
foreach ($seats as $seat) {
 $gloves = FaBCRUEquipment($seat, 'gamblers_gloves');
 foreach ($gloves as $glove) {
  $gloveUID = intval(FaBIdentityFromMZ($glove)['object']->UniqueID);
  $tip = 'Player_' . $roller . '_rolled_' . $roll;
  $reroll = await $seat.Modal(1, 1, "Keep_roll&Destroy_gloves_and_reroll", $tip);
  if ($reroll === '1') { FaBMoveUID($gloveUID, 'Graveyard', $seat); $roll = EngineRandomInt(1, 6); }
 }
}
$player = $roller;"""
def pay_one(target='$attacker'):
    return f"""$paid = false;
if (FaBAvailablePitch({target}) >= 1) {{
 $choice = await {target}.Modal(1, 1, "Decline&Pay_one_resource", "Pay_to_avoid_trap");
 if ($choice === '1') {{
  while (intval(GetResources({target})) < 1) {{
   $refs = FaBARCPitchChoices({target});
   $pitched = await {target}.MZChoose($refs, "Pitch_to_pay");
   FaBARCPitchForEffect({target}, $pitched);
  }}
  AddResources({target}, intval(GetResources({target})) - 1);
  $paid = true;
 }}
}}"""
for c in cards:
 id=c['id'];base=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id
 v=4-int(c['pitch'] or 0);abilities=[];handled=False
 def add(macro,code):
  if 'WTR_POWER:' in code and macro in ['AttackDeclared','PrepareCard']:
   code=code.replace('FaBTagUID(', 'FaBCRUSelfTagUID(').replace('FaBWTRTag($o,', 'FaBCRUSelfTag($o,')
  # The await compiler supports indexed for/while, but not foreach suspension.
  loop=0
  pattern=r'foreach \(([^\n]+) as (\$[A-Za-z_][A-Za-z0-9_]*)\) \{'
  while re.search(pattern,code):
   loop+=1
   m=re.search(pattern,code);seq=f'$cruSeq{loop}';idx=f'$cruIdx{loop}'
   code=code[:m.start()]+f'{seq} = {m[1]};\nfor ({idx} = 0; {idx} < count({seq}); ++{idx}) {{\n {m[2]} = {seq}[{idx}];'+code[m.end():]
  # Expand block braces so each await compiler branch has an explicit boundary.
  formatted=[];quote=None;escaped=False;depth=0
  for ch in code:
   if quote:
    formatted.append(ch)
    if escaped:escaped=False
    elif ch=='\\':escaped=True
    elif ch==quote:quote=None
   elif ch in [chr(34),chr(39)]:quote=ch;formatted.append(ch)
   elif ch=='(':depth+=1;formatted.append(ch)
   elif ch==')':depth-=1;formatted.append(ch)
   elif ch==';' and depth==0:formatted.append(';\n')
   elif ch=='{':formatted.append('{\n')
   elif ch=='}':formatted.append('\n}\n')
   else:formatted.append(ch)
  code=''.join(formatted)
  abilities.append({'macroName':macro,'abilityCode':code,'isImplemented':True})
 if base in ['brutal_assault','ira_crimson_haze']:
  handled=True
 if base in ['courage_of_bladehold','crater_fist']:
  handled=True;add('ResolveAbility', "FaBCRUAdd($player, '"+('COURAGE' if base=='courage_of_bladehold' else 'CRATER')+"');")
 if base=='promise_of_plenty' and id not in existing:
  handled=True;add('Hit','FaBFaiPromise();')
 if id in support_ids:
  handled=True
  code=dice()+'\nFaBCRULegacyRoll($player, '+repr(id)+', $roll);'
  add('ResolveCard' if id=='bone_head_barrier_yellow' else 'ResolveAbility',code)
 if id in existing and id not in support_ids:
  snapshot.append(existing[id]);continue
 # Continuous rules and standard printed keywords are evaluated by CRUCards.
 if base in ['absorption_dome','barraging_big_horn','beast_within','benji_the_piercing_wind','crane_dance','data_doll_mkii','edge_of_autumn','emerging_dominance','flying_kick','gamblers_gloves','kassai_cintari_sellsword','mandible_claw','meat_and_greet','meganetic_shockwave','overblast','predatory_assault','reaping_blade','riled_up','sledge_of_anvilheim','springboard_somersault','swing_fist_think_later','talishar_the_lost_prince','towering_titan','zephyr_needle','cintari_saber']:
  handled=True
 if base=='absorption_dome':add('ResolveCard', "FaBARCEnterItem($player, FaBIdentityFromMZ($mzID)['object']);")
 if base in ['barraging_big_horn','swing_fist_think_later']:
  # Paid by the common Brute additional-cost path, including discard triggers.
  pass
 if base in ['blessing_of_serenity','feign_death','mauvrion_skies','increase_the_tension','poison_the_tips','snag','rattle_bones','bloodsheath_skeleta','perch_grapplers','viziertronic_model_i']:
  handled=True
  effects={'blessing_of_serenity':('SERENITY',v),'feign_death':('FEIGN',1),'mauvrion_skies':('MAUVRION',v),'increase_the_tension':('TENSION',v),'poison_the_tips':('POISON',1),'perch_grapplers':('PERCH',1),'viziertronic_model_i':('VIZIER',1)}
  if base in effects:
   name,n=effects[base];add('ResolveAbility' if base in ['perch_grapplers','viziertronic_model_i'] else 'ResolveCard',f"FaBCRUAdd($player, '{name}', {n});")
  if base=='bloodsheath_skeleta':add('ResolveAbility',"FaBCRUAdd($player, 'SKELETA_AA');\nFaBCRUAdd($player, 'SKELETA_NAA');")
  if base=='snag':add('ResolveCard',"FaBCRUApplySnag();")
  if base=='rattle_bones':add('ResolveCard',"""$targets = FaBARCSelect($player, 'Graveyard', 'Runeblade', 'AA');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Banish_attack_to_play_this_turn");
 $o = FaBMoveChoice($player, $chosen, 'Graveyard', 'Banish');
 if ($o !== null) { $o->PlayableFromBanish = 1; }
}""")
 if base in ['copper','kavdaen_trader_of_skins','skullhorn','red_liner','plasma_barrel_shot','plasma_purifier']:
  handled=True
  if base=='copper':add('ResolveAbility','DoDrawCard($player, 1);')
  if base=='kavdaen_trader_of_skins':add('ResolveAbility','FaBCRUKavdaen();')
  if base=='skullhorn':add('ResolveAbility','DoDrawCard($player, 1);\nFaBDiscardRandom($player, 1);')
  if base=='red_liner':add('ResolveAbility',"""if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = FaBARCSelect($player, 'Hand', '', 'Arrow');
 if ($targets !== '') {
  $chosen = await $player.MZChoose($targets, "Load_arrow_face_up");
  FaBARCLoadArsenal($player, $chosen, true);
 }
}""")
  if base=='plasma_barrel_shot':add('ResolveAbility','FaBARCCharge($mzID);')
  if base=='plasma_purifier':add('ResolveAbility',"""if (intval(DecisionQueueController::GetVariable('arcAbilityIndex')) === 0) {
 FaBARCCharge($mzID);
} else {
 $targets = implode('&', FaBChoiceRefs($player, 'Weapons', ['type'=>'Pistol']));
 if ($targets !== '') {
  $chosen = await $player.MZChoose($targets, "Pistol_gains_one_power_this_turn");
  $o = FaBIdentityFromMZ($chosen);
  if ($o !== null) { FaBWTRTag($o['object'], 'WTR_POWER:1'); }
 }
}""")
 if base in ['hit_and_run','push_forward','dauntless','spoils_of_war']:
  handled=True
  if base=='hit_and_run':code=f"FaBWTRAddEffect($player, 'NEXT_WEAPON', 0, ['goAgain'=>true]);\nif (FaBCRUCount($player, 'WEAPONS') > 0) {{ FaBWTRAddEffect($player, 'NEXT_ATTACK', {v}); }}"
  else:
   code=f"FaBWTRAddEffect($player, 'NEXT_WEAPON', {2 if base=='spoils_of_war' else v}, ['goAgain'=>{'true' if base=='spoils_of_war' else 'false'}]);"
   if base=='spoils_of_war':code+="\nFaBCRUAdd($player, 'SPOILS');"
   if base=='push_forward':code+="\nif (FaBCRUCount($player, 'WEAPONS') > 0) { FaBWTRAddEffect($player, 'NEXT_ATTACK', 0, ['dominate'=>true]); }"
   if base=='dauntless':code+="\nFaBCRUAdd($player, 'DAUNTLESS_PENDING');"
  add('ResolveCard',code)
 if base in ['combustible_courier','high_speed_impact']:
  handled=True
  add('PrepareCard',"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
if (count(FaBChoiceRefs($player, 'Deck')) > 0) {
 $boost = await $player.Modal(1, 1, "Boost&Do_not_boost", "Banish_top_card_to_boost");
 if ($boost === '0') { FaBARCBoost($player, $uid); }
}
FaBFinishPreparedCard($uid);""")
  add('Hit',"FaBCRUAdd($player, '"+('COURIER' if base=='combustible_courier' else 'IMPACT')+"', "+('3' if base=='combustible_courier' else '1')+");")
 if base in ['foreboding_bolt','rousing_aether','snapback','aether_conduit','chain_lightning']:
  handled=True
  if base not in ['aether_conduit','chain_lightning']:add('PrepareCard',arcane_prepare(False))
  if base=='aether_conduit':
   code="""$targets = FaBARCHeroTargets($player, false);
$chosen = await $player.MZChoose($targets, "Choose_arcane_damage_target");
$target = FaBARCTargetSeat($player, $chosen, false);
$damage = 2;
$dealt = 0;
"""+damage_body()
  elif base=='chain_lightning':
   code="""FaBWTRAddEffect($player, 'ARC_NEXT_WIZARD_INSTANT', 1);
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
if (FaBCRUWizardPlayed($player, 'chain_lightning_yellow')) {
 $victims = FaBOpponents($player);
 foreach ($victims as $target) {
  $damage = FaBARCArcaneAmount($player, $uid, 3, $target);
"""+damage_body()+"\n }\n}"
  else:
   code=f"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$target = intval(FaBARCCard($uid, 'target'));
$damage = FaBARCArcaneAmount($player, $uid, {v+1 if base=='rousing_aether' else v}, $target);
$dealt = 0;
"""+damage_body()
   if base=='foreboding_bolt':code+='\n'+opt(1)
   if base=='rousing_aether':code+="\nFaBWTRAddEffect($player, 'ARC_NEXT_ARCANE', 1);"
  add('ResolveAbility' if base=='aether_conduit' else 'ResolveCard','$caster = $player;\n'+code.replace('$player','$caster'))
 if base in ['cindering_foresight','gaze_the_ages','teklovossens_workshop','sutcliffes_research_notes']:
  handled=True
  if base=='cindering_foresight':code="FaBWTRAddEffect($player, 'ARC_NEXT_ARCANE', 1);\n"+opt(v)
  if base=='gaze_the_ages':code="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);\n"+opt(2)+"\nif (FaBCRUWizardPlayed($player, 'gaze_the_ages_blue')) { FaBMoveUID($uid, 'Hand', $player); }"
  if base=='teklovossens_workshop':code=opt("FaBARCEffect($player, 'ARC_BOOSTED')")+"""
$ref = FaBChoiceRefs($player, 'Deck')[0] ?? '';
FaBRevealChoices($player, $ref);
$o = FaBIdentityFromMZ($ref);
if ($o !== null && FaBHasType($o['object'], 'Mechanologist') && FaBHasType($o['object'], 'Item') && intval(CardCost($o['object']->CardID)) <= 2) { FaBARCPutItem($player, $ref, 'Deck'); }
"""
  if base=='sutcliffes_research_notes':code="""$uids = FaBARCStageTop($player, 3);
$refs = implode('&', FaBChoiceRefs($player, 'Temp'));
FaBRevealChoices($player, $refs);
$n = count(explode('&', FaBARCSelect($player, 'Temp', 'Runeblade', 'AA')));
if (FaBARCSelect($player, 'Temp', 'Runeblade', 'AA') !== '') { FaBARCCreateRunes($player, $n); }
if (count($uids) > 0) {
 $param = FaBARCOrderParam($uids, 'Top');
 $order = await $player.Rearrange($param);
 FaBARCFinishOrder($player, $uids, $order);
}"""
  add('ResolveCard',code)
 if base in ['soulbead_strike','torrent_of_tempo','bittering_thorns','find_center','whirling_mist_blossom','meat_and_greet','sleep_dart','remorseless','chokeslam','crush_the_weak','massacre','dread_triptych','runeblood_barrier','stamp_authority','zen_state','arknight_shard','gorganian_tome']:
  handled=True
  if base in ['soulbead_strike','torrent_of_tempo']:add('Hit',"FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'GO_AGAIN');")
  if base=='bittering_thorns':add('Hit',"FaBWTRAddEffect($player, 'NEXT_ATTACK', 1);")
  if base=='find_center':add('Hit',"if (FaBPreviousAttackBase() === 'crane_dance') { $o = FaBWTRCreateArena($player, 'zen_state'); FaBSetObjectCounter($o, 'BALANCE', 1); }")
  if base=='whirling_mist_blossom':add('Hit',"if (intval(FaBGetState()['consecutiveHits'] ?? 0) >= 2) { DoDrawCard($player, 2); }")
  if base in ['meat_and_greet','dread_triptych']:add('Hit','FaBARCCreateRunes($player, 1);')
  if base=='dread_triptych':add('AttackDeclared',"if (count(FaBARCPlayed($player, true)) > 0) { FaBARCCreateRunes($player, 1); }\nif (FaBCRUArcane($player) > 0) { FaBARCCreateRunes($player, 1); }")
  if base in ['sleep_dart','remorseless','chokeslam','crush_the_weak']:
   effectname={'sleep_dart':'NO_HERO_ABILITY','remorseless':'CRU_REMORSELESS','chokeslam':'CRU_CHOKESLAM','crush_the_weak':'CRU_CRUSH_WEAK'}[base]
   cond='FaBFaiHeroHit()'+(' && $amount >= 4' if base in ['chokeslam','crush_the_weak'] else '')
   add('Hit',f"if ({cond}) {{ $target = intval(FaBGetState()['defender']); FaBWTRAddEffect($target, '{effectname}', 1, ['expiresAfterTurnOf'=>$target], {'true' if base in ['chokeslam','crush_the_weak'] else 'false'}); }}")
  if base=='runeblood_barrier':add('ResolveCard','FaBARCCreateRunes($player, 4);')
  if base=='stamp_authority':add('ResolveCard',"if (count(FaBChoiceRefs($player, 'Pitch', ['minCost'=>3])) >= 2) { FaBWTRAddEffect($player, 'INTELLECT', 1); }")
  if base=='zen_state':add('ResolveCard',"FaBSetObjectCounter(FaBIdentityFromMZ($mzID)['object'], 'BALANCE', 1);")
  if base=='arknight_shard':add('CardPitched','FaBARCCreateRunes($player, 1);\nFaBTryCompletePayment();')
  if base=='gorganian_tome':add('ResolveCard',"$n = 0;\nforeach (FaBLiveSeats() as $seat) { $n += count(FaBChoiceRefs($seat, 'Graveyard', ['base'=>'gorganian_tome'])); }\nDoDrawCard($player, $n);")
 if base=='consuming_volition':
  handled=True;add('Hit',"""if (FaBFaiHeroHit() && in_array('CRU_DISCARD_HIT', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true)) {
 $defender = intval(FaBGetState()['defender']);
 $targets = implode('&', FaBChoiceRefs($defender, 'Hand'));
 if ($targets !== '') {
  $chosen = await $defender.MZChoose($targets, "Choose_a_card_to_discard");
  FaBDiscardChoice($defender, $chosen);
 }
}""")
 if base=='breeze_rider_boots':
  handled=True;add('Hit',"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$choice = await $player.Modal(1, 1, "Keep_boots&Destroy_boots_for_combo_go_again", "Breeze_Rider_Boots");
if ($choice === '1') { FaBMoveUID($uid, 'Graveyard', $player); FaBCRUAdd($player, 'BREEZE'); }""")
 if base=='viziertronic_model_i':add('CardPlayed',"""DoDrawCard($player, 1);
$targets = implode('&', FaBChoiceRefs($player, 'Hand'));
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Put_a_card_on_top_of_deck");
 FaBPlaceChosenOnDeck($player, $chosen, true);
}""")
 if base=='kayo_berserker_runt':
  handled=True;add('CardPlayed',"$attackUID = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);\n"+dice()+"\nFaBARCSetCard($attackUID, 'kayo', $roll);")
 if base=='argh_smash':
  handled=True;add('ResolveCard',dice()+"""
$targets = FaBCRUTargets($player, 'items');
$maximum = min(intdiv($roll, 2), count(explode('&', $targets)));
if ($targets !== '' && $maximum > 0) {
 $chosen = await $player.MZMultiChoose($targets, 0, $maximum, "Destroy_up_to_rolled_items");
 foreach (explode('&', $chosen) as $ref) { $f = FaBIdentityFromMZ($ref); if ($f !== null) { FaBMoveUID(intval($f['object']->UniqueID), 'Graveyard', $f['player']); } }
}""")
 if base=='metacarpus_node':
  handled=True;add('CardPlayed',"""$caster = $player;
$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
if (FaBAvailablePitch($caster) >= 1) {
 $choice = await $caster.Modal(1, 1, "Decline&Pay_one_for_extra_arcane_damage", "Metacarpus_Node");
 if ($choice === '1') {
  while (intval(GetResources($caster)) < 1) {
   $targets = FaBARCPitchChoices($caster);
   $chosen = await $caster.MZChoose($targets, "Pitch_for_Metacarpus_Node");
   FaBARCPitchForEffect($caster, $chosen);
  }
  AddResources($caster, intval(GetResources($caster)) - 1);
  FaBARCSetCard($uid, 'arcaneBonus', intval(FaBARCCard($uid, 'arcaneBonus')) + 1);
  foreach (FaBCRUEquipment($caster, 'metacarpus_node') as $ref) { FaBWTRTag(FaBIdentityFromMZ($ref)['object'], 'CRU_NODE_USED'); }
 }
}""")
 if base in ['tripwire_trap','pitfall_trap','rockslide_trap']:
  handled=True
  code="$caster = $player;\n$attacker = intval(FaBGetState()['attacker']);\n$attackUID = intval(FaBGetState()['attackUID']);\n"+pay_one()
  code+="\nif (!$paid) { "+("DoDamage($caster, '', $attacker, 2, 'PHYSICAL');" if base=='pitfall_trap' else "FaBTagUID($attackUID, 'WTR_POWER:-2');" if base=='rockslide_trap' else "FaBTagUID($attackUID, 'CRU_NO_HIT');")+" }"
  add('Defended',code)
 if base in ['lunging_press','out_for_blood','unified_decree','twinning_blade']:
  handled=True
  if base=='twinning_blade':code="""$targets = implode('&', FaBChoiceRefs($player, 'Weapons', ['type'=>'Sword']));
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Sword_may_attack_an_additional_time");
 $o = FaBIdentityFromMZ($chosen);
 if ($o !== null) { FaBWTRTag($o['object'], 'CRU_EXTRA_ATTACK'); }
}"""
  else:
   code=f"FaBTagUID(intval(FaBGetState()['attackUID']), 'CRU_REACTION_POWER:{1 if base=='lunging_press' else 3 if base=='unified_decree' else v}');"
   if base=='out_for_blood':code+="\nif (FaBWTRDefendedFromHand(FaBGetState())) { FaBWTRAddEffect($player, 'NEXT_ATTACK', 1); }"
   if base=='unified_decree':code+="""
if (FaBWTRDefendedFromHand(FaBGetState())) {
 $uids = FaBARCStageTop($player, 1);
 $targets = FaBARCSelect($player, 'Temp', '', 'AR');
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Banish_attack_reaction_to_play_this_chain");
  $o = FaBMoveChoice($player, $chosen, 'Temp', 'Banish');
  if ($o !== null) { $o->PlayableFromBanish = 1; FaBARCSetCard(intval($o->UniqueID), 'untilChainCloses', true); }
 }
 FaBARCFinishOrder($player, $uids, '');
}"""
  add('ResolveCard',code)
 if base=='reinforce_the_line':
  handled=True;add('ResolveCard',f"""$targets = FaBCRUTargets($player, 'defendingAA');
if ($targets !== '') {{
 $chosen = await $player.MZChoose($targets, "Defending_attack_action_gains_defense");
 $o = FaBIdentityFromMZ($chosen);
 if ($o !== null) {{ FaBWTRTag($o['object'], 'WTR_DEFENSE:{v+1}'); }}
}}""")
 if base=='aetherize':
  handled=True;add('PrepareCard',"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$targets = FaBCRUTargets($player, 'negate');
$chosen = await $player.MZChoose($targets, "Negate_target_instant_cost_one_or_less");
FaBARCSetCard($uid, 'negateUID', intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0));
FaBFinishPreparedCard($uid);""")
  add('ResolveCard',"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$f = FaBFindUID(intval(FaBARCCard($uid, 'negateUID')));
if ($f !== null && $f['zone'] === 'Stack' && FaBHasType($f['object'], 'Instant') && intval(CardCost($f['object']->CardID)) <= 1) { FaBMoveStackUID(intval($f['object']->UniqueID), 'Graveyard', intval($f['object']->Controller)); }""")
 if base=='cash_in':
  handled=True;add('PrepareCard',"""$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
$options = FaBCRUCashOptions($player);
if ($options !== 'Pay_resources') {
 $choice = await $player.Modal(1, 1, $options, "Cash_In_payment");
 FaBCRUCashPay($player, $uid, $options, $choice);
}
FaBFinishPreparedCard($uid);""");add('ResolveCard','DoDrawCard($player, 2);')
 if base=='coax_a_commotion':
  handled=True;add('Hit',"""$choice = await $player.Modal(0, 3, "Each_hero_creates_Quicken&Each_hero_draws&Each_hero_gains_life", "Choose_any_number");
FaBCRUCoax($choice);""")
 if base=='pathing_helix' or base=='poison_the_tips':
  handled=True
  code="""if (count(FaBChoiceRefs($player, 'Arsenal')) === 0) {
 $targets = implode('&', FaBChoiceRefs($player, 'Hand'));
 if ($targets !== '') {
  $chosen = await $player.MZMayChoose($targets, "Reload_face_down");
  FaBARCLoadArsenal($player, $chosen, false);
 }
}"""
  if base=='poison_the_tips':abilities[0]['abilityCode']+='\n'+code
  else:add('Hit',code)
 if base=='herons_flight':
  handled=True;add('AttackDeclared',"""if (FaBPreviousAttackBase() === 'crane_dance') {
 $uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);
 FaBTagUID($uid, 'WTR_POWER:2');
 $choice = await $player.Modal(1, 1, "Only_attack_actions&Only_non_attack_actions", "Choose_allowed_defenders");
 FaBTagUID($uid, $choice === '0' ? 'CRU_HERON_AA' : 'CRU_HERON_NAA');
}""")
 if base=='flood_of_force':
  handled=True;add('AttackDeclared',"""if (in_array(FaBPreviousAttackBase(), ['rushing_river', 'flood_of_force'], true)) {
 $ref = FaBChoiceRefs($player, 'Deck')[0] ?? '';
 FaBRevealChoices($player, $ref);
 $f = FaBIdentityFromMZ($ref);
 if ($f !== null && FaBHasKeyword($f['object'], 'Combo')) {
  FaBMoveUID(intval($f['object']->UniqueID), 'Hand', $player);
  $o = FaBIdentityFromMZ($mzID)['object']; FaBWTRTag($o, 'WTR_POWER:3'); FaBWTRTag($o, 'GO_AGAIN');
 }
}""")
 if base=='rushing_river':
  handled=True;add('Hit',"""if (FaBPreviousAttackBase() === 'torrent_of_tempo') {
 $n = intval(FaBGetState()['chainHits']);
 DoDrawCard($player, $n);
 $targets = implode('&', FaBChoiceRefs($player, 'Hand'));
 $n = min($n, FaBHandCount($player));
 if ($n > 0) {
  $chosen = await $player.MZMultiChoose($targets, $n, $n, "Put_drawn_count_back_on_deck");
  $uids = [];
  foreach (explode('&', $chosen) as $ref) { $uid = intval(FaBIdentityFromMZ($ref)['object']->UniqueID); FaBMoveUID($uid, 'Temp', $player); $uids[] = $uid; }
  $param = FaBARCOrderParam($uids, 'Top');
  $order = await $player.Rearrange($param);
  FaBARCFinishOrder($player, $uids, $order);
 }
}""")
 if base=='mangle':
  handled=True;add('Hit',"""if ($amount >= 4 && FaBFaiHeroHit()) {
 $target = intval(FaBGetState()['defender']);
 $targets = FaBCRUTargets($player, 'damagedEquipment', $target);
 if ($targets !== '') {
  $chosen = await $player.MZChoose($targets, "Destroy_equipment_with_minus_defense_counter");
  $f = FaBIdentityFromMZ($chosen);
  if ($f !== null) { FaBMoveUID(intval($f['object']->UniqueID), 'Graveyard', $target); }
 }
}""")
 if base=='righteous_cleansing':
  handled=True;add('Hit',"""if ($amount >= 4 && FaBFaiHeroHit()) {
 $caster = $player;
 $target = intval(FaBGetState()['defender']);
 $uids = FaBCRUStageOther($caster, $target, 5);
 $targets = implode('&', FaBChoiceRefs($caster, 'Temp'));
 if ($targets !== '') {
  $chosen = await $caster.MZChoose($targets, "Choose_a_name_to_banish");
  $name = CardName(FaBIdentityFromMZ($chosen)['object']->CardID);
  $firstUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);
  FaBMoveUID($firstUID, 'Banish', $target);
  $same = [];
  foreach ($uids as $uid) { $f = FaBFindUID($uid); if ($f !== null && $f['zone'] === 'Temp' && CardName($f['object']->CardID) === $name) { $same[] = $f['mzID']; } }
  $targets = implode('&', $same);
  $maximum = count($same);
  if ($maximum > 0) {
   $more = await $caster.MZMultiChoose($targets, 0, $maximum, "Banish_more_with_the_same_name");
   foreach (explode('&', $more) as $ref) { $f = FaBIdentityFromMZ($ref); if ($f !== null) { FaBMoveUID(intval($f['object']->UniqueID), 'Banish', $target); } }
  }
  $remaining = [];
  foreach ($uids as $uid) { $f = FaBFindUID($uid); if ($f !== null && $f['zone'] === 'Temp') { $remaining[] = $uid; } }
  if (count($remaining) > 0) {
   $param = FaBARCOrderParam($remaining, 'Top');
   $order = await $caster.Rearrange($param);
   FaBCRURestoreOther($caster, $target, $remaining, $order);
  }
 }
}""")
 if base=='shiyana_diamond_gemini':
  handled=True;add('StartTurn',"""$targets = FaBCRUTargets($player, 'hero');
if ($targets !== '') {
 $chosen = await $player.MZChoose($targets, "Copy_target_hero_until_your_next_turn");
 FaBCRUCopyHero($player, $chosen);
}""")
 if handled:snapshot.append({'cardId':id,'abilities':abilities})
 else:pending.append(id)
if pending:raise RuntimeError('Unhandled CRU identities: '+', '.join(pending))
(HERE/'cru_support_abilities.json').write_text(json.dumps([c for c in snapshot if c['cardId'] in support_ids],indent=2)+'\n')
snapshot=[c for c in snapshot if c['cardId'] not in support_ids]
(HERE/'cru_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n')
print(f'Authored {len(snapshot)} CRU identities and {len(support_ids)} shared dice integrations')
