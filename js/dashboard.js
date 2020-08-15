(function () {
    let getUserData = async function (username) {
        let userModal = document.getElementById("userModal");
        let modalContent = document.getElementById("modalContent");
        modalContent.innerHTML = "";
        let resp = await fetch("/api/user/getLiveUser?username=" + getCookie("username") + "&requested_user=" + username, {
            method: 'GET',
            headers: {
                Bearer: getCookie("token")
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
        console.log(body);
    };
    let updateLiveUsersList = async function () {
        let resp = await fetch("/api/user/getlive?username=" + getCookie("username"), {
            method: 'GET',
            headers: {
                Bearer: getCookie("token")
            }
        });
        if (resp.status == 200) {
            let body = await resp.json();
            let divUsersList = document.getElementById("users-list");
            divUsersList.innerHTML = "";
            for (const [key, value] of Object.entries(body)) {
                console.log(`${key}: ${value}`);
                let divUserContainer = document.createElement("div");
                divUserContainer.classList.add("user");
                divUserContainer.addEventListener("click", ()=> getUserData(value.username));
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
        console.log(resp);
    }
    updateLiveUsersList();
    setInterval(async () => {
        updateLiveUsersList();
    }, 3000);


    var logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", async function () {
            await fetch("/api/user/logout");
            deleteCookie("token");
            deleteCookie("username");
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

}());