/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2018 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
var TM_Anchor = function() {
};

TM_Anchor.prototype.setConfirm = function() {
    var i, el;
    var a = document.getElementsByTagName('a');
    for(i = 0; i < a.length; i++){
        el = a[i];
        if(el.dataset.confirm){
            el.addEventListener('click', TM.anchor.confirm, false);
        }
    }
};

TM_Anchor.prototype.confirm = function(event) {
    var msg = '';
    switch(event.target.dataset.confirm){
        case 'delete':
            msg = 'データを削除します。取消はできません\nよろしいですか？';
            break;
    }
    if (!confirm(msg)) {
        event.preventDefault();
    }
};
