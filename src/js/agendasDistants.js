define('agendasDistants', ['jquery', 'on-change', 'validator'], function($, onChange, validator) {

  var agendasDistants = [];

  $(function() {

    for (let valuid of jsuids) {
      if (valuid.type == 'gmail') {
        agendasDistants.push(valuid);
      }
    }

    // observer des changements sur objet, appel uniquement après après mis les valeurs existantes
    agendasDistants = onChange.default(agendasDistants, function(path, value, previousValue, applyData) {
//      console.log('this:', this);
//      console.log('path:', path);
//      console.log('value:', value);
//      console.log('previousValue:', previousValue);
//      console.log('applyData:', applyData);

//      if (value.length > previousValue.length || value.length == 0) {
        ajouterDOMLigneUriMail();
//      }
    });

    initierVisuelFBExternes();

    $('#creneauMailInput').on('shown.bs.modal', showModal);
  });

  function showModal() {
    for (let ext of agendasDistants) {
      if (ext.data == true) {
        $('#creneauMailParticipant_ul').append("<li>" + ext.uid + "</li>");
      }
    }
  }

  function initierVisuelFBExternes() {

    let lenExts = (agendasDistants && agendasDistants.length > 0) ? agendasDistants.length : 0;

    let i = 0;
    do {
      let divEntry = $("#aclonerDivUriMail").clone(true);

      divEntry.removeAttr('id');
      divEntry.removeClass('d-none');

      $("#agendasDistant").append(divEntry);

      let buttonAdd = divEntry.find('button.ajouterDistantUri');
      buttonAdd.on("click", cliquerAjouter);

      if (lenExts > 0) {
        const entry = agendasDistants[i];
        divEntry.find('input').val(entry.uri);

        if (entry.data == false) {
          divEntry.prepend($('#refDanger').clone(true).removeAttr('id').removeClass('d-none').text('Erreur de données sur cette ressource'));
        } else {
          let divElem = buttonAdd.parent().parent();
          ajouterDOMLigneUriMail(divElem, divElem.find("input[type='text']"), entry.uri, true);

          if (i == lenExts - 1)
            ajouterOuPasNewInput();
        }
      }

      i++;
    } while (i < lenExts);
  }

  function ajouterDOMLigneUriMail() {
    let boutonUri = $("#aclonerDivUriMail").clone(true).removeAttr('id').removeClass('d-none');
    boutonUri.find('button.ajouterDistantUri').on("click", cliquerAjouter);
    boutonUri.insertAfter($("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").last());
  }

  function _processDatas(divElem, inputUrl, inputMail) {
    let entry = { type: 'gmail', url: inputUrl.val(), mail: inputMail.val(), data: false, valid: true};

    if (agendasDistants.findIndex((elem) => elem.url == entry.url) == -1) {
      agendasDistants.push(entry);
    }

    let bouttonSupprimerAgenda = divElem.find('.ajouterDistantUri');
    bouttonSupprimerAgenda.removeClass('ajouterDistantUri').html('supprimer').addClass('supprimerDistantUri').off('click');

    bouttonSupprimerAgenda.on('click', (elem) => {
      agendasDistants = agendasDistants.filter((elem) => elem.url != entry.url );
      elem.target.parentElement.parentElement.remove();
    });
  }

  function cliquerAjouter(event) {
    event.preventDefault();

    // correspond à #agendasDistant
    let divElem = $(event.target).parent().parent();

    let inputUrl = divElem.find("input[type='uri']");
    let inputMail = divElem.find("input[type='email']");

    let test = true;

    let divInvalidMail = divElem.find('.divMail div.invalid-feedback');
    if (! validator.isEmail(inputMail.val())) {
      inputMail.removeClass('is-valid');
      inputMail.addClass('is-invalid');
      divInvalidMail.html('Email mauvais format');
      test = false;
    } else {
      inputMail.removeClass('is-invalid');
      inputMail.addClass('is-valid');
    }

    let divInvalidUrl = divElem.find('.divUrl div.invalid-feedback');
    if (! validator.isURL(inputUrl.val())) {
      inputUrl.removeClass('is-valid');
      inputUrl.addClass('is-invalid');
      divInvalidUrl.html('format URL invalide');
      test = false;
    } else {
      inputUrl.removeClass('is-invalid');
      inputUrl.addClass('is-valid');
    }

    if (! test) {
      return false;
    }

    test = true;

    let existMail = agendasDistants.findIndex( (elem) => inputMail.val() == elem.mail);

    if (existMail != -1) {
      inputMail.removeClass('is-valid');
      inputMail.addClass('is-invalid');
      divInvalidMail.html('Email déja présent');
      inputMail.val('');
      test = false;
    }

    let existUrl = agendasDistants.findIndex( (elem) => inputUrl.val() == elem.url);

    if (existUrl != -1) {
      inputUrl.removeClass('is-valid');
      inputUrl.addClass('is-invalid');
      divInvalidUrl.html('URL déja présente');
      inputUrl.val('');
      test = false;
    }

    if (! test) {
      return false;
    }

    inputUrl.prop('disabled', 'disabled');
    inputUrl.prop('readonly', 'readonly');

    inputMail.prop('disabled', 'disabled');
    inputMail.prop('readonly', 'readonly');

    _processDatas(divElem, inputUrl, inputMail);
  }

  return {
    'agendasDistants': agendasDistants
  }

});

