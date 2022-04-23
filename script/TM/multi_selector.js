/**
 * Javascript Class Template
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function MultiSelector() {
    this.startElement;
    this.cnAssumed = '';
    this.assumed = { active:'assumed-on', inactive:'assumed-off' };
    //this.onLoad(this, 'init');
    TM.initModule(this.init, this, 'interactive');
}

MultiSelector.prototype.startSelect = function(element) {
    this.startElement = element;

    var input = element.querySelector('input');
    this.cnAssumed = (input.checked) ? this.assumed.inactive : this.assumed.active;
    element.classList.add(this.cnAssumed);
};

MultiSelector.prototype.endSelect = function(element) {
    if (!this.startElement) {
        return;
    }

    var elements = [].slice.call(this.startElement.parentNode.childNodes);
    var i, max;
    for (i = 0, max = elements.length; i < max; i++) {
        var element = elements[i];
        if (element.nodeType === 1) {
            var input = element.querySelector('input');
            if (element.classList.contains(this.assumed.active)) {
                input.checked = true;
                element.classList.remove(this.assumed.active);
            }
            else if (element.classList.contains(this.assumed.inactive)) {
                input.checked = false;
                element.classList.remove(this.assumed.inactive);
            }
        }
    }

    this.clear();
};

MultiSelector.prototype.onDragOver = function(element) {
    if (!this.startElement) {
        return;
    }

    var origin = this.startElement.parentNode;
    var prn = element;

    var match = false;
    while (prn) {
        if (origin === prn) {
            match = true;
            break;
        }
        prn = prn.parentNode;
    }
    if (!match) {
        return;
    }

    var endElement = element
    var elements = [].slice.call(endElement.parentNode.childNodes);
    var start = elements.indexOf(this.startElement);
    var end = elements.indexOf(endElement);

    var s = Math.min(start, end);
    var e = Math.max(start, end);
    var i, max;
    for (i = 0, max = elements.length; i < max; i++) {
        var element = elements[i];
        if (element.nodeType !== 1) {
            continue;
        }
        if (i >= s && i <= e) {
            element.classList.add(this.cnAssumed);
        }
        else {
            element.classList.remove(this.cnAssumed);
        }
    }
};

MultiSelector.prototype.clear = function() {
    var key;
    for (key in this.assumed) {
        var elements = document.getElementsByClassName(this.assumed[key]);
        for (var i = elements.length - 1; i >= 0; i--) {
            elements[i].classList.remove(this.assumed[key]);
        }
    }

    this.startElement = undefined;
}

MultiSelector.prototype.listener = function(event) {
    switch (event.type) {
        case 'click':
            event.preventDefault();
            break;
        case 'mouseover':
            TM.multiSelector.onDragOver(event.currentTarget);
            break;
        case 'mousedown':
            TM.multiSelector.startSelect(event.currentTarget);
            break;
        case 'mouseup':
            TM.multiSelector.endSelect(event.target);
            break;
    }
};

MultiSelector.prototype.init = function(event) {
    var i, max;
    var selectors = document.getElementsByClassName(TM.form.cnMultiSelector);
    if (!selectors) {
        return;
    }
    for (i = 0, max = selectors.length; i < max; i++) {
        var elements = selectors[i].childNodes;
        var j, maxJ;
        for (j = 0, maxJ = elements.length; j < maxJ; j++) {
            var element = elements[j];
            if (element.nodeType !== 1) {
                continue;
            }
            element.addEventListener('click', this.listener, false);
            element.addEventListener('mouseover', this.listener, false);
            element.addEventListener('mousedown', this.listener, false);
            //element.addEventListener('mouseup', this.listener, false);
        }
    }

    if (selectors.length > 0) {
        window.addEventListener('mouseup', this.listener, false);
    }
};

//MultiSelector.prototype.onLoad = function(scope, func)
//{
//    window.addEventListener(
//        'DOMContentLoaded',
//        function(evn) {
//            scope[func](evn);
//        },
//        false
//    );
//};

TM.multiSelector = new MultiSelector();
