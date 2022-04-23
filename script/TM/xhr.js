/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_XMLHttpRequest = function() {
    this.xhr = undefined;
    this.callback = undefined;
    this.method = undefined;
    this.location = undefined;
    this.async = undefined;
    this.headers = {};
};

TM_XMLHttpRequest.prototype.header = function(key, value) {
    this.headers[key] = value;
};

TM_XMLHttpRequest.prototype.send = function(data) {
    this.xhr.open(this.method, this.location, this.async);
    if (!('X-Requested-With' in this.headers)) {
        this.headers['X-Requested-With'] = 'XMLHttpRequest';
    }
    for (var key in this.headers) {
        this.xhr.setRequestHeader(key, this.headers[key]);
    }
    this.xhr.send(data);
};

TM_XMLHttpRequest.prototype.init = function(method, location, async, callback) {
    this.headers = {};
    this.method = method;
    this.location = location;
    this.async = async;
    this.xhr = new XMLHttpRequest();
    this.xhr.addEventListener('loadend', callback, false);
};

TM.xhr = new TM_XMLHttpRequest();
