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

    jQuery.validator.addMethod("requireOneQuota", function(value, element) {
        return $('#quotas input[value="yes"]:checked').length > 0;
    }, "É obrigatório confirmar pertencer a pelo menos um dos perfis descritos.");

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
    }, "Você precisa ter pelo menos 14 anos para participar.");

    $("#registration-form").validate({
        groups: {
            quotasGroup: "quota-public-school quota-private-school-scholarship quota-low-income quota-indigenous quota-black-or-mixed quota-quilombola quota-pwd"
        },
        rules : {
            "declaration-of-reading":{required: true},
            "image-usage-authorization":{required: true},
            "lgpd-authorization":{required: true},
            "first-name":{required: true},
            "last-name":{required: true},
            "social-name":{required: true},
            "nickname":{required: true},
            "pronouns":{required: true},
            "gender":{required: true},
            "cpf":{required: true},
            "birth-date":{required: true, birthDate: true},
            "email":{required: true},
            "phone":{required: true, phone:true},
            "emergency-contact-name":{required: true},
            "emergency-contact-kinship":{required: true},
            "emergency-contact-phone":{required: true},
            "first-option":{required: true},
            "review-confirmation":{required: true},
            "school-name":{required:true},
            "school-city":{required:true},
            "school-uf":{required:true},
            "school-registration":{required:true},
            "school-grade":{required:true},
            "school-class":{required:true},
            "school-shift":{required:true},
            "school-type":{required:true},
            "school-conclusion-year":{required:true},
            "has-taken-enem":{required:true},
            "has-taken-ufsc":{required:true},
            "has-taken-others-exams":{required:true},
            "university-course":{required: true},
            "university-type":{required: true},
            "work":{required: true},
            "work-time":{required: true},
            "working-hours":{required: true},
            "study-routine":{required: true},
            "study-days":{required: true},
            "study-time":{required: true},
            "route-origin":{required: true},
            "transport-type":{required: true},
            "route-time":{required: true},
            "quota-public-school": { requireOneQuota: true },
            "quota-private-school-scholarship": { requireOneQuota: true },
            "quota-low-income": { requireOneQuota: true },
            "quota-indigenous": { requireOneQuota: true },
            "quota-black-or-mixed": { requireOneQuota: true },
            "quota-quilombola": { requireOneQuota: true },
            "quota-pwd": { requireOneQuota: true }
        },
        messages:{
            "declaration-of-reading":{required:"<b>ATENÇÃO!</b> Você precisa concordar com os termos do edital!"},
            "lgpd-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar a coleta, armazenamento e tratamento dos seus dados pessoais!"},
            "image-usage-authorization":{required:"<b>ATENÇÃO!</b> Você precisa autorizar o uso da sua imagem e voz!"},
            "review-confirmation":{required: "<b>ATENÇÃO!</b> Você precisa confirmar que revisou os dados antes de enviar."},
            "birth-date":{
                required: "Informe a sua data de nascimento.",
                min: "Informe uma data maior do que 01/01/1900.",
                max: "Informe uma data anterior a hoje."},
            name:{required:"É obrigatório informar o seu nome."},
            email:{required:"É obrigatório informar o seu e-mail.",
                    email:"O endereço de e-mail digitado é inválido!"},
            cpf:{
                required: "É obrigatório informar o seu CPF."
            }
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            if ($(element).parents("#quotas").length) {
                $("#quotas input").addClass("is-invalid").removeClass("is-valid");
            } else {
                $(element).addClass("is-invalid").removeClass("is-valid");
            }
        },
        unhighlight: function(element, errorClass, validClass) {
            if ($(element).parents("#quotas").length) {
                $("#quotas input").removeClass("is-invalid").addClass("is-valid");
            } else {
                $(element).removeClass("is-invalid").addClass("is-valid");
            }
        },
        errorPlacement: function(error, element) {
            if (element.parents("#quotas").length) {
                error.insertAfter("#quotas");
            } 
            else if (element.attr("type") === 'checkbox') {
                error.insertAfter(element.parent());
            } 
            else {
                error.insertAfter(element);
            }
        }
    });

    $("#cpf").mask("999.999.999-99", {autoclear: false});
    $("#phone").mask("(99) 9 9999-9999", {autoclear: false});
    $("#emergency-contact-phone").mask("(99) 9 9999-9999", {autoclear: false});

    function fillTheFields() {
        if($('#use-social-name-yes').prop('checked')) {
            $("#review-full-name").val($("#social-name").val());
            
        } else {
            $("#review-full-name").val($("#first-name").val()+" "+$("#last-name").val());
        }
        $("#review-nickname").val($("#nickname").val());
        $("#review-pronouns").val($("#pronouns").val());
        $("#review-cpf").val($("#cpf").val());
        $("#review-birth-date").val($("#birth-date").val());
        $("#review-email").val($("#email").val());
        $("#review-phone").val($("#phone").val());
        $("#review-school-grade").val($("#school-grade").val()+" - "+$("#school-class").val()+" - "+$("#school-shift").val());
        if($("#school-name-select").val() === "Outra") {
            $("#review-school-name").val($("#school-name-text").val());
        } else {
            $("#review-school-name").val($("#school-name-select").val());
        }
        $("#review-school-city").val($("#school-city").val()+" - "+$("#school-uf").val());
        $("#review-conclusion-year").val($("#school-conclusion-year").val());
        $("#review-school-type").val($("#school-type").val());
        let quotas = '';
        if($('#quota-public-school-yes').prop('checked')) {
            quotas += 'Escola pública, ';
        }
        if($('#quota-private-school-scholarship-yes').prop('checked')) {
            quotas += 'Escola privada com bolsa, ';
        }
        if($('#quota-low-income-yes').prop('checked')) {
            quotas += 'Baixa renda, ';
        }
        if($('#quota-indigenous-yes').prop('checked')) {
            quotas += 'Indígena, ';
        }
        if($('#quota-black-or-mixed-yes').prop('checked')) {
            quotas += 'Negro (preto ou pardo), ';
        }
        if($('#quota-quilombola-yes').prop('checked')) {
            quotas += 'Quilombola, ';
        }
        if($('#quota-pwd-yes').prop('checked')) {
            quotas += 'Pessoa com deficiência (PCD), ';
        }
        if(quotas.endsWith(', ')) {
            quotas = quotas.slice(0, -2) + '.';
        }

        $("#review-selected-quotas").text(quotas);
    }

    function renameButtons(section) {
        if(section === "start") {
            $("#btn-previous-section").text("Cancelar");
            if($("#declaration-of-reading").is(":checked")) {
                $("#btn-next-section").text("Próximo");
            } else {
                $("#btn-next-section").text("Iniciar Inscrição");
            }
        } else if (section === "review-and-send") {
            fillTheFields();
            $("#btn-previous-section").text("Anterior");
            $("#btn-next-section").text("Enviar Inscrição");
        } else {
            $("#btn-previous-section").text("Anterior");
            $("#btn-next-section").text("Próximo");
        }
    }

    function validateSections(finalSection){
        if(finalSection === 'review-and-send') {
            fillTheFields();
        }
        var validator = $("#registration-form").validate();
        var currentSection = "start";
        var previousSection = "";
        $("section").each(function(){
            $(this).addClass("d-none");
        });

        do {
            if(previousSection !== "") {
                if(!validator.form()) {
                    break;
                } else{
                    $("#"+previousSection).addClass("d-none");
                }
            }
            $("#"+currentSection).removeClass("d-none");
            previousSection = currentSection;
            currentSection = $("#"+currentSection).next().attr("id");
        } while(previousSection != finalSection);
        
        renameButtons(previousSection); 
    }

    $("#btn-next-section, #btn-previous-section").css({
        "pointer-events": "auto",
        "z-index": "9999",
        "position": "relative"
    });

    $(".btn-section").on("click tap", function(event){
        event.stopPropagation();
        validateSections($(this).val());
    });

    $("#btn-previous-section").on("click tap", function(event){
        event.preventDefault();
        event.stopPropagation();
        let currentSection = $("section:not(.d-none)").attr("id");
        let previousSection = $("#"+currentSection).prev("section").attr("id");
        
        if(previousSection) {
            $("#"+currentSection).addClass("d-none");
            $("#"+previousSection).removeClass("d-none");
            renameButtons(previousSection);
        } else {
            $("form").trigger("reset");
            $.post("/logout");
            window.history.back();
        }
    });


    $("#btn-next-section").on("click tap", function(event){
        event.preventDefault();
        event.stopPropagation();
        let currentSection = $("section:not(.d-none)").attr("id");
        let nextSection = $("#"+currentSection).next("section").attr("id");
        if(nextSection == 'review-and-send') {
            fillTheFields();
        }
        
        var validator = $("#registration-form").validate();
        if(validator.form()) {
            if(nextSection) {
                $("#"+currentSection).addClass("d-none");
                $("#"+nextSection).removeClass("d-none");
                renameButtons(nextSection)
             } else {
                $("#page-content").addClass("d-none");
                $("#loading-message").removeClass("d-none");
                $("#registration-form").submit();
             }
        }
    });

    $('#birth-date').on("blur", function(){
        $("#age").val(getAge($(this).val()));
    });

    $("#student-type-studying").on("click tap", function(event){
        event.stopPropagation();
        $("#review-student-fields").removeClass("d-none");
        $("#review-formed-fields").addClass("d-none");
        $("#formed-fields").addClass("d-none");
        $("#studying-fields").removeClass("d-none");
        $("#formed-fields input").each(function(){
            $(this).val("").removeClass("is-valid is-invalid");
        });
    });

    $("#student-type-formed").on("click tap", function(event){
        event.stopPropagation();
        $("#studying-fields").addClass("d-none");
        $("#review-student-fields").addClass("d-none");
        $("#review-formed-fields").removeClass("d-none");
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