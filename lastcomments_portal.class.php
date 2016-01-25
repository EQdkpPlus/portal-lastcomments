<?php
/*	Project:	EQdkp-Plus
 *	Package:	Last Comments Portal Module
 *	Link:		http://eqdkp-plus.eu
 *
 *	Copyright (C) 2006-2016 EQdkp-Plus Developer Team
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Affero General Public License as published
 *	by the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Affero General Public License for more details.
 *
 *	You should have received a copy of the GNU Affero General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(!defined('EQDKP_INC')){
	header('HTTP/1.0 404 Not Found');exit;
}

class lastcomments_portal extends portal_generic {
	protected static $path = 'lastcomments';
	protected static $data = array(
		'name'			=> 'LastComments Module',
		'version'		=> '0.1.0',
		'author'		=> 'Asitara',
		'icon'			=> 'fa-comment',
		'contact'		=> EQDKP_PROJECT_URL,
		'description'	=> 'Show last comments',
		'multiple'		=> false,
		'lang_prefix'	=> 'lastcomments_'
	);
	protected static $apiLevel = 20;
	protected static $positions = array('left', 'right');
	protected static $install = array(
		'autoenable'		=> '1',
		'defaultposition'	=> 'right',
		'defaultnumber'		=> '2',
	);
	public $template_file = 'lastcomments_portal.html';
	
	protected $settings	= array(
		'limit'	=> array(
			'type'		=> 'spinner',
			'default'	=> 5,
			'min'		=> 1,
			'size'		=> 2,
		),
		'length' => array(
			'type'		=> 'spinner',
			'default'	=> 350,
			'min'		=> 50,
			'max'		=> 500,
			'step'		=> 25,
			'size'		=> 3,
		),
	);
	
	public function output(){
		$intLimit		= ($this->config('limit') > 0)? $this->config('limit') : 5;
		$intLength		= ($this->config('length') > 0)? $this->config('length') : 350;
		$arrComments	= $this->pdh->get('comment', 'comments');
		
		if($arrComments){
			foreach(array_slice($arrComments, 0, $intLimit) as $arrComment){
				$strText = (strlen($arrComment['text']) > $intLength)? substr($arrComment['text'], 0, $intLength).'...' : $arrComment['text'];
				
				$this->tpl->assign_block_vars('pm_lastcomments', array(
					'ID'			=> $arrComment['id'],
					'USER_ID'		=> $arrComment['userid'],
					'USER_NAME'		=> $this->pdh->geth('user', 'name', array($arrComment['userid'], '', '', true)),
					'USER_AVATAR'	=> $this->pdh->geth('user', 'avatarimglink', array($arrComment['userid'], false)),
					'DATE'			=> $this->time->user_date($arrComment['date'], true),
					'TEXT'			=> $strText,
				));
			}
		}
		
		$this->tpl->add_css('
			#pm_lastcomments { line-height: 18px; }
			.lastcomments_comment { margin-bottom: 8px; }
			.lastcomment_date { margin-left: 38px; }
		');
		
		return 'Error: Template file is empty.';
	}
}
?>