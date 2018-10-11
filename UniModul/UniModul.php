<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#


//error_reporting(E_ALL);  //dbg

require_once('DatabaseConnection.php');
require_once('UniStructures.php');
require_once('UniDebug.php');
require_once('UniSync.php');

class UniModulConfig extends CheckNonexistentFieldsLogOnly {
	var $mysql_server;
	var $mysql_dbname;
	var $mysql_login;
	var $mysql_password;
	var $databaseConnection;  // IDatabaseConnection, if it is set, the field mysql_* are not used
	var $uniModulDirUrl;
	var $funcGetCallbackUrl;
	var $funcProcessReplyStatus; //urceno typicky pro davkove parovani transakci z banky atp., jinak pouzit standardni reply a notification
	var $adapterName;
}

abstract class UniModul extends CheckNonexistentFieldsLogOnly {
	var $name;
	var $logger;
	var $baseConfig;
	public $config; //public kvuli mocku
	public $dictionary;
	public $subMethod = null; // 1 - pokud adapter dokaze v jednom modulu zobrazit vice metod, jmeno submetody je-li pro adapter znama jiz pri konstrukci, null nebo "" pro defaultni modul
	protected $dbConn;
	public $uniModulProperties = array(); // asoc pole vlastnosti/pozadavku UniModulu
	public $version;
	public $versionDate;
	var $configAcke;
	var $activationKeyOk;


	public function __construct($name, $configSetting, $subMethod) {
		$this->name = $name;
		$this->subMethod = $subMethod;
		preg_match("/^#Ver:(.+):(.+)#/", "#Ver:PRV079-15-g0f319ea:2018-08-28#", $verpars);
		$this->version = isset($verpars[1]) ? $verpars[1] : 'DEVV';
		$this->versionDate = isset($verpars[2]) ? $verpars[2] : 'DEVT';
		$this->logger = new UniLogger();
		if ($configSetting!==null) {
			$this->baseConfig = $configSetting->uniModulConfig;
			if ($this->baseConfig->databaseConnection != null) {
				$this->dbConn = $this->baseConfig->databaseConnection;
			} else {
				$this->dbConn = new MysqliConnectionShared($this->baseConfig->mysql_server, $this->baseConfig->mysql_login, $this->baseConfig->mysql_password, $this->baseConfig->mysql_dbname);
			}
			$this->checkConfig($configSetting);
		}
		$this->dictionary = $this->getDictionary();
	}

	protected function getDictionary() {
		return new UniDictionary($this->name);
	}


	abstract public function getConfigInfo($language='en'); //vola to jen UniFactory
	//vyreseno, neni nutne soucast interfacu//  abstract public function setConfigFromData($configSetting);  //vola to konstruktor odvozeneho UniModulu, nekdy ale kdyz konfigurace neni k mani pri konstrukci adapteru, tak se to volat jeste dodatecne

	// vrati mozne subMetody formou Array of strings
	public function getSubMethods() {
		return null;
	}


	//// detail produktu na splatky
	public function ProductGetInstallmentEmbedHtml($shopBaseUrl, $currency, $price, $language='cs') {
		return null;
	}

	//// volane pri placeni

	abstract public function queryPrePayGWInfo($orderToPayInfo);
	abstract public function gatewayOrderRedirectAction($orderToPayInfo);
	abstract public function gatewayReceiveReply($language='en');
	public function gatewayReceivePreliminaryReply() {  //pro ziskani gwOrdNumber nebo shopPairingInfo pro dohled�n� nap�. instance pro v�ceinstan�n� shopy u metod kde nelze programov� volit replyUrl a notifyUrl
		user_error('Not implemented');
	}
	public function gatewayReceiveNotification() {
		user_error('Not implemented');
	}
	public function gatewayReceivePreliminaryNotification() {
		user_error('Not implemented');
	}


	public function gatewayReceiveReply_pub() {
		$orderReplyStatus = gatewayReceiveReply();

		// zjisti zda $orderReplyStatus->shopPairingInfo
		/*
		ulozime stav do db ke gw orderu
		reportujeme gw order nasledovne
			pending + fail -> rep-fail

			sucess + fail -> CHYBA!!!!   nevime jak to rict eshopu
			* + fail -> nic
			* + pending -> pending
			* + success -> success
		a pak zkompletujeme vysledek za cely pairing-info
			(init->fail) a (ex success) -> eshop beze zmeny, ale ukazat success
			(init->success) 	-> eshop success

			rep-fail a
		kdyz vysledel je
		*/
	}


