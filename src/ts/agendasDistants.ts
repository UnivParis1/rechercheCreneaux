import $, { error } from 'jquery';
import validator from 'validator';
import * as bootstrap from 'bootstrap';

declare global {
  interface Global {
    jsuids: string[];
    agendasDistants: Array<any>;
  }
}

var initAgendasDistants: Array<any> = new Array();
(globalThis as any).agendasDistants = new Array();

$(function () {
	(globalThis as any).jsuids.forEach((elem: any) => {
		if (elem.type != "up1") {
			initAgendasDistants.push(elem);
		}
	});

    const myModal = document.querySelector('#creneauMailInput');
    if (myModal) {
		new bootstrap.Modal(myModal);
		myModal.addEventListener('show.bs.modal', () => {
			initAgendasDistants.forEach((elem) => {
				if (elem.code == 200 && elem.valid) {
					$("#creneauMailParticipant_ul").append("<li>" + elem.uid + "</li>");
				}
			});
		});
	}

	let i = 0;
	do {
		let divEntry = $("#aclonerDivUriMail").clone(true);
		divEntry.removeAttr("id").removeClass("d-none");
		$("#agendasDistant").append(divEntry);

        divEntry.find("#inputUrl").on('blur', detectGoogmail);
		let buttonAdd = divEntry.find("button.ajouterDistantUri");
		buttonAdd.on("click", cliquerAjouter);

		if (initAgendasDistants.length > 0) {
			const entry = initAgendasDistants[i];
			let inputUrl = divEntry.find("input#inputUrl");
			let inputMail = divEntry.find("input#inputEmail");

			inputUrl.val(entry.url);
			inputMail.val(entry.uid);

			buttonAdd.trigger("click");

            if (entry.code != 200 || entry.valid == false) {
                let errorTxt:string;
                if (entry.code == 200) {
                    errorTxt = "Les données retournées ne correspondent pas au format iCalendar";
                } else {
                    switch (entry.code) {
                        case 404:
                            errorTxt = "Assurez-vous que l'URL d'agenda renseignée est accessible en public";
                            break;
                        case 500:
                            errorTxt = "Erreur ressource distante";
                            break;
                        case 0:
                            errorTxt = "url serveur non accessible";
                            break;
                        default:
                            errorTxt = "Erreur inconnue:" + entry.code + " : code retour non géré";
                            break;
                       }
                }

				inputUrl.removeClass("is-valid").addClass("is-invalid");
				inputUrl.next().html(errorTxt);
			}

			if (i == initAgendasDistants.length - 1) {
				ajouterDOMLigneUriMail();
			}
		}
		i++;
	} while (i < initAgendasDistants.length);
});

