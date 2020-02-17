$(document).ready( function() {
    toggleCustomFieldByCategory($('#category_id').val());
    $('#category_id').on('change', function() {
        toggleCustomFieldByCategory(this.value);
    });
    if ($('#project_underwritings').val() == 1) {
        $('#summary').val('[automatic]');
    }
    if ($('#custom_field_63_row td').html() == 'Yes') {
        $('#custom_field_63_row td').addClass('bg-warning text-danger');
        $('#custom_field_63_row td').html('<strong>Yes</strong>');
    }
    if ($('#custom_field_87_row td').html() == 'Yes') {
        $('#custom_field_87_row td').addClass('bg-warning text-danger');
        $('#custom_field_87_row td').html('<strong>Yes</strong>');
    }
    if ($('#show_duplicated').val() == 'yes') {
        $('#custom_field_63_row').show();
        $('#custom_field_87_row').show();
    } else {
        $('#custom_field_63_row').hide();
        $('#custom_field_87_row').hide();
    }
});

function toggleCustomFieldByCategory(category) {
    if (category == 26) {
        $('#custom_field_79_row').show();
        $('#custom_field_11_row').show();
        $('#custom_field_56_row').show();
        $('#custom_field_57_row').show();
        $('#custom_field_61_row').show();
        $('#custom_field_69_row').show();
        $('#custom_field_64_row').show();
        $('#custom_field_70_row').show();
        $('#custom_field_65_row').show();
        $('#custom_field_71_row').show();
        $('#custom_field_66_row').show();
        $('#custom_field_72_row').show();
        $('#custom_field_67_row').show();
        $('#custom_field_73_row').show();
        $('#custom_field_68_row').show();
        $('#custom_field_62_row').show();
        $('#custom_field_81_row').show();
        $('#custom_field_80_row').hide();
        $('#custom_field_82_row').show();
        $('#custom_field_83_row').hide();
        $('#custom_field_84_row').hide();
    } else if (category == 27) {
        $('#custom_field_79_row').hide();
        $('#custom_field_79').val(null);
        $('#custom_field_11_row').hide();
        if ($('#custom_field_11').val() == '') {
            $('#custom_field_11').val('NA');
        }
        $('#custom_field_56_row').hide();
        if ($('#custom_field_56_year').val() == 0) {
            $('#custom_field_56_year option[value=1920]').attr('selected', 'selected');
            $('#custom_field_56_month option[value=1]').attr('selected', 'selected');
            $('#custom_field_56_day option[value=1]').attr('selected', 'selected');
        }
        $('#custom_field_57_row').hide();
        if ($('#custom_field_57').val() == '') {
            $('#custom_field_57').val('NA');
        }
        $('#custom_field_61_row').hide();
        $('#custom_field_69_row').hide();
        $('#custom_field_64_row').hide();
        $('#custom_field_70_row').hide();
        $('#custom_field_65_row').hide();
        $('#custom_field_71_row').hide();
        $('#custom_field_66_row').hide();
        $('#custom_field_72_row').hide();
        $('#custom_field_67_row').hide();
        $('#custom_field_73_row').hide();
        $('#custom_field_68_row').hide();
        $('#custom_field_62_row').hide();
        $('#custom_field_81_row').hide();
        $('#custom_field_80_row').show();
        $('#custom_field_82_row').show();
        $('#custom_field_83_row').show();
        $('#custom_field_84_row').show();
    }
}