	function checkConfig($configSetting) {
		eval(base64_decode('CQlpZiAoJGNvbmZpZ1NldHRpbmcgIT0gbnVsbCAmJiAkY29uZmlnU2V0dGluZy0+Y29uZmlnRGF0YSAhPSBudWxsKSB7DQoJCQkkdGhpcy0+Y29uZmlnQWNrZSA9ICRjb25maWdTZXR0aW5nLT5jb25maWdEYXRhWydhY3RpdmF0aW9uS2V5J107DQoJCQkkY2hrc3Vtb2sgPSB0cnVlOw0KCQkJZm9yZWFjaChleHBsb2RlKCcgJywkdGhpcy0+Y29uZmlnQWNrZSkgYXMgJGFrZXkwKSB7DQoJCQkJJGFrZXkgPSBzdHJfcmVwbGFjZSgnLScsICcnLCAkYWtleTApOw0KCQkJCWlmIChzdHJsZW4oJGFrZXkpPDIpIGNvbnRpbnVlOw0KCQkJCWlmIChzdWJzdHIobWQ1KHN1YnN0cigkYWtleSwwLC0yKSksIDAsMikgIT0gc3Vic3RyKCRha2V5LC0yKSkgew0KCQkJCQkkY2hrc3Vtb2sgPSBmYWxzZTsNCgkJCQkJJHRoaXMtPmxvZ2dlci0+d3JpdGVMb2coIldybyIuIm5nIGtleSAiIC4gJGFrZXkwKTsNCgkJCQl9DQoJCQl9DQoJCQkkdGhpcy0+YWN0aXZhdGlvbktleU9rID0gJGNoa3N1bW9rOw0KDQoJCQlpZiAoIWZ1bmN0aW9uX2V4aXN0cygndnJmYWNrZScpKSB7DQoJCQkJJEdMT0JBTFNbJ3ZyZmFja2UnXSA9ICd2cmZhY2tlJzsNCgkJCQlmdW5jdGlvbiB2cmZhY2tlICgkdGhzKSB7DQoJCQkJCSRkb20gPSAkR0xPQkFMU1siX1NFUiIuIlZFUiJdWyJIVFQiLiJQX0giLiJPU1QiXTsNCgkJCQkJJGFjdEtleXMgPSAkdGhzLT5jb25maWdBY2tlOw0KCQkJCQkkbW9kID0gJHRocy0+bmFtZTsNCgkJCQkJJGFkYSA9ICR0aHMtPmJhc2VDb25maWctPmFkYXB0ZXJOYW1lOw0KCQkJCQkkdXBkZGF0ZWUgPSBleHBsb2RlKCctJywgJHRocy0+dmVyc2lvbkRhdGUpOw0KCQkJCQkkdXBkZGF0ZTE1ID0gY291bnQoJHVwZGRhdGVlKT4yID8gKCR1cGRkYXRlZVswXS0yMDE1KSoxMisoJHVwZGRhdGVlWzFdLTEpIDogMDsNCg0KCQkJCQkkb2sgPSBmYWxzZTsNCgkJCQkJJGRvbSA9IHByZWdfcmVwbGFjZSgnLyg6WzAtOV0rKS8nLCAnJywgJGRvbSk7CgkJCQkJJGRvbTMgPSBwcmVnX3JlcGxhY2UoJy8oXnd3d1wuKS8nLCAnJywgJGRvbSk7CgkJCQkJaWYgKCRkb20zPT0nbG9jYWxob3N0JyB8fCAkZG9tMz09JzEyNy4wLjAuMScpIHsNCgkJCQkJCSRvayA9IHRydWU7DQoJCQkJCX0NCgkJCQkJZm9yZWFjaChleHBsb2RlKCcgJywkYWN0S2V5cykgYXMgJGFjdGtleSkgew0KCQkJCQkJJGFjdGtleSA9IHN0cl9yZXBsYWNlKCctJywgJycsICRhY3RrZXkpOw0KCQkJCQkJJGFjdGtleSA9IHByZWdfcmVwbGFjZSgnL1teMC05YS1mXS8nLCcwJywkYWN0a2V5KTsNCgkJCQkJCSRhY3RrZXkgPSBzdHJfcGFkKCRhY3RrZXksIDE0LCAnMCcpOw0KDQoJCQkJCQkkcm5kaCA9IHN1YnN0cigkYWN0a2V5LCAwLCAyKTsNCgkJCQkJCSRkYXRlc2ggPSBzdWJzdHIoJGFjdGtleSwgMiwgNik7DQoJCQkJCQkkZGF0ZXMgPSBpbnR2YWwoYmFzZV9jb252ZXJ0KCRkYXRlc2gsIDE2LCAxMCkpIF4gYmFzZV9jb252ZXJ0KCRybmRoLiRybmRoLiRybmRoLCAxNiwgMTApOw0KCQkJCQkJJG1heGRhdGUgPSBpbnR2YWwoJGRhdGVzPj4xMik7DQoJCQkJCQkkbWF4dXBkZGF0ZSA9ICRkYXRlcyAlICgxPDwxMik7DQoJCQkJCQkkZGF0ZTE1ID0gKGRhdGUoInkiKS0xNSkqMTIrKGRhdGUoIm0iKS0xKTsNCgkJCQkJCSRkYXRlb2sgPSAkZGF0ZTE1PD0kbWF4ZGF0ZSAmJiAkdXBkZGF0ZTE1IDw9ICRtYXh1cGRkYXRlOw0KDQoJCQkJCQkkbnVtaGFzaCA9IGludHZhbChiYXNlX2NvbnZlcnQoc3Vic3RyKG1kNSgkZG9tMy4kYWRhLiRtb2QuJGRhdGVzaC4kcm5kaCksMCwgNCksIDE2LCAxMCkpICUgNjE0MjM7DQoJCQkJCQkkbnVtaGFzaGRldiA9IGludHZhbChiYXNlX2NvbnZlcnQoc3Vic3RyKG1kNSgkZG9tMy4kZGF0ZXNoLiRybmRoKSwwLCA0KSwgMTYsIDEwKSkgJSA2MTQyMzsNCgkJCQkJCSRkZWMgPSBiY3Bvd21vZChiYXNlX2NvbnZlcnQoc3Vic3RyKCRhY3RrZXksOCw0KSwgMTYsIDEwKSwgNDMsIDYxNDIzLCAwKTsNCgkJCQkJCSRkb21vayA9ICRudW1oYXNoID09ICRkZWMgfHwgJG51bWhhc2hkZXYgPT0gJGRlYzsNCg0KCQkJCQkJJG9rID0gJG9rIHx8ICgkZGF0ZW9rICYmICRkb21vayk7DQoJCQkJCX0NCgkJCQkJaWYgKCEkb2spIHsNCgkJCQkJCSR0aHMtPmxvZ2dlci0+d3JpdGVMb2coIkJhIi4iZCBrZXkgJGFkYSAkbW9kICRkb20gJyRhY3RLZXlzJyIpOw0KCQkJCQl9DQoJCQkJCXJldHVybiAkb2s7DQoJCQkJfQ0KCQkJfQ0KDQoJCX0NCg=='));
	}

