$(function(){
    
    $.validator.addMethod("requireOneDietaryRestrictions", function(value, element) {
        return $('#dietary-restrictions-fields input[type="checkbox"]:checked').length > 0;
    }, "Selecione pelo menos uma opção da lista.");

    $.validator.addMethod("phone", function(value, element) {
        var phone = value.replace(/[^\d]+/g,'');
        if(phone.length != 11) {
            return false;
        }
        return true;
    }, "O número de telefone é inválido.");

    const $formEncupRegistration = $("#form-encup-registration");

    $formEncupRegistration.validate({
        rules: {
            "proof-authorization":{required: true, accept: "application/pdf", maxsize:2097152},
            "no-restriction": { requireOneDietaryRestrictions: true },
            "vegan": { requireOneDietaryRestrictions: true },
            "vegetarian": { requireOneDietaryRestrictions: true },
            "lactose-intolerance": { requireOneDietaryRestrictions: true },
            "gluten-intolerance": { requireOneDietaryRestrictions: true },
            "sugar-restriction": { requireOneDietaryRestrictions: true },
            "nut-allergy": { requireOneDietaryRestrictions: true },
            "egg-allergy": { requireOneDietaryRestrictions: true },
            "seafood-allergy": { requireOneDietaryRestrictions: true },
            "cow-milk-allergy": { requireOneDietaryRestrictions: true },
            "other-restriction": { requireOneDietaryRestrictions: true },
            "other-restriction-text": {
                required: function(element) {
                    return $("#other-restriction").is(":checked");
                }
            },
            "emergency-contact-name":{required: true},
            "emergency-contact-kinship":{required: true},
            "emergency-contact-phone":{required: true, phone:true},
            "participates-cup":{required: true},
            "organization-name":{required: true},
            "participates-other-organization":{required: true},
            "participates-affiliated-cup":{required: true},
            "ticket-id":{required: true},
            "review-confirmation":{required: true}
        },
        groups: {
            dietaryRestrictionsGroup: "no-restriction vegan vegetarian lactose-intolerance gluten-intolerance sugar-restriction nut-allergy egg-allergy seafood-allergy cow-milk-allergy other-restriction"
        },
        messages:{
            "emergency-contact-name":{required: "Informe o nome do seu contato de emergência."},
            "emergency-contact-kinship":{required:"Informe o parentesco do contato de emergência."},
            "emergency-contact-phone":{required: "Informe o telefone do seu contato de emergência.", phone:"Informe um telefone válido."},
            "proof-authorization":{
                required: "Anexe a autorização de participação assinada.", accept: "Anexa um arquivo em formato PDF.", maxsize:"Anexe um arquivo de no máximo 2MB."},
            "participates-cup":{required:"Selecione uma opção."},
            "participates-other-organization":{required:"Selecione uma opção."},
            "participates-other-organization":{required: "Selecione uma opção."},
            "organization-name":{required:"Informe o nome do seu cursinho/organização."},
            "ticket-id":{required:"Selecione uma opção de ingresso."},
            "review-confirmation":{required:"Confirme a revisão dos dados de inscrição no evento."}
        },
        errorClass: "invalid-feedback",
        highlight: function(element, errorClass, validClass) {
            if (element.type === "radio") {
                const radios = $('input[name="' + element.name + '"]:visible');
                radios.addClass("is-invalid").removeClass("is-valid");

                // Busca a label de erro pelo ID exato gerado pelo plugin (ex: #ticket-id-error)
                const $errorLabel = $('#' + element.name + '-error');
                
                // Se o erro existir no DOM e houver opções de ingresso visíveis na tela, move a label
                if ($errorLabel.length && radios.length) {
                    const lastRadio = radios.last();
                    $errorLabel.insertAfter(lastRadio.closest('.form-check'));
                }
            } else {
                $(element).addClass("is-invalid").removeClass("is-valid");
            }
        },
        unhighlight: function(element, errorClass, validClass) {
            if (element.type === "radio") {
                const radios = $('input[name="' + element.name + '"]:visible');
                radios.removeClass("is-invalid").addClass("is-valid");
            } else {
                $(element).removeClass("is-invalid").addClass("is-valid");
            }
        },
        errorPlacement: function(error, element) {
            if (element.attr("type") === 'radio') {
                // Filtra para inserir o erro após o último radio VISÍVEL
                const lastRadio = $('input[name="' + element.attr("name") + '"]:visible').last();
                error.insertAfter(lastRadio.closest('.form-check'));
                error.css('display', 'block');
            } else if (element.hasClass('checkbox-dietary-restriction')) {
                error.appendTo(element.closest('.row'));
            } else if(element.attr("type") === 'checkbox') {
                error.insertAfter(element.parent());
            } else {
                error.insertAfter(element);
            }
        }
    });

    $("#emergency-contact-phone").mask("(99) 9 9999-9999", {autoclear: false});
    $("#emergency-contact-phone-2").mask("(99) 9 9999-9999", {autoclear: false});

    const $checkboxOthers = $('#other-restriction');
    const $inputOthersText = $('#other-restriction-text');
    const $checkboxNotSpecified = $('#no-restriction');
    const $allOtherCheckboxes = $('.checkbox-dietary-restriction').not($checkboxNotSpecified).not($inputOthersText);

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

    const $participatesAffiliatedCup = $("#participates-affiliated-cup-field");
    const $afffiliatedCupName = $("#affiliated-cup-name-field");
    const $otherEducationalOrganization = $("#participates-other-organization-field");
    const $otherOrganizationName = $("#other-organization-name-field");
    const $otherOrganizationNameLabel = $("#other-organization-name-label");
    const $ticketsExternalParticipant = $("#tickets-external-participant");
    const $ticketsAffiliatedCupMember = $("#tickets-affiliated-cup-member");

    function clearOrganizationNameFields() {
        $afffiliatedCupName.addClass('d-none').find("select").val('').prop('disabled', true).removeClass('is-valid is-invalid');
        $otherOrganizationName.addClass('d-none').find("input").val('').prop('disabled', true).removeClass('is-valid is-invalid');
    }

    function showExternalParticipantTickets() {
        $ticketsExternalParticipant.removeClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
        $ticketsAffiliatedCupMember.addClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
    }

    $('input[name="participates-cup"]').on('change', function(event) {
        if($(this).val() === 'yes') {
            $participatesAffiliatedCup.removeClass('d-none');
            $otherEducationalOrganization.addClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
            clearOrganizationNameFields();
        } else {
            $otherEducationalOrganization.removeClass('d-none');
            $participatesAffiliatedCup.addClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
            clearOrganizationNameFields();
        }
    });

    $('input[name="participates-affiliated-cup"]').on('change', function(event) {
        if($(this).val() === 'yes') {
            $afffiliatedCupName.removeClass("d-none").find("select").prop('disabled', false);
            $otherOrganizationName.addClass('d-none').find("input").val('').prop('disabled', true).removeClass('is-valid is-invalid');
            $ticketsAffiliatedCupMember.removeClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
            $ticketsExternalParticipant.addClass('d-none').find(":radio").prop("checked", false).removeClass('is-valid is-invalid');
        } else {
            $otherOrganizationNameLabel.text("Nome do seu cursinho:");
            $otherOrganizationName.removeClass('d-none').find("input").prop('disabled', false);
            $afffiliatedCupName.addClass('d-none').find("select").val('').prop('disabled', true).removeClass('is-valid is-invalid');
            showExternalParticipantTickets();
        }
    });

    $('input[name="participates-other-organization"]').on('change', function (event) {
        showExternalParticipantTickets();
        if($(this).val() === 'yes') {
            $otherOrganizationNameLabel.text("Nome da sua organização:");
            $otherOrganizationName.removeClass('d-none').find("input").val('').prop('disabled', false);
        } else {
            $otherOrganizationNameLabel.text("Nome da sua organização:");
            $otherOrganizationName.addClass('d-none').find("input").val('Nenhuma').prop('disabled', false);
        }
    });

    $('input[name="ticket-id"]').on('change', function() {
        if ($(this).is(':checked')) {
            const ticketName = $(this).data('ticket-name');
            const ticketPrice = $(this).data('ticket-price');
            const ticketType = $(this).data('ticket-type');
            
            $('#review-ticket-field').text(ticketName);
            $('#review-amount-field').text('R$ ' + ticketPrice);
            $('#review-ticket-type-field').text(ticketType);
            if(ticketPrice == '0,00') {
                $("#payment-instructions").text("Não é necessário realizar nenhum pagamento. Sua inscrição será confirmada automaticamente.");
            } else if(ticketType.startsWith('Membro de CUP filiado')) {
                $("#payment-instructions").text("Após salvar a sua inscrição serão apresentadas as informações para realizar o pagamento da taxa de inscrição e o envio do comprovante. Você também poderá solicitar inscrição no lote social caso se enquadre nos critérios.");
            } else {
                $("#payment-instructions").text("Após salvar a sua inscrição serão apresentadas as informações para realizar o pagamento da taxa de inscrição e o envio do comprovante.");
            }
        }
    });

    const $btnNextSection = $("#btn-next-section");
    const $btnPreviousSection = $("#btn-previous-section");

    $btnPreviousSection.on("click", function(event){
        event.preventDefault();
        const $activeSection = $('form section').not('.d-none');
        const $previousSection = $activeSection.prev();
        
        if($previousSection.length) {
            
            // Remove o estado de validação visual apenas dos campos que estão vazios
            $activeSection.find('input, select, textarea').each(function() {
                const $input = $(this);
                const name = $input.attr('name');
                
                if (!name) return; // Ignora elementos sem o atributo name

                let isEmpty = false;

                if ($input.is(':radio, :checkbox')) {
                    // Verifica se nenhuma opção daquele grupo está selecionada
                    if ($('input[name="' + name + '"]:checked').length === 0) {
                        isEmpty = true;
                    }
                } else if ($.trim($input.val()) === '') {
                    // Verifica se o campo de texto/select está vazio
                    isEmpty = true;
                }

                if (isEmpty) {
                    // Remove as classes de validação e oculta o label de erro
                    if ($input.is(':radio, :checkbox')) {
                        $('input[name="' + name + '"]').removeClass('is-invalid is-valid');
                    } else {
                        $input.removeClass('is-invalid is-valid');
                    }
                    $('#' + name + '-error').hide();
                }
            });

            $btnNextSection.html("<strong>Próximo</strong>");
            $activeSection.addClass('d-none');
            $previousSection.removeClass('d-none');
            
            if($previousSection.prev().length === 0) {
                $btnPreviousSection.html("<strong>Cancelar</strong>");
            }
        } else {
            location.href = '/participante';
        }
    });

    $btnNextSection.on("click", function(event){
        event.preventDefault();
        if(!$formEncupRegistration.valid()) {
            return;
        }
        const $activeSection = $('form section').not('.d-none');
        const $nextSection = $activeSection.next();
        if($nextSection.length) {
            $btnPreviousSection.html("<strong>Anterior</strong>")
            $activeSection.addClass('d-none');
            $nextSection.removeClass('d-none');
            if($nextSection.next().length === 0) {
                $btnNextSection.html("<strong>Enviar</strong>")
            }
        } else {
            $formEncupRegistration.submit();
        }
    })

})
