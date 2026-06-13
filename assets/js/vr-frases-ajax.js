/**
 * VR-Frases AJAX Management System
 *
 * Provides client-side AJAX functionality for admin interface including
 * deletion operations, form handling, and user experience enhancements.
 *
 * @package   VR_Frases
 * @author    Vicente Ruiz Gálvez
 * @version   4.1.0
 * @license   GPL-2.0+
 * @since     4.1.0
 */

/**
 * Inserts admin message with WordPress styling.
 *
 * Creates dismissible messages with auto-hide behavior and proper styling.
 *
 * @since 4.1.0
 * @param {string} text Message content to display.
 * @param {string} [type="success"] Message type: success, error, warning, info, danger.
 * @param {boolean} [persistent=false] Whether message remains until dismissed.
 * @returns {void}
 */
(function ($) {
  "use strict";

  window.vrFrasesInsertMessage = function (text, type = "success", persistent = false) {
    let className;

    switch (type) {
      case "error":
      case "danger":
        className = "notice notice-error";
        break;
      case "warning":
        className = "notice notice-warning";
        break;
      case "info":
        className = "notice notice-info";
        break;
      default:
        className = "notice notice-success";
        break;
    }

    const $notice = $("<div>")
      .addClass(className)
      .addClass("is-dismissible")
      .css("position", "relative")
      .append(`<p>${text}</p>`).append(`<button type="button" class="notice-dismiss">
        <span class="screen-reader-text">Dismiss this notice.</span>
      </button>`);

    // Add click handler for close button.
    $notice.find(".notice-dismiss").on("click", function () {
      $notice.fadeOut(300, function () {
        $(this).remove();
      });
    });

    const $wrap = $(".wrap");
    const $h1 = $wrap.find("h1");

    if ($h1.length) {
      $h1.after($notice);
    } else {
      $wrap.prepend($notice);
    }

    // Only auto-close if not persistent and not an error/warning.
    if (!persistent && type !== "error" && type !== "warning" && type !== "danger") {
      setTimeout(() => {
        $notice.fadeOut(300, function () {
          $(this).remove();
        });
      }, 5000);
    }
  };

  /**
   * Handles individual item deletion with confirmation dialog.
   *
   * Implements secure deletion workflow with user confirmation, loading states,
   * and table row removal animation.
   *
   * @since 4.1.0
   * @fires click - On .vr-delete-button elements
   * @returns {void}
   */
  $(document).on("click", ".vr-delete-button", function (e) {
    e.preventDefault();

    const $btn = $(this);
    const id = $btn.data("id");
    const tipo = $btn.data("tipo");
    const nonce = $btn.data("nonce");

    if (!id || !tipo || !nonce) {
      vrFrasesInsertMessage(
        vrFrasesTranslations.invalidData || "Missing or invalid data.",
        "error",
      );
      return;
    }

    if (
      !confirm(
        vrFrasesTranslations.confirmDeleteSingle || "Are you sure you want to delete this item?",
      )
    ) {
      return;
    }

    $btn.prop("disabled", true).addClass("deleting");

    // Show overlay with delete message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.deleting || "Deleting...");
    }

    $.post(vrFrasesAjax.ajaxurl, {
      action: "vr_frases_delete_item",
      id,
      tipo,
      nonce,
    })
      .done(function (response) {
        if (response.success) {
          vrFrasesInsertMessage(response.data.message || "Item deleted.", "success");

          const row = $btn.closest("tr");
          row.fadeOut(300, function () {
            row.remove();
          });
        } else {
          const msg = response?.data?.message || "Error deleting item.";
          vrFrasesInsertMessage(msg, "error");
        }
      })
      .fail(function () {
        vrFrasesInsertMessage("AJAX request failed.", "error");
      })
      .always(function () {
        $btn.prop("disabled", false).removeClass("deleting");

        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  });
})(jQuery);

/**
 * Handles bulk deletion of selected items.
 *
 * Processes multiple item deletion with confirmation dialog and progress feedback.
 *
 * @since 4.1.0
 * @fires click - On #vr-delitems-button element
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  $("#vr-delitems-button").on("click", function (e) {
    e.preventDefault();

    const tipo = $(this).data("tipo");
    const nonce = $(this).data("nonce");
    const confirmText = $(this).data("confirm");

    if (!confirm(confirmText)) return;

    const ids = $('.vr-checkbox[data-tipo="' + tipo + '"]:checked')
      .map(function () {
        return $(this).data("id");
      })
      .get();

    if (ids.length === 0) {
      vrFrasesInsertMessage(vrFrasesAjax.noItemsSelected, "warning", false);
      return;
    }

    $(this).prop("disabled", true);

    // Show overlay with delete message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.deleting || "Deleting items...");
    }

    $.post(vrFrasesAjax.ajaxurl, {
      action: "vr_frases_delete_multiple_items",
      tipo: tipo,
      nonce: nonce,
      ids: ids,
    })
      .done(function (response) {
        $("#vr-delitems-button").prop("disabled", false);

        if (response.success) {
          ids.forEach(function (id) {
            $('.vr-checkbox[data-id="' + id + '"]')
              .closest("tr")
              .fadeOut(300, function () {
                $(this).remove();
              });
          });
          vrFrasesInsertMessage(
            response?.data?.message || vrFrasesAjax.defaultSuccessMessage || "Items deleted.",
            "success",
          );
        } else {
          vrFrasesInsertMessage(
            response?.data?.message || vrFrasesAjax.defaultErrorMessage || "An error occurred.",
            "error",
          );
        }
      })
      .fail(function () {
        $("#vr-delitems-button").prop("disabled", false);
        vrFrasesInsertMessage(vrFrasesAjax.ajaxError || "AJAX request failed.", "error");
      })
      .always(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  });
});

/**
 * Initializes generic AJAX form handlers for content creation.
 *
 * Sets up form submission handlers for classes, themes, authors, and quotes
 * with validation, table updates, and user feedback.
 *
 * @since 4.1.0
 * @fires DOMContentLoaded
 * @returns {void}
 */
document.addEventListener("DOMContentLoaded", function () {
  /**
   * Initializes generic AJAX form submission for adding items.
   *
   * Creates reusable form handler for classes, themes, and similar entities
   * with validation, table updates, and error handling.
   *
   * @since 4.1.0
   * @param {Object} config - Configuration object
   * @param {string} config.formId - Form element ID
   * @param {string} config.inputName - Input field name
   * @param {string} config.ajaxAction - WordPress AJAX action
   * @param {string} config.tbodySelector - Table body selector
   * @param {Function} config.rowHtml - Function to generate row HTML
   * @returns {void}
   */
  function vrFrasesInitAltaGeneric(config) {
    var form = document.getElementById(config.formId);
    if (!form) return;
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var value = form[config.inputName].value.trim();
      var nonce = form.nonce.value;
      if (!value) {
        form[config.inputName].setCustomValidity(
          vrFrasesTranslations.fieldRequired || "This field is required.",
        );
        form[config.inputName].reportValidity();
        return;
      }

      // Clear any previous validation message
      form[config.inputName].setCustomValidity("");
      var btn = document.getElementById("addnew");
      if (btn) btn.disabled = true;

      // Show overlay with saving message.
      if (window.vrFrasesOverlay) {
        window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
      }

      var postData = {
        action: config.ajaxAction,
        nonce: nonce,
      };
      postData[config.inputName] = value;
      fetch(ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(postData),
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (btn) btn.disabled = false;
          if (data.success) {
            window.vrFrasesInsertMessage(data.data.message, "success");
            form[config.inputName].value = "";
            // Add new row(s) to list without page reload.
            if (Array.isArray(data.data.added)) {
              data.data.added.forEach(function (obj) {
                var id = obj.id;
                var name = obj.name;
                var rowHtml = config.rowHtml(id, name, form.nonce.value);
                var $tbody = document.querySelector(config.tbodySelector);
                if ($tbody) {
                  $tbody.insertAdjacentHTML("afterbegin", rowHtml);
                }
              });
            }
          } else {
            window.vrFrasesInsertMessage(data.data.message, "error");
          }
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          window.vrFrasesInsertMessage("AJAX error.", "error");
        })
        .finally(function () {
          // Hide overlay.
          if (window.vrFrasesOverlay) {
            window.vrFrasesOverlay.hide();
          }
        });
    });
  }

  /**
   * Handles AJAX author addition form submission.
   *
   * Processes author creation with validation and form clearing on success.
   * Authors require full page refresh due to complex table structure.
   *
   * @since 4.1.0
   * @returns {void}
   */
  var authorForm = document.getElementById("add-author-form");
  if (authorForm) {
    authorForm.addEventListener("submit", function (e) {
      e.preventDefault();
      var autor = authorForm.autor.value.trim();
      var nonce = authorForm.nonce.value;
      if (!autor) {
        authorForm.autor.setCustomValidity(
          vrFrasesTranslations.fieldRequired || "This field is required.",
        );
        authorForm.autor.reportValidity();
        return;
      }

      // Clear any previous validation message
      authorForm.autor.setCustomValidity("");
      var btn = authorForm.querySelector('input[type="submit"], button[type="submit"]');
      if (btn) btn.disabled = true;

      // Show overlay with saving message.
      if (window.vrFrasesOverlay) {
        window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
      }

      var postData = new URLSearchParams({
        action: "vrfr_add_autor",
        autor: autor,
        nonce: nonce,
      });
      fetch(ajaxurl, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: postData,
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (btn) btn.disabled = false;
          if (data.success) {
            window.vrFrasesInsertMessage(data.data.message, "success");
            authorForm.autor.value = "";
          } else {
            window.vrFrasesInsertMessage(data.data.message, "error");
          }
        })
        .catch(function () {
          if (btn) btn.disabled = false;
          window.vrFrasesInsertMessage("AJAX error.", "error");
        })
        .finally(function () {
          // Hide overlay.
          if (window.vrFrasesOverlay) {
            window.vrFrasesOverlay.hide();
          }
        });
    });
  }
});

