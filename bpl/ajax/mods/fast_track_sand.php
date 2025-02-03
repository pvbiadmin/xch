<?php

namespace BPL\Ajax\Mods\Sand\Fast_Track;

use BPL\Lib\Local\Database\Db_Connect as DB;
use DateTime;
use DateTimeZone;
use Exception;
use PDO;

use function BPL\Mods\Local\Database\Query\fetch_all;
use function BPL\Mods\Local\Database\Query\crud;
use function BPL\Mods\Local\Helpers\settings;
use function BPL\Mods\Local\Helpers\user;
use function BPL\Mods\Local\Helpers\echo_json;
use function BPL\Mods\Local\Helpers\validate_fast_track;

class FastTrackProcessor
{
    private PDO $dbh;
    private array $settings;
    private object $user;
    private float $input;
    private int $userId;

    public function __construct(float $input, int $userId)
    {
        $this->dbh = DB::connect();
        $this->settings = [
            'plans' => settings('plans'),
            'investment' => settings('investment'),
            'referral' => settings('referral_fast_track_principal')
        ];
        $this->input = $input;
        $this->userId = $userId;
        $this->user = user($userId);
    }

    /**
     * Main processing function
     * @return array Response data
     * @throws Exception
     */
    public function process(): array
    {
        $return = $_POST;

        // Validate input
        validate_fast_track($this->input, $this->userId, $return);

        try {
            $this->dbh->beginTransaction();

            // Process fast track entry
            $this->insertFastTrack();

            // Update user balance
            if ($this->updateUserBalance()) {
                $sponsorId = $this->user->sponsor_id;
                $this->processReferralBonus($sponsorId);
            }

            $this->dbh->commit();

            // Prepare response
            return $this->prepareResponse($return);

        } catch (Exception $e) {
            $this->dbh->rollBack();
            throw new Exception('Fast track processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Process referral bonuses for multiple levels
     * @param int $sponsorId Initial sponsor ID
     * @param int $levels Number of levels to process (default 2)
     * @return array Processing results
     */
    private function processReferralBonus(int $sponsorId, int $levels = 2): array
    {
        if (!$this->settings['plans']->direct_referral_fast_track_principal) {
            return [];
        }

        $levels = min(max(1, $levels), 10);
        $updates = [];
        $currentSponsorId = $sponsorId;

        for ($level = 1; $level <= $levels; $level++) {
            $sponsor = user($currentSponsorId);

            if (!$sponsor) {
                break;
            }

            $bonus = $this->calculateBonus($sponsor, $level);

            if ($this->updateSponsorBalance($currentSponsorId, $bonus)) {
                $updates[] = [
                    'level' => $level,
                    'sponsor_id' => $currentSponsorId,
                    'bonus' => $bonus,
                    'percentage' => $this->calculateLevelPercentage($sponsor, $level)
                ];
            }

            $currentSponsorId = $sponsor->sponsor_id;
        }

        return $updates;
    }

    /**
     * Calculate bonus for a specific level
     * @param object $sponsor Sponsor user object
     * @param int $level Current level
     * @return float Calculated bonus
     */
    private function calculateBonus(object $sponsor, int $level): float
    {
        $basePercent = $this->settings['referral']->{$sponsor->account_type . '_referral_fast_track_principal'};
        $levelFactor = 1 - (($level - 1) * 0.1);
        $percent = $basePercent * $levelFactor;

        return $this->input * ($percent / 100);
    }

    /**
     * Calculate percentage for a specific level
     * @param object $sponsor Sponsor user object
     * @param int $level Current level
     * @return float Calculated percentage
     */
    private function calculateLevelPercentage(object $sponsor, int $level): float
    {
        $basePercent = $this->settings['referral']->{$sponsor->account_type . '_referral_fast_track_principal'};
        return $basePercent * (1 - (($level - 1) * 0.1));
    }

    /**
     * Insert new fast track entry
     * @return bool Success status
     */
    private function insertFastTrack(): bool
    {
        $time = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $now = $time->getTimestamp();

        $processing = $this->settings['investment']->{$this->user->account_type . '_fast_track_processing'};

        return crud(
            'INSERT INTO network_fast_track (
                user_id, time_last, value_last, day, principal, 
                date_entry, processing, date_last_cron
            ) VALUES (
                :user_id, :time_last, :value_last, :day, :principal,
                :date_entry, :processing, :date_last_cron
            )',
            [
                'user_id' => $this->userId,
                'time_last' => 0,
                'value_last' => 0,
                'day' => 0,
                'principal' => $this->input,
                'date_entry' => $now,
                'processing' => $processing,
                'date_last_cron' => $now
            ]
        );
    }

    /**
     * Update user's balance
     * @return bool Success status
     */
    private function updateUserBalance(): bool
    {
        return crud(
            'UPDATE network_users 
            SET fast_track_principal = :principal,
                payout_transfer = :payout_transfer
            WHERE id = :id',
            [
                'principal' => ($this->user->fast_track_principal + $this->input),
                'payout_transfer' => ($this->user->payout_transfer - $this->input),
                'id' => $this->userId
            ]
        );
    }

    /**
     * Update sponsor's balance
     * @param int $sponsorId Sponsor ID
     * @param float $bonus Bonus amount
     * @return bool Success status
     */
    private function updateSponsorBalance(int $sponsorId, float $bonus): bool
    {
        $sponsor = user($sponsorId);
        return crud(
            'UPDATE network_users 
            SET income_referral_fast_track_principal = :income_referral_ftp,
                payout_transfer = :payout_transfer
            WHERE id = :id',
            [
                'income_referral_ftp' => ($sponsor->income_referral_fast_track_principal + $bonus),
                'payout_transfer' => ($sponsor->payout_transfer + $bonus),
                'id' => $sponsorId
            ]
        );
    }

    /**
     * Prepare response data
     * @param array $return Initial return data
     * @return array Complete response data
     */
    private function prepareResponse(array $return): array
    {
        $userLatest = user($this->userId);
        $fastTracks = $this->getUserFastTracks();

        $valueLast = array_reduce($fastTracks, function ($carry, $item) {
            return $carry + $item->value_last;
        }, 0);

        $return['principal'] = $userLatest->fast_track_principal;
        $return['balance'] = $userLatest->payout_transfer;
        $return['interest'] = $valueLast + $userLatest->fast_track_interest;
        $return['input'] = $this->input;
        $return['success_fast_track'] = $this->settings['plans']->fast_track_name . ' successful!';

        try {
            $return['fast_track_json'] = json_encode($return, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            $return['fast_track_json'] = '{}';
        }

        return $return;
    }

    /**
     * Get user's fast track entries
     * @return array Fast track entries
     */
    private function getUserFastTracks(): array
    {
        return fetch_all(
            'SELECT * FROM network_fast_track 
            WHERE user_id = :user_id 
            ORDER BY id DESC',
            ['user_id' => $this->userId]
        );
    }
}

// Entry point
$input = filter_input(INPUT_POST, 'input', FILTER_VALIDATE_FLOAT);
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

if ($input === false || $userId === false) {
    echo_json(['error' => 'Invalid input parameters']);
    exit;
}

header('Content-Type: application/json');

try {
    $processor = new FastTrackProcessor($input, $userId);
    $result = $processor->process();
    echo_json($result);
} catch (Exception $e) {
    echo_json(['error' => $e->getMessage()]);
}