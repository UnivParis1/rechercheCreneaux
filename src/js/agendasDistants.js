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

    let arrayTest = [];

    arrayTest.push({field:'mail', input: divElem.find("input[type='email']"), divInvalid: divElem.find('.divMail div.invalid-feedback'), validate: validator.isEmail, errorTxt: 'Email mauvais format', errorExist: 'Email déja présent'});
    arrayTest.push({field:'url', input: divElem.find("input[type='uri']"), divInvalid: divElem.find('.divUrl div.invalid-feedback'), validate: validator.isURL, errorTxt: 'format URL invalide', errorExist: 'URL déja présente'});

    for (let array of arrayTest) {
      array.testFormat = testDistantValidField(array.input, array.divInvalid, array.validate, array.errorTxt);
      array.testExist = testExistField(array.input, array.field, array.divInvalid, array.errorExist);
    }

    let test = false;
    for (let array of arrayTest) {
      let input = array.input;

      if (array.testFormat && array.testExist) {
        input.prop('disabled', 'disabled');
        input.prop('readonly', 'readonly');
        test = true;
        _processDatas(divElem, inputUrl, inputMail);
      } else {
        test = false;
      }
    }

    return test;
  }

  function testDistantValidField(input, divInvalid, validate, errorTxt) {
    let test = true;

    if (! validate(input.val())) {
      input.removeClass('is-valid');
      input.addClass('is-invalid');
      divInvalid.html(errorTxt);
      return false; 
    }

    input.removeClass('is-invalid');
    input.addClass('is-valid');

    return true;
  }

  function testExistField(input, field, divInvalid, errorTxt) {
    let exist = agendasDistants.findIndex( (elem) => input.val() == elem.field);

    if (exist != -1) {
      input.removeClass('is-valid');
      input.addClass('is-invalid');
      divInvalid.html(errorTxt);
      input.val('');
      return false;
    }
    return true;
  }


  return {
    'agendasDistants': agendasDistants
  }

});

