$(function(){
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));

    $("#issuing-state").val($("#issuing-state-server").val());

    let validator = $("#form-update").validate({
        rules : {
            "first-name":{required: true},
            "last-name":{required: true},
            "full-name":{required: true},
            "social-name":{required: true},
            "cpf":{required: true, cpfBR: true},
            "birth-date":{required: true},
            "gender":{required: true},
            "pronouns":{required: true},
            "nickname":{required: true},
            "id-number":{required: true},
            "issue-date":{required: true},
            "issuing-authority":{required: true},
            "issuing-state":{required: true},
            "mother-name":{required: true},
            "id-card-file":{required: true, accept: "application/pdf,image/png,image/jpeg", maxsize:2097152},
            "email-personal":{required: true, email:true},
            "phone":{required: true, phone:true},
            "email-institucional":{required: true},
            "cep":{required:true, postalcodeBR:true},
            "street":{required:true},
            "number":{required:true},
            "neighborhood":{required:true},
            "city":{required:true},
            "state":{required:true},
            "address-proof-file":{required: true, accept: "application/pdf,image/png,image/jpeg", maxsize:2097152},
            "bank-code":{required:true, bankCode:true},
            "bank-name":{required:true},
            "account-type":{required:true},
            "branch":{required:true, bankBranch:true},
            "branch-digit":{required:true},
            "account":{required:true},
            "account-digit":{required:true},
            "bank-proof-file":{required: true, accept: "application/pdf,image/png,image/jpeg", maxsize:2097152},
            "emergency-contact-name":{required: true},
            "emergency-contact-kinship":{required: true},
            "emergency-contact-phone":{required: true, phone:true}
        },
        messages:{
            name:{required:"É obrigatório informar o seu nome."},
            email:{required:"É obrigatório informar o seu e-mail.",
                    email:"O endereço de e-mail digitado é inválido!"},
            cpf:{
                required: "É obrigatório informar o seu CPF."
            }
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            $(element).addClass("is-invalid").removeClass("is-valid");
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass("is-invalid").addClass("is-valid");
        },
        errorPlacement:function(error, element) {
            error.insertAfter(element)
        }
    });

    jQuery.validator.addMethod("bankBranch", function(value, element) {
        var phone = value.replace(/[^\d]+/g,'');
        if(phone.length != 4) {
            return false;
        }
        return true;
    }, "Número da agência inválido.");

    jQuery.validator.addMethod("bankCode", function(value, element) {
        var bankCode = value.replace(/[^\d]+/g,'');
        if(bankCode.length != 3) {
            return false;
        }
        return true;
    }, "Código do banco inválido.");

    jQuery.validator.addMethod("phone", function(value, element) {
        var phone = value.replace(/[^\d]+/g,'');
        if(phone.length != 11) {
            return false;
        }
        return true;
    }, "Forneça um número de telefone válido.");

    $("#cpf").mask("999.999.999-99", {autoclear: false});
    $("#cep").mask("99999-999", {autoclear: false});
    $("#phone").mask("(99)99999-9999", {autoclear: false});
    $("#emergency-contact-phone").mask("(99) 9 9999-9999", {autoclear: false});
    $("#bank-code").mask("999", {autoclear: false});
    $("#branch").mask("9999", {autoclear: false});
    $("#branch-digit").mask("9", {autoclear: false});
    $("#account-digit").mask("9", {autoclear: false});

    function cleanCepFields() {
        $("#street").val("");
        $("#neighborhood").val("");
        $("#city").val("");
        $("#state").val("");
    }

    $("#cep").blur(function() {

        let cep = $(this).val().replace(/\D/g, '');

        if (cep != "") {

            let validateCep = /^[0-9]{8}$/;

            if(validateCep.test(cep)) {

                $("#street").val("...");
                $("#neighborhood").val("...");
                $("#city").val("...");
                $("#state").val("...");

                $.getJSON("/cep/"+ cep, function(data) {

                    if(!("error" in data)) {
                        if (!("erro" in data)) {
                            if(data.logradouro === "") {
                                $("#street").val("").prop("readonly", false).removeClass("bg-dark-subtle");
                            } else {
                                $("#street").val(data.logradouro).prop("readonly", true).addClass("bg-dark-subtle");
                            }
                            if(data.bairro === "") {
                                $("#neighborhood").val("").prop("readonly", false).removeClass("bg-dark-subtle");
                            } else {
                                $("#neighborhood").val(data.bairro).prop("readonly", true).addClass("bg-dark-subtle");
                            }
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

    $(".btn-edit-fields").on("click tap", function(event){
        event.preventDefault();
        $("#form-fields-"+$(this).val()+" input").prop("disabled", false);
        $("#form-fields-"+$(this).val()+" select").prop("disabled", false);
        $("#form-fields-"+$(this).val()+" textarea").prop("disabled", false);
        $(".btn-edit-fields").addClass("d-none");
        $("#form-fields-"+$(this).val()+" > :last").removeClass("d-none");
        if($(this).val() === 'personal-data') {
            $("#btn-edit-name").prop("disabled", false);
        }
    });

    $("#btn-edit-name").on("click tap", function(event){
        event.preventDefault();
        $("#full-name-field").addClass("d-none");
        $("#edit-name-fields").removeClass("d-none");
        $("#update-name").val("yes");
        $(".id-card-field").prop("disabled", true);
    })
});