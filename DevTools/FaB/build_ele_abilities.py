"""Author ELE card macros. Explicit coverage: unknown families fail the build."""
import ast, json, hashlib
from pathlib import Path
HERE=Path(__file__).parent
# Reuse the established formatting and choice helpers, without running another set builder.
for name in ['build_arc_abilities.py','build_mon_abilities.py']:
 src=(HERE/name).read_text(encoding='utf-8')
 for node in ast.parse(src).body:
  if isinstance(node,ast.FunctionDef) and node.name in ['clean','choice','multi','damage_body']:
   if node.name=='damage_body' and name.startswith('build_mon'):continue
   exec(ast.get_source_segment(src,node))
cards=json.loads((HERE/'ele_catalog.json').read_text(encoding='utf-8'))
previous=json.loads((HERE/'ele_abilities.json').read_text(encoding='utf-8')) if (HERE/'ele_abilities.json').exists() else []
existing={}
for name in ['wtr','arc','cru','mon','fai','professor','ira','boltyn','levia','prism']:
 for c in json.loads((HERE/(name+'_abilities.json')).read_text(encoding='utf-8')):existing[c['cardId']]=c
snapshot=[];pending=[]
UID="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);"
def pay(cost,who='$player'):
 return f"$paid = false; if (FaBAvailablePitch({who}) >= {cost}) {{ $payMode = await {who}.Modal(1, 1, \"Decline&Pay_resources\", \"Pay_resources\"); if ($payMode === '1') {{ while (intval(GetResources({who})) < {cost}) {{ $pitchRefs = FaBARCPitchChoices({who}); $pitchChoice = await {who}.MZChoose($pitchRefs, \"Pitch_to_pay\"); FaBARCPitchForEffect({who}, $pitchChoice); }} AddResources({who}, intval(GetResources({who})) - {cost}); $paid = true; }} }}"
def discard(n,who='$target'):
 return f"$maximum = min({n}, FaBHandCount({who})); if ($maximum > 0) {{ $handRefs = implode('&', FaBChoiceRefs({who}, 'Hand')); $discarded = await {who}.MZMultiChoose($handRefs, $maximum, $maximum, \"Discard_cards\"); FaBMoveChoices({who}, $discarded, 'Hand', 'Graveyard'); }}"
def tax(n):return pay(n,'$target')+' if (!$paid) { '+discard(1)+' }'
def target(opposing=False):
 return choice('FaBARCHeroTargets($player, '+('true' if opposing else 'false')+')','Choose_hero',False,var='$hero')+" $target = FaBARCTargetSeat($player, $hero, "+('true' if opposing else 'false')+');'
def arcane(n,t=None):
 code=UID+' $caster = $player; '
 code+=target() if t is None else '$target = '+t+';'
 code+=f"$damage = FaBELEDamageBonus($caster, FaBFindUID($uid)['mzID'] ?? '', {n}, 'ARCANE');"
 body=damage_body().replace('$dealt = FaBARCDealArcane($player, $target, $damage, $payment);',"$voidRefs = FaBMONSpellvoidRefs($target); while ($damage > $payment && $voidRefs !== '') { $void = await $target.MZMayChoose($voidRefs, \"Destroy_Spellvoid_to_prevent_arcane_damage\"); if ($void === 'PASS') { break; } $damage = max(0, $damage - FaBMONSpellvoid($target, $void)); $voidRefs = FaBMONSpellvoidRefs($target); } $dealt = FaBELEDealArcane($caster, $target, $damage, $payment, $uid);")
 return code+body+' $player = $caster;'
def eff(k,n=1):return f"FaBELEAdd($player, '{k}', {n});"
def nxt(k,n=0,tags=()):return f"FaBWTRAddEffect($player, 'ELE_NEXT_{k}', {n}, ['tags'=>["+','.join(repr(t) for t in tags)+']]);'
def fused(code,e=''):return f"if (FaBELEFused(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), '{e}')) {{ {code} }}"
def hit(code):return 'if (FaBFaiHeroHit()) { $target = intval(FaBGetState()[\'defender\']); '+code+' }'
def reload():return "if (FaBELEArsenalSpace($player)) { "+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Reload')+" if ($chosen !== 'PASS') { FaBARCLoadArsenal($player, $chosen, false); } }"
def retrieve(elements,kind='Action',dest='Hand',maxcost=999):
 return choice(f"FaBELESelect($player, 'Graveyard', '{elements}', '{kind}', {maxcost})")+f" if ($chosen !== 'PASS') {{ FaBMoveChoice($player, $chosen, 'Graveyard', '{dest}'); }}"
