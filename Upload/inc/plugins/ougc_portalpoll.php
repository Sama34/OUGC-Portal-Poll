<?php

/***************************************************************************
 *
 *    OUGC Portal Poll plugin (/inc/plugins/ougc_portalpoll.php)
 *    Author: Omar Gonzalez
 *    Copyright: � 2012-2014 Omar Gonzalez
 *
 *    Website: http://omarg.me
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

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run the ACP hooks.
if (defined('IN_ADMINCP')) {
    $plugins->add_hook('admin_config_settings_start', 'ougc_portalpoll_lang_load');
    $plugins->add_hook('admin_style_templates_set', 'ougc_portalpoll_lang_load');
} elseif (THIS_SCRIPT == 'portal.php') {
    global $plugins, $templatelist;

    $plugins->add_hook('portal_end', 'ougc_build_poll');

    if (!isset($templatelist)) {
        $templatelist = '';
    } else {
        $templatelist .= ',';
    }

    $templatelist .= 'ougcportalpoll, ougcportalpoll_resultbit, ougcportalpoll_option, ougc_portalpollresults';
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_portalpoll_info()
{
    global $lang;
    ougc_portalpoll_lang_load();

    return array(
        'name' => 'OUGC Portal Poll',
        'description' => $lang->setting_group_ougc_portalpoll_desc,
        'website' => 'http://omarg.me',
        'author' => 'Omar G.',
        'authorsite' => 'http://omarg.me',
        'version' => '1.0',
        'versioncode' => 1000,
        'compatibility' => '16*',
        'guid' => '',
        'pl' => array(
            'version' => 12,
            'url' => 'http://mods.mybb.com/view/pluginlibrary'
        )
    );
}

// _activate() routine
function ougc_portalpoll_activate()
{
    global $PL, $lang, $cache;
    ougc_portalpoll_lang_load();
    ougc_portalpoll_deactivate();

    // Add settings group
    $PL->settings(
        'ougc_portalpoll',
        $lang->setting_group_ougc_portalpoll,
        $lang->setting_group_ougc_portalpoll_desc,
        array(
            'random' => array(
                'title' => $lang->setting_ougc_portalpoll_random,
                'description' => $lang->setting_ougc_portalpoll_random_desc,
                'optionscode' => 'yesno',
                'value' => 0,
            ),
            'forums' => array(
                'title' => $lang->setting_ougc_portalpoll_forums,
                'description' => $lang->setting_ougc_portalpoll_forums_desc,
                'optionscode' => 'text',
                'value' => '',
            ),
            'pid' => array(
                'title' => $lang->setting_ougc_portalpoll_pid,
                'description' => $lang->setting_ougc_portalpoll_pid_desc,
                'optionscode' => 'text',
                'value' => '',
            )
        )
    );

    // Add template group
    $PL->templates('ougcportalpoll', '<lang:setting_group_ougc_portalpoll>', array(
        '' => '<form action="{$mybb->settings[\'bburl\']}/polls.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<input type="hidden" name="action" value="vote" />
<input type="hidden" name="pid" value="{$poll[\'pid\']}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td colspan="4" class="thead" align="center"><strong>{$lang->ougc_portalpoll_poll} {$poll[\'question\']}</strong>{$expire_message}</td>
</tr>
{$polloptions}
</table>
<table width="100%" align="center">
<tr>
<td><input type="submit" class="button" value="{$lang->ougc_portalpoll_vote}" /></td>
<td valign="top" align="right"><span class="smalltext">[<a href="{$mybb->settings[\'bburl\']}/polls.php?action=showresults&amp;pid={$poll[\'pid\']}">{$lang->ougc_portalpoll_show_results}</a>{$edit_poll}]</span></td>
</tr>
<tr>
<td colspan="2"><span class="smalltext">{$publicnote}</span></td>
</tr>
</table>
</form>',
        'option' => '<tr>
<td class="trow1" width="1%"><input type="{$type}" class="{$type}" name="option{$name}" id="option_{$number}" value="{$value}" /></td>
<td class="trow1" colspan="3">{$option}</td>
</tr>',
        'results' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="4" align="center"><strong>{$lang->ougc_portalpoll_poll} {$poll[\'question\']}</strong><br /><span class="smalltext">{$pollstatus}</span>{$expire_message}</td>
</tr>
{$polloptions}
<tr>
<td class="tfoot" align="right" colspan="2"><strong>{$lang->ougc_portalpoll_total}</strong></td>
<td class="tfoot" align="center"><strong>{$lang->ougc_portalpoll_total_votes}</strong></td>
<td class="tfoot" align="center"><strong>{$totpercent}</strong></td>
</tr>
</table>
<table cellspacing="0" cellpadding="2" border="0" width="100%" align="center">
<tr>
<td align="left"><span class="smalltext">{$lang->ougc_portalpoll_you_voted}</span></td>
<td align="right"><span class="smalltext">[<a href="{$mybb->settings[\'bburl\']}/polls.php?action=showresults&amp;pid={$poll[\'pid\']}">{$lang->ougc_portalpoll_show_results}</a>{$edit_poll}]</span></td>
</tr>
</table>
<br />',
        'resultbit' => '<tr>
<td class="{$trow}" align="right">{$option}{$votestar}</td>
<td class="{$trow}"><img src="{$theme[\'imgdir\']}/pollbar-s.gif" alt="" /><img src="{$theme[\'imgdir\']}/pollbar.gif" width="{$imagewidth}" height="10" alt="{$percent}%" title="{$percent}%" /><img src="{$theme[\'imgdir\']}/pollbar-e.gif" alt="" /></td>
<td class="{$trow}" width="67" align="center">{$votes}</td>
<td class="{$trow}" width="67" align="center">{$percent}%</td>
</tr>'
    ));

    // Modify templates
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
    find_replace_templatesets(
        'portal',
        '#' . preg_quote('{$announcements}') . '#',
        '{\$ougc_portalpoll}{\$announcements}'
    );

    // Insert/update version into cache
    $plugins = $cache->read('ougc_plugins');
    if (!$plugins) {
        $plugins = array();
    }

    $info = ougc_portalpoll_info();

    if (!isset($plugins['portalpoll'])) {
        $plugins['portalpoll'] = $info['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/
    if ($plugins['portalpoll'] <= 1100) {
        global $db;

        $db->delete_query('settings', 'gid=\'12\' AND name=\'ougc_portalpoll\'');

        $db->delete_query(
            'templates',
            'title IN(\'' . implode(
                '\', \'ougc_portalpoll',
                array('', '_resultbit', '_option_multiple', '_option', '_results')
            ) . '\') AND sid=\'-1\''
        );

        rebuild_settings();
    }
    /*~*~* RUN UPDATES END *~*~*/

    $plugins['portalpoll'] = $info['versioncode'];
    $cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_portalpoll_deactivate()
{
    ougc_portalpoll_pl_check();

    // Revert template edits
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
    find_replace_templatesets('portal', '#' . preg_quote('{$ougc_portalpoll}') . '#', '', 0);
}

