
<?php
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.plugin.plugin' );

// Lets load the language file
$lang = JFactory::getLanguage();
$lang->load("plg_jevents_simple_attendance_ajax", JPATH_ADMINISTRATOR);

class plgAjaxSimpleAttendance extends JPlugin
{
    function onAjaxSimpleAttendance()
    {        
        $input        = JFactory::getApplication()->input;
        $repetitionId = $input->getInt('rp_id');
        $userId       = JFactory::getUser()->id;
        $attend       = filter_var($input->get('attend', 'true'), FILTER_VALIDATE_BOOLEAN);
        
        //TODO: Permission check!
        //TODO: Prevent SQL injection!     
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
            
        if ($attend) {           
            $query  ->insert($db->quoteName('#__simple_attendance'))
                    ->columns($db->quoteName(array('rp_id', 'user_id')))
                    ->values($repetitionId . ',' . $userId);        
        } else {
            $query ->delete($db->quoteName('#__simple_attendance'))
                   ->where(array('rp_id = '.$repetitionId, 'user_id = '.$userId));
        }
        
        $db->setQuery($query);
        return $db->execute();
    }
}
