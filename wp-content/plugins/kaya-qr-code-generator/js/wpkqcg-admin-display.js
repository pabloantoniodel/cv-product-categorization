/**
 * Run the generator on admin.
 *
 * @since 1.5.0
 */
var wp_kqrcg_js_preview_displayed = false;
try
{
    window.addEventListener("DOMContentLoaded", (event) => { wpkqcg_qrcode_preview_display(); wp_kqrcg_js_preview_displayed = true; });
}
catch (err)
{
    jQuery(document).ready(function () { if (!wp_kqrcg_js_preview_displayed) { wpkqcg_qrcode_preview_display(); } });
}
/**
 * Run the color-picker on admin.
 *
 * @since 1.5.0
 */
jQuery(document).ready(function ($)
{
    $('.wp-kqcg-color-picker').wpColorPicker({
        change: function (event, ui)
        {
            var fieldID = event.target.attributes['id'].nodeValue;
            var fieldObject = document.getElementById(fieldID);
            fieldObject.value = ui.color.toString();
            fieldObject.setAttribute("value", ui.color.toString());
            wpkqcg_qrcode_sc_gen(fieldObject, fieldObject.getAttribute("data-slug"));
        }
    });
});