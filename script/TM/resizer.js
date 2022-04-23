/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Resizer = function() {
    this.handle = undefined;
    this.resizeBox = undefined;
    this.cnResizeBox = 'resizable';
    this.cnHandle = 'TM-resizehandle';
    this.idPrefix = 'TMResizeBox';
    TM.initModule(this.init, this, 'complete');
};

TM_Resizer.prototype.start = function(event) {
    var instance = TM.resizer;
    event.preventDefault();
    instance.resizeBox = event.currentTarget.parentNode;
    document.addEventListener('mouseup', instance.stop, false);
    document.addEventListener('mousemove', instance.move, false);
};

TM_Resizer.prototype.stop = function(event) {
    var instance = TM.resizer;
    instance.setSize(instance.resizeBox.id, instance.resizeBox.style.width);
    document.removeEventListener('mouseup', instance.stop, false);
    document.removeEventListener('mousemove', instance.move, false);
};

TM_Resizer.prototype.move = function(event) {
    var instance = TM.resizer;
    var x = event.pageX;
    var minWidth = parseInt(instance.resizeBox.dataset.minwidth);
    if (x < minWidth) {
        x = instance.minWidth;
    }
    instance.resizeBox.style.width = x + 'px';
};

TM_Resizer.prototype.setSize = function(id, size) {
    var saved = TM.getCookie('TMResizerSize');
    var exists = false;
    if (!saved) {
        saved = [];
    }
    else {
        saved = JSON.parse(saved);
        for (var i = 0; i < saved.length; i++) {
            if (saved[i].id === id && saved[i].href === location.href) {
                saved[i].size = size;
                exists = true;
                break;
            }
        }
    }
    if (!exists) {
        saved.push(
            { id: id, href: location.href, size: size }
        );
    }
    TM.setCookie('TMResizerSize', JSON.stringify(saved));
};

TM_Resizer.prototype.getSize = function(id) {
    var saved = TM.getCookie('TMResizerSize');
    if (saved) {
        saved = JSON.parse(saved);
        for (var i = 0; i < saved.length; i++) {
            if (saved[i].id === id && saved[i].href === location.href) {
                return saved[i].size;
            }
        }
    }
    return null;
};

TM_Resizer.prototype.init = function() {
    var elements = document.getElementsByClassName(this.cnResizeBox);
    if(elements) {
        var i, element;
        for(i = 0; i < elements.length; i++){
            element = elements[i];
            if (!element.id) {
                element.id = this.idPrefix + i;
            }

            var size = this.getSize(element.id);
            if (size) {
                element.style.width = size;
            }

            var style = document.defaultView.getComputedStyle(element, '');
            var position = style.position.match(/relative|absolute|fixed/i);
            if (!position) {
                element.style.position = 'relative';
            }

            var handle = element.appendChild(document.createElement('div'));
            handle.className = this.cnHandle;
            handle.addEventListener('mousedown', this.start, false);
        }
    }
};

TM.resizer = new TM_Resizer();
