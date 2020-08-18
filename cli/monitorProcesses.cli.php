<?php
/**
 * monitors whether all processes ar running
 * if not, attempts to restart them and sends an e-mail report
 *
 * run every minute
 */
require_once(dirname( __FILE__ ) . '/prepend.cli.php');
leafProcessController::ensureAllIsRunning();
