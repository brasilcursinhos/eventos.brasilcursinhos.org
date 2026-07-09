$(function(){

    const clickEventType = (('ontouchstart' in document.documentElement) ? 'tap' : 'click');

    let dayRules = {};
    let dayGroups = [];
    let allRules = {
        "first-name":{required: true},
        "last-name":{required: true},
        "social-name":{required: true},
        "pronouns":{required: true},
        "cpf":{required: true, cpfBR: true},
        "birth-date":{required: true, birthDate: true},
        "email":{required: true, email:true},
        "phone":{required: true, phone:true},
        "emergency-contact-name":{required: true},
        "emergency-contact-kinship":{required: true},
        "emergency-contact-phone":{required: true},
        "school-name":{required:true},
        "school-city":{required:true},
        "school-uf":{required:true},
        "school-grade":{required:true},
        "school-class":{required:true},
        "school-shift":{required:true},
        "school-type":{required:true},
        "school-conclusion-year":{required:true},
        "password":{required: true, securePassword:true, minlength:8, maxlength:20},
        "password-confirmation":{required: true, equalTo:"#password"},
        "declaration-of-reading":{required: true},
        "image-usage-authorization":{required: true},
        "lgpd-authorization":{required: true}
    }
    
    $('.day-checkbox').each(function() {
        let name = $(this).attr('name');
        allRules[name] = { requireOneDay: true };
        dayGroups.push(name);
    });

    $("#registration-form").validate({
        groups: {
            days: dayGroups.join(" ")
        },
        rules : allRules,
        messages:{
            "cpf":{
                required:"É obrigatório informar o seu CPF.",
                cpfBR:"O número de CPF informado é inválido."
            },
            "email":{
                required:"Informe o seu endereço de e-mail.",
                email:"Informe um endereço de e-mail válido."
            },
            "email2":{
                required:"Confirme o seu endereço de e-mail.",
                equalTo:"Os endereços informados não coincidem."
            },
            "password":{
                required: "Informe uma senha.",
                minlength:"Sua senha deve ter no mínimo 8 caracteres",
                maxlength:"Sua senha deve ter no máximo 20 caracteres"
            },
            "password-confirmation":{
                required: "Confirme a sua senha.",
                equalTo:"As senhas não são iguais."
            },
            "birth-date":{
                required: "Informe a sua data de nascimento.",
                min: "Informe uma data maior do que 01/01/1900.",
                max: "Informe uma data anterior a hoje."
            },
            "declaration-of-reading":{required:"<b>ATENÇÃO!</b> Você precisa concordar com os termos de inscrição!"},
            "lgpd-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar a coleta, armazenamento e tratamento dos seus dados pessoais!"},
            "image-usage-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar o uso da sua imagem e voz!"}
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            if ($(element).hasClass("day-checkbox")) {
                $('.day-checkbox').addClass("is-invalid").removeClass("is-valid");
            } else {
                $(element).addClass("is-invalid").removeClass("is-valid");
            }
        },
        unhighlight: function(element, errorClass, validClass) {
            if ($(element).hasClass("day-checkbox")) {
                $('.day-checkbox').removeClass("is-invalid").addClass("is-valid");
            } else {
                $(element).removeClass("is-invalid").addClass("is-valid");
            }
        },
        errorPlacement:function(error, element) {
            if(element.hasClass("day-checkbox")) {
                error.appendTo("#days-error-container");
            } else if(element.attr("type") === 'checkbox') {
                error.insertAfter(element.parent());
            } else{
                error.insertAfter(element)
            }
        }
    });

    jQuery.validator.addMethod("requireOneDay", function(value, element) {
        return $('.day-checkbox:checked').length > 0;
    }, "ATENÇÃO! Selecione pelo menos um dia de aulão para continuar.");

    jQuery.validator.addMethod("phone", function(value, element) {
        var phone = value.replace(/[^\d]+/g,'');
        if(phone.length != 11) {
            return false;
        }
        return true;
    }, "Forneça um número de telefone válido.");

    jQuery.validator.addMethod("birthDate", function(value, element) {
        var now = new Date();
        now.setFullYear(now.getFullYear()-14);
        if(now.toISOString().split('T')[0] >= value) {
            return true;
        }
        return false;
    }, "Você precisa ter pelo menos 14 anos completos para participar.");

    jQuery.validator.addMethod("securePassword", function(value, element) {
    return this.optional(element) || 
      /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/.test(value);
  }, "A senha deve ter pelo menos 1 letra maiúscula, 1 letra minúscula e 1 número.");

    $("#cpf").mask("999.999.999-99", {autoclear: false});
    $("#phone").mask("(99) 9 9999-9999", {autoclear: false});
    $("#emergency-contact-phone").mask("(99) 9 9999-9999", {autoclear: false});

    $("#student-type-studying").on("click tap", function(event){
        event.stopPropagation();
        $("#formed-fields").addClass("d-none");
        $("#studying-fields").removeClass("d-none");
        $("#formed-fields input").each(function(){
            $(this).val("").removeClass("is-valid is-invalid");
        });
    });

    $("#student-type-formed").on("click tap", function(event){
        event.stopPropagation();
        $("#studying-fields").addClass("d-none");
        $("#formed-fields").removeClass("d-none");
        $("#studying-fields input").each(function(){
            $(this).val("").removeClass("is-valid is-invalid");
        });
        $("#studying-fields select").each(function(){
            $(this).val("").removeClass("is-valid is-invalid");
        });
    });

    $("#use-social-name-yes").on("click tap", function(event){
        event.stopPropagation();
        $("#social-name-field").removeClass("d-none");
    });

    $("#use-social-name-no").on("click tap", function(event){
        event.stopPropagation();
        $("#social-name-field").addClass("d-none");
        $("#social-name").val("").removeClass("is-valid is-invalid");
    });

    $("#school-name-select").on("change", function(){
        if($(this).val() === "Outra") {
            $(this).val("").prop("hidden", true).prop("disabled", true).removeClass("is-valid is-invalid");
            $("#school-name-label").attr("for", "school-name-text");
            $("#school-name-text").prop("disabled", false);
            $("#school-name-text-field").removeClass("d-none");
            $("#school-city").val("").removeClass("is-valid is-invalid");
            $("#school-uf").val("").removeClass("is-valid is-invalid");
        } else if($(this).val() === "E.E.B. Apolônio Ireno Cardoso") {
            $("#school-city").val("Balneário Arroio do Silva").addClass("is-valid").removeClass("is-invalid");
            $("#school-uf").val("SC").addClass("is-valid").removeClass("is-invalid");
        } else if($(this).val() === "E.E.B. Manoel Gomes Baltazar") {
            $("#school-city").val("Maracajá").addClass("is-valid").removeClass("is-invalid");
            $("#school-uf").val("SC").addClass("is-valid").removeClass("is-invalid");
        } else if($(this).val() != "") {
            $("#school-city").val("Araranguá").addClass("is-valid").removeClass("is-invalid");
            $("#school-uf").val("SC").addClass("is-valid").removeClass("is-invalid");
        } else {
            $("#school-city").val("").removeClass("is-valid is-invalid");
            $("#school-uf").val("").removeClass("is-valid is-invalid");
        }
    })

    $("#btn-back-school-name-select").on("click tap", function(event){
        event.preventDefault();
        event.stopPropagation();
        $("#school-name-text").val("").prop("disabled", true).removeClass("is-valid is-invalid");
        $("#school-name-text-field").addClass("d-none");
        $("#school-name-label").attr("for", "school-name-select");
        $("#school-name-select").prop("hidden", false).prop("disabled", false);
        $("#school-city").val("").removeClass("is-valid is-invalid");
        $("#school-uf").val("").removeClass("is-valid is-invalid");
    })
})
