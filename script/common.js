/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.1
 */

/**
 * Extended String Object
 */
String.prototype.lcfirst = function() {
    return this.charAt(0).toLowerCase() + this.slice(1);
}
String.prototype.ucfirst = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}
String.prototype.translate = function() {
    let str = '';
    for (let i = 0; i < this.length; i++) {
        str += this[i];
    }

    if (typeof DICTIONARY === 'object' && DICTIONARY[str]) {
        return DICTIONARY[str];
    }

    return str;
}

/**
 * Extended Node Object
 */
Node.prototype.childOf = function(element) {
    var i = -1;
    var parent = this.parentNode;
    while (parent) {
        ++i;
        if (parent == element) {
            return i;
        }
        parent = parent.parentNode;
    }
    return -1;
};
Node.prototype.findParent = function(key) {
    let findBy = 'nodeName';
    switch (key.substr(0,1)) {
        case '.' :
            findBy = 'className';
            break;
        case '#' :
            findBy = 'id';
            break;
        case '@' :
            findBy = 'attribute';
            break;
    }
    key = (findBy !== 'nodeName') ? key.substr(1) : key.toUpperCase();
    let parent = this.parentNode;
    while (parent) {
        if (findBy === 'attribute' && parent.hasAttribute(key)) {
            return parent;
        }
        else if (findBy === 'className' && parent.classList.contains(key)) {
            return parent;
        }
        else if (parent[findBy] === key) {
            return parent;
        }
        parent = parent.parentNode;
        if (!parent.classList) {
            break;
        }
    }
};

/**
 * Extended Date Object
 */
Date.prototype.format = function(format) {
    let date = format;

    date = date.replace(/Y/, this.getFullYear());
    date = date.replace(/y/, this.getYear());
    date = date.replace(/n/, this.getMonth() + 1);
    date = date.replace(/j/, this.getDate());
    date = date.replace(/G/, this.getHours());
    date = date.replace(/m/, ('00' + (this.getMonth() + 1)).slice(-2));
    date = date.replace(/d/, ('00' + this.getDate()).slice(-2));
    date = date.replace(/H/, ('00' + this.getHours()).slice(-2));
    date = date.replace(/i/, ('00' + this.getMinutes()).slice(-2));
    date = date.replace(/s/, ('00' + this.getSeconds()).slice(-2));

    return date;
};

/**
 * Extended HTMLCollection Object
 */
HTMLCollection.prototype.forEach = Array.prototype.forEach;

/**
 * Extended NodeList Object
 */
if (window.NodeList && !NodeList.prototype.forEach) {
    NodeList.prototype.forEach = Array.prototype.forEach;
}

function setcookie(name, value, maxage, path, samesite, domain) {
    let cookieStr = name + '=' + encodeURIComponent(value);
    maxage = (typeof value === 'undefined' || value === null) ? 0 : parseInt(maxage);
    if (!isNaN(maxage)) cookieStr += '; max-age=' + maxage;
    if (path) cookieStr += '; Path=' + path;
    if (domain) cookieStr += '; Domain=.' + domain.replace(/^\.+/, '');
    if (location.protocol === 'https:') cookieStr += '; secure';
    cookieStr += '; SameSite=' + (samesite || document.head.dataset.coks || 'Lax');
    document.cookie = cookieStr;

    return getcookie(name);
}
function getcookie(name) {
    const cookieStr = document.cookie + ';';
    let data = '';
    let fStart = cookieStr.indexOf(name)
    let fEnd;
    name += '=';
    if (fStart !== -1) {
        fEnd = cookieStr.indexOf(';', fStart);
        data = decodeURIComponent(cookieStr.substring(fStart + name.length, fEnd));
    }

    return data;
}

/**
 * A common set of functions
 *
 * version: 1.0.0
 */
var TM_Common = function() {
    this.debug = 1;
    this.onLoad(this, 'init');
};

TM_Common.__FILE__ = (document.currentScript) ? document.currentScript.src : (function(){
    var script = document.getElementsByTagName('script');
    return script[script.length-1].src;
})();

TM_Common.__DIR__ = (function() {
    var a = document.createElement('a');
    a.href = TM_Common.__FILE__;
    var pathname = a.pathname;

    //
    if (!pathname.match(/^\/.*$/)) {
        pathname = '/' + pathname;
    }

    return pathname.replace(/\/?[^\/]+$/, '');
})();

