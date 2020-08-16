//login.js module
import { common } from "./common.js";
export let login = {
    initEventHandlers: function () {
        var loginBtn = document.getElementById("loginBtn");
        if (loginBtn) {
            loginBtn.addEventListener("click", async function () {
                let loginFormData = new FormData(document.getElementById("loginForm"));
                let resp = await fetch("/api/user/login", {
                    method: 'POST',
                    body: loginFormData,
                });
                if (resp.status == 200) {
                    let body = await resp.json();
                    common.setCookie("token", body.token, 1);
                    common.setCookie("username", loginFormData.get("username"), 1);
                    window.location.href = "/dashboard.html";
                } else {
                    alert("There was an error in login, check your username and password");
                }

            });
        }

    }
};
