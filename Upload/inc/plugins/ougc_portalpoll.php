<?php

/***************************************************************************
 *
 *    OUGC Portal Poll plugin (/inc/plugins/ougc_portalpoll.php)
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

use function ougc\PortalPoll\Core\addHooks;
use function ougc\PortalPoll\Core\buildPoll;
use function ougc\PortalPoll\Core\loadLanguage;

use const ougc\PortalPoll\Core\ROOT;

defined('IN_MYBB') || die('Direct initialization of this file is disallowed.');

if (!defined('ougc\PortalPoll\Core\ROOT')) {
    define('ougc\PortalPoll\Core\ROOT', MYBB_ROOT . 'inc/plugins/ougc/PortalPoll');
}

require_once ROOT . '/core.php';

defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

if (defined('IN_ADMINCP')) {
} else {
    require_once ROOT . '/hooks/forum.php';

    addHooks('ougc\PortalPoll\Hooks\Forum');
}

global $plugins;

// Run the ACP hooks.
if (defined('IN_ADMINCP')) {
    //$plugins->add_hook('admin_config_settings_start', 'ougc_portalpoll_lang_load');
    //$plugins->add_hook('admin_style_templates_set', 'ougc_portalpoll_lang_load');
}

// Plugin API
function ougc_portalpoll_info()
{
    global $lang;

    loadLanguage();

    return [
        'name' => 'OUGC Portal Poll',
        'description' => $lang->setting_group_ougc_portalpoll_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.0',
        'versioncode' => 1800,
        'compatibility' => '18*',
        'codename' => 'ougc_fileprofilefields',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ]
    ];
}

// _activate() routine
function ougc_portalpoll_activate()
{
    global $PL, $lang, $cache;
    loadLanguage();
    ougc_portalpoll_deactivate();

    // Add settings group
    $PL->settings(
        'ougc_portalpoll',
        $lang->setting_group_ougc_portalpoll,
        $lang->setting_group_ougc_portalpoll_desc,
        [
            'random' => [
                'title' => $lang->setting_ougc_portalpoll_random,
                'description' => $lang->setting_ougc_portalpoll_random_desc,
                'optionscode' => 'yesno',
                'value' => 0,
            ],
            'forums' => [
                'title' => $lang->setting_ougc_portalpoll_forums,
                'description' => $lang->setting_ougc_portalpoll_forums_desc,
                'optionscode' => 'forumselect',
                'value' => '',
            ],
            'pid' => [
                'title' => $lang->setting_ougc_portalpoll_pid,
                'description' => $lang->setting_ougc_portalpoll_pid_desc,
                'optionscode' => 'numeric',
                'value' => '',
            ]
        ]
    );

    // Add template group
    $PL->templates('ougcportalpoll', 'OUGC Portal Poll', [
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
</tr>',
        'proPortal' => '{$pollBox}',
        'proPortalWrapperResponsive' => '<div class="col-lg-12 col-md-12 col-xs-12 col-sm-12" style="padding: 0; padding-bottom:{$proportal->settings[\'horizontalspace\']};">
	{$ougcPollBox}
</div>',
        'proPortalWrapper' => '<div style="padding: 0; padding-bottom:{$proportal->settings[\'horizontalspace\']};">
	{$ougcPollBox}
</div>'
    ]);

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
        $plugins = [];
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
                ['', '_resultbit', '_option_multiple', '_option', '_results']
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

// PluginLibrary dependency check & load
function ougc_portalpoll_pl_check()
{
    global $lang;
    loadLanguage();
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
function ougc_build_poll(array $options = [])
{
    buildPoll($options);
}