import React, { useState, useEffect, useCallback } from "react";
import { useUserContext, Axios } from "./Context";
import { useLocation } from "react-router-dom";
import FullScreenLogo from "../images/full_screen_logo.png";

export default function Login() {
  const { sendData } = useUserContext();

  const [intro, setIntro] = useState<boolean>(true);

  const [maxHeight, setMaxHeight] = useState<number>(0);

  useEffect(() => {
    setMaxHeight(window.innerHeight - 50);

    setTimeout(() => {
      setIntro(false);
    }, 1200);
  }, []);

  const location = useLocation();

  const params: URLSearchParams = new URLSearchParams(location.search);

  const userId: string | null = params.get("user_id");

  const auth: string | null = params.get("auth");

  const authReg: string | null = params.get("auth_reg");

  const [screen, setScreen] = useState<number>(1);

  // Forms

  const [usernameCheck, setUsernameCheck] = useState<boolean>(true);

  const [formFields, setFormFields] = useState<FormFields>({
    usernameEmail: "",
    password: "",
    emailRemind: "",
    reset1: "",
    reset2: "",
    usernameRegister: "",
    emailRegister: "",
    passwordRegister1: "",
    passwordRegister2: "",
    policyAcceptation: false,
  });

  function formChange(event: React.ChangeEvent<HTMLInputElement>): void {
    const { name, value } = event.target;

    if (event.target.type === "checkbox") {
      setFormFields((prevFormFields) => {
        return {
          ...prevFormFields,
          [name]: event.target.checked,
        };
      });
    } else {
      setFormFields((prevFormFields) => {
        return {
          ...prevFormFields,
          [name]: value,
        };
      });
    }
  }

  function usernameChange(event: React.ChangeEvent<HTMLInputElement>): void {
    const { name, value } = event.target;

    setFormFields((prevFormFields) => {
      return {
        ...prevFormFields,
        [name]: value,
      };
    });

    if (value !== "") {
      const handler = "login";
      const requestType = "usernameCheck";
      const formFields: LoginData = {
        usernameCheck: value,
      };

      Axios.post("classes/handler.php", { formFields, handler, requestType }, { timeout: 500 })
        .then(function (response) {
          const checkResult: boolean =
            response.data.result === false ? false : true;
          setUsernameCheck(checkResult);
        })
        .catch((error) => {
          console.log(error);
          setUsernameCheck(true);
        });
    } else {
      setUsernameCheck(true);
    }
  }

  const handleKeyDown = (
    event: React.KeyboardEvent<HTMLInputElement>,
  ): void => {
    if (event.key === "Enter") {
      if (!auth) {
        if (screen === 1) {
          sendLogin();
        } else if (screen === 2) {
          initiatePasswordReset();
        } else {
          registerUser();
        }
      } else {
        resetPassword();
      }
    }
  };

  // Logging in

  const sendLogin = async (): Promise<void> => {
    if (!formFields.usernameEmail || !formFields.password) {
      showModal("error", "Please fill both fields!");
    } else {
      const button: HTMLElement | null =
        document.getElementById("mail-login-button");

      if (button) {
        button.setAttribute("disabled", "disabled");
      }

      const requestType = "mailLogin";
      const loginData: LoginData = {
        ...formFields,
      };

      const data: LoginResponse = await sendData(loginData, requestType);

      if (!data.token) {
        const message: string = data.message
          ? data.message
          : "Unknown error has occured. Please try again later.";

        showModal("error", message);

        if (button) {
          button.removeAttribute("disabled");
        }

        if (data.resend) {
          setResendActivation(true);
        }
      }
    }
  };

  // Initiate password reset

  const initiatePasswordReset = async (): Promise<void> => {
    const email: string = formFields.emailRemind;
    const mailRegex: RegExp = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    let error: string = "";

    if (!email) {
      error = "Please enter email address that is registered in the app.";
    } else if (!email.match(mailRegex)) {
      error = "Invalid email address entered.";
    }

    if (error) {
      showModal("error", error);
      return;
    }

    const button: HTMLElement | null =
      document.getElementById("mail-remind-button");

    if (button) {
      button.setAttribute("disabled", "disabled");
    }

    const requestType = "initiatePasswordReset";

    const data: LoginResponse = await sendData(formFields, requestType);
    const message: string = data.message!;
    const type: ModalType = data.success ? "info" : "error";

    if (data.resend) {
      setResendActivation(true);
    }

    showModal(type, message);

    if (button) {
      button.removeAttribute("disabled");
    }
  };

  // Ustawianie nowego hasła

  const [redirect, setRedirect] = useState<boolean>(false);

  async function resetPassword(): Promise<void> {
    const newPassword: string = formFields.reset1;
    const newPasswordRepeat: string = formFields.reset2;
    let error: string = "";
    const regex: RegExp = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

    if (!newPassword || !newPasswordRepeat) {
      error = "Please fill both fields.";
    } else if (newPassword !== newPasswordRepeat) {
      error = "Passwords do not match";
    } else if (!regex.test(newPassword)) {
      error =
        "Password must have at least 8 characters, one capital letter, a number and special character.";
    }

    if (error) {
      showModal("error", error);
      return;
    }

    const button: HTMLElement | null =
      document.getElementById("mail-reset-button");

    if (button) {
      button.setAttribute("disabled", "disabled");
    }

    const requestType = "resetPassword";
    const loginData: LoginData = {
      ...formFields,
      userId: userId!,
      auth: auth!,
      
    };

    const data: LoginResponse = await sendData(loginData, requestType);
    const message: string = data.message!;
    const type: ModalType = data.success ? "info" : "error";

    showModal(type, message);

    if (button) {
      button.removeAttribute("disabled");
    }

    if (data.redirect) {
      setRedirect(true);
    }
  }

  // Registration

  async function registerUser(): Promise<void> {
    const mailRegex: RegExp = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    const passwordRegex: RegExp = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;
    const username: string = formFields.usernameRegister;
    const email: string = formFields.emailRegister;
    const password1: string = formFields.passwordRegister1;
    const password2: string = formFields.passwordRegister2;
    const policyAcceptation: boolean = formFields.policyAcceptation;
    let error: string = "";

    if (!username) {
      error = "Username can not be empty.\n";
    }

    if (!email) {
      error += "Enter your email address.\n";
    } else if (!email.match(mailRegex)) {
      error += "Invalid email address entered.\n";
    }

    if (!password1 || !password2) {
      error += "Password is missing.\n";
    } else if (password1 !== password2) {
      error += "Passwords do not match.\n";
    } else if (!passwordRegex.test(password1)) {
      error +=
        "Password must have at least 8 characters, one capital letter, a number and special character.\n";
    }

    if (!policyAcceptation) {
      error += "You need to accept data processing.";
    }

    if (error) {
      showModal("error", error);
      return;
    }

    const button: HTMLElement | null = document.getElementById(
      "mail-register-button",
    );

    if (button) {
      button.setAttribute("disabled", "disabled");
    }

    const requestType = "registerUser";
    const loginData: LoginData = {
      ...formFields,
    };

    const data: LoginResponse = await sendData(loginData, requestType);
    const message: string = data.message!;
    const type: ModalType = data.success ? "info" : "error";

    if (data.success) {
      setRedirect(true);
    }

    showModal(type, message);

    if (button) {
      button.removeAttribute("disabled");
    }
  }

  // Modal

  const [modalMessage, setModalMessage] = useState<string | null>(null);

  const [modal, setModal] = useState<Modal>({
    show: false,
    info: false,
    error: false,
  });

  function closeModal() {
    setModalMessage(null);

    const closed = Object.fromEntries(
      Object.keys(modal).map((key) => [key, false]),
    ) as Modal;

    setModal(closed);

    if (redirect) {
      window.history.replaceState(null, "", window.location.pathname);
      window.location.reload();
    }
  }

  const showModal = useCallback((type: ModalType, message: string): void => {
    setModalMessage(message);
    setModal((prev) => ({ ...prev, show: true, [type]: true }));
  }, []);

  useEffect(() => {
    if (modal.show) {
      const element: HTMLElement | null = document.getElementById("modal");

      if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  }, [modal.show]);

  // Registration

  const verifyRegisteredUser = useCallback(
    async (loginData: LoginData) => {
      const requestType = "verifyUser";
      const data: LoginResponse = await sendData(loginData, requestType);
      const message: string = data.message!;
      const type: ModalType = data.success ? "info" : "error";

      showModal(type, message);

      if (data.resend) {
        setResendActivation(true);
      }

      setRedirect(true);
    },
    [showModal, sendData],
  );

  useEffect(() => {
    if (userId && authReg) {
      const loginData: LoginData = {
        userId: userId,
        authReg: authReg
      };

      verifyRegisteredUser(loginData);
    }
  }, [userId, authReg, verifyRegisteredUser]);

  // Resending of activation link

  const [resendActivation, setResendActivation] = useState<boolean>(false);

  const resendActivationMail = async (): Promise<void> => {
    const reactivationData: ReactivationData = {};

    if (userId) {
      reactivationData.userId = userId;
    } else {
      if (screen === 1) {
        reactivationData.usernameEmail = formFields.usernameEmail;
      }

      if (screen === 2) {
        reactivationData.usernameEmail = formFields.emailRemind;
      }
    }

    const requestType = "resendActivation";
    const loginData: LoginData = {
      reactivationData: reactivationData
    };

    const data: LoginResponse = await sendData(loginData, requestType);
    const message: string = data.message!;
    const type: ModalType = data.success ? "info" : "error";

    showModal(type, message);

    if (data.success) {
      setResendActivation(false);

      if (userId) setRedirect(true);
    }
  };

  return (
    <div id="app-outer-container">
      {intro && !authReg && (
        <div id="intro-outer-container">
          <img
            id="intro-logo"
            src={FullScreenLogo}
            alt="logo"
            style={{ height: maxHeight + "px" }}
          ></img>
        </div>
      )}
      {!intro && (
        <div id="login-inner-container">
          <div id="login-inner-1">
            {!userId && !auth && (
              <div className="login-inner-2">
                {screen === 1 && (
                  <div id="login">
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="username_email"
                        type="text"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="usernameEmail"
                        value={formFields.usernameEmail}
                        placeholder="username or email"
                      />
                    </div>
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="password"
                        type="password"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="password"
                        value={formFields.password}
                        placeholder="password"
                      />
                    </div>
                    <p className="mail-login-button-wrapper">
                      <button
                        id="mail-login-button"
                        className="mail-login-button"
                        onClick={sendLogin}
                      >
                        <span className="mail-login-button-text">Sign in</span>
                      </button>
                    </p>
                    <div className="login-screen-change-container">
                      <p className="login-screen-change-text">
                        Not registered?
                        <span
                          className="login-screen-change-button"
                          onClick={() => setScreen(3)}
                        >
                          create account
                        </span>
                      </p>
                      <span className="login-screen-change-separator">or</span>
                      <p className="login-screen-change-text">
                        Forgot password?
                        <span
                          className="login-screen-change-button"
                          onClick={() => setScreen(2)}
                        >
                          restore access
                        </span>
                      </p>
                    </div>
                  </div>
                )}
                {screen === 2 && (
                  <div id="remind">
                    <div className="login-field">
                      <p className="mail-login-text">
                        Please enter email that is registered in the app:
                      </p>
                    </div>
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="email_remind"
                        type="text"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="emailRemind"
                        value={formFields.emailRemind}
                        placeholder="email"
                      />
                    </div>
                    <p className="mail-login-button-wrapper">
                      <button
                        id="mail-remind-button"
                        className="mail-login-button"
                        onClick={initiatePasswordReset}
                      >
                        <span className="mail-login-button-text">
                          Recover password
                        </span>
                      </button>
                    </p>
                    <p className="login-screen-change-text">
                      <span
                        className="login-screen-change-button"
                        onClick={() => setScreen(1)}
                      >
                        back to sign in
                      </span>
                    </p>
                  </div>
                )}
                {screen === 3 && (
                  <div id="register">
                    <div className="login-field">
                      <p className="mail-login-text">
                        Fill the form below to sign up:
                      </p>
                    </div>
                    <div className="login-field">
                      <input
                        className={
                          usernameCheck
                            ? "login-form"
                            : "login-form login-form-warning"
                        }
                        id="username_register"
                        type="text"
                        onChange={usernameChange}
                        onKeyDown={handleKeyDown}
                        name="usernameRegister"
                        value={formFields.usernameRegister}
                        placeholder="username"
                      />
                      {!usernameCheck && (
                        <p id="username-taken">Username taken</p>
                      )}
                    </div>
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="email_register"
                        type="text"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="emailRegister"
                        value={formFields.emailRegister}
                        placeholder="email"
                      />
                    </div>
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="password_register_1"
                        type="password"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="passwordRegister1"
                        value={formFields.passwordRegister1}
                        placeholder="password"
                      />
                    </div>
                    <div className="login-field">
                      <input
                        className="login-form"
                        id="password_register_2"
                        type="password"
                        onChange={formChange}
                        onKeyDown={handleKeyDown}
                        name="passwordRegister2"
                        value={formFields.passwordRegister2}
                        placeholder="repeat password"
                      />
                    </div>
                    <div className="login-field">
                      <div id="mail-login-policy-container">
                        <div>
                          <input
                            type="checkbox"
                            onChange={formChange}
                            id="policy_acceptation"
                            name="policyAcceptation"
                            checked={formFields.policyAcceptation}
                          />
                        </div>
                        <div>
                          <p className="policy-text">
                            I accept data processing in
                          </p>
                          <p className="policy-text">
                            accordance with{" "}
                            <span id="policy-link">privacy policy</span>
                          </p>
                        </div>
                      </div>
                    </div>
                    <p className="mail-login-button-wrapper">
                      <button
                        id="mail-register-button"
                        className="mail-login-button"
                        onClick={registerUser}
                      >
                        <span className="mail-login-button-text">Sign up</span>
                      </button>
                    </p>
                    <p className="login-screen-change-text">
                      already registered?
                      <span
                        className="login-screen-change-button"
                        onClick={() => setScreen(1)}
                      >
                        back to sign in
                      </span>
                    </p>
                  </div>
                )}
              </div>
            )}
            {userId && auth && (
              <div className="login-inner-2">
                <div id="reset">
                  <input type="text" style={{ display: "none" }} />
                  <input
                    type="text"
                    style={{ display: "none" }}
                    autoComplete="new-password"
                  />
                  <div className="login-field">
                    <input
                      className="login-form"
                      id="reset_1"
                      type="password"
                      autoComplete={"off"}
                      onChange={formChange}
                      onKeyDown={handleKeyDown}
                      name="reset1"
                      value={formFields.reset1}
                      placeholder="new password"
                    />
                  </div>
                  <div className="login-field">
                    <input
                      className="login-form"
                      id="reset_2"
                      type="password"
                      autoComplete={"off"}
                      onChange={formChange}
                      onKeyDown={handleKeyDown}
                      name="reset2"
                      value={formFields.reset2}
                      placeholder="repeat password"
                    />
                  </div>
                  <p className="mail-login-button-wrapper">
                    <button
                      id="mail-reset-button"
                      className="mail-login-button"
                      onClick={resetPassword}
                    >
                      <span className="mail-login-button-text">
                        Change password
                      </span>
                    </button>
                  </p>
                </div>
              </div>
            )}
            <div id="login-background">
              <span
                id="login-background-shape4"
                className="login-background-shape"
              ></span>
              <span
                id="login-background-shape3"
                className="login-background-shape"
              ></span>
              <span
                id="login-background-shape2"
                className="login-background-shape"
              ></span>
              <span
                id="login-background-shape1"
                className="login-background-shape"
              ></span>
            </div>
          </div>
        </div>
      )}
      {modal.show && (
        <div className="modal-overlay" onClick={closeModal}>
          <div
            id="modal"
            className="modal"
            onClick={(e) => e.stopPropagation()}
          >
            <div
              className={
                modal.info ? "modal-header" : "modal-header modal-header-error"
              }
            >
              <h2 className="modal-title">{modal.info ? "Notice" : "Error"}</h2>
            </div>
            <div className="modal-body">
              <div className="modal-wrapper">
                {modalMessage &&
                  modalMessage.split("\n").map((line, index) => (
                    <React.Fragment key={index}>
                      <p className="modal-text">{line}</p>
                      <br className="modal-break" />
                    </React.Fragment>
                  ))}
                {resendActivation && (
                  <button
                    className="resend-activation-link-button"
                    onClick={resendActivationMail}
                  >
                    Resend activation email
                  </button>
                )}
              </div>
            </div>
            {!resendActivation && (
              <div className="modal-footer">
                <div className="modal-single-button-wrapper">
                  <button
                    className={
                      modal.info
                        ? "modal-single-button"
                        : "modal-single-button modal-single-error-button"
                    }
                    onClick={closeModal}
                  >
                    OK
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
