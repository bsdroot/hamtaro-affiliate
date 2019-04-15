jQuery( document ).ready(function($) {
  $( "#affiliate_accaunt_info" ).tabs();
  $("#get_url").click(function(){ 
  var data = {
    'action': 'generate_affiliate_code',
    'product_id': this.dataset.id,
    'plugin_url': ajax_object.plugin_url     
  };
  var myWindow = window.open(data.plugin_url+"inc/get_code_window.html", "", "width=200,height=100"); 
  
  myWindow.onload = function(){
    var button = myWindow.document.gnrcodeform; 
    var text = myWindow.document.gnrcodeform.gnrcode; 
    $.post(ajax_object.ajax_url, data, function(response) {   
        text.value=response;
    }); 
    $(button).find('a').on('click',function(e){  
        text.select();
        myWindow.document.execCommand("copy");
    });
  }; 
  });

  $(".single_add_to_cart_button").click(function(e){ 
    var data = {
      'action': 'add_script_javascript', 
      'product_id': $('#get_url')[0].dataset.id,
      'affilate_token': $('#get_token')[0].dataset.token,
      'plugin_url': ajax_object.plugin_url       
    };  
   
    $.post(ajax_object.ajax_url, data, function(response) {     
    });  
  });

  $("#analytics-tab").click(function(e){  
    var data = {
      'action': 'analytics'    
    };  
   
    $.post(ajax_object.ajax_url, data, function(response) { 
      response = JSON.parse(response);     
      var options = {
          animationEnabled: true,
          title: {
            text: "Affiliate gain"
          },
          axisY: {
            title: "Rate (in %)",
            suffix: "%",
            includeZero: false
          },
          axisX: {
            title: "Countries"
          },
          data: [{
            type: "column",
            yValueFormatString: "#,##0.0#"%"",
            dataPoints: response
          }]
        };
        $("#chartorder").CanvasJSChart(options);
    });  
  });

  $(".copy_my_url").click(function(e){
      e.preventDefault();  
        var url = $(this).children('.url_value');
        url.select();
        document.execCommand("copy");
  });

});