TM_Common.prototype.init = function(evn) {
    if (document.body.dataset.loadmessage) {
        setTimeout(this.showMessage, 300);
    }
};

TM_Common.prototype.showMessage = function() {
    if (document.body.dataset.loadmessage) {
        alert(decodeURIComponent(document.body.dataset.loadmessage));
    }
};

TM_Common.prototype.setCookie  = function() {
    var str, name, value, expires, path, domain, secure, expires = '';
    var arg = TM.setCookie.arguments;

    if (arg.length === 0) {
        return false;
    }

    name   = arg[0];
    value  = (typeof(arg[1]) !== 'undefined') ? arg[1] : '';
    expire = (typeof(arg[2]) !== 'undefined') ? arg[2] :  0;
    path   = (typeof(arg[3]) !== 'undefined') ? arg[3] : '';
    domain = (typeof(arg[4]) !== 'undefined') ? arg[4] : '';
    secure = (typeof(arg[5]) !== 'undefined') ? arg[5] :  0;
    path   = (path   !== '') ? 'Path=' + path + '; ' : '';
    domain = (domain !== '') ? 'Domain=' + domain + '; ' : '';
    secure = (secure  >  0) ? 'secure' : '';

    if (value === '') {
        expires = 'expires=Thu, 1-jan-1970 00:00:00 GMT' + '; ';
    } else if(expire !== 0){
        expires = 'expires=' + expire.toUTCString() + '; ';
    }

    str = name + '=' + encodeURIComponent(value) + "; "
        + expires + path + domain + secure;
    document.cookie = str;
    return TM.getCookie(name) !== value;
};

TM_Common.prototype.getCookie  = function(name) {
    var str = document.cookie + ';';
    var data = '';
    var fStart = str.indexOf(name), fEnd;
    name += '=';
    if (fStart !== -1) {
        fEnd = str.indexOf(';', fStart);
        data = decodeURIComponent(str.substring(fStart + name.length, fEnd));
    }
    return data;
};

TM_Common.prototype.getParentNode = function(el, key) {
    var attr = 'nodeName';
    switch(key.substr(0,1)) {
        case '.' :
            attr = 'className';
            break;
        case '#' :
            attr = 'id';
            break;
    }
    key = (attr !== 'nodeName') ? key.substr(1) : key.toUpperCase();
    var pn = el.parentNode;
    while (pn) {
        if(attr !== 'className' && pn[attr] === key) return pn;
        else if (attr === 'className' && pn.classList && pn.classList.contains(key)) return pn;
        pn = pn.parentNode;
    }
};

TM_Common.prototype.isTextbox = function(el) {
    return (el.type !== 'reset' && el.type !== 'checkbox' &&
            el.type !== 'radio' && el.type !== 'hidden' &&
            el.type !== 'image' && el.type !== 'button' &&
            el.type !== 'file') || el.nodeName === 'TEXTAREA';
};

TM_Common.prototype.basename = function(str) {
    var m = str.match(/([^\/\\]+)$/);
    return (m) ? m[1] : str;
};

TM_Common.prototype.parseQuery = function(str) {
    str = str.replace(/^\?/, '').replace(/&amp;/, '&');
    var i, query = {};
    var pairs = str.split('&');
    for (i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        query[pair[0]] = decodeURIComponent(pair[1]);
    }
    return query;
};

TM_Common.prototype.apply = function(command, args) {
    var names = command.split('.');
    var i, max;
    var obj = window;
    for (i = 0, max = names.length; i < max; i++) {
        var key = names[i];
        if (typeof(obj[key]) === 'undefined') {
            break;
        }
        else if (typeof(obj[key]) === 'function') {
            return obj[key].apply(obj, args);
        }
        obj = obj[key];
    }
};

TM_Common.prototype.hash = function(str) {
    return str.substr(str.indexOf('#'));
};

TM_Common.prototype.errorHandler = function(evn) {
    if (!evn.message) {
        return;
    }
    evn.preventDefault();
    var filename = evn.filename.replace(/^.*?\/\/[^\/]+/, '');
    var msg = evn.message + ' at ' + filename + ':' + evn.lineno + ',' + evn.colno;
    console.error(msg);
};

TM_Common.prototype.onLoad = function(scope, func) {
    addEventListener('error', this.errorHandler, false);
    addEventListener('load', function(evn){ scope[func](evn); }, false);
};

