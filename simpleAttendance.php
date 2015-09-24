
<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

// Lets load the language file
$lang = JFactory::getLanguage();
$lang->load("plg_jevents_simple_attendance", JPATH_ADMINISTRATOR);

class plgJEventsSimpleAttendance extends JPlugin
{
    function onDisplayCustomFields(&$row){            
        $row->_attendance = "DisplayCustomFields: " . $row->rp_id();
        return $row->_attendance;
    }

    static function fieldNameArray($layout='detail')
    {
        if ($layout != "detail")
                return array();
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
