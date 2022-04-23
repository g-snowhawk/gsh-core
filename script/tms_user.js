/**
 * Javascript Library for G.Snowhawk Application
 *
 * This software is released under the MIT License.
 * https://www.plus-5.com/licenses/mit-license
 *
 * @copyright 2017 PlusFive (https://www.plus-5.com)
 * @version 1.0.0
 */
function erasePassword(el)
{
    if (el.classList.contains('close')) {
        var fm = TM.getParentNode(el, 'FORM');
        fm.upass.value = '';
        fm.retype.value = '';
    }
}
