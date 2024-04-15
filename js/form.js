const divpersonselect = "#divpersonselect";
const idperson_ul = "#person_ul";

const titrecreneauSelector = "#titrecreneau input[name='titrecreneau']";
const summarycreneauSelector = "#summarycreneau textarea[name='summarycreneau']";
const lieucreneauSelector = "#lieucreneau input[name='lieucreneau']";
const zoomButtonSelector = "#zoom button[name='zoom']";

var jsSessionZoomInfos = typeof(jsSessionZoomInfos) == 'undefined' ? null : jsSessionZoomInfos;

let listDisplayname = new Map();

let newParticipant=false;
let start=null;
let end=null;

let lieuCreneauElem=null;
let zoomElem=null;

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

    optionnel.click(function() {
        afficherPermutterHide(testOptions(getCurrentOptions()));
    });

    newLi.append(optionnel);
    // newLi.append('<input name="listUidsOptionnels[]" type="checkbox" class="form-check-input,form-participant-optionnel" />');
    newLi.append('<label class="col-4 px-0 text-left form-check-label" for="form-participant-optionnel">Participant optionnel</label>');

    $(idperson_ul).append(newLi);

    button.click(function () {
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
    else if (ui.item.category == 'groups_structures' || ui.item.category == 'local') {
        $.ajax({
            url: urlwsgroupUserInfos,
            jsonp: "callback",
            data: { key: ui.item.key, CAS: true, filter_member_of_group: ui.item.key, filter_mail: "*", maxRows: 100, attrs: "uid,displayName"},
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

function creneauSessionIdx(start, end, jsSessionInfos) {
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

function objSessionIdx(jsSessionInfos, start, end, typeObj) {
    if (typeof jsSessionInfos != 'undefined') {
        if ((typeObj.zoom) || (typeObj.invite && typeObj.newParticipant)) {
            let key = creneauSessionIdx(start, end, jsSessionInfos);

            if (key !== -1) {
                return jsSessionInfos[key];
            }
        }
    }
    return null;
}

class bsModalShowZoom {
    constructor(jsSessionZoomInfos) {
        this.jsSessionZoomInfos = jsSessionZoomInfos;
        this.key = creneauSessionIdx(start, end, jsSessionZoomInfos);
        this.currentObj = (this.key !== -1) ? jsSessionZoomInfos[this.key] : null;
    }

    lieuCreneauDiv(url) {
        return $("<p id='lieucreneau'><a href='"+ url +"'>Lien zoom</a></p>");
    }

    bsModalShowZoomDom() {
        if(this.currentObj != null) {
            $(zoomButtonSelector).text("Zoom crée");
            $(zoomButtonSelector).attr('disabled', true);
            $('#lieucreneau').empty().append(this.lieuCreneauDiv(this.currentObj.data.join_url));
        } else {
            $(zoomButtonSelector).text("Créer un Zoom");
        }
    }
}

function formModalValidate() {
    let vals = getCurrentOptions();
    return testOptions(vals);
}

function onSubmit(event) {
    event.preventDefault();
    // change la valeur de l'input pour indiquer l'action à réaliser à la soumission du formulaire
    if (event.originalEvent.submitter.name == "submitModal") {
        $("input[name='actionFormulaireValider']").val("envoiInvitation");
    }

    if (formModalValidate() == true) {
        this.submit();
    } else {
        errorShow(true);
    }
}

function onTimeClick() {
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
}

function bsModalShowInvitationDOM(currentObj) {
    if (currentObj != null) {
        $(titrecreneauSelector).val(currentObj.infos.titleEvent);
        $(summarycreneauSelector).val(currentObj.infos.descriptionEvent);
        $(lieucreneauSelector).val(currentObj.infos.lieuEvent);
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
}

function bsModalShow() {
    $("#creneauBoxInput input[type='text'],textarea,button").attr('disabled', false);
    $("#creneauBoxInput input[type='text'],textarea").attr('required', true);

    $("#creneauBoxInput ~ input[type='datetime-local']").attr('disabled', false);
    $("#creneauBoxInput ~ input[type='datetime-local']").attr('required', true);

    zoomChange();

    let currentInviteObj = objSessionIdx(jsSessionInviteInfos, start, end, {invite: true, newParticipant: newParticipant}); // objets courant à partir d'une variable jsSession définit dans l'index
    bsModalShowInvitationDOM(currentInviteObj);

    (new bsModalShowZoom(jsSessionZoomInfos)).bsModalShowZoomDom();
    $(zoomButtonSelector).click(zoomClick);
}

function bsModalHide() {
    $('#zoom').empty().append(zoomElem);
    $("#lieucreneau").empty().append(lieuCreneauElem);
    $(zoomButtonSelector).click(zoomClick);

    $("#creneauBoxInput input[type='text'],textarea,button").attr('disabled', true);
    $("#creneauBoxInput input[type='text'],textarea").attr('required', false);

    $("#creneauBoxInput ~ input[type='datetime-local']").attr('disabled', true);
    $("#creneauBoxInput ~ input[type='datetime-local']").attr('required', false);
}

let isLoading = false;
let zoomError = false;

function zoomClickError(data) {
    zoomError=true;
    let zoom = $(zoomButtonSelector);
    zoom.removeClass('btn-secondary');
    zoom.removeClass('btn-success');
    zoom.addClass('bg-danger');
    zoom.text(data.msg);
}

function zoomClick() {
        if (isLoading == true) {
            return;
        }
        isLoading = true;
        $("input[name='actionFormulaireValider']").val("zoomMeeting");

        let zoom = $(zoomButtonSelector);

        zoom.empty().append(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                     <span class="sr-only">En cours...</span>`);

        let datas = $("#form").serialize();
        $.ajax({
            type: "GET",
            url: "zoom.php",
            data: datas,
            dataType: "json",
            encode: true,
          }).done(function (data) {
            if (data.status == true) {
                let bsZoom;
                if (jsSessionZoomInfos==null) {
                    jsSessionZoomInfos = [];
                    jsSessionZoomInfos[0]=data.zoomMeeting[0];
                    jsSessionZoomInfos[0].data = data.data;
                } else {
                    bsZoom = new bsModalShowZoom(jsSessionZoomInfos);
                    if (bsZoom.currentObj == null) {
                        jsSessionZoomInfos.push(Object.assign({data: data.data}, data.zoomMeeting.at(-1)));
                    } else {
                        jsSessionZoomInfos[bsZoom.key].data = data.data;
                    }
                }
                bsZoom = new bsModalShowZoom(jsSessionZoomInfos);
                bsZoom.bsModalShowZoomDom();
            } else {
                zoomClickError(data);
            }}).fail(function (data) {
                zoomClickError(data);
            }).always(function() {
                zoom.attr('disabled', true);
                isLoading=false;
            });
}

function zoomChange() {
        let summary = $(summarycreneauSelector);
        let zoom = $(zoomButtonSelector);
        let title = $(titrecreneauSelector);

        let bsZoom = new bsModalShowZoom(jsSessionZoomInfos);

        if (summary.val().length > 0 && title.val().length > 0 && bsZoom.currentObj == null && zoomError==false) {
            zoom.removeAttr('disabled');
            zoom.removeClass('btn-secondary');
            zoom.addClass('btn-success');
        } else {
            zoom.attr('disabled', true);
	        zoom.removeClass('btn-success');
	        zoom.addClass('btn-secondary');
	}
}

$(function () {
    $("#person").autocompleteUserAndGroup(
        urlwsgroupUsersAndGroups, {
        select: wsCallbackUid,
        wantedAttr: "uid",
        wsParams: {
            CAS: 1,
            filter_category: "groups",
            filter_group_cn: "collab.*|employees.*",
            filter_eduPersonAffiliation: "teacher|researcher|staff|emeritus"
        }
    });

    $('#divpersonselect').hide();

    // Set FR pour le formattage des dates avec la librairie moment.js
    moment.locale('fr');
    lieuCreneauElem = $("#lieucreneau").clone(true).detach();
    zoomElem = $("#zoom").clone(true).detach();

    $('[data-toggle="tooltip"]').tooltip({ 'html': true });
    $("#form").on("submit", onSubmit);
    $("#reponse li a").click(onTimeClick);
    $(zoomButtonSelector).click(zoomClick);
    $('#creneauMailInput').on('shown.bs.modal', bsModalShow);
    $('#creneauMailInput').on('hidden.bs.modal', bsModalHide);
    $("#summarycreneau,#titrecreneau").on("change keyup", zoomChange);
});
