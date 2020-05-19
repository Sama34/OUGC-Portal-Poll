<?php

/***************************************************************************
 *
 *	OUGC Portal Poll - ASB plugin
 *	Author: Omar Gonzalez
 *	Copyright: © 2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Shows an poll in portal.
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

// Die if IN_ASB is not defined, for security reasons.
defined('IN_ASB') or die('Direct initialization of this file is not allowed.');

// Plugin API
function asb_ougc_portalpoll_info()
{
	global $lang, $db;
	ougc_portalpoll_lang_load();

	return array(
		'title'				=> 'OUGC Portal Poll',
		'description'		=> $lang->setting_group_ougc_portalpoll_desc,
		'author'			=> 'Omar G.',
		'author_site'		=> 'http://omarg.me',
		'module_site'		=> 'http://omarg.me',
		'wrap_content'		=> true,
		'version'			=> '1.8.22',
		'compatibility'		=> '4.0',
		'xmlhttp'			=> false,
		'debugMode'			=> true,
		//'noContentTemplate'	=> 'asb_ougc_portalpoll_empty',
		'settings'			=> array(
			'ougc_portalpoll_random'	=> array(
				'name'			=> 'ougc_portalpoll_random',
				'title'			=> $lang->setting_ougc_portalpoll_random,
				'description'	=> $lang->setting_ougc_portalpoll_random_desc,
				'optionscode'	=> 'yesno',
				'value'			=> 0
			),
			'ougc_portalpoll_forums'	=> array(
				'name'			=> 'ougc_portalpoll_forums',
				'title'			=> $lang->setting_ougc_portalpoll_forums,
				'description'	=> $lang->setting_ougc_portalpoll_forums_desc,
				'optionscode'	=> 'text',
				'value'			=> ''
			),
			'ougc_portalpoll_pid'	=> array(
				'name'			=> 'ougc_portalpoll_pid',
				'title'			=> $lang->setting_ougc_portalpoll_pid,
				'description'	=> $lang->setting_ougc_portalpoll_pid_desc,
				'optionscode'	=> 'text',
				'value'			=> ''
			)
		),
		'removedTemplates'	=> array('asb_ougc_portalpoll_thread', 'asb_ougc_portalpoll_gotounread', 'asb_ougc_portalpoll_last_poster_name', 'asb_ougc_portalpoll_last_poster_avatar', 'asb_ougc_portalpoll_last_poster_avatar_avatar', 'asb_ougc_portalpoll_last_post_link', 'asb_ougc_portalpoll_no_content'),
		'installData' => array(
			'templates' => array(
				array(
					'title'		=> 'asb_ougc_portalpoll',
					'template'	=> <<<EOF
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
				),
				array(
					'title'		=> 'asb_ougc_portalpoll_option',
					'template'	=> $db->escape_string('<tr>
	<td class="trow1" width="1%"><input type="{$type}" class="{$type}" name="option{$name}" id="option_{$number}" value="{$value}" /></td>
	<td class="trow1" colspan="3">{$option}</td>
</tr>')
				),
				array(
					'title'		=> 'asb_ougc_portalpoll_results',
					'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
<br />')
				),
				array(
					'title'		=> 'asb_ougc_portalpoll_resultbit',
					'template'	=> $db->escape_string('<tr>
	<td class="{$trow}" align="right">{$option}{$votestar}</td>
	<td class="{$trow}"><img src="{$theme[\'imgdir\']}/pollbar-s.gif" alt="" /><img src="{$theme[\'imgdir\']}/pollbar.gif" width="{$imagewidth}" height="10" alt="{$percent}%" title="{$percent}%" /><img src="{$theme[\'imgdir\']}/pollbar-e.gif" alt="" /></td>
	<td class="{$trow}" width="67" align="center">{$votes}</td>
	<td class="{$trow}" width="67" align="center">{$percent}%</td>
</tr>')
				)
			),
		),
	);
}

if(!function_exists('ougc_build_poll'))
{
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
	$ougc_poll = ougc_build_poll(array(
		'random'		=> (bool)$settings['ougc_portalpoll_random'],
		'forums'		=> (string)$settings['ougc_portalpoll_forums'],
		'pid'			=> (int)$settings['ougc_portalpoll_pid'],
		'tmplprefix'	=> 'asb_ougc_portalpoll',
		'return'		=> true,
		'template_var'		=> $template_var,
	));

	return $ougc_poll;
}

if(!function_exists('ougc_build_poll'))
{
	/**
	 * Builds a formatted thread poll based off settings.
	 *
	 * @param array Poll Options ( (bool) random, (string) forums, (int) pid, (bool) return string)
	 * @return string Formatted poll
	**/
	function ougc_build_poll($options=array())
	{
		global $mybb;

		$options = array_merge(array(
			'random'		=> (bool)$mybb->settings['ougc_portalpoll_random'],
			'forums'		=> (string)$mybb->settings['ougc_portalpoll_forums'],
			'pid'			=> (int)$mybb->settings['ougc_portalpoll_pid'],
			'tmplprefix'	=> 'ougcportalpoll',
		), $options);

		if(!empty($options['template_var']))
		{
			$template_var = (string)$options['template_var'];
		}

		if(!$options['random'] && !$options['pid'] && !$options['forums'])
		{
			return false;
		}

		static $pollcache = array();

		if(!isset($pollcache[$poll['pid']]))
		{
			global $db;

			// Build a where clause
			$where = array();

			if($unviewableforums = get_unviewable_forums(true))
			{
				$where[] = 't.fid NOT IN('.$unviewableforums.')';
			}

			if($inactiveforums = get_inactive_forums())
			{
				$where[] = 't.fid NOT IN('.$inactiveforums.')';
			}

			if($options['forums'])
			{
				$where[] = 't.fid IN ('.implode(',', array_map('intval', explode(',', $options['forums']))).')';
			}

			if($options['random'] && $options['forums'])
			{
				$pids = array();

				// there should be a better way for this, but I'm lazy since it will probably cost time building a query for different engines.
				$query = $db->simple_select('polls p LEFT JOIN '.TABLE_PREFIX.'threads t ON (t.poll=p.pid)', 'p.pid', implode(' AND ', $where));
				while($pids[] = (int)$db->fetch_field($query, 'pid'));

				$options['pid'] = $pids[mt_rand(0, count(array_filter($pids)) -1)];
			}

			if($options['pid'])
			{
				$where[] = 'p.pid=\''.$options['pid'].'\'';
			}
			elseif($options['random'])
			{
				$where[] = 'RAND()';
			}

			$query_options = array('limit' => 1, 'order_by' => 'dateline', 'order_dir' => 'desc');

			///~~~***---***~~~///
			$xthreads = function_exists('xthreads_gettfcache');

			$fields = '';

			if($xthreads)
			{
				$threadfield_cache = xthreads_gettfcache();

				if(!empty($threadfield_cache))
				{
					$fids = array_flip(array_map('intval', explode(',', $settings['forum_show_list'])));
					$all_fids = ($settings['forum_show_list'] == '');
					$fields = '';
					foreach($threadfield_cache as $k => &$v) {
						$available = (!$v['forums']) || $all_fids;
						if(!$available)
							foreach(explode(',', $v['forums']) as $fid) {
								if(isset($fids[$fid])) {
									$available = true;
									break;
								}
							}
						if($available)
							$fields .= ', tfd.`'.$v['field'].'` AS `xthreads_'.$v['field'].'`';
					}
				}
			}
			///~~~***---***~~~///

			$query = $db->simple_select('polls p LEFT JOIN '.TABLE_PREFIX.'threads t ON (t.poll=p.pid) LEFT JOIN '.TABLE_PREFIX.'posts po ON (po.pid=t.firstpost) LEFT JOIN '.TABLE_PREFIX.'threadfields_data tfd ON (tfd.tid=p.tid)', 'p.*, t.closed AS threadclosed, t.fid, po.message'.$fields, implode(' AND ', $where), $query_options);
			$poll = $db->fetch_array($query);

			if(!$poll['pid'])
			{
				return false;
			}

			global $lang, $forum_cache, $parser, $templates, $theme;
			ougc_portalpoll_lang_load();

			if(!is_object($parser))
			{
				require_once MYBB_ROOT.'inc/class_parser.php';
				$parser = new postParser;
			}

			$forum_cache or cache_forums();
			$forum = $forum_cache[(int)$poll['fid']];

			$parser_options = array(
				'allow_html'		=>	(bool)$forum['allowhtml'],
				'allow_mycode'		=>	(bool)$forum['allowmycode'],
				'allow_smilies'		=>	(bool)$forum['allowsmilies'],
				'allow_imgcode'		=>	(bool)$forum['allowimgcode'],
				'allow_videocode'	=>	(bool)$forum['allowvideocode'],
				'filter_badwords'	=>	1
			);

			preg_match_all('#<img(.+?)src=\"(.+?)\"(.+?)/>#i', (string)$parser->parse_message($poll['message'], $parser_options), $matches);

			$poll['image'] = $poll['image_url'] = '';
			if(is_array($matches))
			{
				$poll['image'] = $matches[0][0];
				$poll['image_url'] = $matches[2][0];
			}

			$poll['timeout'] = $poll['timeout']*60*60*24;
			$expiretime = $poll['dateline'] + $poll['timeout'];

			$expire_message = $poll['timeout'] ? $lang->sprintf($lang->ougc_portalpoll_expire_message, my_date($mybb->settings['dateformat'], $expiretime), my_date($mybb->settings['timeformat'], $expiretime)) : '';

			$showresults = ($poll['closed'] || $poll['threadclosed'] || ($expiretime < TIME_NOW && $poll['timeout'] > 0)) ? true : false;

			$mybb->user['uid'] = (int)$mybb->user['uid'];
			$poll['pid'] = (int)$poll['pid'];

			$votedfor = array();
			$alreadyvoted = false;

			if($mybb->user['uid'])
			{
				$query = $db->simple_select('pollvotes', 'voteoption', 'uid=\''.$mybb->user['uid'].'\' AND pid=\''.$poll['pid'].'\'');
				while($voteoption = $db->fetch_field($query, 'voteoption'))
				{	
					$alreadyvoted = $votedfor[$voteoption] = true;
				}
			}
			elseif(isset($mybb->cookies['pollvotes'][$poll['pid']]) && $mybb->cookies['pollvotes'][$poll['pid']] !== '')
			{
				$alreadyvoted = true;
			}

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

			$totpercent = !empty($poll['totvotes']) ? '100%' : '0%';

			///~~~***---***~~~///
			if($xthreads && !empty($threadfield_cache)) {
				xthreads_set_threadforum_urlvars('thread', $poll['tid']);
				xthreads_set_threadforum_urlvars('forum', $poll['fid']);

				// make threadfields array
				$threadfields = array(); // clear previous threadfields
				
				foreach($threadfield_cache as $k => &$v) {
					if($v['forums'] && strpos(','.$v['forums'].',', ','.$poll['fid'].',') === false)
						continue;

					xthreads_get_xta_cache($v, $poll['tid']);
					
					$threadfields[$k] =& $poll['xthreads_'.$k];
					xthreads_sanitize_disp($threadfields[$k], $v, ($poll['username'] !== '' ? $poll['username'] : $poll['threadusername'])); // username isn't got
				}
			}
			///~~~***---***~~~///

			for($i = 1; $i <= $poll['numoptions']; ++$i)
			{
				$option = $parser->parse_message($optionsarray[$i-1], $parser_options);
				$votes = (int)$votesarray[$i-1];
				$totalvotes += $votes;
				$number = $i;

				#$trow = alt_trow();
				$trow = isset($votedfor[$number]) ? 'trow2' : 'trow1';
				$votestar = isset($votedfor[$number]) ? '*' : '';

				if($alreadyvoted || $showresults)
				{
					$percent = !$votes ? 0 : number_format($votes/$poll['totvotes']*100, 2);
					$imagewidth = round(($percent/3)*5);
					$imagerowwidth = $imagewidth+10;
					eval('$polloptions .= "'.$templates->get($options['tmplprefix'].'_resultbit').'";');
				}
				else
				{
					$type = $poll['multiple'] ? 'checkbox' : 'radio';
					$name = $poll['multiple'] ? '['.(int)$number.']' : '';
					$value = $poll['multiple'] ? 1 : (int)$number;

					eval('$polloptions .= "'.$templates->get($options['tmplprefix'].'_option').'";');
				}
			}

			$edit_poll = is_moderator($poll['fid'], 'caneditposts') ? ' | <a href="'.$mybb->settings['bburl'].'/polls.php?action=editpoll&amp;pid='.$poll['pid'].'">'.$lang->ougc_portalpoll_edit.'</a>' : '';

			if($alreadyvoted || $showresults)
			{
				$pollstatus = $alreadyvoted ? $lang->ougc_portalpoll_already_voted : $lang->ougc_portalpoll_poll_closed;
				if($alreadyvoted && $mybb->usergroup['canundovotes'])
				{
					$pollstatus .= ' [<a href="'.$mybb->settings['bburl'].'/polls.php?action=do_undovote&amp;pid='.$poll['pid'].'&amp;my_post_key='.$mybb->post_code.'">'.$lang->ougc_portalpoll_undo_vote.'</a>]';
				}

				$lang->ougc_portalpoll_total_votes = $lang->sprintf($lang->ougc_portalpoll_total_votes, $totalvotes);
				$options['tmplprefix'] .= '_results';
			}
			else
			{
				$publicnote = $poll['public'] ? $lang->public_note : '&nbsp;';
			}

			eval('$pollcache['.$poll['pid'].'] = "'.$templates->get($options['tmplprefix']).'";');

			global $plugins;

			$plugins->run_hooks('showthread_poll_results');
		}

		if($options['return'])
		{
			return $pollcache[$poll['pid']];
		}

		global $ougc_portalpoll;

		$ougc_portalpoll = (string)$pollcache[$poll['pid']];
	}
}