// frontend/js/cookie-consent.js
(function () {
  "use strict";

  const COOKIE_NAME = "ecoride_consent_v1";
  const COOKIE_MAX_AGE_DAYS = 180; // 6 mois
  const CATEGORIES = ["analytics", "marketing"]; // "essentiels" sont implicites

  // --- Utils cookie ---
  function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    document.cookie =
      `${encodeURIComponent(name)}=${encodeURIComponent(value)};` +
      `path=/;` +
      `max-age=${maxAge};` +
      `SameSite=Lax`;
  }
  function getCookie(name) {
    const target = encodeURIComponent(name) + "=";
    return (
      document.cookie
        .split(";")
        .map((s) => s.trim())
        .find((s) => s.startsWith(target))
        ?.slice(target.length) || ""
    );
  }
  function parseConsent() {
    const raw =
      getCookie(COOKIES.COOKIE_NAME || COOKIE_NAME) || getCookie(COOKIE_NAME);
    if (!raw) return null;
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch {
      return null;
    }
  }
  const COOKIES = { COOKIE_NAME };

  // --- UI elements (présents dans tes pages) ---
  const $banner = document.getElementById("cookie-banner");
  const $modal = document.getElementById("cookie-modal");
  const $blocker = document.getElementById("cookie-blocker");
  const $openPrefLink = document.getElementById("open-cookie-modal");
  const $chkAnalytics = document.getElementById("consent-analytics");
  const $chkMarketing = document.getElementById("consent-marketing");

  // --- Appliquer décision & lancer scripts différés ---
  function applyConsent(consent) {
    // Sauvegarde
    setCookie(
      COOKIE_NAME,
      encodeURIComponent(JSON.stringify(consent)),
      COOKIE_MAX_AGE_DAYS
    );

    // Fermer UI
    hide($banner);
    hide($modal);
    hide($blocker);

    // Déclencher event pour ton app
    document.dispatchEvent(
      new CustomEvent("ecoride:consentchange", { detail: consent })
    );

    // Exécuter scripts marqués type="text/plain" data-consent="..."
    enableDeferredScripts(consent);
  }

  function enableDeferredScripts(consent) {
    const nodes = document.querySelectorAll(
      'script[type="text/plain"][data-consent]'
    );
    nodes.forEach((node) => {
      const needs = node
        .getAttribute("data-consent")
        .split(",")
        .map((s) => s.trim().toLowerCase());

      // Autorisé si toutes les catégories requises sont accordées
      const allowed = needs.every((cat) => consent[cat] === true);

      if (!allowed) return;

      const newScript = document.createElement("script");
      // Copie d'attributs utiles
      [
        "id",
        "async",
        "defer",
        "crossorigin",
        "referrerpolicy",
        "integrity",
      ].forEach((attr) => {
        if (node.hasAttribute(attr))
          newScript.setAttribute(attr, node.getAttribute(attr));
      });

      const src = node.getAttribute("data-src");
      if (src) {
        newScript.src = src;
      } else {
        newScript.text = node.text || node.textContent || "";
      }

      // Remplace dans le DOM
      node.parentNode.replaceChild(newScript, node);
    });
  }

  // --- Helpers UI ---
  function show(el) {
    if (el) el.hidden = false;
  }
  function hide(el) {
    if (el) el.hidden = true;
  }

  function openModal(prefill) {
    if ($chkAnalytics) $chkAnalytics.checked = !!prefill.analytics;
    if ($chkMarketing) $chkMarketing.checked = !!prefill.marketing;
    show($modal);
    show($blocker);
  }

  function openBanner() {
    show($banner);
    show($blocker);
  }

  // --- Handlers boutons du bandeau ---
  function bindBannerActions() {
    if (!$banner) return;
    $banner
      .querySelector('[data-action="accept-all"]')
      ?.addEventListener("click", () => {
        const consent = baseConsent({ analytics: true, marketing: true });
        applyConsent(consent);
      });

    $banner
      .querySelector('[data-action="reject-all"]')
      ?.addEventListener("click", () => {
        const consent = baseConsent({ analytics: false, marketing: false });
        applyConsent(consent);
      });

    $banner
      .querySelector('[data-action="customize"]')
      ?.addEventListener("click", () => {
        const current = parseConsent() || baseConsent({});
        openModal(current);
      });
  }

  // --- Handlers du modal ---
  function bindModalActions() {
    if (!$modal) return;

    $modal
      .querySelector('[data-action="save"]')
      ?.addEventListener("click", () => {
        const consent = baseConsent({
          analytics: $chkAnalytics ? $chkAnalytics.checked : false,
          marketing: $chkMarketing ? $chkMarketing.checked : false,
        });
        applyConsent(consent);
      });

    $modal
      .querySelector('[data-action="close"]')
      ?.addEventListener("click", () => {
        hide($modal);
        hide($blocker);
      });
  }

  // --- Base consent object ---
  function baseConsent(patch) {
    return {
      version: 1,
      date: new Date().toISOString(),
      // Essentiels toujours actifs
      essentials: true,
      analytics: false,
      marketing: false,
      ...patch,
    };
  }

  // --- API publique pratique ---
  const API = {
    get() {
      return parseConsent() || baseConsent({});
    },
    set(patch = {}) {
      const next = { ...API.get(), ...patch, date: new Date().toISOString() };
      applyConsent(next);
    },
    open() {
      openModal(API.get());
    },
    reset() {
      // Efface en réouvrant le bandeau
      setCookie(COOKIE_NAME, "", -1);
      openBanner();
    },
  };
  window.CookieConsentAPI = API; // accessible dans la console

  // --- Lien "Gérer mes cookies" ---
  if ($openPrefLink) {
    $openPrefLink.addEventListener("click", (e) => {
      e.preventDefault();
      API.open();
    });
  }

  // --- Boot ---
  document.addEventListener("DOMContentLoaded", () => {
    bindBannerActions();
    bindModalActions();

    const existing = parseConsent();
    if (existing) {
      // Consent déjà donné → activer les scripts autorisés
      enableDeferredScripts(existing);
      hide($banner);
      hide($modal);
      hide($blocker);
    } else {
      // Première visite → afficher le bandeau et bloquer rien de non-essentiel
      openBanner();
    }
  });
})();
