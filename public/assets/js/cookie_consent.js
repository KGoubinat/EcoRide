// frontend/js/cookie_consent.js
(function () {
  "use strict";

  const COOKIE_NAME = "ecoride_consent_v1";
  const COOKIE_MAX_AGE_DAYS = 180; // 6 mois

  // --- Utils cookie ---
  function setCookie(name, value, days) {
    const maxAge = days * 24 * 60 * 60;
    const secure = location.protocol === "https:" ? ";Secure" : "";
    document.cookie =
      `${encodeURIComponent(name)}=${encodeURIComponent(value)};` +
      `path=/;` +
      `max-age=${maxAge};` +
      `SameSite=Lax` +
      secure;
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
    const raw = getCookie(COOKIE_NAME);
    if (!raw) return null;
    // tolérant aux anciennes versions double-encodées
    try {
      return JSON.parse(decodeURIComponent(raw));
    } catch {
      try {
        return JSON.parse(decodeURIComponent(decodeURIComponent(raw)));
      } catch {
        return null;
      }
    }
  }

  // --- UI elements (présents dans tes pages) ---
  const $banner = document.getElementById("cookie-banner");
  const $modal = document.getElementById("cookie-modal");
  const $blocker = document.getElementById("cookie-blocker");
  const $openPrefLink = document.getElementById("open-cookie-modal");
  const $chkAnalytics = document.getElementById("consent-analytics");
  const $chkMarketing = document.getElementById("consent-marketing");

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

  // --- Base consent object ---
  function baseConsent(patch) {
    return {
      version: 1,
      date: new Date().toISOString(),
      essentials: true, // toujours actifs
      analytics: false,
      marketing: false,
      ...patch,
    };
  }

  // --- Appliquer décision & lancer scripts différés ---
  function applyConsent(consent) {
    // IMPORTANT: ne PAS re-encoder ici (setCookie s’en charge déjà)
    setCookie(COOKIE_NAME, JSON.stringify(consent), COOKIE_MAX_AGE_DAYS);

    hide($banner);
    hide($modal);
    hide($blocker);

    document.dispatchEvent(
      new CustomEvent("ecoride:consentchange", { detail: consent })
    );

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

      const allowed = needs.every((cat) => consent[cat] === true);
      if (!allowed) return;

      const newScript = document.createElement("script");

      // Copie d'attributs utiles (sauf type/data-*)
      [
        "id",
        "async",
        "defer",
        "crossorigin",
        "referrerpolicy",
        "integrity",
      ].forEach((attr) => {
        if (node.hasAttribute(attr)) {
          newScript.setAttribute(attr, node.getAttribute(attr));
        }
      });

      const src = node.getAttribute("data-src");
      if (src) {
        newScript.src = src;
      } else {
        newScript.textContent = node.textContent || "";
      }

      node.parentNode.replaceChild(newScript, node);
    });
  }

  // --- Handlers bandeau ---
  function bindBannerActions() {
    if (!$banner) return;

    $banner
      .querySelector('[data-action="accept-all"]')
      ?.addEventListener("click", () => {
        applyConsent(baseConsent({ analytics: true, marketing: true }));
      });

    $banner
      .querySelector('[data-action="reject-all"]')
      ?.addEventListener("click", () => {
        applyConsent(baseConsent({ analytics: false, marketing: false }));
      });

    $banner
      .querySelector('[data-action="customize"]')
      ?.addEventListener("click", () => {
        const current = parseConsent() || baseConsent({});
        openModal(current);
      });
  }

  // --- Handlers modal ---
  function bindModalActions() {
    if (!$modal) return;

    $modal
      .querySelector('[data-action="save"]')
      ?.addEventListener("click", () => {
        applyConsent(
          baseConsent({
            analytics: $chkAnalytics ? $chkAnalytics.checked : false,
            marketing: $chkMarketing ? $chkMarketing.checked : false,
          })
        );
      });

    $modal
      .querySelector('[data-action="close"]')
      ?.addEventListener("click", () => {
        hide($modal);
        hide($blocker);
      });
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
      // Efface puis ré-ouvre le bandeau
      setCookie(COOKIE_NAME, "", -1);
      openBanner();
    },
  };
  window.CookieConsentAPI = API;

  // Lien "Gérer mes cookies"
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
      enableDeferredScripts(existing);
      hide($banner);
      hide($modal);
      hide($blocker);
    } else {
      openBanner();
    }
  });
})();
