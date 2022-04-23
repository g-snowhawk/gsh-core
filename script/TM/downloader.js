/**
 * Javascript Library for G.Snowhawk Application
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Downloader = function() {
    this.timer = undefined;
    this.startCookie = 'download-start';
    this.endCookie = 'download-end';
    TM.initModule(this.init, this, 'interactive');
};

TM_Downloader.prototype.click = function(event) {
    var instance = TM.downloader;
    event.preventDefault();
    instance.starter = event.target;
    var formElement = instance.starter.form;
    instance.start = new Date().getTime();
    TM.setCookie(instance.startCookie, instance.start);
    instance.appendHidden(formElement, 'start_cookie', instance.startCookie);
    instance.appendHidden(formElement, 'end_cookie', instance.endCookie);
    formElement.submit();
    instance.timer = setInterval(instance.poling, 1000);
};

TM_Downloader.prototype.appendHidden = function(formElement, name, value) {
    var node = document.createElement('input');
    node.setAttribute('type', 'hidden');
    node.setAttribute('name', name);
    node.setAttribute('value', value);
    formElement.appendChild(node);
};

TM_Downloader.prototype.poling = function() {
    var instance = TM.downloader;
    var result = TM.getCookie(instance.endCookie);
    if (TM.downloader.start === result) {
        clearInterval(instance.timer);
        if (instance.starter.dataset.redirect) {
            location.href = instance.starter.dataset.redirect;
        } else {
            location.reload();
        }
    }
};

TM_Downloader.prototype.init = function(event) {
    var elements = document.getElementsByTagName('input');
    for (var i = 0; i < elements.length; i++) {
        var element = elements[i];
        if (element.dataset.request === 'TM.downloader') {
            element.addEventListener('click', this.click, false);
        }
    }
};

TM.downloader = new TM_Downloader();
