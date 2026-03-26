define('agendasDistants', ['jquery', 'validator'], function($, validator) {

  var agendasDistants = [];

  $(function() {

    for (let valuid of jsuids) {
      if (valuid.type == 'gmail') {
        agendasDistants.push(valuid);
      }
    }

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
          // TODO : cas ou les urls sont renseignés GET
          ajouterDOMLigneUriMail(divElem, divElem.find("input[type='text']"), entry.uri, true);
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

    if ( ! agendasDistants.filter((elem) => elem.url == entry.url).length == 0) {
      return false;
    }
    agendasDistants.push(entry);

    let bouttonSupprimerAgenda = divElem.find('.ajouterDistantUri');
    bouttonSupprimerAgenda.removeClass('ajouterDistantUri').html('supprimer').addClass('supprimerDistantUri').off('click');

    bouttonSupprimerAgenda.on('click', (elem) => {
      agendasDistants = agendasDistants.filter((elem) => elem.url != entry.url );
      elem.target.parentElement.parentElement.remove();

      if (agendasDistants.length == 0 && $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == 0) {
        ajouterDOMLigneUriMail();
      }
    });

    if (agendasDistants.length == $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length) {
      ajouterDOMLigneUriMail();
    }

    return true;
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
        input.prop('readonly', 'readonly');
        input.addClass('form-control-plaintext');
        test = true;
      } else {
        test = false;
        break;
      }
    }

    if (test) { 
        _processDatas(divElem, arrayTest[1].input, arrayTest[0].input);
    }
    return test;
  }

  function testDistantValidField(input, divInvalid, validate, errorTxt) {
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
    let length = agendasDistants.filter( (elem) => input.val() == elem[field]).length;

    if (length > 0) {
      input.removeClass('is-valid');
      input.addClass('is-invalid');
      divInvalid.html(errorTxt);
      return false;
    }
    return true;
  }


  return {
    'agendasDistants': agendasDistants
  }

});

