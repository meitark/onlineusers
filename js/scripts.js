import { common } from "./common.js";
(async function () {
    switch (common.getPage()) {
        case 'index.html':
                let {login} = await import("./login.js");
                login.initEventHandlers();
            break;
        case 'register.html':
                let {registration} = await import("./registration.js");
                registration.initEventHandlers();
            break;
        case 'dashboard.html':
                let {dashboard} = await import("./dashboard.js");
                dashboard.initEventHandlers();
            break;            
    
        default:
            break;
    }    
}());
