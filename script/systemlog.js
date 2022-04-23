/**
 * This file is part of G.Snowhawk Application.
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @author    PlusFive.
 * @copyright (c)2018 PlusFive. (http://www.plus-5.com/)
 */
function SystemLog() {
    TM.initModule(this.init, this, 'complete');
}

SystemLog.prototype.init = function(event) {
    var logs = ['error', 'access'];
    var xhr = {};
    for (var i = 0; i < logs.length; i++) {
        var id = logs[i] + '-log';
        xhr[id] = new TM_XMLHttpRequest();
        xhr[id].init('GET', '?mode=system.response:' + id, true, function(event){
            if (this.status == 200) {
                var m = this.responseURL.match(/^.+:(.+)$/);
                var container = document.getElementById(m[1]);
                if (container) {
                    container.innerHTML = this.responseText;
                }
            }
            else {
                console.log(this.status);
            }
        });
        xhr[id].header('If-Modified-Since', 'Thu, 01 Jun 1970 00:00:00 GMT');
        xhr[id].send(null);
    }
};

TM.systemLog = new SystemLog();
