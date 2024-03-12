let divpersonselect = "#divpersonselect";
let idperson_ul = "#person_ul";
let listDisplayname = new Map();

function addOptionWithUid(uid) {       
    let newLi = $('<li>').attr('class', 'row align-items-center');

    newLi.append($('<input>').attr('type', 'text').attr('name', 'listuids[]').attr('multiple', true).attr('checked', true).val(uid).css('display', 'none'));
    newLi.append($('<label>').attr('class', 'col-3 px-0').text(uid));

    let button = $('<button>').text('supprimer').attr('class', 'col-2 px-0');
    newLi.append(button);

    $(idperson_ul).append(newLi);

    button.on("click", function () {
        $(this).parent().remove();
    });
}

function setOptionsUid(jsuids) {
    for (uid of jsuids) {
        addOptionWithUid(uid);
    }
}

$(function () {
    let select = $('input#person');
    let ajout = $('<button>Ajouter</button>');

    ajout.on('click', function(event ) {
        event.preventDefault();
        let uid = $('input#person').val();
        addOptionWithUid(uid);
    });
    select.after(ajout);
});
