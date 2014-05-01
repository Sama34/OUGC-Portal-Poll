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

// Include a check for Advanced Sidebox
defined('IN_MYBB') && defined('IN_ASB') or die('Direct initialization of this file is not allowed.');

// Plugin API
function asb_ougc_portalpoll_info()
{
	function_exists('ougc_portalpoll_info') or require_once(MYBB_ROOT.'inc/plugins/ougc_portalpoll.php');

	if(!ougc_portalpoll_is_installed())
	{
		return false;
	}

	global $lang;
	ougc_portalpoll_lang_load();
	isset($lang->asb_addon) or  $lang->load('asb_addon');

	$info = ougc_portalpoll_info();

	return array(
		'title'			=> $info['name'],
		'description'	=> $info['description'],
		'wrap_content'	=> false,
		'xmlhttp'		=> true,
		'version'		=> '1',
		'compatibility' => '2.1',
		'settings' => array(
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
			),
			'xmlhttp_on'		=> array(
				'name'			=> 'xmlhttp_on',
				'title'			=> $lang->asb_xmlhttp_on_title,
				'description'	=> $lang->asb_xmlhttp_on_description,
				'optionscode'	=> 'text',
				'value'			=> 0
			)
		)
	);
}

// _build_template() routine
function asb_ougc_portalpoll_build_template($args, $xmlhttp=false)
{
	if(ougc_portalpoll_is_installed())
	{
		global ${$args['template_var']};

		${$args['template_var']} = ougc_build_poll(array(
			'random'	=> (bool)$args['settings']['ougc_portalpoll_random']['value'],
			'forums'	=> (string)$args['settings']['ougc_portalpoll_forums']['value'],
			'pid'		=> (int)$args['settings']['ougc_portalpoll_pid']['value'],
			'return'	=> true,
		));

		if($xmlhttp)
		{
			return !empty(${$args['template_var']}) ? ${$args['template_var']} : 'nochange';
		}

		return true;
	}

	return false;
}

// _xmlhttp() routine
function asb_ougc_portalpoll_xmlhttp($args)
{
	$poll = ougc_portalpoll_is_installed() ? asb_ougc_portalpoll_build_template($args, true) : 'nochange';

	return $poll;
}