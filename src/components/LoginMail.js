import React, { useState, useContext, useEffect } from "react";
import {UserContext, Axios} from './UserContext';
import { useLocation } from "react-router-dom";
import FullScreenLogo from '../images/full_screen_logo.webp';

export default function Login(){

    const {loginUser} = useContext(UserContext);

    const [message, setMessage] = useState(null);

    const [intro, setIntro] = useState(false);

    const [currentScreen, setCurrentScreen] = useState(1);

    // Limit height of logo in intro - bug in chrome

    const [maxHeight, setMaxHeight] = useState(0);

    useEffect(() => {

        setMaxHeight(window.innerHeight -50);

        setTimeout(() => {

            setIntro(false);

        }, 1200);

    }, []);

    // Get query parameters

    const location = useLocation();

    const [params, setParams] = useState({user_id: null, auth: null, auth_reg: null});

    useEffect(() => {

        const params = new URLSearchParams(location.search);
        
        const user_id = params.get("user_id"); 

        const auth = params.get("auth");

        const auth_reg = params.get("auth_reg");
    
        setParams({user_id: user_id, auth: auth, auth_reg: auth_reg});

        if(!user_id){

            setIntro(true);

        }

    }, [location.search]);

    // Forms

    const [form, setForm] = useState(
        {
            username_email: '',
            password: '',
            email_remind: '',
            reset_1: '',
            reset_2: '',
            register_username: '',
            register_email: '',
            register_password: '',
            repeat_password: '',
            policy_acceptation: ''
        }
    );

    function clearForm(){

        setForm(
            {
                username_email: '',
                password: '',
                email_remind: '',
                reset_1: '',
                reset_2: '',
                register_username: '',
                register_email: '',
                register_password: '',
                repeat_password: '',
                policy_acceptation: ''
            }
        );

    }

    function formChange(event){

        const {name, value} = event.target;

        if(event.target.type !== 'checkbox'){

            setForm(prevForm => {
                return {
                    ...prevForm,
                    [name]: value
                }
            });

        } else {

            setForm(prevForm => {
                return {
                    ...prevForm,
                    [name]: event.target.checked
                }
            });

        }

    }

    // Handle enter key press on forms

    const handleLoginKeyDown = (event) => {

        if(event.key === "Enter"){

            sendLogin();

        }

    }

    const handleRemindKeyDown = (event) => {

        if(event.key === "Enter"){

            initiatePasswordReset();

        }

    }

    const handleRegisterKeyDown = (event) => {

        if(event.key === "Enter"){

            registerUser();

        }

    }

    const handleResetKeyDown = (event) => {

        if(event.key === "Enter"){

            resetPassword();

        }

    }

    // Checking login credentials

    const sendLogin = async() => {

        if(!form.username_email || !form.password){

            showModal('error', 'Please fill both fields!');

        } else {

            let button = document.getElementById('login-button');

            if(button){

                button.setAttribute("disabled", "disabled");

            }

            const formData = { username_email: form.username_email, password: form.password, request_type: 'mail_login' };

            const data = await loginUser(formData);

            if(data.token){

                setMessage(data.message);

            } else {

                let message = data.message ? data.message : 'Data processing error. Please try again later.';

                showModal('error', message);

            }

            if(button){

                button.removeAttribute("disabled");

            }

        }

    }

    // Processing password reset request

    function initiatePasswordReset(){

        let button = document.getElementById('recover-password-button');

        if(button){

            button.setAttribute("disabled", "disabled");

        }

        let email = form.email_remind;

        let mailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        let error;

        if(!email){

            error = 'Enter registered email address.';

        } else if(!email.match(mailRegex)){

            error = 'Invalid email address.';

        }

        if(error){

            showModal('error', error);

        } else {

            let formData = { email_remind: email, request_type: "initiate_password_reset" };

            Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){

                if(response.data.message){

                    let message = response.data.message;

                    let type = response.data.success ? 'info' : 'error';

                    showModal(type, message);

                    if(response.data.resend){

                        setResendActivation(true);

                    }

                } else {

                    showModal('error', 'Data processing error. Please try again later.');

                }

            }).catch((error) => {

                console.warn(error);

                showModal('error', 'Data processing error. Please try again later.');

            });

        }

        if(button){

            button.removeAttribute("disabled");

        }

    }

    // Setting new password

    function resetPassword(){

        let button = document.getElementById('reset-password-button');

        if(button){

            button.setAttribute("disabled", "disabled");

        }

        const new_password = form.reset_1;

        const new_password_repeat = form.reset_2;

        let error;

        const regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

        if(!new_password || !new_password_repeat){

            error = 'Fill both form fields!';

        } else if(new_password !== new_password_repeat){

            error = 'Passwords are not identical!';

        } else if(!regex.test(new_password)){

            error = 'Password must have at least 8 characters, one capital letter, one digit and a special character.';

        }

        if(error){

            showModal('error', error);

        } else {

            let formData = { user_id: params.user_id, auth: params.auth, new_password: new_password, request_type: 'reset_password' };

            Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){

                if(response.data.message){

                    let message = response.data.message;

                    let type = response.data.success ? 'info' : 'error';

                    showModal(type, message);

                    if(response.data.success || response.data.redirect){

                        window.history.replaceState({}, document.title, window.location.pathname);

                        setParams({user_id: null, auth: null, auth_reg: null});

                    }

                } else {

                    showModal('error', 'Data processing error. Please try again later.');
                    
                }

            }).catch((error) => {

                console.warn(error);

                showModal('error', 'Data processing error. Please try again later.');

            });

        }

    }

    // Register

    // Checking if username is not taken

    const [usernameCheckMessage, setUsernameCheckMessage] = useState(null);

    useEffect(() => {

        if(form.register_username !== ""){

            checkUsername(form.register_username);

        } else {

            setUsernameCheckMessage(null);

        }

        function checkUsername(username_check){

            let formData = { username_check: username_check, request_type: 'username_check' };
    
            Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){
    
                if(response.data.hasOwnProperty('result')){
    
                    let result = response.data.result;

                    if(result){

                        setUsernameCheckMessage(null);

                    } else {

                        setUsernameCheckMessage("Username exists!");

                    }
    
                } else {

                    setUsernameCheckMessage(null);
    
                    console.log('Unknown error while checking username'); 
                    
                }
    
            }).catch((error) => {

                setUsernameCheckMessage(null);
    
                console.warn(error);
    
            });
    
        }

    }, [form.register_username]);

    // Registering new user

    async function registerUser(){

        let button = document.getElementById('create-account-button');

        if(button){

            button.setAttribute("disabled", "disabled");

        }

        let username = form.register_username;

        let email = form.register_email;

        let password = form.register_password;

        let repeat_password = form.repeat_password;

        let policy_acceptation = form.policy_acceptation;

        let error = "";

        const password_regex = /^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

        const mail_regex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;

        if(!username){

            error = 'Enter your username\n';

        } else if(username.length < 4){

            error = 'Username must have at least 4 characters.\n';

        }

        if(!email){

            error += 'Enter your email address.\n';

        } else if(!email.match(mail_regex)){

            error += 'Enter valid email address.\n';

        }

        if(!password && !repeat_password){

            error += 'Passwords can not be empty.\n';

        } else if(password !== repeat_password){

            error += 'Passwords are not identical!\n';

        } else if(!password_regex.test(password)){

            error += 'Password must have at least 8 characters, one capital letter, one digit and a special character.\n';

        }

        if(!policy_acceptation){

            error += 'You need to accept data processing and privacy policy.';

        }

        if(error){

            showModal('error', error);

        } else {

            let formData = { register_username: username, register_email: email, register_password: password, register_policy_acceptation: policy_acceptation, request_type: 'register_user' };

            Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){

                if(response.data.message){

                    let message = response.data.message;

                    let type = response.data.success ? 'info' : 'error';

                    showModal(type, message);

                    if(response.data.success){

                        setCurrentScreen(1);

                        clearForm();

                    }

                } else {

                    showModal('error', 'Data processing error. Please try again later.');
                    
                }

            }).catch((error) => {

                console.warn(error);

                showModal('error', 'Data processing error. Please try again later.');

            });

        }

        if(button){

            button.removeAttribute("disabled");

        }

    }

    // Resend activation link

    const [resendActivation, setResendActivation] = useState(false);

    function resendActivationLink(email){

        closeModal();

        let param = email ? email : params.user_id;

        let formData = { resend_activation: param, request_type: 'resend_activation' };

        Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){

            if(response.data.message){

                let message = response.data.message;

                let type = response.data.success ? 'info' : 'error';

                showModal(type, message);

            } else {

                showModal('error', 'Data processing error. Please try again later.');
                
            }

        }).catch((error) => {

            console.warn(error);

            showModal('error', 'Data processing error. Please try again later.');

        });

    }

    // Activation via link

    useEffect(() => {

        if(params.auth_reg && params.user_id){

            let formData = { user_id: params.user_id, auth_reg: params.auth_reg, request_type: 'verify_user' };

            Axios.post('classes/login.php', { formData }, { timeout: 5000 }).then(function(response){

                if(response.data.message){
    
                    let message = response.data.message;

                    let type = response.data.success ? 'info' : 'error';

                    handleResult(type, message, response.data.resend);

                    if(response.data.resend){

                        setResendActivation(true);

                    }
    
                } else {

                    handleResult('error', 'Data processing error. Please try again later.', false);
                    
                }
    
            }).catch((error) => {
    
                console.warn(error);

                handleResult('error', 'Data processing error. Please try again later.', false);
    
            });

        }

        function handleResult(type, message, resend){

            if(!resend){

                window.history.replaceState({}, document.title, window.location.pathname);

                setParams({user_id: null, auth: null, auth_reg: null});
    
            }

            setModalMessage(message);

            setModal(prev => {
                return {
                    ...prev,
                    show: true,
                    [type]: true
                }
            });

        }

    }, [params.auth_reg, params.user_id]);

    // Modal

    const [modalMessage, setModalMessage] = useState(null);

    const [modal, setModal] = useState({
        show: false,
        info: false,
        error: false
    });

    function showModal(type, message){

        setModalMessage(message);

        setModal({...modal, show: true, [type]: true});

    }

    function closeModal(){

        setModalMessage(null);

        Object.keys(modal).forEach(key => modal[key] = false);

        setResendActivation(false);

    }

    useEffect(() => {

        if(modal.show){

            const element = document.getElementById('modal');

            if(element){

                element.scrollIntoView({ behavior: "smooth", block: "center" });

            }

        }

    }, [modal.show]);

    return (
        <div id="app-outer-container">
            {intro && 
                <div id="intro-outer-container">
                    <img id="intro-logo" src={FullScreenLogo} alt="logo" style={{height: maxHeight+"px"}}></img>
                </div>
            }
            {!intro && 
                <div id="login-inner-container">
                    <div id="login-inner-1">
                        {!params.user_id && 
                            <div className="login-inner-2">
                                {currentScreen === 1 && 
                                    <div id="login">
                                        <div className="mail-login-top-wrapper">
                                            <p className="mail-login-title">To sign in please enter your credentials:</p>
                                            <div className="login-field">
                                                <input 
                                                    className="login-form"
                                                    id="username_email"
                                                    type="text"
                                                    onChange={formChange}
                                                    onKeyDown={handleLoginKeyDown}
                                                    name="username_email"
                                                    value={form.username_email}
                                                    placeholder="Username or email"
                                                />
                                            </div>
                                            <div className="login-field">
                                                <input 
                                                    className="login-form"
                                                    id="password"
                                                    type="password"
                                                    onChange={formChange}
                                                    onKeyDown={handleLoginKeyDown}
                                                    name="password"
                                                    value={form.password}
                                                    placeholder="Password"
                                                />
                                            </div>
                                        </div>
                                        <p className="mail-login-button-wrapper">
                                            <button id="login-button" className="mail-login-button" onClick={sendLogin}><span className="mail-login-button-text">Sign in</span></button>	
                                            {message && <span className="login-message">{message}</span>}
                                        </p>
                                        <div className="login-screen-change-buttons-wrapper">
                                            <span className="login-screen-change-button" onClick={() => setCurrentScreen(2)}>Forgot password?</span>
                                            <span className="login-screen-change-separator">&#10023;</span>
                                            <span className="login-screen-change-button" onClick={() => setCurrentScreen(3)}>Register account</span>
                                        </div>
                                    </div>
                                }
                                {currentScreen === 2 && 
                                    <div id="remind">
                                        <div className="mail-login-top-wrapper">
                                            <p className="mail-login-title">Enter valid email address that is registered in the service:</p>
                                            <div className="login-field">
                                                <input 
                                                    className="login-form"
                                                    id="email_remind"
                                                    type="text"
                                                    onChange={formChange}
                                                    onKeyDown={handleRemindKeyDown}
                                                    name="email_remind"
                                                    value={form.email_remind}
                                                    placeholder="Email address"
                                                />
                                            </div>
                                        </div>  
                                        <p className="mail-login-button-wrapper">
                                            <button id="recover-password-button" className="mail-login-button" onClick={initiatePasswordReset}><span className="mail-login-button-text">Recover password</span></button>	
                                        </p>
                                        <div className="login-screen-change-buttons-wrapper">
                                            <span className="login-screen-change-button" onClick={() => setCurrentScreen(1)}>Back to sign in</span>
                                        </div>
                                    </div>
                                }
                                {currentScreen === 3 && 
                                    <div id="register">
                                        <div className="mail-login-top-wrapper">
                                            <p className="mail-login-title">Please enter required information:</p>
                                            <div id="register-username-wrapper" className="login-field login-field-double">
                                                <p id="register-username-error">{usernameCheckMessage}</p>
                                                <input 
                                                    className="login-form login-form-half"
                                                    id="register_username"
                                                    type="text"
                                                    onChange={formChange}
                                                    onKeyDown={handleRegisterKeyDown}
                                                    name="register_username"
                                                    value={form.register_username}
                                                    placeholder="Username"
                                                />
                                                <input 
                                                    className="login-form login-form-half"
                                                    id="register_email"
                                                    type="text"
                                                    onChange={formChange}
                                                    onKeyDown={handleRegisterKeyDown}
                                                    name="register_email"
                                                    value={form.register_email}
                                                    placeholder="Email"
                                                />
                                            </div>
                                            <div className="login-field login-field-double">
                                                <input 
                                                    className="login-form login-form-half"
                                                    id="register_password"
                                                    type="password"
                                                    onChange={formChange}
                                                    onKeyDown={handleRegisterKeyDown}
                                                    name="register_password"
                                                    value={form.register_password}
                                                    placeholder="Password"
                                                />
                                                <input 
                                                    className="login-form login-form-half"
                                                    id="repeat_password"
                                                    type="password"
                                                    onChange={formChange}
                                                    onKeyDown={handleRegisterKeyDown}
                                                    name="repeat_password"
                                                    value={form.repeat_password}
                                                    placeholder="Repeat password"
                                                />
                                            </div>
                                            <div id="mail-login-policy-wrapper">
                                                <input 
                                                    type ="checkbox" 
                                                    onChange={formChange}
                                                    id="policy_acceptation"
                                                    name="policy_acceptation"
                                                    checked={form.policy_acceptation}
                                                />
                                                <p className="mail-login-text">I accept data processing in accordance with <span id="mail-login-link">privacy policy</span></p>
                                            </div>
                                        </div>
                                        <p className="mail-login-button-wrapper">
                                            <button id="create-account-button" className="mail-login-button" onClick={registerUser}><span className="mail-login-button-text">Register</span></button>	
                                            {message && <span className="login-message">{message}</span>}
                                        </p>
                                        <div className="login-screen-change-buttons-wrapper">
                                            <span className="login-screen-change-button" onClick={() => setCurrentScreen(1)}>Already registered?</span>
                                        </div>
                                    </div>
                                }
                            </div>
                        }
                        {params.user_id && params.auth && 
                            <div className="login-inner-2">
                                <div id="reset">
                                    <input type="text" style={{ display: 'none' }} />
                                    <input type="text" style={{ display: 'none' }} autoComplete="new-password" />
                                    <div className="login-field">
                                        <input 
                                            className="login-form"
                                            id="reset_1"
                                            type="password"
                                            autoComplete={'off'}
                                            onChange={formChange}
                                            onKeyDown={handleResetKeyDown}
                                            name="reset_1"
                                            value={form.reset_1}
                                            placeholder="New password"
                                        />
                                    </div>
                                    <div className="login-field">
                                        <input 
                                            className="login-form"
                                            id="reset_2"
                                            type="password"
                                            autoComplete={'off'}
                                            onChange={formChange}
                                            onKeyDown={handleResetKeyDown}
                                            name="reset_2"
                                            value={form.reset_2}
                                            placeholder="Repeat password"
                                        />
                                    </div>
                                    <p className="mail-login-button-wrapper">
                                        <button id="reset-password-button" className="mail-login-button" onClick={resetPassword}><span className="mail-login-button-text">Change password</span></button>	
                                    </p>
                                </div>
                            </div>
                        }
                    </div>
                </div>
            }
            {modal.show &&
                <div className="modal-overlay" /*onClick={closeModal}*/>
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
                                {resendActivation && 
                                    <span id="resend-activation-button" className="resend-activation-link-button" onClick={() => resendActivationLink(form.email_remind)}>Resend activation link</span>
                                }
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