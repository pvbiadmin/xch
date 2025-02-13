<?php

namespace Templates\SB_Admin\Tmpl\Sidenav;

require_once 'bpl/mods/helpers.php';
require_once 'bpl/mods/url_sef.php';

use Joomla\CMS\Uri\Uri;

use function BPL\Mods\Helpers\settings;
use function BPL\Mods\Url_SEF\sef;
use function BPL\Mods\Helpers\db;
use function BPL\Mods\Helpers\session_get;
use function BPL\Mods\Helpers\user;

function main()
{
    $user_id = session_get('user_id');
    $admintype = session_get('admintype');

    if (!$user_id) {
        return '';
    }

    $user = user($user_id);
    $usertype = $user->usertype;

    if ($usertype == 'Admin') {
        return admin($admintype, $user_id);
    }

    return member($user_id);
}

function admin($admintype, $user_id)
{
    $user = user($user_id);
    $username = $user->username;

    $dashboard = dashboard_admin($admintype);
    $members = members_admin($admintype);
    $codes = codes($admintype);
    $logs = logs_admin($admintype);
    $efund_admin = efund($admintype, $user_id);
    $wallet_admin = wallet_admin($admintype);
    $fixed_daily_token_admin = fixed_daily_token($admintype);
    $investment = investment();
    $shop = shop($admintype, $user_id);
    $purchases = purchases_admin($user_id);
    $p2p_commerce = p2p_commerce();
    $trader_admin = trader($user_id);
    $p2p_trading = p2p_trading();
    $commission = commission($user_id);
    $settings_adjust = settings_adjust($admintype);
    $crons = crons($admintype);

    $core = '';
    $shop = '';
    $passive = '';
    $token = '';

    if (
        $dashboard
        || $members
        // || $codes
        || $logs
        || $efund_admin
        || $wallet_admin
    ) {
        $core = <<<HTML
        <div class="sb-sidenav-menu-heading">Page</div>
        {$dashboard}
        {$members}
        <!-- {$codes} -->
        {$logs}
        {$efund_admin}
        {$wallet_admin}
        {$commission}
        {$settings_adjust}
        {$crons}
        HTML;
    }

    if ($shop || $purchases || $p2p_commerce) {
        $shop = <<<HTML
        <div class="sb-sidenav-menu-heading">Shop</div>
        {$shop}
        {$purchases}
        {$p2p_commerce}
        HTML;
    }

    if (
        $fixed_daily_token_admin
        || $investment
    ) {
        $passive = <<<HTML
        <div class="sb-sidenav-menu-heading">Support</div>
        {$fixed_daily_token_admin}
        {$investment}
        HTML;
    }

    if ($trader_admin || $p2p_trading) {
        $token = <<<HTML
        <div class="sb-sidenav-menu-heading">Token</div>
        {$trader_admin}
        {$p2p_trading}
        HTML;
    }

    return <<<HTML
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    {$core}                    
                    {$shop}
                    {$passive}
                    {$token}                    
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as:</div>
                {$username}
            </div>
        </nav>
    </div>
    HTML;
}

function member($user_id)
{
    $user = user($user_id);
    $username = $user->username;

    $dashboard = dashboard_member($user_id);
    $wallet = wallet_member($user_id);
    $efund = efund('', $user_id);
    $fixed_daily_token = fixed_daily_token('');
    $investment = investment();
    $shop = shop('', $user_id);
    $commission = commission($user_id);
    $trader = trader($user_id);
    $p2p_trading = p2p_trading();

    $core = '';
    $token = '';

    if (
        $dashboard
        || $wallet
        || $efund
        || $fixed_daily_token
        || $shop
        || $commission
    ) {
        $core = <<<HTML
        <div class="sb-sidenav-menu-heading">Page</div>
        {$dashboard}
        {$wallet}
        {$efund}
        
        {$shop}
        {$commission}
        HTML;
    }

    if (
        $fixed_daily_token
        || $investment
    ) {
        $passive = <<<HTML
        <div class="sb-sidenav-menu-heading">Support</div>
        {$fixed_daily_token}
        {$investment}
        HTML;
    }

    if ($trader || $p2p_trading) {
        $token = <<<HTML
        <div class="sb-sidenav-menu-heading">Token</div>
        {$trader}
        {$p2p_trading}
        HTML;
    }

    return <<<HTML
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
            <div class="sb-sidenav-menu">
                <div class="nav">
                    {$core}  
                    {$passive}
                    {$token}                                     
                </div>
            </div>
            <div class="sb-sidenav-footer">
                <div class="small">Logged in as:</div>
                {$username}
            </div>
        </nav>
    </div>
    HTML;
}

function dashboard_admin($admintype)
{
    // dashboard links
    $dashboard_link = sef(43);
    $sales_overview_link = sef(79);
    $account_summary_link = sef(1);
    $active_income_link = sef(2);
    $system_reset_link = sef(97);

    $system_reset = '';

    if ($admintype === 'Super') {
        $system_reset = <<<HTML
    <a class="nav-link" href="{$system_reset_link}">System Reset</a>
    HTML;
    }

    return <<<HTML
<a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseDashboardAdmin"
    aria-expanded="false" aria-controls="collapseDashboardAdmin">
    <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
    Dashboard
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseDashboardAdmin" aria-labelledby="headingOne"
    data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <!-- <a class="nav-link" href="{$dashboard_link}">Home</a> -->
        <a class="nav-link" href="{$sales_overview_link}">System Monitoring</a>
        <!-- <a class="nav-link" href="{$account_summary_link}">Account Summary</a> -->
        <!-- <a class="nav-link" href="{$active_income_link}">Active Income</a> -->
        {$system_reset}
    </nav>
</div>
HTML;
}

