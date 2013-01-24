<?php

/***************************************************************************
 *
 *   OUGC Portal Poll plugin plugin
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://www.udezain.com.ar
 *
 *   Shows a poll in portal.
 *
 ***************************************************************************/
 
/****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run the ACP hooks.
if(!defined('IN_ADMINCP') && defined('THIS_SCRIPT') && THIS_SCRIPT == 'portal.php')
{
	global $plugins, $templatelist;

	$plugins->add_hook('portal_end', 'ougc_portalpoll');

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}

	$templatelist .= 'ougc_portalpoll, ougc_portalpoll_resultbit, ougc_portalpoll_option_multiple, ougc_portalpoll_option, ougc_portalpoll_results';
}

// Necessary plugin information for the ACP plugin manager.
function ougc_portalpoll_info()
{
	global $lang;
    $lang->load('ougc_portalpoll');

	return array(
		'name'			=> 'OUGC Portal Poll',
		'description'	=> $lang->ougc_portalpoll_d,
		'website'		=> 'http://udezain.com.ar/',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://udezain.com.ar/',
		'version'		=> '1.0',
		'compatibility'	=> '16*'
	);
}

// Activate the plugin.
function ougc_portalpoll_activate()
{
	global $mybb, $db, $lang;
    $lang->load('ougc_portalpoll');
	ougc_portalpoll_deactivate(false);

	$query = $db->simple_select('settings', 'MAX(disporder) AS disporder', "gid='12'");
	$disporder = (int)$db->fetch_field($query, 'disporder');

	$db->insert_query('settings', array(
		'name'			=> 'ougc_portalpoll',
		'title'			=> $db->escape_string($lang->ougc_portalpoll_set),
		'description'	=> $db->escape_string($lang->ougc_portalpoll_set_d),
		'optionscode'	=> 'text',
		'value'			=> '',
		'disporder'		=> ++$disporder,
		'gid'			=> 12
	));

	$db->free_result($query);

	rebuild_settings();

	$db->insert_query('templates', array(
		'title'		=>	'ougc_portalpoll',
		'template'	=>	$db->escape_string('<form action="{$mybb->settings[\'bburl\']}/polls.php" method="post">
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
</form>'),
		'sid'		=>	-1,
	));
	$db->insert_query('templates', array(
		'title'		=>	'ougc_portalpoll_resultbit',
		'template'	=>	$db->escape_string('<tr>
<td class="{$optionbg}" align="right">{$option}{$votestar}</td>
<td class="{$optionbg}"><img src="{$theme[\'imgdir\']}/pollbar-s.gif" alt="" /><img src="{$theme[\'imgdir\']}/pollbar.gif" width="{$imagewidth}" height="10" alt="{$percent}%" title="{$percent}%" /><img src="{$theme[\'imgdir\']}/pollbar-e.gif" alt="" /></td>
<td class="{$optionbg}" width="67" align="center">{$votes}</td>
<td class="{$optionbg}" width="67" align="center">{$percent}%</td>
</tr>'),
		'sid'		=>	-1,
	));
	$db->insert_query('templates', array(
		'title'		=>	'ougc_portalpoll_option_multiple',
		'template'	=>	$db->escape_string('<tr>
<td class="trow1" width="1%"><input type="checkbox" class="checkbox" name="option[{$number}]" id="option_{$number}" value="1" /></td>
<td class="trow2" colspan="3">{$option}</td>
</tr>'),
		'sid'		=>	-1,
	));
	$db->insert_query('templates', array(
		'title'		=>	'ougc_portalpoll_option',
		'template'	=>	$db->escape_string('<tr>
<td class="trow1" width="1%"><input type="radio" class="radio" name="option" id="option_{$number}" value="{$number}" /></td>
<td class="trow1" colspan="3">{$option}</td>
</tr>'),
		'sid'		=>	-1,
	));
	$db->insert_query('templates', array(
		'title'		=>	'ougc_portalpoll_results',
		'template'	=>	$db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
<br />'),
		'sid'		=>	-1,
	));

	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('portal', '#'.preg_quote('{$announcements}').'#', '{$ougc_portalpoll}{$announcements}');
}

// Deactivate the plugin.
function ougc_portalpoll_deactivate($rebuild_settings=true)
{
	global $db;

	$db->delete_query('settings', "gid='12' AND name='ougc_portalpoll'");
	if($rebuild_settings)
	{
		rebuild_settings();
	}

	$db->delete_query('templates', "title IN('ougc_portalpoll', 'ougc_portalpoll_resultbit', 'ougc_portalpoll_option_multiple', 'ougc_portalpoll_option', 'ougc_portalpoll_results') AND sid='-1'");

	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('portal', '#'.preg_quote('{$ougc_portalpoll}').'#', '', 0);
}

// Run our hook
function ougc_portalpoll()
{
	global $mybb, $ougc_portalpoll;
	$ougc_portalpoll = '';
	$pid = intval($mybb->settings['ougc_portalpoll']);

	if(!$pid)
	{
		return;
	}

	global $db;

	$where = '';
	if($unviewable_forums = get_unviewable_forums(true))
	{
		$where .= ' AND p.fid NOT IN ('.$unviewable_forums.')';
	}

	if($inactiveforums = get_inactive_forums())
	{
		$where .= ' AND t.fid NOT IN('.$inactiveforums.')';
	}

	$poll = $db->fetch_array($db->simple_select('polls p LEFT JOIN '.TABLE_PREFIX.'threads t ON (t.poll=p.pid)', 'p.*, t.closed AS threadclosed, t.fid', "p.pid='{$pid}'{$where}", array('limit' => 1)));

	if(!$poll['pid'])
	{
		return;
	}

	$poll['timeout'] = $poll['timeout']*60*60*24;
	$expiretime = $poll['dateline'] + $poll['timeout'];


	global $lang;
    $lang->load('ougc_portalpoll');

	$expire_message = '';
	if($poll['timeout'])
	{
		$expire_message = $lang->sprintf($lang->ougc_portalpoll_expire_message, my_date($mybb->settings['dateformat'], $expiretime), my_date($mybb->settings['timeformat'], $expiretime));
	}

	if($poll['closed'] || $poll['threadclosed'] || ($expiretime < TIME_NOW && $poll['timeout'] > 0))
	{
		$showresults = 1;
	}

	// If the user is not a guest, check if he already voted.
	if($mybb->user['uid'])
	{
		$query = $db->simple_select('pollvotes', '*', "uid='{$mybb->user['uid']}' AND pid='{$poll['pid']}'");
		while($votecheck = $db->fetch_array($query))
		{	
			$alreadyvoted = 1;
			$votedfor[$votecheck['voteoption']] = 1;
		}
	}
	else
	{
		if(isset($mybb->cookies['pollvotes'][$poll['pid']]) && $mybb->cookies['pollvotes'][$poll['pid']] !== '')
		{
			$alreadyvoted = 1;
		}
	}

	$totpercent = '0%';
	$optionsarray = explode('||~|~||', $poll['options']);
	$votesarray = explode('||~|~||', $poll['votes']);
	$poll['question'] = htmlspecialchars_uni($poll['question']);
	$polloptions = $edit_poll = '';
	$totalvotes = 0;
	$threadlink = get_thread_link($poll['tid']);

	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$poll['totvotes'] = $poll['totvotes']+$votesarray[$i-1];
	}

	global $forum_cache, $parser, $templates, $theme;
	$forum = $forum_cache[$poll['fid']];

	for($i = 1; $i <= $poll['numoptions']; ++$i)
	{
		$parser_options = array(
			'allow_html'		=>	$forum['allowhtml'],
			'allow_mycode'		=>	$forum['allowmycode'],
			'allow_smilies'		=>	$forum['allowsmilies'],
			'allow_imgcode'		=>	$forum['allowimgcode'],
			'allow_videocode'	=>	$forum['allowvideocode'],
			'filter_badwords'	=>	1
		);

		$option = $parser->parse_message($optionsarray[$i-1], $parser_options);
		$votes = $votesarray[$i-1];
		$totalvotes += $votes;
		$number = $i;

		$optionbg = 'trow1';
		$votestar = '';
		if($votedfor[$number])
		{
			$optionbg = 'trow2';
			$votestar = '*';
		}

		if($alreadyvoted || $showresults)
		{
			if(intval($votes) == '0')
			{
				$percent = '0';
			}
			else
			{
				$percent = number_format($votes/$poll['totvotes']*100, 2);
			}
			$imagewidth = round(($percent/3) * 5);
			$imagerowwidth = $imagewidth + 10;
			eval('$polloptions .= "'.$templates->get('ougc_portalpoll_resultbit').'";');
		}
		else
		{
			if($poll['multiple'] == 1)
			{
				eval('$polloptions .= "'.$templates->get('ougc_portalpoll_option_multiple').'";');
			}
			else
			{
				eval('$polloptions .= "'.$templates->get('ougc_portalpoll_option').'";');
			}
		}
	}

	if($poll['totvotes'])
	{
		$totpercent = '100%';
	}

	if(is_moderator($poll['fid'], 'caneditposts'))
	{
		$edit_poll = " | <a href=\"{$mybb->settings['bburl']}/polls.php?action=editpoll&amp;pid={$poll['pid']}\">{$lang->ougc_portalpoll_edit}</a>";
	}

	if($alreadyvoted || $showresults)
	{
		if($alreadyvoted)
		{
			$pollstatus = $lang->ougc_portalpoll_already_voted;
			
			if($mybb->usergroup['canundovotes'] == 1)
			{
				$pollstatus .= " [<a href=\"{$mybb->settings['bburl']}/polls.php?action=do_undovote&amp;pid={$poll['pid']}&amp;my_post_key={$mybb->post_code}\">{$lang->ougc_portalpoll_undo_vote}</a>]";
			}
		}
		else
		{
			$pollstatus = $lang->ougc_portalpoll_poll_closed;
		}
		$lang->ougc_portalpoll_total_votes = $lang->sprintf($lang->ougc_portalpoll_total_votes, $totalvotes);
		eval('$ougc_portalpoll = "'.$templates->get('ougc_portalpoll_results').'";');
	}
	else
	{
		$publicnote = '&nbsp;';
		if($poll['public'] == 1)
		{
			$publicnote = $lang->public_note;
		}
		eval('$ougc_portalpoll = "'.$templates->get('ougc_portalpoll').'";');
	}

	global $plugins;

	$plugins->run_hooks('showthread_poll_results');
}