TM_Common.prototype.loadModule = function(scripts) {
    var me = document.getElementsByTagName('script')[0];
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var element = document.createElement('script');
        element.src = TM_Common.__DIR__ + '/TM/' + script.src + '.js';
        if (script.async !== '') {
            element.async = true;
        }
        if (script.defer !== '') {
            element.defer = true;
        }
        me.parentNode.insertBefore(element, me);
    }
};

TM_Common.prototype.dynamicLoadModule = function(scripts) {
    for (var i = 0; i < scripts.length; i++) {
        var script = scripts[i];
        var element = document.createElement('script');
        element.src = TM_Common.__DIR__ + '/TM/' + script.src + '.js';
        document.head.appendChild(element);
    }
};

TM_Common.prototype.loadStyle = function(styles) {
    var scriptElement = document.querySelector('script');
    for (var i = 0; i < styles.length; i++) {
        var style = styles[i];
        var element = document.createElement('link');
        element.rel = style.rel;
        element.href = TM_Common.__DIR__ + '/TM/' + style.href + '.css';
        scriptElement.parentNode.insertBefore(element, scriptElement);
    }
};

TM_Common.prototype.initModule = function() {
    var args = (arguments.length === 1 ? [arguments[0]] : Array.apply(null, arguments));
    var func = args.shift();
    var module = args.shift();
    var state = args.shift();
    var eventType = (state === 'interactive') ? 'DOMContentLoaded' : 'load';

    if (   document.readyState === state
        || (state === 'interactive' && document.readyState === 'complete')
    ) {
        func.apply(module, args);
    }
    else {
        window.addEventListener(
            eventType,
            function() {
                func.apply(module, args);
            },
            false
        );
    }
};


class Dialog {
    constructor(title, type, callback) {
        this.callback = callback;
        this.type = type;
        this.createDialog(title, type);
    }

    createDialog(title, type) {
        this.container = document.body.appendChild(document.createElement('div'));
        this.setStyle(this.container, {
            alignItems : 'center',
            backgroundColor : 'rgba(0,0,0,0.5)',
            bottom : '0',
            display : 'flex',
            justifyContent : 'center',
            left : '0',
            position : 'absolute',
            right : '0',
            top : '0',
            zIndex : '2147483647',
        });

        const dialog = this.container.appendChild(document.createElement('div'));
        this.setStyle(dialog, {
            backgroundColor : '#FFF',
            borderRadius : '6px',
            minWidth : '380px',
            minHeight : '60px',
            overflow: 'hidden',
        });

        const message = dialog.appendChild(document.createElement('p'));
        message.innerHTML = title;
        this.setStyle(message, {
            fontSize: '11pt',
            margin: '1.2em 1em',
        });

        if (type === 'prompt' || type === 'secret') {
            const span = message.appendChild(document.createElement('span'));
            this.setStyle(span, {
                display: 'block',
                width: '100%',
            });
            this.input = span.appendChild(document.createElement('input'));
            this.input.name = 'answer';
            this.input.type = (type === 'secret') ? 'password' : 'text';
            this.setStyle(this.input, {
                fontSize: '9pt',
                width: '100%',
            });

            this.input.focus();
        }

        const line = dialog.appendChild(document.createElement('hr'));
        this.setStyle(line, {
            border : '0 none transparent',
            borderTop : '1px solid #ccc',
            height : '0',
            margin : '0',
        });

        const buttons = dialog.appendChild(document.createElement('p'));
        this.setStyle(buttons, {
            margin: '0 0.8em',
            textAlign : 'right',
        });

        if (type === 'confirm' || type === 'prompt' || type === 'secret') {
            const cancelButton = buttons.appendChild(document.createElement('button'));
            cancelButton.value = 'cancel';
            cancelButton.innerHTML = 'Cancel';
            this.setStyle(cancelButton, {
                background: 'transparent',
                borderRadius: '0',
                borderWidth: '0',
                color : 'blue',
                fontSize: '11pt',
                height: 'auto',
                lineHeight: '1',
                margin: '0.8em 0.5em',
                minHeight: 'auto',
                minWidth: 'auto',
                padding: '0',
                textAlign : 'center',
                textDecoration : 'none',
                width: 'auto',
            });

            cancelButton.addEventListener('click', this.close.bind(this));
        }

        const okButton = buttons.appendChild(document.createElement('button'));
        okButton.value = 'ok';
        okButton.innerHTML = 'OK';
        this.setStyle(okButton, {
            background: 'transparent',
            borderRadius: '0',
            borderWidth: '0',
            color : 'blue',
            fontSize: '11pt',
            height: 'auto',
            lineHeight: '1',
            margin: '0.8em 0.5em',
            minHeight: 'auto',
            minWidth: 'auto',
            padding: '0',
            textAlign : 'center',
            textDecoration : 'none',
            width: 'auto',
        });

        okButton.addEventListener('click', this.close.bind(this));
    }

