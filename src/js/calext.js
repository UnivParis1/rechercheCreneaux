define('calext', ['jquery', 'on-change'], function($, onChange) {

  var entriesExts = [];

  $(function() {

    for (let valuid of jsuids) {
      if (valuid.type == 'gmail') {
        entriesExts.push(valuid);
      }
    }

    // observer des changements sur objet, appel uniquement après après mis les valeurs existantes
    entriesExts = onChange.default(entriesExts, () => ajouterOuPasNewInput());

    initierVisuelFBExternes();

    $('#creneauMailInput').on('shown.bs.modal', showModal);
  });

  function showModal() {
    for (let ext of entriesExts) {
      if (ext.data == true) {
        $('#creneauMailParticipant_ul').append("<li>" + ext.uid + "</li>");
      }
    }
  }

  function ajouterOuPasNewInput() {
    let test = true;

    $("#externalFBs .exturi:not(#refexturi)").each((element, obj) => {
      if ($(obj).find("input[type='text']").length > 0) {
        test = false;
      }
    });

    if (test) {
      addChampsInputExt();
    }
  }

  function initierVisuelFBExternes() {

    let lenExts = (entriesExts && entriesExts.length > 0) ? entriesExts.length : 0;

    let i = 0;
    do {
      let divEntry = $("#refexturi").clone(true);

      divEntry.removeAttr('id');
      divEntry.removeClass('d-none');

      $("#externalFBs").append(divEntry);

      let buttonAdd = divEntry.find('button.addExternalFB');
      buttonAdd.on("click", clickExt);

      if (lenExts > 0) {
        const entry = entriesExts[i];
        divEntry.find('input').val(entry.uri);

        if (entry.data == false) {
          divEntry.prepend('<span class="text-danger text-align-center">False datas</span>');
        } else {
          let divElem = buttonAdd.parent().parent();
          _ajouterInputVisuel(divElem, divElem.find("input[type='text']"), entry.uri, true);

          if (i == lenExts - 1)
            ajouterOuPasNewInput();
        }
      }

      i++;
    } while (i < lenExts);
  }

  function addChampsInputExt() {
    let extUri = $("#refexturi").clone(true).removeAttr('id').removeClass('d-none');
    extUri.on("click", clickExt);
    extUri.insertAfter($("#externalFBs .exturi:not(#refexturi)").last());
  }

  function _ajouterInputVisuel(divElem, inputUrl, uri, trigger) {
    let entry = { type: 'gmail', uri: uri, data: false, valid: false };

    if (!testExternalUri(divElem, inputUrl)) {
      entriesExts.push(entry);
      return false;
    }

    entry.valid = true;

    divElem.find("span.text-danger").remove();
    inputUrl.attr('type', 'hidden');

    inputUrl.after($('<pre class="pb-3 pt-1">' + inputUrl.val() + '</pre>'));
    let buttonAddExternalFB = divElem.find('.addExternalFB');
    buttonAddExternalFB.removeClass('addExternalFB').html('supprimer').addClass('rmExternalFB').off('click');

    let iNewEntry;
    if (!trigger) {
      iNewEntry = entriesExts.push(entry) - 1;
    } else {
      iNewEntry = entriesExts.findIndex((elem) => elem.uri == uri);
    }

    buttonAddExternalFB.on('click', (elem) => {
      entriesExts.splice(iNewEntry, 1);
      elem.target.parentElement.parentElement.remove();
    });
  }

  function clickExt(event) {
    event.preventDefault();

    // correspond à #externalFBs
    let divElem = $(event.target).parent().parent();

    let inputUrl = divElem.find("input[type='text']");
    if (inputUrl.val().length == 0) {
      return false;
    }

    _ajouterInputVisuel(divElem, inputUrl, false);
  }

  function testExternalUri(divElem, inputUrl) {

    if (inputUrl.val().startsWith("https://") == false) {
      let elemDanger = divElem.find('.text-danger');
      if (elemDanger.length == 0) {
        let spanDanger = '<span class="text-danger text-align-center">Url mal formattée</span>';
        divElem.prepend(spanDanger);
      }
      return false;
    }

    return true;
  }

  return {
    'entriesExts': entriesExts
  }

});

