
(function() {
    function loadCSS(url) {
      var elt = document.createElement('link');
      elt.setAttribute('rel', 'stylesheet');
      elt.setAttribute('type', 'text/css');
      elt.setAttribute('href', url);
      document.head.appendChild(elt);
    }
    loadCSS('//wsgroups.univ-paris1.fr/web-widget/autocompleteUser.css');

})();

this.window = this.window || {};
this.window.kraaden_autocomplete = (function () {
  'use strict';

  /**
   * Copyright (c) 2016 Denys Krasnoshchok
   *
   * Homepage: https://smartscheduling.com/en/documentation/autocomplete
   * Source: https://github.com/kraaden/autocomplete
   *
   * MIT License
   */
  function autocomplete(settings) {
      // just an alias to minimize JS file size
      var doc = document;
      var container = settings.container || doc.createElement('div');
      var preventSubmit = settings.preventSubmit || 0 /* Never */;
      container.id = container.id || 'autocomplete-' + uid();
      var containerStyle = container.style;
      var debounceWaitMs = settings.debounceWaitMs || 0;
      var disableAutoSelect = settings.disableAutoSelect;
      var customContainerParent = container.parentElement;
      var items = [];
      var inputValue = '';
      var minLen = 2;
      var showOnFocus = settings.showOnFocus;
      var selected;
      var fetchCounter = 0;
      var debounceTimer;
      var destroyed = false;
      // Fixes #104: autocomplete selection is broken on Firefox for Android
      var suppressAutocomplete = false;
      if (settings.minLength !== undefined) {
          minLen = settings.minLength;
      }
      if (!settings.input) {
          throw new Error('input undefined');
      }
      var input = settings.input;

      container.className = 'autocomplete ' + (settings.className || '');
      container.setAttribute('role', 'listbox');
      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-expanded', 'false');
      input.setAttribute('aria-autocomplete', 'list');
      input.setAttribute('aria-controls', container.id);
      input.setAttribute('aria-owns', container.id);
      input.setAttribute('aria-activedescendant', '');
      input.setAttribute('aria-haspopup', 'listbox');
      // IOS implementation for fixed positioning has many bugs, so we will use absolute positioning
      containerStyle.position = 'absolute';
      /**
       * Generate a very complex textual ID that greatly reduces the chance of a collision with another ID or text.
       */
      function uid() {
          return Date.now().toString(36) + Math.random().toString(36).substring(2);
      }
      /**
       * Detach the container from DOM
       */
      function detach() {        
          var parent = container.parentNode;
          if (parent) {
              parent.removeChild(container);
          }
      }
      /**
       * Clear debouncing timer if assigned
       */
      function clearDebounceTimer() {
          if (debounceTimer) {
              window.clearTimeout(debounceTimer);
          }
      }
      /**
       * Attach the container to DOM
       */
      function attach() {        
          if (!container.parentNode) {
              (customContainerParent || doc.body).appendChild(container);
          }
      }
      /**
       * Check if container for autocomplete is displayed
       */
      function containerDisplayed() {
          return !!container.parentNode;
      }
      /**
       * Clear autocomplete state and hide container
       */
      function clear() {
          // prevent the update call if there are pending AJAX requests
          fetchCounter++;
          items = [];
          inputValue = '';
          selected = undefined;
          input.setAttribute('aria-activedescendant', '');
          input.setAttribute('aria-expanded', 'false');
          detach();
      }
      /**
       * Update autocomplete position
       */
      function updatePosition() {
          if (!containerDisplayed()) {
              return;
          }
          input.setAttribute('aria-expanded', 'true');
          containerStyle.height = 'auto';
          containerStyle.width = input.offsetWidth + 'px';
          var inputRect;
          function calc() {
              var docEl = doc.documentElement;
              var clientTop = docEl.clientTop || doc.body.clientTop || 0;
              var clientLeft = docEl.clientLeft || doc.body.clientLeft || 0;
              var scrollTop = window.pageYOffset || docEl.scrollTop;
              var scrollLeft = window.pageXOffset || docEl.scrollLeft;
              inputRect = input.getBoundingClientRect();
              var top = inputRect.top + input.offsetHeight + scrollTop - clientTop;
              var left = inputRect.left + scrollLeft - clientLeft;
              containerStyle.top = top + 'px';
              containerStyle.left = left + 'px';
              containerStyle.top = top + 'px';
              containerStyle.bottom = '';
              containerStyle.left = left + 'px';
          }
          // the calc method must be called twice, otherwise the calculation may be wrong on resize event (chrome browser)
          calc();
          calc();
          // do our best to display all choices
          container.scrollIntoView({ block: "nearest" });
          // but ensure the input is still visible
          input.scrollIntoView({ block: "nearest" });
          if (settings.customize && inputRect) {
              settings.customize(input, inputRect, container);
          }
      }
      /**
       * Redraw the autocomplete div element with suggestions
       */
      function update() {
          container.textContent = '';
          input.setAttribute('aria-activedescendant', '');
          // function for rendering autocomplete suggestions
          var render = function (item, _, __) {
              var itemElement = doc.createElement('div');
              itemElement.textContent = item.label || '';
              return itemElement;
          };
          if (settings.render) {
              render = settings.render;
          }
          // function to render autocomplete groups
          var renderGroup = function (groupName, _) {
              var groupDiv = doc.createElement('div');
              groupDiv.textContent = groupName;
              return groupDiv;
          };
          if (settings.renderGroup) {
              renderGroup = settings.renderGroup;
          }
          var fragment = doc.createDocumentFragment();
          var prevGroup = uid();
          items.forEach(function (item, index) {
              if (item.group && item.group !== prevGroup) {
                  prevGroup = item.group;
                  var groupDiv = renderGroup(item.group, inputValue);
                  if (groupDiv) {
                      groupDiv.className += ' group';
                      fragment.appendChild(groupDiv);
                  }
              }
              var div = render(item, inputValue, index);
              if (div) {
                  div.id = container.id + "_" + index;
                  div.setAttribute('role', 'option');
                  div.addEventListener('click', function (ev) {
                      suppressAutocomplete = true;
                      try {
                          settings.onSelect(item, input);
                      }
                      finally {
                          suppressAutocomplete = false;
                      }
                      clear();
                      ev.preventDefault();
                      ev.stopPropagation();
                  });
                  if (item === selected) {
                      div.className += ' selected';
                      div.setAttribute('aria-selected', 'true');
                      input.setAttribute('aria-activedescendant', div.id);
                  }
                  fragment.appendChild(div);
              }
          });
          container.appendChild(fragment);
          if (items.length < 1) {
              if (settings.emptyMsg) {
                  var empty = doc.createElement('div');
                  empty.id = container.id + "_" + uid();
                  empty.className = 'empty';
                  empty.textContent = settings.emptyMsg;
                  container.appendChild(empty);
                  input.setAttribute('aria-activedescendant', empty.id);
              }
              else {
                  clear();
                  return;
              }
          }
          attach();
          updatePosition();
          updateScroll();
      }
      function inputEventHandler() {
          if (!suppressAutocomplete) {
              fetch(0 /* Keyboard */);
          }
      }
      /**
       * Automatically move scroll bar if selected item is not visible
       */
      function updateScroll() {
          var elements = container.getElementsByClassName('selected');
          if (elements.length > 0) {
              var element = elements[0];
              element.scrollIntoView({ block: 'nearest' });
          }
      }
      function selectPreviousSuggestion() {
          var index = items.indexOf(selected);
          selected = index === -1
              ? undefined
              : items[(index + items.length - 1) % items.length];
          updateSelectedSuggestion(index);
      }
      function selectNextSuggestion() {
          var index = items.indexOf(selected);
          selected = items.length < 1
              ? undefined
              : index === -1
                  ? items[0]
                  : items[(index + 1) % items.length];
          updateSelectedSuggestion(index);
      }
      function updateSelectedSuggestion(index) {
          if (items.length > 0) {
              unselectSuggestion(index);
              selectSuggestion(items.indexOf(selected));
              updateScroll();
          }
      }
      function selectSuggestion(index) {
          var element = doc.getElementById(container.id + "_" + index);
          if (element) {
              element.classList.add('selected');
              element.setAttribute('aria-selected', 'true');
              input.setAttribute('aria-activedescendant', element.id);
          }
      }
      function unselectSuggestion(index) {
          var element = doc.getElementById(container.id + "_" + index);
          if (element) {
              element.classList.remove('selected');
              element.removeAttribute('aria-selected');
              input.removeAttribute('aria-activedescendant');
          }
      }
      function handleArrowAndEscapeKeys(ev, key) {
          var containerIsDisplayed = containerDisplayed();
          if (key === 'Escape') {
              clear();
          }
          else {
              if (!containerIsDisplayed || items.length < 1) {
                  return;
              }
              key === 'ArrowUp'
                  ? selectPreviousSuggestion()
                  : selectNextSuggestion();
          }
          ev.preventDefault();
          if (containerIsDisplayed) {
              ev.stopPropagation();
          }
      }
      function handleEnterKey(ev) {
          if (selected) {
              if (preventSubmit === 2 /* OnSelect */) {
                  ev.preventDefault();
              }
              suppressAutocomplete = true;
              try {
                  settings.onSelect(selected, input);
              }
              finally {
                  suppressAutocomplete = false;
              }
              clear();
          }
          if (preventSubmit === 1 /* Always */) {
              ev.preventDefault();
          }
      }
      function keydownEventHandler(ev) {
          var key = ev.key;
          switch (key) {
              case 'ArrowUp':
              case 'ArrowDown':
              case 'Escape':
                  handleArrowAndEscapeKeys(ev, key);
                  break;
              case 'Enter':
                  handleEnterKey(ev);
                  break;
          }
      }
      function focusEventHandler() {
          if (showOnFocus) {
              fetch(1 /* Focus */);
          }
      }
      function fetch(trigger) {
          if (input.value.length >= minLen) {
              clearDebounceTimer();
              debounceTimer = window.setTimeout(function () { return startFetch(input.value, trigger, input.selectionStart || 0); }, trigger === 0 /* Keyboard */ || trigger === 2 /* Mouse */ ? debounceWaitMs : 0);
          }
          else {
              clear();
          }
      }
      function startFetch(inputText, trigger, cursorPos) {
          if (destroyed)
              return;
          var savedFetchCounter = ++fetchCounter;
          input.classList.add('ac-loading');
          settings.fetch(inputText, function (elements) {
              input.classList.remove('ac-loading');
              if (fetchCounter === savedFetchCounter && elements) {
                  items = elements;
                  inputValue = inputText;
                  selected = (items.length < 1 || disableAutoSelect) ? undefined : items[0];
                  update();
              }
          }, trigger, cursorPos);
      }
      function keyupEventHandler(e) {
          if (settings.keyup) {
              settings.keyup({
                  event: e,
                  fetch: function () { return fetch(0 /* Keyboard */); }
              });
              return;
          }
          if (!containerDisplayed() && e.key === 'ArrowDown') {
              fetch(0 /* Keyboard */);
          }
      }
      function clickEventHandler(e) {
          settings.click && settings.click({
              event: e,
              fetch: function () { return fetch(2 /* Mouse */); }
          });
      }
      function blurEventHandler() {
          // when an item is selected by mouse click, the blur event will be initiated before the click event and remove DOM elements,
          // so that the click event will never be triggered. In order to avoid this issue, DOM removal should be delayed.
          setTimeout(function () {
              if (doc.activeElement !== input) {
                  clear();
              }
          }, 200);
      }
      function manualFetch() {
          startFetch(input.value, 3 /* Manual */, input.selectionStart || 0);
      }
      /**
       * Fixes #26: on long clicks focus will be lost and onSelect method will not be called
       */
      container.addEventListener('mousedown', function (evt) {
          evt.stopPropagation();
          evt.preventDefault();
      });
      // If the custom autocomplete container is already appended to the DOM during widget initialization, detach it.
      detach();
      /**
       * This function will remove DOM elements and clear event handlers
       */
      function destroy() {
          input.removeEventListener('focus', focusEventHandler);
          input.removeEventListener('keyup', keyupEventHandler);
          input.removeEventListener('click', clickEventHandler);
          input.removeEventListener('keydown', keydownEventHandler);
          input.removeEventListener('input', inputEventHandler);
          input.removeEventListener('blur', blurEventHandler);
          input.removeAttribute('role');
          input.removeAttribute('aria-expanded');
          input.removeAttribute('aria-autocomplete');
          input.removeAttribute('aria-controls');
          input.removeAttribute('aria-activedescendant');
          input.removeAttribute('aria-owns');
          input.removeAttribute('aria-haspopup');
          clearDebounceTimer();
          clear();
          destroyed = true;
      }
      // setup event handlers
      input.addEventListener('keyup', keyupEventHandler);
      input.addEventListener('click', clickEventHandler);
      input.addEventListener('keydown', keydownEventHandler);
      input.addEventListener('input', inputEventHandler);
      input.addEventListener('blur', blurEventHandler);
      input.addEventListener('focus', focusEventHandler);
      return {
          destroy: destroy,
          fetch: manualFetch
      };
  }

  return autocomplete;

}());
//# sourceMappingURL=autocomplete.js.map
(function ($) {
  var attrs = "uid,mail,displayName,cn,employeeType,departmentNumber,eduPersonPrimaryAffiliation,supannEntiteAffectation-ou,supannRoleGenerique,supannEtablissement";
    var affiliation2order = { staff: 1, teacher: 2, researcher: 3, emeritus: 4, student: 5, affiliate: 6, alum: 7, member: 8, "registered-reader": 9, "library-walk-in": 10 };
    var affiliation2text = { teacher: "Enseignants", student: "Etudiants", staff: "Biatss", researcher: "Chercheurs", emeritus: "Professeurs émérites", affiliate: "Invité", alum: "Anciens étudiants", retired: "Retraités", "registered-reader": "Lecteur externe", "library-walk-in": "Visiteur bibliothèque" };

  var category2order = { groups_role: 6, structures: 5, groups_structures: 5, affiliation: 5, diploma: 1, elp: 2, gpelp: 3, gpetp: 4 };

  var category2text = {
      groups_role: 'Fonctions',
      structures: 'Directions / Composantes / Laboratoires',
      groups_structures: 'Directions / Composantes / Laboratoires',
      location: 'Sites',
      affiliation: 'Directions / Composantes / Laboratoires',
      diploma: 'Diplômes / Étapes',
      elp: 'Groupes Matières',
      gpelp: 'Groupes TD'
  };
  var subAndSuper_category2text = {
      structures: 'Groupes parents',
      affiliation: 'Groupes parents',
      diploma: 'Étapes associées',
      elp: 'Matières associées',
      gpelp: 'Groupes TD associés'
  };

  var symbol_navigate = "\u21B8";

  var highlight = function (text) {
      return "<span class='match'>" + text + "</span>";
  };

  var getDetails = function (item) {
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
//      if (item.supannEtablissement)
//	  details.push(item.supannEtablissement.join(" - "));

      if (details.length) 
	  return "<div class='details'>" + details.join("<br>") + "</div>"
      else
	  return "";
  };

  var highlightMatched = function (text, searchedTokenL) {
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

  var getNiceDisplayName = function (item) {
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

  var renderOneWarning = function(container, topOrBottom, msg) {
      let elt = $("<div></div>").addClass("warning").append(msg)
      if (topOrBottom === 'top') elt.prependTo(container); else elt.appendTo(container)
  };

  var defaultWarningMsgs = {
    listeRouge_plural: "NB : des r&eacute;sultats ont &eacute;t&eacute; cach&eacute;s<br>&agrave; la demande des personnes.",
    listeRouge_one:    "NB : un r&eacute;sultat a &eacute;t&eacute; cach&eacute;<br>&agrave; la demande de la personne.",
}

  var mayAddWarning = function(container, topOrBottom, item, warningMsgs) {
      if (item.nbListeRouge)
	    renderOneWarning(container, topOrBottom, 
	      item.nbListeRouge > 1 ? warningMsgs.listeRouge_plural : warningMsgs.listeRouge_one
	  );

      if (item.partialResults)
	    renderOneWarning(container, topOrBottom, "Votre recherche est limit&eacute;e &agrave; " + item.partialResults + " r&eacute;sultats.<br>Pour les autres r&eacute;sultats, veuillez affiner la recherche.");
      if (item.partialResultsNoFullSearch)
	    renderOneWarning(container, topOrBottom, "Votre recherche est limit&eacute;e.<br>Pour les autres r&eacute;sultats, veuillez affiner la recherche.");
  };
  var myRenderItemRaw = function(item, moreClass, renderItemContent) {
	if (item.wsError) 
        return $("<div></div>").addClass("warning").append("Erreur web service")[0]

	var content = renderItemContent(item);
      return $("<div></div>").addClass(item.odd_even ? "odd" : "even").addClass(moreClass).addClass("ui-menu-item")
	    .data("item.autocomplete", item)
	    .append("<a>" + content + "</a>")[0]
  };
  var myRenderUserItem = function (item) {
      return myRenderItemRaw(item, 'userItem', function (item) {
	  return getNiceDisplayName(item) + getDetails(item);
      });
  };

  var countOccurences = function (list) {
	var r = {};
	$.each(list, function (i, e) {
	    r[e] = (r[e] || 0) + 1;
	});
	return r;
  };

  var sortByAffiliation = function (items) {
      return items.sort(function(a,b) { 
	  return (affiliation2order[a.eduPersonPrimaryAffiliation] || 99) - (affiliation2order[b.eduPersonPrimaryAffiliation] || 99);
      });
  }

  var groupByAttr = function(l, attrName) {
      var r = [];
      var subl, prevAttrVal;
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

  var transformItems = function (items, idAttr, displayAttr, searchedToken) {
    var searchedTokenL = searchedToken.toLowerCase();
    var odd_even;
    $.each(items, function ( i, item ) {
	    item.label = item[displayAttr];
	    item.value = item[idAttr] || 'unknown';
	    item.searchedTokenL = searchedTokenL;
	    item.odd_even = odd_even = !odd_even;
    });
  };

  var transformUserItems = function (items, wantedAttr, searchedToken) {
      items = sortByAffiliation(items);

      var items_by_affiliation = groupByAttr(items, 'eduPersonPrimaryAffiliation');

      transformItems(items, wantedAttr, 'displayName', searchedToken);
      var r = [];
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
 
  function onSelect(input, settings) {
    return function(item) {
        if (settings.select) {
            const fake_event = {}
            settings.select.bind(input)(fake_event, { item })
        } else {
            input.value = item.value;
        }
    }
  }

  $.fn.autocompleteUser = function (searchUserURL, options) {
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

      var source = function( request, response ) {
	  wsParams.token = request.term = request.term.trim();
	    $.ajax({
		url: searchUserURL,
		dataType: "jsonp",
		crossDomain: true, // needed if searchUserURL is CAS-ified or on a different host than application using autocompleteUser
		data: wsParams,
		error: function () {
		    // we should display on error. but we do not have a nice error to display
		    // the least we can do is to show the user the request is finished!
		    input.kraaden_autocomplete_installed.warning = {}
		    response([ { wsError: true } ]);
		},
		success: function (dataAll) {
                    if (options.modifyResults) {
                        dataAll = options.modifyResults(dataAll, wsParams.token);
                    }
		    data = $.grep(dataAll, function (item, i) { 
			return item.displayName !== "supannListeRouge"; 
		    });
		    nbListeRouge = dataAll.length - data.length;

		    data = transformUserItems(data, settings.wantedAttr, request.term);

		    let warning = {}
		    input.kraaden_autocomplete_installed.warning = warning
		    if (data.length >= settings.maxRows) {
			warning.partialResults = settings.maxRows;;
		    } else if (request.term.length < settings.minLengthFullSearch) {
			warning.partialResultsNoFullSearch = 1;
		    }
		    warning.nbListeRouge = nbListeRouge;

		    response(data);
		}
	    });
      };

      // disable browser proposing previous values
      input.autocomplete = "off"     

      input.kraaden_autocomplete_installed = window.kraaden_autocomplete({ 
        showOnFocus: true,
        minLength: settings.minLength,
        debounceWaitMs: 200,
        preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
        input: input, 
        fetch: function(text, update) {
           source({ term: text }, update)
        },
        onSelect: onSelect(input, settings),
        emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
        render: myRenderUserItem,
        customize: function(input, _inputRect, container) {
            mayAddWarning(container, 'top', input.kraaden_autocomplete_installed.warning, warningMsgs);
        },
      });

      return { wsParams: wsParams };
  };


  var transformGroupItems = function (items, wantedAttr, searchedToken) {
      transformItems(items, wantedAttr, 'name', searchedToken);
      $.each(items, function ( i, item ) {
      item.group = category2text[item.category || ""] || 'Autres types de groupes';
	});
    return sortByGroupCategory(items);
  };

  var transformRoleGeneriqueItems = function (items, category, group, searchedToken) {
      $.each(items, function ( i, item ) {
        item.category = category;
        item.group = group;
      });
      items.sort(function (a, b) { return a.name.localeCompare(b.name) });
      transformItems(items, 'key', 'name', searchedToken);
      return items;
  }

  function object_values(o) {
      return $.map(o, function (e) { return e; })
  }

  // ["aa", "aaa", "ab"] => "a"
  function find_common_prefix(list){
      var A = list.slice(0).sort(), word1 = A[0], word2 = A[A.length-1];
      var len = word1.length, i= 0;
      while(i < len && word1.charAt(i)=== word2.charAt(i)) i++;
      return word1.substring(0, i);
  }

  // ["aa", "aaa", "ab"] => ["a", "aa", "b"]
  function remove_common_prefix(list) {
      var offset = find_common_prefix(list).length;
      return $.map(list, function(e) {
	  return e.substring(offset);
      });
  }

  var simplifySubGroups = function (subGroups) {
      if (subGroups.length <= 1) return;
      var names = $.map(subGroups, function (e) { return e.name });
      var offset = find_common_prefix(names).length;
      $.each(subGroups, function(i, e) {
	  e.name = e.name.substring(offset);
      });
  };
 
  var flattenSuperGroups = function (superGroups, groupId) {
      // remove current group
      delete superGroups[groupId];
      return sortByGroupCategory(object_values(superGroups));
  };

  var transformSubAndSuperGroups = function (items, wantedAttr) {
      var categoryText;
      var odd_even;
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

  var onNavigate = function (input) {
	return function (item) {
        input.kraaden_autocomplete_installed.navigate = item
        input.kraaden_autocomplete_installed.warning = {};
        input.kraaden_autocomplete_installed.fetch()
    }
  }
  function fetch_subAndSuperGroups(item, settings, response) {
	    var current = $.extend({}, item);
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
          input.kraaden_autocomplete_installed.warning = {}
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

var myRenderGroupItem = function (navigate) {
   return function (item) {

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

  function sortByGroupCategory (items) {
      return items.sort(function (a, b) {
	  var cmp = (category2order[a.category] || 99) - (category2order[b.category] || 99);
          return cmp ? cmp : a.name.localeCompare(b.name);
      });
  }

  $.fn.autocompleteGroup = function (searchGroupURL, options) {
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

    var input = this[0];

      var source = function( request, response ) {
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

          let warning = {}
          input.kraaden_autocomplete_installed.warning = warning
		    if (data.length >= settings.maxRows) {
			warning.partialResults = settings.maxRows;;
		    }
		    response(data);
		}
	    });
      };

    var navigate = settings.subAndSuperGroupsURL && onNavigate(input);

    // disable browser proposing previous values
    input.autocomplete = "off"     

    input.kraaden_autocomplete_installed = window.kraaden_autocomplete({ 
        showOnFocus: true,
        minLength: settings.minLength,
        debounceWaitMs: 200,
        preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
        input: input, 
        fetch: function(text, update) {
           source({ term: text }, update)
        },
        onSelect: onSelect(input, settings),
        emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
        render: myRenderGroupItem(navigate),
        customize: function(input, _inputRect, container) {
            mayAddWarning(container, 'top', input.kraaden_autocomplete_installed.warning, warningMsgs);
        },
      });
  };

  function myRenderUserOrGroupItem(item) {
      if (item && item.category === 'users')
          return myRenderUserItem(item);
      else
          return myRenderGroupItem(undefined)(item);
  }

  $.fn.autocompleteUserAndGroup = function (searchUserAndGroupURL, options) {
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

      var source = function( request, response ) {
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
                    var users = $.grep(data.users, function (item, i) {
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
            
            let warning = {}
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
	    });
      };

      // disable browser proposing previous values
      input.autocomplete = "off"     

      input.kraaden_autocomplete_installed = window.kraaden_autocomplete({ 
        showOnFocus: true,
        minLength: settings.minLength,
        disableAutoSelect: settings.disableAutoSelect,
        debounceWaitMs: 200,
        preventSubmit: 2, // settings.disableEnterKey ? 1 : 0
        input: input, 
        fetch: function(text, update) {
           source({ term: text }, update)
        },
        onSelect: onSelect(input, settings),
        emptyMsg: 'aucun résultat', // NB: emptyMsg is needed for "customize" to be called
        render: myRenderUserOrGroupItem,
        customize: function(input, _inputRect, container) {
            mayAddWarning(container, settings.warningPosition, input.kraaden_autocomplete_installed.warning, warningMsgs);
        },
      });
  };
    
  $.fn.autocompleteUser_remove = function () {
    var input = this[0];
    if (input.kraaden_autocomplete_installed)  {
        input.kraaden_autocomplete_installed.destroy()
        delete input.kraaden_autocomplete_installed
    }
  }

  $.fn.handlePlaceholderOnIE = function () {
    // doing nothing (IE is dead!)
  };

})(jQuery);
