(function($) {
    $(document).ready(function() {                                                       
        var renderList = function(items) {   
            if (items.length === 0) {
                return 'Keine';
            } else if (items.length === 1) {
                return items[0];
            } else {    
                return items.slice(0, items.length-1).join(', ') + ' und ' + items[items.length - 1];                    
            }
        };
        
        var renderLength = function(actualLength, maximumLength) {
            if (maximumLength === null) {
                return ' (' + actualLength + ')';
            } else {
                return ' (' + actualLength + '/' + maximumLength + ')';
            }
        }
        
        var getTargetCountClass = function(actualLength, maximumLength) {
            if (maximumLength === null) {
                return 'no-target-count';
            } else {
                if (actualLength >= maximumLength) {
                    return 'target-count-reached';
                } else {
                    return 'target-count-not-reached';
                }
            }
        }

        var renderAttendance = function(element, attendanceInfo) {  
            element.html('');
            
            $.each(attendanceInfo, function(roleName, attendanceInfo) {
                var html = '';
                var attendees = attendanceInfo.attendMyself ? [simpleAttendance.ME_LBL].concat(attendanceInfo.otherAttendees) : attendanceInfo.otherAttendees;                        
                html += '<div class=\'role ' + getTargetCountClass(attendees.length, attendanceInfo.targetCount) + '\'>';
                html += roleName + renderLength(attendees.length, attendanceInfo.targetCount) + ': ' + renderList(attendees);                                            

                if (attendanceInfo.attendMyself) {
                    html += '<a>Als ' + roleName + ' absagen</a>';
                } else {
                    if (attendanceInfo.allowNewAttendees) {
                        html += '<a>Als ' + roleName + ' zusagen</a>';
                    }
                }

                html += '</div>';               
                
                var newElement = $(html);
                newElement.find('a').click(function() {
                    $.get('index.php?option=com_ajax&plugin=simpleAttendance&format=json&rp_id=' + attendanceInfo.repetitionId + 
                            '&ev_id=' + attendanceInfo.eventId + 
                            '&role=' + roleName +
                            '&attend=' + !attendanceInfo.attendMyself                             
                    , function(data){
                        renderAttendance(element, data.data[0]);                    
                    });
                }); 
                
                newElement.appendTo(element);
            });                   
        };                        

        $.fn.max = function(selector) { 
            return Math.max.apply(null, this.map(function(index, el) { return selector.apply(el); }).get() ); 
        }

        $.fn.min = function(selector) { 
            return Math.min.apply(null, this.map(function(index, el) { return selector.apply(el); }).get() );
        }

        var addRole = function(button) {            
            var index = Math.max($('input.attendanceRole').max(function() {
                return parseInt(this.name.match(/custom_AttendanceRoles\[(\d+)\]/)[1]);
            }) + 1, 0);
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
        
        // TODO: Disable handlers if links have attribute "disabled"
        $('#addAttendanceRole').click (function() {            
            addRole(this, $('#attendanceRoles'));            
        });
        
        $('#attendanceRoles').on('click', '.removeAttendanceRole', function() {            
            $(this).closest('tr').remove();            
        });
    });
})(jQuery);