	public function updateShopOrderNumber($orderReplyStatus, $shopOrderNumber) {
		$sql = "update unimodul_transactions set dateModified = FROM_UNIXTIME(".time()."), shopOrderNumber=".toSql($shopOrderNumber)." where gwOrderNumber = ".toSql($orderReplyStatus->gwOrderNumber)." and uniModulName=".toSql($this->name);
		$this->dbConn->sqlExecute($sql);
	}




	public function getOrderTransactionRecordFromDbUnique($gwOrderNumber, $transactionPK=null) {
		return $this->getOrderTransactionRecordFromDbPriv(null, $gwOrderNumber, null, $transactionPK);
	}

	// pri prohlizeni obejdnavky napr v prehledu objednavek v eshopu pro zjisteni napr. c.obj. na brane
	public function getOrderTransactionRecordFromDbLast($shopOrderNumber, $shopPairingInfo=null) {
		return $this->getOrderTransactionRecordFromDbPriv($shopOrderNumber, null, $shopPairingInfo, null);
	}

	// tato funkce nebude volana niky primo ale jen pres ty dve funkce vyse, protoze micha unikatni selecty s neunikatnim poslednima
	public function getOrderTransactionRecordFromDb($shopOrderNumber, $gwOrderNumber=null, $shopPairingInfo=null, $transactionPK=null) {
		UniWriteErrLog(0, "MyWARN: Zastarale, pouzij getOrderTransactionRecordFromDbUnique nebo getOrderTransactionRecordFromDbLast", __FILE__, __LINE__ );
		return $this->getOrderTransactionRecordFromDbPriv($shopOrderNumber, $gwOrderNumber, $shopPairingInfo, $transactionPK);
	}

