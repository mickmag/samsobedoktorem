<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

$dict['cs']['unsupportedCurrency'] = 'Nelze platit v';
$dict['en']['unsupportedCurrency'] = 'Unsupported currency';
$dict['sk']['unsupportedCurrency'] = 'Nelze platit v';
$dict['ru']['unsupportedCurrency'] = 'Не поддерживается валюта';
$dict['pl']['unsupportedCurrency'] = 'Płatność niemożliwa w';   // Płatność w ...? niemożliwa
$dict['de']['unsupportedCurrency'] = 'Es ist nicht möglich zahlen in';   // Płatność w ...? niemożliwa
$dict['es']['unsupportedCurrency'] = 'Moneda no admitida';
$dict['hu']['unsupportedCurrency'] = 'nem támogatott valuta';

$dict['cs']['forexMessageTemplate'] = 'Vaše platba bude převedena na {newtotalstr} {newcur} s kurzem 1 {actcur} = {ratestr} {newcur}';
$dict['en']['forexMessageTemplate'] = 'Your payment will be converted to {newtotalstr} {newcur} at exchange rate 1 {actcur} = {ratestr} {newcur}';
$dict['sk']['forexMessageTemplate'] = 'Vaše platba bude prevedena na {newtotalstr} {newcur} s kurzem 1 {actcur} = {ratestr} {newcur}';
$dict['pl']['forexMessageTemplate'] = 'Twoja płatność zostanie przewalutowana na {newtotalstr} {newcur} według kursu 1 {actcur} = {ratestr} {newcur}';
$dict['ru']['forexMessageTemplate'] = 'Ваш платеж будет преобразован в {newtotalstr} {newcur} по курсу 1 {actcur} = {ratestr} {newcur}';
$dict['de']['forexMessageTemplate'] = 'Ihre Zahlung wird umgerechnet auf {newtotalstr} {newcur} mit dem Kurz 1 {actcur} = {ratestr} {newcur}';
$dict['es']['forexMessageTemplate'] = 'Se convertirá tu pago a {newtotalstr} {newcur} al tipo de cambio 1 {actcur} = {ratestr} {newcur}';
$dict['hu']['forexMessageTemplate'] = 'a kifizetés át lesz adva {newtotalstr} {newcur} a tanfolyamon 1 {actcur} = {ratestr} {newcur}';

$dict['cs']['forexNoteLabel'] = 'Převedena měna';
$dict['en']['forexNoteLabel'] = 'Currency converted';
$dict['sk']['forexNoteLabel'] = 'Prevedena měna';
$dict['pl']['forexNoteLabel'] = 'Waluta po przewalutowaniu';
$dict['ru']['forexNoteLabel'] = 'Валюта конвертируется';
$dict['de']['forexNoteLabel'] = 'Währung umgerechnet.';
$dict['es']['forexNoteLabel'] = 'Moneda convertida';
$dict['hu']['forexNoteLabel'] = 'átutalt pénznem';


//

$dict['cs']['infoBoxPlatitiLinkText'] = 'Modul {modulname} pro {shopname} od platiti.cz';
$dict['en']['infoBoxPlatitiLinkText'] = 'Module {modulname} for {shopname} by platiti.cz';
$dict['sk']['infoBoxPlatitiLinkText'] = 'Modul {modulname} pro {shopname} od platiti.cz';
$dict['pl']['infoBoxPlatitiLinkText'] = 'Płatność {modulname} w sklepie {shopname} za pomocą systemu platiti.cz';
$dict['ru']['infoBoxPlatitiLinkText'] = 'Модуль {modulname} от {shopname} platiti.cz';
$dict['de']['infoBoxPlatitiLinkText'] = 'Modul {modulname} für {shopname} von platiti.cz';
$dict['es']['infoBoxPlatitiLinkText'] = 'Módulo {modulname} para {shopname} por platiti.cz';
$dict['hu']['infoBoxPlatitiLinkText'] = 'Modul {modulname} mert {shopname} által platiti.cz';


// config

// std ciselniky

$dict['cs']['yes'] = 'ano';
$dict['en']['yes'] = 'yes';
$dict['sk']['yes'] = 'ano';
$dict['ru']['yes'] = 'да';
$dict['es']['yes'] = 'sí';
$dict['hu']['yes'] = 'Igen';
$dict['cs']['no'] = 'ne';
$dict['en']['no'] = 'no';
$dict['sk']['no'] = 'ne';
$dict['ru']['no'] = 'нет';
$dict['es']['no'] = 'no';
$dict['hu']['no'] = 'nem';


// std ke stavum obj

$dict['cs']['orderStatusSuccessfull'] = 'Stav objednávky po úspěšném zaplacení';
$dict['en']['orderStatusSuccessfull'] = 'Order state after successful payment';
$dict['sk']['orderStatusSuccessfull'] = 'Stav objednávky po úspěšném zaplacení';
$dict['ru']['orderStatusSuccessfull'] = 'Состояние заказа после успешной оплаты';
$dict['es']['orderStatusSuccessfull'] = 'Estado del pedido después del pago aceptado';
$dict['hu']['orderStatusSuccessfull'] = 'Megrendelés sikeres kifizetés után';

