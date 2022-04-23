/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function AmendsInput() {
    this.inited = false;
    if (!navigator.userAgent.match(/(Trident|MSIE)/)) {
        TM.initModule(this.init, this, 'complete');
    }
}

AmendsInput.prototype.setAmends = function(targetObject) {
    if (!targetObject) {
        targetObject = document;
    }
    var amends = getcookie('amendsInput');
    if (!amends) {
        var testPhrase = 'The element is not support type attribute.';
        var testElement = document.body.appendChild(document.createElement('input'));
        testElement.style.display = 'none';

        var types = {
            color: { type:'color', sample:'#ffffff' },
            date: { type:'date', sample:'1970-01-01' },
            time: { type:'time', sample:'00:00' },
            datetimeLocal: { type:'datetime-local', sample:'1970-01-01T00:00' }
        };

        amends = [];
        for (var key in types) {
            testElement.type = types[key].type;
            testElement.value = testPhrase;

            if (testElement.value !== testPhrase) {
                testElement.value = types[key].sample;
                if (testElement.value === types[key].sample) {
                    continue;
                }
            }
            amends.push(types[key].type);
        }
        setcookie('amendsInput', JSON.stringify(amends));
        testElement.parentNode.removeChild(testElement);
    } else {
        amends = JSON.parse(amends);
    }

    var n, i, max;
    for (n = 0; n < amends.length; n++) {
        var elements = targetObject.querySelectorAll('[type=' + amends[n] + ']');
        for (i = 0, max = elements.length; i < max; i++) {
            var element = elements[i];
            switch (element.getAttribute('type')) {
                case 'color':
                    TM.colorpicker.initColor(element);
                    break;
                case 'date':
                    TM.datetime.initDate(element);
                    break;
                case 'datetime-local':
                    TM.datetime.initDateTime(element);
                    break;
                case 'time':
                    TM.datetime.initTime(element);
                    break;
            }
        }
    }
};

AmendsInput.prototype.init = function(event) {
    this.setAmends();
    this.inited = true;
};

TM.amendsInput = new AmendsInput();
