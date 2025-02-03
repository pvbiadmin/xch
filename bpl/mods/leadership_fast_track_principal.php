<?php

namespace BPL\Ajax\Mods\Leadership_Fast_Track_Principal;

use function BPL\Mods\Local\Database\Query\{fetch, crud};
use function BPL\Mods\Local\Url_SEF\{sef, qs};
use function BPL\Mods\Local\Helpers\{settings, user, users, directs_valid};

class LeadershipFastTrackPrincipal
{
    private const STARTER_ACCOUNT = 'starter';

    private $settings;
    private $plansSettings;
    private $ancillarySettings;

    public function __construct()
    {
        $this->settings = settings('leadership_fast_track_principal');
        $this->plansSettings = settings('plans');
        $this->ancillarySettings = settings('ancillaries');
    }

    public function calculateBonuses(): void
    {
        foreach (users() as $user) {
            $this->calculateUserBonus($user);
        }
    }

    private function calculateUserBonus(object $user): void
    {
        if (!$this->isEligibleForBonus($user)) {
            return;
        }

        $bonusDetails = $this->calculateBonusAmount($user);

        if ($bonusDetails['addedBonus'] <= 0) {
            return;
        }

        $this->processBonus($user, $bonusDetails);
    }

    private function isEligibleForBonus(object $user): bool
    {
        return $this->hasLftp($user);
    }

    private function calculateBonusAmount(object $user): array
    {
        $accountType = $user->account_type;
        $userId = $user->id;
        $currentBonus = $user->bonus_leadership_fast_track_principal;

        $incomeLimit = $this->settings->{$accountType . '_leadership_fast_track_principal_max_daily_income'};
        $maxIncome = $this->settings->{$accountType . '_leadership_fast_track_principal_maximum'};

        $userLftp = $this->getUserLftp($userId);
        $currentDailyIncome = $userLftp->income_today;

        $totalBonus = $this->calculateTotalBonus($userId)['bonus'];
        $addedBonus = $totalBonus - $userLftp->bonus_leadership_fast_track_principal_last;

        if ($incomeLimit > 0) {
            $addedBonus = min($addedBonus, $incomeLimit - $currentDailyIncome);
        }

        if ($maxIncome > 0) {
            $addedBonus = min($addedBonus, $maxIncome - $currentBonus);
        }

        return [
            'totalBonus' => $totalBonus,
            'addedBonus' => max(0, $addedBonus)
        ];
    }

    private function processBonus(object $user, array $bonusDetails): void
    {
        $this->updateUserBalance($user, $bonusDetails['addedBonus']);
        $this->updateNetworkLftp($user->id, $bonusDetails);
        $this->logBonusActivity($user, $bonusDetails['addedBonus']);
    }

    private function updateUserBalance(object $user, float $bonusAmount): void
    {
        crud(
            'UPDATE network_users 
            SET bonus_leadership_fast_track_principal = bonus_leadership_fast_track_principal + :bonus,
                payout_transfer = payout_transfer + :bonus
            WHERE id = :id',
            [
                'bonus' => $bonusAmount,
                'id' => $user->id
            ]
        );
    }

    private function updateNetworkLftp(int $userId, array $bonusDetails): void
    {
        crud(
            'UPDATE network_leadership_fast_track_principal
            SET bonus_leadership_fast_track_principal = bonus_leadership_fast_track_principal + :bonus,
                bonus_leadership_fast_track_principal_now = bonus_leadership_fast_track_principal_now + :bonus,
                bonus_leadership_fast_track_principal_last = bonus_leadership_fast_track_principal_last + :total_bonus,
                income_today = income_today + :bonus
            WHERE user_id = :user_id',
            [
                'bonus' => $bonusDetails['addedBonus'],
                'total_bonus' => $bonusDetails['totalBonus'],
                'user_id' => $userId
            ]
        );
    }

    private function logBonusActivity(object $user, float $bonusAmount): void
    {
        $activity = sprintf(
            '<b>%s Bonus: </b> <a href="%s">%s</a> has earned %s %s',
            $this->plansSettings->leadership_fast_track_principal_name,
            sef(44) . qs() . 'uid=' . $user->id,
            $user->username,
            number_format($bonusAmount, 2),
            $this->ancillarySettings->currency
        );

        crud(
            'INSERT INTO network_activity (user_id, sponsor_id, activity, activity_date)
            VALUES (:user_id, :sponsor_id, :activity, :activity_date)',
            [
                'user_id' => $user->id,
                'sponsor_id' => $user->sponsor_id,
                'activity' => $activity,
                'activity_date' => time()
            ]
        );
    }

    private function hasLftp(object $user): bool
    {
        $accountType = $user->account_type;
        $sponsored = directs_valid($user->id);

        $requiredLevel = $this->settings->{$accountType . '_leadership_fast_track_principal_level'};
        $requiredDirects = $this->settings->{$accountType . '_leadership_fast_track_principal_sponsored'};

        return $requiredLevel && count($sponsored) >= $requiredDirects;
    }

    private function calculateTotalBonus(int $userId): array
    {
        $headUser = user($userId);
        $accountType = $headUser->account_type;

        $requiredDirects = $this->settings->{$accountType . '_leadership_fast_track_principal_sponsored'};
        $maxLevel = $this->settings->{$accountType . '_leadership_fast_track_principal_level'};

        if (count($this->getUserDirects($userId)) < $requiredDirects) {
            return ['member' => 0, 'bonus' => 0];
        }

        return $this->calculateLevelBonuses($headUser, $maxLevel);
    }

    private function calculateLevelBonuses(object $headUser, int $maxLevel): array
    {
        $totalMembers = 0;
        $totalBonus = 0;

        for ($level = 1; $level <= $maxLevel; $level++) {
            $indirects = $this->getIndirectsAtLevel($level, $headUser);
            $totalMembers += count($indirects);
            $totalBonus += $this->calculateLevelBonus($level, $indirects);
        }

        return [
            'member' => $totalMembers,
            'bonus' => $totalBonus
        ];
    }

    private function getIndirectsAtLevel(int $level, object $user): array
    {
        $currentLevel = [$user];
        for ($i = 1; $i <= $level; $i++) {
            $currentLevel = $this->getLevelDirects($currentLevel);
        }
        return $currentLevel;
    }

    private function getLevelDirects(array $users): array
    {
        $directs = [];
        foreach ($users as $user) {
            $directs = array_merge($directs, $this->getUserDirects($user->id));
        }
        return $directs;
    }

    private function calculateLevelBonus(int $level, array $indirects): float
    {
        if (empty($indirects)) {
            return 0.0;
        }

        return array_reduce($indirects, function ($total, $indirect) use ($level) {
            $user = user($indirect->id);
            $accountType = $user->account_type;

            $sharePercentage = $this->settings->{$accountType . '_leadership_fast_track_principal_share_' . $level} / 100;
            $shareCutPercentage = $this->settings->{$accountType . '_leadership_fast_track_principal_share_cut_' . $level} / 100;

            return $total + ($user->fast_track_principal * $sharePercentage * $shareCutPercentage);
        }, 0.0);
    }

    private function getUserLftp(int $userId): object
    {
        return fetch(
            'SELECT * FROM network_leadership_fast_track_principal WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    private function getUserDirects(int $userId): array
    {
        return fetch(
            'SELECT * FROM network_users 
            WHERE account_type <> :starter AND sponsor_id = :sponsor_id',
            [
                'starter' => self::STARTER_ACCOUNT,
                'sponsor_id' => $userId
            ]
        );
    }
}