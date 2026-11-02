$(function() {

    $("#first").rlAccordion();

    $("#second").rlAccordion('single', {
        childNumOptions: false
    });
	
	$("#thirth").rlAccordion('mix', {
        childNumOptions: false
    });
	
    //$("#thirth").rlAccordion('mix', {
     //   childNum: 2
    //});

});
