$(function(){

    $("#reset-password-form").validate({
        rules : {
            "password":{required: true, securePassword:true, minlength:8, maxlength:20},
            "password-confirmation":{required: true, equalTo:"#password"},
        },
        messages:{
            "password":{
                required: "Informe uma senha.",
                minlength:"Sua senha deve ter no mínimo 8 caracteres",
                maxlength:"Sua senha deve ter no máximo 20 caracteres"
            },
            "password-confirmation":{
                required: "Confirme a sua senha.",
                equalTo:"As senhas não são iguais."
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
            if (element.parent().hasClass("input-group")) {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        }
    });

    jQuery.validator.addMethod("securePassword", function(value, element) {
        if (this.optional(element)) {
            return true;
        }

        const hasLowercase = /[a-z]/.test(value);
        const hasUppercase = /[A-Z]/.test(value);
        const hasNumber = /\d/.test(value);

        return hasLowercase && hasUppercase && hasNumber;
        }, function(params, element) {
        const value = $(element).val();
        const missing = [];

        if (!/[A-Z]/.test(value)) missing.push("1 letra maiúscula");
        if (!/[a-z]/.test(value)) missing.push("1 letra minúscula");
        if (!/\d/.test(value)) missing.push("1 número");

        return "A senha deve conter pelo menos: " + missing.join(", ") + ".";
    });

    $("#btn-password-visibility").on("click", function(event){
        event.preventDefault();
        if($(this).children().text() === 'visibility') {
            $(this).children().text('visibility_off');
            $("#password").prop("type", "text");
            $("#btn-password-visibility").prop("title", "Esconder senha");
            $("#btn-password-visibility").attr("aria-label", "Esconder senha");
        } else {
            $(this).children().text('visibility');
            $("#password").prop("type", "password");
            $("#btn-password-visibility").prop("title", "Mostrar senha");
            $("#btn-password-visibility").attr("aria-label", "Mostrar senha");
        }
    });

    $("#btn-password-confirmation-visibility").on("click", function(event){
        event.preventDefault();
        if($(this).children().text() === 'visibility') {
            $(this).children().text('visibility_off');
            $("#password-confirmation").prop("type", "text");
            $("#btn-password-confirmation-visibility").prop("title", "Esconder confirmação de senha");
            $("#btn-password-confirmation-visibility").attr("aria-label", "Esconder confirmação de senha");
        } else {
            $(this).children().text('visibility');
            $("#password-confirmation").prop("type", "password");
            $("#btn-password-confirmation-visibility").prop("title", "Mostrar confirmação de senha");
            $("#btn-password-confirmation-visibility").attr("aria-label", "Mostrar confirmação de senha");
        }
    });
})
