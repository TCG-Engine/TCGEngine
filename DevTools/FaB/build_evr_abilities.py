"""Everfest saved card macros; fail closed on unhandled identities."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename in ['build_arc_abilities.py','build_mon_abilities.py','build_ele_abilities.py']:
 src=(HERE/filename).read_text(encoding='utf-8')
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and not (filename=='build_mon_abilities.py' and node.name=='damage_body'):exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
existing={}
for path in HERE.glob('*_abilities.json'):
 if not path.name.startswith('evr'):
  for c in json.loads(path.read_text(encoding='utf-8')):existing[c['cardId']]=c
cards=json.loads((HERE/'evr_catalog.json').read_text(encoding='utf-8'))
snapshot=[];pending=[]
for c in cards:
 id=c['id'];base=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id
 v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True
  prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:snapshot.append(existing[id]);continue
 if base in ['arcane_lantern','macho_grande','thunder_quake','pulverize','wax_on','hundred_winds','payload','dreadbore','iyslander','valda_brightaxe','earthlore_bounty','signal_jammer','dissolution_sphere','teklo_pounder','runeblood_incantation','pyroglyphic_protection','passing_mirage','pierce_reality','haze_bending','shimmers_of_silver','nerves_of_steel','skull_crushers','talisman_of_balance','talisman_of_cremation','talisman_of_featherfoot','talisman_of_recompense','talisman_of_tithes','talisman_of_warfare','stalagmite_bastion_of_isenloft','fractal_replication','firebreathing','mask_of_the_pouncing_lynx','silver_palms']:
  handled=True
 if base in ['amulet_of_assertiveness','amulet_of_echoes','amulet_of_havencall','amulet_of_ignition','amulet_of_intervention','amulet_of_oblation','clarity_potion','healing_potion','potion_of_deja_vu','potion_of_ironhide','potion_of_luck','potion_of_seeing','silver','vexing_quillhand','crown_of_reflection','helm_of_sharp_eye','micro_processor','genis_wotchuneed','krakens_aethervein']:handled=True
 if base=='grandeur_of_valahai':add('CardPitched',"FaBWTRCreateArena($player, 'seismic_surge');")
 if base=='seismic_stir':add('ResolveCard',f"FaBEVRCreate($player, 'seismic_surge', {v});")
 if base=='rain_razors':add('ResolveCard',"FaBEVRAdd($player, 'RAIN', 2);")
 if base=='ready_to_roll':add('ResolveCard',"FaBEVRAdd($player, 'READY');")
 if base=='high_striker':add('ResolveCard',f"FaBEVRAdd($player, 'HIGH_STRIKER', {2*v});")
 if base=='revel_in_runeblood':add('ResolveCard',"if (FaBELEBothActions($player) && count(FaBARCPlayed($player, true)) > 1) { FaBARCCreateRunes($player, 4); } FaBEVRAdd($player, 'REVEL');")
 if base in ['read_the_glide_path','release_the_tension']:add('ResolveCard',nxt('ARROW',v,['CRU_NO_ARSENAL_DR'] if base=='release_the_tension' else [])+(opt(1) if base=='read_the_glide_path' else ''))
 if base=='rotary_ram':add('ResolveCard',f"FaBEVRNext($player, 'MECH', {v}); if (FaBARCEffect($player, 'ARC_BOOSTED')) {{ FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'EVR_BOTTOM'); }}")
 if base=='outland_skirmish':add('ResolveCard',f"FaBEVRNext($player, 'ONE_HAND', {v}); FaBEVRAdd($player, 'OUTLAND');")
 if base=='slice_and_dice':add('ResolveCard',f"FaBEVRAdd($player, 'SLICE', {v});")
 if base=='oath_of_steel':add('ResolveCard',"FaBEVRAdd($player, 'OATH');")
 if base=='smashing_good_time':add('ResolveCard',f"FaBEVRAdd($player, 'SMASH'); if (FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'fromZone') === 'Arsenal') {{ FaBWTRAddEffect($player, 'NEXT_ATTACK', {v}); }}")
 if base=='bad_beats':add('ResolveCard',f"if (FaBEVRRoll($player) >= 4) {{ FaBWTRAddEffect($player, 'NEXT_BRUTE', {v+2}); }}")
 if base=='rolling_thunder':add('ResolveCard',"$roll = FaBEVRRoll($player); FaBEVRNext($player, 'BRUTE_ATTACK', $roll);")
 if base=='high_roller':add('ResolveCard',"FaBRequestIntimidate($player); if (FaBEVRCount($player, 'HIGH_ROLL')) { FaBRequestIntimidate($player); }")
 if base in ['bare_fangs','wild_ride']:
  add('AttackDeclared',UID+"DoDrawCard($player, 1); $discard = FaBRandomHandUID($player); $six = $discard > 0 && intval(CardPower(FaBFindUID($discard)['object']->CardID)) >= 6; if ($discard > 0) { FaBDiscardChoice($player, FaBFindUID($discard)['mzID']); } if ($six) { FaBTagUID($uid, '"+('WTR_POWER:2' if base=='bare_fangs' else 'GO_AGAIN')+"'); }")
 if base=='zoom_in':add('AttackDeclared',"$boosts = FaBCRUCount($player, 'CHAIN_BOOST');"+opt('$boosts'))
 if base=='t_bone':handled=True
 if base=='battering_bolt':add('Hit',hit("FaBRevealChoices($target, implode('&', FaBChoiceRefs($target, 'Hand'))); FaBEVRBattering($target);"))
 if base=='bingo':add('Hit',UID+hit(choice("implode('&', FaBChoiceRefs($target, 'Hand'))",'Reveal_a_card',False,chooser='$target')+" if ($chosen !== 'PASS') { FaBRevealChoices($target, $chosen); $o = FaBIdentityFromMZ($chosen)['object']; if (FaBWTRIsAttackAction($o)) { FaBTagUID($uid, 'GO_AGAIN'); } elseif (FaBHasType($o, 'Action')) { DoDrawCard($player, 1); } }"))
 if base in ['fatigue_shot','pulverize','timidity_point']:add('Hit',hit("FaBWTRAddEffect($target, 'EVR_"+{'fatigue_shot':'FATIGUE','pulverize':'PULVERIZE','timidity_point':'TIMID'}[base]+"', 1, ['expiresAfterTurnOf'=>$target], true);"))
 if base=='ride_the_tailwind':add('Hit',"FaBEVRAdd($player, 'TAILWIND');")
 if base=='spring_tidings':add('Hit',"DoDrawCard($player, FaBEVRSmallChain($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID)));")
 if base=='break_tide':add('AttackDeclared',"if (in_array(FaBWTRBase(FaBGetState()['previousAttackCardID']), ['rushing_river','flood_of_force'], true)) { FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_POWER:3'); FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'DOMINATE'); FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'EVR_BREAK_TIDE'); }");add('Hit',"if (in_array('EVR_BREAK_TIDE', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true)) { FaBEVRBanishTop($player, 'NEXT_TURN'); }")
 if base=='winds_of_eternity':add('AttackDeclared',"if (FaBWTRBase(FaBGetState()['previousAttackCardID']) === 'winds_of_eternity') { FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_POWER:2'); FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'EVR_WINDS'); }");add('Hit',"if (in_array('EVR_WINDS', (array)FaBIdentityFromMZ($mzID)['object']->TurnEffects, true)) { FaBEVRReturnWinds($player); }")
 if base=='drowning_dire':add('Hit',choice("FaBARCSelect($player, 'Graveyard', '', 'NAA')",'Bottom_non_attack')+" if ($chosen !== 'PASS') { FaBARCToDeck($player, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), false); }")
 if base in ['shrill_of_skullform','swarming_gloomveil','reek_of_corruption','swing_big']:handled=True
 if base=='runic_reclamation':add('Hit',hit(choice("FaBEVRTargets($player, 'Aura', $target)",'Destroy_aura',False)+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); FaBARCCreateRunes($player, 1); }"))
 if base=='twin_twisters':add('ResolveCard',UID+'$mode = await $player.Modal(1, 1, "Next_attack_on_hit&This_attack", "Choose_Twin_Twisters_mode"); FaBTagUID($uid, $mode === \'0\' ? \'EVR_TWISTERS\' : \'WTR_POWER:1\');')
 if base=='veiled_intentions':add('ResolveCard',f"FaBEVRNext($player, 'VEILED', {v+1});")
 if base=='blade_runner':add('ResolveCard',choice("FaBEVRTargets($player, '1HAttack')",'Grant_go_again',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'GO_AGAIN'); }} FaBEVRNext($player, 'WEAPON', {v});")
 if base=='in_the_swing':add('ResolveCard',choice("FaBEVRTargets($player, 'WeaponAttack')",'Empower_weapon',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'WTR_POWER:{v}'); }}")
 if base=='tri_shot':add('ResolveCard',choice("FaBEVRTargets($player, 'Bow')",'Choose_bow',False)+" if ($chosen !== 'PASS') { FaBEVRExtraBow($chosen, 2); }")
 if base=='this_rounds_on_me':add('ResolveCard',"$seats = FaBLiveSeats(); $i = 0; while ($i < count($seats)) { $seat = $seats[$i]; DoDrawCard($seat, 1); $i = $i + 1; } FaBEVRRound($player);")
 if base in ['aether_wildfire','emeritus_scolding','timekeepers_whim']:
  damage='4' if base=='aether_wildfire' else (f'($player === intval(GetTurnPlayer()) ? {v+1} : {v+3})' if base=='emeritus_scolding' else str(v+2))
  add('PrepareCard',arcane_prepare(base=='aether_wildfire'))
  code=arcane(damage, "intval(FaBARCCard($uid, 'target'))").replace('FaBELEDamageBonus($caster,', 'FaBEVRArcaneBonus($caster,')
  if base=='aether_wildfire':code=code.replace('FaBARCHeroTargets($player, false)', 'FaBARCHeroTargets($player, true)').replace('FaBARCTargetSeat($player, $hero, false)', 'FaBARCTargetSeat($player, $hero, true)');code+=" if ($player !== intval(GetTurnPlayer())) { FaBEVRWildfire($dealt); }"
  if base=='timekeepers_whim':code+=" if ($player !== intval(GetTurnPlayer())) { FaBTagUID($uid, 'EVR_BOTTOM'); }"
  add('ResolveCard',code)
 if base=='pry':
  add('ResolveCard',target()+f"$maximum = min(FaBHandCount($target), $player !== intval(GetTurnPlayer()) ? FaBHandCount($target) : {v}); $shown = ''; if ($maximum > 0) {{ $targets = implode('&', FaBChoiceRefs($target, 'Hand')); $shown = await $target.MZMultiChoose($targets, $maximum, $maximum, \"Reveal_cards\"); FaBRevealChoices($target, $shown); $chosen = await $player.MZMayChoose($shown, \"Bottom_revealed_card\"); if ($chosen !== 'PASS') {{ FaBARCToDeck($target, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), false); DoDrawCard($target, 1); }} }}")
 if base=='even_bigger_than_that':add('ResolveCard',opt(v)+"$top = FaBChoiceRefs($player, 'Deck')[0] ?? ''; FaBRevealChoices($player, $top); if ($top !== '' && intval(CardPower(FaBIdentityFromMZ($top)['object']->CardID)) > FaBEVRCount($player, 'PHYSICAL')) { FaBWTRCreateArena($player, 'quicken'); DoDrawCard($player, 1); }")
 if base=='silver':add('ResolveAbility','DoDrawCard($player, 1);')
 if base=='healing_potion':add('ResolveAbility','FaBCRUGainLife($player, 2);')
 if base=='clarity_potion':add('ResolveAbility',opt(2))
 if base=='vexing_quillhand':add('ResolveAbility','FaBARCCreateRunes($player, 2);')
 if base=='potion_of_ironhide':add('ResolveAbility',"FaBEVRAdd($player, 'IRONHIDE');")
 if base=='potion_of_luck':add('ResolveAbility',"$count = FaBEVRShuffleHandArsenal($player); DoDrawCard($player, $count);")
 if base=='potion_of_deja_vu':add('ResolveAbility',"$uids = FaBARCStageRefs($player, implode('&', FaBChoiceRefs($player, 'Pitch'))); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBARCFinishOrder($player, $uids, $order); }")
 if base=='potion_of_seeing':add('ResolveAbility',target()+"$shownUIDs = FaBEVRPrivateHand($player, $target); $view = FaBEVRUIDRefs($shownUIDs); if ($view !== '') { $seen = await $player.MZChoose($view, \"View_hand_and_continue\"); } FaBEVRForgetHand($shownUIDs);")
 if base=='amulet_of_ignition':add('ResolveAbility',"FaBEVRAdd($player, 'IGNITION');")
 if base=='amulet_of_intervention':add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1);")
 if base=='amulet_of_echoes':add('ResolveAbility',choice("FaBEVRTargets($player, 'Echoes')",'Choose_hero',False)+" $target = FaBARCTargetSeat($player, $chosen, false); "+discard(2))
 if base in ['amulet_of_assertiveness','amulet_of_oblation']:
  add('ResolveAbility',choice("FaBELECombatChoices('"+('AA' if base=='amulet_of_oblation' else 'Attack')+"')",'Choose_attack',False)+" if ($chosen !== 'PASS') { FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), '"+('EVR_BOTTOM' if base=='amulet_of_oblation' else 'EVR_ASSERT')+"'); }")
 if base=='dreadbore':add('ResolveAbility',"if (FaBELEArsenalSpace($player)) { "+choice("FaBELESelect($player, 'Hand', '', 'Arrow')",'Load_arrow')+" if ($chosen !== 'PASS') { $uid = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); FaBARCLoadArsenal($player, $chosen, true); FaBTagUID($uid, 'WTR_POWER:1'); } }")
 if base=='firebreathing':add('ResolveAbility',"FaBTagUID(intval(DecisionQueueController::GetVariable('arcAttackUID')), 'WTR_POWER:1');")
 if base=='helm_of_sharp_eye':add('ResolveAbility',"FaBEVRBanishTop($player, 'CHAIN');")
 if base=='genis_wotchuneed':add('ResolveAbility',"$seats = FaBOpponents($player); $i = 0; $silver = 0; while ($i < count($seats)) { $target = $seats[$i]; "+choice("implode('&', FaBChoiceRefs($target, 'Hand'))",'Bottom_card_and_draw',chooser='$target')+" if ($chosen !== 'PASS') { FaBARCToDeck($target, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), false); DoDrawCard($target, 1); FaBWTRCreateArena($player, 'silver'); $silver = $silver + 1; } $i = $i + 1; } if ($silver === 0) { DoDrawCard($player, 1); }")
 if base in ['coalescence_mirage','phantasmal_haze','miraging_metamorph']:
  if base=='phantasmal_haze':add('ResolveAbility',"FaBWTRCreateArena($player, 'spectral_shield');")
  if base=='coalescence_mirage':add('ResolveAbility',choice("FaBARCSelect($player, 'Hand', 'Illusionist', 'Aura', 0)",'Put_aura_into_arena')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Hand', 'Arena'); }")
  if base=='miraging_metamorph':add('ResolveAbility',choice("FaBEVRTargets($player, 'Aura', $player)",'Copy_aura',False)+" if ($chosen !== 'PASS') { FaBEVRCopyAura($player, $chosen); }")
 if base=='bravo_star_of_the_show':add('StartTurn',"$cards = []; "+choice("FaBELESelect($player, 'Hand', 'Earth')",'Reveal_Earth')+" if ($chosen !== 'PASS') { $cards[] = $chosen; "+choice("FaBELESelect($player, 'Hand', 'Ice')",'Reveal_Ice')+" if ($chosen !== 'PASS') { $cards[] = $chosen; "+choice("FaBELESelect($player, 'Hand', 'Lightning')",'Reveal_Lightning')+" if ($chosen !== 'PASS') { $cards[] = $chosen; FaBRevealChoices($player, implode('&', $cards)); FaBEVRAdd($player, 'STAR'); } } }")
 if base in ['cash_out','knick_knack_bric_a_brac','blood_on_her_hands']:
  kind={'cash_out':'Cash','knick_knack_bric_a_brac':'Coins','blood_on_her_hands':'Copper'}[base]
  add('PrepareCard',UID+f"$targets = FaBEVRCostTargets($player, '{kind}'); $maximum = count(array_filter(explode('&', $targets))); $chosen = ''; if ($maximum > 0) {{ $chosen = await $player.MZMultiChoose($targets, 0, $maximum, \"Destroy_as_additional_cost\"); }} FaBEVRPayObjects($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  if base=='cash_out':add('ResolveCard',"FaBEVRCreate($player, 'silver', intval(FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'evrDestroyed')));")
  if base=='knick_knack_bric_a_brac':add('ResolveCard',UID+"$count = 1 + intval(FaBARCCard($uid, 'evrCoinValue')); while ($count > 0) { "+choice("FaBEVRSearchItems($player)",'Find_Amulet_Potion_Talisman')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Deck', 'Arena'); } $count = $count - 1; } FaBShuffleDeck($player);")
  if base=='blood_on_her_hands':add('ResolveCard',UID+"$count = intval(FaBARCCard($uid, 'evrDestroyed')); $uses = [0,0,0]; while ($count > 0) { $options = FaBEVRBloodModes($uses); if ($options === '') { break; } $mode = await $player.Modal(1, 1, $options, \"Choose_weapon_bonus\"); $index = FaBEVRBloodModeIndex($uses, intval($mode)); $uses[$index] = $uses[$index] + 1; "+choice("FaBEVRWeapons($player, '1H')",'Choose_weapon',False)+" if ($chosen !== 'PASS') { FaBEVRBloodWeapon($chosen, $index); } $count = $count - 1; }")
 if base=='imposing_visage':add('PrepareCard',UID+"$maximum = FaBAvailablePitch($player, $uid); $options = FaBEVRNumbers(max(0, $maximum - 3)); $mode = await $player.Modal(1, 1, $options, \"Choose_X\"); FaBEVRSetX($uid, intval($mode), 3); FaBFinishPreparedCard($uid);");add('ResolveCard',UID+"$x = intval(FaBARCCard($uid, 'evrX')); "+choice("FaBARCSelect($player, 'Deck', '', 'Aura', $x)",'Find_aura')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Deck', 'Arena'); } FaBShuffleDeck($player);")
 if base=='life_of_the_party':add('PrepareCard',UID+choice("FaBEVRBrew($player)",'Use_Crazy_Brew_instead')+" FaBEVRLifeParty($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
 if base=='pick_a_card_any_card':add('ResolveCard',target(True)+"$remaining = "+str(v+1)+"; while ($remaining > 0 && FaBHandCount($target) > 0) { $shownUIDs = FaBEVRPrivateHand($player, $target); "+choice("FaBEVRUIDRefs($shownUIDs)",'Name_a_card',False)+" $name = CardName(FaBIdentityFromMZ($chosen)['object']->CardID); FaBEVRForgetHand($shownUIDs); $uid = FaBRandomHandUID($target); $card = FaBFindUID($uid); FaBRevealChoices($target, $card['mzID']); if (CardName($card['object']->CardID) === $name) { FaBWTRCreateArena($player, 'silver'); } $remaining = $remaining - 1; }")
 if base=='scour':add('PrepareCard',UID+"$maximum = FaBAvailablePitch($player, $uid); $options = FaBEVRNumbers($maximum); $mode = await $player.Modal(1, 1, $options, \"Choose_X\"); FaBEVRSetX($uid, intval($mode), 0); FaBFinishPreparedCard($uid);");add('ResolveCard',UID+target()+"$caster = $player; $maximum = intval(FaBARCCard($uid, 'evrX')); $targets = FaBEVRScour($target); $maximum = min($maximum, count(array_filter(explode('&', $targets)))); $damage = 0; if ($maximum > 0) { $chosen = await $player.MZMultiChoose($targets, $maximum, $maximum, \"Destroy_auras\"); $damage = FaBEVRDestroyChoices($chosen); } "+damage_body()+"$player = $caster;")
 if base=='shatter':add('ResolveCard',choice("FaBEVRWeapons($player, '2H')",'Choose_weapon',False)+" if ($chosen !== 'PASS') { FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'EVR_SHATTER'); }")
 if base=='steadfast':add('ResolveCard',"$targets = FaBEVRSources(); "+choice('$targets','Choose_damage_source',False)+f" if ($chosen !== 'PASS') {{ FaBWTRAddEffect($player, 'EVR_STEADFAST', {v+3}, ['sourceUID'=>intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)]); }}")
 if base=='sigil_of_parapets':handled=True
 if base=='crown_of_reflection':add('ResolveAbility',choice("FaBARCSelect($player, 'Arena', 'Illusionist', 'Aura')",'Destroy_your_aura',False)+" if ($chosen !== 'PASS') { $cost = intval(CardCost(FaBIdentityFromMZ($chosen)['object']->CardID)); FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); "+choice("FaBARCSelect($player, 'Hand', 'Illusionist', 'Aura', $cost)",'Put_aura_into_arena')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Hand', 'Arena'); } }")
 if base=='krakens_aethervein':add('ResolveAbility',"$caster = $player; "+target(True)+"$damage = 1; "+damage_body()+"DoDrawCard($caster, $dealt); $player = $caster;")
 if base=='micro_processor':add('ResolveAbility',"$index = intval(DecisionQueueController::GetVariable('arcAbilityIndex')); if ($index === 0) { "+opt(1)+" } elseif ($index === 1) { DoDrawCard($player, 1); "+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Put_card_on_top',False)+" if ($chosen !== 'PASS') { FaBARCToDeck($player, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), true); } } else { FaBEVRBanishOnly($player); }")
 if base=='amulet_of_havencall':add('ResolveAbility',choice("implode('&', FaBChoiceRefs($player, 'Deck', ['base'=>'rally_the_rearguard']))",'Defend_with_Rally')+" if ($chosen !== 'PASS') { FaBEVRAddDefender($player, $chosen); } FaBShuffleDeck($player);")
 if base in ['pulverize','thunder_quake']:add('ResolveAbility',choice("FaBEVRHeaveChoices($player)",'Heave_a_card')+" if ($chosen !== 'PASS') { $uid = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); while (intval(GetResources($player)) < 3) { $pitchRefs = FaBEVRHeavePitch($player, $uid); $pitched = await $player.MZChoose($pitchRefs, \"Pitch_for_heave\"); FaBARCPitchForEffect($player, $pitched); } AddResources($player, intval(GetResources($player)) - 3); $f = FaBFindUID($uid); if ($f !== null && $f['zone'] === 'Hand' && FaBELEArsenalSpace($player)) { FaBARCLoadArsenal($player, $f['mzID'], true); FaBEVRCreate($player, 'seismic_surge', 3); } }")
 if base=='mask_of_the_pouncing_lynx':add('Hit',UID+"$mode = await $player.Modal(1, 1, \"Keep&Destroy_and_search\", \"Mask_of_the_Pouncing_Lynx\"); if ($mode === '1' && FaBFindUID($uid) !== null) { FaBMONDestroy($uid); "+choice("FaBEVRSmallDeck($player)",'Find_small_attack')+" if ($chosen !== 'PASS') { $cardUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); $o = FaBMoveChoice($player, $chosen, 'Deck', 'Banish'); if ($o !== null) { $o->PlayableFromBanish = 1; } } FaBShuffleDeck($player); }")
 if base=='smashing_good_time':add('ResolveAbility',"$target = intval(FaBGetState()['defender']); "+choice("FaBEVRTargets($player, 'Item', $target)",'Destroy_item')+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); }")
 if base=='silver_palms':add('ResolveAbility',"$target = intval(GetTurnPlayer()); $mode = await $target.Modal(1, 1, \"Decline&Draw\", \"Silver_Palms_draw\"); if ($mode === '1' && FaBSeatIsLive($target)) { DoDrawCard($target, 1); FaBWTRCreateArena($player, 'silver'); }")
 if base=='talisman_of_cremation':add('ResolveAbility',"$name = await $player.NameCard(\"\", \"Name_a_card\"); FaBEVRCremate($player, $name);")
 if base=='shatter':add('ResolveAbility',"$targets = FaBEVRShatterTargets(); "+choice('$targets','Destroy_equipment_instead_of_damage')+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); FaBEVRAdd($player, 'SHATTER_REPLACE'); } FaBBeginDamageStep();")
 if not handled:pending.append(id)
 snapshot.append(dict(cardId=id,abilities=a))
old={c['cardId']:c for c in json.loads((HERE/'evr_abilities.json').read_text(encoding='utf-8'))} if (HERE/'evr_abilities.json').exists() else {}
for c in snapshot:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'evr_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
print('EVR identities:',len(snapshot),'Unhandled:',len(pending));print('\n'.join(pending))
if pending:raise SystemExit(1)