	private function getOrderTransactionRecordFromDbPriv($shopOrderNumber, $gwOrderNumber=null, $shopPairingInfo=null, $transactionPK=null) {
		if (!is_null($transactionPK)) {
			$where = "transactionPK=".toSql($transactionPK);
		} else if (!is_null($shopOrderNumber)) {
			$where = "shopOrderNumber=".toSql($shopOrderNumber);
		} else if (!is_null($gwOrderNumber)) {
			$where = "gwOrderNumber=".toSql($gwOrderNumber);
		} else {
			$where = "shopPairingInfo=".toSql($shopPairingInfo);
		}
		$sql = "select * from unimodul_transactions where ".$where." and uniModulName=".toSql($this->name). " order by orderStatus, transactionPK desc";
		$ar = $this->dbConn->sqlQuery($sql);

		if (count($ar)==0) { // to je potreba napr pro Prestu, kde v adminu hookovane infa o gwOrdnumberu se volaji vsechny moduly, i kdyz nemaji nic spolecneho s danou platbou
			return null;
		}
		if (count($ar)>1 && ($gwOrderNumber !== null || $transactionPK !== null)) {
			user_error("duplikatni zaznamy v unimodul_transaction: ".$sql);
		}
		$ar0 = $ar[0];
		$transactionRecord = new TransactionRecord();
		$transactionRecord->transactionPK = $ar0['transactionPK'];
		$transactionRecord->uniModulName = $ar0['uniModulName'];
		$transactionRecord->gwOrderNumber = $ar0['gwOrderNumber'];
		$transactionRecord->shopOrderNumber = $ar0['shopOrderNumber'];
		$transactionRecord->shopPairingInfo = $ar0['shopPairingInfo'];
		$transactionRecord->uniAdapterData = unserialize($ar0['uniAdapterData']);
		$transactionRecord->uniModulData = unserialize($ar0['uniModulData']);
		$transactionRecord->forexNote = $ar0['forexNote'];
		$transactionRecord->orderStatus = $ar0['orderStatus'];
		$transactionRecord->dateCreated = new DateTime($ar0['dateCreated']);
		$transactionRecord->dateModified = new DateTime($ar0['dateModified']);
		return $transactionRecord;
	}


	// rozhrani pro zdedene UniModuly  - protected

	protected function writeOrderToDb($shopOrderNumber, $shopPairingInfo, $gwOrderNumber, $forexNote, $orderStatus=null, $uniAdapterData=null, $uniModulData=null) {
		if ($orderStatus === null) {
			$orderStatus = OrderStatus::$initiated;
		}
		$sql = "insert into unimodul_transactions (uniModulName, gwOrderNumber, shopOrderNumber, shopPairingInfo, forexNote, orderStatus, uniAdapterData, uniModulData, dateCreated) values ("
		.toSql($this->name).", "
		.toSql($gwOrderNumber).","
		.toSql($shopOrderNumber).","
		.toSql($shopPairingInfo).","
		.toSql($forexNote).","
		.$orderStatus.","
		.toSql(serialize($uniAdapterData)).","
		.toSql(serialize($uniModulData)).","
		."FROM_UNIXTIME(".time().")"
		.")";
		$this->dbConn->sqlExecute($sql);
		return $this->dbConn->getInsertId();
	}

	protected function updateGwOrderNumber($transactionPK, $gwOrderNumber) {
		$sql = "update unimodul_transactions set dateModified = FROM_UNIXTIME(".time()."), gwOrderNumber=".toSql($gwOrderNumber)." where transactionPK = ".toSql($transactionPK);
		$this->dbConn->sqlExecute($sql);
	}

	protected function updateOrderReplyStatusGwOrdNumInDb($orderReplyStatus, $transactionPK = null) {
		if ($transactionPK !== null) {
			$sql = "update unimodul_transactions set DateModified = FROM_UNIXTIME(".time()."), orderStatus=".toSql($orderReplyStatus->orderStatus).", gwOrderNumber=".toSql($orderReplyStatus->gwOrderNumber)." where transactionPK = ".toSql($transactionPK);
			$this->dbConn->sqlExecute($sql);
		} else if ($orderReplyStatus->gwOrderNumber != null) {
			$sql = "update unimodul_transactions set DateModified = FROM_UNIXTIME(".time()."), orderStatus=".toSql($orderReplyStatus->orderStatus)." where gwOrderNumber = ".toSql($orderReplyStatus->gwOrderNumber)." and uniModulName=".toSql($this->name);
			$this->dbConn->sqlExecute($sql);
		} else {
			user_error("updateOrderReplyStatusGwOrdNumInDb chybi transactionId i orderReplyStatus->gwOrderNumber");
		}
	}

	protected function ensureGlobalPairingInfoStatusUpgradeOnly($orderReplyStatus) {
		// overime ze nedojde ke snizeni urovne dokonceni objednavky podle pairingInfo, aby se nezmenil stav v eshopu po te co nektery z predchozich pokusu o zaplaceni zpozdene zahlasi neuspech
		if ($orderReplyStatus->orderStatus == OrderStatus::$successful || $orderReplyStatus->orderStatus == OrderStatus::$invalidReply) {
			$hledStat = null;
		} else if ($orderReplyStatus->orderStatus == OrderStatus::$pending) {
			$hledStat = OrderStatus::$successful;
		} else {
			$hledStat = OrderStatus::$successful . ',' . OrderStatus::$pending;
		}
		if ($hledStat !== null) {
			$sql = "select * from unimodul_transactions where orderStatus in (".$hledStat.") and shopPairingInfo=".toSql($orderReplyStatus->shopPairingInfo) . " and gwOrderNumber != ".toSql($orderReplyStatus->gwOrderNumber);
			$ar = $this->dbConn->sqlQuery($sql);
		} else {
			$ar = array();
		}
		if (count($ar) != 0) {
			$this->logger->writeLog("WARNING: Jiz existuje stav platba objednavky s vyssi urovni zaplaceni, prepiname na invalidReply. Status stavajici objednavky je ".$orderReplyStatus->orderStatus.", ale existujici obejdnavka GwOrdNum=" . $ar[0]['gwOrderNumber'] . " ma stav " . $ar[0]['orderStatus']);
			return OrderStatus::$invalidReply;
		} else {
			return $orderReplyStatus->orderStatus;
		}
	}