for c in cards:
 id=c['id'];base=id.rsplit('_',1)[0] if id.endswith(('_red','_yellow','_blue')) else id
 base='deep_blue' if id=='deep_blue' else base
 v=4-int(c['pitch'] or 0);a=[];handled=False
 def add(m,code):
  global handled
  handled=True
  prior=next((x for x in a if x['macroName']==m),None)
  if prior:prior['abilityCode']+='\n'+clean(code)
  else:a.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
 if id in existing:snapshot.append(existing[id]);continue
 text=c['functional_text_plain'];fusion=text.split(' Fusion')[0] if ' Fusion' in text else ''
 if fusion:
  elements=[e for e in ['Earth','Ice','Lightning'] if e in fusion];both='and/or' not in fusion and len(elements)>1
  code=UID
  if both:
   condition=' && '.join(f"FaBELESelect($player, 'Hand', '{e}') !== ''" for e in elements)
   code+=f" $fuseRefs = []; if ({condition}) {{ "+choice(f"FaBELESelect($player, 'Hand', '{elements[0]}')",'Reveal_to_fuse',var='$first')+" if ($first !== 'PASS') { $fuseRefs[] = $first; "+choice(f"FaBELESelect($player, 'Hand', '{elements[1]}')",'Reveal_to_fuse',False,var='$second')+" $fuseRefs[] = $second; } }"
  else:
   code+=' $fuseRefs = [];'
   for e in elements:code+=choice(f"FaBELESelect($player, 'Hand', '{e}')",'Reveal_'+e+'_to_fuse')+' $fuseRefs[] = $chosen;'
  code+=" FaBELEFinishFusion($player, $uid, $fuseRefs, ["+','.join(repr(e) for e in elements)+"]); FaBFinishPreparedCard($uid);"
  add('PrepareCard',code)
 # Explicit shared-rule / vanilla families.
 if base in ['autumns_touch','heavens_claws','winters_grasp','rotten_old_buckler','frostbite','embodiment_of_earth','embodiment_of_lightning','briar','briar_warden_of_thorns','burgeoning','lightning_surge','evergreen','ball_lightning','new_horizon','titans_fist','duskblade','channel_mount_heroic','channel_lake_frigid','channel_thunder_steppe','spellbound_creepers','mark_of_lightning','rampart_of_the_rams_head','amulet_of_earth','amulet_of_ice','amulet_of_lightning','sting_of_sorcery']:
  handled=True
 if base in ['entwine_earth','stir_the_wildwood','oaken_old','frost_lock']:add('CardPlayed',fused("FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_POWER:"+('1' if base=='frost_lock' else '2')+"');"))
 if base in ['entwine_ice','flake_out','glacial_footsteps','oaken_old']:add('CardPlayed',fused("FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'DOMINATE');"))
 if base in ['dazzling_crescendo','entwine_lightning']:add('CardPlayed',fused("FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'GO_AGAIN');"))
 if base=='turn_timber':add('CardPlayed',fused("FaBTagUID(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'WTR_DEFENSE:2');"))
 if base in ['cold_wave','frost_lock']:add('CardPlayed',eff('TAX') if base=='frost_lock' else fused(eff('TAX')))
 if base in ['blizzard_bolt','buzz_bolt','chilling_icevein','frazzle']:add('CardPlayed',fused(eff({'blizzard_bolt':'BLIZZARD','buzz_bolt':'BUZZ','chilling_icevein':'ICEVEIN','frazzle':'FRAZZLE'}[base])))
 if base in ['arcanic_shockwave','rites_of_lightning','blossoming_spellblade']:add('AttackDeclared',fused(arcane(1)))
 if base in ['explosive_growth','singeing_steelblade']:add('AttackDeclared',arcane(1))
 if base=='rosetta_thorn':add('AttackDeclared','if (FaBELEBothActions($player)) { '+arcane(2)+' }')
 if base=='sigil_of_suffering':add('ResolveCard',arcane(1,"intval(FaBGetState()['attacker'])"))
 if base=='inspire_lightning':add('ResolveCard',fused(arcane(v)))
 if base=='flicker_wisp':add('ResolveCard',fused(eff('FLICKER'))+arcane(1))
 if base=='sting_of_sorcery':add('ResolveAbility',arcane(1))
 if base=='chilling_icevein':add('ResolveAbility',"$target = intval(DecisionQueueController::GetVariable('eleTarget')); $repeats = intval(DecisionQueueController::GetVariable('eleRepeats')); for ($i = 0; $i < $repeats; ++$i) { "+tax(1)+' }')
 if base=='entangle':add('Hit',hit(fused("FaBWTRAddEffect($target, 'ELE_ENTANGLE', 1, ['expiresAfterTurnOf'=>$target], true);")))
 if base=='endless_winter':add('Hit',hit("FaBWTRAddEffect($target, 'ELE_ENDLESS', 1, ['expiresAfterTurnOf'=>$target]);"))
 if base=='frost_lock':add('Hit',hit(fused("FaBWTRAddEffect($target, 'ELE_NO_ZERO', 1, ['expiresAfterTurnOf'=>$target]);")))
 if base in ['icy_encounter','snow_under']:add('Hit',hit('FaBELEFrost($target);' if base=='icy_encounter' else fused('FaBELEFrost($target);')))
 if base=='frost_fang':add('Hit',hit(tax(2)))
 if base=='biting_gale':add('ResolveCard',fused("$target = intval(FaBGetState()['attacker']);"+tax(2)))
 if base=='mulch':add('Hit',hit(fused(choice("implode('&', FaBChoiceRefs($target, 'Arsenal'))",'Put_arsenal_on_bottom',False,chooser='$target')+" if ($chosen !== 'PASS') { FaBMoveChoice($target, $chosen, 'Arsenal', 'Deck'); }")))
 if base=='oaken_old':add('Hit',hit(fused("$uids = FaBELERandomHand($target, 2); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Bottom'); $order = await $target.Rearrange($orderParam); FaBARCFinishOrder($target, $uids, $order); }")))
 if base=='light_it_up':add('Hit',hit(fused("DoDamage($player, $mzID, $target, count(FaBChoiceRefs($target, 'Equipment')), 'PHYSICAL');")))
 if base=='boltn_shot':add('Hit',hit("if (FaBAttackPower(FaBGetState()) > intval(CardPower('"+id+"'))) { "+reload()+' }'))
 if base=='thump':
  add('DominateModifier',"return FaBAttackPower(FaBGetState()) > intval(CardPower($subjectObj->CardID)) ? 1 : 0;")
  add('Hit',hit("if (FaBAttackPower(FaBGetState()) > intval(CardPower('"+id+"'))) { "+discard(1)+' }'))
 if base in ['electrify','polar_blast']:add('ResolveCard',"if (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') { DoDrawCard($player, 1); }")
 if base=='electrify':add('ResolveCard',eff('ELECTRIFY',v))
 if base=='ice_quake':add('ResolveCard',nxt('ATTACK',v)+eff('QUAKE'))
 if base=='chill_to_the_bone':add('ResolveCard',eff('CHILL',v))
 if base=='earthlore_surge':add('ResolveCard',nxt('AA',v+2))
 if base=='invigorate':add('ResolveCard',nxt('FUSED',v+1))
 if base=='flash':add('ResolveCard',eff('NEXT_ACTION_GO'))
 if base=='blink':add('ResolveCard','AddActionPoints($player, intval(GetActionPoints($player)) + 1);')
 if base=='rejuvenate':add('ResolveCard',f'FaBCRUGainLife($player, {v});')
 if base=='force_of_nature':add('ResolveCard',eff('FORCE')+fused(nxt('ATTACK',1)))
 if base.startswith('weave_'):add('ResolveCard',f"FaBELEAdd($player, 'WEAVE_{base[6:].title()}', {v});")
 if base=='bramble_spark':add('ResolveCard',nxt('AA',0,['ELE_ARCANE:1'])+fused(nxt('AA',v)))
 if base=='ice_storm':add('ResolveCard',nxt('ARROW',3)+fused(nxt('ARROW',0,['ELE_ICE_STORM','ELE_HIT_DAMAGE:1'])))
 if base=='seek_and_destroy':add('ResolveCard',nxt('ARROW',3,['ELE_SEEK_DESTROY']))
 if base=='tear_asunder':
  add('ResolveCard',nxt('GUARDIAN',1,['DOMINATE','ELE_TEAR']))
  add('ResolveAbility',"$target = intval(FaBGetState()['defender']);"+discard(2))
 if base=='over_flex':add('ResolveCard',nxt('ARROW',v+1)+reload())
 if base=='pulse_of_volthaven':add('ResolveCard',nxt('ELEMENT',4))
 if base=='pulse_of_isenloft':add('ResolveCard',eff('PULSE_DEFENSE'))
 if base=='fulminate':add('ResolveCard',fused(eff('AA_POWER',3),'Earth')+fused(eff('AA_GO'),'Lightning'))
 if base=='flashfreeze':
  add('ResolveCard',fused(eff('FLASH_ICE'),'Ice')+fused(eff('FLASH_LIGHTNING',3),'Lightning'))
  add('ResolveAbility',UID+"$target = intval(FaBGetState()['defender']);"+pay(2,'$target')+" if (!$paid) { FaBTagUID($uid, 'DOMINATE'); }")
 if base in ['winters_bite','polar_blast']:
  add('ResolveCard',target(base=='polar_blast')+(tax(v) if base=='winters_bite' else pay(v,'$target')+" if (!$paid) { "+nxt('ATTACK',0,['DOMINATE'])+' }'))
 if base=='blizzard':add('ResolveCard',UID+"$attackUID = intval(FaBGetState()['attackUID']); $target = intval(FaBGetState()['attacker']);"+pay(2,'$target')+" if (!$paid) { FaBTagUID($attackUID, 'ELE_NO_GO'); }")
 if base=='lightning_press':add('ResolveCard',choice("FaBELECombatChoices('AA', '', 1)",'Empower_attack',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'WTR_POWER:{v}'); }}")
 if base=='summerwood_shelter':add('ResolveCard',choice("FaBELECombatChoices('DEFENSE', 'Earth|Elemental')",'Empower_defense',False)+f" if ($chosen !== 'PASS') {{ FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'WTR_DEFENSE:{v+1}'); }}")
 if base in ['emerging_avalanche','strength_of_sequoia']:add('ResolveCard',fused(target()+"FaBELEFrost($target);" if base=='emerging_avalanche' else "FaBWTRCreateArena($player, 'seismic_surge');"))
 if base=='embolden':add('ResolveCard',"if (FaBELEOtherAura($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID))) { DoDrawCard($player, 1); }")
 if base=='break_ground':add('AttackDeclared',choice("implode('&', FaBChoiceRefs($player, 'Arsenal'))",'Bottom_arsenal_to_draw')+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Arsenal', 'Deck'); DoDrawCard($player, 1); }")
 if base=='rites_of_replenishment':
  add('AttackDeclared',"if (FaBCRUArcane($player)) { "+retrieve('','NAA','Deck')+' }'+fused(retrieve('','AA','Deck')))
 if base=='vela_flash':add('CardPlayed',fused(eff('NEXT_INSTANT')))
 if base=='snap_shot':add('CardPlayed',fused(eff('SNAP')))
 if base=='blossoming_spellblade':add('ResolveAbility',choice("FaBELESelect($player, 'Graveyard', '', 'NAA')",'Banish_non_attack_to_play')+" if ($chosen !== 'PASS') { FaBELESpellblade($player, $chosen); }")
 if base=='tome_of_harvests':
  add('PrepareCard',UID+choice("implode('&', FaBChoiceRefs($player, 'Arsenal'))",'Bottom_arsenal_as_cost',False)+" FaBMoveChoice($player, $chosen, 'Arsenal', 'Deck'); FaBFinishPreparedCard($uid);")
  add('ResolveCard','DoDrawCard($player, 3);')
 if base=='sow_tomorrow':add('ResolveCard',UID+choice("FaBELESowChoices($player, "+str(3-v)+")",'Return_action_to_deck',False)+" if ($chosen !== 'PASS') { FaBMoveChoice($player, $chosen, 'Graveyard', 'Deck'); }"+" if (DecisionQueueController::GetVariable('fabSourceZone') === 'Arsenal') { DoDrawCard($player, 1); } FaBMoveUID($uid, 'Banish', $player);")
 if base=='pulse_of_candlehold':add('ResolveCard',UID+"$refs = FaBELESelect($player, 'Graveyard', 'Earth|Lightning|Elemental', 'Action'); if ($refs !== '') { $chosen = await $player.MZMultiChoose($refs, 0, 2, \"Return_up_to_two_actions\"); $uids = FaBELEStage($player, $chosen); if (count($uids) > 0) { $orderParam = FaBARCOrderParam($uids, 'Top'); $order = await $player.Rearrange($orderParam); FaBARCFinishOrder($player, $uids, $order); } } FaBMoveUID($uid, 'Banish', $player);")
 if base=='awakening':add('ResolveCard',UID+choice('FaBELEAwakeningHeroes($player)', 'Choose_hero_for_life_difference', False)+"$other = FaBIdentityFromMZ($chosen); $n = FaBELEAwakening($player, $uid, intval($other['player'] ?? 0)); $refs = FaBARCSelect($player, 'Deck', 'Guardian', 'AA', $n); $uids = FaBARCStageRefs($player, $refs); $targets = implode('&', FaBChoiceRefs($player, 'Temp')); if ($targets !== '') { $chosen = await $player.MZChoose($targets, \"Find_Guardian_attack\"); FaBRevealChoices($player, $chosen); FaBMoveChoice($player, $chosen, 'Temp', 'Hand'); } FaBFinishSearch($player);")
 # Abilities, upkeep and complex equipment are appended below.
 if base.startswith('channel_'):
  element={'channel_lake_frigid':'Ice','channel_mount_heroic':'Earth','channel_thunder_steppe':'Lightning'}[base]
  add('StartTurn',UID+"$needed = FaBELEFlow($uid); $refs = FaBELESelect($player, 'Pitch', '"+element+"'); $chosen = ''; if (count(explode('&', $refs)) >= $needed && $refs !== '') { $chosen = await $player.MZMultiChoose($refs, 0, $needed, \"Pay_channel_upkeep_or_destroy\"); } FaBELEChannelPay($player, $uid, $chosen, $needed);")
 if base=='channel_thunder_steppe':add('ResolveAbility',"$actionUID = intval(DecisionQueueController::GetVariable('eleActionUID'));"+pay(1)+" if ($paid) { FaBTagUID($actionUID, 'GO_AGAIN'); }")
 if base in ['lexi','lexi_livewire']:
  add('PrepareCard',UID+choice('FaBELEFaceDown($player)','Reveal_arsenal_as_cost',False)+" $selected = FaBIdentityFromMZ($chosen); FaBELELexiReveal($player, $uid, $chosen); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('eleAbilityLightning')) { "+nxt('ATTACK',0,['GO_AGAIN'])+" } if (DecisionQueueController::GetVariable('eleAbilityIce')) { "+target()+" FaBELEFrost($target); }")
 if base in ['oldhim','oldhim_grandfather_of_eternity']:
  add('ResolveAbility',"if (DecisionQueueController::GetVariable('elePitchedEarth')) { FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2); } if (DecisionQueueController::GetVariable('elePitchedIce')) { $target = intval(FaBGetState()['attacker']); "+choice("implode('&', FaBChoiceRefs($target, 'Hand'))",'Put_hand_card_on_top',False,chooser='$target')+" if ($chosen !== 'PASS') { FaBARCToDeck($target, intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), true); } }")
 if base=='winters_wail':add('Hit',hit("if (FaBARCCard(intval(FaBIdentityFromMZ($mzID)['object']->UniqueID), 'elePitchedIce')) { FaBELEFrost($target); }"))
 if base=='shock_charmers':add('ResolveAbility',eff('ELECTRIFY',1))
 if base=='heart_of_ice':add('ResolveAbility',eff('TAX'))
 if base=='coat_of_frost':add('ResolveAbility',target()+"FaBELEFrost($target);")
 if base=='cracker_jax':add('ResolveAbility',nxt('AA',1))
 if base=='deep_blue':
  add('PrepareCard',UID+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Bottom_card_as_cost',False)+" FaBMoveChoice($player, $chosen, 'Hand', 'Deck'); FaBFinishPreparedCard($uid);")
  add('ResolveAbility','AddResources($player, intval(GetResources($player)) + 3);')
 if base=='crown_of_seeds':
  add('PrepareCard',UID+choice('FaBELEFaceDown($player)','Bottom_arsenal_as_cost',False)+" FaBMoveChoice($player, $chosen, 'Arsenal', 'Deck'); FaBFinishPreparedCard($uid);")
  add('ResolveAbility',"DoDrawCard($player, 1); FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1);")
 if base=='runaways':add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 1);")
 if base=='plume_of_evergrowth':add('ResolveAbility',retrieve('Earth','ActionOrInstant'))
 if base=='honing_hood':add('ResolveAbility',"FaBMoveChoices($player, implode('&', FaBChoiceRefs($player, 'Arsenal')), 'Arsenal', 'Hand'); "+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Put_card_in_arsenal',False)+" if ($chosen !== 'PASS') { FaBARCLoadArsenal($player, $chosen, false); }")
 if base=='ragamuffins_hat':add('ResolveAbility',"DoDrawCard($player, 1); "+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Return_card_to_deck',False)+" if ($chosen !== 'PASS') { $returnUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); $mode = await $player.Modal(1, 1, \"Top&Bottom\", \"Choose_deck_position\"); FaBARCToDeck($player, $returnUID, $mode === '0'); }")
 if base=='amulet_of_earth':add('ResolveAbility',eff('AA_POWER')+eff('AA_DEFENSE'))
 if base=='amulet_of_ice':add('ResolveAbility',target()+tax(2))
 if base in ['amulet_of_lightning','sutcliffes_suede_hides']:add('ResolveAbility',choice("FaBELECombatChoices('"+('AA' if base=='sutcliffes_suede_hides' else 'Action')+"')",'Grant_go_again',False)+" if ($chosen !== 'PASS') { FaBTagUID(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID), 'GO_AGAIN'); }")
 if base=='spellbound_creepers':add('ResolveAbility',eff('NEXT_INSTANT'))
 if base in ['shiver','voltaire_strike_twice']:
  add('ResolveAbility',"if (FaBELEArsenalSpace($player)) { "+choice("FaBELESelect($player, 'Hand', '', 'Arrow')",'Load_arrow')+" if ($chosen !== 'PASS') { $arrowUID = intval(FaBIdentityFromMZ($chosen)['object']->UniqueID); FaBARCLoadArsenal($player, $chosen, true); $mode = await $player.Modal(1, 1, \"Power&"+('Dominate' if base=='shiver' else 'Go_again')+"\", \"Choose_arrow_bonus\"); FaBTagUID($arrowUID, $mode === '0' ? 'WTR_POWER:1' : '"+('DOMINATE' if base=='shiver' else 'GO_AGAIN')+"'); } }")
 if base=='rampart_of_the_rams_head':add('ResolveAbility',UID+pay(1)+" if ($paid) { FaBTagUID($uid, 'WTR_DEFENSE:1'); }")
 if base=='mark_of_lightning':add('ResolveAbility',UID+"$mode = await $player.Modal(1, 1, \"Keep&Destroy_for_damage\", \"Mark_of_Lightning\"); if ($mode === '1' && FaBFindUID($uid) !== null) { FaBMONDestroy($uid); $s = FaBGetState(); $attack = FaBFindUID(intval($s['attackUID'])); if ($attack !== null) { DoDamage($player, $attack['mzID'], intval($s['defender']), 1, 'PHYSICAL'); } }")
 if base=='shock_striker':add('AttackDeclared',UID+pay(2)+" if ($paid) { FaBTagUID($uid, 'ELE_HIT_DAMAGE:1'); }")
 if base=='exposed_to_the_elements':
  add('ResolveCard',fused(choice("FaBELEEquipmentTargets($player)",'Weaken_equipment',False)+" if ($chosen !== 'PASS') { $o = FaBIdentityFromMZ($chosen)['object']; FaBSetObjectCounter($o, 'DEFENSE', intval(FaBObjectCounters($o)['DEFENSE'] ?? 0) + 1); }",'Earth')+fused(target()+pay(2,'$target')+" if (!$paid) { "+choice('FaBELEEquipmentTargets($target, true)','Destroy_zero_defense_equipment',False)+" if ($chosen !== 'PASS') { FaBMONDestroy(intval(FaBIdentityFromMZ($chosen)['object']->UniqueID)); } }",'Ice'))
 if base=='korshem_crossroad_of_elements':
  add('ResolveCard',UID+'FaBELELandmark($uid);')
  add('ResolveAbility',"$mode = await $player.Modal(1, 1, \"Resource&Life&Attack&Defense\", \"Korshem_reveal_bonus\"); FaBELEKorshemBonus($player, $mode);")
 if not handled:pending.append(id)
 snapshot.append(dict(cardId=id,abilities=a))
# Prior hashes make future authored edits safe to re-import.
old={c['cardId']:c for c in previous}
for c in snapshot:
 for a in c['abilities']:
  prior=next((x for x in old.get(c['cardId'],{}).get('abilities',[]) if x['macroName']==a['macroName']),None)
  if prior and prior['abilityCode']!=a['abilityCode']:a['previousCodeHash']=hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
(HERE/'ele_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
print('ELE identities:',len(snapshot),'Unhandled:',len(pending));print('\n'.join(pending))
if pending:raise SystemExit(1)
