/**
 * VR-Frases Client-Side Functionality System
 *
 * Primary JavaScript module for admin interface enhancements including
 * form handling, file imports, and user interface components.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

/**
 * Controls "select all" checkboxes in admin list tables.
 *
 * @since 4.1.0
 * @param {string} formName Form identifier containing checkboxes.
 * @param {string} fieldName Checkbox field name (unused).
 * @param {boolean} checkValue True to select all, false to deselect all.
 * @returns {void}
 */
// eslint-disable-next-line no-unused-vars
function SetAllCheckBoxes(formName, fieldName, checkValue) {
  const form = document.forms[formName];
  if (!form) return;

  const checkboxes = form.querySelectorAll('input[type="checkbox"][name="ids[]"]');
  checkboxes.forEach((box) => {
    box.checked = checkValue;
  });

  // Sincroniza los checkboxes de cabecera (arriba y abajo).
  const cabeceras = form.querySelectorAll('input[type="checkbox"][name="ids[]"][value=""]');
  cabeceras.forEach((cb) => {
    cb.checked = checkValue;
  });
}

/**
 * Handles AJAX submission of plugin options form.
 *
 * @since 4.1.0
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  $("#vr_frases_options_form").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission.
    var data = $(this).serialize(); // Serialize form data.
    data += "&action=vr_frases_save_options"; // Add action for AJAX handler.

    $.post(ajaxurl, data, function (response) {
      $("#vr_frases_message").html(response).fadeIn().delay(3000).fadeOut(); // Show success or error message.
    }).fail(function (xhr) {
      $("#vr_frases_message")
        .html('<div class="error"><p>' + xhr.responseText + "</p></div>")
        .fadeIn()
        .delay(3000)
        .fadeOut();
    });
  });
});

/**
 * Initializes drag-and-drop file import system.
 *
 * @since 4.1.0
 * @returns {void}
 */
