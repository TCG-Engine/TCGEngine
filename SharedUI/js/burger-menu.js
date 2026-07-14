/**
 * Shared mobile navigation for SiteDef-driven pages.
 * Site scripts load from <head>, so initialization must tolerate the body not existing yet.
 */
(function() {
  'use strict';

  function initBurgerMenu() {
    var body = document.body;
    var navBar = document.querySelector('.nav-bar');
    if (!body || !navBar || navBar.dataset.burgerMenuReady === '1') return;

    var burgerMenu = document.querySelector('.burger-menu');
    if (!burgerMenu) {
      burgerMenu = document.createElement('button');
      burgerMenu.type = 'button';
      burgerMenu.className = 'burger-menu';
      burgerMenu.setAttribute('aria-label', 'Open navigation');
      burgerMenu.setAttribute('aria-expanded', 'false');
      burgerMenu.innerHTML = '<span></span><span></span><span></span>';
      body.insertBefore(burgerMenu, body.firstChild);
    }

    var menuOverlay = document.querySelector('.menu-overlay');
    if (!menuOverlay) {
      menuOverlay = document.createElement('div');
      menuOverlay.className = 'menu-overlay';
      body.insertBefore(menuOverlay, navBar);
    }
    menuOverlay.style.display = 'none';
    navBar.dataset.burgerMenuReady = '1';

    var navBarUser = navBar.querySelector('.nav-bar-user');
    var navBarLinks = navBar.querySelector('.nav-bar-links');
    if (navBarUser) navBarUser.style.display = 'block';
    if (navBarLinks) navBarLinks.style.display = 'block';

    function setOpen(open) {
      burgerMenu.classList.toggle('active', open);
      navBar.classList.toggle('active', open);
      menuOverlay.style.display = open ? 'block' : 'none';
      body.classList.toggle('menu-open', open);
      burgerMenu.setAttribute('aria-expanded', open ? 'true' : 'false');
      burgerMenu.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      if (!open) {
        navBar.querySelectorAll('.dropdown.is-open').forEach(function(dropdown) {
          dropdown.classList.remove('is-open');
          var trigger = dropdown.querySelector('.NavBarItem');
          if (trigger) trigger.setAttribute('aria-expanded', 'false');
        });
      }
    }

    burgerMenu.addEventListener('click', function(event) {
      event.stopPropagation();
      setOpen(!navBar.classList.contains('active'));
    });
    menuOverlay.addEventListener('click', function(event) {
      event.stopPropagation();
      setOpen(false);
    });
    navBar.addEventListener('click', function(event) {
      var link = event.target.closest('a');
      if (!link) return;
      var dropdown = link.parentElement && link.parentElement.classList.contains('dropdown')
        ? link.parentElement : null;
      if (dropdown && window.innerWidth <= 768) {
        event.preventDefault();
        var open = !dropdown.classList.contains('is-open');
        dropdown.classList.toggle('is-open', open);
        link.setAttribute('aria-expanded', open ? 'true' : 'false');
        return;
      }
      setOpen(false);
    });
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') setOpen(false);
    });
    window.addEventListener('resize', function() {
      if (window.innerWidth > 768) setOpen(false);
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initBurgerMenu, { once: true });
  else initBurgerMenu();
})();
