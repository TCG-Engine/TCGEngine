"""Run sea_ui_fixture.php first; checks the actual generated HTTP response."""
import argparse
import urllib.request

parser = argparse.ArgumentParser()
parser.add_argument('--base-url', default='http://127.0.0.1/TCGEngine')
args = parser.parse_args()
for seats, owner, opponent in [(2, 1, 2), (4, 4, 1)]:
    for viewer in [owner, opponent]:
        url = (f'{args.base_url}/FaBSim/GetNextTurn.php?gameName=9802650{seats}'
               f'&playerID={viewer}&lastUpdate=-1')
        with urllib.request.urlopen(url, timeout=15) as response:
            frame = response.read().decode()
        assert ('wailer_humperdinck_yellow' in frame) == (viewer == owner), (
            seats, viewer, 'Face-down graveyard identity leaked or hidden from owner')
        assert 'oysten_heart_of_gold_yellow' in frame, (
            seats, viewer, 'Public card identity missing')
    print(f'{seats}-seat graveyard HTTP privacy passed.')
