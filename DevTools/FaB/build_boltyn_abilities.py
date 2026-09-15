"""Pinned Boltyn deck additions; player choices use the engine's await syntax."""
import ast,json
from pathlib import Path
HERE=Path(__file__).parent
source=(HERE/'build_mon_abilities.py').read_text(encoding='utf-8')
for node in ast.parse(source).body:
    if isinstance(node,ast.FunctionDef) and node.name in ['clean','choice']:
        exec(ast.get_source_segment(source,node))
cards=json.loads((HERE/'boltyn_catalog.json').read_text(encoding='utf-8'))
snapshot=[]
for card in cards:
    id=card['id'];abilities=[]
    def add(m,code):abilities.append(dict(macroName=m,abilityCode=clean(code),isImplemented=True))
    if id.startswith(('beaming_bravado_','light_the_way_')):
        code="$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); "+choice("FaBMONAffordableHand($player, '', true)",'Charge_your_soul')
        code+=" $yellow = FaBBoltynYellowChoice($chosen); FaBMONCharge($player, $chosen);"
        if id.startswith('beaming'):code+=" if ($yellow) { FaBCRUSelfTagUID($uid, 'WTR_POWER:1'); }"
        else:code+=" if ($yellow) { FaBTagUID($uid, 'BOLT_YELLOW_CHARGE'); }"
        add('PrepareCard',code+' FaBFinishPreparedCard($uid);')
        if id.startswith('light_the_way'):
            add('Hit',"$f = FaBIdentityFromMZ($mzID); if ($f !== null && in_array('BOLT_YELLOW_CHARGE', (array)$f['object']->TurnEffects, true)) { FaBWTRTag($f['object'], 'GO_AGAIN'); }")
    elif id=='edict_of_steel_red':
        add('ResolveCard',choice('FaBBoltynSwordChoices($player)','Sharpen_your_sword',False)+' FaBBoltynSharpen($player, $chosen);')
    elif id=='flat_trackers':add('ResolveAbility',"FaBWTRCreateArena($player, 'agility');")
    elif id=='garland_of_spring':add('ResolveAbility','AddResources($player, intval(GetResources($player)) + 1);')
    elif id in ['gauntlets_of_unity','helm_of_unity']:
        add('Defended',"FaBBoltynUnity($player, intval(FaBIdentityFromMZ($mzID)['object']->UniqueID));")
    elif id=='radiant_touch':
        add('PrepareCard',"$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); "+choice('FaBMONSoul($player)','Banish_from_soul',False)+" FaBMoveChoice($player, $chosen, 'Soul', 'Banish'); FaBFinishPreparedCard($uid);")
        add('ResolveAbility',"FaBWTRAddEffect($player, 'PREVENT_DAMAGE', 2);")
    elif id=='roaring_beam_yellow':
        add('ResolveCard',"FaBWTRCreateArena($player, 'courage'); if (FaBMONSoul($player) === '') { $uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID); FaBMoveUID($uid, 'Hand', $player); "+choice("implode('&', FaBChoiceRefs($player, 'Hand'))",'Charge_your_soul',False)+" FaBMONCharge($player, $chosen); }")
    elif id=='toe_the_line_red':add('ResolveCard',"FaBWTRAddEffect($player, 'BOLTYN_TOE', 2);")
    elif id not in ['banneret_of_salvation_yellow','duty_bound_blitz_red','duty_bound_blitz_yellow','agility','courage','flurry']:
        raise ValueError('Unhandled '+id)
    snapshot.append(dict(cardId=id,abilities=abilities))
(HERE/'boltyn_abilities.json').write_text(json.dumps(snapshot,indent=2)+'\n',encoding='utf-8')
print('Boltyn deck: '+str(len(snapshot))+' added identities.')
