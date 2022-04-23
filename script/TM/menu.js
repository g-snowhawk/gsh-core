/**
 * function on global navigation
 *
 * version: 1.0.0
 */
function TM_Menu(id) {
    this.classname= 'navroot';
    this.initializedClass = 'inited';
    this.openClass = 'open';
    this.closeClass = 'close';
    this.deepLevelClass = 'deep';
    this.levelTag = 'UL';
    this.container = undefined;
    this.opener = [];
    this.isopen = [];
    TM.initModule(this.init, this, 'complete', id);
};

TM_Menu.prototype.checkOpen = function(element) {
    var instance = TM.menu;
    return element.classList.contains(instance.openClass);
};

TM_Menu.prototype.toggle = function(el, force) {
    var instance = TM.menu || this;

    var prv = el.previousSibling;
    var a;
    while (prv) {
        if (prv.dataset && prv.dataset.switcher === 'on') {
            a = prv;
            break;
        }
        prv = prv.previousSibling;
    }

    if (force) {
        if (force === 'open') {
            el.classList.remove(instance.closeClass);
            el.classList.add(instance.openClass);
        } else if (force === 'close') {
            el.classList.remove(instance.openClass);
            el.classList.add(instance.closeClass);
        }
    } else {
        if (el.classList.contains(instance.openClass)) {
            el.classList.remove(instance.openClass);
            el.classList.add(instance.closeClass);
            force = 'close';
        } else {
            el.classList.remove(instance.closeClass);
            el.classList.add(instance.openClass);
            force = 'open';
        }
    }

    for (var key in el.dataset) {
        if (key.match(/^pulldown/)) {
            var k = TM.lcfirst(key.replace(/^pulldown/, ''));
            el.style[k] = (force === 'open') ? el.dataset[key] : '';
        }
    }
    if (a) {
        var src = (force === 'open') ? 'close' : 'open';
        el.classList.remove(src);
        el.classList.add(force);
    }
    return force;
};

TM_Menu.prototype.openPullDown = function(ev, mo) {
    var i, j, u;
    var instance = TM.menu;
    var el = ev.target;
    var parent = el.parentNode;
    var cn = parent.childNodes;
    var level = instance.getLevel(el);

    var root = TM.getParentNode(el, '.' + instance.classname);
    if (root.dataset.pulldownLevel && root.dataset.pulldownLevel < level) return true;

    for(i = 0; i < cn.length; i++){
        u = cn[i];
        if (u.nodeName === instance.levelTag) {

            var style = u.currentStyle || document.defaultView.getComputedStyle(u, '');
            if (style.position === 'static') return true;

            if (!instance.checkOpen(u)){
                if (ev.type === 'mouseover'){
                    if (instance.isopen[level] != true) return;
                    for(j in instance.isopen){
                        if (j > level) instance.isopen[j] = false;
                    }
                }
                if (mo != true) instance.closePullDown(ev);
                instance.toggle(u);
                instance.isopen[level] = true;
                instance.opener.push(u);

                window.addEventListener('click', instance.closePullDown, false);
                window.addEventListener('touchend', instance.closePullDown, false);

                if (ev.stopPropagation) ev.stopPropagation();
                else if (ev.cancelBubble) ev.cancelBubble();
            } else {
                if (mo != true) instance.closePullDown(ev);
            }
        }
    }
    ev.preventDefault();
    return false;
};

TM_Menu.prototype.closePullDown = function(ev) {
    var i, j, u;
    var instance = TM.menu;
    var el = ev.target;
    var level = instance.getLevel(el);
    var tmp = [];
    if (ev.type === 'mouseover' && instance.touched) {
        return;
    }
    while (u = instance.opener.shift()){
        if (ev.type === 'mouseover' && u.parentNode === el.parentNode){
            tmp.push(u);
            continue;
        }
        //if (instance.isMyParent(el, u) === true){
        if (el.childOf(u) !== -1) {
            if (ev.type !== 'click' || el.dataset.forceClose !== "1") {
                tmp.push(u);
                continue;
            }
        }
        instance.toggle(u, 'close');
        if (ev.type !== 'mouseover') {
            instance.isopen[level] = false;
        }
    }
    var opened = (ev.type === 'mouseover') ? instance.isopen[level] : false;
    instance.opener = tmp;
    if (instance.opener.length === 0 && ev.type !== 'mouseover'){
        window.removeEventListener('click', instance.closePullDown, false);
        window.removeEventListener('touchend', instance.closePullDown, false);
        instance.isopen = [];
    }
    if (opened) {
        instance.openPullDown(ev, true);
    }
};

