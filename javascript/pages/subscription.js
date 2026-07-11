$(function(){
    function getAge(birthDate) {
        var now = new Date();
        [year, month, day] = birthDate.split('-');
        year = parseInt(year);
        month = parseInt(month);
        day = parseInt(day);
        var age;
        if(now.getFullYear() > year) {
            age = now.getFullYear() - year;
            if(month > (now.getMonth()+1) || (month == (now.getMonth()+1) && day > now.getDate())) {
                age--;
            }
        } else {
            age = 0;
        }
        return age;
    }

    $.validator.addMethod("requireOnePronoun", function(value, element) {
        return $('input[type="checkbox"][name^="pronouns-"]:checked').length > 0;
    }, "Selecione pelo menos uma opção de pronome.");

    $.validator.addMethod("phone", function(value, element) {
        var phone = value.replace(/[^\d]+/g,'');
        if(phone.length != 11) {
            return false;
        }
        return true;
    }, "O número de telefone é inválido.");

    $.validator.addMethod("birthDate", function(value, element) {
        var now = new Date();
        now.setFullYear(now.getFullYear()-16);
        if(now.toISOString().split('T')[0] >= value) {
            return true;
        }
        return false;
    }, "Você precisa ter pelo menos 16 anos completos para realizar o cadastro.");

    $("#subscription-form").validate({
        rules: {
            "full-name":{required: true},
            "social-name":{required: true},
            "cpf":{required: true, cpfBR: true},
            "birth-date":{required: true, birthDate: true},
            "nickname":{required: true},
            "gender-identity":{required: true},
            "ethnicity":{required: true},
            "pronouns-he-his": { requireOnePronoun: true },
            "pronouns-she-her": { requireOnePronoun: true },
            "pronouns-they-them": { requireOnePronoun: true },
            "pronouns-others": { requireOnePronoun: true },
            "pronouns-not-specified": { requireOnePronoun: true },
            "pronouns-others-text": {
                required: function(element) {
                    return $("#pronouns-others").is(":checked");
                }
            },
            "email":{required: true, email:true},
            "phone":{required: true, phone:true},
            "phone-confirmation":{required: true, equalTo:"#phone"},
            "cep":{required:true, postalcodeBR:true},
            "declaration-of-reading":{required: true},
            "image-usage-authorization":{required: true},
            "lgpd-authorization":{required: true}
        },
        groups: {
            pronounsGroup: "pronouns-he-his pronouns-she-her pronouns-they-them pronouns-others pronouns-not-specified"
        },
        messages:{
            "full-name":{required:"Informe o seu nome completo."},
            "social-name":{required:"Imporme o seu nome social."},
            "cpf":{required:"Informe o seu CPF.", cpfBR:"Informe um CPF válido."},
            "birth-date":{required:"Informe sua data de nascimento."},
            "nickname":{required:"Informe o nome pelo qual prefere ser chamado."},
            "gender-identity":{required:"Informe seu gênero."},
            "ethnicity":{required:"Informe sua cor/raça."},
            "pronouns-others-text": {required:"Informe seu pronome."},
            "email":{required:"Informe seu e-mail.", email:"Informe um e-mail válido."},
            "phone":{required:"Informe o seu telefone.", phone:"Informe um telefone válido."},
            "phone-confirmation":{required:"Confirme seu telefone", equalTo:"Os números não são iguais."},
            "cep":{required:"Informe o seu CEP", postalcodeBR:"Informe um CEP válido."},
            "declaration-of-reading":{required:"<b>ATENÇÃO!</b> Você precisa concordar com os termos de inscrição!"},
            "lgpd-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar a coleta, armazenamento e tratamento dos seus dados pessoais!"},
            "image-usage-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar o uso da sua imagem e voz!"}
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
        errorPlacement:function(error, element) {
            if (element.attr("name").startsWith("pronouns-")) {
                error.appendTo(element.closest('.row'));
            } else if(element.attr("type") === 'checkbox') {
                error.insertAfter(element.parent());
            } else{
                error.insertAfter(element)
            }
        }
    });

    $("#cpf").mask("999.999.999-99", {autoclear: false});
    $("#phone").mask("(99) 9 9999-9999", {autoclear: false});
    $("#phone-confirmation").mask("(99) 9 9999-9999", {autoclear: false});
    $("#cep").mask("99999-999", {autoclear: false});

    $("#use-social-name-yes").on("click tap", function(event){
        event.stopPropagation();
        $("#social-name-field").removeClass("d-none");
    });

    $("#use-social-name-no").on("click tap", function(event){
        event.stopPropagation();
        $("#social-name-field").addClass("d-none");
        $("#social-name").val("").removeClass("is-valid is-invalid");
    });

    $('#birth-date').on("blur", function(){
        $("#age").val(getAge($(this).val()));
    });

    const $checkboxOthers = $('#pronouns-others');
    const $inputOthersText = $('#pronouns-others-text');
    const $checkboxNotSpecified = $('#pronouns-not-specified');
    const $allOtherCheckboxes = $('input[name^="pronouns-"]').not($checkboxNotSpecified).not($inputOthersText);

    $checkboxOthers.on('change', function() {
        if ($(this).is(':checked')) {
            $inputOthersText.prop('disabled', false).trigger('focus');
        } else {
            $inputOthersText.prop('disabled', true).val('').removeClass('is-valid is-invalid');
        }
    });

    $checkboxNotSpecified.on('change', function() {
        if ($(this).is(':checked')) {
            $allOtherCheckboxes.prop('checked', false).prop('disabled', true).removeClass('is-valid is-invalid');
            $inputOthersText.prop('disabled', true).val('').removeClass('is-valid is-invalid');
        } else {
            $allOtherCheckboxes.prop('disabled', false);
        }
    });

    function cleanCepFields() {
        $("#city").val("");
        $("#state").val("");
    }

    $("#cep").blur(function() {

        let cep = $(this).val().replace(/\D/g, '');

        if (cep != "") {

            let validateCep = /^[0-9]{8}$/;

            if(validateCep.test(cep)) {

                $("#city").val("...");
                $("#state").val("...");

                $.getJSON("/cep/"+ cep, function(data) {

                    if(!("error" in data)) {
                        if (!("erro" in data)) {
                            $("#city").val(data.localidade);
                            $("#state").val(data.uf);
                        } else {
                            cleanCepFields();
                            validator.showErrors({"cep": "O CEP não foi encontrado."});
                        }
                    } else {
                        cleanCepFields();
                        validator.showErrors({"cep": "Houve um erro ao consultar o CEP."});
                    }
                }).fail(function(){
                    cleanCepFields();
                    validator.showErrors({"cep": "Houve um erro ao consultar o CEP."});
                });
            } else {
                cleanCepFields();
            }
        } else {
            cleanCepFields();
        }
    });

})
