/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function UserAlias() {
    this.selectID = 'userkey';
    this.onLoad(this, 'init');
}

UserAlias.prototype.init = function(event) {
};

UserAlias.prototype.refreshList = function() {
    this.change();
};

UserAlias.prototype.posted = function(json, form) {
    var template = document.createElement('template');
    if (template.content) {
        template.innerHTML = json.source;
        document.head.appendChild(template);
        var nodeList = template.content.childNodes;
        var i, max;
        for (i = 0, max = nodeList.length; i < max; i++) {
            var element = nodeList[i];
            if (element.nodeType != Node.ELEMENT_NODE || !element.id) {
                continue;
            }
            var origin = document.getElementById(element.id);
            if (origin) {
                origin.parentNode.replaceChild(element.cloneNode(true), origin);
            }
        }
        document.head.removeChild(template);
    }
};

UserAlias.prototype.change = function() {
    var form = document.forms[0];
    var formData = new FormData();
    formData.append('stub', form.stub.value);
    formData.append('mode', 'user.receive:aliasList');

    TM.xhr.init('POST', form.action, false, function(event){
        var instance = TM.userAlias;
        if(this.status == 200){
            try {
                var obj = JSON.parse(this.responseText);
            } catch (exceptionObject) {
                console.error(exceptionObject.message);
                console.log(this.responseText);
                return;
            }
            instance.posted(obj, form);
        } else {
            // TODO: add error handling
            console.log(this.responseText);
        }
    });

    TM.xhr.send(formData);
};

UserAlias.prototype.listener = function(event) {
    var instance = TM.userAlias;
};

UserAlias.prototype.onLoad = function(scope, func) {
    window.addEventListener(
        'load',
        function(evn) {
            scope[func](evn);
        },
        false
    );
};

/**
 * Create instance
 */
window.TM = window.TM || new TM_Common();
TM.userAlias = new UserAlias();
