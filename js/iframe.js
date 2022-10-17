var isInIframe = inIframe();
if(isInIframe == true) {
    if (window.frameElement.getAttribute("stayrequest_y") == "0") {
        parent.location.href = redirect_hub(parent.location.href);
    } else {
        parent.location.href = addMessageLetter(parent.location.href, window.frameElement.getAttribute("message"));
    }
}
function inIframe(){
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

function addMessageLetter(url,letter){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }
    if(letter != ""){
        if(url.match(/(&message=)([A-Z]{1})/)){
            url = url.replace( /(&message=)([A-Z]{1})/, "&message="+letter );
        }else{
            if(url.includes("?")){
                url = url + "&message="+letter;
            }else{
                url = url + "?message="+letter;
            }
        }
    }else{
        if(url.match(/(&message=)([A-Z]{1})/)){
            url = url.replace( /(&message=)([A-Z]{1})/, "" );
        }
    }
    alert("url1: "+url);
    return url;
}
function redirect_hub(url){
    if (url.substring(url.length-1) == "#")
    {
        url = url.substring(0, url.length-1);
    }
    if(url.match(/(&record=)(\d+)/)){
        url = url.replace( /(&record=)(\d+)/, "" );
    }
    return url;
}