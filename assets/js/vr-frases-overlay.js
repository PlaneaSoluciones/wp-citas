/**
 * VR-Frases Overlay Management System
 *
 * Provides loading overlays and progress feedback for database operations.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

/**
 * Overlay management for loading indicators and progress feedback.
 *
 * @since 4.1.0
 * @returns {void}
 */
(function ($) {
  /**
   * Creates loading overlay DOM structure.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function createLoadingOverlay() {
    if ($("#vr-loading-overlay").length > 0) {
      return; // Already exists
    }

    const overlayHTML = `
      <div id="vr-loading-overlay">
        <div class="vr-overlay-dialog">
          <div class="vr-overlay-content">
            <div class="vr-spinner"></div>
            <p></p>
          </div>
        </div>
      </div>
    `;
    $("body").append(overlayHTML);
  }

  /**
   * Shows loading overlay with default message.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function showLoadingOverlay() {
    // Ensure overlay exists
    createLoadingOverlay();

    // Set default message using preserved translations
    const defaultMessage =
      typeof window.vrFrasesOverlay !== "undefined" && window.vrFrasesOverlay.updatingText
        ? window.vrFrasesOverlay.updatingText
        : "Updating results...";
    $("#vr-loading-overlay .vr-overlay-content p").text(defaultMessage);

    $("#vr-loading-overlay").fadeIn(200);
  }

  /**
   * Hides loading overlay.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function hideLoadingOverlay() {
    $("#vr-loading-overlay").fadeOut(200);
  }

  /**
   * Sets up event handlers for overlay triggering.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function setupEventHandlers() {
    const triggerSelectors = [
      "#categoria",
      "#orden",
      "#frases-searchform",
      ".author-link",
      'a[href*="autor="]',
      ".reset-search",
      "a.button.button-secondary",
      ".pagination-selector",
      ".tablenav-pages select",
      ".tablenav-pages a",
    ];

    const events = {
      form: "submit",
      select: "change",
      "a, button": "click",
    };

    triggerSelectors.forEach((selector) => {
      const element = $(selector);
      if (element.length) {
        let eventType;
        if (element.is("form")) {
          eventType = events.form;
        } else if (element.is("select")) {
          eventType = events.select;
        } else {
          eventType = events["a, button"];
        }
        $(document).on(eventType, selector, showLoadingOverlay);
      }
    });
  }

  // Create public API for external use - PRESERVE existing translations
  const originalTranslations =
    typeof window.vrFrasesOverlay !== "undefined" ? window.vrFrasesOverlay : {};

  window.vrFrasesOverlay = Object.assign(originalTranslations, {
    show: function (customMessage) {
      createLoadingOverlay();
      if (customMessage) {
        $("#vr-loading-overlay .vr-overlay-content p").text(customMessage);
        $("#vr-loading-overlay").fadeIn(200);
      } else {
        showLoadingOverlay();
      }
    },
    hide: function () {
      hideLoadingOverlay();
    },
    showDefault: function () {
      const defaultMessage = originalTranslations.updatingText
        ? originalTranslations.updatingText
        : "Updating results...";
      createLoadingOverlay();
      $("#vr-loading-overlay .vr-overlay-content p").text(defaultMessage);
      $("#vr-loading-overlay").fadeIn(200);
    },
  });

  $(document).ready(function () {
    setupEventHandlers();
  });

  // Auto-hide overlay when page finishes loading (safety net)
  $(window).on("load", function () {
    setTimeout(hideLoadingOverlay, 100);
  });
})(jQuery);
