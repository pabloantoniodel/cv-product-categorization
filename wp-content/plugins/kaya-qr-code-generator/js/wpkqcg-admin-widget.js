/**
 * Run the color-picker on admin widget page.
 *
 * @since 1.5.0
 */
jQuery(document).ready(function ($)
{
    $(document).ajaxSuccess(function ()
    {
        $('.wp-kqcg-color-picker').wpColorPicker({
            change: _.throttle(function (event, ui)
            {
                var fieldID = event.target.attributes['id'].nodeValue;
                var fieldObject = document.getElementById(fieldID);
                fieldObject.value = ui.color.toString();
                fieldObject.setAttribute("value", ui.color.toString());
                jQuery(this).trigger('change');
            }, 3000)
        });
    });
});
jQuery(document).on('widget-added widget-updated', function (event, widget)
{
    jQuery('.wp-kqcg-color-picker').wpColorPicker({
        change: _.throttle(function (event, ui)
        {
            var fieldID = event.target.attributes['id'].nodeValue;
            var fieldObject = document.getElementById(fieldID);
            fieldObject.value = ui.color.toString();
            fieldObject.setAttribute("value", ui.color.toString());
            jQuery(this).trigger('change');
        }, 3000)
    });
}); 