<?php

/*
	CoinPayments.net API Example
	Copyright 2014 CoinPayments.net. All rights reserved.	
	License: GPLv2 - http://www.gnu.org/licenses/gpl-2.0.txt
*/

require('../../lib/CoinPaymentsAPI.php');

use BPL\Lib\Local\CryptoCurrency\API\CoinPaymentsAPI;

$cps = new CoinPaymentsAPI();

$cps->Setup('Your_Private_Key', 'Your_Public_Key');

$result = $cps->GetBalances();

if ($result['error'] === 'ok')
{
	print 'Coins returned: ' . count($result['result']) . "\n";

	$le = PHP_SAPI === 'cli' ? "\n" : '<br />';

	foreach ($result['result'] as $coin => $bal)
	{
		print $coin . ': ' . sprintf('%.08f', $bal['balancef']) . $le;
	}
}
else
{
	print 'Error: ' . $result['error'] . "\n";
}
