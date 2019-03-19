jQuery(document).ready(function($) {           //wrapper
	$('#datatable').show();


	if ($('#dry-run').is(':checked')) {
		console.log('dry-run is checked');
		$('#accept-container').fadeTo("fast",.3);
		$('#accept-terms').attr("checked",true);
	}

	$('#map-plugins, #progressBar').fadeTo("fast",0);
	//ajax
	$.post(adc_ajax_obj.ajax_url, {         //POST request
		_ajax_nonce: adc_ajax_obj.nonce,     //nonce
		action: "adcGetTables",            //action
		//          dataType:'json',
		type: "included"                  //data
	}, function(data) {                    //callback
		//  console.log('data is= '+data);
		$.each( data, function( key, value ){
			//       console.log('key= '+key+', value='+value);

			//							var select_item = '<option value="'+value+'">'+value+'</option>';
			//           $('#dbtable-explorer-name').append(select_item);
			var included_item = '<div class="dbtable-item" title="" id="'+value+'" sid="'+key+'">'+value+'</div>';
			$('#dbtable-included').append(included_item);
		});
	});
	//end
	//ajax
	$.post(adc_ajax_obj.ajax_url, {         //POST request
		_ajax_nonce: adc_ajax_obj.nonce,     //nonce
		action: "adcGetDefaultTables",            //action
		//          dataType:'json',
		type: "included"                  //data
	}, function(data) {                    //callback
		//          console.log('default data is= '+data);
		$.each( data, function( key, value ){
			//  console.log('key= '+key+', value='+value);

			$('#'+key+'.dbtable-item').addClass('dbtable-default').prop('title','Core WP table\n').change();
		});
	});
	//end
	//ajax
	$.post(adc_ajax_obj.ajax_url, {         //POST request
		_ajax_nonce: adc_ajax_obj.nonce,     //nonce
		action: "adcGetPlugins",            //action
		dataType:'json',
		type: "included"                  //data
	}, function(data) {                    //callback
		// console.log(data);
		var i = 0;
		Object.entries(data).forEach(([key, value]) => {
			if ((typeof key !== "undefined") && ( key !== null)) {
				// console.log('key= '+key+' value = '+value);
				var item = '<div class="dbtable-item" title="" id="'+key+'" pid="'+value+'" sid="'+i+'">'+key+'</div>';
				$('#dbtable-plugins').append(item);
			};
		});
		$('#map-plugins').fadeTo("slow",1);
	});
	//end
	//click to start plugin mapping


	$('#dry-run').on('change',function() {
		if ($(this).is(':checked')) {
			$('#accept-container').fadeTo("fast",.3);
			$('#accept-terms').attr("checked",true);
		} else {
			$('#accept-container').fadeTo("slow",1);
			$('#accept-terms').attr("checked",false);

		}
	});


	$('#map-plugins').on('click',function() {
		$('#progressBar').fadeTo("slow",1);
		$('#progressBar div').css({ 'background' : ''}).addClass('progressBar-ani');
		//var plugin_tables = [];
		var plugins_count = $('#dbtable-plugins div').size();
		var plugins_processed = 0;
		$('#dbtable-plugins div').each(function(){
			var plugin_name = $(this).attr("id");
			var plugin_full_name = $(this).attr("pid");
			// 			console.log('plugin_full_name='+plugin_full_name);
			$.post(adc_ajax_obj.ajax_url, {         //POST request
				_ajax_nonce: adc_ajax_obj.nonce,     //nonce
				action: "adcGetPluginTables",            //action
				dataType:'json',
				plugin_full: plugin_full_name                  //data
			},  function(data) {                    //callback
				//      console.log(data);
				//             		plugins_processed = plugins_processed + 1;

				Object.entries(data).forEach(([key, value]) => {
					if ((typeof key !== "undefined") && ( key !== null)) {
						//  console.log('key= '+key+' value = '+value);
						//  plugin_tables =plugin_name
						$('#'+plugin_name).addClass(value).attr("title", function() { return $('#'+plugin_name).attr("title") + " " + value+'\n'});
						$('#'+value).addClass(plugin_name).attr("title",  function() { return $('#'+value).attr("title") + " " + plugin_name+'\n'});

						// var item = '<div class="dbtable-item" id="'+key+'" pid="'+value+'" sid="'+i+'">'+key+'</div>';
						//       $('#dbtable-plugins').append(item);
					};
				}); //foreach data
				plugins_processed = plugins_processed + 1;
				//      console.log('processed='+plugins_processed);
				//      console.log('total='+plugins_count);
				var pct_complete= parseInt((plugins_processed/plugins_count)*100);
				//   console.log('pct= '+pct_complete);
				progress(pct_complete,$('#progressBar'));
			}); //ajax




		}); //each


	}); //onclick
	$('#dbtable-plugins').on('mouseenter mouseleave','.dbtable-item',function(e) {
		e.stopPropagation();
		e.stopImmediatePropagation();
		// $('#dbtable-plugins').hover('.dbtable-item',function(e) {
		//     console.log('hovering');
		var class_list = $(this).attr('class').split(/\s+/);
		var triggered =  $(this).attr('id');
		var etype = e.type;
		var trigger_list= '';
		$.each(class_list, function(index, item) {
			if (item!='dbtable-item') {
				trigger_list=trigger_list+"#"+item+", ";
			};
		});
		if (trigger_list.length >=2) {
			var trig_len = (trigger_list.length-2);
		} else {
			var trig_len = (trigger_list.length);

		}

		trigger_list=trigger_list.substring(0,trig_len );
		//		 console.log(etype);
		//      console.log('#dbtable-included '+trigger_list);

		if (!$(e.target).is(triggered)) {
			if ( etype == 'mouseenter') {
				//               		 console.log('adding class');
				$('#dbtable-included,#dbtable-exluded').find(trigger_list).addClass('dbtable-item-hover');
			}// mouseenter
			if ( etype == 'mouseleave') {
				//            		 console.log('removing class');
				$('#dbtable-included,#dbtable-exluded').find(trigger_list).removeClass('dbtable-item-hover');
			}// mouseleave
		}//not triggered

	});

	function progress(percent, $element) {
		//       console.log('ppct='+percent);
		var progressBarWidth = percent * $element.width() / 100;
		var real_pct = parseInt(progressBarWidth/500);
		$element.find('div').animate({ width: progressBarWidth }, 500).html('<span style="margin: 0 5px 3px 0;">'+percent + "% </span>");
		if (percent == 100) {
			$element.find('div').css({ 'background' : 'none'}).css({ 'background-color' : 'green' }).fadeTo("slow", 1);
			$element.find('div').css({ 'text-align' : 'center'}).find('span').text('Mapping Complete');
			$('#dbtable-included, #dbtable-excluded').not('dbtable-default').find('div').each(function(){
				if ($(this).attr('title').length == 0 ) {
					$(this).addClass('no-plugin');
				}
			});

			//snippet tables

			$('#dbtable-included, #dbtable-excluded').not('.dbtable-default').find('.no-plugin').each(function(){
				var table_name = $(this).attr("id");
				var plugin_name = 'code-snippets';
				console.log('found a snippet table');
				console.log('TN= '+table_name);
				$.post(adc_ajax_obj.ajax_url, {         //POST request
					_ajax_nonce: adc_ajax_obj.nonce,     //nonce
					action: "adcGetTablesInSnippets",            //action
					dataType:'json',
					table_name: table_name                  //data
				},  function(data) {                    //callback
					//      console.log(data);
					//             		plugins_processed = plugins_processed + 1;
					var value = parseInt(data.found_it);
					console.log('ST found='+value);
					//Object.entries(data).forEach(([key, value]) => {
					if ((typeof value !== "undefined") && (value == 1)) {
						//  console.log('key= '+key+' value = '+value);
						//  plugin_tables =plugin_name
						$('#dbtable-plugins').find('#'+plugin_name).addClass(table_name).attr("title", function() { return $('#'+plugin_name).attr("title") + " " + table_name+'\n'});
						$('#dbtable-included, #dbtable-excluded').find('#'+table_name).removeClass('no-plugin').addClass(plugin_name).addClass('snippet-table').attr("title",  function() { return $('#'+table_name).attr("title") + " " + plugin_name+'\n'});

					} // if value = 1
					//   }); //foreach key value;

				} //function(data)
				); //post
			}); //foreach table

			//   snippet tables


		} //percent=100

	} //function progress()
});//ready
