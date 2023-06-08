var urlwsgroup = 'https://wsgroups.univ-paris1.fr/searchUser';
var divpersonselect = "#divpersonselect";
var idperson_ul = "#person_ul";

function getCurrentOptions() {
    var getVals = new Array();

    $(idperson_ul + " li input").each(function (idx, option) {
        getVals[idx] = option.value;
    });

    return getVals;
}

function setOptionsUid(jsuids) {
    for (uid of jsuids) {
        addOptionWithUid(uid);
    }
}

function addOptionWithUid(uid, displayName) {

    if ($(divpersonselect).is(":hidden"))
        $(divpersonselect).show();

    var newLi = $('<li>');
    var label = $('<input>');
    label.attr('type', 'radio').attr('name', 'listuid[]').val(uid).css('display', 'none');

    newLi.append(label);
    newLi.append(displayName);

    var button = $('<button>').text('delete');

    newLi.append(button);
    $(idperson_ul).append(newLi);

    button.on("click", function () {
        $(this).parent().remove();

        if (getCurrentOptions().length == 0)
            $(divpersonselect).hide();
    });
}

function addOptionUid(uid, displayName) {
    //var uid=this.value;
    var vals = getCurrentOptions();
    if (vals.indexOf(uid) == -1) {
        addOptionWithUid(uid, displayName);
    }
    if (vals.length > 1) {
        $(".alertrequire").hide();
    }
}

function wsCallbackUid(event, ui) {

    var uid = ui.item.uid;
    var displayName = ui.item.displayName;

    addOptionUid(uid, displayName);
    
    return false;
}

$(function() {
    $("#person").autocompleteUser(
            urlwsgroup, {
                select: wsCallbackUid,
                wantedAttr: "uid"
            }
    );

    $("#form").on("submit", function (e) {
        e.preventDefault();

        var vals = getCurrentOptions();

        if (vals.length > 1)
            this.submit();
        else
            $(".alertrequire").show();
    });
    
    $('#divpersonselect').hide();
});