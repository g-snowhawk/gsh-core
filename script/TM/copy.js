/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_CopyFrame = function() {
    TM.initModule(this.init, this, 'complete');
}

TM_CopyFrame.prototype.copy = function(element) {
    try {
        var targetID = element.dataset.copyTarget;
        var frame = document.getElementById(targetID);
        if (!frame) {
            throw new Error('Not found copying frame.');
        }
        if (frame.nodeName.toLowerCase() !== 'iframe') {
            throw new Error('Target is not copying element.');
        }
        frame.contentDocument.execCommand('SelectAll');
        frame.contentDocument.execCommand('copy');
    } catch(exception) {
        console.error(exception.message);
    }
};

TM_CopyFrame.prototype.listener = function(event) {
    switch (event.type) {
        case 'click':
            TM.copyframe.copy(event.currentTarget);
            break;
    }
};

TM_CopyFrame.prototype.init = function() {
    var i;
    var buttons = document.getElementsByClassName('copy-button');
    for (i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', this.listener);
    }
};

TM.copyframe = new TM_CopyFrame();
