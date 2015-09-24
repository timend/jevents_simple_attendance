
<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

// Lets load the language file
$lang = JFactory::getLanguage();
$lang->load("plg_jevents_simple_attendance", JPATH_ADMINISTRATOR);

class plgJEventsSimpleAttendance extends JPlugin
{
    function doesAttend($userId, $repetitionId) {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);            
        $query->select('*')
                ->from($db->quoteName('#__simple_attendance'))
                ->where(array('rp_id = '.$repetitionId, 'user_id = '.$userId));
        $db->setQuery($query);
        $result = $db->loadObjectList();
        return !empty($result);
    }
    
    function getAttendees($repetitionId) {
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);            
        $query->select('user_id') 
                ->from($db->quoteName('#__simple_attendance'))
                ->where(array('rp_id = '.$repetitionId));
        $db->setQuery($query);
        $results = $db->loadObjectList();
        
        $attendees = array();
        
        foreach ($results as $result) {
            $attendees[] = JFactory::getUser($result->user_id);
        }
        
        return $attendees;
    }
    
    function onDisplayCustomFields(&$row){        
        $user = JFactory::getUser();
        //$row->_attendance = "Repition ID: " . $row->rp_id() . " User Id: " . $user->id;
        // $row->_attendance = "";
        JHtml::_('jquery.framework');
        $doesAttend = $this->doesAttend($user->id, $row->rp_id()) ? 'checked = "checked"' : '';
        $attendees = $this->getAttendees($row->rp_id());
        $getUserName = function($user) {return $user->username;};
        $row->_attendeesList = implode(', ', array_map($getUserName, $attendees));
        
        $row->_attendance = 
        <<<EOT
        <input type="checkbox" name="simple_attendance" value="true" id="simple_attendance"{$doesAttend}><label for="simple_attendance">Ich nehme teil</label>
        <script>
        (function($) {
            $(document).ready(function() {
                $('#simple_attendance').change( function(){                
                    var attend = $('#simple_attendance').is(':checked') ? 'true' : 'false';
                    $.get('index.php?option=com_ajax&plugin=simpleAttendance&format=json&rp_id={$row->rp_id()}&attend=' + attend, function(data){
                        //parse the JSON
                        var response = jQuery.parseJSON(data);

                        //do something with the JSON object
                    });
                });
            });
        })(jQuery);
        </script>
EOT;
                       
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
        $labels[] = JText::_("JEV_SIMPLE_ATTENDANCE_LIST", true);
        $values[] = "JEV_SIMPLE_ATTENDANCE_LIST_ENABLE";
                
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
        elseif ($code == "JEV_SIMPLE_ATTENDANCE_LIST_ENABLE")
        {
            if(isset($row->_attendeesList))
            {
                return $row->_attendeesList;
            }
        }
    }
}
