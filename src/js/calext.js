define('calext', ['jquery'], function($) {
  var entriesExts;

  $(function() {
    gererAffichageHaut();

    $('#creneauMailInput').on('shown.bs.modal', showModal);
  });

  function showModal() {
    for (let ext of entriesExts) {
      if (ext.data == true) {
        $('#creneauMailParticipant_ul').append("<li>" + ext.uid + "</li>");
      }
    }
  }

  function gererAffichageHaut() {
    entriesExts = Array();

    jsuids.forEach(function(valuid) {
      if (valuid.type == 'gmail') {
        entriesExts.push(valuid);
      }
    });

    let lenExts = (entriesExts && entriesExts.length > 0) ? entriesExts.length : 0;

    let i = 0;
    do {
      let extUri = $("#refexturi").clone(true);
      let showError = false;

      if (lenExts > 0) {
        const extInfo = entriesExts[i];
        const uriInfo = extInfo.uri;
        extUri.find('input').val(uriInfo);

        if (extInfo.data == false) {
          showError = true;
          extUri.prepend('<span class="text-danger text-align-center">False datas</span>');
        }
      }

      extUri.removeAttr('id');
      extUri.removeClass('d-none');

      $("#externalFBs").append(extUri);

      let buttonAdd = extUri.find('button.addExternalFB');
      buttonAdd.on("click", clickExt);

      buttonAdd.trigger('click', [showError, true]);

      i++;
    } while (i < lenExts);

    // rajoute un champ url externe si aucun vide

    let testVide = false;
    $("#externalFBs .exturi:not(#refexturi) input[type=text]").each((index, elem) => {
      if (testVide == false) {
        testVide = elem.value.length == 0 ? true : false;
      }
    });

    if (!testVide) {
      addChampsInputExt();
    }
  }

  function addChampsInputExt() {
    let extUri = $("#refexturi").clone(true).removeAttr('id').removeClass('d-none');
    extUri.on("click", clickExt);
    extUri.insertAfter($("#externalFBs .exturi:not(#refexturi)").last());
  }

  function clickExt(event, error = false, notInsertNew = false) {
    event.preventDefault();

    // correspond à #externalFBs
    let divElem = $(event.target).parent().parent();

    let extUrl = divElem.find("input[type='text']");
    if (extUrl.val().length == 0) {
      return false;
    }

    let valid = addExternalUri(divElem, extUrl, error);

    if (!valid) {
      return false;
    }

    $("#externalFBs .exturi:not(#refexturi) input[type=text]").each((index, elem) => {
      notInsertNew = (notInsertNew == false && elem.value.length == 0) ? true : false;
    });

    divElem.find("span.text-danger").remove();
    extUrl.attr('type', 'hidden');

    extUrl.after($('<pre class="pb-3 pt-1">' + extUrl.val() + '</pre>'));
    divElem.find('.addExternalFB').removeClass('addExternalFB').html('supprimer').addClass('rmExternalFB').off('click').on('click', (elem) => elem.target.parentElement.parentElement.remove());

    if (notInsertNew) {
      return false;
    }

    addChampsInputExt();
  }

  function addExternalUri(divElem, extUrl, error) {

    if (!error && extUrl.val().startsWith("https://") == false) {
      let elemDanger = divElem.find('.text-danger');
      if (elemDanger.length == 0) {
        let spanDanger = '<span class="text-danger text-align-center">Url mal formattée</span>';
        divElem.prepend(spanDanger);
      }
      return false;
    }

    return error ? false : true;
  }

});

