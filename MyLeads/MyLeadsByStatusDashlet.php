<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/**
 * SugarCRM is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004 - 2007 SugarCRM Inc.
 * 
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 * 
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 * 
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 * 
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 * 
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo. If the display of the logo is not reasonably feasible for
 * technical reasons, the Appropriate Legal Notices must display the words
 * "Powered by SugarCRM".
 */




require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');
require_once('include/charts/Charts.php');
require_once('modules/Dashboard/Forms.php');

class MyLeadsByStatusDashlet extends Dashlet {
    var $myleadschart_date_start;
    var $myleadschart_date_end;
    var $myleadschart_status = null;

    var $refresh = false;
    
    function MyLeadsByStatusDashlet($id, $options) {
        global $timedate;
        parent::Dashlet($id);
        $this->isConfigurable = true;
        $this->isRefreshable = true;

        if(empty($options['myleadschart_date_start'])) 
            $this->myleadschart_date_start = date($timedate->get_db_date_time_format(), strtotime('2008-01-01'));
        else
            $this->myleadschart_date_start = $options['myleadschart_date_start']; 
            
        if(empty($options['myleadschart_date_end']))
            $this->myleadschart_date_end = date($timedate->get_db_date_time_format(), time());
        else
            $this->myleadschart_date_end = $options['myleadschart_date_end'];        
            
        if(empty($options['myleadschart_status']))
            $this->myleadschart_status = array();
        else
            $this->myleadschart_status = $options['myleadschart_status'];

        if(empty($options['title'])) 
        	$this->title = translate('LBL_MY_LEAD_PIPELINE_FORM_TITLE', 'Charts');
        else
        	$this->title = $options['title'];
    }

    function saveOptions($req) {
        global $sugar_config, $timedate, $current_user, $theme;
        $options = array();
                
        $date_start = $this->myleadschart_date_start;
        $date_end = $this->myleadschart_date_end;
        $dateStartDisplay = strftime($timedate->get_user_date_format(), strtotime($date_start));
        $dateEndDisplay     = strftime($timedate->get_user_date_format(), strtotime($date_end));
        $seps               = array("-", "/");
        $dates              = array($dateStartDisplay, $dateEndDisplay);
        $dateFileNameSafe   = str_replace($seps, "_", $dates);
        if(is_file($sugar_config['tmp_dir'] . $current_user->getUserPrivGuid()."_".$theme."_my_status_".$dateFileNameSafe[0]."_".$dateFileNameSafe[1].".xml"))
            unlink($sugar_config['tmp_dir'] . $current_user->getUserPrivGuid()."_".$theme."_my_status_".$dateFileNameSafe[0]."_".$dateFileNameSafe[1].".xml");
        
        $options['title'] = $_REQUEST['myleadschart_dashlet_title'];
        $options['myleadschart_status'] = $_REQUEST['myleadschart_status'];
        $timeFormat = $current_user->getUserDateTimePreferences();
       
        $options['myleadschart_date_start'] =  $timedate->swap_formats($_REQUEST['myleadschart_date_start'], $timeFormat['date'], $timedate->dbDayFormat);
        $options['myleadschart_date_end'] =  $timedate->swap_formats($_REQUEST['myleadschart_date_end'], $timeFormat['date'], $timedate->dbDayFormat);

        return $options;
    }

