/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */

/**
 * CustomSelector
 */
function CustomSelector() {
    this.inited = false;
    this.cnSelectBox = 'select-box';
    this.cnSelectText = 'select-text';
    this.cnSelectMenu = 'select-menu';
    this.cnOpen = 'select-open';
    this.cnPulldown = 'pulldown';
    TM.initModule(this.init, this);
}

CustomSelector.prototype.selected = function(element, textOnly) {
    var node = element.parentNode.querySelector('.' + this.cnPulldown);
    var parent = TM.getParentNode(element, '.' + this.cnSelectBox);
    var textbox = parent.querySelector('.' + this.cnSelectText);
    textbox.innerHTML = node.firstChild.nodeValue;
    if (textOnly) {
        return;
    }
    this.close();
};

CustomSelector.prototype.close = function() {
    var instance = TM.customSelector;
    var element = document.body.querySelector('.' + instance.cnOpen);
    if (element) {
        element.classList.remove(instance.cnOpen);
    }
    window.removeEventListener('click', instance.close, true);
    event.stopPropagation();
}

CustomSelector.prototype.toggle = function(element) {
    var menu = element.parentNode.querySelector('.' + this.cnSelectMenu);
    if (menu.classList.contains(this.cnOpen)) {
        menu.classList.remove(this.cnOpen);
        window.removeEventListener('click', this.close, true);
    }
    else {
        menu.classList.add(this.cnOpen);
        window.addEventListener('click', this.close, true);
    }
};

CustomSelector.prototype.listener = function(event) {
    var instance = TM.customSelector;
    var caller = event.target;
    switch (event.type) {
        case 'click':
            event.stopPropagation();
            if (caller.nodeName === 'INPUT') {
                instance.selected(caller);
            }
            else if (caller.classList.contains(instance.cnSelectText)) {
                event.preventDefault();
                instance.toggle(caller);
            }
            break;
    }
};

CustomSelector.prototype.init = function(event) {
    if (this.inited) {
        return;
    }

    var elements = document.body.getElementsByClassName(this.cnSelectText);
    var i, j, maxI, maxJ;
    for (i = 0, maxI = elements.length; i < maxI; i++) {
        elements[i].addEventListener('click', this.listener, false);
        var menu = elements[i].parentNode.querySelector('.' + this.cnSelectMenu);
        var inputs = menu.getElementsByTagName('input');
        for (j = 0, maxJ = inputs.length; j < maxJ; j++) {
            inputs[j].addEventListener('click', this.listener, false);
            if (inputs[j].checked) {
                this.selected(inputs[j], true);
            }
        }
    }

    this.inited = true;
};

TM.customSelector = new CustomSelector();
