<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

//$GLOBALS['UniErrorReporting_DBG'] = 1;   // pro ladeni neparovych UniErru

if(file_exists(dirname(__FILE__).'/UniModulConfig.php')) {
    include 'UniModulConfig.php';
}
if (!isset($GLOBALS['UniErrorControl'])) {
	$GLOBALS['UniErrorControl'] = 0;
}

function get_debug_print_backtrace($traces_to_ignore = 1){
    $traces = debug_backtrace();
    $ret = array();
    foreach($traces as $i => $call){
        if ($i < $traces_to_ignore ) {
            continue;
        }

        $object = '';
        if (isset($call['class'])) {
            $object = $call['class'].$call['type'];
			/*
            if (isset($call['args']) && is_array($call['args'])) {
                foreach ($call['args'] as &$arg) {
                    get_arg($arg);
                }
            }
			*/
        }
		if (isset($call['file']) && isset($call['args'])) {
			$ret[] = '#'.str_pad($i - $traces_to_ignore, 3, ' ').$object.$call['function'].'('./*implode(', ', $call['args']).*/') called at ['.$call['file'].':'.$call['line'].']';
		}
    }

    return implode("\n",$ret);
}

/*  // TODO: sice se nepouziva, ale kdyby jo, nutno odstranit dosazovani do reference $arg!!
function get_arg(&$arg) {
    if (is_object($arg)) {
        $arr = (array)$arg;
        $args = array();
        foreach($arr as $key => $value) {
            if (strpos($key, chr(0)) !== false) {
                $key = '';    // Private variable found
            }
            $args[] =  '['.$key.'] => '.get_arg($value);
        }

        $arg = get_class($arg) . ' Object ('.implode(',', $args).')';
        STOPPP('funkce prave zmenila promenou v ramci stacku, ma dopad na runtime reference parametry!');
    }
}
*/

// UniErr Logging
if (!defined('E_UNIERR_DEFAULT')) {
	define('E_UNIERR_DEFAULT', E_ALL & ~E_STRICT);
}

function BeginUniErr($erlev = E_UNIERR_DEFAULT) {
	if (!isset($GLOBALS['UniErrorReporting'])) {
		$GLOBALS['UniErrorReporting'] = array();
		$GLOBALS['UniErrorReporting'][] = $erlev;
		if ($GLOBALS['UniErrorControl']!=0) {
			$GLOBALS['UniErrorReporting_PrevErrHandler'] = set_error_handler("UniErrHandler");
			$GLOBALS['UniErrorReporting_PrevExcHandler'] = set_exception_handler ("UniErrExceptionHandler");
			register_shutdown_function('UniErrShutdownHandler');
		}
	} else {
		$GLOBALS['UniErrorReporting'][] = $erlev;
		if ($GLOBALS['UniErrorControl'] == 2) {   // opakovana kontrola a obnova proti prebirani handleru jinymi funkcemi
			$prevHandler = set_error_handler("UniErrHandler");
			if ($prevHandler == "UniErrHandler") {
				restore_error_handler();
			}
			$prevHandler = set_exception_handler ("UniErrExceptionHandler");
			if ($prevHandler == "UniErrExceptionHandler") {
				restore_exception_handler();
			}
		}
		
	}
	if (isset($GLOBALS['UniErrorReporting_DBG'])) UniWriteErrLog(E_UNIERR_INTERNAL, "BeginUniErr ".count($GLOBALS['UniErrorReporting']), 0, 0, 2);
}

function EndUniErr($ret = NULL) {
	if (isset($GLOBALS['UniErrorReporting_DBG'])) UniWriteErrLog(E_UNIERR_INTERNAL, "EndUniErr ".count($GLOBALS['UniErrorReporting']), 0, 0, 2);
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		array_pop($GLOBALS['UniErrorReporting']);
	} else {
		UniWriteErrLog(E_UNIERR_INTERNAL, "EndUniErr bez odpovidajiciho BeginUniErr", 0, 0 , 2);
		user_error("EndUniErr bez odpovidajiciho BeginUniErr");
	}
	return $ret;
}