    function displayOptions() {
        global $timedate, $image_path, $app_strings, $current_user, $app_list_strings;
        
        $ss = new Sugar_Smarty();
        $ss->assign('id', $this->id);
        $ss->assign('module', $_REQUEST['module']);
        $ss->assign('dashletType', 'predefined_chart');      
        $ss->assign('LBL_TITLE', translate('LBL_TITLE', 'Charts'));
        $ss->assign('LBL_CHART_TYPE', translate('LBL_CHART_TYPE', 'Charts'));
        $ss->assign('LBL_DATE_START', translate('LBL_DATE_START', 'Charts'));
        $ss->assign('LBL_DATE_END', translate('LBL_DATE_END', 'Charts'));
        $ss->assign('LBL_LEAD_STATUS', translate('LBL_LEAD_STATUS', 'Charts'));
        $ss->assign('LBL_ENTER_DATE', translate('LBL_ENTER_DATE', 'Charts'));
        $ss->assign('LBL_SELECT_BUTTON_TITLE', $app_strings['LBL_SELECT_BUTTON_TITLE']);
        $ss->assign('image_path', $image_path);
        
        //get the dates to display
        $date_start = $this->myleadschart_date_start;
        $date_end = $this->myleadschart_date_end;
        
        $timeFormat = $current_user->getUserDateTimePreferences();
        $ss->assign('date_start', $timedate->swap_formats($date_start, $timedate->dbDayFormat, $timeFormat['date']));
        $ss->assign('date_end', $timedate->swap_formats($date_end, $timedate->dbDayFormat, $timeFormat['date']));
        
        $tempx = array();
        $datax = array();
        $selected_datax = array();
        //get list of lead status keys to display
        $user_status = $this->myleadschart_status;
        $tempx = $user_status;

        //set $datax using selected lead status keys
        if (count($tempx) > 0) {
            foreach ($tempx as $key) {
                $datax[$key] = $app_list_strings['lead_status_dom'][$key];
                array_push($selected_datax, $key);
            }
        }
        else {
            $datax = $app_list_strings['lead_status_dom'];
            $selected_datax = array_keys($app_list_strings['lead_status_dom']);
        }
        
        $ss->assign('dashlet_title', $this->title);

        $ss->assign('selected_datax', get_select_options_with_id($app_list_strings['lead_status_dom'], $selected_datax));

        $ss->assign('user_date_format', $timedate->get_user_date_format());
        $ss->assign('cal_dateformat', $timedate->get_cal_date_format());
        
        $ss->assign('module', $_REQUEST['module']);
        
        return parent::displayOptions() . $ss->fetch('custom/modules/Charts/Dashlets/MyLeadsByStatusDashlet/MyLeadsByStatusDashletConfigure.tpl');
    }
    
    /**
     * Displays the javascript for the dashlet
     * 
     * @return string javascript to use with this dashlet
     */
    function displayScript() {
    	global $sugar_config, $current_user, $current_language;
		
		$xmlFile = $sugar_config['tmp_dir']. $current_user->id . '_' . $this->id . '.xml';
		$chartStringsXML = 'cache/xml/chart_strings.' . $current_language .'.lang.xml';    
    	
    	$ss = new Sugar_Smarty();
        $ss->assign('chartName', $this->id);
        $ss->assign('chartXMLFile', $xmlFile);    

        $ss->assign('chartStyleCSS', chartStyle());
        $ss->assign('chartColorsXML', chartColors());
        $ss->assign('chartStringsXML', $chartStringsXML);
                
        $str = $ss->fetch('modules/Charts/Dashlets/PredefinedChartDashletScript.tpl');     
        return $str;
    }

    function getTitle($text) {
        global $image_path, $app_strings, $sugar_config;
        
        if($this->isConfigurable) 
            $additionalTitle = '<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td width="99%">' . $text 
                               . '</td><td nowrap width="1%"><div style="width: 100%;text-align:right"><a href="#" onclick="SUGAR.mySugar.configureDashlet(\'' 
                               . $this->id . '\'); return false;" class="chartToolsLink">'    
                               . get_image($image_path.'edit','title="' . translate('LBL_DASHLET_EDIT', 'Home') . '" alt="' . translate('LBL_DASHLET_EDIT', 'Home') . '"  border="0"  align="absmiddle"').'</a> ' 
                               . '';
        else 
            $additionalTitle = '<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr><td width="99%">' . $text 
                   . '</td><td nowrap width="1%"><div style="width: 100%;text-align:right">';
        
        if($this->isRefreshable)
            $additionalTitle .= '<a href="#" onclick="SUGAR.mySugar.retrieveDashlet(\'' 
                                . $this->id . '\',\'predefined_chart\'); return false;"><img width="13" height="13" border="0" align="absmiddle" title="' . translate('LBL_DASHLET_REFRESH', 'Home') . '" alt="' . translate('LBL_DASHLET_REFRESH', 'Home') . '" src="' 
                                . $image_path . 'refresh.gif"/></a> ';
        $additionalTitle .= '<a href="#" onclick="SUGAR.mySugar.deleteDashlet(\'' 
                            . $this->id . '\'); return false;"><img width="13" height="13" border="0" align="absmiddle" title="' . translate('LBL_DASHLET_DELETE', 'Home') . '" alt="' . translate('LBL_DASHLET_DELETE', 'Home') . '" src="' 
                            . $image_path . 'close_dashboard.gif"/></a></div></td></tr></table>';
            
        if(!function_exists('get_form_header')) {
            global $theme;
            require_once('themes/'.$theme.'/layout_utils.php');
        }
        
        $str = '<div ';
        if(empty($sugar_config['lock_homepage']) || $sugar_config['lock_homepage'] == false) $str .= ' onmouseover="this.style.cursor = \'move\';"';
        $str .= 'id="dashlet_header_' . $this->id . '">' . get_form_header($this->title, $additionalTitle, false) . '</div>';
        
        return $str;
    }    
    
