/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_PrintFrame = function() {
    TM.initModule(this.init, this, 'complete');
}

TM_PrintFrame.prototype.userReissued = function(doc) {
    var form = document.getElementById('TMS-mainform');
    if (!form) {
        return;
    }

    doc.documentElement.id = 'print-user-reissued';
    doc.body.innerHTML = '<h1>' + form.mail_subject.value + '</h1>'
        + '<p>' + form.mail_body.value.replace(/(\r\n|\n|\r)/g, '<br>') + '</p>';
};

TM_PrintFrame.prototype.frameLoaded = function(event) {
    var instance = TM.print;
    var frame = event.currentTarget;
    var doc = frame.contentDocument;
    switch (document.documentElement.id) {
        case 'user-reissued':
            instance.userReissued(doc);
            break;
    }
    frame.contentWindow.print();
};

TM_PrintFrame.prototype.print = function(element) {
    try {
        var frame = document.getElementById('print-frame');
        if (frame) {
            frame.parentNode.removeChild(frame);
        }

        frame = document.body.appendChild(document.createElement('iframe'));
        frame.id = 'print-frame';
        frame.src = '?mode=system.response:print-frame';
        frame.addEventListener("load", this.frameLoaded);
    } catch(exception) {
        console.error(exception.message);
    }
};

TM_PrintFrame.prototype.listener = function(event) {
    switch (event.type) {
        case 'click':
            TM.print.print(event.currentTarget);
            break;
    }
};

TM_PrintFrame.prototype.init = function() {
    var i;
    var buttons = document.getElementsByClassName('print-button');
    for (i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', this.listener);
    }
};

TM.print = new TM_PrintFrame();
