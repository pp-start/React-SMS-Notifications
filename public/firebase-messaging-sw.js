importScripts(
  "https://www.gstatic.com/firebasejs/10.1.0/firebase-app-compat.js",
);
importScripts(
  "https://www.gstatic.com/firebasejs/10.1.0/firebase-messaging-compat.js",
);

firebase.initializeApp({
  apiKey: "your_apiKey",
  authDomain: "your_authDomain",
  projectId: "your_projectId",
  storageBucket: "your_storageBucket",
  messagingSenderId: "your_messagingSenderId",
  appId: "your_appId",
});

const messaging = firebase.messaging();

self.addEventListener("push", (event) => {
  const notificationData = event.data.json();

  const notification = notificationData.data;

  if (notification) {
    const title = notification.title;

    const body = notification.body;

    const icon = notification.icon;

    const options = {
      body: body,
      icon: icon,
      badge: "/badge-icon.png",
      vibrate: [200, 100, 200],
      //data: notificationData,
    };

    event.waitUntil(self.registration.showNotification(title, options));
  } else {
    const options = {
      body: "empty",
      icon: "/logo192.png",
      badge: "/badge-icon.png",
      vibrate: [200, 100, 200],
    };

    event.waitUntil(self.registration.showNotification("empty", options));
  }
});
