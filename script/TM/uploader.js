/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */

var TM_Uploader = function() {
    this.ta = undefined;
    this.lstr = '';
    this.mstr = '';
    this.rstr = '';
    this.popupwindow = undefined;
    TM.initModule(this.init, this, 'interactive');
};
TM_Uploader.prototype.init = function(ev) {
    var els = document.getElementsByClassName(TM.form.cnImageSelector);
    for(var i = 0; i < els.length; i++){
        els[i].addEventListener('change', this.onChange, false);
        var prn = TM.getParentNode(els[i], '.file-set');
        var a = prn.getElementsByTagName('a');
        for(var j = 0; j < a.length; j++){
          a[j].addEventListener('click', this.onClick, false);
        }
    }

    //window.addEventListener('drop', this.onDrop, false);

    this.ta = document.getElementById('body');
    if (!this.ta) {
        return;
    }
    this.ta.addEventListener('dragover', this.onDragCancel, false);
    this.ta.addEventListener('dragenter', this.onDragCancel, false);
    this.ta.addEventListener('drop', this.onDrop, false);

    var els = document.getElementsByTagName('img');
    for(var i = 0; i < els.length; i++){
        els[i].addEventListener('dragstart', this.onDragStart, false);
    }
};
TM_Uploader.prototype.onClick = function(ev) {
    ev.preventDefault();
    TM.uploader.delete(ev.currentTarget);
};
TM_Uploader.prototype.onChange = function(ev) {
    if(ev.currentTarget.value == ''){
        ev.preventDefault();
        return;
    }
    TM.uploader.thumbnail(ev.currentTarget);
};
TM_Uploader.prototype.onDragCancel = function(ev) {
    ev.preventDefault();
};
TM_Uploader.prototype.onDragStart = function(ev) {
    var el = ev.currentTarget;
    ev.dataTransfer.setData('src', el.src);
};
TM_Uploader.prototype.onDrop = function(ev) {
    ev.preventDefault();
    ev.stopPropagation();
    var el = ev.currentTarget;
    if(el.id != 'body'){
        return;
    }

    el = TM.uploader.ta;
    var val = el.value;
    var s = el.selectionStart;
    var e = el.selectionEnd;
    TM.uploader.lstr = val.slice(0, s);
    TM.uploader.rstr = val.slice(e);
    TM.uploader.mstr = (s != e) ? val.slice(s, e) : '';

    TM.uploader.ta.value = TM.uploader.lstr + '![' + TM.uploader.mstr + '](' + ev.dataTransfer.getData('src') + ')' + TM.uploader.rstr;
};
TM_Uploader.prototype.delete = function(el) {
    var i, ipt;
    var prn = TM.getParentNode(el, '.file-set');
    if (el.href == '#cancel') {
        prn.parentNode(prn);
        return;
    }

    if (el.className == 'marked') {
        el.className = 'mark';
        el.style.opacity = null;

        ipt = el.getElementsByTagName('input');
        for(i = 0; i < ipt.length; i++){
            el.removeChild(ipt[i]);
        }

        ipt = prn.getElementsByTagName('input');
        for(i = 0; i < ipt.length; i++){
            if(ipt[i].name == 'donothing[]'){
                ipt[i].name = 'file[]';
            }
        }

    } else {
        ipt = prn.getElementsByTagName('input');
        var id = ipt[0].name.match(/^file\[(.*?)([0-9]+)\]/);
        if (id) {
            var nm = id[1] + id[2];
            var hdn = document.createElement('input');
            hdn.type = 'hidden';
            hdn.name = 'delete[' + nm + ']';
            hdn.value = id[2];
            el.appendChild(hdn);
        } else {
            ipt[0].name = 'donothing[]';
        }
        el.className = 'marked';
        el.style.opacity = '1';
    }
};
TM_Uploader.prototype.thumbnail = function(el) {
    var prn = TM.getParentNode(el, '.file-set');
    var span = prn.getElementsByClassName('thumbnail');
    if(span.length < 1) return;
    span = span[0];
    span.innerHTML = '';

    if(el.files.length < 1){
        return;
    }

    var file = el.files[0];
    if(!file.type.match('(image.*|.*pdf.*)')){
        return;
    }
    var render = new FileReader();
    render.onload = function(){
        if(el.parentNode.className !== 'selected'){
            var cln = prn.parentNode.appendChild(prn.cloneNode(false));
            cln.innerHTML = prn.innerHTML;
            var ipt = cln.getElementsByTagName('input');
            for(i = 0; i < ipt.length; i++){
                ipt[i].addEventListener('change', TM.uploader.onChange, false);
            }
            el.parentNode.className = 'selected';

            cln.id = prn.id;
            prn.removeAttribute('id');

            if (typeof window[prn.dataset.afterAdded] === 'function') {
                window[prn.dataset.afterAdded].apply(window, [prn]);
            }
        }
        var img = span.appendChild(document.createElement('img'));
        var fileType;
        if(this.result.match('^data:image/(jpeg|png|gif)')){
            img.src = this.result;
            fileType = 'image';
        } else {
            var dir = TM_Common.__DIR__ + '/../';
            img.src = dir + 'style/icon_pdf.svg';

            var spn = span.appendChild(document.createElement('span'));
            spn.className = "filename";
            spn.innerHTML = TM.basename(el.value);
            if(this.result.match('^data:application/pdf')){
                fileType = 'pdf';
            }
        }
        img.title = el.value;
        img.draggable = false;
        img.addEventListener('dragstart', TM.uploader.onDragStart, false);

        var a = prn.appendChild(document.createElement('a'));
        a.href = '#delete';
        a.className = 'mark';
        a.addEventListener('click', TM.uploader.onClick, false);

        var template = document.getElementById('popup-note').content.querySelector('.popup');
        var popup = template.cloneNode(true);
        if (fileType !== 'pdf') {
            var select = popup.querySelector('select');
            if (select) {
                select.parentNode.removeChild(select);
            }
        }
        prn.appendChild(popup);
        prn.addEventListener('contextmenu', this.onContextMenu, false);

        if (typeof window[prn.dataset.preset] === 'function') {
            window[prn.dataset.preset].apply(window, [prn]);
        }
    }
    render.readAsDataURL(file);
};

TM.uploader = new TM_Uploader();


switch (document.readyState) {
    case 'loading' :
        window.addEventListener('DOMContentLoaded', customFileInit)
        break;
    case 'interactive':
    case 'complete':
        customFileInit();
        break;
}

function customFileInit(event) {
    const elements = document.querySelectorAll('label.customfile');
    elements.forEach((element) => {
        const input = element.querySelector('input[type=file]');
        input.addEventListener('change', customFileThumbnail);
    });
}

function customFileThumbnail(event) {
    const element = event.target;
    const thumbnail = element.parentNode.querySelector('span.thumbnail');
    const file = element.files[0];
    if (!file.type.match('(image.*|.*pdf.*)')) return;

    const render = new FileReader();
    render.onload = function() {
        const img = thumbnail.querySelector('img') || thumbnail.appendChild(document.createElement('img'));
        let fileType;
        if(this.result.match('^data:image/(jpeg|png|gif)')){
            img.src = this.result;
            fileType = 'image';
        } else {
            const dir = TM_Common.__DIR__ + '/../';
            img.src = dir + 'style/icon_pdf.svg';
        }
        img.title = element.value;
    }
    render.readAsDataURL(file);
}
