let divpersonselect = "#divpersonselect";
let idperson_ul = "#person_ul";
let listDisplayname = new Map();

function errorShow(toShow) {
    if (toShow === true) {
        if ($(divpersonselect).is(":hidden"))
            $(divpersonselect).show();
        $(divpersonselect + " .alertrequire").css('display', 'inherit');
    }
    if (toShow === false) {
        $(divpersonselect + " .alertrequire").css('display', 'none');
    }
}

function getCurrentOptions() {
    let getVals = new Map();

    $(idperson_ul + " li input[name='listuids[]']").each(function (idx, option) {
        getVals.set(idx, [option.value,true]);
    });

    return getVals;
}

function testOptions(vals) {
    if (vals.size < 2) {
        return false;
    }

    let panticipantsChecked = $(idperson_ul + " li input:checked[name='listUidsOptionnels[]']");

    if ((vals.size - panticipantsChecked.length) < 2) {
        return false;
    }

    return true;
}

function afficherPermutterHide(testFormInput) {
    if (testFormInput == false) {
        errorShow(true);
    } else {
        errorShow(false);
    }
}

function setOptionsUid(jsuids) {
    for (uid of jsuids) {
        $.ajax({
            url: urlwsgroupUserInfos,
            jsonp: "callback",
            data: { token: uid, CAS: true, maxRows: 1, attrs: "uid,displayName,mail" },
            dataType: 'jsonp',
            success: function (response) {
                if (typeof response[0].mail != "undefined") {
                    addOptionUid(response[0].uid, response[0].displayName);
                }
            }
        });
    }
}

function addOptionWithUid(uid, displayName) {
    listDisplayname.set(uid, displayName);

    if ($(divpersonselect).is(":hidden"))
        $(divpersonselect).show();

    let newLi = $('<li>').attr('class', 'row align-items-center');

    newLi.append($('<input>').attr('type', 'text').attr('name', 'listuids[]').attr('multiple', true).attr('checked', true).val(uid).css('display', 'none'));

    newLi.append('<img class="col-2 rounded-circle" alt="' + uid + '" src="' + urlwsphoto + '?uid='+ uid + '" />');

    newLi.append($('<label>').attr('class', 'col-3 px-0').text(displayName));

    let button = $('<button>').text('supprimer').attr('class', 'col-2 px-0');
    newLi.append(button);

    let optionnel = $('<input>');
    optionnel.attr('name', 'listUidsOptionnels[]').attr('type', 'checkbox').attr('class', 'col-1 checkbox-inline').val(uid);

    if (typeof jsListUidsOptionnels != 'undefined') {
        for (uidBlock of jsListUidsOptionnels) {
            if (uidBlock == uid) {
                optionnel.attr('checked', true);
            }
        }
    }

    optionnel.on('click', function() {
        afficherPermutterHide(testOptions(getCurrentOptions()));
    });

    newLi.append(optionnel);
    // newLi.append('<input name="listUidsOptionnels[]" type="checkbox" class="form-check-input,form-participant-optionnel" />');
    newLi.append('<label class="col-4 px-0 text-left form-check-label" for="form-participant-optionnel">Participant optionnel</label>');

    $(idperson_ul).append(newLi);

    button.on("click", function () {
        $(this).parent().remove();
        let opts = getCurrentOptions();
        let testFormInput = testOptions(opts);
        afficherPermutterHide(testFormInput);

        if (testFormInput == false && opts.size == 0) {
            $(divpersonselect).hide();
        }
    });
}

function addOptionUid(uid, displayName) {
    //let uid=this.value;
    let vals = getCurrentOptions();

    let testUidVals = false;
    for (const [key, value] of vals) {
        if (value.at() == uid) {
            testUidVals = true;
        }
    }

    if (testUidVals === false) {
        addOptionWithUid(uid, displayName);
    }
    vals = getCurrentOptions();

    // if (vals.size > 1) {
    if (testOptions(vals) == true) {
        errorShow(false);
    }
}

