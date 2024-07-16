<?php

/***************************************************************************
 *
 *    OUGC Portal Poll plugin (/inc/plugins/ougc/PortalPoll/hooks/forum.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
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

namespace ougc\PortalPoll\Hooks\Forum;

use function ougc\PortalPoll\Core\buildPoll;

function global_start()
{
    global $templatelist;

    if (!isset($templatelist)) {
        $templatelist = '';
    } else {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT === 'portal.php') {
        $templatelist .= 'ougcportalpoll, ougcportalpoll_resultbit, ougcportalpoll_option, ougc_portalpollresults';
    }
}

function portal_end()
{
    buildPoll();
}