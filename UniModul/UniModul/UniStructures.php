<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#


class CheckNonexistentFields {
	function __set($name, $value ) {
		user_error("Assigning nonexistent field ".get_class($this)."::".$name);
		$this->$name = $value;
	}
}

class CheckNonexistentFieldsLogOnly {
	function __set($name, $value ) {
		UniWriteErrLog('CheckNonexistentFieldsLogOnly', "Assigning nonexistent field ".get_class($this)."::".$name, null, null, 2); 
		$this->$name = $value;
	}
}


class OrderToPayInfo extends CheckNonexistentFieldsLogOnly {
	var $shopOrderNumber;  // pouze pokud je to znamo dopredu, jinak null
	var $shopPairingInfo;
	var $uniAdapterData;		// serialized to blob, sessions atp 
	var $amount;
	var $currency;		// ciselnik CZK, EUR, USD, GBP
	var $ccBrand;		//ciselnik dle CS pro CS, Tato polozka se jiz nepouziva
	var $customerData;   //type CustomerData
	var $language;  //ciselnik  cz, sk, en, de, pl   // ISO-639 http:www.ics.uci.edu/pub/ietf/http/related/iso639.txt (currently cs, en)
	var $description;
	var $replyUrl;
	var $notifyUrl;
	var $uniModulDirUrl;  // url adresare UniModul v rootu eshopu
	var $currencyRates; //array(ISO=>rate)
	var $subMethod; // nazev vybrane submetody, prazdne pro defaultni metodu
	var $adapterProperties; // asoc pole vlastnosti/pozadavku Adapteru
	var $recurrenceType; // RecurrenceType
	var $recurrenceParentShopOrderNumber; // pro recurrenceType::child je to prislusny ShopOrderNumber s recurrenceType::parent
	var $recurrenceDateTo; // YYYY-MM-DD
	var $cartItems; // array of CartItem
}

class CustomerData extends CheckNonexistentFieldsLogOnly {
	var $email;
	var $first_name;
	var $last_name;
	var $street;
	var $houseNumber;
	var $city;
	var $post_code;
	var $state;  // iso AZ, TX, ...http://en.wikipedia.org/wiki/ISO_3166-2:US
	var $country; // iso CZ, US, ...https://cs.wikipedia.org/wiki/ISO_3166-1
	var $phone;
	var $identifier; // identifikator zakaznika v ramci eshopu

}

class UniCartItem extends CheckNonexistentFields {
	var $name;
	var $unitPrice;   // s dani
	var $quantity;
	var $taxRate; // v procentech
	var $unitTaxAmount;   // samotna dan, dodana pozdeji, nektere adaptery ji nemusi doplnovat
	var $type; // UniCartItemType, u starsich adapteru nemusi byt dosazovana
}

abstract class UniCartItemType {
	const commodity = 1;
	const delivery = 2;
	const discount = 3;
}

class TransactionRecord extends CheckNonexistentFieldsLogOnly {
	var $transactionPK;
	var $uniModulName;
	var $gwOrderNumber;
	var $shopOrderNumber;
	var $shopPairingInfo;
	var $uniAdapterData;
	var $uniModulData;
	var $forexNote;
	var $orderStatus;
	var $dateCreated;
	var $dateModified;
}

// {{{ Konfigurace

class ConfigSetting extends CheckNonexistentFieldsLogOnly {
	var $uniModulConfig; //UniModulConfig
	var $configData; //array (n=>v)
}

class ConfigInfo extends CheckNonexistentFieldsLogOnly {
	var $configFields; //Array of ConfigField
}

class ConfigField extends CheckNonexistentFieldsLogOnly {
	var $name;
	var $type; //ConfigFieldType
	var $choiceItems; //array(v=>l) 
	var $label;
	var $comment;
}

class ConfigFieldType extends CheckNonexistentFieldsLogOnly {
	static $text = 1;
	static $choice = 2;
	static $orderStatus = 3;
	static $subMethodsSelection = 4;
}

class RecurrenceType extends CheckNonexistentFieldsLogOnly {
	const none = 0;
	const parent = 1;
	const child = 2;
}

// }}} Konfigurace


class PrePayGWInfo extends CheckNonexistentFieldsLogOnly {
	var $paymentMethodName;
	var $paymentMethodDescription;
	var $paymentMethodIconUrl;
	var $isPossible; // bool
	
	var $selectCsPayBrand; // bool, vyber brandu pro CS
	var $selectCsPayBrandTitle; // text pro nadpis ke kartám
	
	var $forexMessage; // string, info o automatickem prevodu
	
	var $convertToCurrency;  	// pro konverzi
	var $convertToCurrencyAmount;

	var $subMethods;  //submetody pouzitelne pro platbu   - pokud null, tak jen hlavni modul, pokud jich je vice tak prazdny retezec znamena hlavni modul
}

class FormChoice extends CheckNonexistentFieldsLogOnly {
	var $formTitle;
	var $formKey;
	var $formItems; // Array of (value=>text)
}

class OrderReplyStatus extends CheckNonexistentFieldsLogOnly {
	var $shopOrderNumber;  // melo by stacit jen to pairing info, asi toto odstranit
	var $shopPairingInfo;
	var $gwOrderNumber;
	var $orderStatus; //typ OrderStatus
	var $resultText; // pouziva se pri neuspechu pro predani detailni chybove hlasky
	var $successHtml; // pouziva se pri ok nebo pendingu pro zobrazeni instrukci k offile platbe, zobrazi se na strane s podekovanim za platbu
	var $forexNote;
	var $uniAdapterData;		// serialized from blob
}

class OrderStatus extends CheckNonexistentFieldsLogOnly {
	static $initiated = 0;
	static $successful = 1;
	static $pending = 2;
	static $failedRetriable = 3;
	static $failedFinal = 4;
	static $invalidReply = 5;  // muze byt pouzito i pro nic nerikajici stavy, aby se nezpracovali, napr PayU stav 1.
	static $gwUnpaired = 6;
}

class RedirectAction extends CheckNonexistentFieldsLogOnly {
	// bude zadne z nasledujicich tri, nebo vyplneno redirectUrl nebo redirectForm nebo inlineForm, pokud inlineForm tak totez i v redirectForm pro zpetnou kompatibilitu
	var $redirectUrl;
	var $redirectForm;
	var $inlineForm;
	var $orderReplyStatus;  //vysledek hned, obvykle kdyz nelze provest platbu, nebo kdyz je nutno vytvorit objednavku soucasne s presmerovanim na branu
}

class InfoBoxData extends CheckNonexistentFieldsLogOnly {
	public $title;
	public $image;
	public $link;
	public $platitiLink;
	public $platitiLinkText;
}
