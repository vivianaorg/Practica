$(document).ready(function () {
    //Demo code
//    $('.password-container').pschecker();

    //Demo code
    $('.password-container').pschecker({onPasswordValidate: validatePassword, onPasswordMatch: matchPassword});

    var submitbutton = $('.submit-button');
    var errorBox = $('.error');
    errorBox.css('visibility', 'hidden');
    submitbutton.attr("disabled", "disabled");

    //this function will handle onPasswordValidate callback, which mererly checks the password against minimum length
    function validatePassword(isValid) {
        if (!isValid)
            errorBox.css('visibility', 'visible');
        else
            errorBox.css('visibility', 'hidden');
    }
    //this function will be called when both passwords match
    function matchPassword(isMatched) {
        if (isMatched) {
            submitbutton.addClass('unlocked').removeClass('locked');
            submitbutton.removeAttr("disabled", "disabled");
        }
        else {
            submitbutton.attr("disabled", "disabled");
            submitbutton.addClass('locked').removeClass('unlocked');
        }
    }
});