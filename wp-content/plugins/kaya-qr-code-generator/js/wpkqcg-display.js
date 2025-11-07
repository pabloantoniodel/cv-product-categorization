/**
 * Run the generator on front.
 *
 * @since 1.5.0
 */
var wp_kqrcg_js_displayed = false;
try
{
    window.addEventListener("DOMContentLoaded", (event) =>
    {
        wpkqcg_qrcode_display();
        wp_kqrcg_js_displayed = true;
    });
}
catch (err)
{
    jQuery(document).ready(function ()
    {
        if (!wp_kqrcg_js_displayed)
        {
            wpkqcg_qrcode_display();
        }
    });
}
jQuery(document).ready(function ($)
{
    $(document).ajaxSuccess(function ()
    {
        wpkqcg_qrcode_display();
    });
});
