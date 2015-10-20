
<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

// Lets load the language file
$lang = JFactory::getLanguage();
$lang->load("plg_jevents_simple_attendance_ajax", JPATH_ADMINISTRATOR);

class plgAjaxSimpleAttendance extends JPlugin
{
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
    
    function onAjaxSimpleAttendance()
    {                
        $input        = JFactory::getApplication()->input;
        $repetitionId = $input->getInt('rp_id');
        $eventId      = $input->getInt('ev_id');
        $role         = $input->getString('role');
        $userId       = JFactory::getUser()->id;
        $attend       = is_null($input->get('attend')) ? null : filter_var($input->get('attend'), FILTER_VALIDATE_BOOLEAN);
        
        //TODO: Authorization check!                                      
        if (isset($attend)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);
            if ($attend) {           
                $query  ->insert($db->quoteName('#__simple_attendance'))
                        ->columns($db->quoteName(array('rp_id', 'user_id', 'role')))
                        ->values((int)$repetitionId . ',' . (int)$userId . ',' . $db->q($role));        
            } else {
                $query ->delete($db->quoteName('#__simple_attendance'))
                       ->where(array('rp_id = '. (int)$repetitionId, 'user_id = '.(int)$userId, 'role = '.$db->q($role)));
            }
            
            $db->setQuery($query);
            $db->execute();
        }
        
        return $this->getAttendanceInfo($eventId, $repetitionId, $userId);
    }
}
