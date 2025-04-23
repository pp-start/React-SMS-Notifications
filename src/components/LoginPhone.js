import React, { useState, useContext, useEffect, useCallback, useRef } from "react";
import {UserContext} from './UserContext';
import FullScreenLogo from '../images/full_screen_logo.webp';

export default function Login(){

    const {sendOTP, verifyOTPCode} = useContext(UserContext);

    const [intro, setIntro] = useState(true);

    // Limit height of logo in intro - bug in chrome

    const [maxHeight, setMaxHeight] = useState(0); 

    // Height change with active virtual keyboard

    const [visualViewportHeight, setVisualViewportHeight] = useState(0);

    // Login screen

    const [screen, setScreen] = useState(1);

    // Modal

    const [modalMessage, setModalMessage] = useState(null);

    const [modal, setModal] = useState({
        show: false,
        info: false,
        error: false
    });

    function closeModal(){

        setModalMessage(null);

        Object.keys(modal).forEach(key => modal[key] = false);

    }

    const showModal = useCallback((type, message) => {

        setModalMessage(message);

        setModal(prevModal => ({...prevModal, show: true, [type]: true}));

    }, []);

    useEffect(() => {

        if(modal.show){

            const element = document.getElementById('modal');

            if(element){

                element.scrollIntoView({ behavior: "smooth", block: "center" });

            }

        }

    }, [modal.show]);

    // Limiting new login attempts

    const [countdown, setCountdown] = useState(0);

    useEffect(() => {

        if(countdown > 0){

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

        // Height of logo and viewport

        setMaxHeight(window.innerHeight - 50);

        if(window.visualViewport){

            setVisualViewportHeight(window.visualViewport.height);

            window.visualViewport.addEventListener('resize', () => {

                setVisualViewportHeight(window.visualViewport.height);

            });

        }

        // Getting or creating new verification code

        let verification_code = sessionStorage.getItem("verification_code");

        handleCode(verification_code);

        function handleCode(verification_code){

            if(!verification_code){

                const letters = 'ABCDEFGHIJKLMNOPRSTUWXYZ';

                const number = '0123456789';

                let code = '';

                for(let i = 1; i <= 6; i++){

                    if(i <= 3){

                        let random = Math.floor(Math.random() * (23 - 0) + 0);

                        code = code + letters.charAt(random);

                    } else {

                        let random = Math.floor(Math.random() * (9 - 0) + 0);

                        code = code + number.charAt(random);

                    }

                }

                saveCode(code);

            } else {

                saveCode(verification_code);

            }

        }

        function saveCode(verification_code){

            setForm(prevForm => {
                return {
                    ...prevForm,
                    verification_code: verification_code
                }
            });

            sessionStorage.setItem("verification_code", verification_code);

        }

    }, []);

    // Login form

    const [form, setForm] = useState(
        {
            phone_number: '',
            policy_acceptation: false,
            one_time_password: '',
            verification_code: ''
        }
    );

    const currentForm = useRef(null);

    useEffect(() => {

        currentForm.current = form;

    }, [form])

    function formChange(event){

        const {name, value} = event.target;

        if(event.target.type !== 'checkbox'){

            if((/^\d*$/.test(value) && value.length <= 9) || value === ""){

                setForm(prevForm => {
                    return {
                        ...prevForm,
                        [name]: value
                    }
                });

            }

        } else {

            setForm(prevForm => {
                return {
                    ...prevForm,
                    [name]: event.target.checked
                }
            });

        }

    }

    // Scroll lock when login form is filled

    let preventScroll;

    function handleFocus(){

        preventScroll = (event) => event.preventDefault();

        document.addEventListener("touchmove", preventScroll, { passive: false });

    }

    function handleBlur(){

        const element = document.getElementById('app-outer-container');

        setTimeout(() => element.style.minHeight = '100dvh', 100);

        if(preventScroll){

            document.removeEventListener("touchmove", preventScroll, { passive: false });

            preventScroll = null;

        }

    }

    const handleKeyDown = (event) => {

        if(event.key === "Enter"){

            verifyPhone();

        }

    }

    // Phone number verification - via SMS

    const verifyPhone = async() => {

        if(countdown > 0){

            return;

        }

        let button = document.getElementById('phone-login-button-1');

        if(button){

            button.setAttribute("disabled", "disabled");

        }

        let phone_number = form.phone_number;

        let policy_acceptation = form.policy_acceptation;

        let error = "";

        if(!phone_number){

            error += 'Enter phone number.\n';

        } else if(!(/^\d{9}$/.test(phone_number))){

            error += 'Enter valid phone number(only 9 digits).\n';

        }

        if(!policy_acceptation){

            error += 'You need to accept data processing and privacy policy.';

        }

        if(error){

            showModal('error', error);

        } else {

            let formData = { phone_number: phone_number, policy_acceptation: policy_acceptation, verification_code: form.verification_code, request_type: 'generate_OTP' };

            const data = await sendOTP(formData);

            if(data.success){

                setScreen(2);

                setCountdown(120);

            }

            if(data.message){

                showModal('error', data.message);

                // Locking login attemts and counting down to the release of lockdown 

                if(data.time){

                    const prev_time = new Date(data.time); 

                    const current_time = new Date();

                    const difference = 120 - Math.floor((current_time - prev_time) / 1000);

                    if(difference > 0 && difference < 120){

                        setCountdown(difference);

                    }

                }

            }

        }

        if(button){

            button.removeAttribute("disabled");

        }

    }

    const handleOTPKeyDown = (event) => {

        if(event.key === "Enter"){

            verifyOTP();

        }

    }

    // OTP verification

    const verifyOTP = useCallback(async () => {

        const formData = currentForm.current;

        formData.request_type = "verify_OTP";
    
        let button = document.getElementById('phone-login-button-2');

        if(button){

            button.setAttribute("disabled", "disabled");

        }

        let one_time_password = formData.one_time_password;

        let error;

        if(!one_time_password){

            error = 'Please enter one time password';

        } else if(!(/^\d{6}$/.test(one_time_password))){

            error = 'Incorrect password format - should be only 6 digits.';

        }

        if(error){

            showModal('error', error);

            if(button){

                button.removeAttribute("disabled");

            }

            return;

        }

        const data = await verifyOTPCode(formData);

        if(data.return){

            setScreen(1);

            setForm(prevForm => {
                return {
                    ...prevForm,
                    one_time_password: ''
                }
            });

        }

        if(data.message){

            showModal('error', data.message);

        }

        if(button){

            button.removeAttribute("disabled");

        }


    }, [verifyOTPCode, showModal]);

    // Automatic login when OTP is entered

    useEffect(() => {

        if(form.one_time_password.length === 6){

            verifyOTP();

        }

    }, [form.one_time_password, verifyOTP]);

    // Automatic retrieval of OTP from SMS

    useEffect(() => {

        if ("OTPCredential" in window) {

            const ac = new AbortController();

            navigator.credentials.get({
                otp: { transport: ["sms"] },
                signal: ac.signal,
            }).then((otp) => {

                setForm(prevForm => ({
                    ...prevForm,
                    one_time_password: otp.code
                }));

            }).catch((err) => console.log(err));

            return () => ac.abort(); 

        }

    }, []);

    return (
        <div id="app-outer-container" style={{minHeight: visualViewportHeight ? visualViewportHeight+"px" : "100vh"}}>
            {intro && 
                <div id="intro-outer-container">
                    <img id="intro-logo" src={FullScreenLogo} alt="logo" style={{height: maxHeight+"px"}}></img>
                </div>
            }
            <div id="login-inner-container">
                {!intro && 
                    <div id="phone-login-inner-container">
                        {screen === 1 && 
                            <div id="phone-login-wrapper-1" className="phone-login-wrapper">
                                <p className="phone-login-title">To sign in please enter your phone number:</p>
                                <input 
                                    className="phone-login-form"
                                    id="phone_number"
                                    type="text"
                                    autoComplete={'off'}
                                    onChange={formChange}
                                    onFocus={handleFocus}
                                    onBlur={handleBlur}
                                    onKeyDown={handleKeyDown}
                                    inputMode="numeric"
                                    maxLength="9"
                                    name="phone_number"
                                    value={form.phone_number}
                                    placeholder="Your phone number"
                                />
                                <div id="phone-login-policy-container">
                                    <div id="phone-login-policy-left-wrapper">
                                        <input 
                                            type ="checkbox" 
                                            onChange={formChange}
                                            id="policy_acceptation"
                                            name="policy_acceptation"
                                            checked={form.policy_acceptation}
                                        />
                                    </div>
                                    <div id="phone-login-policy-right-wrapper">
                                        <p className="phone-login-text">I accept data processing in accordance with <span id="phone-login-link">privacy policy</span></p>
                                    </div>
                                </div>
                                <p className="phone-login-button-wrapper">
                                    <button className={countdown === 0 ? "phone-login-button" : "phone-login-button phone-login-button-disabled"} id="phone-login-button-1" onClick={verifyPhone}><span>Next</span></button>
                                </p>
                                {countdown > 0 && <p className="phone-login-countdown">For security reasons next sign in</p>}
                                {countdown > 0 && <p className="phone-login-countdown">will be possible in {countdown} sec.</p>}
                            </div>
                        }
                        {screen === 2 && 
                            <div className={countdown > 0 ? "phone-login-wrapper" : "phone-login-wrapper phone-login-wrapper-single-extended"}>
                                <p className="phone-login-title">Your one time password send via SMS to phone number {form.phone_number}:</p>
                                <input
                                    className="phone-login-form"
                                    id="one_time_password"
                                    type="text"
                                    autoComplete={'off'}
                                    onChange={formChange}
                                    onKeyDown={handleOTPKeyDown}
                                    inputMode="numeric"
                                    maxLength="6"
                                    name="one_time_password"
                                    value={form.one_time_password}
                                    placeholder="Enter password"
                                />
                                <p className="phone-login-button-wrapper">
                                    <button className="phone-login-button" id="phone-login-button-2" onClick={verifyOTP}><span>Sign in</span></button>
                                </p>
                                <p className="phone-login-text">SMS didn't arrive? <span id="phone-login-link" className={countdown > 0 ? "phone-login-link-not-active" : ""} onClick={verifyPhone}>Send again</span></p>
                                {countdown > 0 && <p className="phone-login-text">(available in {countdown} sec.)</p>}
                            </div>
                        }
                    </div>
                }
            </div>
            {modal.show &&
                <div className="modal-overlay" onClick={closeModal}>
                    <div id="modal" className="modal" onClick={(e)=>e.stopPropagation()}>
                        <div className={modal.info ? "modal-header" : "modal-header modal-header-error"}>
                            <h2 className="modal-title">{modal.info ? 'Notice' : 'Error'}</h2>
                        </div>
                        <div className="modal-body">
                            <div className="modal-wrapper">
                                {modalMessage.split("\n").map((line, index) => (
                                    <React.Fragment key={index}>
                                        <p className="modal-text">{line}</p>
                                        <br className="modal-break"/>
                                    </React.Fragment>
                                ))}
                            </div>
                        </div>
                        <div className="modal-footer">
                            <div className="modal-single-button-wrapper"> 
                                <button className={modal.info ? "modal-single-button" : "modal-single-button modal-single-error-button"} onClick={closeModal}>OK</button>
                            </div>
                        </div>
                    </div>
                </div>
            }
        </div>
    );
};