(function($) {
    $(document).ready(function() {                                                       
        var renderList = function(items) {   
            if (items.length === 0) {
                return simpleAttendance.NONE_LBL;
            } else if (items.length === 1) {
                return items[0];
            } else {    
                return items.slice(0, items.length-1).join(', ') + ' und ' + items[items.length - 1] + ' (' + items.length + ')';                    
            }
        };

        var renderAttendance = function(element, attendanceInfo) {                                        
            var attendees = attendanceInfo.attendMyself ? [simpleAttendance.ME_LBL].concat(attendanceInfo.otherAttendees) : attendanceInfo.otherAttendees;                        
            var html = simpleAttendance.ATTENDEES_LBL + ': ' + renderList(attendees);                                            

            if (attendanceInfo.attendMyself) {
                html += '<a>' + simpleAttendance.ACTION_UNATTEND + '</a>';
            } else {
                html += '<a>' + simpleAttendance.ACTION_ATTEND + '</a>';
            }

            element.html(html);
            element.find('a').click(function() {
                $.get('index.php?option=com_ajax&plugin=simpleAttendance&format=json&rp_id=' + attendanceInfo.repetitionId + '&attend=' + !attendanceInfo.attendMyself, function(data){
                    renderAttendance(element, data.data[0]);                    
                });
            });
        };                        

        $('.simple_attendance').each (function() {
            var $this = $(this);
            var attendanceInfo = $this.data('initial'); 
            renderAttendance($this, attendanceInfo);
        });                
    });
})(jQuery);