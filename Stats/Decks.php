<?php

include_once '../SharedUI/MenuBar.php';
echo('<link rel="stylesheet" href="/TCGEngine/SharedUI/Sites/SWUDeck/css/hud.css">');
include_once '../SharedUI/Header.php';
include_once '../Core/HTTPLibraries.php';
include_once "../Core/UILibraries.php";
include_once '../Database/ConnectionManager.php';
include_once '../SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once '../Core/StatsBaseRegistry.php';        // ResolveOpponentBase / BaseGroupDisplayLabel
include_once '../AppCore/SWU/Formats.php';                 // SWUFormatLegalSets (dict-free; no card-dictionary collision)

$isMobile = IsMobile();

$forIndividual = false;

$conn = GetLocalMySQLConnection();

// --- Premier-only filter state -------------------------------------------------
// Same curated Premier rotation the deck-builder's "Filter Legal" checkbox uses.
$legalSets = SWUFormatLegalSets('premier');
// Default ON for a fresh load; once the form is submitted, respect the box's state
// (unchecked checkboxes are not sent, so we key off the hidden formSubmitted marker).
$premierOnly = isset($_GET['formSubmitted']) ? isset($_GET['premierOnly']) : true;

// A leader GUID is Premier-legal when its set is in the rotation. We check the
// primary printing only ($setData is a single set per GUID; the reprint map lives
// on the JS side) — leaders never reprint into a legal set from an illegal one, so
// this matches the Filter Legal result for leaders. Unknown sets are shown (lenient)
// so a missing dictionary entry never hides real decks.
function LeaderNotPremierLegal($guid, array $legalSets) {
    $set = CardSet($guid);
    return $set !== null && $set !== '' && !in_array($set, $legalSets, true);
}

// --- Build base search groups from live published-deck bases --------------------
// One pass over the distinct bases: common bases (30HP/Force/Splash) collapse into a
// per-color bucket via the submission-time registry; rare/special bases stay
// individual (specific-card search). $baseGroups: key => [label, members[], sort].
$baseGroups = [];
$baseQuery = "SELECT DISTINCT keyIndicator2 FROM ownership WHERE assetType=1 AND assetVisibility=2 AND keyIndicator2 IS NOT NULL AND keyIndicator2 <> ''";
$baseResult = mysqli_query($conn, $baseQuery);
$typeOrder  = ['Standard' => 0, 'Force' => 1, 'Splash' => 2];
$colorOrder = ['Green' => 0, 'Blue' => 1, 'Red' => 2, 'Yellow' => 3, 'Colorless' => 4];
while ($baseRow = mysqli_fetch_assoc($baseResult)) {
    $guid = $baseRow['keyIndicator2'];
    if ($guid === '' || $guid === null) continue;
    $r = ResolveOpponentBase($guid);
    if ($r && $r['kind'] === 'common') {
        $key   = 'grp:' . $r['type'] . ':' . $r['color'];
        $label = BaseGroupDisplayLabel($r['type'], $r['color']);
        // '1' sub-order keeps individual colors after the "Any" aggregate ('0' below).
        $sort  = '0' . ($typeOrder[$r['type']] ?? 9) . '1' . ($colorOrder[$r['color']] ?? 9);
    } else {
        // Named rare (or unresolvable) — keep it as a specific-card option.
        $key   = $guid;
        $label = CardTitle($guid);
        if ($label === '' || $label === null) $label = $guid;
        $sort  = '2' . $label;
    }
    if (!isset($baseGroups[$key])) {
        $baseGroups[$key] = ['label' => $label, 'members' => [], 'sort' => $sort];
    }
    $baseGroups[$key]['members'][] = $guid;
}