	public function updateUniAdapterDataInDb($transactionPK, $uniAdapterData) {
		$sql = "update unimodul_transactions set dateModified = FROM_UNIXTIME(".time()."), uniAdapterData=".toSql(serialize($uniAdapterData))." where transactionPK = ".toSql($transactionPK);
		$this->dbConn->sqlExecute($sql);
	}

	public function updateUniModulDataInDb($transactionPK, $uniModulData) {
		$sql = "update unimodul_transactions set dateModified = FROM_UNIXTIME(".time()."), uniModulData=".toSql(serialize($uniModulData))." where transactionPK = ".toSql($transactionPK);
		$this->dbConn->sqlExecute($sql);
	}


	// update stavu pri offline zjistovani stavu transakce z cronu
	protected function updateGwPairingInfo($orderReplyStatus, $transactionPK = null) {
		if ($transactionPK !== null) {
			$sql = "update unimodul_transactions set DateModified = FROM_UNIXTIME(".time()."), orderStatus=".toSql($orderReplyStatus->orderStatus).", gwOrderNumber=".toSql($orderReplyStatus->gwOrderNumber)." where uniModulName=".toSql($this->name)." and  transactionPK = ".toSql($transactionPK);
			$this->dbConn->sqlExecute($sql);
		} else if ($orderReplyStatus->gwOrderNumber != null) {
			$sql = "update unimodul_transactions set DateModified = FROM_UNIXTIME(".time()."), orderStatus=".toSql($orderReplyStatus->orderStatus)." where uniModulName=".toSql($this->name)." and  gwOrderNumber = ".toSql($orderReplyStatus->gwOrderNumber);
			$this->dbConn->sqlExecute($sql);
		} else {
			user_error("updateOrderReplyStatusGwOrdNumInDb chybi transactionId i orderReplyStatus->gwOrderNumber");
		}
	}

	// zjisteni pending transakci pri offline zjistovani stavu transakce z cronu
	protected function getAllPendingOrderTransactionRecords() {
		$sql = "select * from unimodul_transactions where uniModulName=".toSql($this->name)." and  orderStatus=".toSql(OrderStatus::$pending)." order by greatest(dateCreated, dateModified)";
		$ar = $this->dbConn->sqlQuery($sql);
		$pendingTransactions = array();
		foreach($ar as $ar0) {
			$transactionRecord = new TransactionRecord();
			$transactionRecord->transactionPK = $ar0['transactionPK'];
			$transactionRecord->uniModulName = $ar0['uniModulName'];
			$transactionRecord->gwOrderNumber = $ar0['gwOrderNumber'];
			$transactionRecord->shopOrderNumber = $ar0['shopOrderNumber'];
			$transactionRecord->shopPairingInfo = $ar0['shopPairingInfo'];
			$transactionRecord->uniAdapterData = unserialize($ar0['uniAdapterData']);
			$transactionRecord->uniModulData = unserialize($ar0['uniModulData']);
			$transactionRecord->forexNote = $ar0['forexNote'];
			$transactionRecord->orderStatus = $ar0['orderStatus'];
			$transactionRecord->dateCreated = new DateTime($ar0['dateCreated']);
			$transactionRecord->dateModified = new DateTime($ar0['dateModified']);
			$pendingTransactions[]=$transactionRecord;
		}
		return $pendingTransactions;
	}

