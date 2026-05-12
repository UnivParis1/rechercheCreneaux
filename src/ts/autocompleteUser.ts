import autocomplete from "autocompleter";
import $ from 'jquery';


declare global {
    interface JQuery {
      autocompleteGroup(this:any, searchGroupURL:string, options:any): JQuery;
      autocompleteUserAndGroup(this:any, searchUserAndGroupURL:string, options:any): JQuery;
      autocompleteUser(this:any, searchUserURL:string, options:any): JQuery;
      autocompleteUser_remove(this:any): JQuery;
      handlePlaceholderOnIE(): JQuery;
    }
}

var autocompleteGroup:any = function (this:any, searchGroupURL:string, options:any) {
    if (!searchGroupURL) throw "missing param searchGroupURL";

    var settings = $.extend( 
    { 'minLength' : 3,
      'maxRows' : 20,
      'wantedAttr' : 'key',
    }, options);

    var warningMsgs = $.extend(defaultWarningMsgs, settings.warningMsgs);    
    
    var wsParams = $.extend({ 
    maxRows: settings.maxRows
    }, settings.wsParams);

  var input:any = this[0];

    var source = function( request:any, response:any ) {
    input.classList.add('ac-loading');
    if (input.kraaden_autocomplete_installed.navigate) {
      var pivot_item = input.kraaden_autocomplete_installed.navigate
      delete input.kraaden_autocomplete_installed.navigate;
      return fetch_subAndSuperGroups(pivot_item, settings, response)
    }
    wsParams.token = request.term = request.term.trim();
      $.ajax({
      url: searchGroupURL,
      dataType: "jsonp",
      crossDomain: true, // needed if searchGroupURL is CAS-ified or on a different host than application using autocompleteUser
      data: wsParams,
      error: function () {
          // we should display on error. but we do not have a nice error to display
          // the least we can do is to show the user the request is finished!
        input.kraaden_autocomplete_installed.warning = {}
        response([ { wsError: true } ]);
      },
      success: function (data) {
          data = transformGroupItems(data, settings.wantedAttr, request.term);

        let warning:any = {};
        input.kraaden_autocomplete_installed.warning = warning;
          if (data.length >= settings.maxRows) {
          warning.partialResults = settings.maxRows;;
          }
          response(data);
      }
      }).always(() => input.classList.remove('ac-loading'));
    };

  var navigate = settings.subAndSuperGroupsURL && onNavigate(input);

  // disable browser proposing previous values
  input.autocomplete = "off";     

  input.kraaden_autocomplete_installed = autocomplete({ 
      showOnFocus: false,
      minLength: settings.minLength,
      debounceWaitMs: 200,
      preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
      input: input, 
      fetch: function(text:string, update:any) {
         source({ term: text }, update)
      },
      onSelect: onSelect(input, settings),
      emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
      render: myRenderGroupItem(navigate),
      customize: function(input:any, _inputRect:any, container:any) {
          mayAddWarning(container, 'top', input.kraaden_autocomplete_installed.warning, warningMsgs);
      },
    });
};

function myRenderUserOrGroupItem(item:any) {
    if (item && item.category === 'users')
        return myRenderUserItem(item);
    else
        return myRenderGroupItem(undefined)(item);
}

