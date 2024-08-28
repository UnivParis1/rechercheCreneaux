$(function() {
    $('#eventoSubmit').on("click", function() {
        $("#eventoSubmit").removeAttr("formnovalidate");
        if (eventoFormCheck() == false) {
            return;
        }

        $('#modalEvento').modal('hide');
        $('#spinnerEvento').modal('show');

        let titre = $("input[name='titrevento']").val();
        let desc = $("textarea[name='summaryevento']").val();

        let isNotNotif = $('#NotifEvento').is(':checked');
        let isAuth = $('#AuthEvento').is(':checked');
        // recherche evento existant avec titre et description
        let id = eventoAjaxSurvey({title: titre, description: desc}, 'GET');

        if (id == false) {
            let dataPost = eventoDatasRequest({titre: titre, desc: desc, phase: 1, isNotNotif: isNotNotif, isAuth: isAuth});

            eventoAjaxSurvey(dataPost, 'POST');
        }
    });
}); 

function eventoDatasRequest(args) {
    let questions = $('#reponse ul li input:checked~a');

    let jsonData = Object.assign({}, args.phase == 1 ? eventoDraftBase : eventoSurveyBase);
    let propositionBase = Object.assign({}, jsonData.questions[0].propositions[0]);

    jsonData.title = args.titre;
    jsonData.description = args.desc;

    args.isAuth ? jsonData.settings.enable_anonymous_answer = 0 : 1;
    args.isAuth ? jsonData.settings.reply_access = "opened_to_authenticated" : "opened_to_everyone";

    args.isNotNotif ? jsonData.settings.dont_receive_invitation_copy = 1 : 0;
    args.isNotNotif ? jsonData.settings.dont_notify_on_reply = 1 : 0;

    let lastQEndTs = questions[questions.length - 1].getAttribute('timeend');
    jsonData.settings.auto_close = moment(moment.unix(lastQEndTs).add('1','day')).unix();

    let insertProposition = [];
    for (let i =0; i < questions.length; i++) {
        let question = questions.get(i);
        let timestart = question.getAttribute('timestart');
        let timeend = question.getAttribute('timeend');

        let base_day = moment(moment.unix(timestart).format('Y-M-D') + ' 00:00:00', 'YYYY-M-D').unix();
        // dirty hack pour faire correspondre les bonnes infos sur evento
        base_day = base_day + (3600*24);
        let local_base_day = base_day + (3600*2);

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

    listDisplayname.forEach(function (datas) {
        jsonData.new_guests.push(datas.mail);
        jsonData.guests.push({email:datas.mail,name:datas.displayName});
    });

    return jsonData;
}

function eventoAjaxSurvey(datas, type) {
   let id = false;

    $.ajax({
        url: eventoWsUrl + "survey",
        type: type,
        contentType: 'application/json',
//        data: datas,
        data: JSON.stringify(datas),
        crossDomain: true,
        xhrFields: {
             withCredentials: true
        },
        done: function () {
            console.log('done');
        },
        fail: function (data) {
            console.log('fail');
        },
        success: function(response) {
            console.log("success");
            if (typeof(response.path)!= 'undefined') {

                if (response.data.path.indexOf('https://evento') != -1 && response.data.path.indexOf('/survey/') != -1) {
                    let urlEvento = response.data.path.replace('renater', 'univ-paris1');
                    let div = $('#eventoDiv');
                    div.empty().append("<a href='" + urlEvento + "' target='_blank'>" + urlEvento + "</a>");

                    let copySpan = $('<span type="button" class="btn-clipboard d-inline px-2" title="Copier le lien"><i class="bi bi-clipboard" aria-hidden="true"></i></span>');

                    copySpan.on("click", function () {
                        $(this).children().removeClass('bi-clipboard').addClass("bi-check2");
                        navigator.clipboard.writeText(urlEvento);
                    });

                    div.append(copySpan);
                }
            }
        },
        complete: function() {
            console.log('complete');
            $(".modal-backdrop").remove();
            $('#spinnerEvento').hide();
        }});

    return id;
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

function updateEventoCreneaux(selector) {
    let ulEventoCreneaux = $('#modalEventoCreneaux');

    ulEventoCreneaux.empty();

    selector.each(function() {
        let ts = $(this).attr('timestart');
        let te = $(this).attr('timeend');

        ulEventoCreneaux.append('<li>' + textTimeStr(ts, te) + '</li>');
    });
}

function eventoCheck() {
    let evento = $('#evento');

    let selector = $('#reponse ul li input[type="checkbox"]:checked ~ a');

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

    updateEventoCreneaux(selector);
}
