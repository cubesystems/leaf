/* Latvian initialisation for the jQuery UI date picker plugin. */
/* Written by Jānis Grigaļuns (janis.grigaluns@cube.lv). */
jQuery(function($)
{
	$.datepicker.regional['lv'] = 
	{
		clearText: 'Nodzēst', 
		clearStatus: '',
		closeText: 'Aizvērt', 
		closeStatus: 'Aizvērt bez izmainīšanas',
		prevText: 'Iepriekšējais', 
		prevStatus: 'Aplūkot iepriekšējo mēnesi',
		nextText: 'Nākamais', 
		nextStatus: 'Aplūkot nākošo mēnesi',
		currentText: 'Pašreizējais', 
		currentStatus: 'Aplūkot pašreizējo mēnesi',
		monthNames: ['Janvāris','Februāris','Marts','Aprīlis','Maijs','Jūnijs','Jūlijs','Augusts','Septembris','Oktobris','Novembris','Decembris'],
		monthNamesShort: ['Jan','Feb','Mar','Apr','Mai','Jun','Jūl','Aug','Sep','Okt','Nov','Dec'],
		monthStatus: 'Aplūkot citu mēnesi', 
		yearStatus: 'Aplūkot citu gadu',
		weekHeader: 'Ned', 
		weekStatus: '',
		dayNames: ['Svētdiena','Pirmdiena','Otrdiena','Trešdiena','Ceturtdiena','Piektdiena','Sestdiena'],
		dayNamesShort: ['Sv','Pi','Ot','Tr','Cet','Pk','Se'],
		dayNamesMin: ['Sv','P','O','T','C','P','S'],
		dayStatus: 'Lietot DD kā nedēļas pirmo dienu', 
		dateStatus: 'Izvēlieties DD, MM d',
		dateFormat: 'dd.mm.yy', 
		firstDay: 1, 
		initStatus: 'Izvēlieties datumu', 
		isRTL: false
	};
	$.datepicker.setDefaults($.datepicker.regional['lv']);
});