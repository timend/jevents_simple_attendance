
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
        $query->select(array('user_id', 'role')) 
                ->from($db->quoteName('#__simple_attendance'))
                ->where(array('rp_id = '.(int)$repetitionId));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        $attendees = array();                
        
        foreach ($results as $result) {            
            if (!isset($attendees[$result->role])) {
                $attendees[$result->role] = array();
            }
            
            $attendees[$result->role][] = JFactory::getUser($result->user_id);
        }
        
        return $attendees;
    }   
    
    function getRoles($eventId, $repetitionId) {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);            
        $query->select('rawdata') 
                ->from($db->quoteName('#__jevents_vevent'))
                ->where(array('ev_id = '.(int)$eventId));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        $rawData = unserialize($results[0]->rawdata);
        
        if (isset($rawData['custom_AttendanceRoles'])) {
            return $rawData['custom_AttendanceRoles'];
        } else {
            return array();
        }
    }    
    
    function getAttendanceInfo($eventId, $repetitionId, $userId) {
        $attendeesOfAllRoles = $this->getAttendees($repetitionId);        
        $roles = $this->getRoles($eventId, $repetitionId);
        $getAttendanceRoleInfo = function($attendees) use ($repetitionId, $eventId, $userId) {            
            $getUserName = function($user) {return $user->username;};
            $notMeFilter = function($otherUser) use($userId) {return $userId != $otherUser->id;};
                    
            $result = new stdClass();
            $result->repetitionId = $repetitionId;
            $result->eventId = $eventId;
            $result->otherAttendees = array_map($getUserName, array_filter($attendees, $notMeFilter));
            $result->attendMyself = count($attendees) > count($result->otherAttendees);
            $result->allowNewAttendees = false;
            $result->targetCount = null;
            return $result;
        };
        
        $result = array_map($getAttendanceRoleInfo, $attendeesOfAllRoles);
        
        foreach ($roles as $role) {            
            if (!isset($result[$role['name']])) {
                $result[$role['name']] = new stdClass();
                $result[$role['name']]->repetitionId = $repetitionId;
                $result[$role['name']]->otherAttendees = array();
                $result[$role['name']]->attendMyself = false;                
                $result[$role['name']]->eventId = $eventId;
            }
            
            $result[$role['name']]->allowNewAttendees = true;
            $result[$role['name']]->targetCount = empty($role['count']) ? null : $role['count'];
        }
        
        ksort($result);
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
        $attendenceInfo = $this->getAttendanceInfo($row->ev_id(), $row->rp_id(), $userId);                     
        
        $attendenceInfoEscaped = htmlspecialchars(json_encode($attendenceInfo));
        $row->_attendance = 
            "<div class=\"simple_attendance\" id=\"simple_attendance_{$attendenceInfo->repetitionId}\" data-initial=\"$attendenceInfoEscaped\"></div>";
            
        return $row->_attendance;
    }
    
    
    function onEditCustom(&$row, &$customFields){ 
         $customField = array();
         $customField['group'] = 'default';
         $customField['label'] = 'TestLabel';
                  
         $roles = $this->getRoles($row->ev_id(), null);
         $roleToInput = function($index, $role) { 
             return "<tr><td><input class=\"attendanceRole\" type=\"text\" name=\"custom_AttendanceRoles[$index][name]\" value=\"{$role['name']}\"/></td>".
                    "<td><input type=\"text\" name=\"custom_AttendanceRoles[$index][count]\" value=\"{$role['count']}\"/></td>".
                    "<td><a class=\"btn btn-small removeAttendanceRole\">Löschen</a></td>".
                    "</tr>";              
         };
         
         $input = '<table id="attendanceRoles"><tr><th>Art der Teilnehmer</th><th>Zielanzahl</th><th></tr>' . join('', array_map($roleToInput, array_keys($roles), $roles)) . '</table>';
         
         $customField['input'] = $input . '<br/><a class="btn btn-small" id="addAttendanceRole">Teilnehmer-Art hinzufügen</a>';
         $customFields['JEV_SIMPLE_ATTENDANCE_OPTIONS'] = $customField;
    }    

    static function fieldNameArray($layout='detail')
    {
        if ($layout == "edit") {
            $labels = array();
            $values = array();
            $labels[] = JText::_("JEV_SIMPLE_ATTENDANCE", true);
            $values[] = "JEV_SIMPLE_ATTENDANCE_OPTIONS";

            $return = array();
            $return['group'] = JText::_("JEV_SIMPLE_ATTENDANCE", true);
            $return['values'] = $values;
            $return['labels'] = $labels;
            return $return;
        }
        
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
        else if ($code == "JEV_SIMPLE_ATTENDANCE_OPTIONS")
        {
            if(isset($row->_attendanceOptions))
            {
                return $row->_attendanceOptions;
            }			
        }    
    }
}
