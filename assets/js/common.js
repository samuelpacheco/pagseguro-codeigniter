var desativaLoader = false;
var formActive = null;
var desativaScroll = false;
var timeoutRedirect = 5000;

$(function(){
	
//Ajax setup
	$.ajaxSetup({
    type: 'POST',
    cache:false,
    dataType: 'json',
    contentType : "application/x-www-form-urlencoded; charset=utf-8",
    beforeSend: function() {
      if(formActive !== null) {
        formActive.find('.ctn-error li').remove();
        formActive.find('.ctn-error').removeClass('success');
        formActive.removeClass('error');
        formActive.find('.ctn-error').hide();
      }
    },
    complete: function() {
      if(!desativaScroll && formActive !== null) {
        var body = $('body, html');
        var scrollElement = formActive.position().top;
        body.animate({scrollTop: scrollElement }, 500);
      }
    },
    success: function(data) {
      var k;
      var e;
      var errorList;
      formActive.find('.ctn-error li').remove();
      formActive.find('.ctn-error').removeClass('success');
      formActive.find('.ctn-error').removeClass('error');
      
      errorList = data.errorList;
      if(!data.hasError){
        for (k in errorList){
          e = errorList[k];
          formActive.find('.ctn-error').append('<li>' + e.message + '</li>');
        }
        formActive.find('.ctn-error').addClass('success');
        formActive.find('.ctn-error').show();
        setTimeout(function() { 
          formActive.find('.ctn-error').fadeOut();
          if(data.redirect) {
            window.location = data.redirect;
          }
        }, timeoutRedirect);
      } else {
        for (k in errorList){
          e = errorList[k];
          $('#' + e.field).addClass('error');
          formActive.find('.ctn-error').append('<li>' + e.message + '</li>');
        }
        
        formActive.find('.ctn-error').show();
      }
    }
  });

  //seta os defaults para o jquery.validate
  jQuery.validator.setDefaults({
    debug: false,
    onkeyup: false,
    onclick: false,
    onfocusout: false,
    onfocusin: false,
    wrapper: 'li',
    meta: "validate",
    errorContainer: '#msgError',
    errorLabelContainer: '#msgError',
    submitHandler: function(form) {
      formActive = $(form);
      
      formActive.find('.ctn-error li').remove();
      formActive.find('.ctn-error').removeClass('success');
      
      formActive.ajaxSubmit({
        url: formActive.attr('action'),
        data: formActive.serialize()
      });
      
      return false;
    }
  });
});