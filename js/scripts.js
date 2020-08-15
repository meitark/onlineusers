//Common
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
  }

  function deleteCookie(cname) {
      setCookie(cname,"",-1);
  }
  
  function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
      var c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }

//Registration Page
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

//Login Page
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
            setCookie("token",body.token,1);
            setCookie("username",loginFormData.get("username"),1);
            window.location.href = "/dashboard.html";
        } else {
            alert("There was an error in login, check your username and password");
        }

    });
}

//Welcome Page