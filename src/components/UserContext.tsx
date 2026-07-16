import { useState, useEffect, useCallback, type ReactNode } from "react";
import { Axios, UserContext, isLocalhost } from "./Context";
import axios from "axios";
import { db } from "./Db";

type EncryptedPayload = {
  iv: string;
  data: string;
  date: string;
};

export const UserContextProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);

  const generateCode = useCallback((): string => {
    const letters: string = "ABCDEFGHIJKLMNOPRSTUWXYZ";
    const number: string = "0123456789";
    let code: string = "";

    for (let i = 1; i <= 10; i++) {
      if (i <= 3) {
        const random: number = Math.floor(Math.random() * letters.length);
        code = code + letters.charAt(random);
      } else {
        const random: number = Math.floor(Math.random() * number.length);
        code = code + number.charAt(random);
      }
    }

    localStorage.setItem("code", code);

    return code;
  }, []);

  useEffect(() => {
    const code: string | null = localStorage.getItem("code");

    if (!code) {
      generateCode();
    }
  }, [generateCode]);

  // Encrypting

  const encrypt = async (data: UserData): Promise<UserData> => {
    const encryptedUsername: string = data.username
      ? await encryptString(data.username)
      : "";

    const encryptedUserId: string = data.userId
      ? await encryptString(data.userId)
      : "";

    const encryptedRole: string = data.role
      ? await encryptString(data.role)
      : "";

    const encryptedToken: string = data.token
      ? await encryptString(data.token)
      : "";

    const encryptedCode: string = data.code
      ? await encryptString(data.code)
      : "";

    return {
      username: encryptedUsername,
      userId: encryptedUserId,
      role: encryptedRole,
      token: encryptedToken,
      code: encryptedCode,
    };
  };

  async function encryptString(text: string): Promise<string> {
    const date: string = new Date().toISOString().split("T")[0];
    const key: CryptoKey = await generateKey(date);
    const iv: Uint8Array<ArrayBuffer> = crypto.getRandomValues(
      new Uint8Array(12),
    );

    const encodedText: Uint8Array<ArrayBuffer> = new TextEncoder().encode(text);
    const encryptedBuffer: ArrayBuffer = await crypto.subtle.encrypt(
      { name: "AES-GCM", iv },
      key,
      encodedText,
    );

    return JSON.stringify({
      iv: arrayBufferToBase64(iv),
      data: arrayBufferToBase64(encryptedBuffer),
      date,
    });
  }

  async function generateKey(date: string): Promise<CryptoKey> {
    const encoder: TextEncoder = new TextEncoder();
    const secret: string = "auth_app";
    const dateKeyMaterial: ArrayBuffer = await crypto.subtle.digest(
      "SHA-256",
      encoder.encode(date + secret),
    );

    return crypto.subtle.importKey(
      "raw",
      dateKeyMaterial,
      { name: "AES-GCM" },
      false,
      ["encrypt", "decrypt"],
    );
  }

  function arrayBufferToBase64(buffer: Uint8Array | ArrayBuffer): string {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
  }

  // Decrypting

  const decryptString = useCallback(
    async (encryptedText: string): Promise<string> => {
      if (encryptedText) {
        const parsed: unknown = JSON.parse(encryptedText);

        if (isEncryptedPayload(parsed)) {
          const { iv, data, date }: { iv: string; data: string; date: string } =
            parsed;
          const key: CryptoKey = await generateKey(date);
          const decryptedBuffer: ArrayBuffer = await crypto.subtle.decrypt(
            { name: "AES-GCM", iv: base64ToArrayBuffer(iv) },
            key,
            base64ToArrayBuffer(data),
          );

          return new TextDecoder().decode(decryptedBuffer);
        } else {
          throw new Error("Invalid encrypted payload format");
        }
      } else {
        return "";
      }

      function isEncryptedPayload(obj: unknown): obj is EncryptedPayload {
        return (
          typeof obj === "object" &&
          obj !== null &&
          typeof (obj as Record<string, unknown>).iv === "string" &&
          typeof (obj as Record<string, unknown>).data === "string" &&
          typeof (obj as Record<string, unknown>).date === "string"
        );
      }
    },
    [],
  );

  const decrypt = useCallback(
    async (data: UserData): Promise<UserData> => {
      const decryptedUsername: string = data.username
        ? await decryptString(data.username)
        : "";

      const decryptedUserId: string = data.userId
        ? await decryptString(data.userId)
        : "";

      const decryptedRole: string = data.role
        ? await decryptString(data.role)
        : "";

      const decryptedToken: string = data.token
        ? await decryptString(data.token)
        : "";

      const decryptedCode: string = data.code
        ? await decryptString(data.code)
        : "";

      return {
        username: decryptedUsername,
        userId: decryptedUserId,
        role: decryptedRole,
        token: decryptedToken,
        code: decryptedCode,
      };
    },
    [decryptString],
  );

  function base64ToArrayBuffer(base64: string): ArrayBuffer {
    const binaryString = atob(base64);
    const bytes = new Uint8Array(binaryString.length);

    for (let i = 0; i < binaryString.length; i++) {
      bytes[i] = binaryString.charCodeAt(i);
    }

    return bytes.buffer;
  }

  // LOGIN - BY MAIL

  const sendData = async (formFields: LoginData, requestType: string): Promise<LoginResponse> => {
    try {
      const handler = 'login';
      const { data }: { data: LoginResponse } = await Axios.post(
        "classes/handler.php",
        { 
          formFields,
          handler,
          requestType
        },
      );

      if (data.token) {
        handleUser(data);
      }

      if (data.message) {
        return data;
      }

      return { message: "Unknown error has occured. Please try again later." };
    } catch (err) {
      console.warn(err);

      return { message: "Unknown error has occured. Please try again later." };
    }
  };

  // Saving user

  const handleUser = async (data: UserData): Promise<void> => {
    const code: string = localStorage.getItem("code") ?? generateCode();

    data.code =
      code +
      "-" +
      window.navigator.hardwareConcurrency +
      "-" +
      window.navigator.maxTouchPoints;

    const encrypted: UserData = await encrypt(data);

    if (userDB.length === 0) {
      await db.user.put({ index: 1, ...encrypted });
    } else {
      await db.user.update(1, { ...encrypted });
    }

    setUserDB([{ ...encrypted }]);
  };

  // Getting user

  const [userDB, setUserDB] = useState<UserData[]>([]);

  useEffect(() => {
    db.user.toArray().then(function (result) {
      if (result.length > 0) {
        setUserDB(result);
      } else {
        setUser({ username: "", userId: "", role: "none" });
      }
    });
  }, []);

  // Logging in

  useEffect(() => {
    if (userDB.length > 0) {
      const data: UserData = userDB[0];

      Axios.options("auth/getUser.php", { timeout: 1500 })
        .then(function () {
          logOnline(data);
        })
        .catch((error) => {
          console.log(error);

          setUser({ username: "", userId: "", role: "none" });
        });
    }

    const logOnline = async (data: UserData): Promise<void> => {
      const decryptedData: UserData = await decrypt(data);
      const storedCode: string | null = localStorage.getItem("code");
      const currentCode =
        storedCode +
        "-" +
        window.navigator.hardwareConcurrency +
        "-" +
        window.navigator.maxTouchPoints;

      const emptyUser: User = { username: "", userId: "", role: "none" };

      

      if (decryptedData.code === currentCode) {
        const loginToken: string | undefined = decryptedData.token;

        if (loginToken) {
          Axios.defaults.headers.common["Authorization"] =
            "Bearer " + loginToken;

          Axios.defaults.headers.common["Method"] =
            import.meta.env.VITE_APP_LOGIN_METHOD;

          const { data }: { data: unknown } =
            await Axios.get("auth/getUser.php");

          if (
            typeof data === "object" &&
            data !== null &&
            "success" in data &&
            "user" in data
          ) {
            
            const userData = data.user as User;
            setUser(userData);
          } else {
            setUser(emptyUser);
          }
        } else {
          setUser(emptyUser);
        }
      } else {
        setUser(emptyUser);
      }
    };
  }, [decrypt, userDB]);

  // LOGIN - BY PHONE

  // Phone number verification

  const sendOTP = async (
    formFields: PhoneFormFields,
    resend: boolean,
  ): Promise<UserData> => {
    try {
      const handler = "login";
      const requestType = "generateOtp";

      const { data } = await Axios.post("classes/handler.php", {
        formFields,
        handler,
        requestType,
        resend,
      });

      if (data.success || data.message) {
        return data;
      }

      return { message: "Unknown error has occured. Please try again later." };
    } catch (err) {
      console.warn(err);

      return { message: "Unknown error has occured. Please try again later." };
    }
  };

  // Verification of one time password

  const verifyOTPCode = async (formFields: PhoneFormFields) => {
    try {
      const handler = "login";
      const requestType = "verifyOtp";

      const { data } = await Axios.post("classes/handler.php", { 
        handler,
        requestType,
        formFields
      });

      if (data.success && data.token) {
        handleUser(data);
        return data;
      }

      if (data.message) {
        return data;
      }

      return { message: "Unknown error has occured. Please try again later." };
    } catch (err) {
      console.warn(err);

      return { message: "Unknown error has occured. Please try again later." };
    }
  };

  // Logging out

  const logout = (): void => {
    db.user.clear();
    setUser(null);
    window.location.reload();
  };

  // Check if app is online

  const [isOnline, setIsOnline] = useState<boolean>(navigator.onLine);

  useEffect(() => {
    const interval = setInterval(() => {
      axios
        .get("https://pp-start.pl/php/connection.php", { timeout: 1000 })
        .then(function (response) {
          if (response.status === 204) {
            setIsOnline(true);
          } else {
            setTimeout(reCheckConnection, 5000);
          }
        })
        .catch((error) => {
          console.log(error);

          setTimeout(reCheckConnection, 5000);
        });
    }, 1000000); // ZMIENIĆ NA 10000

    function reCheckConnection(): void {
      axios
        .get("https://pp-start.pl/php/connection.php", { timeout: 1000 })
        .then(function (response) {
          if (response.status === 204) {
            setIsOnline(true);
          } else {
            setIsOnline(false);
          }
        })
        .catch((error) => {
          console.log(error);

          setIsOnline(false);
        });
    }

    const handleOnline = (): void => setIsOnline(true);
    const handleOffline = (): void => setIsOnline(false);

    window.addEventListener("online", handleOnline);
    window.addEventListener("offline", handleOffline);

    return () => {
      clearInterval(interval);

      window.removeEventListener("online", handleOnline);
      window.removeEventListener("offline", handleOffline);
    };
  }, []);

  return (
    <UserContext.Provider
      value={{
        user: user,
        setUser,
        sendData,
        sendOTP,
        verifyOTPCode,
        logout,
        isOnline,
        isLocalhost,
      }}
    >
      {children}
    </UserContext.Provider>
  );
};

export default UserContextProvider;