// _is_installed() routine
function ougc_portalpoll_is_installed()
{
    global $cache;

    $plugins = (array)$cache->read('ougc_plugins');

    return !empty($plugins['portalpoll']);
}

// _uninstall() routine
function ougc_portalpoll_uninstall()
{
    global $PL, $cache;
    ougc_portalpoll_pl_check();

    $PL->settings_delete('ougc_portalpoll');
    $PL->templates_delete('ougcportalpoll');

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['portalpoll'])) {
        unset($plugins['portalpoll']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $PL->cache_delete('ougc_plugins');
    }
}

// Loads language strings
function ougc_portalpoll_lang_load()
{
    global $lang;

    isset($lang->setting_group_ougc_portalpoll) or $lang->load('ougc_portalpoll');
}

// PluginLibrary dependency check & load
function ougc_portalpoll_pl_check()
{
    global $lang;
    ougc_portalpoll_lang_load();
    $info = ougc_portalpoll_info();

    if (!file_exists(PLUGINLIBRARY)) {
        flash_message(
            $lang->sprintf($lang->ougc_portalpoll_pl_required, $info['pl']['url'], $info['pl']['version']),
            'error'
        );
        admin_redirect('index.php?module=config-plugins');
        exit;
    }

    global $PL;

    $PL or require_once PLUGINLIBRARY;

    if ($PL->version < $info['pl']['version']) {
        flash_message(
            $lang->sprintf($lang->ougc_portalpoll_pl_old, $info['pl']['url'], $info['pl']['version'], $PL->version),
            'error'
        );
        admin_redirect('index.php?module=config-plugins');
        exit;
    }
}

