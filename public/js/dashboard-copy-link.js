// The dashboard's "copy link" field.
$(document).ready(function() {
    $('#copy-button,#copy-input').bind('click', function() {
        $("#copy-input").select();      
        try {
            var success = document.execCommand('copy');
            if (success) {
                toastr["info"]("Copied!");
            } else {
                toastr["error"]("Copy with Ctrl-c");
            }
        } catch (err) {
            toastr["error"]("Copy with Ctrl-c");
        }
    });
});
