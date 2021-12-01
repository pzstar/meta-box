/**
 * All Types Meta Box Class JS
 *
 * JS used for the custom metaboxes and other form items.
 *
 */

var $ = jQuery.noConflict();

function update_repeater_fields() {
    cehr_metabox_fields.updateRepeater();
}
//metabox fields object
var cehr_metabox_fields = {
    oncefancySelect: false,
    updateRepeater: function () {
        this.load_time_picker();
        this.load_date_picker();
        this.load_color_picker();
        this.fancySelect();
    },
    init: function () {
        if (!this.oncefancySelect) {
            this.fancySelect();
            this.oncefancySelect = true;
        }
        this.load_conditional();
        this.load_time_picker();
        this.load_date_picker();
        this.load_color_picker();
        this.load_tab();
        this.upload_image();
        this.delete_image();
    },
    fancySelect: function () {
        if ($().select2) {
            $(".ht--select, .ht--posts-select, .ht--tax-select, .ht--widget-select").each(function () {
                if (!$(this).hasClass('no-fancy'))
                    $(this).select2();
            });
        }
    },
    load_conditional: function () {
        $(".ht--meta-box-conditional-control").click(function () {
            if ($(this).is(':checked')) {
                $(this).closest('.ht--meta-box-cond').next('.ht--meta-box-conditional-container').show('fast');
            } else {
                $(this).closest('.ht--meta-box-cond').next('.ht--meta-box-conditional-container').hide('fast');
            }
        });
    },
    load_time_picker: function () {
        $('.ht--time').each(function () {

            var $this = $(this);
            var format = $this.attr('rel');

            $this.timepicker({timeFormat: format});

        });
    },
    load_date_picker: function () {
        $('.ht--date').each(function () {

            var $this = $(this),
                    format = $this.attr('rel');

            $this.datepicker({showButtonPanel: true, dateFormat: format});

        });
    },
    load_color_picker: function () {
        if ($('.ht--color-iris').length > 0)
            $('.ht--color-iris').wpColorPicker();
    },
    load_tab: function () {
        $('.ht--meta-box-tab').on('click', function () {
            var panel = $(this).attr('data-panel');
            $(this).siblings('.ht--meta-box-tab').removeClass('ht--active-tab');
            $(this).addClass('ht--active-tab');
            $(this).closest('.ht--meta-box-container').find('.ht--meta-box-panel').hide();
            $(this).closest('.ht--meta-box-container').find('.' + panel).show();
            return false;
        });
    },
    upload_image: function () {
        // ADD IMAGE LINK
        $('body').on('click', '.ht--meta-box-upload-image, .ht--meta-box-image-preview', function (event) {
            event.preventDefault();
            var imgContainer = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-preview');
            var imgIdInput = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-id');
            var imgUrlInput = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-url');
            var bgPrams = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-bg-params');
            var uploadButton = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-upload-image');

            // Create a new media frame
            frame = wp.media({
                title: 'Select or Upload Image',
                button: {
                    text: 'Use Image'
                },
                multiple: false // Set to true to allow multiple files to be selected
            });

            // When an image is selected in the media frame...
            frame.on('select', function () {

                // Get media attachment details from the frame state
                var attachment = frame.state().get('selection').first().toJSON();

                // Send the attachment URL to our custom image input field.
                imgContainer.html('<img src="' + attachment.url + '" style="max-width:100%;"/>');
                imgIdInput.val(attachment.id);
                imgUrlInput.val(attachment.url);
                bgPrams.show();
                uploadButton.removeClass("ht--meta-box-upload-image").addClass('ht--meta-box-remove-image').val('Remove Image');
            });

            // Finally, open the modal on click
            frame.open();

        });
    },
    delete_image: function () {
        // DELETE IMAGE LINK
        $('body').on('click', '.ht--meta-box-remove-image', function (event) {
            event.preventDefault();
            var imgContainer = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-preview');
            var imgIdInput = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-id');
            var imgUrlInput = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-image-url');
            var bgPrams = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-bg-params');
            var removeButton = $(this).closest('.ht--meta-box-row').find('.ht--meta-box-remove-image');

            // Clear out the preview image
            imgContainer.find('img').remove();
            imgIdInput.val('');
            imgUrlInput.val('');
            bgPrams.hide();
            removeButton.removeClass("ht--meta-box-remove-image").addClass('ht--meta-box-upload-image').val('Upload Image');
        });
    }
};
//call object init in delay
window.setTimeout('cehr_metabox_fields.init();', 2000);

jQuery(document).ready(function ($) {
    // repater Field
    $("body").on('click', '.ht--re-toggle', function () {
        $(this).closest('.ht--repater-block').find('.ht--meta-box-repeater-table').slideToggle();
    });

    $('body').on('click', '.ht--re-remove', function () {
        $(this).closest('.ht--repater-block').slideUp(500, function () {
            $(this).remove();
        });
    });

    // repeater sortable
    $('.ht--repeater-sortable').sortable({
        opacity: 0.8,
        cursor: 'move',
        handle: '.ht--re-sort-handle'
    });

    // Linked button
    $(".ht--linked").on("click", function () {
        // Set up variables
        var $this = $(this);
        // Remove linked class
        $this.parent().parent(".ht--dimension-wrap").prevAll().slice(0, 4).find("input").removeClass("linked");
        // Remove class
        $this.parent(".ht--link-dimensions").removeClass("unlinked");
    });
    // Unlinked button
    $(".ht--unlinked").on("click", function () {
        // Set up variables
        var $this = $(this);
        // Add linked class
        $this.parent().parent(".ht--dimension-wrap").prevAll().slice(0, 4).find("input").addClass("linked");
        // Add class
        $this.parent(".ht--link-dimensions").addClass("unlinked");
    });
    // Values linked inputs
    $(".ht--dimension-wrap").on("input", ".linked", function () {

        var $val = $(this).val();
        $('.linked').each(function (key, value) {
            $(this).val($val);
        });
    });
});