
// raw javascripts 

/**
 * Eevent for clicking download pdf 
 * @since 1.5.7.0 
 */
esig("#esig-pdf-font-download").addEventListener("click",function(){

          
    esigRemoteRequest("esig_pdf_font_download", "GET", function(data){

        
             var response  = JSON.parse(data.responseText);

             if(response.status == "success")
             {
                 esig("#esig-pdf-font-errorbox").classList.add("esig-updated"); 
                 esig("#esig-pdf-font-errorbox").innerHTML = response.errorMsg;
                 esig("#esig-pdf-font-download").remove();   
                 esig("#esig-export-pdf-different-language").removeAttribute("disabled");
                 esig("#esig-export-pdf-different-language").removeAttribute("readonly");
                
             }
             else  
             {
                 esig("#esig-pdf-font-errorbox").classList.add("error"); 
                 esig("#esig-pdf-font-errorbox").innerHTML = response.errorMsg;
                 esig("#esig-pdf-font-download").innerHTML = "Downloading PDF fonts";
             }
             
            //esig("#esig-pdf-font-download").innerHTML = this.responseText;
        });

    esig("#esig-pdf-font-download").innerHTML = "Downloading font please wait.."; 

});
