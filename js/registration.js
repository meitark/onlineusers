//registration.js module
export let registration = {
    initEventHandlers: function () {
        var registerBtn = document.getElementById("registerBtn");
        if (registerBtn) {
            registerBtn.addEventListener("click", function () {
                let registerFormData = new FormData(document.getElementById("formRegistration"));
                fetch("/api/user/register", {
                    method: 'POST',
                    body: registerFormData,
                }).then((resp) => {
                    if (resp.status == 200) {
                        alert("Registration Succesful, you'll now be redirected to login page");
                        window.location.href = "/index.html";
                    } else {
                        alert("There was an error registering the user, maybe it already exists?");
                    }
                });
            });
        }
    }
};

