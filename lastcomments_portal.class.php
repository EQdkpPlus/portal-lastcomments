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
		'version'		=> '0.1.5',
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
	
	public function get_settings($state){
		$arrCategories = $this->pdh->get('article_categories', 'id_list', array(true));
		$settings = array(
			'categories'	=> array(
				'type'		=> 'multiselect',
				'options'	=> $this->pdh->aget('article_categories', 'name', 0, array($arrCategories)),
			),
			'limit'	=> array(
				'type'		=> 'spinner',
				'default'	=> 5,
				'min'		=> 1,
				'size'		=> 2,
			),
			'length' => array(
				'type'		=> 'spinner',
				'default'	=> 150,
				'min'		=> 50,
				'max'		=> 500,
				'step'		=> 25,
				'size'		=> 3,
			),
		);
		return $settings;
	}
	
	public function output(){
		$intLimit		= ($this->config('limit') > 0)? $this->config('limit') : 5;
		$intLength		= ($this->config('length') > 0)? $this->config('length') : 150;
		$arrCategoryIDs	= $this->config('categories');
		if(empty($arrCategoryIDs)) $arrCategoryIDs = array();
		
		$myrootpath		= $this->server_path;
		$this->bbcode->SetSmiliePath($myrootpath.'images/smilies');
		
		//fetch all article_ids
		$arrArticleIDs = $arrFilteredComments = array();
		foreach($arrCategoryIDs as $intCategoryID){
			$arrArticleIDs = array_merge($arrArticleIDs, $this->pdh->get('article_categories', 'published_id_list', array($intCategoryID, $this->user->id, false)));
		}
		$arrArticleIDs = array_unique($arrArticleIDs);
		
		//filter comments by attach_id = article_id
		$arrComments = $this->pdh->get('comment', 'filtered_list', array('articles'));
		foreach($arrComments as $arrComment){
			if(strpos($arrComment['attach_id'], '_') == false){
				$arrComment['article_id']	= $arrComment['attach_id'];
				$arrComment['is_cal_event']	= false;
			}else{
				$arrAttachIDs = explode('_', $arrComment['attach_id']);
				$arrComment['article_id']	= $arrAttachIDs[0];
				$arrComment['event_id']		= $arrAttachIDs[1];
				$arrComment['is_cal_event']	= true;
			}
			
			if(in_array($arrComment['article_id'], $arrArticleIDs)){
				$arrFilteredComments[] = $arrComment;
			}
		}
		
		//output filtered comments
		if($arrFilteredComments){
			foreach(array_slice($arrFilteredComments, 0, $intLimit) as $arrComment){
				if($arrComment['is_cal_event']){
					$strArticlePath = $this->routing->build('calendarevent', $this->pdh->get('calendar_events', 'name', array($arrComment['event_id'])), $arrComment['event_id'], true, true);
				}else{
					$strArticlePath = $this->pdh->get('articles', 'path', array($arrComment['article_id']));
				}
				$strText = $this->bbcode->remove_embeddedMedia($arrComment['text']);
				$strText = str_replace('<div class="embed-content"><div class="embed-media"></div></div>', " [Media]", $strText);
				$intLengthPlain = strlen($strText);
				
				$strText = $this->autolink($strText);
				$strText = $this->bbcode->toHTML($strText);
				$intLengthHTML = strlen($strText);
				$diff = $intLengthHTML-$intLengthPlain;
				if($diff < 0) $diff = 2;

				$strText = ($intLengthPlain > $intLength)? truncate($strText, $intLength+($diff/2), ' ...', false, true) : $strText;
				
				#$strText = $this->bbcode->toHTML($strText);
				$strText =  register('myemojione')->shortcodeToImage($strText);
				$strText = $this->bbcode->MyEmoticons($strText);
				
				$this->tpl->assign_block_vars('pm_lastcomments', array(
					'ID'			=> $arrComment['id'],
					'USER_ID'		=> $arrComment['userid'],
					'USER_NAME'		=> $this->pdh->geth('user', 'name', array($arrComment['userid'], '', '', true)),
					'USER_AVATAR'	=> $this->pdh->geth('user', 'avatarimglink', array($arrComment['userid'], false)),
					'DATE'			=> $this->time->user_date($arrComment['date'], true),
					'TEXT'			=> $strText,
					'ARTICLE_PATH'	=> ucfirst($strArticlePath),	
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
	
	private function autolink($str) {
		$str = ' ' . $str;
		$str = preg_replace_callback(
				"/\[url\s*+=\s*+([^]\s]++)]([^[]++)\[\/url]/im",
				function ($matches) {
					$url = strlen($matches[1]) ? $matches[1] : $matches[2];
					$text = $matches[2];
					if(mb_strlen($text) > 45){
						$text = mb_substr($text, 0, 20) .'...' . mb_substr($text, -20);
					}
					return '[url='.$url.']'.$text.'[/url]';
				},
				$str
				);
		
		$str = preg_replace_callback(
				"/\[url]([^[]++)\[\/url]/im",
				function ($matches) {
					$url = $matches[1];
					$text = $url;
					if(mb_strlen($text) > 45){
						$text = mb_substr($text, 0, 20) .'...' . mb_substr($text, -20);
					}
					return '[url='.$url.']'.$text.'[/url]';
				},
				$str
				);
		
		//Normal
		$str = preg_replace_callback(
				"/(^|[^=\]\"\/])((((http|https|ftp):\/\/|www.)\S++))/im",
				function ($matches) {
					$url = $matches[2];
					$text = $url;
					if(mb_strlen($text) > 45){
						$text = mb_substr($text, 0, 20) .'...' . mb_substr($text, -20);
					}
					return $matches[1].'[url='.$url.']'.$text.'[/url]';
				},
				$str
				);
		
		$str = substr($str, 1);
		$str = preg_replace('`url=\"www`','url="http://www',$str);
		$str = preg_replace('`url=www`','url=http://www',$str);
		
		// fügt http:// hinzu, wenn nicht vorhanden
		return trim($str);
	}
}
?>