// Add a "<Type> — Any" aggregate per common type (union of all its colors), so a
// player can search e.g. every 30HP deck at once. Only when >1 color is present,
// otherwise it would just duplicate the single color option.
foreach (['Standard', 'Force', 'Splash'] as $type) {
    $members = [];
    $colorCount = 0;
    foreach ($baseGroups as $k => $g) {
        if (strpos($k, 'grp:' . $type . ':') === 0) {
            $members = array_merge($members, $g['members']);
            $colorCount++;
        }
    }
    if ($colorCount >= 2) {
        $baseGroups['grp:' . $type . ':*'] = [
            'label'   => BaseGroupDisplayLabel($type, '*'),
            'members' => array_values(array_unique($members)),
            'sort'    => '0' . ($typeOrder[$type] ?? 9) . '0',   // '0' sub-order => sorts first within type
        ];
    }
}

uasort($baseGroups, function ($a, $b) { return strcmp($a['sort'], $b['sort']); });

// Resolve the selected base to a group key. Handles both a group key from this page's
// dropdown and a raw/canonical base GUID arriving from a DeckMetaStats drill-down link.
$selectedBase = isset($_GET['base']) ? $_GET['base'] : '';
$selectedKey  = '';
if ($selectedBase !== '') {
    if (isset($baseGroups[$selectedBase])) {
        $selectedKey = $selectedBase;
    } else {
        $r = ResolveOpponentBase($selectedBase);
        $selectedKey = ($r && $r['kind'] === 'common')
            ? 'grp:' . $r['type'] . ':' . $r['color']
            : $selectedBase;
    }
}

?>
<form method="get" action="" style="float: left; padding-left: 25px;">
    <input type="hidden" name="formSubmitted" value="1">
    <div style="display: flex; flex-direction: column; align-items: flex-start;">
        <div style="margin-bottom: 10px;">
            <label for="leader" style="margin-right: 5px;">Leader:</label>
            <select id="leader" name="leader">
                <option value="">-- All Leaders --</option>
                <?php
                    $leaderQuery = "SELECT DISTINCT keyIndicator1 FROM ownership WHERE assetType=1 AND assetVisibility=2 AND keyIndicator1 IS NOT NULL";
                    $leaderResult = mysqli_query($conn, $leaderQuery);
                    // Collect + filter first, then sort alphabetically before emitting.
                    $leaders = [];
                    while ($leaderRow = mysqli_fetch_assoc($leaderResult)) {
                        $rawLeader = $leaderRow['keyIndicator1'];
                        if ($premierOnly && LeaderNotPremierLegal($rawLeader, $legalSets)) continue;
                        $leaderName = CardTitle($rawLeader);
                        $subtitle = CardSubtitle($rawLeader);
                        $leaderName .= $subtitle != "" ? ", " . $subtitle : "";
                        if($leaderName == "" || $leaderName == "Shield" || $leaderName == "Experience") continue;
                        $leaders[] = ['guid' => $rawLeader, 'name' => $leaderName];
                    }
                    usort($leaders, function ($a, $b) { return strcasecmp($a['name'], $b['name']); });
                    foreach ($leaders as $leader) {
                        $currentLeader = htmlspecialchars($leader['guid']);
                        $selected = (isset($_GET['leader']) && $_GET['leader'] === $leader['guid']) ? 'selected' : '';
                        echo "<option value='{$currentLeader}' {$selected}>" . htmlspecialchars($leader['name']) . "</option>";
                    }
                ?>
            </select>
        </div>
        <div style="margin-bottom: 10px;">
            <label for="base" style="margin-right: 5px;">Base:</label>
            <select id="base" name="base">
                <option value="">-- All Bases --</option>
                <?php
                    foreach ($baseGroups as $groupKey => $group) {
                        $selected = ($groupKey === $selectedKey) ? 'selected' : '';
                        $optVal   = htmlspecialchars($groupKey);
                        $optLabel = htmlspecialchars($group['label']);
                        echo "<option value='{$optVal}' {$selected}>{$optLabel}</option>";
                    }
                ?>
            </select>
        </div>
        <div style="margin-bottom: 10px;">
            <label style="cursor: pointer;">
                <input type="checkbox" name="premierOnly" value="1" onchange="this.form.submit()" <?php echo $premierOnly ? 'checked' : ''; ?>>
                Premier only
            </label>
        </div>
        <div>
            <button type="submit">Filter</button>
        </div>
    </div>