    function display() {
        global $app_list_strings, $current_language, $sugar_config, $currentModule, $action, $current_user, $theme, $timedate, $image_path;
        
        $this->loadLanguage('MyLeadsByStatusDashlet', 'custom/modules/Charts/Dashlets/');
        $returnStr = '';
        
        $user_dateFormat = $timedate->get_date_format();
        $current_module_strings = return_module_language($current_language, 'Charts');
        
        if(isset($_REQUEST['myleadschart_refresh'])) { 
            $refresh = $_REQUEST['myleadschart_refresh']; 
        }
        else { 
            $refresh = false;
        }
        
        $date_start = $this->myleadschart_date_start;
        $date_end = $this->myleadschart_date_end;

        // cn: format date_start|end to user's preferred
        $dateStartDisplay = strftime($timedate->get_user_date_format(), strtotime($date_start));
        $dateEndDisplay     = strftime($timedate->get_user_date_format(), strtotime($date_end));
        $seps               = array("-", "/");
        $dates              = array($date_start, $date_end);
        $dateFileNameSafe   = str_replace($seps, "_", $dates);
        //$dateXml[0]         = $timedate->swap_formats($date_start, $user_dateFormat, $timedate->dbDayFormat);
        //$dateXml[1]         = $timedate->swap_formats($date_end, $user_dateFormat, $timedate->dbDayFormat);
        $dateXml[0]			= $date_start;
        $dateXml[1]			= $date_end;
        
        $datax = array();
        $selected_datax = array();
        //get list of lead status keys to display
        $user_status = $this->myleadschart_status;
        $tempx = $user_status;
        
        //set $datax using selected lead status keys
        if (count($tempx) > 0) {
            foreach ($tempx as $key) {
                $datax[$key] = $app_list_strings['lead_status_dom'][$key];
                array_push($selected_datax, $key);
            }
        }
        else {
            $datax = $app_list_strings['lead_status_dom'];
            $selected_datax = array_keys($app_list_strings['lead_status_dom']);
        }
        $GLOBALS['log']->debug("datax is:");
        $GLOBALS['log']->debug($datax);

        $ids = array($current_user->id);
        //create unique prefix based on selected users for image files
        $id_hash = '1';
        if (isset($ids)) {
            sort($ids);
            $id_hash = crc32(implode('',$ids));
            if($id_hash < 0)
            {
                $id_hash = $id_hash * -1;
            }
        }
        $GLOBALS['log']->debug("ids is:");
        $GLOBALS['log']->debug($ids);
        $id_md5 = substr(md5($current_user->id),0,9);
        $seps               = array("-", "/");
        $dates              = array($dateStartDisplay, $dateEndDisplay);
        $dateFileNameSafe   = str_replace($seps, "_", $dates);
        $cache_file_name = $current_user->getUserPrivGuid()."_".$theme."_my_status_".$dateFileNameSafe[0]."_".$dateFileNameSafe[1].".xml";
        
        $GLOBALS['log']->debug("cache file name is: $cache_file_name");
        
        if (file_exists($sugar_config['tmp_dir'].$cache_file_name)) {
            $file_date = date($timedate->get_date_format()." ".$timedate->get_time_format(), filemtime($sugar_config['tmp_dir'].$cache_file_name));
        }
        else {
            $file_date = '';
        }

		require_once('include/Sugar_Smarty.php');
		require_once('include/SugarCharts/SugarChart.php');
		
		$sugar_smarty = new Sugar_Smarty();
	
		$charts = array();
	
		$sugarChart = new SugarChart();
		
		$sugarChart->base_url = array( 	'module' => 'Leads',
								'action' => 'index',
								'query' => 'true',
								'searchFormTab' => 'advanced_search',
							 );
		$sugarChart->url_params = array( 'assigned_user_id' => $current_user->id );		

		$sugarChart->group_by = $this->constructGroupBy();
		$query = $this->constructQuery($datax, $dateXml[0], $dateXml[1], $ids, $sugar_config['tmp_dir'].$cache_file_name, $refresh,'hBarS',$current_module_strings);

		$total = format_number($sugarChart->getTotal(), 0, 0);
		
        $sugarChart->thousands_symbol = translate('LBL_OPP_THOUSANDS', 'Charts');
        
        $subtitle = translate('LBL_LEAD_COUNT', 'Charts');
		
			$dataset = $this->constructCEChartData($this->getChartData($query));
			$sugarChart->setData($dataset);
			$total = format_number($this->getHorizBarTotal($dataset), 0, 0);		
			$pipeline_total_string = translate('LBL_TOTAL_PIPELINE', 'Charts') . $total . $sugarChart->thousands_symbol;
			$sugarChart->setProperties($pipeline_total_string, $subtitle, 'horizontal bar chart');

		$xmlFile = $sugar_config['tmp_dir']. $current_user->id . '_' . $this->id . '.xml';
	
		$sugarChart->saveXMLFile($xmlFile, $sugarChart->generateXML());
		$returnStr .= $sugarChart->display($this->id, $xmlFile, '100%', '480', false);       
	        
	    return $this->getTitle('') . '<div align="center">' .$returnStr . '</div><br />';		
    }