	protected function fixCurrency($orderToPayInfo, $currencySupported=null) {
		global $vrfacke;
		if ($currencySupported===null) {
			$currencySupported = strpos($this->config->supportedCurrencies, $orderToPayInfo->currency)!==false;
		}
		$forexMessage = null;
		$forexNote = null;
		$newtotal = $orderToPayInfo->amount;
		$actcur = $orderToPayInfo->currency;
		$newcur = $actcur;
		$newtotal = $orderToPayInfo->amount;
		$orderReplyStatusFail = null;
		if ($newtotal == 0) {
			$isPossible = false;
			$orderReplyStatusFail = $this->createImmediateFailReplyStatus($orderToPayInfo, "Zero amount");
		} else if (!$currencySupported) {
			if (!empty($this->config->convertToCurrencyIfUnsupported) && $orderToPayInfo->currencyRates !== null) {
				$newcur = $this->config->convertToCurrencyIfUnsupported;
				$rate = $orderToPayInfo->currencyRates[$newcur] / $orderToPayInfo->currencyRates[$actcur];
				$newtotal = $orderToPayInfo->amount * $rate;

				$orderToPayInfo->amount = $newtotal;
				$orderToPayInfo->currency = $newcur;
				if ($orderToPayInfo->cartItems != null) {
					array_walk($orderToPayInfo->cartItems, function($v, $k) use ($rate) {$v->unitPrice *= $rate;});
				}

				$ratestr = number_format($rate, 4, '.', ' ');
				$newtotalstr = number_format($newtotal, 2, '.', ' ');

				$forexMessageTemplate = $this->dictionary->get('forexMessageTemplate', $orderToPayInfo->language);
				$forexMessage = strtr($forexMessageTemplate, array(
					"{actcur}"=>$actcur,
					"{newcur}"=>$newcur,
					"{newtotalstr}"=>$newtotalstr,
					"{ratestr}"=>$ratestr,
				));

				$forexNote = "{$orderToPayInfo->amount} $actcur -> $newtotalstr $newcur @ $ratestr $newcur/$actcur";
				if (!empty($GLOBALS['UniModul_TrackDisplayPaymentOption'])) {
					$this->logger->writeLog("fixCurrency " . $forexNote);
				}
				$isPossible = true;
			} else {
				$isPossible = false;

				$resultText = $this->dictionary->get('unsupportedCurrency', $orderToPayInfo->language)." ".$orderToPayInfo->currency;
				$orderReplyStatusFail = $this->createImmediateFailReplyStatus($orderToPayInfo, $resultText);
			}
		} else {
			$isPossible = true;
		}
		$ok = $vrfacke($this);
		$isPossible = $isPossible && $ok;

		if (!empty($GLOBALS['UniModul_TrackDisplayPaymentOption'])) {
			$this->logger->writeLogNoNewLines("fixCurrency (".$this->name." ".$this->subMethod."): isPossible = " . ($isPossible?"true":"false") . "   currencySupported:".($currencySupported?"true":"false")  . "  orderToPayInfo:".print_r($orderToPayInfo, true) . "  " . ($ok?"ACK":" NAK"));
		}
		return array($isPossible, $newcur, $newtotal, $forexMessage, $forexNote, $orderReplyStatusFail);
	}

	function createImmediateFailReplyStatus($orderToPayInfo, $resultText) {
		$orderReplyStatusFail = new OrderReplyStatus();
		$orderReplyStatusFail->orderStatus = OrderStatus::$failedRetriable;
		$orderReplyStatusFail->resultText = $resultText;
		$orderReplyStatusFail->shopOrderNumber = $orderToPayInfo->shopOrderNumber;
		//$orderReplyStatusFail->gwOrderNumber = $gwOrderNumber;
		$orderReplyStatusFail->shopPairingInfo = $orderToPayInfo->shopPairingInfo;
		$orderReplyStatusFail->uniAdapterData = $orderToPayInfo->uniAdapterData;
		return $orderReplyStatusFail;
	}

	// infoBox

	public function getInfoBoxData($uniAdapterName, $language) {
		$infoBoxLinkTextTemplate = $this->dictionary->get('infoBoxPlatitiLinkText', $language);
		$linkText = strtr($infoBoxLinkTextTemplate, array(
			"{modulname}"=>$this->name,
			"{shopname}"=>$uniAdapterName,
		));

		$infoBoxData = new InfoBoxData();
		$infoBoxData->title = $this->name;
		$infoBoxData->image = "Uni".$this->name."Logo.png";
		//$infoBoxData->link = "http://www.payu.cz";  // dosadi se az v konkretnim modulu
		$infoBoxData->platitiLink = "http://www.platiti.cz/{$uniAdapterName}-{$this->name}.php";
		$infoBoxData->platitiLinkText = $linkText;
		return $infoBoxData;
	}

	//////////

	// hack na normalni session

	private $userSessionHandler;
	private $userSessionName;
	private $userSessionSavePath;

	protected function openStdSession() {
		if (!isset($_SESSION)) {
			session_start();
		}
		if ($this->userSessionHandler != 'files') {

			$this->userSessionName = session_name();
			$this->userSessionHandler = ini_get('session.save_handler');
			$this->userSessionSavePath = session_save_path();

			session_write_close();
			unset($_SESSION);

			ini_set('session.save_handler','files');
			$xtmp = ini_get_all('session');
			session_name($xtmp['session.name']['global_value']);
			session_save_path($xtmp['session.save_path']['global_value']);

			session_start();
		}
	}

	protected function closeStdSession() {
		if ($this->userSessionHandler != 'files') {

			session_write_close();
			unset($_SESSION);

			ini_set('session.save_handler',$this->userSessionHandler);
			session_name($this->userSessionName);
			session_save_path($this->userSessionSavePath);
			session_start();
		}
	}

	// helpers

