<?php /* zzDesignSystemPreview.php — design-system preview harness (dev tool).
   Serve at /TCGEngine/zzDesignSystemPreview.php  or  ?theme=hud to load a theme's tokens.
   Loads the shared layer; ?theme=<name> additionally links that theme's *.tokens.css. */
$theme = preg_replace('/[^a-z0-9-]/', '', $_GET['theme'] ?? '');
$themeFiles = [
  'hud'       => '/TCGEngine/SharedUI/Themes/hud.tokens.css',
  'clarent'   => '/TCGEngine/SharedUI/Themes/clarent.tokens.css',
  'petranaki' => '/TCGEngine/SharedUI/Themes/petranaki.tokens.css',
  'petranaki-hud' => '/TCGEngine/SharedUI/Themes/petranaki-hud.tokens.css',
  'gudnak'    => '/TCGEngine/SharedUI/Themes/gudnak.tokens.css',
  'molten-forge'       => '/TCGEngine/SharedUI/Themes/molten-forge.tokens.css',
  'circuit-sigil-cyan' => '/TCGEngine/SharedUI/Themes/circuit-sigil-cyan.tokens.css',
  'circuit-sigil-gold' => '/TCGEngine/SharedUI/Themes/circuit-sigil-gold.tokens.css',
  'infernal-edge'      => '/TCGEngine/SharedUI/Themes/infernal-edge.tokens.css',
];
?>
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Design System Preview<?= $theme ? " — $theme" : '' ?></title>
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/tokens.css">
<link rel="stylesheet" href="/TCGEngine/SharedUI/css/components.css">
<?php if ($theme && isset($themeFiles[$theme])): ?>
<link rel="stylesheet" href="<?= $themeFiles[$theme] ?>">
<?php endif; ?>
<style>body{background:#0e1420;color:#fff;font-family:barlow,sans-serif;margin:0;padding:32px;}
.row{display:flex;gap:14px;flex-wrap:wrap;align-items:center;margin:0 0 22px;}
h2{letter-spacing:.1em;text-transform:uppercase;font-size:15px;opacity:.7;margin:26px 0 10px;}</style>
</head><body>
  <h1 class="u-label">Design System Preview — theme: <?= $theme ?: 'neutral' ?></h1>
  <h2>Buttons</h2>
  <div class="row">
    <button>Default</button>
    <button class="btn-primary">Primary</button>
    <button class="btn-success">Success</button>
    <button class="btn-danger">Danger</button>
    <button disabled>Disabled</button>
  </div>
  <h2>Input-type buttons (no pseudos)</h2>
  <div class="row">
    <input type="submit" value="Submit">
    <input type="button" value="Button">
  </div>
  <h2>Typography</h2>
  <div class="u-label">This is a u-label (ALLCAPS display)</div>
  <div class="prose"><p>This is prose — a readable paragraph in the body font with normal case, comfortable line-height, and a max width so long walls of text stay legible instead of stretching edge to edge.</p></div>
  <h2>Inputs</h2>
  <div class="row">
    <input type="text" placeholder="Text input">
    <select class="styled-select"><option>Styled select</option><option>Title</option><option>Cost</option><option>Aspect</option><option>Power</option></select>
    <select><option>Native select</option><option>Second</option></select>
    <label style="display:inline-flex;align-items:center"><input type="checkbox" checked> Checkbox</label>
    <label style="display:inline-flex;align-items:center"><input type="radio" name="r" checked> Radio A</label>
    <label style="display:inline-flex;align-items:center"><input type="radio" name="r"> Radio B</label>
    <button class="switch" aria-pressed="false" onclick="this.classList.toggle('is-on')"></button>
    <button class="switch is-on" aria-pressed="true" onclick="this.classList.toggle('is-on')"></button>
    <span class="u-label" style="font-size:11px">SWITCH (off / on)</span>
  </div>
  <div class="dropdown-panel" style="width:200px;padding:4px 0">
    <div class="dropdown-panel__item" style="padding:8px 14px">Menu item</div>
    <div class="dropdown-panel__item is-active" style="padding:8px 14px">Active item</div>
    <div class="dropdown-panel__item" style="padding:8px 14px">Another item</div>
  </div>
  <h2>Tabs</h2>
  <div class="row">
    <span class="panelTab is-active">Overview</span>
    <span class="panelTab">Cards</span>
    <span class="panelTab">Stats</span>
  </div>
  <h2>Table (.ds-table + .interactive-row)</h2>
  <table class="ds-table" style="max-width:520px">
    <thead><tr><th style="text-align:left">Deck</th><th style="text-align:left">Record</th></tr></thead>
    <tbody>
      <tr class="interactive-row"><td>Aggro Red</td><td>12–3</td></tr>
      <tr class="interactive-row"><td>Control Blue</td><td>8–7</td></tr>
      <tr class="interactive-row"><td>Midrange Green</td><td>10–5</td></tr>
    </tbody>
  </table>
  <h2>Panel (.panel)</h2>
  <div class="panel" style="max-width:420px"><div class="u-label">Panel heading</div><div class="prose"><p>A token-themed surface panel with border and backdrop blur.</p></div></div>
  <h2>Dialogs</h2>
  <div class="row">
    <button onclick="StyledConfirm('Delete this deck?', {title:'Confirm', danger:true, confirmLabel:'Delete'}).then(v=>Toast('confirm → '+v))">Open confirm</button>
    <button onclick="StyledPrompt('New deck name?', {title:'Rename', initial:'My Deck'}).then(v=>Toast('prompt → '+v))">Open prompt</button>
    <button onclick="StyledAlert('Deck imported successfully.', {title:'Done'})">Open alert</button>
    <button onclick="Toast('Copied to clipboard', {type:'success'})">Show toast</button>
  </div>
  <script src="/TCGEngine/Core/StyledDialog.js"></script>
  <script src="/TCGEngine/Core/StyledSelect.js"></script>
</body></html>
