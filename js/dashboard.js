//dashboard.js module
import { common } from "./common.js";
export let dashboard = {
    getUserData: async function (username) {
        let userModal = document.getElementById("userModal");
        let modalContent = document.getElementById("modalContent");
        modalContent.innerHTML = "";
        let resp = await fetch("/api/user/getLiveUser?username=" + common.getCookie("username") + "&requested_user=" + username, {
            method: 'GET',
            headers: {
                Bearer: common.getCookie("token")
            }
        });
        let body = await resp.json();
        let divUsername = document.createElement("div");
        divUsername.innerText = "Username: " + body.username;
        modalContent.appendChild(divUsername);
        let divUA = document.createElement("div");
        divUA.innerText = "User Agent: " + body.ua;
        modalContent.appendChild(divUA);
        let divRegTime = document.createElement("div");
        divRegTime.innerText = "Registration Time: " + body.created_at;
        modalContent.appendChild(divRegTime);
        let divLogins = document.createElement("div");
        divLogins.innerText = "Logins Count: " + body.logins;
        modalContent.appendChild(divLogins);
        userModal.style.display = 'block';
    },

    updateLiveUsersList: async function () {
        let resp = await fetch("/api/user/getlive?username=" + common.getCookie("username"), {
            method: 'GET',
            headers: {
                Bearer: common.getCookie("token")
            }
        });
        if (resp.status == 200) {
            let body = await resp.json();
            let divUsersList = document.getElementById("users-list");
            divUsersList.innerHTML = "";
            for (const [key, value] of Object.entries(body)) {
                let divUserContainer = document.createElement("div");
                divUserContainer.classList.add("user");
                divUserContainer.addEventListener("click", () => this.getUserData(value.username));
                let divUsername = document.createElement("div");
                divUsername.innerText = value.username;
                let divLoginTime = document.createElement("div");
                divLoginTime.innerText = value.login_time;
                let divLastUpdated = document.createElement("div");
                divLastUpdated.innerText = value.updated_at;
                let divIP = document.createElement("div");
                divIP.innerText = value.ip;
                divUserContainer.appendChild(divUsername);
                divUserContainer.appendChild(divLoginTime);
                divUserContainer.appendChild(divLastUpdated);
                divUserContainer.appendChild(divIP);
                divUsersList.appendChild(divUserContainer);
            }
        }
    },

    initEventHandlers: function () {
        dashboard.updateLiveUsersList();
        setInterval(async () => {
            dashboard.updateLiveUsersList();
        }, 3000);


        var logoutBtn = document.getElementById("logoutBtn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", async function () {
                await fetch("/api/user/logout?username=" + common.getCookie("username"), {
                    method: 'POST',
                    headers: {
                        Bearer: common.getCookie("token")
                    }
                });
                common.deleteCookie("token");
                common.deleteCookie("username");
                window.location.href = "/index.html";
            });
        }

        var modalCloseBtn = document.getElementById("userModalClose");
        if (modalCloseBtn) {
            modalCloseBtn.addEventListener("click", function () {
                let userModal = document.getElementById("userModal");
                if (userModal) {
                    userModal.style.display = 'none';
                }
            });
        }
    }

};
