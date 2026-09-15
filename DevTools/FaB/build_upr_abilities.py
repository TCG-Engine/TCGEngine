"""Uprising card authoring. Unhandled families fail the build."""
import ast,json,hashlib
from pathlib import Path
HERE=Path(__file__).parent
for filename in ['build_arc_abilities.py','build_mon_abilities.py','build_ele_abilities.py']:
 src=(HERE/filename).read_text(encoding='utf-8')
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and not (filename=='build_mon_abilities.py' and node.name=='damage_body'):exec(ast.get_source_segment(src,node))
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
def transform(dragon='aether_ashwing',count=1,invocation=False,optional=True):
 return f"$remaining = {count}; while ($remaining > 0) {{ "+choice("FaBUPRAsh($player)",'Transform_ash',optional and not invocation)+f" if ($chosen === 'PASS') {{ break; }} $dragonUID = FaBUPRTransform($player, $chosen, '{dragon}', "+('$uid' if invocation else '0')+"); $remaining = $remaining - 1; }"
def prepare_target(hero=False,opposing=False):
 expr='FaBARCHeroTargets($player, true)' if opposing else 'FaBUPRAnyTargets($player, '+('true' if hero else 'false')+')'
 return choice(expr,'Choose_damage_target',False)+" FaBUPRStoreTarget($uid, $chosen);"
def deal(n,targetUID='$targetUID',sourceUID='$uid',physical=False):
 code=f"$packet = {n}; $packetTarget = {targetUID}; $packetSource = {sourceUID}; $f = FaBFindUID($packetTarget); $dealt = 0; if ($f !== null && $packet > 0) {{ $victim = intval($f['player']); $isHero = $f['zone'] === 'Hero'; $packet = FaBUPRDamageBonus($player, $packetSource, $packet, "+("'PHYSICAL'" if physical else "'ARCANE'")+" ); $used = []; if ($isHero) { "
 if not physical:
  code+="$barriers = FaBUPRBarrierChoices($victim, $used, $packetSource); while ($packet > 0 && $barriers !== '') { $barrier = await $victim.MZMayChoose($barriers, \"Choose_arcane_barrier\"); if ($barrier === 'PASS') { break; } $barrierUID = intval(FaBIdentityFromMZ($barrier)['object']->UniqueID); $cost = FaBUPRBarrierValue($barrier); while (intval(GetResources($victim)) < $cost) { $pitchRefs = FaBARCPitchChoices($victim); $pitched = await $victim.MZChoose($pitchRefs, \"Pitch_for_arcane_barrier\"); FaBARCPitchForEffect($victim, $pitched); } AddResources($victim, intval(GetResources($victim)) - $cost); $prevented = min($packet, $cost); $packet = max(0, $packet - $cost); $used[] = $barrierUID; if (FaBUPRAlluvionReady($barrierUID, $prevented)) { $mode = await $victim.Modal(1, 1, \"Add_energy&Decline\", \"Alluvion_Constellas\"); FaBUPRAlluvion($barrierUID, $mode === '0'); } $barriers = FaBUPRBarrierChoices($victim, $used, $packetSource); } $voidRefs = FaBMONSpellvoidRefs($victim); while ($packet > 0 && $voidRefs !== '' && !FaBUPRUnpreventable($packetSource)) { $void = await $victim.MZMayChoose($voidRefs, \"Destroy_Spellvoid\"); if ($void === 'PASS') { break; } $packet = max(0, $packet - FaBMONSpellvoid($victim, $void)); $voidRefs = FaBMONSpellvoidRefs($victim); } "
 code+="$usedQuell = []; $quell = FaBUPRQuellChoices($victim, $usedQuell); while ($packet > 0 && $quell !== '' && !FaBUPRUnpreventable($packetSource)) { $item = await $victim.MZMayChoose($quell, \"Quell_damage\"); if ($item === 'PASS') { break; } $usedQuell[] = intval(FaBIdentityFromMZ($item)['object']->UniqueID); while (intval(GetResources($victim)) < 1) { $pitchRefs = FaBARCPitchChoices($victim); $pitched = await $victim.MZChoose($pitchRefs, \"Pitch_for_Quell\"); FaBARCPitchForEffect($victim, $pitched); } $packet = max(0, $packet - FaBUPRQuell($victim, $item)); $quell = FaBUPRQuellChoices($victim, $usedQuell); } } $dealt = FaBUPRDeal($player, $packetSource, $packetTarget, $packet, "+("'PHYSICAL'" if physical else "'ARCANE'")+"); }"
 return code
