$(function(){

    const $formEncupRegistration = $("#form-event-payment");

    $formEncupRegistration.validate({
        rules: {
            "payment-proof":{required: true, accept: "application/pdf,image/*", maxsize:2097152},
        },
        messages:{
            "payment-proof":{
                required:"Envie o comprovante do pagamento.",
                accept:"Envie um arquivo de imagem ou PDF.",
                maxsize:"Envie um arquivo de no máximo 2MB."
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

    $("#payment-proof").on("change", function() {
        $(this).valid();
    });

    $("#btn-pix-code-copy").on("click", function() {
        navigator.clipboard && navigator.clipboard.writeText($("#pix-code").val()).then(function() {
            alert("C\xF3digo PIX copiado para \xE1rea de transfer\xEAncia!")
        }, function() {
            alert("Falha ao copiar c\xF3digo PIX para \xE1rea de transfer\xEAncia!")
        })
    })

})