var autocompleteUserAndGroup:any = function (this:any, searchUserAndGroupURL:string, options:any) {
    if (!searchUserAndGroupURL) throw "missing param searchUserAndGroupURL";

    var settings = $.extend( 
    { 'minLength' : 2,
      'user_minLengthFullSearch' : 4,
      'user_attrs' : attrs,
      'maxRows' : 10,
          'group_minLength' : 2,
      'warningPosition': 'bottom',
    }, options);

    var warningMsgs = $.extend(defaultWarningMsgs, settings.warningMsgs);     
    

    var input = this[0];

    var source = function( request:any, response:any ) {
      input.classList.add('ac-loading');
      var wsParams = $.extend({ 
          maxRows: settings.maxRows,
          user_attrs: settings.user_attrs
      }, options.wsParams);
      wsParams.token = request.term = request.term.trim();
      $.ajax({
      url: searchUserAndGroupURL,
      dataType: "jsonp",
      crossDomain: true, // needed if searchGroupURL is CAS-ified or on a different host than application using autocompleteUser
      data: wsParams,
      error: function () {
          // we should display on error. but we do not have a nice error to display
          // the least we can do is to show the user the request is finished!
        input.kraaden_autocomplete_installed.warning = {}
        response([ { wsError: true } ]);
      },
      success: function (data) {
                  if (settings.onSearchSuccess) data = settings.onSearchSuccess(data);
                  var users = $.grep(data.users, function (item:any, i) {
          return item.displayName !== "supannListeRouge"; 
          });
          var nbListeRouge = users.length - data.users.length;

                  $.each(users, function (i, item) { item.category = 'users'; });                    
          users = transformUserItems(users, 'uid', request.term);
          var groups = transformGroupItems(data.groups, 'key', request.term);

          var roles = 
              transformRoleGeneriqueItems(data.supannRoleGenerique || [], 'supannRoleGenerique', 'Fonctions', request.term).concat(
              transformRoleGeneriqueItems(data.supannActivite || [], 'supannActivite', 'Emplois', request.term)
          )
          
          let warning:any = {}
          input.kraaden_autocomplete_installed.warning = warning
                  var l = users.concat(roles, groups);
          if (users.length >= settings.maxRows || groups.length >= settings.maxRows) {
          warning.partialResults = settings.maxRows;;
          } else if (request.term.length < settings.user_minLengthFullSearch) {
          warning.partialResultsNoFullSearch = 1;
          }
          warning.nbListeRouge = nbListeRouge;
                 
          response(l);
      }
      }).always(() => input.classList.remove('ac-loading'));
    };

    // disable browser proposing previous values
    input.autocomplete = "off";

    input.kraaden_autocomplete_installed = autocomplete({ 
      showOnFocus: false,
      minLength: settings.minLength,
      disableAutoSelect: settings.disableAutoSelect,
      debounceWaitMs: 200,
      preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
      input: input, 
      fetch: function(text:string, update:any) {
         source({ term: text }, update)
      },
      onSelect: onSelect(input, settings),
      emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
      render: myRenderUserOrGroupItem,
      customize: function(input:any, _inputRect:any, container:any) {
          mayAddWarning(container, settings.warningPosition, input.kraaden_autocomplete_installed.warning, warningMsgs);
      },
    });
};
  
var autocompleteUser_remove:any = function (this:any) {
  var input = this[0];
  if (input.kraaden_autocomplete_installed)  {
      input.kraaden_autocomplete_installed.destroy()
      delete input.kraaden_autocomplete_installed
  }
}

var handlePlaceholderOnIE:any = function () {
  // doing nothing (IE is dead!)
};

var attrs = "uid,mail,displayName,cn,employeeType,departmentNumber,eduPersonPrimaryAffiliation,supannEntiteAffectation-ou,supannRoleGenerique,supannEtablissement";
  var affiliation2order:any = { staff: 1, teacher: 2, researcher: 3, emeritus: 4, student: 5, affiliate: 6, alum: 7, member: 8, "registered-reader": 9, "library-walk-in": 10 };
  var affiliation2text:any = { teacher: "Enseignants", student: "Etudiants", staff: "Biatss", researcher: "Chercheurs", emeritus: "Professeurs émérites", affiliate: "Invité", alum: "Anciens étudiants", retired: "Retraités", "registered-reader": "Lecteur externe", "library-walk-in": "Visiteur bibliothèque" };

var category2order:any = { groups_role: 6, structures: 5, groups_structures: 5, affiliation: 5, diploma: 1, elp: 2, gpelp: 3, gpetp: 4 };

var category2text:any = {
    groups_role: 'Fonctions',
    structures: 'Directions / Composantes / Laboratoires',
    groups_structures: 'Directions / Composantes / Laboratoires',
    location: 'Sites',
    affiliation: 'Directions / Composantes / Laboratoires',
    diploma: 'Diplômes / Étapes',
    elp: 'Groupes Matières',
    gpelp: 'Groupes TD'
};
var subAndSuper_category2text:any = {
    structures: 'Groupes parents',
    affiliation: 'Groupes parents',
    diploma: 'Étapes associées',
    elp: 'Matières associées',
    gpelp: 'Groupes TD associés'
};

var symbol_navigate = "\u21B8";

var highlight = function (text: string) {
    return "<span class='match'>" + text + "</span>";
};

var getDetails = function (item: any) {
    var details = [];

    if (item.mail && item.searchedTokenL === item.mail.toLowerCase()) {
    details.push(highlight(item.mail));
    } else if (item.duplicateDisplayName) {
    details.push(item.mail);
    }
    if (item.employeeType)
    details.push(item.employeeType.join(" - "));
    if (item.supannRoleGenerique)
    details.push(item.supannRoleGenerique.join(" - "));
    if (item['supannEntiteAffectation-ou']) {
    var prev = details.pop();
    details.push((prev ? prev + " - " : '') + item['supannEntiteAffectation-ou'].join(" - "));
    }
    if (item.departmentNumber) {
    details.push((item.departmentNumber.count >= 2 ? "Disciplines : " : "Discipline : ") + item.departmentNumber.join(' - '));
    }
      if (item.supannEtablissement)
	  details.push(item.supannEtablissement.join(" - "));

    if (details.length) 
    return "<div class='details'>" + details.join("<br>") + "</div>"
    else
    return "";
};

