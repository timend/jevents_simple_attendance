
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
    
    function onAjaxSimpleAttendance()
    {                
        $input        = JFactory::getApplication()->input;
        $repetitionId = $input->getInt('rp_id');
        $userId       = JFactory::getUser()->id;
        $attend       = is_null($input->get('attend')) ? null : filter_var($input->get('attend'), FILTER_VALIDATE_BOOLEAN);
        
        //TODO: Authorization check!                                      
        if (isset($attend)) {
            $db    = JFactory::getDbo();
            $query = $db->getQuery(true);
            if ($attend) {           
                $query  ->insert($db->quoteName('#__simple_attendance'))
                        ->columns($db->quoteName(array('rp_id', 'user_id')))
                        ->values((int)$repetitionId . ',' . (int)$userId);        
            } else {
                $query ->delete($db->quoteName('#__simple_attendance'))
                       ->where(array('rp_id = '. (int)$repetitionId, 'user_id = '.(int)$userId));
            }
            
            $db->setQuery($query);
            $db->execute();
        }
        
        return $this->getAttendanceInfo($repetitionId, $userId);
    }
}