/**
 * Handles AJAX form submission for quotes.
 *
 * @since 4.1.0
 * @returns {void}
 */
document.addEventListener("DOMContentLoaded", function () {
  var form = document.getElementById("addnew_frase");
  if (!form) return;
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var frase = form.frase.value.trim();
    var autor = form.autor.value.trim();
    var nonce = form.nonce.value;
    // Form validation is handled by HTML5 required attributes
    // The browser will automatically validate and show native messages
    var btn = form.querySelector('input[type="submit"], button[type="submit"]');
    if (btn) btn.disabled = true;

    // Show overlay with saving message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
    }

    var postData = new URLSearchParams({
      action: "vrfr_add_frase",
      frase: frase,
      autor: autor,
      nonce: nonce,
    });
    fetch(ajaxurl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: postData,
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (btn) btn.disabled = false;
        if (data.success) {
          window.vrFrasesInsertMessage(data.data.message, "success");
          form.frase.value = "";
          form.autor.value = "";
        } else {
          window.vrFrasesInsertMessage(data.data.message, "error");
        }
      })
      .catch(function () {
        if (btn) btn.disabled = false;
        window.vrFrasesInsertMessage("AJAX error.", "error");
      })
      .finally(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  });
});


jQuery(document).ready(function ($) {
  /**
   * Fetches author data and opens a quick-edit form for the author.
   *
   * @since 4.1.0
   * @returns {void}
   */
  $(document).on("click", '.quick-edit[data-context="autores"]', function (event) {
    event.preventDefault();

    const id = $(this).data("id");
    const nonce = vrFrasesTranslations.nonceAutores; // Nonce for authors actions.
    const row = $(this).closest("tr");

    // Save the original row content.
    const originalContent = row.html();

    // Verify if the ID is defined.
    if (!id) {
      alert(vrFrasesTranslations.error || "Error: Invalid ID.");
      return;
    }

    // Fetch author data via AJAX.
    $.post(
      vrFrasesTranslations.ajaxurl,
      {
        action: "get_autor_data",
        idautor: id,
        nonce: nonce,
      },
      function (response) {
        if (response.success) {
          renderQuickEditForm(response.data, row, originalContent);
        } else {
          alert(response.data?.message || vrFrasesTranslations.error || "Unknown error.");
        }
      },
    ).fail(function () {
      alert(vrFrasesTranslations.error || "Unknown error.");
    });
  });

  /**
   * Renders the quick-edit form for an author and binds form actions.
   *
   * @since 4.1.0
   * @param {Object} autorData Author data object returned by AJAX.
   * @param {jQuery} row The table row jQuery element to replace.
   * @param {string} originalContent The original HTML content of the row.
   * @returns {void}
   */
  function renderQuickEditForm(autorData, row, originalContent) {
    const saveButtonLabel = vrFrasesTranslations.save || "Save";
    const cancelButtonLabel = vrFrasesTranslations.cancel || "Cancel";

    // Format dates according to rules.

    // Form in 2 rows and 3 columns.
    row.html(`
      <td colspan="10">
        <form method="post" action="" class="vr-quick-edit-form">
          <input type="hidden" name="accion" value="quickedit" />
          <input type="hidden" name="nonce" value="${vrFrasesTranslations.nonceAutores}" />
          <input type="hidden" name="idautor" value="${autorData.idautor}" />
          <table style="width: 100%; border-collapse: collapse;">
            <tr>
              <td style="width:20%">
                <label for="autor">${vrFrasesTranslations.author}:</label><br>
                <input type="text" name="autor" id="autor" value="${autorData.autor || ""}" size="40" required />
              </td>
              <td style="width:20%">
                <label for="pais">${vrFrasesTranslations.country}:</label><br>
                <input type="text" name="pais" id="pais" value="${autorData.pais || ""}" size="40" />
              </td>
              <td style="width:55%" rowspan="2">
                <label for="datos">${vrFrasesTranslations.details}:</label><br>
                <textarea name="datos" id="datos" rows="4" cols="100">${autorData.datos || ""}</textarea>
              </td>
              <td style="width:5%" rowspan="2" display: flex; justify-content: center; padding-top:8px;">
                <button type="submit" class="button">${saveButtonLabel}</button>
                <button type="button" class="button cancel-edit">${cancelButtonLabel}</button>
              </td>
            </tr>
            <tr>
              <td>
                <label for="nacido">${vrFrasesTranslations.birthDate}:</label><br>
                <input type="date" name="nacido" id="nacido" value="${autorData.nacido || ""}" />
                <label style="margin-left:8px;">
                  <input type="checkbox" name="nacido_acdc" value="AC" ${autorData.nacido_acdc === "AC" ? "checked" : ""} /> AC
                </label>
              </td>
              <td>
                <label for="muerto">${vrFrasesTranslations.deathDate}:</label><br>
                <input type="date" name="muerto" id="muerto" value="${autorData.muerto || ""}" />
                <label style="margin-left:8px;">
                  <input type="checkbox" name="muerto_acdc" value="AC" ${autorData.muerto_acdc === "AC" ? "checked" : ""} /> AC
                </label>
              </td>
            </tr>
          </table>
        </form>
      </td>
    `);

    // Handle cancel button.
    row.find(".cancel-edit").on("click", function () {
      row.html(originalContent);
    });

    // Handle AJAX save for quick-edit.
    row.find("form.vr-quick-edit-form").on("submit", function (e) {
      e.preventDefault();
      const $form = $(this);
      const idautor = $form.find("input[name='idautor']").val();
      const autor = $form.find("input[name='autor']").val();
      const pais = $form.find("input[name='pais']").val();
      const nacido = $form.find("input[name='nacido']").val();
      const nacido_acdc = $form.find("input[name='nacido_acdc']").is(":checked") ? "AC" : "";
      const muerto = $form.find("input[name='muerto']").val();
      const muerto_acdc = $form.find("input[name='muerto_acdc']").is(":checked") ? "AC" : "";
      const datos = $form.find("textarea[name='datos']").val();
      const nonce = $form.find("input[name='nonce']").val();

      const postData = {
        action: "vr_frases_quick_edit_autores",
        idautor: idautor,
        autor: autor,
        pais: pais,
        nacido: nacido,
        nacido_acdc: nacido_acdc,
        muerto: muerto,
        muerto_acdc: muerto_acdc,
        datos: datos,
        nonce: nonce,
      };

      // Disable button to prevent double submit.
      $form.find("button[type='submit']").prop("disabled", true);

      // Show overlay with saving message.
      if (window.vrFrasesOverlay) {
        window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
      }

      $.post(vrFrasesAjax.ajaxurl, postData)
        .done(function (response) {
          if (response.success && response.data && response.data.idautor) {
            // Render updated row using global function.
            const updatedRow = window.renderAutorRow(response.data, nonce);
            row.replaceWith(updatedRow);
            window.vrFrasesInsertMessage(
              response.data.message ||
                vrFrasesTranslations.authorUpdated ||
                "Author updated successfully.",
              "success",
            );
          } else {
            const msg =
              response?.data?.message ||
              vrFrasesTranslations.errorUpdatingAuthor ||
              "Error updating author.";
            window.vrFrasesInsertMessage(msg, "error");
            row.html(originalContent);
          }
        })
        .fail(function () {
          window.vrFrasesInsertMessage(
            vrFrasesTranslations.ajaxErrorUpdatingAuthor || "AJAX error updating author.",
            "error",
          );
          row.html(originalContent);
        })
        .always(function () {
          $form.find("button[type='submit']").prop("disabled", false);

          // Hide overlay.
          if (window.vrFrasesOverlay) {
            window.vrFrasesOverlay.hide();
          }
        });
    });
  }

  // Handler for Wikipedia icon in authors table.
  $(document).on("click", ".search-wikipedia", function (e) {
    e.preventDefault();
    const $btn = $(this);
    const autor = $btn.data("autor");
    const nonce = vrFrasesTranslations.nonceAutores;
    if (!autor || !nonce) {
      window.vrFrasesInsertMessage(
        vrFrasesTranslations.error || "Error: Autor o nonce no definido.",
        "error",
      );
      return;
    }
    $btn.prop("disabled", true).addClass("loading");
    $.post(vrFrasesTranslations.ajaxurl, {
      action: "search_wikipedia",
      autor: autor,
      nonce: nonce,
    })
      .done(function (response) {
        if (response.success && response.data && response.data.url) {
          window.open(response.data.url, "_blank");
        } else {
          const msg =
            response?.data?.message ||
            vrFrasesTranslations.error ||
            "No se encontró página en Wikipedia.";
          window.vrFrasesInsertMessage(msg, "error");
        }
      })
      .fail(function () {
        window.vrFrasesInsertMessage(
          vrFrasesTranslations.error || "Error en la búsqueda de Wikipedia.",
          "error",
        );
      })
      .always(function () {
        $btn.prop("disabled", false).removeClass("loading");
      });
  });
}); // Correct closure of ready block.