function dashboard_member($user_id)
{
    // $sa = settings('ancillaries');
    // $payment_mode = $sa->payment_mode;

    // $user = user($user_id);
    // $account_type = $user->account_type;

    $home_link = sef(2);
    $account_info_link = sef(44);
    $add_account_link = sef(144);
    // $buy_package_link = sef(10);

    $buy_package = '';

    /* if ($account_type === 'starter' && $payment_mode === 'ECASH') {
        $buy_package = <<<HTML
        <a class="nav-link" href="{$buy_package_link}">Buy Package</a>
        HTML;
    } */

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseDashboardMember"
        aria-expanded="false" aria-controls="collapseDashboardMember">
        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
        Dashboard
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseDashboardMember" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$home_link}">Home</a>
            <a class="nav-link" href="{$account_info_link}">Member Details</a>
            <a class="nav-link" href="{$add_account_link}">Signup</a>
            {$buy_package}
        </nav>
    </div>
    HTML;
}

function wallet_member($user_id)
{
    $sa = settings('ancillaries');
    $withdrawal_mode = $sa->withdrawal_mode;

    $user = user($user_id);
    $account_type = $user->account_type;

    if (!($account_type !== 'starter' && $withdrawal_mode === 'standard')) {
        return '';
    }

    $convert_to_efund_link = sef(15);
    $payout_logs_link = sef(49);
    $withdrawal_request_link = sef(113);
    $withdrawal_completed_link = sef(111);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseWalletMember"
        aria-expanded="false" aria-controls="collapseWalletMember">
        <div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>
        My Wallet
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseWalletMember" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <!-- <a class="nav-link" href="{$convert_to_efund_link}">Transfer Efund</a> -->
            <a class="nav-link" href="{$payout_logs_link}">Payout Logs</a>
            <a class="nav-link" href="{$withdrawal_request_link}">Withdrawal Request</a>
            <a class="nav-link" href="{$withdrawal_completed_link}">Withdrawal Completed</a>
        </nav>
    </div>
    HTML;
}

function members_admin($admintype)
{
    $sa = settings('ancillaries');

    $list_members_link = sef(40);
    $registration_link = sef(144);

    $payment_mode = $sa->payment_mode;

    if ($payment_mode === 'CODE') {
        $registration_link = sef(65);
    }

    $member_info_link = sef(44);
    $profile_update_link = sef(60);

    $admin_account_update_link = sef(6);

    $admin_account_update = '';

    if ($admintype === 'Super') {
        $admin_account_update = <<<HTML
    <a class="nav-link" href="{$admin_account_update_link}">Admin Account Update</a>
    HTML;
    }

    return <<<HTML
<a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseMembersAdmin"
    aria-expanded="false" aria-controls="collapseMembersAdmin">
    <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
    Members
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseMembersAdmin" aria-labelledby="headingOne"
    data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a class="nav-link" href="{$list_members_link}">All Members</a>
        <a class="nav-link" href="{$registration_link}">Registration</a>
        <!-- <a class="nav-link" href="{$member_info_link}">Information</a>
        <a class="nav-link" href="{$profile_update_link}">Edit Details</a> -->
        {$admin_account_update}
    </nav>
</div>
HTML;
}

function codes($admintype)
{
    $available_codes_link = sef(66);
    $used_codes_link = sef(68);
    $inventory_codes_link = sef(67);
    $inventory_codes_admin_link = sef(7);

    $inventory_codes_admin = '';

    if ($admintype === 'Super') {
        $inventory_codes_admin = <<<HTML
    <a class="nav-link" href="{$inventory_codes_admin_link}">Inventory (Admin)</a>
    HTML;
    }

    $search_codes_link = sef(42);
    $generate_codes_link = sef(34);

    return <<<HTML
<a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCodes"
    aria-expanded="false" aria-controls="collapseCodes">
    <div class="sb-nav-link-icon"><i class="fas fa-code"></i></div>
    Codes
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseCodes" aria-labelledby="headingOne"
    data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a class="nav-link" href="{$available_codes_link}">Available</a>
        <a class="nav-link" href="{$used_codes_link}">Used</a>
        <a class="nav-link" href="{$inventory_codes_link}">Inventory</a>
        {$inventory_codes_admin}
        <a class="nav-link" href="{$search_codes_link}">Search</a>
        <a class="nav-link" href="{$generate_codes_link}">Generate</a>
    </nav>
</div>
HTML;
}

