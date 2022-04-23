/**
 * Color picker
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function TM_Colorpicker() {
    this.activeSelector = undefined;
    this.bar = undefined;
    this.base = undefined;
    this.baseWidth = 0;
    this.baseHeight = 0;
    this.colorButton = undefined;
    this.colorTip = undefined;
    this.pointer= undefined;
    this.slider = undefined;
    this.slideOffset = 0;
    this.slideTop = 0;
    this.slideBottom = 0;
    this.H = 0;
    this.S = 255;
    this.V = 255;
    this.idSuffix = '-picker';
    this.selectedColor = '';
}

TM_Colorpicker.prototype.initColor = function(element) {
    var outer = element.parentNode.insertBefore(document.createElement('div'), element);
    outer.id = element.name + this.idSuffix;
    outer.classList.add('input-color');
    outer.addEventListener('click', this.listener);

    element.style.display = 'none';
    this.selectedColor = element.value;
    var bg = (this.selectedColor) ? this.selectedColor : '#000000';

    var inner = outer.appendChild(document.createElement('span'));
    inner.classList.add('selected');
    inner.style.backgroundColor = bg;
    inner.dataset.for = element.name;
};

TM_Colorpicker.prototype.preview = function(event) {
    var instance = TM.colorpicker;

    var x = Math.round(event.clientX - instance.baseOffsetX);
    if (x < 0) {
        x = 0;
    }
    else if (x > instance.baseWidth) {
        x = instance.baseWidth;
    }
    var y = Math.round(event.clientY - instance.baseOffsetY);
    if (y < 0) {
        y = 0;
    }
    else if (y > instance.baseHeight) {
        y = instance.baseHeight;
    }

    var hsv = {
        h : instance.H,
        s : Math.round(255 * (x / instance.baseWidth)),
        v : Math.round(255 * (Math.abs(instance.baseHeight - y) / instance.baseHeight))
    };
    var rgb = instance.hsv2rgb(hsv);
    instance.selectedColor = instance.rgb2String(rgb);
    instance.colorTip.style.backgroundColor = instance.selectedColor;
    instance.S = hsv.s;
    instance.V = hsv.v;

    instance.pointer.style.left = x + 'px';
    instance.pointer.style.top = y + 'px';
};

TM_Colorpicker.prototype.endPreview = function(event) {
    var instance = TM.colorpicker;
    window.removeEventListener('mousemove', instance.preview);
    window.removeEventListener('mouseup', instance.endPreview);
};

TM_Colorpicker.prototype.rgb2Hsv = function(rgb) {
    var maximum = Math.max(rgb.r, rgb.g, rgb.b);
    var minimum = Math.min(rgb.r, rgb.g, rgb.b);

    var hsv = { h:0, s:0, v:0 };
    if (rgb.r === maximum) {
        hsv.h = Math.round(60 * ((rgb.g - rgb.b) / (maximum - minimum)));
    }
    else if (rgb.g === maximum) {
        hsv.h = Math.round(60 * ((rgb.b - rgb.r) / (maximum - minimum)) + 120);
    }
    else if (rgb.b === maximum) {
        hsv.h = Math.round(60 * ((rgb.r - rgb.g) / (maximum - minimum)) + 240);
    }

    if (hsv.h < 0) {
        hsv.h = hsv.h + 360;
    }

    hsv.s = Math.round(((maximum - minimum) / maximum) * 255);
    hsv.v = maximum;

    return hsv;
};

TM_Colorpicker.prototype.hsv2rgb = function(hsv) {
    var maximum = hsv.v;
    var minimum = Math.round(maximum - ((hsv.s / 255) * maximum));

    var rgb = {r:0, g:0, b:0};
    if (hsv.h > 300) {
        rgb.r = maximum;
        rgb.g = minimum;
        rgb.b = Math.round(((360 - hsv.h) / 60) * (maximum - minimum) + minimum);
    }
    else if (hsv.h > 240) {
        rgb.r = Math.round(((hsv.h - 240) / 60) * (maximum - minimum) + minimum);
        rgb.g = minimum;
        rgb.b = maximum;
    }
    else if (hsv.h > 180) {
        rgb.r = minimum;
        rgb.g = Math.round(((240 - hsv.h) / 60) * (maximum - minimum) + minimum);
        rgb.b = maximum;
    }
    else if (hsv.h > 120) {
        rgb.r = minimum;
        rgb.g = maximum;
        rgb.b = Math.round(((hsv.h - 120) / 60) * (maximum - minimum) + minimum);
    }
    else if (hsv.h > 60) {
        rgb.r = Math.round(((120 - hsv.h) / 60) * (maximum - minimum) + minimum);
        rgb.g = maximum;
        rgb.b = minimum;
    }
    else {
        rgb.r = maximum;
        rgb.g = Math.round((hsv.h / 60) * (maximum - minimum) + minimum);
        rgb.b = minimum;
    }

    return rgb;
}

TM_Colorpicker.prototype.string2Rgb = function(str) {
    var found = str.match(/^#([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2})$/i);
    var rgb = { r:0, g:0, b:0 };
    if (found) {
        rgb.r = parseInt(found[1], 16),
        rgb.g = parseInt(found[2], 16),
        rgb.b = parseInt(found[3], 16)
    }
    else {
        found = str.match(/^#([a-f0-9])([a-f0-9])([a-f0-9])$/i);
        if (found) {
            rgb.r = parseInt(found[1]+found[1], 16),
            rgb.g = parseInt(found[2]+found[2], 16),
            rgb.b = parseInt(found[3]+found[3], 16)
        }
        else {
            found = str.match(/^rgb\(([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\)$/i);
            if (found) {
                rgb.r = parseInt(found[1]),
                rgb.g = parseInt(found[2]),
                rgb.b = parseInt(found[3])
            }
        }
    }

    return rgb;
};

TM_Colorpicker.prototype.rgb2String = function(rgb) {
    return '#' + this.toHex(rgb.r) + this.toHex(rgb.g) + this.toHex(rgb.b);
};

TM_Colorpicker.prototype.toHex = function(num) {
    return ('0' + num.toString(16)).slice(-2);
};

TM_Colorpicker.prototype.cancel = function(event) {
    var instance = TM.colorpicker;
    var targetElement = event.target;

    if (event.type === 'mousedown') {
        while (targetElement && targetElement.nodeType === Node.ELEMENT_NODE) {
            if (   targetElement.classList.contains('color-picker-panel')
                || targetElement.classList.contains('input-color')
            ) {
                return;
            }
            targetElement = targetElement.parentNode;
        }
    }

    if (instance.colorTip) {
        var input = document.getElementsByName(instance.colorTip.dataset.for)[0];
        instance.colorTip.style.backgroundColor = input.value;
    }

    instance.clearPicker();
    window.removeEventListener('mousedown', this.cancel);
};

TM_Colorpicker.prototype.ok = function(event) {
    var instance = TM.colorpicker;

    if (instance.colorTip) {
        var input = document.getElementsByName(instance.colorTip.dataset.for)[0];
        input.value = instance.selectedColor;
    }

    instance.clearPicker();
};

TM_Colorpicker.prototype.clearPicker = function(element) {
    var element = document.getElementById('TM-colorpicker');
    if (element) {
        element.parentNode.removeChild(element);
    }
    this.init();
};

TM_Colorpicker.prototype.createPicker = function(event) {
    var pickerID = 'TM-colorpicker';
    var element = event.currentTarget;
    var newPicker = element.querySelector('.selected');
    var returnFlg = (newPicker === this.colorTip);

    var anotherPanel = document.getElementById(pickerID);
    if (anotherPanel) {
        this.cancel(event);
        if (returnFlg) {
            return;
        }
    }

    this.colorTip = newPicker;

    var pos = element.getBoundingClientRect();

    var outer = document.body.appendChild(document.createElement('div'));
    outer.id = pickerID;
    outer.classList.add('color-picker-panel');
    outer.style.top = pos.bottom + 'px';
    outer.style.left = pos.left + 'px';
    var outerRect = outer.getBoundingClientRect();
    this.slideOffset = outerRect.top;

    this.base = outer.appendChild(document.createElement('div'));
    this.base.classList.add('base-color');
    this.base.addEventListener('mousedown', this.listener);
    this.base.addEventListener('click', this.listener);
    var rect = this.base.getBoundingClientRect();
    this.baseWidth = rect.width - 1;
    this.baseHeight = rect.height - 1;
    this.baseOffsetX = rect.left;
    this.baseOffsetY = rect.top;

    var inner = this.base.appendChild(document.createElement('div'));
    inner.classList.add('brightness');

    this.pointer = inner.appendChild(document.createElement('div'));
    this.pointer.classList.add('pointer');

    this.bar = outer.appendChild(document.createElement('div'));
    this.bar.classList.add('base-color-selector');
    var barRect = this.bar.getBoundingClientRect();

    this.slideTop = barRect.top - outerRect.top - 1
    this.slideBottom = this.slideTop + barRect.height;

    this.slider = outer.appendChild(document.createElement('div'));
    this.slider.classList.add('selector-slider');
    this.slider.addEventListener('mousedown', this.listener);

    var controls = outer.appendChild(document.createElement('div'));
    controls.classList.add('controls');

    var cancel = controls.appendChild(document.createElement('button'));
    cancel.type = 'button';
    cancel.innerHTML = 'Cancel';
    cancel.addEventListener('click', this.cancel);

    var ok = controls.appendChild(document.createElement('button'));
    ok.type = 'button';
    ok.innerHTML = 'OK';
    ok.addEventListener('click', this.ok);

    // Initialize
    var rgb = this.string2Rgb(this.colorTip.style.backgroundColor);
    var hsv = this.rgb2Hsv(rgb);

    var silderY = Math.round(barRect.height * (hsv.h / 360) + this.slideTop);
    this.slider.style.top = silderY  + 'px';
    var ccc = this.hsv2rgb({
        h : Math.round(360 * ((silderY - this.slideTop) / (this.slideBottom - this.slideTop))),
        s : 255,
        v : 255
    });
    this.base.style.backgroundColor = this.rgb2String(ccc);

    var x = Math.round(this.baseWidth * (hsv.s / 255));
    var y = Math.abs(this.baseHeight - Math.round(this.baseHeight * (hsv.v / 255)));
    this.pointer.style.left = x + 'px';
    this.pointer.style.top = y + 'px';
    this.S = hsv.s;
    this.V = hsv.v;

    window.addEventListener('mousedown', this.cancel);
};

TM_Colorpicker.prototype.slide = function(event) {
    var instance = TM.colorpicker;

    var y = Math.round(event.clientY - instance.slideOffset);
    if (y < instance.slideTop) {
        y = instance.slideTop;
    }
    else if (y > instance.slideBottom) {
        y = instance.slideBottom;
    }

    var hsv = {
        h : Math.round(360 * ((y - instance.slideTop) / (instance.slideBottom - instance.slideTop))),
        s : 255,
        v : 255
    };
    var rgb = instance.hsv2rgb(hsv);
    instance.base.style.backgroundColor = instance.rgb2String(rgb);

    instance.slider.style.top = y + 'px';

    hsv.s = instance.S;
    hsv.v = instance.V;
    rgb = instance.hsv2rgb(hsv);
    instance.selectedColor = instance.rgb2String(rgb);
    instance.colorTip.style.backgroundColor = instance.selectedColor;
};

TM_Colorpicker.prototype.endSlide = function(event) {
    var instance = TM.colorpicker;
    window.removeEventListener('mousemove', instance.slide);
    window.removeEventListener('mouseup', instance.endSlide);
};

TM_Colorpicker.prototype.listener = function(event) {
    var instance = TM.colorpicker;
    var element = event.currentTarget;
    switch (event.type) {
        case 'click':
            if (element.classList.contains('input-color')) {
                instance.createPicker(event);
            }
            else if (element === instance.base) {
                instance.preview(event);
            }
            break;
        case 'mousedown':
            if (element.classList.contains('selector-slider')) {
                window.addEventListener('mousemove', instance.slide);
                window.addEventListener('mouseup', instance.endSlide);
            }
            else if (element === instance.base) {
                var borderWidth = (instance.slider.offsetHeight - instance.slider.clientHeight) / 2;
                var y = (instance.slider.offsetTop + borderWidth) - instance.bar.offsetTop;
                instance.H = Math.round(360 * (y / (instance.slideBottom - instance.slideTop)));
                window.addEventListener('mousemove', instance.preview);
                window.addEventListener('mouseup', instance.endPreview);
            }
            break;
        case 'mouseout':
            if (element === instance.base) {
                element.removeEventListener('click', instance.select);
                element.removeEventListener('mousemove', instance.preview);
            }
            break;
        case 'mouseover':
            break;
    }
};

TM_Colorpicker.prototype.init = function(event) {
    this.activeSelector = undefined;
    this.bar = undefined;
    this.base = undefined;
    this.baseWidth = 0;
    this.baseHeight = 0;
    this.colorButton = undefined;
    this.colorTip = undefined;
    this.pointer= undefined;
    this.slider = undefined;
    this.slideOffset = 0;
    this.slideTop = 0;
    this.slideBottom = 0;
    this.H = 0;
    this.S = 255;
    this.V = 255;
    this.idSuffix = '-picker';
    this.selectedColor = '';
};

TM.colorpicker = new TM_Colorpicker();