document.addEventListener("DOMContentLoaded", function () {
  const dropZone = document.getElementById("drop-zone");
  const fileInput = document.getElementById("import_files");
  const fileNamesDisplay = document.getElementById("file-name");

  // Only continue if all elements exist.
  if (!dropZone || !fileInput || !fileNamesDisplay) {
    return;
  }

  let selectedFiles = []; // Store the selected files.
  const allowedExtensions = ["csv", "txt"]; // Allowed extensions.

  dropZone.addEventListener("click", function () {
    fileInput.click();
  });

  dropZone.addEventListener("dragover", function (e) {
    e.preventDefault();
    dropZone.style.borderColor = "#007cba";
  });

  dropZone.addEventListener("dragleave", function () {
    dropZone.style.borderColor = "#ccc";
  });

  dropZone.addEventListener("drop", function (e) {
    e.preventDefault();
    dropZone.style.borderColor = "#ccc";
    if (e.dataTransfer.files.length) {
      validateAndAddFiles(e.dataTransfer.files);
    }
  });

  fileInput.addEventListener("change", function () {
    if (fileInput.files.length) {
      validateAndAddFiles(fileInput.files);
    }
  });

  /**
   * Validates and adds selected files to import input.
   *
   * @since 4.1.0
   * @param {FileList|Array<File>} files Files to validate and add.
   * @returns {void}
   */
  function validateAndAddFiles(files) {
    const dataTransfer = new DataTransfer(); // Create a new DataTransfer object to store valid files.

    Array.from(files).forEach((file) => {
      const fileExtension = file.name.split(".").pop().toLowerCase();
      if (allowedExtensions.includes(fileExtension)) {
        if (!selectedFiles.some((f) => f.name === file.name)) {
          selectedFiles.push(file);
          dataTransfer.items.add(file); // Add the valid file to the DataTransfer object.
        }
      } else {
        alert(`Invalid file type: ${file.name}. Please upload only CSV or TXT files.`);
      }
    });

    // Assign validated files to the input field.
    fileInput.files = dataTransfer.files;

    // Display the selected file names.
    displayFileNames();
  }

  /**
   * Displays selected file names in UI.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function displayFileNames() {
    const fileNames = selectedFiles.map((file) => file.name);
    fileNamesDisplay.textContent = fileNames.join(", ");
  }
});

/**
 * Initializes jQuery UI accordion component.
 *
 * @since 4.1.0
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  $("#vr-frases-accordion").accordion({
    heightStyle: "content",
    collapsible: true,
    active: false, // Allow all panels to be collapsed initially.
  });
});

/**
 * Handles Wikipedia search for author lookup.
 *
 * @since 4.1.0
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  $(".search-wikipedia").on("click", function (e) {
    e.preventDefault(); // Prevent default link behavior.

    try {
      // Get author value and ensure it's correctly formatted.
      let autor = $(this).data("autor");

      // Validate input.
      if (!autor || typeof autor !== "string") {
        console.error("Error: Author is invalid or not specified");
        return;
      }

      // Properly decode and sanitize the author name.
      autor = decodeURIComponent(autor);

      // Additional validation after decoding.
      if (autor.length === 0 || autor.length > 200) {
        console.error("Error: Author length is invalid");
        return;
      }

      // Replace spaces with underscores for Wikipedia URL format.
      autor = autor.replace(/\s+/g, "_");

      // Get the configured Wikipedia language or default to Spanish.
      const wikilang =
        typeof vrFrasesTranslations !== "undefined" && vrFrasesTranslations.wikilang
          ? vrFrasesTranslations.wikilang
          : "es";

      // Validate the language code for additional security.
      const validLangs = ["es", "en", "fr", "de", "it", "pt", "ru", "ja", "zh", "ar"]; // Add more as needed.
      const lang = validLangs.includes(wikilang) ? wikilang : "es";

      // Properly encode the URL components.
      const safeAutor = encodeURIComponent(autor);

      // Build the Wikipedia URL with sanitized components.
      const wikipediaUrl = `https://${lang}.wikipedia.org/wiki/${safeAutor}`;

      // Open the URL in a new tab.
      window.open(wikipediaUrl, "_blank", "noopener,noreferrer");
    } catch (error) {
      console.error("Error processing Wikipedia search:", error);
    }
  });
});

/**
 * Handles custom accordion toggle functionality.
 *
 * @since 4.1.0
 * @returns {void}
 */
document.addEventListener("DOMContentLoaded", function () {
  const toggles = document.querySelectorAll(".vr-accordion-toggle");
  toggles.forEach((toggle) => {
    toggle.addEventListener("click", function () {
      const content = this.nextElementSibling;
      const isOpen = content.style.display === "block";
      document.querySelectorAll(".vr-accordion-content").forEach((c) => (c.style.display = "none"));
      content.style.display = isOpen ? "none" : "block";
    });
  });
});

/**
 * Handles GDPR notice toggle functionality.
 *
 * @since 4.1.0
 * @returns {void}
 */
document.addEventListener("DOMContentLoaded", function () {
  const toggleButton = document.getElementById("vr-gdpr-toggle");
  const details = document.getElementById("vr-gdpr-details");
  const arrow = document.getElementById("vr-gdpr-arrow");
  const text = document.getElementById("vr-gdpr-text");

  // Only proceed if all elements exist.
  if (!toggleButton || !details || !arrow || !text) {
    return;
  }

  // Get translated texts from localized data (if available).
  const showText =
    typeof vrFrasesTranslations !== "undefined" && vrFrasesTranslations.gdpr_show
      ? vrFrasesTranslations.gdpr_show
      : "Show details";

  const hideText =
    typeof vrFrasesTranslations !== "undefined" && vrFrasesTranslations.gdpr_hide
      ? vrFrasesTranslations.gdpr_hide
      : "Hide details";

  toggleButton.addEventListener("click", function (e) {
    e.preventDefault();

    if (details.style.display === "none" || details.style.display === "") {
      // Show details.
      details.style.display = "block";
      arrow.className = "dashicons dashicons-arrow-up-alt2";
      text.textContent = hideText;
    } else {
      // Hide details.
      details.style.display = "none";
      arrow.className = "dashicons dashicons-arrow-down-alt2";
      text.textContent = showText;
    }
  });
});
