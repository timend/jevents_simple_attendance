
<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

// Lets load the language file
$lang = JFactory::getLanguage();
$lang->load("plg_jevents_simple_attendance", JPATH_ADMINISTRATOR);

class plgJEventsSimpleAttendance extends JPlugin
{
    //TODO: Unduplicate these two functions from plgAjaxSimpleAttendance
    function getAttendees($repetitionId) {        
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);            
        $query->select('user_id') 
                ->from($db->quoteName('#__simple_attendance'))
                ->where(array('rp_id = '.(int)$repetitionId));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        $attendees = array();
        
        foreach ($results as $result) {
            $attendees[] = JFactory::getUser($result->user_id);
        }
        
        return $attendees;
    }   
    
    function getAttendanceInfo($repetitionId, $userId) {
        $attendees = $this->getAttendees($repetitionId);
        $getUserName = function($user) {return $user->username;};
        $notMeFilter = function($otherUser) use($userId) {return $userId != $otherUser->id;};
        
        $result = new stdClass();
        $result->repetitionId = $repetitionId;
        $result->otherAttendees = array_map($getUserName, array_filter($attendees, $notMeFilter));
        $result->attendMyself = count($attendees) > count($result->otherAttendees);
        return $result;
    }
    //End of duplicated code from plgAjaxSimpleAttendance
    
    public function onContentPrepare($context, &$row, &$params, $page = 0)
    {
        $document = JFactory::getDocument();
        $document->addScript(JURI::base(). "plugins/jevents/simpleAttendance/simpleAttendance.js");
        
        $lang = JFactory::getLanguage();
        $lang->load("plg_jevents_simple_attendance", JPATH_ADMINISTRATOR);

        $localizedProperties = new stdClass();
        $localizedProperties->ATTENDEES_LBL = $lang->_('PLG_JEVENTS_SIMPLE_ATTENDANCE_ATTENDEES_LBL');
        $localizedProperties->ACTION_ATTEND = $lang->_('PLG_JEVENTS_SIMPLE_ATTENDANCE_ACTION_ATTEND');
        $localizedProperties->ACTION_UNATTEND = $lang->_('PLG_JEVENTS_SIMPLE_ATTENDANCE_ACTION_UNATTEND');
        $localizedProperties->ME_LBL = $lang->_('PLG_JEVENTS_SIMPLE_ATTENDANCE_ME_LBL');
        $localizedProperties->NONE_LBL = $lang->_('PLG_JEVENTS_SIMPLE_ATTENDANCE_NONE_LBL');
        $document->addScriptDeclaration("var simpleAttendance = ". json_encode($localizedProperties). ";");
    }
    
    function onDisplayCustomFieldsMultiRow($rows)
    {
        //TODO: Optimize if necessary
        foreach($rows as $row) {
            $this->onDisplayCustomFields($row);
        }
    }
    
    function onDisplayCustomFields(&$row){                  
        JHtml::_('jquery.framework');        
        $userId       = JFactory::getUser()->id;
        $attendenceInfo = $this->getAttendanceInfo($row->rp_id(), $userId);                     
        
        $attendenceInfoEscaped = htmlspecialchars(json_encode($attendenceInfo));
        $row->_attendance = 
            "<div class=\"simple_attendance\" id=\"simple_attendance_{$attendenceInfo->repetitionId}\" data-initial=\"$attendenceInfoEscaped\"></div>";
        
        return $row->_attendance;
    }

    static function fieldNameArray($layout='detail')
    {
        if ($layout != "detail" && $layout != "list") {
            return array();
        }
        
        $labels = array();
        $values = array();
        
        $labels[] = JText::_("JEV_SIMPLE_ATTENDANCE", true);
        $values[] = "JEV_SIMPLE_ATTENDANCE_ENABLE";
                
        $return = array();
        $return['group'] = JText::_("JEV_SIMPLE_ATTENDANCE", true);
        $return['values'] = $values;
        $return['labels'] = $labels;

        return $return;

    }
    static function substitutefield($row, $code)
    {
        if ($code == "JEV_SIMPLE_ATTENDANCE_ENABLE")
        {
            if(isset($row->_attendance))
            {
                return $row->_attendance;
            }			
        }
    }
}
