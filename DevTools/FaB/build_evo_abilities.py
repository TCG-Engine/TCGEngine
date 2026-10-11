"""Bright Lights source snapshot; fail on every unaccounted functional identity."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename,names in [('build_mon_abilities.py',['clean']),('build_dyn_abilities.py',['pick']),('build_arc_abilities.py',['opt']),('build_upr_abilities.py',['deal'])]:
 src=(HERE/filename).read_text()
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in names:exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
V="$victim = intval(FaBGetState()['defender']);"
def select(expr,tip='Choose_card',may=True,p='$player',var='$chosen'):return pick(expr,tip,may,var=var,who=p)
def multi(expr,n,tip='Choose_cards',minimum=0,p='$player'):
 return f'$refs = {expr}; $maximum = min({n}, count(array_filter(explode("&", $refs)))); $chosen = "-"; if ($maximum > 0) {{ $chosen = await {p}.MZMultiChoose($refs, {minimum}, $maximum, "{tip}"); }}'
def hit(code):return 'if (FaBFaiHeroHit()) { '+V+code+' }'
def ref(z,k='',all=False,p='$player'):return f"FaBEVORefs({p}, '{z}', '{k}', "+('true' if all else 'false')+')'
def steam(kind='crank',all=False):return select(f"FaBEVOItemTargets($player, '{kind}', "+('true' if all else 'false')+", true)",'Add_steam_counter',False)+"FaBEVOSteam($chosen);"
def item(z='Hand',all=False):return select(ref(z,'cheap',all),'Put_item_into_arena')+"FaBEVOPutItem($player, $chosen);"
def under(optional=False):return "$sourceUID = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); $previews = FaBEVOUnderPreview($player, $sourceUID); $chosen = 'PASS'; if ($previews !== '') { $chosen = await $player."+('MZMayChoose' if optional else 'MZChoose')+"($previews, \"Destroy_card_under_equipment\"); } $paid = FaBEVODestroyUnder($player, $sourceUID, $chosen, $previews);"
def attacktag(tag):return select('FaBEVOAttackRefs()','Choose_attack')+f"FaBDYNTag($chosen, '{tag}');"
def physical(n):return "$targetUID = FaBUPRHeroUID($victim); "+deal(n,physical=True)
existing={c['cardId']:c for p in sorted(HERE.glob('*_abilities.json')) if p.name!='evo_abilities.json' for c in json.loads(p.read_text())}
old={c['cardId']:c for c in json.loads((HERE/'evo_abilities.json').read_text())} if (HERE/'evo_abilities.json').exists() else {}
out=[];pending=[]
for c in json.loads((HERE/'evo_catalog.json').read_text()):
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);txt=c['functional_text_plain'];a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:
  if 'Boost' in c['card_keywords'] and not any(x['macroName']=='PrepareCard' for x in existing[id]['abilities']):a=list(existing[id]['abilities']);handled=True
  else:out.append(existing[id]);continue
 if 'Boost' in c['card_keywords']:
  n=2 if b=='twin_drive' else 1
  if b=='twin_drive':add('PrepareCard',UID+"$maxBoost = min(2, count(FaBChoiceRefs($player, 'Deck'))); $boostCount = await $player.NumberChoose(0, $maxBoost, \"Choose_number_of_boosts\"); for ($i = 0; $i < intval($boostCount); $i = $i + 1) { FaBARCBoost($player, $uid); } FaBFinishPreparedCard($uid);")
  else:add('PrepareCard',UID+f"for ($i = 0; $i < {n}; $i = $i + 1) {{ if (count(FaBChoiceRefs($player, 'Deck')) > 0) {{ $boost = await $player.Modal(1, 1, \"Boost&Do_not_boost\", \"Banish_top_card_to_boost\"); if ($boost === '0') {{ FaBARCBoost($player, $uid); }} }} }} FaBFinishPreparedCard($uid);")
 if 'Scrap' in c['card_keywords']:
  add('PrepareCard',UID+multi(ref('Graveyard','scrap'),2 if b=='scrap_trader' else 1,'Scrap_items_or_equipment')+"FaBEVOScrap($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  effects={'junkyard_dogg':"FaBTagUID($uid, 'WTR_POWER:1');",'hydraulic_press':"FaBTagUID($uid, 'OVERPOWER');",'scrap_compactor':"FaBEVOAdd($player, 'INSTANT_EVO');",'scrap_hopper':"FaBWTRCreateArena($player, 'quicken');",'scrap_harvester':steam(),'scrap_prospector':'AddResources($player, intval(GetResources($player)) + 1);'}
  if b in effects:add('AttackDeclared',UID+"if (FaBARCCard($uid, 'evoScrap')) { "+effects[b]+' }')
  if b=='scrap_trader':add('ResolveCard',UID+"AddResources($player, intval(GetResources($player)) + 2 * intval(FaBARCCard($uid, 'evoScrap')));")
 if 'Galvanize' in txt:add('Defended',UID+select(ref('Arena','item'),'Destroy_item_to_galvanize')+"if ($chosen !== 'PASS') { FaBDYNDestroyChoice($chosen); FaBTagUID($uid, 'WTR_DEFENSE:2'); }")
 if 'Evo' in c['types'] and 'Equipment' in c['types'] and 'Demi-Hero' not in c['types']:
  code=UID
  if b in ['evo_circuit_breaker','evo_atom_breaker','evo_face_breaker','evo_mach_breaker']:
   code+=multi("FaBEVOItemTargets($player, 'driver', false, true)",99,'Transform_Hyper_Drivers')+"$n = count(FaBUPRUIDs($chosen)); if (FaBEVOTransform($player, $mzID, $chosen)) { FaBWTRAddEffect($player, 'ARC_PREVENT_ONCE', 2 * $n); }"
  else:code+="FaBEVOTransform($player, $mzID);"
  add('ResolveCard',code)
  if 'Instant - Destroy a card under this' in txt:
   add('PrepareCard',UID+"$sourceUID = intval(FaBIdentityFromMZ($mzID)['object']->SourceUniqueID); $previews = FaBEVOUnderPreview($player, $sourceUID); $chosen = 'PASS'; if ($previews !== '') { $chosen = await $player.MZChoose($previews, \"Destroy_card_under_equipment\"); } FaBEVODestroyUnder($player, $sourceUID, $chosen, $previews); FaBFinishPreparedCard($uid);")
   body={'evo_command_center':"FaBEVOAdd($player, 'WEAPON_DRAW');",'evo_engine_room':"FaBEVOAdd($player, 'WEAPON_DISCOUNT');",'evo_smoothbore':"FaBWTRAddEffect($player, 'NEXT_WEAPON', 1);",'evo_thruster':select("implode('&', FaBChoiceRefs($player, 'Weapons'))",'Choose_weapon',False)+"FaBDYNTag($chosen, 'CRU_EXTRA_ATTACK');",'evo_data_mine':"DoDrawCard($player, 1); "+select(ref('Hand'),'Top_card_from_hand',False)+"FaBDTDTopChoice($player, $chosen);",'evo_battery_pack':steam(),'evo_cogspitter':item(),'evo_charging_rods':"FaBWTRCreateArena($player, 'quicken');"}[b]
   add('ResolveAbility',body)
 if b.startswith('evo_steel_soul_'):
  body={'evo_steel_soul_memory':"FaBEVOAdd($player, 'INTELLECT');",'evo_steel_soul_processor':"AddResources($player, intval(GetResources($player)) + 3);",'evo_steel_soul_tower':"FaBEVOAP($player);",'evo_steel_soul_controller':select(ref('Graveyard','six'),'Put_six_power_attack_fifth')+"FaBEVOFifth($player, $chosen);"}[b];add('ResolveAbility',body)
 if b in ['evo_circuit_breaker','evo_atom_breaker','evo_face_breaker','evo_mach_breaker']:
  body={'evo_circuit_breaker':multi(ref('Banish','aa'),2,'Shuffle_banished_attacks','$maximum')+"FaBEVOShuffle($player, $chosen);",'evo_atom_breaker':"AddResources($player, intval(GetResources($player)) + 2);",'evo_face_breaker':"FaBTagUID(intval(DecisionQueueController::GetVariable('evoAttackUID')), 'WTR_POWER:2');",'evo_mach_breaker':"FaBWTRCreateArena($player, 'quicken');"}[b];add('ResolveAbility',under(True)+"if ($paid) { "+body+' }')
 if b in ['evo_zoom_call','evo_buzz_hive','evo_whizz_bang','evo_zip_line']:
  body={'evo_zoom_call':select(ref('Hand'),'Banish_from_hand')+"if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Hand', 'Banish'); DoDrawCard($player, 1); }",'evo_buzz_hive':"AddResources($player, intval(GetResources($player)) + 1);",'evo_whizz_bang':attacktag('WTR_POWER:1'),'evo_zip_line':attacktag('GO_AGAIN')}[b];add('ResolveAbility',body)
 if 'Item' in c['types']:
  add('ResolveCard',"FaBARCEnterItem($player, FaBIdentityFromMZ($mzID)['object']);")
  if 'At the start of your turn' in txt:
   code=UID+"$steam = intval(FaBObjectCounters(FaBIdentityFromMZ($mzID)['object'])['STEAM'] ?? 0); $mode = '1'; if ($steam > 0) { $mode = await $player.Modal(1, 1, \"Remove_steam&Destroy_item\", \"Maintain_item\"); } if ($mode === '0') { FaBEVOSteam(FaBDTDSource($uid), -1); } else { FaBMONDestroy($uid); "
   if b=='tick_tock_clock':code+='$victim = $player; '+physical(1)
   add('StartTurn',code+' }')
 if b=='master_cog':
  add('CardPitched',steam()+"FaBTryCompletePayment();")
  add('ResolveAbility',UID+"$mode = await $player.Modal(1, 1, \"Crank&Keep_steam\", \"Remove_steam_to_crank\"); if ($mode === '0') { FaBEVODoCrank($player, $uid); }")
 if b=='symbiosis_shot':add('ResolveAbility',UID+"$mode = await $player.Modal(1, 1, \"Add_steam&Decline\", \"Symbiosis_Shot\"); if ($mode === '0') { FaBEVOSymbiosisSteam($uid); }")
 if b=='banksy':add('Hit',hit(steam()))
 if b in ['maxx_nitro','maxx_the_hype_nitro']:add('ResolveAbility',"FaBEVOCreateDriver($player);")
 if b in ['teklovossen','teklovossen_esteemed_magnate']:add('ResolveAbility',"FaBEVOAdd($player, 'TEKLO_INSTANT');")
 if b in ['dash_io','dash_database']:add('ResolveAbility',"$previews = FaBDYNPeekTop($player, $player); if (count($previews) > 0) { $order = FaBARCOrderParam($previews); $ignored = await $player.Rearrange($order); FaBEVOClearPeek($player, $previews); }")
 if b.startswith('cogwerx_base_'):
  body={'head':select(ref('Banish','mechAA'),'Shuffle_banished_attack')+"FaBEVOShuffle($player, $chosen);",'chest':"AddResources($player, intval(GetResources($player)) + 2);",'arms':"FaBWTRAddEffect($player, 'ARC_NEXT_MECH', 1);",'legs':"FaBEVOAP($player);"}[b.split('_')[-1]];add('ResolveAbility',body)
 if b=='big_bertha':add('ResolveAbility',steam('driver'))
 if b=='under_loop':add('Hit',UID+"FaBARCToDeck($player, $uid, false);")
 if b in ['data_link','dive_through_data']:add('Hit',opt(1))
 if b in ['expedite','metex']:add('Hit',item())
 if b=='heist':add('Hit',hit(item('Banish',True)))
 if b in ['gas_up','quickfire','re_charge']:
  code=f"FaBEVOAdd($player, 'NEXT_BOOST', {v+1});"
  if b=='gas_up':code+=select(ref('Banish','driver'),'Return_Hyper_Driver')+"FaBEVOPutDriver($player, $chosen);"
  if b=='re_charge':code+=steam('driver')
  add('ResolveCard',code)
 if b=='gigawatt':add('ResolveCard',f"FaBWTRAddEffect($player, 'ARC_NEXT_MECH', {v+1});")
 if b=='demolition_protocol':add('AttackDeclared',hit(multi('FaBEVOSteamTargets($victim)','FaBEvoCount($player)','Remove_all_steam')+"FaBEVORemoveSteamMany($chosen);"))
 if b=='spring_a_leak':add('Hit',hit(select('FaBEVOSteamTargets($victim)','Remove_all_steam',False)+"FaBEVORemoveSteam($chosen);"))
 if b=='hyper_scrapper':
  add('PrepareCard',UID+multi(ref('Graveyard','item'),99,'Banish_items_as_cost')+"FaBEVOHyperScrap($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  add('AttackDeclared',UID+"FaBTagUID($uid, 'WTR_POWER:'.intval(FaBARCCard($uid, 'evoScrap'))); if (intval(FaBARCCard($uid, 'evoDrivers')) >= 3) { AddResources($player, intval(GetResources($player)) + 6); FaBTagUID($uid, 'GO_AGAIN'); }")
 if b=='moonshot':
  add('PrepareCard',UID+multi(ref('Arena','driver'),99,'Destroy_Hyper_Drivers_as_cost')+"FaBARCSetCard($uid, 'evoDrivers', count(FaBUPRUIDs($chosen))); FaBDYNDestroyUIDs(FaBUPRUIDs($chosen)); FaBFinishPreparedCard($uid);")
  add('AttackDeclared',UID+"FaBTagUID($uid, 'WTR_POWER:'.(3 * intval(FaBARCCard($uid, 'evoDrivers'))));")
 if b in ['annihilator_engine','terminator_tank','war_machine']:
  body={'annihilator_engine':"FaBEVODestroyDefenders();",'terminator_tank':select(ref('Hand',p='$victim'),'Discard_a_card',False,p='$victim')+"FaBDiscardChoice($victim, $chosen);",'war_machine':"FaBDYNDestroyUIDs(FaBUPRUIDs(FaBEVORefs($victim, 'Arsenal')));"}[b];add('Hit',hit("if (FaBEvoCount($player) >= 1) { "+body+' }'))
 if b=='smash_and_grab':add('Hit',hit("if (FaBARCEffect($player, 'ARC_BOOSTED') >= 2) { "+select(ref('Arena','item',p='$victim'),'Gain_control_of_item',False)+"FaBDTDSteal($player, $chosen); }"))
 if b=='pulsewave_protocol':add('AttackDeclared',V+"if (FaBFaiHeroHit()) { $x = FaBEvoCount($player); "+multi(ref('Hand',p='$victim'),'$x','Reveal_cards',minimum='$maximum',p='$victim')+"$revealed = $chosen; FaBRevealChoices($victim, $revealed); "+select('FaBEVOPulseChoices($revealed, $x)','Add_action_as_defender',False)+"FaBEVOPulseDefend($victim, $chosen); }")
 if b=='meganetic_lockwave':add('PrepareCard',UID+"$maxX = FaBEVOMaxVariableCost($player, $uid); $x = await $player.NumberChoose(0, $maxX, \"Choose_X\"); FaBEVOSetX($uid, intval($x)); FaBFinishPreparedCard($uid);");add('ResolveCard',UID+select('FaBDYNHeroTargets($player)','Choose_hero',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); $x = intval(FaBARCCard($uid, 'evoX')); "+multi("FaBEVOEquipment($victim)",'$x','Choose_equipment',minimum='$maximum',p='$victim')+"$selected = $chosen === '-' ? '' : $chosen; "+select('$selected','Choose_required_equipment',False)+"FaBEVOLockwave($player, $chosen);")
 if b=='singularity':add('ResolveCard',UID+"FaBEVOSingularity($player, $uid);")
 if b=='teklovossen_the_mechropotent':
  add('PrepareCard',UID+multi('FaBMONSoul($player)',2,'Banish_two_soul_cards',2)+"FaBDTDBanishSoul($player, $chosen); FaBFinishPreparedCard($uid);")
  add('AttackDeclared',hit(select(ref('Hand',p='$victim'),'Discard_a_card',False,p='$victim')+"FaBDiscardChoice($victim, $chosen);"))
 if b=='dissolving_shield':add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1); FaBEVOEmptyShield($mzID);")
 if b=='fuel_injector':add('ResolveAbility',"AddResources($player, intval(GetResources($player)) + 1);")
 if b=='medkit':add('ResolveAbility',"FaBCRUGainLife($player, 2);")
 if b=='steam_canister':add('ResolveAbility',steam())
 if b.startswith('backup_protocol_'):
  add('ResolveAbility',select(f"FaBEVOBackup($player, {int(c['pitch'])})",'Recover_Mechanologist_attack')+"FaBMoveChoice($player, $chosen, 'Graveyard', 'Hand');")
 if b=='quantum_processor':add('ResolveAbility',item())
 if b=='prismatic_lens':add('ResolveAbility',"$pitch = FaBEVORevealTop($player); "+select('FaBEVOLens($player, $pitch)','Top_matching_item',False)+"FaBDTDTopChoice($player, $chosen);")
 if b=='grinding_gears':add('ResolveAbility',select('FaBDYNHeroTargets($player)','Choose_hero',False)+"FaBEVODestroyTop(intval(FaBIdentityFromMZ($chosen)['player']));")
 if b=='stasis_cell':
  a=[x for x in a if x['macroName']!='ResolveCard'];add('ResolveCard',"if (DecisionQueueController::GetVariable('evoEvent') !== 'stasis') { FaBARCEnterItem($player, FaBIdentityFromMZ($mzID)['object'], false); } "+select('FaBEVOEquipment($player, true)','Disable_equipment_activation',False)+"FaBEVOStasis($chosen);")
  add('ResolveAbility',select('FaBEVOEquipment($player, true)','Disable_equipment_defense',False)+"FaBEVOStasis($chosen, true);")
 if b in ['boom_grenade','tick_tock_clock']:
  body=UID+"$victim = intval(DecisionQueueController::GetVariable('evoVictim')); FaBMONDestroy($uid); "
  if b=='tick_tock_clock':body+=multi(ref('Arena','item',True),2,'Destroy_other_items')+"$n = 1 + count(FaBUPRUIDs($chosen)); FaBDYNDestroyUIDs(FaBUPRUIDs($chosen)); "+physical('$n')
  else:body+=physical(v+1)
  add('Hit',body)
 if b=='system_failure':add('ResolveCard',UID+select('FaBEVOSteamTargets($player, true)','Remove_steam_counters',False)+"$victim = intval(FaBIdentityFromMZ($chosen)['player']); $n = FaBEVORemoveSteam($chosen); if ($n >= 2) { "+physical(2)+' }')
 if b=='system_reset':add('ResolveCard',multi(ref('Arena','mechCheap'),99,'Reset_items')+"FaBEVOResetItems($player, $chosen);")
 if b=='adaptive_plating':add('ResolveAbility',UID+"$slot = await $player.Modal(1, 1, \"Head&Chest&Arms&Legs\", \"Choose_equipment_zone\"); FaBEVOModular($player, $uid, ['Head','Chest','Arms','Legs'][intval($slot)]);")
 if b=='fabricate':add('ResolveCard',UID+"$modes = await $player.Modal(2, 2, \"Equip_Proto&Empower_Evos&Put_under_Evo&Banish_Evo_and_draw\", \"Choose_two_modes\"); $modes = explode(',', $modes); for ($i = 0; $i < count($modes); $i = $i + 1) { $mode = intval($modes[$i]); if ($mode === 0) { "+select(ref('Inventory','proto'),'Equip_Proto',False)+"FaBEVOEquipProto($player, $chosen); } if ($mode === 1) { FaBEVOAdd($player, 'FABRICATE'); } if ($mode === 2) { "+select('FaBEVOEquipment($player, false, true)','Put_Fabricate_under_Evo',False)+"$parent = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID ?? 0); FaBEVOAttach($player, $parent, FaBDTDSource($uid)); } if ($mode === 3) { "+select(ref('Hand','evo'),'Banish_Evo')+"if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Hand', 'Banish'); DoDrawCard($player, 1); } } }")
 if b=='teklonetic_force_field':add('Defended',UID+"$s = FaBGetState(); $attack = FaBFindUID(intval($s['attackUID'])); if ($attack && FaBDYNOverpower($attack['object'], $s)) { FaBTagUID($uid, 'WTR_DEFENSE:2'); }")
 if b=='already_dead':add('Hit',hit("FaBDYNBanishTop($player, $victim, 1); "+select('FaBDefendingChoices($victim, false)','Banish_defending_card',False)+"FaBEVOContractBanish($player, $chosen);"))
 if b=='emboldened_blade':add('ResolveCard',select('FaBEVOFaceDownArsenals()','Turn_arsenal_face_up',False)+"FaBEVOEmbolden($player, $chosen);")
 if b=='intoxicating_shot':add('Hit',hit("FaBWTRCreateArena($victim, 'courage'); FaBWTRCreateArena($victim, 'quicken');"))
 if b=='slay':add('ResolveCard',select(ref('Arena','angel',True),'Destroy_angel',False)+"FaBDYNDestroyChoice($chosen);")
 if b=='smashing_performance':add('AttackDeclared',"DoDrawCard($player, 1); $discarded = FaBDiscardRandom($player, 1); if (count($discarded) > 0 && intval(CardPower(FaBFindUID(intval($discarded[0]))['object']->CardID)) >= 6) { FaBEVORandomItem(); }")
 if b in ['sonata_fantasmia','tectonic_rift']:
  add('PrepareCard',UID+"$maxX = FaBEVOMaxVariableCost($player, $uid); $x = await $player.NumberChoose(0, $maxX, \"Choose_X\"); FaBEVOSetX($uid, intval($x)); FaBFinishPreparedCard($uid);")
  code=UID+"$x = intval(FaBARCCard($uid, 'evoX')); "
  if b=='sonata_fantasmia':code+="FaBARCCreateRunes($player, $x); if ($x >= 6) { "+select('FaBDYNHeroTargets($player)','Choose_hero_to_discard',False)+"FaBDiscardRandom(intval(FaBIdentityFromMZ($chosen)['player']), 3); }"
  else:code+="for ($i = 0; $i < $x; $i = $i + 1) { FaBWTRCreateArena($player, 'seismic_surge'); }"
  add('ResolveCard',code)
 if b=='warband_of_bellona':add('ResolveAbility',"if (DecisionQueueController::GetVariable('evoEvent') === 'charge') { "+select(ref('Hand'),'Charge_soul')+"$yellow = FaBBoltynYellowChoice($chosen); FaBMONCharge($player, $chosen); if ($yellow) { DoDrawCard($player, 1); } } else { FaBEVOAdd($player, 'BELLONA'); }")
 if b=='wax_off':add('ResolveCard',"if (FaBEVOWaxOn($player)) { FaBWTRCreateArena($player, 'zen_state'); }")
 if b=='shriek_razors':
  add('StartTurn',UID+"if (count(FaBChoiceRefs($player, 'Arena', ['base'=>'silver'])) >= 2) { "+multi("implode('&', FaBChoiceRefs($player, 'Arena', ['base'=>'silver']))",2,'Destroy_two_Silvers_to_equip',0)+"FaBDYNReequip($player, $uid, $chosen); }")
  add('ResolveAbility',select('FaBEVOShriek()','Weaken_defending_attack',False)+"FaBDYNTag($chosen, 'WTR_DEFENSE:-1');")
 if b=='tome_of_imperial_flame':add('ResolveCard',"DoDrawCard($player, FaBDYNRoyal($player) ? 2 : 1); "+multi("implode('&', FaBChoiceRefs($player, 'Hand', ['pitch'=>1]))",2,'Pitch_two_red_cards',0)+"FaBEVOImperial($player, $chosen);")
 if b in ['big_shot','burn_rubber','heavy_artillery','hyper_x3','liquid_cooled_mayhem','mechanical_strength','meganetic_protocol','mini_forcefield','phantom_tidemaw','security_script','steel_street_enforcement','teklo_base_arms','teklo_base_chest','teklo_base_head','teklo_base_legs','teklo_leveler','contest_the_mindfield','dust_from_the_chrome_caverns']:handled=True
 if not handled:pending.append(id)
 out.append(dict(cardId=id,abilities=a))
if pending:print('Unhandled',pending);raise SystemExit(1)
for c in out:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior.get('previousCodeHash'):a['previousCodeHash']=prior['previousCodeHash']
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'evo_abilities.json').write_text(json.dumps(out,indent=2)+'\n');print('EVO:',len(out),'identities;',sum(len(c['abilities']) for c in out),'macros')
