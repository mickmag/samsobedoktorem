<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

require_once(dirname(__FILE__)."/UniModul.php");
require_once(dirname(__FILE__)."/component/TpUtils.php");
require_once(dirname(__FILE__)."/component/helpers/TpMerchantHelper.php");
require_once(dirname(__FILE__)."/component/TpReturnedPayment.php");
require_once(dirname(__FILE__)."/component/helpers/TpDataApiHelper.php");

class UniThePayConfig {
	public $merchantId;
	public $accountId;
	public $password;
	public $dataApiPassword;
	public $isTest;
	public $supportedCurrencies;
	public $sendEetAmounts;

	public $convertToCurrencyIfUnsupported; //jedna mena
	public $subMethodsSelection; //array submetod
}


class UniThePay extends UniModul {

	public $uniModulProperties = array('HonorsShopOrderNumberIsAlpha'=>true); // ThePay v OrderId může mít i písmena

	public function __construct($configSetting, $subMethod, $name="ThePay") {
		parent::__construct($name, $configSetting, $subMethod);
		$this->setConfigFromData($configSetting);

		$this->subMethods = $this->subMethods += array('CreditCard'=>21, 'FerBuy'=>20, 'SuperCash'=>5, 'EPlatby'=>1, 'MojePlatba'=>11, 'MPenize'=>12, 'GEMoney'=>13, 'FioBank'=>17, 'CSOB'=>19, 'CeskaSporitelna'=>23, 'SberBank'=>26, 'Zuno'=>27, 'EquaBank'=>22, 'UniCredit'=>28, 'Prevod'=>18, 'BitCoin'=>29);
	}

	public function setConfigFromData($configSetting) {
		$this->config = new UniThePayConfig();
		if ($configSetting != null && $configSetting->configData != null) {
			$configData = $configSetting->configData;
			$this->config->isTest = trim($configData['isTest']);
			$this->config->merchantId = trim($configData['merchantId']);
			$this->config->accountId = trim($configData['accountId']);
			$this->config->password = trim($configData['password']);
			$this->config->dataApiPassword = trim($configData['dataApiPassword']);
			$this->config->supportedCurrencies = trim($configData['supportedCurrencies']);
			$this->config->convertToCurrencyIfUnsupported = $configSetting->configData['convertToCurrencyIfUnsupported'];
			$this->config->subMethodsSelection = explode(' ',$configSetting->configData['subMethodsSelection']);
			if (isset($configData['sendEetAmounts'])) {
				$this->config->sendEetAmounts = trim($configData['sendEetAmounts']);
			}
		}
	}

	public function getConfigInfo($language='en') {

		$d = $this->dictionary;
		$d->setDefaultLanguage($language);


		$configInfo = new ConfigInfo();

		$configFields = array();

		$configFields[] = create_initialize_object('ConfigField', array('name'=>'isTest', 'label'=>$d->get('isTest'), 'type'=>ConfigFieldType::$choice, 'choiceItems'=>array(1=>$d->get('yes'), 0=>$d->get('no'))));
		$configFields[] = create_initialize_object('ConfigField', array('name'=>'merchantId', 'label'=>$d->get('merchantId'), 'type'=>ConfigFieldType::$text));
		$configFields[] = create_initialize_object('ConfigField', array('name'=>'accountId', 'label'=>$d->get('accountId'), 'type'=>ConfigFieldType::$text));
		$configFields[] = create_initialize_object('ConfigField', array('name'=>'password', 'label'=>$d->get('password'), 'type'=>ConfigFieldType::$text));
		$configFields[] = create_initialize_object('ConfigField', array('name'=>'dataApiPassword', 'label'=>$d->get('dataApiPassword'), 'type'=>ConfigFieldType::$text));
		$configFields[] = create_initialize_object('ConfigField', array('name'=>'supportedCurrencies', 'label'=>$d->get('supportedCurrencies'), 'type'=>ConfigFieldType::$text));


		$configField = new ConfigField();
		$configField->name = 'convertToCurrencyIfUnsupported';
		$configField->label = $d->get('convertToCurrencyIfUnsupported');
		$configField->type = ConfigFieldType::$text;
		$configFields[]=$configField;

		$configField = new ConfigField();
		$configField->name = 'orderStatusSuccessfull';
		$configField->label = $d->get('orderStatusSuccessfull');
		$configField->type = ConfigFieldType::$orderStatus;
		$configFields[]=$configField;

		$configField = new ConfigField();
		$configField->name = 'orderStatusPending';
		$configField->label = $d->get('orderStatusPending');
		$configField->type = ConfigFieldType::$orderStatus;
		$configFields[]=$configField;

		$configField = new ConfigField();
		$configField->name = 'orderStatusFailed';
		$configField->label = $d->get('orderStatusFailed');
		$configField->type = ConfigFieldType::$orderStatus;
		$configFields[]=$configField;

		$configField = new ConfigField();
		$configField->name = 'subMethodsSelection';
		$configField->label = $d->get('subMethodsSelection');
		$configField->type = ConfigFieldType::$subMethodsSelection;
		$configField->choiceItems = array();  //ne-povolime obecnou submetodu
		$configFields[]=$configField;



		$configInfo->configFields = $configFields;
		return $configInfo;
	}