TM_Menu.prototype.checkExternalClick = function(el) {
    var instance = TM.menu;
    var parent = el.parentNode;
    while (parent && parent.className === instance.classname) {
        if (chk === parent) {
            return false;
        }
        parent = parent.parentNode;
    }
    return true;
};

TM_Menu.prototype.isMyParent = function(element, chk) {
    var instance = TM.menu;
    var parent = el.parentNode;
    while(parent && parent.className !== instance.classname){
        if (chk === parent) {
            return true;
        }
        parent = parent.parentNode;
    }
};

TM_Menu.prototype.getLevel = function(element) {
    var level = 0;
    var instance = TM.menu;
    var parent = element;
    while(parent && parent.className !== instance.classname){
        parent = parent.parentNode;
        if (parent && parent.nodeName === instance.levelTag) {
            ++level;
        }
    }
    return level;
};

TM_Menu.prototype.getElementsByClassName = function(className) {
    var elements = document.getElementsByTagName('ul');
    var returnValue = [];
    for(var i = 0; i < elements.length; i++){
        if (elements[i].className !== className) {
            continue;
        }
        returnValue.push(elements[i]);
    }
    return returnValue;
};

TM_Menu.prototype.confirmSignOut = function(event) {
    var element = event.currentTarget;
    var message = (element.dataset && element.dataset.confirm)
        ? element.dataset.confirm
        : 'サインアウトします。よろしいですか？';
    if (!confirm(message)) {
        event.preventDefault();
    }
};

TM_Menu.prototype.touchStartAction = function(event) {
    TM.menu.touched = true;
};

TM_Menu.prototype.init = function() {

    // Hack for shit browsers
    if (navigator.userAgent.match(/(Trident|MSIE)/)) {
        this.openClass += '-msie';
        this.closeClass += '-msie';
    }

    var i, j, n, o, element;
    var root = document.getElementsByClassName(this.classname);

    for(o = 0; o < root.length; o++){

        var stylekeeper = root[o].dataset.stylekeeper;
        if (stylekeeper) {
            stylekeeper = stylekeeper.split(',');
        }

        // Style hack for shit browsers
        if (navigator.userAgent.match(/(Trident|MSIE)/)) {
            root[o].style.fontSize = 'inherit';
        }

        var leaf = root[o].getElementsByTagName('li');
        for (i = 0; i < leaf.length; i++){
            var submenu = 0;
            var li = leaf[i];
            var branch = li.getElementsByTagName('ul');
            if (branch) {
                for (j = 0; j < branch.length; j++) {

                    // Style hack for shit browsers
                    if (navigator.userAgent.match(/(Trident|MSIE)/)) {
                        branch[j].style.fontSize = 'inherit';
                    }

                    if (stylekeeper) {
                        var style = branch[j].currentStyle || document.defaultView.getComputedStyle(branch[j], '');
                        for (n = 0; n < stylekeeper.length; n++) {
                            if (style[stylekeeper[n]]) {
                                var key = 'pulldown' + TM.ucfirst(stylekeeper[n]);
                                branch[j].dataset[key] = style[stylekeeper[n]];
                            }
                        }
                    }

                    this.toggle(branch[j], 'close');
                    ++submenu;
                }
            }

            var node = li.childNodes;
            for (j = 0; j < node.length; j++) {
                element = node[j];
                if (element.nodeName === 'A') {
                    if (submenu > 0) {
                        element.addEventListener('click', this.openPullDown, false);
                        element.dataset.switcher = 'on';
                    }
                    else {
                        element.dataset.forceClose = '1';
                        element.addEventListener('click', this.closePullDown, false);
                    }
                    element.addEventListener('touchstart', this.touchStartAction, false);
                    element.addEventListener('mouseover', this.closePullDown, false);
                }
            }
        }
    }

    var signout = document.getElementsByClassName('signout');
    for (i = 0; i < signout.length; i++) {
        signout[i].addEventListener('click', this.confirmSignOut, false);
    }
};

//TM.menu = new TM_Menu('global-nav');
