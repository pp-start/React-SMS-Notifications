declare module "@fontsource/roboto";

// ## User context and login

// # Mail method

// User form

declare type FormFields = {
  usernameEmail: string;
  password: string;
  emailRemind: string;
  reset1: string;
  reset2: string;
  usernameRegister: string;
  emailRegister: string;
  passwordRegister1: string;
  passwordRegister2: string;
  policyAcceptation: boolean;
};

declare type LoginData = Partial<FormFields> & {
  //handler: string;
  //requestType: string;
  usernameCheck?: string;
  userId?: string;
  auth?: string;
  authReg?: string;
  reactivationData?: ReactivationData;
};

declare type ReactivationData = {
  userId?: string;
  usernameEmail?: string;
};

declare type PasswordResetData = {
  //requestType: string;
  userId: string;
  auth: string;
  newPassword: string;
};

// # Phone method

declare type PhoneFormFields = {
  //handler?: string;
  //requestType?: string;
  phoneNumber: string;
  policyAcceptation: boolean;
  oneTimePassword: string;
  verificationCode: string;
};

// Response

declare type LoginResponse = {
  username?: string;
  email?: string;
  userId?: string;
  role?: string;
  message?: string;
  redirect?: boolean;
  resend?: boolean;
  success?: boolean;
  token?: string;
};

// User

declare type UserContextType = {
  user: User | null;
  setUser: React.Dispatch<React.SetStateAction<User | null>>;
  sendData: (formFields: LoginData, requestType: string) => Promise<LoginResponse>;
  sendOTP: (formFields: PhoneFormFields, resend: boolean) => Promise<UserData>;
  verifyOTPCode: (formFields: PhoneFormFields) => Promise<UserData>;
  logout: () => void;
  isOnline: boolean;
  isLocalhost: boolean;
};

declare type User = {
  username: string;
  userId: string;
  role: string;
};

declare type UserData = Partial<User> & {
  message?: string;
  token?: string;
  code?: string;
  redirect?: boolean;
  return?: boolean;
  success?: boolean;
  time?: string;
};

// Modal

declare type Modal = {
  show: boolean;
  info: boolean;
  error: boolean;
};

type ModalType = "info" | "error";

// OTP

interface OTPCredential extends Credential {
  code: string;
}

interface OTPCredentialRequestOptions {
  transport: ("sms" | "email")[];
}

interface CredentialRequestOptions {
  otp?: OTPCredentialRequestOptions;
}
