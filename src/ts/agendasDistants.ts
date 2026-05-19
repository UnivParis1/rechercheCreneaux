import $, { event } from 'jquery';
import validator from 'validator';
import * as bootstrap from 'bootstrap';

declare global {
  interface Global {
    jsuids: string[];
  }
}

class Upload {
	file: File;
	constructor (file: File) {
		this.file = file;
	}
	public getType() {
		return this.file.type;
	}
	public getSize() {
        return this.file.size;
	}
	public getName() {
		return this.file.name;
	}
	public doUpload(inputElem: HTMLElement) {
	    var that = this;
	    var formData = new FormData();

	    // Append the file to the FormData object
	    formData.append("file", this.file, this.getName());
	    formData.append("upload_file", "true");

	    $.ajax({
	        type: "POST",
	        url: "uploadICS.php",
	        xhr: function () {
	            // Get the native XHR object
	            var myXhr = $.ajaxSettings.xhr();
	            if (myXhr.upload) {
	                // Monitor upload progress
	                myXhr.upload.addEventListener('progress', that.progressHandling, false);
	            }
	            return myXhr;
	        },
	        success: function (dataraw) {
				let data = JSON.parse(dataraw);

				if (data.status == true) {
					let hidden=$(inputElem).parent().find('input[type="hidden"]');
					hidden.removeAttr("disabled");
					hidden.attr('value', data.name + "=" + data.fullname);
				}
	        },
	        error: function (error) {
	            // Handle upload errors
	            console.error("Upload error:", error);
	        },
	        async: true,
	        data: formData,
	        cache: false,
	        contentType: false, // Essential for FormData
	        processData: false, // Essential for FormData
	        timeout: 60000 // Optional timeout
	    });
	}

	public progressHandling(event:any) {
	    var percent = 0;
	    var position = event.loaded || event.position;
	    var total = event.total;

	    if (event.lengthComputable) {
	        percent = Math.ceil(position / total * 100);
	    }
		console.log("Upload file: " + percent + "%");
	}
}


var initAgendasDistants: Array<any> = new Array();
var agendasDistants: Array<any> = new Array();

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
				if (elem.data && elem.valid) {
					$("#creneauMailParticipant_ul").append("<li>" + elem.uid + "</li>");
				}
			});
		});
	}

	$("#inputFile").on('change', (event) => {
		let inputUrl = event.target.parentElement?.nextElementSibling?.children[1];
		const file = (event.target as HTMLInputElement).files![0];

		if (typeof inputUrl != "undefined") {
			var upload:Upload = new Upload(file);
			upload.doUpload(event.target);

			// grise les url
			$(inputUrl).prop('disabled', true);
		}
	});

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

			if (entry.data == false || entry.valid == false) {
				inputUrl.removeClass("is-valid").addClass("is-invalid");
				inputUrl.next().html("Erreur de données sur cette ressource");
			}

			if (i == initAgendasDistants.length - 1) {
				ajouterDOMLigneUriMail();
			}
		}
		i++;
	} while (i < initAgendasDistants.length);
});

function detectGoogmail(event: any) {
  let val = event.target.value;

  if (val.search('calendar.google.com')) {
	let splited:Array<string> = val.split('/');
	if (typeof splited[5] != 'undefined') {
		let mailval:string = splited[5];
		if (typeof mailval != "undefined") {
		    let mail:string = mailval.replace('%40', '@');
		    if (validator.isEmail(mail)) {
		      $(event.target.parentElement.parentElement).find('input#inputEmail').val(mail);
		    }
		}
	}
  }
}

function ajouterDOMLigneUriMail() {
	let boutonUri = $("#aclonerDivUriMail")
		.clone(true)
		.removeAttr("id")
		.removeClass("d-none");
    boutonUri.find("input#inputUrl").on('blur', detectGoogmail);
	boutonUri.find("button.ajouterDistantUri").on("click", cliquerAjouter);
	boutonUri.insertAfter(
		$("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").last(),
	);
}

function _processDatas(divElem: any, inputUrl: JQuery<HTMLElement>, inputMail: JQuery<HTMLElement>, isUserEvent: boolean) {
	let entry = {
		type: "default",
		url: inputUrl.val(),
		mail: inputMail.val(),
		data: false,
		valid: true,
		idx: -1, 
	};

	let agendasDOM = $(
		"#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)",
	);
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

	if (
		isUserEvent &&
		agendasDistants.length ==
			$("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length
	) {
		ajouterDOMLigneUriMail();
	}
}

function cliquerModifier(event: any) {
	let cible = event.target.parentElement.parentElement;
	let agendasDOM = $(
		"#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)",
	);
	for (let i = 0; i < agendasDOM.length; i++) {
		if (agendasDOM[i] == cible) {
			agendasDistants = agendasDistants.filter((elem: any) => elem.idx != i);
		}
	}
	cliquerAjouter(event);
}

function cliquerSupprimer(event: any) {
	let cible = event.target.parentElement.parentElement;
	let agendasDOM = $(
		"#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)",
	);
	for (let i = 0; i < agendasDOM.length; i++) {
		if (agendasDOM[i] == cible) {
			agendasDOM[i].remove();
			agendasDistants = agendasDistants.filter((elem: any) => elem.idx != i);
			for (let j = i; j < agendasDistants.length; j++) {
				agendasDistants[j].idx--;
			}
		}
	}

	if (
		agendasDistants.length == 0 &&
		$("#agendasDistant .aclonerUriClass:not(#aclonerDivUriMail)").length == 0
	) {
		ajouterDOMLigneUriMail();
	}
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
	let length = agendasDistants.filter(
		(elem: any) => input.val() == elem[field],
	).length;

	if (length > 0) {
		input.removeClass("is-valid");
		input.addClass("is-invalid");
		divInvalid.html(errorTxt);
		return false;
	}
	return true;
}

export default agendasDistants;