	protected $subMethods = array(); // dosazeno v konstruktoru

	public function getSubMethods() {
		return array_keys($this->subMethods);
	}

	public function queryPrePayGWInfo($orderToPayInfo) {
		if ($orderToPayInfo->subMethod==null) {  //fix pro subModuly
			$orderToPayInfo->subMethod = $this->subMethod;
		}

		list($isPossible, $newcur, $newtotal, $forexMessage, $forexNote, $orderReplyStatusFail) = $this->fixCurrency($orderToPayInfo);

		if ($orderToPayInfo->subMethod===null) {  // bez === nefunguji adaptery co chteji seznam metod
			$isPossible = false;
		}

		if ($orderToPayInfo->amount == 0) {  // pokud je kosik prazdny, tak preskocime dotaz na dostupne metody
			$isPossible = false;
		}

		$d = $this->dictionary;
		$prePayGWInfo = new PrePayGWInfo();
		$methodNameKey = ($orderToPayInfo->subMethod == '') ? 'payment_method_name' : 'submethod_name_'.$orderToPayInfo->subMethod;
		$prePayGWInfo->paymentMethodName = $this->dictionary->get($methodNameKey, $orderToPayInfo->language);
		$prePayGWInfo->forexMessage = $forexMessage;

		if ($isPossible) { // overeni online jake metody jsou pouzitelne

			if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}

			if (isset($_SESSION['UniThePay_payTypes'])) {
				$possiblePayTypes = $_SESSION['UniThePay_payTypes'];    // nacteni z cache - pro ruzne submoduly stejneho modulu
			} else {
				try {
					$thepayConfig = $this->getThepayConfig();
					$tpApi = new TpDataApiHelper();
					$this->logger->writeLogNoNewLines("getPaymentMethods call: only active, config: ".print_r($thepayConfig, true));
					$methodsResp = $tpApi->getPaymentMethods($thepayConfig, true);
					$this->logger->writeLogNoNewLines("getPaymentMethods reply: ".print_r($methodsResp, true));

					$possiblePayTypes = array();
					foreach($methodsResp->getMethods() as $tpMethod) {
						if ($tpMethod->getActive()) {
							$possiblePayTypes[] = array_search($tpMethod->getId(), $this->subMethods);
						}
					}
					$_SESSION['UniThePay_payTypes'] = $possiblePayTypes;
				} catch (Exception $e) {
					user_error($e);
				}
			}
			if ($orderToPayInfo->currency != 'CZK') {
				$possiblePayTypes = array_intersect($possiblePayTypes, array('CreditCard'));
			}

			if ($orderToPayInfo->subMethod != null) {
				if (in_array($orderToPayInfo->subMethod, $possiblePayTypes)) {
					$prePayGWInfo->subMethods = array($orderToPayInfo->subMethod);
				} else {
					$prePayGWInfo->subMethods = array();
					$isPossible = false;
				}
			} else {
				$prePayGWInfo->subMethods = array_intersect($this->config->subMethodsSelection, $possiblePayTypes);
			}

		}

