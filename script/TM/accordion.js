/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Accordion = function() {
    this.cnSwitcher = 'accordion-switcher';
    this.cnOpened = 'accordion-opened';
    this.cnClosed = 'accordion-closed';
    TM.initModule(this.init, this, 'complete');
};

TM_Accordion.prototype.openingAndClosing = function(element) {
    var id = element.hash || element.dataset.href;
    var accordion = document.getElementById(id.substr(1));
    if (accordion.classList.contains(this.cnClosed)) {
        accordion.classList.remove(this.cnClosed);
        accordion.style.height = accordion.dataset.height + 'px';
    } else {
        accordion.classList.add(this.cnClosed);
        accordion.style.height = null;
    }

    // Apply callback function
    if (element.dataset.callback) {
        var func = new Function('arg1', 'arg2', 'return ' + element.dataset.callback + '(arg1, arg2)');
        func(accordion, element);
    }
};

TM_Accordion.prototype.listener = function(event) {
    var instance = TM.accordion;
    switch (event.type) {
        case 'click':
            event.preventDefault();
            instance.openingAndClosing(event.currentTarget);
            break;
    }
};

TM_Accordion.prototype.init = function() {
    var anchor = document.querySelectorAll('.' + this.cnSwitcher);
    for (var i = 0; i < anchor.length; i++) {
        var element = anchor[i];
        if (element.dataset.childOf) {
            if (!element.findParent(element.dataset.childOf)) {
                continue;
            }
        }
        var id = element.hash || element.dataset.href;
        var accordion = document.getElementById(id.substr(1));
        var origin = accordion.style.height;
        accordion.style.height = 'auto';
        accordion.dataset.height = accordion.clientHeight;
        accordion.style.height = origin;
        accordion.classList.add('accordion-closed');
        element.addEventListener('click', this.listener, false);
        element.dataset.cancelWindowEvent = 1;
        element.dataset.more = element.innerHTML;
    }
};

//TM.accordion = new TM_Accordion();
