<?php if (! defined ( 'BASEPATH' )) exit ( 'No direct script access allowed' );

class Ofertas extends CI_Controller {
	
	private $urlRetornoPagamento;
	
	/**
	 * Construtor da classe
	 */
	public function __construct() {
		parent::__construct ();
		// DESCOMENTE ISSO SE NAO ESTIVER NO AUTOLOAD
		// $this->load->config('pagseguro');
		// $this->load->library('PagSeguroLibrary');
		
		$this->urlRetornoPagamento = base_url('ofertas/retornoPagamento');
	}

	public function registra_venda() {
		// ....
		// Aqui vc faz todo o registro de sua venda e ao finalizar chama o método de pagamento pagseguro.
		redirect(base_url('ofertas/pagseguro/ID_SUA_VENDA'));
		
		// SE VC QUER UTILIZAR CHECKOUT TRANSPARENTE VIA LIGHTBOX PAGSEGURO, COMMENTE A CHAMADA ACIMA
		// E NA SUA VIEW IMPLEMENTE OS JS A SEGUIR NA ORDEM:
		// jquery-1.7.2.min.js
		// jquery.validate.min.js
		// jquery-forms.js
		// common.js
		// pagamento.js
		
		// COM ISSO FARA UMA CHAMADA AJAX GET e RETORNANDO TUDO OK IRA ABRIR O LIGHTBOX PAGSEGURO.
		
		
		
		// IMPORTANTE:
		// CONFIGURE CORRETAMENTE AS URLS DE RETORNO NO PAGSEGURO.
		
	}
	
	
	//  TODOS OS METODOS ABAIXO DEVEM SER IMPLEMENTADOS.
	
/**
	 * Pagseguro
	 *
	 * @access public
	 * @param int(11) idVenda
	 */
	public function pagseguro($idVenda) {
		if($idVenda) {

			$hasError = false;
			$errorList = array();

			// procura sua transação no banco de dados
			$venda = $this->db->get_where( 'tabela_vendas', array('idVenda' => $idVenda) )->row(); 
			// validações

			if(!is_object($venda)) {
				$hasError = true;
				$errorList[] = array('message' => 'Não foi possível localizar sua compra.');
			}

			if(!$hasError) {
				// Instantiate a new payment request
				$paymentRequest = new PagSeguroPaymentRequest ();

				// Sets the currency
				$paymentRequest->setCurrency ( "BRL" );

				// Sets a reference code for this payment request, it is useful to
				// identify this payment in future notifications.
				$paymentRequest->setReference ( $venda->idVenda );

				// Add an item for this payment request
				$paymentRequest->addItem ( '0001', 'nome do item', 1, number_format ( $venda->valorDevido, 2, '.', '' ) );

				$paymentRequest->setShippingType ( 3 );
				$paymentRequest->setShippingAddress ( str_replace ( '-', '', str_replace ( '.', '', $venda->CEP ) )
						, utf8_decode($venda->endereco)
						, $venda->numero
						, utf8_decode($venda->complemento)
						, utf8_decode($venda->bairro)
						, utf8_decode($venda->cidade)
						, (($venda->sigla)? $venda->sigla : ''), 'BRA' );

				// Sets your customer information.
				$telefone = $venda->telefone1;
// 				$paymentRequest->setSenderName(truncate($userObj->nome, 40));
				$paymentRequest->setSenderEmail($venda->email);
				$paymentRequest->setSenderPhone(substr ( $telefone, 0, 2 ), substr ( $telefone, 2, 8 ));

				// TODO Alterar a URL de RETORNO DE PAGAMENTO SUA URL
				$paymentRequest->setRedirectUrl ( $this->urlRetornoPagamento );
				$paymentRequest->setMaxAge(86400 * 3);

				try {
					$credentials = new PagSeguroAccountCredentials ( $this->config->item ( 'pagseguroAccount' ), $this->config->item ( 'pagseguroToken' ) );
					$url = $paymentRequest->register ( $credentials );
					$parts = parse_url($url);
					parse_str($parts['query'], $query);
					
					
					if($this->input->is_ajax_request()) {
						$data = array('hasError' => FALSE, 'checkoutCode' => $query['code']);
						$this->output
							->set_content_type('application/json')
							->set_output(json_encode($data));
					} else {
						redirect ( $url );
					}
				} catch ( PagSeguroServiceException $e ) {
					$hasError = true;
					$errorList[] = array('message' => 'Ocorreu um erro ao comunicar com o Pagseguro.' .$e->getCode() . ' - ' .  $e->getMessage());
				}
			}
		} else {
			redirect(base_url());
		}
	}