</form>

<?php
$sql = "SELECT * FROM ownership WHERE assetType=1 AND assetVisibility=2";

if (isset($_GET['leader']) && $_GET['leader'] !== '') {
        $leaderFilter = mysqli_real_escape_string($conn, $_GET['leader']);
        $sql .= " AND keyIndicator1 = '{$leaderFilter}'";
}
if ($selectedBase !== '') {
        // Expand the selected base to every member GUID in its bucket (a single GUID
        // for named rares / unresolved), so a common-base pick matches all its printings.
        $members = (isset($baseGroups[$selectedKey]) && !empty($baseGroups[$selectedKey]['members']))
            ? $baseGroups[$selectedKey]['members']
            : [$selectedBase];
        $escaped = array_map(function ($m) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $m) . "'";
        }, $members);
        $sql .= " AND keyIndicator2 IN (" . implode(',', $escaped) . ")";
}
$result = mysqli_query($conn, $sql);

echo "<style>
#sciFiScroll::-webkit-scrollbar {
    width: 12px;
}

/* Ensure the track itself has rounded corners */
#sciFiScroll::-webkit-scrollbar-track {
    background: #000022;
    box-shadow: inset 0 0 5px #000;
    border-radius: 8px; /* Ensure rounded edges */
    overflow: hidden; /* Prevents clipping */
}

/* Modify the scrollbar thumb */
#sciFiScroll::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #000066, #000099);
    border-radius: 12px; /* Increase for more rounded effect */
    box-shadow: inset 0 0 5px #000;
}

/* Smooth animation for hover */
#sciFiScroll::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #000099, #000066);
}

/* Optional: Handle the scrollbar corners */
#sciFiScroll::-webkit-scrollbar-corner {
    background: transparent; /* Prevents awkward edges */
}

</style>";
echo "<div style='position: absolute; top: 180px; left: 50%; transform: translate(-50%, 0%);'>";
echo "  <div id='sciFiScroll' style='max-height: calc(100vh - 220px); overflow: auto;'>";
echo "    <table border='0' cellpadding='5' cellspacing='0'>";
echo "      <tr><th>Leader</th><th>Base</th><th>Asset Name</th><th>Likes</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    $assetName = htmlspecialchars($row['assetName'] ?? '');
    $leader = htmlspecialchars($row['keyIndicator1'] ?? '');
    $base = htmlspecialchars($row['keyIndicator2'] ?? '');
    $likes = htmlspecialchars($row['numLikes'] ?? '');
    if($leader == "" || $base == "") continue;
    if ($premierOnly && LeaderNotPremierLegal($row['keyIndicator1'], $legalSets)) continue;
    echo "<tr onclick=\"window.location='https://swustats.net/TCGEngine/NextTurn.php?gameName=" . $row['assetIdentifier'] . "&playerID=1&folderPath=SWUDeck';\" onmouseover=\"this.style.boxShadow='0 0 10px 5px rgba(51, 204, 255, 0.6)'; this.style.transform='scaleY(1.02)';\" onmouseout=\"this.style.boxShadow='none'; this.style.transform='none';\" style='cursor: pointer; transition: all 0.3s ease-in-out;'>";
    echo "    <td><img style='height:80px' src='../SWUDeck/concat/" . $leader . ".webp' title='" . CardTitle($leader) . "' /></td>";
    echo "    <td><img style='height:80px' src='../SWUDeck/concat/" . $base . ".webp' title='" . CardTitle($base) . "' /></td>";
    echo "    <td>{$assetName}</td>";
    echo "    <td>{$likes}</td>";
    echo "</tr>";
}
echo "    </table>";
echo "  </div>";
echo "</div>";


$conn->close();



include_once '../SharedUI/Disclaimer.php';

?>