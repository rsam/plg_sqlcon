<?php
/**
 * @copyright	Copyright (C) 2013 RSA, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Import library dependencies
jimport('joomla.event.plugin');

/**
 * Content Plugin
 *
 * @package		Joomla.Plugin
 * @subpackage	Content.joomla
 * @since		1.6
 */
class plgContentSQLCon extends JPlugin
{

	function plgContentSQLCon(&$subject)
	{
		parent::__construct($subject);
	}

	function onContentPrepare($context, &$article)
	{
		$this->onPrepareContent($article, $context, false);
	}

	function onPrepareContent(&$article, &$params, $limitstart)
	{

		// Search tags
		$tag_start = '{sqlcon}';
		$tag_stop = '{/sqlcon}';

		$start_pos = strpos($article->text, $tag_start);

		// If no sqlcon tag then return
		if ($start_pos===false) return '';

		$SQLCON = false;

		$Id = $article->id;
		$SQLCON = new clsSQLCon();
		$SQLCON->Render = SQLCON_NOTHING;
		$SQLCON->Source = $article->text;
		$tag_list = sqlcon_plugin_mainloop($SQLCON, $start_pos, $tag_start, $tag_stop);
		$article->text = $SQLCON->Source;

		unset($SQLCON);
		return '';
	}
}


function sqlcon_plugin_mainloop(&$SQLCON, $start_pos, $tag_start, $tag_stop)
{
	$db = &JFactory::getDBO();
	if (!$db->connected()) {
		echo "Нет соединения с сервером баз данных. Повторите запрос позже";
		jexit();
	}
	
	// Чтение username из таблицы User
	$user = &JFactory::getUser();
			
	$username = $user->get('id');
	if($username == null) $username = 0;
			
	// Цикл составления запросов к базе данных
	do {
		$stop_pos = strpos($SQLCON->Source, $tag_stop, $start_pos);
		//echo ' start_pos='.$start_pos;
		//echo ' stop_pos='.$stop_pos;
		
		if ($stop_pos===false) {
			// Тегов больше нет
			$start_pos = false;
		}
		else {
			$sbs = substr($SQLCON->Source, $start_pos+strlen($tag_start),$stop_pos-$start_pos-strlen($tag_start));
			//echo ' $sbs='.$sbs;

			
			$lst = array();
			
			// table_name.column_name.column_name_for_where=value
			// lst[0] = table_name
			// lst[1] = column_name or constant 'usertarif'
			// lst[2] = column_name_for_where=value
			$lst = explode('.', $sbs);
			
			// table_name.usertarif.column_name_for_where=value
			// table_name.usertarifcalc.column_name_for_where=value
			
			if($lst[2] == "usertarif"){
				// table_name.column_name.usertarif
				$sql = "SELECT ". $lst[1] .
						 " FROM #__". $lst[0] .
						 " WHERE user_id = '". $username . "';";
			}			
			else{
				$lst[2] = explode('=', $lst[2]);
				// lst[2][0] = column_name_for_where
				// lst[2][1] = value
				
				switch($lst[1]){
					case "usertarifcalc": {
						
						break;
					}
					case "usertarif": {
						// tarif for current user with cat.
						$sql = "SELECT cat FROM #__tsj_account WHERE user_id = '". $username . "';";
		
						$db->setQuery( $sql );
						$row =& $db->loadResult();
		
						// Проверка на ошибки
						if (!$result = $db->query()) {
							echo $db->stderr();
						//	return false;
						}
						if ($row == "1") $lst[1] = "tarif_1";
						else if ($row == "2") $lst[1] = "tarif_2";
						else /*if ($row == "0")*/ $lst[1] = "tarif";
						
						$sql = "SELECT ". $lst[1] .
								" FROM #__". $lst[0] .
								" WHERE ". trim($lst[2][0], ' ') . " = '". trim($lst[2][1],' ') ."';";
						
						break;
					}
					default:{
						$sql = "SELECT ". $lst[1] .
							" FROM #__". $lst[0] .
							" WHERE ". trim($lst[2][0], ' ') . " = '". trim($lst[2][1],' ') ."';";
						
						break;
					}
				}
			}

			//echo "<br>sql=".$sql."<br>";
			$db->setQuery( $sql );
			$row =& $db->loadResult();

			// Проверка на ошибки
			if (!$result = $db->query()) {
				echo $db->stderr();
				//return false;
			}

			$SQLCON->Source = substr_replace($SQLCON->Source,$row,$start_pos,$stop_pos-$start_pos+strlen($tag_stop));

			$start_pos = strpos($SQLCON->Source,$tag_start);
		}
	} while ($start_pos!==false);

	//print_r($sql_table_list);
	return $tag_lst;
}

class clsSQLCon {
	function __construct() {

	}
}