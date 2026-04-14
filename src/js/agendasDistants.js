const { event } = require("jquery");

define('agendasDistants', ['jquery', 'validator'], function($, validator) {

  var initAgendasDistants = [];
  var agendasDistants = [];
  globalThis.agendasDistants = agendasDistants;

  $(function() {

    for (let valuid of jsuids) {
      if (valuid.type == 'gmail') {
        initAgendasDistants.push(valuid);
      }
    }

    initierVisuelFBExternes();

    $('#creneauMailInput').on('shown.bs.modal', showModal);
  });

  function showModal() {
    for (let ext of initAgendasDistants) {
      if (ext.data == true) {
        $('#creneauMailParticipant_ul').append("<li>" + ext.uid + "</li>");
      }
    }
  }

  function initierVisuelFBExternes() {

    let i = 0;
    do {
      let divEntry = $("#aclonerDivUriMail").clone(true);

      divEntry.removeAttr('id').removeClass('d-none');

      $("#agendasDistant").append(divEntry);

      let buttonAdd = divEntry.find('button.ajouterDistantUri');
      buttonAdd.on("click", cliquerAjouter);

      if (initAgendasDistants.length > 0) {
        const entry = initAgendasDistants[i];
        let inputUrl = divEntry.find('input#inputUrl');
        let inputMail = divEntry.find('input#inputEmail');

        inputUrl.val(entry.url)
        inputMail.val(entry.uid);

        buttonAdd.trigger("click");

        if (entry.data == false || entry.valid == false) {
          inputUrl.removeClass('is-valid').addClass('is-invalid');
          inputUrl.next().html('Erreur de données sur cette ressource');
        }

        if (i == initAgendasDistants.length - 1) {
          let divElem = buttonAdd.parent().parent();
          ajouterDOMLigneUriMail(divElem, divElem.find("input[type='text']"), entry.url, true);
        }
      }
      i++;
    } while (i < initAgendasDistants.length);
  }

  function ajouterDOMLigneUriMail() {
    let boutonUri = $("#aclonerDivUriMail").clone(true).removeAttr('id').removeClass('d-none');
    boutonUri.find('button.ajouterDistantUri').on("click", cliquerAjouter);
    boutonUri.insertAfter($("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").last());
  }

  function _processDatas(divElem, inputUrl, inputMail, isUserEvent) {
    let entry = { type: 'gmail', url: inputUrl.val(), mail: inputMail.val(), data: false, valid: true, idx: null};

    let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
    for (i = 0;i < agendasDOM.length; i++) {
      if (divElem[0] == agendasDOM[i]) {
        let aexist = agendasDistants.find((elem) => elem.idx == i);
        if (!aexist) {
          entry.idx = i;
          agendasDistants.push(entry);
        }
      }
    }

    let bouttonModifierAgenda = divElem.find('.ajouterDistantUri');
    let bouttonSupprimerAgenda = divElem.find('.supprimerDistantUri');

    bouttonSupprimerAgenda.parent().removeClass('invisible');

    bouttonModifierAgenda.removeClass('ajouterDistantUri').html('modifier').addClass('modifierDistantUri').off('click');

    bouttonModifierAgenda.on('click', cliquerModifier);

    bouttonSupprimerAgenda.on('click', cliquerSupprimer);

    if (isUserEvent && agendasDistants.length == $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length) {
      ajouterDOMLigneUriMail();
    }
    globalThis.agendasDistants = agendasDistants;
  }

  function cliquerModifier(event) {
      let cible = event.target.parentElement.parentElement;
      let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
      for (i = 0; i < agendasDOM.length; i++) {
        if (agendasDOM[i] == cible) {
            agendasDistants = agendasDistants.filter((elem) => elem.idx != i);
        }
      }
      globalThis.agendasDistants = agendasDistants;
      cliquerAjouter(event);
  }

  function cliquerSupprimer(event) {
      let cible = event.target.parentElement.parentElement;
      let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
      for (i=0; i < agendasDOM.length; i++) {
        if (agendasDOM[i] == cible) {
          agendasDOM[i].remove();
          agendasDistants = agendasDistants.filter((elem) => elem.idx != i);
          for (j=i; j < agendasDistants.length; j++) {
            agendasDistants[j].idx--;
          }
        }
      }
      globalThis.agendasDistants = agendasDistants;

      if (agendasDistants.length == 0 && $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == 0) {
        ajouterDOMLigneUriMail();
      }
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

    let test;
    arrayTest.forEach( (array) => {
      test = (array.testFormat && array.testExist) ? true : false;
      if (!test) {
        return;
      }
      array.input.addClass('form-control-plaintext');
    });

    if (test) {
      _processDatas(divElem, arrayTest[1].input, arrayTest[0].input, (typeof event.isTrigger == 'undefined') ? true : false);
    }
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