	public function formRedirect($form) {
		//ob_clean();
		echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>';
		echo $form;
		echo "</body></html>";
		//ob_flush();
		//flush();
		ResetUniErr();
		exit();

	}

	public function getModuleSubMethodName($language='en', $subMethod='') {
		if ($subMethod == '') $subMethod = $this->subMethod;
		if ($subMethod == '') {
			$subname = $this->dictionary->get('payment_method_name', $language);
		} else {
			$subname = $this->dictionary->get('submethod_name_'.$subMethod, $language);
		}
		return $subname;
	}

	public function getModulSubMethodLogoImage($shopBaseUrl, $subMethod='') {
		if ($subMethod == '') $subMethod = $this->subMethod;
	    if (file_exists(dirname(__FILE__)."/Uni".$this->name.$subMethod."Logo.png")) {
	    	$submetLogoPiece = $subMethod;
	    } else {
	    	$submetLogoPiece = "";
	    }
	    $logoimg = ($shopBaseUrl!==null ?  $shopBaseUrl . "UniModul/" : "") . "Uni".urlencode($this->name . $submetLogoPiece)."Logo.png";
	    return $logoimg;
	}

	public function getModulSubMethodLogoImage2($subMethod='') {
		if ($subMethod == '') $subMethod = $this->subMethod;
	    if (file_exists(dirname(__FILE__)."/Uni".$this->name.$subMethod."Logo.png")) {
	    	$submetLogoPiece = $subMethod;
	    } else {
	    	$submetLogoPiece = "";
	    }
	    $logoimg = $this->baseConfig->uniModulDirUrl . "Uni".urlencode($this->name . $submetLogoPiece)."Logo.png";
	    return $logoimg;
	}


	public function getMiniLogoUrl() {
		return "https://www.platiti.cz/muzo/minilogo.png";
	}

	public function getMiniLogoSpan() {
		return "<span style=\"background:url('".$this->getMiniLogoUrl()."')\"></span>";
	}

	public function processCallbackRequest($callbackName, $arguments) {
		$this->logger->writeLog("Cannot process callback request " . $_SERVER['REQUEST_URI']);
		user_error("Cannot process callback request");
	}


	// sesumiruje polozkyp pres jednotlive sazby a provede korekci zaokrouhleni, vysledek EetCastkySazby
	public function getEetRozdeleni($orderToPayInfo, $convertToCzk = true) {

		if (empty($orderToPayInfo->cartItems)) {
			$this->logger->writeLog("Prazdne cart Items");
			return null;
		}

		// secteni pres sazby
		$rozdeleni = array();
		$rozdeleniTax = array();
		foreach ($orderToPayInfo->cartItems as $ci) {

			$act = $ci->unitPrice * $ci->quantity;
			$cum = isset($rozdeleni[$ci->taxRate]) ? $rozdeleni[$ci->taxRate] : 0;
			$rozdeleni[($ci->taxRate)] = $cum + $act;

			if ($ci->unitTaxAmount !== null) {
				$act = $ci->unitTaxAmount * $ci->quantity;
			} else {
				$koef = round($ci->taxRate/(100 + $ci->taxRate), 4);
				$act = $ci->unitPrice * $koef  * $ci->quantity;
			}
			$cum = isset($rozdeleniTax[$ci->taxRate]) ? $rozdeleniTax[$ci->taxRate] : 0;
			$rozdeleniTax[($ci->taxRate)] = $cum + $act;

		}

		// prevod do CZK
		if ($convertToCzk && $orderToPayInfo->currency != 'CZK') {
			$fxrate = $orderToPayInfo->currencyRates['CZK'] / $orderToPayInfo->currencyRates[$orderToPayInfo->currency];
			array_walk($rozdeleni, function(&$v, $k) use ($fxrate) {$v = $v * $fxrate;});
			array_walk($rozdeleniTax, function(&$v, $k) use ($fxrate) {$v = $v * $fxrate;});
			$total =$orderToPayInfo->amount * $fxrate;
		} else {
			$total =$orderToPayInfo->amount;
		}


		// zaokrouhleni na halere
		array_walk($rozdeleni, function(&$v, $k) {$v = round($v, 2);});
		array_walk($rozdeleniTax, function(&$v, $k) {$v = round($v, 2);});

		// korekce pro pripadne zaokrouhleni do nejnizsi ze sazeb
		$total = round($total, 2);
		$vychoziSuma = array_sum($rozdeleni);
		$rozdilZaokrouhleni = $total - $vychoziSuma;
		$minsazba = array_reduce(array_keys($rozdeleni), 'min', 999999);
		$rozdeleni[$minsazba] += $total - $vychoziSuma;
		if (abs($rozdilZaokrouhleni) > 10) {
			$this->logger->writeLog("Nesouhlasi castky v polozkach kosiku. Placena castka " . $total . ", soucet polozek je " . $vychoziSuma . "Polozky kosiku: " . print_r($orderToPayInfo->cartItems, true));
			return null;
		}

		// kontrola na pripustne sazby pro DPH
		$eetSazby = array(0, 21, 15, 10);
		$sazbyNok = array_diff(array_keys($rozdeleni), $eetSazby);
		if (count($sazbyNok)>0) {
			$this->logger->writeLog("Neplatna sazba DPH " . implode(",", $sazbyNok) . ", polozky kosiku: " . print_r($orderToPayInfo->cartItems, true));
			return null;
		}


		// vypocet zakladu a dane
		$eetCastky = new EetCastkySazby();
		$eetCastky->celk_trzba = array_sum($rozdeleni);
		if (isset($rozdeleni[0])) {
			$eetCastky->zakl_nepodl_dph = $rozdeleni[0];
		}
		for ($i=1; $i<=3; $i++) {
			$sazba = $eetSazby[$i];
			if (isset($rozdeleni[$sazba])) {
				$celk = $rozdeleni[$sazba];
				if (empty($rozdeleniTax[$sazba])) {
					$koef = round($sazba/(100 + $sazba), 4);
					$dan = round($celk * $koef, 2);
				} else {
					$dan = $rozdeleniTax[$sazba];
				}
				$eetCastky->{"zakl_dan$i"} = $celk - $dan;
				$eetCastky->{"dan$i"} = $dan;
			}
		}
		return $eetCastky;
	}

}
$vrfacke = "is_null";