/**
 * Renders an author table row with provided data (global reusable function).
 *
 * @since 4.1.0
 * @param {Object} data - Author data object
 * @param {number} data.idautor - Author ID
 * @param {string} data.autor - Author name
 * @param {string} data.pais - Country
 * @param {string} data.nacido - Birth date
 * @param {string} data.nacido_acdc - Birth era (AC/DC)
 * @param {string} data.muerto - Death date
 * @param {string} data.muerto_acdc - Death era (AC/DC)
 * @param {string} data.datos - Additional data
 * @param {number} data.contador - Quote count
 * @param {string} nonce - Security nonce
 * @returns {string} HTML string for the table row
 */
window.renderAutorRow = function renderAutorRow(data, nonce) {
  // HTML escape utility.
  function escapeHtml(str) {
    if (typeof str !== "string") return str;
    return str.replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  // Format dates according to plugin rules.
  function formatDate(date, suffix) {
    if (date === "0001-01-01") return "";
    const [year, month, day] = date.split("-");
    if (day === "01" && month === "01" && suffix === "AC") {
      return `${year} AC`;
    }
    return `${day}/${month}/${year}`;
  }

  // Auxiliary links and buttons.
  // Link to view more quotes from this author (same as in PHP).
  const quotesUrl = `${vrFrasesTranslations.adminUrlBase || ""}?page=vrfr_managefrases&accion=buscar&autor=${encodeURIComponent(data.autor)}`;
  const authorLink = `<a title="${vrFrasesTranslations.viewMoreQuotes || "View more quotes from this Author..."}" href="${quotesUrl}">${escapeHtml(data.autor)}</a>`;
  // Wikipedia link (always present, same as in PHP).
  const wikiBtn = `<a href="javascript:void(0);" class="search-wikipedia" data-autor="${escapeHtml(data.autor)}" title="${vrFrasesTranslations.searchWikipedia || "Search on Wikipedia"}"><span class="dashicons dashicons-external"></span></a>`;
  const nacidoFmt = formatDate(data.nacido || "", data.nacido_acdc || "");
  const muertoFmt = formatDate(data.muerto || "", data.muerto_acdc || "");

  // Delete button only if counter === 0.
  let deleteButton = "";
  let checkboxHtml = "";
  if (parseInt(data.contador, 10) === 0) {
    deleteButton = `<button type="button" class="button vr-delete-button" title="${vrFrasesTranslations.deleteThisAuthor || "Delete this Author"}" data-id="${data.idautor}" data-tipo="autores" data-nonce="${nonce}"><span class="dashicons dashicons-trash" style="vertical-align: text-bottom; color: #a00;"></span> ${vrFrasesTranslations.delete || "Delete"}</button>`;
    checkboxHtml = `<input type="checkbox" class="vr-checkbox" data-id="${data.idautor}" data-tipo="autores">`;
  } else {
    deleteButton = `<span class="button disabled"><span class="dashicons dashicons-no" style="vertical-align: text-bottom;"></span> ${vrFrasesTranslations.delete || "Delete"}</span>`;
    checkboxHtml = "";
  }

  return `
      <tr id="autor-${data.idautor}">
        <th scope="row" class="check-column vr-column-center">
          ${checkboxHtml}
        </th>
        <td class="vr-column-center">${data.idautor}</td>
        <td>
          ${authorLink}
          ${wikiBtn}
        </td>
        <td>${escapeHtml(data.pais)}</td>
        <td>${nacidoFmt}</td>
        <td>${muertoFmt}</td>
        <td>${escapeHtml(data.datos)}</td>
        <td class="vr-column-center">${data.contador}</td>
        <td class="vr-column-center">
          <button type="button" class="quick-edit button" data-context="autores" data-id="${data.idautor}">
            <span class="dashicons dashicons-edit" style="vertical-align: text-bottom;"></span> ${vrFrasesTranslations.editData || "Edit Data"}
          </button>
        </td>
        <td class="vr-column-center">
          ${deleteButton}
        </td>
      </tr>
        `;
};

/**
 * Handles AJAX edit quote functionality.
 *
 * Loads quote data and saves changes via AJAX for the edit form.
 * Uses the same patterns as other functions in this file.
 *
 * @since 4.1.0
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  if ($("#vr-edit-quote-form").length) {
    // Load quote data on page load.
    loadQuoteData();

    // Save button click handler.
    $("#vr-save-quote-btn").on("click", function () {
      saveQuoteData();
    });
  }

  /**
   * Loads quote data for editing via AJAX.
   *
   * Retrieves quote information from server and populates the edit form
   * with quote text, author, classes, and themes data.
   *
   * @since 4.1.0
   * @returns {void}
   */
  function loadQuoteData() {
    // Show overlay with loading message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.loading || "Loading data...");
    }

    $.post(vrFrasesAjax.ajaxurl, {
      action: "vr_frases_get_frase_data",
      id: $("#edit-quote-id").val(),
      nonce: $("#edit-quote-nonce").val(),
    })
      .done(function (response) {
        if (response.success) {
          populateEditForm(response.data);
          $("#vr-edit-quote-loading").hide();
          $("#vr-edit-quote-form").show();
        } else {
          showEditError(response.data.message);
        }
      })
      .fail(function () {
        showEditError(vrFrasesAjax.errorLoadingData || "Error loading quote data.");
      })
      .always(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  }

  /**
   * Populate edit form with loaded quote data.
   *
   * Populates edit form with quote data and dropdown options.
   *
   * @since 4.1.0
   * @param {Object} data Quote data from server response.
   * @returns {void}
   */
  function populateEditForm(data) {
    // Fill form fields.
    $("#edit-quote-text").val(data.frase.frase);
    $("#edit-quote-author").val(data.frase.autor);
  }

  /**
   * Save quote data to server via AJAX request with comprehensive error handling.
   *
   * Handles both creation and updating of quote records with validation,
   * security nonce verification, and detailed success/error callbacks.
   *
   * @since 4.1.0
   * @returns {void} Executes AJAX save operation with loading states.
   */
  function saveQuoteData() {
    // Show loading state.
    $("#vr-save-quote-btn").hide();
    $("#vr-save-quote-loading").show();
    $("#vr-edit-quote-success").hide();
    $("#vr-edit-quote-error").hide();

    // Show overlay with saving message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
    }

    $.post(vrFrasesAjax.ajaxurl, {
      action: "vr_frases_save_frase_data",
      idfrase: $("#edit-quote-id").val(),
      frase: $("#edit-quote-text").val(),
      autor: $("#edit-quote-author").val(),
      nonce: $("#edit-quote-nonce").val(),
    })
      .done(function (response) {
        $("#vr-save-quote-loading").hide();
        $("#vr-save-quote-btn").show();

        if (response.success) {
          window.vrFrasesInsertMessage(
            response.data.message ||
              vrFrasesTranslations.quoteUpdated ||
              "Quote updated successfully.",
            "success",
          );
          setTimeout(function () {
            window.location.href = "admin.php?page=vrfr_managefrases";
          }, 2000);
        } else {
          window.vrFrasesInsertMessage(response.data.message, "error");
        }
      })
      .fail(function () {
        $("#vr-save-quote-loading").hide();
        $("#vr-save-quote-btn").show();
        window.vrFrasesInsertMessage(
          vrFrasesAjax.errorSavingData || "Error saving quote data.",
          "error",
        );
      })
      .always(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  }

  /**
   * Display error message in quote edit interface with user-friendly formatting.
   *
   * Shows error messages in the edit quote modal with proper state management,
   * hiding loading indicators and displaying error messages clearly.
   *
   * @since 4.1.0
   * @param {string} message Error message to display to user.
   * @returns {void} Updates UI to show error state.
   */
  function showEditError(message) {
    $("#vr-edit-quote-loading").hide();
    $("#vr-edit-quote-error-message").text(message);
    $("#vr-edit-quote-error").show();
  }

  // === IMPORT MANAGEMENT AJAX ===.

  /**
   * Handles save imported quote via AJAX.
   *
   * Saves individual imported quotes with selected class and themes,
   * providing immediate feedback and updating the interface.
   *
   * @since 4.1.0
   * @returns {void}
   */
  $(document).on("click", ".vr-save-import-button", function (e) {
    e.preventDefault();

    const button = $(this);
    const idimport = button.data("id");
    const row = button.closest("tr");

    // Show overlay with saving message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.saving || "Saving...");
    }

    // Disable button and show loading state.
    button.prop("disabled", true);
    const originalText = button.html();
    button.html(
      '<span class="dashicons dashicons-update-alt" style="animation: spin 1s linear infinite; vertical-align: text-bottom;"></span> ' +
        (vrFrasesAjax.saving || "Saving..."),
    );

    // Send AJAX request.
    $.post(vrFrasesAjax.ajaxurl, {
      action: "vr_frases_save_import",
      idimport: idimport,
      nonce: vrFrasesAjax.nonceImport,
    })
      .done(function (response) {
        if (response.success) {
          // Show success message.
          vrFrasesInsertMessage(
            response.data.message ||
              vrFrasesAjax.defaultSuccessMessage ||
              "Quote saved successfully.",
            "success",
          );

          // Animate row removal.
          row.fadeOut(300, function () {
            $(this).remove();

            // Check if this was the last row.
            if ($(".wp-list-table tbody tr").length === 0) {
              // Reload page to show "no imported quotes" message.
              setTimeout(function () {
                window.location.reload();
              }, 1000);
            }
          });
        } else {
          // Show error message.
          vrFrasesInsertMessage(
            response.data.message || vrFrasesAjax.defaultErrorMessage || "Error saving quote.",
            "error",
          );

          // Restore button.
          button.prop("disabled", false);
          button.html(originalText);
        }
      })
      .fail(function () {
        // Show error message.
        vrFrasesInsertMessage(vrFrasesAjax.errorSavingData || "Error saving quote data.", "error");

        // Restore button.
        button.prop("disabled", false);
        button.html(originalText);
      })
      .always(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  });

  // === FILE IMPORT AJAX ===.

  /**
   * Handle file import form submission via AJAX
   */
  $(document).on("submit", "#import-form", function (e) {
    e.preventDefault();

    const form = $(this);
    const fileInput = form.find("#import_files")[0];
    const files = fileInput.files;

    // Validate that files are selected.
    if (!files || files.length === 0) {
      vrFrasesInsertMessage(
        vrFrasesAjax.noFilesSelected || "Please select files to import.",
        "error",
      );
      return;
    }

    // Validate file types.
    const allowedTypes = ["text/csv", "text/plain", "application/csv"];
    const allowedExtensions = ["csv", "txt"];

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      const extension = file.name.split(".").pop().toLowerCase();

      if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(extension)) {
        vrFrasesInsertMessage(
          (
            vrFrasesAjax.invalidFileType ||
            'Invalid file type for "{filename}". Please upload CSV or TXT files only.'
          ).replace("{filename}", file.name),
          "error",
        );
        return;
      }
    }

    // Show overlay with importing message.
    if (window.vrFrasesOverlay) {
      window.vrFrasesOverlay.show(vrFrasesOverlay.loading || "Processing files...");
    }

    // Prepare FormData.
    const formData = new FormData();
    formData.append("action", "vr_frases_import_files");
    formData.append("nonce", vrFrasesAjax.nonceImport);

    // Add all selected files.
    for (let i = 0; i < files.length; i++) {
      formData.append("import_files[]", files[i]);
    }

    // Send AJAX request.
    $.ajax({
      url: vrFrasesAjax.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      timeout: 60000, // 60 seconds timeout for large files.
    })
      .done(function (response) {
        if (response.success) {
          // Show duplicates first if any (this is independent and permanent).
          if (response.data.duplicates && response.data.duplicates.length > 0) {
            showDuplicatesModal(response.data.duplicates);
          }

          // Show success message with detailed info (shorter, without duplicate count).
          let message = response.data.message;

          // Only show basic success message, duplicates are shown in the dedicated panel above.
          vrFrasesInsertMessage(message, "success", false); // Not persistent since duplicates have their own panel.

          // Clear the file input.
          fileInput.value = "";
          $("#file-name").text("");

          // Reload the imported data table after a longer delay to allow reading duplicates.
          const reloadDelay = response.data.duplicates_count > 0 ? 8000 : 3000; // 8s if duplicates, 3s if not.
          setTimeout(function () {
            // Only reload if duplicates panel is not visible (user hasn't closed it).
            if (
              response.data.duplicates_count === 0 ||
              $("#duplicados-lista-import").length === 0
            ) {
              // Show overlay before auto-reload.
              if (window.vrFrasesOverlay) {
                window.vrFrasesOverlay.show(vrFrasesOverlay.updatingText || "Updating results...");
              }
              setTimeout(function () {
                window.location.reload();
              }, 200);
            } else {
              // Show a notice that page will reload when duplicates panel is closed.
              const reloadNotice = $(`
                <div id="reload-notice" class="notice notice-info" style="margin:10px 0;padding:10px;border-left:4px solid #72aee6;background:#f0f6fc;">
                  <p style="margin:0;"><strong><span class="dashicons dashicons-info" style="margin-right:5px;"></span>${vrFrasesAjax.pageWillReload || "Page will reload automatically when you close the duplicates panel."}</strong></p>
                </div>
              `);
              $("#duplicados-lista-import").after(reloadNotice);
            }
          }, reloadDelay);
        } else {
          vrFrasesInsertMessage(
            response.data.message || vrFrasesAjax.defaultErrorMessage || "Error importing files.",
            "error",
            true, // Error messages should be persistent.
          );
        }
      })
      .fail(function (xhr, status) {
        let errorMessage = vrFrasesAjax.errorImportingFiles || "Error importing files.";

        if (status === "timeout") {
          errorMessage =
            vrFrasesAjax.importTimeout || "Import timeout. Please try with smaller files.";
        }

        vrFrasesInsertMessage(errorMessage, "error", true); // Error messages should be persistent.
      })
      .always(function () {
        // Hide overlay.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.hide();
        }
      });
  });

  /**
   * Displays duplicate quotes detection results in a modal panel.
   *
   * @since 4.1.0
   * @param {Array} duplicates - Array of duplicate quote objects
   * @returns {void}
   */
  function showDuplicatesModal(duplicates) {
    // Create a more prominent and user-friendly duplicates display.
    let duplicatesHtml = `
      <div id="duplicados-lista-import" class="vr-duplicates-panel" style="margin:20px 0;padding:20px;border:2px solid #ffb900;background:#fff8e5;position:relative;box-shadow:0 2px 8px rgba(0,0,0,0.15);border-radius:5px;z-index:1000;">
        <button type="button" class="vr-duplicates-close" style="position: absolute; top: 10px; right: 10px; border: 2px solid #ffb900; margin: 0; padding: 8px 12px; background: #fff; color: #8f6000; cursor: pointer; border-radius: 3px; font-weight: bold; font-size: 12px;">
          <span class="dashicons dashicons-dismiss" style="font-size: 14px; width: 14px; height: 14px; margin-right: 3px;"></span>
          ${vrFrasesAjax.hide || "CLOSE"}
        </button>
        <div style="margin-right:60px;">
          <h3 style="margin:0 0 15px 0;color:#8f6000;font-size:16px;font-weight:bold;">
            <span class="dashicons dashicons-warning" style="color:#ffb900;margin-right:8px;font-size:18px;"></span>
            ${vrFrasesAjax.duplicatesFoundTitle || "Duplicate records found:"} (${duplicates.length})
          </h3>
          <div style="max-height:350px;overflow-y:auto;border:2px solid #ddd;background:#fff;padding:15px;border-radius:5px;box-shadow:inset 0 1px 3px rgba(0,0,0,0.1);">
            <ul style="margin:0;padding:0;list-style:none;">`;

    duplicates.forEach(function (dup) {
      let typeText = "";
      let typeColor = "#666";

      if (dup.type === "database") {
        typeText = vrFrasesAjax.alreadyInDatabase || "Already in database";
        typeColor = "#d63638";
      } else if (dup.type === "import") {
        typeText = vrFrasesAjax.alreadyInImport || "Already in import list";
        typeColor = "#dba617";
      }

      duplicatesHtml += `
        <li style="padding:8px 0;border-bottom:1px solid #f1f1f1;">
          <div style="font-weight:bold;color:#333;margin-bottom:3px;">${dup.frase}</div>
          <div style="color:#666;font-size:13px;margin-bottom:3px;">
            <strong>${vrFrasesAjax.author || "Author"}:</strong> ${dup.autor}
          </div>`;

      if (typeText) {
        duplicatesHtml += `
          <div style="margin-bottom:3px;">
            <span style="background:${typeColor};color:#fff;padding:2px 6px;border-radius:3px;font-size:11px;font-weight:bold;">
              ${typeText}
            </span>
          </div>`;
      }

      if (dup.file) {
        duplicatesHtml += `
          <div style="color:#888;font-size:12px;">
            <span class="dashicons dashicons-media-document" style="width:12px;height:12px;font-size:12px;"></span>
            ${vrFrasesAjax.file || "File"}: <strong>${dup.file}</strong>`;

        if (dup.line) {
          duplicatesHtml += ` | ${vrFrasesAjax.line || "Line"}: <strong>${dup.line}</strong>`;
        }

        duplicatesHtml += "</div>";
      }

      duplicatesHtml += "</li>";
    });

    duplicatesHtml += `
            </ul>
          </div>
          <p style="margin:10px 0 0 0;color:#8f6000;font-style:italic;font-size:13px;">
            <span class="dashicons dashicons-info" style="color:#ffb900;margin-right:3px;font-size:13px;"></span>
            ${vrFrasesAjax.duplicateNotice || "These quotes were not imported because they already exist."}
          </p>
        </div>
      </div>`;

    // Remove previous duplicates list if exists.
    $("#duplicados-lista-import").remove();

    // Insert after the form.
    const $duplicatesPanel = $(duplicatesHtml);

    // Add click handler for close button with auto-reload.
    $duplicatesPanel.find(".vr-duplicates-close").on("click", function () {
      $duplicatesPanel.fadeOut(300, function () {
        $(this).remove();
        // Remove reload notice if exists.
        $("#reload-notice").remove();

        // Show overlay while reloading.
        if (window.vrFrasesOverlay) {
          window.vrFrasesOverlay.show(vrFrasesOverlay.updatingText || "Updating results...");
        }

        // Reload page after closing duplicates panel.
        setTimeout(function () {
          window.location.reload();
        }, 500);
      });
    });

    $("#import-form").after($duplicatesPanel);
  }

  // Add spinning animation for loading icons.
  $("<style>")
    .prop("type", "text/css")
    .html(
      `
      @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
      }
    `,
    )
    .appendTo("head");
});

