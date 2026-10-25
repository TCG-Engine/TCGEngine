import json,pathlib,copy,os
repo=pathlib.Path(os.environ.get('FAB_REGRESSION_REPO',next((p for p in pathlib.Path(__file__).resolve().parents if (p/'Core').is_dir()),pathlib.Path.cwd())))
dest=repo/'DevTools/FaB/CardRegression'
base=json.loads((dest/'card-regression-rule-probes.json').read_text())[0]
def zone(card,player=1,z='Weapons',**props):return dict(cardID=card,player=player,zone=z,properties=props)
def check(*checks):return dict(kind='checks',checks=list(checks))
def scalar(z,n):return dict(type='scalar_zone_equals',zone=z,value=n)
def count(z,n):return dict(type='zone_count',zone=z,value=n)
def exists(z,id):return dict(type='card_exists',zone=z,cardID=id)
cases=[]
setups=[('no-bow-rejected','Arsenal',[],{},False),('non-bow-weapon-rejected','Arsenal',[zone('talishar_the_lost_prince')],{},False),('bow-in-inventory-rejected','Arsenal',[zone('death_dealer',z='Inventory')],{},False),('bow-in-graveyard-rejected','Arsenal',[zone('death_dealer',z='Graveyard')],{},False),('opponent-bow-rejected','Arsenal',[zone('death_dealer',player=2)],{},False),('removed-bow-rejected','Arsenal',[zone('death_dealer',removed=True)],{},False),('hand-with-bow-rejected','Hand',[zone('death_dealer')],{},False),('banish-permission-with-bow-rejected','Banish',[zone('death_dealer')],{'PlayableFromBanish':1},False),('equipped-bow-facedown-allowed','Arsenal',[zone('death_dealer')],{'FaceDown':1},True),('equipped-bow-faceup-allowed','Arsenal',[zone('death_dealer')],{'FaceDown':0},True),('exhausted-bow-allowed','Arsenal',[zone('death_dealer',Status=1)],{'FaceDown':1},True),('ability-blanked-bow-allowed','Arsenal',[zone('death_dealer',TurnEffects=['NO_ABILITIES'])],{'FaceDown':1},True)]
for branch,source,weapons,props,allowed in setups:
 c=copy.deepcopy(base);c.update(slug='card-sic-em-shot-red-'+branch,name='Sic Em Shot / '+branch,branches=[branch],heroes=['azalea','azalea'],actionPoints=[1,0],zones=[zone('sic_em_shot_red',z=source,**props)]+weapons)
 c['oracle']='CR8.2.6a: arrow requires arsenal and a controlled bow. '+('Status, ability suppression, and face-down arsenal do not remove the bow subtype or control requirement.' if allowed else 'This setup does not satisfy both arrow play restrictions; no card movement, stack, cost or AP mutation is allowed.')
 c['sources']=['https://rules.fabtcg.com/en/cr/08-keywords/#826a','FaBSim/GeneratedCode/cardArrayCache.json#sic_em_shot_red']
 c['steps']=[dict(kind='click',player=1,mzID='p1'+source+'-0')]
 if not allowed:c['steps']+=[check(exists('p1'+source,'sic_em_shot_red'),count('Stack',0),scalar('p1Resources',1),scalar('p1ActionPoints',1),count('p1Pitch',0))]
 else:c['steps']+=[check(count('p1Arsenal',0),count('Stack',1),scalar('p1Resources',0),scalar('p1ActionPoints',0)),dict(kind='pass_until',until=dict(window='RESOLUTION')),check(scalar('p2Health',16),scalar('p1ActionPoints',1),exists('p1CombatChain','sic_em_shot_red'),exists('p1Weapons','death_dealer'),count('p1Pitch',0))]
 cases.append(c)
(dest/'card-regression-batch08.json').write_text(json.dumps(cases,indent=2)+'\n')
print('prepared',len(cases),'independently reviewed arrow restriction cases')
