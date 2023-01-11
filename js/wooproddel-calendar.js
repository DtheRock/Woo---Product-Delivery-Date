jQuery(document).ready(function($) {
     try {
        
        // Create an empty array to store the data
        var dataArray = [];

        $('table.wooproddel-calendar').DataTable( {
            stateSave: true,
            "lengthMenu": [[5, 10, 25, 50, 75, -1], [5, 10, 25, 50, 75, "All"]],
            "pagingType": "full_numbers",
            "order": [[ 0, "asc" ]],
            "columnDefs": [
                { "orderable": true, "targets": 0 },
                { "orderable": true, "targets": 1 },
                { "orderable": true, "targets": 2 },
                { "orderable": true, "targets": 3 },
                { "orderable": true, "targets": 4 },
                { "orderable": true, "targets": 5 },
                { "orderable": true, "targets": 6 },
                { "orderable": true, "targets": 7 },
                { "orderable": true, "targets": 8 },
            ],
            initComplete: function () {
                $('.dataTables_wrapper .dataTables_paginate .paginate_button').addClass("button-primary");
                $('.dataTables_wrapper thead th').each( function () {
                    $(this).append(' <i class="fas fa-sort"></i>');
                    $(this).on('mouseover', function(){
                        $(this).css('cursor', 'pointer');
                    });
                });
            }
        });

        // Add an on change event listener to all input fields
        $('table.wooproddel-calendar td').find('input[name^="delivery_date"], input[name^="product_delivery_date"]').on('change', function(){
           // Get the data from the inputs and store it in an object 
           var obj = {
               order_id: $(this).closest('tr').find('td:first').text(),
               delivery_date: $(this).closest('td').find('input[name^="delivery_date"]').val(),
               product_delivery_date: $(this).closest('td').find('input[name^="product_delivery_date"]').val(),
               order_item_id: $(this).closest('tr').find('td:nth-child(8)').text()
           };

           // Push the object to the data array 
           dataArray.push(obj);
        });

        $(window).on('click', function(){
        
            $('.dataTables_wrapper .dataTables_paginate .paginate_button').addClass("button-primary");
        });
        $('.dataTables_filter input').on('keyup', function(){
            $('.dataTables_wrapper .dataTables_paginate .paginate_button').addClass("button-primary");
        });

        // Add a click event listener to the submit button
        $('.button').on('click', function(){
            var requestsComplete = 0;
            for (var i = 0; i < dataArray.length; i++) {
                var data = dataArray[i];

                // Send the data to the AJAX request
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_delivery_date',
                        delivery_date: data.delivery_date,
                        product_delivery_date: data.product_delivery_date,
                        // Use the order item id if it exists, otherwise use the order id
                        order_id: data.order_item_id || data.order_id
                    },
                   success: function(data) {
                    requestsComplete++;
                    // When all requests are finished
                    if (requestsComplete === dataArray.length) {
                        alert('Delivery date updated successfully!');
                        window.location.reload();
                    }
                }
            });
        }
        });
     } catch (err) {
        console.log(err);
    }
    });