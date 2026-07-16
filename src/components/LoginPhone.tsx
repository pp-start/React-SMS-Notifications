import React, { useState, useEffect, useCallback } from "react";
import { useUserContext } from "./Context";
import FullScreenLogo from "../images/full_screen_logo.png";

export default function Login() {
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
  }

  const showModal = useCallback((type: ModalType, message: string): void => {
    setModalMessage(message);

    setModal((prev) => ({ ...prev, show: true, [type]: true }));
  }, []);

  useEffect(() => {
    if (modal.show) {
      const element = document.getElementById("modal");

      if (element) {
        element.scrollIntoView({ behavior: "smooth", block: "center" });
      }
    }
  }, [modal.show]);

  const { sendOTP, verifyOTPCode } = useUserContext();

  const [intro, setIntro] = useState<boolean>(true);

  // Limiting height of logo in intro - bug in chrome

  const [maxHeight, setMaxHeight] = useState<number>(0);

  // Changing height of page while keyboard is active on mobile phone

  const [visualViewportHeight, setVisualViewportHeight] = useState<number>(0);

  // Screen type

  const [screen, setScreen] = useState<number>(1);

  // Waiting for an option to send another SMS

  const [countdown, setCountdown] = useState<number>(0);

  useEffect(() => {
    if (countdown > 0) {
      setTimeout(() => {
        setCountdown((prev) => prev - 1);
      }, 1000);
    }
  }, [countdown]);

  useEffect(() => {
    // Intro

    setTimeout(() => {
      setIntro(false);
    }, 1200);

    // Height of logo and screen

    setMaxHeight(window.innerHeight - 50);

    const visualViewport: VisualViewport | null = window.visualViewport;

    if (visualViewport) {
      setVisualViewportHeight(visualViewport.height);

      visualViewport.addEventListener("resize", () => {
        setVisualViewportHeight(visualViewport.height);
      });
    }

    // Getting or creating verification code

    const verificationCode: string | null =
      sessionStorage.getItem("verificationCode");

    handleCode(verificationCode);

    function handleCode(verificationCode: string | null): void {
      if (!verificationCode) {
        const letters: string = "ABCDEFGHIJKLMNOPRSTUWXYZ";

        const number: string = "0123456789";

        let code: string = "";

        for (let i = 1; i <= 6; i++) {
          if (i <= 3) {
            const random: number = Math.floor(Math.random() * (23 - 0) + 0);
            code = code + letters.charAt(random);
          } else {
            const random: number = Math.floor(Math.random() * (9 - 0) + 0);
            code = code + number.charAt(random);
          }
        }

        saveCode(code);
      } else {
        saveCode(verificationCode);
      }
    }

    function saveCode(verificationCode: string): void {
      setPhoneFormFields((prev) => {
        return {
          ...prev,
          verificationCode: verificationCode,
        };
      });

      sessionStorage.setItem("verificationCode", verificationCode);
    }
  }, []);

  // Login form

  const [phoneFormFields, setPhoneFormFields] = useState<PhoneFormFields>({
    phoneNumber: "",
    policyAcceptation: false,
    oneTimePassword: "",
    verificationCode: "",
  });

  function formChange(event: React.ChangeEvent<HTMLInputElement>): void {
    const { name, value } = event.target;

    if (event.target.type !== "checkbox") {
      if ((/^\d*$/.test(value) && value.length <= 9) || value === "") {
        setPhoneFormFields((prev) => {
          return {
            ...prev,
            [name]: value,
          };
        });
      }
    } else {
      setPhoneFormFields((prev) => {
        return {
          ...prev,
          [name]: event.target.checked,
        };
      });
    }
  }

  // Disable scrolling while form is active

  let preventScroll: ((event: Event) => void) | null = null;

  function handleFocus(): void {
    preventScroll = (event: Event) => {
      (event as TouchEvent).preventDefault();
    };

    document.addEventListener("touchmove", preventScroll, { passive: false });
  }

  function handleBlur(): void {
    const element: HTMLElement | null = document.getElementById(
      "app-outer-container",
    );

    if (!element) return;

    setTimeout(() => (element.style.minHeight = "100dvh"), 100);

    if (preventScroll) {
      document.removeEventListener("touchmove", preventScroll);
      preventScroll = null;
    }
  }

  const handleKeyDown = (
    event: React.KeyboardEvent<HTMLInputElement>,
  ): void => {
    if (event.key === "Enter") {
      verifyPhone(false);
    }
  };

  // Phone number verification - SMS

  const verifyPhone = async (resend: boolean): Promise<void> => {
    if (countdown > 0) {
      return;
    }

    const button: HTMLElement | null = document.getElementById(
      "phone-login-button-1",
    );

    if (button) {
      button.setAttribute("disabled", "disabled");
    }

    const phoneNumber: string = phoneFormFields.phoneNumber;
    const policyAcceptation: boolean = phoneFormFields.policyAcceptation;
    let error: string = "";

    if (!phoneNumber) {
      error += "Enter phone number.\n";
    } else if (!/^\d{9}$/.test(phoneNumber)) {
      error += "Enter valid phone number(only 9 digits).\n";
    }

    if (!policyAcceptation) {
      error += "You need to accept data processing policy.";
    }

    if (error) {
      showModal("error", error);

      if (button) {
        button.removeAttribute("disabled");
      }

      return;
    }

    const data: UserData = await sendOTP(phoneFormFields, resend);

    if (data.success) {
      setScreen(2);
      setCountdown(120);
    }

    if (data.message) {
      showModal("error", data.message);

      // Counting down to a time when sending new code will be possible

      if (data.time) {
        const prevTime: Date = new Date(data.time);
        const currentTime: Date = new Date();
        const difference: number =
          120 - Math.floor((currentTime.getTime() - prevTime.getTime()) / 1000);

        if (difference > 0 && difference < 120) {
          setCountdown(difference);
        }
      }
    }

    if (button) {
      button.removeAttribute("disabled");
    }
  };

  const handleOTPKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
    if (event.key === "Enter") {
      verifyOTP();
    }
  };

  // Weryfikowanie kodu OTP

  const verifyOTP = useCallback(async (): Promise<void> => {
    const button: HTMLElement | null = document.getElementById(
      "phone-login-button-2",
    );

    if (button) {
      button.setAttribute("disabled", "disabled");
    }

    const oneTimePassword: string = phoneFormFields.oneTimePassword;
    let error;

    if (!oneTimePassword) {
      error = "Please enter one time password.";
    } else if (!/^\d{6}$/.test(oneTimePassword)) {
      error = "Invalid password format(should be 6 digits).";
    }

    if (error) {
      showModal("error", error);

      if (button) {
        button.removeAttribute("disabled");
      }

      return;
    }

    const data: UserData = await verifyOTPCode(phoneFormFields);

    if (data.return) {
      setScreen(1);

      setPhoneFormFields((prev) => {
        return {
          ...prev,
          oneTimePassword: "",
        };
      });
    }

    if (data.message) {
      showModal("error", data.message);
    }

    if (button) {
      button.removeAttribute("disabled");
    }
  }, [phoneFormFields, showModal, verifyOTPCode]);

  // Automatic login after OTP input

  useEffect(() => {
    if (phoneFormFields.oneTimePassword.length === 6) {
      verifyOTP();
    }
  }, [phoneFormFields.oneTimePassword, verifyOTP]);

  // Automatic OTP retrieval from SMS

  useEffect(() => {
    if ("OTPCredential" in window) {
      const ac = new AbortController();

      navigator.credentials
        .get({
          otp: { transport: ["sms"] },
          signal: ac.signal,
        })
        .then((otp) => {
          if (otp) {
            const { code } = otp as OTPCredential;

            setPhoneFormFields((prev) => ({
              ...prev,
              oneTimePassword: code,
            }));
          }
        })
        .catch((err) => console.log(err));

      return () => ac.abort();
    }
  }, []);

  return (
    <div
      id="app-outer-container"
      style={{
        minHeight: visualViewportHeight ? visualViewportHeight + "px" : "100vh",
      }}
    >
      {intro && (
        <div id="intro-outer-container">
          <img
            id="intro-logo"
            src={FullScreenLogo}
            alt="logo"
            style={{ height: maxHeight + "px" }}
          ></img>
        </div>
      )}
      <div id="login-inner-container">
        {!intro && (
          <div id="phone-login-inner-container">
            {screen === 1 && (
              <div id="phone-login-wrapper-1" className="phone-login-wrapper">
                <p className="phone-login-title">
                  Please enter your phone number:
                </p>
                <input
                  className="phone-login-form"
                  id="phone_number"
                  type="text"
                  autoComplete={"off"}
                  onChange={formChange}
                  onFocus={handleFocus}
                  onBlur={handleBlur}
                  onKeyDown={handleKeyDown}
                  inputMode="numeric"
                  maxLength={9}
                  name="phoneNumber"
                  value={phoneFormFields.phoneNumber}
                  placeholder="phone number"
                />
                <p className="phone-login-button-wrapper">
                  <button
                    className={
                      countdown === 0
                        ? "phone-login-button"
                        : "phone-login-button phone-login-button-disabled"
                    }
                    id="phone-login-button-1"
                    onClick={() => verifyPhone(false)}
                  >
                    <span>Next</span>
                  </button>
                </p>
                {countdown > 0 && (
                  <p className="phone-login-countdown">
                    Due to security reasons another
                  </p>
                )}
                {countdown > 0 && (
                  <p className="phone-login-countdown">
                    log-in will be available in {countdown} sec.
                  </p>
                )}
                <div id="phone-login-policy-container">
                  <div id="phone-login-policy-left-wrapper">
                    <input
                      type="checkbox"
                      onChange={formChange}
                      id="policy_acceptation"
                      name="policyAcceptation"
                      checked={phoneFormFields.policyAcceptation}
                    />
                  </div>
                  <div id="phone-login-policy-right-wrapper">
                    <p className="phone-login-text">
                      I accept data processing in
                    </p>
                    <p className="phone-login-text">
                      accordance with{" "}
                      <span id="phone-login-link">privacy policy</span>
                    </p>
                  </div>
                </div>
              </div>
            )}
            {screen === 2 && (
              <div
                className={
                  countdown > 0
                    ? "phone-login-wrapper"
                    : "phone-login-wrapper phone-login-wrapper-single-extended"
                }
              >
                <p className="phone-login-title">
                  Please enter one time password sent to{" "}
                  {phoneFormFields.phoneNumber}:
                </p>
                <input
                  className="phone-login-form"
                  id="one_time_password"
                  type="text"
                  autoComplete={"off"}
                  onChange={formChange}
                  onKeyDown={handleOTPKeyDown}
                  inputMode="numeric"
                  maxLength={6}
                  name="oneTimePassword"
                  value={phoneFormFields.oneTimePassword}
                  placeholder="enter password"
                />
                <p className="phone-login-button-wrapper">
                  <button
                    className="phone-login-button"
                    id="phone-login-button-2"
                    onClick={verifyOTP}
                  >
                    <span>Sign in</span>
                  </button>
                </p>
                <p className="phone-login-text">
                  Didn't receive SMS?{" "}
                  <span
                    id="phone-login-link"
                    className={
                      countdown > 0 ? "phone-login-link-not-active" : ""
                    }
                    onClick={() => verifyPhone(true)}
                  >
                    Send another
                  </span>
                </p>
                {countdown > 0 && (
                  <p className="phone-login-text">
                    (available in {countdown} sec.)
                  </p>
                )}
              </div>
            )}
          </div>
        )}
      </div>
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
              <h2 className="modal-title">{modal.info ? "Info" : "Error"}</h2>
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
              </div>
            </div>
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
          </div>
        </div>
      )}
    </div>
  );
}