$dict['cs']['orderStatusPending'] = 'Stav objednávky při čekání na převod prostředků';
$dict['en']['orderStatusPending'] = 'Order state when waiting for bank transfer';
$dict['sk']['orderStatusPending'] = 'Stav objednávky při čekání na převod prostředků';
$dict['ru']['orderStatusPending'] = 'Состояние заказа при ожидании банковским переводом';
$dict['es']['orderStatusPending'] = 'Estado del pedido a la espera de la transferencia bancaria';
$dict['hu']['orderStatusPending'] = 'Rendelés állapota banki átutalás esetén';

$dict['cs']['orderStatusFailed'] = 'Stav objednávky při selhání pokusu o platbu';
$dict['en']['orderStatusFailed'] = 'Order state if payment failed';
$dict['sk']['orderStatusFailed'] = 'Stav objednávky při selhání pokusu o platbu';
$dict['ru']['orderStatusFailed'] = 'Состояние заказа, если платеж не прошел';
$dict['es']['orderStatusFailed'] = 'Estado del pedido si el pago falla';
$dict['hu']['orderStatusFailed'] = 'Rendelés állapota, ha a fizetés sikertelen';

// 

$dict['cs']['supportedCurrencies'] = 'Podporované měny (3-písmené ISO kódy oddělené mezerou, např. "CZK EUR")';
$dict['en']['supportedCurrencies'] = 'Supported currencies (3-letter ISO codes separated by space, e.g. "CZK EUR")';
$dict['sk']['supportedCurrencies'] = 'Podporované měny (3-písmené ISO kódy oddělené mezerou, např. "CZK EUR")';
$dict['ru']['supportedCurrencies'] = 'Поддерживаемые валюты (коды 3 ISO через пробел, например, "CZK EUR")';
$dict['es']['supportedCurrencies'] = 'Monedas admitidas (códigos ISO de 3 letras separados por espacio, ejemplo: "CZK EUR")';
$dict['hu']['supportedCurrencies'] = 'Támogatott pénznemek (3-betűs ISO kódok térközzel elválasztva, például "HUF EUR")';

$dict['cs']['convertToCurrencyIfUnsupported'] = 'Měna for pro převod, pokud platební metoda měnu košíku nepodporuje, prázdné=nepřevádět';
$dict['en']['convertToCurrencyIfUnsupported'] = 'Currency for conversion if the cart currency is not supported by payment method, empty=do not convert';
$dict['sk']['convertToCurrencyIfUnsupported'] = 'Měna for pro převod, pokud platební metoda měnu košíku nepodporuje, prázdné=nepřevádět';
$dict['ru']['convertToCurrencyIfUnsupported'] = 'Курсы для преобразования, если корзина валют не поддерживается способа оплаты, empty=do not convert';
$dict['es']['convertToCurrencyIfUnsupported'] = 'Moneda para la conversión si el método de pago no admite el método de compra, vacío = no convertir';
$dict['hu']['convertToCurrencyIfUnsupported'] = 'konverziós pénznem, ha a kosár pénzneme nem támogatott fizetési móddal, üres = nem konvertál';

$dict['cs']['subMethodsSelection'] = 'Povolené platební metody';
$dict['en']['subMethodsSelection'] = 'Enabled payment methods';
$dict['sk']['subMethodsSelection'] = 'Povolené platobné metody';
$dict['ru']['subMethodsSelection'] = 'Включено способы оплаты';
$dict['es']['subMethodsSelection'] = 'Métodos de pago habilitados';
$dict['hu']['subMethodsSelection'] = 'Engedélyezett fizetési módok';

$dict['cs']['activationKey'] = 'Aktivační klíč';
$dict['en']['activationKey'] = 'Activation key';
$dict['sk']['activationKey'] = 'Aktivační klíč';
$dict['es']['activationKey'] = 'Activation key';
$dict['hu']['activationKey'] = 'aktivációs kulcs';

$dict['cs']['gwOrderNumberOffset'] = 'Číslo první platby na platební bráně (nastavit na 1000 a pak už neměnit)';
$dict['en']['gwOrderNumberOffset'] = 'Gateway order number offset (recommended value 1000, do not change after it is once set)';
$dict['sk']['gwOrderNumberOffset'] = 'Číslo první platby na platební bráně (nastavit na 1000 a pak už neměnit)';
$dict['ru']['gwOrderNumberOffset'] = 'Шлюз номер заказа смещение (рекомендуемое значение 1000, не изменяются после однократного установить)';
$dict['es']['gwOrderNumberOffset'] = 'Offset del número de pedido de plataforma (valor recomendado 1000, no cambiar después de configurado)';