var highlightMatched = function (text: string, searchedTokenL: any) {
  var textL = text.toLowerCase();
  var pos = textL.search(searchedTokenL);
  if (pos < 0) 
      return null;
  else {
      var endPos = pos + searchedTokenL.length;
      return text.substring(0, pos) + 
      highlight(text.substring(pos, endPos)) +
      text.substring(endPos);
  }
};

var getNiceDisplayName = function (item: any) {
    var uid = item.uid;
    var displayName = item.displayName;
    var searchedTokenL = item.searchedTokenL;
    var display_uid = item.duplicateDisplayName;
    if (uid === searchedTokenL) {
    display_uid = true;
    uid = highlight(uid);
    } else if (item.cn.toLowerCase().indexOf(searchedTokenL) === 0)
    displayName = highlightMatched(item.cn, searchedTokenL);
    else {
    displayName = highlightMatched(item.displayName, searchedTokenL);
    if (!displayName && item.mail) displayName = highlightMatched(item.mail, searchedTokenL);
    if (!displayName) displayName = item.displayName;
    }

    if (display_uid)
    displayName += " (" + uid + ")";

    return displayName;
};

var renderOneWarning = function(container: any, topOrBottom: string, msg:string) {
    let elt = $("<div></div>").addClass("warning").append(msg)
    if (topOrBottom === 'top') elt.prependTo(container); else elt.appendTo(container)
};

var defaultWarningMsgs = {
  listeRouge_plural: "NB : des r&eacute;sultats ont &eacute;t&eacute; cach&eacute;s<br>&agrave; la demande des personnes.",
  listeRouge_one:    "NB : un r&eacute;sultat a &eacute;t&eacute; cach&eacute;<br>&agrave; la demande de la personne."
}

var mayAddWarning:any = function(container: any, topOrBottom: string, item: any, warningMsgs: any) {
    if (item.nbListeRouge)
      renderOneWarning(container, topOrBottom, 
        item.nbListeRouge > 1 ? warningMsgs.listeRouge_plural : warningMsgs.listeRouge_one
    );

    if (item.partialResults)
      renderOneWarning(container, topOrBottom, "Votre recherche est limit&eacute;e &agrave; " + item.partialResults + " r&eacute;sultats.<br>Pour les autres r&eacute;sultats, veuillez affiner la recherche.");
    if (item.partialResultsNoFullSearch)
      renderOneWarning(container, topOrBottom, "Votre recherche est limit&eacute;e.<br>Pour les autres r&eacute;sultats, veuillez affiner la recherche.");
};
var myRenderItemRaw = function(item: any, moreClass: string, renderItemContent: any) {
  if (item.wsError) 
      return $("<div></div>").addClass("warning").append("Erreur web service")[0]

  var content = renderItemContent(item);
    return $("<div></div>").addClass(item.odd_even ? "odd" : "even").addClass(moreClass).addClass("ui-menu-item")
      .data("item.autocomplete", item)
      .append("<a>" + content + "</a>")[0]
};
var myRenderUserItem:any = function (item: any) {
    return myRenderItemRaw(item, 'userItem', function (item: any) {
    return getNiceDisplayName(item) + getDetails(item);
    });
};

var countOccurences = function (list: any) {
  var r:any = {};
  $.each(list, function (i, e: any) {
      r[e] = (r[e] || 0) + 1;
  });
  return r;
};

var sortByAffiliation = function (items: any) {
    return items.sort(function(a: any,b: any) { 
    return (affiliation2order[a.eduPersonPrimaryAffiliation] || 99) - (affiliation2order[b.eduPersonPrimaryAffiliation] || 99);
    });
}

var groupByAttr = function(l:any, attrName: string) {
    var r:any = [];
    var subl:any, prevAttrVal:string;
    $.each(l, function (i, e) {
    var attrVal = e[attrName] || '';	    
    if (attrVal != prevAttrVal) {
        subl = [];
        r.push(subl);
        prevAttrVal = attrVal;
    }
    subl.push(e);
  });
  return r;
};

