// on load

$(function(){
  $('#concluir').click(function() {
  	desativaLoader = true;
  	var redirect = baseUrl + 'ofertas/pagseguro/' + $('#idVenda').val() + '/true';
  	$.getJSON(redirect, function(data){
  		if(!data.hasError) {
  			isOpenLightbox = PagSeguroLightbox({
  					code: data.checkoutCode
  				}, {
  				success : function(transactionCode) {
  					window.location = baseUrl + 'ofertas/retornoPagamento/?idTransacao=' + transactionCode ;
  				},
  				abort : function() {
  					
  				}
				});
				if (!isOpenLightbox){
					location.href="https://pagseguro.uol.com.br/v2/checkout/payment.html?code="+code;
				}
  		}
  	});
  });
});