$(function(){
    let form = $("#login-form").validate({
        rules : {
            "password":{required: true, minlength: 8, maxlength: 20},
            "cpf":{required: true, cpfBR: true}
        },
        messages:{
            "password":{required: "É obrigatório informar a sua senha!",
            minlength: "Informe pelo menos 8 caracteres!", maxlength: "Informe menos de 20 caracteres!"},
            "cpf":{required: "É obrigatório informar o seu CPF!",
            cpfBR: "O número de CPF informado é inválido!"}
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
        errorPlacement:function(error, element) {
            if(element.attr("id") === 'password') {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element)
            }
        }
    });
    $("#cpf").mask("999.999.999-99", {autoclear: false});
    $("#cpf-registration").mask("999.999.999-99", {autoclear: false});
    $("#password").on('keydown', function(){
        if($("#passwordValidated").val() === "true") {
            form.element("#password");
        }
    });
    $("#cpf").on('keydown', function(){
        if($("#cpfValidated").val() === "true") {
            form.element("#cpf");
        }
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

    $("#initial-registration-form").validate({
        rules : {
            "cpf-registration":{required: true, cpfBR: true},
            "email":{required: true, email:true},
            "email2":{required: true, email:true, equalTo: "#email"},
        },
        messages:{
            "cpf-registration":{
                required:"É obrigatório informar o seu CPF.",
                cpfBR:"O número de CPF informado é inválido."
            },
            "email":{
                required:"Informe o seu endereço de e-mail.",
                email:"Informe um endereço de e-mail válido."
            },
            "email2":{
                required:"Confirme o seu endereço de e-mail.",
                email:"Informe um endereço de e-mail válido.",
                equalTo:"Os endereços informados não coincidem."
            }
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
    });

    $("#cpf").mask("999.999.999-99", {autoclear: false});

    $("#btn-start-registration").on("click tap", function(){
        $("#initial-registration-form").submit();
    });

    $(".btn-cancel").on("click tap", function(){
        $("input").removeClass("is-valid is-invalid").val("");
        $(".invalid-feedback").remove();
    });
    
})
