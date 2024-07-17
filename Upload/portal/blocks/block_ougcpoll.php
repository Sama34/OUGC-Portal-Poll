<?php

/***************************************************************************
 *
 *    OUGC Portal Poll plugin (/portal/blocks/block_ougcpoll.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2024 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Add a side-box poll in your portal.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

use function ougc\PortalPoll\Core\buildPoll;

use const ougc\PortalPoll\Core\ROOT;

defined('IN_PORTAL') || die('Direct initialization of this file is disallowed.');

if (!defined('ougc\PortalPoll\Core\ROOT')) {
    define('ougc\PortalPoll\Core\ROOT', MYBB_ROOT . 'inc/plugins/ougc/PortalPoll');
}

require_once ROOT . '/core.php';

echo (function () {
    global $mybb, $templates, $lang, $theme;
    global $proportal, $result_blocks;

    $pollBox = buildPoll(['return' => true]);

    $ougcPollBox = eval($templates->render('ougcportalpoll_proPortal'));

    if (empty($result_blocks['title'])) {
        $result_blocks['title'] = $lang->poll_block;
    }

    if (!empty($proportal->settings['responsive']) && empty($mybb->user['uid']) || !empty($mybb->user['portalresonsive'])) {
        return eval($templates->render('ougcportalpoll_proPortalWrapperResponsive'));
    } elseif (empty($mybb->user['portalresonsive']) || empty($proportal->settings['responsive']) && empty($mybb->user['uid'])) {
        return eval($templates->render('ougcportalpoll_proPortalWrapper'));
    }

    return '';
})();
