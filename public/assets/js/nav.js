/**
 * Navbar interactions — hamburger toggle + dropdown menu.
 * Loaded from layouts/main.php.
 */
(function () {
    'use strict';

    // Hamburger
    document.querySelectorAll('[data-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.toggle);
            if (target) target.classList.toggle(btn.dataset.toggle + '--open');
        });
    });

    // Dropdown triggers — açma/kapama (klavye için)
    document.querySelectorAll('.ds-nav__group > a').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            var menu = trigger.parentElement.querySelector('.ds-nav__menu');
            if (menu) menu.classList.toggle('ds-nav__menu--open');
        });
    });

    // Dış tıklamada kapat
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ds-nav__group')) {
            document.querySelectorAll('.ds-nav__menu--open').forEach(function (m) {
                m.classList.remove('ds-nav__menu--open');
            });
        }
    });
})();