	/**
	 * retornoPagamentoPagseguro
	 *
	 * Recebe o retorno de pagamento da promoção via pagseguro
	 * @access public
	 * @return void
	 */
	public function retornoPagamento() {
		$transaction = false;

		// Verifica se existe a transação
		if ($this->input->get ( 'idTransacao' )) {
			$transaction = self::TransactionNotification ( $this->input->get ( 'idTransacao' ) );
		}

		// Se a transação for um objeto
		if (is_object ( $transaction )) {
			self::setTransacaoPagseguro($transaction);
		}

		// VC PODE COLOCAR A URL QUE VC DESEJA ENVIAR O USUARIO APOS CONCLUIR A COMPRA
		redirect ( base_url() );
	}

	/**
	 * setTransacaoPagseguro
	 *
	 * Seta os status da transação vindas do Pagseguro
	 *
	 * @param array $transaction
	 * @return void
	 */
private function setTransacaoPagseguro($transaction = null) {
		// Pegamos o objeto da transação
		$transactionObj = self::getTransaction ( $transaction );

		// Buscamos a venda
		$venda = $this->db->get_where( 'tabela_vendas', array('idVenda' => $transactionObj ['reference']) )->row();

		// existindo a venda
		if (is_object($venda)) {
			// Aguardando pagamento
			if ($transactionObj ['status'] == 1) {
				// ACAO PARA AGUARDANDO PAGAMENTO
			}


			// Aguardando aprovação
			if ($transactionObj ['status'] == 2) {
				// ACAO PARA GUARDAR TRANSACAO AGUARDANDO APROVACAO
			}

			// Transação paga
			if ($transactionObj ['status'] == 3) {

				// ACAO PARA TRANSACAO PAGA
			}

			// Pagamento cancelado
			if ($transactionObj ['status'] == 7) {
				// ACAO PARA TRANSACAO CANCELADA
			}
		}
	}

	/**
	 * getTransaction
	 *
	 * Método para buscar a transação no pag reguto
	 * @access public
	 * @param PagSeguroTransaction $transaction
	 * @return array
	 */
	public static function getTransaction(PagSeguroTransaction $transaction) {
		return array ('reference' => $transaction->getReference (), 'status' => $transaction->getStatus ()->getValue () );
	}

	/**
	 * NotificationListener
	 *
	 * Recebe as notificações do pagseguro sobre atualização de pagamento.
	 * @access public
	 * @return bool
	 */
	public function NotificationListener() {

		$code = (isset ( $_POST ['notificationCode'] ) && trim ( $_POST ['notificationCode'] ) !== "" ? trim ( $_POST ['notificationCode'] ) : null);
		$type = (isset ( $_POST ['notificationType'] ) && trim ( $_POST ['notificationType'] ) !== "" ? trim ( $_POST ['notificationType'] ) : null);
		$transaction = false;

		if ($code && $type) {

			$notificationType = new PagSeguroNotificationType ( $type );
			$strType = $notificationType->getTypeFromValue ();

			switch ($strType) {

				case 'TRANSACTION' :
					$transaction = self::TransactionNotification ( $code );
					break;

				default :
					LogPagSeguro::error ( "Unknown notification type [" . $notificationType->getValue () . "]" );

			}
		} else {

			LogPagSeguro::error ( "Invalid notification parameters." );
			self::printLog ();
		}

		if (is_object ( $transaction )) {
			self::setTransacaoPagseguro($transaction);
		}

		return TRUE;
	}

	/**
	 * TransactionNotification
	 *
	 * Recupera a transação através de uma notificação
	 * @access private
	 * @param unknown_type $notificationCode
	 * @return Ambigous <a, NULL, PagSeguroTransaction>
	 */

	private static function TransactionNotification($notificationCode) {
		$CI = & get_instance ();
		$credentials = new PagSeguroAccountCredentials ( $CI->config->item ( 'pagseguroAccount' ), $CI->config->item ( 'pagseguroToken' ) );

		try {
			$transaction = PagSeguroNotificationService::checkTransaction ( $credentials, $notificationCode );
		} catch ( PagSeguroServiceException $e ) {
			die ( $e->getMessage () );
		}

		return $transaction;
	}

	/**
	 * Método que registra logs do pagseguro
	 * @access private
	 * @param String $strType
	 */
	private static function printLog($strType = null) {
		$count = 30;
		echo "<h2>Receive notifications</h2>";
		if ($strType) {
			echo "<h4>notifcationType: $strType</h4>";
		}
		echo "<p>Last <strong>$count</strong> items in <strong>log file:</strong></p><hr>";
		echo LogPagSeguro::getHtml ( $count );
	}
}
/* End of file exemplo.php */
/* Location: ./application/controllers/exemplo.php */