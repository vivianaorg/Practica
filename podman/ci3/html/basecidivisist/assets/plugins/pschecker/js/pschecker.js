(function ($) {
    $.fn.extend({
        pschecker: function (options) {
            var settings = $.extend({minlength: 8, maxlength: 16, onPasswordValidate: null, onPasswordMatch: null}, options);
            return this.each(function () {
                var wrapper = $('.password-container');
                var password = $('.strong-password:eq(0)', wrapper);
                var cPassword = $('.strong-password:eq(1)', wrapper);
                var nivel_clave = 0;
                cPassword.removeClass('no-match');
                password.keyup(validatePassword).blur(validatePassword).focus(validatePassword);
                cPassword.keyup(validatePassword).blur(validatePassword).focus(validatePassword);
                function validatePassword() {
                    var pstr = password.val().toString();
                    var meter = $('.meter');
                    var indicador_clave = $('.indicador_clave');
                    var password_error = $('#diffpasswords'); 
                    meter.html("");
                    //fires password validate event if password meets the min length requirement
                    if (settings.onPasswordValidate != null) {
                        settings.onPasswordValidate(pstr.length >= settings.minlength);
                    }
                    if (pstr.length < settings.maxlength) {
                        meter.removeClass('strong').removeClass('medium').removeClass('week');
                        indicador_clave.html(
                                "<div class='progress progress-xs active'>" +
                                "<div class='progress-bar progress-bar-striped progress-bar-info' style='width: 0%'></div>" +
                                "</div>" +
                                "<p class='text-muted'>Nivel de Seguridad</p>"
                                );
                        password_error.html("");
                    }
                    if (pstr.length > 0) {
                        var rx = new RegExp(/^(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{7,30}$/);
                        if (rx.test(pstr)) {
                            meter.addClass('strong');
                            meter.html("Strong");
                            indicador_clave.html(
                                    "<div class='progress progress-xs active'>" +
                                    "<div class='progress-bar progress-bar-striped progress-bar-green' style='width: 100%'></div>" +
                                    "</div>" +
                                    "<p class='text-green'>Contraseña Fuerte</p>"
                                    );
                        }
                        else {
                            var alpha = containsAlpha(pstr);
                            var number = containsNumeric(pstr);
                            var upper = containsUpperCase(pstr);
                            var special = containsSpecialCharacter(pstr);
                            var result = alpha + number + upper + special;
                            nivel_clave = result;
                            
                            if (result > 2) {
                                meter.addClass('medium');
                                meter.html("Medium");
                                indicador_clave.html(
                                        "<div class='progress progress-xs active'>" +
                                        "<div class='progress-bar progress-bar-striped progress-bar-warning' style='width: 66%'></div>" +
                                        "</div>" +
                                        "<p class='text-yellow'>Contraseña Normal</p>"
                                        );
                            }
                            else {
                                meter.addClass('week');
                                meter.html("Week");
                                indicador_clave.html(
                                        "<div class='progress progress-xs active'>" +
                                        "<div class='progress-bar progress-bar-striped progress-bar-red' style='width: 33%'></div>" +
                                        "</div>" +
                                        "<p class='text-red'>Contraseña Debil</p>"
                                        );
                            }
                        }
                        if (cPassword.val().toString().length > 0) {
                            if (pstr == cPassword.val().toString()) {
                                cPassword.removeClass('no-match');     
                                if (settings.onPasswordMatch != null && nivel_clave > 2){
                                    $('#level').val('1');
                                    settings.onPasswordMatch(true);
                                    password_error.html("");
                                } else {
                                    password_error.html("La contraseña no cumple el nivel mínimo de seguridad");
                                }
                            }
                            else {
                                $('#level').val('0');
                                cPassword.addClass('no-match');
                                password_error.html("Las contraseñas no coinciden");
                                if (settings.onPasswordMatch != null)
                                    settings.onPasswordMatch(false);
                            }
                        }
                        else {
                            $('#level').val('0');
                            password_error.html("Las contraseñas no coinciden");
                            cPassword.addClass('no-match');                            
                            if (settings.onPasswordMatch != null)
                                settings.onPasswordMatch(false);
                        }
                    }
                }

                function containsAlpha(str) {
                    var rx = new RegExp(/[a-z]/);
                    if (rx.test(str))
                        return 1;
                    return 0;
                }

                function containsNumeric(str) {
                    var rx = new RegExp(/[0-9]/);
                    if (rx.test(str))
                        return 1;
                    return 0;
                }

                function containsUpperCase(str) {
                    var rx = new RegExp(/[A-Z]/);
                    if (rx.test(str))
                        return 1;
                    return 0;
                }
                function containsSpecialCharacter(str) {

                    var rx = new RegExp(/[\W]/);
                    if (rx.test(str))
                        return 1;
                    return 0;
                }


            });
        }
    });
})(jQuery);