function wsCallbackUid(event, ui) {

    if (ui.item.category == 'users') {

        let uid = ui.item.uid;
        let displayName = ui.item.displayName;

        if (typeof ui.item.mail == "undefined") {
            alert("Le courriel de l'utilisateur " + ui.item.displayName + " étant absent, son entrée n'est pas ajoutée à la liste");
        } else {
            addOptionUid(uid, displayName);
        }
    }
    else if (ui.item.category == 'structures' || ui.item.category == 'local') {
        $.ajax({
            url: urlwsgroupUserInfos,
            jsonp: "callback",
            data: { key: ui.item.key, CAS: true, filter_member_of_group: ui.item.key, filter_mail: "*", maxRows: 30, attrs: "uid,displayName"},
            dataType: 'jsonp',
            success: function (response) {
                let arrayUids = new Array();
                for (obj of response) {
                    arrayUids.push(obj.uid);
                }
                setOptionsUid(arrayUids);
            }
        });
    }

    $('input#person').val('');

    return false;
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip({ 'html': true });

    $("#person").autocompleteUserAndGroup(
        urlwsgroupUsersAndGroups, {
        select: wsCallbackUid,
        wantedAttr: "uid",
        wsParams: {
            filter_category: "groups",
            filter_group_cn: "collab.*|employees.*",
            filter_eduPersonAffiliation: "teacher|researcher|staff|emeritus"
        }
    });

    $("#form").on("submit", function (event) {
        event.preventDefault();
        // change la valeur de l'input pour indiquer l'action à réaliser à la soumission du formulaire
        if (event.originalEvent.submitter.name == "submitModal") {
            $("input[name='actionFormulaireValider']").val("envoiInvitation");
        }

        let vals = getCurrentOptions();

        if (testOptions(vals) == true) {
            this.submit();
            return true;
        } else {
            errorShow(true);
            return false;
        }
    });

    $('#divpersonselect').hide();

    let slider = document.getElementById('slider');

    let selectorPlagesHoraires = "input:hidden[name='plagesHoraires[]']";
    let p1a = $(selectorPlagesHoraires)[0].value.split('-');
    let p2a = $(selectorPlagesHoraires)[1].value.split('-');

    let formatter = function(valueString) {
                        if (valueString.search('H30') != -1) {
                            return Number(valueString.replace('H30', '.5'));
                        }
                        if (valueString.search('H00') != -1){
                            return Number(valueString.replace('H00', ''));
                        }
                        if (valueString.search('H') != -1){
                            return Number(valueString.replace('H', ''));
                        }
                        return Number(valueString);
                    };

    let plagesStrings = p1a.concat(p2a);

    let arrayStart = Array();
    for (plage of plagesStrings) {
        arrayStart.push(formatter(plage));
    }

// création du slider pour la séléction des plages horaires
    noUiSlider.create(slider, {
        start: arrayStart,
        step: 0.5,
        connect: [false, true, false, true, false],
        tooltips: {
            to: function(value) {
                if (value % 1 != 0) {
                    let valueEntier = value - 0.5;
                    return valueEntier + "H30";
                }
                else {
                    return value + "H00";
                }
            },
            from: formatter
        },
        range: {
            'min': [7],
            'max': [20]
        }
    });

    slider.noUiSlider.on('update', function (arrayValues) {

        if (arrayValues[0] == "NaN")
            return;

        inputFirst = $(selectorPlagesHoraires).first();
        inputSecond = $(selectorPlagesHoraires).last();

        idx = 0;
        for (value of arrayValues) {
            if (Number(value) % 1 != 0)
                valueStrNew = value.replace('.50', 'H30');
            else
                valueStrNew = value.replace('.00', 'H00');

            if (idx < 2)
                input = inputFirst;
            else
                input = inputSecond;
            if (idx % 2 == 0)
                valueComplete = valueStrNew + "-";
            else
                input.val(valueComplete.concat(valueStrNew));
            idx++;
        }
    });

    function rechercheCreaneauGetIdx(start, end, jsSessionInfos) {
        for (key in jsSessionInfos) {
            let modalCreneauStart = jsSessionInfos[key].modalCreneau.modalCreneauStart;
            let modalCreneauEnd = jsSessionInfos[key].modalCreneau.modalCreneauEnd;
            let mstart = moment(modalCreneauStart);
            let mend = moment(modalCreneauEnd);
            if (start.diff(mstart) == 0 && end.diff(mend) == 0) {
                return key;
            }
        }
        return -1;
    }

    let newParticipant=false;
    let start=null;
    let end=null;

    $("#reponse li a").on("click", function() {
        let ts=$(this).attr("timestart");
        let te=$(this).attr("timeend");

        start = moment.unix(ts);
        end = moment.unix(te);

        $('#creneauBoxDesc #creneauInfo').text(start.format('LL') + " de " + start.format('HH:mm').replace(':', 'h') + ' à ' + end.format('HH:mm').replace(':','h'));

        $("#creneauBoxInput ~ input[name='modalCreneauStart']").val(start.format(moment.HTML5_FMT.DATETIME_LOCAL));
        $("#creneauBoxInput ~ input[name='modalCreneauEnd']").val(end.format(moment.HTML5_FMT.DATETIME_LOCAL));

        if ($(this).attr('newParticipant').valueOf() == 'true') {
            newParticipant = true;
        }
        else {
            newParticipant = false;
        }
    });

    // Set FR pour le formattage des dates avec la librairie moment.js
    moment.locale('fr');

    $('#creneauMailInput').on('shown.bs.modal', function () {
        $("#creneauBoxInput > input[type='text'],textarea").attr('disabled', false);
        $("#creneauBoxInput > input[type='text'],textarea").attr('required', true);

        $("#creneauBoxInput ~ input[type='datetime-local']").attr('disabled', false);
        $("#creneauBoxInput ~ input[type='datetime-local']").attr('required', true);

        let currentObj=null; // objets courant à partir de jsSessionInfos
        if (typeof jsSessionInfos != 'undefined' && newParticipant == true) {
            let key = rechercheCreaneauGetIdx(start, end, jsSessionInfos);
            if (key !== -1) {
                currentObj = jsSessionInfos[key];
                $('#titrecreneau').val(currentObj.infos.titleEvent);
                $('#summarycreneau').val(currentObj.infos.descriptionEvent);
                $('#lieucreneau').val(currentObj.infos.lieuEvent);
            }
        }
        ul = $("#creneauMailParticipant_ul");
        ul.empty();
        listDisplayname.forEach(function(displayName, uid) {
            let li=$('<li>');
            li.text(displayName);
            if (currentObj != null && typeof currentObj.mails[uid] != 'undefined' && currentObj.mails[uid].sended == true) {
                li.append(' <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="green" class="bi bi-check2-circle" viewBox="0 0 16 16"><path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"></path><path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"></path>');
            }
            ul.append(li);
        });
    });

    $('#creneauMailInput').on('hidden.bs.modal', function () {
        $("#creneauBoxInput > input[type='text'],textarea").attr('disabled', true);
        $("#creneauBoxInput > input[type='text'],textarea").attr('required', false);

        $("#creneauBoxInput ~ input[type='datetime-local']").attr('disabled', true);
        $("#creneauBoxInput ~ input[type='datetime-local']").attr('required', false);
    });
});