/**
 * Builds a formatted thread poll based off settings.
 *
 * @param array Poll Options ( (bool) random, (string) forums, (int) pid, (bool) return string)
 * @return string Formatted poll
 **/
function ougc_build_poll($options = array())
{
    global $mybb;

    $options or $options = array(
        'random' => (bool)$mybb->settings['ougc_portalpoll_random'],
        'forums' => (string)$mybb->settings['ougc_portalpoll_forums'],
        'pid' => (int)$mybb->settings['ougc_portalpoll_pid'],
        'return' => false,
    );

    if (!$options['random'] && !$options['pid']) {
        return false;
    }

    static $pollcache = array();

    if (!isset($pollcache[$poll['pid']])) {
        global $db;

        // Build a where clause
        $where = array();

        if ($options['random']) {
            $where[] = 'RAND()'; //DEBUG
        } else {
            $where[] = 'p.pid=\'' . $options['pid'] . '\'';
        }

        if ($unviewableforums = get_unviewable_forums(true)) {
            $where[] = 't.fid NOT IN(' . $unviewableforums . ')';
        }

        if ($inactiveforums = get_inactive_forums()) {
            $where[] = 't.fid NOT IN(' . $inactiveforums . ')';
        }

        $poll = $db->fetch_array(
            $db->simple_select(
                'polls p LEFT JOIN ' . TABLE_PREFIX . 'threads t ON (t.poll=p.pid)',
                'p.*, t.closed AS threadclosed, t.fid',
                implode(' AND ', $where),
                array('limit' => 1)
            )
        );

        if (!$poll['pid']) {
            return false;
        }

        global $lang, $forum_cache, $parser, $templates, $theme;
        ougc_portalpoll_lang_load();

        $forum_cache or cache_forums();
        $forum = $forum_cache[(int)$poll['fid']];

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

        $votedfor = array();
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

        for ($i = 1; $i <= $poll['numoptions']; ++$i) {
            $option = $parser->parse_message($optionsarray[$i - 1], array(
                'allow_html' => (bool)$forum['allowhtml'],
                'allow_mycode' => (bool)$forum['allowmycode'],
                'allow_smilies' => (bool)$forum['allowsmilies'],
                'allow_imgcode' => (bool)$forum['allowimgcode'],
                'allow_videocode' => (bool)$forum['allowvideocode'],
                'filter_badwords' => 1
            ));
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
                eval('$polloptions .= "' . $templates->get('ougcportalpoll_resultbit') . '";');
            } else {
                $type = $poll['multiple'] ? 'checkbox' : 'radio';
                $name = $poll['multiple'] ? '[' . (int)$number . ']' : '';
                $value = $poll['multiple'] ? 1 : (int)$number;

                eval('$polloptions .= "' . $templates->get('ougcportalpoll_option') . '";');
            }
        }

        $edit_poll = is_moderator(
            $poll['fid'],
            'caneditposts'
        ) ? ' | <a href="' . $mybb->settings['bburl'] . '/polls.php?action=editpoll&amp;pid={' . $poll['pid'] . '}">' . $lang->ougc_portalpoll_edit . '</a>' : '';

        $tmpl = '';
        if ($alreadyvoted || $showresults) {
            $pollstatus = $alreadyvoted ? $lang->ougc_portalpoll_already_voted : $lang->ougc_portalpoll_poll_closed;
            if ($alreadyvoted && $mybb->usergroup['canundovotes']) {
                $pollstatus .= ' [<a href="' . $mybb->settings['bburl'] . '/polls.php?action=do_undovote&amp;pid=' . $poll['pid'] . '&amp;my_post_key=' . $mybb->post_code . '">' . $lang->ougc_portalpoll_undo_vote . '</a>]';
            }

            $lang->ougc_portalpoll_total_votes = $lang->sprintf($lang->ougc_portalpoll_total_votes, $totalvotes);
            $tmpl = '_results';
        } else {
            $publicnote = $poll['public'] ? $lang->public_note : '&nbsp;';
        }

        eval('$pollcache[' . $poll['pid'] . '] = "' . $templates->get('ougcportalpoll' . $tmpl) . '";');

        global $plugins;

        $plugins->run_hooks('showthread_poll_results');
    }

    if ($options['return']) {
        return $pollcache[$poll['pid']];
    }

    global $ougc_portalpoll;

    $ougc_portalpoll = (string)$pollcache[$poll['pid']];
}