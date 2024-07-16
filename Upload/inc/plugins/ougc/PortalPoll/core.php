<?php

/***************************************************************************
 *
 *    OUGC Portal Poll plugin (/inc/plugins/ougc/PortalPoll/core.php)
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

namespace ougc\PortalPoll\Core;

use postParser;

function loadLanguage()
{
    global $lang;

    isset($lang->setting_group_ougc_portalpoll) || $lang->load('ougc_portalpoll');
}

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, null, 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

/**
 * Builds a formatted thread poll based off settings.
 *
 * @param array Poll Options ( (bool) random, (string) forums, (int) pid, (bool) return string)
 * @return string Formatted poll
 **/
function buildPoll(array $pollOptions = [])
{
    global $mybb;

    $pollOptions = array_merge([
        'random' => (bool)$mybb->settings['ougc_portalpoll_random'],
        'forums' => (string)$mybb->settings['ougc_portalpoll_forums'],
        'pid' => (int)$mybb->settings['ougc_portalpoll_pid'],
        'tmplprefix' => 'ougcportalpoll',
        'forum_show_list' => '',
    ], $pollOptions);

    if (!empty($pollOptions['template_var'])) {
        //$template_var = (string)$pollOptions['template_var'];
    }

    if (!$pollOptions['random'] && !$pollOptions['pid'] && !$pollOptions['forums']) {
        return false;
    }

    global $db;

    $whereClauses = [];

    if ($unviewableforums = get_unviewable_forums(true)) {
        $whereClauses[] = "t.fid NOT IN({$unviewableforums})";
    }

    if ($inactiveforums = get_inactive_forums()) {
        $whereClauses[] = "t.fid NOT IN({$inactiveforums})";
    }

    if ($pollOptions['forums'] && (int)$pollOptions['forums'] !== -1) {
        $pollForumIDs = implode("','", array_map('intval', explode(',', $pollOptions['forums'])));

        $whereClauses[] = "t.fid IN ('{$pollForumIDs}')";
    }

    if ($pollOptions['random']) {
        $foundPollIDs = [];

        // there should be a better way for this, but I'm lazy since it will probably cost time building a query for different engines.
        $query = $db->simple_select(
            "polls p LEFT JOIN {$db->table_prefix}threads t ON (t.poll=p.pid)",
            'p.pid',
            implode(' AND ', $whereClauses)
        );

        while ($foundPollIDs[] = (int)$db->fetch_field($query, 'pid')) {
        }

        $foundPollIDs = array_filter($foundPollIDs);

        if ($foundPollIDs) {
            $pollOptions['pid'] = $foundPollIDs[mt_rand(0, count($foundPollIDs) - 1)];
        }
    }

    if (!empty($pollOptions['pid'])) {
        $whereClauses[] = "p.pid='{$pollOptions['pid']}'";
    }

    $query_options = ['limit' => 1, 'order_by' => 'dateline', 'order_dir' => 'desc'];

    $queryTables = [
        'polls p',
        "{$db->table_prefix}threads t ON (t.poll=p.pid)",
        "{$db->table_prefix}posts po ON (po.pid=t.firstpost)",
    ];

    $queryFields = ['p.*', 't.closed AS threadclosed', 't.fid', 'po.message'];

    $xThreadFields = xThreadsGetFields($pollOptions['forum_show_list']);

    if ($xThreadFields) {
        $queryFields = array_merge($queryFields, $xThreadFields);

        $queryTables[] = "{$db->table_prefix}threadfields_data tfd ON (tfd.tid=p.tid)";
    }

    $query = $db->simple_select(
        implode(' LEFT JOIN ', $queryTables),
        implode(',', $queryFields),
        implode(' AND ', $whereClauses),
        $query_options
    );

    $poll = $db->fetch_array($query);

    if (!$poll['pid']) {
        return false;
    }

    static $pollsCache = [];

    if (isset($pollsCache[$poll['pid']])) {
        if ($pollOptions['return']) {
            return $pollsCache[$poll['pid']];
        }

        global $ougc_portalpoll;

        $ougc_portalpoll = $pollsCache[$poll['pid']];

        return true;
    }

    global $lang, $forum_cache, $parser, $templates, $theme;

    loadLanguage();

    if (!($parser instanceof postParser)) {
        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser();
    }

    $forum_cache || cache_forums();

    $forum = $forum_cache[(int)$poll['fid']];

    $parser_options = [
        'allow_html' => (bool)$forum['allowhtml'],
        'allow_mycode' => (bool)$forum['allowmycode'],
        'allow_smilies' => (bool)$forum['allowsmilies'],
        'allow_imgcode' => (bool)$forum['allowimgcode'],
        'allow_videocode' => (bool)$forum['allowvideocode'],
        'filter_badwords' => 1
    ];

    preg_match_all(
        '#<img(.+?)src=\"(.+?)\"(.+?)/>#i',
        (string)$parser->parse_message($poll['message'], $parser_options),
        $matches
    );

    $poll['image'] = $poll['image_url'] = '';

    if (is_array($matches)) {
        $poll['image'] = $matches[0][0];

        $poll['image_url'] = $matches[2][0];
    }

    $poll['timeout'] = $poll['timeout'] * 60 * 60 * 24;

    $expiretime = $poll['dateline'] + $poll['timeout'];

    $expire_message = $poll['timeout'] ? $lang->sprintf(
        $lang->ougc_portalpoll_expire_message,
        my_date($mybb->settings['dateformat'], $expiretime),
        my_date($mybb->settings['timeformat'], $expiretime)
    ) : '';

    $showresults = ($poll['closed'] || $poll['threadclosed'] || ($expiretime < TIME_NOW && $poll['timeout'] > 0)) ? true : false;

    $mybb->user['uid'] = (int)$mybb->user['uid'];
    $poll['pid'] = (int)$poll['pid'];

    $votedfor = [];
    $alreadyvoted = false;

    if ($mybb->user['uid']) {
        $query = $db->simple_select(
            'pollvotes',
            'voteoption',
            'uid=\'' . $mybb->user['uid'] . '\' AND pid=\'' . $poll['pid'] . '\''
        );
        while ($voteoption = $db->fetch_field($query, 'voteoption')) {
            $alreadyvoted = $votedfor[$voteoption] = true;
        }
    } elseif (isset($mybb->cookies['pollvotes'][$poll['pid']]) && $mybb->cookies['pollvotes'][$poll['pid']] !== '') {
        $alreadyvoted = true;
    }

    $optionsarray = explode('||~|~||', $poll['options']);
    $votesarray = explode('||~|~||', $poll['votes']);
    $poll['question'] = htmlspecialchars_uni($poll['question']);
    $polloptions = $edit_poll = '';
    $totalvotes = 0;
    $threadlink = get_thread_link($poll['tid']);

    for ($i = 1; $i <= $poll['numoptions']; ++$i) {
        $poll['totvotes'] = $poll['totvotes'] + $votesarray[$i - 1];
    }

    $totpercent = !empty($poll['totvotes']) ? '100%' : '0%';

    ///~~~***---***~~~///
    if ($xThreadFields) {
        xthreads_set_threadforum_urlvars('thread', $poll['tid']);

        xthreads_set_threadforum_urlvars('forum', $poll['fid']);

        // make threadfields array
        $threadfields = []; // clear previous threadfields

        foreach (xthreads_gettfcache() as $k => &$v) {
            if (!empty($v['forums']) && strpos(",{$v['forums']},", ",{$poll['fid']},") === false) {
                continue;
            }

            xthreads_get_xta_cache($v, $poll['tid']);

            $threadfields[$k] =& $poll['xthreads_' . $k];

            xthreads_sanitize_disp(
                $threadfields[$k],
                $v,
                ($poll['username'] !== '' ? $poll['username'] : $poll['threadusername'])
            ); // username isn't got
        }
    }
    ///~~~***---***~~~///

    for ($i = 1; $i <= $poll['numoptions']; ++$i) {
        $option = $parser->parse_message($optionsarray[$i - 1], $parser_options);
        $votes = (int)$votesarray[$i - 1];
        $totalvotes += $votes;
        $number = $i;

        #$trow = alt_trow();
        $trow = isset($votedfor[$number]) ? 'trow2' : 'trow1';
        $votestar = isset($votedfor[$number]) ? '*' : '';

        if ($alreadyvoted || $showresults) {
            $percent = !$votes ? 0 : number_format($votes / $poll['totvotes'] * 100, 2);
            $imagewidth = round(($percent / 3) * 5);
            $imagerowwidth = $imagewidth + 10;
            $polloptions .= eval($templates->render($pollOptions['tmplprefix'] . '_resultbit'));
        } else {
            $type = $poll['multiple'] ? 'checkbox' : 'radio';
            $name = $poll['multiple'] ? '[' . (int)$number . ']' : '';
            $value = $poll['multiple'] ? 1 : (int)$number;

            $polloptions .= eval($templates->render($pollOptions['tmplprefix'] . '_option'));
        }
    }

    $edit_poll = '';

    if (is_moderator($poll['fid'], 'caneditposts')) {
        $edit_poll = " | <a href=\"{$mybb->settings['bburl']}/polls.php?action=editpoll&amp;pid={$poll['pid']}\">{$lang->ougc_portalpoll_edit}</a>";
    }

    $templateName = '';

    if ($alreadyvoted || $showresults) {
        $pollstatus = $alreadyvoted ? $lang->ougc_portalpoll_already_voted : $lang->ougc_portalpoll_poll_closed;
        if ($alreadyvoted && $mybb->usergroup['canundovotes']) {
            $pollstatus .= ' [<a href="' . $mybb->settings['bburl'] . '/polls.php?action=do_undovote&amp;pid=' . $poll['pid'] . '&amp;my_post_key=' . $mybb->post_code . '">' . $lang->ougc_portalpoll_undo_vote . '</a>]';
        }

        $lang->ougc_portalpoll_total_votes = $lang->sprintf($lang->ougc_portalpoll_total_votes, $totalvotes);

        $templateName = '_results';
    } else {
        $publicnote = $poll['public'] ? $lang->public_note : '&nbsp;';
    }

    $pollsCache[$poll['pid']] = eval($templates->render($pollOptions['tmplprefix'] . $templateName));

    global $plugins;

    $plugins->run_hooks('showthread_poll_results');

    if ($pollOptions['return']) {
        return $pollsCache[$poll['pid']];
    }

    global $ougc_portalpoll;

    $ougc_portalpoll = (string)$pollsCache[$poll['pid']];

    return true;
}

function xThreadsGetFields(string $forumIDs = ''): array
{
    $xThreadsFields = [];

    if (!function_exists('xthreads_gettfcache')) {
        return $xThreadsFields;
    }

    $threadFieldCache = xthreads_gettfcache();

    if (empty($threadFieldCache)) {
        return $xThreadsFields;
    }

    $forumIDs = array_flip(array_map('intval', explode(',', $forumIDs)));

    foreach ($threadFieldCache as $k => &$v) {
        $fieldIsAvailable = empty($v['forums']) || empty($forumIDs);

        if (!$fieldIsAvailable) {
            foreach (explode(',', (string)$v['forums']) as $forumID) {
                if (isset($forumIDs[$forumID])) {
                    $fieldIsAvailable = true;

                    break;
                }
            }
        }

        if ($fieldIsAvailable) {
            $xThreadsFields[] = "tfd.`{$v['field']}` AS `xthreads_{$v['field']}`";
        }
    }

    return $xThreadsFields;
}