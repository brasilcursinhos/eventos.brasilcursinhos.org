$(function(){

    const $formEncupRegistration = $("#form-update-transactions");

    $formEncupRegistration.validate({
        rules: {
            "transactions-file":{required: true, accept: "text/csv", maxsize:10485760},
        },
        messages:{
            "transactions-file":{
                required:"Envie o extrato de transações da conta.",
                accept:"Envie um arquivo CSV.",
                maxsize:"Envie um arquivo de no máximo 10MB."
            }
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        }
    });

    $("#transactions-file").on("change", function() {
        $(this).valid();
    });

})
