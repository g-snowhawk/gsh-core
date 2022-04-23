/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
if (getcookie('enableCookie') == 'yes') {
    document.write('<input type="submit" name="authEnabler" value="サインイン">');
}
else {
    document.write('<div class="err"><p>Cookieを有効にして、ページを再読込みしてください</p></div>');
}

if (navigator.userAgent.match(/(Trident|MSIE)/)) {
    document.write('<div class="warn"><p>お使いのブラウザでは、表示の乱れ、動作の不具合が発生する可能性があります。<br>Google Chrome&trade;、Firefox<sup>&reg;</sup>、Microsoft<sup>&reg;</sup> Edge&nbsp;など最新のブラウザをお使いください。</p></div>');
}
