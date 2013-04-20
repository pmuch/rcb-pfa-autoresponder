/* Autoresponder interface (tab) */

if (window.rcmail) {
    rcmail.addEventListener('init', function(evt) {
    var tab = $('<span>').attr('id', 'settingstabpluginautoresponder').addClass('tablink');

    var button = $('<a>').attr('href', rcmail.env.comm_path+'&_action=plugin.pfadmin_autoresponder').html(rcmail.gettext('autoresponder','pfadmin_autoresponder'), 'error')).appendTo(tab);
        button.bind('click', function(e){ return rcmail.command('plugin.pfadmin_autoresponder', this) });
    rcmail.add_element(tab, 'tabs');       
     
    rcmail.register_command('plugin.pfadmin_autoresponder', function() { rcmail.goto_url('plugin.pfadmin_autoresponder') }, true);
    rcmail.register_command('plugin.pfadmin_autoresponder-save', function() { 
    var input_date = rcube_find_object('_autoresponderdate');
    var input_datefrom = rcube_find_object('_autoresponderdatefrom');
    var input_subject = rcube_find_object('_autorespondersubject');
    
    if(input_subject.value == ""){
      rcmail.display_message(rcmail.gettext('subjectempty','pfadmin_autoresponder'), 'error');    
      input_subject.focus();    
//    }
//    else if (!ValidateDate()) {
//	    input_date.focus();
    } else {
	    document.forms.autoresponderform.submit();
    }
    }, true);
  })
}

/**
 * DHTML date validation script. Courtesy of SmartWebby.com (http://www.smartwebby.com/dhtml/)
 */
// Declaring valid date character, minimum year and maximum year
var dtCh= "/";
var minYear=1900;
var maxYear=2100;

function isInteger(s){
	var i;
    for (i = 0; i < s.length; i++){   
        // Check that current character is number.
        var c = s.charAt(i);
        if (((c < "0") || (c > "9"))) return false;
    }
    // All characters are numbers.
    return true;
}

function stripCharsInBag(s, bag){
	var i;
    var returnString = "";
    // Search through string's characters one by one.
    // If character is not in bag, append to returnString.
    for (i = 0; i < s.length; i++){   
        var c = s.charAt(i);
        if (bag.indexOf(c) == -1) returnString += c;
    }
    return returnString;
}

function daysInFebruary (year){
	// February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}
function DaysArray(n) {
	for (var i = 1; i <= n; i++) {
		this[i] = 31
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
		if (i==2) {this[i] = 29}
   } 
   return this
}

function isDate(dtStr,ex){
	var daysInMonth = DaysArray(12);
	var pos1=dtStr.indexOf(dtCh);
	var pos2=dtStr.indexOf(dtCh,pos1+1);
	var strYear=dtStr.substring(0,pos1);
	var strMonth=dtStr.substring(pos1+1,pos2);
	var strDay=dtStr.substring(pos2+1);
	strYr=strYear;
	if (strDay.charAt(0)=="0" && strDay.length>1) strDay=strDay.substring(1);
	if (strMonth.charAt(0)=="0" && strMonth.length>1) strMonth=strMonth.substring(1);
	for (var i = 1; i <= 3; i++) {
		if (strYr.charAt(0)=="0" && strYr.length>1) strYr=strYr.substring(1);
	}
	month=parseInt(strMonth);
	day=parseInt(strDay);
	year=parseInt(strYr);
	if (pos1==-1 || pos2==-1){
		rcmail.display_message(rcmail.gettext('dateformatinconsistency', 'pfadmin_autoresponder') + ": " + rcmail.gettext('dateformat', 'pfadmin_autoresponder'),'error'); //yyyy/mm/dd
		return false;
	}
	if (strMonth.length<1 || month<1 || month>12){
		rcmail.display_message(rcmail.gettext('entervalidmonth', 'pfadmin_autoresponder'),'error');
		return false;
	}
	if (strDay.length<1 || day<1 || day>31 || (month==2 && day>daysInFebruary(year)) || day > daysInMonth[month]){
		rcmail.display_message(rcmail.gettext('entervalidday', 'pfadmin_autoresponder'), 'error');
		return false;
	}
	if (strYear.length != 4 || year==0 || year<minYear || year>maxYear){
		rcmail.display_message(rcmail.gettext('enterfordigityear', 'pfadmin_autoresponder') +" "+minYear+" " + rcmail.gettext('and', 'pfadmin_autoresponder') +" "+maxYear+".",'error');
		return false;
	}
	if (dtStr.indexOf(dtCh,pos2+1)!=-1 || isInteger(stripCharsInBag(dtStr, dtCh))==false){
		rcmail.display_message(rcmail.gettext('entervaliddate', 'pfadmin_autoresponder'),'error');
		return false
	}
	var vDate = Date.parse(dtStr);
  var today = new Date().getTime();
  if(vDate < today && ex.checked){
    alert(rcmail.gettext('dateinpast', 'pfadmin_autoresponder'));
    ex.checked = "";
    return true;
  }
  return true;
}

function ValidateDate(){
	var dt=document.autoresponderform._autoresponderdate;
	var ex=document.autoresponderform._autoresponderexpires;
	if (dt.value == "")
    return true;
	if (isDate(dt.value,ex)==false){
		return false;
	}
    return true;
}