function detectGoogmail(event: any) {
    let agendasDistants = (globalThis as any).agendasDistants;
    let val = event.target.value;

    if (val.search('calendar.google.com')) {
        let splited:Array<string> = val.split('/');
        if (typeof splited[5] != 'undefined') {
            let mailval:string = splited[5];
            if (typeof mailval != "undefined") {
                let mail:string = mailval.replace('%40', '@');
                if (validator.isEmail(mail)) {
                // récupère la node parent encapsulant la ligne d'ajout de l'agenda externe courant
                    let rootLine:JQuery<HTMLElement> = $(event.target.parentElement.parentElement);
                    rootLine.find('input#inputEmail').val(mail);
                    rootLine.find('button.ajouterDistantUri').trigger("click");

                    if ($("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == agendasDistants.length) {
                        ajouterDOMLigneUriMail();
                    }
                }
            }
	    }
    }
}

function ajouterDOMLigneUriMail() {
	let boutonUri = $("#aclonerDivUriMail").clone(true)
                                           .removeAttr("id")
                                           .removeClass("d-none");
    boutonUri.find("input#inputUrl").on('blur', detectGoogmail);
	boutonUri.find("button.ajouterDistantUri").on("click", cliquerAjouter);
	boutonUri.insertAfter( $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").last() );
}

function _processDatas(divElem: any, inputUrl: JQuery<HTMLElement>, inputMail: JQuery<HTMLElement>, isUserEvent: boolean) {
	let entry = {
		type: "default",
		url: inputUrl.val(),
		mail: inputMail.val(),
		code: -1,
		valid: true,
		idx: -1, 
	};

	let agendasDistants = (globalThis as any).agendasDistants;
	let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
	for (let i = 0; i < agendasDOM.length; i++) {
		if (divElem[0] == agendasDOM[i]) {
			let aexist = agendasDistants.find((elem: any) => elem.idx == i);
			if (!aexist) {
				entry.idx = i;
				agendasDistants.push(entry);
			}
		}
	}

	let bouttonModifierAgenda = divElem.find(".ajouterDistantUri");
	let bouttonSupprimerAgenda = divElem.find(".supprimerDistantUri");

	bouttonModifierAgenda
		.removeClass("ajouterDistantUri")
		.html("modifier")
		.addClass("modifierDistantUri")
		.off("click");
	bouttonModifierAgenda.on("click", cliquerModifier);

	bouttonSupprimerAgenda.parent().removeClass("invisible");
	bouttonSupprimerAgenda.on("click", cliquerSupprimer);

	if ($("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == agendasDistants.length && isUserEvent) {
		ajouterDOMLigneUriMail();
	}
}

function cliquerModifier(event: any) {
	let agendasDistants = (globalThis as any).agendasDistants;
	let cible = event.target.parentElement.parentElement;
	let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
	for (let i = 0; i < agendasDOM.length; i++) {
		if (agendasDOM[i] == cible) {
			(globalThis as any).agendasDistants = agendasDistants.filter((elem: any) => elem.idx != i);
		}
	}
	cliquerAjouter(event);
}

function cliquerSupprimer(event: any) {
	let agendasDistants = (globalThis as any).agendasDistants;
	let cible = event.target.parentElement.parentElement;
	let agendasDOM = $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)");
	for (let i = 0; i < agendasDOM.length; i++) {
		if (agendasDOM[i] == cible) {
			agendasDOM[i].remove();
			agendasDistants = agendasDistants.filter((elem: any) => elem.idx != i);
			for (let j = i; j < agendasDistants.length; j++) {
				agendasDistants[j].idx--;
			}
		}
	}

	if (agendasDistants.length == 0 && $("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == 0) {
		ajouterDOMLigneUriMail();
	}
    (globalThis as any).agendasDistants = agendasDistants;
}

function cliquerAjouter(event: any) {
	event.preventDefault();

	// correspond à #agendasDistant
	let divElem = $(event.target).parent().parent();

	let arrayTest = [];

	arrayTest.push({
		field: "mail",
		input: divElem.find("input[type='email']"),
		divInvalid: divElem.find(".divMail div.invalid-feedback"),
		validate: Object.assign(validator.isEmail),
		errorTxt: "Email mauvais format",
		errorExist: "Email déja présent",
    testFormat: false,
    testExist: false 
	});
	arrayTest.push({
		field: "url",
		input: divElem.find("input[type='uri']"),
		divInvalid: divElem.find(".divUrl div.invalid-feedback"),
		validate: Object.assign(validator.isURL),
		errorTxt: "format URL invalide",
		errorExist: "URL déja présente",
	});

	for (let array of arrayTest) {
		array.testFormat = testDistantValidField(
			array.input,
			array.divInvalid,
			array.validate,
			array.errorTxt,
		);
		array.testExist = testExistField(
			array.input,
			array.field,
			array.divInvalid,
			array.errorExist,
		);
	}

	let test;
	arrayTest.forEach((array) => {
		test = array.testFormat && array.testExist ? true : false;
		if (!test) {
			return;
		}
		array.input.addClass("form-control-plaintext");
	});

	if (test) {
		_processDatas(
			divElem,
			arrayTest[1].input,
			arrayTest[0].input,
			typeof event.isTrigger == "undefined" ? true : false,
		);
	}
}

function testDistantValidField(input: JQuery<HTMLElement>, divInvalid: JQuery<HTMLElement>, validate: any, errorTxt: string) {
	if (!validate(input.val())) {
		input.removeClass("is-valid");
		input.addClass("is-invalid");
		divInvalid.html(errorTxt);
		return false;
	}

	input.removeClass("is-invalid");
	input.addClass("is-valid");

	return true;
}

function testExistField(input: JQuery<HTMLElement>, field: any, divInvalid: JQuery<HTMLElement>, errorTxt: string) {
	let length = (globalThis as any).agendasDistants.filter( (elem: any) => input.val() == elem[field] ).length;

	if (length > 0) {
		input.removeClass("is-valid");
		input.addClass("is-invalid");
		divInvalid.html(errorTxt);
		return false;
	}
	return true;
}

//export default agendasDistants;
