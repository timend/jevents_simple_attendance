
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
        $result->otherAttendees = array_map($getUserName, array_filter($attendees, $notMeFilter));
        $result->attendMyself = count($attendees) > count($result->otherAttendees);
        return $result;
    }
    //End of duplicated code from plgAjaxSimpleAttendance
    
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
        $attendenceInfoJson = json_encode($this->getAttendanceInfo($row->rp_id(), $userId));         
        
        //TODO: Optimize multiple incarnations of the same script in a single page
        $row->_attendance =                                 
        <<<EOT
        <div class="simple_attendance" id="simple_attendance_{$row->rp_id()}"></div>        
        <script>
        (function($) {
            $(document).ready(function() {                                                       
                var renderList = function(items) {   
                    if (items.length == 0) {
                        return 'keine';
                    } else if (items.length == 1) {
                        return items[0];
                    } else {    
                        return items.slice(0, items.length-1).join(', ') + ' und ' + items[items.length - 1] + ' (' + items.length + ')';                    
                    }
                };
        
                var renderAttendance = function(element, attendanceInfo) {                                        
                    var attendees = attendanceInfo.attendMyself ? ['Ich'].concat(attendanceInfo.otherAttendees) : attendanceInfo.otherAttendees;                        
                    var html = 'Teilnehmer: ' + renderList(attendees);                                            
                    
                    if (attendanceInfo.attendMyself) {
                        html += '<a>Nicht mehr teilnehmen</a>';
                    } else {
                        html += '<a>Auch teilnehmen</a>';
                    }
                        
                    element.html(html);
                    element.find('a').click(function() {
                        $.get('index.php?option=com_ajax&plugin=simpleAttendance&format=json&rp_id={$row->rp_id()}&attend=' + !attendanceInfo.attendMyself, function(data){
                            renderAttendance(element, data.data[0]);                    
                        });
                    });
                };                        
                    
                var initialAttendenceInfo = $attendenceInfoJson;
                    
                renderAttendance($('#simple_attendance_{$row->rp_id()}'), initialAttendenceInfo);
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