function logs_admin($admintype)
{
    $activity_logs_link = sef(3);

    $activity_logs = '';

    if ($admintype === 'Super') {
        $activity_logs = <<<HTML
    <a class="nav-link" href="{$activity_logs_link}">Activity</a>
    HTML;
    }

    $transaction_logs_link = sef(106);

    $income_logs_link = sef(35);

    $income_logs = '';

    if ($admintype === 'Super') {
        $income_logs = <<<HTML
    <a class="nav-link" href="{$income_logs_link}">Income Logs</a>
    HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseLogsAdmin"
        aria-expanded="false" aria-controls="collapseLogsAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-edit"></i></div>
        Logs
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseLogsAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            {$activity_logs}
            <a class="nav-link" href="{$transaction_logs_link}">Transactions</a>
            {$income_logs}            
        </nav>
    </div>
    HTML;
}

function efund($admintype, $user_id)
{
    $sa = settings('ancillaries');

    $efund_name = $sa->efund_name;

    $user = user($user_id);
    $account_type = $user->account_type;

    $buy_efund_link = sef(73);
    $buy_efund_confirmed_link = sef(74);
    $buy_efund_pending_link = sef(76);
    $add_efund_link = sef(4);
    $efund_transfer_link = sef(16);
    $buy_efund_logs_link = sef(75);
    $withdraw_efund_link = sef(57);
    $pending_efund_withdrawal_link = sef(58);
    $approved_efund_withdrawals_link = sef(59);
    $efund_withdrawal_logs_link = sef(122);

    $buy_efund_pending = <<<HTML
        <a class="nav-link" href="{$buy_efund_pending_link}">Request {$efund_name} Pending</a>
    HTML;

    $add_efund = '';
    $transfer = '';
    $pending_efund_withdrawals = '';
    $withdraw_efund = '';
    $approved_efund_withdrawals = '';
    $efund_withdrawal_logs = '';

    if ($admintype === 'Super') {
        $add_efund = <<<HTML
        <a class="nav-link" href="{$add_efund_link}">Add {$efund_name}</a>
    HTML;
        $pending_efund_withdrawals = <<<HTML
        <a class="nav-link" href="{$pending_efund_withdrawal_link}">Pending {$efund_name} Withdrawals</a>
    HTML;
        $transfer = <<<HTML
        <a class="nav-link" href="{$efund_transfer_link}">{$efund_name} Transfer</a>
    HTML;
    }

    if ($account_type !== 'starter') {
        $withdraw_efund = <<<HTML
        <a class="nav-link" href="{$withdraw_efund_link}">Withdraw {$efund_name}</a>
    HTML;
        $approved_efund_withdrawals = <<<HTML
        <a class="nav-link" href="{$approved_efund_withdrawals_link}">Approved {$efund_name} Withdrawals</a>
    HTML;
        $efund_withdrawal_logs = <<<HTML
        <a class="nav-link" href="{$efund_withdrawal_logs_link}">{$efund_name} Withdrawal Logs</a>
    HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseEfundAdmin"
        aria-expanded="false" aria-controls="collapseEfundAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-bank"></i></div>
        Bank
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseEfundAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">            
            <a class="nav-link" href="{$buy_efund_link}">Request {$efund_name}</a> 
            <a class="nav-link" href="{$buy_efund_confirmed_link}">Request {$efund_name} History</a>    
            {$buy_efund_pending}     
            {$add_efund}      
            $transfer
            <!-- <a class="nav-link" href="{$buy_efund_logs_link}">Request {$efund_name} Logs</a> -->
            <!-- {$withdraw_efund}
            {$pending_efund_withdrawals}
            {$approved_efund_withdrawals}
            {$efund_withdrawal_logs} -->
        </nav>
    </div>
    HTML;
}

function wallet_admin($admintype)
{
    $sa = settings('ancillaries');

    $efund_name = $sa->efund_name;

    $withdrawal_mode = $sa->withdrawal_mode;

    if ($withdrawal_mode !== 'standard') {
        return '';
    }

    // $add_ecash_link = sef(116);

    $add_ecash = '';

    // if ($admintype === 'Super') {
    //     $add_ecash = <<<HTML
    //     <a class="nav-link" href="{$add_ecash_link}">Add E-Cash</a>
    //     HTML;
    // }

    $convert_to_efund_link = sef(15);
    $withdrawal_requests_link = sef(113);
    $withdrawal_confirm_link = sef(112);
    $withdrawal_completed_link = sef(111);
    $payout_logs_link = sef(49);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseWalletAdmin"
        aria-expanded="false" aria-controls="collapseWalletAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-wallet"></i></div>
        My Wallet
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseWalletAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">    
            {$add_ecash}        
            <!-- <a class="nav-link" href="{$convert_to_efund_link}">Convert to {$efund_name}</a> 
            <a class="nav-link" href="{$withdrawal_requests_link}">Withdrawal Request</a> -->
            <a class="nav-link" href="{$withdrawal_confirm_link}">Pending Withdrawals</a>
            <a class="nav-link" href="{$withdrawal_completed_link}">Withdrawals History</a>
            <!-- <a class="nav-link" href="{$payout_logs_link}">Payout Logs</a> -->
        </nav>
    </div>
    HTML;
}

function fixed_daily_token($admintype)
{
    $sp = settings('plans');

    if (!$sp->fixed_daily_token) {
        return '';
    }

    $fixed_daily_token_name = $sp->fixed_daily_token_name;

    $fixed_daily_token_link = sef(151);
    $withdraw_token_link = sef(98);
    $completed_token_withdrawals_link = sef(99);
    $pending_token_withdrawals_link = sef(100);

    $pending_token_withdrawals = '';

    if ($admintype === 'Super') {
        $pending_token_withdrawals = <<<HTML
        <a class="nav-link" href="{$pending_token_withdrawals_link}">Pending B2P Withdrawals</a>
        HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseFixedDailyAdmin"
        aria-expanded="false" aria-controls="collapseFixedDailyAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-coins"></i></div>
        B2P Holdings
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseFixedDailyAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$fixed_daily_token_link}">{$fixed_daily_token_name}</a> 
            <a class="nav-link" href="{$withdraw_token_link}">Withdraw B2P</a>
            <a class="nav-link" href="{$completed_token_withdrawals_link}">Completed B2P Withdrawals</a>
            {$pending_token_withdrawals}
        </nav>
    </div>
    HTML;
}

function commission($user_id)
{
    $indirect_referral = indirect_referral($user_id);
    $echelon = echelon($user_id);
    $unilevel = unilevel($user_id);
    $binary = binary($user_id);
    $leadership_binary = leadership_binary($user_id);
    $passup_binary = passup_binary($user_id);
    $leadership_passive = leadership_passive($user_id);
    $leadership_fast_track_principal = leadership_fast_track_principal($user_id);
    $harvest = harvest($user_id);
    $table_matrix = table_matrix($user_id);

    return <<<HTML
    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCommissions"
        aria-expanded="false" aria-controls="collapseCommissions">
        <div class="sb-nav-link-icon"><i class="fas fa-hand-holding-usd"></i></div>
        Leadership
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>

    <div class="collapse" id="collapseCommissions" aria-labelledby="headingTwo"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionCommissions">
            
            {$indirect_referral}
            {$echelon}
            {$unilevel}
            {$binary}
            {$leadership_binary}
            {$passup_binary}
            {$leadership_passive}
            {$leadership_fast_track_principal}
            {$harvest}
            {$table_matrix}

        </nav>
    </div>
    HTML;
}

function settings_adjust($admintype)
{
    if ($admintype !== 'Super') {
        return '';
    }

    $sp = settings('plans');

    $links = [
        'plans' => sef(88),
        'entry' => sef(81),
        'direct_referral' => sef(91),
        'direct_referral_fast_track_principal' => sef(158),
        'indirect_referral' => sef(82),
        'echelon' => sef(146),
        'unilevel' => sef(93),
        'royalty' => sef(90),
        'binary_pair' => sef(80),
        'leadership_binary' => sef(84),
        'leadership_passive' => sef(85),
        'leadership_fast_track_principal' => sef(155),
        'harvest' => sef(121),
        'matrix' => sef(86),
        'upline_support' => sef(117),
        'passup_binary' => sef(148),
        'elite_reward' => sef(119),
        'investment' => sef(83),
        'trading' => sef(92),
        'merchant' => sef(87),
        'ancillaries' => sef(94),
        'freeze' => sef(129),
    ];

    $settings = [
        'direct_referral' => $sp->direct_referral,
        'direct_referral_fast_track_principal' => $sp->direct_referral_fast_track_principal,
        'indirect_referral' => $sp->indirect_referral,
        'echelon' => $sp->echelon,
        'unilevel' => $sp->unilevel,
        'royalty' => $sp->royalty,
        'binary_pair' => $sp->binary_pair,
        'leadership_binary' => $sp->leadership_binary && $sp->binary_pair,
        'leadership_passive' => $sp->leadership_passive && ($sp->etrade || $sp->top_up || $sp->fast_track || $sp->fixed_daily),
        'leadership_fast_track_principal' => $sp->leadership_fast_track_principal,
        'harvest' => $sp->harvest,
        'matrix' => $sp->matrix,
        'upline_support' => $sp->upline_support,
        'passup_binary' => $sp->passup_binary,
        'elite_reward' => $sp->elite_reward,
        'investment' => $sp->etrade || $sp->top_up || $sp->fast_track || $sp->fixed_daily,
        'trading' => $sp->trading,
        'merchant' => ($sp->unilevel || $sp->redundant_binary) && $sp->merchant,
    ];

    $navLinks = '';

    foreach ($settings as $key => $enabled) {
        if ($enabled) {
            $name = $sp->{"{$key}_name"} ?? ucfirst(str_replace('_', ' ', $key));
            $navLinks .= <<<HTML
            <a class="nav-link" href="{$links[$key]}">{$name}</a>
            HTML;
        }
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseSettings"
        aria-expanded="false" aria-controls="collapseSettings">
        <div class="sb-nav-link-icon"><i class="fas fa-gear"></i></div>
        Settings
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseSettings" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$links['plans']}">Plans</a> 
            <a class="nav-link" href="{$links['entry']}">Entry</a>
            {$navLinks}
            <a class="nav-link" href="{$links['ancillaries']}">Ancillaries</a> 
            <a class="nav-link" href="{$links['freeze']}">Freeze</a>
        </nav>
    </div>
    HTML;
}

function crons($admintype)
{
    if ($admintype !== 'Super') {
        return '';
    }

    $cron_efund = cron_efund();
    $cron_unilevel = cron_unilevel();
    $cron_grace_period = cron_grace_period();
    $cron_fast_track = cron_fast_track();
    $cron_fixed_daily = cron_fixed_daily();
    $cron_fixed_daily_token = cron_fixed_daily_token();
    $cron_flushout = cron_flushout();

    return <<<HTML
    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCrons"
        aria-expanded="false" aria-controls="collapseCrons">
        <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
        Crons
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>

    <div class="collapse" id="collapseCrons" aria-labelledby="headingTwo"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionCrons">
            
            {$cron_efund}
            {$cron_unilevel}
            {$cron_grace_period}
            {$cron_fast_track}
            {$cron_fixed_daily}
            {$cron_fixed_daily_token}
            {$cron_flushout}

        </nav>
    </div>
    HTML;
}

function cron_efund()
{
    $sa = settings('ancillaries');

    $efund_name = $sa->efund_name;

    $cron_convert_reset_link = Uri::root(true) . '/crons/cron_efund_convert_reset.php';
    $cron_buy_reset_link = Uri::root(true) . '/crons/cron_efund_request_reset.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronEfund"
        aria-expanded="false" aria-controls="collapseCronEfund">
        {$efund_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronEfund" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_convert_reset_link}">Convert Reset</a> 
            <a class="nav-link" href="{$cron_buy_reset_link}">Buy Reset</a>                        
        </nav>
    </div>
    HTML;
}

function cron_unilevel()
{
    $sp = settings('plans');

    $unilevel_name = $sp->unilevel_name;

    $cron_unilevel_maintain_link = Uri::root(true) . '/crons/cron_unilevel_maintain.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronUnilevel"
        aria-expanded="false" aria-controls="collapseCronUnilevel">
        {$unilevel_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronUnilevel" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_unilevel_maintain_link}">{$unilevel_name} Maintain</a>                        
        </nav>
    </div>
    HTML;
}

function cron_grace_period()
{
    $grace_period_link = Uri::root(true) . '/crons/cron_grace_period.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronGracePeriod"
        aria-expanded="false" aria-controls="collapseCronGracePeriod">
        Grace Period
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronGracePeriod" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$grace_period_link}">Grace Period</a>                        
        </nav>
    </div>
    HTML;
}

function cron_fast_track()
{
    $sp = settings('plans');

    $fast_track_name = $sp->fast_track_name;

    $cron_fast_track_link = Uri::root(true) . '/crons/cron_fast_track.php';
    $cron_fast_track_processing_link = Uri::root(true) . '/crons/cron_fast_track_processing.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronFastTrack"
        aria-expanded="false" aria-controls="collapseCronFastTrack">
        {$fast_track_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronFastTrack" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_fast_track_link}">{$fast_track_name}</a>  
            <a class="nav-link" href="{$cron_fast_track_processing_link}">Processing</a>                      
        </nav>
    </div>
    HTML;
}

function cron_fixed_daily()
{
    $sp = settings('plans');

    $fixed_daily_name = $sp->fixed_daily_name;

    $cron_fixed_daily_link = Uri::root(true) . '/crons/cron_fixed_daily.php';
    $cron_fixed_daily_procesing_link = Uri::root(true) . '/crons/cron_fixed_daily_processing.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronFixedDaily"
        aria-expanded="false" aria-controls="collapseCronFixedDaily">
        {$fixed_daily_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronFixedDaily" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_fixed_daily_link}">{$fixed_daily_name}</a>  
            <a class="nav-link" href="{$cron_fixed_daily_procesing_link}">Processing</a>                      
        </nav>
    </div>
    HTML;
}

function cron_fixed_daily_token()
{
    $sp = settings('plans');

    $fixed_daily_token_name = $sp->fixed_daily_token_name;

    $cron_fixed_daily_token_link = Uri::root(true) . '/crons/cron_fixed_daily_token.php';
    $cron_fixed_daily_token_processing_link = Uri::root(true) . '/crons/cron_fixed_daily_token_processing.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronFixedDailyToken"
        aria-expanded="false" aria-controls="collapseCronFixedDailyToken">
        {$fixed_daily_token_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronFixedDailyToken" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_fixed_daily_token_link}">{$fixed_daily_token_name}</a>  
            <a class="nav-link" href="{$cron_fixed_daily_token_processing_link}">Processing</a>                      
        </nav>
    </div>
    HTML;
}

function cron_flushout()
{
    $sp = settings('plans');

    $binary_pair_name = $sp->binary_pair_name;
    $indirect_referral_name = $sp->indirect_referral_name;
    $leadership_binary_name = $sp->leadership_binary_name;
    $leadership_passive_name = $sp->leadership_passive_name;
    $unilevel_name = $sp->unilevel_name;

    $cron_flushout_binary_link = Uri::root(true) . '/crons/cron_binary_flushout.php';
    $cron_flushout_indirect_link = Uri::root(true) . '/crons/cron_indirect_flushout.php';
    $cron_flushout_leadership_binary_link = Uri::root(true) . '/crons/cron_leadership_binary_flushout.php';
    $cron_flushout_leadership_passive_link = Uri::root(true) . '/crons/cron_leadership_passive_flushout.php';
    $cron_unilevel_flushout_link = Uri::root(true) . '/crons/cron_unilevel_flushout.php';

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseCronFlushout"
        aria-expanded="false" aria-controls="collapseCronFlushout">
        Flushout
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseCronFlushout" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCrons">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$cron_flushout_binary_link}">{$binary_pair_name}</a>  
            <a class="nav-link" href="{$cron_flushout_indirect_link}">{$indirect_referral_name}</a>
            <a class="nav-link" href="{$cron_flushout_leadership_binary_link}">{$leadership_binary_name}</a>
            <a class="nav-link" href="{$cron_flushout_leadership_passive_link}">{$leadership_passive_name}</a>
            <a class="nav-link" href="{$cron_unilevel_flushout_link}">{$unilevel_name}</a>                      
        </nav>
    </div>
    HTML;
}

function indirect_referral($user_id)
{
    $sp = settings('plans');

    $indirect_referral_name = $sp->indirect_referral_name;

    if (!($sp->indirect_referral && user_indirect($user_id))) {
        return '';
    }

    $indirect_line_link = sef(24);
    $income_chart_link = sef(36);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseIndirectReferral"
        aria-expanded="false" aria-controls="collapseIndirectReferral">
        {$indirect_referral_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseIndirectReferral" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$indirect_line_link}">Indirect Line</a> 
            <a class="nav-link" href="{$income_chart_link}">Income Chart</a>                        
        </nav>
    </div>
    HTML;
}

function echelon($user_id)
{
    $sp = settings('plans');

    if (!($sp->echelon && user_echelon($user_id))) {
        return '';
    }

    $echelon_name = $sp->echelon_name;

    $team_line_link = sef(147);
    $profit_board_link = sef(145);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseEchelon"
        aria-expanded="false" aria-controls="collapseEchelon">
        {$echelon_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseEchelon" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$team_line_link}">Team Line</a> 
            <a class="nav-link" href="{$profit_board_link}">Profit Board</a>                        
        </nav>
    </div>
    HTML;
}

function unilevel($user_id)
{
    $sp = settings('plans');
    $sul = settings('unilevel');

    $user = user($user_id);

    $account_type = $user->account_type;

    if (
        !($sp->unilevel
            && $sul->{$account_type . '_unilevel_level'} > 0
            && !empty(user_unilevel($user_id)))
    ) {
        return '';
    }

    $unilevel_name = $sp->unilevel_name;

    $genealogy_link = sef(33);
    $profit_board_link = sef(109);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseEchelon"
        aria-expanded="false" aria-controls="collapseEchelon">
        {$unilevel_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseEchelon" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$genealogy_link}">Genealogy</a> 
            <a class="nav-link" href="{$profit_board_link}">Profit Board</a>                        
        </nav>
    </div>
    HTML;
}

function binary($user_id)
{
    $sp = settings('plans');

    if (!($sp->binary_pair && user_binary($user_id))) {
        return '';
    }

    $binary_pair_name = $sp->binary_pair_name;

    $graphical_link = sef(21);
    $listing_link = sef(14);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseBinary"
        aria-expanded="false" aria-controls="collapseBinary">
        {$binary_pair_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseBinary" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$graphical_link}">Graphical</a> 
            <a class="nav-link" href="{$listing_link}">Listing</a>                        
        </nav>
    </div>
    HTML;
}

function leadership_binary($user_id)
{
    $sp = settings('plans');
    $slb = settings('leadership');

    $user = user($user_id);
    $account_type = $user->account_type;

    if (
        !(
            $account_type !== 'starter'
            && $sp->binary_pair
            && $sp->leadership_binary
            && $slb->{$account_type . '_leadership_level'} > 0
        )
    ) {
        return '';
    }

    $leadership_binary_name = $sp->leadership_binary_name;

    $direct_line_link = sef(25);
    $leadership_chart_link = sef(37);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseLeadershipBinary"
        aria-expanded="false" aria-controls="collapseLeadershipBinary">
        {$leadership_binary_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseLeadershipBinary" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$direct_line_link}">Direct Line</a> 
            <a class="nav-link" href="{$leadership_chart_link}">Leadership Chart</a>                        
        </nav>
    </div>
    HTML;
}

function passup_binary($user_id)
{
    $sp = settings('plans');

    if (!($sp->passup_binary && user_passup_binary($user_id))) {
        return '';
    }

    $passup_binary_name = $sp->passup_binary_name;

    $infinity_line_link = sef(150);
    $infinity_bonus_board_link = sef(149);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapsePassupBinary"
        aria-expanded="false" aria-controls="collapsePassupBinary">
        {$passup_binary_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapsePassupBinary" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$infinity_line_link}">Infinity Line</a> 
            <a class="nav-link" href="{$infinity_bonus_board_link}">Infinity Bonus Board</a>                        
        </nav>
    </div>
    HTML;
}

function leadership_passive($user_id)
{
    $sp = settings('plans');
    $slp = settings('leadership_passive');

    $user = user($user_id);
    $account_type = $user->account_type;

    if (
        !(
            $account_type !== 'starter'
            && (
                $sp->leadership_passive
                && $slp->{$account_type . '_leadership_passive_level'} > 0
                && (
                    $sp->etrade
                    || $sp->top_up
                    || $sp->fast_track
                    || $sp->fixed_daily
                )
            )
        )
    ) {
        return '';
    }

    $leadership_passive_name = $sp->leadership_passive_name;

    $bounty_line_link = sef(38);
    $bounty_chart_link = sef(39);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseLeadershipPassive"
        aria-expanded="false" aria-controls="collapseLeadershipPassive">
        {$leadership_passive_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseLeadershipPassive" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$bounty_line_link}">Bounty Line</a> 
            <a class="nav-link" href="{$bounty_chart_link}">Bounty Chart</a>                        
        </nav>
    </div>
    HTML;
}

function leadership_fast_track_principal($user_id)
{
    $sp = settings('plans');
    $slftp = settings('leadership_fast_track_principal');

    $user = user($user_id);
    $account_type = $user->account_type;

    if (
        !(
            $account_type !== 'starter'
            && (
                $sp->leadership_fast_track_principal
                && $slftp->{$account_type . '_leadership_fast_track_principal_level'} > 0
                && $sp->fast_track
            )
        )
    ) {
        return '';
    }

    $lftp_name = $sp->leadership_fast_track_principal_name;

    $bounty_line_link = sef(157);
    $bounty_chart_link = sef(156);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseLeadershipPassive"
        aria-expanded="false" aria-controls="collapseLeadershipPassive">
        {$lftp_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseLeadershipPassive" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$bounty_line_link}"><!-- {$lftp_name} Line -->Genealogy Tree</a> 
            <a class="nav-link" href="{$bounty_chart_link}">{$lftp_name} Chart</a>                        
        </nav>
    </div>
    HTML;
}

function harvest($user_id)
{
    $sp = settings('plans');

    $user_harvest_associate = user_harvest($user_id, 'associate');
    $user_harvest_basic = user_harvest($user_id, 'basic');

    if (
        !(
            ($user_harvest_associate || $user_harvest_basic)
            && $sp->harvest
        )
    ) {
        return '';
    }

    $harvest_name = $sp->harvest_name;

    $silver_harvest_link = sef(22);
    $bronze_harvest_link = sef(23);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseHarvest"
        aria-expanded="false" aria-controls="collapseHarvest">
        {$harvest_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseHarvest" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$silver_harvest_link}">Silver</a> 
            <a class="nav-link" href="{$bronze_harvest_link}">Bronze</a>                        
        </nav>
    </div>
    HTML;
}

function table_matrix($user_id)
{
    $se = settings('entry');
    $sp = settings('plans');

    // Early return if conditions are not met
    if (!($sp->table_matrix && !$se->executive_entry && !$se->regular_entry && !$se->associate_entry)) {
        return '';
    }

    // Define link mappings
    $links = [
        'basic' => sef(28),
        'associate' => sef(27),
        'regular' => sef(32),
        'executive' => sef(31),
        'director' => sef(30),
        'chairman' => sef(29),
        'deposit' => sef(61),
    ];

    // Generate structure links based on user shares
    $structures = [];
    $types = ['basic', 'associate', 'regular', 'executive', 'director', 'chairman'];
    foreach ($types as $type) {
        if (has_user_share($type, $user_id)) {
            $ctype = ucfirst($type);
            $structures[] = <<<HTML
            <a class="nav-link" href="{$links[$type]}">{$ctype} Structure</a>
            HTML;
        }
    }

    // Add deposit link
    $structures[] = <<<HTML
    <a class="nav-link" href="{$links['deposit']}">Deposit Profit Share</a>
    HTML;

    // Combine all structures into a single string
    $structureLinks = implode('', $structures);

    // Return the final HTML
    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseMatrix"
        aria-expanded="false" aria-controls="collapseMatrix">
        {$sp->table_matrix_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseMatrix" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordionCommissions">
        <nav class="sb-sidenav-menu-nested nav">
            {$structureLinks}
        </nav>
    </div>
    HTML;
}

function investment()
{
    $sp = settings('plans');

    if (
        !(
            $sp->etrade
            || $sp->top_up
            || $sp->fixed_daily
            || $sp->fast_track
        )
    ) {
        return '';
    }

    $etrade_name = $sp->etrade_name;
    $top_up_name = $sp->top_up_name;
    $fixed_daily_name = $sp->fixed_daily_name;
    $fast_track_name = $sp->fast_track_name;

    $etrade_link = sef(114);
    $top_up_link = sef(103);
    $fixed_daily_link = sef(17);
    $fast_track_link = sef(19);

    $etrade = '';
    $top_up = '';
    $fixed_daily = '';
    $fast_track = '';

    if ($sp->etrade) {
        $etrade = <<<HTML
        <a class="nav-link" href="{$etrade_link}">{$etrade_name}</a>
        HTML;
    }

    if ($sp->top_up) {
        $top_up = <<<HTML
        <a class="nav-link" href="{$top_up_link}">{$top_up_name}</a>
        HTML;
    }

    if ($sp->fixed_daily) {
        $fixed_daily = <<<HTML
        <a class="nav-link" href="{$fixed_daily_link}">{$fixed_daily_name}</a>
        HTML;
    }

    if ($sp->fast_track) {
        $fast_track = <<<HTML
        <a class="nav-link" href="{$fast_track_link}">{$fast_track_name}</a>
        HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseInvestment"
        aria-expanded="false" aria-controls="collapseInvestment">
        <div class="sb-nav-link-icon"><i class="fas fa-percent"></i></div>
        Partnership Program
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseInvestment" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            {$etrade}  
            {$top_up}   
            {$fixed_daily}     
            {$fast_track}              
        </nav>
    </div>
    HTML;
}

function shop($admintype, $user_id)
{
    $sp = settings('plans');
    $sul = settings('unilevel');

    $user = user($user_id);
    $account_type = $user->account_type;

    if (
        !(($sp->unilevel &&
            $sul->{$account_type . '_unilevel_level'}) ||
            $sp->redundant_binary)
    ) {
        return '';
    }

    $buy_items_link = sef(9);
    $redeem_point_rewards_link = sef(64);
    $transfer_point_link = sef(133);
    $repeat_purchase_items_list_link = sef(72);
    $point_reward_redemption_list_link = sef(53);
    $add_points_link = sef(132);
    $repeat_purchase_link = sef(69);
    $point_rewards_link = sef(50);

    $add_points = '';
    $repeat_purchase_items = '';
    $point_rewards_items = '';

    if ($admintype === 'Super') {
        $add_points = <<<HTML
        <a class="nav-link" href="{$add_points_link}">Add Points</a>
        HTML;
        $repeat_purchase_items = <<<HTML
        <a class="nav-link" href="{$repeat_purchase_link}">Repeat Purchase Items</a>
        HTML;
        $point_rewards_items = <<<HTML
        <a class="nav-link" href="{$point_rewards_link}">Point Rewards Items</a>
        HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseShopAdmin"
        aria-expanded="false" aria-controls="collapseShopAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-shopping-basket"></i></div>
        Online Shop
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseShopAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$buy_items_link}">Buy Items</a> 
            <a class="nav-link" href="{$redeem_point_rewards_link}">Redeem Point Rewards</a> 
            <a class="nav-link" href="{$transfer_point_link}">Transfer Points</a>
            <a class="nav-link" href="{$repeat_purchase_items_list_link}">Repeat Purchase Items List</a>
            <a class="nav-link" href="{$point_reward_redemption_list_link}">Point Reward Redemption List</a>
            {$add_points} 
            {$repeat_purchase_items}
            {$point_rewards_items}       
        </nav>
    </div>
    HTML;
}

function purchases_admin($user_id)
{
    $sp = settings('plans');
    $sul = settings('unilevel');

    $user = user($user_id);
    $account_type = $user->account_type;

    if (
        !(($sp->unilevel &&
            $sul->{$account_type . '_unilevel_level'} > 0) ||
            $sp->redundant_binary)
    ) {
        return '';
    }

    $purchase_history_link = sef(47);
    $purchase_confirm_link = sef(62);
    $reward_redemption_history_link = sef(48);
    $reward_redemption_confirm_link = sef(78);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapsePurchasesAdmin"
        aria-expanded="false" aria-controls="collapsePurchasesAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-tags"></i></div>
        Purchases
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapsePurchasesAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$purchase_history_link}">Overall Purchase History</a> 
            <a class="nav-link" href="{$purchase_confirm_link}">Purchase Items Confirm</a> 
            <a class="nav-link" href="{$reward_redemption_history_link}">Overall Reward Redemption History</a>
            <a class="nav-link" href="{$reward_redemption_confirm_link}">Reward Redemption Confirm</a>                       
        </nav>
    </div>
    HTML;
}

function p2p_commerce()
{
    $sp = settings('plans');

    if (!$sp->p2p_commerce) {
        return '';
    }

    $p2p_commerce_name = $sp->p2p_commerce_name;

    $seller_link = sef(126);
    $buyer_link = sef(127);
    $logs_link = sef(128);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseP2PTrading"
        aria-expanded="false" aria-controls="collapseP2PTrading">
        <div class="sb-nav-link-icon"><i class="fas fa-handshake"></i></div>
        {$p2p_commerce_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseP2PTrading" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$seller_link}">Seller</a> 
            <a class="nav-link" href="{$buyer_link}">Buyer</a> 
            <a class="nav-link" href="{$logs_link}">Logs</a>                                  
        </nav>
    </div>
    HTML;
}

function trader($user_id)
{
    $sp = settings('plans');

    $user = user($user_id);
    $usertype = $user->usertype;

    if (!$sp->trading) {
        return '';
    }

    $trading_name = $sp->trading_name;

    $converter_link = sef(12);
    $merchant_link = sef(45);
    $trader_link = sef(105);

    $merchant = '';
    $trader = '';

    if ($usertype === 'Admin') {
        $merchant = <<<HTML
        <a class="nav-link" href="{$merchant_link}">Merchant</a>
        HTML;
        $trader = <<<HTML
        <a class="nav-link" href="{$trader_link}">Trader</a>
        HTML;
    }

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseTraderAdmin"
        aria-expanded="false" aria-controls="collapseTraderAdmin">
        <div class="sb-nav-link-icon"><i class="fas fa-line-chart"></i></div>
        {$trading_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseTraderAdmin" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$converter_link}">Portal</a> 
            {$merchant}
            {$trader}                                
        </nav>
    </div>
    HTML;
}

function p2p_trading()
{
    $sp = settings('plans');

    if (!$sp->p2p_trading) {
        return '';
    }

    $p2p_trading_name = $sp->p2p_trading_name;

    $sell_link = sef(54);
    $buy_link = sef(55);
    $trade_history_link = sef(56);

    return <<<HTML
    <a class="nav-link collapsed" href="javascript:void(0);" data-bs-toggle="collapse" data-bs-target="#collapseP2PTrading"
        aria-expanded="false" aria-controls="collapseP2PTrading">
        <div class="sb-nav-link-icon"><i class="fas fa-exchange-alt"></i></div>
        {$p2p_trading_name}
        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
    </a>
    <div class="collapse" id="collapseP2PTrading" aria-labelledby="headingOne"
        data-bs-parent="#sidenavAccordion">
        <nav class="sb-sidenav-menu-nested nav">
            <a class="nav-link" href="{$sell_link}">Sell</a> 
            <a class="nav-link" href="{$buy_link}">Buy</a> 
            <a class="nav-link" href="{$trade_history_link}">Trade History</a>                                  
        </nav>
    </div>
    HTML;
}

function user_indirect($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_indirect ' .
        'WHERE user_id = ' . $db->quote($user_id)
    )->loadObject();
}

function user_binary($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_binary ' .
        'WHERE user_id = ' . $db->quote($user_id)
    )->loadObject();
}

function user_echelon($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_echelon ' .
        'WHERE user_id = ' . $db->quote($user_id)
    )->loadObject();
}

function user_unilevel($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_unilevel ' .
        'WHERE user_id = ' . $db->quote($user_id)
    )->loadObject();
}

function user_passup_binary($user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_passup_binary ' .
        'WHERE user_id = ' . $db->quote($user_id)
    )->loadObject();
}

function user_harvest($user_id, $type)
{
    $db = db();

    return $db->setQuery(
        'SELECT id ' .
        'FROM network_harvest_' . $type .
        ' WHERE user_id = ' . $db->quote($user_id)
    )->loadObjectList();
}

function has_user_share($account_type, $user_id): bool
{
    return count(user_share($account_type, $user_id)) === 1;
}

function user_share($type, $user_id)
{
    $db = db();

    return $db->setQuery(
        'SELECT * ' .
        'FROM network_share_' . $type .
        ' WHERE user_id = ' . $db->quote($user_id) .
        ' AND is_active = ' . $db->quote(1) .
        ' AND has_mature = ' . $db->quote(0)
    )->loadObjectList();
}