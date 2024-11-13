define('evento', ['jquery', 'moment', 'form'], function($, moment, form) {

$(function() {
    // test si l'evento en cours n'est pas clos
    if (isEventoSession == true && typeof idEvento != 'undefined' && typeof urlEvento != 'undefined') {
        eventoAjaxSurvey(eventoDatasRequest({id: idEvento, path: urlEvento}), 'GET', eventoWsUrl + "survey" + "/" + idEvento, function(response) {
            if (response[0].is_closed == true) {
                domEnleverMAJHtml();
                isEventoSession = false;
                idEvento = false;
                urlEvento = false;
            }
        });
    }

    // test si l'evento en cours n'est pas clos
    if (isEventoSession == true && typeof idEvento != 'undefined' && typeof urlEvento != 'undefined') {
        eventoAjaxSurvey(eventoDatasRequest({id: idEvento, path: urlEvento}), 'GET', eventoWsUrl + "survey" + "/" + idEvento, function(response) {
            if (response[0].is_closed == true) {
                domEnleverMAJHtml();
                isEventoSession = false;
                idEvento = false;
                urlEvento = false;
            }
        });
    }

    $('#modalEvento').on('shown.bs.modal', () => {
        $("#eventoModalHeader div span[type='button'] i").removeClass('bi-check2');
        $("#eventoModalHeader div span[type='button'] i").addClass('bi-clipboard');
        $("#evento + span[type='button'] i").removeClass('bi-check2');
        $("#evento + span[type='button'] i").addClass('bi-clipboard');
    });

    $('#eventoSubmit').on("click", () => {
        $("#eventoSubmit").removeAttr("formnovalidate");
        if (eventoFormCheck() == false) {
            return;
        }

        $('#modalEvento').modal('hide');
        $('#spinnerEvento').modal('show');

        let titre = $("input[name='titrevento']").val();
        let desc = $("textarea[name='summaryevento']").val();

        let isNotif = $('#NotifEvento').is(':checked');
        let isAuth = $('#AuthEvento').is(':checked');

        let id = isEventoSession ? idEvento : false;

        let path = id ? urlEvento : false;

        let dataPost = eventoDatasRequest({id: id, path: path, titre: titre, desc: desc, isNotif: isNotif, isAuth: isAuth});

        let url = eventoWsUrl + "survey";
        if (id) {
            url = url + "/" + id;
        }
        eventoAjaxSurvey(dataPost, (id == false) ? 'POST' : 'PUT', url, traiteReponseAjax);
    });
});

function traiteReponseAjax(response, dataPost) {
    if (typeof(response.path)!= 'undefined') {

        if (response.data.path.indexOf('https://evento') != -1 && response.data.path.indexOf('/survey/') != -1) {
            let urlEvento = response.data.path.replace('renater', 'univ-paris1');

            // si la notification des participants est désactivée, ajout des infos participants aux données envoyés pour le stockage session des eventos
            if (dataPost.notify_new_guests == false) {
                form.listDisplayname.forEach((elem) => {
                    dataPost.new_guests.push(elem.mail);
                    dataPost.guests.push({email:elem.mail,name:elem.displayName});
                });
            }

            // ajout des paramètre de la réponse ajax aux données envoyés à l'enregistrement de la session
            dataPost.id = response.data.id;
            dataPost.path = urlEvento;

            // envoie les données, dumb_evento_up.php ne fait que les stocker en variable de session
            $.get('dumb_evento_up.php', dataPost);

            domMiseajourEventoHTML(urlEvento);
        }
    }
}

function domEnleverMAJHtml() {
    $("#eventoModalHeader p").text("Création de l'Evento");

    $("#eventoModalHeader a").empty();

    $("#evento + span[type='button'] i").removeClass('bi-clipboard');
    $("#evento + span[type='button'] i").addClass('bi-check2');

    $("#eventoModalHeader span").addClass('d-none');

    // affichage cohérent html index
    $("input#evento[name='evento'][type='button']").val("Créer un Evento");
    $("#evento + span[type='button'] i").removeAttr('data-creneau-url');
    $("#evento + span[type='button']").addClass('d-none');

    $("#form input[name='eventoTitre']").remove();
    $("#form input[name='summaryevento']").remove();

    $("#eventoSubmit").text("Créer Evento");
}

function domMiseajourEventoHTML(urlEvento) {
    let titre = $("input[name='titrevento']").val();
    let desc = $("textarea[name='summaryevento']").val();

    $("#eventoModalHeader p").text("Mise à jour de l'evento");

    $("#eventoModalHeader a").attr('href', urlEvento);
    $("#eventoModalHeader a").text(titre);

    $("#evento + span[type='button'] i").removeClass('bi-check2');
    $("#evento + span[type='button'] i").addClass('bi-clipboard');

    $("#eventoModalHeader span").removeClass('d-none');

    // affichage cohérent html index
    $("input#evento[name='evento'][type='button']").val("Mettre à jour l'Evento");
    $("#evento + span[type='button'] i").attr('data-creneau-url', urlEvento);
    $("#evento + span[type='button']").removeClass('d-none');

    $("#eventoSubmit").text("Mettre à jour Evento");

    // ajout d'un input hidden pour passer le titre et la description en paramètre (pour assurer l'état des variables sur l'url $_GET )
    if ($("#form input[name='eventoTitre']").length == 0) {
        $("#form").append($("<input type='hidden' name='eventoTitre' value='"+ titre +"'>"));
    } else {
        $("#form input[name='eventoTitre']").val(titre);
    }

    if ($("#form input[name='summaryevento']").length == 0) {
        $("#form").append($("<input type='hidden' name='summaryevento' value='"+ desc +"'>"));
    } else {
        $("#form input[name='summaryevento']").val(desc);
    }
}

function eventoDatasRequest(args) {

    let idxChecked = $('#modalEventoCreneaux li > input:checked');

    let questions = [];

    idxChecked.each(function() {
        // l'index commence à 0 ...
        let idx = Number(this.value) + 1;
        let select = $("#listReponse li:nth-child("+ idx +") a");
        questions.push({'timestart': select.attr('timestart'), timeend: select.attr('timeend')});
    });

    let jsonData = Object.assign({}, eventoDraftBase);
    let propositionBase = Object.assign({}, jsonData.questions[0].propositions[0]);

    jsonData.title = args.titre;
    jsonData.description = args.desc;

    jsonData.id = args.id ? args.id : "";
    jsonData.path = args.path ? args.path : "";

    args.isAuth ? jsonData.settings.enable_anonymous_answer = 0 : jsonData.settings.enable_anonymous_answer = 1;
    args.isAuth ? jsonData.settings.reply_access = "opened_to_authenticated" : jsonData.settings.reply_access = "opened_to_everyone";

    args.isNotif ? jsonData.notify_new_guests = true : jsonData.notify_new_guests = false;
    args.isNotif ? jsonData.notify_update = true : jsonData.notify_update = false;

    let lastQEndTs = questions[questions.length - 1].timeend;
    jsonData.settings.auto_close = moment(moment.unix(lastQEndTs).add('1','day')).unix();

    let insertProposition = [];
    for (const question of questions) {
        let timestart = question.timestart;
        let timeend = question.timeend;

        let base_day = moment(moment.unix(timestart).format('Y-M-D') + ' 00:00:00', 'YYYY-M-D').unix();

        // pour quelle raison il faut rajouter 1 jour pour evento, je l'ignore
        base_day = moment.unix(base_day).add(1, 'day').unix();
        let local_base_day = base_day + (base_day - (new Date(moment.unix(base_day).utc().format().slice(0, -1)).valueOf())/1000);

        let propose = Object.assign({}, propositionBase);

        propose.base_day = base_day;
        propose.local_base_day = local_base_day;
        propose.base_time = timestart - local_base_day;
        propose.end_time = timeend - local_base_day;
        propose.label = moment.unix(base_day).format('LLLL').replace(' 00:00', '') + ' de ' + moment.unix(timestart).format('HH:mm').replace(':','H') + ' à ' + moment.unix(timeend).format('HH:mm').replace(':','H');

        insertProposition.push(propose);
    }
    jsonData.questions[0].propositions = insertProposition;

    jsonData.guests = [];
    jsonData.new_guests = [];

    if (jsonData.notify_new_guests == true) {
        form.listDisplayname.forEach((datas) => {
            jsonData.new_guests.push(datas.mail);
            jsonData.guests.push({email:datas.mail,name:datas.displayName});
        });
    }

    return jsonData;
}

function eventoAjaxSurvey(datas, type, url, traiteReponseAjax) {
    $.ajax({
        async: true,
        url: url, 
        type: type,
        contentType: 'application/json',
        data: JSON.stringify(datas),
        crossDomain: true,
        xhrFields: {withCredentials: true},
        done: () => console.log('done'),
        fail: () => console.log('fail'),
        success: (response) => {
            traiteReponseAjax(response, datas);
            console.log("success");
        },
        complete: (function() {
            $(".modal-backdrop").hide();
            $('#spinnerEvento').hide();
            console.log("complete");
        })
    });
}

function eventoFormCheck() {
    let titreSel = $("input[name='titrevento']");
    if (titreSel.val().length == 0) {
        $("input[name='titrevento']").get(0).setCustomValidity(true);
        $("input[name='titrevento']").get(0).reportValidity();
        return false;
    } else {
        $("input[name='titrevento']").get(0).setCustomValidity('');
    }

    let descSel = $("textarea[name='summaryevento']");
    if (descSel.val().length == 0) {
        $("textarea[name='summaryevento']").get(0).setCustomValidity(true);
        $("textarea[name='summaryevento']").get(0).reportValidity();
        return false;
    } else {
       $("textarea[name='summaryevento']").get(0).setCustomValidity('');
    }

    return true;
}

function eventoCheck() {
    let evento = $('#eventoSubmit');

    let selector = $('#modalEventoCreneaux li > input:checked');

    if (selector.length == 0) {
        evento.attr('disabled', 'disabled');
        if (evento.hasClass('btn-success')) {
            evento.removeClass('btn-success');
        }
        evento.addClass('btn-secondary');
    } else {
        evento.removeAttr('disabled');
        if (evento.hasClass('btn-secondary')) {
            evento.removeClass('btn-secondary');
        }
        evento.addClass('btn-success');
    }
}



});