function ResetUniErr($new = array()) {
	if (isset($GLOBALS['UniErrorReporting_DBG'])) UniWriteErrLog(E_UNIERR_INTERNAL, "ResetUniErr ".count($GLOBALS['UniErrorReporting']), 0, 0, 2);
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		$old = $GLOBALS['UniErrorReporting'];
	} else {
		$old = array();
	}
	$GLOBALS['UniErrorReporting'] = $new;
	return $old;
}

function ExceptionUniErr($ex) {
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		UniWriteErrLog('Rethrowing exception',  $ex->__toString(), 0,  0);
		throw $ex;
	}
}

function UniErrHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		$erlev = end($GLOBALS['UniErrorReporting']);
		if ($erlev & $errno) {
			UniWriteErrLog($errno, $errstr, $errfile, $errline );
		}
	}
	if (!empty($GLOBALS['UniErrorReporting_PrevErrHandler'])) {
	return call_user_func($GLOBALS['UniErrorReporting_PrevErrHandler'], $errno, $errstr, $errfile, $errline, $errcontext);
	} else {
		return false; // normal err handling
	}
}

function UniErrExceptionHandler($ex) {
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		UniWriteErrLog('Unhandled exception',  $ex->__toString(), 0,  0); 
	}
	if (!empty($GLOBALS['UniErrorReporting_PrevExcHandler'])) {
		call_user_func($GLOBALS['UniErrorReporting_PrevExcHandler'], $ex);
	} else {
		$ue = ResetUniErr();
		user_error($ex->__toString());
		ResetUniErr($ue);
	}
}

function UniErrShutdownHandler() {
	if (isset($GLOBALS['UniErrorReporting']) && count($GLOBALS['UniErrorReporting'])!=0) {
		$err = error_get_last();
		if ($err['type'] & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
			$GLOBALS['UniErrorReporting_PrevErrHandler'] = null; //vypneme volani pripadneho retezeneho err handleru
			UniErrHandler($err['type'], 'UniErr-SHUTDOWN ' . $err['message'], $err['file'],  $err['line'], null); 
		} else {
			if (!empty($GLOBALS['UniErrorReporting_ShutdownMessage'])) {
				UniWriteErrLog(E_UNIERR_INTERNAL, "SpecialShutdown: " . $GLOBALS['UniErrorReporting_ShutdownMessage'], 0, 0);
			} else {
				UniWriteErrLog(E_UNIERR_INTERNAL, "Shutdown s aktivním UniErrem!, hloubka: ".count($GLOBALS['UniErrorReporting']), 0, 0);
			}
		}
		ResetUniErr(); // musi se to killnout, jinak nasledny shutdown kod vyvolava ruzne warningu, coz je matouci
	}
}


function UniWriteErrLog($errno, $errstr, $errfile, $errline , $traces_to_ignore=3, $backTrace=null) {
	$logger = new UniLogger();
	if (!isset($GLOBALS['UniErrorReportingUsed'])) {
		$logger->WriteLog("UniErrInfo: "."-------------------------------------------- new request #Ver:PRV079-15-g0f319ea:2018-08-28#");
		$logger->writeLogNoNewLines("UniErrInfo: "."REQUEST ". $_SERVER['REMOTE_ADDR']." ".$_SERVER['REQUEST_URI']." PostData: ".var_export($_POST, true));
		$GLOBALS['UniErrorReportingUsed'] = 1;
	}
	$logger->WriteLog("UniErrInfo: ".errnoName($errno).", $errstr, $errfile, $errline\n" . ($backTrace!=null ? $backTrace : get_debug_print_backtrace($traces_to_ignore)));
}


function errnoName($errno) {
	if (!is_int($errno)) return $errno;
	$errbitNames =  array('E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR', 'E_CORE_WARNING', 'E_COMPILE_ERROR', 'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_STRICT', 'E_RECOVERABLE_ERROR', 'E_DEPRECATED', 'E_USER_DEPRECATED', 'E_UNIERR_INTERNAL');
	$errStr = "";
	for ($i=0; $i<count($errbitNames); $i++) {
		if (($errno >> $i) & 1) {
			$errStr .= $errbitNames[$i] . " ";
		}
	}
	return $errStr;
}

if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);
if (!defined('E_DEPRECATED')) define('E_DEPRECATED', 8192);
if (!defined('E_USER_DEPRECATED')) define('E_USER_DEPRECATED', 16384);
define('E_UNIERR_INTERNAL', 2<<18);
