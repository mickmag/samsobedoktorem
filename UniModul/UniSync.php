<?php
// Autor (c) Miroslav Novak, www.platiti.cz
// Pouzivani bez souhlasu autora neni povoleno
// #Ver:PRV079-15-g0f319ea:2018-08-28#

function BeginSynchronized() {
	global $UniSyncFile;
	if (!isset($UniSyncFile)) {
		register_shutdown_function('SynchronizedShutdownHandler');
	}
	if ($UniSyncFile != null) {
		user_error("ERROR: BeginSynchronized v ramci aktivni synchronizace");
	}
	$UniSyncFile = fopen(dirname(__FILE__)."/sync.lock", "r+");
	if (!flock($UniSyncFile, LOCK_EX | LOCK_NB)) { // do an exclusive lock
		SynchronizedWriteLog("BLOCK: Cekam na sync");
		if(!flock($UniSyncFile, LOCK_EX)) {
			SynchronizedWriteLog("ERROR: Synchronizace se nezdarila");
			user_error("ERROR: Synchronizace se nezdarila");
			return;
		}
		SynchronizedWriteLog("BLOCK: Dockal jsem se");
	}
}

function EndSynchronized() {
	global $UniSyncFile;
	if ($UniSyncFile == null) {
		user_error("ERROR: EndSynchronized bez BeginSynchronized");
		return;
	}
	flock($UniSyncFile, LOCK_UN); // release the lock
	fclose($UniSyncFile);
	$UniSyncFile = 0;  // isset pak vraci true
}

function BeginMaybeExitSynchronized() {
	global $UniSyncFile, $UniSyncMaybeExit;
	if ($UniSyncFile == null) {
		user_error("ERROR: BeginMaybeExitSynchronized bez BeginSynchronized");
		return;
	}
	if ($UniSyncMaybeExit) {
		user_error("ERROR: BeginMaybeExitSynchronized jiz aktivni");
		return;
	}
	$UniSyncMaybeExit = true;
}

function EndMaybeExitSynchronized() {
	global $UniSyncFile, $UniSyncMaybeExit;
	if ($UniSyncFile == null) {
		user_error("ERROR: EndMaybeExitSynchronized bez BeginSynchronized");
		return;
	}
	if (!$UniSyncMaybeExit) {
		user_error("ERROR: EndMaybeExitSynchronized bez EndMaybeExitSynchronized");
		return;
	}
	$UniSyncMaybeExit = false;
}


function SynchronizedShutdownHandler() {
	global $UniSyncFile, $UniSyncMaybeExit;
	if ($UniSyncFile != null && !$UniSyncMaybeExit) {
		SynchronizedWriteLog("ERROR: Shutdown bez ukonceni synchronizace");
		user_error("ERROR: Shutdown bez ukonceni synchronizace");
	}
}


function SynchronizedWriteLog($msg) {
	$logger = new UniLogger();
	$logger->WriteLog("Sync: ".$msg);
}