		if (!empty($GLOBALS['UniModul_TrackDisplayPaymentOption'])) {
			$this->logger->writeLog("queryPrePayGWInfo (".$this->subMethod."): isPossible = " . ($isPossible?"true":"false"));
		}

		$prePayGWInfo->isPossible = $isPossible;
		return $prePayGWInfo;
	}


	function gatewayOrderRedirectAction($orderToPayInfo) {
		$this->logger->writeLog("NEW_ORDER");
		$vychoziOrderToPayInfo = unserialize(serialize($orderToPayInfo));
		if ($orderToPayInfo->subMethod==null) {  //fix pro subModuly
			$orderToPayInfo->subMethod = $this->subMethod;
		}
		$currencySupported = $orderToPayInfo->currency == 'CZK' || $orderToPayInfo->subMethod == 'CreditCard' && strpos($this->config->supportedCurrencies, $orderToPayInfo->currency)!==false ;
		list($isPossible, $newcur, $newtotal, $forexMessage, $forexNote, $orderReplyStatusFail) = $this->fixCurrency($orderToPayInfo, $currencySupported);

		$uniModulData = null;

		if ($orderToPayInfo->subMethod==null) {
			$isPossible = false;

			$orderReplyStatusFail = new OrderReplyStatus();
			$orderReplyStatusFail->orderStatus = OrderStatus::$failedRetriable;
			$orderReplyStatusFail->shopOrderNumber = $orderToPayInfo->shopOrderNumber;
			//$orderReplyStatusFail->gwOrderNumber = $gwOrderNumber;
			$orderReplyStatusFail->shopPairingInfo = $orderToPayInfo->shopPairingInfo;
			$orderReplyStatusFail->uniAdapterData = $orderToPayInfo->uniAdapterData;
			$orderReplyStatusFail->resultText = 'ThePay nema obecnou metodu';

			user_error('ThePay nema obecnou metodu');
		}

		if (!$isPossible) {
			$transactionPK = $this->writeOrderToDb($orderToPayInfo->shopOrderNumber, $orderToPayInfo->shopPairingInfo, null, $forexNote, $orderReplyStatusFail->orderStatus, null, $orderToPayInfo->uniAdapterData, $uniModulData);
			$this->logger->writeLog("CANNOT SEND ORDER ".print_r($orderToPayInfo, true)."   resultText:".$orderReplyStatusFail->resultText);

			$redirectActionFail = new RedirectAction();
			$redirectActionFail->orderReplyStatus = $orderReplyStatusFail;
			return $redirectActionFail;
		}

		$transactionPK = $this->writeOrderToDb($orderToPayInfo->shopOrderNumber, $orderToPayInfo->shopPairingInfo, null, $forexNote, null, $orderToPayInfo->uniAdapterData, $uniModulData);


		$isShopOrderNumberOkSpecific = preg_match('/^[0-9]{1,10}$/', $orderToPayInfo->shopOrderNumber);

		$description = '';
		if ($orderToPayInfo->shopOrderNumber != '' && !$isShopOrderNumberOkSpecific) {
			$description .= "(Ord.No.:".$orderToPayInfo->shopOrderNumber.") ";
		}

		$description .= $orderToPayInfo->description;
		$description = mb_substr($description, 0, 100, 'UTF-8');

		$thepayConfig = $this->getThepayConfig();
		try {
			$tpPayment = new TpPayment($thepayConfig);

			$tpPayment->setValue($newtotal);
			$tpPayment->setCurrency($newcur);

			$tpPayment->setDescription($description);
			$tpPayment->setMerchantData($orderToPayInfo->shopPairingInfo);
			$tpPayment->setReturnUrl($orderToPayInfo->replyUrl);
			$tpPayment->setBackToEshopUrl($orderToPayInfo->replyUrl . (strpos($orderToPayInfo->replyUrl, '?')===false ? '?' : '&') . "backToEshop=1");

			if (session_status() == PHP_SESSION_NONE) {
				session_start();
			}

			$_SESSION['UniThePay_backToEshop_shopPairingInfo'] = $orderToPayInfo->shopPairingInfo;
			$tpPayment->setCustomerEmail($orderToPayInfo->customerData->email);

			if ($isShopOrderNumberOkSpecific) {
				$tpPayment->setMerchantSpecificSymbol($orderToPayInfo->shopOrderNumber);
			}

			/*

			$payargs = array(
				'session_id'=>	$gwOrderNumber,
				'amount' 	=>	$amount,
				'desc'		=>	$description,
				'first_name'=>	$orderToPayInfo->customerData->first_name,
				'last_name'	=>	$orderToPayInfo->customerData->last_name,
				'email'		=>	$orderToPayInfo->customerData->email,
				'order_id'	=>	$orderToPayInfo->shopOrderNumber ? $orderToPayInfo->shopOrderNumber : $gwOrderNumber,   // v ABO exportu z ThePay není vidět session_id, tak v eshopech kde order není k dispozici sem dam to co do session

				// nepovinne
				'language'	=>	$orderToPayInfo->language == 'cs' ? 'cs' : 'en',  //ceske ThePay umi jen cs a en
				'street'	=>	$orderToPayInfo->customerData->street,
				'street_hn'	=>	$orderToPayInfo->customerData->houseNumber,
				'city'	=>	$orderToPayInfo->customerData->city,
				'post_code'	=>	$orderToPayInfo->customerData->post_code,
				'phone'	=>	$orderToPayInfo->customerData->phone,

				// TODO
				//	$country;    muselo by se ejak kontrolovat zatim nepouziju http:www.chemie.fu-berlin.de/diverse/doc/ISO_3166.html)
			);
			*/

			if ($orderToPayInfo->subMethod != null && $orderToPayInfo->subMethod != '') {
				$tpPayment->setMethodId($this->subMethods[$orderToPayInfo->subMethod]);
			}


			// eet
			if ($this->config->sendEetAmounts) {
				$eetCastky = $this->getEetRozdeleni($orderToPayInfo);
				if ($eetCastky == null) {
					$orderReplyStatus = new OrderReplyStatus();
					$orderReplyStatus->orderStatus = OrderStatus::$failedFinal;
					$orderReplyStatus->resultText = "Chyba dane EET";
					$orderReplyStatus->shopOrderNumber = $orderToPayInfo->shopOrderNumber;
					$orderReplyStatus->shopPairingInfo = $orderToPayInfo->shopPairingInfo;
					$orderReplyStatus->uniAdapterData = $orderToPayInfo->uniAdapterData;
					$redirectAction = new RedirectAction();
					$redirectAction->orderReplyStatus = $orderReplyStatus;
					$this->logger->writeLog("Chyba rozpadu cen na danova pasma orderStatus=".$orderReplyStatus->orderStatus);
					user_error("Chyba dane EET");
					return $redirectAction;
				}

				$this->logger->writeLog("Rozpad cen pro EET v CZK pro ThePay: ".print_r($eetCastky, true)."\n Vychozi kosik celkem ".$vychoziOrderToPayInfo->amount." ".$vychoziOrderToPayInfo->currency.":".print_r($vychoziOrderToPayInfo->cartItems, true));

				$tpEetDph = new TpEetDph();
				$tpEetDph->setZaklNepodlDph($eetCastky->zakl_nepodl_dph);
				$tpEetDph->setZaklDan1($eetCastky->zakl_dan1);
				$tpEetDph->setDan1($eetCastky->dan1);
				$tpEetDph->setZaklDan2($eetCastky->zakl_dan2);
				$tpEetDph->setDan2($eetCastky->dan2);
				$tpEetDph->setZaklDan3($eetCastky->zakl_dan3);
				$tpEetDph->setDan3($eetCastky->dan3);
				$tpPayment->setEetDph($tpEetDph);
			}


			$redirectAction = new RedirectAction();
			$redirectAction->redirectUrl = $thepayConfig->gateUrl . '?' . $this->buildQuery($tpPayment);
			if (in_array($orderToPayInfo->subMethod, array('SuperCash', 'Prevod', 'GEMoney', 'FioBank', 'CSOB', 'EquaBank', 'SberBank' ))) { // okamzite nastaveni pendingu pro offline metody, ktere nevraci prohlizec na vysledkovou stranu, ale na homepage
				$orderReplyStatus = new OrderReplyStatus();
				$orderReplyStatus->orderStatus = OrderStatus::$pending;
				$orderReplyStatus->resultText = null;
				$orderReplyStatus->gwOrderNumber = null;
				$orderReplyStatus->shopOrderNumber = $orderToPayInfo->shopOrderNumber;
				$orderReplyStatus->shopPairingInfo = $orderToPayInfo->shopPairingInfo;
				$orderReplyStatus->forexNote = $forexNote;
				$orderReplyStatus->uniAdapterData = $orderToPayInfo->uniAdapterData;
				$this->logger->writeLog("Immediate status orderStatus=".$orderReplyStatus->orderStatus);
				$redirectAction->orderReplyStatus = $orderReplyStatus;
			}
			$this->logger->writeLog("MAKING_ORDER_URL ".$redirectAction->redirectUrl."   ".$_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']);
		} catch (Exception $e) {
			user_error($e);
		}
		return $redirectAction;
	}

	function buildQuery($tpPayment, $args = array()) {
		$out = array_merge(
			$tpPayment->getArgs(), // Arguments of the payment
			$args, // Optional helper arguments
			array("signature" => $tpPayment->getSignature()) // Signature
		);

		$str = array();
		foreach ($out as $key=>$val) {
			$str[] = rawurlencode($key)."=".rawurlencode($val);
		}
		return implode("&", $str);
		//return implode("&amp;", $str);
	}


	public function gatewayReceiveReply($language='en') {
		$this->logger->writeLog("REPLY   ".$_SERVER["QUERY_STRING"]);

		BeginSynchronized();
		try {
			$backToEshop = !empty($_GET['backToEshop']);
			if (!$backToEshop) {
				$thepayConfig = $this->getThepayConfig();
				$tpReturnedPayment = new TpReturnedPayment($thepayConfig);
				$tpReturnedPayment->verifySignature();
				$result = $tpReturnedPayment->getStatus();

				if (!$this->config->isTest) {
					// overeni jeste pres API
					$thepayConfig = $this->getThepayConfig();
					$tpApi = new TpDataApiHelper();
					$this->logger->writeLogNoNewLines("getPaymentState call: paymentId: ".$tpReturnedPayment->getPaymentId().", config: ".print_r($thepayConfig, true));
					$apiState = $tpApi->getPaymentState($thepayConfig, $tpReturnedPayment->getPaymentId());
					$this->logger->writeLogNoNewLines("getPaymentState reply: ".print_r($apiState, true));
					if ($result != $apiState->getState()) {
						$this->logger->writeLog("POZOR: NESOUHLASI Stav z Query stringu (".$result.") s API (".$apiState->getState()."), pouziji ten z API");
					}
					$result = $apiState->getState();
				}

				switch($result){
					case TpReturnedPayment::STATUS_OK:
					case TpReturnedPayment::STATUS_CARD_DEPOSIT:
						$orderStatus = OrderStatus::$successful;
						break;
					case TpReturnedPayment::STATUS_CANCELED:
						$orderStatus = OrderStatus::$failedFinal;
						break;
					case TpReturnedPayment::STATUS_ERROR:
						$orderStatus = OrderStatus::$failedFinal;
						break;
					case TpReturnedPayment::STATUS_UNDERPAID:
						$orderStatus = OrderStatus::$failedFinal;
						$this->logger->writeLog("ThePay WARNING: STATUS_UNDERPAID");
						break;
					case TpReturnedPayment::STATUS_WAITING:
						$orderStatus = OrderStatus::$pending;
						break;
					default:
						$orderStatus = OrderStatus::$failedFinal;
						user_error("Neznámý stav platby ThePay");
				}
				$shopPairingInfo = $tpReturnedPayment->getMerchantData();

			} else {

				if (session_status() == PHP_SESSION_NONE) {
					session_start();
				}

				$shopPairingInfo = $_SESSION['UniThePay_backToEshop_shopPairingInfo'];
				$orderStatus = OrderStatus::$pending;

			}

			$transactionRecord = $this->getOrderTransactionRecordFromDbLast(null, $shopPairingInfo);

			if ($transactionRecord == null) {
				user_error("nenalezeno merchantData coby pairingInfo v DB  " . $tpReturnedPayment->getMerchantData());
				$orderStatus = OrderStatus::$invalidReply;
			}

		} catch (Exception $e) {
			$orderStatus = OrderStatus::$invalidReply;
			user_error($e);
		}

		$this->logger->writeLog("IsPaymentDone uniStatus=".$orderStatus. (isset($reslult) ? "  status=".print_r($result, true) : ""));

		$orderReplyStatus = new OrderReplyStatus();
		$orderReplyStatus->orderStatus = $orderStatus;
		$orderReplyStatus->resultText = null; //preklad atp
		if(!$backToEshop) {
			$orderReplyStatus->gwOrderNumber = $tpReturnedPayment->getPaymentId();
		}
		if ($orderReplyStatus->orderStatus != OrderStatus::$invalidReply) {
			$orderReplyStatus->shopOrderNumber = $transactionRecord->shopOrderNumber;
			$orderReplyStatus->shopPairingInfo = $transactionRecord->shopPairingInfo;
			$orderReplyStatus->orderStatus = $this->ensureGlobalPairingInfoStatusUpgradeOnly($orderReplyStatus);
			if ($orderReplyStatus->orderStatus != OrderStatus::$invalidReply) {
				$orderReplyStatus->forexNote = $transactionRecord->forexNote;
				$orderReplyStatus->uniAdapterData = $transactionRecord->uniAdapterData;
				if(!$backToEshop) {
					$this->updateGwOrderNumber($transactionRecord->transactionPK, $tpReturnedPayment->getPaymentId());
					$this->updateOrderReplyStatusGwOrdNumInDb($orderReplyStatus);
				}
			}
		}
		EndSynchronized();
		$this->logger->writeLog("orderReplyStatus=".$orderReplyStatus->orderStatus);
		if ($orderReplyStatus->orderStatus == OrderStatus::$invalidReply) {
			$this->logger->writeLog("Ukoncuji skript z duvodu invalidReply. Predpokladame notifikaci na pozadi, coz ale u ThePay nelze rozlisit, a foreground reply by z toho udelala chybnou platbu.");
			ResetUniErr();
			die("Stav objednavky s platbou ThePay nezmenen");
		}
		return $orderReplyStatus;
	}

	function gatewayReceiveNotification() {
		user_error('chybne volani notify url u ThePay. ' . $_SERVER["QUERY_STRING"]);
		return null;
	}



	function getThepayConfig() {
		$tpConfig = new TpMerchantConfig();
		$tpConfig->merchantId = $this->config->merchantId;
		$tpConfig->accountId = $this->config->accountId;
		$tpConfig->password = $this->config->password;
		$tpConfig->dataApiPassword = $this->config->dataApiPassword;

		if ($this->config->isTest) {
			$tpConfig->gateUrl = 'https://www.thepay.cz/demo-gate/';
			$tpConfig->webServicesWsdl = 'https://www.thepay.cz/demo-gate/api/api-demo.wsdl';
			$tpConfig->dataWebServicesWsdl = 'https://www.thepay.cz/demo-gate/api/data-demo.wsdl';
		} else {
			$tpConfig->gateUrl = 'https://www.thepay.cz/gate/';
			$tpConfig->webServicesWsdl = 'https://www.thepay.cz/gate/api/api.wsdl';
			$tpConfig->dataWebServicesWsdl = 'https://www.thepay.cz/gate/api/data.wsdl';
		}

		return $tpConfig;
	}

	public function getInfoBoxData($uniAdapterName, $language) {
		$infoBoxData = parent::getInfoBoxData($uniAdapterName, $language);
		$infoBoxData->link = 'http://www.thepay.cz';
		$infoBoxData->image = 'UniThePayLogo.png';
		return $infoBoxData;
	}

}

