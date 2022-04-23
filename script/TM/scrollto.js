/**
 * Smooth scroller
 *
 * Copyright (c) 2016 PlusFive. (http://www.plus-5.com/)
 *
 * This software is released under the MIT License.
 * http://www.plus-5.com/licenses/mit-license
 */
/*
 * Technical specification
 *
 * - スクロール対象は直近の offsetParent 要素
 * - スクロール終点は指定された ID を持つブロック要素の座標
 */
function TM_Scrollto() {
    this.x = 0;
    this.y = 0;
    this.offsetX = 0;
    this.offsetY = 0;
    this.timer = 0;
    this.speed = 28;
    this.acceleration = .15;
    this.scrollFrame = undefined;
    TM.initModule(this.init, this, 'interactive');
};

TM_Scrollto.prototype.start = function(element)
{
    var instance = this;

    var hash = element.hash.substr(1);
    var scrollTarget = document.getElementById(hash);
    if (!scrollTarget) {
        return;
    }

    instance.x = scrollTarget.offsetLeft;
    instance.y = scrollTarget.offsetTop;

    var sf = scrollTarget.offsetParent || document.body;

    // TODO: Is this necessary?
    var s,m,p,b;
    s = sf.currentStyle || document.defaultView.getComputedStyle(sf, '');
    m = parseInt(s.marginLeft);
    if (isNaN(m)) {
        m = 0;
    }
    p = parseInt(s.paddingLeft);
    if (isNaN(p)) {
        p = 0;
    }
    b = parseInt(s.borderLeftWidth);
    if (isNaN(b)) {
        b = 0;
    }
    instance.x -= m + p + b;
    m = parseInt(s.marginTop);
    if (isNaN(m)) {
        m = 0;
    }
    p = parseInt(s.paddingTop);
    if (isNaN(p)) {
        p = 0;
    }
    b = parseInt(s.borderTopWidth);
    if (isNaN(b)) {
        b = 0;
    }
    instance.y -= m + p + b;

    var h = (sf === document.body) ? window.innerHeight : sf.offsetHeight;
    var w = (sf === document.body) ? window.innerWidth : sf.offsetWidth;
    var maxH = sf.scrollHeight - h;
    var maxW = sf.scrollWidth - w;

    if (sf != scrollTarget.offsetParent) {
        instance.x -= sf.offsetLeft;
        instance.y -= sf.offsetTop;
    }
    if (instance.x > maxW) {
        instance.x = maxW;
    }
    if (instance.y > maxH) {
        instance.y = maxH;
    }

    if (instance.x < 0) {
        instance.x = 0;
    }
    if (instance.y < 0) {
        instance.y = 0;
    }

    instance.offsetX = sf.scrollLeft;
    instance.offsetY = sf.scrollTop;

    instance.scrollFrame = instance.getScrollFrame(sf);
    clearInterval(instance.timer);
    instance.timer = setInterval(instance.scrolling, instance.speed);

    window.addEventListener('mousedown', instance.cancel, false);
};

TM_Scrollto.prototype.cancel = function(ev)
{
    var instance = TM.scrollto;
    clearInterval(instance.timer);
    instance.x = 0;
    instance.y = 0;
    instance.offsetX = 0;
    instance.offsetY = 0;
    window.removeEventListener('mousedown', instance.cancel, false);
};

TM_Scrollto.prototype.getScrollFrame = function(sf)
{
    var notBody = false;
    if (sf === document.body) {
        var esc = sf.scrollTop;
        var n = (esc < 1) ? 1 : -1;
        sf.scrollTop += n;
        if (sf.scrollTop === esc) {
            notBody = true;
        }
        sf.scrollTop = esc;
    }
    return (notBody) ? document.documentElement : sf;
};

TM_Scrollto.prototype.scrolling = function()
{
    var instance = TM.scrollto;

    var x = instance.scrollFrame.scrollLeft;
    var y = instance.scrollFrame.scrollTop;

    var offsetX = (instance.x - x) * instance.acceleration;
    var offsetY = (instance.y - y) * instance.acceleration;

    var distX = (Math.abs(offsetX) > 0) ? Math.floor(offsetX) : Math.ceil(offsetX);
    var distY = (Math.abs(offsetY) > 0) ? Math.floor(offsetY) : Math.ceil(offsetY);
    instance.scrollFrame.scrollLeft += distX;
    instance.scrollFrame.scrollTop += distY;

    instance.offsetX = offsetX;
    instance.offsetY = offsetY;

    var n = 0.1;
    if (Math.abs(offsetX) < n && Math.abs(offsetY) < n) {
        instance.scrollFrame.scrollLeft = instance.x;
        instance.scrollFrame.scrollTop  = instance.y;
        instance.cancel();
    }
};

TM_Scrollto.prototype.listener = function(event)
{
    event.preventDefault();
    switch(event.type){
        case 'click':
        case 'touchend':
            TM.scrollto.start(event.currentTarget);
            break;
    }
};

TM_Scrollto.prototype.init = function(event) {
    var anchor = document.getElementsByTagName('a');
    for (var i = 0; i < anchor.length; i++) {
        var element = anchor[i];
        if (element.dataset.rel === 'scrollto') {
            element.addEventListener('click', this.listener, false);
        }
    }
};

TM.scrollto = new TM_Scrollto();
