"""New Maxx Armory Deck cards; reprints use existing set implementations."""
import ast
import hashlib
import json
from pathlib import Path

HERE = Path(__file__).parent
source = (HERE / 'build_mon_abilities.py').read_text()
for node in ast.parse(source).body:
    if isinstance(node, ast.FunctionDef) and node.name == 'clean':
        exec(ast.get_source_segment(source, node))
UID = "$uid = intval(FaBIdentityFromMZ($mzID)['object']->UniqueID);\n"
cards = {}

def add(card, macro, code):
    cards.setdefault(card, []).append(dict(macroName=macro, abilityCode=clean(code), isImplemented=True))

add('construct_bank_breaker_yellow', 'ResolveCard', UID + """
$refs = FaBAMXWrenches($player);
$wrench = 'PASS';
if ($refs !== '') { $wrench = await $player.MZChoose($refs, "Choose_wrench_to_transform"); }
$drivers = FaBEVOItemTargets($player, 'driver', false, true);
$chosen = '-';
if (count(array_filter(explode('&', $drivers))) >= 3) {
    $chosen = await $player.MZMultiChoose($drivers, 3, 3, "Transform_three_Hyper_Drivers");
}
FaBAMXConstruct($player, $uid, $wrench, $chosen);
""")
add('bank_breaker', 'AttackDeclared', UID + """
$weaponUID = intval(FaBObjectCounters(FaBIdentityFromMZ($mzID)['object'])['WEAPON_UID'] ?? 0);
$previews = FaBEVOUnderPreview($player, $weaponUID);
$chosen = 'PASS';
if ($previews !== '') { $chosen = await $player.MZMultiChoose($previews, 0, 1, "Banish_Bank_Breaker_material"); }
FaBAMXBanishMaterial($player, $uid, $weaponUID, $chosen, $previews);
""")
add('breaker_helm_protos', 'Defended', UID + """
$refs = FaBEVORefs($player, 'Hand', 'driver');
$chosen = 'PASS';
if ($refs !== '') { $chosen = await $player.MZMayChoose($refs, "Discard_Hyper_Driver_for_defense"); }
FaBAMXHelm($player, $uid, $chosen);
""")
add('clamp_press_blue', 'ResolveCard', "FaBARCEnterItem($player, FaBIdentityFromMZ($mzID)['object']);")
add('clamp_press_blue', 'StartTurn', UID + """
$steam = intval(FaBObjectCounters(FaBIdentityFromMZ($mzID)['object'])['STEAM'] ?? 0);
$mode = '1';
if ($steam > 0) { $mode = await $player.Modal(1, 1, "Remove_steam&Destroy_item", "Maintain_item"); }
if ($mode === '0') { FaBEVOSteam(FaBDTDSource($uid), -1); } else { FaBMONDestroy($uid); }
""")
add('fist_pump', 'ResolveAbility', """
$refs = FaBAMXWrenches($player);
if ($refs !== '') {
    $chosen = await $player.MZChoose($refs, "Empower_wrench");
    FaBDYNTag($chosen, 'WTR_POWER:1');
}
""")
add('twintek_charging_station_red', 'ResolveCard', """
FaBEVOAdd($player, 'NEXT_BOOST', 3);
$refs = FaBEVORefs($player, 'Graveyard', 'driver');
$chosen = 'PASS';
if ($refs !== '') { $chosen = await $player.MZMayChoose($refs, "Recycle_Hyper_Driver"); }
FaBAMXRecycle($player, $chosen);
""")
cards['drive_brake'] = []  # Boost event hook; printed Battleworn uses shared rules.
cards['puffer_jacket'] = []  # Entry replacement; printed Temper uses shared rules.
path = HERE / 'amx_abilities.json'
old = {c['cardId']: c for c in json.loads(path.read_text())} if path.exists() else {}
out = []
for card, abilities in sorted(cards.items()):
    for ability in abilities:
        prior = next((a for a in old.get(card, {}).get('abilities', []) if a['macroName'] == ability['macroName']), None)
        if prior and prior.get('previousCodeHash'):
            ability['previousCodeHash'] = prior['previousCodeHash']
        if prior and prior['abilityCode'] != ability['abilityCode']:
            ability['previousCodeHash'] = hashlib.sha256(prior['abilityCode'].strip().encode()).hexdigest()
    out.append(dict(cardId=card, abilities=abilities))
path.write_text(json.dumps(out, indent=2)+'\n')
print(f'AMX: {len(out)} identities, {sum(len(c["abilities"]) for c in out)} macros')
