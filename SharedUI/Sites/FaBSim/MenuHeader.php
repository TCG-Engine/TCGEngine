<?php
$homeHref = htmlspecialchars($def['branding']['homeHref'], ENT_QUOTES, 'UTF-8');
?>
<header class="fab-home-header">
  <h1><a href="<?= $homeHref ?>" aria-label="Talishar home">
    <img class="fab-home-logo" src="/TCGEngine/SharedUI/Sites/FaBSim/assets/talishar-logo.webp" alt="Talishar" width="1080" height="483">
  </a></h1>
  <ul class="fab-home-links">
    <li><a class="fab-support-link" href="https://talishar.net/premium" target="_blank" rel="noopener">Support Us!</a></li>
    <?php foreach ($def['navLinks'] as $link) echo _RenderNavLink($link); ?>
  </ul>
</header>
