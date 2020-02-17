$(document).ready( function() {
    toggleBugNoteForm($('#bugnote-type').val());

    $('#bugnote-type').on('change', function() {
        toggleBugNoteForm(this.value);
    });
});

function toggleBugNoteForm(type) {
    if (type == 1) {
        $('#call-log-time').hide();
        $('#call-log-tel-no').hide();

    } else if (type == 2) {
        $('#call-log-time').show();
        $('#call-log-tel-no').show();

    }
}