    setStyle(element, styles) {
        for (let key in styles) {
            element.style[key] = styles[key];
        }
    }

    close(event) {
        event.preventDefault();
        const returnValue = event.target.value;
        this.container.parentNode.removeChild(this.container);
        if (typeof this.callback === 'function') {
            const func = function(click, input, type) {
                return (type === 'prompt' || type === 'secret')
                    ? input : click;
            }
            let answer = (returnValue === 'cancel') ? null : func(returnValue, this.input.value, this.type);
            this.callback(answer);
        }
    }
}

switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', operateConfirmationInit)
        window.addEventListener('DOMContentLoaded', setcookieAndReloadInit)
        window.addEventListener('DOMContentLoaded', innerHtmlChangeInit)
        break;
    case 'interactive':
    case 'complete':
        operateConfirmationInit();
        setcookieAndReloadInit();
        innerHtmlChangeInit();
        break;
}

function operateConfirmationInit(event) {
    const elements = document.querySelectorAll('*[data-confirmation]');
    elements.forEach((element) => {
        let action = undefined;
        switch (element.nodeName) {
            case 'A':
            case 'BUTTON':
                action = 'click';
                break;
            case 'FORM':
                action = 'submit';
                break;
            case 'INPUT':
                if (element.type === 'submit' || element.type === 'button' || element.type === 'reset') {
                    action = 'click';
                }
                break;
        }

        if (action) {
            element.addEventListener(action, operateConfirmationExecute);
        }
    });
}

function operateConfirmationExecute(event) {
    const element = event.currentTarget;
    if (!confirm(decodeURIComponent(element.dataset.confirmation))) {
        event.preventDefault();
    }

    const form = element.form;
    if (element.dataset.cancelConfirm === 'yes' && form && form.dataset.confirm) {
        delete form.dataset.confirm;
    }
}


/**
 * Create|Clear progress screen
 */
const progressScreenId = 'progress-screen1';
function setProgressScreen(clear) {
    let screen = document.getElementById(progressScreenId);
    if (screen) {
        screen.parentNode.removeChild(screen);
    }

    if (clear) return;

    screen = document.body.appendChild(document.createElement('div'));
    screen.id = progressScreenId;
    const div = screen.appendChild(document.createElement('div'));
    div.appendChild(document.createElement('progress'));
}


/**
 * Set cookie and page reload
 */
function setcookieAndReloadInit(event) {
    const elements = document.querySelectorAll('select.setcookie-and-reload');
    elements.forEach(element => {
        element.addEventListener('change', setcookieAndReloadExec);
    });
}
function setcookieAndReloadExec(event) {
    const element = event.target;
    setcookie(element.name, element.options[element.selectedIndex].value, 60 * 60 * 24 * 30);
    element.form.dataset.freeUnload = '1';
    location.reload();
}


/**
 * Change innder HTML
 */
function innerHtmlChangeInit(event) {
    const elements = document.querySelectorAll('*[data-inner-html]');
    elements.forEach(element => {
        let action;
        if (element.tagName === 'INPUT' && (element.type === 'checkbox' || element.type === 'radio')) {
            action = 'click';
        } else if (element.tagName === 'SELECT') {
            action = 'change';
        }
        if (action) {
            element.addEventListener(action, innerHtmlChangeExec);
        }
    });
}
function innerHtmlChangeExec(event) {
    const element = event.target;
    const json = JSON.parse(element.dataset.innerHtml);
    for (let i = 0; i < json.length; i++) {
        const elements = document.querySelectorAll('*[data-change-name="' + json[i].name + '"]');
        elements.forEach(item => {
            item.innerHTML = json[i].value;
        });
    }
}
