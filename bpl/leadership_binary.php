<?php

namespace BPL\Leadership_Binary;

use BPL\Mods\Database\Query;
use BPL\Mods\Helpers;
use BPL\Mods\Url_SEF;

use function BPL\Mods\Commission_Deduct\Filter\main as cd_filter;

class LeadershipBonus
{
	protected $db;

	public function __construct()
	{
		$this->db = Helpers\db();
	}

	public function calculateBonuses()
	{
		foreach (Helpers\users() as $user) {
			$this->calculateUserBonus($user);
		}
	}

	protected function calculateUserBonus($user)
	{
		$slb = Helpers\settings('leadership');

		$accountType = $user->account_type;
		$userBonusLb = $user->bonus_leadership;

		$sponsored = $this->userDirects($user->id);

		$typeLevel = $slb->{$accountType . '_leadership_level'};
		$requiredDirects = $slb->{$accountType . '_leadership_sponsored'};
		$maxDailyIncome = $slb->{$accountType . '_leadership_max_daily_income'};
		$maxIncomeTotal = $slb->{$accountType . '_leadership_max'};

		$ulb = $this->userLeadership($user->id);

		$incomeToday = $ulb->income_today;

		if ($typeLevel && count($sponsored) >= $requiredDirects) {
			$lb = $this->bonusTotal($user);
			$lbAdd = $lb - $ulb->bonus_leadership_last;

			if ($lbAdd > 0) {
				if ($maxDailyIncome > 0 && ($incomeToday + $lbAdd) >= $maxDailyIncome) {
					$lbAdd = $this->nonZero($maxDailyIncome - $incomeToday);
				}

				if ($maxIncomeTotal > 0 && ($userBonusLb + $lbAdd) >= $maxIncomeTotal) {
					$lbAdd = $this->nonZero($maxIncomeTotal - $userBonusLb);
				}

				$this->updateBonusLb($lbAdd, $lb, $user);
			}
		}
	}

	protected function updateBonusLb($lbAdd, $lb, $user)
	{
		$userId = $user->id;

		$fieldUser = ['bonus_leadership = bonus_leadership + ' . $lbAdd];

		if (Helpers\settings('ancillaries')->withdrawal_mode === 'standard') {
			$fieldUser[] = 'balance = balance + ' . cd_filter($userId, $lbAdd);
		} else {
			$fieldUser[] = 'payout_transfer = payout_transfer + ' . cd_filter($userId, $lbAdd);
		}

		Query\update(
			'network_users',
			$fieldUser,
			['id = ' . $this->db->quote($userId)]
		);

		$this->updateLeadership($lbAdd, $lb, $userId);
		$this->logActivity($user, $lb);
	}

	protected function nonZero($value)
	{
		return max(0, $value);
	}

	protected function updateLeadership($leadershipAdd, $leadership, $userId)
	{
		Query\update(
			'network_leadership',
			[
				'bonus_leadership = bonus_leadership + ' . $leadershipAdd,
				'bonus_leadership_now = bonus_leadership_now + ' . $leadershipAdd,
				'bonus_leadership_last = ' . $this->db->quote($leadership),
				'income_today = income_today + ' . $leadershipAdd
			],
			['user_id = ' . $this->db->quote($userId)]
		);
	}

	protected function logActivity($user, $bonus)
	{
		Query\insert(
			'network_activity',
			[
				'user_id',
				'sponsor_id',
				'activity',
				'activity_date'
			],
			[
				$this->db->quote($user->id),
				$this->db->quote($user->sponsor_id),
				$this->db->quote(
					'<b>' . Helpers\settings('plans')->leadership_binary_name .
					' Bonus: </b> <a href="' . Url_SEF\sef(44) . Url_SEF\qs() . 'uid=' . $user->id . '">' .
					$user->username . '</a> has earned ' . number_format($bonus, 2) .
					' ' . Helpers\settings('ancillaries')->currency
				),
				$this->db->quote(time())
			]
		);
	}

	protected function userDirects($sponsorId)
	{
		return $this->db->setQuery(
			'SELECT * ' .
			'FROM network_users ' .
			'WHERE account_type <> ' . $this->db->quote('starter') .
			'AND sponsor_id = ' . $this->db->quote($sponsorId)
		)->loadObjectList();
	}

	protected function userLeadership($userId)
	{
		return $this->db->setQuery(
			'SELECT * ' .
			'FROM network_leadership ' .
			'WHERE user_id = ' . $this->db->quote($userId)
		)->loadObject();
	}

	protected function bonusTotal($user)
	{
		$settingsLeadership = Helpers\settings('leadership');

		$accountType = $user->account_type;
		$requiredDirects = $settingsLeadership->{$accountType . '_leadership_sponsored'};
		$typeLevel = $settingsLeadership->{$accountType . '_leadership_level'};

		$total = 0;

		if (count($this->userDirects($user->id)) >= $requiredDirects) {
			for ($i = 1; $i <= $typeLevel; $i++) {
				$total += $this->calculateLeadershipBonus($i, $user);
			}
		}

		return $total;
	}

	protected function calculateLeadershipBonus($level, $user)
	{
		$indirects = $this->getIndirects($level, $user);
		return $this->bonusLeadership($level, $indirects);
	}

	protected function getIndirects($level, $user)
	{
		$indirects = [$user];
		for ($i = 1; $i <= $level; $i++) {
			$indirects = $this->levelDirects($indirects);
		}
		return $indirects;
	}

	protected function levelDirects(array $users)
	{
		$directs = [];
		foreach ($users as $user) {
			$directs = array_merge($directs, $this->userDirects($user->id));
		}
		return $directs;
	}

	protected function bonusLeadership($level, $indirects)
	{
		$bonus = 0;

		if (!empty($indirects)) {
			$slb = Helpers\settings('leadership');

			foreach ($indirects as $indirect) {
				$userBinary = $this->userBinary($indirect->id);
				$indirectAccountType = $userBinary->account_type;

				$indirectShare = $slb->{$indirectAccountType . '_leadership_share_' . $level} / 100;
				$indirectShareCut = $slb->{$indirectAccountType . '_leadership_share_cut_' . $level} / 100;
				$indirectBonusShare = $indirectShare * $indirectShareCut;

				$bonus += $userBinary->income_cycle * $indirectBonusShare;
			}
		}

		return $bonus;
	}

	protected function userBinary($userId)
	{
		return $this->db->setQuery(
			'SELECT * ' .
			'FROM network_users u ' .
			'INNER JOIN network_binary b ' .
			'ON u.id = b.user_id ' .
			'WHERE b.user_id = ' . $this->db->quote($userId)
		)->loadObject();
	}

	protected function userCd($userId)
	{
		return $this->db->setQuery(
			'SELECT * ' .
			'FROM network_commission_deduct ' .
			'WHERE id = ' . $this->db->quote($userId)
		)->loadObject();
	}
}

// Usage
// $leadershipBonus = new LeadershipBonus();
// $leadershipBonus->calculateBonuses();