	// awu: Bug 16794 - this function is a hack to get the correct lead status order until i can clean it up later     
    function getChartData($query){
    	global $app_list_strings, $current_user, $sugar_config;
    	
    	$data = array();
    	$temp_data = array();
    	$selected_datax = array();
    	
    	$user_status = $this->myleadschart_status;
        $tempx = $user_status;
        
        //set $datax using selected lead status keys
        if (count($tempx) > 0) {
            foreach ($tempx as $key) {
                $datax[$key] = $app_list_strings['lead_status_dom'][$key];
                array_push($selected_datax, $key);
            }
        }
        else {
            $datax = $app_list_strings['lead_status_dom'];
            $selected_datax = array_keys($app_list_strings['lead_status_dom']);
        }
        
        $db = &PearDatabase::getInstance();
        
        $result = $db->query($query);
        $row = $db->fetchByAssoc($result, -1, false);
        
        while($row != null){
        	array_push($temp_data, $row);
        	$row = $db->fetchByAssoc($result, -1, false);
        }

		// reorder and set the array based on the order of selected_datax        
        foreach($selected_datax as $status){
        	foreach($temp_data as $key => $value){
        		if ($value['status'] == $status){
        			//$value['total'] = $symbol . $value['total'];
        			$value['status'] = $app_list_strings['lead_status_dom'][$value['status']];
        			array_push($data, $value);
        			unset($temp_data[$key]);
        		}
        	}
        }
        return $data;
    }
    
    function getHorizBarTotal($dataset){
    	$total = 0;
    	foreach($dataset as $value){
    		$total += $value;
    	}
    	
    	return $total;
    }
    
    function constructCEChartData($dataset){
    	$newData = array();
    	foreach($dataset as $key=>$value){
    		$newData[$value['status']] = $value['count'];
    	}
    	return $newData;	
    }
       
    function constructQuery($datax=array('foo','bar'), $date_start='2071-10-15', $date_end='2071-10-15', $user_id=array('1'), $cache_file_name='a_file', $refresh=false,$chart_size='hBarF',$current_module_strings) {
        global $app_strings, $charset, $lang, $barChartColors, $current_user, $theme;
        require_once('themes/' . $theme . '/layout_utils.php');
        require_once('modules/Leads/Lead.php');
        $kDelim = $current_user->getPreference('num_grp_sep');
        global $timedate;

        $opp = new Lead;
        $where="";
        //build the where clause for the query that matches $user
        $count = count($user_id);
        $id = array();
        $user_list = get_user_array(false);
        foreach ($user_id as $key) {
            $new_ids[$key] = $user_list[$key];
        }
        if ($count>0) {
            foreach ($new_ids as $the_id=>$the_name) {
                $id[] = "'".$the_id."'";
            }
            $ids = join(",",$id);
            $where .= "leads.assigned_user_id IN ($ids) ";

        }

        //build the where clause for the query that matches $datax
        $count = count($datax);
        $dataxArr = array();
        if ($count>0) {

            foreach ($datax as $key=>$value) {
                $dataxArr[] = "'".$key."'";
            }
            $dataxArr = join(",",$dataxArr);
            $where .= "AND leads.status IN ($dataxArr) ";
        }

        //build the where clause for the query that matches $date_start and $date_end
        $where .= " AND leads.date_entered >= ". db_convert("'".$date_start."'",'datetime'). " 
                    AND leads.date_entered <= ".db_convert("'".$date_end."'",'datetime') ;
        $where .= " AND leads.assigned_user_id = users.id  AND leads.deleted=0 ";

        //Now do the db queries
        //query for opportunity data that matches $datax and $user
        $query = "  SELECT leads.status,
                        users.user_name,
                        leads.assigned_user_id,
                        count( * ) AS count
                    FROM users,leads  ";



        $query .= "WHERE " .$where;
        $query .= " GROUP BY leads.status";
        $query .= ",users.user_name,leads.assigned_user_id";

		return $query;
    }
    
    function constructGroupBy(){
    	$groupBy = array('status');
    	
    	array_push($groupBy, 'user_name');

    	return $groupBy; 
    }
}

?>
