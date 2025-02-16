<?php

namespace BPL\Settings\Plans;

require_once 'bpl/mods/query.php';
require_once 'bpl/mods/helpers.php';

use Exception;

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Exception\ExceptionHandler;

use function BPL\Mods\Database\Query\update as query_update;
use function BPL\Mods\Url_SEF\sef;

use function BPL\Mods\Helpers\input_get;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\application;
use function BPL\Mods\Helpers\settings;

function view(): string
{
	$form_token = HTMLHelper::_('form.token');

	$notifications = notifications();

	$row_settings_plans = row_settings_plans();

	return <<<HTML
		$notifications
		<div class="card mb-4">
			<div class="card-header">
				<i class="fas fa-table me-1"></i>
				Plans
			</div>
			<div class="card-body">
				<form method="post">
					$form_token
					<div class="table-responsive">
						<table class="table align-middle">
							<thead>
								<tr>
									<th scope="col">Plan</th>
									<th scope="col">Alias</th>
									<th scope="col">Active</th>								
								</tr>
							</thead>
							<tbody>
								$row_settings_plans
							</tbody>
						</table>
					</div>
					<div class="d-grid gap-2 col-6 mx-auto">
						<button type="submit" class="btn btn-primary">Update</button>                
					</div>					
				</form>
			</div>
		</div>
HTML;
}

/**
 * @throws Exception
 *
 * @since 2021
 */
function row_settings_plans(): string
{
	$inputs = input();

	$sp = settings('plans');

	$str = '';

	foreach ($inputs as $k => $v) {
		$str .= '<tr>';

		foreach ($v as $u => $y) {
			$name = ($u === 'name' ? ($k . '_' . $u) : '');
			$value = ($u === 'status' ? $k : '');

			$default = name_default();

			$str .= $name !== '' ?
				'<th scope="row">' . $default[$name] . '</th>
                <td>
					<div class="input-group">
						<label>
							<input type="text" class="form-control" name="' . $name . '" value="' . $sp->$name . '">
						</label>						
					</div>
                </td>' : '';

			$str .= $value !== '' ? '<td>
					<div class="input-group">
                        <label>
                            <input type="checkbox" name="' . $value . '" value="1" ' . ($sp->$value ? 'checked' : '') . '>
						</label>
                    </div>
                </td>' : '';
		}

		$str .= '</tr>';
		$str .= "\n";
	}

	return $str;
}

function notifications(): string
{
	$app = application();

	// Display Joomla messages as dismissible alerts
	$messages = $app->getMessageQueue(true);
	$notification_str = fade_effect(); // Initialize the notification string

	if (!empty($messages)) {
		foreach ($messages as $message) {
			// Map Joomla message types to Bootstrap alert classes
			$alert_class = '';
			switch ($message['type']) {
				case 'error':
					$alert_class = 'danger'; // Bootstrap uses 'danger' instead of 'error'
					break;
				case 'warning':
					$alert_class = 'warning';
					break;
				case 'notice':
					$alert_class = 'info'; // Joomla 'notice' maps to Bootstrap 'info'
					break;
				case 'message':
				default:
					$alert_class = 'success'; // Joomla 'message' maps to Bootstrap 'success'
					break;
			}

			$notification_str .= <<<HTML
            <div class="alert alert-{$alert_class} alert-dismissible fade show mt-5" role="alert">
                {$message['message']}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
HTML;
		}
	}

	return $notification_str;
}

function fade_effect(int $duration = 10000)
{
	return <<<HTML
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Select all alert elements
      const alerts = document.querySelectorAll('.alert');

      // Loop through each alert and set a timeout to dismiss it
      alerts.forEach(function (alert) {
        setTimeout(function () {
          // Use Bootstrap's alert method to close the alert
          bootstrap.Alert.getOrCreateInstance(alert).close();
        }, $duration);
      });
    });
  </script>
HTML;
}

function input(): array
{
	$keys = [
		'account_freeze',
		'direct_referral',
		'direct_referral_fast_track_principal',
		'indirect_referral',
		'unilevel',
		'binary_pair',
		'leadership_binary',
		'leadership_passive',
		'leadership_fast_track_principal',
		'redundant_binary',
		'royalty',
		'echelon',
		'etrade',
		'top_up',
		'fast_track',
		'fixed_daily',
		'fixed_daily_token',
		'trading',
		'p2p_trading',
		'p2p_commerce',
		'merchant',
		'upline_support',
		'passup_binary',
		'elite_reward',
		'harvest',
		'stockist',
		'franchise',
	];

	$inputs = [];

	foreach ($keys as $key) {
		$inputs[$key]['name'] = input_get($key . '_name', '', 'RAW');
		$inputs[$key]['status'] = input_get($key, 0);
	}

	return $inputs;
}

/**
 * @throws Exception
 * @since 2021
 */
function update()
{
	$app = application();

	$db = db();

	$inputs = input();

	$test = [];

	if (!empty($inputs)) {
		foreach ($inputs as $input) {
			foreach ($input as $value) {
				if (!empty($value) && $value !== '0' && $value !== '') {
					$test[] = $value;
				}
			}
		}
	}

	if (!empty($test)) {
		$fields = [];

		foreach ($inputs as $k => $v) {
			foreach ($v as $u => $y) {
				if ($u === 'status') {
					$fields[] = $k . ' = ' . $db->quote($y);
				} elseif ($u === 'name') {
					$fields[] = $k . '_' . $u . ' = ' . $db->quote($y);
				}
			}
		}

		try {
			$db->transactionStart();

			query_update('network_settings_plans', $fields);

			$db->transactionCommit();
		} catch (Exception $e) {
			$db->transactionRollback();
			ExceptionHandler::render($e);
		}

		$app->enqueueMessage('Marketing Plan Settings Updated Successfully!', 'success');
		$app->redirect(Uri::root(true) . '/' . sef(88));
	}
}

/**
 *
 * @return string[]
 *
 * @since version
 */
function name_default(): array
{
	return [
		'account_freeze_name' => 'Account Freeze',
		'direct_referral_name' => 'Direct Referral',
		'direct_referral_fast_track_principal_name' => 'Direct Referral FTP',
		'indirect_referral_name' => 'Indirect Referral',
		'unilevel_name' => 'Unilevel',
		'binary_pair_name' => 'Binary',
		'leadership_binary_name' => 'Leadership Binary',
		'leadership_passive_name' => 'Leadership Passive',
		'leadership_fast_track_principal_name' => 'Leadership Fast Track Principal',
		'redundant_binary_name' => 'Redundant Binary',
		'matrix_name' => 'Matrix',
		'power_name' => 'Power',
		'royalty_name' => 'Royalty',
		'echelon_name' => 'Echelon',
		'etrade_name' => 'Etrade',
		'top_up_name' => 'Top Up',
		'fast_track_name' => 'Fast Track',
		'fixed_daily_name' => 'Fixed Daily',
		'fixed_daily_token_name' => 'Fixed Daily Token',
		'trading_name' => 'Trading',
		'table_matrix_name' => 'Table Matrix',
		'merchant_name' => 'Merchant',
		'upline_support_name' => 'Upline Support',
		'passup_name' => 'Passup',
		'passup_binary_name' => 'Passup Binary',
		'elite_reward_name' => 'Elite Reward',
		'stockist_name' => 'Stockist',
		'franchise_name' => 'Franchise',
		'harvest_name' => 'Harvest',
		'p2p_trading_name' => 'P2P Trading',
		'p2p_commerce_name' => 'P2P Commerce'
	];
}