var transformItems = function (items:any, idAttr:string, displayAttr:string, searchedToken:string) {
  var searchedTokenL = searchedToken.toLowerCase();
  var odd_even:boolean;
  $.each(items, function ( i, item ) {
      item.label = item[displayAttr];
      item.value = item[idAttr] || 'unknown';
      item.searchedTokenL = searchedTokenL;
      item.odd_even = odd_even = !odd_even;
  });
};

var transformUserItems = function (items:any, wantedAttr:string, searchedToken:string) {
    items = sortByAffiliation(items);

    var items_by_affiliation = groupByAttr(items, 'eduPersonPrimaryAffiliation');

    transformItems(items, wantedAttr, 'displayName', searchedToken);
    var r:any = [];
    $.each(items_by_affiliation, function (i, items) {
  // nb: "cn" is easer to compare since there is no accents. Two "displayName"s could be equal after removing accents.
  var cnOccurences = countOccurences($.map(items, function (item) { return item.cn }));
  var displayNameOccurences = countOccurences($.map(items, function (item) { return item.displayName }));
  $.each(items, function ( i, item ) {
      var affiliation = item.eduPersonPrimaryAffiliation;
      item.group = affiliation2text[affiliation] || "Divers" ;

      if (displayNameOccurences[item.displayName] > 1 || cnOccurences[item.cn] > 1)
      item.duplicateDisplayName = true;
  });
  $.merge(r, items);
    });
    return r;
};

function onSelect(input:any, settings:any) {
  return function(item:any) {
      if (settings.select) {
          const fake_event = {}
          settings.select.bind(input)(fake_event, { item })
      } else {
          input.value = item.value;
      }
  }
}

var autocompleteUser:any = function (this:any, searchUserURL:string, options:any) {
    if (!searchUserURL) throw "missing param searchUserURL";

    var settings = $.extend( 
    { 'minLength' : 2,
      'minLengthFullSearch' : 4,
      'maxRows' : 10,
      'wantedAttr' : 'uid',
      'attrs' : attrs
    }, options);

    var warningMsgs = $.extend(defaultWarningMsgs, settings.warningMsgs);

    var wsParams = $.extend({ 
    maxRows: settings.maxRows, 
    attrs: settings.attrs + "," + settings.wantedAttr
    }, settings.wsParams);

    var input = this[0];

    var source = function( request:any, response:any ) {
    input.classList.add('ac-loading');
    wsParams.token = request.term = request.term.trim();
      $.ajax({
      url: searchUserURL,
      dataType: "jsonp",
      crossDomain: true, // needed if searchUserURL is CAS-ified or on a different host than application using autocompleteUser
      data: wsParams,
      error: function () {
          // we should display on error. but we do not have a nice error to display
          // the least we can do is to show the user the request is finished!
          input.kraaden_autocomplete_installed.warning = {};
          response([ { wsError: true } ]);
      },
      success: function (dataAll:any) {
                  if (options.modifyResults) {
                      dataAll = options.modifyResults(dataAll, wsParams.token);
                  }
          let data:any = $.grep(dataAll, function (item:any, i) { 
          return item.displayName !== "supannListeRouge"; 
          });
          let nbListeRouge:number = dataAll.length - data.length;

          data = transformUserItems(data, settings.wantedAttr, request.term);

          let warning:any = {};
          input.kraaden_autocomplete_installed.warning = warning;
          if (data.length >= settings.maxRows) {
          warning.partialResults = settings.maxRows;
          } else if (request.term.length < settings.minLengthFullSearch) {
          warning.partialResultsNoFullSearch = 1;
          }
          warning.nbListeRouge = nbListeRouge;

          response(data);
      }
      }).always(() => input.classList.remove('ac-loading'));
    };

    // disable browser proposing previous values
    input.autocomplete = "off";

    input.kraaden_autocomplete_installed = autocomplete({ 
      showOnFocus: false,
      minLength: settings.minLength,
      debounceWaitMs: 200,
      preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
      input: input, 
      fetch: function(text:string, update:any) {
         source({ term: text }, update)
      },
      onSelect: onSelect(input, settings),
      emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
      render: myRenderUserItem,
      customize: function(input:any, _inputRect:any, container:any) {
          mayAddWarning(container, 'top', input.kraaden_autocomplete_installed.warning, warningMsgs);
      },
    });

    return { wsParams: wsParams };
};


var transformGroupItems = function (items:any, wantedAttr:string, searchedToken:string) {
    transformItems(items, wantedAttr, 'name', searchedToken);
    $.each(items, function ( i, item ) {
    item.group = category2text[item.category || ""] || 'Autres types de groupes';
  });
  return sortByGroupCategory(items);
};

