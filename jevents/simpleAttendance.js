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

        $.fn.max = function(selector) { 
            return Math.max.apply(null, this.map(function(index, el) { return selector.apply(el); }).get() ); 
        }

        $.fn.min = function(selector) { 
            return Math.min.apply(null, this.map(function(index, el) { return selector.apply(el); }).get() );
        }

        var addRole = function(button) {            
            var index = $('input.attendanceRole').max(function() {
                return parseInt(this.name.match(/custom_AttendanceRoles\[(\d+)\]/)[1]);
            }) + 1;
            $('#attendanceRoles tr:last').after(
                '<tr>' +
                '<td><input class="attendanceRole" type="text" name="custom_AttendanceRoles['+index+'][name]" value=""/></td>' +
                '<td><input type="text" name="custom_AttendanceRoles['+index+'][count]" value=""/></td>' +
                '<td><a class=\"btn btn-small removeAttendanceRole\">Löschen</a></td>' +
                '</tr>'
            );
        }
        
        $('.simple_attendance').each (function() {
            var $this = $(this);
            var attendanceInfo = $this.data('initial'); 
            renderAttendance($this, attendanceInfo);
        });        
        
        $('#addAttendanceRole').click (function() {
            addRole(this, $('#attendanceRoles'));
        })               
        
        $('#attendanceRoles').on('click', '.removeAttendanceRole', function() {
            $(this).closest('tr').remove();
        });
    });
})(jQuery);