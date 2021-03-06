<?php
include_once(DIR_APPLICATION."controller/payment/Mobile_Detect.php");
class ControllerPaymentOPCreditCard extends Controller {
	const PUSH 			= "[PUSH]";
	const BrowserReturn = "[Browser Return]";
	
	public function confirm()
	{
		$this->load->model('checkout/order');
		
		$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('op_creditcard_default_order_status_id'));
	}
	
	protected function index() {
		$detect = new Mobile_Detect(); 
		if($detect->isiOS()){  
			$_SESSION['pages'] = '1';
		}elseif($detect->isMobile()){  
			$_SESSION['pages'] = '1';
		}elseif($detect->isTablet()){ 
			$_SESSION['pages'] = '1'; 
		}else{
			$_SESSION['pages'] = '0';
		}
		
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['button_back'] = $this->language->get('button_back');
		$this->load->model('checkout/order');
		
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		$this->load->library('encryption');
		$this->id = 'payment';
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard.tpl';
		} else {
			$this->template = 'default/template/payment/op_creditcard.tpl';
		}	
		
		$this->render();
	}
	
	public function op_creditcard_form()
	{
		
		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_iframe.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_iframe.tpl';
		} else {
			$this->template = 'default/template/payment/op_creditcard_iframe.tpl';
		}
	
		$this->children = array(
				'common/content_top',
				'common/content_bottom',
				'common/footer',
				'common/header'
		);

		$this->op_creditcard();
	
		$this->response->setOutput($this->render());

	}
	
	
	public function op_creditcard() {
		$this->data['button_confirm'] = $this->language->get('button_confirm');
		$this->data['button_back'] = $this->language->get('button_back');
		$this->load->model('checkout/order');
		$this->model_checkout_order->confirm($this->session->data['order_id'], $this->config->get('op_creditcard_default_order_status_id'));
		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		
		//????????????????????????
		if (!empty($order_info)) {
			
			$this->load->library('encryption');
			
			$this->load->model('payment/op_creditcard');
			$product_info = $this->model_payment_op_creditcard->getOrderProducts($this->session->data['order_id']);
			
			//??????????????????
			$productDetails = $this->getProductItems($product_info);
			//?????????????????????
			$customer_info = $this->model_payment_op_creditcard->getCustomerDetails($order_info['customer_id']);
			
			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$base_url = $this->config->get('config_url');
			} else {
				$base_url = $this->config->get('config_ssl');
			}
			
			
			
			//????????????
			$action = $this->config->get('op_creditcard_transaction');
			$this->data['action'] = $action;
			
			//?????????
			$account = $this->config->get('op_creditcard_account');
			$this->data['account'] = $account;

			//?????????
			$order_number = $order_info['order_id'];
			$this->data['order_number'] = $order_number;
			
			//??????
			$order_amount = $this->currency->format($order_info['total'], $order_info['currency_code'], '', FALSE);
			$this->data['order_amount'] = $order_amount;
			
			//??????
			$order_currency = $order_info['currency_code'];
			$this->data['order_currency'] = $order_currency;
			
			//???3D??????
			$_SESSION['is_3d'] = 0;
			
			//??????????????????3D??????
			if($this->config->get('op_creditcard_3d') == 1){
				//??????????????????3D??????
				$validate_arr = $this->validate3D($order_currency, $order_amount, $order_info);
			}else{
				$validate_arr['terminal'] = $this->config->get('op_creditcard_terminal');
				$validate_arr['securecode'] = $this->config->get('op_creditcard_securecode');
			}
			

			//?????????
			$terminal = $validate_arr['terminal'];
			$this->data['terminal'] = $terminal;
			
			//securecode
			$securecode = $validate_arr['securecode'];
			
			//????????????
			$backUrl = $base_url.'index.php?route=payment/op_creditcard/callback';
			$this->data['backUrl'] = $backUrl;
			
			//?????????????????????
			$noticeUrl = $base_url.'index.php?route=payment/op_creditcard/notice';
			$this->data['noticeUrl'] = $noticeUrl;
			
			//??????
			$order_notes = '';
			$this->data['order_notes'] = $order_notes;
			
			//????????????
			$methods = "Credit Card";
			$this->data['methods'] = $methods;
			
			//????????????
			$billing_firstName = $this->OceanHtmlSpecialChars($order_info['payment_firstname']);
			$this->data['billing_firstName'] = $billing_firstName;
			
			//????????????
			$billing_lastName = $this->OceanHtmlSpecialChars($order_info['payment_lastname']);
			$this->data['billing_lastName'] = $billing_lastName;
			 
			//???????????????
			$billing_email = $this->OceanHtmlSpecialChars($order_info['email']);
			$this->data['billing_email'] = $billing_email;
			 
			//???????????????
			$billing_phone = $order_info['telephone'];
			$this->data['billing_phone'] = $billing_phone;
			 
			//???????????????
			$billing_country = $order_info['payment_country'];
			$this->data['billing_country'] = $billing_country;
			 
			//????????????
			$billing_state = $order_info['payment_zone'];
			$this->data['billing_state'] = $billing_state;
			 
			//???????????????
			$billing_city = $order_info['payment_city'];
			$this->data['billing_city'] = $billing_city;
			 
			//???????????????
			if (!$order_info['payment_address_2']) {
				$billing_address = $order_info['payment_address_1'] ;
			} else {
				$billing_address = $order_info['payment_address_1'] . ',' . $order_info['payment_address_2'];
			}
			$this->data['billing_address'] = $billing_address;
			 
			//???????????????
			$billing_zip = $order_info['payment_postcode'];
			$this->data['billing_zip'] = $billing_zip;
			
			//?????????
			$signValue   = hash("sha256",$account.$terminal.$backUrl.$order_number.$order_currency.$order_amount.$billing_firstName.$billing_lastName.$billing_email.$securecode);
			$this->data['signValue'] = $signValue;
			
			//????????????
			$ship_firstName = $order_info['shipping_firstname'];
			$this->data['ship_firstName'] = $ship_firstName;
			
			//????????????
			$ship_lastName = $order_info['shipping_lastname'];
			$this->data['ship_lastName'] = $ship_lastName;
			
			//???????????????
			$ship_phone = $order_info['telephone'];
			$this->data['ship_phone'] = $ship_phone;
				
			//???????????????
			$ship_country = $order_info['shipping_iso_code_2'];
			$this->data['ship_country'] = $ship_country;
				
			//????????????
			$ship_state = $order_info['shipping_zone'];
			$this->data['ship_state'] = $ship_state;
				
			//???????????????
			$ship_city = $order_info['shipping_city'];
			$this->data['ship_city'] = $ship_city;
				
			//???????????????
			if (!$order_info['shipping_address_2']) {
				$ship_addr = $order_info['shipping_address_1'] ;
			} else {
				$ship_addr = $order_info['shipping_address_1'] . ',' . $order_info['shipping_address_2'];
			}
			$this->data['ship_addr'] = $ship_addr;
				
			//???????????????
			$ship_zip = $order_info['shipping_postcode'];
			$this->data['ship_zip'] = $ship_zip;
			
			//????????????
			$productName = $productDetails['productName'];
			$this->data['productName'] = $productName;
			
			//??????SKU
			$productSku = $productDetails['productSku'];
			$this->data['productSku'] = $productSku;
			
			//????????????
			$productNum = $productDetails['productNum'];
			$this->data['productNum'] = $productNum;
			
			//???????????????
			$cart_info = 'opencart';
			$this->data['cart_info'] = $cart_info;
			
			//API??????
			$cart_api = 'V1.7.1';
			$this->data['cart_api'] = $cart_api;
			
			//??????????????????
			$pages = isset($_SESSION['pages']) ? $_SESSION['pages'] : 0;
			$this->data['pages'] = $pages;
			
			//????????????-?????????????????????
			$ET_REGISTERDATE = empty($customer_info['date_added']) ? 'N/A' : $customer_info['date_added'];
			$this->data['ET_REGISTERDATE'] = $ET_REGISTERDATE;
				
			//????????????-?????????????????????
			$ET_COUPONS = isset($this->session->data['coupon']) ? 'Yes' : 'No';
			$this->data['ET_COUPONS'] = $ET_COUPONS;
			
			
			
			//???????????????oceanpayment???post log
			$filedate = date('Y-m-d');
			$postdate = date('Y-m-d H:i:s');
			$newfile  = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );
			$post_log = $postdate."[POST to Oceanpayment]\r\n" .
					"account = "           .$account . "\r\n".
					"terminal = "          .$terminal . "\r\n".
					"backUrl = "           .$backUrl . "\r\n".
					"noticeUrl = "         .$noticeUrl . "\r\n".
					"order_number = "      .$order_number . "\r\n".
					"order_currency = "    .$order_currency . "\r\n".
					"order_amount = "      .$order_amount . "\r\n".
					"billing_firstName = " .$billing_firstName . "\r\n".
					"billing_lastName = "  .$billing_lastName . "\r\n".
					"billing_email = "     .$billing_email . "\r\n".
					"billing_phone = "     .$billing_phone . "\r\n".
					"billing_country = "   .$billing_country . "\r\n".
					"billing_state = "     .$billing_state . "\r\n".
					"billing_city = "      .$billing_city . "\r\n".
					"billing_address = "   .$billing_address . "\r\n".
					"billing_zip = "       .$billing_zip . "\r\n".
					"ship_firstName = "    .$ship_firstName . "\r\n".
					"ship_lastName = "     .$ship_lastName . "\r\n".
					"ship_phone = "        .$ship_phone . "\r\n".
					"ship_country = "  	   .$ship_country . "\r\n".
					"ship_state = "        .$ship_state . "\r\n".
					"ship_city = "         .$ship_city . "\r\n".
					"ship_addr = "  	   .$ship_addr . "\r\n".
					"ship_zip = "          .$ship_zip . "\r\n".
					"methods = "           .$methods . "\r\n".
					"signValue = "         .$signValue . "\r\n".
					"productName = "       .$productName . "\r\n".
					"productSku = "        .$productSku . "\r\n".
					"productNum = "        .$productNum . "\r\n".
					"cart_info = "         .$cart_info . "\r\n".
					"cart_api = "          .$cart_api . "\r\n".
					"order_notes = "       .$order_notes . "\r\n".
					"ET_REGISTERDATE = "   .$ET_REGISTERDATE . "\r\n".
					"ET_COUPONS = "        .$ET_COUPONS . "\r\n";
			$post_log = $post_log . "*************************************\r\n";
			$post_log = $post_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");
			$filename = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );
			fwrite($filename,$post_log);
			fclose($filename);
			fclose($newfile);
			
			if ($this->request->get['route'] != 'checkout/guest_step_3') {
				$this->data['back'] = HTTPS_SERVER . 'index.php?route=checkout/payment';
			} else {
				$this->data['back'] = HTTPS_SERVER . 'index.php?route=checkout/guest_step_2';
			}
			
			$this->id = 'payment';
			
			//????????????Pay Mode
			if($this->config->get('op_creditcard_pay_mode') == 1){
				//??????Iframe
				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_iframe.tpl')) {
					$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_iframe.tpl';
				} else {
					$this->template = 'default/template/payment/op_creditcard_iframe.tpl';
				}
					
			}else{
				//??????Redirect
				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_form.tpl')) {
					$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_form.tpl';
				} else {
					$this->template = 'default/template/payment/op_creditcard_form.tpl';
				}
					
			}
			
			$this->response->setOutput($this->render());
			
		}else{		
			$this->response->redirect($this->url->link('checkout/cart'));
		}
		

				
		
	}
	
	public function callback() {
			if (isset($this->request->post['order_number']) && !(empty($this->request->post['order_number']))) {
			$this->language->load('payment/op_creditcard');
		
			$this->data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));

			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$this->data['base'] = HTTP_SERVER;
			} else {
				$this->data['base'] = HTTPS_SERVER;
			}
		
			$this->data['charset'] = $this->language->get('charset');
			$this->data['language'] = $this->language->get('code');
			$this->data['direction'] = $this->language->get('direction');
			$this->data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));		
			$this->data['text_response'] = $this->language->get('text_response');
			$this->data['text_success'] = $this->language->get('text_success');
			$this->data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), HTTPS_SERVER . 'index.php?route=checkout/success');
            $this->data['text_success_url'] = HTTPS_SERVER . 'index.php?route=checkout/success';
			$this->data['text_failure_url'] = HTTPS_SERVER . 'index.php?route=checkout/checkout';
			$this->data['text_failure'] = $this->language->get('text_failure');		
			$this->data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), HTTPS_SERVER . 'index.php?route=checkout/checkout');
				
			$this->data['text_order_number']='<font color="green">'.$this->request->post['order_number'].'</font>';
			$this->data['text_result']='<font color="green">'.$this->request->post['payment_status'].'</font>';						
			
			
			//????????????
			$account = $this->config->get('op_creditcard_account');
			$terminal = $this->request->post['terminal'];
			$response_type = $this->request->post['response_type'];
			$payment_id = $this->request->post['payment_id'];
			$order_number = $this->request->post['order_number'];
			$order_currency =$this->request->post['order_currency'];
			$order_amount =$this->request->post['order_amount'];
			$payment_status =$this->request->post['payment_status'];
			$back_signValue = $this->request->post['signValue'];
			$payment_details = $this->request->post['payment_details'];
			$methods = $this->request->post['methods'];
			$payment_country = $this->request->post['payment_country'];
			$order_notes = $this->request->post['order_notes'];
			$card_number = $this->request->post['card_number'];
			$payment_authType = $this->request->post['payment_authType'];
			$payment_risk = $this->request->post['payment_risk'];
			$payment_solutions = $this->request->post['payment_solutions'];
				
			
			//??????????????????????????????????????????
			$getErrorCode = explode(':', $payment_details);
			$ErrorCode = $getErrorCode[0];
			$this->data['op_errorCode'] = $ErrorCode;
			$this->data['payment_details'] = $payment_details;
			$this->data['payment_solutions'] = $payment_solutions;
			
	
			
			//???????????????   ????????????3D??????
			if($terminal == $this->config->get('op_creditcard_terminal')){
				//???????????????
				$securecode = $this->config->get('op_creditcard_securecode');		
				$text_is_3d = '';
			}elseif($terminal == $this->config->get('op_creditcard_3d_terminal')){
				//3D?????????
				$securecode = $this->config->get('op_creditcard_3d_securecode');	
				$text_is_3d = '[3D] ';
			}else{				
				$securecode = '';	
				$text_is_3d = '';
			}
			
			if($this->session->data['op_creditcard_location'] == '1'){
				$data['op_creditcard_locations']  =	$this->session->data['op_creditcard_locations'];
                $data['op_creditcard_location']   = 1;
			}else{
                $data['op_creditcard_location']   = 0;
			}

            if($this->session->data['op_creditcard_entity'] == '1'){
                $data['op_creditcard_entitys']  =	 $this->session->data['op_creditcard_entitys'];
                $data['op_creditcard_entity']   = 1;
			}else{
                $data['op_creditcard_entity']   = 0;
			}

			
		
			
			//????????????		
			$local_signValue = hash("sha256",$account.$terminal.$order_number.$order_currency.$order_amount.$order_notes.$card_number.
					$payment_id.$payment_authType.$payment_status.$payment_details.$payment_risk.$securecode);
			
		
			if($this->config->get('op_creditcard_logs') == 'True'){
				//???????????????????????????
				$this->returnLog(self::BrowserReturn);
			}
			
			

			//?????????????????????
			$pages = isset($_SESSION['pages']) ? $_SESSION['pages'] : 0;
			if($pages == 1){
				$MobileType = '(Mobile)';
			}else{
				$MobileType = '';
			}
			
			
			$message = self::BrowserReturn . $text_is_3d . $MobileType;
			if ($payment_status == 1){           //????????????
				$message .= 'PAY:Success.';
			}elseif ($payment_status == 0){
				$message .= 'PAY:Failure.';
			}elseif ($payment_status == -1){
				if($payment_authType == 1){
					$message .= 'PAY:Success.';
				}else{
					$message .= 'PAY:Pending.';
				}
			}
			$message .= ' | ' . $payment_id . ' | ' . $order_currency . ':' . $order_amount . ' | ' . $payment_details . "\n";
		
			
			$this->load->model('checkout/order');		
			if (strtoupper($local_signValue) == strtoupper($back_signValue)) {     //??????????????????

				if($response_type == 0){		
					//?????????????????????
					if(substr($payment_details,0,5) == 20061){	 
						//?????????????????????(20061)?????????	
						if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl')) {
							$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl';
						} else {
							$this->template = 'default/template/payment/op_creditcard_failure.tpl';
						}
						
					}else{
						if ($payment_status == 1 ){  
							//????????????
							$this->model_checkout_order->update($this->request->post['order_number'], $this->config->get('op_creditcard_success_order_status_id'), $message, FALSE);
							
							unset($this->session->data['coupon']);
							
							$this->data['continue'] = HTTPS_SERVER . 'index.php?route=checkout/success';
							if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_success.tpl')) {
								$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_success.tpl';
							} else {
								$this->template = 'default/template/payment/op_creditcard_success.tpl';
							}
								
						}elseif ($payment_status == -1 ){   
							//??????????????? 
							//?????????????????????
							if($payment_authType == 1){
								$message .= '(Pre-auth)';
								unset($this->session->data['coupon']);
							}
							$this->model_checkout_order->update($this->request->post['order_number'], $this->config->get('op_creditcard_failed_order_status_id'),$message, FALSE);
							
							if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl')) {
								$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl';
							} else {
								$this->template = 'default/template/payment/op_creditcard_failure.tpl';
							}
								
						}else{     
							//????????????
							$this->model_checkout_order->update($this->request->post['order_number'], $this->config->get('op_creditcard_failed_order_status_id'),$message, FALSE);
							
							if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl')) {
								$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl';
							} else {
								$this->template = 'default/template/payment/op_creditcard_failure.tpl';
							}
								
						}
 					}								
				}					
			
			}else {     
				//????????????????????????
				$this->model_checkout_order->update($this->request->post['order_number'], $this->config->get('op_creditcard_failed_order_status_id'),$message, FALSE);	
				
				if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl')) {
					$this->template = $this->config->get('config_template') . '/template/payment/op_creditcard_failure.tpl';
				} else {
					$this->template = 'default/template/payment/op_creditcard_failure.tpl';
				}
			}
		}
		
		$this->response->setOutput($this->render(TRUE), $this->config->get('config_compression'));
		
		unset($data['op_creditcard_location']);
      unset($data['op_creditcard_locations']);
      unset($data['op_creditcard_entity']);
      unset($data['op_creditcard_entitys']);


		
	}
	
	
	
	
	
	public function notice() {
		//?????????????????????XML
		$xml_str = file_get_contents("php://input");
		
		//?????????????????????????????????xml
		if($this->xml_parser($xml_str)){
			$xml = simplexml_load_string($xml_str);
		
			error_reporting(0);
			
			//????????????????????????$_REQUEST
			$_REQUEST['response_type']	  = (string)$xml->response_type;
			$_REQUEST['account']		  = (string)$xml->account;
			$_REQUEST['terminal'] 	      = (string)$xml->terminal;
			$_REQUEST['payment_id'] 	  = (string)$xml->payment_id;
			$_REQUEST['order_number']     = (string)$xml->order_number;
			$_REQUEST['order_currency']   = (string)$xml->order_currency;
			$_REQUEST['order_amount']     = (string)$xml->order_amount;
			$_REQUEST['payment_status']   = (string)$xml->payment_status;
			$_REQUEST['payment_details']  = (string)$xml->payment_details;
			$_REQUEST['signValue'] 	      = (string)$xml->signValue;
			$_REQUEST['order_notes']	  = (string)$xml->order_notes;
			$_REQUEST['card_number']	  = (string)$xml->card_number;
			$_REQUEST['payment_authType'] = (string)$xml->payment_authType;
			$_REQUEST['payment_risk'] 	  = (string)$xml->payment_risk;
			$_REQUEST['methods'] 	  	  = (string)$xml->methods;
			$_REQUEST['payment_country']  = (string)$xml->payment_country;
			$_REQUEST['payment_solutions']= (string)$xml->payment_solutions;
				
				
			//???????????????   ????????????3D??????
			if($_REQUEST['terminal'] == $this->config->get('op_creditcard_terminal')){
				//???????????????
				$securecode = $this->config->get('op_creditcard_securecode');
				$text_is_3d = '';
			}elseif($_REQUEST['terminal'] == $this->config->get('op_creditcard_3d_terminal')){
				//3D?????????
				$securecode = $this->config->get('op_creditcard_3d_securecode');
				$text_is_3d = '[3D] ';
			}else{
				$securecode = '';
				$text_is_3d = '';
			}
			
			
		}
		
		
		if($_REQUEST['response_type'] == 1){
			if($this->config->get('op_creditcard_logs') == 'True'){
				//????????????????????????
				$this->returnLog(self::PUSH);
			}
			
			
			//????????????
			$local_signValue = hash("sha256",$_REQUEST['account'].$_REQUEST['terminal'].$_REQUEST['order_number'].$_REQUEST['order_currency'].$_REQUEST['order_amount'].$_REQUEST['order_notes'].$_REQUEST['card_number'].
					$_REQUEST['payment_id'].$_REQUEST['payment_authType'].$_REQUEST['payment_status'].$_REQUEST['payment_details'].$_REQUEST['payment_risk'].$securecode);
				
			//????????????
			$getErrorCode	= explode(':', $_REQUEST['payment_details']);
			$errorCode      = $getErrorCode[0];
			
			//??????????????????
			if (strtoupper($local_signValue) == strtoupper($_REQUEST['signValue'])) {
			
				$this->load->model('checkout/order');
				
				$message = self::PUSH . $text_is_3d;
				if ($_REQUEST['payment_status'] == 1){           //????????????
					$message .= 'PAY:Success.';
				}elseif ($_REQUEST['payment_status'] == 0){
					$message .= 'PAY:Failure.';
				}elseif ($_REQUEST['payment_status'] == -1){
					if($_REQUEST['payment_authType'] == 1){
						$message .= 'PAY:Success.';
					}else{
						$message .= 'PAY:Pending.';
					}
				}		
				$message .= ' | ' . $_REQUEST['payment_id'] . ' | ' . $_REQUEST['order_currency'] . ':' . $_REQUEST['order_amount'] . ' | ' . $_REQUEST['payment_details'] . "\n";
				
				
				if($errorCode == 20061){
					//?????????????????????(20061)?????????
				}else{
					if ($_REQUEST['payment_status'] == 1 ){
						//????????????
						$this->model_checkout_order->update($_REQUEST['order_number'], $this->config->get('op_creditcard_success_order_status_id'), $message, false);
					}elseif ($_REQUEST['payment_status'] == -1){
						//???????????????
						//?????????????????????
						if($_REQUEST['payment_authType'] == 1){
							$message .= '(Pre-auth)';
						}
						$this->model_checkout_order->update($_REQUEST['order_number'], $this->config->get('op_creditcard_pending_order_status_id'), $message, false);
					}else{
						//????????????
						$this->model_checkout_order->update($_REQUEST['order_number'], $this->config->get('op_creditcard_failed_order_status_id'), $message, false);
					}
				}	
			}
			
			echo "receive-ok";
			
		}
		
	
		
	}
	
	
	
	/**
	 * ??????????????????3D??????
	 */
	public function validate3D($order_currency, $order_amount, $order_info){
	
		//????????????3D??????
		$is_3d = 0;
		//??????3D??????????????????????????????
		$currencies_value = $this->config->get('op_creditcard_currencies_value');
	
		//????????????????????????
		if(isset($currencies_value[$order_currency])){
	
			//??????3D???????????????
			//??????????????????????????????3d?????????
			if($order_amount >= $currencies_value[$order_currency]){
				//??????3D
				$is_3d = 1;
			}
	
		}
	
	
	
		//??????3D?????????????????????
		$countries_3d = $this->config->get('op_creditcard_country_array');

		if(isset($countries_3d)){
			//?????????
			$billing_country_id = $order_info['payment_country_id'];
			//?????????
			$ship_country_id = $order_info['shipping_country_id'];
		
		
			//???????????????????????????3D????????????
			if (in_array($billing_country_id , $countries_3d)){
				$is_3d = 1;
			}
			//???????????????????????????3D????????????
			if (in_array($ship_country_id , $countries_3d)){
				$is_3d = 1;
			}
		}
		
			
	
	
	
	
		if($is_3d ==  0){
	
			//?????????
			$terminal = $this->config->get('op_creditcard_terminal');
			//securecode
			$securecode = $this->config->get('op_creditcard_securecode');
				
		}elseif($is_3d == 1){
				
			//3D?????????
			$terminal= $this->config->get('op_creditcard_3d_terminal');
			//3D securecode
			$securecode = $this->config->get('op_creditcard_3d_securecode');
			//???3D??????
			$_SESSION['is_3d'] = 1;
		}
	
	
		$validate_arr['terminal'] = $terminal;
		$validate_arr['securecode'] = $securecode;
	
		return $validate_arr;
	
	}
	
	
	
	
	/**
	 * return log
	 */
	public function returnLog($logType){
	
		$filedate   = date('Y-m-d');
		$returndate = date('Y-m-d H:i:s');			
		$newfile    = fopen( "oceanpayment_log/" . $filedate . ".log", "a+" );			
		$return_log = $returndate . $logType . "\r\n".
				"response_type = "       . $_REQUEST['response_type'] . "\r\n".
				"account = "             . $_REQUEST['account'] . "\r\n".
				"terminal = "            . $_REQUEST['terminal'] . "\r\n".
				"payment_id = "          . $_REQUEST['payment_id'] . "\r\n".
				"order_number = "        . $_REQUEST['order_number'] . "\r\n".
				"order_currency = "      . $_REQUEST['order_currency'] . "\r\n".
				"order_amount = "        . $_REQUEST['order_amount'] . "\r\n".
				"payment_status = "      . $_REQUEST['payment_status'] . "\r\n".
				"payment_details = "     . $_REQUEST['payment_details'] . "\r\n".
				"signValue = "           . $_REQUEST['signValue'] . "\r\n".
				"order_notes = "         . $_REQUEST['order_notes'] . "\r\n".
				"card_number = "         . $_REQUEST['card_number'] . "\r\n".
				"methods = "    		 . $_REQUEST['methods'] . "\r\n".
				"payment_country = "     . $_REQUEST['payment_country'] . "\r\n".
				"payment_authType = "    . $_REQUEST['payment_authType'] . "\r\n".
				"payment_risk = "        . $_REQUEST['payment_risk'] . "\r\n".
				"payment_solutions = "   . $_REQUEST['payment_solutions'] . "\r\n";
	
		$return_log = $return_log . "*************************************\r\n";			
		$return_log = $return_log.file_get_contents( "oceanpayment_log/" . $filedate . ".log");			
		$filename   = fopen( "oceanpayment_log/" . $filedate . ".log", "r+" );			
		fwrite($filename,$return_log);	
		fclose($filename);	
		fclose($newfile);
	
	}
	
	

	
	/**
	 *  ???????????????xml
	 */
	function xml_parser($str){
		$xml_parser = xml_parser_create();
		if(!xml_parse($xml_parser,$str,true)){
			xml_parser_free($xml_parser);
			return false;
		}else {
			return true;
		}
	}
	
	
	
	
	
	/**
	 * ??????????????????
	 */
	function getProductItems($AllItems){
	
		$productDetails = array();
		$productName = array();
		$productSku = array();
		$productNum = array();
			
		foreach ($AllItems as $item) {
			$productName[] = $item['name'];
			$productSku[] = $item['product_id'];
			$productNum[] = $item['quantity'];
		}
	
		$productDetails['productName'] = implode(';', $productName);
		$productDetails['productSku'] = implode(';', $productSku);
		$productDetails['productNum'] = implode(';', $productNum);
	
		return $productDetails;
	
	}
	
	
	/**
	 * ????????????Html??????????????????
	 */
	function OceanHtmlSpecialChars($parameter){
	
		//??????????????????
		$parameter = trim($parameter);
	
		//??????"?????????,<?????????,>?????????,'?????????
		$parameter = str_replace(array("<",">","'","\""),array("&lt;","&gt;","&#039;","&quot;"),$parameter);
	
		return $parameter;
	
	}
	
}
?>
