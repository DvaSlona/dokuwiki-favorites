//Cookie functions from http://www.webreference.com/js/column8/functions.html
/*
   name - name of the cookie
   value - value of the cookie
   [expires] - expiration date of the cookie
     (defaults to end of current session)
   [path] - path for which the cookie is valid
     (defaults to path of calling document)
   [domain] - domain for which the cookie is valid
     (defaults to domain of calling document)
   [secure] - Boolean value indicating if the cookie transmission requires a secure transmission

   * an argument defaults when it is assigned null as a placeholder
   * a null placeholder is not required for trailing omitted arguments
*/
function setCookie(name, value, expires, path, domain, secure) {
  var curCookie = name + "=" + escape(value) +
                  ((expires) ? "; expires=" + expires.toGMTString() : "") +
                  ((path) ? "; path=" + path : "") +
                  ((domain) ? "; domain=" + domain : "") +
                  ((secure) ? "; secure" : "");
  document.cookie = curCookie;
}

/*
  name - name of the desired cookie
  return string containing value of specified cookie or null if cookie does not exist
*/
function getCookie(name) {
  var dc = document.cookie;
  var prefix = name + "=";
  var begin = dc.indexOf("; " + prefix);
  if (begin == -1) {
    begin = dc.indexOf(prefix);
    if (begin != 0) return null;
  }
  else begin += 2;
  var end = document.cookie.indexOf(";", begin);
  if (end == -1) end = dc.length;
  return unescape(dc.substring(begin + prefix.length, end));
}

/*
   name - name of the cookie
   [path] - path of the cookie (must be same as path used to create cookie)
   [domain] - domain of the cookie (must be same as domain used to create cookie)
   path and domain default if assigned null or omitted if no explicit argument proceeds
*/
function deleteCookie(name, path, domain) {
  if (getCookie(name)) {
    document.cookie = name + "=" +
                      ((path) ? "; path=" + path : "") +
                      ((domain) ? "; domain=" + domain : "") +
                      "; expires=Thu, 01-Jan-70 00:00:01 GMT";
  }
}

function cache(id) {
  if (document && document.getElementById(id)) 
    document.getElementById(id).style.display="none";
}

function afficheMasque(id) {
  if (document && document.getElementById(id)) 
    if (document.getElementById(id).style.display=="block") 
      document.getElementById(id).style.display="none";
    else
      document.getElementById(id).style.display="block";
}

var timeout;
var anc_title;

function afficherControles(e, delai) {
  if (!e) var e = window.event;
  e.cancelBubble = true;
  if (e.stopPropagation) e.stopPropagation();

  if (!anc_title) anc_title=document.getElementById('enveloppe').title;
  if (delai==0) clearSelection();

  clearTimeout(timeout);
  timeout=setTimeout(function () {
      var controles=document.getElementsByName("ctrl");
      if (controles) 
        for (var i=0; i<controles.length; i++) controles[i].style.display="inline";
      document.getElementById('enveloppe').title="";
    }, delai);
}

function masquerControles(e) {
  if (!e) var e = window.event;
  e.cancelBubble = true;
  if (e.stopPropagation) e.stopPropagation();

  clearTimeout(timeout);
  timeout=setTimeout(function () {
      var controles=document.getElementsByName("ctrl");
      if (controles) 
        for (var i=0; i<controles.length; i++) controles[i].style.display="none";
  
      cache('exclues');
      document.getElementById('enveloppe').title=anc_title;
    }, 500);
}

function clearSelection() {
  var sel;
  if(document.selection && document.selection.empty) document.selection.empty();
  else if(window.getSelection) {
    sel=window.getSelection();
    if(sel && sel.removeAllRanges)
      sel.removeAllRanges();
  }
}

function recharge() {
  window.location.reload();
}

function sauvePref() {
  setCookie('fav_maxRec', document.getElementById('maxRec').value, new Date('July 21, 2099 00:00:00'), '/');
  setCookie('fav_maxFav', document.getElementById('maxFav').value, new Date('July 21, 2099 00:00:00'), '/');
  recharge();
}