var transformRoleGeneriqueItems = function (items:any, category:string, group:string, searchedToken:string) {
    $.each(items, function ( i, item ) {
      item.category = category;
      item.group = group;
    });
    items.sort(function (a:any, b:any) { return a.name.localeCompare(b.name) });
    transformItems(items, 'key', 'name', searchedToken);
    return items;
}

function object_values(o:any) {
    return $.map(o, function (e) { return e; })
}

// ["aa", "aaa", "ab"] => "a"
function find_common_prefix(list:any){
    var A = list.slice(0).sort(), word1 = A[0], word2 = A[A.length-1];
    var len = word1.length, i= 0;
    while(i < len && word1.charAt(i)=== word2.charAt(i)) i++;
    return word1.substring(0, i);
}

// ["aa", "aaa", "ab"] => ["a", "aa", "b"]
function remove_common_prefix(list:any) {
    var offset = find_common_prefix(list).length;
    return $.map(list, function(e) {
    return e.substring(offset);
    });
}

var simplifySubGroups = function (subGroups:any) {
    if (subGroups.length <= 1) return;
    var names = $.map(subGroups, function (e) { return e.name });
    var offset = find_common_prefix(names).length;
    $.each(subGroups, function(i, e) {
    e.name = e.name.substring(offset);
    });
};

var flattenSuperGroups = function (superGroups:any, groupId:string) {
    // remove current group
    delete superGroups[groupId];
    return sortByGroupCategory(object_values(superGroups));
};

var transformSubAndSuperGroups = function (items:any, wantedAttr:string) {
    var categoryText:any;
    var odd_even:boolean;
    $.each(items, function ( i, item ) {
      item.label = item.name;
      item.value = item[wantedAttr];

    var categoryText_ = item.selected ? 'Selectionné' : subAndSuper_category2text[item.category || ""] || 'Autres types de groupes';
      if (categoryText != categoryText_) {
    item.group = categoryText = categoryText_;
      }
      item.odd_even = odd_even = !odd_even;
  });
};

var onNavigate = function (input:any) {
  return function (item:any) {
      input.kraaden_autocomplete_installed.navigate = item
      input.kraaden_autocomplete_installed.warning = {};
      input.kraaden_autocomplete_installed.fetch()
  }
}

function fetch_subAndSuperGroups(item:any, settings:any, response:any) {
      var current:any = $.extend({}, item);
      current.selected = true;

      var wsParams = $.extend({ 
      key: item.key,
      depth: 99
      }, settings.wsParams);

      $.ajax({
      url: settings.subAndSuperGroupsURL,
      dataType: "jsonp",
      crossDomain: true, // needed if searchGroupURL is CAS-ified or on a different host than application using autocompleteUser
      data: wsParams,
      error: function () {
          // we should display on error. but we do not have a nice error to display
          // the least we can do is to show the user the request is finished!
        item.kraaden_autocomplete_installed.warning = {}
        response([ { wsError: true } ]);
      },
      success: function (data) {
          var subGroups = sortByGroupCategory(data.subGroups);
          simplifySubGroups(subGroups);
          var superGroups = flattenSuperGroups(data.superGroups, item.key);
          var items = $.merge(subGroups, superGroups);
        transformSubAndSuperGroups(items, settings.wantedAttr);
        response($.merge([current], items));
      }
      });
}

 var myRenderGroupItem:any = function (navigate:any) {
 return function (item:any) {

  var content = item.name;
    var li = $("<div></div>").addClass(item.odd_even ? "odd" : "even").addClass('groupItem').addClass("ui-menu-item")
       .data("item.autocomplete", item);

  var button_navigate;
  if (navigate && !item.selected) {
    button_navigate = $("<a style='display: inline' href='#'>" + symbol_navigate + "</a>").click(function (event) {
      navigate(item);
      return false;
    });
    li.append($("<big>").append(button_navigate));
  }
      li.append($("<a style='display: inline' >")
         .append(content + " &nbsp;"));
    return li[0]
   };
};

function sortByGroupCategory (items:any) {
    return items.sort(function (a:any, b:any) {
    var cmp = (category2order[a.category] || 99) - (category2order[b.category] || 99);
        return cmp ? cmp : a.name.localeCompare(b.name);
    });
}

$.fn.extend({
    autocompleteGroup:autocompleteGroup,
    autocompleteUserAndGroup:autocompleteUserAndGroup,
    autocompleteUser:autocompleteUser,
    autocompleteUser_remove:autocompleteUser_remove,
    handlePlaceholderOnIE:handlePlaceholderOnIE
});

export default JQuery;
