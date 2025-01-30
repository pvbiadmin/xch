<?php

namespace BPL\Mods\API_Token_Price;

require_once 'bpl/mods/file_get_contents_curl.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use function BPL\Mods\File_Get_Contents_Curl\main as file_get_contents_curl;

/**
 * Fetches token price from multiple sources with fallback mechanisms
 * 
 * @param   string  $token
 * @param   int     $max_retries     Maximum number of retry attempts
 * @param   int     $retry_delay     Delay between retries in milliseconds
 *
 * @return array
 */
function main(string $token, int $max_retries = 3, int $retry_delay = 1000): array
{
	$data = [];
	$tokens = list_token();

	if (!array_key_exists($token, $tokens)) {
		return $data;
	}

	$token_id = $tokens[$token];
	$apis = get_api_endpoints($token_id);

	foreach ($apis as $api) {
		$attempt = 0;
		while ($attempt < $max_retries) {
			try {
				$result = fetch_price($api, $token_id);
				if (!empty($result)) {
					return [
						'symbol' => $token,
						'price' => $result,
						'source' => $api['name']
					];
				}
			} catch (Exception $e) {
				error_log("API Error ({$api['name']}): " . $e->getMessage());
			}

			$attempt++;
			if ($attempt < $max_retries) {
				usleep($retry_delay * 1000); // Convert to microseconds
			}
		}
	}

	return $data;
}

/**
 * Fetches price from a specific API endpoint
 *
 * @param   array   $api
 * @param   string  $token_id
 *
 * @return float|null
 */
function fetch_price(array $api, string $token_id): ?float
{
	$url = sprintf($api['url'], $token_id);

	$json = !in_array('curl', get_loaded_extensions()) || is_localhost() ?
		@file_get_contents($url) : file_get_contents_curl($url);

	if (empty($json)) {
		return null;
	}

	$data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

	return isset($data[$api['price_path'][0]][$api['price_path'][1]]) ?
		(float) $data[$api['price_path'][0]][$api['price_path'][1]] : null;
}

/**
 * Returns list of API endpoints with their configurations
 *
 * @param   string  $token_id
 *
 * @return array
 */
function get_api_endpoints(string $token_id): array
{
	return [
		[
			'name' => 'CoinGecko',
			'url' => 'https://api.coingecko.com/api/v3/simple/price?ids=%s&vs_currencies=usd',
			'price_path' => [$token_id, 'usd']
		],
		[
			'name' => 'Binance',
			'url' => 'https://api.binance.com/api/v3/ticker/price?symbol=%sUSDT',
			'price_path' => ['price', null],
			'symbol_transform' => function ($token_id) {
				// Convert token_id to Binance symbol format
				$symbols = [
					'binancecoin' => 'BNB',
					'bitcoin' => 'BTC',
					// Add more mappings as needed
				];
				return $symbols[$token_id] ?? null;
			}
		]
	];
}

/**
 * Returns list of supported tokens
 *
 * @return string[]
 */
function list_token(): array
{
	return [
		'USDT' => 'tether',
		'BTC' => 'bitcoin',
		'ETH' => 'ethereum',
		'BNB' => 'binancecoin',
		'LTC' => 'litecoin',
		'ADA' => 'cardano',
		'USDC' => 'usd-coin',
		'LINK' => 'chainlink',
		'DOGE' => 'dogecoin',
		'DAI' => 'dai',
		'BUSD' => 'binance-usd',
		'SHIB' => 'shiba-inu',
		'UNI' => 'uniswap',
		'MATIC' => 'polygon',
		'DOT' => 'polkadot',
		'TRX' => 'tron',
		'SOL' => 'solana',
		'XRP' => 'ripple',
		'TON' => 'the-open-network',
		'BCH' => 'bitcoin-cash'
	];
}

/**
 * Checks if the current environment is localhost
 *
 * @param   string[]  $whitelist
 *
 * @return bool
 */
function is_localhost(array $whitelist = ['127.0.0.1', '::1']): bool
{
	return in_array($_SERVER['REMOTE_ADDR'] ?? '', $whitelist, true);
}