class EetCastkySazby {
	var $celk_trzba;
	var $zakl_nepodl_dph;
	var $zakl_dan1;
	var $dan1;
	var $zakl_dan2;
	var $dan2;
	var $zakl_dan3;
	var $dan3;
}

class UniModulFactory extends CheckNonexistentFieldsLogOnly {
	function getConfigInfo($name, $language='en', $subMethod=null) {
		$uniModul = $this->createUniModul($name, null, $subMethod);
		$cofingInfo = $uniModul->getConfigInfo($language);

		$d = $uniModul->dictionary;
		$d->setDefaultLanguage($language);
		$configField = new ConfigField();
		$configField->name = 'activationKey';
		$configField->label = $d->get('activationKey');
		$configField->type = ConfigFieldType::$text;
		array_unshift($cofingInfo->configFields, $configField);

		return $cofingInfo;
	}

	function createUniModul($name, $configSetting, $subMethod=null) {
		if (!ctype_alnum($name)) {
			user_error('Neplatny UniModul: '.$name);
			return null;
		}
		$vniModul = "Uni".$name;
		require_once($vniModul.".php");
		$unimod = new $vniModul($configSetting, $subMethod);
		return $unimod;
	}
}



class UniDictionary extends CheckNonexistentFieldsLogOnly {
	protected $dictionary;

	function __construct($dictionaryFile) {
		include(dirname(__FILE__).'/Uni'."Modul"."Lang.php");  // nejak parametrizovat aby to bylo pripravene pro pluginy
		include(dirname(__FILE__).'/Uni'.$dictionaryFile."Lang.php");
		$this->dictionary = $dict;
		/* pro nacitani z CSV
		$fh = fopen(dirname(__FILE__).'/'.$dictionaryFile, "r");
			$langs = fgetcsv($fh, 1000, ";");
			$this->dictionary = array();
			for ($i=1; $i<count($langs); $i++) {
				$this->dictionary[$langs[$i]]=array();
			}
			while ($line = fgetcsv($fh, 1000, ";")) {
				$key = $line[0];
				for ($i=1; $i<count($line); $i++) {
					$this->dictionary[$langs[$i]][$key] = $line[$i];
				}
			}
		*/
	}

	private $defaultLanguage;

	function setDefaultLanguage($language) {
		$this->defaultLanguage = $language;
	}

	function get($key, $language=null) {
		if ($language === null) {
			$language = $this->defaultLanguage;
		}
		if (isset($this->dictionary[$language]) && isset($this->dictionary[$language][$key])) {
			return $this->dictionary[$language][$key];
		} else {
			return "[$language:$key]";
		}
	}


}



class UniLogger extends CheckNonexistentFieldsLogOnly {
	var $logFile;

	function __construct() {
		$this->logFile = dirname(__FILE__)."/logs/UniModul.log";
	}

	function writeLog($s) {
		$line = "*** ".date('r')." ".$s."\n";
		file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
	}

	function writeLogNoNewLines($s) {
		$this->writeLog($this->replaceLogCrLf($s));
	}

	function replaceLogCrLf($s) {
		$s = str_replace("\r","",str_replace("\n","|",$s));
		return $s;
	}
}



function create_initialize_object($className, $data) { // data array(n=>v)
	$object = new $className();
	foreach($data as $name => $value) {
		$object->{$name} = $value;
	}
	return $object;
}



