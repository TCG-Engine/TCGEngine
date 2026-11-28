<?php
// The multiplayer TABLE's RIGHT column (docs/superpowers/specs/2026-11-21-swusim-multiplayer-table-design.md): the pair you are not
// in — its bottom board facing its top board — in the same rows as the left column (GameLayoutMobile.php), so the two line up row for
// row. Display only: the slots are seat-named (#p{n}<Zone>Slot) and the generated SWURenderSeatPair fills them every render, so its
// cards carry the engine's own target ids (p{n}<Zone>-{i}). Nothing here acts — a choice that targets one of these cards is picked
// through the table's targeting glue (GameLayoutShared.php).
// Arenas: ground INSIDE (toward the left column), space OUTSIDE (owner, 2026-11-21) — so ground comes first in DOM here.
// Included by GameLayoutMobile.php with $swuTbl = SWUTableClientConfig(viewer) in scope.
// 3P (Phase 2): bottom seat 0 — the column holds the top board (them 2) ALONE: its bar, arenas and its two centre cells, nothing more.
$rt = intval($swuTbl['seating']['right']['top']);
$rb = intval($swuTbl['seating']['right']['bottom']);
$viewerSeat = intval($swuTbl['seating']['left']['bottom']);
$mate = $rb > 0 && SWUIsTeamGame() && SWUTeamOf($rb) === SWUTeamOf($viewerSeat);   // your teammate sits bottom-right in team games
$side = function (int $seat) use ($viewerSeat) { return (SWUIsTeamGame() && SWUTeamOf($seat) === SWUTeamOf($viewerSeat)) ? 'is-mine' : 'is-theirs'; };
$bar = function (int $seat, string $row) {
    return '<div class="swu-m-band swu-tbar" data-seat="' . $seat . '"' . ($row !== '' ? ' data-trow="' . $row . '"' : '') . '>'
        . '<div class="swu-init-placeholder" aria-hidden="true"></div>'
        . '<span class="swu-tbl-name" data-seat="' . $seat . '"></span>'
        . '<div class="swu-res-badge"><div class="swu-res-badge-btn" title="Resources">'
        . '<img class="swu-res-img" src="./Assets/Icons/swusim-resource-badge.webp" alt="Resources"><span id="swuTblRes' . $seat . '">0/0</span></div></div>'
        . '<div class="swu-m-pile swu-m-hand-pile" title="Hand size"><div class="swu-m-pile-label">Hand</div>'
        . '<div class="swu-m-hand-pile-slot"><span id="swuTblHand' . $seat . '" class="swu-m-hand-count">0</span></div></div>'
        . '<div class="swu-m-piles-right">'
        . '<div class="swu-m-pile"><div class="swu-m-pile-label">Deck</div><div id="p' . $seat . 'DeckSlot"></div></div>'
        . '<div class="swu-m-pile"><div class="swu-m-pile-label">Discard</div><div id="p' . $seat . 'DiscardSlot"></div></div>'
        . '</div></div>';
};
$arenas = function (int $seat, string $row, string $cls) {
    return '<div class="swu-m-arena-row ' . $cls . '" data-seat="' . $seat . '"' . ($row !== '' ? ' data-trow="' . $row . '"' : '') . '>'
        . '<div class="swu-m-arena-col"><div class="swu-arena-glow" aria-hidden="true"></div><div id="p' . $seat . 'GroundArenaSlot" class="swu-m-scroll"></div></div>'
        . '<div class="swu-m-arena-col"><div class="swu-arena-glow" aria-hidden="true"></div><div id="p' . $seat . 'SpaceArenaSlot" class="swu-m-scroll"></div></div>'
        . '</div>';
};
?>
<div class="swu-tcol" id="swuTableRight" data-top="<?php echo $rt; ?>" data-bottom="<?php echo $rb; ?>">
    <?php echo $bar($rt, 'band'); ?>
    <!-- The top seat's hand is never shown in full (a count in its bar); its slot is kept, hidden, so the count can read it. -->
    <div class="swu-m-section swu-tbl-hidden-hand"><div id="p<?php echo $rt; ?>HandSlot" class="swu-m-scroll"></div></div>
    <?php echo $arenas($rt, 'arena-top', $side($rt)); ?>
    <!-- Leader/base cells in the left column's order — bottom leader, bottom base, top base, top leader — so the same ordering
         rules place them; the right column puts its bases on its LEFT (inside) edge. -->
    <div class="swu-m-centers swu-m-centers-row" data-trow="centers">
<?php if ($rb > 0) { ?>
        <div class="swu-m-center <?php echo $side($rb); ?>" data-seat="<?php echo $rb; ?>"><div id="p<?php echo $rb; ?>LeaderSlot"></div></div>
        <div class="swu-m-center <?php echo $side($rb); ?>" data-seat="<?php echo $rb; ?>"><div id="p<?php echo $rb; ?>BaseHealth" class="swu-base-health"></div><div id="p<?php echo $rb; ?>BaseSlot"></div><div id="p<?php echo $rb; ?>BaseTabs" class="swu-base-tabs"></div></div>
<?php } ?>
        <div class="swu-m-center <?php echo $side($rt); ?>" data-seat="<?php echo $rt; ?>"><div id="p<?php echo $rt; ?>BaseHealth" class="swu-base-health"></div><div id="p<?php echo $rt; ?>BaseSlot"></div><div id="p<?php echo $rt; ?>BaseTabs" class="swu-base-tabs"></div></div>
        <div class="swu-m-center <?php echo $side($rt); ?>" data-seat="<?php echo $rt; ?>"><div id="p<?php echo $rt; ?>LeaderSlot"></div></div>
    </div>
<?php if ($rb > 0) { ?>
    <?php echo $arenas($rb, 'arena-bot', $side($rb)); ?>
    <!-- The bottom seat's hand: face-up for your 4P Always Two partner (the server sends it so), backs for everyone else. Read-only. -->
    <div class="swu-m-section <?php echo $side($rb); ?> swu-tbl-hand<?php echo $mate ? ' is-mate' : ''; ?>" data-seat="<?php echo $rb; ?>" data-trow="hand"><div id="p<?php echo $rb; ?>HandSlot" class="swu-m-scroll"></div></div>
    <div class="swu-m-footer-stack" data-trow="footer"><?php echo $bar($rb, ''); ?></div>
    <div id="p<?php echo $rb; ?>ResourcesSlot" class="swu-tbl-res"></div>
<?php } ?>
    <div id="p<?php echo $rt; ?>ResourcesSlot" class="swu-tbl-res"></div>
</div><!-- /swuTableRight -->
