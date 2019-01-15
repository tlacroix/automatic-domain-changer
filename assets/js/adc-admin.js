jQuery(document).ready(function($){
	console.log('ADC loaded');
	$('#timer').hide();
	$('.adc-backup-button').click(function(ev){
		ev.preventDefault();
		$('#adc-backup-db input[name=type]').val( $(this).attr('data-type') );
		$('#adc-backup-db').submit();
	});
	if(document.getElementById('change-domain').checked) {
		$( '#table-search' ).fadeTo( 'slow' , 0.3, function() {
			// Animation complete.
		});
		$( '#table-domain' ).fadeTo( 'fast' , 1, function() {
			// Animation complete.
		});
	} else {
		$( '#table-search' ).fadeTo( 'slow' , 1, function() {
			// Animation complete.
		});
		$( '#table-domain' ).fadeTo( 'fast' , 0.3, function() {
			// Animation complete.
		});
	}
	//      $('#search').val('test').change;
	$('#search, #replace, #change-replace,#table-search').on( 'click keyup keypress blur change', function( event ) {
		//     	alert(event.element);
		$('#change-replace').attr('Checked','Checked');
		$('#change-domain').removeAttr('Checked');
		console.log('replace');
		$( '#table-domain' ).fadeTo( 'slow' , 0.3, function() {
			// Animation complete.
		});
		$( '#table-search' ).fadeTo( 'fast' , 1, function() {
			// Animation complete.
		});
	});
	$( '#old-domain, #new-domain,#change-domain,#table-domain' ).on( 'click keyup keypress blur change', function( event ) {
		//            	alert(event.type);
		$('#change-domain').attr('Checked','Checked');
		$('#change-replace').removeAttr('Checked');
		console.log('domain');
		$( '#table-search' ).fadeTo( 'slow' , 0.3, function() {
			// Animation complete.
		});
		$( '#table-domain' ).fadeTo( 'fast' , 1, function() {
			// Animation complete.
		});
	});
	$('#submit').on( 'click', function( event ) {
		//     	alert(event.element);
		$('#timer').show();
		startTimer();
	});
	function startTimer() {
		var seconds = 0;
		timer = setInterval(function() {
			seconds ++;
			document.getElementById('seconds').innerText = seconds % 60;
			document.getElementById('minutes').innerText = parseInt(seconds / 60);
		}, 1000);
	}
	$('#dbtable-included, #dbtable-excluded').on('click','.dbtable-item',function(){
		var table_id = $(this).attr('id');
		var cont = $(this).parent().attr('id');
		var target='dbtable-included';
		var source='dbtable-excluded';
		if (cont=='dbtable-included') {
			var target='dbtable-excluded';
			var source='dbtable-included';
		}
		console.log('tid='+table_id+',pcont='+cont+',source='+source+',target='+target)
		//$('#'+table_id).hide();
		$('#'+table_id).appendTo('#'+target);

		$('#'+target+' div').sort(function(a,b) {
			//	console.log('aid='+$(a).attr('id'));
			//		console.log('bid='+$(b).attr('id'));
			//		var comp_a =parseInt($(a).attr('sid').replace('te',''));
			//			var comp_b = parseInt($(b).attr('id').replace('te',''));

			var comp_a =parseInt($(a).attr('sid'));
			var comp_b = parseInt($(b).attr('sid'));
			console.log('caid='+comp_a);
			console.log('cbid='+comp_b);

			return comp_a - comp_b;
		}).appendTo('#'+target);

	});

}) //ready;
