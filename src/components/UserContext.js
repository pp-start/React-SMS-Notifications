import {createContext, useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { db } from "./Db";

export const UserContext = createContext();

const isLocalhost = Boolean(

    window.location.hostname === 'localhost' || window.location.hostname === '[::1]' ||

    window.location.hostname.match(/^127(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/) ||

    window.location.hostname.match(/^192(?:\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}$/)

);

export const Axios = axios.create({

    baseURL: isLocalhost ? 'your_location/php/' : 'php/', // TO CHANGE - local path to match your location
    
});

export const UserContextProvider = ({children}) => {

    // Lock portrait display mode in case app runs fullscreen

    const [fullScreen, setFullScreen] = useState(false);

    useEffect(() => {

        const fullscreen = window.matchMedia('(display-mode: fullscreen)').matches || window.navigator.fullscreen;

        if(fullscreen){

            setFullScreen(true);

        }

    }, []);

    useEffect(() => {

        if(fullScreen){

            if(window.screen.orientation && window.screen.orientation.lock){

                window.screen.orientation.lock('portrait').catch(error => {

                    console.error('Failed to lock orientation:', error);

                });

            }

        }

    }, [fullScreen]);

    const [theUser, setUser] = useState(null);

    // User identification code

    useEffect(() => {

        let code = localStorage.getItem("code");

        if(!code){

            generateCode();

        }

        function generateCode(){

            const letters = 'ABCDEFGHIJKLMNOPRSTUWXYZ';

            const number = '0123456789';

            let code = '';

            for(let i = 1; i <= 10; i++){

                if(i <= 3){

                    let random = Math.floor(Math.random() * letters.length);

                    code = code + letters.charAt(random);

                } else {

                    let random = Math.floor(Math.random() * number.length);

                    code = code + number.charAt(random);

                }

            }

            localStorage.setItem("code", code);

        }

    }, []);

    // Cryptography

    async function textToArrayBuffer(text){

        const encoder = new TextEncoder();

        return encoder.encode(text);

    }

    function arrayBufferToBase64(buffer){

        return btoa(String.fromCharCode(...new Uint8Array(buffer)));

    }

    function base64ToArrayBuffer(base64){

        const binaryString = atob(base64);

        const bytes = new Uint8Array(binaryString.length);

        for (let i = 0; i < binaryString.length; i++){

            bytes[i] = binaryString.charCodeAt(i);

        }

        return bytes.buffer;

    }

    async function generateKey(dateString){

        const encoder = new TextEncoder();

        const secret = "your_constant_secret";  // TO CHANGE - Your secret constant

        const dateKeyMaterial = await crypto.subtle.digest("SHA-256", encoder.encode(dateString + secret));

        return crypto.subtle.importKey(
            "raw",
            dateKeyMaterial,
            { name: "AES-GCM" },
            false,
            ["encrypt", "decrypt"]
        );

    }

    async function encryptString(plainText){

        const date = new Date().toISOString().split('T')[0];

        const key = await generateKey(date);

        const iv = crypto.getRandomValues(new Uint8Array(12));

        const encodedText = await textToArrayBuffer(plainText);

        const encryptedBuffer = await crypto.subtle.encrypt(
            { name: "AES-GCM", iv },
            key,
            encodedText
        );

        return JSON.stringify({
            iv: arrayBufferToBase64(iv),
            data: arrayBufferToBase64(encryptedBuffer),
            date
        });

    }

    const decryptString = useCallback(async (encryptedText) => {

        if(encryptedText){

            const { iv, data, date } = JSON.parse(encryptedText); 
    
            if(!date){

                throw new Error("Missing encryption date in data");

            }
    
            const key = await generateKey(date);
    
            const decryptedBuffer = await crypto.subtle.decrypt(
                { name: "AES-GCM", iv: base64ToArrayBuffer(iv) },
                key,
                base64ToArrayBuffer(data)
            );
    
            return new TextDecoder().decode(decryptedBuffer);

        } else {

            return "";

        }

    }, []);

    const encrypt = async (data)=>{

        const encrypted_username = data.username ? await encryptString(data.username) : "";

        const encrypted_user_id = data.user_id ? await encryptString(data.user_id) : "";

        const encrypted_role = data.role ? await encryptString(data.role) : "";

        const encrypted_token = data.token ? await encryptString(data.token) : "";

        const encrypted_code = data.code ? await encryptString(data.code) : "";

        const encrypted_phone_number = data.phone_number ? await encryptString(data.phone_number) : ""; 

        const obj = {username: encrypted_username, user_id: encrypted_user_id, role: encrypted_role, token: encrypted_token, phone_number: encrypted_phone_number, code: encrypted_code}

        return obj;

    }

    const decrypt = useCallback(async (data) => {

        const decrypted_username = data.username ? await decryptString(data.username) : "";

        const decrypted_user_id = data.user_id ? await decryptString(data.user_id) : "";

        const decrypted_role = data.role ? await decryptString(data.role) : "";

        const decrypted_token = data.token ? await decryptString(data.token) : ""; 

        const decrypted_phone_number = data.phone_number ? await decryptString(data.phone_number) : ""; 

        const decrypted_code = data.code ? await decryptString(data.code) : "";
    
        return { username: decrypted_username, user_id: decrypted_user_id, role: decrypted_role, token: decrypted_token, phone_number: decrypted_phone_number, code: decrypted_code };

    }, [decryptString]);

    // Login

    const loginUser = async (formData) => {

        try {

            const {data} = await Axios.post('classes/login.php', {formData});

            if(data.token){

                let user_code = localStorage.getItem("code");

                const code = user_code + "-" + window.navigator.hardwareConcurrency + "-" + window.navigator.maxTouchPoints;

                data.code = code;

                if(userDB.length === 0){

                    createUser(data);

                } else {

                    updateUser(data);

                }

                return data;

            }

            return {message: data.message};

        } catch(err){

            return {success:0, message:'Server error!'};

        }

    }

    // Saving user to local DB

    const createUser = async (data)=>{

        const encrypted = await encrypt(data);

        await db.user.put({index: 1, username: encrypted.username, user_id: encrypted.user_id, token: encrypted.token, role: encrypted.role, code: encrypted.code});

        setUserDB([{username: encrypted.username, user_id: encrypted.user_id, role: encrypted.role, token: encrypted.token, code: encrypted.code}]);

    }

    // Updating user in local DB

    const updateUser = async (data)=>{

        const encrypted = await encrypt(data);

        await db.user.update(1, {username: encrypted.username, user_id: encrypted.user_id, token: encrypted.token, role: encrypted.role, code: encrypted.code});

        setUserDB([{username: encrypted.username, user_id: encrypted.user_id, role: encrypted.role, token: encrypted.token, code: encrypted.code}]);

    }

    // Getting user from local DB

    const [userDB, setUserDB] = useState([]);

    useEffect(() => {

        db.user.toArray().then(function(result){

            if(result.length > 0){

                setUserDB(result);

            } else {

                setUser({username: '', user_id: '', role: 'none'});

            }

        });

    },[]);

    // Logging in

    useEffect(() => {

        if(userDB.length > 0){

            const data = userDB[0];

            Axios.options('auth/getUser.php', { timeout: 1500 }).then(function(){

                logOnline(data);

            }).catch((error) => {

                console.log(error);

                setUser({username: '', user_id: '', role: 'none'});

            });

        }

        const logOnline = async (data) => {

            const loginMethod = process.env.REACT_APP_LOGIN_METHOD;

            const decrypted_data = await decrypt(data);

            let stored_code = localStorage.getItem("code");

            const current_code = stored_code + "-" + window.navigator.hardwareConcurrency + "-" + window.navigator.maxTouchPoints;

            if(decrypted_data.code === current_code){

                const loginToken = decrypted_data.token;

                Axios.defaults.headers.common['Authorization'] = 'Bearer ' + loginToken;

                Axios.defaults.headers.common['Method'] = loginMethod;

                if(loginToken){

                    const {data} = await Axios.get('auth/getUser.php');

                    if(data.success && data.user){

                        let userData = data.user[0];

                        let role = userData.role ? userData.role : "user";

                        setUser({username: userData.username, user_id: userData.user_id, role: role});

                        return;

                    } else if(data.success === 0 && data.message === "User not found"){

                        setUser({username: '', user_id: '', role: 'none'});

                    } else {

                        setUser({username: '', user_id: '', role: 'none'});

                    }

                } else {

                    setUser({username: '', user_id: '', role: 'none'});

                }

            } else {

                setUser({username: '', user_id: '', role: 'none'});

            }

        }

    },[decrypt, userDB]);

    // Verification by phone

    const sendOTP = async (formData) => {

        try {

            const {data} = await Axios.post('classes/login.php', {formData});

            if(data.success){

                return data;

            }

            if(data.message){

                return data;

            }

            return { message: 'Unknown error occured. Please try again later.' };

        } catch(err) {

            return { message: 'Unknown error occured. Please try again later.' };

        }

    }

    const verifyOTPCode = async (formData) => {

        try {

            const {data} = await Axios.post('classes/login.php', {formData});

            if(data.success && data.token){

                let user_code = localStorage.getItem("code");

                const code = user_code + "-" + window.navigator.hardwareConcurrency + "-" + window.navigator.maxTouchPoints;

                data.code = code;

                if(userDB.length === 0){

                    createUser(data);    

                } else {

                    updateUser(data);

                }

                return data;

            }

            if(data.message){

                return data;

            }

            return { message: 'Unknown error occured. Please try again later.' };

        } catch(err) {

            return { message: 'Unknown error occured. Please try again later.' };

        }

    }

    // Logout

    const logout = () => {
        db.user.clear();
        setUser(null);
        window.location.reload(true);
    }

    return (
        <UserContext.Provider value={{loginUser, sendOTP, verifyOTPCode, user:theUser, logout}}>
            {children}
        </UserContext.Provider>
    );

}

export default UserContextProvider;