cards=json.loads((HERE/'upr_catalog.json').read_text(encoding='utf-8'));existing={}
for file in sorted(HERE.glob('*_abilities.json')):
 if not file.name.startswith('upr'):
  for c in json.loads(file.read_text(encoding='utf-8')):existing[c['cardId']]=c
snapshot=[];pending=[]
for c in cards:
 id=c['id'];b=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id;v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True;prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing and id!='red_hot_red':snapshot.append(existing[id]);continue
 if 'Ice Fusion' in c['functional_text_plain']:
  add('PrepareCard',UID+choice("FaBELESelect($player, 'Hand', 'Ice')",'Reveal_Ice_to_fuse')+" FaBELEFinishFusion($player, $uid, [$chosen], ['Ice']);")
 if b in ['aether_dart','aether_hail','aether_icevein','dampen','encase','freezing_point','frosting','ice_bolt','icebind','polar_cap','succumb_to_winter']:
  add('PrepareCard',('' if a else UID)+prepare_target(b=='freezing_point')+"FaBFinishPreparedCard($uid);")
  n=v+({'aether_hail':1,'aether_icevein':2,'dampen':1,'freezing_point':2,'ice_bolt':2,'polar_cap':1,'succumb_to_winter':2}.get(b,0))
  code=UID+"$targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); $f = FaBFindUID($targetUID); $target = intval($f['player'] ?? 0); $heroTarget = $f !== null && $f['zone'] === 'Hero'; $fused = FaBELEFused($uid, 'Ice'); $damage = "+str(n)+';'
  if b=='freezing_point':code+=" if ($fused) { $damage = $damage + FaBUPRIceCount($target); }"
  if b=='succumb_to_winter':code+=" if ($fused) { if ($heroTarget) { "+choice("FaBUPRFrozenArsenal($target)",'Destroy_frozen_arsenal',False)+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); } } elseif ($f !== null && FaBUPRFrozen($f['object'])) { FaBMONDestroy($targetUID); } }"
  code+=deal('$damage')
  if b=='dampen':code+="FaBWTRAddEffect($player, 'ARC_PREVENT', $dealt);"
  if b=='aether_icevein':code+="if ($heroTarget && $fused && $dealt > 0) { "+tax(2)+' }'
  if b=='encase':code+="if ($heroTarget && $fused && $dealt > 0) { FaBUPRFreezeHero($player, $target); }"
  if b=='icebind':code+="if ($heroTarget && $fused && $dealt > 0) { "+choice("FaBUPRFreezeChoices($target, 'Arsenal')",'Freeze_arsenal',False)+" if ($chosen !== 'PASS') { FaBUPRFreeze($player, $chosen); } }"
  if b=='polar_cap':code+="if ($heroTarget && $fused && $dealt > 0) { FaBELEFrost($target); }"
  add('ResolveCard',code)
 if b.startswith('invoke_'):
  add('PrepareCard',UID+choice("FaBUPRAsh($player)",'Choose_ash',False)+"FaBARCSetCard($uid, 'uprAshUID', intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$ash = FaBFindUID(intval(FaBARCCard($uid, 'uprAshUID'))); if ($ash !== null) { FaBUPRTransform($player, $ash['mzID'], '"+b[7:]+"', $uid); }")
 if b in ['critical_strike','dust_runner_outlaw','rebellious_rush','cinderskin_devotion','dromai','dromai_ash_artist','ash','aether_ashwing','storm_of_sandikai','iyslander_stormbind','dragons_of_legend','searing_emberblade','lava_burst','rise_up','spreading_flames','uprising','themai','miragai','nekria','yendurai','cromai','frost_hex','hypothermia','insidious_chill','channel_the_bleak_expanse','fog_down','sigil_of_protection','tiger_stripe_shuko','quelling_robe','quelling_sleeves','quelling_slippers','heat_wave','silken_form','conduit_of_frostburn','alluvion_constellas','flamescale_furnace','ghostly_touch','helios_mitre','sash_of_sandikai','spellfire_cloak','silent_stilettos','tide_flippers','waning_moon','glacial_horns','coronet_peak']:handled=True
 if b=='fai_rising_rebellion':snapshot.append(dict(cardId=id,abilities=existing['fai']['abilities']));continue
 if b=='blood_of_the_dracai':add('CardPitched',"FaBUPRAdd($player, 'BLOOD', 3);")
 if b in ['billowing_mirage','dustup','rake_the_embers','skittering_sands','ouvia','silken_form']:
  m={'billowing_mirage':'AttackDeclared','dustup':'Hit','ouvia':'StartTurn','silken_form':'ResolveAbility'}.get(b,'ResolveCard')
  code=("FaBWTRCreateArena($player, 'ash');" if b in ['dustup','rake_the_embers'] else '')+transform(count=v if b=='rake_the_embers' else 1,optional=b not in ['skittering_sands','silken_form'])
  if b=='rake_the_embers':
   code="FaBWTRCreateArena($player, 'ash'); $targets = FaBUPRAsh($player); $maximum = min("+str(v)+", count(array_filter(explode('&', $targets)))); if ($maximum > 0) { $chosen = await $player.MZMultiChoose($targets, 0, $maximum, \"Choose_ash_to_transform\"); $ashUIDs = FaBUPRUIDs($chosen); for ($i = 0; $i < count($ashUIDs); ++$i) { $ash = FaBFindUID($ashUIDs[$i]); if ($ash !== null) { FaBUPRTransform($player, $ash['mzID'], 'aether_ashwing'); } } }"
  if b=='skittering_sands':code+=f"if (isset($dragonUID) && $dragonUID > 0) {{ FaBTagUID($dragonUID, 'WTR_POWER:{v}'); }}"
  add(m,code)
 if b in ['dunebreaker_cenipai','embermaw_cenipai']:add('ResolveAbility',"FaBWTRCreateArena($player, 'ash');")
 if b=='sweeping_blow':add('AttackDeclared',"FaBWTRCreateArena($player, 'ash');")
 if b=='healing_balm':add('ResolveCard',f'FaBCRUGainLife($player, {v});')
 if b=='transmogrify':add('ResolveCard',f"FaBUPRAdd($player, 'TRANSMOGRIFY', {v+5});")
 if b in ['brothers_in_arms','flex']:
  code=UID+pay(1 if b=='brothers_in_arms' else 2)+" if ($paid) { FaBTagUID($uid, '"+('WTR_DEFENSE:2' if b=='brothers_in_arms' else 'WTR_POWER:2')+"'); }"
  add('Defended',code)
  if b=='flex':add('AttackDeclared',code)
 if b=='fyendals_fighting_spirit':
  for m in ['AttackDeclared','Defended']:add(m,"if (FaBARCLowerLife($player)) { FaBCRUGainLife($player, 1); }")
 if b=='burn_away':add('PrepareCard',UID+choice('FaBFaiFlames($player)','Banish_Phoenix_Flame')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Graveyard', 'Banish'); FaBTagUID($uid, 'WTR_POWER:2'); FaBTagUID($uid, 'GO_AGAIN'); } FaBFinishPreparedCard($uid);")
 if b in ['flameborn_retribution','inflame','stoke_the_flames']:
  code=UID+choice('FaBFaiFlames($player)','Return_Phoenix_Flame')+" if ($chosen !== 'PASS') { FaBFaiReturnFlame($player, $chosen); "+("FaBTagUID($uid, 'GO_AGAIN');" if b=='stoke_the_flames' else '')+' }'
  if b=='flameborn_retribution':code="if (FaBELECount($player, 'DAMAGED')) { "+code+' }'
  if b=='inflame':code="if (FaBUPRRed($player, 2)) { "+code+' }'
  add({'flameborn_retribution':'Defended','inflame':'AttackDeclared','stoke_the_flames':'Hit'}[b],code)
 if b=='flamecall_awakening':add('AttackDeclared',"if (FaBUPRRed($player, 2) && !FaBUPRBleak()) { "+choice("implode('&', FaBChoiceRefs($player, 'Deck', ['base'=>'phoenix_flame']))",'Find_Phoenix_Flame')+" if ($chosen !== 'PASS') { FaBRevealChoices($player, $chosen); FaBMoveChoice($player, $chosen, 'Deck', 'Hand'); } FaBShuffleDeck($player); }")
 if b in ['mounting_anger','soaring_strike']:
  add('Hit',choice("FaBUPRSmallHand($player)",'Banish_attack_to_play')+" if ($chosen !== 'PASS') { $o = FaBMoveChoice($player, $chosen, 'Hand', 'Banish'); if ($o !== null) { $o->PlayableFromBanish = 1; FaBTagUID(intval($o->UniqueID), '"+('WTR_POWER:1' if b=='mounting_anger' else 'GO_AGAIN')+"'); } }")
 if b=='engulfing_flamewave':add('Hit',"FaBUPREngulf($player);")
 if b=='take_the_tempo':add('Hit',"if (intval(FaBGetState()['chainHits'] ?? 0) >= 3) { FaBUPRTempo($player); }")
 if b=='vipox':add('Hit',hit('FaBARCLoseLife($target, FaBHandCount($target), $player);'))
 if b=='erase_face':add('Hit',hit("FaBWTRAddEffect($target, 'UPR_ERASE', 1, ['expiresAfterTurnOf'=>$target], true);"))
 if b=='tome_of_firebrand':add('ResolveCard','DoDrawCard($player, 2);')
 if b=='sift':add('ResolveCard',f"$maximum = min({v+1}, FaBHandCount($player)); $targets = implode('&', FaBChoiceRefs($player, 'Hand')); if ($maximum > 0) {{ $chosen = await $player.MZMultiChoose($targets, 0, $maximum, \"Bottom_cards_then_draw\"); $count = FaBUPRBottomHand($player, $chosen); DoDrawCard($player, $count); }}")
 if b=='trade_in':add('AttackDeclared',choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Discard_and_draw')+" if ($chosen !== 'PASS') { FaBDiscardChoice($player, $chosen); DoDrawCard($player, 1); }")
 if b in ['rapid_reflex','tide_flippers','combustion_point','liquefy','semblance']:
  kind={'rapid_reflex':'ZERO','tide_flippers':'SMALL','combustion_point':'DRACONIC_NINJA','liquefy':'AA','semblance':'ILLUSION'}[b]
  tag={'rapid_reflex':f'WTR_POWER:{v}','tide_flippers':'GO_AGAIN','combustion_point':'WTR_POWER:1','liquefy':'UPR_LIQUEFY','semblance':'MON_NO_PHANTASM'}[b]
  code=choice(f"FaBUPRAttackChoices($player, '{kind}')",'Choose_attack',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), '{tag}'); }}"
  if b=='liquefy':code="if (intval(FaBGetState()['chainLink']) >= 4) { "+code+' }'
  if b=='combustion_point':code+=choice("FaBUPRCombustion($player)",'Banish_defending_card')+" if ($chosen !== 'PASS') { $f = FaBIdentityFromMZ($chosen); FaBMoveUID(intval($f['object']->UniqueID), 'Banish', $f['player']); }"
  add('ResolveAbility' if b=='tide_flippers' else 'ResolveCard',code)
 if b=='sand_cover':add('ResolveCard',choice('FaBUPRAsh($player)','Give_ash_ward',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'UPR_WARD:{v+1}'); }}")
 if b in ['alluvion_constellas','conduit_of_frostburn','heat_wave','sash_of_sandikai','spellfire_cloak','flamescale_furnace','ghostly_touch']:
  code={'alluvion_constellas':"FaBUPRAdd($player, 'STAFF_DISCOUNT', 3);",'conduit_of_frostburn':"$target = intval(DecisionQueueController::GetVariable('uprVictim')); if ($target > 0) { "+choice('FaBUPRFrozenArsenal($target)','Destroy_frozen_arsenal',False)+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); } } else { FaBUPRAdd($player, 'FROSTBURN'); }",'heat_wave':"FaBUPRAdd($player, 'HEAT');",'sash_of_sandikai':"AddResources($player, intval(GetResources($player)) + 1);",'spellfire_cloak':"AddResources($player, intval(GetResources($player)) + 1);",'flamescale_furnace':"AddResources($player, intval(GetResources($player)) + count(FaBChoiceRefs($player, 'Pitch', ['pitch'=>1])));",'ghostly_touch':"FaBUPRGhost($player, $mzID);"}[b];add('ResolveAbility',code)
 if b=='silent_stilettos':add('ResolveAbility',UID+pay(3)+" if ($paid && FaBFindUID($uid) !== null) { FaBMONDestroy($uid); AddActionPoints($player, intval(GetActionPoints($player)) + 1); }")
 if b=='coronet_peak':add('ResolveAbility',target()+tax(1))
 if b=='arctic_incarceration':add('ResolveCard',target()+f"FaBELEFrost($target, {v});")
 if b=='isenhowl_weathervane':add('ResolveCard',f"FaBUPRAdd($player, 'WEATHERVANE', {v+1});")
 if b=='sigil_of_permafrost':add('PrepareCard','FaBFinishPreparedCard($uid);');add('ResolveCard',fused("FaBUPRAdd($player, 'PERMAFROST');",'Ice'))
 if b=='frightmare':handled=True
 if b=='uprising':add('ResolveCard',"FaBUPRAdd($player, 'UPRISING', 4);")

 if b in ['ronin_renegade']:handled=True
 if b=='rise_from_the_ashes':add('ResolveCard',f"FaBWTRAddEffect($player, 'FAI_RISE', {v});"+choice('FaBFaiFlames($player)','Return_Phoenix_Flame')+" if ($chosen !== 'PASS') { FaBFaiReturnFlame($player, $chosen); }")
 if b=='rising_resentment':add('Hit',choice('FaBUPRSmallHand($player)','Banish_attack_to_play')+" if ($chosen !== 'PASS') { FaBFaiBanishAttack($player, $chosen, true); }")
 if b=='crown_of_providence':add('Defended',choice("implode('&', array_merge(FaBChoiceRefs($player, 'Hand'), FaBChoiceRefs($player, 'Arsenal')))",'Bottom_card_then_draw')+" if ($chosen !== 'PASS') { FaBUPRBottom($chosen); DoDrawCard($player, 1); }")
 if b=='that_all_you_got':add('Defended',UID+"if (FaBAttackPower(FaBGetState()) <= 2) { FaBTagUID($uid, 'UPR_CLOSE_DRAW'); }")
 if b=='cold_snap':add('ResolveCard',UID+"$arsenal = DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal';"+target()+pay(v,'$target')+"if (!$paid) { "+choice('FaBUPRFreezeChoices($target)','Freeze_card',False)+" if ($chosen !== 'PASS') { FaBUPRFreeze($player, $chosen); } } if ($arsenal) { DoDrawCard($player, 1); }")
 if b=='brain_freeze':
  add('PrepareCard',prepare_target(True,True)+"FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$target = intval(FaBARCCard($uid, 'target')); if (!FaBUPRBleak()) { $hand = implode('&', FaBChoiceRefs($target, 'Hand')); FaBRevealChoices($target, $hand); if (FaBELEFused($uid, 'Ice')) { "+choice(f"FaBARCSelect($target, 'Hand', '', 'Action', {v-1})",'Topdeck_action',False)+" if ($chosen !== 'PASS') { FaBUPRTop($chosen); } } }")
 if b=='ice_eternal':
  add('PrepareCard',"$options = FaBEVRNumbers(intdiv(FaBAvailablePitch($player, $uid), 2)); $x = await $player.Modal(1, 1, $options, \"Choose_X_pay_twice_X\"); FaBUPRSetX($uid, intval($x));"+prepare_target(True)+"FaBFinishPreparedCard($uid);")
  add('ResolveCard',UID+"$target = intval(FaBARCCard($uid, 'target')); $targetUID = intval(FaBARCCard($uid, 'uprTargetUID')); FaBELEFrost($target, intval(FaBARCCard($uid, 'uprX'))); if (FaBELEFused($uid, 'Ice')) { $damage = count(FaBMONArena($target, 'frostbite')); "+deal('$damage')+' }')
 if b in ['hypothermia','frost_hex']:add('ResolveCard',UID+choice('FaBARCHeroTargets($player, true)','Choose_afflicted_hero',False)+"FaBUPRAfflict($player, $uid, $chosen);")
 if b=='frost_hex':add('ResolveAbility',UID+"$targetUID = intval(FaBIdentityFromMZ(FaBChoiceRefs($player, 'Hero')[0])['object']->UniqueID);"+deal(1))
 if b=='insidious_chill':
  add('ResolveCard',UID+"FaBSetObjectCounter(FaBFindUID($uid)['object'], 'FROST', 3);")
  add('ResolveAbility',target()+tax(2))
 if b=='isenhowl_weathervane':add('ResolveAbility',"$count = intval(DecisionQueueController::GetVariable('uprFrostCount'));"+target()+"FaBELEFrost($target, $count);")
 if b=='channel_the_bleak_expanse':add('StartTurn',UID+"$n = FaBELEFlow($uid); $refs = FaBELESelect($player, 'Pitch', 'Ice'); $chosen = 'PASS'; if (count(array_filter(explode('&', $refs))) >= $n) { $chosen = await $player.MZMultiChoose($refs, $n, $n, \"Bottom_Ice_to_keep_channel\"); } FaBELEChannelPay($player, $uid, $chosen, $n);")
 if b=='read_the_ripples':add('StartTurn',UID+"FaBMONDestroy($uid);"+f"for ($i = 0; $i < {v}; ++$i) {{ "+opt(1)+" } DoDrawCard($player, 1);")
 if b=='strategic_planning':add('ResolveCard',choice(f"FaBUPRGraveActions($player, {v-1})",'Bottom_action',False)+"if ($chosen !== 'PASS') { FaBUPRBottom($chosen); } FaBUPRAdd($player, 'STRATEGIC');")
 if b=='rewind':add('ResolveCard',choice('FaBUPRRewindTargets($player)','Negate_non_attack_action',False)+"if ($chosen !== 'PASS') { FaBUPRRewind($chosen); }")
 if b=='tome_of_duplicity':add('ResolveCard',"$uids = FaBARCStageTop($player, 2);"+choice("FaBEVRUIDRefs($uids)",'Banish_card',False)+"if ($chosen !== 'PASS') { FaBUPRDuplicity($player, $chosen); } FaBUPRRestoreTop($player, $uids);")
 if b=='thaw':add('StartTurn',UID+"$mode = await $player.Modal(1, 1, \"Decline&Destroy_Frostbite&Destroy_Ice_affliction&Unfreeze_card\", \"Banish_Thaw\"); if ($mode !== '0') { FaBMoveUID($uid, 'Banish', $player); "+choice('FaBUPRThawTargets($player, intval($mode))','Choose_card',False)+"if ($chosen !== 'PASS') { FaBUPRThaw($chosen, intval($mode)); } }")
 if b in ['oasis_respite','helios_mitre']:
  code=(target() if b=='oasis_respite' else '$target = $player;')+choice('FaBEVRSources()','Choose_damage_source',False)+f"FaBUPRProtect($target, $chosen, {v+1 if b=='oasis_respite' else 1});"
  if b=='oasis_respite':code+="if (FaBUPRLowestLife($target)) { $mode = await $target.Modal(1, 1, \"Gain_life&Decline\", \"Gain_one_life\"); if ($mode === '0') { FaBCRUGainLife($target, 1); } }"
  else:code+="FaBUPRDestroyAtEnd($player, 'helios_mitre');"
  add('ResolveCard' if b=='oasis_respite' else 'ResolveAbility',code)
 if b=='glacial_horns':add('ResolveAbility',choice('FaBARCHeroTargets($player, false, true)','Choose_hero',False)+"$target = intval(FaBIdentityFromMZ($chosen)['player']);"+choice("FaBUPRFreezeChoices($target, 'Arsenal')",'Freeze_arsenal')+"if ($chosen !== 'PASS') { FaBUPRFreeze($player, $chosen); }"+choice("FaBUPRFreezeChoices($target, 'Ally')",'Freeze_ally')+"if ($chosen !== 'PASS') { FaBUPRFreeze($player, $chosen); }")
 if b=='waning_moon':add('ResolveAbility',UID+target()+"$targetUID = intval(FaBIdentityFromMZ($hero)['object']->UniqueID); $damage = $player === intval(GetTurnPlayer()) ? 2 : 3;"+deal('$damage'))
 if b=='searing_touch':add('AttackDeclared',UID+"if (intval(FaBGetState()['chainLink']) >= 4) { "+choice('FaBUPRAnyTargets($player)','Choose_damage_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);"+deal(2,physical=True)+' }')
 if b=='singe':add('ResolveCard',UID+target()+"$targets = [intval(FaBIdentityFromMZ($hero)['object']->UniqueID)]; $refs = FaBUPRFreezeChoices($target, 'Ally'); $maximum = min("+str(v)+", count(array_filter(explode('&', $refs)))); if ($maximum > 0) { $chosen = await $player.MZMultiChoose($refs, 0, $maximum, \"Choose_allies\"); $targets = array_merge($targets, FaBUPRUIDs($chosen)); } for ($i = 0; $i < count($targets); ++$i) { $targetUID = $targets[$i]; "+deal(1)+' }')
 if b=='burn_them_all':
  add('ResolveAbility',UID+"$targets = FaBOpponents($player); for ($i = 0; $i < count($targets); ++$i) { $targetUID = FaBUPRHeroUID($targets[$i]); "+deal(1)+' }')
  add('StartTurn',UID+"$n = FaBUPRRaze($uid); $refs = implode('&', FaBChoiceRefs($player, 'Graveyard', ['pitch'=>1])); $chosen = 'PASS'; if (count(array_filter(explode('&', $refs))) >= $n) { $mode = await $player.Modal(1, 1, \"Destroy_aura&Banish_red_cards\", \"Keep_Burn_Them_All\"); if ($mode === '1') { $chosen = await $player.MZMultiChoose($refs, $n, $n, \"Banish_red_cards\"); } } FaBUPRPayRaze($player, $uid, $chosen, $n);")
 if b=='azvolai':add('AttackDeclared',UID+"$refs = FaBUPRAnyTargets($player, false, true); $maximum = min(2, count(array_filter(explode('&', $refs)))); $chosen = await $player.MZMultiChoose($refs, 0, $maximum, \"Choose_up_to_two_targets\"); $targets = FaBUPRUIDs($chosen); for ($i = 0; $i < count($targets); ++$i) { $targetUID = $targets[$i]; "+deal(1)+' }')
 if b in ['dominia','tomeltai','dracona_optimai']:
  n={'dominia':1,'tomeltai':2,'dracona_optimai':3}[b]
  code=UID+"if (FaBUPRAttackingHero()) { $target = intval(FaBGetState()['defender']); $red = FaBUPRRevealRed($player, "+str(n)+"); if ($red > 0) { "
  if b=='dominia':code+="$copies = FaBEVRPrivateHand($player, $target);"+choice('FaBEVRUIDRefs($copies)','Banish_from_their_hand',False)+"FaBUPRBanishHandCopy($chosen, $target); FaBEVRForgetHand($copies);"
  if b=='tomeltai':code+=choice("implode('&', FaBChoiceRefs($target, 'Equipment'))",'Weaken_equipment',False)+"FaBUPRWeaken($chosen, $red);"
  if b=='dracona_optimai':code+=choice("FaBUPRControlledTargets($target)",'Choose_damage_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); $damage = 2 * $red;"+deal('$damage')
  add('AttackDeclared',code+' } }')
 if b=='vynserakai':add('Hit',hit(UID+"$targetUID = FaBUPRHeroUID($target);"+deal(3)))
 if b=='kyloria':add('Hit',hit(choice("implode('&', FaBChoiceRefs($target, 'Arena', ['type'=>'Item']))",'Gain_control_of_item',False)+"if ($chosen === 'PASS') { DoDrawCard($player, 1); } else { FaBUPRSteal($player, $chosen); }"))

 if b=='red_hot':add('AttackDeclared',UID+"if (intval(FaBGetState()['chainLink']) >= 4) { $damage = FaBUPRRevealRed($player, FaBFaiChainCount($player)); "+choice('FaBUPRAnyTargets($player)','Choose_damage_target',False)+"$targetUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID);"+deal('$damage',physical=True)+" FaBShuffleDeck($player); }")
 if b=='liquefy':add('ResolveAbility',"$target = intval(DecisionQueueController::GetVariable('uprVictim'));"+choice("implode('&', FaBChoiceRefs($target, 'Equipment'))",'Weaken_equipment',False)+"FaBUPRWeaken($chosen, 1);")

 if b=='quelling_robe':add('ResolveAbility',UID+"$remaining = max(0, FaBAttackPower(FaBGetState()) - FaBDefenseValue(FaBGetState())); $used = []; $refs = FaBUPRQuellChoices($player, $used); while ($remaining > 0 && $refs !== '') { $item = await $player.MZMayChoose($refs, \"Quell_combat_damage\"); if ($item === 'PASS') { break; } $used[] = intval(FaBIdentityFromMZ($item)['object']->UniqueID); while (intval(GetResources($player)) < 1) { $pitchRefs = FaBARCPitchChoices($player); $pitched = await $player.MZChoose($pitchRefs, \"Pitch_for_Quell\"); FaBARCPitchForEffect($player, $pitched); } $prevented = FaBUPRQuell($player, $item); $remaining = $remaining - $prevented; FaBUPRProtect($player, FaBFindUID($uid)['mzID'], $prevented); $refs = FaBUPRQuellChoices($player, $used); } if (DecisionQueueController::GetVariable('uprResumeDamage')) { FaBBeginDamageStep(); }")
 if not handled:pending.append(id)
 snapshot.append(dict(cardId=id,abilities=a))
old={c['cardId']:c for c in json.loads((HERE/'upr_abilities.json').read_text(encoding='utf-8'))} if (HERE/'upr_abilities.json').exists() else {}
for c in snapshot:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'upr_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
print('UPR identities:',len(snapshot),'Unhandled:',len(pending));print('\n'.join(pending))
if pending:raise SystemExit(1)
