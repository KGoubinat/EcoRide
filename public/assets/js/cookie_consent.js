// assets/js/cookie_consent.js

(function () {
  const COOKIE_NAME = 'ecoride_consent_v1';
  const COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // 1 an

  // ---- Helpers cookie ----
  function readConsent() {
    const all = document.cookie.split(';').map(c => c.trim());
    const row = all.find(c => c.startsWith(COOKIE_NAME + '='));
    if (!row) return {};
    const raw = row.split('=').slice(1).join('='); // au cas où
    try {
      const decoded = decodeURIComponent(raw);
      const data = JSON.parse(decoded);
      return typeof data === 'object' && data !== null ? data : {};
    } catch (e) {
      return {};
    }
  }

  function writeConsent(consent) {
    const value = encodeURIComponent(JSON.stringify(consent));
    document.cookie =
      COOKIE_NAME + '=' + value +
      '; Max-Age=' + COOKIE_MAX_AGE +
      '; Path=/' +
      '; SameSite=Lax';
    // (Tu peux ajouter ;Secure si tu es 100% en HTTPS)
  }

  function hasUserMadeChoice(consent) {
    // On considère qu'il a fait un choix s'il y a une clé autre que "essentials"
    return Object.keys(consent).length > 1 || 'analytics' in consent || 'marketing' in consent;
  }

  // ---- UI helpers ----
  function show(el) {
    if (!el) return;
    el.hidden = false;
  }

  function hide(el) {
    if (!el) return;
    el.hidden = true;
  }

  // ---- Application du consentement aux scripts ----
  function applyConsentToScripts(consent) {
    const scripts = document.querySelectorAll('script[type="text/plain"][data-consent]:not([data-applied="1"])');

    scripts.forEach(srcScript => {
      const key = srcScript.dataset.consent;
      const allowed = !!consent[key]; // true si autorisé

      if (!allowed) {
        return; // on ne charge pas ce script
      }

      // Marquer comme appliqué pour éviter les doublons
      srcScript.dataset.applied = '1';

      if (srcScript.dataset.src) {
        // Script externe (analytics par ex)
        const s = document.createElement('script');
        s.src = srcScript.dataset.src;
        s.async = srcScript.async;
        document.head.appendChild(s);
      } else if (srcScript.textContent.trim() !== '') {
        // Script inline
        const s = document.createElement('script');
        s.text = srcScript.textContent;
        document.body.appendChild(s);
      }
    });
  }

  // ---- Initialisation au chargement ----
  document.addEventListener('DOMContentLoaded', function () {
    const banner = document.getElementById('cookie-banner');
    const modal = document.getElementById('cookie-modal');
    const blocker = document.getElementById('cookie-blocker');
    const openModalLink = document.getElementById('open-cookie-modal');
    const analyticsCheckbox = document.getElementById('consent-analytics');
    const marketingCheckbox = document.getElementById('consent-marketing');

    if (!banner || !modal) {
      return; // sécurité
    }

    let consent = readConsent();

    // Si un consentement existe déjà → on l'applique et on ne montre pas le bandeau
    if (hasUserMadeChoice(consent)) {
      // Met à jour les cases à cocher en fonction du cookie
      if (analyticsCheckbox) {
        analyticsCheckbox.checked = !!consent.analytics;
      }
      if (marketingCheckbox) {
        marketingCheckbox.checked = !!consent.marketing;
      }
      applyConsentToScripts(consent);
    } else {
      // Pas encore de choix → montrer le bandeau
      show(banner);
      show(blocker);
    }

    // ---- Gestion des boutons du bandeau ----
    banner.addEventListener('click', function (event) {
      const btn = event.target.closest('button[data-action]');
      if (!btn) return;

      const action = btn.dataset.action;

      if (action === 'accept-all') {
        consent = {
          essentials: true,
          analytics: true,
          marketing: true
        };
        writeConsent(consent);
        if (analyticsCheckbox) analyticsCheckbox.checked = true;
        if (marketingCheckbox) marketingCheckbox.checked = true;
        hide(banner);
        hide(blocker);
        applyConsentToScripts(consent);
      }

      if (action === 'reject-all') {
        consent = {
          essentials: true,
          analytics: false,
          marketing: false
        };
        writeConsent(consent);
        if (analyticsCheckbox) analyticsCheckbox.checked = false;
        if (marketingCheckbox) marketingCheckbox.checked = false;
        hide(banner);
        hide(blocker);
        // On ne charge rien de plus ici
      }

      if (action === 'customize') {
        hide(banner);
        show(modal);
        show(blocker);
        // Pré-remplir les cases avec la valeur actuelle
        if (analyticsCheckbox) analyticsCheckbox.checked = !!consent.analytics;
        if (marketingCheckbox) marketingCheckbox.checked = !!consent.marketing;
      }
    });

    // ---- Lien "Gérer mes cookies" dans le footer ----
    if (openModalLink) {
      openModalLink.addEventListener('click', function (e) {
        e.preventDefault();
        hide(banner);
        show(modal);
        show(blocker);
        // Pré-remplir à partir du cookie actuel
        consent = readConsent();
        if (analyticsCheckbox) analyticsCheckbox.checked = !!consent.analytics;
        if (marketingCheckbox) marketingCheckbox.checked = !!consent.marketing;
      });
    }

    // ---- Boutons dans la modale ----
    modal.addEventListener('click', function (event) {
      const btn = event.target.closest('button[data-action]');
      if (!btn) return;

      const action = btn.dataset.action;

      if (action === 'save') {
        consent = {
          essentials: true,
          analytics: analyticsCheckbox ? analyticsCheckbox.checked : false,
          marketing: marketingCheckbox ? marketingCheckbox.checked : false
        };
        writeConsent(consent);
        hide(modal);
        hide(blocker);
        applyConsentToScripts(consent);
      }

      if (action === 'close') {
        hide(modal);
        // Si l'utilisateur n'a jamais fait de choix avant, on peut réafficher le bandeau
        const stored = readConsent();
        if (!hasUserMadeChoice(stored)) {
          show(banner);
          show(blocker);
        } else {
          hide(blocker);
        }
      }
    });
  });
})();
