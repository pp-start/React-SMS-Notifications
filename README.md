# 📦 React SMS OTP & Google Firebase notifications

React App that can be used as a solid starting framework for your custom App. It allows user login either with email or phone number.

## 🚀 Features

- login method choice - either by email or phone number

- phone number verification by automatic retrieval of One Time Password send via SMS

- notification system working through Google Firebase

## 🛠️ Tech Stack

**Frontend:** React

**Backend:** PHP

**Database:** MySQL

## 🧑‍💻 Getting Started

### Prerequisites

- Node.js & npm installed

### Installation

```bash
git clone git@github.com:pp-start/React-SMS-Notifications.git

cd React-SMS-Notifications
npm install

cd public/php
composer install
```

### MySQL database setup

```sql
CREATE TABLE `users_mail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(8) NOT NULL,
  `username` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(512) NOT NULL,
  `role` varchar(8) NOT NULL,
  `verified` tinyint(1) NOT NULL,
  `phone_number` varchar(9) DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB

CREATE TABLE `users_phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(8) NOT NULL,
  `phone_number` varchar(9) NOT NULL,
  `email` varchar(128) DEFAULT NULL,
  `firebase_token` varchar(512) DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `one_time_password` varchar(6) DEFAULT NULL,
  `otp_time` datetime DEFAULT NULL,
  `created_on` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB
```

### 💻 Running the App

```bash
npm start
```

### 🧱 Build

```bash
npm run build
```

## ⚙️ Environment Variables and 🔥 Firebase Setup

- `.env` in the project root directory:

```ini
#App login method - can be either mail or phone

REACT_APP_LOGIN_METHOD= 

# Google Firabase credentials for notification system

REACT_APP_VAPID_KEY=
REACT_APP_FIREBASE_API_KEY=
REACT_APP_FIREBASE_AUTH_DOMAIN=
REACT_APP_FIREBASE_PROJECT_ID=
REACT_APP_FIREBASE_STORAGE_BUCKET=
REACT_APP_FIREBASE_MESSAGING_SENDER_ID=
REACT_APP_FIREBASE_APP_ID=
REACT_APP_FIREBASE_MEASUREMENT_ID=
```

### navigate to `public`

- firebase-messaging-sw.js

```js
// Change this with your data received from Google Firebase

firebase.initializeApp({
    apiKey: 'YOUR_API_KEY',
    authDomain: 'YOUR_AUTH_DOMAIN',
    projectId: 'YOUR_PROJECT_ID',
    storageBucket: 'YOUR_STORAGE_BUCKET',
    messagingSenderId: 'YOUR_MESSAGING_SENDER_ID',
    appId: 'YOUR_APP_ID',  
});
```

### navigate to `public/php/classes`

- `notifications.php`

```php
// Change this with your data received from Google Firebase

$project_id = 'YOUR_FIREBASE_PROJECT_ID';

putenv('GOOGLE_APPLICATION_CREDENTIALS=config/your_firebase_credentials.json');
```

### navigate to `public/php/classes/config`

- `config.ini`:

```ini
# Credentials to connect with MySQL database

host = your_host
user = your_username
pass = your_password
db = your_db
```

- Copy your Google Firabase credentials JSON file:

```json
// Template file

{
  "type": "service_account",
  "project_id": "YOUR_PROJECT_ID",
  "private_key_id": "YOUR_PRIVATE_KEY_ID",
  "private_key": "-----BEGIN PRIVATE KEY-----YOUR_PRIVATE_KEY-----END PRIVATE KEY-----\n",
  "client_email": "YOUR_CLIENT_EMAIL",
  "client_id": "YOUR_CLIENT_ID",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "YOUR_CLIENT_X509_CERT_URL",
  "universe_domain": "googleapis.com"
}
```

### navigate to `src/components`

- `UserContext.js`

```js
// Local path to match your disk location when App is deployed on localhost
// Example below - as if the App was inside directory 'React-SMS-Notifications' in main html directory

export const Axios = axios.create({

    baseURL: isLocalhost ? 'React-SMS-Notifications/public/php/' : 'php/', 
    
});
```

## 🧾 License

GNU GENERAL PUBLIC LICENSE - Version 3

## 🙋‍♂️ Contact

- You can reach me on [LinkedIn](https://www.linkedin.com/in/pawel-pokrywka-348018251/)

- Paweł Pokrywka - [Github](https://github.com/pp-start) 