/**
 * Converts WordPress options forms to AJAX submission with auto-hide messages.
 *
 * Intercepts standard WordPress settings forms to provide better user feedback
 * with success messages that auto-hide after 5 seconds.
 *
 * @since 4.1.0
 * @fires submit - On form[action='options.php'] elements
 * @returns {void}
 */
jQuery(document).ready(function ($) {
  // Only run on settings pages with options forms
  if ($("form[action='options.php']").length === 0) {
    return;
  }

  // Intercept the WordPress options form in settings pages
  $("form[action='options.php']").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitButton = $form.find("input[type='submit']");
    var originalButtonText = $submitButton.val();

    // Disable submit button and show loading state
    $submitButton.prop("disabled", true).val(vrFrasesAjax.saving);

    // Submit form via AJAX
    $.post("options.php", $form.serialize())
      .done(function () {
        // Show success message using the plugin's message system (auto-hides after 5s)
        window.vrFrasesInsertMessage(vrFrasesTranslations.settingsSavedMessage, "success", false);
      })
      .fail(function () {
        // Show error message (persistent until manually dismissed)
        window.vrFrasesInsertMessage(vrFrasesTranslations.settingsErrorMessage, "error", true);
      })
      .always(function () {
        // Restore button state
        $submitButton.prop("disabled", false).val(originalButtonText);
      });
  });
});
