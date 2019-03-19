jQuery(document).ready(function($) {           //wrapper

	//ajax
	var  Set = "";
	var query;
	var Table;
	var oTable;
	var columns_data;
	var display_entities;

	Table = $('#dbtable-explorer-name').find(":selected").text();

	$('#adc-entities').each(function(){
		if ($(this).is(":checked")) {
			display_entities = 1;
		} else {
			display_entities = 0;
		}
	});
	console.log('table= '+Table);
	setTimeout(function(){
		refresh_datatable(Table);
		console.log('refreshing');
	}, 2000);



	$('#dbtable-explorer-name').on('change',function(){
		Table  = $(this).val();
		if ($.fn.DataTable.isDataTable('#datatable')) {
			oTable.destroy();
			//           table.ajax.reload();
		}
		else {
			//           initializeDataTable(searchParameters);
		}    ;
		console.log('datatable destroyed new table is '+Table);
		$('#datatable  thead').find('th').empty();
		$('#datatable  thead').find('tr').empty();

		$('#datatable  tbody').find('tr').empty();
		column_data = '';
		//     	$('#datatable tfoot').html('');
		//   oTable.destroy();
		console.log('table - changed= '+Table);


		refresh_datatable(Table);
	});

	$('#adc-entities').on('change',function(){
		if ($(this).is(":checked")) {
			display_entities = 1;
		} else {
			display_entities = 0;
		}
		if ($.fn.DataTable.isDataTable('#datatable')) {
			oTable.destroy();
		}
		else {
			//           initializeDataTable(searchParameters);
		}    ;
		console.log('entities datatable destroyed new table is '+Table);
		$('#datatable  thead').find('th').empty();
		$('#datatable  thead').find('tr').empty();

		$('#datatable  tbody').find('tr').empty();
		column_data = '';

		console.log('entities - changed= '+Table);


		refresh_datatable(Table);
	});

	function refresh_datatable(Table) {
		console.log('table - refresh= '+Table);


		$.post(adc_datatables_ajax_obj.ajax_url, {         //POST request
			"_ajax_nonce": adc_datatables_ajax_obj.nonce,     //nonce
			"action": "adcGetColumns",            //action
			"dataType":"json",
			"table_name": Table,                  //data post
			"draw": 0

		}, function(json) {                    //callback
			column_data = json;
			var tableHeaders = "";

			json.forEach((item) => {
				//   console.log('item='+JSON.stringify(item));
				Object.entries(item).forEach(([key, val]) => {
					if (key == "data" ) {
						tableHeaders += "<th>" + JSON.stringify(val).replace(/"/g,'') + "</th>";
						//				  				console.log(`key-${key}-val-${JSON.stringify(val)}`)
					}
				}); //foreach key val
				//     console.log('next item');
			}); //foreach item
			tableHeaders ="<tr>"+tableHeaders+"</tr>";
			//			console.log(tableHeaders);
			$('#datatable thead').append(tableHeaders);
			//    	$('#datatable tfoot').append(tableHeaders);
			console.log('cd= '+JSON.stringify(column_data));
			console.log('added header and footer'+tableHeaders);

			oTable = $('#datatable').DataTable( {
				processing: true,
				serverSide: true,
				scrollX: true,
				ajax: {
					url: adc_datatables_ajax_obj.ajax_url,
					type: "post",
					dataType:"json",
					data: {
						_ajax_nonce: adc_datatables_ajax_obj.nonce,     //nonce
						action: "adcGetQuery",            //action
						table_name: Table,                  //data post
						display_entities: display_entities
					},
					//    "dataSrc": function ( json ) {
					//     console.log('cd= '+JSON.stringify(column_data));
					//      return json;
					//   }
				},
				//  dataSrc: data.data,
				columns: column_data
			} );

			//oTable.draw( 'page' );

		}); //ajax post


		$(this).find('.modal-dialog').css({width:'auto',
			height:'auto',
		'max-height':'100%'});

	}; //function refresh_datatable()

	//end


});  //ready


