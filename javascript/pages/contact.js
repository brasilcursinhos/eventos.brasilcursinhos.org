$(function(){
    $("#message-form").validate({
        rules : {
            "name":{required: true},
            "email":{required: true, email:true},
            "subject":{required: true},
            "message":{required: true, minlength: 20, maxlength: 2000},
            "type-selection":{required: true},
            "registration-number":{number: true}
        },
        messages:{
            "name":{required:"Informe o seu nome completo."},
            "email":{required:"Informe o seu endereço de e-mail para contato.",
                    email:"Informe um endereço de e-mail válido!"},
            "subject":{required:"Selecione o assunto."},
            "message":{required:"Digite a sua mensagem.", minlength:"Digite pelo menos 20 caracteres na sua mensagem.", maxlength:"Digite menos de 2000 caracteres na sua mensagem."},
            "type-selection":{required:"Selecione o tipo de Processo Seletivo."},
            "registration-number":{number:"O número de inscrição é inválido."}
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        }
    });

    $("#registration-number").mask("99999999", {autoclear: false});

    $("#subject").on("change", function(){
        if($(this).val() === "8") {
            $("#selection-infos").removeClass("d-none");
        } else {
            $("#selection-infos").addClass("d-none");
            $("#type-selection").val("").removeClass("is-valid is-invalid");
            $("#registration-number").val("").removeClass("is-valid is-invalid");
        }
    });

    $("select").on("change", function(){
        if($(this).val() === "") {
            $(this).addClass("text-secondary");
        } else {
            $(this).removeClass("text-secondary");
        }
    });

    $("#btn-send-message").on("click", function(event){
        event.preventDefault();
        if($("#message-form").validate().form()){
            $("#contact-form").addClass("d-none");
            $("#sending-message").removeClass("d-none");
            $("#message-form").submit();
        }
    })
})