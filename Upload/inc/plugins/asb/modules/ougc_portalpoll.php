<?php

/***************************************************************************
 *
 *    OUGC Portal Poll - ASB plugin
 *    Author: Omar Gonzalez
 *    Copyright: © 2014 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Shows an poll in portal.
 *
 ***************************************************************************/

/****************************************************************************
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

// Die if IN_ASB is not defined, for security reasons.
use function ougc\PortalPoll\Core\buildPoll;

defined('IN_ASB') or die('Direct initialization of this file is not allowed.');

// Plugin API
function asb_ougc_portalpoll_info()
{
    global $lang, $db;
    ougc_portalpoll_lang_load();

    return [
        'title' => 'OUGC Portal Poll',
        'description' => $lang->setting_group_ougc_portalpoll_desc,
        'author' => 'Omar G.',
        'author_site' => 'http://omarg.me',
        'module_site' => 'http://omarg.me',
        'wrap_content' => true,
        'version' => '1.8.22',
        'compatibility' => '4.0',
        'xmlhttp' => false,
        'debugMode' => true,
        //'noContentTemplate'	=> 'asb_ougc_portalpoll_empty',
        'settings' => [
            'ougc_portalpoll_random' => [
                'name' => 'ougc_portalpoll_random',
                'title' => $lang->setting_ougc_portalpoll_random,
                'description' => $lang->setting_ougc_portalpoll_random_desc,
                'optionscode' => 'yesno',
                'value' => 0
            ],
            'ougc_portalpoll_forums' => [
                'name' => 'ougc_portalpoll_forums',
                'title' => $lang->setting_ougc_portalpoll_forums,
                'description' => $lang->setting_ougc_portalpoll_forums_desc,
                'optionscode' => 'text',
                'value' => ''
            ],
            'ougc_portalpoll_pid' => [
                'name' => 'ougc_portalpoll_pid',
                'title' => $lang->setting_ougc_portalpoll_pid,
                'description' => $lang->setting_ougc_portalpoll_pid_desc,
                'optionscode' => 'text',
                'value' => ''
            ]
        ],
        'removedTemplates' => [
            'asb_ougc_portalpoll_thread',
            'asb_ougc_portalpoll_gotounread',
            'asb_ougc_portalpoll_last_poster_name',
            'asb_ougc_portalpoll_last_poster_avatar',
            'asb_ougc_portalpoll_last_poster_avatar_avatar',
            'asb_ougc_portalpoll_last_post_link',
            'asb_ougc_portalpoll_no_content'
        ],
        'installData' => [
            'templates' => [
                [
                    'title' => 'asb_ougc_portalpoll',
                    'template' => <<<EOF
<form action="{\$mybb->settings[\'bburl\']}/polls.php" method="post">
	<input type="hidden" name="my_post_key" value="{\$mybb->post_code}" />
	<input type="hidden" name="action" value="vote" />
	<input type="hidden" name="pid" value="{\$poll[\'pid\']}" />
	<table border="0" cellspacing="{\$theme[\'borderwidth\']}" cellpadding="{\$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td colspan="4" class="thead" align="center"><strong>{\$lang->ougc_portalpoll_poll} {\$poll[\'question\']}</strong>{\$expire_message}</td>
		</tr>
			{\$polloptions}
		</table>
		<table width="100%" align="center">
		<tr>
			<td><input type="submit" class="button" value="{\$lang->ougc_portalpoll_vote}" /></td>
			<td valign="top" align="right"><span class="smalltext">[<a href="{\$mybb->settings[\'bburl\']}/polls.php?action=showresults&amp;pid={\$poll[\'pid\']}">{\$lang->ougc_portalpoll_show_results}</a>{\$edit_poll}]</span></td>
		</tr>
		<tr>
			<td colspan="2"><span class="smalltext">{\$publicnote}</span></td>
		</tr>
	</table>
</form>
EOF
                ],
                [
                    'title' => 'asb_ougc_portalpoll_option',
                    'template' => $db->escape_string(
                        '<tr>
	<td class="trow1" width="1%"><input type="{$type}" class="{$type}" name="option{$name}" id="option_{$number}" value="{$value}" /></td>
	<td class="trow1" colspan="3">{$option}</td>
</tr>'
                    )
                ],
                [
                    'title' => 'asb_ougc_portalpoll_results',
                    'template' => $db->escape_string(
                        '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
<br />'
                    )
                ],
                [
                    'title' => 'asb_ougc_portalpoll_resultbit',
                    'template' => $db->escape_string(
                        '<tr>
	<td class="{$trow}" align="right">{$option}{$votestar}</td>
	<td class="{$trow}"><img src="{$theme[\'imgdir\']}/pollbar-s.gif" alt="" /><img src="{$theme[\'imgdir\']}/pollbar.gif" width="{$imagewidth}" height="10" alt="{$percent}%" title="{$percent}%" /><img src="{$theme[\'imgdir\']}/pollbar-e.gif" alt="" /></td>
	<td class="{$trow}" width="67" align="center">{$votes}</td>
	<td class="{$trow}" width="67" align="center">{$percent}%</td>
</tr>'
                    )
                ]
            ],
        ],
    ];
}

if (!function_exists('ougc_build_poll')) {
    // Loads language strings
    function ougc_portalpoll_lang_load()
    {
        global $lang;

        isset($lang->setting_group_ougc_portalpoll) or $lang->load('ougc_portalpoll');
    }
}

// _build_template() routine
function asb_ougc_portalpoll_get_content($settings, $script, $dateline, $template_var)
//function asb_ougc_portalpoll_build_template($args, $xmlhttp=false)
{
    $ougc_poll = buildPoll([
        'random' => (bool)$settings['ougc_portalpoll_random'],
        'forums' => (string)$settings['ougc_portalpoll_forums'],
        'pid' => (int)$settings['ougc_portalpoll_pid'],
        'tmplprefix' => 'asb_ougc_portalpoll',
        'return' => true,
        'template_var' => $template_var,
        'forum_show_list' => $settings['forum_show_list'],
    ]);

    return $ougc_poll;
}